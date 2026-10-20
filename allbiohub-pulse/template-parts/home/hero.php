<?php
/**
 * Hero: lead story (left) + Trending Stories 01–05 (right).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$hero = pulse_hero_id();
if ( ! $hero ) {
	return;
}
pulse_seen( array( $hero ) );
$trending = pulse_trending_ids( 5, pulse_seen() );
pulse_seen( $trending );
$subtitle = pulse_subtitle( $hero );
?>
<section class="pulse-hero pulse-wrap" aria-labelledby="pulse-hero-title">
	<article class="pulse-hero__lead<?php echo pulse_is_sponsored( $hero ) ? ' is-sponsored' : ''; ?>">
		<a class="pulse-hero__media" href="<?php echo esc_url( get_permalink( $hero ) ); ?>" tabindex="-1" aria-hidden="true">
			<?php echo pulse_image( $hero, array( 'size' => 'pulse-hero', 'loading' => 'eager', 'fetchpriority' => 'high', 'sizes' => '(min-width: 1024px) 760px, 100vw' ) ); // phpcs:ignore ?>
		</a>
		<div class="pulse-hero__body">
			<div class="pulse-story__kicker">
				<?php echo pulse_is_sponsored( $hero ) ? pulse_sponsored_badge( $hero, true ) : pulse_category_label( $hero ); // phpcs:ignore ?>
				<?php if ( pulse_is_pinned( $hero ) ) : ?>
					<span class="pulse-pinbadge pulse-pinbadge--inline"><?php echo pulse_icon( 'pin', 13 ); // phpcs:ignore ?><?php esc_html_e( 'Pinned', 'allbiohub-pulse' ); ?></span>
				<?php endif; ?>
			</div>
			<h2 class="pulse-hero__title" id="pulse-hero-title"><a href="<?php echo esc_url( get_permalink( $hero ) ); ?>"><?php echo esc_html( get_the_title( $hero ) ); ?></a></h2>
			<p class="pulse-hero__dek"><?php echo esc_html( $subtitle ? wp_trim_words( $subtitle, 40, '…' ) : pulse_excerpt( $hero, 34 ) ); ?></p>
			<p class="pulse-meta">
				<?php echo pulse_byline( $hero ); // phpcs:ignore ?>
				<span class="pulse-meta__sep" aria-hidden="true">·</span>
				<?php echo pulse_time( $hero ); // phpcs:ignore ?>
				<span class="pulse-meta__sep" aria-hidden="true">·</span>
				<span><?php echo esc_html( pulse_reading_time( $hero ) ); ?></span>
			</p>
			<?php pulse_the_ad( 'hero_sponsor', 'line' ); ?>
		</div>
	</article>

	<?php if ( $trending ) : ?>
		<aside class="pulse-hero__trending" aria-labelledby="pulse-trending-title">
			<h2 class="pulse-label-title" id="pulse-trending-title"><?php esc_html_e( 'Trending stories', 'allbiohub-pulse' ); ?></h2>
			<ol class="pulse-numbered">
				<?php foreach ( $trending as $i => $id ) : ?>
					<li><?php pulse_part( 'story-card', array( 'post' => $id, 'variant' => 'row', 'size' => 'pulse-thumb', 'number' => $i + 1 ) ); ?></li>
				<?php endforeach; ?>
			</ol>
		</aside>
	<?php endif; ?>
</section>
