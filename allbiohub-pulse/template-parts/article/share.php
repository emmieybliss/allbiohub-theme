<?php
/**
 * Share links. Plain links to each network's own share URL: no third-party scripts.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$url   = rawurlencode( get_permalink() );
$title = rawurlencode( html_entity_decode( wp_strip_all_tags( get_the_title() ), ENT_QUOTES, 'UTF-8' ) );
$nets  = array(
	'facebook' => array( 'Facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $url ),
	'x'        => array( 'X', 'https://x.com/intent/post?url=' . $url . '&text=' . $title ),
	'whatsapp' => array( 'WhatsApp', 'https://wa.me/?text=' . $title . '%20' . $url ),
	'linkedin' => array( 'LinkedIn', 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url ),
);
?>
<ul class="pulse-share" aria-label="<?php esc_attr_e( 'Share this story', 'allbiohub-pulse' ); ?>">
	<?php foreach ( $nets as $key => $net ) : ?>
		<li><a class="pulse-share__btn" href="<?php echo esc_url( $net[1] ); ?>" target="_blank" rel="noopener nofollow"><?php echo pulse_icon( $key, 17 ); // phpcs:ignore ?><span class="screen-reader-text"><?php /* translators: %s: network */ printf( esc_html__( 'Share on %s', 'allbiohub-pulse' ), esc_html( $net[0] ) ); ?></span></a></li>
	<?php endforeach; ?>
	<li><button class="pulse-share__btn" type="button" data-pulse-copy="<?php echo esc_url( get_permalink() ); ?>"><?php echo pulse_icon( 'link', 17 ); // phpcs:ignore ?><span class="screen-reader-text"><?php esc_html_e( 'Copy link', 'allbiohub-pulse' ); ?></span></button></li>
</ul>
