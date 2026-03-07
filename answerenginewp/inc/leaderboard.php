<?php
/**
 * Leaderboard Data Model
 *
 * Queries scan results to produce ranked leaderboards.
 * No new CPT — uses existing aewp_scan posts.
 *
 * @package AnswerEngineWP
 */

/**
 * Get leaderboard entries ranked by score.
 *
 * Deduplicates by domain (keeps highest-scoring scan per domain).
 * Tie-breaks alphabetically by domain.
 *
 * @param string $segment Optional segment/category filter (stored in post meta _aewp_segment).
 * @param int    $limit   Max entries to return.
 * @return array Array of entries: [ {rank, domain, score, tier_label} ]
 */
function aewp_get_leaderboard( $segment = '', $limit = 20 ) {
	$meta_query = array();

	if ( ! empty( $segment ) ) {
		$meta_query[] = array(
			'key'   => '_aewp_segment',
			'value' => sanitize_text_field( $segment ),
		);
	}

	$args = array(
		'post_type'   => 'aewp_scan',
		'post_status' => 'publish',
		'numberposts' => 500, // Fetch a batch, then deduplicate.
		'meta_key'    => '_aewp_score',
		'orderby'     => 'meta_value_num',
		'order'       => 'DESC',
	);

	if ( ! empty( $meta_query ) ) {
		$args['meta_query'] = $meta_query;
	}

	$scans = get_posts( $args );

	// Deduplicate by domain — keep the highest score per domain.
	$by_domain = array();
	foreach ( $scans as $scan ) {
		$url    = get_post_meta( $scan->ID, '_aewp_url', true );
		$score  = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
		$domain = aewp_format_domain( $url );

		if ( empty( $domain ) ) {
			continue;
		}

		$domain_key = strtolower( $domain );

		if ( ! isset( $by_domain[ $domain_key ] ) || $score > $by_domain[ $domain_key ]['score'] ) {
			$tier = aewp_get_tier( $score );
			$by_domain[ $domain_key ] = array(
				'domain'     => $domain,
				'score'      => $score,
				'tier_label' => $tier['label'],
				'tier_color' => $tier['color'],
			);
		}
	}

	// Sort descending by score, then alphabetically by domain.
	uasort( $by_domain, function( $a, $b ) {
		if ( $a['score'] === $b['score'] ) {
			return strcmp( $a['domain'], $b['domain'] );
		}
		return $b['score'] - $a['score'];
	} );

	// Apply limit and assign ranks.
	$entries = array();
	$rank = 0;
	foreach ( $by_domain as $entry ) {
		$rank++;
		if ( $rank > $limit ) {
			break;
		}
		$entry['rank'] = $rank;
		$entries[] = $entry;
	}

	return $entries;
}
