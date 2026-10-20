<?php
/**
 * Pinned Stories (3–5, admin-ordered). Falls back to the latest stories when
 * nothing is pinned, without numbering them. The heading is always
 * "Pinned Stories" so phones and desktops match.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$pinned = array_values( array_diff( pulse_pinned_ids(), array( pulse_hero_id() ) ) );
$is_pin = (bool) $pinned;
if ( ! $is_pin ) {
	$q      = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 4, 'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true, 'post__not_in' => pulse_seen() ) );
	$pinned = array_map( 'intval', $q->posts );
}
if ( ! $pinned ) {
	return;
}
pulse_seen( $pinned );
?>
<section class="pulse-section pulse-pinned pulse-wrap" aria-labelledby="pulse-pinned-title">
	<header class="pulse-sechead">
		<h2 class="pulse-sechead__title" id="pulse-pinned-title">
			<?php echo pulse_icon( 'pin', 18 ); // phpcs:ignore ?> <?php esc_html_e( 'Pinned Stories', 'allbiohub-pulse' ); ?>
		</h2>
	</header>
	<div class="pulse-rail pulse-pinned__grid" data-count="<?php echo esc_attr( count( $pinned ) ); ?>">
		<?php foreach ( $pinned as $i => $id ) : ?>
			<?php pulse_part( 'story-card', array( 'post' => $id, 'variant' => 'card', 'pin' => $is_pin ? $i + 1 : 0 ) ); ?>
		<?php endforeach; ?>
	</div>
</section>
