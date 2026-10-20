<?php
/**
 * Search form.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$pulse_uid = wp_unique_id( 'pulse-s-' );
?>
<form role="search" method="get" class="pulse-searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="screen-reader-text" for="<?php echo esc_attr( $pulse_uid ); ?>"><?php esc_html_e( 'Search for:', 'allbiohub-pulse' ); ?></label>
	<?php echo pulse_icon( 'search', 20 ); // phpcs:ignore ?>
	<input type="search" id="<?php echo esc_attr( $pulse_uid ); ?>" class="pulse-searchform__input" placeholder="<?php esc_attr_e( 'Search people, stories, topics…', 'allbiohub-pulse' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" autocomplete="off">
	<button type="submit" class="pulse-btn pulse-searchform__btn"><?php esc_html_e( 'Search', 'allbiohub-pulse' ); ?></button>
</form>
