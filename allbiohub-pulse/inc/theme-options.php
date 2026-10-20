<?php
/**
 * AllBioHub → Theme Settings: a tabbed settings screen driven by the schema.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

add_action( 'admin_menu', 'pulse_admin_menu', 9 );
function pulse_admin_menu() {
	add_menu_page( __( 'AllBioHub', 'allbiohub-pulse' ), __( 'AllBioHub', 'allbiohub-pulse' ), 'edit_others_posts', 'allbiohub', 'pulse_settings_screen_router', 'dashicons-megaphone', 3 );
	add_submenu_page( 'allbiohub', __( 'Theme Settings', 'allbiohub-pulse' ), __( 'Theme Settings', 'allbiohub-pulse' ), 'manage_options', 'allbiohub', 'pulse_settings_screen_router' );
}

function pulse_settings_screen_router() {
	$tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! current_user_can( 'manage_options' ) ) {
		// Editors land on Pinned Stories, which they can manage.
		pulse_pins_screen();
		return;
	}
	pulse_settings_screen( $tab );
}

/**
 * Tab definitions. Each field key must exist in pulse_schema().
 * Field: key => array( label, help, control? ) where control overrides the
 * schema-derived control (e.g. 'picker:5', 'order', 'select-post', 'media').
 */
function pulse_settings_tabs() {
	$section_labels = array();
	foreach ( pulse_section_map() as $k => $row ) {
		$section_labels[ $k ] = $row['label'];
	}
	return array(
		'general'     => array(
			'label'  => __( 'General', 'allbiohub-pulse' ),
			'fields' => array(
				'tagline'      => array( __( 'Tagline', 'allbiohub-pulse' ), __( 'Shown in the footer and the homepage title area.', 'allbiohub-pulse' ) ),
				'footer_about' => array( __( 'Short description', 'allbiohub-pulse' ), __( 'Shown in the footer under the logo.', 'allbiohub-pulse' ) ),
				'logo_bundled' => array( __( 'Use the AllBioHub Media logo', 'allbiohub-pulse' ), __( 'Shows the logo that comes with the theme, with a light version for dark mode. Turn off to use the logo from Appearance → Customize → Site Identity.', 'allbiohub-pulse' ) ),
				'logo_dark'    => array( __( 'Logo for dark mode (optional)', 'allbiohub-pulse' ), __( 'The main logo comes from Appearance → Customize → Site Identity. If your logo is dark, add a light version here.', 'allbiohub-pulse' ), 'media' ),
				'advertise_url' => array( __( 'Advertise link', 'allbiohub-pulse' ), __( 'Your media kit. Menu links to the Advertise With Us page open this instead. Leave empty to keep the page.', 'allbiohub-pulse' ) ),
			),
		),
		'header'      => array(
			'label'  => __( 'Header', 'allbiohub-pulse' ),
			'fields' => array(
				'header_sticky'   => array( __( 'Sticky header', 'allbiohub-pulse' ), '' ),
				'header_search'   => array( __( 'Search button', 'allbiohub-pulse' ), '' ),
				'header_darkmode' => array( __( 'Dark mode toggle', 'allbiohub-pulse' ), '' ),
				'ticker_enabled'  => array( __( 'Trending Now ticker', 'allbiohub-pulse' ), __( 'The slim bar under the header. Its stories are set on the Trending tab.', 'allbiohub-pulse' ) ),
			),
		),
		'homepage'    => array(
			'label'  => __( 'Homepage', 'allbiohub-pulse' ),
			'fields' => array(
				'home_order'            => array( __( 'Section order', 'allbiohub-pulse' ), __( 'Header and Trending Now are always first; Footer is always last.', 'allbiohub-pulse' ), 'order' ),
				'home_hero'             => array( __( 'Hero + Trending Stories', 'allbiohub-pulse' ), '' ),
				'hero_post'             => array( __( 'Hero story', 'allbiohub-pulse' ), __( 'Leave empty to use the newest story with an image.', 'allbiohub-pulse' ), 'picker:1' ),
				'home_pinned'           => array( __( 'Pinned Stories', 'allbiohub-pulse' ), __( 'Choose the stories under AllBioHub → Pinned Stories.', 'allbiohub-pulse' ) ),
				'home_sponsored'        => array( __( 'Sponsored / Featured Content', 'allbiohub-pulse' ), __( 'Filled from the Sponsored Content and Sponsored Story placements.', 'allbiohub-pulse' ) ),
				'home_biography'        => array( __( 'Featured Biography + People You Should Know', 'allbiohub-pulse' ), '' ),
				'home_video'            => array( __( 'AllBioHub Video', 'allbiohub-pulse' ), '' ),
				'home_categories'       => array( __( 'Category sections', 'allbiohub-pulse' ), '' ),
				'home_sponsored_banner' => array( __( 'Sponsored banner', 'allbiohub-pulse' ), '' ),
				'home_newsletter'       => array( __( 'Newsletter', 'allbiohub-pulse' ), '' ),
			),
		),
		'trending'    => array(
			'label'  => __( 'Trending', 'allbiohub-pulse' ),
			'fields' => array(
				'ticker_source'   => array( __( 'Trending Now ticker source', 'allbiohub-pulse' ), __( 'Automatic uses most-viewed stories. Hybrid shows your picks first, then automatic.', 'allbiohub-pulse' ) ),
				'ticker_posts'    => array( __( 'Ticker stories (manual / hybrid)', 'allbiohub-pulse' ), '', 'picker:12' ),
				'ticker_count'    => array( __( 'Ticker items', 'allbiohub-pulse' ), '' ),
				'trending_source' => array( __( 'Trending Stories (beside the hero) source', 'allbiohub-pulse' ), '' ),
				'trending_posts'  => array( __( 'Trending Stories (manual)', 'allbiohub-pulse' ), '', 'picker:5' ),
				'trending_days'   => array( __( 'Most viewed window (days)', 'allbiohub-pulse' ), __( 'Stories published within this many days compete for “most viewed”.', 'allbiohub-pulse' ) ),
			),
		),
		'biography'   => array(
			'label'  => __( 'Biography', 'allbiohub-pulse' ),
			'fields' => array(
				'bio_category'      => array( __( 'Biography category slug', 'allbiohub-pulse' ), __( 'Posts in this category use the biography layout and feed People You Should Know.', 'allbiohub-pulse' ) ),
				'bio_featured_post' => array( __( 'Featured Biography', 'allbiohub-pulse' ), __( 'Leave empty to feature the newest biography with an image.', 'allbiohub-pulse' ), 'picker:1' ),
			),
		),
		'people'      => array(
			'label'  => __( 'People You Should Know', 'allbiohub-pulse' ),
			'fields' => array(
				'people_title' => array( __( 'Section title', 'allbiohub-pulse' ), '' ),
				'people_count' => array( __( 'Number of people', 'allbiohub-pulse' ), __( 'Between 3 and 12. Desktop shows up to 6 at once; the rest scroll.', 'allbiohub-pulse' ) ),
				'people_posts' => array( __( 'Hand-picked people (optional)', 'allbiohub-pulse' ), __( 'Choose biography posts. Empty slots fill automatically from the newest biographies.', 'allbiohub-pulse' ), 'picker:12' ),
			),
		),
		'video'       => array(
			'label'  => __( 'Video', 'allbiohub-pulse' ),
			'fields' => array(
				'video_title'      => array( __( 'Section title', 'allbiohub-pulse' ), '' ),
				'video_feature'    => array( __( 'Lead video', 'allbiohub-pulse' ), __( 'Week / Month use the video you marked on the video’s edit screen, with automatic fallbacks. Featured uses the highest-priority active video.', 'allbiohub-pulse' ) ),
				'video_more_count' => array( __( 'More videos to show', 'allbiohub-pulse' ), __( 'Between 3 and 6.', 'allbiohub-pulse' ) ),
				'video_style'      => array( __( 'Display style', 'allbiohub-pulse' ), __( 'Split: player left, details right. Stacked: details under the player.', 'allbiohub-pulse' ) ),
			),
		),
		'categories'  => array(
			'label'  => __( 'Categories', 'allbiohub-pulse' ),
			'fields' => array(
				'sections'      => array( __( 'Homepage category sections', 'allbiohub-pulse' ), __( 'Tick and order the sections. Which categories feed each section is set under Migration & Health.', 'allbiohub-pulse' ), 'order:' . wp_json_encode( $section_labels ) ),
				'section_posts' => array( __( 'Stories per section', 'allbiohub-pulse' ), '' ),
			),
		),
		'colors'      => array(
			'label'  => __( 'Colours & Dark mode', 'allbiohub-pulse' ),
			'fields' => array(
				'accent'               => array( __( 'AllBioHub purple (light)', 'allbiohub-pulse' ), '' ),
				'accent_dark'          => array( __( 'AllBioHub purple (dark mode)', 'allbiohub-pulse' ), __( 'A lighter tint keeps contrast on dark backgrounds.', 'allbiohub-pulse' ) ),
				'color_scheme_default' => array( __( 'Default colour scheme', 'allbiohub-pulse' ), __( 'Visitors can still switch with the header toggle.', 'allbiohub-pulse' ) ),
			),
		),
		'articles'    => array(
			'label'  => __( 'Articles', 'allbiohub-pulse' ),
			'fields' => array(
				'article_toc'     => array( __( 'Table of contents', 'allbiohub-pulse' ), __( 'Shown when an article has 3 or more headings.', 'allbiohub-pulse' ) ),
				'article_share'   => array( __( 'Share buttons', 'allbiohub-pulse' ), '' ),
				'article_related' => array( __( 'Related stories', 'allbiohub-pulse' ), __( 'Between 3 and 6.', 'allbiohub-pulse' ) ),
				'reading_wpm'     => array( __( 'Reading speed (words per minute)', 'allbiohub-pulse' ), '' ),
			),
		),
		'social'      => array(
			'label'  => __( 'Social Profiles', 'allbiohub-pulse' ),
			'fields' => array(
				'social_facebook'  => array( 'Facebook', '' ),
				'social_x'         => array( 'X', '' ),
				'social_instagram' => array( 'Instagram', '' ),
				'social_youtube'   => array( 'YouTube', '' ),
				'social_tiktok'    => array( 'TikTok', '' ),
				'social_linkedin'  => array( 'LinkedIn', '' ),
				'social_pinterest' => array( 'Pinterest', '' ),
				'social_telegram'  => array( 'Telegram', '' ),
				'social_medium'    => array( 'Medium', '' ),
				'social_flipboard' => array( 'Flipboard', '' ),
			),
		),
		'newsletter'  => array(
			'label'  => __( 'Newsletter', 'allbiohub-pulse' ),
			'fields' => array(
				'newsletter_title'     => array( __( 'Title', 'allbiohub-pulse' ), '' ),
				'newsletter_text'      => array( __( 'Text', 'allbiohub-pulse' ), '' ),
				'newsletter_brevo_list' => array( __( 'Brevo list ID', 'allbiohub-pulse' ), __( 'In Brevo → Contacts → Lists, the number next to your newsletter list. With this set, the theme\'s own signup box adds readers straight to that list.', 'allbiohub-pulse' ) ),
				'newsletter_brevo_key'  => array( __( 'Brevo API key (optional)', 'allbiohub-pulse' ), __( 'Leave empty to use the key already saved in the Brevo plugin. Otherwise paste a key from Brevo → SMTP & API → API keys.', 'allbiohub-pulse' ), 'secret' ),
				'newsletter_shortcode'  => array( __( 'Or: Brevo form shortcode', 'allbiohub-pulse' ), __( 'Shows a form made in Brevo → Forms instead, e.g. [sibwp_form id=1]. If no list ID or shortcode is set, the first Brevo plugin form is used.', 'allbiohub-pulse' ) ),
				'newsletter_action'     => array( __( 'Or: form action URL', 'allbiohub-pulse' ), __( 'For any other provider that accepts a plain email form post.', 'allbiohub-pulse' ) ),
			),
		),
		'seo'         => array(
			'label'  => __( 'SEO', 'allbiohub-pulse' ),
			'fields' => array(
				'schema_fallback' => array( __( 'Fallback schema and meta', 'allbiohub-pulse' ), __( 'Only used when no SEO plugin is active. With Rank Math active, Pulse outputs no SEO tags of its own.', 'allbiohub-pulse' ) ),
			),
		),
		'performance' => array(
			'label'  => __( 'Performance', 'allbiohub-pulse' ),
			'fields' => array(
				'perf_disable_emoji' => array( __( 'Remove WordPress emoji script', 'allbiohub-pulse' ), '' ),
				'perf_preload_fonts' => array( __( 'Preload headline and body fonts', 'allbiohub-pulse' ), '' ),
				'views_enabled'      => array( __( 'Count article views', 'allbiohub-pulse' ), __( 'Powers “most viewed” trending. Editors and bots are not counted.', 'allbiohub-pulse' ) ),
			),
		),
		'backup'      => array(
			'label'  => __( 'Backup / Restore', 'allbiohub-pulse' ),
			'fields' => array(),
		),
	);
}

/**
 * Brand header for every AllBioHub admin screen.
 */
function pulse_admin_header( $title, $intro = '' ) {
	?>
	<header class="pulse-admin__head">
		<div class="pulse-admin__brand"><span class="pulse-admin__mark" aria-hidden="true"></span>AllBioHub <span>Pulse</span></div>
		<h1><?php echo esc_html( $title ); ?></h1>
		<?php if ( $intro ) : ?><p><?php echo esc_html( $intro ); ?></p><?php endif; ?>
	</header>
	<?php
}

/**
 * Post picker (search, add, reorder, remove). Value is a CSV of IDs.
 */
function pulse_admin_picker( $name, array $ids, $max = 5, $post_type = 'post', $numbered = false ) {
	?>
	<div class="pulse-picker" data-max="<?php echo esc_attr( $max ); ?>" data-type="<?php echo esc_attr( $post_type ); ?>" <?php echo $numbered ? 'data-numbered' : ''; ?>>
		<input type="hidden" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>" data-picker-value>
		<ol class="pulse-picker__list" data-picker-list>
			<?php foreach ( $ids as $id ) : ?>
				<?php $p = get_post( $id ); if ( ! $p ) { continue; } ?>
				<li data-id="<?php echo esc_attr( $id ); ?>" draggable="true">
					<?php echo pulse_image( $p, array( 'size' => 'thumbnail', 'class' => 'pulse-picker__thumb' ) ); // phpcs:ignore ?>
					<span class="pulse-picker__title"><?php echo esc_html( get_the_title( $p ) ); ?>
						<small><?php echo esc_html( get_the_date( '', $p ) ); ?> · <a href="<?php echo esc_url( get_permalink( $p ) ); ?>" target="_blank"><?php esc_html_e( 'Preview', 'allbiohub-pulse' ); ?></a></small>
					</span>
					<span class="pulse-picker__actions">
						<button type="button" class="button-link" data-picker-up aria-label="<?php esc_attr_e( 'Move up', 'allbiohub-pulse' ); ?>">↑</button>
						<button type="button" class="button-link" data-picker-down aria-label="<?php esc_attr_e( 'Move down', 'allbiohub-pulse' ); ?>">↓</button>
						<button type="button" class="button-link pulse-danger" data-picker-remove><?php esc_html_e( 'Remove', 'allbiohub-pulse' ); ?></button>
					</span>
				</li>
			<?php endforeach; ?>
		</ol>
		<div class="pulse-picker__search">
			<input type="search" placeholder="<?php esc_attr_e( 'Search stories to add…', 'allbiohub-pulse' ); ?>" data-picker-search aria-label="<?php esc_attr_e( 'Search stories to add', 'allbiohub-pulse' ); ?>">
			<ul class="pulse-picker__results" data-picker-results></ul>
		</div>
	</div>
	<?php
}

/**
 * Render one field by schema type (or override control).
 */
function pulse_render_field( $key, $def ) {
	$schema  = pulse_schema();
	$type    = $schema[ $key ][0];
	$value   = pulse_opt( $key );
	$label   = $def[0];
	$help    = $def[1];
	$control = isset( $def[2] ) ? $def[2] : '';
	$name    = 'pulse[' . $key . ']';
	$id      = 'pulse-' . $key;
	echo '<div class="pulse-row"><div class="pulse-row__label"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';
	if ( $help ) {
		echo '<p class="description">' . esc_html( $help ) . '</p>';
	}
	echo '</div><div class="pulse-row__control">';

	if ( 0 === strpos( $control, 'picker:' ) ) {
		pulse_admin_picker( $name, pulse_opt_ids( $key ) ? pulse_opt_ids( $key ) : ( 'int' === $type && $value ? array( (int) $value ) : array() ), (int) substr( $control, 7 ) );
	} elseif ( 0 === strpos( $control, 'order' ) ) {
		$items = 'order' === $control ? array(
			'hero'             => __( 'Hero + Trending Stories', 'allbiohub-pulse' ),
			'pinned'           => __( 'Pinned Stories', 'allbiohub-pulse' ),
			'sponsored'        => __( 'Sponsored Content', 'allbiohub-pulse' ),
			'biography'        => __( 'Featured Biography + People', 'allbiohub-pulse' ),
			'video'            => __( 'AllBioHub Video', 'allbiohub-pulse' ),
			'categories'       => __( 'Category sections', 'allbiohub-pulse' ),
			'sponsored_banner' => __( 'Sponsored banner', 'allbiohub-pulse' ),
			'newsletter'       => __( 'Newsletter', 'allbiohub-pulse' ),
		) : (array) json_decode( substr( $control, 6 ), true );
		$current = pulse_opt_slugs( $key );
		$ordered = array_merge( array_intersect( $current, array_keys( $items ) ), array_diff( array_keys( $items ), $current ) );
		echo '<div class="pulse-order" data-pulse-order><input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( implode( ',', $current ) ) . '" data-order-value><ol>';
		foreach ( $ordered as $slug ) {
			printf(
				'<li data-slug="%1$s"><label><input type="checkbox" %2$s %4$s> %3$s</label><span><button type="button" class="button-link" data-order-up aria-label="%5$s">↑</button><button type="button" class="button-link" data-order-down aria-label="%6$s">↓</button></span></li>',
				esc_attr( $slug ),
				checked( in_array( $slug, $current, true ), true, false ),
				esc_html( $items[ $slug ] ),
				'order' === $control ? 'data-order-check' : 'data-order-check',
				esc_attr__( 'Move up', 'allbiohub-pulse' ),
				esc_attr__( 'Move down', 'allbiohub-pulse' )
			);
		}
		echo '</ol></div>';
	} elseif ( 'media' === $control ) {
		printf( '<input type="hidden" name="%s" value="%s" data-pulse-media-input><button type="button" class="button" data-pulse-media="image">%s</button> <span data-pulse-media-label>%s</span>', esc_attr( $name ), esc_attr( $value ), esc_html__( 'Select image', 'allbiohub-pulse' ), $value ? wp_get_attachment_image( (int) $value, 'thumbnail' ) : '' ); // phpcs:ignore
	} elseif ( 'secret' === $control ) {
		printf( '<input type="password" class="regular-text" id="%s" name="%s" value="%s" autocomplete="off" spellcheck="false">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	} elseif ( 'bool' === $type ) {
		printf( '<label class="pulse-switch"><input type="checkbox" id="%s" name="%s" value="1" %s><span></span></label>', esc_attr( $id ), esc_attr( $name ), checked( (int) $value, 1, false ) );
	} elseif ( 0 === strpos( $type, 'select:' ) ) {
		$labels = array(
			'automatic' => __( 'Automatic', 'allbiohub-pulse' ), 'manual' => __( 'Manual', 'allbiohub-pulse' ), 'hybrid' => __( 'Hybrid', 'allbiohub-pulse' ),
			'most_viewed' => __( 'Most viewed', 'allbiohub-pulse' ), 'recent' => __( 'Most recent', 'allbiohub-pulse' ),
			'system' => __( 'Follow device', 'allbiohub-pulse' ), 'light' => __( 'Light', 'allbiohub-pulse' ), 'dark' => __( 'Dark', 'allbiohub-pulse' ),
			'week' => __( 'Video of the Week', 'allbiohub-pulse' ), 'month' => __( 'Video of the Month', 'allbiohub-pulse' ), 'featured' => __( 'Featured (priority)', 'allbiohub-pulse' ),
			'split' => __( 'Split', 'allbiohub-pulse' ), 'stacked' => __( 'Stacked', 'allbiohub-pulse' ),
		);
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '">';
		foreach ( explode( '|', substr( $type, 7 ) ) as $choice ) {
			printf( '<option value="%s" %s>%s</option>', esc_attr( $choice ), selected( $value, $choice, false ), esc_html( isset( $labels[ $choice ] ) ? $labels[ $choice ] : $choice ) );
		}
		echo '</select>';
	} elseif ( 'textarea' === $type ) {
		printf( '<textarea class="large-text" rows="3" id="%s" name="%s">%s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_textarea( $value ) );
	} elseif ( 'int' === $type ) {
		printf( '<input type="number" min="0" id="%s" name="%s" value="%s" class="small-text">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	} elseif ( 'color' === $type ) {
		printf( '<input type="color" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	} elseif ( 'url' === $type ) {
		printf( '<input type="url" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	} else {
		printf( '<input type="text" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ) );
	}
	echo '</div></div>';
}

add_action( 'admin_post_pulse_save_settings', 'pulse_settings_save' );
function pulse_settings_save() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You are not allowed to do that.', 'allbiohub-pulse' ), 403 );
	}
	check_admin_referer( 'pulse_save_settings' );
	$tab  = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';
	$tabs = pulse_settings_tabs();
	if ( isset( $tabs[ $tab ] ) ) {
		$input = isset( $_POST['pulse'] ) ? (array) $_POST['pulse'] : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- sanitised per schema.
		// Pickers bound to single-int settings submit a CSV; keep the first ID.
		foreach ( array_keys( $tabs[ $tab ]['fields'] ) as $key ) {
			if ( 'int' === pulse_schema()[ $key ][0] && isset( $input[ $key ] ) && false !== strpos( (string) $input[ $key ], ',' ) ) {
				$input[ $key ] = strtok( (string) $input[ $key ], ',' );
			}
		}
		pulse_save_settings( $input, array_keys( $tabs[ $tab ]['fields'] ) );
	}
	$back = isset( $_POST['return'] ) ? sanitize_key( wp_unslash( $_POST['return'] ) ) : 'allbiohub';
	wp_safe_redirect( add_query_arg( array( 'page' => $back, 'tab' => $tab, 'updated' => 1 ), admin_url( 'admin.php' ) ) );
	exit;
}

function pulse_settings_screen( $tab = 'general' ) {
	$tabs = pulse_settings_tabs();
	if ( ! isset( $tabs[ $tab ] ) ) {
		$tab = 'general';
	}
	$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : 'allbiohub'; // phpcs:ignore WordPress.Security.NonceVerification
	$links = array(
		'pinned'    => array( __( 'Pinned Stories', 'allbiohub-pulse' ), 'allbiohub-pinned' ),
		'ads'       => array( __( 'Advertising', 'allbiohub-pulse' ), 'allbiohub-ads' ),
		'inventory' => array( __( 'Ad Inventory', 'allbiohub-pulse' ), 'allbiohub-ad-inventory' ),
		'migration' => array( __( 'Migration & Health', 'allbiohub-pulse' ), 'allbiohub-migration' ),
	);
	?>
	<div class="wrap pulse-admin">
		<?php pulse_admin_header( __( 'Theme Settings', 'allbiohub-pulse' ), __( 'Everything on the AllBioHub front end is controlled here. No code editing needed.', 'allbiohub-pulse' ) ); ?>
		<?php if ( isset( $_GET['updated'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings saved.', 'allbiohub-pulse' ); ?></p></div>
		<?php endif; ?>
		<?php if ( isset( $_GET['restored'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Settings restored from backup.', 'allbiohub-pulse' ); ?></p></div>
		<?php endif; ?>
		<div class="pulse-split">
			<nav class="pulse-sidenav" aria-label="<?php esc_attr_e( 'Settings sections', 'allbiohub-pulse' ); ?>">
				<?php foreach ( $tabs as $slug => $t ) : ?>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'allbiohub', 'tab' => $slug ), admin_url( 'admin.php' ) ) ); ?>" class="<?php echo $slug === $tab ? 'is-current' : ''; ?>" <?php echo $slug === $tab ? 'aria-current="page"' : ''; ?>><?php echo esc_html( $t['label'] ); ?></a>
				<?php endforeach; ?>
				<hr>
				<?php foreach ( $links as $l ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $l[1] ) ); ?>"><?php echo esc_html( $l[0] ); ?> →</a>
				<?php endforeach; ?>
			</nav>
			<div class="pulse-card">
				<h2><?php echo esc_html( $tabs[ $tab ]['label'] ); ?></h2>
				<?php if ( 'backup' === $tab ) : ?>
					<?php pulse_backup_panel(); ?>
				<?php else : ?>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<input type="hidden" name="action" value="pulse_save_settings">
						<input type="hidden" name="tab" value="<?php echo esc_attr( $tab ); ?>">
						<input type="hidden" name="return" value="<?php echo esc_attr( $page ); ?>">
						<?php wp_nonce_field( 'pulse_save_settings' ); ?>
						<?php
						foreach ( $tabs[ $tab ]['fields'] as $key => $def ) {
							pulse_render_field( $key, $def );
						}
						?>
						<?php if ( 'video' === $tab ) : ?>
							<p class="description"><?php printf( wp_kses_post( __( 'Add and edit videos under <a href="%s">AllBioHub → Videos</a>. Mark the Video of the Week or Month on a video’s edit screen.', 'allbiohub-pulse' ) ), esc_url( admin_url( 'edit.php?post_type=abh_video' ) ) ); ?></p>
						<?php endif; ?>
						<p class="submit"><button class="button button-primary"><?php esc_html_e( 'Save changes', 'allbiohub-pulse' ); ?></button></p>
					</form>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
}

/* ---------- Backup / Restore ---------- */

function pulse_backup_keys() {
	return array( PULSE_OPTION, PULSE_ADS, PULSE_SECTION_MAP );
}

function pulse_backup_panel() {
	?>
	<p><?php esc_html_e( 'Download all AllBioHub Pulse settings (theme settings, ad placements and the section map) as a file, or restore them from one. Posts, pages and media are not included and are never changed.', 'allbiohub-pulse' ); ?></p>
	<p><a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=pulse_export' ), 'pulse_export' ) ); ?>"><?php esc_html_e( 'Download settings backup', 'allbiohub-pulse' ); ?></a></p>
	<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="pulse_import">
		<?php wp_nonce_field( 'pulse_import' ); ?>
		<p><label for="pulse-import"><strong><?php esc_html_e( 'Restore from backup file', 'allbiohub-pulse' ); ?></strong></label><br><input type="file" id="pulse-import" name="pulse_import" accept="application/json,.json"></p>
		<p><button class="button"><?php esc_html_e( 'Restore', 'allbiohub-pulse' ); ?></button></p>
	</form>
	<?php
}

add_action( 'admin_post_pulse_export', 'pulse_export' );
function pulse_export() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', 403 );
	}
	check_admin_referer( 'pulse_export' );
	$out = array( 'pulse_version' => PULSE_VERSION, 'exported' => gmdate( 'c' ) );
	foreach ( pulse_backup_keys() as $key ) {
		$out[ $key ] = get_option( $key, array() );
	}
	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="allbiohub-pulse-settings-' . gmdate( 'Ymd-His' ) . '.json"' );
	echo wp_json_encode( $out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	exit;
}

add_action( 'admin_post_pulse_import', 'pulse_import' );
function pulse_import() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( '', 403 );
	}
	check_admin_referer( 'pulse_import' );
	if ( empty( $_FILES['pulse_import']['tmp_name'] ) ) {
		wp_die( esc_html__( 'No file uploaded.', 'allbiohub-pulse' ) );
	}
	$data = json_decode( (string) file_get_contents( $_FILES['pulse_import']['tmp_name'] ), true ); // phpcs:ignore
	if ( ! is_array( $data ) || empty( $data['pulse_version'] ) ) {
		wp_die( esc_html__( 'That file is not an AllBioHub Pulse settings backup.', 'allbiohub-pulse' ) );
	}
	// Settings go back through the schema sanitiser.
	if ( isset( $data[ PULSE_OPTION ] ) && is_array( $data[ PULSE_OPTION ] ) ) {
		$keys = array_intersect( array_keys( $data[ PULSE_OPTION ] ), array_keys( pulse_schema() ) );
		pulse_save_settings( wp_slash( $data[ PULSE_OPTION ] ), $keys );
		foreach ( array( 'video_week_id', 'video_month_id' ) as $raw ) {
			if ( isset( $data[ PULSE_OPTION ][ $raw ] ) ) {
				pulse_set_opt_raw( $raw, absint( $data[ PULSE_OPTION ][ $raw ] ) );
			}
		}
	}
	if ( isset( $data[ PULSE_SECTION_MAP ] ) && is_array( $data[ PULSE_SECTION_MAP ] ) ) {
		update_option( PULSE_SECTION_MAP, pulse_sanitize_section_map( $data[ PULSE_SECTION_MAP ] ) );
	}
	if ( isset( $data[ PULSE_ADS ] ) && is_array( $data[ PULSE_ADS ] ) ) {
		$ads = array();
		foreach ( $data[ PULSE_ADS ] as $slot => $ad ) {
			if ( array_key_exists( $slot, pulse_ad_slots() ) && is_array( $ad ) ) {
				$ad          = array_intersect_key( wp_parse_args( $ad, pulse_ad_defaults() ), pulse_ad_defaults() );
				$ad['html']  = current_user_can( 'unfiltered_html' ) ? (string) $ad['html'] : wp_kses_post( (string) $ad['html'] );
				$ad['url']   = esc_url_raw( $ad['url'] );
				$ads[ $slot ] = $ad;
			}
		}
		update_option( PULSE_ADS, $ads );
	}
	wp_safe_redirect( admin_url( 'admin.php?page=allbiohub&tab=backup&restored=1' ) );
	exit;
}
