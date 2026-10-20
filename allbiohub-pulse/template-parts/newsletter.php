<?php
/**
 * STAY UPDATED WITH ALLBIOHUB. Uses the existing provider form (Brevo).
 * Hidden from visitors until a form is configured.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$form = pulse_newsletter_form();
if ( ! $form && ! current_user_can( 'manage_options' ) ) {
	return;
}
?>
<section class="pulse-section pulse-wrap" aria-labelledby="pulse-news-title">
	<div class="pulse-news">
		<div class="pulse-news__copy">
			<span class="pulse-news__icon"><?php echo pulse_icon( 'mail', 22 ); // phpcs:ignore ?></span>
			<h2 class="pulse-news__title" id="pulse-news-title"><?php echo esc_html( pulse_opt( 'newsletter_title' ) ); ?></h2>
			<p class="pulse-news__text"><?php echo esc_html( pulse_opt( 'newsletter_text' ) ); ?></p>
		</div>
		<div class="pulse-news__action">
			<?php if ( $form ) : ?>
				<?php echo $form; // phpcs:ignore WordPress.Security.EscapeOutput -- provider shortcode or escaped form. ?>
			<?php else : ?>
				<a class="pulse-adslot-hint" href="<?php echo esc_url( admin_url( 'admin.php?page=allbiohub&tab=newsletter' ) ); ?>"><?php esc_html_e( 'Connect your Brevo form to show the signup here (only admins see this).', 'allbiohub-pulse' ); ?></a>
			<?php endif; ?>
			<?php pulse_the_ad( 'newsletter_sponsor', 'line' ); ?>
		</div>
	</div>
</section>
