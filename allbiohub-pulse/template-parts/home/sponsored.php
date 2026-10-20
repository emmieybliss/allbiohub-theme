<?php
/**
 * Sponsored / Featured Content: the Sponsored Content panel plus an optional
 * sponsored story card. Hidden entirely when neither placement is active.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$panel = pulse_render_ad( 'sponsored', 'panel' );
$story = pulse_render_ad( 'home_story', 'card' );
if ( ! $panel && ! $story ) {
	return;
}
?>
<section class="pulse-section pulse-sponsorzone pulse-wrap" aria-label="<?php esc_attr_e( 'Sponsored content', 'allbiohub-pulse' ); ?>">
	<div class="pulse-sponsorzone__grid<?php echo $panel && $story ? ' has-two' : ''; ?>">
		<?php echo $panel . $story; // phpcs:ignore WordPress.Security.EscapeOutput -- built by pulse_render_ad. ?>
	</div>
</section>
