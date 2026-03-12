<?php
/**
 * AI Visibility Scanner Theme Functions
 *
 * Presentation layer only — requires the AI Visibility Scanner plugin
 * for scanning, REST API, PDF reports, and all backend logic.
 *
 * @package AIVisibilityScanner
 */

// ─── Plugin Dependency Check ────────────────────────────────────────────────

/**
 * Check whether the AI Visibility Scanner plugin is active.
 *
 * @return bool
 */
function aivs_is_plugin_active() {
	return defined( 'AIVS_SCANNER_VERSION' );
}

/**
 * Show admin notice when the required plugin is not active.
 */
function aivs_plugin_missing_notice() {
	if ( aivs_is_plugin_active() ) {
		return;
	}
	echo '<div class="notice notice-warning"><p>';
	echo '<strong>AI Visibility Scanner Theme</strong> requires the <em>AI Visibility Scanner</em> plugin to be installed and activated for scanning functionality.';
	echo '</p></div>';
}
add_action( 'admin_notices', 'aivs_plugin_missing_notice' );

// ─── Theme Setup ────────────────────────────────────────────────────────────

function aivs_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

	register_nav_menus( array(
		'primary' => 'Primary Navigation',
	) );
}
add_action( 'after_setup_theme', 'aivs_setup' );

// ─── Enqueue Styles & Scripts ───────────────────────────────────────────────

function aivs_scripts() {
	// Google Fonts with preconnect.
	wp_enqueue_style(
		'aivs-fonts',
		'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=JetBrains+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap',
		array(),
		null
	);

	// Main stylesheet.
	wp_enqueue_style(
		'aivs-style',
		get_stylesheet_uri(),
		array( 'aivs-fonts' ),
		wp_get_theme()->get( 'Version' )
	);

	// Scanner script on front page and scan page.
	if ( is_front_page() || is_page( 'scan' ) || is_page_template( 'page-scan.php' ) ) {
		wp_enqueue_script(
			'aivs-scanner',
			get_template_directory_uri() . '/assets/js/scanner.js',
			array(),
			'1.0',
			true
		);
		wp_localize_script( 'aivs-scanner', 'aivsScanner', array(
			'apiUrl'       => rest_url( 'aivs/v1/scan' ),
			'emailUrl'     => rest_url( 'aivs/v1/email' ),
			'waitlistUrl'  => rest_url( 'aivs/v1/waitlist' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'siteUrl'      => home_url(),
			'tierConfig'   => function_exists( 'aivs_get_tier_config_for_js' ) ? aivs_get_tier_config_for_js() : array(),
		) );
	}

	// Dequeue jQuery on frontend (keep registered so core dependencies don't break).
	if ( ! is_admin() ) {
		wp_dequeue_script( 'jquery' );
	}
}
add_action( 'wp_enqueue_scripts', 'aivs_scripts' );

// Add preconnect for Google Fonts.
function aivs_preconnect_fonts() {
	echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
	echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'aivs_preconnect_fonts', 1 );

// Plausible Analytics — set AIVS_PLAUSIBLE_DOMAIN constant in wp-config.php to enable.
function aivs_plausible_analytics() {
	if ( ! defined( 'AIVS_PLAUSIBLE_DOMAIN' ) || empty( AIVS_PLAUSIBLE_DOMAIN ) ) {
		return;
	}
	$domain = esc_attr( AIVS_PLAUSIBLE_DOMAIN );
	echo '<script defer data-domain="' . $domain . '" src="https://plausible.io/js/script.js"></script>' . "\n";
}
add_action( 'wp_head', 'aivs_plausible_analytics', 2 );

// Remove WordPress emoji script.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Remove block library CSS.
function aivs_remove_block_css() {
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'wp-block-library-theme' );
	wp_dequeue_style( 'wc-blocks-style' );
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'aivs_remove_block_css', 100 );

// ─── Template Routing ───────────────────────────────────────────────────────

function aivs_template_redirect() {
	// Short redirect: /r/{domain} -> /report/{domain} (works without plugin).
	$short = get_query_var( 'aivs_short_redirect' );
	if ( $short ) {
		wp_redirect( home_url( '/report/' . $short ), 301 );
		exit;
	}

	// Everything below requires the scanner plugin.
	if ( ! function_exists( 'aivs_get_scan_by_hash' ) ) {
		return;
	}

	// Domain-based report: /report/{domain}.
	$domain = get_query_var( 'aivs_report_domain' );
	if ( $domain ) {
		include get_template_directory() . '/page-report.php';
		exit;
	}
	// Backward compat: /score/{hash} -> resolve and redirect.
	$hash = get_query_var( 'aivs_score_hash' );
	if ( $hash ) {
		$scan = aivs_get_scan_by_hash( $hash );
		if ( $scan ) {
			$url    = get_post_meta( $scan->ID, '_aivs_url', true );
			$domain = aivs_clean_url_for_display( $url );
			wp_redirect( home_url( '/report/' . $domain ), 301 );
			exit;
		}
		// Hash not found — show report page anyway (will handle 404).
		include get_template_directory() . '/page-report.php';
		exit;
	}
	if ( get_query_var( 'aivs_leaderboard' ) ) {
		include get_template_directory() . '/page-leaderboard.php';
		exit;
	}
}
add_action( 'template_redirect', 'aivs_template_redirect' );

// ─── Theme Includes ─────────────────────────────────────────────────────────

require_once get_template_directory() . '/inc/theme-setup.php';

// ─── SEO: JSON-LD & Meta Tags ───────────────────────────────────────────────

function aivs_homepage_jsonld() {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<script type="application/ld+json">
	{
		"@context": "https://schema.org",
		"@type": "WebApplication",
		"name": "AI Visibility Scanner",
		"applicationCategory": "WebApplication",
		"operatingSystem": "Any",
		"description": "Free AI Visibility Scanner. Check if your website is visible to ChatGPT, Perplexity, and Google AI Overviews. Get your score in under 10 seconds.",
		"url": "https://aivisibilityscanner.com",
		"offers": {
			"@type": "Offer",
			"price": "0",
			"priceCurrency": "USD",
			"name": "Free"
		}
	}
	</script>
	<?php
}
add_action( 'wp_head', 'aivs_homepage_jsonld' );

function aivs_meta_tags() {
	if ( is_front_page() ) {
		echo '<meta name="description" content="Free AI Visibility Score for any website. See what ChatGPT can extract from your site — and what it can\'t.">' . "\n";
		echo '<meta property="og:title" content="AI Visibility Scanner — Free AI Visibility Score for any website">' . "\n";
		echo '<meta property="og:description" content="Free AI Visibility Score for any website. See what ChatGPT can extract from your site — and what it can\'t.">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-image-1200x630.png' ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		echo '<meta name="twitter:title" content="Is your website invisible to ChatGPT?">' . "\n";
		echo '<meta name="twitter:description" content="Free AI Visibility Score for any website. Find out in 10 seconds.">' . "\n";
		echo '<meta name="twitter:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-image-1200x630.png' ) . '">' . "\n";
	} elseif ( is_page( 'scan' ) || is_page_template( 'page-scan.php' ) ) {
		echo '<meta name="description" content="Free AI Visibility Score for any website. Enter your URL and see what AI systems can extract from your site in under 10 seconds.">' . "\n";
		echo '<meta property="og:title" content="AI Visibility Scanner — Scan any site free">' . "\n";
		echo '<meta property="og:description" content="Enter any URL. Get your AI Visibility Score in under 10 seconds. Free, no login required.">' . "\n";
		echo '<meta property="og:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-scanner-1200x630.png' ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}
}
add_action( 'wp_head', 'aivs_meta_tags' );

// Custom title for report pages.
function aivs_report_page_title( $title ) {
	// Requires plugin for database lookups.
	if ( ! function_exists( 'aivs_get_latest_scan_for_domain' ) ) {
		return $title;
	}
	$domain = get_query_var( 'aivs_report_domain' );
	if ( $domain ) {
		$scan = aivs_get_latest_scan_for_domain( $domain );
		if ( $scan ) {
			$score = get_post_meta( $scan->ID, '_aivs_score', true );
			return esc_html( $domain ) . ' scored ' . intval( $score ) . '/100 — AI Visibility Score';
		}
		return esc_html( $domain ) . ' — AI Visibility Report';
	}
	$hash = get_query_var( 'aivs_score_hash' );
	if ( $hash ) {
		$scan = aivs_get_scan_by_hash( $hash );
		if ( $scan ) {
			$url   = get_post_meta( $scan->ID, '_aivs_url', true );
			$score = get_post_meta( $scan->ID, '_aivs_score', true );
			return esc_html( $url ) . ' scored ' . intval( $score ) . '/100 — AI Visibility Score';
		}
	}
	return $title;
}
add_filter( 'pre_get_document_title', 'aivs_report_page_title' );
