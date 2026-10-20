<?php
/**
 * Author archive: profile card, then their stories.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

get_header();

$pulse_author = get_queried_object();
$pulse_bio    = $pulse_author ? get_the_author_meta( 'description', $pulse_author->ID ) : '';
$pulse_site   = $pulse_author ? get_the_author_meta( 'user_url', $pulse_author->ID ) : '';
?>
<div class="pulse-archive">
	<header class="pulse-archhead pulse-archhead--author">
		<div class="pulse-wrap pulse-authorhead">
			<?php echo get_avatar( $pulse_author->ID, 112, '', '', array( 'loading' => 'eager' ) ); ?>
			<div>
				<p class="pulse-archhead__label"><?php esc_html_e( 'Author', 'allbiohub-pulse' ); ?></p>
				<h1 class="pulse-archhead__title"><?php echo esc_html( $pulse_author->display_name ); ?></h1>
				<?php if ( $pulse_bio ) : ?><p class="pulse-archhead__desc"><?php echo esc_html( $pulse_bio ); ?></p><?php endif; ?>
				<p class="pulse-meta">
					<?php /* translators: %s: number of stories */ echo esc_html( sprintf( _n( '%s story', '%s stories', count_user_posts( $pulse_author->ID, 'post', true ), 'allbiohub-pulse' ), number_format_i18n( count_user_posts( $pulse_author->ID, 'post', true ) ) ) ); ?>
					<?php if ( $pulse_site ) : ?><span class="pulse-meta__sep" aria-hidden="true">·</span><a href="<?php echo esc_url( $pulse_site ); ?>" rel="me noopener"><?php echo esc_html( wp_parse_url( $pulse_site, PHP_URL_HOST ) ); ?></a><?php endif; ?>
				</p>
			</div>
		</div>
	</header>
	<div class="pulse-wrap pulse-archive__layout">
		<div class="pulse-archive__main"><?php pulse_part( 'archive-loop', array( 'lead' => false ) ); ?></div>
		<?php pulse_part( 'sidebar', array( 'area' => 'pulse-archive' ) ); ?>
	</div>
</div>
<?php
get_footer();
