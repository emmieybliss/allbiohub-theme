<?php
/**
 * SEO compatibility. AllBioHub runs Rank Math, which owns titles, meta, Open
 * Graph, schema, breadcrumbs and sitemaps. Pulse outputs none of those while an
 * SEO plugin is active, and only adds a minimal fallback when none is.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

/**
 * Which SEO plugin is active, if any.
 */
function pulse_seo_plugin() {
	if ( defined( 'RANK_MATH_VERSION' ) || class_exists( 'RankMath' ) ) {
		return 'rankmath';
	}
	if ( defined( 'WPSEO_VERSION' ) ) {
		return 'yoast';
	}
	if ( defined( 'AIOSEO_VERSION' ) ) {
		return 'aioseo';
	}
	if ( defined( 'SEOPRESS_VERSION' ) ) {
		return 'seopress';
	}
	return '';
}

/**
 * Whether the SEO plugin is already emitting VideoObject schema for videos.
 * Filterable in case the site's Rank Math setup auto-detects videos.
 */
function pulse_seo_plugin_outputs_video_schema() {
	return (bool) apply_filters( 'pulse_seo_outputs_video_schema', false );
}

/**
 * Breadcrumbs from the SEO plugin, or a simple trail.
 */
function pulse_breadcrumbs() {
	if ( function_exists( 'rank_math_the_breadcrumbs' ) ) {
		echo '<nav class="pulse-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'allbiohub-pulse' ) . '">';
		rank_math_the_breadcrumbs();
		echo '</nav>';
		return;
	}
	if ( function_exists( 'yoast_breadcrumb' ) ) {
		yoast_breadcrumb( '<nav class="pulse-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'allbiohub-pulse' ) . '">', '</nav>' );
		return;
	}
	if ( ! is_singular() ) {
		return;
	}
	$cat = is_singular( 'post' ) ? pulse_primary_category() : null;
	echo '<nav class="pulse-crumbs" aria-label="' . esc_attr__( 'Breadcrumb', 'allbiohub-pulse' ) . '"><a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Home', 'allbiohub-pulse' ) . '</a>';
	if ( $cat ) {
		echo ' <span aria-hidden="true">/</span> <a href="' . esc_url( get_term_link( $cat ) ) . '">' . esc_html( html_entity_decode( $cat->name ) ) . '</a>';
	} elseif ( is_singular( 'abh_video' ) ) {
		echo ' <span aria-hidden="true">/</span> <a href="' . esc_url( get_post_type_archive_link( 'abh_video' ) ) . '">' . esc_html__( 'Video', 'allbiohub-pulse' ) . '</a>';
	} elseif ( is_singular( array( 'abh_startup', 'abh_founder' ) ) && get_page_by_path( 'startups' ) ) {
		$startups = get_page_by_path( 'startups' );
		echo ' <span aria-hidden="true">/</span> <a href="' . esc_url( get_permalink( $startups ) ) . '">' . esc_html( get_the_title( $startups ) ) . '</a>';
	}
	echo '</nav>';
}

/**
 * Fallback meta + Article schema, only when no SEO plugin is active.
 */
add_action( 'wp_head', 'pulse_seo_fallback', 5 );
function pulse_seo_fallback() {
	if ( pulse_seo_plugin() || ! pulse_opt( 'schema_fallback' ) ) {
		return;
	}
	if ( is_singular( 'post' ) ) {
		$post = get_queried_object();
		$desc = wp_strip_all_tags( pulse_subtitle( $post ) ? pulse_subtitle( $post ) : pulse_excerpt( $post, 30 ) );
		$img  = wp_get_attachment_image_url( pulse_image_id( $post ), 'full' );
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
		printf( '<meta property="og:type" content="article"><meta property="og:title" content="%s"><meta property="og:url" content="%s">%s' . "\n", esc_attr( wp_strip_all_tags( get_the_title( $post ) ) ), esc_url( get_permalink( $post ) ), $img ? '<meta property="og:image" content="' . esc_url( $img ) . '"><meta name="twitter:card" content="summary_large_image">' : '' );
		$schema = array(
			'@context'         => 'https://schema.org',
			'@type'            => pulse_is_biography( $post ) ? 'Article' : 'NewsArticle',
			'headline'         => wp_strip_all_tags( get_the_title( $post ) ),
			'datePublished'    => get_the_date( DATE_W3C, $post ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
			'author'           => array( '@type' => 'Person', 'name' => get_the_author_meta( 'display_name', $post->post_author ), 'url' => get_author_posts_url( $post->post_author ) ),
			'publisher'        => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ),
			'mainEntityOfPage' => get_permalink( $post ),
		);
		if ( $img ) {
			$schema['image'] = array( $img );
		}
		echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
