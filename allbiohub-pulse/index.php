<?php
/**
 * Fallback template (also the posts page when one is set).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>
<div class="pulse-archive">
	<header class="pulse-archhead">
		<div class="pulse-wrap">
			<h1 class="pulse-archhead__title"><?php echo esc_html( is_home() && get_option( 'page_for_posts' ) ? get_the_title( get_option( 'page_for_posts' ) ) : __( 'Latest stories', 'allbiohub-pulse' ) ); ?></h1>
		</div>
	</header>
	<div class="pulse-wrap pulse-archive__layout">
		<div class="pulse-archive__main"><?php pulse_part( 'archive-loop' ); ?></div>
		<?php pulse_part( 'sidebar', array( 'area' => 'pulse-archive' ) ); ?>
	</div>
</div>
<?php
get_footer();
