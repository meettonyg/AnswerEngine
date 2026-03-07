<?php
/**
 * AnswerEngineWP Theme Functions
 *
 * @package AnswerEngineWP
 */

// Theme setup
function aewp_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );

    register_nav_menus( array(
        'primary' => 'Primary Navigation',
    ) );
}
add_action( 'after_setup_theme', 'aewp_setup' );

// Enqueue styles and scripts
function aewp_scripts() {
    // Google Fonts with preconnect
    wp_enqueue_style(
        'aewp-fonts',
        'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=JetBrains+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap',
        array(),
        null
    );

    // Main stylesheet
    wp_enqueue_style(
        'aewp-style',
        get_stylesheet_uri(),
        array( 'aewp-fonts' ),
        wp_get_theme()->get( 'Version' )
    );

    // Page-specific scripts
    if ( is_front_page() ) {
        wp_enqueue_script(
            'aewp-home',
            get_template_directory_uri() . '/assets/js/home.js',
            array(),
            '1.0',
            true
        );
        wp_localize_script( 'aewp-home', 'aewpHome', array(
            'scannerUrl' => home_url( '/scanner/' ),
        ) );
    }

    if ( is_page( 'scanner' ) || is_page_template( 'page-scanner.php' ) ) {
        wp_enqueue_script(
            'aewp-scanner',
            get_template_directory_uri() . '/assets/js/scanner.js',
            array(),
            '1.0',
            true
        );
        wp_localize_script( 'aewp-scanner', 'aewpScanner', array(
            'apiUrl'     => rest_url( 'aewp/v1/scan' ),
            'emailUrl'   => rest_url( 'aewp/v1/email' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'siteUrl'    => home_url(),
            'tierConfig' => aewp_get_tier_config_for_js(),
        ) );
    }

    // Dequeue jQuery on frontend
    if ( ! is_admin() ) {
        wp_deregister_script( 'jquery' );
    }
}
add_action( 'wp_enqueue_scripts', 'aewp_scripts' );

// Add preconnect for Google Fonts
function aewp_preconnect_fonts() {
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
}
add_action( 'wp_head', 'aewp_preconnect_fonts', 1 );

// Plausible Analytics — set AEWP_PLAUSIBLE_DOMAIN constant in wp-config.php to enable
function aewp_plausible_analytics() {
    if ( ! defined( 'AEWP_PLAUSIBLE_DOMAIN' ) || empty( AEWP_PLAUSIBLE_DOMAIN ) ) {
        return;
    }
    $domain = esc_attr( AEWP_PLAUSIBLE_DOMAIN );
    echo '<script defer data-domain="' . $domain . '" src="https://plausible.io/js/script.js"></script>' . "\n";
}
add_action( 'wp_head', 'aewp_plausible_analytics', 2 );

// Remove WordPress emoji script
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );

// Remove block library CSS
function aewp_remove_block_css() {
    wp_dequeue_style( 'wp-block-library' );
    wp_dequeue_style( 'wp-block-library-theme' );
    wp_dequeue_style( 'wc-blocks-style' );
    wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'aewp_remove_block_css', 100 );

// Register CPT for storing scan results
function aewp_register_scan_results() {
    register_post_type( 'aewp_scan', array(
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
add_action( 'init', 'aewp_register_scan_results' );

// Custom rewrite rules for /score/{hash} and /leaderboard/ URLs
function aewp_rewrite_rules() {
    add_rewrite_rule(
        '^score/([a-zA-Z0-9]+)/?$',
        'index.php?aewp_score_hash=$1',
        'top'
    );
    add_rewrite_rule(
        '^leaderboard/?$',
        'index.php?aewp_leaderboard=1',
        'top'
    );
}
add_action( 'init', 'aewp_rewrite_rules' );

function aewp_query_vars( $vars ) {
    $vars[] = 'aewp_score_hash';
    $vars[] = 'aewp_leaderboard';
    return $vars;
}
add_filter( 'query_vars', 'aewp_query_vars' );

function aewp_template_redirect() {
    $hash = get_query_var( 'aewp_score_hash' );
    if ( $hash ) {
        include get_template_directory() . '/page-score-result.php';
        exit;
    }
    if ( get_query_var( 'aewp_leaderboard' ) ) {
        include get_template_directory() . '/page-leaderboard.php';
        exit;
    }
}
add_action( 'template_redirect', 'aewp_template_redirect' );

// Load includes
require_once get_template_directory() . '/inc/score-tiers.php';
require_once get_template_directory() . '/inc/visual-utils.php';
require_once get_template_directory() . '/inc/theme-setup.php';
require_once get_template_directory() . '/inc/rest-api.php';
require_once get_template_directory() . '/inc/scanner-engine.php';
require_once get_template_directory() . '/inc/pdf-generator.php';
require_once get_template_directory() . '/inc/badge-generator.php';
require_once get_template_directory() . '/inc/og-image-generator.php';
require_once get_template_directory() . '/inc/rate-limiter.php';
require_once get_template_directory() . '/inc/comparison-renderer.php';
require_once get_template_directory() . '/inc/leaderboard.php';
require_once get_template_directory() . '/inc/leaderboard-graphic.php';

// JSON-LD for homepage
function aewp_homepage_jsonld() {
    if ( ! is_front_page() ) {
        return;
    }
    ?>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "SoftwareApplication",
        "name": "AnswerEngineWP",
        "applicationCategory": "WebApplication",
        "operatingSystem": "WordPress",
        "description": "AI Visibility plugin for WordPress. Make your site readable, extractable, and citable by ChatGPT, Perplexity, and Google AI Overviews.",
        "url": "https://answerenginewp.com",
        "offers": [
            { "@type": "Offer", "price": "0", "priceCurrency": "USD", "name": "Free" },
            { "@type": "Offer", "price": "49", "priceCurrency": "USD", "name": "Pro" },
            { "@type": "Offer", "price": "199", "priceCurrency": "USD", "name": "Agency" }
        ]
    }
    </script>
    <?php
}
add_action( 'wp_head', 'aewp_homepage_jsonld' );

// Custom meta tags
function aewp_meta_tags() {
    if ( is_front_page() ) {
        echo '<meta name="description" content="Free AI Visibility Score for any website. See what ChatGPT can extract from your site — and what it can\'t.">' . "\n";
        echo '<meta property="og:title" content="Is your website invisible to ChatGPT? &middot; AnswerEngineWP">' . "\n";
        echo '<meta property="og:description" content="Free AI Visibility Score for any website. See what ChatGPT can extract from your site — and what it can\'t.">' . "\n";
        echo '<meta property="og:url" content="https://answerenginewp.com">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-image-1200x630.png' ) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="Is your website invisible to ChatGPT?">' . "\n";
        echo '<meta name="twitter:description" content="Free AI Visibility Score for any website. Find out in 10 seconds.">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-image-1200x630.png' ) . '">' . "\n";
    } elseif ( is_page( 'scanner' ) || is_page_template( 'page-scanner.php' ) ) {
        echo '<meta name="description" content="Free AI Visibility Score for any website. Enter your URL and see what AI systems can extract from your site in under 10 seconds.">' . "\n";
        echo '<meta property="og:title" content="Is your website invisible to AI? Scan free. &middot; AnswerEngineWP">' . "\n";
        echo '<meta property="og:description" content="Enter any URL. Get your AI Visibility Score in under 10 seconds. Free, no login required.">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-scanner-1200x630.png' ) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    }
}
add_action( 'wp_head', 'aewp_meta_tags' );

// Custom title for score pages
function aewp_score_page_title( $title ) {
    $hash = get_query_var( 'aewp_score_hash' );
    if ( $hash ) {
        $scan = aewp_get_scan_by_hash( $hash );
        if ( $scan ) {
            $url   = get_post_meta( $scan->ID, '_aewp_url', true );
            $score = get_post_meta( $scan->ID, '_aewp_score', true );
            return esc_html( $url ) . ' scored ' . intval( $score ) . '/100 — AI Visibility Score &middot; AnswerEngineWP';
        }
    }
    return $title;
}
add_filter( 'pre_get_document_title', 'aewp_score_page_title' );

/**
 * Get scan post by hash
 */
function aewp_get_scan_by_hash( $hash ) {
    $hash  = sanitize_text_field( $hash );
    $scans = get_posts( array(
        'post_type'  => 'aewp_scan',
        'meta_key'   => '_aewp_hash',
        'meta_value' => $hash,
        'numberposts' => 1,
        'post_status' => 'publish',
    ) );
    return ! empty( $scans ) ? $scans[0] : null;
}

