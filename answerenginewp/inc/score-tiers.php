<?php
/**
 * Score Tier Configuration
 *
 * Single source of truth for tier thresholds, colors, labels, and messages.
 * All visual outputs must reference this config instead of hardcoding values.
 *
 * @package AnswerEngineWP
 */

/**
 * Tier configuration array.
 * Each tier defines its minimum score threshold, key, label, color, CSS class, and message.
 * Ordered descending by threshold for lookup.
 */
define( 'AEWP_TIER_CONFIG', array(
	array(
		'min'     => 90,
		'key'     => 'authority',
		'label'   => 'AI Authority',
		'color'   => '#22C55E',
		'css_var' => '--tier-green',
		'class'   => 'tier-green',
		'message' => 'Your site is fully optimized for AI extraction and citation. AI systems treat your content as a trusted, authoritative source. You are an AI authority in your space.',
	),
	array(
		'min'     => 70,
		'key'     => 'extractable',
		'label'   => 'AI Extractable',
		'color'   => '#3B82F6',
		'css_var' => '--tier-blue',
		'class'   => 'tier-blue',
		'message' => 'AI systems can extract and structure your content effectively. You\'re close to becoming a preferred citation source — a few structural improvements separate you from authority status.',
	),
	array(
		'min'     => 40,
		'key'     => 'readable',
		'label'   => 'AI Readable',
		'color'   => '#EAB308',
		'css_var' => '--tier-amber',
		'class'   => 'tier-amber',
		'message' => 'AI systems can find your content, but they\'re choosing better-structured competitors to cite. You\'re in the room — but you\'re not being quoted.',
	),
	array(
		'min'     => 0,
		'key'     => 'invisible',
		'label'   => 'Invisible to AI',
		'color'   => '#EF4444',
		'css_var' => '--tier-red',
		'class'   => 'tier-red',
		'message' => 'ChatGPT cannot reliably extract or cite your site. Better-structured competitors are more likely to be cited while your content goes unread by the systems your audience increasingly trusts.',
	),
) );

/**
 * Brand colors referenced by visual outputs.
 */
define( 'AEWP_BRAND_COLORS', array(
	'navy'       => '#0F1923',
	'navy_light' => '#1A2332',
	'blue'       => '#2563EB',
	'red'        => '#EF4444',
	'yellow'     => '#EAB308',
	'tier_blue'  => '#3B82F6',
	'green'      => '#22C55E',
	'light_gray' => '#F1F5F9',
	'dark_text'  => '#111827',
	'muted_text' => '#64748B',
	'gray_400'   => '#94A3B8',
	'border'     => '#E2E8F0',
) );

/**
 * Get tier data from score using the shared config.
 *
 * @param int $score Score 0-100.
 * @return array Tier data with key, label, color, css_var, class, message.
 */
function aewp_get_tier( $score ) {
	$score = intval( $score );
	foreach ( AEWP_TIER_CONFIG as $tier ) {
		if ( $score >= $tier['min'] ) {
			return $tier;
		}
	}
	// Fallback (should never reach here).
	return AEWP_TIER_CONFIG[ count( AEWP_TIER_CONFIG ) - 1 ];
}

/**
 * Get just the tier color for a score.
 *
 * @param int $score Score 0-100.
 * @return string Hex color.
 */
function aewp_get_tier_color( $score ) {
	$tier = aewp_get_tier( $score );
	return $tier['color'];
}

/**
 * Get tier config as JSON for JavaScript consumption.
 *
 * @return array Simplified config for wp_localize_script.
 */
function aewp_get_tier_config_for_js() {
	$config = array();
	foreach ( AEWP_TIER_CONFIG as $tier ) {
		$config[] = array(
			'min'   => $tier['min'],
			'key'   => $tier['key'],
			'label' => $tier['label'],
			'color' => $tier['color'],
			'class' => $tier['class'],
		);
	}
	return $config;
}
