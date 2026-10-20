<?php
/**
 * Article / archive sidebar: sidebar ad, Most read, then the widget area.
 *
 * Args: area (pulse-article|pulse-archive), exclude (IDs).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$area    = ! empty( $args['area'] ) ? $args['area'] : 'pulse-article';
$exclude = ! empty( $args['exclude'] ) ? (array) $args['exclude'] : array();
$popular = pulse_trending_ids( 5, $exclude );
?>
<aside class="pulse-sidebar" aria-label="<?php esc_attr_e( 'More from AllBioHub', 'allbiohub-pulse' ); ?>">
	<div class="pulse-sidebar__sticky">
		<?php pulse_the_ad( 'sidebar', 'panel' ); ?>
		<?php if ( $popular ) : ?>
			<section class="pulse-sidebox">
				<h2 class="pulse-label-title"><?php esc_html_e( 'Most read', 'allbiohub-pulse' ); ?></h2>
				<ol class="pulse-numbered pulse-numbered--compact">
					<?php foreach ( $popular as $i => $id ) : ?>
						<li><?php pulse_part( 'story-card', array( 'post' => $id, 'variant' => 'compact', 'number' => $i + 1, 'heading' => 'h3' ) ); ?></li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
		<?php if ( is_active_sidebar( $area ) ) : ?>
			<?php dynamic_sidebar( $area ); ?>
		<?php endif; ?>
	</div>
</aside>
