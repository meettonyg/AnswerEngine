<?php
/**
 * OG Image Generator
 *
 * Generates 1200x630 PNG images for social sharing.
 * Uses PHP GD library.
 *
 * @package AnswerEngineWP
 */

/**
 * Generate OG image for a scan result.
 *
 * @param WP_Post $scan The scan post object.
 * @return string|WP_Error URL to generated image or error.
 */
function aewp_generate_og_image( $scan ) {
    if ( ! function_exists( 'imagecreatetruecolor' ) ) {
        return new WP_Error( 'gd_missing', 'GD library is not available.' );
    }

    $url   = get_post_meta( $scan->ID, '_aewp_url', true );
    $score = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
    $hash  = get_post_meta( $scan->ID, '_aewp_hash', true );
    $tier  = aewp_get_tier( $score );

    // Create image
    $width  = 1200;
    $height = 630;
    $img    = imagecreatetruecolor( $width, $height );

    // Colors
    $bg_color    = imagecolorallocate( $img, 15, 25, 35 );        // --navy
    $white       = imagecolorallocate( $img, 255, 255, 255 );
    $gray_light  = imagecolorallocate( $img, 148, 163, 184 );     // --gray-400
    $gray_medium = imagecolorallocate( $img, 100, 116, 139 );     // --gray-500
    $blue        = imagecolorallocate( $img, 37, 99, 235 );       // --blue

    // Tier color
    $hex = $tier['color'];
    $r = hexdec( substr( $hex, 1, 2 ) );
    $g = hexdec( substr( $hex, 3, 2 ) );
    $b = hexdec( substr( $hex, 5, 2 ) );
    $tier_color = imagecolorallocate( $img, $r, $g, $b );

    // Fill background
    imagefilledrectangle( $img, 0, 0, $width, $height, $bg_color );

    // Draw score (large, centered)
    $score_text = (string) $score;
    $font_size  = 5; // Built-in GD font (1-5)
    $score_x    = ( $width - strlen( $score_text ) * imagefontwidth( $font_size ) * 8 ) / 2;

    // Use built-in fonts since we can't rely on TTF
    // Score number - draw large using multiple passes
    for ( $i = 0; $i < 8; $i++ ) {
        for ( $j = 0; $j < 8; $j++ ) {
            imagestring( $img, 5, (int) ( $width / 2 - 40 + $i ), 200 + $j, $score_text, $tier_color );
        }
    }

    // /100 text
    imagestring( $img, 4, (int) ( $width / 2 + 30 ), 210, '/100', $gray_light );

    // Tier label
    $tier_text = $tier['label'];
    $tier_x    = (int) ( ( $width - strlen( $tier_text ) * imagefontwidth( 4 ) ) / 2 );
    imagestring( $img, 4, $tier_x, 260, $tier_text, $tier_color );

    // URL
    $url_display = aewp_clean_url_for_display( $url );
    $url_x       = (int) ( ( $width - strlen( $url_display ) * imagefontwidth( 3 ) ) / 2 );
    imagestring( $img, 3, $url_x, 300, $url_display, $gray_light );

    // Brand
    $brand_text = 'AI Visibility Score';
    $brand_x    = (int) ( ( $width - strlen( $brand_text ) * imagefontwidth( 4 ) ) / 2 );
    imagestring( $img, 4, $brand_x, 160, $brand_text, $gray_medium );

    // CTA
    $cta_text = 'Scan yours -> answerenginewp.com';
    $cta_x    = (int) ( ( $width - strlen( $cta_text ) * imagefontwidth( 3 ) ) / 2 );
    imagestring( $img, 3, $cta_x, 380, $cta_text, $blue );

    // AnswerEngineWP branding
    $brand_name = 'AnswerEngineWP';
    $bn_x       = (int) ( ( $width - strlen( $brand_name ) * imagefontwidth( 4 ) ) / 2 );
    imagestring( $img, 4, $bn_x, 560, $brand_name, $gray_medium );

    // Save image
    $upload_dir = wp_upload_dir();
    $og_dir     = $upload_dir['basedir'] . '/aewp-og';
    if ( ! file_exists( $og_dir ) ) {
        wp_mkdir_p( $og_dir );
    }

    $file_path = $og_dir . '/' . $hash . '.png';
    imagepng( $img, $file_path );
    imagedestroy( $img );

    return $upload_dir['baseurl'] . '/aewp-og/' . $hash . '.png';
}
