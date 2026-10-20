<?php
/**
 * Related stories: same primary category first, then shared tags.
 *
 * Args: title (string).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$post_id = get_the_ID();
$count   = max( 3, min( 6, (int) pulse_opt( 'article_related' ) ) );
$key     = 'pulse_rel_' . $post_id . '_' . $count;
$ids     = get_transient( $key );
if ( false === $ids ) {
	$ids  = array();
	$base = array( 'post_type' => 'post', 'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true, 'post_status' => 'publish' );
	$cat  = pulse_primary_category( $post_id );
	if ( $cat ) {
		$q   = new WP_Query( $base + array( 'posts_per_page' => $count, 'cat' => $cat->term_id, 'post__not_in' => array( $post_id ) ) );
		$ids = $q->posts;
	}
	$tags = wp_get_post_tags( $post_id, array( 'fields' => 'ids' ) );
	if ( count( $ids ) < $count && $tags ) {
		$q   = new WP_Query( $base + array( 'posts_per_page' => $count - count( $ids ), 'tag__in' => $tags, 'post__not_in' => array_merge( array( $post_id ), $ids ) ) );
		$ids = array_merge( $ids, $q->posts );
	}
	if ( count( $ids ) < 3 ) {
		$q   = new WP_Query( $base + array( 'posts_per_page' => $count - count( $ids ), 'post__not_in' => array_merge( array( $post_id ), $ids ) ) );
		$ids = array_merge( $ids, $q->posts );
	}
	$ids = array_map( 'intval', $ids );
	set_transient( $key, $ids, 6 * HOUR_IN_SECONDS );
}
$ids = pulse_published_only( $ids );
if ( ! $ids ) {
	return;
}
?>
<section class="pulse-section pulse-related" aria-labelledby="pulse-related-title">
	<header class="pulse-sechead"><h2 class="pulse-sechead__title" id="pulse-related-title"><?php echo esc_html( ! empty( $args['title'] ) ? $args['title'] : __( 'Related stories', 'allbiohub-pulse' ) ); ?></h2></header>
	<div class="pulse-grid pulse-grid--3">
		<?php foreach ( $ids as $id ) : ?>
			<?php pulse_part( 'story-card', array( 'post' => $id ) ); ?>
		<?php endforeach; ?>
	</div>
</section>
