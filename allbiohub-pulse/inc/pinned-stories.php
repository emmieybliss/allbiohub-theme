<?php
/**
 * Pinned Stories: 3–5 admin-chosen posts shown right below the hero.
 *
 * Stored as post meta (_allbiohub_pinned = 1, _allbiohub_pin_order = n) so pins
 * survive theme option resets and are visible in the posts list. Managed from
 * AllBioHub → Pinned Stories, or the "Pin" row action. No PHP editing needed.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_PIN_KEY   = '_allbiohub_pinned';
const PULSE_PIN_ORDER = '_allbiohub_pin_order';
const PULSE_PIN_MAX   = 5;

add_action( 'init', 'pulse_register_pin_meta' );
function pulse_register_pin_meta() {
	foreach ( array( PULSE_PIN_KEY, PULSE_PIN_ORDER ) as $key ) {
		register_post_meta(
			'post',
			$key,
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => false,
				'auth_callback' => function () {
					return current_user_can( 'edit_others_posts' );
				},
			)
		);
	}
}

/**
 * Pinned post IDs in display order.
 */
function pulse_pinned_ids() {
	$q = new WP_Query(
		array(
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'posts_per_page'      => 20,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'meta_key'            => PULSE_PIN_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
			'meta_value'          => '1', // phpcs:ignore WordPress.DB.SlowDBQuery
		)
	);
	// Order in PHP so a pin set directly in meta without an order still shows (last).
	$ids = array_map( 'intval', $q->posts );
	usort(
		$ids,
		function ( $a, $b ) {
			$oa = (int) get_post_meta( $a, PULSE_PIN_ORDER, true );
			$ob = (int) get_post_meta( $b, PULSE_PIN_ORDER, true );
			$oa = $oa ? $oa : 99;
			$ob = $ob ? $ob : 99;
			return $oa === $ob ? $b - $a : $oa - $ob;
		}
	);
	return array_slice( $ids, 0, PULSE_PIN_MAX );
}

function pulse_is_pinned( $post_id ) {
	return '1' === (string) get_post_meta( $post_id, PULSE_PIN_KEY, true );
}

/**
 * Replace the full pin list with $ids (in order). Unpins anything not listed.
 */
function pulse_set_pins( array $ids ) {
	$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) ), 0, PULSE_PIN_MAX );
	$ids = array_values( array_filter( $ids, function ( $id ) {
		return 'post' === get_post_type( $id );
	} ) );

	$current = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids', 'meta_key' => PULSE_PIN_KEY ) ); // phpcs:ignore WordPress.DB.SlowDBQuery
	foreach ( $current as $id ) {
		if ( ! in_array( (int) $id, $ids, true ) ) {
			delete_post_meta( $id, PULSE_PIN_KEY );
			delete_post_meta( $id, PULSE_PIN_ORDER );
		}
	}
	foreach ( $ids as $i => $id ) {
		update_post_meta( $id, PULSE_PIN_KEY, 1 );
		update_post_meta( $id, PULSE_PIN_ORDER, $i + 1 );
	}
	return $ids;
}

/* ---------- Admin screen ---------- */

add_action( 'admin_menu', 'pulse_pins_menu', 11 );
function pulse_pins_menu() {
	add_submenu_page( 'allbiohub', __( 'Pinned Stories', 'allbiohub-pulse' ), __( 'Pinned Stories', 'allbiohub-pulse' ), 'edit_others_posts', 'allbiohub-pinned', 'pulse_pins_screen' );
}

add_action( 'admin_post_pulse_save_pins', 'pulse_pins_save' );
function pulse_pins_save() {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'allbiohub-pulse' ), 403 );
	}
	check_admin_referer( 'pulse_save_pins' );
	$raw = isset( $_POST['pins'] ) ? sanitize_text_field( wp_unslash( $_POST['pins'] ) ) : '';
	pulse_set_pins( explode( ',', $raw ) );
	wp_safe_redirect( add_query_arg( array( 'page' => 'allbiohub-pinned', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}

function pulse_pins_screen() {
	$ids = pulse_pinned_ids();
	?>
	<div class="wrap pulse-admin">
		<?php pulse_admin_header( __( 'Pinned Stories', 'allbiohub-pulse' ), __( 'Choose 3 to 5 stories to pin directly below the homepage hero. Drag or use the arrows to set their order. Number 1 shows first.', 'allbiohub-pulse' ) ); ?>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Pinned stories saved.', 'allbiohub-pulse' ); ?></p></div>
		<?php endif; ?>
		<?php if ( count( $ids ) && count( $ids ) < 3 ) : ?>
			<div class="notice notice-warning"><p><?php esc_html_e( 'At least 3 pinned stories are recommended. Fewer will still display.', 'allbiohub-pulse' ); ?></p></div>
		<?php elseif ( ! $ids ) : ?>
			<div class="notice notice-info"><p><?php esc_html_e( 'Nothing is pinned, so the homepage currently shows the latest featured stories in this row.', 'allbiohub-pulse' ); ?></p></div>
		<?php endif; ?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pulse-card">
			<input type="hidden" name="action" value="pulse_save_pins">
			<?php wp_nonce_field( 'pulse_save_pins' ); ?>
			<?php pulse_admin_picker( 'pins', $ids, PULSE_PIN_MAX, 'post', true ); ?>
			<p class="submit"><button class="button button-primary"><?php esc_html_e( 'Save pinned stories', 'allbiohub-pulse' ); ?></button></p>
		</form>
	</div>
	<?php
}

/* ---------- Posts list integration ---------- */

add_filter( 'manage_post_posts_columns', 'pulse_pin_column' );
function pulse_pin_column( $cols ) {
	$cols['pulse_pin'] = '<span title="' . esc_attr__( 'Pinned on homepage', 'allbiohub-pulse' ) . '">' . esc_html__( 'Pin', 'allbiohub-pulse' ) . '</span>';
	return $cols;
}

add_action( 'manage_post_posts_custom_column', 'pulse_pin_column_value', 10, 2 );
function pulse_pin_column_value( $col, $post_id ) {
	if ( 'pulse_pin' === $col && pulse_is_pinned( $post_id ) ) {
		echo '<span class="pulse-pin-badge">' . esc_html( (int) get_post_meta( $post_id, PULSE_PIN_ORDER, true ) ) . '</span>';
	}
}

add_filter( 'post_row_actions', 'pulse_pin_row_action', 10, 2 );
function pulse_pin_row_action( $actions, $post ) {
	if ( 'post' !== $post->post_type || 'publish' !== $post->post_status || ! current_user_can( 'edit_others_posts' ) ) {
		return $actions;
	}
	$pinned = pulse_is_pinned( $post->ID );
	$url    = wp_nonce_url( admin_url( 'admin-post.php?action=pulse_toggle_pin&post=' . $post->ID ), 'pulse_toggle_pin_' . $post->ID );
	$actions['pulse_pin'] = sprintf( '<a href="%s">%s</a>', esc_url( $url ), $pinned ? esc_html__( 'Unpin', 'allbiohub-pulse' ) : esc_html__( 'Pin to homepage', 'allbiohub-pulse' ) );
	return $actions;
}

add_action( 'admin_post_pulse_toggle_pin', 'pulse_toggle_pin' );
function pulse_toggle_pin() {
	$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
	if ( ! $post_id || ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'allbiohub-pulse' ), 403 );
	}
	check_admin_referer( 'pulse_toggle_pin_' . $post_id );
	$ids = pulse_pinned_ids();
	if ( pulse_is_pinned( $post_id ) ) {
		$ids = array_diff( $ids, array( $post_id ) );
		$msg = 'unpinned';
	} elseif ( count( $ids ) >= PULSE_PIN_MAX ) {
		$msg = 'full';
	} else {
		$ids[] = $post_id;
		$msg   = 'pinned';
	}
	pulse_set_pins( $ids );
	wp_safe_redirect( add_query_arg( 'pulse_pin', $msg, wp_get_referer() ? wp_get_referer() : admin_url( 'edit.php' ) ) );
	exit;
}

add_action( 'admin_notices', 'pulse_pin_notice' );
function pulse_pin_notice() {
	if ( empty( $_GET['pulse_pin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$msg = sanitize_key( $_GET['pulse_pin'] ); // phpcs:ignore WordPress.Security.NonceVerification
	$map = array(
		'pinned'   => array( 'success', __( 'Story pinned to the homepage.', 'allbiohub-pulse' ) ),
		'unpinned' => array( 'success', __( 'Story unpinned.', 'allbiohub-pulse' ) ),
		'full'     => array( 'warning', __( 'Five stories are already pinned. Unpin one first.', 'allbiohub-pulse' ) ),
	);
	if ( isset( $map[ $msg ] ) ) {
		printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr( $map[ $msg ][0] ), esc_html( $map[ $msg ][1] ) );
	}
}
