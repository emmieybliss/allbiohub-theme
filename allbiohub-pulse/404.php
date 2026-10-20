<?php
/**
 * Not found: search plus the latest stories.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pulse_latest = new WP_Query( array( 'post_type' => 'post', 'posts_per_page' => 6, 'fields' => 'ids', 'no_found_rows' => true, 'ignore_sticky_posts' => true ) );
?>
<div class="pulse-wrap pulse-404">
	<p class="pulse-404__code" aria-hidden="true">404</p>
	<h1 class="pulse-404__title"><?php esc_html_e( 'This page has moved or no longer exists', 'allbiohub-pulse' ); ?></h1>
	<p class="pulse-404__text"><?php esc_html_e( 'Search for the person or story you were looking for, or start with the latest from AllBioHub.', 'allbiohub-pulse' ); ?></p>
	<div class="pulse-404__form"><?php get_search_form(); ?></div>
	<?php if ( $pulse_latest->posts ) : ?>
		<section class="pulse-section" aria-labelledby="pulse-404-latest">
			<header class="pulse-sechead"><h2 class="pulse-sechead__title" id="pulse-404-latest"><?php esc_html_e( 'Latest stories', 'allbiohub-pulse' ); ?></h2></header>
			<div class="pulse-grid pulse-grid--3">
				<?php foreach ( $pulse_latest->posts as $pulse_id ) : ?>
					<?php pulse_part( 'story-card', array( 'post' => $pulse_id ) ); ?>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endif; ?>
</div>
<?php
get_footer();
