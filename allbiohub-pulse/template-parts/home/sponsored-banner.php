<?php
/**
 * Homepage sponsored banner. When the placement is empty, editors with the
 * right capability see where it would go; visitors see nothing.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$ad = pulse_render_ad( 'home_banner', 'banner' );
if ( $ad ) {
	echo '<div class="pulse-section pulse-wrap">' . $ad . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
	return;
}
if ( current_user_can( 'manage_options' ) ) {
	printf(
		'<div class="pulse-section pulse-wrap"><a class="pulse-adslot-hint" href="%s">%s</a></div>',
		esc_url( admin_url( 'admin.php?page=allbiohub-ads&slot=home_banner' ) ),
		esc_html__( 'Homepage sponsored banner is empty. Set it up (only admins see this).', 'allbiohub-pulse' )
	);
}
