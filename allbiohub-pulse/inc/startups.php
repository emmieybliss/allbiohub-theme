<?php
/**
 * Featured Startups on the homepage, from the AllBioHub Startup Database
 * plugin. Pulse only reads the plugin's public data (published startups,
 * their logo and featured placement); nothing here changes a startup.
 *
 * Who shows up, in this order:
 * 1. Startups with a live paid/approved featured placement in the plugin.
 *    They are always shown first.
 * 2. Startups picked in Theme Settings → Featured Startups.
 * 3. Optionally, the newest startups that have a logo, to fill empty spots.
 *
 * Rotation works with page caching: the page carries a small pool and the
 * browser shows a different mix on each visit, or the server changes the mix
 * once a day.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_STARTUP_TYPE = 'abh_startup';
const PULSE_STARTUP_META = '_abh_';

function pulse_startups_available() {
	return post_type_exists( PULSE_STARTUP_TYPE );
}

/**
 * Number of startups shown at once (4 to 8).
 */
function pulse_startups_count() {
	return max( 4, min( 8, (int) pulse_opt( 'startups_count' ) ) );
}

/**
 * Does this startup have a live featured placement in the Startup Database?
 */
function pulse_startup_is_featured( $id ) {
	if ( 'featured' !== get_post_meta( $id, PULSE_STARTUP_META . 'featured_status', true ) ) {
		return false;
	}
	$now   = time();
	$from  = (int) get_post_meta( $id, PULSE_STARTUP_META . 'featured_from', true );
	$until = (int) get_post_meta( $id, PULSE_STARTUP_META . 'featured_until', true );
	return ( ! $from || $from <= $now ) && ( ! $until || $until > $now );
}

function pulse_startup_query_ids( array $args ) {
	$q = new WP_Query(
		array_merge(
			array(
				'post_type'           => PULSE_STARTUP_TYPE,
				'post_status'         => 'publish',
				'posts_per_page'      => 24,
				'fields'              => 'ids',
				'no_found_rows'       => true,
				'ignore_sticky_posts' => true,
			),
			$args
		)
	);
	return array_map( 'intval', $q->posts );
}

/**
 * The pool for the homepage block: list of array( id, paid ).
 */
function pulse_home_startups() {
	if ( ! pulse_startups_available() ) {
		return array();
	}
	$show = pulse_startups_count();
	$size = 'visit' === pulse_opt( 'startups_rotate' ) ? min( 24, max( $show * 2, 12 ) ) : $show;

	$paid = array_values(
		array_filter(
			pulse_startup_query_ids(
				array(
					'meta_key'   => PULSE_STARTUP_META . 'featured_status', // phpcs:ignore WordPress.DB.SlowDBQuery
					'meta_value' => 'featured', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			),
			'pulse_startup_is_featured'
		)
	);

	$picks = array();
	foreach ( pulse_opt_ids( 'startups_picks' ) as $id ) {
		$p = get_post( $id );
		if ( $p && PULSE_STARTUP_TYPE === $p->post_type && 'publish' === $p->post_status ) {
			$picks[] = (int) $id;
		}
	}

	$fill = array();
	if ( pulse_opt( 'startups_fill' ) && count( $paid ) + count( $picks ) < $size ) {
		$fill = pulse_startup_query_ids(
			array(
				'posts_per_page' => $size,
				'post__not_in'   => array_merge( $paid, $picks ),
				'meta_query'     => array( array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ) ), // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);
	}

	$rest = array_values( array_diff( array_unique( array_merge( $picks, $fill ) ), $paid ) );
	if ( 'daily' === pulse_opt( 'startups_rotate' ) ) {
		$paid = pulse_startups_daily_shuffle( $paid );
		$rest = pulse_startups_daily_shuffle( $rest );
	}

	$pool = array();
	foreach ( $paid as $id ) {
		$pool[] = array( 'id' => $id, 'paid' => true );
	}
	foreach ( $rest as $id ) {
		$pool[] = array( 'id' => $id, 'paid' => false );
	}
	return array_slice( $pool, 0, max( $size, count( $paid ) ) );
}

/**
 * Same order all day, a new order tomorrow.
 */
function pulse_startups_daily_shuffle( array $ids ) {
	$seed = gmdate( 'Ymd' );
	usort(
		$ids,
		function ( $a, $b ) use ( $seed ) {
			return strcmp( md5( $seed . $a ), md5( $seed . $b ) );
		}
	);
	return $ids;
}

/**
 * Short line under the name: the plugin's short description, else the start
 * of the full description.
 */
function pulse_startup_blurb( $id ) {
	$p    = get_post( $id );
	$text = $p->post_excerpt ? $p->post_excerpt : wp_strip_all_tags( strip_shortcodes( $p->post_content ) );
	return wp_trim_words( $text, 16, '…' );
}

function pulse_startup_term( $id, $tax ) {
	if ( ! taxonomy_exists( $tax ) ) {
		return '';
	}
	$terms = get_the_terms( $id, $tax );
	return ( $terms && ! is_wp_error( $terms ) ) ? $terms[0]->name : '';
}

/**
 * Startup Database pages, when they exist.
 */
function pulse_startups_page_url( $slug ) {
	$page = get_page_by_path( $slug );
	return ( $page && 'publish' === $page->post_status ) ? get_permalink( $page ) : '';
}

/* ---------- Admin picker search (startups are not in the WP REST API) ---------- */

add_action( 'rest_api_init', 'pulse_startups_pick_route' );
function pulse_startups_pick_route() {
	register_rest_route(
		'allbiohub/v1',
		'/pick-startups',
		array(
			'methods'             => 'GET',
			'callback'            => 'pulse_startups_pick_search',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		)
	);
}

function pulse_startups_pick_search( WP_REST_Request $req ) {
	if ( ! pulse_startups_available() ) {
		return array();
	}
	$ids = pulse_startup_query_ids(
		array(
			's'              => sanitize_text_field( (string) $req->get_param( 'search' ) ),
			'posts_per_page' => 8,
		)
	);
	return array_map(
		function ( $id ) {
			return array( 'id' => $id, 'title' => html_entity_decode( get_the_title( $id ), ENT_QUOTES ) );
		},
		$ids
	);
}

/* ---------- Upgrades and cache ---------- */

/**
 * Sites that saved their homepage order before 1.4.5 get the new block placed
 * after Biography once; after that the admin's own order is respected.
 */
add_action( 'admin_init', 'pulse_startups_add_to_order' );
add_action( 'after_switch_theme', 'pulse_startups_add_to_order' );
function pulse_startups_add_to_order() {
	if ( get_option( 'pulse_startups_in_order' ) ) {
		return;
	}
	$stored = (array) get_option( PULSE_OPTION, array() );
	if ( isset( $stored['home_order'] ) ) {
		$order = pulse_opt_slugs( 'home_order' );
		if ( ! in_array( 'startups', $order, true ) ) {
			$at = array_search( 'biography', $order, true );
			array_splice( $order, false === $at ? count( $order ) : $at + 1, 0, 'startups' );
			$stored['home_order'] = implode( ',', $order );
			update_option( PULSE_OPTION, $stored, true );
		}
	}
	update_option( 'pulse_startups_in_order', 1, false );
}

/**
 * Featuring or unfeaturing a startup changes the homepage.
 */
add_action( 'added_post_meta', 'pulse_startups_meta_changed', 10, 3 );
add_action( 'updated_post_meta', 'pulse_startups_meta_changed', 10, 3 );
add_action( 'deleted_post_meta', 'pulse_startups_meta_changed', 10, 3 );
function pulse_startups_meta_changed( $meta_id, $post_id, $key ) {
	if ( in_array( $key, array( PULSE_STARTUP_META . 'featured_status', PULSE_STARTUP_META . 'featured_from', PULSE_STARTUP_META . 'featured_until' ), true ) ) {
		pulse_cache_option_changed( PULSE_OPTION );
	}
}
