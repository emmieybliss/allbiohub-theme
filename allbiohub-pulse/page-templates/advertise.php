<?php
/**
 * Template Name: Advertise with AllBioHub
 * Template Post Type: page
 *
 * The page's own content comes first, unchanged. Below it, the advertising
 * formats this theme supports. No audience numbers are shown: the site owner
 * adds those to the page content when they have verified figures.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pulse_formats = array(
	array( 'pin', __( 'Sponsored stories', 'allbiohub-pulse' ), __( 'Your story written for AllBioHub readers, clearly labelled as sponsored and featured on the homepage.', 'allbiohub-pulse' ) ),
	array( 'arrow', __( 'Homepage placements', 'allbiohub-pulse' ), __( 'The Sponsored Content panel, the hero “presented by” line and a full-width homepage banner.', 'allbiohub-pulse' ) ),
	array( 'link', __( 'Article placements', 'allbiohub-pulse' ), __( 'Top, inline and end-of-article placements, plus the sidebar on desktop.', 'allbiohub-pulse' ) ),
	array( 'menu', __( 'Category sponsorship', 'allbiohub-pulse' ), __( 'Present a whole section such as Business, Technology or Lifestyle.', 'allbiohub-pulse' ) ),
	array( 'mail', __( 'Newsletter sponsorship', 'allbiohub-pulse' ), __( 'A “presented by” line in the AllBioHub newsletter signup.', 'allbiohub-pulse' ) ),
	array( 'play', __( 'Video features', 'allbiohub-pulse' ), __( 'Interviews, product reviews and event coverage on AllBioHub Video.', 'allbiohub-pulse' ) ),
);
$pulse_contact = get_page_by_path( 'contact-us' );
$pulse_contact = $pulse_contact && 'publish' === $pulse_contact->post_status ? get_permalink( $pulse_contact ) : 'mailto:' . antispambot( get_option( 'admin_email' ) );

while ( have_posts() ) :
	the_post();
	?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'pulse-page pulse-advertise' ); ?>>
		<header class="pulse-archhead pulse-advertise__head">
			<div class="pulse-wrap pulse-wrap--narrow">
				<p class="pulse-archhead__label"><?php esc_html_e( 'Partner with us', 'allbiohub-pulse' ); ?></p>
				<h1 class="pulse-archhead__title"><?php the_title(); ?></h1>
			</div>
		</header>
		<?php if ( '' !== trim( get_the_content() ) ) : ?>
			<div class="pulse-wrap pulse-wrap--narrow">
				<div class="pulse-prose entry-content"><?php the_content(); ?></div>
			</div>
		<?php endif; ?>
		<section class="pulse-section pulse-wrap" aria-labelledby="pulse-formats-title">
			<header class="pulse-sechead"><h2 class="pulse-sechead__title" id="pulse-formats-title"><?php esc_html_e( 'Ways to work with AllBioHub', 'allbiohub-pulse' ); ?></h2></header>
			<ul class="pulse-formats">
				<?php foreach ( $pulse_formats as $pulse_f ) : ?>
					<li class="pulse-format">
						<span class="pulse-format__icon"><?php echo pulse_icon( $pulse_f[0], 22 ); // phpcs:ignore ?></span>
						<h3 class="pulse-format__title"><?php echo esc_html( $pulse_f[1] ); ?></h3>
						<p class="pulse-format__text"><?php echo esc_html( $pulse_f[2] ); ?></p>
					</li>
				<?php endforeach; ?>
			</ul>
			<div class="pulse-advertise__cta">
				<p><?php esc_html_e( 'Tell us about your brand and campaign, and we will send you the options that fit.', 'allbiohub-pulse' ); ?></p>
				<a class="pulse-btn" href="<?php echo esc_url( $pulse_contact ); ?>"><?php esc_html_e( 'Get in touch', 'allbiohub-pulse' ); ?> <?php echo pulse_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
			</div>
		</section>
	</article>
	<?php
endwhile;

get_footer();
