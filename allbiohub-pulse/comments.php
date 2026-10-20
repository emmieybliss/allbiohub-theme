<?php
/**
 * Comments. Existing comments are shown exactly as stored.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

if ( post_password_required() ) {
	return;
}
?>
<section id="comments" class="pulse-comments">
	<?php if ( have_comments() ) : ?>
		<h2 class="pulse-comments__title">
			<?php /* translators: %s: comment count */ echo esc_html( sprintf( _n( '%s comment', '%s comments', get_comments_number(), 'allbiohub-pulse' ), number_format_i18n( get_comments_number() ) ) ); ?>
		</h2>
		<ol class="pulse-comments__list">
			<?php wp_list_comments( array( 'style' => 'ol', 'short_ping' => true, 'avatar_size' => 40 ) ); ?>
		</ol>
		<?php the_comments_navigation(); ?>
	<?php endif; ?>
	<?php if ( ! comments_open() && get_comments_number() ) : ?>
		<p class="pulse-comments__closed"><?php esc_html_e( 'Comments are closed.', 'allbiohub-pulse' ); ?></p>
	<?php endif; ?>
	<?php
	comment_form(
		array(
			'class_submit'       => 'pulse-btn',
			'title_reply'        => __( 'Join the conversation', 'allbiohub-pulse' ),
			'title_reply_before' => '<h2 id="reply-title" class="pulse-comments__title">',
			'title_reply_after'  => '</h2>',
		)
	);
	?>
</section>
