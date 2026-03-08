<?php
/**
 * PDF Report Generator
 *
 * Generates branded AI Visibility Audit Reports.
 * Uses Dompdf for HTML-to-PDF conversion.
 *
 * @package AnswerEngineWP
 */

/**
 * Generate PDF report for a scan result.
 *
 * @param WP_Post $scan The scan post object.
 * @return string|WP_Error Path to generated PDF or error.
 */
function aewp_generate_pdf( $scan ) {
    // Check if Dompdf is available via Composer
    $autoload = get_template_directory() . '/vendor/autoload.php';
    if ( ! file_exists( $autoload ) ) {
        return aewp_generate_pdf_fallback( $scan );
    }

    require_once $autoload;

    $url           = get_post_meta( $scan->ID, '_aewp_url', true );
    $score         = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
    $tier          = get_post_meta( $scan->ID, '_aewp_tier', true );
    $sub_scores    = get_post_meta( $scan->ID, '_aewp_sub_scores', true );
    $extraction    = get_post_meta( $scan->ID, '_aewp_extraction_data', true );
    $fixes         = get_post_meta( $scan->ID, '_aewp_fixes', true );
    $hash          = get_post_meta( $scan->ID, '_aewp_hash', true );
    $scanned_at    = get_post_meta( $scan->ID, '_aewp_scanned_at', true );
    $competitor    = get_post_meta( $scan->ID, '_aewp_competitor_data', true );

    $tier_data = aewp_get_tier( $score );

    // Build HTML for PDF
    $html = aewp_build_pdf_html( array(
        'url'         => $url,
        'score'       => $score,
        'tier_data'   => $tier_data,
        'sub_scores'  => $sub_scores ?: array(),
        'extraction'  => $extraction ?: array(),
        'fixes'       => $fixes ?: array(),
        'competitor'  => $competitor ?: null,
        'scanned_at'  => $scanned_at,
        'hash'        => $hash,
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
        $pdf_dir    = $upload_dir['basedir'] . '/aewp-reports';
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
function aewp_generate_pdf_fallback( $scan ) {
    $url        = get_post_meta( $scan->ID, '_aewp_url', true );
    $score      = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
    $hash       = get_post_meta( $scan->ID, '_aewp_hash', true );
    $sub_scores = get_post_meta( $scan->ID, '_aewp_sub_scores', true );
    $fixes      = get_post_meta( $scan->ID, '_aewp_fixes', true );
    $tier_data  = aewp_get_tier( $score );

    // Simple HTML-based approach
    $html = aewp_build_pdf_html( array(
        'url'         => $url,
        'score'       => $score,
        'tier_data'   => $tier_data,
        'sub_scores'  => $sub_scores ?: array(),
        'extraction'  => get_post_meta( $scan->ID, '_aewp_extraction_data', true ) ?: array(),
        'fixes'       => $fixes ?: array(),
        'competitor'  => get_post_meta( $scan->ID, '_aewp_competitor_data', true ) ?: null,
        'scanned_at'  => get_post_meta( $scan->ID, '_aewp_scanned_at', true ),
        'hash'        => $hash,
    ) );

    // Save as HTML (fallback when no PDF lib available)
    $upload_dir = wp_upload_dir();
    $pdf_dir    = $upload_dir['basedir'] . '/aewp-reports';
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
function aewp_build_pdf_html( $data ) {
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

    // Page 1: Cover
    $html .= '<div class="page">
        <div class="brand">AnswerEngineWP</div>
        <h1>AI Visibility Audit Report</h1>
        <p style="color:#64748B;margin-bottom:48px;">' . $url . ' &bull; ' . $date . '</p>
        <div class="score-big">' . $score . '</div>
        <div class="tier-label">' . esc_html( $tier['label'] ) . '</div>
        <p class="tier-message">' . esc_html( $tier['message'] ) . '</p>
        <div class="footer-line">Generated by AnswerEngineWP &middot; answerenginewp.com</div>
    </div>';

    // Page 2: Score Breakdown
    $html .= '<div class="page">
        <div class="brand">AnswerEngineWP</div>
        <h2>Score Breakdown</h2>';
    if ( is_array( $sub_scores ) ) {
        foreach ( $sub_scores as $key => $sub ) {
            if ( ! is_array( $sub ) ) continue;
            $html .= '<div class="sub-score">
                <span class="sub-score-label">' . esc_html( $sub['label'] ) . '</span>
                <span class="sub-score-value">' . intval( $sub['score'] ) . '/100</span>
            </div>';
        }
    }
    $html .= '<div class="footer-line">Generated by AnswerEngineWP &middot; answerenginewp.com</div></div>';

    // Page 3: Competitor Gap (if applicable)
    if ( ! empty( $competitor ) && is_array( $competitor ) ) {
        $html .= '<div class="page">
            <div class="brand">AnswerEngineWP</div>
            <h2>Competitor Structure Gap</h2>';
        $html .= aewp_render_comparison_bars(
            array( 'score' => $score, 'sub_scores' => $sub_scores ),
            $competitor,
            aewp_clean_url_for_display( $url ),
            isset( $competitor['url'] ) ? $competitor['url'] : 'Competitor'
        );
        $html .= '<div class="footer-line">Generated by AnswerEngineWP &middot; answerenginewp.com</div></div>';
    }

    // Page 4: Extraction Preview
    $html .= '<div class="page">
        <div class="brand">AnswerEngineWP</div>
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
        if ( ! empty( $extraction['missing'] ) ) {
            $html .= '<h3>Missing Signals</h3>';
            foreach ( $extraction['missing'] as $item ) {
                $html .= '<div class="extraction-item missing">&cross; ' . esc_html( $item ) . '</div>';
            }
        }
    }
    $html .= '<div class="footer-line">Generated by AnswerEngineWP &middot; answerenginewp.com</div></div>';

    // Page 5: Top 3 Fixes
    $html .= '<div class="page">
        <div class="brand">AnswerEngineWP</div>
        <h2>Top 3 Recommended Fixes</h2>';
    if ( is_array( $fixes ) ) {
        foreach ( $fixes as $fix ) {
            if ( ! is_array( $fix ) ) continue;
            $html .= '<div class="fix">
                <div class="fix-points">+' . intval( $fix['points'] ) . ' pts</div>
                <div class="fix-title">' . esc_html( $fix['title'] ) . '</div>
                <div class="fix-desc">' . esc_html( $fix['description'] ) . '</div>
            </div>';
        }
    }
    $html .= '<div class="footer-line">Generated by AnswerEngineWP &middot; answerenginewp.com</div></div>';

    // Page 6: CTA
    $html .= '<div class="page">
        <div class="brand">AnswerEngineWP</div>
        <div class="cta-box">
            <h2>Fix this in 60 seconds with AnswerEngineWP</h2>
            <p>Install the free WordPress plugin to automatically add AI-visible structure to your site.</p>
            <p style="margin-top:24px;font-weight:600;">https://wordpress.org/plugins/answerenginewp/</p>
        </div>
        <p style="text-align:center;margin-top:32px;color:#64748B;font-size:14px;">
            Scan more sites: <strong>answerenginewp.com/scanner</strong>
        </p>
        <div class="footer-line">Generated by AnswerEngineWP &middot; answerenginewp.com</div>
    </div>';

    $html .= '</body></html>';

    return $html;
}

/**
 * Generate an agency-branded prospect PDF report.
 *
 * Same content as the standard PDF but with agency branding
 * (logo, name, "Prepared for") on every page header.
 *
 * @param WP_Post $scan The scan post object.
 * @return string|WP_Error Path to generated PDF or error.
 */
function aewp_generate_prospect_pdf( $scan ) {
    $autoload = get_template_directory() . '/vendor/autoload.php';
    $has_dompdf = file_exists( $autoload );
    if ( $has_dompdf ) {
        require_once $autoload;
    }

    $url           = get_post_meta( $scan->ID, '_aewp_url', true );
    $score         = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
    $sub_scores    = get_post_meta( $scan->ID, '_aewp_sub_scores', true );
    $extraction    = get_post_meta( $scan->ID, '_aewp_extraction_data', true );
    $fixes         = get_post_meta( $scan->ID, '_aewp_fixes', true );
    $hash          = get_post_meta( $scan->ID, '_aewp_hash', true );
    $scanned_at    = get_post_meta( $scan->ID, '_aewp_scanned_at', true );
    $competitor    = get_post_meta( $scan->ID, '_aewp_competitor_data', true );
    $tier_data     = aewp_get_tier( $score );

    $html = aewp_build_prospect_pdf_html( array(
        'url'         => $url,
        'score'       => $score,
        'tier_data'   => $tier_data,
        'sub_scores'  => $sub_scores ?: array(),
        'extraction'  => $extraction ?: array(),
        'fixes'       => $fixes ?: array(),
        'competitor'  => $competitor ?: null,
        'scanned_at'  => $scanned_at,
        'hash'        => $hash,
    ) );

    if ( ! $has_dompdf ) {
        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/aewp-reports';
        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }
        $path = $pdf_dir . '/prospect-' . $hash . '.html';
        file_put_contents( $path, $html );
        return $path;
    }

    try {
        $options = new \Dompdf\Options();
        $options->set( 'isHtml5ParserEnabled', true );
        $options->set( 'isRemoteEnabled', true );
        $options->set( 'defaultFont', 'Helvetica' );

        $dompdf = new \Dompdf\Dompdf( $options );
        $dompdf->loadHtml( $html );
        $dompdf->setPaper( 'letter', 'portrait' );
        $dompdf->render();

        $upload_dir = wp_upload_dir();
        $pdf_dir    = $upload_dir['basedir'] . '/aewp-reports';
        if ( ! file_exists( $pdf_dir ) ) {
            wp_mkdir_p( $pdf_dir );
        }

        $pdf_path = $pdf_dir . '/prospect-' . $hash . '.pdf';
        file_put_contents( $pdf_path, $dompdf->output() );

        return $pdf_path;
    } catch ( Exception $e ) {
        return new WP_Error( 'pdf_error', 'Failed to generate prospect PDF: ' . $e->getMessage() );
    }
}

/**
 * Build the HTML for the agency-branded prospect PDF.
 */
function aewp_build_prospect_pdf_html( $data ) {
    $url        = esc_html( $data['url'] );
    $score      = $data['score'];
    $tier       = $data['tier_data'];
    $sub_scores = $data['sub_scores'];
    $extraction = $data['extraction'];
    $fixes      = $data['fixes'];
    $competitor = $data['competitor'];
    $date       = $data['scanned_at'] ? date( 'F j, Y', strtotime( $data['scanned_at'] ) ) : date( 'F j, Y' );
    $domain     = aewp_format_domain( $data['url'] );

    // Agency branding
    $agency_name  = aewp_get_agency_name();
    $agency_logo  = aewp_get_agency_logo_url();
    $has_agency   = aewp_has_agency_branding();

    $brand_line = $has_agency ? esc_html( $agency_name ) : 'AnswerEngineWP';
    $footer_line = $has_agency
        ? 'Technology powered by AI Visibility Scanner &middot; answerenginewp.com'
        : 'Generated by AnswerEngineWP &middot; answerenginewp.com';

    $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #334155; line-height: 1.6; margin: 0; padding: 40px; }
        .page { page-break-after: always; padding: 40px 0; }
        .page:last-child { page-break-after: auto; }
        h1 { color: #0F172A; font-size: 28px; margin-bottom: 8px; }
        h2 { color: #0F172A; font-size: 22px; margin-top: 32px; margin-bottom: 16px; }
        h3 { color: #0F172A; font-size: 16px; margin-bottom: 8px; }
        .brand { font-size: 14px; font-weight: bold; margin-bottom: 8px; color: #2563EB; }
        .agency-header { margin-bottom: 32px; padding-bottom: 16px; border-bottom: 2px solid #E2E8F0; }
        .agency-name { font-size: 16px; font-weight: bold; color: #0F172A; }
        .agency-logo { max-height: 40px; margin-bottom: 8px; }
        .prepared-for { font-size: 13px; color: #64748B; margin-top: 4px; }
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

    // Agency header helper
    $agency_header_html = '<div class="agency-header">';
    if ( $has_agency ) {
        if ( $agency_logo ) {
            $agency_header_html .= '<img src="' . esc_url( $agency_logo ) . '" class="agency-logo">';
        }
        $agency_header_html .= '<div class="agency-name">' . esc_html( $agency_name ) . '</div>';
    } else {
        $agency_header_html .= '<div class="brand">AnswerEngineWP</div>';
    }
    $agency_header_html .= '</div>';

    // Page 1: Cover
    $html .= '<div class="page">';
    $html .= $agency_header_html;
    $html .= '<h1>AI Visibility Audit</h1>
        <p class="prepared-for">Prepared for: ' . esc_html( $domain ) . ' &bull; ' . $date . '</p>
        <div class="score-big">' . $score . '</div>
        <div class="tier-label">' . esc_html( $tier['label'] ) . '</div>
        <p class="tier-message">' . esc_html( $tier['message'] ) . '</p>
        <div class="footer-line">' . $footer_line . '</div>
    </div>';

    // Page 2: Score Breakdown
    $html .= '<div class="page">';
    $html .= $agency_header_html;
    $html .= '<h2>Score Breakdown</h2>';
    if ( is_array( $sub_scores ) ) {
        foreach ( $sub_scores as $key => $sub ) {
            if ( ! is_array( $sub ) ) continue;
            $html .= '<div class="sub-score">
                <span class="sub-score-label">' . esc_html( $sub['label'] ) . '</span>
                <span class="sub-score-value">' . intval( $sub['score'] ) . '/100</span>
            </div>';
        }
    }
    $html .= '<div class="footer-line">' . $footer_line . '</div></div>';

    // Page 3: Competitor Gap (if applicable)
    if ( ! empty( $competitor ) && is_array( $competitor ) ) {
        $html .= '<div class="page">';
        $html .= $agency_header_html;
        $html .= '<h2>Competitor Comparison</h2>';
        $html .= aewp_render_comparison_bars(
            array( 'score' => $score, 'sub_scores' => $sub_scores ),
            $competitor,
            esc_html( $domain ),
            isset( $competitor['url'] ) ? $competitor['url'] : 'Competitor'
        );
        $html .= '<div class="footer-line">' . $footer_line . '</div></div>';
    }

    // Page 4: Extraction Preview
    $html .= '<div class="page">';
    $html .= $agency_header_html;
    $html .= '<h2>What AI Can Extract</h2>';
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
        if ( ! empty( $extraction['missing'] ) ) {
            $html .= '<h3>Missing Signals</h3>';
            foreach ( $extraction['missing'] as $item ) {
                $html .= '<div class="extraction-item missing">&cross; ' . esc_html( $item ) . '</div>';
            }
        }
    }
    $html .= '<div class="footer-line">' . $footer_line . '</div></div>';

    // Page 5: Recommended Fixes
    $html .= '<div class="page">';
    $html .= $agency_header_html;
    $html .= '<h2>Recommended Fixes</h2>';
    if ( is_array( $fixes ) ) {
        foreach ( $fixes as $fix ) {
            if ( ! is_array( $fix ) ) continue;
            $html .= '<div class="fix">
                <div class="fix-points">+' . intval( $fix['points'] ) . ' pts</div>
                <div class="fix-title">' . esc_html( $fix['title'] ) . '</div>
                <div class="fix-desc">' . esc_html( $fix['description'] ) . '</div>
            </div>';
        }
    }
    $html .= '<div class="footer-line">' . $footer_line . '</div></div>';

    // Page 6: CTA
    $html .= '<div class="page">';
    $html .= $agency_header_html;
    $html .= '<div class="cta-box">
            <h2>Fix your AI visibility</h2>
            <p>Install AnswerEngineWP to automatically add AI-visible structure to your WordPress site.</p>
            <p style="margin-top:24px;font-weight:600;">https://wordpress.org/plugins/answerenginewp/</p>
        </div>
        <p style="text-align:center;margin-top:32px;color:#64748B;font-size:14px;">
            Run more audits: <strong>answerenginewp.com/scanner</strong>
        </p>
        <div class="footer-line">' . $footer_line . '</div>
    </div>';

    $html .= '</body></html>';

    return $html;
}
