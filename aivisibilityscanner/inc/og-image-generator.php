<?php
/**
 * OG Image Generator
 *
 * Generates 1200x630 PNG images for social sharing using PHP GD.
 * Also saves an SVG version of the social card alongside the PNG
 * for platforms that support SVG OG images.
 *
 * @package AIVisibilityScanner
 */

/**
 * Generate OG image for a scan result.
 *
 * @param WP_Post $scan The scan post object.
 * @return string|WP_Error URL to generated image or error.
 */
function aivs_generate_og_image( $scan ) {
	$url   = get_post_meta( $scan->ID, '_aivs_url', true );
	$score = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
	$hash  = get_post_meta( $scan->ID, '_aivs_hash', true );
	$tier  = aivs_get_tier( $score );

	$upload_dir = wp_upload_dir();
	$og_dir     = $upload_dir['basedir'] . '/aivs-og';
	if ( ! file_exists( $og_dir ) ) {
		wp_mkdir_p( $og_dir );
	}

	$file_path = $og_dir . '/' . $hash . '.png';

	$result = aivs_rasterize_og_gd( $score, $url, $tier, $file_path );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return $upload_dir['baseurl'] . '/aivs-og/' . $hash . '.png';
}

/**
 * Generate OG image as PNG using GD, plus an SVG social card alongside.
 *
 * @param int    $score     Score 0-100.
 * @param string $url       Scanned URL.
 * @param array  $tier      Tier data.
 * @param string $file_path Output path for PNG.
 * @return true|WP_Error
 */
function aivs_rasterize_og_gd( $score, $url, $tier, $file_path ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return new WP_Error( 'gd_missing', 'GD library is not available.' );
	}

	$width  = 1200;
	$height = 630;
	$img    = imagecreatetruecolor( $width, $height );

	// Enable anti-aliasing.
	imageantialias( $img, true );

	// Colors.
	$bg    = imagecolorallocate( $img, 15, 23, 42 );  // #0F172A
	$white = imagecolorallocate( $img, 255, 255, 255 );
	$gray  = imagecolorallocate( $img, 148, 163, 184 ); // #94A3B8
	$muted = imagecolorallocate( $img, 100, 116, 139 ); // #64748B
	$blue  = imagecolorallocate( $img, 37, 99, 235 );   // #2563EB

	$hex = $tier['color'];
	$r = hexdec( substr( $hex, 1, 2 ) );
	$g = hexdec( substr( $hex, 3, 2 ) );
	$b = hexdec( substr( $hex, 5, 2 ) );
	$tier_color = imagecolorallocate( $img, $r, $g, $b );

	// Fill background.
	imagefilledrectangle( $img, 0, 0, $width - 1, $height - 1, $bg );

	// Tier color accent line at top.
	imagefilledrectangle( $img, 0, 0, $width - 1, 3, $tier_color );

	// Draw gauge circle (arc).
	$cx = $width / 2;
	$cy = 260;
	$gauge_r = 85;
	imagesetthickness( $img, 8 );

	// Track circle.
	$track = imagecolorallocate( $img, 30, 41, 59 ); // #1E293B
	imagearc( $img, (int) $cx, $cy, $gauge_r * 2, $gauge_r * 2, 0, 360, $track );

	// Score arc — draw from -90 degrees, proportional to score.
	$arc_degrees = (int) ( 360 * $score / 100 );
	if ( $arc_degrees > 0 ) {
		imagearc( $img, (int) $cx, $cy, $gauge_r * 2, $gauge_r * 2, 270, 270 + $arc_degrees, $tier_color );
	}
	imagesetthickness( $img, 1 );

	// Score number — centered in gauge using built-in font scaled.
	$score_text = (string) $score;
	$font_size  = 5;
	$char_w     = imagefontwidth( $font_size );
	$char_h     = imagefontheight( $font_size );

	// Scale score text by drawing multiple times for thickness.
	$score_w = strlen( $score_text ) * $char_w;
	$sx = (int) ( $cx - $score_w * 2 );
	$sy = $cy - $char_h * 2;
	for ( $i = 0; $i < 4; $i++ ) {
		for ( $j = 0; $j < 4; $j++ ) {
			imagestring( $img, 5, $sx + $i, $sy + $j, $score_text, $white );
		}
	}

	// /100 beneath score.
	$sub_text = '/100';
	$sub_w    = strlen( $sub_text ) * imagefontwidth( 3 );
	imagestring( $img, 3, (int) ( $cx - $sub_w / 2 ), $cy + $char_h, $sub_text, $gray );

	// Brand — top.
	$brand = 'AI Visibility Scanner';
	$brand_w = strlen( $brand ) * imagefontwidth( 4 );
	imagestring( $img, 4, (int) ( $cx - $brand_w / 2 ), 60, $brand, $blue );

	// "AI Visibility Score" label.
	$vis_text = 'AI Visibility Score';
	$vis_w = strlen( $vis_text ) * imagefontwidth( 3 );
	imagestring( $img, 3, (int) ( $cx - $vis_w / 2 ), 95, $vis_text, $gray );

	// Tier label.
	$tier_text = $tier['label'];
	$tier_w    = strlen( $tier_text ) * imagefontwidth( 4 );
	imagestring( $img, 4, (int) ( $cx - $tier_w / 2 ), 380, $tier_text, $tier_color );

	// Domain.
	$domain = aivs_format_domain( $url, 40 );
	$domain_w = strlen( $domain ) * imagefontwidth( 3 );
	imagestring( $img, 3, (int) ( $cx - $domain_w / 2 ), 410, $domain, $gray );

	// CTA.
	$cta = 'Scan yours free at aivisibilityscanner.com';
	$cta_w = strlen( $cta ) * imagefontwidth( 3 );
	imagestring( $img, 3, (int) ( $cx - $cta_w / 2 ), 500, $cta, $blue );

	// Footer.
	$footer = 'aivisibilityscanner.com';
	$footer_w = strlen( $footer ) * imagefontwidth( 2 );
	imagestring( $img, 2, (int) ( $cx - $footer_w / 2 ), 580, $footer, $muted );

	imagepng( $img, $file_path );
	imagedestroy( $img );

	// Also save the SVG version for platforms that support it.
	$svg = aivs_generate_badge_svg( array(
		'score' => $score,
		'url'   => $url,
	), 'social' );
	$svg_path = str_replace( '.png', '.svg', $file_path );
	file_put_contents( $svg_path, $svg );

	return true;
}
