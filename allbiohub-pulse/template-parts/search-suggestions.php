<?php
/**
 * Inside the search panel: quick category links and trending stories, so
 * readers can tap straight to something without typing.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$pulse_links = array();
foreach ( array( 'celebrity', 'biography', 'business', 'technology', 'women', 'news', 'lifestyle', 'reviews' ) as $key ) {
	$link = pulse_section_link( $key );
	if ( $link ) {
		$pulse_links[ $link ] = pulse_section_label( $key );
	}
}
$pulse_trending = pulse_ticker_ids();
$pulse_trending = array_slice( $pulse_trending, 0, 5 );
?>
<div class="pulse-search__extra">
	<?php if ( $pulse_links ) : ?>
		<p class="pulse-search__label"><?php esc_html_e( 'Browse', 'allbiohub-pulse' ); ?></p>
		<ul class="pulse-chips">
			<?php foreach ( $pulse_links as $href => $label ) : ?>
				<li><a class="pulse-chip" href="<?php echo esc_url( $href ); ?>"><?php echo esc_html( $label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
	<?php if ( $pulse_trending ) : ?>
		<p class="pulse-search__label"><?php esc_html_e( 'Trending now', 'allbiohub-pulse' ); ?></p>
		<ol class="pulse-search__trending">
			<?php foreach ( $pulse_trending as $pid ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $pid ) ); ?>"><?php echo esc_html( get_the_title( $pid ) ); ?></a></li>
			<?php endforeach; ?>
		</ol>
	<?php endif; ?>
</div>
