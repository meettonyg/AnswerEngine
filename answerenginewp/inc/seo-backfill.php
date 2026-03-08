<?php
/**
 * SEO Backfill — Domain Slug Migration
 *
 * Backfills _aewp_domain_slug meta for existing scans that
 * were created before the programmatic SEO feature.
 *
 * Runs automatically on admin_init if needed, processing
 * in batches to avoid timeouts.
 *
 * @package AnswerEngineWP
 */

/**
 * Check if backfill is needed and run it.
 */
function aewp_maybe_backfill_slugs() {
    if ( ! is_admin() || wp_doing_ajax() ) {
        return;
    }

    // Only run once — set a flag when complete.
    if ( get_option( 'aewp_slugs_backfilled' ) ) {
        return;
    }

    aewp_backfill_domain_slugs();
}
add_action( 'admin_init', 'aewp_maybe_backfill_slugs' );

/**
 * Backfill domain slugs for existing scans.
 *
 * Processes scans in batches of 100. Sets the aewp_slugs_backfilled
 * option when all scans have been processed.
 */
function aewp_backfill_domain_slugs() {
    $batch_size = 100;

    // Find scans missing the domain slug.
    $scans = get_posts( array(
        'post_type'   => 'aewp_scan',
        'post_status' => 'publish',
        'numberposts' => $batch_size,
        'meta_query'  => array(
            'relation' => 'AND',
            array(
                'key'     => '_aewp_url',
                'compare' => 'EXISTS',
            ),
            array(
                'key'     => '_aewp_domain_slug',
                'compare' => 'NOT EXISTS',
            ),
        ),
    ) );

    if ( empty( $scans ) ) {
        // All done — mark as complete.
        update_option( 'aewp_slugs_backfilled', true );
        return;
    }

    foreach ( $scans as $scan ) {
        $url = get_post_meta( $scan->ID, '_aewp_url', true );
        if ( ! empty( $url ) ) {
            $slug = aewp_url_to_slug( $url );
            update_post_meta( $scan->ID, '_aewp_domain_slug', $slug );
        }
    }

    // If we processed a full batch, there may be more — schedule another run.
    if ( count( $scans ) >= $batch_size ) {
        // Will run again on next admin page load.
        return;
    }

    // All done.
    update_option( 'aewp_slugs_backfilled', true );
}
