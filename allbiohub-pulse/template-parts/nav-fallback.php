<?php
/**
 * Navigation before a menu is assigned: built from the section map, so it
 * links only to categories that really exist.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$class = isset( $args['class'] ) ? $args['class'] : 'pulse-nav__list';
echo '<ul class="' . esc_attr( $class ) . '">';
echo '<li class="menu-item' . ( is_front_page() ? ' current-menu-item' : '' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'allbiohub-pulse' ) . '</a></li>';
foreach ( array( 'celebrity', 'biography', 'business', 'technology', 'women', 'news', 'lifestyle', 'reviews' ) as $key ) {
	$link = pulse_section_link( $key );
	if ( $link ) {
		$current = is_category() && pulse_section_for_term( get_queried_object() ) === $key;
		printf( '<li class="menu-item%s"><a href="%s">%s</a></li>', $current ? ' current-menu-item' : '', esc_url( $link ), esc_html( preg_replace( '/ (News|& Startups)$/', '', pulse_section_label( $key ) ) ) );
	}
}
echo '</ul>';
