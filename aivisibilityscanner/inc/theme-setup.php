<?php
/**
 * Auto-create required pages on theme activation.
 *
 * @package AIVisibilityScanner
 */

function aivisibilityscanner_create_pages() {
    $pages = array(
        'scan'         => array( 'title' => 'Scan',            'template' => 'page-scan.php' ),
        'badge'        => array( 'title' => 'Badge',           'template' => 'page-badge.php' ),
        'methodology'  => array( 'title' => 'Methodology',     'template' => 'page-methodology.php' ),
        'privacy'      => array( 'title' => 'Privacy Policy',  'template' => 'page-privacy.php' ),
    );

    foreach ( $pages as $slug => $page_data ) {
        $existing = get_page_by_path( $slug );
        if ( ! $existing ) {
            $page_id = wp_insert_post( array(
                'post_title'   => $page_data['title'],
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
            ) );
            if ( $page_id && ! empty( $page_data['template'] ) ) {
                update_post_meta( $page_id, '_wp_page_template', $page_data['template'] );
            }
        }
    }

    // Set front page to static
    $front = get_page_by_path( 'home' );
    if ( ! $front ) {
        $front_id = wp_insert_post( array(
            'post_title'   => 'Home',
            'post_name'    => 'home',
            'post_status'  => 'publish',
            'post_type'    => 'page',
            'post_content' => '',
        ) );
    } else {
        $front_id = $front->ID;
    }

    update_option( 'show_on_front', 'page' );
    update_option( 'page_on_front', $front_id );

    // Flush rewrite rules
    flush_rewrite_rules();
}
add_action( 'after_switch_theme', 'aivisibilityscanner_create_pages' );

// One-time fallback: run page creation if it hasn't happened yet
function aivisibilityscanner_maybe_create_pages() {
    if ( get_option( 'aivisibilityscanner_pages_created' ) ) {
        return;
    }
    aivisibilityscanner_create_pages();
    update_option( 'aivisibilityscanner_pages_created', '1' );
}
add_action( 'init', 'aivisibilityscanner_maybe_create_pages' );
