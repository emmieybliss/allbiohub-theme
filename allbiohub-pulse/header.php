<?php
/**
 * Site header: top ad, sticky bar, search overlay, mobile drawer, ticker.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#FBFAF7" media="(prefers-color-scheme: light)">
	<meta name="theme-color" content="#121014" media="(prefers-color-scheme: dark)">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="pulse-skip" href="#content"><?php esc_html_e( 'Skip to content', 'allbiohub-pulse' ); ?></a>

<?php pulse_the_ad( 'top', 'top' ); ?>

<header class="pulse-header" data-pulse-header>
	<div class="pulse-wrap pulse-header__bar">
		<button class="pulse-iconbtn pulse-header__menu" type="button" aria-controls="pulse-drawer" aria-expanded="false" data-pulse-drawer-open>
			<?php echo pulse_icon( 'menu', 22 ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Open menu', 'allbiohub-pulse' ); ?></span>
		</button>

		<?php pulse_part( 'logo' ); ?>

		<nav class="pulse-nav" aria-label="<?php esc_attr_e( 'Primary', 'allbiohub-pulse' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'pulse-nav__list',
						'depth'          => 2,
						'fallback_cb'    => false,
					)
				);
			} else {
				pulse_part( 'nav-fallback' );
			}
			?>
		</nav>

		<div class="pulse-header__actions">
			<?php if ( pulse_opt( 'header_search' ) ) : ?>
				<button class="pulse-iconbtn" type="button" aria-controls="pulse-search" aria-expanded="false" data-pulse-search-open>
					<?php echo pulse_icon( 'search', 20 ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Search', 'allbiohub-pulse' ); ?></span>
				</button>
			<?php endif; ?>
			<?php if ( pulse_opt( 'header_darkmode' ) ) : ?>
				<button class="pulse-iconbtn pulse-scheme" type="button" data-pulse-scheme aria-live="polite">
					<span class="pulse-scheme__light"><?php echo pulse_icon( 'moon', 20 ); // phpcs:ignore ?></span>
					<span class="pulse-scheme__dark"><?php echo pulse_icon( 'sun', 20 ); // phpcs:ignore ?></span>
					<span class="screen-reader-text" data-pulse-scheme-label><?php esc_html_e( 'Switch colour scheme', 'allbiohub-pulse' ); ?></span>
				</button>
			<?php endif; ?>
		</div>
	</div>

	<?php if ( pulse_opt( 'ticker_enabled' ) ) : ?>
		<?php pulse_part( 'trending' ); ?>
	<?php endif; ?>
</header>

<div class="pulse-search" id="pulse-search" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Search AllBioHub', 'allbiohub-pulse' ); ?>" hidden>
	<div class="pulse-wrap pulse-search__inner">
		<?php get_search_form(); ?>
		<button class="pulse-iconbtn" type="button" data-pulse-search-close><?php echo pulse_icon( 'close', 22 ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Close search', 'allbiohub-pulse' ); ?></span></button>
	</div>
</div>

<div class="pulse-drawer" id="pulse-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Menu', 'allbiohub-pulse' ); ?>" hidden>
	<div class="pulse-drawer__panel">
		<div class="pulse-drawer__head">
			<?php pulse_part( 'logo' ); ?>
			<button class="pulse-iconbtn" type="button" data-pulse-drawer-close><?php echo pulse_icon( 'close', 22 ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Close menu', 'allbiohub-pulse' ); ?></span></button>
		</div>
		<div class="pulse-drawer__search"><?php get_search_form(); ?></div>
		<nav aria-label="<?php esc_attr_e( 'Mobile', 'allbiohub-pulse' ); ?>">
			<?php
			$mobile_loc = has_nav_menu( 'mobile' ) ? 'mobile' : ( has_nav_menu( 'primary' ) ? 'primary' : '' );
			if ( $mobile_loc ) {
				wp_nav_menu( array( 'theme_location' => $mobile_loc, 'container' => false, 'menu_class' => 'pulse-drawer__list', 'depth' => 2, 'fallback_cb' => false ) );
			} else {
				pulse_part( 'nav-fallback', array( 'class' => 'pulse-drawer__list' ) );
			}
			if ( has_nav_menu( 'top-bar' ) ) {
				wp_nav_menu( array( 'theme_location' => 'top-bar', 'container' => false, 'menu_class' => 'pulse-drawer__minor', 'depth' => 1, 'fallback_cb' => false ) );
			}
			?>
		</nav>
		<?php pulse_part( 'social' ); ?>
	</div>
	<div class="pulse-drawer__scrim" data-pulse-drawer-close></div>
</div>

<main id="content" class="pulse-main" tabindex="-1">
