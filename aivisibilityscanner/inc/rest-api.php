<?php
/**
 * REST API Endpoints
 *
 * @package AIVisibilityScanner
 */

// Register REST routes
function aivs_register_rest_routes() {
    // Scan endpoint
    register_rest_route( 'aivs/v1', '/scan', array(
        'methods'             => 'POST',
        'callback'            => 'aivs_handle_scan',
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
    register_rest_route( 'aivs/v1', '/report/(?P<hash>[a-zA-Z0-9]+)', array(
        'methods'             => 'GET',
        'callback'            => 'aivs_handle_report',
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
    register_rest_route( 'aivs/v1', '/badge/(?P<hash>[a-zA-Z0-9]+)\.svg', array(
        'methods'             => 'GET',
        'callback'            => 'aivs_handle_badge',
        'permission_callback' => '__return_true',
        'args'                => array(
            'hash' => array(
                'required' => true,
                'type'     => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // Comparison SVG endpoint
    register_rest_route( 'aivs/v1', '/score/(?P<hash>[a-zA-Z0-9]+)/comparison\.svg', array(
        'methods'             => 'GET',
        'callback'            => 'aivs_handle_comparison_svg',
        'permission_callback' => '__return_true',
        'args'                => array(
            'hash' => array(
                'required' => true,
                'type'     => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );

    // Leaderboard endpoint
    register_rest_route( 'aivs/v1', '/leaderboard', array(
        'methods'             => 'GET',
        'callback'            => 'aivs_handle_leaderboard',
        'permission_callback' => '__return_true',
    ) );

    // Leaderboard graphic endpoint
    register_rest_route( 'aivs/v1', '/leaderboard/graphic', array(
        'methods'             => 'GET',
        'callback'            => 'aivs_handle_leaderboard_graphic',
        'permission_callback' => '__return_true',
    ) );

    // Email capture endpoint
    register_rest_route( 'aivs/v1', '/email', array(
        'methods'             => 'POST',
        'callback'            => 'aivs_handle_email_capture',
        'permission_callback' => '__return_true',
        'args'                => array(
            'email' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_email',
            ),
            'hash' => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
            ),
        ),
    ) );
}
add_action( 'rest_api_init', 'aivs_register_rest_routes' );

/**
 * Handle scan request
 */
function aivs_handle_scan( WP_REST_Request $request ) {
    $url = $request->get_param( 'url' );
    $competitor_url = $request->get_param( 'competitor_url' );

    if ( empty( $url ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'URL is required.',
        ), 400 );
    }

    // Rate limiting
    $rate_check = aivs_check_rate_limit();
    if ( is_wp_error( $rate_check ) ) {
        return new WP_REST_Response( array(
            'success'     => false,
            'message'     => $rate_check->get_error_message(),
            'retry_after' => $rate_check->get_error_data(),
        ), 429 );
    }

    // Check cache (24h)
    $cache_key = 'aivs_scan_' . md5( $url . $competitor_url );
    $cached = get_transient( $cache_key );
    if ( $cached ) {
        return new WP_REST_Response( $cached, 200 );
    }

    // Run scan
    $result = aivs_scan_url( $url );
    if ( is_wp_error( $result ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => $result->get_error_message(),
        ), 400 );
    }

    // Scan competitor if provided
    $competitor_data = null;
    if ( ! empty( $competitor_url ) ) {
        $competitor_result = aivs_scan_url( $competitor_url );
        if ( ! is_wp_error( $competitor_result ) ) {
            $competitor_data = array(
                'url'        => aivs_clean_url_for_display( $competitor_url ),
                'score'      => $competitor_result['score'],
                'tier'       => $competitor_result['tier'],
                'tier_label' => $competitor_result['tier_label'],
                'sub_scores' => $competitor_result['sub_scores'],
                'extraction' => $competitor_result['extraction'],
            );
        }
    }

    // Save scan result to CPT (creates or updates existing record)
    $saved = aivs_save_scan_result( $url, $result, $competitor_data );
    if ( is_wp_error( $saved ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => $saved->get_error_message(),
        ), 500 );
    }
    $post_id = $saved['post_id'];
    $hash    = $saved['hash'];

    // Build response
    $tier_data = aivs_get_tier( $result['score'] );

    $domain = aivs_format_domain( $url );

    $response = array(
        'success'            => true,
        'scan_id'            => (string) $post_id,
        'hash'               => $hash,
        'url'                => aivs_clean_url_for_display( $url ),
        'domain'             => $domain,
        'scan_timestamp'     => gmdate( 'c' ),
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
        'is_public'          => true,
        'share_url'          => home_url( '/report/' . $domain ),
        'public_url'         => home_url( '/report/' . $domain ),
        'pdf_url'            => rest_url( 'aivs/v1/report/' . $hash ),
        'badge_url'          => rest_url( 'aivs/v1/badge/' . $hash . '.svg' ),
    );

    // Cache for 24 hours
    set_transient( $cache_key, $response, DAY_IN_SECONDS );

    return new WP_REST_Response( $response, 200 );
}

/**
 * Handle PDF report request
 */
function aivs_handle_report( WP_REST_Request $request ) {
    $hash = $request->get_param( 'hash' );
    $scan = aivs_get_scan_by_hash( $hash );

    if ( ! $scan ) {
        return new WP_REST_Response( array( 'message' => 'Scan not found.' ), 404 );
    }

    $pdf_path = aivs_generate_pdf( $scan );

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
 * Handle badge SVG request.
 *
 * Supports variant query param: inline (default), social, small.
 */
function aivs_handle_badge( WP_REST_Request $request ) {
    $hash = $request->get_param( 'hash' );
    $scan = aivs_get_scan_by_hash( $hash );

    if ( ! $scan ) {
        return new WP_REST_Response( array( 'message' => 'Scan not found.' ), 404 );
    }

    $score   = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
    $url     = get_post_meta( $scan->ID, '_aivs_url', true );
    $variant = isset( $_GET['variant'] ) ? sanitize_text_field( $_GET['variant'] ) : 'inline';

    $allowed_variants = array( 'inline', 'social', 'small' );
    if ( ! in_array( $variant, $allowed_variants, true ) ) {
        $variant = 'inline';
    }

    $svg = aivs_generate_badge_svg( array(
        'score' => $score,
        'url'   => $url,
    ), $variant );

    header( 'Content-Type: image/svg+xml' );
    header( 'Cache-Control: public, max-age=86400' );
    echo $svg;
    exit;
}

/**
 * Clean URL for display (remove protocol, trailing slash)
 */
function aivs_clean_url_for_display( $url ) {
    $url = preg_replace( '#^https?://#i', '', $url );
    return rtrim( $url, '/' );
}

/**
 * Get client IP address
 */
function aivs_get_client_ip() {
    $ip = '';
    if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        $ips = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) );
        $ip = trim( $ips[0] );
    } elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
        $ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
    }
    return $ip;
}

/**
 * Get previous scan for a URL (for before/after comparison).
 *
 * @param string $url     The scanned URL.
 * @param int    $exclude Post ID to exclude (current scan).
 * @return WP_Post|null Previous scan post or null.
 */
function aivs_get_previous_scan_for_url( $url, $exclude = 0 ) {
    $args = array(
        'post_type'   => 'aivs_scan',
        'meta_key'    => '_aivs_url',
        'meta_value'  => $url,
        'numberposts' => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_status' => 'publish',
    );
    if ( $exclude ) {
        $args['post__not_in'] = array( $exclude );
    }
    $scans = get_posts( $args );
    return ! empty( $scans ) ? $scans[0] : null;
}

/**
 * Handle email capture request.
 *
 * Stores email against the scan result and optionally
 * triggers wp_mail with the PDF report link.
 */
function aivs_handle_email_capture( WP_REST_Request $request ) {
    $email = $request->get_param( 'email' );
    $hash  = $request->get_param( 'hash' );

    if ( ! is_email( $email ) ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Please enter a valid email address.',
        ), 400 );
    }

    $scan = aivs_get_scan_by_hash( $hash );
    if ( ! $scan ) {
        return new WP_REST_Response( array(
            'success' => false,
            'message' => 'Scan not found.',
        ), 404 );
    }

    // Store email (hashed with salt for privacy, raw for sending)
    update_post_meta( $scan->ID, '_aivs_email', sanitize_email( $email ) );
    $salt = defined( 'AUTH_SALT' ) ? AUTH_SALT : 'aivs-email-salt';
    update_post_meta( $scan->ID, '_aivs_email_hash', hash( 'sha256', strtolower( trim( $email ) ) . $salt ) );

    // Send report email
    $url       = get_post_meta( $scan->ID, '_aivs_url', true );
    $score     = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
    $tier_data = aivs_get_tier( $score );
    $fixes     = get_post_meta( $scan->ID, '_aivs_fixes', true );
    $pdf_url   = rest_url( 'aivs/v1/report/' . $hash );
    $score_url = home_url( '/score/' . $hash );

    $subject = 'Your AI Visibility Report: ' . aivs_clean_url_for_display( $url ) . ' scored ' . $score . '/100';

    $body  = "AI Visibility Audit Report\n";
    $body .= "==========================\n\n";
    $body .= "URL: " . $url . "\n";
    $body .= "Score: " . $score . "/100 — " . $tier_data['label'] . "\n\n";
    $body .= $tier_data['message'] . "\n\n";

    if ( is_array( $fixes ) && ! empty( $fixes ) ) {
        $body .= "Top 3 Recommended Fixes:\n";
        foreach ( $fixes as $i => $fix ) {
            if ( ! is_array( $fix ) ) continue;
            $body .= ( $i + 1 ) . ". " . $fix['title'] . " (+" . $fix['points'] . " pts)\n";
            $body .= "   " . $fix['description'] . "\n\n";
        }
    }

    $body .= "Download your full PDF report:\n" . $pdf_url . "\n\n";
    $body .= "View and share your score:\n" . $score_url . "\n\n";
    $body .= "---\n";
    $body .= "Improve your score with AnswerEngineWP (free WordPress plugin):\n";
    $body .= "https://wordpress.org/plugins/answerenginewp/\n";
    $body .= "\nPowered by AI Visibility Scanner — aivisibilityscanner.com\n";

    $headers = array( 'Content-Type: text/plain; charset=UTF-8' );

    wp_mail( $email, $subject, $body, $headers );

    return new WP_REST_Response( array(
        'success' => true,
        'message' => 'Report sent to ' . $email,
    ), 200 );
}

/**
 * Handle comparison SVG request.
 */
function aivs_handle_comparison_svg( WP_REST_Request $request ) {
    $hash = $request->get_param( 'hash' );
    $scan = aivs_get_scan_by_hash( $hash );

    if ( ! $scan ) {
        return new WP_REST_Response( array( 'message' => 'Scan not found.' ), 404 );
    }

    $competitor = get_post_meta( $scan->ID, '_aivs_competitor_data', true );
    if ( empty( $competitor ) || ! is_array( $competitor ) ) {
        return new WP_REST_Response( array( 'message' => 'No competitor data for this scan.' ), 404 );
    }

    $score      = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
    $url        = get_post_meta( $scan->ID, '_aivs_url', true );
    $sub_scores = get_post_meta( $scan->ID, '_aivs_sub_scores', true );

    $svg = aivs_generate_comparison_svg(
        array( 'score' => $score, 'sub_scores' => $sub_scores ),
        $competitor,
        aivs_format_domain( $url ),
        isset( $competitor['url'] ) ? $competitor['url'] : 'Competitor'
    );

    header( 'Content-Type: image/svg+xml' );
    header( 'Cache-Control: public, max-age=86400' );
    echo $svg;
    exit;
}

/**
 * Handle leaderboard JSON request.
 */
function aivs_handle_leaderboard( WP_REST_Request $request ) {
    $segment = isset( $_GET['segment'] ) ? sanitize_text_field( $_GET['segment'] ) : '';
    $limit   = isset( $_GET['limit'] ) ? min( 100, max( 1, intval( $_GET['limit'] ) ) ) : 20;

    $entries = aivs_get_leaderboard( $segment, $limit );

    $title = $segment
        ? 'Top ' . count( $entries ) . ' ' . ucfirst( $segment ) . ' Sites by AI Visibility'
        : 'Top ' . count( $entries ) . ' Sites by AI Visibility';

    return new WP_REST_Response( array(
        'title'   => $title,
        'segment' => $segment,
        'entries' => $entries,
    ), 200 );
}

/**
 * Handle leaderboard graphic SVG request.
 */
function aivs_handle_leaderboard_graphic( WP_REST_Request $request ) {
    $variant = isset( $_GET['variant'] ) ? sanitize_text_field( $_GET['variant'] ) : 'social';
    $limit   = isset( $_GET['limit'] ) ? min( 50, max( 1, intval( $_GET['limit'] ) ) ) : 10;
    $segment = isset( $_GET['segment'] ) ? sanitize_text_field( $_GET['segment'] ) : '';

    $entries = aivs_get_leaderboard( $segment, $limit );

    $title = $segment
        ? 'Top ' . count( $entries ) . ' ' . ucfirst( $segment ) . ' Sites by AI Visibility'
        : 'Top ' . count( $entries ) . ' Sites by AI Visibility';

    $svg = aivs_generate_leaderboard_svg( $entries, $title, $variant );

    header( 'Content-Type: image/svg+xml' );
    header( 'Cache-Control: public, max-age=3600' );
    echo $svg;
    exit;
}

/**
 * Save a scan result to the CPT. Creates a new post or updates an existing one for the same URL.
 *
 * @param string     $url             The scanned URL.
 * @param array      $result          Scan result from aivs_scan_url().
 * @param array|null $competitor_data Optional competitor data.
 * @return array|WP_Error Array with 'post_id' and 'hash' on success, WP_Error on failure.
 */
function aivs_save_scan_result( $url, $result, $competitor_data = null ) {
    // Check for existing scan — update instead of creating duplicate
    $existing_scan = aivs_get_previous_scan_for_url( $url );
    if ( $existing_scan ) {
        $post_id = $existing_scan->ID;
        $hash    = get_post_meta( $post_id, '_aivs_hash', true );

        // Store before-score for improvement tracking
        $before_score = intval( get_post_meta( $post_id, '_aivs_score', true ) );
        update_post_meta( $post_id, '_aivs_before_score', $before_score );
        update_post_meta( $post_id, '_aivs_before_tier', get_post_meta( $post_id, '_aivs_tier', true ) );

        wp_update_post( array(
            'ID'         => $post_id,
            'post_title' => aivs_clean_url_for_display( $url ) . ' — ' . $result['score'] . '/100',
        ) );
    } else {
        $hash = substr( md5( $url . time() . wp_rand() ), 0, 12 );

        $post_id = wp_insert_post( array(
            'post_type'   => 'aivs_scan',
            'post_title'  => aivs_clean_url_for_display( $url ) . ' — ' . $result['score'] . '/100',
            'post_status' => 'publish',
        ), true );

        if ( is_wp_error( $post_id ) ) {
            return new WP_Error( 'save_failed', 'Could not save scan results.' );
        }

        update_post_meta( $post_id, '_aivs_hash', $hash );
    }

    update_post_meta( $post_id, '_aivs_url', $url );
    update_post_meta( $post_id, '_aivs_score', $result['score'] );
    update_post_meta( $post_id, '_aivs_tier', $result['tier'] );
    update_post_meta( $post_id, '_aivs_sub_scores', $result['sub_scores'] );
    update_post_meta( $post_id, '_aivs_extraction_data', $result['extraction'] );
    update_post_meta( $post_id, '_aivs_fixes', $result['fixes'] );
    update_post_meta( $post_id, '_aivs_scanned_at', current_time( 'mysql' ) );
    update_post_meta( $post_id, '_aivs_ip_hash', md5( aivs_get_client_ip() . AUTH_SALT ) );

    if ( $competitor_data ) {
        update_post_meta( $post_id, '_aivs_competitor_url', $competitor_data['url'] ?? '' );
        update_post_meta( $post_id, '_aivs_competitor_score', $competitor_data['score'] ?? 0 );
        update_post_meta( $post_id, '_aivs_competitor_data', $competitor_data );
    }

    return array( 'post_id' => $post_id, 'hash' => $hash );
}
