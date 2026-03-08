<?php
/**
 * XML Sitemap Generator
 *
 * Generates XML sitemaps for /report/ and /compare/ pages
 * to enable search engine discovery of programmatic SEO pages.
 *
 * @package AnswerEngineWP
 */

/**
 * Serve the appropriate sitemap based on query var.
 *
 * @param string $type Sitemap type: 'index', 'reports', or 'compare'.
 */
function aewp_serve_sitemap( $type ) {
    $page = max( 1, intval( get_query_var( 'aewp_sitemap_page' ) ) );
    if ( empty( $page ) || $page < 1 ) {
        $page = 1;
    }

    header( 'Content-Type: application/xml; charset=UTF-8' );
    header( 'X-Robots-Tag: noindex' );

    switch ( $type ) {
        case 'index':
            echo aewp_generate_sitemap_index();
            break;
        case 'reports':
            echo aewp_generate_report_sitemap( $page );
            break;
        case 'compare':
            echo aewp_generate_compare_sitemap( $page );
            break;
        default:
            status_header( 404 );
            echo '<?xml version="1.0" encoding="UTF-8"?><error>Sitemap not found.</error>';
    }
}

/**
 * Generate the sitemap index XML.
 *
 * Lists sub-sitemaps for reports and comparisons.
 *
 * @return string XML sitemap index.
 */
function aewp_generate_sitemap_index() {
    $slugs_count = aewp_count_unique_slugs();
    $report_pages = max( 1, ceil( $slugs_count / 1000 ) );

    // Estimate comparison pages from scan data with competitors.
    $compare_count = aewp_count_comparison_pairs();
    $compare_pages = max( 1, ceil( max( 1, $compare_count ) / 1000 ) );

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    for ( $i = 1; $i <= $report_pages; $i++ ) {
        $xml .= '  <sitemap>' . "\n";
        $xml .= '    <loc>' . esc_url( home_url( '/sitemap-aewp-reports-' . $i . '.xml' ) ) . '</loc>' . "\n";
        $xml .= '  </sitemap>' . "\n";
    }

    if ( $compare_count > 0 ) {
        for ( $i = 1; $i <= $compare_pages; $i++ ) {
            $xml .= '  <sitemap>' . "\n";
            $xml .= '    <loc>' . esc_url( home_url( '/sitemap-aewp-compare-' . $i . '.xml' ) ) . '</loc>' . "\n";
            $xml .= '  </sitemap>' . "\n";
        }
    }

    $xml .= '</sitemapindex>' . "\n";
    return $xml;
}

/**
 * Generate a paginated report sitemap.
 *
 * @param int $page Page number (1-based).
 * @return string XML sitemap.
 */
function aewp_generate_report_sitemap( $page ) {
    global $wpdb;

    $per_page = 1000;
    $offset   = ( $page - 1 ) * $per_page;

    // Get unique domain slugs with their most recent scan date.
    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT slug_meta.meta_value AS slug, MAX(p.post_date_gmt) AS last_modified
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} slug_meta ON p.ID = slug_meta.post_id AND slug_meta.meta_key = '_aewp_domain_slug'
        WHERE p.post_type = 'aewp_scan' AND p.post_status = 'publish'
        AND slug_meta.meta_value != ''
        GROUP BY slug_meta.meta_value
        ORDER BY last_modified DESC
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ) );

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    if ( ! empty( $results ) ) {
        foreach ( $results as $row ) {
            $lastmod = gmdate( 'c', strtotime( $row->last_modified ) );
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . esc_url( home_url( '/report/' . $row->slug . '/' ) ) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.6</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
    }

    $xml .= '</urlset>' . "\n";
    return $xml;
}

/**
 * Generate a paginated comparison sitemap.
 *
 * Builds comparison URLs from scans that have competitor data.
 *
 * @param int $page Page number (1-based).
 * @return string XML sitemap.
 */
function aewp_generate_compare_sitemap( $page ) {
    global $wpdb;

    $per_page = 1000;
    $offset   = ( $page - 1 ) * $per_page;

    // Find scans with competitor data and domain slugs.
    $results = $wpdb->get_results( $wpdb->prepare(
        "SELECT slug_meta.meta_value AS slug_a, comp_url_meta.meta_value AS competitor_url, MAX(p.post_date_gmt) AS last_modified
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} slug_meta ON p.ID = slug_meta.post_id AND slug_meta.meta_key = '_aewp_domain_slug'
        INNER JOIN {$wpdb->postmeta} comp_url_meta ON p.ID = comp_url_meta.post_id AND comp_url_meta.meta_key = '_aewp_competitor_url'
        WHERE p.post_type = 'aewp_scan' AND p.post_status = 'publish'
        AND slug_meta.meta_value != ''
        AND comp_url_meta.meta_value != ''
        GROUP BY slug_meta.meta_value, comp_url_meta.meta_value
        ORDER BY last_modified DESC
        LIMIT %d OFFSET %d",
        $per_page,
        $offset
    ) );

    $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

    if ( ! empty( $results ) ) {
        foreach ( $results as $row ) {
            $slug_b  = aewp_url_to_slug( $row->competitor_url );
            if ( empty( $slug_b ) || $slug_b === $row->slug_a ) {
                continue;
            }
            $lastmod = gmdate( 'c', strtotime( $row->last_modified ) );
            $xml .= '  <url>' . "\n";
            $xml .= '    <loc>' . esc_url( home_url( '/compare/' . $row->slug_a . '-vs-' . $slug_b . '/' ) ) . '</loc>' . "\n";
            $xml .= '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            $xml .= '    <changefreq>weekly</changefreq>' . "\n";
            $xml .= '    <priority>0.5</priority>' . "\n";
            $xml .= '  </url>' . "\n";
        }
    }

    $xml .= '</urlset>' . "\n";
    return $xml;
}

/**
 * Count unique domain slugs in the database.
 *
 * @return int
 */
function aewp_count_unique_slugs() {
    global $wpdb;

    return (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT slug_meta.meta_value)
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} slug_meta ON p.ID = slug_meta.post_id AND slug_meta.meta_key = '_aewp_domain_slug'
        WHERE p.post_type = 'aewp_scan' AND p.post_status = 'publish'
        AND slug_meta.meta_value != ''"
    );
}

/**
 * Count unique comparison pairs.
 *
 * @return int
 */
function aewp_count_comparison_pairs() {
    global $wpdb;

    return (int) $wpdb->get_var(
        "SELECT COUNT(DISTINCT CONCAT(slug_meta.meta_value, '|', comp_url_meta.meta_value))
        FROM {$wpdb->posts} p
        INNER JOIN {$wpdb->postmeta} slug_meta ON p.ID = slug_meta.post_id AND slug_meta.meta_key = '_aewp_domain_slug'
        INNER JOIN {$wpdb->postmeta} comp_url_meta ON p.ID = comp_url_meta.post_id AND comp_url_meta.meta_key = '_aewp_competitor_url'
        WHERE p.post_type = 'aewp_scan' AND p.post_status = 'publish'
        AND slug_meta.meta_value != ''
        AND comp_url_meta.meta_value != ''"
    );
}
