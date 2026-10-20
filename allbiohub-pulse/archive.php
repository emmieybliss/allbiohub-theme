<?php
/**
 * Category, tag, date and other archives. Category URLs are unchanged:
 * this only changes how /category/… pages look.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pulse_term  = get_queried_object();
$pulse_is_tx = $pulse_term instanceof WP_Term;
$pulse_desc  = $pulse_is_tx ? term_description( $pulse_term ) : get_the_archive_description();
$pulse_kids  = ( $pulse_is_tx && 'category' === $pulse_term->taxonomy ) ? get_terms( array( 'taxonomy' => 'category', 'parent' => $pulse_term->term_id, 'hide_empty' => true ) ) : array();
$pulse_label = is_tag() ? __( 'Tag', 'allbiohub-pulse' ) : ( is_category() ? __( 'Section', 'allbiohub-pulse' ) : __( 'Archive', 'allbiohub-pulse' ) );
$pulse_title = $pulse_is_tx ? html_entity_decode( $pulse_term->name, ENT_QUOTES, 'UTF-8' ) : wp_strip_all_tags( get_the_archive_title() );
if ( function_exists( 'mb_convert_case' ) && mb_strtoupper( $pulse_title ) === $pulse_title ) {
	$pulse_title = mb_convert_case( mb_strtolower( $pulse_title ), MB_CASE_TITLE, 'UTF-8' );
}
?>
<div class="pulse-archive">
	<header class="pulse-archhead">
		<div class="pulse-wrap">
			<?php pulse_breadcrumbs(); ?>
			<p class="pulse-archhead__label"><?php echo esc_html( $pulse_label ); ?></p>
			<h1 class="pulse-archhead__title"><?php echo esc_html( $pulse_title ); ?></h1>
			<?php if ( $pulse_desc ) : ?>
				<div class="pulse-archhead__desc"><?php echo wp_kses_post( $pulse_desc ); ?></div>
			<?php endif; ?>
			<?php if ( $pulse_kids && ! is_wp_error( $pulse_kids ) ) : ?>
				<ul class="pulse-chips" aria-label="<?php esc_attr_e( 'Sub-sections', 'allbiohub-pulse' ); ?>">
					<?php foreach ( $pulse_kids as $pulse_kid ) : ?>
						<li><a class="pulse-chip" href="<?php echo esc_url( get_term_link( $pulse_kid ) ); ?>"><?php echo esc_html( html_entity_decode( $pulse_kid->name ) ); ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<?php pulse_the_ad( 'category_sponsor', 'line' ); ?>
		</div>
	</header>
	<div class="pulse-wrap pulse-archive__layout">
		<div class="pulse-archive__main">
			<?php pulse_part( 'archive-loop' ); ?>
		</div>
		<?php pulse_part( 'sidebar', array( 'area' => 'pulse-archive' ) ); ?>
	</div>
</div>
<?php
get_footer();
