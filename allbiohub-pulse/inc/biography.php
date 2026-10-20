<?php
/**
 * Biography system: detection, Quick Profile, table of contents, name parsing.
 *
 * Article content is never modified in the database. Heading anchors for the
 * table of contents are added at render time only.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_PROFILE_KEY = '_allbiohub_profile';

/**
 * Is this post a biography? (In the configured biography category.)
 */
function pulse_is_biography( $post = null ) {
	$post = get_post( $post );
	if ( ! $post || 'post' !== $post->post_type ) {
		return false;
	}
	$slug = sanitize_title( (string) pulse_opt( 'bio_category' ) );
	return $slug && has_category( $slug, $post );
}

function pulse_bio_term() {
	$slug = sanitize_title( (string) pulse_opt( 'bio_category' ) );
	return $slug ? get_term_by( 'slug', $slug, 'category' ) : false;
}

/**
 * Use single-biography.php for biography posts.
 */
add_filter( 'single_template', 'pulse_bio_template' );
function pulse_bio_template( $template ) {
	if ( is_singular( 'post' ) && pulse_is_biography( get_queried_object_id() ) ) {
		$bio = locate_template( 'single-biography.php' );
		if ( $bio ) {
			return $bio;
		}
	}
	return $template;
}

/**
 * Person's name from a biography title.
 * "Hilda Baci Biography: Age, Husband, Net Worth" → "Hilda Baci".
 */
function pulse_person_name( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$profile = pulse_profile( $post->ID );
	if ( ! empty( $profile['known_as'] ) ) {
		return $profile['known_as'];
	}
	$title = html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' );
	$name  = preg_split( '/\s*(?::|\||–|—|\s-\s|,|\()\s*/u', $title )[0];
	$name  = preg_replace( '/\b(full\s+)?(biography|bio|profile|net\s*worth|age|wiki|wikipedia|life\s+story|early\s+life|career|family|husband|wife|boyfriend|girlfriend|parents|real\s+name|and\s+more|facts)\b.*$/iu', '', $name );
	$name  = preg_replace( '/^(who\s+is|meet|the\s+story\s+of)\s+/iu', '', trim( $name ) );
	$name  = preg_replace( "/'s$/u", '', trim( $name, " \t\n\r\0\x0B-–—:" ) );
	// A name is short. If parsing left something long or empty, show the title.
	$words = preg_split( '/\s+/u', trim( $name ), -1, PREG_SPLIT_NO_EMPTY );
	if ( ! $words || count( $words ) > 5 ) {
		return $title;
	}
	return trim( $name );
}

/**
 * Short descriptor: Quick Profile profession, else a profession word found in
 * the excerpt/title/tags, else "Biography".
 */
function pulse_person_descriptor( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$profile = pulse_profile( $post->ID );
	if ( ! empty( $profile['profession'] ) ) {
		return wp_trim_words( $profile['profession'], 4, '' );
	}
	$professions = array( 'Actress', 'Actor', 'Singer', 'Rapper', 'Musician', 'Comedian', 'Chef', 'Blogger', 'Journalist', 'Content Creator', 'Influencer', 'Model', 'Entrepreneur', 'Founder', 'CEO', 'Investor', 'Businesswoman', 'Businessman', 'Footballer', 'Wrestler', 'Boxer', 'Athlete', 'Politician', 'Activist', 'Producer', 'Director', 'Writer', 'Author', 'Presenter', 'Broadcaster', 'TikToker', 'YouTuber', 'Philanthropist', 'Engineer', 'Developer', 'Designer', 'Dancer', 'DJ', 'Skit Maker', 'Pastor', 'Beauty Queen', 'Street Santa' );
	$hay         = ' ' . wp_strip_all_tags( get_the_title( $post ) . ' ' . $post->post_excerpt . ' ' . wp_trim_words( $post->post_content, 80, '' ) ) . ' ';
	$tags        = wp_get_post_terms( $post->ID, 'post_tag', array( 'fields' => 'names' ) );
	$hay        .= ' ' . implode( ' ', is_array( $tags ) ? $tags : array() );
	foreach ( $professions as $p ) {
		if ( preg_match( '/\b' . preg_quote( $p, '/' ) . '\b/i', $hay ) ) {
			return $p;
		}
	}
	return __( 'Biography', 'allbiohub-pulse' );
}

/* ---------- Quick Profile ---------- */

function pulse_profile_fields() {
	return array(
		'full_name'   => __( 'Full name', 'allbiohub-pulse' ),
		'known_as'    => __( 'Known as', 'allbiohub-pulse' ),
		'born'        => __( 'Born', 'allbiohub-pulse' ),
		'nationality' => __( 'Nationality', 'allbiohub-pulse' ),
		'profession'  => __( 'Profession', 'allbiohub-pulse' ),
		'known_for'   => __( 'Known for', 'allbiohub-pulse' ),
	);
}

function pulse_profile( $post_id ) {
	$v = get_post_meta( $post_id, PULSE_PROFILE_KEY, true );
	return is_array( $v ) ? $v : array();
}

add_action( 'add_meta_boxes_post', 'pulse_profile_box' );
function pulse_profile_box() {
	add_meta_box( 'pulse-profile', __( 'Biography: Quick Profile', 'allbiohub-pulse' ), 'pulse_profile_box_html', 'post', 'side', 'default' );
}

function pulse_profile_box_html( $post ) {
	$profile = pulse_profile( $post->ID );
	wp_nonce_field( 'pulse_profile', 'pulse_profile_nonce' );
	echo '<p class="description">' . esc_html__( 'Shown at the top of biography posts. Leave fields empty to hide them.', 'allbiohub-pulse' ) . '</p>';
	foreach ( pulse_profile_fields() as $key => $label ) {
		printf(
			'<p><label for="pulse-profile-%1$s"><strong>%2$s</strong></label><br><input class="widefat" type="text" id="pulse-profile-%1$s" name="pulse_profile[%1$s]" value="%3$s"></p>',
			esc_attr( $key ),
			esc_html( $label ),
			esc_attr( isset( $profile[ $key ] ) ? $profile[ $key ] : '' )
		);
	}
}

add_action( 'save_post_post', 'pulse_profile_save' );
function pulse_profile_save( $post_id ) {
	if ( ! isset( $_POST['pulse_profile_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pulse_profile_nonce'] ) ), 'pulse_profile' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in  = isset( $_POST['pulse_profile'] ) ? (array) wp_unslash( $_POST['pulse_profile'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$out = array();
	foreach ( array_keys( pulse_profile_fields() ) as $key ) {
		$v = isset( $in[ $key ] ) ? sanitize_text_field( $in[ $key ] ) : '';
		if ( '' !== $v ) {
			$out[ $key ] = $v;
		}
	}
	if ( $out ) {
		update_post_meta( $post_id, PULSE_PROFILE_KEY, $out );
	} else {
		delete_post_meta( $post_id, PULSE_PROFILE_KEY );
	}
}

/* ---------- Table of contents (render-time only) ---------- */

/**
 * Headings (h2/h3) in a post's content.
 *
 * @return array<int,array{level:int,text:string,id:string}>
 */
function pulse_toc_items( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return array();
	}
	static $cache = array();
	if ( isset( $cache[ $post->ID ] ) ) {
		return $cache[ $post->ID ];
	}
	$items = array();
	$used  = array();
	if ( preg_match_all( '/<h([23])([^>]*)>(.*?)<\/h\1>/is', $post->post_content, $m, PREG_SET_ORDER ) ) {
		foreach ( $m as $h ) {
			$text = trim( html_entity_decode( wp_strip_all_tags( $h[3] ), ENT_QUOTES, 'UTF-8' ) );
			if ( '' === $text ) {
				continue;
			}
			$id = '';
			if ( preg_match( '/\sid=["\']([^"\']+)["\']/i', $h[2], $idm ) ) {
				$id = $idm[1];
			} else {
				$base = sanitize_title( $text );
				$base = $base ? $base : 'section';
				$id   = $base;
				$n    = 2;
				while ( isset( $used[ $id ] ) ) {
					$id = $base . '-' . $n++;
				}
			}
			$used[ $id ] = true;
			$items[]     = array( 'level' => (int) $h[1], 'text' => $text, 'id' => $id );
		}
	}
	$cache[ $post->ID ] = $items;
	return $items;
}

/**
 * Add ids to h2/h3 in the rendered content so TOC links work.
 */
add_filter( 'the_content', 'pulse_heading_anchors', 20 );
function pulse_heading_anchors( $content ) {
	if ( ! is_singular( array( 'post', 'page', 'abh_video' ) ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}
	$items = pulse_toc_items();
	if ( ! $items ) {
		return $content;
	}
	$i = 0;
	return preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		function ( $h ) use ( $items, &$i ) {
			$text = trim( wp_strip_all_tags( $h[3] ) );
			if ( '' === $text || ! isset( $items[ $i ] ) ) {
				return $h[0];
			}
			$item = $items[ $i++ ];
			if ( preg_match( '/\sid=/i', $h[2] ) ) {
				return $h[0];
			}
			$section = pulse_bio_section_key( $item['text'] );
			$class   = $section ? ' data-bio-section="' . esc_attr( $section ) . '"' : '';
			return '<h' . $h[1] . ' id="' . esc_attr( $item['id'] ) . '"' . $class . $h[2] . '>' . $h[3] . '</h' . $h[1] . '>';
		},
		$content
	);
}

/**
 * Map a heading to one of the standard biography sections, if it is one.
 */
function pulse_bio_section_key( $text ) {
	$map = array(
		'early-life'    => '/early\s+life|childhood|background|birth|born/i',
		'education'     => '/education|school|university|studies/i',
		'career'        => '/career|work|profession|rise|journey/i',
		'personal-life' => '/personal\s+life|relationship|family|married|husband|wife|children|dating/i',
		'achievements'  => '/achievement|award|honou?r|recognition|accomplish/i',
		'social-media'  => '/social\s+media|instagram|tiktok|twitter|youtube|online\s+presence/i',
		'net-worth'     => '/net\s*worth|income|earnings|wealth/i',
	);
	foreach ( $map as $key => $re ) {
		if ( preg_match( $re, $text ) ) {
			return $key;
		}
	}
	return '';
}

/**
 * Links to standard sections that exist in a biography (for Featured Biography).
 */
function pulse_bio_section_links( $post = null ) {
	$post   = get_post( $post );
	$wanted = array(
		'early-life'    => __( 'Early Life', 'allbiohub-pulse' ),
		'career'        => __( 'Career', 'allbiohub-pulse' ),
		'personal-life' => __( 'Personal Life', 'allbiohub-pulse' ),
		'achievements'  => __( 'Achievements', 'allbiohub-pulse' ),
	);
	$links = array();
	foreach ( pulse_toc_items( $post ) as $item ) {
		$key = pulse_bio_section_key( $item['text'] );
		if ( $key && isset( $wanted[ $key ] ) && ! isset( $links[ $key ] ) ) {
			$links[ $key ] = array( 'label' => $wanted[ $key ], 'url' => get_permalink( $post ) . '#' . $item['id'] );
		}
	}
	// Keep the canonical order.
	return array_values( array_filter( array_replace( array_fill_keys( array_keys( $wanted ), null ), $links ) ) );
}

/**
 * The biography to feature: admin pick, else the newest biography with a real image.
 */
function pulse_featured_bio_id( $exclude = array() ) {
	$pick = (int) pulse_opt( 'bio_featured_post' );
	if ( $pick && 'publish' === get_post_status( $pick ) ) {
		return $pick;
	}
	$term = pulse_bio_term();
	if ( ! $term ) {
		return 0;
	}
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => 1,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'cat'                 => $term->term_id,
			'post__not_in'        => $exclude,
			'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return $q->posts ? (int) $q->posts[0] : 0;
}
