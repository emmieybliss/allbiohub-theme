<?php
/**
 * AllBioHub Pulse bootstrap.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

define( 'PULSE_VERSION', '1.4.0' );
define( 'PULSE_DIR', get_template_directory() );
define( 'PULSE_URI', get_template_directory_uri() );

$pulse_modules = array(
	'helpers',
	'options',
	'setup',
	'enqueue',
	'views',
	'sections',
	'pinned-stories',
	'trending',
	'biography',
	'people',
	'video',
	'sponsored',
	'advertising',
	'newsletter',
	'seo',
	'performance',
	'theme-options',
	'brand',
	'migration',
);

foreach ( $pulse_modules as $pulse_module ) {
	require PULSE_DIR . '/inc/' . $pulse_module . '.php';
}
unset( $pulse_modules, $pulse_module );
