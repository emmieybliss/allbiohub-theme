<?php
/**
 * Template helpers: reading time, subtitles, primary category, images, dates.
 *
 * Everything here reads existing WordPress data. Nothing writes to posts.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reading time in minutes, from the live content word count.
 */
function pulse_reading_minutes( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 1;
	}
	$cache_key = 'pulse_rt_' . $post->ID . '_' . md5( $post->post_modified_gmt );
	$words     = wp_cache_get( $cache_key, 'pulse' );
	if ( false === $words ) {
		$text  = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
		$words = count( preg_split( '/\s+/u', trim( $text ), -1, PREG_SPLIT_NO_EMPTY ) );
		if ( ! $words ) {
			$words = (int) get_post_meta( $post->ID, 'foxiz_content_total_word', true );
		}
		wp_cache_set( $cache_key, $words, 'pulse', DAY_IN_SECONDS );
	}
	$wpm = max( 100, (int) pulse_opt( 'reading_wpm' ) );
	return max( 1, (int) ceil( $words / $wpm ) );
}

function pulse_reading_time( $post = null ) {
	/* translators: %d: minutes */
	return sprintf( _n( '%d min read', '%d min read', pulse_reading_minutes( $post ), 'allbiohub-pulse' ), pulse_reading_minutes( $post ) );
}

/**
 * Subtitle from the previous themes' meta, then the manual excerpt.
 */
function pulse_subtitle( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	foreach ( array( 'ruby_tagline', 'tie_post_sub_title' ) as $key ) {
		$v = trim( (string) get_post_meta( $post->ID, $key, true ) );
		if ( '' !== $v ) {
			return $v;
		}
	}
	return has_excerpt( $post ) ? get_the_excerpt( $post ) : '';
}

/**
 * Short excerpt for cards.
 */
function pulse_excerpt( $post = null, $words = 26 ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$text = has_excerpt( $post ) ? $post->post_excerpt : strip_shortcodes( $post->post_content );
	// Block tags become spaces so paragraphs don't run together.
	$text = preg_replace( '#</?(p|div|h[1-6]|li|br|blockquote|figure|figcaption)\b[^>]*>#i', ' ', $text );
	return wp_trim_words( wp_strip_all_tags( $text ), $words, '…' );
}

/**
 * Primary category: Rank Math, then Jannah's meta, then Yoast, then the first category.
 */
function pulse_primary_category( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	foreach ( array( 'rank_math_primary_category', 'tie_primary_category', '_yoast_wpseo_primary_category' ) as $key ) {
		$id = (int) get_post_meta( $post->ID, $key, true );
		if ( $id ) {
			$term = get_term( $id, 'category' );
			if ( $term && ! is_wp_error( $term ) && has_term( $term->term_id, 'category', $post ) ) {
				return $term;
			}
		}
	}
	$cats = get_the_category( $post->ID );
	foreach ( $cats as $cat ) {
		if ( 'sponsored' !== $cat->slug ) {
			return $cat;
		}
	}
	return $cats ? $cats[0] : null;
}

/**
 * Category label link. Category names on the site are stored in capitals; the
 * label is uppercased by CSS anyway, so we normalise to title case for screen readers.
 */
function pulse_category_label( $post = null, $class = 'pulse-cat' ) {
	$term = pulse_primary_category( $post );
	if ( ! $term ) {
		return '';
	}
	$name = html_entity_decode( $term->name, ENT_QUOTES, 'UTF-8' );
	if ( function_exists( 'mb_convert_case' ) && mb_strtoupper( $name ) === $name ) {
		$name = mb_convert_case( mb_strtolower( $name ), MB_CASE_TITLE, 'UTF-8' );
	}
	return sprintf( '<a class="%s" href="%s">%s</a>', esc_attr( $class ), esc_url( get_term_link( $term ) ), esc_html( $name ) );
}

/**
 * Relative time for recent items, date for older ones.
 */
function pulse_time( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$ts   = get_post_timestamp( $post );
	$diff = time() - $ts;
	if ( $diff < DAY_IN_SECONDS * 7 && $diff >= 0 ) {
		/* translators: %s: human time difference */
		$label = sprintf( __( '%s ago', 'allbiohub-pulse' ), human_time_diff( $ts ) );
	} else {
		$label = wp_date( get_option( 'date_format' ), $ts );
	}
	return sprintf( '<time datetime="%s">%s</time>', esc_attr( get_the_date( DATE_W3C, $post ) ), esc_html( $label ) );
}

/**
 * Find the best image attachment ID for a post.
 * Order: featured image → first image in content → first attached image.
 */
function pulse_image_id( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return 0;
	}
	$id = (int) get_post_thumbnail_id( $post );
	if ( $id && wp_attachment_is_image( $id ) ) {
		return $id;
	}
	$cache = wp_cache_get( 'pulse_img_' . $post->ID, 'pulse' );
	if ( false !== $cache ) {
		return (int) $cache;
	}
	$found = 0;
	if ( preg_match_all( '/wp-image-(\d+)/', $post->post_content, $m ) ) {
		foreach ( $m[1] as $candidate ) {
			if ( wp_attachment_is_image( (int) $candidate ) ) {
				$found = (int) $candidate;
				break;
			}
		}
	}
	if ( ! $found && preg_match( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $m ) ) {
		$found = (int) attachment_url_to_postid( preg_replace( '/-\d+x\d+(?=\.[a-z]{3,4}$)/i', '', $m[1] ) );
	}
	if ( ! $found ) {
		$attached = get_children( array( 'post_parent' => $post->ID, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'numberposts' => 1, 'fields' => 'ids' ) );
		$found    = $attached ? (int) reset( $attached ) : 0;
	}
	wp_cache_set( 'pulse_img_' . $post->ID, $found, 'pulse', HOUR_IN_SECONDS );
	return $found;
}

/**
 * Responsive image for a post, never broken. Always has width/height.
 *
 * @param array $args size, class, loading (lazy|eager), fetchpriority, sizes.
 */
function pulse_image( $post = null, $args = array() ) {
	$post = get_post( $post );
	$args = wp_parse_args(
		$args,
		array(
			'size'          => 'pulse-card',
			'class'         => '',
			'loading'       => 'lazy',
			'fetchpriority' => '',
			'sizes'         => '',
		)
	);
	$id  = $post ? pulse_image_id( $post ) : 0;
	$alt = $post ? get_the_title( $post ) : '';
	if ( $id ) {
		$attr = array(
			'class'    => trim( 'pulse-img ' . $args['class'] ),
			'loading'  => $args['loading'],
			'decoding' => 'async',
		);
		$meta_alt = trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) );
		$attr['alt'] = '' !== $meta_alt ? $meta_alt : wp_strip_all_tags( $alt );
		if ( $args['fetchpriority'] ) {
			$attr['fetchpriority'] = $args['fetchpriority'];
		}
		if ( $args['sizes'] ) {
			$attr['sizes'] = $args['sizes'];
		}
		$html = wp_get_attachment_image( $id, $args['size'], false, $attr );
		if ( $html ) {
			return $html;
		}
	}
	return pulse_placeholder( $args['size'], $args['class'] );
}

/**
 * The AllBioHub placeholder, sized to the requested image size.
 */
function pulse_placeholder( $size = 'pulse-card', $class = '' ) {
	$sizes = wp_get_registered_image_subsizes();
	$w     = isset( $sizes[ $size ] ) ? (int) $sizes[ $size ]['width'] : 640;
	$h     = isset( $sizes[ $size ] ) && $sizes[ $size ]['height'] ? (int) $sizes[ $size ]['height'] : (int) round( $w * 0.75 );
	return sprintf(
		'<img class="%s" src="%s" width="%d" height="%d" alt="" loading="lazy" decoding="async">',
		esc_attr( trim( 'pulse-img pulse-img--placeholder ' . $class ) ),
		esc_url( PULSE_URI . '/assets/images/placeholder.svg' ),
		$w,
		$h
	);
}

/**
 * Author byline with link.
 */
function pulse_byline( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return '';
	}
	$author = (int) $post->post_author;
	$name   = get_the_author_meta( 'display_name', $author );
	if ( '' === trim( (string) $name ) ) {
		return '';
	}
	return sprintf( '<a class="pulse-byline" href="%s">%s</a>', esc_url( get_author_posts_url( $author ) ), esc_html( $name ) );
}

/**
 * Inline SVG icon from a small fixed set.
 */
function pulse_icon( $name, $size = 20 ) {
	$paths = array(
		'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'     => '<path d="M20 14.5A8 8 0 1 1 9.5 4a6.5 6.5 0 0 0 10.5 10.5Z"/>',
		'pin'      => '<path d="M9 4h6l-1 5 3 3v2H7v-2l3-3-1-5Z"/><path d="M12 14v6"/>',
		'play'     => '<path d="M8 5v14l11-7Z" fill="currentColor" stroke="none"/>',
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8Z"/>',
		'x'        => '<path d="M4 4l16 16M20 4 4 20"/>',
		'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 19Z"/><path d="M9 9c0 3 3 6 6 6l1-1.5-2-1-1 1c-1-.5-2-1.5-2.5-2.5l1-1-1-2Z"/>',
		'linkedin' => '<path d="M5 9v10M5 5v.01M9 19v-6a3 3 0 0 1 6 0v6M9 9v10"/>',
		'link'     => '<path d="M10 14a4 4 0 0 0 5.7 0l3-3a4 4 0 0 0-5.7-5.7l-1 1"/><path d="M14 10a4 4 0 0 0-5.7 0l-3 3a4 4 0 0 0 5.7 5.7l1-1"/>',
		'instagram' => '<rect x="4" y="4" width="16" height="16" rx="4"/><circle cx="12" cy="12" r="3.5"/><path d="M16.5 7.5v.01"/>',
		'youtube'  => '<rect x="3" y="6" width="18" height="12" rx="3"/><path d="m10 9 5 3-5 3Z" fill="currentColor"/>',
		'tiktok'   => '<path d="M14 4v10a3 3 0 1 1-3-3"/><path d="M14 4a5 5 0 0 0 5 5"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
	);
	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}
	return sprintf(
		'<svg class="pulse-icon pulse-icon--%1$s" width="%2$d" height="%2$d" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%3$s</svg>',
		esc_attr( $name ),
		(int) $size,
		$paths[ $name ]
	);
}

/**
 * Section heading with the AllBioHub "pulse rule".
 */
function pulse_section_head( $title, $link = '', $link_label = '' ) {
	echo '<header class="pulse-sechead"><h2 class="pulse-sechead__title">' . esc_html( $title ) . '</h2>';
	if ( $link ) {
		printf(
			'<a class="pulse-sechead__more" href="%s">%s %s</a>',
			esc_url( $link ),
			esc_html( $link_label ? $link_label : __( 'View all', 'allbiohub-pulse' ) ),
			pulse_icon( 'arrow', 16 ) // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG.
		);
	}
	echo '</header>';
}

/**
 * Load a template part with args (wrapper for readability).
 */
function pulse_part( $slug, $args = array() ) {
	get_template_part( 'template-parts/' . $slug, null, $args );
}

/**
 * Exclude list shared by homepage sections so stories don't repeat.
 */
function pulse_seen( $ids = null ) {
	static $seen = array();
	if ( null !== $ids ) {
		$seen = array_values( array_unique( array_merge( $seen, array_map( 'intval', (array) $ids ) ) ) );
	}
	return $seen;
}

/**
 * Social profiles configured in Theme Settings.
 */
function pulse_social_links() {
	$out = array();
	foreach ( array( 'facebook' => 'Facebook', 'x' => 'X', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'linkedin' => 'LinkedIn' ) as $key => $label ) {
		$url = pulse_opt( 'social_' . $key );
		if ( $url ) {
			$out[ $key ] = array( 'url' => $url, 'label' => $label );
		}
	}
	return $out;
}

/**
 * A page whose content is a plugin's app (a shortcode or block), such as the
 * Startup Database directory, dashboard and forms.
 */
function pulse_is_app_page( $post = null ) {
	$post    = get_post( $post );
	$content = $post ? (string) $post->post_content : '';
	$is_app  = (bool) preg_match( '/\[abh_|<!-- wp:(abh|allbiohub)/i', $content );
	return (bool) apply_filters( 'pulse_is_app_page', $is_app, $post );
}
