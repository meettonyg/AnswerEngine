<?php
/**
 * Auto-create required pages on theme activation.
 */

function answerenginewp_create_pages() {
    $pages = array(
        'scanner'      => array( 'title' => 'Scanner',        'template' => 'page-scanner.php' ),
        'score-result' => array( 'title' => 'Score Result',    'template' => '' ),
        'badge'        => array( 'title' => 'Badge',           'template' => '' ),
        'docs'         => array( 'title' => 'Documentation',   'template' => '' ),
        'methodology'  => array( 'title' => 'Methodology',     'template' => 'page-methodology.php' ),
        'privacy'      => array( 'title' => 'Privacy Policy',  'template' => '' ),
        'support'      => array( 'title' => 'Support',         'template' => '' ),
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
add_action( 'after_switch_theme', 'answerenginewp_create_pages' );
