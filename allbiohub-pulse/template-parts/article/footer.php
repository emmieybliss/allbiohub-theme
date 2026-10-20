<?php
/**
 * After the article body: tags, share, author box.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$author = (int) get_post_field( 'post_author', get_the_ID() );
$bio    = get_the_author_meta( 'description', $author );
$tags   = get_the_tags();
?>
<footer class="pulse-art__foot">
	<?php if ( $tags ) : ?>
		<ul class="pulse-tags" aria-label="<?php esc_attr_e( 'Tags', 'allbiohub-pulse' ); ?>">
			<?php foreach ( $tags as $tag ) : ?>
				<li><a href="<?php echo esc_url( get_tag_link( $tag ) ); ?>"><?php echo esc_html( $tag->name ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>

	<?php if ( pulse_opt( 'article_share' ) ) : ?>
		<div class="pulse-art__sharefoot">
			<span><?php esc_html_e( 'Share this story', 'allbiohub-pulse' ); ?></span>
			<?php pulse_part( 'article/share' ); ?>
		</div>
	<?php endif; ?>

	<section class="pulse-authorbox" aria-label="<?php esc_attr_e( 'About the author', 'allbiohub-pulse' ); ?>">
		<a href="<?php echo esc_url( get_author_posts_url( $author ) ); ?>" tabindex="-1" aria-hidden="true"><?php echo get_avatar( $author, 72 ); ?></a>
		<div>
			<p class="pulse-authorbox__label"><?php esc_html_e( 'Written by', 'allbiohub-pulse' ); ?></p>
			<p class="pulse-authorbox__name"><?php echo pulse_byline(); // phpcs:ignore ?></p>
			<?php if ( $bio ) : ?>
				<p class="pulse-authorbox__bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>
		</div>
	</section>
</footer>
