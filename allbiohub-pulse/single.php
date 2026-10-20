<?php
/**
 * Single story.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pulse-art' ); ?> data-pulse-view="<?php the_ID(); ?>">
		<div class="pulse-wrap pulse-art__top">
			<?php pulse_part( 'article/header' ); ?>
			<?php pulse_part( 'article/media' ); ?>
		</div>
		<div class="pulse-wrap pulse-art__layout">
			<div class="pulse-art__main">
				<?php pulse_the_ad( 'article_top', 'inline' ); ?>
				<?php pulse_part( 'article/toc' ); ?>
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
			<?php pulse_part( 'article/related' ); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
