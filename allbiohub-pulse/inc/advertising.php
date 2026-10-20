<?php
/**
 * AllBioHub advertising: placements, scheduling, rendering, tracking, inventory.
 *
 * Every placement starts disabled, so activating the theme never doubles up
 * ads that Ad Inserter already places. Raw HTML/script can only be saved by
 * users with the unfiltered_html capability; everyone else is sanitised.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_ADS        = 'allbiohub_ads';
const PULSE_AD_STATS   = 'allbiohub_ad_stats';

/**
 * Placement registry: slot => [label, group, description].
 */
function pulse_ad_slots() {
	return array(
		'top'                => array( __( 'Top Site Advertisement', 'allbiohub-pulse' ), 'site', __( 'Above the header on every page.', 'allbiohub-pulse' ) ),
		'hero_sponsor'       => array( __( 'Hero Sponsor', 'allbiohub-pulse' ), 'home', __( 'Small “presented by” line under the homepage hero.', 'allbiohub-pulse' ) ),
		'home_story'         => array( __( 'Homepage Sponsored Story', 'allbiohub-pulse' ), 'home', __( 'A sponsored story card in the Sponsored section.', 'allbiohub-pulse' ) ),
		'sponsored'          => array( __( 'Sponsored Content', 'allbiohub-pulse' ), 'home', __( 'The main Sponsored section after Pinned Stories.', 'allbiohub-pulse' ) ),
		'home_banner'        => array( __( 'Homepage Sponsored Banner', 'allbiohub-pulse' ), 'home', __( 'Full-width banner after the category sections.', 'allbiohub-pulse' ) ),
		'category_sponsor'   => array( __( 'Category Sponsor', 'allbiohub-pulse' ), 'home', __( 'Shown on category pages and homepage category sections.', 'allbiohub-pulse' ) ),
		'newsletter_sponsor' => array( __( 'Newsletter Sponsor', 'allbiohub-pulse' ), 'home', __( 'Line inside the newsletter block.', 'allbiohub-pulse' ) ),
		'article_top'        => array( __( 'Article Top', 'allbiohub-pulse' ), 'article', __( 'Below the article hero image.', 'allbiohub-pulse' ) ),
		'inline'             => array( __( 'Inline Article Advertisement', 'allbiohub-pulse' ), 'article', __( 'Inside the article body, after the chosen paragraphs.', 'allbiohub-pulse' ) ),
		'sidebar'            => array( __( 'Sidebar Advertisement', 'allbiohub-pulse' ), 'article', __( 'Article and archive sidebar.', 'allbiohub-pulse' ) ),
		'article_bottom'     => array( __( 'Article Bottom', 'allbiohub-pulse' ), 'article', __( 'After the article, before related stories.', 'allbiohub-pulse' ) ),
		'footer'             => array( __( 'Footer Advertisement', 'allbiohub-pulse' ), 'footer', __( 'Above the footer on every page.', 'allbiohub-pulse' ) ),
		'custom'             => array( __( 'Custom Advertisement', 'allbiohub-pulse' ), 'custom', __( 'Place anywhere with [allbiohub_ad slot="custom"].', 'allbiohub-pulse' ) ),
		'head'               => array( __( 'Site-wide Ad Code (head)', 'allbiohub-pulse' ), 'site', __( 'Code that loads in the page head on every page, such as AdSense Auto ads. Nothing is shown in a box.', 'allbiohub-pulse' ) ),
	);
}

function pulse_ad_defaults() {
	return array(
		'enabled'     => 0,
		'type'        => 'image',
		'image'       => 0,
		'url'         => '',
		'headline'    => '',
		'body'        => '',
		'cta'         => '',
		'html'        => '',
		'shortcode'   => '',
		'post_id'     => 0,
		'start'       => '',
		'end'         => '',
		'new_tab'     => 1,
		'label'       => __( 'Sponsored', 'allbiohub-pulse' ),
		'campaign'    => '',
		'advertiser'  => '',
		'tracking'    => '',
		'track_imp'   => 0,
		'track_click' => 1,
		'after_para'  => '3',
		'repeat'      => 0,
	);
}

function pulse_ads() {
	$stored = get_option( PULSE_ADS, array() );
	$out    = array();
	foreach ( array_keys( pulse_ad_slots() ) as $slot ) {
		$out[ $slot ] = wp_parse_args( isset( $stored[ $slot ] ) && is_array( $stored[ $slot ] ) ? $stored[ $slot ] : array(), pulse_ad_defaults() );
	}
	return $out;
}

function pulse_ad( $slot ) {
	$ads = pulse_ads();
	return isset( $ads[ $slot ] ) ? $ads[ $slot ] : null;
}

/**
 * Enabled, in date window, and has something to show.
 */
function pulse_ad_is_active( $slot ) {
	$ad = pulse_ad( $slot );
	if ( ! $ad || ! $ad['enabled'] ) {
		return false;
	}
	$today = wp_date( 'Y-m-d' );
	if ( ( $ad['start'] && $today < $ad['start'] ) || ( $ad['end'] && $today > $ad['end'] ) ) {
		return false;
	}
	switch ( $ad['type'] ) {
		case 'image':
			return (bool) ( $ad['image'] || $ad['headline'] );
		case 'html':
		case 'script':
			return '' !== trim( $ad['html'] );
		case 'shortcode':
			return '' !== trim( $ad['shortcode'] );
		case 'post':
			return $ad['post_id'] && 'publish' === get_post_status( $ad['post_id'] );
	}
	return false;
}

/**
 * Click-through URL (tracked redirect when click tracking is on).
 */
function pulse_ad_href( $slot, $ad ) {
	if ( ! $ad['url'] ) {
		return '';
	}
	if ( $ad['track_click'] ) {
		return add_query_arg( array( 'abh_ad' => $slot ), home_url( '/' ) );
	}
	return $ad['url'];
}

/**
 * Render a placement. $variant tweaks layout (banner|card|inline|line|panel).
 */
function pulse_render_ad( $slot, $variant = 'banner' ) {
	if ( ! pulse_ad_is_active( $slot ) ) {
		return '';
	}
	$ad    = pulse_ad( $slot );
	$label = $ad['label'] ? $ad['label'] : __( 'Sponsored', 'allbiohub-pulse' );
	$attrs = sprintf( ' data-slot="%s"%s', esc_attr( $slot ), $ad['track_imp'] ? ' data-pulse-imp="' . esc_url( rest_url( 'allbiohub/v1/ad-imp/' . $slot ) ) . '"' : '' );
	$href  = pulse_ad_href( $slot, $ad );
	$rel   = 'sponsored noopener' . ( $ad['new_tab'] ? ' noreferrer' : '' );
	$tgt   = $ad['new_tab'] ? ' target="_blank"' : '';
	$inner = '';

	switch ( $ad['type'] ) {
		case 'image':
			$img = $ad['image'] ? wp_get_attachment_image( $ad['image'], 'large', false, array( 'class' => 'pulse-ad__img', 'loading' => 'lazy', 'alt' => $ad['headline'] ? $ad['headline'] : $ad['advertiser'] ) ) : '';
			if ( $ad['headline'] || $ad['body'] ) {
				// Rich sponsored format: label, headline, body, CTA (+ optional image).
				$inner = sprintf(
					'%s<div class="pulse-ad__copy"><p class="pulse-ad__headline">%s</p>%s%s%s</div>',
					$img ? '<div class="pulse-ad__media">' . $img . '</div>' : '',
					esc_html( $ad['headline'] ),
					$ad['body'] ? '<p class="pulse-ad__body">' . esc_html( $ad['body'] ) . '</p>' : '',
					$ad['advertiser'] ? '<p class="pulse-ad__by">' . esc_html( $ad['advertiser'] ) . '</p>' : '',
					$href ? sprintf( '<span class="pulse-btn pulse-btn--sponsored">%s</span>', esc_html( $ad['cta'] ? $ad['cta'] : __( 'Learn more', 'allbiohub-pulse' ) ) ) : ''
				);
				$inner = $href ? sprintf( '<a class="pulse-ad__link pulse-ad__rich" href="%s" rel="%s"%s>%s</a>', esc_url( $href ), esc_attr( $rel ), $tgt, $inner ) : '<div class="pulse-ad__rich">' . $inner . '</div>';
			} else {
				$inner = $href ? sprintf( '<a class="pulse-ad__link" href="%s" rel="%s"%s>%s</a>', esc_url( $href ), esc_attr( $rel ), $tgt, $img ) : $img;
			}
			break;
		case 'html':
		case 'script':
			$inner = $ad['html']; // Saved only by unfiltered_html users or kses-filtered.
			break;
		case 'shortcode':
			$inner = do_shortcode( $ad['shortcode'] );
			break;
		case 'post':
			$p     = get_post( $ad['post_id'] );
			$inner = sprintf(
				'<a class="pulse-ad__link pulse-ad__rich" href="%s"><div class="pulse-ad__media">%s</div><div class="pulse-ad__copy"><p class="pulse-ad__headline">%s</p><p class="pulse-ad__body">%s</p>%s<span class="pulse-btn pulse-btn--sponsored">%s</span></div></a>',
				esc_url( get_permalink( $p ) ),
				pulse_image( $p, array( 'size' => 'pulse-card' ) ),
				esc_html( get_the_title( $p ) ),
				esc_html( pulse_excerpt( $p, 22 ) ),
				$ad['advertiser'] ? '<p class="pulse-ad__by">' . esc_html( $ad['advertiser'] ) . '</p>' : '',
				esc_html( $ad['cta'] ? $ad['cta'] : __( 'Read more', 'allbiohub-pulse' ) )
			);
			break;
	}
	if ( '' === trim( (string) $inner ) ) {
		return '';
	}
	$pixel = $ad['tracking'] ? sprintf( '<img class="pulse-ad__pixel" src="%s" width="1" height="1" alt="" aria-hidden="true" loading="lazy">', esc_url( $ad['tracking'] ) ) : '';
	return sprintf(
		'<aside class="pulse-ad pulse-ad--%1$s pulse-ad--slot-%2$s"%3$s aria-label="%4$s"><span class="pulse-ad__label">%5$s</span>%6$s%7$s</aside>',
		esc_attr( $variant ),
		esc_attr( $slot ),
		$attrs,
		esc_attr( $label ),
		esc_html( $label ),
		$inner, // phpcs:ignore WordPress.Security.EscapeOutput -- escaped per type above.
		$pixel
	);
}

function pulse_the_ad( $slot, $variant = 'banner' ) {
	echo pulse_render_ad( $slot, $variant ); // phpcs:ignore WordPress.Security.EscapeOutput
}

add_shortcode( 'allbiohub_ad', 'pulse_ad_shortcode' );
function pulse_ad_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'slot' => 'custom' ), $atts, 'allbiohub_ad' );
	$slot = sanitize_key( $atts['slot'] );
	return array_key_exists( $slot, pulse_ad_slots() ) ? pulse_render_ad( $slot, 'inline' ) : '';
}

/**
 * Paragraph numbers for the inline ad: the listed ones (e.g. "3, 8, 14"),
 * then every N paragraphs after the last one when "repeat" is set.
 * At most 10 ads per article.
 */
function pulse_inline_positions( $ad, $total ) {
	$list = array_filter( array_map( 'absint', preg_split( '/[^0-9]+/', (string) $ad['after_para'] ) ) );
	$list = $list ? $list : array( 3 );
	$list = array_values( array_unique( $list ) );
	sort( $list );
	$every = (int) $ad['repeat'];
	if ( $every > 0 ) {
		for ( $n = end( $list ) + $every; $n < $total; $n += $every ) {
			$list[] = $n;
		}
	}
	// Never after the last paragraph, so the article doesn't end on an ad.
	$list = array_filter( $list, function ( $n ) use ( $total ) { return $n < $total; } );
	return array_slice( array_values( $list ), 0, 10 );
}

/**
 * Inline article ads after the chosen paragraphs (render-time; content is
 * not edited).
 */
add_filter( 'the_content', 'pulse_inline_ad', 40 );
function pulse_inline_ad( $content ) {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || pulse_is_sponsored() || ! pulse_ad_is_active( 'inline' ) ) {
		return $content;
	}
	$parts = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE );
	$total = (int) floor( count( $parts ) / 2 );
	$at    = pulse_inline_positions( pulse_ad( 'inline' ), $total );
	if ( ! $at ) {
		return $content;
	}
	$ad  = pulse_render_ad( 'inline', 'inline' );
	$out = '';
	$n   = 0;
	foreach ( $parts as $part ) {
		$out .= $part;
		if ( 0 === strcasecmp( $part, '</p>' ) && in_array( ++$n, $at, true ) ) {
			$out .= $ad;
		}
	}
	return $out;
}

/* ---------- Tracking ---------- */

function pulse_ad_count( $slot, $kind ) {
	$stats = get_option( PULSE_AD_STATS, array() );
	$day   = wp_date( 'Y-m-d' );
	if ( ! isset( $stats[ $slot ][ $day ] ) ) {
		$stats[ $slot ][ $day ] = array( 'imp' => 0, 'click' => 0 );
	}
	++$stats[ $slot ][ $day ][ $kind ];
	// Keep 90 days per slot.
	if ( count( $stats[ $slot ] ) > 90 ) {
		ksort( $stats[ $slot ] );
		$stats[ $slot ] = array_slice( $stats[ $slot ], -90, null, true );
	}
	update_option( PULSE_AD_STATS, $stats, false );
}

add_action( 'template_redirect', 'pulse_ad_click_redirect', 1 );
function pulse_ad_click_redirect() {
	if ( empty( $_GET['abh_ad'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return;
	}
	$slot = sanitize_key( wp_unslash( $_GET['abh_ad'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
	$ad   = pulse_ad( $slot );
	if ( ! $ad || ! $ad['url'] ) {
		return;
	}
	if ( $ad['track_click'] ) {
		pulse_ad_count( $slot, 'click' );
	}
	nocache_headers();
	// Only the URL stored by an admin for this slot can be the target.
	wp_redirect( esc_url_raw( $ad['url'] ), 302, 'AllBioHub' ); // phpcs:ignore WordPress.Security.SafeRedirect
	exit;
}

add_action( 'rest_api_init', 'pulse_ad_imp_route' );
function pulse_ad_imp_route() {
	register_rest_route(
		'allbiohub/v1',
		'/ad-imp/(?P<slot>[a-z_]+)',
		array(
			'methods'             => 'POST',
			'permission_callback' => '__return_true',
			'callback'            => function ( WP_REST_Request $r ) {
				$slot = sanitize_key( $r['slot'] );
				$ad   = pulse_ad( $slot );
				if ( $ad && $ad['track_imp'] && pulse_ad_is_active( $slot ) ) {
					pulse_ad_count( $slot, 'imp' );
				}
				return new WP_REST_Response( array( 'ok' => true ), 200 );
			},
		)
	);
}

function pulse_ad_totals( $slot, $days = 30 ) {
	$stats = get_option( PULSE_AD_STATS, array() );
	$from  = wp_date( 'Y-m-d', time() - $days * DAY_IN_SECONDS );
	$t     = array( 'imp' => 0, 'click' => 0 );
	foreach ( isset( $stats[ $slot ] ) ? $stats[ $slot ] : array() as $day => $row ) {
		if ( $day >= $from ) {
			$t['imp']   += (int) $row['imp'];
			$t['click'] += (int) $row['click'];
		}
	}
	return $t;
}

/* ---------- Admin ---------- */

add_action( 'admin_menu', 'pulse_ads_menu', 11 );
function pulse_ads_menu() {
	add_submenu_page( 'allbiohub', __( 'Ads & Sponsored Content', 'allbiohub-pulse' ), __( 'Ads & Sponsored', 'allbiohub-pulse' ), 'manage_options', 'allbiohub-ads', 'pulse_ads_screen' );
	add_submenu_page( 'allbiohub', __( 'Ad Inventory', 'allbiohub-pulse' ), __( 'Ad Inventory', 'allbiohub-pulse' ), 'manage_options', 'allbiohub-ad-inventory', 'pulse_ad_inventory_screen' );
}

add_action( 'admin_post_pulse_save_ad', 'pulse_ad_save' );
function pulse_ad_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'allbiohub-pulse' ), 403 );
	}
	check_admin_referer( 'pulse_save_ad' );
	$slot = isset( $_POST['slot'] ) ? sanitize_key( wp_unslash( $_POST['slot'] ) ) : '';
	if ( ! array_key_exists( $slot, pulse_ad_slots() ) ) {
		wp_die( esc_html__( 'Unknown placement.', 'allbiohub-pulse' ) );
	}
	$in   = isset( $_POST['ad'] ) ? (array) wp_unslash( $_POST['ad'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$type = isset( $in['type'] ) && in_array( $in['type'], array( 'image', 'html', 'script', 'shortcode', 'post' ), true ) ? $in['type'] : 'image';
	$raw  = isset( $in['html'] ) ? (string) $in['html'] : '';
	if ( 'script' === $type && ! current_user_can( 'unfiltered_html' ) ) {
		$type = 'html';
	}
	$ad = array(
		'enabled'     => empty( $in['enabled'] ) ? 0 : 1,
		'type'        => $type,
		'image'       => absint( isset( $in['image'] ) ? $in['image'] : 0 ),
		'url'         => esc_url_raw( isset( $in['url'] ) ? $in['url'] : '' ),
		'headline'    => sanitize_text_field( isset( $in['headline'] ) ? $in['headline'] : '' ),
		'body'        => sanitize_textarea_field( isset( $in['body'] ) ? $in['body'] : '' ),
		'cta'         => sanitize_text_field( isset( $in['cta'] ) ? $in['cta'] : '' ),
		'html'        => current_user_can( 'unfiltered_html' ) ? $raw : wp_kses_post( $raw ),
		'shortcode'   => sanitize_text_field( isset( $in['shortcode'] ) ? $in['shortcode'] : '' ),
		'post_id'     => absint( isset( $in['post_id'] ) ? $in['post_id'] : 0 ),
		'start'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', isset( $in['start'] ) ? $in['start'] : '' ) ? $in['start'] : '',
		'end'         => preg_match( '/^\d{4}-\d{2}-\d{2}$/', isset( $in['end'] ) ? $in['end'] : '' ) ? $in['end'] : '',
		'new_tab'     => empty( $in['new_tab'] ) ? 0 : 1,
		'label'       => sanitize_text_field( isset( $in['label'] ) ? $in['label'] : '' ),
		'campaign'    => sanitize_text_field( isset( $in['campaign'] ) ? $in['campaign'] : '' ),
		'advertiser'  => sanitize_text_field( isset( $in['advertiser'] ) ? $in['advertiser'] : '' ),
		'tracking'    => esc_url_raw( isset( $in['tracking'] ) ? $in['tracking'] : '' ),
		'track_imp'   => empty( $in['track_imp'] ) ? 0 : 1,
		'track_click' => empty( $in['track_click'] ) ? 0 : 1,
		'after_para'  => implode( ', ', array_slice( array_unique( array_filter( array_map( 'absint', preg_split( '/[^0-9]+/', (string) ( isset( $in['after_para'] ) ? $in['after_para'] : '3' ) ) ) ) ), 0, 10 ) ),
		'repeat'      => min( 50, absint( isset( $in['repeat'] ) ? $in['repeat'] : 0 ) ),
	);
	if ( '' === $ad['label'] ) {
		$ad['label'] = __( 'Sponsored', 'allbiohub-pulse' );
	}
	$all          = get_option( PULSE_ADS, array() );
	$all[ $slot ] = $ad;
	update_option( PULSE_ADS, $all, true );
	wp_safe_redirect( add_query_arg( array( 'page' => 'allbiohub-ads', 'slot' => $slot, 'updated' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}

function pulse_ads_screen() {
	$slots   = pulse_ad_slots();
	$current = isset( $_GET['slot'] ) ? sanitize_key( $_GET['slot'] ) : 'top'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! isset( $slots[ $current ] ) ) {
		$current = 'top';
	}
	$ad = pulse_ad( $current );
	?>
	<div class="wrap pulse-admin">
		<?php pulse_admin_header( __( 'Ads & Sponsored Content', 'allbiohub-pulse' ), __( 'Each placement is off until you enable it. Sponsored items are always labelled so readers can tell them apart from editorial stories.', 'allbiohub-pulse' ) ); ?>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Placement saved.', 'allbiohub-pulse' ); ?></p></div>
		<?php endif; ?>
		<div class="pulse-split">
			<nav class="pulse-sidenav" aria-label="<?php esc_attr_e( 'Placements', 'allbiohub-pulse' ); ?>">
				<?php foreach ( $slots as $slot => $info ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'allbiohub-ads', 'slot' => $slot ), admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $slot === $current ? 'is-current' : ''; ?>" <?php echo $slot === $current ? 'aria-current="page"' : ''; ?>>
						<span class="pulse-dot <?php echo pulse_ad_is_active( $slot ) ? 'is-on' : ''; ?>"></span><?php echo esc_html( $info[0] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pulse-card pulse-adform">
				<input type="hidden" name="action" value="pulse_save_ad">
				<input type="hidden" name="slot" value="<?php echo esc_attr( $current ); ?>">
				<?php wp_nonce_field( 'pulse_save_ad' ); ?>
				<h2><?php echo esc_html( $slots[ $current ][0] ); ?></h2>
				<p class="description"><?php echo esc_html( $slots[ $current ][2] ); ?></p>

				<p class="pulse-toggle"><label><input type="checkbox" name="ad[enabled]" value="1" <?php checked( $ad['enabled'], 1 ); ?>> <strong><?php esc_html_e( 'Enabled', 'allbiohub-pulse' ); ?></strong></label></p>

				<fieldset class="pulse-field">
					<legend><?php esc_html_e( 'Format', 'allbiohub-pulse' ); ?></legend>
					<?php
					$types = array(
						'image'     => __( 'Image / sponsored card', 'allbiohub-pulse' ),
						'html'      => __( 'HTML', 'allbiohub-pulse' ),
						'script'    => __( 'Script (ad network code)', 'allbiohub-pulse' ),
						'shortcode' => __( 'Shortcode', 'allbiohub-pulse' ),
						'post'      => __( 'Post / sponsored article', 'allbiohub-pulse' ),
					);
					foreach ( $types as $k => $label ) :
						if ( 'script' === $k && ! current_user_can( 'unfiltered_html' ) ) {
							continue;
						}
						?>
						<label class="pulse-radio"><input type="radio" name="ad[type]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $ad['type'], $k ); ?> data-pulse-adtype> <?php echo esc_html( $label ); ?></label>
					<?php endforeach; ?>
				</fieldset>

				<div data-pulse-when-ad="image">
					<p class="pulse-field"><strong><?php esc_html_e( 'Image', 'allbiohub-pulse' ); ?></strong><br>
						<input type="hidden" name="ad[image]" value="<?php echo esc_attr( $ad['image'] ); ?>" data-pulse-media-input>
						<button type="button" class="button" data-pulse-media="image"><?php esc_html_e( 'Select image', 'allbiohub-pulse' ); ?></button>
						<span data-pulse-media-label><?php echo $ad['image'] ? wp_get_attachment_image( $ad['image'], 'thumbnail' ) : ''; // phpcs:ignore ?></span>
					</p>
					<div class="pulse-grid">
						<p class="pulse-field"><label><strong><?php esc_html_e( 'Headline (optional)', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="text" name="ad[headline]" value="<?php echo esc_attr( $ad['headline'] ); ?>" placeholder="<?php esc_attr_e( 'Shown as a sponsored card with text', 'allbiohub-pulse' ); ?>"></label></p>
						<p class="pulse-field"><label><strong><?php esc_html_e( 'Button text', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="text" name="ad[cta]" value="<?php echo esc_attr( $ad['cta'] ); ?>" placeholder="<?php esc_attr_e( 'Learn more', 'allbiohub-pulse' ); ?>"></label></p>
					</div>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'Short text (optional)', 'allbiohub-pulse' ); ?></strong><br><textarea class="widefat" rows="2" name="ad[body]"><?php echo esc_textarea( $ad['body'] ); ?></textarea></label></p>
				</div>
				<p class="pulse-field" data-pulse-when-ad="html script"><label><strong><?php esc_html_e( 'Code', 'allbiohub-pulse' ); ?></strong><br><textarea class="widefat code" rows="7" name="ad[html]"><?php echo esc_textarea( $ad['html'] ); ?></textarea></label>
					<?php if ( ! current_user_can( 'unfiltered_html' ) ) : ?><span class="description"><?php esc_html_e( 'Scripts are removed for your account. Ask an administrator to add ad-network code.', 'allbiohub-pulse' ); ?></span><?php endif; ?></p>
				<p class="pulse-field" data-pulse-when-ad="shortcode"><label><strong><?php esc_html_e( 'Shortcode', 'allbiohub-pulse' ); ?></strong><br><input class="widefat code" type="text" name="ad[shortcode]" value="<?php echo esc_attr( $ad['shortcode'] ); ?>"></label></p>
				<div class="pulse-field" data-pulse-when-ad="post"><strong><?php esc_html_e( 'Sponsored article', 'allbiohub-pulse' ); ?></strong><?php pulse_admin_picker( 'ad[post_id]', $ad['post_id'] ? array( $ad['post_id'] ) : array(), 1 ); ?></div>

				<div class="pulse-grid">
					<p class="pulse-field" data-pulse-when-ad="image"><label><strong><?php esc_html_e( 'Advertisement URL', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="url" name="ad[url]" value="<?php echo esc_attr( $ad['url'] ); ?>"></label></p>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'Sponsored label', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="text" name="ad[label]" value="<?php echo esc_attr( $ad['label'] ); ?>"></label></p>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'Advertiser', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="text" name="ad[advertiser]" value="<?php echo esc_attr( $ad['advertiser'] ); ?>"></label></p>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'Campaign', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="text" name="ad[campaign]" value="<?php echo esc_attr( $ad['campaign'] ); ?>"></label></p>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'Start date', 'allbiohub-pulse' ); ?></strong><br><input type="date" name="ad[start]" value="<?php echo esc_attr( $ad['start'] ); ?>"></label></p>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'End date', 'allbiohub-pulse' ); ?></strong><br><input type="date" name="ad[end]" value="<?php echo esc_attr( $ad['end'] ); ?>"></label></p>
					<p class="pulse-field"><label><strong><?php esc_html_e( 'Impression tracking pixel URL', 'allbiohub-pulse' ); ?></strong><br><input class="widefat" type="url" name="ad[tracking]" value="<?php echo esc_attr( $ad['tracking'] ); ?>"></label></p>
					<?php if ( 'inline' === $current ) : ?>
						<p class="pulse-field"><label><strong><?php esc_html_e( 'Insert after paragraphs', 'allbiohub-pulse' ); ?></strong><br><input type="text" inputmode="numeric" class="regular-text" name="ad[after_para]" value="<?php echo esc_attr( $ad['after_para'] ); ?>" placeholder="3, 8, 14"></label><br><span class="description"><?php esc_html_e( 'One or more paragraph numbers, separated by commas. Example: 3, 8, 14 shows the ad three times.', 'allbiohub-pulse' ); ?></span></p>
						<p class="pulse-field"><label><strong><?php esc_html_e( 'Then repeat every … paragraphs', 'allbiohub-pulse' ); ?></strong><br><input type="number" min="0" max="50" class="small-text" name="ad[repeat]" value="<?php echo esc_attr( $ad['repeat'] ); ?>"></label><br><span class="description"><?php esc_html_e( 'Optional. For long articles, keeps adding the ad every this many paragraphs after the last number above. 0 = off. Up to 10 ads per article, and never after the last paragraph.', 'allbiohub-pulse' ); ?></span></p>
					<?php endif; ?>
				</div>
				<p class="pulse-field">
					<label><input type="checkbox" name="ad[new_tab]" value="1" <?php checked( $ad['new_tab'], 1 ); ?>> <?php esc_html_e( 'Open in new tab', 'allbiohub-pulse' ); ?></label><br>
					<label><input type="checkbox" name="ad[track_click]" value="1" <?php checked( $ad['track_click'], 1 ); ?>> <?php esc_html_e( 'Count clicks', 'allbiohub-pulse' ); ?></label><br>
					<label><input type="checkbox" name="ad[track_imp]" value="1" <?php checked( $ad['track_imp'], 1 ); ?>> <?php esc_html_e( 'Count impressions', 'allbiohub-pulse' ); ?></label>
				</p>
				<?php $t = pulse_ad_totals( $current ); ?>
				<p class="description"><?php printf( esc_html__( 'Last 30 days: %1$s impressions, %2$s clicks.', 'allbiohub-pulse' ), esc_html( number_format_i18n( $t['imp'] ) ), esc_html( number_format_i18n( $t['click'] ) ) ); ?></p>
				<p class="submit"><button class="button button-primary"><?php esc_html_e( 'Save placement', 'allbiohub-pulse' ); ?></button></p>
			</form>
		</div>
	</div>
	<?php
}

function pulse_ad_inventory_screen() {
	$slots  = pulse_ad_slots();
	$ads    = pulse_ads();
	$groups = array(
		'home'    => __( 'Homepage', 'allbiohub-pulse' ),
		'article' => __( 'Article', 'allbiohub-pulse' ),
		'site'    => __( 'Site-wide', 'allbiohub-pulse' ),
		'footer'  => __( 'Footer', 'allbiohub-pulse' ),
		'custom'  => __( 'Custom', 'allbiohub-pulse' ),
	);
	?>
	<div class="wrap pulse-admin">
		<?php pulse_admin_header( __( 'Ad Inventory', 'allbiohub-pulse' ), __( 'Every advertising placement on AllBioHub, and whether it is live right now.', 'allbiohub-pulse' ) ); ?>
		<div class="pulse-inventory">
			<?php foreach ( $groups as $group => $group_label ) : ?>
				<section class="pulse-card">
					<h2><?php echo esc_html( $group_label ); ?></h2>
					<table class="pulse-table">
						<thead><tr><th><?php esc_html_e( 'Placement', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Status', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Advertiser / campaign', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( 'Ends', 'allbiohub-pulse' ); ?></th><th><?php esc_html_e( '30-day clicks', 'allbiohub-pulse' ); ?></th></tr></thead>
						<tbody>
						<?php
						foreach ( $slots as $slot => $info ) :
							if ( $info[1] !== $group ) {
								continue;
							}
							$ad     = $ads[ $slot ];
							$active = pulse_ad_is_active( $slot );
							$status = $active ? __( 'Active', 'allbiohub-pulse' ) : ( $ad['enabled'] ? __( 'Scheduled / incomplete', 'allbiohub-pulse' ) : __( 'Inactive', 'allbiohub-pulse' ) );
							?>
							<tr>
								<td><a href="<?php echo esc_url( add_query_arg( array( 'page' => 'allbiohub-ads', 'slot' => $slot ), admin_url( 'admin.php' ) ) ); ?>"><?php echo esc_html( $info[0] ); ?></a></td>
								<td><span class="pulse-status <?php echo $active ? 'pulse-status--on' : ''; ?>"><?php echo esc_html( $status ); ?></span></td>
								<td><?php echo esc_html( trim( $ad['advertiser'] . ( $ad['campaign'] ? ' · ' . $ad['campaign'] : '' ) ) ); ?></td>
								<td><?php echo esc_html( $ad['end'] ); ?></td>
								<td><?php echo esc_html( number_format_i18n( pulse_ad_totals( $slot )['click'] ) ); ?></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</section>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Site-wide head code (e.g. Auto ads), printed raw in <head>.
 */
add_action( 'wp_head', 'pulse_head_ad_code', 20 );
function pulse_head_ad_code() {
	if ( pulse_ad_is_active( 'head' ) && in_array( pulse_ad( 'head' )['type'], array( 'html', 'script' ), true ) ) {
		echo pulse_ad( 'head' )['html'] . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- saved by unfiltered_html users only.
	}
}

