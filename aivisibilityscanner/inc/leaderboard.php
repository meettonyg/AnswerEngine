<?php
/**
 * Leaderboard Data Model
 *
 * Queries scan results to produce ranked leaderboards.
 * Uses a $wpdb query with GROUP BY for efficient deduplication at the DB level.
 *
 * @package AIVisibilityScanner
 */

/**
 * Get leaderboard entries ranked by score.
 *
 * Deduplicates by URL (keeps highest-scoring scan per URL)
 * using a database-level GROUP BY query for performance.
 * Tie-breaks alphabetically by URL.
 *
 * @param string $segment Optional segment/category filter (stored in post meta _aivs_segment).
 * @param int    $limit   Max entries to return.
 * @return array Array of entries: [ {rank, domain, score, tier_label, tier_color} ]
 */
function aivs_get_leaderboard( $segment = '', $limit = 20 ) {
	global $wpdb;

	$limit = max( 1, min( 100, intval( $limit ) ) );

	$segment_where = '';
	if ( ! empty( $segment ) ) {
		$segment_where = $wpdb->prepare(
			"AND EXISTS (
				SELECT 1 FROM {$wpdb->postmeta} seg
				WHERE seg.post_id = p.ID AND seg.meta_key = '_aivs_segment' AND seg.meta_value = %s
			)",
			sanitize_text_field( $segment )
		);
	}

	// Get the max score per URL directly in SQL, avoiding fetching hundreds of posts into PHP.
	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $segment_where is already prepared.
	$sql = $wpdb->prepare(
		"SELECT url_meta.meta_value AS url, MAX(CAST(score_meta.meta_value AS UNSIGNED)) AS score
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} url_meta ON p.ID = url_meta.post_id AND url_meta.meta_key = '_aivs_url'
		INNER JOIN {$wpdb->postmeta} score_meta ON p.ID = score_meta.post_id AND score_meta.meta_key = '_aivs_score'
		WHERE p.post_type = 'aivs_scan' AND p.post_status = 'publish'
		{$segment_where}
		GROUP BY url_meta.meta_value
		ORDER BY score DESC, url_meta.meta_value ASC
		LIMIT %d",
		$limit
	);
	// phpcs:enable

	$results = $wpdb->get_results( $sql );

	if ( empty( $results ) ) {
		return array();
	}

	$entries = array();
	$rank = 0;
	foreach ( $results as $row ) {
		$domain = aivs_format_domain( $row->url );
		if ( empty( $domain ) ) {
			continue;
		}
		$rank++;
		$score = intval( $row->score );
		$tier  = aivs_get_tier( $score );
		$entries[] = array(
			'rank'       => $rank,
			'domain'     => $domain,
			'score'      => $score,
			'tier_label' => $tier['label'],
			'tier_color' => $tier['color'],
		);
	}

	return $entries;
}
