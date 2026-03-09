<?php
/**
 * Bulk Scanner Admin Page
 *
 * Adds a WP admin page under Scan Results for scanning multiple URLs at once.
 * Uses AJAX to scan URLs sequentially and aivs_save_scan_result() to store results.
 *
 * @package AIVisibilityScanner
 */

/**
 * Register admin menu page.
 */
function aivs_bulk_scanner_menu() {
    add_submenu_page(
        'edit.php?post_type=aivs_scan',
        'Bulk Scanner',
        'Bulk Scanner',
        'manage_options',
        'aivs-bulk-scanner',
        'aivs_bulk_scanner_page'
    );
}
add_action( 'admin_menu', 'aivs_bulk_scanner_menu' );

/**
 * Enqueue admin scripts on the bulk scanner page only.
 */
function aivs_bulk_scanner_scripts( $hook ) {
    if ( 'aivs_scan_page_aivs-bulk-scanner' !== $hook ) {
        return;
    }
    wp_enqueue_script(
        'aivs-admin-bulk-scanner',
        get_template_directory_uri() . '/assets/js/admin-bulk-scanner.js',
        array(),
        '1.0',
        true
    );
    wp_localize_script( 'aivs-admin-bulk-scanner', 'aivsBulk', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'nonce'   => wp_create_nonce( 'aivs_bulk_scan' ),
        'siteUrl' => home_url(),
    ) );
}
add_action( 'admin_enqueue_scripts', 'aivs_bulk_scanner_scripts' );

/**
 * Render the bulk scanner admin page.
 */
function aivs_bulk_scanner_page() {
    ?>
    <div class="wrap">
        <h1>Bulk Scanner</h1>
        <p>Paste up to 50 URLs (one per line) and scan them all at once. Results are saved to the Scan Results database.</p>

        <div id="aivsBulkForm">
            <textarea id="aivsBulkUrls" rows="10" style="width:100%;max-width:700px;font-family:monospace;font-size:13px;" placeholder="https://example.com&#10;https://anothersite.com&#10;https://thirdsite.org"></textarea>
            <br><br>
            <button type="button" id="aivsBulkStart" class="button button-primary button-hero">Scan All &rarr;</button>
            <button type="button" id="aivsBulkStop" class="button button-secondary" style="display:none;margin-left:8px;">Stop</button>
        </div>

        <!-- Progress -->
        <div id="aivsBulkProgress" style="display:none;margin:24px 0 16px;">
            <div style="background:#ddd;border-radius:4px;height:24px;max-width:700px;overflow:hidden;">
                <div id="aivsBulkBar" style="background:#2271b1;height:100%;width:0%;transition:width 0.3s;border-radius:4px;"></div>
            </div>
            <p id="aivsBulkStatus" style="margin-top:8px;"></p>
        </div>

        <!-- Results table -->
        <div id="aivsBulkResults" style="display:none;margin-top:24px;">
            <h2>Results</h2>
            <table class="wp-list-table widefat fixed striped" style="max-width:900px;">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Domain</th>
                        <th style="width:80px;">Score</th>
                        <th style="width:160px;">Tier</th>
                        <th style="width:120px;">Status</th>
                        <th style="width:100px;">Report</th>
                    </tr>
                </thead>
                <tbody id="aivsBulkBody"></tbody>
            </table>
            <br>
            <button type="button" id="aivsBulkExport" class="button button-secondary">Export CSV</button>
        </div>
    </div>
    <?php
}

/**
 * AJAX handler: scan a single URL (called sequentially by JS).
 */
function aivs_ajax_bulk_scan_single() {
    check_ajax_referer( 'aivs_bulk_scan', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( array( 'message' => 'Unauthorized.' ), 403 );
    }

    $url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
    if ( empty( $url ) ) {
        wp_send_json_error( array( 'message' => 'No URL provided.' ) );
    }

    // Run scan
    $result = aivs_scan_url( $url );
    if ( is_wp_error( $result ) ) {
        wp_send_json_error( array( 'message' => $result->get_error_message() ) );
    }

    // Save to CPT
    $saved = aivs_save_scan_result( $url, $result );
    if ( is_wp_error( $saved ) ) {
        wp_send_json_error( array( 'message' => $saved->get_error_message() ) );
    }

    $tier = aivs_get_tier( $result['score'] );
    $domain = aivs_format_domain( $url );

    wp_send_json_success( array(
        'url'        => $url,
        'domain'     => $domain,
        'score'      => $result['score'],
        'tier_label' => $tier['label'],
        'tier_color' => $tier['color'],
        'hash'       => $saved['hash'],
        'report_url' => home_url( '/report/' . $domain ),
    ) );
}
add_action( 'wp_ajax_aivs_bulk_scan_single', 'aivs_ajax_bulk_scan_single' );
