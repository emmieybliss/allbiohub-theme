<?php
/**
 * Newsletter block. Uses the site's existing provider (Brevo) rather than
 * adding a new one: paste the Brevo form shortcode in Theme Settings, or a
 * form action URL for any provider.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Is a newsletter form configured?
 */
function pulse_newsletter_ready() {
	return '' !== trim( (string) pulse_opt( 'newsletter_shortcode' ) ) || '' !== trim( (string) pulse_opt( 'newsletter_action' ) );
}

/**
 * The form markup: provider shortcode, or a plain email form posting to the
 * configured action URL.
 */
function pulse_newsletter_form() {
	$shortcode = trim( (string) pulse_opt( 'newsletter_shortcode' ) );
	if ( $shortcode ) {
		return '<div class="pulse-news__provider">' . do_shortcode( $shortcode ) . '</div>';
	}
	$action = pulse_opt( 'newsletter_action' );
	if ( ! $action ) {
		return '';
	}
	$id = wp_unique_id( 'pulse-news-email-' );
	return sprintf(
		'<form class="pulse-news__form" method="post" action="%1$s" target="_blank"><label class="screen-reader-text" for="%2$s">%3$s</label><input id="%2$s" type="email" name="EMAIL" required autocomplete="email" placeholder="%4$s"><input type="text" name="email_address_check" value="" tabindex="-1" autocomplete="off" class="pulse-hp" aria-hidden="true"><button class="pulse-btn" type="submit">%5$s</button></form>',
		esc_url( $action ),
		esc_attr( $id ),
		esc_html__( 'Email address', 'allbiohub-pulse' ),
		esc_attr__( 'Your email address', 'allbiohub-pulse' ),
		esc_html__( 'Subscribe', 'allbiohub-pulse' )
	);
}
