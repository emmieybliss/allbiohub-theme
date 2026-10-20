<?php
/**
 * Biography story: portrait header, Quick Profile, chapter navigation.
 * Uses the post's own content; nothing is restructured or rewritten.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pulse_name = pulse_person_name();
	$pulse_role = pulse_person_descriptor();
	$pulse_img  = (int) get_post_thumbnail_id();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pulse-art pulse-art--bio' ); ?> data-pulse-view="<?php the_ID(); ?>">
		<div class="pulse-biohead">
			<div class="pulse-wrap pulse-biohead__grid">
				<div class="pulse-biohead__text">
					<?php pulse_part( 'article/header', array( 'bio' => true ) ); ?>
				</div>
				<?php if ( $pulse_img ) : ?>
					<figure class="pulse-biohead__portrait">
						<?php echo wp_get_attachment_image( $pulse_img, 'pulse-portrait', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1024px) 420px, 100vw' ) ); ?>
						<?php if ( $pulse_name ) : ?>
							<figcaption><span class="pulse-biohead__name"><?php echo esc_html( $pulse_name ); ?></span><?php if ( $pulse_role ) : ?><span class="pulse-biohead__role"><?php echo esc_html( $pulse_role ); ?></span><?php endif; ?></figcaption>
						<?php endif; ?>
					</figure>
				<?php endif; ?>
			</div>
		</div>
		<div class="pulse-wrap pulse-art__layout">
			<div class="pulse-art__main">
				<?php pulse_the_ad( 'article_top', 'inline' ); ?>
				<?php pulse_part( 'article/profile' ); ?>
				<?php pulse_part( 'article/toc', array( 'title' => __( 'Chapters', 'allbiohub-pulse' ) ) ); ?>
				<div class="pulse-prose entry-content">
					<?php
					the_content();
					wp_link_pages( array( 'before' => '<nav class="pulse-pages">', 'after' => '</nav>' ) );
					?>
				</div>
				<?php pulse_the_ad( 'article_bottom', 'inline' ); ?>
				<?php pulse_part( 'article/footer' ); ?>
				<?php pulse_part( 'newsletter' ); ?>
				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>
			<?php pulse_part( 'sidebar', array( 'area' => 'pulse-article', 'exclude' => array( get_the_ID() ) ) ); ?>
		</div>
		<div class="pulse-wrap">
			<?php pulse_part( 'article/related', array( 'title' => __( 'More biographies', 'allbiohub-pulse' ) ) ); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
