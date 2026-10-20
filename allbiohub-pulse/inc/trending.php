<?php
/**
 * Trending Now ticker and Trending Stories list.
 *
 * Sources (Theme Settings → Trending):
 *  - automatic: most viewed in the last N days, topped up with recent stories
 *  - manual:    the posts the editor picked, in order
 *  - hybrid:    manual picks first, then automatic to fill
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Only keep published posts from a manual list, preserving order.
 */
function pulse_published_only( array $ids ) {
	return array_values(
		array_filter(
			$ids,
			function ( $id ) {
				return 'publish' === get_post_status( $id );
			}
		)
	);
}

/**
 * Posts for the slim ticker under the header.
 */
function pulse_ticker_ids() {
	$count  = max( 3, min( 15, (int) pulse_opt( 'ticker_count' ) ) );
	$source = pulse_opt( 'ticker_source' );
	$manual = pulse_published_only( pulse_opt_ids( 'ticker_posts' ) );
	$days   = max( 1, (int) pulse_opt( 'trending_days' ) );

	if ( 'manual' === $source && $manual ) {
		return array_slice( $manual, 0, $count );
	}
	$ids = 'hybrid' === $source ? $manual : array();
	if ( count( $ids ) < $count ) {
		$ids = array_merge( $ids, pulse_most_viewed( $count - count( $ids ), $days, $ids ) );
	}
	return array_slice( $ids, 0, $count );
}

/**
 * Posts for the hero's Trending Stories list.
 */
function pulse_trending_ids( $count = 5, $exclude = array() ) {
	$source = pulse_opt( 'trending_source' );
	$days   = max( 1, (int) pulse_opt( 'trending_days' ) );
	if ( 'manual' === $source ) {
		$manual = array_diff( pulse_published_only( pulse_opt_ids( 'trending_posts' ) ), $exclude );
		if ( $manual ) {
			return array_slice( array_values( $manual ), 0, $count );
		}
	}
	if ( 'recent' === $source ) {
		$q = new WP_Query(
			array(
				'post_type'           => 'post',
				'posts_per_page'      => $count,
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
				'post__not_in'        => $exclude,
			)
		);
		return array_map( 'intval', $q->posts );
	}
	return pulse_most_viewed( $count, $days, $exclude );
}
