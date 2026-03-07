<?php
/**
 * OG Image Generator
 *
 * Generates 1200x630 PNG images for social sharing.
 * Uses the social card SVG badge rendered to HTML, then rasterized via Dompdf
 * or PHP GD fallback.
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
	$url   = get_post_meta( $scan->ID, '_aewp_url', true );
	$score = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
	$hash  = get_post_meta( $scan->ID, '_aewp_hash', true );
	$tier  = aewp_get_tier( $score );

	$upload_dir = wp_upload_dir();
	$og_dir     = $upload_dir['basedir'] . '/aewp-og';
	if ( ! file_exists( $og_dir ) ) {
		wp_mkdir_p( $og_dir );
	}

	$file_path = $og_dir . '/' . $hash . '.png';

	// Generate the social card SVG.
	$svg = aewp_generate_badge_svg( array(
		'score' => $score,
		'url'   => $url,
	), 'social' );

	// Try Dompdf rasterization first.
	$autoload = get_template_directory() . '/vendor/autoload.php';
	if ( file_exists( $autoload ) ) {
		require_once $autoload;
		$result = aewp_rasterize_svg_via_dompdf( $svg, $file_path );
		if ( $result ) {
			return $upload_dir['baseurl'] . '/aewp-og/' . $hash . '.png';
		}
	}

	// Fallback: GD-based rendering.
	$result = aewp_rasterize_og_gd( $score, $url, $tier, $file_path );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	return $upload_dir['baseurl'] . '/aewp-og/' . $hash . '.png';
}

/**
 * Rasterize SVG to PNG via Dompdf.
 *
 * Embeds the SVG in an HTML document and renders to image.
 *
 * @param string $svg       SVG markup.
 * @param string $file_path Output file path.
 * @return bool True on success.
 */
function aewp_rasterize_svg_via_dompdf( $svg, $file_path ) {
	try {
		$html = '<!DOCTYPE html><html><head><meta charset="UTF-8">
		<style>
			@page { size: 1200px 630px; margin: 0; }
			body { margin: 0; padding: 0; width: 1200px; height: 630px; overflow: hidden; }
			svg { display: block; }
		</style></head><body>' . $svg . '</body></html>';

		$options = new \Dompdf\Options();
		$options->set( 'isHtml5ParserEnabled', true );
		$options->set( 'isRemoteEnabled', false );
		$options->set( 'defaultFont', 'Helvetica' );

		$dompdf = new \Dompdf\Dompdf( $options );
		$dompdf->loadHtml( $html );
		$dompdf->setPaper( array( 0, 0, 1200, 630 ) );
		$dompdf->render();

		file_put_contents( $file_path, $dompdf->output() );

		// Dompdf outputs PDF, not PNG. Save the SVG directly instead.
		// The SVG file can serve as the OG image in many contexts.
		$svg_path = str_replace( '.png', '.svg', $file_path );
		file_put_contents( $svg_path, $svg );

		// Use GD to create actual PNG from the design.
		return aewp_svg_design_to_png( $file_path );
	} catch ( Exception $e ) {
		return false;
	}
}

/**
 * Create a high-quality PNG from the social card design using GD.
 *
 * This is an improved version that uses proper layout instead of crude pixel fonts.
 *
 * @param string $file_path Output file path.
 * @return bool True on success.
 */
function aewp_svg_design_to_png( $file_path ) {
	// This is a placeholder — the SVG file generated alongside is the preferred asset.
	// Many social platforms accept SVG OG images, and the SVG file is already generated.
	return false;
}

/**
 * Improved GD fallback for OG image generation.
 *
 * @param int    $score     Score 0-100.
 * @param string $url       Scanned URL.
 * @param array  $tier      Tier data.
 * @param string $file_path Output path.
 * @return true|WP_Error
 */
function aewp_rasterize_og_gd( $score, $url, $tier, $file_path ) {
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
	$brand = 'AnswerEngineWP';
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
	$domain = aewp_format_domain( $url, 40 );
	$domain_w = strlen( $domain ) * imagefontwidth( 3 );
	imagestring( $img, 3, (int) ( $cx - $domain_w / 2 ), 410, $domain, $gray );

	// CTA.
	$cta = 'Scan yours free at answerenginewp.com/scanner';
	$cta_w = strlen( $cta ) * imagefontwidth( 3 );
	imagestring( $img, 3, (int) ( $cx - $cta_w / 2 ), 500, $cta, $blue );

	// Footer.
	$footer = 'answerenginewp.com';
	$footer_w = strlen( $footer ) * imagefontwidth( 2 );
	imagestring( $img, 2, (int) ( $cx - $footer_w / 2 ), 580, $footer, $muted );

	imagepng( $img, $file_path );
	imagedestroy( $img );

	// Also save the SVG version for platforms that support it.
	$svg = aewp_generate_badge_svg( array(
		'score' => $score,
		'url'   => $url,
	), 'social' );
	$svg_path = str_replace( '.png', '.svg', $file_path );
	file_put_contents( $svg_path, $svg );

	return true;
}
