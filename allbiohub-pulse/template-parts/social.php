<?php
/**
 * Social profile links (Theme Settings → Social Profiles, else the site's Social menu).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$links = pulse_social_links();
$pulse_menu = $links ? 0 : pulse_social_menu();
if ( $pulse_menu ) {
	foreach ( (array) wp_get_nav_menu_items( $pulse_menu ) as $item ) {
		$host = (string) wp_parse_url( $item->url, PHP_URL_HOST );
		foreach ( array( 'facebook', 'instagram', 'youtube', 'tiktok', 'linkedin', 'pinterest', 'medium', 'flipboard', 'twitter' => 'x', 'x.com' => 'x', 't.me' => 'telegram', 'telegram' ) as $needle => $key ) {
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
