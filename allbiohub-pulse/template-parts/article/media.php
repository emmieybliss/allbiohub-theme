<?php
/**
 * Lead image with caption and credit from the Media Library.
 * Skipped when the content already opens with the same image.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$img_id = (int) get_post_thumbnail_id();
if ( ! $img_id ) {
	return;
}
$content = (string) get_post_field( 'post_content', get_the_ID() );
if ( preg_match( '/wp-image-' . $img_id . '\b/', substr( $content, 0, 900 ) ) ) {
	return;
}
$caption = wp_get_attachment_caption( $img_id );
?>
<figure class="pulse-art__media">
	<?php echo wp_get_attachment_image( $img_id, 'pulse-hero', false, array( 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1100px) 860px, 100vw' ) ); ?>
	<?php if ( $caption ) : ?>
		<figcaption><?php echo esc_html( $caption ); ?></figcaption>
	<?php endif; ?>
</figure>
