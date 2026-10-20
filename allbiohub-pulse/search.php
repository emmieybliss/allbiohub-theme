<?php
/**
 * Search results with section filters (All, Biography, Celebrity, …).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

global $wp_query;
$pulse_in    = pulse_search_filter();
$pulse_terms = get_search_query();
?>
<div class="pulse-archive pulse-searchpage">
	<header class="pulse-archhead">
		<div class="pulse-wrap">
			<p class="pulse-archhead__label"><?php esc_html_e( 'Search', 'allbiohub-pulse' ); ?></p>
			<h1 class="pulse-archhead__title">
				<?php
				if ( $pulse_terms ) {
					/* translators: %s: search terms */
					printf( esc_html__( 'Results for “%s”', 'allbiohub-pulse' ), esc_html( $pulse_terms ) );
				} else {
					esc_html_e( 'Search AllBioHub', 'allbiohub-pulse' );
				}
				?>
			</h1>
			<div class="pulse-searchpage__form"><?php get_search_form(); ?></div>
			<nav class="pulse-chips" aria-label="<?php esc_attr_e( 'Filter results', 'allbiohub-pulse' ); ?>">
				<a class="pulse-chip<?php echo $pulse_in ? '' : ' is-active'; ?>" href="<?php echo esc_url( remove_query_arg( array( 'in', 'paged' ) ) ); ?>"<?php echo $pulse_in ? '' : ' aria-current="true"'; ?>><?php esc_html_e( 'All', 'allbiohub-pulse' ); ?></a>
				<?php foreach ( pulse_search_filters() as $pulse_key => $pulse_label ) : ?>
					<a class="pulse-chip<?php echo $pulse_key === $pulse_in ? ' is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'in', $pulse_key, remove_query_arg( 'paged' ) ) ); ?>"<?php echo $pulse_key === $pulse_in ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $pulse_label ); ?></a>
				<?php endforeach; ?>
			</nav>
			<?php if ( $pulse_terms ) : ?>
				<p class="pulse-meta" role="status">
					<?php /* translators: %s: number of results */ echo esc_html( sprintf( _n( '%s result', '%s results', (int) $wp_query->found_posts, 'allbiohub-pulse' ), number_format_i18n( (int) $wp_query->found_posts ) ) ); ?>
				</p>
			<?php endif; ?>
		</div>
	</header>
	<div class="pulse-wrap pulse-archive__layout">
		<div class="pulse-archive__main">
			<?php pulse_part( 'archive-loop', array( 'lead' => false, 'empty' => __( 'No stories matched your search. Try a different name or fewer words.', 'allbiohub-pulse' ) ) ); ?>
		</div>
		<?php pulse_part( 'sidebar', array( 'area' => 'pulse-archive' ) ); ?>
	</div>
</div>
<?php
get_footer();
