<?php
/**
 * Newsletter block, connected to the site's existing Brevo account.
 *
 * In order of preference:
 * 1. A Brevo list ID in Theme Settings → the theme's own signup box adds the
 *    reader to that list through the Brevo API (key from the Brevo plugin,
 *    or pasted in Theme Settings).
 * 2. A form shortcode pasted in Theme Settings.
 * 3. The first form made in the Brevo plugin, found automatically.
 * 4. A plain form posting to a custom action URL.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Brevo API key: Theme Settings first, else the one the Brevo plugin saved.
 */
function pulse_brevo_key() {
	$key = trim( (string) pulse_opt( 'newsletter_brevo_key' ) );
	if ( $key ) {
		return $key;
	}
	$main = get_option( 'sib_main_option' );
	if ( is_array( $main ) && ! empty( $main['access_key'] ) ) {
		return (string) $main['access_key'];
	}
	return '';
}

/**
 * First form made in the Brevo plugin, as a shortcode, or ''.
 */
function pulse_brevo_plugin_form() {
	if ( ! shortcode_exists( 'sibwp_form' ) ) {
		return '';
	}
	global $wpdb;
	$table = $wpdb->prefix . 'sib_model_forms';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) { // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return '';
	}
	$id = (int) $wpdb->get_var( "SELECT MIN(id) FROM {$table}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return $id ? '[sibwp_form id=' . $id . ']' : '';
}

/**
 * Can the theme's own box add readers to Brevo?
 */
function pulse_brevo_direct() {
	return (int) pulse_opt( 'newsletter_brevo_list' ) > 0 && '' !== pulse_brevo_key();
}

/**
 * Is a newsletter form configured?
 */
function pulse_newsletter_ready() {
	return '' !== pulse_newsletter_form();
}

/**
 * The form markup for the first configured option above.
 */
function pulse_newsletter_form() {
	$shortcode = trim( (string) pulse_opt( 'newsletter_shortcode' ) );
	if ( ! pulse_brevo_direct() && $shortcode ) {
		return '<div class="pulse-news__provider">' . do_shortcode( $shortcode ) . '</div>';
	}
	$action = pulse_opt( 'newsletter_action' );
	if ( ! pulse_brevo_direct() && ! $action ) {
		$plugin_form = pulse_brevo_plugin_form();
		return $plugin_form ? '<div class="pulse-news__provider">' . do_shortcode( $plugin_form ) . '</div>' : '';
	}
	$direct = pulse_brevo_direct();
	$id     = wp_unique_id( 'pulse-news-email-' );
	return sprintf(
		'<form class="pulse-news__form" method="post" action="%1$s"%6$s><label class="screen-reader-text" for="%2$s">%3$s</label><input id="%2$s" type="email" name="%7$s" required autocomplete="email" placeholder="%4$s"><input type="text" name="email_address_check" value="" tabindex="-1" autocomplete="off" class="pulse-hp" aria-hidden="true"><button class="pulse-btn" type="submit">%5$s</button></form><p class="pulse-news__msg" role="status" aria-live="polite" hidden></p>',
		esc_url( $direct ? rest_url( 'allbiohub/v1/subscribe' ) : $action ),
		esc_attr( $id ),
		esc_html__( 'Email address', 'allbiohub-pulse' ),
		esc_attr__( 'Your email address', 'allbiohub-pulse' ),
		esc_html__( 'Subscribe', 'allbiohub-pulse' ),
		$direct ? ' data-pulse-subscribe' : ' target="_blank"',
		$direct ? 'email' : 'EMAIL'
	);
}

add_action( 'rest_api_init', 'pulse_newsletter_route' );
function pulse_newsletter_route() {
	register_rest_route(
		'allbiohub/v1',
		'/subscribe',
		array(
			'methods'             => 'POST',
			'callback'            => 'pulse_newsletter_subscribe',
			'permission_callback' => '__return_true',
		)
	);
}

/**
 * Add one reader to the Brevo list. Already-subscribed readers get the same
 * friendly answer, so the form never reveals who is on the list.
 */
function pulse_newsletter_subscribe( WP_REST_Request $req ) {
	$fail = function ( $msg, $code = 400 ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => $msg ), $code );
	};
	if ( ! pulse_brevo_direct() ) {
		return $fail( __( 'Signup is not available right now.', 'allbiohub-pulse' ), 503 );
	}
	if ( '' !== (string) $req->get_param( 'email_address_check' ) ) {
		return new WP_REST_Response( array( 'ok' => true, 'message' => __( 'Thanks for subscribing!', 'allbiohub-pulse' ) ), 200 );
	}
	$email = sanitize_email( (string) $req->get_param( 'email' ) );
	if ( ! is_email( $email ) ) {
		return $fail( __( 'Please enter a valid email address.', 'allbiohub-pulse' ) );
	}

	// At most 5 attempts per visitor per 10 minutes.
	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$limit = 'pulse_nl_' . md5( $ip );
	$tries = (int) get_transient( $limit );
	if ( $tries >= 5 ) {
		return $fail( __( 'Too many attempts. Please try again in a few minutes.', 'allbiohub-pulse' ), 429 );
	}
	set_transient( $limit, $tries + 1, 10 * MINUTE_IN_SECONDS );

	$res  = wp_remote_post(
		'https://api.brevo.com/v3/contacts',
		array(
			'timeout' => 10,
			'headers' => array(
				'api-key'      => pulse_brevo_key(),
				'accept'       => 'application/json',
				'content-type' => 'application/json',
			),
			'body'    => wp_json_encode(
				array(
					'email'         => $email,
					'listIds'       => array( (int) pulse_opt( 'newsletter_brevo_list' ) ),
					'updateEnabled' => true,
				)
			),
		)
	);
	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( is_wp_error( $res ) || $code >= 300 ) {
		$body = is_wp_error( $res ) ? $res->get_error_message() : wp_remote_retrieve_body( $res );
		update_option( 'pulse_newsletter_last_error', array( 'time' => time(), 'code' => $code, 'body' => substr( (string) $body, 0, 300 ) ), false );
		return $fail( __( 'Sorry, we could not sign you up just now. Please try again later.', 'allbiohub-pulse' ), 502 );
	}
	delete_option( 'pulse_newsletter_last_error' );
	return new WP_REST_Response( array( 'ok' => true, 'message' => __( 'Thanks for subscribing!', 'allbiohub-pulse' ) ), 200 );
}
