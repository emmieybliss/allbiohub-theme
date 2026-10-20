<?php
/**
 * ALLBIOHUB VIDEO: lead video (facade player) + details, then More videos.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$primary = pulse_primary_video();
if ( ! $primary ) {
	return;
}
$id    = (int) $primary['id'];
$data  = pulse_video_data( $id );
$more  = pulse_more_video_ids( array( $id ) );
$style = pulse_opt( 'video_style' );
$cat   = pulse_primary_category( $id );
$dek   = pulse_subtitle( $id ) ? pulse_subtitle( $id ) : pulse_excerpt( $id, 34 );
$who   = array_filter( array( $data['presenter'] ? sprintf( /* translators: %s: name */ __( 'Presented by %s', 'allbiohub-pulse' ), $data['presenter'] ) : '', $data['guest'] ? sprintf( /* translators: %s: name */ __( 'With %s', 'allbiohub-pulse' ), $data['guest'] ) : '' ) );
$archive = get_post_type_archive_link( 'abh_video' );
$has_videos = (bool) wp_count_posts( 'abh_video' )->publish;
?>
<section class="pulse-section pulse-videozone" aria-labelledby="pulse-video-title">
	<div class="pulse-wrap">
		<header class="pulse-videozone__head">
			<h2 class="pulse-videozone__title" id="pulse-video-title"><?php echo pulse_icon( 'play', 18 ); // phpcs:ignore ?> <?php echo esc_html( pulse_opt( 'video_title' ) ); ?></h2>
		</header>
		<div class="pulse-videofeat pulse-videofeat--<?php echo esc_attr( $style ); ?>">
			<div class="pulse-videofeat__player">
				<?php echo pulse_video_player( $id ); // phpcs:ignore ?>
			</div>
			<div class="pulse-videofeat__body">
				<span class="pulse-videofeat__badge"><?php echo esc_html( $primary['badge'] ); ?></span>
				<h3 class="pulse-videofeat__title"><a href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo esc_html( get_the_title( $id ) ); ?></a></h3>
				<?php if ( $dek ) : ?><p class="pulse-videofeat__dek"><?php echo esc_html( wp_trim_words( $dek, 40, '…' ) ); ?></p><?php endif; ?>
				<?php if ( $who ) : ?><p class="pulse-videofeat__who"><?php echo esc_html( implode( ' · ', $who ) ); ?></p><?php endif; ?>
				<p class="pulse-meta">
					<?php if ( $cat ) : ?><?php echo pulse_category_label( $id ); // phpcs:ignore ?><span class="pulse-meta__sep" aria-hidden="true">·</span><?php endif; ?>
					<?php echo pulse_time( $id ); // phpcs:ignore ?>
				</p>
				<div class="pulse-videofeat__actions">
					<a class="pulse-btn" href="<?php echo esc_url( get_permalink( $id ) ); ?>"><?php echo pulse_icon( 'play', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'Watch video', 'allbiohub-pulse' ); ?></a>
					<span class="pulse-videofeat__src"><?php echo esc_html( $data['source'] ); ?><?php echo $data['duration'] ? ' · ' . esc_html( pulse_duration_label( $data['duration'] ) ) : ''; ?></span>
				</div>
			</div>
		</div>

		<?php if ( $more ) : ?>
			<div class="pulse-videomore">
				<header class="pulse-sechead pulse-sechead--onDark">
					<h3 class="pulse-sechead__title"><?php esc_html_e( 'More from AllBioHub Video', 'allbiohub-pulse' ); ?></h3>
					<?php if ( $has_videos && $archive ) : ?>
						<a class="pulse-sechead__more" href="<?php echo esc_url( $archive ); ?>"><?php esc_html_e( 'More videos', 'allbiohub-pulse' ); ?> <?php echo pulse_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
					<?php endif; ?>
				</header>
				<ul class="pulse-rail pulse-videomore__rail">
					<?php foreach ( $more as $vid ) : $vd = pulse_video_data( $vid ); ?>
						<li class="pulse-vcard">
							<a href="<?php echo esc_url( get_permalink( $vid ) ); ?>">
								<span class="pulse-vcard__media">
									<?php echo pulse_image( $vid, array( 'size' => 'pulse-card' ) ); // phpcs:ignore ?>
									<span class="pulse-story__play"><?php echo pulse_icon( 'play', 18 ); // phpcs:ignore ?></span>
									<?php if ( $vd && $vd['duration'] ) : ?><span class="pulse-video__dur"><?php echo esc_html( pulse_duration_label( $vd['duration'] ) ); ?></span><?php endif; ?>
								</span>
								<span class="pulse-vcard__kicker"><?php echo esc_html( $vd && $vd['badge'] && 'abh_video' === get_post_type( $vid ) ? $vd['badge'] : ( pulse_primary_category( $vid ) ? html_entity_decode( pulse_primary_category( $vid )->name ) : '' ) ); ?></span>
								<span class="pulse-vcard__title"><?php echo esc_html( get_the_title( $vid ) ); ?></span>
								<span class="pulse-vcard__meta"><?php echo esc_html( get_the_date( '', $vid ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
