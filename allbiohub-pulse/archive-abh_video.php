<?php
/**
 * /video/ archive: the current feature on top, then every video.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pulse_primary = is_paged() ? null : pulse_primary_video();
?>
<div class="pulse-archive pulse-videoarch">
	<header class="pulse-archhead pulse-archhead--video">
		<div class="pulse-wrap">
			<p class="pulse-archhead__label"><?php echo pulse_icon( 'play', 14 ); // phpcs:ignore ?> <?php esc_html_e( 'Watch', 'allbiohub-pulse' ); ?></p>
			<h1 class="pulse-archhead__title"><?php echo esc_html( pulse_opt( 'video_title' ) ); ?></h1>
			<?php if ( $pulse_primary ) : ?>
				<?php $pulse_vid = (int) $pulse_primary['id']; ?>
				<div class="pulse-videofeat">
					<div class="pulse-videofeat__player"><?php echo pulse_video_player( $pulse_vid, array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?></div>
					<div class="pulse-videofeat__body">
						<span class="pulse-videofeat__badge"><?php echo esc_html( $pulse_primary['badge'] ); ?></span>
						<h2 class="pulse-videofeat__title"><a href="<?php echo esc_url( get_permalink( $pulse_vid ) ); ?>"><?php echo esc_html( get_the_title( $pulse_vid ) ); ?></a></h2>
						<p class="pulse-videofeat__dek"><?php echo esc_html( pulse_excerpt( $pulse_vid, 34 ) ); ?></p>
						<p class="pulse-meta"><?php echo pulse_time( $pulse_vid ); // phpcs:ignore ?></p>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</header>
	<div class="pulse-wrap">
		<?php
		if ( have_posts() ) {
			echo '<div class="pulse-grid pulse-grid--3 pulse-section">';
			while ( have_posts() ) {
				the_post();
				pulse_part( 'story-card', array( 'post' => get_the_ID(), 'heading' => 'h2', 'video' => true ) );
			}
			echo '</div>';
			the_posts_pagination( array( 'class' => 'pulse-paging', 'mid_size' => 1 ) );
		} else {
			echo '<div class="pulse-empty"><p>' . esc_html__( 'No videos have been published yet.', 'allbiohub-pulse' ) . '</p></div>';
		}
		?>
	</div>
</div>
<?php
get_footer();
