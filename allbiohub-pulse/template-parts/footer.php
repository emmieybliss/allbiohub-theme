<?php
/**
 * Footer body: brand, quick links, categories, social.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;
?>
<footer class="pulse-footer">
	<div class="pulse-wrap pulse-footer__grid">
		<div class="pulse-footer__brand">
			<?php pulse_part( 'logo', array( 'no_h1' => true, 'on_dark' => true ) ); ?>
			<p class="pulse-footer__tagline"><?php echo esc_html( pulse_opt( 'tagline' ) ); ?></p>
			<p class="pulse-footer__about"><?php echo esc_html( pulse_opt( 'footer_about' ) ); ?></p>
			<?php pulse_part( 'social' ); ?>
		</div>

		<nav class="pulse-footer__col" aria-label="<?php esc_attr_e( 'Quick links', 'allbiohub-pulse' ); ?>">
			<h2 class="pulse-footer__title"><?php esc_html_e( 'Quick links', 'allbiohub-pulse' ); ?></h2>
			<?php
			if ( has_nav_menu( 'footer-1' ) ) {
				wp_nav_menu( array( 'theme_location' => 'footer-1', 'container' => false, 'depth' => 1, 'fallback_cb' => false ) );
			} else {
				pulse_footer_page_list( array( 'about-us', 'meet-our-team', 'contact-us', 'submit-a-story', 'advertise-with-us', 'guest-post-rate-card', 'startups', 'donation' ) );
			}
			?>
		</nav>

		<nav class="pulse-footer__col" aria-label="<?php esc_attr_e( 'Categories', 'allbiohub-pulse' ); ?>">
			<h2 class="pulse-footer__title"><?php esc_html_e( 'Categories', 'allbiohub-pulse' ); ?></h2>
			<ul>
				<?php
				foreach ( array( 'celebrity', 'biography', 'business', 'technology', 'women', 'news', 'lifestyle', 'reviews' ) as $key ) {
					$link = pulse_section_link( $key );
					if ( $link ) {
						printf( '<li><a href="%s">%s</a></li>', esc_url( $link ), esc_html( pulse_section_label( $key ) ) );
					}
				}
				if ( post_type_exists( 'abh_video' ) && wp_count_posts( 'abh_video' )->publish ) {
					printf( '<li><a href="%s">%s</a></li>', esc_url( get_post_type_archive_link( 'abh_video' ) ), esc_html__( 'Video', 'allbiohub-pulse' ) );
				}
				?>
			</ul>
		</nav>

		<nav class="pulse-footer__col" aria-label="<?php esc_attr_e( 'Policies', 'allbiohub-pulse' ); ?>">
			<?php if ( has_nav_menu( 'footer-2' ) ) : ?>
				<h2 class="pulse-footer__title"><?php esc_html_e( 'More', 'allbiohub-pulse' ); ?></h2>
				<?php wp_nav_menu( array( 'theme_location' => 'footer-2', 'container' => false, 'depth' => 1, 'fallback_cb' => false ) ); ?>
			<?php else : ?>
				<h2 class="pulse-footer__title"><?php esc_html_e( 'Policies', 'allbiohub-pulse' ); ?></h2>
				<?php pulse_footer_page_list( pulse_policy_slugs() ); ?>
			<?php endif; ?>
		</nav>

		<?php if ( is_active_sidebar( 'pulse-footer' ) ) : ?>
			<div class="pulse-footer__col"><?php dynamic_sidebar( 'pulse-footer' ); ?></div>
		<?php endif; ?>
	</div>
	<div class="pulse-wrap pulse-footer__base">
		<p>&copy; <?php echo esc_html( wp_date( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?>. <?php echo esc_html( pulse_opt( 'tagline' ) ); ?></p>
		<a href="#content" class="pulse-footer__top"><?php esc_html_e( 'Back to top', 'allbiohub-pulse' ); ?> ↑</a>
	</div>
</footer>
