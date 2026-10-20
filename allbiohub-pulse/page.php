<?php
/**
 * Pages (About, Contact, Privacy…). Content is rendered exactly as authored,
 * including Elementor layouts, which take the full width. Pages built from
 * a plugin's shortcode or block (the Startup Database pages) get a wide,
 * unstyled column so the plugin's own layout is not squeezed or restyled.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pulse_app     = pulse_is_app_page();
	$pulse_builder = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->documents->get( get_the_ID() ) && \Elementor\Plugin::$instance->documents->get( get_the_ID() )->is_built_with_elementor();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pulse-page' . ( $pulse_builder ? ' is-builder' : '' ) ); ?>>
		<?php if ( $pulse_builder ) : ?>
			<?php the_content(); ?>
		<?php elseif ( $pulse_app ) : ?>
			<div class="pulse-wrap pulse-app">
				<header class="pulse-page__head">
					<h1 class="pulse-page__title"><?php the_title(); ?></h1>
				</header>
				<div class="entry-content">
					<?php the_content(); ?>
				</div>
			</div>
		<?php else : ?>
			<div class="pulse-wrap pulse-wrap--narrow">
				<header class="pulse-page__head">
					<h1 class="pulse-page__title"><?php the_title(); ?></h1>
				</header>
				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="pulse-art__media"><?php the_post_thumbnail( 'pulse-hero', array( 'loading' => 'eager' ) ); ?></figure>
				<?php endif; ?>
				<div class="pulse-prose entry-content">
					<?php
					the_content();
					wp_link_pages( array( 'before' => '<nav class="pulse-pages">', 'after' => '</nav>' ) );
					?>
				</div>
				<?php
				if ( comments_open() || get_comments_number() ) {
					comments_template();
				}
				?>
			</div>
		<?php endif; ?>
	</article>
	<?php
endwhile;

get_footer();
