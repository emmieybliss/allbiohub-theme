<?php
/**
 * Article header: breadcrumbs, kicker, headline, standfirst, byline row, share.
 *
 * Args: bio (bool) for the biography layout.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$post_id   = get_the_ID();
$subtitle  = pulse_subtitle( $post_id );
$sponsored = pulse_is_sponsored( $post_id );
$author    = (int) get_post_field( 'post_author', $post_id );
$modified  = get_post_modified_time( 'U', true, $post_id ) - get_post_time( 'U', true, $post_id ) > DAY_IN_SECONDS;
?>
<header class="pulse-art__head">
	<?php pulse_breadcrumbs(); ?>
	<div class="pulse-story__kicker">
		<?php echo $sponsored ? pulse_sponsored_badge( $post_id, true ) : pulse_category_label( $post_id ); // phpcs:ignore ?>
	</div>
	<h1 class="pulse-art__title"><?php the_title(); ?></h1>
	<?php if ( $subtitle ) : ?>
		<p class="pulse-art__dek"><?php echo esc_html( $subtitle ); ?></p>
	<?php endif; ?>
	<div class="pulse-art__byline">
		<?php if ( $author ) : ?><a class="pulse-art__avatar" href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo get_avatar( $author, 44, '', '', array( 'loading' => 'eager' ) ); ?></a><?php endif; ?>
		<div class="pulse-art__who">
			<?php if ( pulse_byline( $post_id ) ) : ?>
				<span class="pulse-art__by"><?php esc_html_e( 'By', 'allbiohub-pulse' ); ?> <?php echo pulse_byline( $post_id ); // phpcs:ignore ?></span>
			<?php endif; ?>
			<span class="pulse-meta">
				<?php echo pulse_time( $post_id ); // phpcs:ignore ?>
				<?php if ( $modified ) : ?>
					<span class="pulse-meta__sep" aria-hidden="true">·</span>
					<span><?php esc_html_e( 'Updated', 'allbiohub-pulse' ); ?> <time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_modified_date() ); ?></time></span>
				<?php endif; ?>
				<span class="pulse-meta__sep" aria-hidden="true">·</span>
				<span><?php echo esc_html( pulse_reading_time( $post_id ) ); ?></span>
			</span>
		</div>
		<?php if ( pulse_opt( 'article_share' ) ) : ?>
			<?php pulse_part( 'article/share' ); ?>
		<?php endif; ?>
	</div>
</header>
