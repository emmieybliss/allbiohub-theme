<?php
/**
 * "In this story" table of contents from the post's own h2/h3 headings.
 * Shown only for posts with at least three headings.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$items = pulse_toc_items();
if ( count( $items ) < 3 || ! pulse_opt( 'article_toc' ) ) {
	return;
}
$title = ! empty( $args['title'] ) ? $args['title'] : __( 'In this story', 'allbiohub-pulse' );
?>
<details class="pulse-toc" open>
	<summary class="pulse-toc__title"><?php echo esc_html( $title ); ?> <?php echo pulse_icon( 'chevron', 16 ); // phpcs:ignore ?></summary>
	<ol class="pulse-toc__list">
		<?php foreach ( $items as $item ) : ?>
			<li class="pulse-toc__item pulse-toc__item--h<?php echo (int) $item['level']; ?>"><a href="#<?php echo esc_attr( $item['id'] ); ?>"><?php echo esc_html( $item['text'] ); ?></a></li>
		<?php endforeach; ?>
	</ol>
</details>
