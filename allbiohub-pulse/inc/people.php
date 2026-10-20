<?php
/**
 * People You Should Know: drawn automatically from biography posts.
 *
 * Uses each biography's own featured image (or its fallback), so editors never
 * upload a separate portrait. Editors can optionally hand-pick people.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

function pulse_people_ids( $exclude = array() ) {
	$count  = max( 3, min( 12, (int) pulse_opt( 'people_count' ) ) );
	$manual = array_diff( pulse_published_only( pulse_opt_ids( 'people_posts' ) ), $exclude );
	$ids    = array_slice( array_values( $manual ), 0, $count );
	if ( count( $ids ) >= $count ) {
		return $ids;
	}
	$term = pulse_bio_term();
	if ( ! $term ) {
		return $ids;
	}
	// Biographies with a featured image first: they make the best portraits.
	$with_image = new WP_Query(
		array(
			'post_type'           => 'post',
			'posts_per_page'      => $count - count( $ids ),
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'cat'                 => $term->term_id,
			'post__not_in'        => array_merge( $exclude, $ids ),
			'meta_key'            => '_thumbnail_id', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	$ids = array_merge( $ids, array_map( 'intval', $with_image->posts ) );
	if ( count( $ids ) < $count ) {
		$rest = new WP_Query(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => $count - count( $ids ),
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'cat'                 => $term->term_id,
				'post__not_in'        => array_merge( $exclude, $ids ),
			)
		);
		$ids = array_merge( $ids, array_map( 'intval', $rest->posts ) );
	}
	return $ids;
}
