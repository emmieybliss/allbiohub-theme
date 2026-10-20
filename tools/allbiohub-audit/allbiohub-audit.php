<?php
/**
 * Plugin Name: AllBioHub Site Audit
 * Description: Read-only audit of the existing AllBioHub installation (Phase 1 of the AllBioHub Pulse migration). Produces a JSON report under Tools → AllBioHub Audit. Writes nothing to posts, terms, users or options.
 * Version:     1.0.0
 * Author:      AllBioHub
 * License:     GPL-2.0-or-later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package AllBioHub_Audit
 */

defined( 'ABSPATH' ) || exit;

final class AllBioHub_Audit {

	const CAP  = 'manage_options';
	const SLUG = 'allbiohub-audit';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_post_allbiohub_audit_download', array( __CLASS__, 'download' ) );
	}

	public static function menu() {
		add_management_page( 'AllBioHub Audit', 'AllBioHub Audit', self::CAP, self::SLUG, array( __CLASS__, 'render' ) );
	}

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) {
			return;
		}
		$report = self::build();
		$url    = wp_nonce_url( admin_url( 'admin-post.php?action=allbiohub_audit_download' ), 'allbiohub_audit' );
		echo '<div class="wrap"><h1>AllBioHub Site Audit</h1>';
		echo '<p>Read-only. Nothing on the site is changed. Download the report and share it so the migration map can be built from real data.</p>';
		echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">Download audit JSON</a></p>';
		echo '<h2>Summary</h2><table class="widefat striped" style="max-width:720px"><tbody>';
		foreach ( $report['summary'] as $k => $v ) {
			echo '<tr><th>' . esc_html( $k ) . '</th><td>' . esc_html( is_scalar( $v ) ? (string) $v : wp_json_encode( $v ) ) . '</td></tr>';
		}
		echo '</tbody></table>';
		echo '<h2>Full report</h2><textarea readonly style="width:100%;height:480px;font-family:monospace">' . esc_textarea( wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) ) . '</textarea></div>';
	}

	public static function download() {
		if ( ! current_user_can( self::CAP ) ) {
			wp_die( esc_html__( 'Not allowed.' ), 403 );
		}
		check_admin_referer( 'allbiohub_audit' );
		nocache_headers();
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="allbiohub-audit-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( self::build(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * Builds the full report. Every call below is a read.
	 */
	public static function build() {
		$r = array(
			'generated_at' => gmdate( 'c' ),
			'environment'  => self::environment(),
			'theme'        => self::theme(),
			'plugins'      => self::plugins(),
			'post_types'   => self::post_types(),
			'taxonomies'   => self::taxonomies(),
			'categories'   => self::terms( 'category' ),
			'tags'         => self::tag_summary(),
			'pages'        => self::pages(),
			'menus'        => self::menus(),
			'sidebars'     => self::sidebars(),
			'media'        => self::media(),
			'featured'     => self::featured_images(),
			'authors'      => self::authors(),
			'meta_keys'    => self::meta_keys(),
			'seo'          => self::seo(),
			'permalinks'   => self::permalinks(),
			'redirects'    => self::redirects(),
			'comments'     => self::comments(),
			'shortcodes'   => self::shortcodes_in_content(),
			'blocks'       => self::blocks_in_content(),
			'builders'     => self::builders(),
			'theme_mods'   => array_keys( (array) get_theme_mods() ),
			'integrations' => self::integrations(),
		);

		$counts       = wp_count_posts( 'post' );
		$r['summary'] = array(
			'WordPress'          => $r['environment']['wp_version'],
			'PHP'                => $r['environment']['php_version'],
			'Active theme'       => $r['theme']['active']['name'] . ' (' . $r['theme']['active']['stylesheet'] . ')',
			'Parent theme'       => $r['theme']['active']['template'],
			'Active plugins'     => count( $r['plugins']['active'] ),
			'Published posts'    => (int) $counts->publish,
			'Pages'              => count( $r['pages'] ),
			'Categories'         => count( $r['categories'] ),
			'Tags'               => $r['tags']['count'],
			'Media items'        => $r['media']['total'],
			'Posts w/o featured' => $r['featured']['missing_count'],
			'Broken featured'    => $r['featured']['broken_count'],
			'Authors'            => count( $r['authors'] ),
			'SEO plugin'         => $r['seo']['plugin'],
			'Permalinks'         => $r['permalinks']['structure'],
		);
		return $r;
	}

	private static function environment() {
		global $wpdb;
		return array(
			'wp_version'  => get_bloginfo( 'version' ),
			'php_version' => PHP_VERSION,
			'db_server'   => $wpdb->db_server_info(),
			'site_url'    => site_url(),
			'home_url'    => home_url(),
			'blogname'    => get_bloginfo( 'name' ),
			'tagline'     => get_bloginfo( 'description' ),
			'language'    => get_locale(),
			'timezone'    => wp_timezone_string(),
			'multisite'   => is_multisite(),
			'show_on_front' => get_option( 'show_on_front' ),
			'page_on_front' => (int) get_option( 'page_on_front' ),
			'page_for_posts' => (int) get_option( 'page_for_posts' ),
			'posts_per_page' => (int) get_option( 'posts_per_page' ),
			'site_icon'   => get_site_icon_url(),
			'custom_logo' => wp_get_attachment_image_url( (int) get_theme_mod( 'custom_logo' ), 'full' ),
		);
	}

	private static function theme() {
		$t      = wp_get_theme();
		$themes = array();
		foreach ( wp_get_themes() as $slug => $th ) {
			$themes[] = array( 'stylesheet' => $slug, 'name' => $th->get( 'Name' ), 'version' => $th->get( 'Version' ), 'parent' => $th->parent() ? $th->get_template() : null );
		}
		return array(
			'active'    => array(
				'name'       => $t->get( 'Name' ),
				'version'    => $t->get( 'Version' ),
				'stylesheet' => $t->get_stylesheet(),
				'template'   => $t->get_template(),
				'is_child'   => (bool) $t->parent(),
				'block_theme' => function_exists( 'wp_is_block_theme' ) && wp_is_block_theme(),
			),
			'installed' => $themes,
		);
	}

	private static function plugins() {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$active = (array) get_option( 'active_plugins', array() );
		$out    = array( 'active' => array(), 'inactive' => array() );
		foreach ( get_plugins() as $file => $p ) {
			$row = array( 'file' => $file, 'name' => $p['Name'], 'version' => $p['Version'] );
			$out[ in_array( $file, $active, true ) ? 'active' : 'inactive' ][] = $row;
		}
		$out['mu'] = array_keys( get_mu_plugins() );
		return $out;
	}

	private static function post_types() {
		$out = array();
		foreach ( get_post_types( array(), 'objects' ) as $pt ) {
			$c     = wp_count_posts( $pt->name );
			$out[] = array(
				'name'    => $pt->name,
				'label'   => $pt->label,
				'public'  => $pt->public,
				'builtin' => $pt->_builtin,
				'rewrite' => is_array( $pt->rewrite ) ? $pt->rewrite['slug'] : $pt->rewrite,
				'counts'  => array_filter( (array) $c ),
			);
		}
		return $out;
	}

	private static function taxonomies() {
		$out = array();
		foreach ( get_taxonomies( array(), 'objects' ) as $tx ) {
			$out[] = array(
				'name'         => $tx->name,
				'label'        => $tx->label,
				'object_types' => $tx->object_type,
				'public'       => $tx->public,
				'hierarchical' => $tx->hierarchical,
				'term_count'   => (int) wp_count_terms( array( 'taxonomy' => $tx->name, 'hide_empty' => false ) ),
			);
		}
		return $out;
	}

	private static function terms( $taxonomy ) {
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return array();
		}
		$out = array();
		foreach ( $terms as $t ) {
			$out[] = array(
				'id'          => $t->term_id,
				'name'        => $t->name,
				'slug'        => $t->slug,
				'parent'      => $t->parent,
				'count'       => $t->count,
				'url'         => get_term_link( $t ),
				'description' => wp_strip_all_tags( $t->description ),
				'meta_keys'   => array_keys( (array) get_term_meta( $t->term_id ) ),
			);
		}
		return $out;
	}

	private static function tag_summary() {
		$top = get_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false, 'orderby' => 'count', 'order' => 'DESC', 'number' => 200 ) );
		$out = array();
		foreach ( is_wp_error( $top ) ? array() : $top as $t ) {
			$out[] = array( 'name' => $t->name, 'slug' => $t->slug, 'count' => $t->count );
		}
		return array(
			'count'  => (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ),
			'empty'  => (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => false ) ) - (int) wp_count_terms( array( 'taxonomy' => 'post_tag', 'hide_empty' => true ) ),
			'top200' => $out,
		);
	}

	private static function pages() {
		$out = array();
		foreach ( get_pages( array( 'post_status' => 'publish,draft,private' ) ) as $p ) {
			$out[] = array(
				'id'       => $p->ID,
				'title'    => $p->post_title,
				'slug'     => $p->post_name,
				'status'   => $p->post_status,
				'url'      => get_permalink( $p ),
				'template' => get_page_template_slug( $p ),
				'words'    => str_word_count( wp_strip_all_tags( $p->post_content ) ),
				'builder'  => self::builder_for( $p->ID, $p->post_content ),
			);
		}
		return $out;
	}

	private static function menus() {
		$out = array( 'locations' => get_nav_menu_locations(), 'registered' => get_registered_nav_menus(), 'menus' => array() );
		foreach ( wp_get_nav_menus() as $m ) {
			$items = array();
			foreach ( (array) wp_get_nav_menu_items( $m->term_id ) as $i ) {
				$items[] = array( 'title' => $i->title, 'url' => $i->url, 'type' => $i->type, 'object' => $i->object, 'object_id' => (int) $i->object_id, 'parent' => (int) $i->menu_item_parent );
			}
			$out['menus'][] = array( 'id' => $m->term_id, 'name' => $m->name, 'slug' => $m->slug, 'items' => $items );
		}
		return $out;
	}

	private static function sidebars() {
		global $wp_registered_sidebars;
		$widgets = wp_get_sidebars_widgets();
		$out     = array();
		foreach ( $widgets as $id => $list ) {
			if ( 'array_version' === $id ) {
				continue;
			}
			$out[] = array(
				'id'         => $id,
				'name'       => isset( $wp_registered_sidebars[ $id ] ) ? $wp_registered_sidebars[ $id ]['name'] : null,
				'registered' => isset( $wp_registered_sidebars[ $id ] ),
				'widgets'    => (array) $list,
			);
		}
		return $out;
	}

	private static function media() {
		global $wpdb;
		$mimes = $wpdb->get_results( "SELECT post_mime_type AS mime, COUNT(*) AS n FROM {$wpdb->posts} WHERE post_type = 'attachment' GROUP BY post_mime_type ORDER BY n DESC", ARRAY_A );
		$total = 0;
		foreach ( $mimes as $m ) {
			$total += (int) $m['n'];
		}
		$external = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND guid NOT LIKE %s", $wpdb->esc_like( home_url() ) . '%' ) );
		return array(
			'total'          => $total,
			'by_mime'        => $mimes,
			'guid_off_host'  => $external,
			'image_sizes'    => wp_get_registered_image_subsizes(),
			'uploads_baseurl' => wp_get_upload_dir()['baseurl'],
		);
	}

	private static function featured_images() {
		global $wpdb;
		$missing = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id'
			 WHERE p.post_type = 'post' AND p.post_status = 'publish' AND m.meta_id IS NULL"
		);
		$broken_rows = $wpdb->get_results(
			"SELECT p.ID, p.post_title, m.meta_value AS thumb FROM {$wpdb->posts} p
			 JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id'
			 LEFT JOIN {$wpdb->posts} a ON a.ID = m.meta_value AND a.post_type = 'attachment'
			 WHERE p.post_type = 'post' AND p.post_status = 'publish' AND a.ID IS NULL
			 LIMIT 500",
			ARRAY_A
		);
		$missing_sample = $wpdb->get_results(
			"SELECT p.ID, p.post_title FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} m ON m.post_id = p.ID AND m.meta_key = '_thumbnail_id'
			 WHERE p.post_type = 'post' AND p.post_status = 'publish' AND m.meta_id IS NULL
			 ORDER BY p.post_date DESC LIMIT 100",
			ARRAY_A
		);
		return array(
			'missing_count'  => $missing,
			'missing_sample' => $missing_sample,
			'broken_count'   => count( $broken_rows ),
			'broken'         => $broken_rows,
		);
	}

	private static function authors() {
		$out = array();
		foreach ( get_users( array( 'has_published_posts' => true ) ) as $u ) {
			$out[] = array(
				'id'          => $u->ID,
				'login'       => $u->user_login,
				'display'     => $u->display_name,
				'nicename'    => $u->user_nicename,
				'posts'       => (int) count_user_posts( $u->ID ),
				'has_bio'     => '' !== trim( (string) get_user_meta( $u->ID, 'description', true ) ),
				'url'         => get_author_posts_url( $u->ID ),
				'roles'       => $u->roles,
				'social_keys' => array_values( array_filter( array_keys( get_user_meta( $u->ID ) ), function ( $k ) {
					return (bool) preg_match( '/facebook|twitter|instagram|linkedin|youtube|tiktok|social|wpseo|rank_math/i', $k );
				} ) ),
			);
		}
		return $out;
	}

	private static function meta_keys() {
		global $wpdb;
		return $wpdb->get_results(
			"SELECT m.meta_key, COUNT(*) AS n FROM {$wpdb->postmeta} m
			 JOIN {$wpdb->posts} p ON p.ID = m.post_id AND p.post_type IN ('post','page')
			 GROUP BY m.meta_key ORDER BY n DESC LIMIT 300",
			ARRAY_A
		);
	}

	private static function seo() {
		global $wpdb;
		$plugin = 'none detected';
		if ( defined( 'WPSEO_VERSION' ) ) {
			$plugin = 'Yoast SEO ' . WPSEO_VERSION;
		} elseif ( defined( 'RANK_MATH_VERSION' ) ) {
			$plugin = 'Rank Math ' . RANK_MATH_VERSION;
		} elseif ( defined( 'AIOSEO_VERSION' ) ) {
			$plugin = 'All in One SEO ' . AIOSEO_VERSION;
		} elseif ( defined( 'SEOPRESS_VERSION' ) ) {
			$plugin = 'SEOPress ' . SEOPRESS_VERSION;
		}
		$keys = array( '_yoast_wpseo_title', '_yoast_wpseo_metadesc', '_yoast_wpseo_primary_category', '_yoast_wpseo_canonical', 'rank_math_title', 'rank_math_description', 'rank_math_primary_category', 'rank_math_canonical_url', '_aioseo_title', '_aioseo_description', '_seopress_titles_title', '_seopress_titles_desc' );
		$in   = "'" . implode( "','", array_map( 'esc_sql', $keys ) ) . "'";
		return array(
			'plugin'        => $plugin,
			'meta_usage'    => $wpdb->get_results( "SELECT meta_key, COUNT(*) AS n FROM {$wpdb->postmeta} WHERE meta_key IN ($in) GROUP BY meta_key", ARRAY_A ),
			'blog_public'   => (int) get_option( 'blog_public' ),
			'sitemap_core'  => function_exists( 'wp_sitemaps_get_server' ),
			'breadcrumbs'   => array(
				'yoast'    => function_exists( 'yoast_breadcrumb' ),
				'rankmath' => function_exists( 'rank_math_the_breadcrumbs' ),
				'aioseo'   => function_exists( 'aioseo_breadcrumbs' ),
			),
		);
	}

	private static function permalinks() {
		return array(
			'structure'     => get_option( 'permalink_structure' ),
			'category_base' => get_option( 'category_base' ),
			'tag_base'      => get_option( 'tag_base' ),
			'sample_posts'  => array_map( 'get_permalink', get_posts( array( 'numberposts' => 10, 'fields' => 'ids' ) ) ),
		);
	}

	private static function redirects() {
		global $wpdb;
		$out = array();
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'redirection_items' ) ) ) {
			$out['redirection_plugin'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}redirection_items" );
		}
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'rank_math_redirections' ) ) ) {
			$out['rank_math'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}rank_math_redirections" );
		}
		$yoast = get_option( 'wpseo-premium-redirects-base' );
		if ( $yoast ) {
			$out['yoast_premium'] = count( (array) $yoast );
		}
		$out['old_slug_meta'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_wp_old_slug'" );
		return $out;
	}

	private static function comments() {
		$c = wp_count_comments();
		return array(
			'approved'        => (int) $c->approved,
			'pending'         => (int) $c->moderated,
			'spam'            => (int) $c->spam,
			'default_status'  => get_option( 'default_comment_status' ),
		);
	}

	private static function shortcodes_in_content() {
		global $wpdb, $shortcode_tags;
		$found = array();
		$rows  = $wpdb->get_col( "SELECT post_content FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status = 'publish' AND post_content LIKE '%[%'" );
		foreach ( $rows as $content ) {
			if ( preg_match_all( '/\[([a-zA-Z][\w-]*)[\s\]\/]/', $content, $m ) ) {
				foreach ( array_unique( $m[1] ) as $tag ) {
					$found[ $tag ] = isset( $found[ $tag ] ) ? $found[ $tag ] + 1 : 1;
				}
			}
		}
		arsort( $found );
		$out = array();
		foreach ( array_slice( $found, 0, 100, true ) as $tag => $n ) {
			$out[] = array( 'tag' => $tag, 'posts' => $n, 'registered' => isset( $shortcode_tags[ $tag ] ) );
		}
		return $out;
	}

	private static function blocks_in_content() {
		global $wpdb;
		$found = array();
		$rows  = $wpdb->get_col( "SELECT post_content FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status = 'publish' AND post_content LIKE '%<!-- wp:%'" );
		foreach ( $rows as $content ) {
			if ( preg_match_all( '/<!-- wp:([a-z0-9\-\/]+)/', $content, $m ) ) {
				foreach ( array_unique( $m[1] ) as $b ) {
					$found[ $b ] = isset( $found[ $b ] ) ? $found[ $b ] + 1 : 1;
				}
			}
		}
		arsort( $found );
		return array( 'posts_with_blocks' => count( $rows ), 'blocks' => $found );
	}

	private static function builder_for( $post_id, $content ) {
		if ( get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
			return 'elementor';
		}
		if ( false !== strpos( $content, '[vc_row' ) ) {
			return 'wpbakery';
		}
		if ( false !== strpos( $content, '[et_pb_' ) ) {
			return 'divi';
		}
		if ( false !== strpos( $content, '<!-- wp:' ) ) {
			return 'gutenberg';
		}
		return 'classic';
	}

	private static function builders() {
		global $wpdb;
		return array(
			'elementor' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_elementor_edit_mode'" ),
			'wpbakery'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE '%[vc_row%'" ),
			'divi'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE '%[et_pb_%'" ),
		);
	}

	private static function integrations() {
		$active = implode( ' ', (array) get_option( 'active_plugins', array() ) );
		$has    = function ( $needle ) use ( $active ) {
			return false !== stripos( $active, $needle );
		};
		return array(
			'jetpack'         => $has( 'jetpack/' ),
			'site_kit'        => $has( 'google-site-kit' ),
			'woocommerce'     => $has( 'woocommerce/' ),
			'mailchimp'       => $has( 'mailchimp' ) || $has( 'mc4wp' ),
			'mailpoet'        => $has( 'mailpoet' ),
			'newsletter'      => $has( 'newsletter/' ),
			'brevo'           => $has( 'mailin' ) || $has( 'brevo' ) || $has( 'sendinblue' ),
			'adsense_or_ads'  => $has( 'ad-inserter' ) || $has( 'advanced-ads' ) || $has( 'adsense' ) || $has( 'quick-adsense' ),
			'cache'           => array_values( array_filter( array( 'wp-rocket', 'litespeed-cache', 'w3-total-cache', 'wp-super-cache', 'wp-fastest-cache', 'autoptimize' ), $has ) ),
			'social'          => array_values( array_filter( array( 'social-warfare', 'sassy-social-share', 'add-to-any', 'shared-counts', 'monarch' ), $has ) ),
			'views_counter'   => array_values( array_filter( array( 'post-views-counter', 'wp-postviews', 'jetpack' ), $has ) ),
			'ga_in_theme_mods' => (bool) preg_match( '/G-[A-Z0-9]{6,}|UA-\d+-\d+/', wp_json_encode( get_theme_mods() ) ),
		);
	}
}

AllBioHub_Audit::init();
