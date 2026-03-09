<?php
/**
 * AnswerEngineWP Theme Functions
 *
 * Product site only — no scanner backend.
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

    // Scroll animations + nav (used on all pages)
    wp_enqueue_script(
        'aewp-home',
        get_template_directory_uri() . '/assets/js/home.js',
        array(),
        '1.0',
        true
    );

    // Dequeue jQuery on frontend (keep registered so core dependencies don't break)
    if ( ! is_admin() ) {
        wp_dequeue_script( 'jquery' );
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

// Load includes
require_once get_template_directory() . '/inc/theme-setup.php';

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
        echo '<meta name="description" content="AI Visibility plugin for WordPress. Make your site readable, extractable, and citable by ChatGPT, Perplexity, and Google AI Overviews.">' . "\n";
        echo '<meta property="og:title" content="AnswerEngineWP — AI Visibility for WordPress">' . "\n";
        echo '<meta property="og:description" content="Make your WordPress site readable, extractable, and citable by AI systems. Works alongside Yoast and Rank Math.">' . "\n";
        echo '<meta property="og:url" content="https://answerenginewp.com">' . "\n";
        echo '<meta property="og:type" content="website">' . "\n";
        echo '<meta property="og:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-image-1200x630.png' ) . '">' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
        echo '<meta name="twitter:title" content="AnswerEngineWP — AI Visibility for WordPress">' . "\n";
        echo '<meta name="twitter:description" content="Make your WordPress site readable, extractable, and citable by AI systems.">' . "\n";
        echo '<meta name="twitter:image" content="' . esc_url( get_template_directory_uri() . '/assets/images/og-image-1200x630.png' ) . '">' . "\n";
    }
}
add_action( 'wp_head', 'aewp_meta_tags' );
