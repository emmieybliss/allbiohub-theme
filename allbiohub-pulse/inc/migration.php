<?php
/**
 * AllBioHub → Migration & Health.
 *
 * On activation Pulse only DETECTS. Every change is a separate step an admin
 * approves; each applied step is logged with what is needed to undo it.
 * Nothing here deletes or edits existing posts, pages, media, terms, users,
 * comments, SEO data, Foxiz settings or widgets.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_MIG_REPORT = 'allbiohub_migration_report';
const PULSE_MIG_LOG    = 'allbiohub_migration_log';

/* ---------- Activation: detect only ---------- */

add_action( 'after_switch_theme', 'pulse_on_activate', 20, 2 );
function pulse_on_activate( $old_name = '', $old_theme = null ) {
	if ( ! get_option( 'allbiohub_activation' ) ) {
		update_option(
			'allbiohub_activation',
			array(
				'time'           => time(),
				'previous_theme' => $old_theme instanceof WP_Theme ? $old_theme->get_stylesheet() : (string) $old_name,
				'permalinks'     => get_option( 'permalink_structure' ),
			),
			false
		);
	}
	update_option( PULSE_MIG_REPORT, pulse_detect(), false );
}

/**
 * Read-only detection of everything the migration map covers.
 */
function pulse_detect() {
	global $wpdb;
	$posts = wp_count_posts( 'post' );
	$r     = array(
		'time'      => time(),
		'counts'    => array(
			'posts'      => (int) $posts->publish,
			'pages'      => (int) wp_count_posts( 'page' )->publish,
			'categories' => (int) wp_count_terms( array( 'taxonomy' => 'category', 'hide_empty' => false ) ),
			'tags'       => (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ),
			'media'      => (int) array_sum( (array) wp_count_attachments() ),
			'authors'    => count( get_users( array( 'has_published_posts' => array( 'post' ), 'fields' => 'ID' ) ) ),
			'videos'     => (int) wp_count_posts( 'abh_video' )->publish,
		),
		'no_image'  => array_map( 'intval', $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id' WHERE p.post_type = 'post' AND p.post_status = 'publish' AND m.meta_id IS NULL ORDER BY p.post_date DESC LIMIT 200" ) ),
		'broken_img' => array_map( 'intval', $wpdb->get_col( "SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id' LEFT JOIN {$wpdb->posts} a ON a.ID = m.meta_value AND a.post_type = 'attachment' WHERE p.post_type = 'post' AND p.post_status = 'publish' AND a.ID IS NULL LIMIT 200" ) ),
		'empty_tags' => (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ) - (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true ) ),
		'seo'       => pulse_seo_plugin(),
		'menus'     => wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' ),
		'foxiz_ads' => pulse_detect_foxiz_ads(),
		'videos'    => pulse_detect_video_posts(),
	);
	$sections = array();
	foreach ( pulse_section_map() as $key => $row ) {
		$terms             = pulse_section_terms( $key );
		$sections[ $key ]  = count( $terms['cats'] ) + count( $terms['tags'] );
	}
	$r['sections'] = $sections;
	return $r;
}

/**
 * Foxiz Core ad-script widgets in Foxiz sidebars (read-only).
 *
 * @return array<string,array{sidebar:string,widget:string,code:string}>
 */
function pulse_detect_foxiz_ads() {
	$map     = array(
		'foxiz_header_ad'       => 'top',
		'foxiz_entry_top'       => 'article_top',
		'foxiz_entry_bottom'    => 'article_bottom',
		'foxiz_sidebar_default' => 'sidebar',
	);
	$sidebars = wp_get_sidebars_widgets();
	$out      = array();
	foreach ( (array) $sidebars as $sidebar => $widgets ) {
		foreach ( (array) $widgets as $widget_id ) {
			if ( ! preg_match( '/^(.+)-(\d+)$/', (string) $widget_id, $m ) || false === strpos( $m[1], 'ad' ) ) {
				continue;
			}
			$opt  = get_option( 'widget_' . $m[1], array() );
			$inst = isset( $opt[ (int) $m[2] ] ) && is_array( $opt[ (int) $m[2] ] ) ? $opt[ (int) $m[2] ] : array();
			$code = '';
			foreach ( $inst as $field ) {
				if ( is_string( $field ) && ( false !== stripos( $field, '<' ) ) && strlen( $field ) > strlen( $code ) ) {
					$code = $field;
				}
			}
			$out[ $widget_id ] = array(
				'sidebar' => (string) $sidebar,
				'widget'  => (string) $widget_id,
				'code'    => $code,
				'target'  => isset( $map[ $sidebar ] ) ? $map[ $sidebar ] : '',
			);
		}
	}
	return $out;
}

/**
 * Posts containing videos: which ones Pulse can read, which need review.
 */
function pulse_detect_video_posts() {
	global $wpdb;
	$rows = $wpdb->get_col(
		"SELECT ID FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status = 'publish'
		 AND ( post_content LIKE '%youtube.com/%' OR post_content LIKE '%youtu.be/%' OR post_content LIKE '%vimeo.com/%' OR post_content LIKE '%[video%' OR post_content LIKE '%<video%' OR post_content LIKE '%<iframe%' )
		 ORDER BY post_date DESC LIMIT 500"
	);
	$ok     = array();
	$review = array();
	foreach ( $rows as $id ) {
		$data = pulse_find_video_in_content( get_post_field( 'post_content', (int) $id ) );
		if ( $data ) {
			$ok[] = (int) $id;
		} elseif ( false !== stripos( get_post_field( 'post_content', (int) $id ), 'video' ) ) {
			$review[] = (int) $id;
		}
	}
	$migrated = array_map( 'intval', $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_abh_video_source_post'" ) );
	return array( 'found' => $ok, 'review' => $review, 'migrated' => $migrated );
}

/* ---------- Log + undo ---------- */

function pulse_log_add( $step, $summary, $undo ) {
	$log   = (array) get_option( PULSE_MIG_LOG, array() );
	$id    = wp_generate_uuid4();
	$log[] = array(
		'id'      => $id,
		'step'    => $step,
		'summary' => $summary,
		'time'    => time(),
		'user'    => get_current_user_id(),
		'undo'    => $undo,
		'undone'  => 0,
	);
	update_option( PULSE_MIG_LOG, $log, false );
	return $id;
}

function pulse_log_undo( $entry_id ) {
	$log = (array) get_option( PULSE_MIG_LOG, array() );
	foreach ( $log as &$entry ) {
		if ( $entry['id'] !== $entry_id || $entry['undone'] ) {
			continue;
		}
		$u = $entry['undo'];
		switch ( $entry['step'] ) {
			case 'menus':
				set_theme_mod( 'nav_menu_locations', (array) $u['previous'] );
				break;
			case 'foxiz_ads':
				$ads = get_option( PULSE_ADS, array() );
				foreach ( (array) $u['previous'] as $slot => $prev ) {
					if ( null === $prev ) {
						unset( $ads[ $slot ] );
					} else {
						$ads[ $slot ] = $prev;
					}
				}
				update_option( PULSE_ADS, $ads );
				break;
			case 'categories':
				foreach ( (array) $u['created'] as $term_id ) {
					$term = get_term( $term_id, 'category' );
					// Only remove a category Pulse created, and only if nothing is in it.
					if ( $term && ! is_wp_error( $term ) && 0 === (int) $term->count ) {
						wp_delete_term( $term_id, 'category' );
					}
				}
				break;
			case 'assign':
				foreach ( (array) $u['pairs'] as $pair ) {
					wp_remove_object_terms( (int) $pair[0], (int) $pair[1], 'category' );
				}
				break;
			case 'views':
				foreach ( (array) $u['posts'] as $post_id ) {
					delete_post_meta( (int) $post_id, PULSE_VIEWS_KEY );
				}
				break;
			case 'videos':
				foreach ( (array) $u['created'] as $post_id ) {
					if ( 'abh_video' === get_post_type( $post_id ) ) {
						wp_trash_post( $post_id );
					}
				}
				break;
		}
		$entry['undone'] = time();
	}
	unset( $entry );
	update_option( PULSE_MIG_LOG, $log, false );
}

/* ---------- Steps ---------- */

/**
 * Suggested menu → location mapping, matched by menu name.
 */
function pulse_menu_suggestions() {
	$want = array(
		'primary'      => array( 'main menu' ),
		'mobile'       => array( 'main menu', 'mobile menu' ),
		'mobile-quick' => array( 'secondary menu', 'mobile quick' ),
		'top-bar'      => array( 'top bar menu', 'top bar' ),
		'footer-1'     => array( 'footer menu' ),
		'footer-2'     => array( 'footer menu 2' ),
		'social'       => array( 'social menu', 'social' ),
	);
	$menus = wp_get_nav_menus();
	$out   = array();
	foreach ( $want as $loc => $names ) {
		foreach ( $names as $name ) {
			foreach ( $menus as $menu ) {
				if ( 0 === strcasecmp( trim( $menu->name ), $name ) ) {
					$out[ $loc ] = (int) $menu->term_id;
					break 2;
				}
			}
		}
	}
	return $out;
}

function pulse_step_menus() {
	$prev = (array) get_theme_mod( 'nav_menu_locations', array() );
	$new  = array_merge( $prev, pulse_menu_suggestions() );
	set_theme_mod( 'nav_menu_locations', $new );
	return pulse_log_add( 'menus', sprintf( /* translators: %d: count */ __( 'Assigned %d menus to Pulse locations', 'allbiohub-pulse' ), count( pulse_menu_suggestions() ) ), array( 'previous' => $prev ) );
}

function pulse_step_foxiz_ads() {
	$found  = pulse_detect_foxiz_ads();
	$ads    = get_option( PULSE_ADS, array() );
	$prev   = array();
	$copied = 0;
	foreach ( $found as $row ) {
		if ( ! $row['target'] || '' === trim( $row['code'] ) || isset( $prev[ $row['target'] ] ) ) {
			continue;
		}
		$prev[ $row['target'] ] = isset( $ads[ $row['target'] ] ) ? $ads[ $row['target'] ] : null;
		$ads[ $row['target'] ]  = array_merge(
			pulse_ad_defaults(),
			array(
				'enabled'  => 1,
				'type'     => 'script',
				'html'     => $row['code'],
				'campaign' => sprintf( 'Copied from Foxiz %s', $row['widget'] ),
			)
		);
		++$copied;
	}
	update_option( PULSE_ADS, $ads );
	return pulse_log_add( 'foxiz_ads', sprintf( /* translators: %d: count */ __( 'Copied %d Foxiz ad widgets into Pulse placements (originals untouched)', 'allbiohub-pulse' ), $copied ), array( 'previous' => $prev ) );
}

function pulse_step_categories() {
	$created = array();
	foreach ( array( 'technology' => 'Technology', 'lifestyle' => 'Lifestyle' ) as $slug => $name ) {
		if ( ! get_term_by( 'slug', $slug, 'category' ) ) {
			$t = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
			if ( ! is_wp_error( $t ) ) {
				$created[] = (int) $t['term_id'];
			}
		}
	}
	return pulse_log_add( 'categories', sprintf( /* translators: %d: count */ __( 'Created %d new categories (Technology / Lifestyle)', 'allbiohub-pulse' ), count( $created ) ), array( 'created' => $created ) );
}

/**
 * Around the Web posts that look like Technology or Lifestyle, by tags/title.
 */
function pulse_category_suggestions() {
	$src = get_term_by( 'slug', 'around-the-web', 'category' );
	if ( ! $src ) {
		return array();
	}
	$rules = array(
		'technology' => '/\b(tech|technology|ai|artificial intelligence|chatgpt|openai|google|meta|whatsapp|apple|iphone|android|samsung|software|app|startup|vpn|hosting|domain|crypto|internet|gadget|cyber|vibe ?code)\b/i',
		'lifestyle'  => '/\b(lifestyle|fashion|beauty|health|fitness|food|recipe|travel|relationship|wedding|home|style|wellness|diet)\b/i',
	);
	$q   = new WP_Query( array( 'post_type' => 'post', 'cat' => $src->term_id, 'posts_per_page' => 300, 'no_found_rows' => true ) );
	$out = array( 'technology' => array(), 'lifestyle' => array() );
	foreach ( $q->posts as $p ) {
		$hay = $p->post_title . ' ' . implode( ' ', wp_get_post_terms( $p->ID, 'post_tag', array( 'fields' => 'names' ) ) );
		foreach ( $rules as $target => $re ) {
			$t = get_term_by( 'slug', $target, 'category' );
			if ( preg_match( $re, $hay ) && ! ( $t && has_term( $t->term_id, 'category', $p ) ) ) {
				$out[ $target ][] = $p->ID;
			}
		}
	}
	return $out;
}

function pulse_step_assign( $target, array $post_ids ) {
	$term = get_term_by( 'slug', $target, 'category' );
	if ( ! $term ) {
		return '';
	}
	$pairs = array();
	foreach ( $post_ids as $id ) {
		$id = absint( $id );
		if ( 'post' === get_post_type( $id ) && ! has_term( $term->term_id, 'category', $id ) ) {
			// Append: existing categories (and the Rank Math primary category) are kept.
			wp_set_post_categories( $id, array( $term->term_id ), true );
			$pairs[] = array( $id, (int) $term->term_id );
		}
	}
	return pulse_log_add( 'assign', sprintf( /* translators: 1: count, 2: category */ __( 'Added %1$d posts to %2$s (existing categories kept)', 'allbiohub-pulse' ), count( $pairs ), $term->name ), array( 'pairs' => $pairs ) );
}

function pulse_step_views() {
	global $wpdb;
	$ids    = $wpdb->get_col( "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_ahcpro_total_views','tie_views')" );
	$seeded = array();
	foreach ( $ids as $id ) {
		if ( '' === get_post_meta( (int) $id, PULSE_VIEWS_KEY, true ) ) {
			update_post_meta( (int) $id, PULSE_VIEWS_KEY, pulse_views( (int) $id ) );
			$seeded[] = (int) $id;
		}
	}
	return pulse_log_add( 'views', sprintf( /* translators: %d: count */ __( 'Copied legacy view counts for %d posts (old counters untouched)', 'allbiohub-pulse' ), count( $seeded ) ), array( 'posts' => $seeded ) );
}

function pulse_step_videos() {
	$det     = pulse_detect_video_posts();
	$created = array();
	foreach ( array_diff( $det['found'], $det['migrated'] ) as $post_id ) {
		$data = pulse_find_video_in_content( get_post_field( 'post_content', $post_id ) );
		if ( ! $data || 'self' === $data['provider'] && empty( $data['file_url'] ) ) {
			continue;
		}
		$new = wp_insert_post(
			array(
				'post_type'    => 'abh_video',
				'post_status'  => 'draft',
				'post_title'   => get_the_title( $post_id ),
				'post_excerpt' => pulse_excerpt( $post_id, 40 ),
				'post_content' => sprintf( '<p>%s <a href="%s">%s</a></p>', esc_html__( 'Read the full story:', 'allbiohub-pulse' ), esc_url( get_permalink( $post_id ) ), esc_html( get_the_title( $post_id ) ) ),
				'post_author'  => (int) get_post_field( 'post_author', $post_id ),
			),
			true
		);
		if ( is_wp_error( $new ) ) {
			continue;
		}
		$meta = array_merge(
			pulse_video_defaults(),
			array(
				'source'      => 'self' === $data['provider'] ? 'self' : $data['provider'],
				'url'         => isset( $data['url'] ) ? $data['url'] : '',
				'file'        => 'self' === $data['provider'] ? (int) attachment_url_to_postid( $data['file_url'] ) : 0,
				'home'        => 0,
				'source_post' => $post_id,
			)
		);
		$schema = get_post_meta( $post_id, 'rank_math_schema_VideoObject', true );
		if ( is_array( $schema ) && ! empty( $schema['duration'] ) ) {
			$meta['duration'] = pulse_duration_label( pulse_duration_seconds( $schema['duration'] ) );
		}
		update_post_meta( $new, PULSE_VIDEO_META, $meta );
		update_post_meta( $new, '_abh_video_source_post', $post_id );
		$thumb = pulse_image_id( $post_id );
		if ( $thumb ) {
			set_post_thumbnail( $new, $thumb );
		}
		wp_set_post_categories( $new, wp_get_post_categories( $post_id ) );
		$created[] = (int) $new;
	}
	return pulse_log_add( 'videos', sprintf( /* translators: %d: count */ __( 'Created %d draft AllBioHub Videos from posts containing videos (posts untouched)', 'allbiohub-pulse' ), count( $created ) ), array( 'created' => $created ) );
}

/* ---------- Section map ---------- */

function pulse_sanitize_section_map( $in ) {
	$out = array();
	foreach ( (array) $in as $key => $row ) {
		$key = sanitize_key( $key );
		if ( ! $key || ! is_array( $row ) ) {
			continue;
		}
		$split       = function ( $v ) {
			return array_values( array_filter( array_map( 'sanitize_title', is_array( $v ) ? $v : explode( ',', (string) $v ) ) ) );
		};
		$out[ $key ] = array(
			'label' => sanitize_text_field( isset( $row['label'] ) ? $row['label'] : $key ),
			'cats'  => $split( isset( $row['cats'] ) ? $row['cats'] : '' ),
			'tags'  => $split( isset( $row['tags'] ) ? $row['tags'] : '' ),
		);
	}
	return $out;
}

/* ---------- Admin screen ---------- */

add_action( 'admin_menu', 'pulse_migration_menu', 13 );
function pulse_migration_menu() {
	add_submenu_page( 'allbiohub', __( 'Migration & Health', 'allbiohub-pulse' ), __( 'Migration & Health', 'allbiohub-pulse' ), 'manage_options', 'allbiohub-migration', 'pulse_migration_screen' );
}

add_action( 'admin_post_pulse_migration', 'pulse_migration_action' );
function pulse_migration_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', 403 );
	}
	check_admin_referer( 'pulse_migration' );
	$do = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
	if ( in_array( $do, array( 'menus', 'foxiz_ads', 'categories', 'views', 'videos' ), true ) && empty( $_POST['backup_confirmed'] ) ) {
		wp_safe_redirect( admin_url( 'admin.php?page=allbiohub-migration&need_backup=1' ) );
		exit;
	}
	switch ( $do ) {
		case 'rescan':
			update_option( PULSE_MIG_REPORT, pulse_detect(), false );
			break;
		case 'menus':
			pulse_step_menus();
			break;
		case 'foxiz_ads':
			pulse_step_foxiz_ads();
			break;
		case 'categories':
			pulse_step_categories();
			break;
		case 'views':
			pulse_step_views();
			break;
		case 'videos':
			pulse_step_videos();
			break;
		case 'assign':
			$target = isset( $_POST['target'] ) ? sanitize_key( wp_unslash( $_POST['target'] ) ) : '';
			$ids    = isset( $_POST['posts'] ) ? array_map( 'absint', (array) $_POST['posts'] ) : array();
			if ( $ids && in_array( $target, array( 'technology', 'lifestyle' ), true ) ) {
				pulse_step_assign( $target, $ids );
			}
			break;
		case 'undo':
			pulse_log_undo( isset( $_POST['entry'] ) ? sanitize_text_field( wp_unslash( $_POST['entry'] ) ) : '' );
			break;
		case 'sections':
			$map = isset( $_POST['map'] ) ? (array) wp_unslash( $_POST['map'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			update_option( PULSE_SECTION_MAP, pulse_sanitize_section_map( $map ) );
			break;
	}
	update_option( PULSE_MIG_REPORT, pulse_detect(), false );
	wp_safe_redirect( admin_url( 'admin.php?page=allbiohub-migration&done=' . $do ) );
	exit;
}

add_action( 'admin_post_pulse_migration_csv', 'pulse_migration_csv' );
function pulse_migration_csv() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', 403 );
	}
	check_admin_referer( 'pulse_migration_csv' );
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="allbiohub-migration-report-' . gmdate( 'Ymd' ) . '.csv"' );
	$f = fopen( 'php://output', 'w' );
	fputcsv( $f, array( 'Status', 'Area', 'Item', 'Detail' ) );
	foreach ( pulse_report_rows() as $row ) {
		fputcsv( $f, $row );
	}
	fclose( $f ); // phpcs:ignore
	exit;
}

/**
 * Report rows: Changed / Fixed / Skipped / Needs manual review.
 */
function pulse_report_rows() {
	$r    = (array) get_option( PULSE_MIG_REPORT, array() );
	$rows = array();
	foreach ( (array) get_option( PULSE_MIG_LOG, array() ) as $e ) {
		$rows[] = array( $e['undone'] ? 'Skipped (undone)' : 'Changed', $e['step'], $e['summary'], wp_date( 'Y-m-d H:i', $e['time'] ) );
	}
	foreach ( isset( $r['no_image'] ) ? $r['no_image'] : array() as $id ) {
		$rows[] = array( 'Fixed at display', 'Featured image', get_the_title( $id ), 'Fallback image used; add a featured image when possible' );
	}
	foreach ( isset( $r['broken_img'] ) ? $r['broken_img'] : array() as $id ) {
		$rows[] = array( 'Needs manual review', 'Featured image', get_the_title( $id ), 'Featured image points to a deleted attachment' );
	}
	foreach ( isset( $r['sections'] ) ? $r['sections'] : array() as $key => $n ) {
		if ( ! $n ) {
			$rows[] = array( 'Needs manual review', 'Sections', pulse_section_label( $key ), 'No existing category or tag feeds this section; it is hidden' );
		}
	}
	foreach ( isset( $r['foxiz_ads'] ) ? $r['foxiz_ads'] : array() as $ad ) {
		if ( ! $ad['target'] ) {
			$rows[] = array( 'Needs manual review', 'Ads', $ad['widget'], 'Foxiz ad widget in ' . $ad['sidebar'] . ' has no direct Pulse placement' );
		}
	}
	foreach ( isset( $r['videos']['review'] ) ? $r['videos']['review'] : array() as $id ) {
		$rows[] = array( 'Needs manual review', 'Video', get_the_title( $id ), 'Mentions video but no YouTube/Vimeo/MP4 source could be read' );
	}
	if ( ! empty( $r['empty_tags'] ) ) {
		$rows[] = array( 'Skipped', 'Tags', $r['empty_tags'] . ' empty tags', 'Listed only; never deleted automatically' );
	}
	return $rows;
}

function pulse_mig_status( $ok, $warn = false ) {
	if ( $ok ) {
		return '<span class="pulse-check is-ok">✓ ' . esc_html__( 'Completed', 'allbiohub-pulse' ) . '</span>';
	}
	return $warn ? '<span class="pulse-check is-warn">⚠ ' . esc_html__( 'Needs review', 'allbiohub-pulse' ) . '</span>' : '<span class="pulse-check is-err">✕ ' . esc_html__( 'Error', 'allbiohub-pulse' ) . '</span>';
}

function pulse_migration_screen() {
	$r = get_option( PULSE_MIG_REPORT );
	if ( ! is_array( $r ) ) {
		$r = pulse_detect();
		update_option( PULSE_MIG_REPORT, $r, false );
	}
	$act     = (array) get_option( 'allbiohub_activation', array() );
	$log     = array_reverse( (array) get_option( PULSE_MIG_LOG, array() ) );
	$locs    = (array) get_theme_mod( 'nav_menu_locations', array() );
	$ads_on  = count( array_filter( array_keys( pulse_ad_slots() ), 'pulse_ad_is_active' ) );
	$perm_ok = empty( $act['permalinks'] ) || $act['permalinks'] === get_option( 'permalink_structure' );
	$steps   = wp_list_pluck( array_filter( $log, function ( $e ) { return ! $e['undone']; } ), 'step' );
	$form    = function ( $do, $label, $primary = false, $extra = '' ) {
		printf(
			'<form method="post" action="%s" class="pulse-inline-form"><input type="hidden" name="action" value="pulse_migration"><input type="hidden" name="do" value="%s">%s%s<button class="button %s">%s</button></form>',
			esc_url( admin_url( 'admin-post.php' ) ),
			esc_attr( $do ),
			wp_nonce_field( 'pulse_migration', '_wpnonce', true, false ),
			$extra, // phpcs:ignore
			$primary ? 'button-primary' : '',
			esc_html( $label )
		);
	};
	$backup = '<label class="pulse-backup"><input type="checkbox" name="backup_confirmed" value="1" required> ' . esc_html__( 'I have a current backup', 'allbiohub-pulse' ) . '</label> ';
	?>
	<div class="wrap pulse-admin">
		<?php pulse_admin_header( __( 'Migration & Health', 'allbiohub-pulse' ), __( 'Pulse only detects on activation. Each step below runs only when you click it, is logged, and can be undone. Nothing deletes or edits your posts, pages, media, categories, SEO data or Foxiz settings.', 'allbiohub-pulse' ) ); ?>
		<?php if ( isset( $_GET['need_backup'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'Please confirm you have a current backup before applying a step.', 'allbiohub-pulse' ); ?></p></div>
		<?php elseif ( isset( $_GET['done'] ) ) : // phpcs:ignore ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Done. The report below is refreshed.', 'allbiohub-pulse' ); ?></p></div>
		<?php endif; ?>

		<section class="pulse-card">
			<h2><?php esc_html_e( 'Content found', 'allbiohub-pulse' ); ?></h2>
			<div class="pulse-stats">
				<?php
				$labels = array( 'posts' => __( 'Posts', 'allbiohub-pulse' ), 'pages' => __( 'Pages', 'allbiohub-pulse' ), 'categories' => __( 'Categories', 'allbiohub-pulse' ), 'tags' => __( 'Tags', 'allbiohub-pulse' ), 'media' => __( 'Media', 'allbiohub-pulse' ), 'authors' => __( 'Authors', 'allbiohub-pulse' ), 'videos' => __( 'Videos', 'allbiohub-pulse' ) );
				foreach ( $labels as $k => $label ) :
					?>
					<div class="pulse-stat"><strong><?php echo esc_html( number_format_i18n( isset( $r['counts'][ $k ] ) ? $r['counts'][ $k ] : 0 ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<p class="description"><?php printf( esc_html__( 'Last scanned %s.', 'allbiohub-pulse' ), esc_html( wp_date( 'j M Y H:i', $r['time'] ) ) ); ?> <?php $form( 'rescan', __( 'Rescan', 'allbiohub-pulse' ) ); ?> <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pulse_migration_csv' ), 'pulse_migration_csv' ) ); ?>"><?php esc_html_e( 'Download report (CSV)', 'allbiohub-pulse' ); ?></a></p>
		</section>

		<section class="pulse-card">
			<h2><?php esc_html_e( 'Health checks', 'allbiohub-pulse' ); ?></h2>
			<table class="pulse-table">
				<tbody>
					<tr><th><?php esc_html_e( 'Featured images', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( ! $r['broken_img'] && ! $r['no_image'], true ); // phpcs:ignore ?></td><td><?php printf( esc_html__( '%1$d posts without a featured image (fallback image shown), %2$d broken.', 'allbiohub-pulse' ), count( $r['no_image'] ), count( $r['broken_img'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Categories', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( ! in_array( 0, $r['sections'], true ), true ); // phpcs:ignore ?></td><td><?php echo esc_html( implode( ', ', array_map( 'pulse_section_label', array_keys( array_filter( $r['sections'], function ( $n ) { return ! $n; } ) ) ) ) ? sprintf( __( 'Sections with no category yet: %s', 'allbiohub-pulse' ), implode( ', ', array_map( 'pulse_section_label', array_keys( array_filter( $r['sections'], function ( $n ) { return ! $n; } ) ) ) ) ) : __( 'Every section has content.', 'allbiohub-pulse' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'URLs', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( $perm_ok ); // phpcs:ignore ?></td><td><?php printf( esc_html__( 'Permalink structure %s; Pulse changes no slugs or URLs.', 'allbiohub-pulse' ), '<code>' . esc_html( get_option( 'permalink_structure' ) ) . '</code>' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'SEO', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( (bool) $r['seo'], true ); // phpcs:ignore ?></td><td><?php echo esc_html( $r['seo'] ? sprintf( __( '%s detected. Pulse defers all titles, meta, schema and breadcrumbs to it.', 'allbiohub-pulse' ), 'rankmath' === $r['seo'] ? 'Rank Math' : ucfirst( $r['seo'] ) ) : __( 'No SEO plugin; Pulse fallback meta and schema are active.', 'allbiohub-pulse' ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Internal links', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( $perm_ok ); // phpcs:ignore ?></td><td><?php esc_html_e( 'No URLs were changed, so existing internal links keep working.', 'allbiohub-pulse' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Menus', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( ! empty( $locs['primary'] ), true ); // phpcs:ignore ?></td><td><?php echo ! empty( $locs['primary'] ) ? esc_html__( 'Primary navigation assigned.', 'allbiohub-pulse' ) : esc_html__( 'Pulse menu locations are empty. Apply step 1 below.', 'allbiohub-pulse' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Widgets', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( empty( array_filter( $r['foxiz_ads'], function ( $a ) { return ! $a['target']; } ) ), true ); // phpcs:ignore ?></td><td><?php printf( esc_html__( '%d Foxiz ad widgets found. They only display under Foxiz.', 'allbiohub-pulse' ), count( $r['foxiz_ads'] ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Theme settings', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( (bool) get_option( PULSE_OPTION ), true ); // phpcs:ignore ?></td><td><?php echo get_option( PULSE_OPTION ) ? esc_html__( 'Saved.', 'allbiohub-pulse' ) : esc_html__( 'Using defaults. Review AllBioHub → Theme Settings.', 'allbiohub-pulse' ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Advertisements', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( $ads_on > 0, true ); // phpcs:ignore ?></td><td><?php printf( esc_html__( '%d Pulse placements active. Ad Inserter keeps working independently.', 'allbiohub-pulse' ), (int) $ads_on ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Videos', 'allbiohub-pulse' ); ?></th><td><?php echo pulse_mig_status( ! $r['videos']['review'], true ); // phpcs:ignore ?></td><td><?php printf( esc_html__( '%1$d posts with a readable video, %2$d already migrated, %3$d need review.', 'allbiohub-pulse' ), count( $r['videos']['found'] ), count( $r['videos']['migrated'] ), count( $r['videos']['review'] ) ); ?></td></tr>
				</tbody>
			</table>
		</section>

		<section class="pulse-card">
			<h2><?php esc_html_e( 'Migration steps', 'allbiohub-pulse' ); ?></h2>
			<ol class="pulse-steps">
				<li>
					<h3><?php esc_html_e( 'Assign your existing menus', 'allbiohub-pulse' ); ?> <?php echo in_array( 'menus', $steps, true ) ? '<span class="pulse-check is-ok">✓</span>' : ''; ?></h3>
					<p><?php esc_html_e( 'Matched by name:', 'allbiohub-pulse' ); ?>
						<?php
						$names = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );
						$bits  = array();
						foreach ( pulse_menu_suggestions() as $loc => $menu_id ) {
							$bits[] = esc_html( $names[ $menu_id ] ) . ' → <code>' . esc_html( $loc ) . '</code>';
						}
						echo $bits ? wp_kses_post( implode( ', ', $bits ) ) : esc_html__( 'no matching menus found', 'allbiohub-pulse' );
						?>
					</p>
					<p class="description"><?php esc_html_e( 'Affects: which menu shows where in Pulse. Foxiz’s own menu assignments are stored separately and are not touched.', 'allbiohub-pulse' ); ?></p>
					<?php $form( 'menus', __( 'Apply', 'allbiohub-pulse' ), true, $backup ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Copy Foxiz ad code into Pulse placements', 'allbiohub-pulse' ); ?> <?php echo in_array( 'foxiz_ads', $steps, true ) ? '<span class="pulse-check is-ok">✓</span>' : ''; ?></h3>
					<ul class="pulse-list">
						<?php foreach ( $r['foxiz_ads'] as $ad ) : ?>
							<li><code><?php echo esc_html( $ad['widget'] ); ?></code> <?php esc_html_e( 'in', 'allbiohub-pulse' ); ?> <code><?php echo esc_html( $ad['sidebar'] ); ?></code> → <?php echo $ad['target'] ? esc_html( pulse_ad_slots()[ $ad['target'] ][0] ) : '<em>' . esc_html__( 'needs review', 'allbiohub-pulse' ) . '</em>'; ?><?php echo '' === $ad['code'] ? ' <em>(' . esc_html__( 'no code found', 'allbiohub-pulse' ) . ')</em>' : ''; ?></li>
						<?php endforeach; ?>
					</ul>
					<p class="description"><?php esc_html_e( 'Affects: ads shown by Pulse. The Foxiz widgets stay where they are. Ad Inserter is not changed.', 'allbiohub-pulse' ); ?></p>
					<?php $form( 'foxiz_ads', __( 'Apply', 'allbiohub-pulse' ), true, $backup ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Create Technology and Lifestyle categories', 'allbiohub-pulse' ); ?> <?php echo in_array( 'categories', $steps, true ) ? '<span class="pulse-check is-ok">✓</span>' : ''; ?></h3>
					<p class="description"><?php esc_html_e( 'Affects: adds two empty categories with new URLs (/category/technology/, /category/lifestyle/). No existing URL changes.', 'allbiohub-pulse' ); ?></p>
					<?php $form( 'categories', __( 'Apply', 'allbiohub-pulse' ), true, $backup ); ?>
					<?php
					foreach ( pulse_category_suggestions() as $target => $ids ) :
						if ( ! $ids || ! get_term_by( 'slug', $target, 'category' ) ) {
							continue;
						}
						?>
						<details class="pulse-suggest">
							<summary><?php printf( esc_html__( '%1$d Around the Web posts may belong in %2$s', 'allbiohub-pulse' ), count( $ids ), esc_html( ucfirst( $target ) ) ); ?></summary>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
								<input type="hidden" name="action" value="pulse_migration"><input type="hidden" name="do" value="assign"><input type="hidden" name="target" value="<?php echo esc_attr( $target ); ?>">
								<?php wp_nonce_field( 'pulse_migration' ); ?>
								<ul class="pulse-list">
									<?php foreach ( $ids as $id ) : ?>
										<li><label><input type="checkbox" name="posts[]" value="<?php echo esc_attr( $id ); ?>"> <?php echo esc_html( get_the_title( $id ) ); ?></label> <a href="<?php echo esc_url( get_permalink( $id ) ); ?>" target="_blank"><?php esc_html_e( 'view', 'allbiohub-pulse' ); ?></a></li>
									<?php endforeach; ?>
								</ul>
								<p class="description"><?php esc_html_e( 'Affects: ticked posts are ADDED to this category. They keep their existing categories, primary category and URL.', 'allbiohub-pulse' ); ?></p>
								<button class="button button-primary"><?php esc_html_e( 'Add ticked posts', 'allbiohub-pulse' ); ?></button>
							</form>
						</details>
					<?php endforeach; ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Carry over view counts', 'allbiohub-pulse' ); ?> <?php echo in_array( 'views', $steps, true ) ? '<span class="pulse-check is-ok">✓</span>' : ''; ?></h3>
					<p class="description"><?php esc_html_e( 'Copies the old view counters into Pulse’s own counter so “most viewed” starts with history. The old counters are left as they are.', 'allbiohub-pulse' ); ?></p>
					<?php $form( 'views', __( 'Apply', 'allbiohub-pulse' ), true, $backup ); ?>
				</li>
				<li>
					<h3><?php esc_html_e( 'Create AllBioHub Videos from posts with videos', 'allbiohub-pulse' ); ?> <?php echo in_array( 'videos', $steps, true ) ? '<span class="pulse-check is-ok">✓</span>' : ''; ?></h3>
					<p><?php printf( esc_html__( '%d posts contain a readable YouTube, Vimeo or MP4 video.', 'allbiohub-pulse' ), count( array_diff( $r['videos']['found'], $r['videos']['migrated'] ) ) ); ?></p>
					<p class="description"><?php esc_html_e( 'Affects: creates DRAFT videos (not public until you publish them) that link back to the original story. The original posts and their embeds are not changed.', 'allbiohub-pulse' ); ?></p>
					<?php $form( 'videos', __( 'Apply', 'allbiohub-pulse' ), true, $backup ); ?>
				</li>
			</ol>
		</section>

		<section class="pulse-card">
			<h2><?php esc_html_e( 'Section map', 'allbiohub-pulse' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Which existing categories and tags feed each AllBioHub section. Category URLs and slugs are not affected. Use comma-separated slugs.', 'allbiohub-pulse' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="pulse_migration"><input type="hidden" name="do" value="sections">
				<?php wp_nonce_field( 'pulse_migration' ); ?>
				<table class="pulse-table">
					<thead><tr><th><?php esc_html_e( 'Section', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Category slugs', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Tag slugs', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Found', 'allbiohub-pulse' ); ?></th></tr></thead>
					<tbody>
					<?php foreach ( pulse_section_map() as $key => $row ) : $t = pulse_section_terms( $key ); ?>
						<tr>
							<td><input type="text" name="map[<?php echo esc_attr( $key ); ?>][label]" value="<?php echo esc_attr( $row['label'] ); ?>"></td>
							<td><input type="text" class="regular-text" name="map[<?php echo esc_attr( $key ); ?>][cats]" value="<?php echo esc_attr( implode( ', ', $row['cats'] ) ); ?>"></td>
							<td><input type="text" class="regular-text" name="map[<?php echo esc_attr( $key ); ?>][tags]" value="<?php echo esc_attr( implode( ', ', $row['tags'] ) ); ?>"></td>
							<td><?php echo ( $t['cats'] || $t['tags'] ) ? '<span class="pulse-check is-ok">✓ ' . esc_html( count( $t['cats'] ) + count( $t['tags'] ) ) . '</span>' : '<span class="pulse-check is-warn">⚠ ' . esc_html__( 'none', 'allbiohub-pulse' ) . '</span>'; ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
				<p><button class="button button-primary"><?php esc_html_e( 'Save section map', 'allbiohub-pulse' ); ?></button></p>
			</form>
		</section>

		<section class="pulse-card">
			<h2><?php esc_html_e( 'Change log', 'allbiohub-pulse' ); ?></h2>
			<?php if ( ! $log ) : ?>
				<p><?php esc_html_e( 'No changes applied yet.', 'allbiohub-pulse' ); ?></p>
			<?php else : ?>
				<table class="pulse-table">
					<thead><tr><th><?php esc_html_e( 'When', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Change', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'By', 'allbiohub-pulse' ); ?></th><th></th></tr></thead>
					<tbody>
					<?php foreach ( $log as $e ) : ?>
						<tr>
							<td><?php echo esc_html( wp_date( 'j M Y H:i', $e['time'] ) ); ?></td>
							<td><?php echo esc_html( $e['summary'] ); ?></td>
							<td><?php echo esc_html( get_the_author_meta( 'display_name', $e['user'] ) ); ?></td>
							<td><?php echo $e['undone'] ? esc_html__( 'Undone', 'allbiohub-pulse' ) : ''; ?><?php if ( ! $e['undone'] ) { $form( 'undo', __( 'Undo', 'allbiohub-pulse' ), false, '<input type="hidden" name="entry" value="' . esc_attr( $e['id'] ) . '">' ); } ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</section>

		<section class="pulse-card">
			<h2><?php esc_html_e( 'Needs manual review', 'allbiohub-pulse' ); ?></h2>
			<table class="pulse-table">
				<thead><tr><th><?php esc_html_e( 'Status', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Area', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Item', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Detail', 'allbiohub-pulse' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( array_slice( pulse_report_rows(), 0, 200 ) as $row ) : ?>
					<tr><?php foreach ( $row as $cell ) : ?><td><?php echo esc_html( $cell ); ?></td><?php endforeach; ?></tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</section>
	</div>
	<?php
}
