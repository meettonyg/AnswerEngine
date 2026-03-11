<?php
/**
 * Score Tier Configuration
 *
 * Single source of truth for tier thresholds, colors, labels, and messages.
 * All visual outputs must reference this config instead of hardcoding values.
 *
 * @package AIVisibilityScanner
 */

/**
 * Tier configuration array.
 * Each tier defines its minimum score threshold, key, label, color, CSS class, and message.
 * Ordered descending by threshold for lookup.
 */
define( 'AIVS_TIER_CONFIG', array(
	array(
		'min'     => 90,
		'key'     => 'authority',
		'label'   => 'AI Authority',
		'color'   => '#22C55E',
		'css_var' => '--tier-green',
		'class'   => 'tier-green',
		'message' => 'Your Visibility Stack is healthy across Access, Understanding, and Extractability. AI systems treat your content as a trusted, authoritative source.',
	),
	array(
		'min'     => 70,
		'key'     => 'extractable',
		'label'   => 'AI Extractable',
		'color'   => '#3B82F6',
		'css_var' => '--tier-blue',
		'class'   => 'tier-blue',
		'message' => 'You are strong across Layers 1-3 of the Visibility Stack. With a few refinements to Layer 3 (Extractability), you can become a preferred citation source.',
	),
	array(
		'min'     => 40,
		'key'     => 'readable',
		'label'   => 'AI Readable',
		'color'   => '#EAB308',
		'css_var' => '--tier-amber',
		'class'   => 'tier-amber',
		'message' => 'You have likely passed Layer 1 (Access), but your Visibility Stack is weak in Layer 3 (Extractability). AI can find you, but struggles to cite you.',
	),
	array(
		'min'     => 0,
		'key'     => 'invisible',
		'label'   => 'Invisible to AI',
		'color'   => '#EF4444',
		'css_var' => '--tier-red',
		'class'   => 'tier-red',
		'message' => 'Your Visibility Stack is failing early in Layer 1 and Layer 2. AI systems cannot reliably access or understand your content yet.',
	),
) );

/**
 * Brand colors referenced by visual outputs.
 */
define( 'AIVS_BRAND_COLORS', array(
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
function aivs_get_tier( $score ) {
	$score = intval( $score );
	foreach ( AIVS_TIER_CONFIG as $tier ) {
		if ( $score >= $tier['min'] ) {
			return $tier;
		}
	}
	// Fallback (should never reach here).
	return AIVS_TIER_CONFIG[ count( AIVS_TIER_CONFIG ) - 1 ];
}

/**
 * Get just the tier color for a score.
 *
 * @param int $score Score 0-100.
 * @return string Hex color.
 */
function aivs_get_tier_color( $score ) {
	$tier = aivs_get_tier( $score );
	return $tier['color'];
}

/**
 * Get tier config as JSON for JavaScript consumption.
 *
 * @return array Simplified config for wp_localize_script.
 */
function aivs_get_tier_config_for_js() {
	$config = array();
	foreach ( AIVS_TIER_CONFIG as $tier ) {
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
