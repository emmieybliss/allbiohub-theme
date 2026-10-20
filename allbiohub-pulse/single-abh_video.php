<?php
/**
 * Video detail page: player first, then the story around it.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	$pulse_data = pulse_video_data();
	$pulse_who  = array();
	if ( $pulse_data && $pulse_data['presenter'] ) {
		$pulse_who[ __( 'Presented by', 'allbiohub-pulse' ) ] = $pulse_data['presenter'];
	}
	if ( $pulse_data && $pulse_data['guest'] ) {
		$pulse_who[ __( 'Featuring', 'allbiohub-pulse' ) ] = $pulse_data['guest'];
	}
	$pulse_more = pulse_more_video_ids( array( get_the_ID() ) );
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pulse-art pulse-art--video' ); ?> data-pulse-view="<?php the_ID(); ?>">
		<div class="pulse-videopage">
			<div class="pulse-wrap pulse-videopage__inner">
				<?php pulse_breadcrumbs(); ?>
				<div class="pulse-videopage__player">
					<?php echo pulse_video_player( get_the_ID(), array( 'loading' => 'eager', 'fetchpriority' => 'high' ) ); // phpcs:ignore ?>
				</div>
				<div class="pulse-videopage__head">
					<div class="pulse-story__kicker">
						<?php if ( $pulse_data && $pulse_data['badge'] ) : ?><span class="pulse-videofeat__badge"><?php echo esc_html( $pulse_data['badge'] ); ?></span><?php endif; ?>
						<?php echo pulse_category_label(); // phpcs:ignore ?>
					</div>
					<h1 class="pulse-art__title"><?php the_title(); ?></h1>
					<p class="pulse-meta">
						<?php echo pulse_time(); // phpcs:ignore ?>
						<?php if ( $pulse_data ) : ?>
							<span class="pulse-meta__sep" aria-hidden="true">·</span><span><?php echo esc_html( $pulse_data['source'] ); ?></span>
							<?php if ( $pulse_data['duration'] ) : ?><span class="pulse-meta__sep" aria-hidden="true">·</span><span><?php echo esc_html( pulse_duration_label( $pulse_data['duration'] ) ); ?></span><?php endif; ?>
						<?php endif; ?>
					</p>
					<?php if ( $pulse_who ) : ?>
						<dl class="pulse-videopage__who">
							<?php foreach ( $pulse_who as $label => $name ) : ?>
								<div><dt><?php echo esc_html( $label ); ?></dt><dd><?php echo esc_html( $name ); ?></dd></div>
							<?php endforeach; ?>
						</dl>
					<?php endif; ?>
					<?php if ( pulse_opt( 'article_share' ) ) : ?><?php pulse_part( 'article/share' ); ?><?php endif; ?>
				</div>
			</div>
		</div>

		<div class="pulse-wrap pulse-art__layout">
			<div class="pulse-art__main">
				<div class="pulse-prose entry-content"><?php the_content(); ?></div>
				<?php pulse_the_ad( 'article_bottom', 'inline' ); ?>
				<?php pulse_part( 'newsletter' ); ?>
			</div>
			<?php pulse_part( 'sidebar', array( 'area' => 'pulse-article', 'exclude' => array( get_the_ID() ) ) ); ?>
		</div>

		<?php if ( $pulse_more ) : ?>
			<section class="pulse-section pulse-wrap" aria-labelledby="pulse-relvid-title">
				<header class="pulse-sechead"><h2 class="pulse-sechead__title" id="pulse-relvid-title"><?php esc_html_e( 'More from AllBioHub Video', 'allbiohub-pulse' ); ?></h2></header>
				<div class="pulse-rail pulse-grid pulse-grid--3">
					<?php foreach ( $pulse_more as $pulse_id ) : ?>
						<?php pulse_part( 'story-card', array( 'post' => $pulse_id, 'video' => true ) ); ?>
					<?php endforeach; ?>
				</div>
			</section>
		<?php endif; ?>

		<div class="pulse-wrap">
			<?php pulse_part( 'article/related', array( 'title' => __( 'Related stories', 'allbiohub-pulse' ) ) ); ?>
		</div>
	</article>
	<?php
endwhile;

get_footer();
