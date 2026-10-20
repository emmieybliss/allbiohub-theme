<?php
/**
 * Section map: AllBioHub editorial sections → existing categories and tags.
 *
 * Sections are a presentation layer. Category slugs, URLs and term meta are
 * never changed; a section simply lists which existing terms feed it.
 * Editable under AllBioHub → Migration & Health.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_SECTION_MAP = 'allbiohub_section_map';

/**
 * Defaults derived from the 2026-10-20 audit (docs/MIGRATION-MAP.md §1).
 */
function pulse_section_defaults() {
	return array(
		'celebrity'  => array( 'label' => 'Celebrity News', 'cats' => array( 'celebrity-news', 'celebrity' ), 'tags' => array() ),
		'biography'  => array( 'label' => 'Biography', 'cats' => array( 'biography' ), 'tags' => array() ),
		'business'   => array( 'label' => 'Business & Startups', 'cats' => array( 'startup-founders-innovator', 'money-career', 'business' ), 'tags' => array() ),
		'technology' => array( 'label' => 'Technology', 'cats' => array( 'technology' ), 'tags' => array( 'allbiohub-tech', 'africatech', 'tech-news-nigeria' ) ),
		'women'      => array( 'label' => 'Women in Tech', 'cats' => array( 'women-in-tech' ), 'tags' => array() ),
		'lifestyle'  => array( 'label' => 'Lifestyle', 'cats' => array( 'lifestyle' ), 'tags' => array() ),
		'reviews'    => array( 'label' => 'Reviews', 'cats' => array( 'reviews' ), 'tags' => array() ),
		'news'       => array( 'label' => 'Around the Web', 'cats' => array( 'around-the-web' ), 'tags' => array() ),
	);
}

function pulse_section_map() {
	$stored = get_option( PULSE_SECTION_MAP );
	$map    = pulse_section_defaults();
	if ( is_array( $stored ) ) {
		foreach ( $stored as $key => $row ) {
			$map[ $key ] = wp_parse_args( $row, isset( $map[ $key ] ) ? $map[ $key ] : array( 'label' => $key, 'cats' => array(), 'tags' => array() ) );
		}
	}
	return $map;
}

/**
 * Existing term IDs that feed a section.
 *
 * @return array{cats:int[],tags:int[],primary:?WP_Term}
 */
function pulse_section_terms( $key ) {
	$map = pulse_section_map();
	$out = array( 'cats' => array(), 'tags' => array(), 'primary' => null );
	if ( empty( $map[ $key ] ) ) {
		return $out;
	}
	foreach ( (array) $map[ $key ]['cats'] as $slug ) {
		$t = get_term_by( 'slug', $slug, 'category' );
		if ( $t ) {
			$out['cats'][] = (int) $t->term_id;
			if ( ! $out['primary'] || $t->count > $out['primary']->count ) {
				$out['primary'] = $t;
			}
		}
	}
	foreach ( (array) $map[ $key ]['tags'] as $slug ) {
		$t = get_term_by( 'slug', $slug, 'post_tag' );
		if ( $t ) {
			$out['tags'][] = (int) $t->term_id;
		}
	}
	return $out;
}

/**
 * Tax query for a section (categories OR tags).
 */
function pulse_section_tax_query( $key ) {
	$terms = pulse_section_terms( $key );
	$tq    = array( 'relation' => 'OR' );
	if ( $terms['cats'] ) {
		$tq[] = array( 'taxonomy' => 'category', 'field' => 'term_id', 'terms' => $terms['cats'] );
	}
	if ( $terms['tags'] ) {
		$tq[] = array( 'taxonomy' => 'post_tag', 'field' => 'term_id', 'terms' => $terms['tags'] );
	}
	return count( $tq ) > 1 ? $tq : null;
}

/**
 * Post IDs for a section.
 */
function pulse_section_posts( $key, $count = 4, $exclude = array() ) {
	$tq = pulse_section_tax_query( $key );
	if ( ! $tq ) {
		return array();
	}
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => $count,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'post__not_in'        => $exclude,
			'tax_query'           => $tq, // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	return array_map( 'intval', $q->posts );
}

/**
 * The "View all" link for a section: its largest existing category.
 */
function pulse_section_link( $key ) {
	$terms = pulse_section_terms( $key );
	if ( $terms['primary'] && $terms['primary']->count ) {
		$link = get_term_link( $terms['primary'] );
		return is_wp_error( $link ) ? '' : $link;
	}
	if ( $terms['tags'] ) {
		$link = get_term_link( $terms['tags'][0], 'post_tag' );
		return is_wp_error( $link ) ? '' : $link;
	}
	return '';
}

function pulse_section_label( $key ) {
	$map = pulse_section_map();
	return isset( $map[ $key ] ) ? $map[ $key ]['label'] : '';
}

/**
 * Which section does a category archive belong to (for archive headers)?
 */
function pulse_section_for_term( $term ) {
	if ( ! $term instanceof WP_Term ) {
		return '';
	}
	foreach ( pulse_section_map() as $key => $row ) {
		$list = 'category' === $term->taxonomy ? (array) $row['cats'] : (array) $row['tags'];
		if ( in_array( $term->slug, $list, true ) ) {
			return $key;
		}
	}
	return '';
}

/* ---------- Search filters ---------- */

/**
 * Filters shown on the search page: section keys that resolve to real terms.
 *
 * @return array<string,string> key => label
 */
function pulse_search_filters() {
	$out = array();
	foreach ( array( 'biography', 'celebrity', 'business', 'technology', 'lifestyle', 'reviews' ) as $key ) {
		if ( pulse_section_tax_query( $key ) ) {
			$out[ $key ] = pulse_section_label( $key );
		}
	}
	if ( post_type_exists( 'abh_video' ) && wp_count_posts( 'abh_video' )->publish ) {
		$out['video'] = __( 'Video', 'allbiohub-pulse' );
	}
	return $out;
}

/**
 * The active search filter from ?in=, validated against the known list.
 */
function pulse_search_filter() {
	$in = isset( $_GET['in'] ) ? sanitize_key( wp_unslash( $_GET['in'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification -- read-only filter.
	return isset( pulse_search_filters()[ $in ] ) ? $in : '';
}

add_action( 'pre_get_posts', 'pulse_search_query' );
function pulse_search_query( $q ) {
	if ( is_admin() || ! $q->is_main_query() || ! $q->is_search() ) {
		return;
	}
	$in = pulse_search_filter();
	if ( 'video' === $in ) {
		$q->set( 'post_type', 'abh_video' );
		return;
	}
	if ( $in ) {
		$q->set( 'post_type', 'post' );
		$q->set( 'tax_query', pulse_section_tax_query( $in ) );
		return;
	}
	// Default: stories, pages and videos; never attachments.
	$q->set( 'post_type', array( 'post', 'page', 'abh_video' ) );
}
