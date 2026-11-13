<?php
/**
 * Ad code guard. On allbiohub.com something outside WordPress (most likely
 * the host's malware scanner, Imunify360, which runs on this server) deletes
 * <script src="…"> tags it flags, such as Adsterra's invoke.js, straight from
 * the database a few days after the code is saved. It happened with Foxiz
 * and Ad Inserter too, so it is not the theme.
 *
 * Pulse remembers the script URLs of each placement every time the ads are
 * saved. If a URL later disappears without Pulse saving the change, the
 * admin is told which placement lost which script, and can put them back
 * with one click (they are re-added at the end of that placement's code).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_AD_GUARD = 'allbiohub_ad_script_urls';

/**
 * Script URLs in a piece of ad code.
 *
 * @return string[]
 */
function pulse_ad_script_srcs( $html ) {
	preg_match_all( '/<script\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/is', (string) $html, $m );
	return array_values( array_unique( array_map( 'trim', $m[2] ) ) );
}

/**
 * Record the script URLs whenever Pulse saves the ads (any code path).
 */
add_action( 'added_option', 'pulse_ad_guard_record', 10, 2 );
add_action( 'updated_option', 'pulse_ad_guard_record_update', 10, 3 );
function pulse_ad_guard_record_update( $option, $old, $value ) {
	pulse_ad_guard_record( $option, $value );
}
function pulse_ad_guard_record( $option, $value ) {
	if ( PULSE_ADS !== $option ) {
		return;
	}
	$urls = array();
	foreach ( (array) $value as $slot => $ad ) {
		if ( is_array( $ad ) && ! empty( $ad['html'] ) ) {
			$srcs = pulse_ad_script_srcs( $ad['html'] );
			if ( $srcs ) {
				$urls[ $slot ] = $srcs;
			}
		}
	}
	update_option( PULSE_AD_GUARD, array( 'time' => time(), 'urls' => $urls ), false );
}

/**
 * Placements whose script URLs were removed outside Pulse.
 *
 * @return array<string,string[]> slot => missing URLs
 */
function pulse_ad_guard_missing() {
	$saved = get_option( PULSE_AD_GUARD );
	if ( empty( $saved['urls'] ) ) {
		return array();
	}
	$ads = (array) get_option( PULSE_ADS, array() );
	$out = array();
	foreach ( (array) $saved['urls'] as $slot => $urls ) {
		$now     = isset( $ads[ $slot ]['html'] ) ? pulse_ad_script_srcs( $ads[ $slot ]['html'] ) : array();
		$missing = array_values( array_diff( (array) $urls, $now ) );
		if ( $missing && isset( $ads[ $slot ] ) ) {
			$out[ $slot ] = $missing;
		}
	}
	return $out;
}

/**
 * Warn admins on the dashboard and AllBioHub screens, and note when it was
 * first seen so the owner can tell the host how often it happens.
 */
add_action( 'admin_notices', 'pulse_ad_guard_notice' );
function pulse_ad_guard_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$missing = pulse_ad_guard_missing();
	if ( ! $missing ) {
		return;
	}
	$log = (array) get_option( 'allbiohub_ad_guard_log', array() );
	$key = md5( wp_json_encode( $missing ) );
	if ( ! isset( $log[ $key ] ) ) {
		$log[ $key ] = time();
		update_option( 'allbiohub_ad_guard_log', array_slice( $log, -20, 20, true ), false );
	}
	$slots = pulse_ad_slots();
	echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'AllBioHub: some ad scripts were removed from your ad code outside WordPress.', 'allbiohub-pulse' ) . '</strong> ';
	echo esc_html__( 'This is usually the hosting malware scanner (Imunify360) treating ad network scripts as malware. Ask your host to mark them as a false positive, then restore them here.', 'allbiohub-pulse' ) . '</p><ul style="list-style:disc;margin-left:20px">';
	foreach ( $missing as $slot => $urls ) {
		foreach ( $urls as $url ) {
			printf( '<li>%s: <code>%s</code></li>', esc_html( isset( $slots[ $slot ] ) ? $slots[ $slot ][0] : $slot ), esc_html( $url ) );
		}
	}
	echo '</ul><p>' . esc_html( sprintf( /* translators: %s: date */ __( 'First noticed: %s', 'allbiohub-pulse' ), wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log[ $key ] ) ) ) . '</p>';
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="pulse_ad_guard_restore">';
	wp_nonce_field( 'pulse_ad_guard_restore' );
	echo '<p><button class="button button-primary">' . esc_html__( 'Restore the removed scripts', 'allbiohub-pulse' ) . '</button></p></form></div>';
}

add_action( 'admin_post_pulse_ad_guard_restore', 'pulse_ad_guard_restore' );
function pulse_ad_guard_restore() {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'unfiltered_html' ) ) {
		wp_die( esc_html__( 'You need administrator rights with permission to save scripts.', 'allbiohub-pulse' ), 403 );
	}
	check_admin_referer( 'pulse_ad_guard_restore' );
	$ads = (array) get_option( PULSE_ADS, array() );
	foreach ( pulse_ad_guard_missing() as $slot => $urls ) {
		foreach ( $urls as $url ) {
			$ads[ $slot ]['html'] = rtrim( (string) $ads[ $slot ]['html'] ) . "\n" . '<script type="text/javascript" src="' . esc_attr( $url ) . '"></script>';
		}
	}
	update_option( PULSE_ADS, $ads );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'admin.php?page=allbiohub-ads' ) );
	exit;
}
