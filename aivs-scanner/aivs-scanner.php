<?php
/**
 * Plugin Name: AI Visibility Scanner
 * Plugin URI:  https://aivisibilityscanner.com
 * Description: AI Visibility scanning engine — REST API, scoring, PDF reports, badges, and leaderboards. Required by the AI Visibility Scanner theme.
 * Version:     1.0.0
 * Author:      AnswerEngineWP
 * Author URI:  https://answerenginewp.com
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: aivs-scanner
 *
 * @package AIVSScanner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'AIVS_SCANNER_VERSION', '1.0.0' );
define( 'AIVS_SCANNER_DIR', plugin_dir_path( __FILE__ ) );
define( 'AIVS_SCANNER_URL', plugin_dir_url( __FILE__ ) );

// Composer autoloader (Dompdf).
$aivs_autoloader = AIVS_SCANNER_DIR . 'vendor/autoload.php';
if ( file_exists( $aivs_autoloader ) ) {
	require_once $aivs_autoloader;
}

// ─── Include files (load order matters) ─────────────────────────────────────

require_once AIVS_SCANNER_DIR . 'inc/score-tiers.php';
require_once AIVS_SCANNER_DIR . 'inc/visual-utils.php';
require_once AIVS_SCANNER_DIR . 'inc/rate-limiter.php';
require_once AIVS_SCANNER_DIR . 'inc/scanner-engine.php';
require_once AIVS_SCANNER_DIR . 'inc/pdf-generator.php';
require_once AIVS_SCANNER_DIR . 'inc/badge-generator.php';
require_once AIVS_SCANNER_DIR . 'inc/og-image-generator.php';
require_once AIVS_SCANNER_DIR . 'inc/comparison-renderer.php';
require_once AIVS_SCANNER_DIR . 'inc/leaderboard.php';
require_once AIVS_SCANNER_DIR . 'inc/leaderboard-graphic.php';
require_once AIVS_SCANNER_DIR . 'inc/rest-api.php';
require_once AIVS_SCANNER_DIR . 'inc/admin-bulk-scanner.php';

// ─── Custom Post Type ───────────────────────────────────────────────────────

/**
 * Register the aivs_scan CPT for storing scan results.
 */
function aivs_register_scan_results() {
	register_post_type( 'aivs_scan', array(
		'public'       => false,
		'show_ui'      => true,
		'show_in_menu' => true,
		'menu_icon'    => 'dashicons-chart-bar',
		'labels'       => array(
			'name'          => 'Scan Results',
			'singular_name' => 'Scan Result',
		),
		'supports'     => array( 'title' ),
	) );
}
add_action( 'init', 'aivs_register_scan_results' );

// ─── Rewrite Rules ──────────────────────────────────────────────────────────

/**
 * Custom rewrite rules for scanner URL patterns.
 */
function aivs_rewrite_rules() {
	// /report/{domain} — full report page (domain-based lookup).
	add_rewrite_rule(
		'^report/(.+)/?$',
		'index.php?aivs_report_domain=$matches[1]',
		'top'
	);
	// /r/{domain} — short share link, redirects to /report/{domain}.
	add_rewrite_rule(
		'^r/(.+)/?$',
		'index.php?aivs_short_redirect=$matches[1]',
		'top'
	);
	// /score/{hash} — backward compatibility (old hash-based URLs).
	add_rewrite_rule(
		'^score/([a-zA-Z0-9]+)/?$',
		'index.php?aivs_score_hash=$matches[1]',
		'top'
	);
	// /leaderboard.
	add_rewrite_rule(
		'^leaderboard/?$',
		'index.php?aivs_leaderboard=1',
		'top'
	);
}
add_action( 'init', 'aivs_rewrite_rules' );

/**
 * Register custom query vars.
 */
function aivs_query_vars( $vars ) {
	$vars[] = 'aivs_report_domain';
	$vars[] = 'aivs_short_redirect';
	$vars[] = 'aivs_score_hash';
	$vars[] = 'aivs_leaderboard';
	return $vars;
}
add_filter( 'query_vars', 'aivs_query_vars' );

// ─── Data-Access Bridge Functions ───────────────────────────────────────────

/**
 * Get the latest scan for a domain.
 *
 * @param  string        $domain Domain to look up (without protocol).
 * @return WP_Post|null  Scan post or null.
 */
function aivs_get_latest_scan_for_domain( $domain ) {
	$domain = sanitize_text_field( $domain );
	// Try exact match with https.
	$scans = get_posts( array(
		'post_type'   => 'aivs_scan',
		'meta_key'    => '_aivs_url',
		'meta_value'  => 'https://' . $domain,
		'numberposts' => 1,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'post_status' => 'publish',
	) );
	if ( ! empty( $scans ) ) {
		return $scans[0];
	}
	// Try with http.
	$scans = get_posts( array(
		'post_type'   => 'aivs_scan',
		'meta_key'    => '_aivs_url',
		'meta_value'  => 'http://' . $domain,
		'numberposts' => 1,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'post_status' => 'publish',
	) );
	return ! empty( $scans ) ? $scans[0] : null;
}

/**
 * Get scan post by hash.
 *
 * @param  string        $hash Scan hash.
 * @return WP_Post|null  Scan post or null.
 */
function aivs_get_scan_by_hash( $hash ) {
	$hash  = sanitize_text_field( $hash );
	$scans = get_posts( array(
		'post_type'   => 'aivs_scan',
		'meta_key'    => '_aivs_hash',
		'meta_value'  => $hash,
		'numberposts' => 1,
		'post_status' => 'publish',
	) );
	return ! empty( $scans ) ? $scans[0] : null;
}

// ─── Activation / Deactivation ──────────────────────────────────────────────

register_activation_hook( __FILE__, function () {
	aivs_register_scan_results();
	aivs_rewrite_rules();
	flush_rewrite_rules();
} );

register_deactivation_hook( __FILE__, function () {
	flush_rewrite_rules();
} );
