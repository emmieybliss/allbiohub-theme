<?php
/**
 * Category sections (Celebrity · Business · Technology · Lifestyle · …).
 * Sections with no feeding category are skipped, never shown empty.
 *
 * @package AllBioHub_Pulse
 */

defined( 'ABSPATH' ) || exit;

$count = max( 3, min( 8, (int) pulse_opt( 'section_posts' ) ) );
foreach ( pulse_opt_slugs( 'sections' ) as $i => $key ) {
	$ids = pulse_section_posts( $key, $count, pulse_seen() );
	if ( count( $ids ) < 3 ) {
		continue;
	}
	pulse_seen( $ids );
	pulse_part( 'category-section', array( 'key' => $key, 'ids' => $ids, 'index' => $i ) );
}
