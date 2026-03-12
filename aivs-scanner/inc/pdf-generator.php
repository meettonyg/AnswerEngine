<?php
/**
 * PDF Report Generator
 *
 * Generates branded AI Visibility Audit Reports.
 * Uses Dompdf for HTML-to-PDF conversion.
 *
 * @package AIVisibilityScanner
 */

/**
 * Generate PDF report for a scan result.
 *
 * @param WP_Post $scan The scan post object.
 * @return string|WP_Error Path to generated PDF or error.
 */
function aivs_generate_pdf( $scan ) {
    // Check if Dompdf is available via Composer
    $autoload = AIVS_SCANNER_DIR . 'vendor/autoload.php';
    if ( ! file_exists( $autoload ) ) {
        return aivs_generate_pdf_fallback( $scan );
    }

    require_once $autoload;

    $url           = get_post_meta( $scan->ID, '_aivs_url', true );
    $score         = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
    $tier          = get_post_meta( $scan->ID, '_aivs_tier', true );
    $sub_scores    = get_post_meta( $scan->ID, '_aivs_sub_scores', true );
    $extraction    = get_post_meta( $scan->ID, '_aivs_extraction_data', true );
    $fixes         = get_post_meta( $scan->ID, '_aivs_fixes', true );
    $hash          = get_post_meta( $scan->ID, '_aivs_hash', true );
    $scanned_at    = get_post_meta( $scan->ID, '_aivs_scanned_at', true );
    $competitor    = get_post_meta( $scan->ID, '_aivs_competitor_data', true );
    $robots_data       = get_post_meta( $scan->ID, '_aivs_robots_data', true );
    $spa_detection     = get_post_meta( $scan->ID, '_aivs_spa_detection', true );
    $raw_text_data     = get_post_meta( $scan->ID, '_aivs_raw_text', true );
    $page_type         = get_post_meta( $scan->ID, '_aivs_page_type', true );
    $citation_sim      = get_post_meta( $scan->ID, '_aivs_citation_simulation', true );

    $tier_data = aivs_get_tier( $score );

    // Build HTML for PDF
    $html = aivs_build_pdf_html( array(
        'url'           => $url,
        'score'         => $score,
        'tier_data'     => $tier_data,
        'sub_scores'    => $sub_scores ?: array(),
        'extraction'    => $extraction ?: array(),
        'fixes'         => $fixes ?: array(),
        'competitor'    => $competitor ?: null,
        'scanned_at'    => $scanned_at,
        'hash'          => $hash,
        'robots'        => $robots_data ?: array(),
        'spa_detection' => $spa_detection ?: array(),
        'raw_text'      => $raw_text_data ?: array(),
        'page_type'     => $page_type ?: 'auto',
        'citation'      => $citation_sim ?: array(),
    ) );

    // Generate PDF
    try {
        $options = new \Dompdf\Options();
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isRemoteEnabled', false );
        $options->set( 'defaultFont', 'Helvetica' );

        $dompdf = new \Dompdf\Dompdf( $options );
        $dompdf->loadHtml( $html );
        $dompdf->setPaper( 'letter', 'portrait' );
        $dompdf->render();

        // Save to uploads
        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/aivs-reports';
        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }

        $pdf_path = $pdf_dir . '/report-' . $hash . '.pdf';
        file_put_contents( $pdf_path, $dompdf->output() );

        return $pdf_path;
    } catch ( Exception $e ) {
        return new WP_Error( 'pdf_error', 'Failed to generate PDF: ' . $e->getMessage() );
    }
}

/**
 * Fallback PDF generator (plain text PDF) when Dompdf is not available.
 */
function aivs_generate_pdf_fallback( $scan ) {
    $url        = get_post_meta( $scan->ID, '_aivs_url', true );
    $score      = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
    $hash       = get_post_meta( $scan->ID, '_aivs_hash', true );
    $sub_scores = get_post_meta( $scan->ID, '_aivs_sub_scores', true );
    $fixes      = get_post_meta( $scan->ID, '_aivs_fixes', true );
    $tier_data  = aivs_get_tier( $score );

    // Simple HTML-based approach
    $html = aivs_build_pdf_html( array(
        'url'           => $url,
        'score'         => $score,
        'tier_data'     => $tier_data,
        'sub_scores'    => $sub_scores ?: array(),
        'extraction'    => get_post_meta( $scan->ID, '_aivs_extraction_data', true ) ?: array(),
        'fixes'         => $fixes ?: array(),
        'competitor'    => get_post_meta( $scan->ID, '_aivs_competitor_data', true ) ?: null,
        'scanned_at'    => get_post_meta( $scan->ID, '_aivs_scanned_at', true ),
        'hash'          => $hash,
        'robots'        => get_post_meta( $scan->ID, '_aivs_robots_data', true ) ?: array(),
        'spa_detection' => get_post_meta( $scan->ID, '_aivs_spa_detection', true ) ?: array(),
        'raw_text'      => get_post_meta( $scan->ID, '_aivs_raw_text', true ) ?: array(),
        'page_type'     => get_post_meta( $scan->ID, '_aivs_page_type', true ) ?: 'auto',
        'citation'      => get_post_meta( $scan->ID, '_aivs_citation_simulation', true ) ?: array(),
    ) );

    // Save as HTML (fallback when no PDF lib available)
    $upload_dir = wp_upload_dir();
    $pdf_dir    = $upload_dir['basedir'] . '/aivs-reports';
    if ( ! file_exists( $pdf_dir ) ) {
        wp_mkdir_p( $pdf_dir );
    }

    $path = $pdf_dir . '/report-' . $hash . '.html';
    file_put_contents( $path, $html );

    return $path;
}

/**
 * Build the HTML content for the PDF report.
 */
function aivs_build_pdf_html( $data ) {
    $url        = esc_html( $data['url'] );
    $score      = $data['score'];
    $tier       = $data['tier_data'];
    $sub_scores = $data['sub_scores'];
    $extraction = $data['extraction'];
    $fixes      = $data['fixes'];
    $competitor = $data['competitor'];
    $date       = $data['scanned_at'] ? date( 'F j, Y', strtotime( $data['scanned_at'] ) ) : date( 'F j, Y' );

    $gauge_offset = 283 - ( 283 * $score / 100 );

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #334155; line-height: 1.6; margin: 0; padding: 40px; }
        .page { page-break-after: always; padding: 40px 0; }
        .page:last-child { page-break-after: auto; }
        h1 { color: #0F172A; font-size: 28px; margin-bottom: 8px; }
        h2 { color: #0F172A; font-size: 22px; margin-top: 32px; margin-bottom: 16px; }
        h3 { color: #0F172A; font-size: 16px; margin-bottom: 8px; }
        .brand { color: #2563EB; font-size: 14px; font-weight: bold; margin-bottom: 32px; }
        .score-big { font-size: 72px; color: ' . esc_attr( $tier['color'] ) . '; font-weight: bold; text-align: center; margin: 24px 0 8px; }
        .tier-label { text-align: center; font-size: 18px; color: ' . esc_attr( $tier['color'] ) . '; font-weight: 600; margin-bottom: 16px; }
        .tier-message { text-align: center; color: #64748B; font-size: 14px; max-width: 500px; margin: 0 auto 32px; }
        .sub-score { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #E2E8F0; }
        .sub-score-label { font-size: 14px; }
        .sub-score-value { font-weight: 600; }
        .fix { background: #F8FAFC; border-radius: 8px; padding: 16px; margin-bottom: 12px; }
        .fix-title { font-weight: 600; color: #0F172A; margin-bottom: 4px; }
        .fix-points { color: #10B981; font-weight: 600; float: right; }
        .fix-desc { font-size: 13px; color: #64748B; }
        .extraction-item { font-size: 13px; padding: 4px 0; }
        .found { color: #10B981; }
        .missing { color: #EF4444; }
        .footer-line { text-align: center; font-size: 11px; color: #94A3B8; border-top: 1px solid #E2E8F0; padding-top: 16px; margin-top: 40px; }
        .cta-box { background: #2563EB; color: white; text-align: center; padding: 32px; border-radius: 12px; margin-top: 32px; }
        .cta-box h2 { color: white; }
        .cta-box p { color: rgba(255,255,255,0.85); }
        .critical-alert-box { background: #FEF2F2; border: 2px solid #EF4444; border-radius: 8px; padding: 16px; margin-bottom: 16px; }
        .critical-alert-box h3 { color: #EF4444; font-size: 16px; margin-bottom: 8px; }
        .critical-alert-box p { color: #334155; font-size: 13px; }
        .critical-alert-box--spa { background: #FFF7ED; border-color: #F59E0B; }
        .critical-alert-box--spa h3 { color: #F59E0B; }
        .raw-text-box { background: #1E293B; color: #CBD5E1; font-family: monospace; font-size: 11px; padding: 16px; border-radius: 8px; white-space: pre-wrap; word-wrap: break-word; max-height: 400px; overflow: hidden; }
        .sub-score-layer { font-size: 11px; color: #94A3B8; font-weight: normal; margin-left: 8px; }
        .scope-line { font-size: 12px; color: #94A3B8; text-align: center; margin-top: 8px; }
        .stack-layer { display: flex; justify-content: space-between; align-items: center; padding: 12px 16px; margin-bottom: 8px; border-radius: 8px; background: #F8FAFC; }
        .stack-layer--future { opacity: 0.5; }
        .stack-layer__name { font-size: 14px; font-weight: 600; color: #0F172A; }
        .stack-layer__num { display: inline-block; width: 24px; height: 24px; border-radius: 50%; background: #E2E8F0; text-align: center; line-height: 24px; font-size: 12px; font-weight: 600; color: #334155; margin-right: 10px; }
        .stack-layer__score { font-size: 16px; font-weight: 600; }
        .stack-layer__future-label { font-size: 13px; color: #94A3B8; font-style: italic; }
        .citation-card { background: #F8FAFC; border-radius: 8px; padding: 20px; margin-bottom: 16px; }
        .citation-badge { display: inline-block; background: #E2E8F0; border-radius: 4px; padding: 2px 8px; font-size: 11px; font-weight: 600; color: #64748B; text-transform: uppercase; margin-bottom: 12px; }
        .citation-prompt { font-size: 14px; color: #64748B; font-style: italic; margin-bottom: 12px; }
        .citation-verdict { font-size: 14px; font-weight: 600; margin-bottom: 12px; }
        .citation-verdict--yes { color: #10B981; }
        .citation-verdict--no { color: #EF4444; }
        .citation-reason { font-size: 13px; padding: 4px 0; }
        .citation-reason--positive { color: #10B981; }
        .citation-reason--negative { color: #EF4444; }
        .citation-missed-heading { font-size: 13px; font-weight: 600; color: #334155; margin-top: 12px; margin-bottom: 4px; }
        .aewp-cta-link { color: #2563EB; font-weight: 600; font-size: 13px; display: block; margin-top: 8px; }
        .page-type-badge { display: inline-block; background: #F0F9FF; border: 1px solid #BAE6FD; border-radius: 6px; padding: 4px 12px; font-size: 12px; color: #0369A1; margin-top: 8px; }
        .blindspot-box { background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 24px; text-align: center; margin-top: 24px; }
        .blindspot-box h3 { font-size: 18px; margin-bottom: 8px; }
        .blindspot-box p { font-size: 13px; color: #64748B; }
        .comp-table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .comp-table th { text-align: left; font-size: 12px; text-transform: uppercase; color: #94A3B8; padding: 8px; border-bottom: 2px solid #E2E8F0; }
        .comp-table td { padding: 8px; border-bottom: 1px solid #F1F5F9; font-size: 14px; }
        .comparison-chart { margin-top: 16px; }
        .comparison-chart__header { margin-bottom: 16px; }
        .comparison-chart__title { font-size: 16px; color: #0F172A; margin-bottom: 8px; }
        .comparison-chart__legend { display: flex; gap: 16px; margin-bottom: 12px; }
        .comparison-chart__legend-item { font-size: 12px; color: #64748B; display: flex; align-items: center; gap: 6px; }
        .comparison-chart__legend-dot { display: inline-block; width: 10px; height: 10px; border-radius: 2px; }
        .comparison-row { padding: 8px; border-bottom: 1px solid #E2E8F0; }
        .comparison-row--overall { font-weight: 600; }
        .comparison-row__label { font-size: 13px; color: #334155; margin-bottom: 6px; }
        .comparison-row__bars { margin-bottom: 4px; }
        .comparison-row__bar-group { display: flex; align-items: center; gap: 8px; margin-bottom: 4px; }
        .comparison-row__bar-track { flex: 1; height: 12px; background: #F1F5F9; border-radius: 4px; overflow: hidden; }
        .comparison-row__bar-fill--yours { height: 100%; background: #2563EB; border-radius: 4px; }
        .comparison-row__bar-fill--comp { height: 100%; background: #94A3B8; border-radius: 4px; }
        .comparison-row__bar-value { font-size: 12px; color: #334155; min-width: 24px; text-align: right; }
        .comparison-row__delta { font-size: 12px; font-weight: 600; text-align: right; }
        .comparison-row__delta--positive { color: #22C55E; }
        .comparison-row__delta--negative { color: #EF4444; }
        .comparison-row__delta--neutral { color: #94A3B8; }
    </style></head><body>';

    $page_type_str = isset( $data['page_type'] ) ? $data['page_type'] : 'auto';

    // Page 1: Cover
    $html .= '<div class="page">
        <div class="brand">AI Visibility Scanner</div>
        <h1>AI Visibility Audit Report</h1>
        <p style="color:#64748B;margin-bottom:8px;">' . $url . ' &bull; ' . $date . '</p>';
    if ( 'auto' !== $page_type_str && ! empty( $page_type_str ) ) {
        $html .= '<div class="page-type-badge">Scored as: ' . esc_html( ucfirst( str_replace( '_', ' ', $page_type_str ) ) ) . '</div>';
    }
    $html .= '<div style="margin-top:40px;" class="score-big">' . $score . '</div>
        <div class="tier-label">' . esc_html( $tier['label'] ) . '</div>
        <p class="tier-message">' . esc_html( $tier['message'] ) . '</p>
        <p class="scope-line">This scan evaluates structural signals across 3 layers of the AI Visibility Stack.</p>
        <div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div>
    </div>';

    // Page 1.5: Critical Alerts (only if alerts exist)
    $robots = isset( $data['robots'] ) ? $data['robots'] : array();
    $spa    = isset( $data['spa_detection'] ) ? $data['spa_detection'] : array();
    if ( ( ! empty( $robots['has_critical_block'] ) ) || ( ! empty( $spa['is_spa'] ) ) ) {
        $html .= '<div class="page">
            <div class="brand">AI Visibility Scanner</div>
            <h2>Critical Alerts</h2>';

        if ( ! empty( $robots['has_critical_block'] ) ) {
            $blocked_bots = is_array( $robots['ai_bots_blocked'] ) ? implode( ', ', $robots['ai_bots_blocked'] ) : '';
            $html .= '<div class="critical-alert-box">
                <h3>&#9888; robots.txt Blocks AI Crawlers</h3>
                <p>Blocked bots: ' . esc_html( $blocked_bots ) . '</p>
                <p class="aewp-cta-link">Fix with AEWP\'s Bot Manager</p>
            </div>';
        }

        if ( ! empty( $spa['is_spa'] ) ) {
            $html .= '<div class="critical-alert-box critical-alert-box--spa">
                <h3>&#9888; Client-Side Rendering Detected</h3>
                <p>Only ' . intval( $spa['word_count'] ) . ' words of body text found. AI crawlers cannot execute JavaScript.</p>
            </div>';
        }

        $html .= '<div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';
    }

    // Layer mapping for sub-scores
    $layer_map = array(
        'crawl_access'        => array( 'layer' => 1, 'label' => 'Access' ),
        'feed_readiness'      => array( 'layer' => 1, 'label' => 'Access' ),
        'schema_completeness' => array( 'layer' => 2, 'label' => 'Understanding' ),
        'entity_density'      => array( 'layer' => 2, 'label' => 'Understanding' ),
        'content_structure'   => array( 'layer' => 3, 'label' => 'Extractability' ),
        'faq_coverage'        => array( 'layer' => 3, 'label' => 'Extractability' ),
        'summary_presence'    => array( 'layer' => 3, 'label' => 'Extractability' ),
        'content_richness'    => array( 'layer' => 3, 'label' => 'Extractability' ),
    );

    // Page 2: Score Breakdown
    $html .= '<div class="page">
        <div class="brand">AI Visibility Scanner</div>
        <h2>Score Breakdown</h2>';
    if ( is_array( $sub_scores ) ) {
        foreach ( $sub_scores as $key => $sub ) {
            if ( ! is_array( $sub ) ) continue;
            $layer_badge = '';
            if ( isset( $sub['layer'] ) && isset( $sub['layer_name'] ) ) {
                $layer_badge = '<span class="sub-score-layer">Layer ' . intval( $sub['layer'] ) . ': ' . esc_html( $sub['layer_name'] ) . '</span>';
            } elseif ( isset( $layer_map[ $key ] ) ) {
                $layer_badge = '<span class="sub-score-layer">Layer ' . intval( $layer_map[ $key ]['layer'] ) . ': ' . esc_html( $layer_map[ $key ]['label'] ) . '</span>';
            }
            $html .= '<div class="sub-score">
                <span class="sub-score-label">' . esc_html( $sub['label'] ) . $layer_badge . '</span>
                <span class="sub-score-value">' . intval( $sub['score'] ) . '/100</span>
            </div>';
        }
    }
    $html .= '<div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';

    // Page 2.5: AI Visibility Stack Summary
    if ( is_array( $sub_scores ) ) {
        // Layer 1: Access = avg(crawl_access, feed_readiness)
        $crawl_s = isset( $sub_scores['crawl_access']['score'] ) ? intval( $sub_scores['crawl_access']['score'] ) : 0;
        $feed_s  = isset( $sub_scores['feed_readiness']['score'] ) ? intval( $sub_scores['feed_readiness']['score'] ) : 0;
        $layer_1_score = isset( $sub_scores['crawl_access'] ) ? round( ( $crawl_s + $feed_s ) / 2 ) : $feed_s;

        // Layer 2: Understanding = avg(schema_completeness, entity_density)
        $layer_2_score = round( (
            ( isset( $sub_scores['schema_completeness']['score'] ) ? $sub_scores['schema_completeness']['score'] : 0 ) +
            ( isset( $sub_scores['entity_density']['score'] ) ? $sub_scores['entity_density']['score'] : 0 )
        ) / 2 );

        // Layer 3: Extractability = avg(content_structure, faq_coverage, summary_presence, content_richness)
        $l3_sum = ( isset( $sub_scores['content_structure']['score'] ) ? $sub_scores['content_structure']['score'] : 0 ) +
                  ( isset( $sub_scores['faq_coverage']['score'] ) ? $sub_scores['faq_coverage']['score'] : 0 ) +
                  ( isset( $sub_scores['summary_presence']['score'] ) ? $sub_scores['summary_presence']['score'] : 0 ) +
                  ( isset( $sub_scores['content_richness']['score'] ) ? $sub_scores['content_richness']['score'] : 0 );
        $l3_cnt = 3 + ( isset( $sub_scores['content_richness'] ) ? 1 : 0 );
        $layer_3_score = round( $l3_sum / $l3_cnt );

        $stack_layers = array(
            array( 'num' => 1, 'name' => 'Access',          'score' => $layer_1_score, 'future' => false ),
            array( 'num' => 2, 'name' => 'Understanding',   'score' => $layer_2_score, 'future' => false ),
            array( 'num' => 3, 'name' => 'Extractability',  'score' => $layer_3_score, 'future' => false ),
            array( 'num' => 4, 'name' => 'Trust',           'score' => null,           'future' => true ),
            array( 'num' => 5, 'name' => 'Authority',       'score' => null,           'future' => true ),
        );

        $html .= '<div class="page">
            <div class="brand">AI Visibility Scanner</div>
            <h2>AI Visibility Stack Analysis</h2>
            <p style="color:#64748B;font-size:13px;margin-bottom:24px;">The AI Visibility Stack shows how AI systems decide which sources to cite. Each layer depends on the ones below it.</p>';

        foreach ( $stack_layers as $sl ) {
            $future_class = $sl['future'] ? ' stack-layer--future' : '';
            $score_html = '';
            if ( $sl['future'] ) {
                $score_html = '<span class="stack-layer__future-label">&#128274; Premium</span>';
            } else {
                $sl_tier = aivs_get_tier( $sl['score'] );
                $score_html = '<span class="stack-layer__score" style="color:' . esc_attr( $sl_tier['color'] ) . '">' . intval( $sl['score'] ) . '/100</span>';
            }
            $html .= '<div class="stack-layer' . $future_class . '">
                <div><span class="stack-layer__num">' . intval( $sl['num'] ) . '</span><span class="stack-layer__name">' . esc_html( $sl['name'] ) . '</span></div>
                ' . $score_html . '
            </div>';
        }

        $html .= '<p style="color:#94A3B8;font-size:12px;margin-top:16px;text-align:center;">Learn more at aivisibilityscanner.com/methodology</p>
            <div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';
    }

    // Page 3: Competitor Gap (if applicable)
    if ( ! empty( $competitor ) && is_array( $competitor ) ) {
        $html .= '<div class="page">
            <div class="brand">AI Visibility Scanner</div>
            <h2>Competitor Structure Gap</h2>';
        $html .= aivs_render_comparison_bars(
            array( 'score' => $score, 'sub_scores' => $sub_scores ),
            $competitor,
            aivs_clean_url_for_display( $url ),
            isset( $competitor['url'] ) ? $competitor['url'] : 'Competitor'
        );
        $html .= '<div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';
    }

    // Page 4: Extraction Preview
    $html .= '<div class="page">
        <div class="brand">AI Visibility Scanner</div>
        <h2>Extraction Preview</h2>';
    if ( is_array( $extraction ) ) {
        if ( ! empty( $extraction['schema_types'] ) ) {
            $html .= '<h3>Schema Types Found</h3>';
            foreach ( $extraction['schema_types'] as $type ) {
                $html .= '<div class="extraction-item found">&check; ' . esc_html( $type ) . '</div>';
            }
        }
        if ( ! empty( $extraction['entities'] ) ) {
            $html .= '<h3>Entities Detected</h3>';
            foreach ( $extraction['entities'] as $entity ) {
                $html .= '<div class="extraction-item found">&check; ' . esc_html( $entity ) . '</div>';
            }
        }
        if ( ! empty( $extraction['headlines'] ) ) {
            $html .= '<h3>Headings Found</h3>';
            foreach ( array_slice( $extraction['headlines'], 0, 8 ) as $headline ) {
                $html .= '<div class="extraction-item">' . esc_html( $headline ) . '</div>';
            }
        }

        // Crawl & Richness Signals
        $signal_items = array();
        if ( isset( $extraction['ttfb_ms'] ) && $extraction['ttfb_ms'] > 0 ) {
            $ttfb_label = intval( $extraction['ttfb_ms'] ) . 'ms TTFB';
            $signal_items[] = array( 'found' => $extraction['ttfb_ms'] < 2000, 'text' => $ttfb_label );
        }
        if ( ! empty( $extraction['has_canonical'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => 'Canonical tag present' );
        }
        if ( ! empty( $extraction['is_ssr'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => 'Server-side rendered' );
        }
        if ( ! empty( $extraction['stat_count'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => intval( $extraction['stat_count'] ) . ' statistics/data points' );
        }
        if ( ! empty( $extraction['quality_citations'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => intval( $extraction['quality_citations'] ) . ' quality citation(s)' );
        }
        if ( ! empty( $extraction['front_loaded_count'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => intval( $extraction['front_loaded_count'] ) . ' front-loaded answer(s)' );
        }
        if ( ! empty( $extraction['question_heading_count'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => intval( $extraction['question_heading_count'] ) . ' question heading(s)' );
        }
        if ( ! empty( $extraction['list_count'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => intval( $extraction['list_count'] ) . ' list(s) found' );
        }
        if ( ! empty( $extraction['table_count'] ) ) {
            $signal_items[] = array( 'found' => true, 'text' => intval( $extraction['table_count'] ) . ' table(s) found' );
        }
        if ( ! empty( $signal_items ) ) {
            $html .= '<h3>Crawl &amp; Richness Signals</h3>';
            foreach ( $signal_items as $si ) {
                $icon  = $si['found'] ? '&check;' : '&cross;';
                $class = $si['found'] ? 'found' : 'missing';
                $html .= '<div class="extraction-item ' . $class . '">' . $icon . ' ' . esc_html( $si['text'] ) . '</div>';
            }
        }

        if ( ! empty( $extraction['missing'] ) ) {
            $html .= '<h3>Missing Signals</h3>';
            foreach ( $extraction['missing'] as $item ) {
                $html .= '<div class="extraction-item missing">&cross; ' . esc_html( $item ) . '</div>';
            }
        }
    }
    $html .= '<div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';

    // Page 4.5: Citation Simulation (if data available)
    $citation = isset( $data['citation'] ) ? $data['citation'] : array();
    if ( ! empty( $citation ) && isset( $citation['prompt'] ) ) {
        $html .= '<div class="page">
            <div class="brand">AI Visibility Scanner</div>
            <h2>Citation Simulation</h2>
            <p style="color:#64748B;font-size:13px;margin-bottom:24px;">Based on structural signals, not AI ranking models.</p>
            <div class="citation-card">
                <div class="citation-badge">Simulated</div>
                <div class="citation-prompt">&ldquo;' . esc_html( $citation['prompt'] ) . '&rdquo;</div>';

        $would_cite = ! empty( $citation['would_cite'] );
        $verdict_class = $would_cite ? 'citation-verdict--yes' : 'citation-verdict--no';
        $verdict_text = $would_cite
            ? 'Based on your page structure, AI systems would likely cite your content for this query.'
            : 'Based on your page structure, AI systems would likely skip your content for this query.';
        $html .= '<div class="citation-verdict ' . $verdict_class . '">' . esc_html( $verdict_text ) . '</div>';

        if ( ! empty( $citation['reasons'] ) ) {
            foreach ( $citation['reasons'] as $reason ) {
                $reason_class = $would_cite ? 'citation-reason--positive' : 'citation-reason--negative';
                $reason_icon  = $would_cite ? '&check;' : '&cross;';
                $html .= '<div class="citation-reason ' . $reason_class . '">' . $reason_icon . ' ' . esc_html( $reason ) . '</div>';
            }
        }

        if ( ! empty( $citation['missed_citations'] ) ) {
            $html .= '<div class="citation-missed-heading">Missed Citation Opportunities</div>';
            foreach ( $citation['missed_citations'] as $heading ) {
                $html .= '<div class="citation-reason citation-reason--negative">&cross; &ldquo;' . esc_html( $heading ) . '&rdquo; has no FAQ/HowTo schema</div>';
            }
        }

        $html .= '</div>
            <div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';
    }

    // Page 5: Top 3 Fixes
    $html .= '<div class="page">
        <div class="brand">AI Visibility Scanner</div>
        <h2>Top 3 Recommended Fixes</h2>';
    if ( is_array( $fixes ) ) {
        foreach ( $fixes as $fix ) {
            if ( ! is_array( $fix ) ) continue;
            $html .= '<div class="fix">
                <div class="fix-points">+' . intval( $fix['points'] ) . ' pts</div>
                <div class="fix-title">' . esc_html( $fix['title'] ) . '</div>
                <div class="fix-desc">' . esc_html( $fix['description'] ) . '</div>';
            if ( ! empty( $fix['aewp_cta'] ) ) {
                $html .= '<div class="aewp-cta-link">' . esc_html( $fix['aewp_cta'] ) . '</div>';
            }
            $html .= '</div>';
        }
    }
    $html .= '<div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div></div>';

    // Page: How AI Sees Your Page (if raw text available)
    $raw_text = isset( $data['raw_text'] ) ? $data['raw_text'] : array();
    if ( ! empty( $raw_text['raw_text'] ) ) {
        $html .= '<div class="page">
            <div class="brand">AI Visibility Scanner</div>
            <h2>How AI Sees Your Page</h2>
            <p style="color:#64748B;font-size:13px;margin-bottom:16px;">This is the raw text that AI crawlers extract from your page:</p>
            <div class="raw-text-box">' . esc_html( substr( $raw_text['raw_text'], 0, 1500 ) ) . '</div>
            <p style="margin-top:16px;font-size:13px;color:#64748B;">Is this a mess? AnswerEngineWP generates clean, structured feeds for AI. Join the waitlist at aivisibilityscanner.com</p>
            <div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div>
        </div>';
    }

    // Final Page: CTA + Blindspot
    $html .= '<div class="page">
        <div class="brand">AI Visibility Scanner</div>
        <div class="blindspot-box">
            <h3>You just scanned 1 page</h3>
            <p>Your site has dozens (or hundreds) of pages. Each one needs its own schema, structure, and AI-ready signals. This scanner checked just one.</p>
        </div>
        <div class="cta-box">
            <h2>Improve your score with AnswerEngineWP</h2>
            <p>The WordPress plugin that fixes your AI visibility score automatically. Join the waitlist for early access.</p>
            <p style="margin-top:24px;font-weight:600;">aivisibilityscanner.com</p>
        </div>
        <p style="text-align:center;margin-top:32px;color:#64748B;font-size:14px;">
            Scan more sites: <strong>aivisibilityscanner.com</strong>
        </p>
        <div class="footer-line">Generated by AI Visibility Scanner &middot; aivisibilityscanner.com</div>
    </div>';

    $html .= '</body></html>';

    return $html;
}
