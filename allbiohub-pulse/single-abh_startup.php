<?php
/**
 * Startup profiles from the AllBioHub Startup Database plugin. The plugin
 * draws the whole profile (title, logo, details) inside the content, so Pulse
 * only gives it a wide, clean column: no article byline, TOC or related posts.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div id="post-<?php the_ID(); ?>" <?php post_class( 'pulse-app' ); ?>>
		<div class="pulse-wrap">
			<?php pulse_breadcrumbs(); ?>
			<div class="entry-content">
				<?php the_content(); ?>
			</div>
		</div>
	</div>
	<?php
endwhile;

get_footer();
