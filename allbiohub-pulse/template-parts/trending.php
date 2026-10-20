<?php
/**
 * TRENDING NOW ticker. Content comes from pulse_ticker_ids() (admin-controlled source).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$ids = pulse_ticker_ids();
if ( ! $ids ) {
	return;
}
?>
<div class="pulse-ticker" aria-label="<?php esc_attr_e( 'Trending now', 'allbiohub-pulse' ); ?>">
	<div class="pulse-wrap pulse-ticker__inner">
		<span class="pulse-ticker__label"><span class="pulse-ticker__dot" aria-hidden="true"></span><?php esc_html_e( 'Trending now', 'allbiohub-pulse' ); ?></span>
		<div class="pulse-ticker__viewport" data-pulse-ticker>
			<ul class="pulse-ticker__track">
				<?php foreach ( $ids as $id ) : ?>
					<li><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<button class="pulse-ticker__pause" type="button" data-pulse-ticker-toggle aria-pressed="false">
			<span class="screen-reader-text"><?php esc_html_e( 'Pause trending ticker', 'allbiohub-pulse' ); ?></span>
			<svg width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><rect x="2" y="1" width="3" height="10" fill="currentColor"/><rect x="7" y="1" width="3" height="10" fill="currentColor"/></svg>
		</button>
	</div>
</div>
