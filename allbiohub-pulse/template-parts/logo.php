<?php
/**
 * Logo: the site's existing custom logo (plus optional dark variant), else wordmark.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$logo_id = (int) get_theme_mod( 'custom_logo' );
$dark_id = (int) pulse_opt( 'logo_dark' );
$tag     = is_front_page() && empty( $args['no_h1'] ) && ! did_action( 'pulse_logo_h1' ) ? 'h1' : 'div';
if ( 'h1' === $tag ) {
	do_action( 'pulse_logo_h1' );
}
?>
<<?php echo esc_html( $tag ); ?> class="pulse-logo">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
		<?php if ( $logo_id ) : ?>
			<?php echo wp_get_attachment_image( $logo_id, 'medium', false, array( 'class' => 'pulse-logo__img' . ( $dark_id ? ' pulse-logo__img--light' : '' ), 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager', 'fetchpriority' => 'low' ) ); ?>
			<?php if ( $dark_id ) : ?>
				<?php echo wp_get_attachment_image( $dark_id, 'medium', false, array( 'class' => 'pulse-logo__img pulse-logo__img--dark', 'alt' => get_bloginfo( 'name' ), 'loading' => 'eager' ) ); ?>
			<?php endif; ?>
			<span class="screen-reader-text"><?php bloginfo( 'name' ); ?></span>
		<?php else : ?>
			<span class="pulse-wordmark">All<span>Bio</span>Hub</span>
		<?php endif; ?>
	</a>
</<?php echo esc_html( $tag ); ?>>
