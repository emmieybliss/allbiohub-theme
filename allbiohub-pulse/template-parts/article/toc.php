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
		<?php
		$open = false;
		foreach ( $items as $i => $item ) {
			$link = '<a href="#' . esc_attr( $item['id'] ) . '">' . esc_html( $item['text'] ) . '</a>';
			if ( 3 === $item['level'] && $i > 0 ) {
				echo ( $open ? '' : '<ol class="pulse-toc__sub">' ) . '<li class="pulse-toc__item pulse-toc__item--h3">' . $link . '</li>'; // phpcs:ignore WordPress.Security.EscapeOutput
				$open = true;
				continue;
			}
			if ( $i > 0 ) {
				echo ( $open ? '</ol>' : '' ) . '</li>';
			}
			$open = false;
			echo '<li class="pulse-toc__item">' . $link; // phpcs:ignore WordPress.Security.EscapeOutput
		}
		echo ( $open ? '</ol>' : '' ) . '</li>';
		?>
	</ol>
</details>
