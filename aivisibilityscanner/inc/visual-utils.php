<?php
/**
 * Visual Utility Functions
 *
 * Shared helpers for domain formatting, alt text generation,
 * and reusable SVG gauge rendering.
 *
 * @package AIVisibilityScanner
 */

/**
 * Format a domain for display with optional truncation.
 *
 * @param string $url        Full URL or domain.
 * @param int    $max_length Maximum character length before truncation.
 * @return string Formatted domain (e.g., "example.com" or "very-long-subdomain.exam...").
 */
function aivs_format_domain( $url, $max_length = 30 ) {
	$domain = aivs_clean_url_for_display( $url );
	// Remove www. prefix for cleaner display.
	$domain = preg_replace( '/^www\./i', '', $domain );
	// Remove path if present.
	$slash_pos = strpos( $domain, '/' );
	if ( $slash_pos !== false ) {
		$domain = substr( $domain, 0, $slash_pos );
	}
	if ( strlen( $domain ) > $max_length ) {
		$domain = substr( $domain, 0, $max_length - 3 ) . '...';
	}
	return $domain;
}

/**
 * Generate accessible alt text for a score badge/image.
 *
 * @param string $domain     The domain name.
 * @param int    $score      The AI Visibility Score.
 * @param string $tier_label The tier label.
 * @return string Alt text string.
 */
function aivs_generate_alt_text( $domain, $score, $tier_label ) {
	return sprintf(
		'AI Visibility Score for %s: %d out of 100, %s',
		$domain,
		intval( $score ),
		$tier_label
	);
}

/**
 * Generate a reusable SVG gauge (circular arc) for score display.
 *
 * Returns an SVG string of a circular gauge with the score centered inside.
 * The arc fills proportionally to the score value.
 *
 * @param int   $score   Score 0-100.
 * @param int   $size    SVG width/height in pixels.
 * @param array $options Optional overrides:
 *                       - 'stroke_width' (int) Gauge stroke width.
 *                       - 'track_color'  (string) Background track color.
 *                       - 'show_label'   (bool) Show /100 label.
 *                       - 'font_family'  (string) Font for score text.
 * @return string SVG markup.
 */
function aivs_score_gauge_svg( $score, $size = 100, $options = array() ) {
	$score        = max( 0, min( 100, intval( $score ) ) );
	$color        = aivs_get_tier_color( $score );
	$stroke_width = isset( $options['stroke_width'] ) ? intval( $options['stroke_width'] ) : 8;
	$track_color  = isset( $options['track_color'] ) ? $options['track_color'] : '#1E293B';
	$show_label   = isset( $options['show_label'] ) ? $options['show_label'] : true;
	$font_family  = isset( $options['font_family'] ) ? $options['font_family'] : 'Inter, Arial, sans-serif';

	$radius        = 45;
	$circumference = 2 * M_PI * $radius; // ~282.74
	$offset        = $circumference - ( $circumference * $score / 100 );

	$svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 100 100">';

	// Background track.
	$svg .= '<circle cx="50" cy="50" r="' . $radius . '" fill="none" stroke="' . esc_attr( $track_color ) . '" stroke-width="' . $stroke_width . '"/>';

	// Score arc.
	$svg .= '<circle cx="50" cy="50" r="' . $radius . '" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="' . $stroke_width . '"';
	$svg .= ' stroke-dasharray="' . round( $circumference, 2 ) . '" stroke-dashoffset="' . round( $offset, 2 ) . '"';
	$svg .= ' stroke-linecap="round" transform="rotate(-90 50 50)"/>';

	// Score number.
	$font_size = $score >= 100 ? 24 : 28;
	$svg .= '<text x="50" y="' . ( $show_label ? '48' : '54' ) . '" text-anchor="middle" font-family="' . esc_attr( $font_family ) . '" font-size="' . $font_size . '" font-weight="700" fill="white">' . $score . '</text>';

	// /100 label.
	if ( $show_label ) {
		$svg .= '<text x="50" y="66" text-anchor="middle" font-family="' . esc_attr( $font_family ) . '" font-size="11" fill="#94A3B8">/100</text>';
	}

	$svg .= '</svg>';

	return $svg;
}
