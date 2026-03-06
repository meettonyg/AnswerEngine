<?php
/**
 * Auto-create required pages on theme activation.
 */

function answerenginewp_create_pages() {
    $pages = array(
        'scanner'      => 'Scanner',
        'score-result' => 'Score Result',
        'badge'        => 'Badge',
        'docs'         => 'Documentation',
        'methodology'  => 'Methodology',
        'privacy'      => 'Privacy Policy',
        'support'      => 'Support',
    );

    foreach ( $pages as $slug => $title ) {
        $existing = get_page_by_path( $slug );
        if ( ! $existing ) {
            wp_insert_post( array(
                'post_title'   => $title,
                'post_name'    => $slug,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '',
            ) );
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
add_action( 'after_switch_theme', 'answerenginewp_create_pages' );
