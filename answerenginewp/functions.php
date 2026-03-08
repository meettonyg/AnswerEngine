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

// Custom rewrite rules for /score/{hash}, /report/{slug}, /compare/{a}-vs-{b}, /leaderboard/, /top-sites/
function aewp_rewrite_rules() {
    add_rewrite_rule(
        '^score/([a-zA-Z0-9]+)/?$',
        'index.php?aewp_score_hash=$1',
        'top'
    );
    add_rewrite_rule(
        '^report/([a-z0-9][a-z0-9-]*[a-z0-9])/?$',
        'index.php?aewp_report_slug=$1',
        'top'
    );
    add_rewrite_rule(
        '^compare/([a-z0-9][a-z0-9-]*[a-z0-9])-vs-([a-z0-9][a-z0-9-]*[a-z0-9])/?$',
        'index.php?aewp_compare_a=$1&aewp_compare_b=$2',
        'top'
    );
    add_rewrite_rule(
        '^leaderboard/?$',
        'index.php?aewp_leaderboard=1',
        'top'
    );
    add_rewrite_rule(
        '^top-ai-visible-websites/?$',
        'index.php?aewp_top_sites=1',
        'top'
    );
    // Sitemap routes.
    add_rewrite_rule(
        '^sitemap-aewp\.xml$',
        'index.php?aewp_sitemap=index',
        'top'
    );
    add_rewrite_rule(
        '^sitemap-aewp-reports-?([0-9]*)\.xml$',
        'index.php?aewp_sitemap=reports&aewp_sitemap_page=$1',
        'top'
    );
    add_rewrite_rule(
        '^sitemap-aewp-compare-?([0-9]*)\.xml$',
        'index.php?aewp_sitemap=compare&aewp_sitemap_page=$1',
        'top'
    );
}
add_action( 'init', 'aewp_rewrite_rules' );

function aewp_query_vars( $vars ) {
    $vars[] = 'aewp_score_hash';
    $vars[] = 'aewp_report_slug';
    $vars[] = 'aewp_compare_a';
    $vars[] = 'aewp_compare_b';
    $vars[] = 'aewp_leaderboard';
    $vars[] = 'aewp_top_sites';
    $vars[] = 'aewp_sitemap';
    $vars[] = 'aewp_sitemap_page';
    return $vars;
}
add_filter( 'query_vars', 'aewp_query_vars' );

function aewp_template_redirect() {
    $hash = get_query_var( 'aewp_score_hash' );
    if ( $hash ) {
        include get_template_directory() . '/page-score-result.php';
        exit;
    }
    $report_slug = get_query_var( 'aewp_report_slug' );
    if ( $report_slug ) {
        include get_template_directory() . '/page-report.php';
        exit;
    }
    $compare_a = get_query_var( 'aewp_compare_a' );
    $compare_b = get_query_var( 'aewp_compare_b' );
    if ( $compare_a && $compare_b ) {
        include get_template_directory() . '/page-compare.php';
        exit;
    }
    if ( get_query_var( 'aewp_leaderboard' ) ) {
        include get_template_directory() . '/page-leaderboard.php';
        exit;
    }
    if ( get_query_var( 'aewp_top_sites' ) ) {
        include get_template_directory() . '/page-top-sites.php';
        exit;
    }
    $sitemap = get_query_var( 'aewp_sitemap' );
    if ( $sitemap ) {
        aewp_serve_sitemap( $sitemap );
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
require_once get_template_directory() . '/inc/sitemap-generator.php';
require_once get_template_directory() . '/inc/seo-backfill.php';

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

// Custom title for score, report, compare, and top-sites pages
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

    $report_slug = get_query_var( 'aewp_report_slug' );
    if ( $report_slug ) {
        $scan = aewp_get_scan_by_slug( $report_slug );
        if ( $scan ) {
            $domain = aewp_format_domain( get_post_meta( $scan->ID, '_aewp_url', true ) );
            $score  = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
            return ucfirst( $domain ) . ' AI Visibility Score (' . $score . '/100) — AnswerEngineWP';
        }
    }

    $compare_a = get_query_var( 'aewp_compare_a' );
    $compare_b = get_query_var( 'aewp_compare_b' );
    if ( $compare_a && $compare_b ) {
        $scan_a = aewp_get_scan_by_slug( $compare_a );
        $scan_b = aewp_get_scan_by_slug( $compare_b );
        $domain_a = $scan_a ? aewp_format_domain( get_post_meta( $scan_a->ID, '_aewp_url', true ) ) : $compare_a;
        $domain_b = $scan_b ? aewp_format_domain( get_post_meta( $scan_b->ID, '_aewp_url', true ) ) : $compare_b;
        return ucfirst( $domain_a ) . ' vs ' . ucfirst( $domain_b ) . ' — AI Visibility Comparison &middot; AnswerEngineWP';
    }

    if ( get_query_var( 'aewp_top_sites' ) ) {
        return 'Top AI-Visible Websites — AI Visibility Rankings &middot; AnswerEngineWP';
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

/**
 * Convert a URL to a SEO-friendly slug.
 *
 * Strips protocol, www prefix, trailing slashes, and replaces
 * dots/special chars with hyphens.
 *
 * @param string $url The URL to convert.
 * @return string Slug (e.g., "example-com").
 */
function aewp_url_to_slug( $url ) {
    $slug = preg_replace( '#^https?://#i', '', $url );
    $slug = preg_replace( '/^www\./i', '', $slug );
    $slug = rtrim( $slug, '/' );
    // Remove path — keep only the domain.
    $slash_pos = strpos( $slug, '/' );
    if ( $slash_pos !== false ) {
        $slug = substr( $slug, 0, $slash_pos );
    }
    // Replace dots and non-alphanumeric chars with hyphens.
    $slug = preg_replace( '/[^a-z0-9]+/i', '-', strtolower( $slug ) );
    $slug = trim( $slug, '-' );
    return $slug;
}

/**
 * Convert a domain slug back to a display domain.
 *
 * @param string $slug The domain slug (e.g., "example-com").
 * @return string Display domain (e.g., "example.com").
 */
function aewp_slug_to_domain( $slug ) {
    // Replace the last hyphen before a TLD with a dot.
    // Simple heuristic: replace hyphens that precede common TLDs.
    $slug = sanitize_text_field( $slug );
    // Reverse: replace hyphens with dots, but this is imperfect for domains with actual hyphens.
    // Better: look up the actual domain from the stored scan.
    return $slug;
}

/**
 * Get the most recent scan post for a domain slug.
 *
 * @param string $slug Domain slug (e.g., "example-com").
 * @return WP_Post|null
 */
function aewp_get_scan_by_slug( $slug ) {
    $slug  = sanitize_text_field( $slug );
    $scans = get_posts( array(
        'post_type'   => 'aewp_scan',
        'meta_key'    => '_aewp_domain_slug',
        'meta_value'  => $slug,
        'numberposts' => 1,
        'orderby'     => 'date',
        'order'       => 'DESC',
        'post_status' => 'publish',
    ) );
    return ! empty( $scans ) ? $scans[0] : null;
}

/**
 * robots.txt additions for programmatic SEO pages.
 */
function aewp_robots_txt( $output, $public ) {
    if ( '0' === $public ) {
        return $output;
    }
    $output .= "\n# AnswerEngineWP AI Visibility Reports\n";
    $output .= "Allow: /report/\n";
    $output .= "Allow: /compare/\n";
    $output .= "Allow: /leaderboard/\n";
    $output .= "Allow: /top-ai-visible-websites/\n";
    $output .= "Sitemap: " . home_url( '/sitemap-aewp.xml' ) . "\n";
    return $output;
}
add_filter( 'robots_txt', 'aewp_robots_txt', 10, 2 );

