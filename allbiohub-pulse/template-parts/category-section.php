<?php
/**
 * One editorial category section: a lead story and a column of shorter ones.
 * Alternates the lead side so a long homepage keeps its rhythm.
 *
 * Args: key (section key), ids (post IDs), index (int).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$key   = $args['key'];
$ids   = $args['ids'];
$lead  = array_shift( $ids );
$flip  = ( (int) $args['index'] ) % 2 ? ' is-flipped' : '';
$label = pulse_section_label( $key );
?>
<section class="pulse-section pulse-catsec pulse-catsec--<?php echo esc_attr( $key ); ?> pulse-wrap" aria-label="<?php echo esc_attr( $label ); ?>">
	<?php pulse_section_head( $label, pulse_section_link( $key ) ); ?>
	<?php if ( 1 === (int) $args['index'] ) { pulse_the_ad( 'category_sponsor', 'line' ); } ?>
	<div class="pulse-catsec__grid<?php echo esc_attr( $flip ); ?>">
		<div class="pulse-catsec__lead">
			<?php pulse_part( 'story-card', array( 'post' => $lead, 'variant' => 'feature', 'size' => 'pulse-hero', 'excerpt' => true ) ); ?>
		</div>
		<div class="pulse-catsec__list">
			<?php foreach ( $ids as $id ) : ?>
				<?php pulse_part( 'story-card', array( 'post' => $id, 'variant' => 'row', 'size' => 'pulse-thumb' ) ); ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
