<?php
/**
 * Lightweight view counting for "Most viewed" and Trending.
 *
 * Counting happens through a tiny REST beacon fired by site.js, so it keeps
 * working behind LiteSpeed page caching. Editors and bots are not counted.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_VIEWS_KEY = '_allbiohub_views';

add_action( 'rest_api_init', 'pulse_views_route' );
function pulse_views_route() {
	register_rest_route(
		'allbiohub/v1',
		'/view/(?P<id>\d+)',
		array(
			'methods'             => 'POST',
			'callback'            => 'pulse_views_hit',
			'permission_callback' => '__return_true',
			'args'                => array( 'id' => array( 'validate_callback' => 'is_numeric' ) ),
		)
	);
}

function pulse_views_hit( WP_REST_Request $req ) {
	$id   = absint( $req['id'] );
	$post = get_post( $id );
	if ( ! pulse_opt( 'views_enabled' ) || ! $post || 'publish' !== $post->post_status || ! in_array( $post->post_type, array( 'post', 'abh_video' ), true ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 200 );
	}
	if ( current_user_can( 'edit_posts' ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'reason' => 'editor' ), 200 );
	}
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : '';
	if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview|lighthouse/i', $ua ) ) {
		return new WP_REST_Response( array( 'ok' => false ), 200 );
	}
	// One count per visitor per post per hour.
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	$key = 'pulse_v_' . md5( $id . '|' . $ip . '|' . $ua );
	if ( get_transient( $key ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'dedup' => true ), 200 );
	}
	set_transient( $key, 1, HOUR_IN_SECONDS );

	$views = pulse_views( $id ) + 1;
	update_post_meta( $id, PULSE_VIEWS_KEY, $views );
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/**
 * Current view count. If Pulse hasn't counted this post yet, report the
 * previous counters (read-only) so "Most viewed" is meaningful from day one.
 */
function pulse_views( $post_id ) {
	$v = get_post_meta( $post_id, PULSE_VIEWS_KEY, true );
	if ( '' !== $v ) {
		return (int) $v;
	}
	return (int) get_post_meta( $post_id, '_ahcpro_total_views', true ) + (int) get_post_meta( $post_id, 'tie_views', true );
}

/**
 * Most viewed posts. Uses Pulse counts and falls back to the legacy counters,
 * then to recency, so it always returns $count posts when content exists.
 *
 * @param int   $count   Number of posts.
 * @param int   $days    Only posts published within this many days (0 = any).
 * @param array $exclude Post IDs to skip.
 * @param array $extra   Extra WP_Query args (e.g. category).
 */
function pulse_most_viewed( $count = 5, $days = 0, $exclude = array(), $extra = array() ) {
	$cache_key = 'pulse_mv_' . md5( wp_json_encode( func_get_args() ) );
	$ids       = get_transient( $cache_key );
	if ( false === $ids ) {
		$ids  = array();
		$base = array_merge(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'posts_per_page'      => $count,
				'fields'              => 'ids',
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'post__not_in'        => $exclude,
			),
			$extra
		);
		if ( $days ) {
			$base['date_query'] = array( array( 'after' => $days . ' days ago' ) );
		}
		foreach ( array( PULSE_VIEWS_KEY, '_ahcpro_total_views', 'tie_views' ) as $meta_key ) {
			if ( count( $ids ) >= $count ) {
				break;
			}
			$q   = new WP_Query(
				array_merge(
					$base,
					array(
						'meta_key'       => $meta_key, // phpcs:ignore WordPress.DB.SlowDBQuery
						'orderby'        => 'meta_value_num',
						'order'          => 'DESC',
						'posts_per_page' => $count - count( $ids ),
						'post__not_in'   => array_merge( $exclude, $ids ),
					)
				)
			);
			$ids = array_merge( $ids, $q->posts );
		}
		if ( count( $ids ) < $count ) {
			$q   = new WP_Query( array_merge( $base, array( 'posts_per_page' => $count - count( $ids ), 'post__not_in' => array_merge( $exclude, $ids ) ) ) );
			$ids = array_merge( $ids, $q->posts );
		}
		set_transient( $cache_key, $ids, 15 * MINUTE_IN_SECONDS );
	}
	return array_map( 'intval', $ids );
}
