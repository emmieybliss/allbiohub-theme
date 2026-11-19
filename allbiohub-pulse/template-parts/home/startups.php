<?php
/**
 * Featured Startups from the AllBioHub Startup Database.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$pool = pulse_home_startups();
if ( count( $pool ) < 2 ) {
	return;
}
$show   = pulse_startups_count();
$rotate = 'visit' === pulse_opt( 'startups_rotate' ) && count( $pool ) > $show;
$all    = pulse_startups_page_url( 'startups' );
$submit = pulse_startups_page_url( 'list-your-startup' );
?>
<section class="pulse-section pulse-startups" aria-label="<?php echo esc_attr( pulse_opt( 'startups_title' ) ); ?>">
	<div class="pulse-wrap">
		<?php pulse_section_head( pulse_opt( 'startups_title' ), $all, __( 'See all startups', 'allbiohub-pulse' ) ); ?>
		<?php if ( pulse_opt( 'startups_text' ) ) : ?>
			<p class="pulse-startups__intro"><?php echo esc_html( pulse_opt( 'startups_text' ) ); ?></p>
		<?php endif; ?>
		<ul class="pulse-startups__grid" <?php echo $rotate ? 'data-pulse-rotate="' . esc_attr( $show ) . '"' : ''; ?>>
			<?php foreach ( $pool as $i => $item ) : ?>
				<?php
				$sid     = $item['id'];
				$country = pulse_startup_term( $sid, 'abh_country' );
				$sector  = pulse_startup_term( $sid, 'abh_industry' );
				$meta    = implode( ' · ', array_filter( array( $sector, $country ) ) );
				$logo    = get_post_thumbnail_id( $sid );
				$name    = get_the_title( $sid );
				?>
				<li class="pulse-startup" <?php echo $item['paid'] ? 'data-fixed' : ''; ?> <?php echo $i >= $show ? 'hidden' : ''; ?>>
					<a href="<?php echo esc_url( get_permalink( $sid ) ); ?>">
						<span class="pulse-startup__logo">
							<?php if ( $logo ) : ?>
								<?php echo wp_get_attachment_image( $logo, 'thumbnail', false, array( 'loading' => 'lazy', 'decoding' => 'async', 'alt' => '' ) ); ?>
							<?php else : ?>
								<span class="pulse-startup__initial" aria-hidden="true"><?php echo esc_html( mb_strtoupper( mb_substr( wp_strip_all_tags( $name ), 0, 1 ) ) ); ?></span>
							<?php endif; ?>
						</span>
						<span class="pulse-startup__body">
							<span class="pulse-startup__name"><?php echo esc_html( $name ); ?></span>
							<?php if ( $meta ) : ?>
								<span class="pulse-startup__meta"><?php echo esc_html( $meta ); ?></span>
							<?php endif; ?>
							<span class="pulse-startup__blurb"><?php echo esc_html( pulse_startup_blurb( $sid ) ); ?></span>
						</span>
						<?php if ( $item['paid'] ) : ?>
							<span class="pulse-startup__tag"><?php esc_html_e( 'Featured', 'allbiohub-pulse' ); ?></span>
						<?php endif; ?>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php if ( $submit ) : ?>
			<p class="pulse-startups__cta"><?php esc_html_e( 'Building something in Africa?', 'allbiohub-pulse' ); ?> <a href="<?php echo esc_url( $submit ); ?>"><?php esc_html_e( 'List your startup', 'allbiohub-pulse' ); ?> <?php echo pulse_icon( 'arrow', 16 ); // phpcs:ignore WordPress.Security.EscapeOutput -- static SVG. ?></a></p>
		<?php endif; ?>
	</div>
</section>
