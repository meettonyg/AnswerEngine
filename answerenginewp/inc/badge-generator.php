<?php
/**
 * Badge SVG Generator
 *
 * Generates SVG badges in three variants:
 * - inline:  320x140 — for public score pages, landing pages
 * - social:  1200x630 — for OG images, social sharing
 * - small:   220x60 — for embeddable widgets on external sites
 *
 * All variants display domain, score gauge, tier label, and branding.
 *
 * @package AnswerEngineWP
 */

/**
 * Generate an SVG badge for the given scan data.
 *
 * @param array  $data    Scan data with keys: score, url, tier (optional).
 * @param string $variant Badge variant: 'inline', 'social', or 'small'.
 * @return string SVG markup.
 */
function aewp_generate_badge_svg( $data, $variant = 'inline' ) {
	if ( is_int( $data ) || is_string( $data ) ) {
		// Legacy support: accept bare score.
		$data = array( 'score' => intval( $data ) );
	}

	$score  = max( 0, min( 100, intval( $data['score'] ) ) );
	$url    = isset( $data['url'] ) ? $data['url'] : '';
	$tier   = aewp_get_tier( $score );
	$color  = $tier['color'];
	$label  = $tier['label'];

	// Format domain at default length for alt text.
	$domain = $url ? aewp_format_domain( $url ) : '';
	$alt_text = aewp_generate_alt_text( $domain ?: 'this site', $score, $label );

	switch ( $variant ) {
		case 'social':
			return aewp_badge_social( $score, $domain, $color, $label, $alt_text );
		case 'small':
			// Shorter truncation for the compact badge.
			$domain_small = $url ? aewp_format_domain( $url, 20 ) : '';
			return aewp_badge_small( $score, $domain_small, $color, $label, $alt_text );
		case 'inline':
		default:
			return aewp_badge_inline( $score, $domain, $color, $label, $alt_text );
	}
}

/**
 * Inline Widget Badge — 320x140.
 */
function aewp_badge_inline( $score, $domain, $color, $label, $alt_text ) {
	$gauge_svg = aewp_score_gauge_svg( $score, 80, array(
		'stroke_width' => 7,
		'track_color'  => '#1E293B',
		'show_label'   => true,
	) );

	// Extract inner SVG content (remove outer <svg> wrapper to embed inline).
	$gauge_inner = preg_replace( '/<\/?svg[^>]*>/', '', $gauge_svg );

	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="320" height="140" viewBox="0 0 320 140" role="img" aria-label="' . esc_attr( $alt_text ) . '">';
	$svg .= '<title>' . esc_html( $alt_text ) . '</title>';

	// Background.
	$svg .= '<rect width="320" height="140" rx="12" fill="#0F172A"/>';

	// Tier color accent bar.
	$svg .= '<rect x="0" y="0" width="4" height="140" rx="2" fill="' . esc_attr( $color ) . '"/>';

	// Score gauge (positioned left).
	$svg .= '<g transform="translate(20,10) scale(0.8,0.8)">';
	$svg .= $gauge_inner;
	$svg .= '</g>';

	// Domain.
	$domain_display = $domain ?: 'Your Site';
	$svg .= '<text x="120" y="40" font-family="Inter, Arial, sans-serif" font-size="15" font-weight="600" fill="white">' . esc_html( $domain_display ) . '</text>';

	// Tier label.
	$svg .= '<text x="120" y="62" font-family="Inter, Arial, sans-serif" font-size="13" font-weight="500" fill="' . esc_attr( $color ) . '">' . esc_html( $label ) . '</text>';

	// Subtitle.
	$svg .= '<text x="120" y="85" font-family="Inter, Arial, sans-serif" font-size="11" fill="#94A3B8">AI Visibility Score</text>';

	// Attribution.
	$svg .= '<text x="120" y="120" font-family="Inter, Arial, sans-serif" font-size="9" fill="#64748B">answerenginewp.com</text>';

	$svg .= '</svg>';

	return $svg;
}

/**
 * Social Card Badge — 1200x630.
 */
function aewp_badge_social( $score, $domain, $color, $label, $alt_text ) {
	$circumference = 2 * M_PI * 45;
	$offset        = $circumference - ( $circumference * $score / 100 );
	$font_size_score = $score >= 100 ? 24 : 28;

	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="630" viewBox="0 0 1200 630" role="img" aria-label="' . esc_attr( $alt_text ) . '">';
	$svg .= '<title>' . esc_html( $alt_text ) . '</title>';

	// Background.
	$svg .= '<rect width="1200" height="630" fill="#0F172A"/>';

	// Subtle gradient overlay.
	$svg .= '<defs><linearGradient id="bg-grad" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#0F172A"/><stop offset="100%" stop-color="#1A2332"/></linearGradient></defs>';
	$svg .= '<rect width="1200" height="630" fill="url(#bg-grad)"/>';

	// Tier color accent line at top.
	$svg .= '<rect x="0" y="0" width="1200" height="4" fill="' . esc_attr( $color ) . '"/>';

	// Brand.
	$svg .= '<text x="600" y="80" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="18" font-weight="600" fill="#2563EB">AnswerEngineWP</text>';

	// "AI Visibility Score" label.
	$svg .= '<text x="600" y="120" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="16" fill="#94A3B8">AI Visibility Score</text>';

	// Large gauge — centered.
	$svg .= '<g transform="translate(490,140)">';
	$svg .= '<svg width="220" height="220" viewBox="0 0 100 100">';
	$svg .= '<circle cx="50" cy="50" r="45" fill="none" stroke="#1E293B" stroke-width="8"/>';
	$svg .= '<circle cx="50" cy="50" r="45" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="8" stroke-dasharray="' . round( $circumference, 2 ) . '" stroke-dashoffset="' . round( $offset, 2 ) . '" stroke-linecap="round" transform="rotate(-90 50 50)"/>';
	$svg .= '<text x="50" y="48" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="' . $font_size_score . '" font-weight="700" fill="white">' . $score . '</text>';
	$svg .= '<text x="50" y="66" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="11" fill="#94A3B8">/100</text>';
	$svg .= '</svg>';
	$svg .= '</g>';

	// Tier label.
	$svg .= '<text x="600" y="400" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="24" font-weight="600" fill="' . esc_attr( $color ) . '">' . esc_html( $label ) . '</text>';

	// Domain.
	$domain_display = $domain ?: 'Your Site';
	$svg .= '<text x="600" y="440" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="18" fill="#CBD5E1">' . esc_html( $domain_display ) . '</text>';

	// CTA.
	$svg .= '<text x="600" y="530" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="14" fill="#2563EB">Scan yours free at answerenginewp.com/scanner</text>';

	// Footer line.
	$svg .= '<line x1="400" y1="580" x2="800" y2="580" stroke="#1E293B" stroke-width="1"/>';
	$svg .= '<text x="600" y="600" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="11" fill="#64748B">answerenginewp.com</text>';

	$svg .= '</svg>';

	return $svg;
}

/**
 * Small Embeddable Badge — 220x60.
 */
function aewp_badge_small( $score, $domain, $color, $label, $alt_text ) {
	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="60" viewBox="0 0 220 60" role="img" aria-label="' . esc_attr( $alt_text ) . '">';
	$svg .= '<title>' . esc_html( $alt_text ) . '</title>';

	// Background.
	$svg .= '<rect width="220" height="60" rx="8" fill="#0F172A"/>';

	// Tier color bar.
	$svg .= '<rect x="0" y="0" width="3" height="60" rx="1.5" fill="' . esc_attr( $color ) . '"/>';

	// Score circle.
	$svg .= '<circle cx="30" cy="30" r="18" fill="none" stroke="' . esc_attr( $color ) . '" stroke-width="2.5"/>';
	$score_font = $score >= 100 ? 10 : 12;
	$svg .= '<text x="30" y="34" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="' . $score_font . '" font-weight="700" fill="white">' . $score . '</text>';

	// Domain.
	$domain_display = $domain ?: 'Your Site';
	$svg .= '<text x="58" y="22" font-family="Inter, Arial, sans-serif" font-size="11" font-weight="600" fill="white">' . esc_html( $domain_display ) . '</text>';

	// Tier label.
	$svg .= '<text x="58" y="37" font-family="Inter, Arial, sans-serif" font-size="9" fill="' . esc_attr( $color ) . '">' . esc_html( $label ) . '</text>';

	// Attribution.
	$svg .= '<text x="58" y="51" font-family="Inter, Arial, sans-serif" font-size="7" fill="#64748B">answerenginewp.com</text>';

	$svg .= '</svg>';

	return $svg;
}
