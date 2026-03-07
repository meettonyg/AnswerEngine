<?php
/**
 * Leaderboard Graphic Generator
 *
 * Generates SVG leaderboard graphics for social sharing, blog embeds,
 * and presentations.
 *
 * Variants:
 * - social: 1200x1200, top 10 — for LinkedIn, X/Twitter
 * - blog:   1200x1600, top 20 — for article embeds
 * - presentation: 1920x1080, top 10 — for decks/webinars
 *
 * @package AnswerEngineWP
 */

/**
 * Generate a leaderboard as SVG.
 *
 * @param array  $entries Array of {rank, domain, score, tier_label, tier_color}.
 * @param string $title   Leaderboard title.
 * @param string $variant 'social', 'blog', or 'presentation'.
 * @return string SVG markup.
 */
function aewp_generate_leaderboard_svg( $entries, $title, $variant = 'social' ) {
	switch ( $variant ) {
		case 'blog':
			return aewp_leaderboard_svg_blog( $entries, $title );
		case 'presentation':
			return aewp_leaderboard_svg_presentation( $entries, $title );
		case 'social':
		default:
			return aewp_leaderboard_svg_social( $entries, $title );
	}
}

/**
 * Social variant — 1200x1200, top 10.
 */
function aewp_leaderboard_svg_social( $entries, $title ) {
	$entries  = array_slice( $entries, 0, 10 );
	$width    = 1200;
	$row_h    = 80;
	$header_h = 200;
	$footer_h = 120;
	$height   = $header_h + count( $entries ) * $row_h + $footer_h;
	if ( $height < 1200 ) $height = 1200;

	return aewp_leaderboard_svg_render( $entries, $title, $width, $height, $row_h, $header_h );
}

/**
 * Blog variant — 1200x1600, top 20.
 */
function aewp_leaderboard_svg_blog( $entries, $title ) {
	$entries  = array_slice( $entries, 0, 20 );
	$width    = 1200;
	$row_h    = 60;
	$header_h = 180;
	$footer_h = 120;
	$height   = $header_h + count( $entries ) * $row_h + $footer_h;
	if ( $height < 1600 ) $height = 1600;

	return aewp_leaderboard_svg_render( $entries, $title, $width, $height, $row_h, $header_h );
}

/**
 * Presentation variant — 1920x1080, top 10.
 */
function aewp_leaderboard_svg_presentation( $entries, $title ) {
	$entries  = array_slice( $entries, 0, 10 );
	$width    = 1920;
	$row_h    = 70;
	$header_h = 180;
	$footer_h = 100;
	$height   = 1080;

	return aewp_leaderboard_svg_render( $entries, $title, $width, $height, $row_h, $header_h );
}

/**
 * Core SVG render for leaderboard.
 */
function aewp_leaderboard_svg_render( $entries, $title, $width, $height, $row_h, $header_h ) {
	$date = date( 'F j, Y' );
	$alt  = esc_attr( $title );

	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $height . '" viewBox="0 0 ' . $width . ' ' . $height . '" role="img" aria-label="' . $alt . '">';
	$svg .= '<title>' . esc_html( $title ) . '</title>';

	// Background.
	$svg .= '<rect width="' . $width . '" height="' . $height . '" fill="#0F172A"/>';

	// Header accent line.
	$svg .= '<rect x="0" y="0" width="' . $width . '" height="4" fill="#2563EB"/>';

	// Brand.
	$svg .= '<text x="60" y="50" font-family="Inter, Arial, sans-serif" font-size="16" font-weight="600" fill="#2563EB">AnswerEngineWP</text>';

	// Title.
	$svg .= '<text x="60" y="100" font-family="Inter, Arial, sans-serif" font-size="28" font-weight="700" fill="white">' . esc_html( $title ) . '</text>';

	// Date.
	$svg .= '<text x="60" y="130" font-family="Inter, Arial, sans-serif" font-size="14" fill="#94A3B8">' . esc_html( $date ) . '</text>';

	// Column headers.
	$y = $header_h - 20;
	$svg .= '<text x="60" y="' . $y . '" font-family="Inter, Arial, sans-serif" font-size="11" fill="#64748B" text-transform="uppercase">RANK</text>';
	$svg .= '<text x="130" y="' . $y . '" font-family="Inter, Arial, sans-serif" font-size="11" fill="#64748B">DOMAIN</text>';
	$svg .= '<text x="' . ( $width - 220 ) . '" y="' . $y . '" font-family="Inter, Arial, sans-serif" font-size="11" fill="#64748B" text-anchor="end">SCORE</text>';
	$svg .= '<text x="' . ( $width - 60 ) . '" y="' . $y . '" font-family="Inter, Arial, sans-serif" font-size="11" fill="#64748B" text-anchor="end">TIER</text>';

	// Separator.
	$svg .= '<line x1="60" y1="' . ( $header_h - 8 ) . '" x2="' . ( $width - 60 ) . '" y2="' . ( $header_h - 8 ) . '" stroke="#1E293B" stroke-width="1"/>';

	// Rows.
	$y = $header_h;
	foreach ( $entries as $entry ) {
		$is_top_3  = $entry['rank'] <= 3;
		$row_y     = $y + ( $row_h / 2 ) + 5;
		$tier_color = isset( $entry['tier_color'] ) ? $entry['tier_color'] : aewp_get_tier_color( $entry['score'] );

		// Highlight top 3.
		if ( $is_top_3 ) {
			$svg .= '<rect x="40" y="' . $y . '" width="' . ( $width - 80 ) . '" height="' . $row_h . '" rx="8" fill="rgba(37,99,235,0.08)"/>';
		}

		// Rank.
		$rank_size = $is_top_3 ? 22 : 16;
		$rank_weight = $is_top_3 ? '700' : '400';
		$rank_color = $is_top_3 ? '#2563EB' : '#94A3B8';
		$svg .= '<text x="80" y="' . $row_y . '" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="' . $rank_size . '" font-weight="' . $rank_weight . '" fill="' . $rank_color . '">' . intval( $entry['rank'] ) . '</text>';

		// Domain.
		$domain_size = $is_top_3 ? 16 : 14;
		$domain_weight = $is_top_3 ? '600' : '400';
		$svg .= '<text x="130" y="' . $row_y . '" font-family="Inter, Arial, sans-serif" font-size="' . $domain_size . '" font-weight="' . $domain_weight . '" fill="white">' . esc_html( $entry['domain'] ) . '</text>';

		// Score.
		$svg .= '<text x="' . ( $width - 220 ) . '" y="' . $row_y . '" font-family="Inter, Arial, sans-serif" font-size="16" font-weight="600" fill="' . esc_attr( $tier_color ) . '" text-anchor="end">' . intval( $entry['score'] ) . '</text>';

		// Tier chip.
		$tier_text = esc_html( $entry['tier_label'] );
		$chip_w    = max( 100, strlen( $tier_text ) * 7 + 16 );
		$chip_x    = $width - 60 - $chip_w;
		$chip_y    = $row_y - 12;
		$svg .= '<rect x="' . $chip_x . '" y="' . $chip_y . '" width="' . $chip_w . '" height="20" rx="4" fill="none" stroke="' . esc_attr( $tier_color ) . '" stroke-width="1"/>';
		$svg .= '<text x="' . ( $chip_x + $chip_w / 2 ) . '" y="' . ( $chip_y + 14 ) . '" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="10" fill="' . esc_attr( $tier_color ) . '">' . $tier_text . '</text>';

		// Row separator.
		$svg .= '<line x1="60" y1="' . ( $y + $row_h ) . '" x2="' . ( $width - 60 ) . '" y2="' . ( $y + $row_h ) . '" stroke="#1E293B" stroke-width="0.5"/>';

		$y += $row_h;
	}

	// CTA.
	$cta_y = $height - 80;
	$svg .= '<text x="' . ( $width / 2 ) . '" y="' . $cta_y . '" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="14" fill="#2563EB">Run your own scan at answerenginewp.com/scanner</text>';

	// Methodology note.
	$note_y = $height - 50;
	$svg .= '<text x="' . ( $width / 2 ) . '" y="' . $note_y . '" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="10" fill="#64748B">Rankings based on AnswerEngineWP structural AI visibility analysis. Scores reflect extractability signals, not endorsement or traffic.</text>';

	// Footer brand.
	$svg .= '<text x="' . ( $width / 2 ) . '" y="' . ( $height - 20 ) . '" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="11" fill="#64748B">answerenginewp.com</text>';

	$svg .= '</svg>';

	return $svg;
}
