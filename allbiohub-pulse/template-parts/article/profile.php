<?php
/**
 * Biography Quick Profile, from the post's own Quick Profile fields.
 * Hidden when no field has been filled in.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$profile = array_filter( array_map( 'trim', pulse_profile( get_the_ID() ) ) );
if ( ! $profile ) {
	return;
}
?>
<aside class="pulse-profile" aria-labelledby="pulse-profile-title">
	<h2 class="pulse-profile__title" id="pulse-profile-title"><?php esc_html_e( 'Quick profile', 'allbiohub-pulse' ); ?></h2>
	<dl class="pulse-profile__list">
		<?php foreach ( pulse_profile_fields() as $key => $label ) : ?>
			<?php if ( ! empty( $profile[ $key ] ) ) : ?>
				<div class="pulse-profile__row"><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $profile[ $key ] ); ?></dd></div>
			<?php endif; ?>
		<?php endforeach; ?>
	</dl>
</aside>
