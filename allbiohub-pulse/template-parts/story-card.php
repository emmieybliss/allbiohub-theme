<?php
/**
 * Story card used across the site.
 *
 * Args: post (ID), variant (card|row|feature|compact), size, excerpt (bool),
 *       number (int, for numbered lists), pin (int, pin position), heading (h2|h3|h4),
 *       loading (lazy|eager).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$args    = wp_parse_args(
	$args,
	array(
		'post'    => 0,
		'variant' => 'card',
		'size'    => 'pulse-card',
		'excerpt' => false,
		'number'  => 0,
		'pin'     => 0,
		'heading' => 'h3',
		'loading' => 'lazy',
	)
);
$post_id = (int) $args['post'];
if ( ! $post_id || ! get_post( $post_id ) ) {
	return;
}
$tag       = in_array( $args['heading'], array( 'h2', 'h3', 'h4' ), true ) ? $args['heading'] : 'h3';
$sponsored = pulse_is_sponsored( $post_id );
$classes   = array( 'pulse-story', 'pulse-story--' . $args['variant'] );
if ( $sponsored ) {
	$classes[] = 'is-sponsored';
}
if ( $args['pin'] ) {
	$classes[] = 'is-pinned';
}
?>
<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php if ( 'compact' !== $args['variant'] ) : ?>
		<a class="pulse-story__media" href="<?php echo esc_url( get_permalink( $post_id ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo pulse_image( $post_id, array( 'size' => $args['size'], 'loading' => $args['loading'] ) ); // phpcs:ignore ?>
			<?php if ( $args['pin'] ) : ?>
				<span class="pulse-pinbadge"><?php echo pulse_icon( 'pin', 13 ); // phpcs:ignore ?><span class="pulse-pinbadge__n"><?php echo esc_html( $args['pin'] ); ?></span><?php esc_html_e( 'Pinned', 'allbiohub-pulse' ); ?></span>
			<?php endif; ?>
			<?php if ( 'abh_video' === get_post_type( $post_id ) || ( function_exists( 'pulse_video_data' ) && ! empty( $args['video'] ) ) ) : ?>
				<span class="pulse-story__play"><?php echo pulse_icon( 'play', 18 ); // phpcs:ignore ?></span>
			<?php endif; ?>
		</a>
	<?php endif; ?>
	<div class="pulse-story__body">
		<?php if ( $args['number'] ) : ?>
			<span class="pulse-story__num" aria-hidden="true"><?php echo esc_html( sprintf( '%02d', $args['number'] ) ); ?></span>
		<?php endif; ?>
		<div class="pulse-story__kicker">
			<?php
			if ( $sponsored ) {
				echo pulse_sponsored_badge( $post_id ); // phpcs:ignore
			} else {
				echo pulse_category_label( $post_id ); // phpcs:ignore
			}
			?>
		</div>
		<<?php echo esc_html( $tag ); ?> class="pulse-story__title"><a href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></a></<?php echo esc_html( $tag ); ?>>
		<?php if ( $args['excerpt'] ) : ?>
			<p class="pulse-story__excerpt"><?php echo esc_html( pulse_subtitle( $post_id ) ? wp_trim_words( pulse_subtitle( $post_id ), 28, '…' ) : pulse_excerpt( $post_id ) ); ?></p>
		<?php endif; ?>
		<p class="pulse-meta">
			<?php echo pulse_time( $post_id ); // phpcs:ignore ?>
			<span class="pulse-meta__sep" aria-hidden="true">·</span>
			<span><?php echo esc_html( pulse_reading_time( $post_id ) ); ?></span>
		</p>
	</div>
</article>
