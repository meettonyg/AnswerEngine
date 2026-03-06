<?php
/**
 * Badge SVG Generator
 *
 * Generates SVG badges for sites scoring 70+.
 *
 * @package AnswerEngineWP
 */

/**
 * Generate an SVG badge for the given score.
 *
 * @param int $score The AI Visibility Score (0-100).
 * @return string SVG markup.
 */
function aewp_generate_badge_svg( $score ) {
    $tier = aewp_get_tier( $score );
    $color = $tier['color'];
    $label = $tier['label'];

    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="160" height="50" viewBox="0 0 160 50">';

    // Background
    $svg .= '<rect width="160" height="50" rx="8" fill="#0F172A"/>';

    // Tier color bar
    $svg .= '<rect x="0" y="0" width="4" height="50" rx="2" fill="' . esc_attr( $color ) . '"/>';

    // Score
    $svg .= '<text x="24" y="22" font-family="Georgia, serif" font-size="18" font-weight="bold" fill="' . esc_attr( $color ) . '">' . intval( $score ) . '</text>';
    $svg .= '<text x="' . ( intval( $score ) >= 100 ? 56 : ( intval( $score ) >= 10 ? 48 : 38 ) ) . '" y="22" font-family="Arial, sans-serif" font-size="10" fill="#94A3B8">/100</text>';

    // Label
    $svg .= '<text x="24" y="36" font-family="Arial, sans-serif" font-size="9" fill="#94A3B8">AI Visibility Score</text>';

    // Attribution
    $svg .= '<text x="24" y="46" font-family="Arial, sans-serif" font-size="7" fill="#64748B">answerenginewp.com</text>';

    $svg .= '</svg>';

    return $svg;
}
