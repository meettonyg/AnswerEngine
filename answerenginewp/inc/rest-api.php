<?php
/**
 * REST API Endpoints
 *
 * @package AnswerEngineWP
 */

// Register REST routes
function aewp_register_rest_routes() {
    // Scan endpoint
    register_rest_route( 'aewp/v1', '/scan', array(
        'methods'             => 'POST',
        'callback'            => 'aewp_handle_scan',
        'permission_callback' => '__return_true',
        'args'                => array(
            'url' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ),
            'competitor_url' => array(
                'required'          => false,
                'type'              => 'string',
                'sanitize_callback' => 'esc_url_raw',
            ),
        ),
    ) );

    // PDF report endpoint
    register_rest_route( 'aewp/v1', '/report/(?P<hash>[a-zA-Z0-9]+)', array(
        'methods'             => 'GET',
        'callback'            => 'aewp_handle_report',
        'permission_callback' => '__return_true',
        'args'                => array(
            'hash' => array(
                'required' => true,
                'type'     => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // Badge SVG endpoint
    register_rest_route( 'aewp/v1', '/badge/(?P<hash>[a-zA-Z0-9]+)\.svg', array(
        'methods'             => 'GET',
        'callback'            => 'aewp_handle_badge',
        'permission_callback' => '__return_true',
        'args'                => array(
            'hash' => array(
                'required' => true,
                'type'     => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'aewp_register_rest_routes' );

/**
 * Handle scan request
 */
function aewp_handle_scan( WP_REST_Request $request ) {
    $url = $request->get_param( 'url' );
    $competitor_url = $request->get_param( 'competitor_url' );

    if ( empty( $url ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'URL is required.',
        ), 400 );
    }

    // Rate limiting
    $rate_check = aewp_check_rate_limit();
    if ( is_wp_error( $rate_check ) ) {
        return new WP_REST_Response( array(
            'success'     => false,
            'message'     => $rate_check->get_error_message(),
            'retry_after' => $rate_check->get_error_data(),
        ), 429 );
    }

    // Check cache (24h)
    $cache_key = 'aewp_scan_' . md5( $url . $competitor_url );
    $cached = get_transient( $cache_key );
    if ( $cached ) {
        return new WP_REST_Response( $cached, 200 );
    }

    // Run scan
    $result = aewp_scan_url( $url );
    if ( is_wp_error( $result ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => $result->get_error_message(),
        ), 400 );
    }

    // Scan competitor if provided
    $competitor_data = null;
    if ( ! empty( $competitor_url ) ) {
        $competitor_result = aewp_scan_url( $competitor_url );
        if ( ! is_wp_error( $competitor_result ) ) {
            $competitor_data = array(
                'url'        => aewp_clean_url_for_display( $competitor_url ),
                'score'      => $competitor_result['score'],
                'tier'       => $competitor_result['tier'],
                'tier_label' => $competitor_result['tier_label'],
                'sub_scores' => $competitor_result['sub_scores'],
                'extraction' => $competitor_result['extraction'],
            );
        }
    }

    // Generate hash
    $hash = substr( md5( $url . time() . wp_rand() ), 0, 12 );

    // Store scan result as CPT
    $post_id = wp_insert_post( array(
        'post_type'   => 'aewp_scan',
        'post_title'  => aewp_clean_url_for_display( $url ) . ' — ' . $result['score'] . '/100',
        'post_status' => 'publish',
    ) );

    if ( $post_id ) {
        update_post_meta( $post_id, '_aewp_url', $url );
        update_post_meta( $post_id, '_aewp_score', $result['score'] );
        update_post_meta( $post_id, '_aewp_tier', $result['tier'] );
        update_post_meta( $post_id, '_aewp_sub_scores', $result['sub_scores'] );
        update_post_meta( $post_id, '_aewp_extraction_data', $result['extraction'] );
        update_post_meta( $post_id, '_aewp_fixes', $result['fixes'] );
        update_post_meta( $post_id, '_aewp_hash', $hash );
        update_post_meta( $post_id, '_aewp_scanned_at', current_time( 'mysql' ) );
        update_post_meta( $post_id, '_aewp_ip_hash', md5( aewp_get_client_ip() . AUTH_SALT ) );

        if ( $competitor_data ) {
            update_post_meta( $post_id, '_aewp_competitor_url', $competitor_url );
            update_post_meta( $post_id, '_aewp_competitor_score', $competitor_data['score'] );
            update_post_meta( $post_id, '_aewp_competitor_data', $competitor_data );
        }
    }

    // Build response
    $tier_data = aewp_get_tier( $result['score'] );

    $response = array(
        'success'            => true,
        'hash'               => $hash,
        'url'                => aewp_clean_url_for_display( $url ),
        'score'              => $result['score'],
        'tier'               => $tier_data['key'],
        'tier_label'         => $tier_data['label'],
        'tier_color'         => $tier_data['color'],
        'tier_message'       => $tier_data['message'],
        'sub_scores'         => $result['sub_scores'],
        'fixes'              => $result['fixes'],
        'projected_score'    => $result['projected_score'],
        'extraction'         => $result['extraction'],
        'citation_simulation' => $result['citation_simulation'],
        'competitor'         => $competitor_data,
        'share_url'          => home_url( '/score/' . $hash ),
        'pdf_url'            => rest_url( 'aewp/v1/report/' . $hash ),
    );

    // Cache for 24 hours
    set_transient( $cache_key, $response, DAY_IN_SECONDS );

    return new WP_REST_Response( $response, 200 );
}

/**
 * Handle PDF report request
 */
function aewp_handle_report( WP_REST_Request $request ) {
    $hash = $request->get_param( 'hash' );
    $scan = aewp_get_scan_by_hash( $hash );

    if ( ! $scan ) {
        return new WP_REST_Response( array( 'message' => 'Scan not found.' ), 404 );
    }

    $pdf_path = aewp_generate_pdf( $scan );

    if ( is_wp_error( $pdf_path ) ) {
        return new WP_REST_Response( array( 'message' => 'Could not generate report.' ), 500 );
    }

    // Serve the PDF
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="ai-visibility-report-' . $hash . '.pdf"' );
    header( 'Content-Length: ' . filesize( $pdf_path ) );
    readfile( $pdf_path );
    exit;
}

/**
 * Handle badge SVG request
 */
function aewp_handle_badge( WP_REST_Request $request ) {
    $hash = $request->get_param( 'hash' );
    $scan = aewp_get_scan_by_hash( $hash );

    if ( ! $scan ) {
        return new WP_REST_Response( array( 'message' => 'Scan not found.' ), 404 );
    }

    $score = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
    if ( $score < 70 ) {
        return new WP_REST_Response( array( 'message' => 'Badges are available for scores of 70 or above.' ), 403 );
    }
    $svg = aewp_generate_badge_svg( $score );

    header( 'Content-Type: image/svg+xml' );
    header( 'Cache-Control: public, max-age=86400' );
    echo $svg;
    exit;
}

/**
 * Clean URL for display (remove protocol, trailing slash)
 */
function aewp_clean_url_for_display( $url ) {
    $url = preg_replace( '#^https?://#i', '', $url );
    return rtrim( $url, '/' );
}

/**
 * Get client IP address
 */
function aewp_get_client_ip() {
    $ip = '';
    if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        $ip = trim( $ips[0] );
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }
    return $ip;
}
