<?php
/**
 * Sponsored editorial posts: clearly labelled, never disguised as editorial.
 *
 * A post is sponsored if it is in the existing "sponsored" category or is
 * marked sponsored in the post's "Sponsored content" box. Sponsored posts get
 * the SPONSORED badge everywhere they appear, a disclosure line on the article,
 * and rel="sponsored" on outbound links (render-time only).
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_SPONSOR_KEY = '_allbiohub_sponsor';

function pulse_sponsor( $post_id ) {
	$v = get_post_meta( $post_id, PULSE_SPONSOR_KEY, true );
	return wp_parse_args( is_array( $v ) ? $v : array(), array( 'on' => 0, 'name' => '', 'url' => '' ) );
}

function pulse_is_sponsored( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return false;
	}
	$s = pulse_sponsor( $post->ID );
	return ! empty( $s['on'] ) || has_category( 'sponsored', $post );
}

/**
 * Badge markup. Uses the amber sponsored style, never the editorial purple.
 */
function pulse_sponsored_badge( $post = null, $with_name = false ) {
	if ( ! pulse_is_sponsored( $post ) ) {
		return '';
	}
	$s    = pulse_sponsor( get_post( $post )->ID );
	$name = $with_name && $s['name'] ? ' <span class="pulse-sponsored__by">' . esc_html( sprintf( /* translators: %s: sponsor */ __( 'by %s', 'allbiohub-pulse' ), $s['name'] ) ) . '</span>' : '';
	return '<span class="pulse-sponsored">' . esc_html__( 'Sponsored', 'allbiohub-pulse' ) . $name . '</span>';
}

add_action( 'add_meta_boxes_post', 'pulse_sponsor_box' );
function pulse_sponsor_box() {
	add_meta_box( 'pulse-sponsor', __( 'Sponsored content', 'allbiohub-pulse' ), 'pulse_sponsor_box_html', 'post', 'side', 'default' );
}

function pulse_sponsor_box_html( $post ) {
	$s = pulse_sponsor( $post->ID );
	wp_nonce_field( 'pulse_sponsor', 'pulse_sponsor_nonce' );
	?>
	<p><label><input type="checkbox" name="pulse_sponsor[on]" value="1" <?php checked( $s['on'], 1 ); ?>> <?php esc_html_e( 'This post is paid / sponsored', 'allbiohub-pulse' ); ?></label></p>
	<p><label><strong><?php esc_html_e( 'Sponsor name', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="text" name="pulse_sponsor[name]" value="<?php echo esc_attr( $s['name'] ); ?>"></label></p>
	<p><label><strong><?php esc_html_e( 'Sponsor website', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="url" name="pulse_sponsor[url]" value="<?php echo esc_attr( $s['url'] ); ?>"></label></p>
	<p class="description"><?php esc_html_e( 'Posts in the Sponsored category are labelled automatically.', 'allbiohub-pulse' ); ?></p>
	<?php
}

add_action( 'save_post_post', 'pulse_sponsor_save' );
function pulse_sponsor_save( $post_id ) {
	if ( ! isset( $_POST['pulse_sponsor_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pulse_sponsor_nonce'] ) ), 'pulse_sponsor' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in = isset( $_POST['pulse_sponsor'] ) ? (array) wp_unslash( $_POST['pulse_sponsor'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$v  = array(
		'on'   => empty( $in['on'] ) ? 0 : 1,
		'name' => sanitize_text_field( isset( $in['name'] ) ? $in['name'] : '' ),
		'url'  => esc_url_raw( isset( $in['url'] ) ? $in['url'] : '' ),
	);
	if ( $v['on'] || $v['name'] || $v['url'] ) {
		update_post_meta( $post_id, PULSE_SPONSOR_KEY, $v );
	} else {
		delete_post_meta( $post_id, PULSE_SPONSOR_KEY );
	}
}

/**
 * Sponsored posts: mark outbound links rel="sponsored noopener" at render time.
 */
add_filter( 'the_content', 'pulse_sponsored_links', 30 );
function pulse_sponsored_links( $content ) {
	if ( ! is_singular( 'post' ) || ! pulse_is_sponsored() ) {
		return $content;
	}
	$host = wp_parse_url( home_url(), PHP_URL_HOST );
	return preg_replace_callback(
		'/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i',
		function ( $m ) use ( $host ) {
			$link_host = wp_parse_url( $m[1], PHP_URL_HOST );
			if ( ! $link_host || $link_host === $host || preg_match( '/\srel=/i', $m[0] ) ) {
				return $m[0];
			}
			return str_replace( '<a ', '<a rel="sponsored noopener" ', $m[0] );
		},
		$content
	);
}
