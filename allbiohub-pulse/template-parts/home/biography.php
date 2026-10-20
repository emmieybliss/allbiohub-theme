<?php
/**
 * Featured Biography + People You Should Know (both from real biography posts).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$bio = pulse_featured_bio_id( pulse_seen() );
if ( $bio ) {
	pulse_seen( array( $bio ) );
}
$people = pulse_people_ids( pulse_seen() );
if ( ! $bio && ! $people ) {
	return;
}
$bio_term = pulse_bio_term();
?>
<section class="pulse-section pulse-biozone" aria-label="<?php esc_attr_e( 'Biography', 'allbiohub-pulse' ); ?>">
	<div class="pulse-wrap">
		<?php if ( $bio ) : ?>
			<?php pulse_section_head( __( 'Featured biography', 'allbiohub-pulse' ), $bio_term ? get_term_link( $bio_term ) : '', __( 'All biographies', 'allbiohub-pulse' ) ); ?>
			<article class="pulse-featbio">
				<a class="pulse-featbio__media" href="<?php echo esc_url( get_permalink( $bio ) ); ?>" tabindex="-1" aria-hidden="true">
					<?php echo pulse_image( $bio, array( 'size' => 'pulse-portrait', 'sizes' => '(min-width: 1024px) 480px, 100vw' ) ); // phpcs:ignore ?>
				</a>
				<div class="pulse-featbio__body">
					<p class="pulse-featbio__role"><?php echo esc_html( pulse_person_descriptor( $bio ) ); ?></p>
					<h3 class="pulse-featbio__name"><a href="<?php echo esc_url( get_permalink( $bio ) ); ?>"><?php echo esc_html( pulse_person_name( $bio ) ); ?></a></h3>
					<p class="pulse-featbio__title"><?php echo esc_html( get_the_title( $bio ) ); ?></p>
					<p class="pulse-featbio__excerpt"><?php echo esc_html( pulse_excerpt( $bio, 48 ) ); ?></p>
					<a class="pulse-btn" href="<?php echo esc_url( get_permalink( $bio ) ); ?>"><?php esc_html_e( 'Read full story', 'allbiohub-pulse' ); ?> <?php echo pulse_icon( 'arrow', 16 ); // phpcs:ignore ?></a>
					<?php $links = pulse_bio_section_links( $bio ); ?>
					<?php if ( $links ) : ?>
						<ul class="pulse-featbio__chapters" aria-label="<?php esc_attr_e( 'Jump to', 'allbiohub-pulse' ); ?>">
							<?php foreach ( $links as $i => $link ) : ?>
								<li><a href="<?php echo esc_url( $link['url'] ); ?>"><span><?php echo esc_html( sprintf( '%02d', $i + 1 ) ); ?></span><?php echo esc_html( $link['label'] ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php endif; ?>
				</div>
			</article>
		<?php endif; ?>

		<?php if ( $people ) : ?>
			<?php pulse_seen( $people ); ?>
			<div class="pulse-people">
				<?php pulse_section_head( pulse_opt( 'people_title' ) ); ?>
				<ul class="pulse-people__rail">
					<?php foreach ( $people as $id ) : ?>
						<li class="pulse-person">
							<a href="<?php echo esc_url( get_permalink( $id ) ); ?>">
								<span class="pulse-person__photo"><?php echo pulse_image( $id, array( 'size' => 'pulse-square' ) ); // phpcs:ignore ?></span>
								<span class="pulse-person__name"><?php echo esc_html( pulse_person_name( $id ) ); ?></span>
								<span class="pulse-person__role"><?php echo esc_html( pulse_person_descriptor( $id ) ); ?></span>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
