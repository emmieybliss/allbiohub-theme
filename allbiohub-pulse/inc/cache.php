<?php
/**
 * Page-cache freshness. allbiohub.com runs LiteSpeed Cache, which serves
 * visitors a saved copy of each page for up to a week while logged-in admins
 * always get a fresh one. So when ads or theme settings change, the saved
 * copies are cleared; otherwise visitors keep seeing the old page (no new
 * ads) while the admin sees the new one.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Theme options whose change alters what every page shows.
 */
function pulse_cache_watched_options() {
	return array( PULSE_ADS, PULSE_OPTION, PULSE_SECTION_MAP, 'theme_mods_' . get_stylesheet(), 'sidebars_widgets' );
}

/**
 * Pinning or sponsoring a story changes the homepage, not just that post.
 */
add_action( 'added_post_meta', 'pulse_cache_meta_changed', 10, 3 );
add_action( 'updated_post_meta', 'pulse_cache_meta_changed', 10, 3 );
add_action( 'deleted_post_meta', 'pulse_cache_meta_changed', 10, 3 );
function pulse_cache_meta_changed( $meta_id, $post_id, $key ) {
	if ( in_array( $key, array( PULSE_PIN_KEY, PULSE_PIN_ORDER, PULSE_SPONSOR_KEY ), true ) ) {
		pulse_cache_option_changed( PULSE_ADS );
	}
}

add_action( 'updated_option', 'pulse_cache_option_changed' );
add_action( 'added_option', 'pulse_cache_option_changed' );
function pulse_cache_option_changed( $option ) {
	if ( in_array( $option, pulse_cache_watched_options(), true ) && ! has_action( 'shutdown', 'pulse_purge_page_cache' ) ) {
		add_action( 'shutdown', 'pulse_purge_page_cache' ); // Once per request, after all saves.
	}
}

/**
 * Clear the full-page cache of whichever caching plugin is active.
 */
function pulse_purge_page_cache() {
	do_action( 'litespeed_purge_all' );                       // LiteSpeed Cache.
	if ( function_exists( 'rocket_clean_domain' ) ) {         // WP Rocket.
		rocket_clean_domain();
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {              // W3 Total Cache.
		w3tc_flush_all();
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {        // WP Super Cache.
		wp_cache_clear_cache();
	}
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {   // SiteGround Optimizer.
		sg_cachepress_purge_cache();
	}
	do_action( 'pulse_purged_page_cache' );
}

/**
 * Scheduled ads (start / end dates) change on a given day without anyone
 * saving anything, so the cache is also cleared once a day just after
 * midnight (site time) while any ad has dates set.
 */
add_action( 'init', 'pulse_cache_schedule' );
function pulse_cache_schedule() {
	$dated = false;
	foreach ( (array) get_option( PULSE_ADS, array() ) as $ad ) {
		if ( ! empty( $ad['enabled'] ) && ( ! empty( $ad['start'] ) || ! empty( $ad['end'] ) ) ) {
			$dated = true;
			break;
		}
	}
	$next = wp_next_scheduled( 'pulse_daily_purge' );
	if ( $dated && ! $next ) {
		wp_schedule_event( strtotime( 'tomorrow 00:05', current_time( 'timestamp' ) ) - ( (int) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS ), 'daily', 'pulse_daily_purge' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp
	} elseif ( ! $dated && $next ) {
		wp_clear_scheduled_hook( 'pulse_daily_purge' );
	}
}
add_action( 'pulse_daily_purge', 'pulse_purge_page_cache' );

/**
 * Optimisers (Cloudflare Rocket Loader, LiteSpeed JS defer/delay) rewrite
 * <script> tags so they run late or only after a visitor interacts. Ad
 * network code written with document.write or that measures the page then
 * shows nothing. These attributes tell them to leave ad scripts alone.
 */
function pulse_protect_ad_scripts( $html ) {
	return preg_replace_callback(
		'/<script\b(?![^>]*\bdata-cfasync=)([^>]*)>/i',
		function ( $m ) {
			return '<script data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1"' . $m[1] . '>';
		},
		(string) $html
	);
}
