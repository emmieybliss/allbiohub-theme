<?php
/**
 * AllBioHub Video: a first-class video content type at /video/{slug}/.
 *
 * - Sources: YouTube, Vimeo, or a self-hosted file from the Media Library.
 * - Players load as a lightweight facade (poster + play button); the provider
 *   iframe is only created after the visitor clicks.
 * - Video of the Week / Video of the Month are single admin choices with
 *   automatic fallbacks, so nothing is hard-coded.
 *
 * Existing posts that contain videos are not changed. Until AllBioHub Videos
 * exist, the homepage section falls back to the newest post containing a video.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

const PULSE_VIDEO_META = '_abh_video';

/* ---------- Content type ---------- */

add_action( 'init', 'pulse_register_video_type' );
function pulse_register_video_type() {
	if ( post_type_exists( 'abh_video' ) ) {
		return;
	}
	register_post_type(
		'abh_video',
		array(
			'labels'        => array(
				'name'               => __( 'Videos', 'allbiohub-pulse' ),
				'singular_name'      => __( 'Video', 'allbiohub-pulse' ),
				'add_new'            => __( 'Add video', 'allbiohub-pulse' ),
				'add_new_item'       => __( 'Add video', 'allbiohub-pulse' ),
				'edit_item'          => __( 'Edit video', 'allbiohub-pulse' ),
				'new_item'           => __( 'New video', 'allbiohub-pulse' ),
				'view_item'          => __( 'View video', 'allbiohub-pulse' ),
				'search_items'       => __( 'Search videos', 'allbiohub-pulse' ),
				'not_found'          => __( 'No videos yet', 'allbiohub-pulse' ),
				'all_items'          => __( 'Videos', 'allbiohub-pulse' ),
				'menu_name'          => __( 'Videos', 'allbiohub-pulse' ),
			),
			'public'        => true,
			'show_in_rest'  => true,
			'show_in_menu'  => 'allbiohub',
			'has_archive'   => 'video',
			'rewrite'       => array( 'slug' => 'video', 'with_front' => false ),
			'supports'      => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'comments', 'revisions' ),
			'taxonomies'    => array( 'category', 'post_tag' ),
			'menu_icon'     => 'dashicons-video-alt3',
		)
	);
}

/**
 * Flush rewrites once after the type is first registered (theme switch).
 */
add_action( 'after_switch_theme', 'pulse_video_flush' );
function pulse_video_flush() {
	pulse_register_video_type();
	flush_rewrite_rules( false );
}

/* ---------- Meta ---------- */

function pulse_video_feature_types() {
	return array(
		'week'         => __( 'Video of the Week', 'allbiohub-pulse' ),
		'month'        => __( 'Video of the Month', 'allbiohub-pulse' ),
		'interview'    => __( 'Featured Interview', 'allbiohub-pulse' ),
		'celebrity'    => __( 'Celebrity Interview', 'allbiohub-pulse' ),
		'founder'      => __( 'Startup / Founder Interview', 'allbiohub-pulse' ),
		'review'       => __( 'Product Review', 'allbiohub-pulse' ),
		'tech-review'  => __( 'Technology Review', 'allbiohub-pulse' ),
		'documentary'  => __( 'Documentary', 'allbiohub-pulse' ),
		'bts'          => __( 'Behind the Scenes', 'allbiohub-pulse' ),
		'explainer'    => __( 'Explainer', 'allbiohub-pulse' ),
		'event'        => __( 'Event Coverage', 'allbiohub-pulse' ),
		'custom'       => __( 'Custom', 'allbiohub-pulse' ),
	);
}

function pulse_video_defaults() {
	return array(
		'source'      => 'youtube',
		'url'         => '',
		'file'        => 0,
		'duration'    => '',
		'presenter'   => '',
		'interviewee' => '',
		'feature'     => 'custom',
		'label'       => '',
		'home'        => 1,
		'start'       => '',
		'end'         => '',
		'priority'    => 0,
		'source_post' => 0,
	);
}

function pulse_video_meta( $post_id ) {
	$v = get_post_meta( $post_id, PULSE_VIDEO_META, true );
	return wp_parse_args( is_array( $v ) ? $v : array(), pulse_video_defaults() );
}

/**
 * Parse a YouTube or Vimeo URL into provider + ID.
 *
 * @return array{provider:string,id:string}|null
 */
function pulse_parse_video_url( $url ) {
	$url = trim( (string) $url );
	if ( preg_match( '~(?:youtube(?:-nocookie)?\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $url, $m ) ) {
		return array( 'provider' => 'youtube', 'id' => $m[1] );
	}
	if ( preg_match( '~vimeo\.com/(?:video/|channels/[^/]+/|groups/[^/]+/videos/|showcase/\d+/video/)?(\d{6,12})~', $url, $m ) ) {
		return array( 'provider' => 'vimeo', 'id' => $m[1] );
	}
	return null;
}

/**
 * Normalise "18:42", "1:02:03" or "PT18M42S" to seconds.
 */
function pulse_duration_seconds( $d ) {
	$d = trim( (string) $d );
	if ( '' === $d ) {
		return 0;
	}
	if ( preg_match( '/^P(?:T)?(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?$/i', $d, $m ) ) {
		return (int) ( isset( $m[1] ) ? $m[1] : 0 ) * 3600 + (int) ( isset( $m[2] ) ? $m[2] : 0 ) * 60 + (int) ( isset( $m[3] ) ? $m[3] : 0 );
	}
	$parts = array_map( 'intval', explode( ':', $d ) );
	$sec   = 0;
	foreach ( $parts as $p ) {
		$sec = $sec * 60 + $p;
	}
	return $sec;
}

function pulse_duration_label( $seconds ) {
	$seconds = (int) $seconds;
	if ( $seconds <= 0 ) {
		return '';
	}
	$h = intdiv( $seconds, 3600 );
	$m = intdiv( $seconds % 3600, 60 );
	$s = $seconds % 60;
	return $h ? sprintf( '%d:%02d:%02d', $h, $m, $s ) : sprintf( '%d:%02d', $m, $s );
}

/**
 * Admin meta box.
 */
add_action( 'add_meta_boxes_abh_video', 'pulse_video_box' );
function pulse_video_box() {
	add_meta_box( 'pulse-video', __( 'Video settings', 'allbiohub-pulse' ), 'pulse_video_box_html', 'abh_video', 'normal', 'high' );
}

function pulse_video_box_html( $post ) {
	$v = pulse_video_meta( $post->ID );
	wp_nonce_field( 'pulse_video', 'pulse_video_nonce' );
	$week  = (int) pulse_opt_raw( 'video_week_id' ) === $post->ID;
	$month = (int) pulse_opt_raw( 'video_month_id' ) === $post->ID;
	?>
	<div class="pulse-fields">
		<fieldset class="pulse-field">
			<legend><?php esc_html_e( 'Video source', 'allbiohub-pulse' ); ?></legend>
			<?php foreach ( array( 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'self' => __( 'Self hosted', 'allbiohub-pulse' ) ) as $k => $label ) : ?>
				<label class="pulse-radio"><input type="radio" name="pulse_video[source]" value="<?php echo esc_attr( $k ); ?>" <?php checked( $v['source'], $k ); ?> data-pulse-source> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
		</fieldset>
		<p class="pulse-field" data-pulse-when="youtube vimeo">
			<label for="pulse-video-url"><strong><?php esc_html_e( 'Video URL', 'allbiohub-pulse' ); ?></strong></label>
			<input class="widefat" type="url" id="pulse-video-url" name="pulse_video[url]" value="<?php echo esc_attr( $v['url'] ); ?>" placeholder="https://www.youtube.com/watch?v=…">
			<span class="description"><?php esc_html_e( 'Accepts youtube.com/watch?v=, youtu.be/, youtube.com/shorts/ and vimeo.com/ links.', 'allbiohub-pulse' ); ?></span>
		</p>
		<p class="pulse-field" data-pulse-when="self">
			<strong><?php esc_html_e( 'Video file (MP4 or WebM)', 'allbiohub-pulse' ); ?></strong><br>
			<input type="hidden" name="pulse_video[file]" value="<?php echo esc_attr( $v['file'] ); ?>" data-pulse-media-input>
			<button type="button" class="button" data-pulse-media="video"><?php esc_html_e( 'Select from Media Library', 'allbiohub-pulse' ); ?></button>
			<span data-pulse-media-label><?php echo $v['file'] ? esc_html( basename( (string) get_attached_file( $v['file'] ) ) ) : ''; ?></span>
		</p>
		<div class="pulse-grid">
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Duration', 'allbiohub-pulse' ); ?></strong><br><input type="text" name="pulse_video[duration]" value="<?php echo esc_attr( $v['duration'] ); ?>" placeholder="18:42"></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Presenter', 'allbiohub-pulse' ); ?></strong><br><input type="text" name="pulse_video[presenter]" value="<?php echo esc_attr( $v['presenter'] ); ?>"></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Interviewee / guest', 'allbiohub-pulse' ); ?></strong><br><input type="text" name="pulse_video[interviewee]" value="<?php echo esc_attr( $v['interviewee'] ); ?>"></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Feature type', 'allbiohub-pulse' ); ?></strong><br>
				<select name="pulse_video[feature]">
					<?php foreach ( pulse_video_feature_types() as $k => $label ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $v['feature'], $k ); ?>><?php echo esc_html( $label ); ?></option>
					<?php endforeach; ?>
				</select></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Custom badge label', 'allbiohub-pulse' ); ?></strong><br><input type="text" name="pulse_video[label]" value="<?php echo esc_attr( $v['label'] ); ?>" placeholder="<?php esc_attr_e( 'Used when type is Custom', 'allbiohub-pulse' ); ?>"></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Priority', 'allbiohub-pulse' ); ?></strong><br><input type="number" min="0" max="100" name="pulse_video[priority]" value="<?php echo esc_attr( $v['priority'] ); ?>"></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Display from', 'allbiohub-pulse' ); ?></strong><br><input type="date" name="pulse_video[start]" value="<?php echo esc_attr( $v['start'] ); ?>"></label></p>
			<p class="pulse-field"><label><strong><?php esc_html_e( 'Display until', 'allbiohub-pulse' ); ?></strong><br><input type="date" name="pulse_video[end]" value="<?php echo esc_attr( $v['end'] ); ?>"></label></p>
		</div>
		<p class="pulse-field">
			<label><input type="checkbox" name="pulse_video[home]" value="1" <?php checked( $v['home'], 1 ); ?>> <?php esc_html_e( 'Show on homepage', 'allbiohub-pulse' ); ?></label><br>
			<label><input type="checkbox" name="pulse_video_week" value="1" <?php checked( $week ); ?>> <?php esc_html_e( 'Make this the Video of the Week (replaces the current one)', 'allbiohub-pulse' ); ?></label><br>
			<label><input type="checkbox" name="pulse_video_month" value="1" <?php checked( $month ); ?>> <?php esc_html_e( 'Make this the Video of the Month (replaces the current one)', 'allbiohub-pulse' ); ?></label>
		</p>
		<p class="description"><?php esc_html_e( 'Title, description (excerpt), category and poster (featured image) use the standard fields on this screen. For YouTube videos without a poster, the YouTube thumbnail is imported into your Media Library on save.', 'allbiohub-pulse' ); ?></p>
	</div>
	<?php
}

/**
 * Raw stored settings value (bypasses schema; used for single-choice video picks).
 */
function pulse_opt_raw( $key ) {
	$stored = (array) get_option( PULSE_OPTION, array() );
	return isset( $stored[ $key ] ) ? $stored[ $key ] : '';
}

function pulse_set_opt_raw( $key, $value ) {
	$stored         = (array) get_option( PULSE_OPTION, array() );
	$stored[ $key ] = $value;
	update_option( PULSE_OPTION, $stored, true );
}

add_action( 'save_post_abh_video', 'pulse_video_save', 10, 2 );
function pulse_video_save( $post_id, $post ) {
	if ( ! isset( $_POST['pulse_video_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pulse_video_nonce'] ) ), 'pulse_video' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$in  = isset( $_POST['pulse_video'] ) ? (array) wp_unslash( $_POST['pulse_video'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$old = pulse_video_meta( $post_id );
	$v   = array(
		'source'      => in_array( isset( $in['source'] ) ? $in['source'] : '', array( 'youtube', 'vimeo', 'self' ), true ) ? $in['source'] : 'youtube',
		'url'         => esc_url_raw( isset( $in['url'] ) ? $in['url'] : '' ),
		'file'        => absint( isset( $in['file'] ) ? $in['file'] : 0 ),
		'duration'    => sanitize_text_field( isset( $in['duration'] ) ? $in['duration'] : '' ),
		'presenter'   => sanitize_text_field( isset( $in['presenter'] ) ? $in['presenter'] : '' ),
		'interviewee' => sanitize_text_field( isset( $in['interviewee'] ) ? $in['interviewee'] : '' ),
		'feature'     => array_key_exists( isset( $in['feature'] ) ? $in['feature'] : '', pulse_video_feature_types() ) ? $in['feature'] : 'custom',
		'label'       => sanitize_text_field( isset( $in['label'] ) ? $in['label'] : '' ),
		'home'        => empty( $in['home'] ) ? 0 : 1,
		'start'       => preg_match( '/^\d{4}-\d{2}-\d{2}$/', isset( $in['start'] ) ? $in['start'] : '' ) ? $in['start'] : '',
		'end'         => preg_match( '/^\d{4}-\d{2}-\d{2}$/', isset( $in['end'] ) ? $in['end'] : '' ) ? $in['end'] : '',
		'priority'    => min( 100, absint( isset( $in['priority'] ) ? $in['priority'] : 0 ) ),
		'source_post' => (int) $old['source_post'],
	);
	// Detect the provider from the URL so a pasted Vimeo link works even if the radio says YouTube.
	$parsed = pulse_parse_video_url( $v['url'] );
	if ( $parsed && 'self' !== $v['source'] ) {
		$v['source'] = $parsed['provider'];
	}
	update_post_meta( $post_id, PULSE_VIDEO_META, $v );
	update_post_meta( $post_id, '_abh_video_priority', $v['priority'] );

	// Exactly one Video of the Week / Month.
	foreach ( array( 'week', 'month' ) as $slot ) {
		$key = 'video_' . $slot . '_id';
		if ( ! empty( $_POST[ 'pulse_video_' . $slot ] ) ) {
			pulse_set_opt_raw( $key, $post_id );
		} elseif ( (int) pulse_opt_raw( $key ) === $post_id ) {
			pulse_set_opt_raw( $key, 0 );
		}
	}

	if ( $parsed && 'youtube' === $parsed['provider'] && ! has_post_thumbnail( $post_id ) && 'publish' === $post->post_status ) {
		pulse_import_youtube_poster( $post_id, $parsed['id'] );
	}
}

/**
 * Import the YouTube thumbnail into the Media Library (no hotlinking).
 */
function pulse_import_youtube_poster( $post_id, $youtube_id ) {
	if ( ! function_exists( 'media_sideload_image' ) ) {
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
	}
	foreach ( array( 'maxresdefault', 'hqdefault' ) as $variant ) {
		$id = media_sideload_image( 'https://i.ytimg.com/vi/' . rawurlencode( $youtube_id ) . '/' . $variant . '.jpg', $post_id, get_the_title( $post_id ), 'id' );
		if ( ! is_wp_error( $id ) ) {
			set_post_thumbnail( $post_id, $id );
			return (int) $id;
		}
	}
	return 0;
}

/* ---------- Normalised video data (AllBioHub Video or legacy post) ---------- */

/**
 * Everything a template needs about a video, for an abh_video or a regular
 * post that contains a video.
 *
 * @return array|null
 */
function pulse_video_data( $post = null ) {
	$post = get_post( $post );
	if ( ! $post ) {
		return null;
	}
	$data = array(
		'post_id'   => $post->ID,
		'provider'  => '',
		'id'        => '',
		'url'       => '',
		'file_url'  => '',
		'mime'      => '',
		'duration'  => 0,
		'badge'     => '',
		'presenter' => '',
		'guest'     => '',
		'source'    => '',
	);
	if ( 'abh_video' === $post->post_type ) {
		$v = pulse_video_meta( $post->ID );
		if ( 'self' === $v['source'] && $v['file'] ) {
			$data['provider'] = 'self';
			$data['file_url'] = wp_get_attachment_url( $v['file'] );
			$data['mime']     = get_post_mime_type( $v['file'] );
		} elseif ( $parsed = pulse_parse_video_url( $v['url'] ) ) { // phpcs:ignore
			$data['provider'] = $parsed['provider'];
			$data['id']       = $parsed['id'];
			$data['url']      = $v['url'];
		}
		$data['duration']  = pulse_duration_seconds( $v['duration'] );
		$types             = pulse_video_feature_types();
		$data['badge']     = 'custom' === $v['feature'] ? $v['label'] : ( isset( $types[ $v['feature'] ] ) ? $types[ $v['feature'] ] : '' );
		$data['presenter'] = $v['presenter'];
		$data['guest']     = $v['interviewee'];
	} else {
		$found = pulse_find_video_in_content( $post->post_content );
		if ( ! $found ) {
			return null;
		}
		$data = array_merge( $data, $found );
		// Duration only from Rank Math's VideoObject schema; never guessed.
		$schema = get_post_meta( $post->ID, 'rank_math_schema_VideoObject', true );
		if ( is_array( $schema ) && ! empty( $schema['duration'] ) ) {
			$data['duration'] = pulse_duration_seconds( $schema['duration'] );
		}
	}
	if ( ! $data['provider'] ) {
		return null;
	}
	$data['source'] = array( 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'self' => 'AllBioHub' )[ $data['provider'] ];
	return $data;
}

/**
 * First video in post content: YouTube/Vimeo URL (embed, iframe or bare), or
 * a [video]/<video> source.
 */
function pulse_find_video_in_content( $content ) {
	if ( preg_match( '~(?:https?:)?//(?:www\.|m\.)?(?:youtube(?:-nocookie)?\.com/(?:watch\?[^\s"\'<\]]*v=|embed/|shorts/|live/)|youtu\.be/)[A-Za-z0-9_-]{11}[^\s"\'<\]]*~i', $content, $m ) ) {
		$p = pulse_parse_video_url( $m[0] );
		if ( $p ) {
			return array( 'provider' => 'youtube', 'id' => $p['id'], 'url' => 'https://www.youtube.com/watch?v=' . $p['id'] );
		}
	}
	if ( preg_match( '~(?:https?:)?//(?:www\.|player\.)?vimeo\.com/[^\s"\'<\]]+~i', $content, $m ) ) {
		$p = pulse_parse_video_url( $m[0] );
		if ( $p ) {
			return array( 'provider' => 'vimeo', 'id' => $p['id'], 'url' => 'https://vimeo.com/' . $p['id'] );
		}
	}
	if ( preg_match( '~(?:\[video[^\]]*\s(?:mp4|webm|src)=["\']|<video[^>]*\ssrc=["\']|<source[^>]*\ssrc=["\'])([^"\']+\.(mp4|webm|m4v|mov))~i', $content, $m ) ) {
		return array( 'provider' => 'self', 'file_url' => $m[1], 'mime' => 'video/' . ( 'webm' === strtolower( $m[2] ) ? 'webm' : 'mp4' ) );
	}
	return null;
}

/* ---------- Choosing videos ---------- */

/**
 * Is a video inside its display window and enabled for the homepage?
 */
function pulse_video_is_active( $post_id ) {
	$v     = pulse_video_meta( $post_id );
	$today = wp_date( 'Y-m-d' );
	if ( ! $v['home'] ) {
		return false;
	}
	if ( $v['start'] && $today < $v['start'] ) {
		return false;
	}
	if ( $v['end'] && $today > $v['end'] ) {
		return false;
	}
	return 'publish' === get_post_status( $post_id );
}

/**
 * Active homepage videos ordered by priority, then newest.
 */
function pulse_active_video_ids( $limit = 12, $exclude = array() ) {
	$q   = new WP_Query(
		array(
			'post_type'           => 'abh_video',
			'posts_per_page'      => 50,
			'fields'              => 'ids',
			'no_found_rows'       => true,
			'ignore_sticky_posts' => true,
			'post__not_in'        => $exclude,
			'orderby'             => 'date',
			'order'               => 'DESC',
		)
	);
	$ids = array_values( array_filter( array_map( 'intval', $q->posts ), 'pulse_video_is_active' ) );
	usort(
		$ids,
		function ( $a, $b ) {
			$pa = (int) get_post_meta( $a, '_abh_video_priority', true );
			$pb = (int) get_post_meta( $b, '_abh_video_priority', true );
			return $pb === $pa ? get_post_timestamp( $b ) - get_post_timestamp( $a ) : $pb - $pa;
		}
	);
	return array_slice( $ids, 0, $limit );
}

/**
 * The primary homepage video and the badge it should carry.
 *
 * @return array{id:int,badge:string}|null
 */
function pulse_primary_video() {
	$mode  = pulse_opt( 'video_feature' );
	$types = pulse_video_feature_types();

	if ( 'week' === $mode || 'month' === $mode ) {
		$pick = (int) pulse_opt_raw( 'video_' . $mode . '_id' );
		if ( $pick && 'abh_video' === get_post_type( $pick ) && pulse_video_is_active( $pick ) ) {
			return array( 'id' => $pick, 'badge' => $types[ $mode ] );
		}
		if ( 'month' === $mode ) {
			// Highest-priority active video published this month.
			$q = new WP_Query(
				array(
					'post_type'      => 'abh_video',
					'posts_per_page' => 30,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'date_query'     => array( array( 'year' => (int) wp_date( 'Y' ), 'month' => (int) wp_date( 'n' ) ) ),
				)
			);
			$this_month = array_values( array_intersect( pulse_active_video_ids( 50 ), array_map( 'intval', $q->posts ) ) );
			if ( $this_month ) {
				return array( 'id' => $this_month[0], 'badge' => $types['month'] );
			}
		}
	}

	$active = pulse_active_video_ids( 1 );
	if ( $active ) {
		$data = pulse_video_data( $active[0] );
		return array( 'id' => $active[0], 'badge' => $data && $data['badge'] ? $data['badge'] : __( 'Featured Video', 'allbiohub-pulse' ) );
	}

	// No AllBioHub Videos yet: newest regular post that contains a video.
	$legacy = pulse_legacy_video_post_ids( 1 );
	if ( $legacy ) {
		return array( 'id' => $legacy[0], 'badge' => __( 'Featured Video', 'allbiohub-pulse' ) );
	}
	return null;
}

/**
 * Regular posts containing a YouTube/Vimeo/self-hosted video, newest first.
 */
function pulse_legacy_video_post_ids( $limit = 6, $exclude = array() ) {
	$cache_key = 'pulse_legacy_videos';
	$all       = get_transient( $cache_key );
	if ( false === $all ) {
		global $wpdb;
		$rows = $wpdb->get_col(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_type = 'post' AND post_status = 'publish'
			 AND ( post_content LIKE '%youtube.com/%' OR post_content LIKE '%youtu.be/%' OR post_content LIKE '%vimeo.com/%' OR post_content LIKE '%[video%' OR post_content LIKE '%<video%' )
			 ORDER BY post_date DESC LIMIT 60"
		);
		$all  = array();
		foreach ( $rows as $id ) {
			if ( pulse_video_data( (int) $id ) ) {
				$all[] = (int) $id;
			}
		}
		set_transient( $cache_key, $all, HOUR_IN_SECONDS );
	}
	return array_slice( array_values( array_diff( $all, $exclude ) ), 0, $limit );
}

add_action( 'save_post', 'pulse_legacy_videos_bust' );
function pulse_legacy_videos_bust() {
	delete_transient( 'pulse_legacy_videos' );
}

/**
 * "More from AllBioHub Video".
 */
function pulse_more_video_ids( $exclude = array() ) {
	$count = max( 3, min( 6, (int) pulse_opt( 'video_more_count' ) ) );
	$ids   = pulse_active_video_ids( $count, $exclude );
	if ( count( $ids ) < $count ) {
		$ids = array_merge( $ids, pulse_legacy_video_post_ids( $count - count( $ids ), array_merge( $exclude, $ids ) ) );
	}
	return $ids;
}

/* ---------- Rendering ---------- */

/**
 * Lightweight player: poster + play button; iframe only after a click.
 */
function pulse_video_player( $post = null, $args = array() ) {
	$post = get_post( $post );
	$data = $post ? pulse_video_data( $post ) : null;
	if ( ! $data ) {
		return '';
	}
	$args  = wp_parse_args( $args, array( 'size' => 'pulse-hero', 'loading' => 'lazy', 'fetchpriority' => '' ) );
	$title = wp_strip_all_tags( get_the_title( $post ) );
	$img   = pulse_image( $post, array( 'size' => $args['size'], 'loading' => $args['loading'], 'fetchpriority' => $args['fetchpriority'], 'class' => 'pulse-video__poster' ) );

	if ( 'self' === $data['provider'] ) {
		$poster = wp_get_attachment_image_url( pulse_image_id( $post ), $args['size'] );
		return sprintf(
			'<div class="pulse-video pulse-video--native"><video controls playsinline preload="metadata"%s><source src="%s"%s></video></div>',
			$poster ? ' poster="' . esc_url( $poster ) . '"' : '',
			esc_url( $data['file_url'] ),
			$data['mime'] ? ' type="' . esc_attr( $data['mime'] ) . '"' : ''
		);
	}
	$embed = 'youtube' === $data['provider']
		? 'https://www.youtube-nocookie.com/embed/' . rawurlencode( $data['id'] ) . '?autoplay=1&rel=0&modestbranding=1'
		: 'https://player.vimeo.com/video/' . rawurlencode( $data['id'] ) . '?autoplay=1&dnt=1';
	return sprintf(
		'<div class="pulse-video" data-embed="%1$s" data-title="%2$s">%3$s<button type="button" class="pulse-video__play" aria-label="%4$s">%5$s</button>%6$s<noscript><a href="%7$s">%8$s</a></noscript></div>',
		esc_url( $embed ),
		esc_attr( $title ),
		$img, // phpcs:ignore WordPress.Security.EscapeOutput -- built by wp_get_attachment_image.
		/* translators: %s: video title */
		esc_attr( sprintf( __( 'Play video: %s', 'allbiohub-pulse' ), $title ) ),
		pulse_icon( 'play', 28 ),
		$data['duration'] ? '<span class="pulse-video__dur">' . esc_html( pulse_duration_label( $data['duration'] ) ) . '</span>' : '',
		esc_url( $data['url'] ),
		esc_html__( 'Watch on the provider’s site', 'allbiohub-pulse' )
	);
}

/* ---------- Schema ---------- */

add_action( 'wp_head', 'pulse_video_schema', 30 );
function pulse_video_schema() {
	if ( ! is_singular( 'abh_video' ) || ! pulse_opt( 'schema_fallback' ) ) {
		return;
	}
	$post = get_queried_object();
	// Don't duplicate schema an SEO plugin already outputs for this video.
	if ( get_post_meta( $post->ID, 'rank_math_schema_VideoObject', true ) || pulse_seo_plugin_outputs_video_schema() ) {
		return;
	}
	$data = pulse_video_data( $post );
	if ( ! $data ) {
		return;
	}
	$thumb  = wp_get_attachment_image_url( pulse_image_id( $post ), 'full' );
	$schema = array(
		'@context'     => 'https://schema.org',
		'@type'        => 'VideoObject',
		'name'         => wp_strip_all_tags( get_the_title( $post ) ),
		'description'  => wp_strip_all_tags( get_the_excerpt( $post ) ),
		'uploadDate'   => get_the_date( DATE_W3C, $post ),
		'thumbnailUrl' => $thumb ? array( $thumb ) : array(),
	);
	if ( $data['duration'] ) {
		$schema['duration'] = 'PT' . intdiv( $data['duration'], 60 ) . 'M' . ( $data['duration'] % 60 ) . 'S';
	}
	if ( 'self' === $data['provider'] ) {
		$schema['contentUrl'] = $data['file_url'];
	} elseif ( 'youtube' === $data['provider'] ) {
		$schema['embedUrl'] = 'https://www.youtube.com/embed/' . $data['id'];
	} else {
		$schema['embedUrl'] = 'https://player.vimeo.com/video/' . $data['id'];
	}
	echo '<script type="application/ld+json">' . wp_json_encode( array_filter( $schema ), JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
}

/* ---------- Admin: Video Settings page and list columns ---------- */

add_action( 'admin_menu', 'pulse_video_settings_menu', 12 );
function pulse_video_settings_menu() {
	add_submenu_page( 'allbiohub', __( 'Video Settings', 'allbiohub-pulse' ), __( 'Video Settings', 'allbiohub-pulse' ), 'manage_options', 'allbiohub-video-settings', 'pulse_video_settings_screen' );
}

function pulse_video_settings_screen() {
	pulse_settings_screen( 'video' );
}

add_filter( 'manage_abh_video_posts_columns', 'pulse_video_columns' );
function pulse_video_columns( $cols ) {
	$out = array();
	foreach ( $cols as $k => $label ) {
		$out[ $k ] = $label;
		if ( 'title' === $k ) {
			$out['pulse_vsrc']  = __( 'Source', 'allbiohub-pulse' );
			$out['pulse_vtype'] = __( 'Feature', 'allbiohub-pulse' );
			$out['pulse_vhome'] = __( 'Homepage', 'allbiohub-pulse' );
		}
	}
	return $out;
}

add_action( 'manage_abh_video_posts_custom_column', 'pulse_video_column_value', 10, 2 );
function pulse_video_column_value( $col, $post_id ) {
	$v = pulse_video_meta( $post_id );
	if ( 'pulse_vsrc' === $col ) {
		echo esc_html( array( 'youtube' => 'YouTube', 'vimeo' => 'Vimeo', 'self' => __( 'Self hosted', 'allbiohub-pulse' ) )[ $v['source'] ] );
	} elseif ( 'pulse_vtype' === $col ) {
		$types = pulse_video_feature_types();
		$tags  = array();
		if ( (int) pulse_opt_raw( 'video_week_id' ) === $post_id ) {
			$tags[] = '<span class="pulse-pill">' . esc_html( $types['week'] ) . '</span>';
		}
		if ( (int) pulse_opt_raw( 'video_month_id' ) === $post_id ) {
			$tags[] = '<span class="pulse-pill">' . esc_html( $types['month'] ) . '</span>';
		}
		echo wp_kses_post( implode( ' ', $tags ) ) . ' ' . esc_html( $types[ $v['feature'] ] );
	} elseif ( 'pulse_vhome' === $col ) {
		echo pulse_video_is_active( $post_id ) ? '<span class="pulse-status pulse-status--on">' . esc_html__( 'Active', 'allbiohub-pulse' ) . '</span>' : '<span class="pulse-status">' . esc_html__( 'Off / scheduled', 'allbiohub-pulse' ) . '</span>';
	}
}
