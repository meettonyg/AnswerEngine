<?php
/**
 * Comparison Chart Renderer
 *
 * Renders visual comparison charts in two formats:
 * 1. Bar Chart — horizontal bars per subscore, two bars per row
 * 2. Before/After — dual score cards showing improvement
 *
 * Outputs HTML for web display and SVG for image export.
 *
 * @package AIVisibilityScanner
 */

/**
 * Subscore key-to-label mapping (canonical order).
 */
function aivs_get_subscore_labels() {
	return array(
		'crawl_access'        => 'Crawl Access',
		'feed_readiness'      => 'Feed & Manifest Readiness',
		'schema_completeness' => 'Schema Completeness',
		'entity_density'      => 'Entity Density',
		'content_structure'   => 'Content Structure',
		'faq_coverage'        => 'FAQ / Answer Coverage',
		'summary_presence'    => 'Summary Presence',
		'content_richness'    => 'Content Richness',
	);
}

/**
 * Render a comparison bar chart as HTML.
 *
 * Each row shows a subscore with two horizontal bars (yours vs competitor).
 * Delta is color-coded: green for wins, red for losses.
 *
 * @param array  $your_data       Your scan data with sub_scores.
 * @param array  $competitor_data Competitor data with sub_scores.
 * @param string $your_label      Display label for your site.
 * @param string $comp_label      Display label for competitor.
 * @return string HTML markup.
 */
function aivs_render_comparison_bars( $your_data, $competitor_data, $your_label = 'Your Site', $comp_label = 'Competitor' ) {
	if ( empty( $competitor_data ) || ! is_array( $competitor_data ) ) {
		return '';
	}

	$your_scores = isset( $your_data['sub_scores'] ) ? $your_data['sub_scores'] : $your_data;
	$comp_scores = isset( $competitor_data['sub_scores'] ) ? $competitor_data['sub_scores'] : $competitor_data;
	$labels      = aivs_get_subscore_labels();

	$your_overall = isset( $your_data['score'] ) ? intval( $your_data['score'] ) : 0;
	$comp_overall = isset( $competitor_data['score'] ) ? intval( $competitor_data['score'] ) : 0;

	$html = '<div class="comparison-chart">';

	// Header with legend.
	$html .= '<div class="comparison-chart__header">';
	$html .= '<h3 class="comparison-chart__title">Score Comparison</h3>';
	$html .= '<div class="comparison-chart__legend">';
	$html .= '<span class="comparison-chart__legend-item comparison-chart__legend-item--yours"><span class="comparison-chart__legend-dot" style="background:#2563EB"></span>' . esc_html( $your_label ) . '</span>';
	$html .= '<span class="comparison-chart__legend-item comparison-chart__legend-item--comp"><span class="comparison-chart__legend-dot" style="background:#64748B"></span>' . esc_html( $comp_label ) . '</span>';
	$html .= '</div>';
	$html .= '</div>';

	// Overall score row.
	$html .= aivs_render_comparison_row( 'Overall Score', $your_overall, $comp_overall, true );

	// Subscore rows.
	foreach ( $labels as $key => $label ) {
		$yours = 0;
		$theirs = 0;

		if ( is_array( $your_scores ) && isset( $your_scores[ $key ] ) ) {
			$yours = is_array( $your_scores[ $key ] ) ? intval( $your_scores[ $key ]['score'] ) : intval( $your_scores[ $key ] );
		}
		if ( is_array( $comp_scores ) && isset( $comp_scores[ $key ] ) ) {
			$theirs = is_array( $comp_scores[ $key ] ) ? intval( $comp_scores[ $key ]['score'] ) : intval( $comp_scores[ $key ] );
		}

		$html .= aivs_render_comparison_row( $label, $yours, $theirs, false );
	}

	$html .= '</div>';

	return $html;
}

/**
 * Render a single comparison row with two bars and a delta indicator.
 *
 * @param string $label      Row label.
 * @param int    $yours      Your score value.
 * @param int    $theirs     Competitor score value.
 * @param bool   $is_overall Whether this is the overall score row.
 * @return string HTML markup.
 */
function aivs_render_comparison_row( $label, $yours, $theirs, $is_overall = false ) {
	$delta     = $yours - $theirs;
	$delta_cls = $delta > 0 ? 'comparison-row__delta--positive' : ( $delta < 0 ? 'comparison-row__delta--negative' : 'comparison-row__delta--neutral' );
	$delta_str = $delta > 0 ? '+' . $delta : (string) $delta;
	$row_cls   = $is_overall ? 'comparison-row comparison-row--overall' : 'comparison-row';
	$row_tint  = '';
	if ( $delta < 0 ) {
		$row_tint = ' style="background:rgba(239,68,68,0.05)"';
	} elseif ( $delta > 0 ) {
		$row_tint = ' style="background:rgba(34,197,94,0.05)"';
	}

	$html  = '<div class="' . $row_cls . '"' . $row_tint . '>';
	$html .= '<div class="comparison-row__label">' . esc_html( $label ) . '</div>';
	$html .= '<div class="comparison-row__bars">';

	// Your bar.
	$html .= '<div class="comparison-row__bar-group">';
	$html .= '<div class="comparison-row__bar-track"><div class="comparison-row__bar-fill comparison-row__bar-fill--yours" style="width:' . $yours . '%"></div></div>';
	$html .= '<span class="comparison-row__bar-value">' . $yours . '</span>';
	$html .= '</div>';

	// Competitor bar.
	$html .= '<div class="comparison-row__bar-group">';
	$html .= '<div class="comparison-row__bar-track"><div class="comparison-row__bar-fill comparison-row__bar-fill--comp" style="width:' . $theirs . '%"></div></div>';
	$html .= '<span class="comparison-row__bar-value">' . $theirs . '</span>';
	$html .= '</div>';

	$html .= '</div>';

	// Delta.
	$html .= '<div class="comparison-row__delta ' . $delta_cls . '">' . $delta_str . '</div>';

	$html .= '</div>';

	return $html;
}

/**
 * Render a before/after comparison card.
 *
 * @param array $data Before/after data with keys:
 *                    before_score, after_score, before_tier, after_tier,
 *                    domain (optional).
 * @return string HTML markup.
 */
function aivs_render_before_after( $data ) {
	$before_score = intval( $data['before_score'] );
	$after_score  = intval( $data['after_score'] );
	$before_tier  = aivs_get_tier( $before_score );
	$after_tier   = aivs_get_tier( $after_score );
	$improvement  = $after_score - $before_score;
	$domain       = isset( $data['domain'] ) ? aivs_format_domain( $data['domain'] ) : '';

	$html = '<div class="before-after">';

	if ( $domain ) {
		$html .= '<p class="before-after__domain">' . esc_html( $domain ) . '</p>';
	}

	$html .= '<div class="before-after__cards">';

	// Before card.
	$html .= '<div class="before-after__card before-after__card--before">';
	$html .= '<div class="before-after__card-label">Before</div>';
	$html .= '<div class="before-after__score" style="color:' . esc_attr( $before_tier['color'] ) . '">' . $before_score . '</div>';
	$html .= '<div class="before-after__tier" style="color:' . esc_attr( $before_tier['color'] ) . '">' . esc_html( $before_tier['label'] ) . '</div>';
	$html .= '</div>';

	// Arrow.
	$arrow_color = $improvement > 0 ? '#22C55E' : ( $improvement < 0 ? '#EF4444' : '#94A3B8' );
	$html .= '<div class="before-after__arrow" style="color:' . esc_attr( $arrow_color ) . '">';
	$html .= $improvement > 0 ? '&#8594;' : ( $improvement < 0 ? '&#8592;' : '&#8596;' );
	$html .= '<div class="before-after__delta" style="color:' . esc_attr( $arrow_color ) . '">';
	$html .= $improvement > 0 ? '+' . $improvement : (string) $improvement;
	$html .= '</div>';
	$html .= '</div>';

	// After card.
	$html .= '<div class="before-after__card before-after__card--after">';
	$html .= '<div class="before-after__card-label">After</div>';
	$html .= '<div class="before-after__score" style="color:' . esc_attr( $after_tier['color'] ) . '">' . $after_score . '</div>';
	$html .= '<div class="before-after__tier" style="color:' . esc_attr( $after_tier['color'] ) . '">' . esc_html( $after_tier['label'] ) . '</div>';
	$html .= '</div>';

	$html .= '</div>'; // .before-after__cards
	$html .= '</div>'; // .before-after

	return $html;
}

/**
 * Generate a comparison chart as SVG for image export.
 *
 * @param array  $your_data       Your scan data.
 * @param array  $competitor_data Competitor data.
 * @param string $your_label      Your site label.
 * @param string $comp_label      Competitor label.
 * @return string SVG markup.
 */
function aivs_generate_comparison_svg( $your_data, $competitor_data, $your_label = 'Your Site', $comp_label = 'Competitor' ) {
	$your_scores = isset( $your_data['sub_scores'] ) ? $your_data['sub_scores'] : $your_data;
	$comp_scores = isset( $competitor_data['sub_scores'] ) ? $competitor_data['sub_scores'] : $competitor_data;
	$labels      = aivs_get_subscore_labels();

	$your_overall = isset( $your_data['score'] ) ? intval( $your_data['score'] ) : 0;
	$comp_overall = isset( $competitor_data['score'] ) ? intval( $competitor_data['score'] ) : 0;

	$rows = array();
	$rows[] = array( 'label' => 'Overall Score', 'yours' => $your_overall, 'theirs' => $comp_overall );

	foreach ( $labels as $key => $label ) {
		$yours = 0;
		$theirs = 0;
		if ( is_array( $your_scores ) && isset( $your_scores[ $key ] ) ) {
			$yours = is_array( $your_scores[ $key ] ) ? intval( $your_scores[ $key ]['score'] ) : intval( $your_scores[ $key ] );
		}
		if ( is_array( $comp_scores ) && isset( $comp_scores[ $key ] ) ) {
			$theirs = is_array( $comp_scores[ $key ] ) ? intval( $comp_scores[ $key ]['score'] ) : intval( $comp_scores[ $key ] );
		}
		$rows[] = array( 'label' => $label, 'yours' => $yours, 'theirs' => $theirs );
	}

	$row_height  = 60;
	$header_h    = 80;
	$total_h     = $header_h + count( $rows ) * $row_height + 40;
	$width       = 800;
	$bar_start   = 280;
	$bar_width   = 380;
	$value_x     = 680;
	$delta_x     = 740;

	$alt_text = 'AI Visibility Score Comparison: ' . esc_attr( $your_label ) . ' vs ' . esc_attr( $comp_label );

	$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $width . '" height="' . $total_h . '" viewBox="0 0 ' . $width . ' ' . $total_h . '" role="img" aria-label="' . $alt_text . '">';
	$svg .= '<title>' . esc_html( $alt_text ) . '</title>';

	// Background.
	$svg .= '<rect width="' . $width . '" height="' . $total_h . '" rx="12" fill="#0F172A"/>';

	// Title.
	$svg .= '<text x="24" y="35" font-family="Inter, Arial, sans-serif" font-size="16" font-weight="600" fill="white">Score Comparison</text>';

	// Legend.
	$svg .= '<rect x="24" y="50" width="10" height="10" rx="2" fill="#2563EB"/>';
	$svg .= '<text x="40" y="59" font-family="Inter, Arial, sans-serif" font-size="11" fill="#CBD5E1">' . esc_html( $your_label ) . '</text>';
	$svg .= '<rect x="200" y="50" width="10" height="10" rx="2" fill="#64748B"/>';
	$svg .= '<text x="216" y="59" font-family="Inter, Arial, sans-serif" font-size="11" fill="#CBD5E1">' . esc_html( $comp_label ) . '</text>';

	// Rows.
	$y = $header_h;
	foreach ( $rows as $i => $row ) {
		$delta = $row['yours'] - $row['theirs'];
		$bg_color = 'none';
		if ( $delta < 0 ) {
			$bg_color = 'rgba(239,68,68,0.08)';
		} elseif ( $delta > 0 ) {
			$bg_color = 'rgba(34,197,94,0.08)';
		}

		if ( $bg_color !== 'none' ) {
			$svg .= '<rect x="0" y="' . $y . '" width="' . $width . '" height="' . $row_height . '" fill="' . $bg_color . '"/>';
		}

		// Label.
		$font_weight = $i === 0 ? '600' : '400';
		$svg .= '<text x="24" y="' . ( $y + 25 ) . '" font-family="Inter, Arial, sans-serif" font-size="12" font-weight="' . $font_weight . '" fill="#CBD5E1">' . esc_html( $row['label'] ) . '</text>';

		// Your bar.
		$bar_w = max( 2, (int) ( $bar_width * $row['yours'] / 100 ) );
		$svg .= '<rect x="' . $bar_start . '" y="' . ( $y + 12 ) . '" width="' . $bar_w . '" height="12" rx="3" fill="#2563EB"/>';

		// Competitor bar.
		$comp_bar_w = max( 2, (int) ( $bar_width * $row['theirs'] / 100 ) );
		$svg .= '<rect x="' . $bar_start . '" y="' . ( $y + 32 ) . '" width="' . $comp_bar_w . '" height="12" rx="3" fill="#64748B"/>';

		// Values.
		$svg .= '<text x="' . $value_x . '" y="' . ( $y + 23 ) . '" font-family="Inter, Arial, sans-serif" font-size="11" fill="#CBD5E1" text-anchor="end">' . $row['yours'] . '</text>';
		$svg .= '<text x="' . $value_x . '" y="' . ( $y + 43 ) . '" font-family="Inter, Arial, sans-serif" font-size="11" fill="#94A3B8" text-anchor="end">' . $row['theirs'] . '</text>';

		// Delta.
		$delta_color = $delta > 0 ? '#22C55E' : ( $delta < 0 ? '#EF4444' : '#94A3B8' );
		$delta_str   = $delta > 0 ? '+' . $delta : (string) $delta;
		$svg .= '<text x="' . $delta_x . '" y="' . ( $y + 33 ) . '" font-family="Inter, Arial, sans-serif" font-size="12" font-weight="600" fill="' . $delta_color . '" text-anchor="end">' . $delta_str . '</text>';

		$y += $row_height;
	}

	// Footer.
	$svg .= '<text x="' . ( $width / 2 ) . '" y="' . ( $total_h - 10 ) . '" text-anchor="middle" font-family="Inter, Arial, sans-serif" font-size="9" fill="#64748B">aivisibilityscanner.com</text>';

	$svg .= '</svg>';

	return $svg;
}
