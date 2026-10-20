<?php
/**
 * Social profile links (Theme Settings → Social Profiles, else the Social menu).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$links = pulse_social_links();
if ( ! $links && has_nav_menu( 'social' ) ) {
	$locations = get_nav_menu_locations();
	foreach ( (array) wp_get_nav_menu_items( $locations['social'] ) as $item ) {
		$host = (string) wp_parse_url( $item->url, PHP_URL_HOST );
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin', 'twitter' => 'x', 'x.com' => 'x' ) as $needle => $key ) {
			$needle = is_int( $needle ) ? $key : $needle;
			if ( false !== stripos( $host, $needle ) && ! isset( $links[ $key ] ) ) {
				$links[ $key ] = array( 'url' => $item->url, 'label' => $item->title );
			}
		}
	}
}
if ( ! $links ) {
	return;
}
?>
<ul class="pulse-social">
	<?php foreach ( $links as $key => $link ) : ?>
		<li><a href="<?php echo esc_url( $link['url'] ); ?>" rel="me noopener" target="_blank"><?php echo pulse_icon( $key, 18 ); // phpcs:ignore ?><span class="screen-reader-text"><?php echo esc_html( $link['label'] ); ?></span></a></li>
	<?php endforeach; ?>
</ul>
