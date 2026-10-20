<?php
/**
 * Front-end and admin assets.
 *
 * One stylesheet and one small deferred script on the front end. No jQuery,
 * no frameworks, no third-party requests.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'pulse_enqueue' );
function pulse_enqueue() {
	$ver = PULSE_VERSION . '.' . filemtime( PULSE_DIR . '/assets/css/main.css' );
	wp_enqueue_style( 'pulse-main', PULSE_URI . '/assets/css/main.css', array(), $ver );
	wp_add_inline_style( 'pulse-main', pulse_inline_tokens() );

	wp_enqueue_script( 'pulse-site', PULSE_URI . '/assets/js/site.js', array(), PULSE_VERSION . '.' . filemtime( PULSE_DIR . '/assets/js/site.js' ), array( 'strategy' => 'defer', 'in_footer' => true ) );
	wp_localize_script(
		'pulse-site',
		'pulseSite',
		array(
			'viewUrl' => ( pulse_opt( 'views_enabled' ) && is_singular( array( 'post', 'abh_video' ) ) ) ? esc_url_raw( rest_url( 'allbiohub/v1/view/' . get_queried_object_id() ) ) : '',
			'i18n'    => array(
				'copied'         => __( 'Link copied', 'allbiohub-pulse' ),
				'subscribeError' => __( 'Sorry, we could not sign you up just now. Please try again later.', 'allbiohub-pulse' ),
			),
		)
	);

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}

/**
 * Admin-chosen accent colours become CSS custom properties.
 */
function pulse_inline_tokens() {
	$accent = pulse_opt( 'accent' );
	$dark   = pulse_opt( 'accent_dark' );
	$css    = '';
	if ( $accent ) {
		$css .= ':root{--pulse-accent:' . $accent . ';}';
	}
	if ( $dark ) {
		$css .= ':root[data-theme="dark"]{--pulse-accent:' . $dark . ';}@media (prefers-color-scheme:dark){:root:not([data-theme="light"]){--pulse-accent:' . $dark . ';}}';
	}
	return $css;
}

/**
 * Preload the two fonts used above the fold.
 */
add_action( 'wp_head', 'pulse_preload_fonts', 1 );
function pulse_preload_fonts() {
	if ( ! pulse_opt( 'perf_preload_fonts' ) ) {
		return;
	}
	foreach ( array( 'instrument-serif-latin-400-normal.woff2', 'inter-latin-wght-normal.woff2' ) as $font ) {
		printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( PULSE_URI . '/assets/fonts/' . $font ) );
	}
}

/**
 * Apply the stored colour scheme before first paint to avoid a flash.
 */
add_action( 'wp_head', 'pulse_color_scheme_boot', 0 );
function pulse_color_scheme_boot() {
	$default = pulse_opt( 'color_scheme_default' );
	?>
<script>(function(){try{var s=localStorage.getItem('pulse-scheme')||<?php echo wp_json_encode( $default ); ?>;if(s==='light'||s==='dark'){document.documentElement.setAttribute('data-theme',s);}}catch(e){}})();</script>
	<?php
}

add_action( 'admin_enqueue_scripts', 'pulse_admin_enqueue' );
function pulse_admin_enqueue( $hook ) {
	$screen = get_current_screen();
	$ours   = ( false !== strpos( (string) $hook, 'allbiohub' ) ) || ( $screen && in_array( $screen->post_type, array( 'abh_video', 'post' ), true ) );
	if ( ! $ours ) {
		return;
	}
	wp_enqueue_media();
	wp_enqueue_style( 'pulse-admin', PULSE_URI . '/admin/css/admin.css', array(), PULSE_VERSION . '.' . filemtime( PULSE_DIR . '/admin/css/admin.css' ) );
	wp_enqueue_script( 'pulse-admin', PULSE_URI . '/admin/js/admin.js', array(), PULSE_VERSION . '.' . filemtime( PULSE_DIR . '/admin/js/admin.js' ), true );
	wp_localize_script(
		'pulse-admin',
		'pulseAdmin',
		array(
			'rest'  => esc_url_raw( rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'i18n'  => array(
				'search'    => __( 'Search posts…', 'allbiohub-pulse' ),
				'remove'    => __( 'Remove', 'allbiohub-pulse' ),
				'up'        => __( 'Move up', 'allbiohub-pulse' ),
				'down'      => __( 'Move down', 'allbiohub-pulse' ),
				'max'       => __( 'Maximum reached', 'allbiohub-pulse' ),
				'choose'    => __( 'Choose', 'allbiohub-pulse' ),
				'noResults' => __( 'No matching posts', 'allbiohub-pulse' ),
			),
		)
	);
}
