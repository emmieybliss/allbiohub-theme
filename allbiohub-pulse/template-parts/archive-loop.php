<?php
/**
 * Main-query story list for archives and search: a lead story on page one,
 * then a grid, with an inline ad after the first row, then pagination.
 *
 * Args: lead (bool), empty (string message).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$lead = ! isset( $args['lead'] ) || $args['lead'];
$lead = $lead && ! is_paged();

if ( ! have_posts() ) {
	echo '<div class="pulse-empty"><p>' . esc_html( ! empty( $args['empty'] ) ? $args['empty'] : __( 'Nothing here yet.', 'allbiohub-pulse' ) ) . '</p>';
	get_search_form();
	echo '</div>';
	return;
}

$i = 0;
echo '<div class="pulse-list">';
while ( have_posts() ) {
	the_post();
	if ( 0 === $i && $lead ) {
		echo '<div class="pulse-list__lead">';
		pulse_part( 'story-card', array( 'post' => get_the_ID(), 'variant' => 'feature', 'size' => 'pulse-hero', 'excerpt' => true, 'heading' => 'h2', 'loading' => 'eager' ) );
		echo '</div><div class="pulse-grid pulse-grid--3">';
	} else {
		if ( 0 === $i ) {
			echo '<div class="pulse-grid pulse-grid--3">';
		}
		pulse_part( 'story-card', array( 'post' => get_the_ID(), 'excerpt' => true, 'heading' => 'h2', 'loading' => $i < 4 ? 'eager' : 'lazy' ) );
		if ( ( $lead ? 3 : 2 ) === $i ) {
			$ad = pulse_render_ad( 'inline', 'banner' );
			if ( $ad ) {
				echo '<div class="pulse-grid__full">' . $ad . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput
			}
		}
	}
	$i++;
}
echo '</div></div>';

the_posts_pagination(
	array(
		'class'              => 'pulse-paging',
		'mid_size'           => 1,
		'prev_text'          => '<span aria-hidden="true">←</span> ' . esc_html__( 'Newer', 'allbiohub-pulse' ),
		'next_text'          => esc_html__( 'Older', 'allbiohub-pulse' ) . ' <span aria-hidden="true">→</span>',
		'screen_reader_text' => esc_html__( 'More stories', 'allbiohub-pulse' ),
	)
);
