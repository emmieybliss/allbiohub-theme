<?php
/**
 * Brand details taken from the site itself: the accent colour from the logo,
 * the advertising link, and the social profiles from the existing Social menu.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Accent colour from the logo ---------- */

/**
 * Most common strong colour in the custom logo (or site icon), as #rrggbb.
 * Returns '' when there is no logo, GD is missing, or the logo has no colour.
 */
function pulse_logo_colour() {
	$id = (int) get_theme_mod( 'custom_logo' );
	if ( ! $id ) {
		$id = (int) get_option( 'site_icon' );
	}
	$file = $id ? get_attached_file( $id ) : '';
	if ( ! $file || ! is_readable( $file ) || ! function_exists( 'imagecreatefromstring' ) ) {
		return '';
	}
	$src = @imagecreatefromstring( (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.PHP.NoSilencedErrors, WordPress.WP.AlternativeFunctions
	if ( ! $src ) {
		return '';
	}
	$img = imagecreatetruecolor( 64, 64 );
	imagealphablending( $img, false );
	imagesavealpha( $img, true );
	imagecopyresampled( $img, $src, 0, 0, 0, 0, 64, 64, imagesx( $src ), imagesy( $src ) );
	imagedestroy( $src );

	// Bucket strong, mid-light pixels by hue (24 buckets) and keep running sums.
	$buckets = array();
	for ( $x = 0; $x < 64; $x++ ) {
		for ( $y = 0; $y < 64; $y++ ) {
			$c = imagecolorat( $img, $x, $y );
			if ( ( ( $c >> 24 ) & 0x7F ) > 60 ) {
				continue; // Transparent.
			}
			$rgb = array( ( $c >> 16 ) & 0xFF, ( $c >> 8 ) & 0xFF, $c & 0xFF );
			$hsl = pulse_rgb_to_hsl( $rgb );
			if ( $hsl[1] < 0.35 || $hsl[2] < 0.15 || $hsl[2] > 0.85 ) {
				continue; // Grey, near-black or near-white.
			}
			$b = (int) floor( $hsl[0] * 24 ) % 24;
			if ( ! isset( $buckets[ $b ] ) ) {
				$buckets[ $b ] = array( 0, 0, 0, 0 );
			}
			$buckets[ $b ][0] += $rgb[0];
			$buckets[ $b ][1] += $rgb[1];
			$buckets[ $b ][2] += $rgb[2];
			++$buckets[ $b ][3];
		}
	}
	imagedestroy( $img );
	if ( ! $buckets ) {
		return '';
	}
	uasort(
		$buckets,
		function ( $a, $b ) {
			return $b[3] - $a[3];
		}
	);
	$top = reset( $buckets );
	if ( $top[3] < 20 ) {
		return ''; // Too few coloured pixels to call it a brand colour.
	}
	return pulse_hex( array( $top[0] / $top[3], $top[1] / $top[3], $top[2] / $top[3] ) );
}

function pulse_rgb_to_hsl( $rgb ) {
	list( $r, $g, $b ) = array( $rgb[0] / 255, $rgb[1] / 255, $rgb[2] / 255 );
	$max = max( $r, $g, $b );
	$min = min( $r, $g, $b );
	$l   = ( $max + $min ) / 2;
	$d   = $max - $min;
	if ( 0.0 === (float) $d ) {
		return array( 0, 0, $l );
	}
	$s = $d / ( 1 - abs( 2 * $l - 1 ) );
	if ( $max === $r ) {
		$h = fmod( ( $g - $b ) / $d + 6, 6 );
	} elseif ( $max === $g ) {
		$h = ( $b - $r ) / $d + 2;
	} else {
		$h = ( $r - $g ) / $d + 4;
	}
	return array( $h / 6, $s, $l );
}

function pulse_hsl_to_rgb( $hsl ) {
	list( $h, $s, $l ) = $hsl;
	$c = ( 1 - abs( 2 * $l - 1 ) ) * $s;
	$x = $c * ( 1 - abs( fmod( $h * 6, 2 ) - 1 ) );
	$m = $l - $c / 2;
	$p = array( array( $c, $x, 0 ), array( $x, $c, 0 ), array( 0, $c, $x ), array( 0, $x, $c ), array( $x, 0, $c ), array( $c, 0, $x ) );
	$v = $p[ (int) floor( $h * 6 ) % 6 ];
	return array( ( $v[0] + $m ) * 255, ( $v[1] + $m ) * 255, ( $v[2] + $m ) * 255 );
}

function pulse_hex( $rgb ) {
	return sprintf( '#%02X%02X%02X', max( 0, min( 255, round( $rgb[0] ) ) ), max( 0, min( 255, round( $rgb[1] ) ) ), max( 0, min( 255, round( $rgb[2] ) ) ) );
}

function pulse_luminance( $rgb ) {
	$f = function ( $v ) {
		$v /= 255;
		return $v <= 0.03928 ? $v / 12.92 : pow( ( $v + 0.055 ) / 1.055, 2.4 );
	};
	return 0.2126 * $f( $rgb[0] ) + 0.7152 * $f( $rgb[1] ) + 0.0722 * $f( $rgb[2] );
}

function pulse_contrast( $a, $b ) {
	$la = pulse_luminance( $a );
	$lb = pulse_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/**
 * Light- and dark-mode accents from one brand colour, each readable as text
 * (WCAG AA, 4.5:1) on the theme's light and dark backgrounds.
 */
function pulse_accent_pair( $hex ) {
	$hex = ltrim( $hex, '#' );
	$rgb = array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	$hsl = pulse_rgb_to_hsl( $rgb );

	$light = $hsl;
	while ( $light[2] > 0.05 && pulse_contrast( pulse_hsl_to_rgb( $light ), array( 255, 255, 255 ) ) < 4.5 ) {
		$light[2] -= 0.02;
	}
	$dark    = $hsl;
	$dark[2] = max( $dark[2], 0.7 );
	while ( $dark[2] < 0.95 && pulse_contrast( pulse_hsl_to_rgb( $dark ), array( 17, 17, 17 ) ) < 4.5 ) {
		$dark[2] += 0.02;
	}
	return array( pulse_hex( pulse_hsl_to_rgb( $light ) ), pulse_hex( pulse_hsl_to_rgb( $dark ) ) );
}

/**
 * Migration step: set the accent colours from the logo. Logged and undoable.
 */
function pulse_step_brand() {
	$colour = pulse_logo_colour();
	if ( ! $colour ) {
		return '';
	}
	$prev = array(
		'accent'      => pulse_opt_raw( 'accent' ),
		'accent_dark' => pulse_opt_raw( 'accent_dark' ),
	);
	list( $light, $dark ) = pulse_accent_pair( $colour );
	pulse_set_opt_raw( 'accent', $light );
	pulse_set_opt_raw( 'accent_dark', $dark );
	/* translators: 1: logo colour, 2: light accent, 3: dark accent */
	return pulse_log_add( 'brand', sprintf( __( 'Matched the brand colour to the logo (%1$s): %2$s, and %3$s in dark mode', 'allbiohub-pulse' ), $colour, $light, $dark ), array( 'previous' => $prev ) );
}

/* ---------- Advertising link ---------- */

/**
 * Links to the site's own /advertise-with-us/ page point to the media kit
 * set in Theme Settings. The menus themselves are not edited.
 */
add_filter( 'nav_menu_link_attributes', 'pulse_advertise_link' );
function pulse_advertise_link( $atts ) {
	$kit = pulse_opt( 'advertise_url' );
	if ( $kit && ! empty( $atts['href'] ) && pulse_is_advertise_page_url( $atts['href'] ) ) {
		$atts['href'] = $kit;
	}
	return $atts;
}

function pulse_is_advertise_page_url( $url ) {
	$host = wp_parse_url( $url, PHP_URL_HOST );
	$path = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	return ( ! $host || preg_replace( '/^www\./', '', $host ) === preg_replace( '/^www\./', '', $home ) )
		&& '/advertise-with-us' === substr( $path, -18 );
}

/* ---------- Social profiles from the existing Social menu ---------- */

/**
 * The menu holding the social profiles: the one assigned to the Social
 * location, else an existing menu named "Social…".
 */
function pulse_social_menu() {
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['social'] ) ) {
		return (int) $locations['social'];
	}
	foreach ( wp_get_nav_menus() as $menu ) {
		if ( 0 === stripos( $menu->name, 'social' ) ) {
			return (int) $menu->term_id;
		}
	}
	return 0;
}
