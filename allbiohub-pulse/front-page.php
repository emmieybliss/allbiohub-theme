<?php
/**
 * AllBioHub homepage. Each section is a template part, ordered and toggled in
 * AllBioHub → Theme Settings → Homepage. Stories never repeat across sections.
 *
 * The site's existing Elementor front page (page 9822) is not edited; switching
 * back to the previous theme restores it exactly.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pulse_parts = array(
	'hero'             => 'home/hero',
	'pinned'           => 'home/pinned',
	'sponsored'        => 'home/sponsored',
	'biography'        => 'home/biography',
	'video'            => 'home/video',
	'categories'       => 'home/categories',
	'sponsored_banner' => 'home/sponsored-banner',
	'newsletter'       => 'newsletter',
);

echo '<div class="pulse-home">';
foreach ( pulse_opt_slugs( 'home_order' ) as $pulse_key ) {
	if ( isset( $pulse_parts[ $pulse_key ] ) && pulse_opt( 'home_' . $pulse_key ) ) {
		pulse_part( $pulse_parts[ $pulse_key ], array( 'context' => 'home' ) );
	}
}
echo '</div>';

get_footer();
