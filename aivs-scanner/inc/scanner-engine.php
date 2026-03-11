<?php
/**
 * Scanner Scoring Engine
 *
 * Fetches a URL, parses HTML, and calculates 6 sub-scores
 * for the AI Visibility Score (0-100).
 *
 * @package AIVisibilityScanner
 */

/**
 * Validate that a URL is safe to fetch (SSRF protection).
 *
 * Blocks requests to internal/private IPs, localhost, and cloud metadata services.
 *
 * @param string $url The URL to validate.
 * @return true|WP_Error True if safe, WP_Error if blocked.
 */
function aivs_validate_url( $url ) {
    $parsed = wp_parse_url( $url );

    if ( empty( $parsed['host'] ) ) {
        return new WP_Error( 'invalid_url', 'Invalid URL.' );
    }

    // Only allow http/https schemes
    $scheme = isset( $parsed['scheme'] ) ? strtolower( $parsed['scheme'] ) : '';
    if ( ! in_array( $scheme, array( 'http', 'https' ), true ) ) {
        return new WP_Error( 'invalid_scheme', 'Only HTTP and HTTPS URLs are allowed.' );
    }

    $host = strtolower( $parsed['host'] );

    // Block localhost and loopback
    $blocked_hosts = array( 'localhost', '127.0.0.1', '0.0.0.0', '[::1]' );
    if ( in_array( $host, $blocked_hosts, true ) ) {
        return new WP_Error( 'blocked_host', 'Scanning internal addresses is not allowed.' );
    }

    // Resolve hostname and check for private/reserved IPs
    $ip = gethostbyname( $host );
    if ( $ip === $host ) {
        // DNS resolution failed — could be a non-existent domain
        return new WP_Error( 'dns_failed', 'Could not resolve hostname.' );
    }

    if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
        return new WP_Error( 'blocked_ip', 'Scanning internal or reserved IP addresses is not allowed.' );
    }

    return true;
}

/**
 * Scan a URL and return scoring data
 *
 * @param string $url The URL to scan.
 * @return array|WP_Error Scan results or error.
 */
function aivs_scan_url( $url, $page_type = 'auto' ) {
    // SSRF protection: validate URL before fetching
    $url_check = aivs_validate_url( $url );
    if ( is_wp_error( $url_check ) ) {
        return $url_check;
    }

    // Fetch the page
    $response = wp_remote_get( $url, array(
        'timeout'    => 15,
        'user-agent' => 'Mozilla/5.0 (compatible; AIVisibilityScanner/1.0; +https://aivisibilityscanner.com)',
        'sslverify'  => false,
        'headers'    => array(
            'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.5',
        ),
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_Error( 'fetch_failed', 'Could not fetch the URL. The site may be unreachable or blocking requests.' );
    }

    $status = wp_remote_retrieve_response_code( $response );
    if ( $status >= 400 ) {
        return new WP_Error( 'http_error', 'The URL returned an HTTP ' . $status . ' error.' );
    }

    $html = wp_remote_retrieve_body( $response );
    if ( empty( $html ) ) {
        return new WP_Error( 'empty_response', 'The URL returned an empty response.' );
    }

    // Parse HTML
    $doc = new DOMDocument();
    libxml_use_internal_errors( true );
    $doc->loadHTML( '<?xml encoding="UTF-8">' . $html );
    libxml_clear_errors();

    $xpath = new DOMXPath( $doc );

    // Extract domain for /llms.txt and /llms-full.json checks
    $parsed_url = wp_parse_url( $url );
    $domain_root = $parsed_url['scheme'] . '://' . $parsed_url['host'];

    // Fetch robots.txt
    $robots_body = '';
    $robots_url  = $domain_root . '/robots.txt';
    if ( true === aivs_validate_url( $robots_url ) ) {
        $robots_response = wp_remote_get( $robots_url, array( 'timeout' => 3, 'sslverify' => false ) );
        if ( ! is_wp_error( $robots_response ) && wp_remote_retrieve_response_code( $robots_response ) === 200 ) {
            $robots_body = wp_remote_retrieve_body( $robots_response );
        }
    }

    // New analysis functions
    $robots_data   = aivs_analyze_robots( $robots_body );
    $spa_data      = aivs_detect_spa( $doc, $xpath, $html );
    $raw_text_data = aivs_extract_raw_text( $doc, $xpath );

    // Calculate sub-scores (pass page_type to schema analysis)
    $schema_data       = aivs_analyze_schema( $doc, $xpath, $html, $page_type );
    $structure_data    = aivs_analyze_structure( $doc, $xpath );
    $faq_data          = aivs_analyze_faq( $doc, $xpath, $html );
    $summary_data      = aivs_analyze_summaries( $doc, $xpath );
    $feed_data         = aivs_analyze_feeds( $domain_root, $xpath );
    $entity_data       = aivs_analyze_entities( $doc, $xpath );

    $speakable_score = ! empty( $schema_data['has_speakable'] ) ? 100 : 0;

    $sub_scores = array(
        'schema_completeness' => array(
            'score'       => $schema_data['score'],
            'label'       => 'Schema Completeness',
            'description' => 'How many schema.org types are present and properly structured?',
        ),
        'content_structure' => array(
            'score'       => $structure_data['score'],
            'label'       => 'Content Structure',
            'description' => 'Does your heading hierarchy and HTML structure support AI extraction?',
        ),
        'faq_coverage' => array(
            'score'       => $faq_data['score'],
            'label'       => 'FAQ & Answer Coverage',
            'description' => 'Are structured question-answer pairs available for AI to cite?',
        ),
        'summary_presence' => array(
            'score'       => $summary_data['score'],
            'label'       => 'Summary Presence',
            'description' => 'Can AI extract concise definitions and summaries from your pages?',
        ),
        'feed_readiness' => array(
            'score'       => $feed_data['score'],
            'label'       => 'Feed & Manifest Readiness',
            'description' => 'Do /llms.txt and /llms-full.json exist and validate?',
        ),
        'entity_density' => array(
            'score'       => $entity_data['score'],
            'label'       => 'Entity Density',
            'description' => 'How many named entities are machine-identifiable?',
        ),
        'speakable_markup' => array(
            'score'       => $speakable_score,
            'label'       => 'Speakable Markup',
            'description' => 'Can assistants identify citation-ready passages for voice and answer extraction?',
        ),
    );

    $layer_scores = aivs_calculate_layer_scores( $sub_scores );

    // Weighted overall score
    $overall = round(
        $schema_data['score']    * 0.20 +
        $entity_data['score']    * 0.15 +
        $speakable_score         * 0.10 +
        $structure_data['score'] * 0.15 +
        $faq_data['score']       * 0.15 +
        $summary_data['score']   * 0.15 +
        $feed_data['score']      * 0.10
    );
    $overall = max( 0, min( 100, $overall ) );

    $tier_data = aivs_get_tier( $overall );

    // Generate fixes
    $fixes = aivs_generate_fixes( $sub_scores, $schema_data, $structure_data, $faq_data, $summary_data, $feed_data, $entity_data );

    // Calculate projected score
    $projected = $overall;
    foreach ( $fixes as $fix ) {
        $projected += $fix['points'];
    }
    $projected = min( 100, $projected );

    // Build extraction data
    $extraction = array(
        'entities'              => $entity_data['entities'],
        'headlines'             => $structure_data['headlines'],
        'structured_answers'    => $faq_data['count'],
        'extractable_summaries' => $summary_data['count'],
        'schema_types'          => $schema_data['types'],
        'list_count'            => $structure_data['list_count'],
        'table_count'           => $structure_data['table_count'],
        'eeat_fields_found'     => $entity_data['eeat_fields_found'],
        'eeat_fields_missing'   => $entity_data['eeat_fields_missing'],
        'missing'               => aivs_get_missing_items( $schema_data, $structure_data, $faq_data, $summary_data, $feed_data, $entity_data, $robots_data ),
    );

    // Citation simulation (with per-heading warnings)
    $citation = aivs_generate_citation_simulation( $url, $overall, $extraction, $faq_data, $structure_data );

    return array(
        'score'               => $overall,
        'tier'                => $tier_data['key'],
        'tier_label'          => $tier_data['label'],
        'sub_scores'          => $sub_scores,
        'layer_scores'        => $layer_scores,
        'fixes'               => $fixes,
        'projected_score'     => $projected,
        'extraction'          => $extraction,
        'citation_simulation' => $citation,
        'robots'              => $robots_data,
        'spa_detection'       => $spa_data,
        'raw_text'            => $raw_text_data,
        'page_type'           => $page_type,
        'page_type_matches'   => $schema_data['page_type_matches'],
        'page_type_mismatches' => $schema_data['page_type_mismatches'],
    );
}

/**
 * Calculate layer scores for the AI Visibility Stack.
 *
 * @param array $sub_scores Sub-score array keyed by signal id.
 * @return array
 */
function aivs_calculate_layer_scores( $sub_scores ) {
    $get_score = function( $key ) use ( $sub_scores ) {
        return isset( $sub_scores[ $key ]['score'] ) ? intval( $sub_scores[ $key ]['score'] ) : 0;
    };

    return array(
        'layer_1_access' => array(
            'label'   => 'Access',
            'score'   => $get_score( 'feed_readiness' ),
            'signals' => array( 'feed_readiness' ),
        ),
        'layer_2_understanding' => array(
            'label'   => 'Understanding',
            'score'   => round( ( $get_score( 'schema_completeness' ) + $get_score( 'entity_density' ) + $get_score( 'speakable_markup' ) ) / 3 ),
            'signals' => array( 'schema_completeness', 'entity_density', 'speakable_markup' ),
        ),
        'layer_3_extractability' => array(
            'label'   => 'Extractability',
            'score'   => round( ( $get_score( 'content_structure' ) + $get_score( 'faq_coverage' ) + $get_score( 'summary_presence' ) ) / 3 ),
            'signals' => array( 'content_structure', 'faq_coverage', 'summary_presence' ),
        ),
    );
}

/**
 * Analyze schema.org markup (JSON-LD, Microdata, RDFa)
 */
function aivs_analyze_schema( $doc, $xpath, $html, $page_type = 'auto' ) {
    $score = 0;
    $types = array();

    // JSON-LD
    $scripts = $xpath->query( '//script[@type="application/ld+json"]' );
    foreach ( $scripts as $script ) {
        $json = json_decode( $script->textContent, true );
        if ( $json ) {
            if ( isset( $json['@type'] ) ) {
                $types[] = $json['@type'];
                $score += 15;
            }
            if ( isset( $json['@graph'] ) && is_array( $json['@graph'] ) ) {
                foreach ( $json['@graph'] as $item ) {
                    if ( isset( $item['@type'] ) ) {
                        $types[] = $item['@type'];
                        $score += 10;
                    }
                }
            }
        }
    }

    // Check for Microdata
    $microdata = $xpath->query( '//*[@itemtype]' );
    foreach ( $microdata as $item ) {
        $itemtype = $item->getAttribute( 'itemtype' );
        if ( preg_match( '/schema\.org\/(\w+)/', $itemtype, $matches ) ) {
            $types[] = $matches[1];
            $score += 10;
        }
    }

    // Check for Speakable
    if ( stripos( $html, 'speakable' ) !== false || stripos( $html, 'Speakable' ) !== false ) {
        $score += 15;
    }

    $types = array_unique( $types );

    // Page-type-aware schema scoring
    $page_type_matches    = array();
    $page_type_mismatches = array();

    if ( $page_type !== 'auto' ) {
        $expected = aivs_get_expected_schema( $page_type );

        foreach ( $expected['expected'] as $expected_type ) {
            $found = false;
            foreach ( $types as $t ) {
                if ( strcasecmp( $t, $expected_type ) === 0 ) {
                    $found = true;
                    break;
                }
            }
            if ( $found ) {
                $page_type_matches[] = $expected_type;
                $score += 10;
            }
        }

        foreach ( $expected['unexpected'] as $unexpected_type ) {
            foreach ( $types as $t ) {
                if ( strcasecmp( $t, $unexpected_type ) === 0 ) {
                    $page_type_mismatches[] = $unexpected_type . ' schema found on ' . str_replace( '_', ' ', $page_type ) . ' — consider using ' . implode( ' or ', array_slice( $expected['expected'], 0, 2 ) ) . ' instead';
                    break;
                }
            }
        }
    }

    $score = min( 100, $score );

    return array(
        'score'                => $score,
        'types'                => array_values( $types ),
        'has_speakable'        => stripos( $html, 'speakable' ) !== false,
        'page_type_matches'    => $page_type_matches,
        'page_type_mismatches' => $page_type_mismatches,
    );
}

/**
 * Analyze content structure (headings, HTML hierarchy)
 */
function aivs_analyze_structure( $doc, $xpath ) {
    $score = 0;
    $headlines = array();
    $issues = array();

    // Check H1
    $h1s = $xpath->query( '//h1' );
    if ( $h1s->length === 1 ) {
        $score += 25;
        $headlines[] = 'H1: ' . trim( $h1s->item( 0 )->textContent );
    } elseif ( $h1s->length > 1 ) {
        $score += 10;
        $issues[] = 'Multiple H1 tags found';
    } else {
        $issues[] = 'No H1 tag found';
    }

    // Check H2s
    $h2s = $xpath->query( '//h2' );
    if ( $h2s->length > 0 ) {
        $score += 20;
        foreach ( $h2s as $h2 ) {
            $headlines[] = 'H2: ' . trim( $h2->textContent );
        }
    }

    // Check heading hierarchy (no skipping)
    $headings = $xpath->query( '//h1|//h2|//h3|//h4|//h5|//h6' );
    $prev_level = 0;
    $hierarchy_ok = true;
    foreach ( $headings as $heading ) {
        $level = intval( substr( $heading->tagName, 1 ) );
        if ( $prev_level > 0 && $level > $prev_level + 1 ) {
            $hierarchy_ok = false;
            $issues[] = 'Heading hierarchy skips from H' . $prev_level . ' to H' . $level;
            break;
        }
        $prev_level = $level;
    }
    if ( $hierarchy_ok && $headings->length > 2 ) {
        $score += 20;
    }

    // Check semantic HTML elements
    $semantic_elements = array( 'article', 'main', 'section', 'nav', 'aside', 'header', 'footer' );
    $semantic_count = 0;
    foreach ( $semantic_elements as $tag ) {
        $found = $xpath->query( '//' . $tag );
        if ( $found->length > 0 ) {
            $semantic_count++;
        }
    }
    $score += min( 20, $semantic_count * 5 );

    // Check for <p> tags with content
    $paragraphs = $xpath->query( '//article//p | //main//p | //body//p' );
    if ( $paragraphs->length >= 3 ) {
        $score += 15;
    }

    // Check for structured data formatting (lists and tables)
    $lists  = $xpath->query( '//ul | //ol' );
    $tables = $xpath->query( '//table' );
    $list_count  = $lists->length;
    $table_count = $tables->length;

    if ( $list_count > 0 || $table_count > 0 ) {
        $score += 10;
    }

    $score = min( 100, $score );

    return array(
        'score'       => $score,
        'headlines'   => array_slice( $headlines, 0, 10 ),
        'issues'      => $issues,
        'list_count'  => $list_count,
        'table_count' => $table_count,
    );
}

/**
 * Analyze FAQ and Q&A blocks
 */
function aivs_analyze_faq( $doc, $xpath, $html ) {
    $score = 0;
    $count = 0;

    // Check for FAQPage schema
    if ( stripos( $html, 'FAQPage' ) !== false ) {
        $score += 40;
    }

    // Check for QAPage schema
    if ( stripos( $html, 'QAPage' ) !== false ) {
        $score += 20;
    }

    // Check for question-like headings
    $headings = $xpath->query( '//h2|//h3|//h4' );
    foreach ( $headings as $heading ) {
        $text = trim( $heading->textContent );
        if ( preg_match( '/\?$/', $text ) || preg_match( '/^(how|what|why|when|where|who|which|can|do|does|is|are|should)/i', $text ) ) {
            $count++;
        }
    }

    if ( $count > 0 ) {
        $score += min( 30, $count * 10 );
    }

    // Check for <details>/<summary> elements (accordion FAQ patterns)
    $details = $xpath->query( '//details' );
    if ( $details->length > 0 ) {
        $score += 15;
        $count += $details->length;
    }

    // Check for dl/dt/dd (definition lists used for Q&A)
    $dts = $xpath->query( '//dt' );
    if ( $dts->length > 0 ) {
        $score += 10;
        $count += $dts->length;
    }

    // Check for common accordion/FAQ class patterns (Squarespace, Shopify, Wix, etc.)
    $accordion_items = $xpath->query( '//*[contains(@class, "accordion") or contains(@class, "faq-item") or contains(@class, "faq__item") or contains(@role, "accordion")]' );
    if ( $accordion_items->length > 0 ) {
        $score += 10;
        $count += $accordion_items->length;
    }

    $score = min( 100, $score );

    return array(
        'score' => $score,
        'count' => $count,
    );
}

/**
 * Analyze summary presence
 */
function aivs_analyze_summaries( $doc, $xpath ) {
    $score = 0;
    $count = 0;

    // Check for meta description
    $meta_desc = $xpath->query( '//meta[@name="description"]/@content' );
    if ( $meta_desc->length > 0 && strlen( $meta_desc->item( 0 )->value ) > 50 ) {
        $score += 15;
        $count++;
    }

    // Check for article opening summaries (first <p> in <article> or after <h1>)
    // Inverted pyramid: ideal is 40-60 words, 80-300 chars for max score
    $first_p = $xpath->query( '//article/p[1] | //main/p[1]' );
    if ( $first_p->length > 0 ) {
        $text = trim( $first_p->item( 0 )->textContent );
        $fp_word_count = str_word_count( $text );

        if ( $fp_word_count >= 40 && $fp_word_count <= 60 && strlen( $text ) >= 80 && strlen( $text ) <= 300 ) {
            $score += 25;
            $count++;
        } elseif ( strlen( $text ) >= 80 && strlen( $text ) <= 300 ) {
            $score += 25;
            $count++;
        } elseif ( strlen( $text ) >= 50 ) {
            $score += 15;
            $count++;
        }
    }

    // Check for og:description
    $og_desc = $xpath->query( '//meta[@property="og:description"]/@content' );
    if ( $og_desc->length > 0 && strlen( $og_desc->item( 0 )->value ) > 50 ) {
        $score += 10;
    }

    // Check for description in JSON-LD
    $scripts = $xpath->query( '//script[@type="application/ld+json"]' );
    foreach ( $scripts as $script ) {
        $json = json_decode( $script->textContent, true );
        if ( $json && isset( $json['description'] ) && strlen( $json['description'] ) > 50 ) {
            $score += 20;
            $count++;
            break;
        }
    }

    // Check for <abstract> or summary-like elements
    $abstracts = $xpath->query( '//*[contains(@class, "summary") or contains(@class, "abstract") or contains(@class, "excerpt") or contains(@class, "tldr")]' );
    if ( $abstracts->length > 0 ) {
        $score += 20;
        $count++;
    }

    // Check for Speakable markup
    $scripts = $xpath->query( '//script[@type="application/ld+json"]' );
    foreach ( $scripts as $script ) {
        if ( stripos( $script->textContent, 'speakable' ) !== false ) {
            $score += 10;
            break;
        }
    }

    $score = min( 100, $score );

    return array(
        'score' => $score,
        'count' => $count,
    );
}

/**
 * Analyze feed and manifest readiness
 *
 * Uses parallel HTTP requests via Requests::request_multiple() to check
 * all feed URLs concurrently instead of sequentially, reducing worst-case
 * from ~20s to ~3s.
 */
function aivs_analyze_feeds( $domain_root, $xpath = null ) {
    $score = 0;

    // Discover RSS feed URL from <link> tags instead of assuming /feed/ (WordPress-only)
    $rss_url = $domain_root . '/feed/';
    if ( $xpath ) {
        $feed_links = $xpath->query( '//link[@type="application/rss+xml"]/@href | //link[@type="application/atom+xml"]/@href' );
        if ( $feed_links->length > 0 ) {
            $discovered = $feed_links->item( 0 )->value;
            if ( filter_var( $discovered, FILTER_VALIDATE_URL ) ) {
                $rss_url = $discovered;
            } elseif ( strpos( $discovered, '/' ) === 0 ) {
                $rss_url = $domain_root . $discovered;
            } else {
                // Resolve page-relative path against domain root
                $rss_url = $domain_root . '/' . $discovered;
            }
        }
    }

    // Validate all feed URLs against SSRF before fetching
    $candidate_urls = array(
        'llms_txt'      => $domain_root . '/llms.txt',
        'llms_json'     => $domain_root . '/llms-full.json',
        'llms_full_txt' => $domain_root . '/llms-full.txt',
        'rss'           => $rss_url,
        'sitemap'       => $domain_root . '/sitemap.xml',
    );

    $urls = array();
    foreach ( $candidate_urls as $key => $feed_url ) {
        if ( true === aivs_validate_url( $feed_url ) ) {
            $urls[ $key ] = $feed_url;
        }
    }

    if ( empty( $urls ) ) {
        return array( 'score' => 0 );
    }

    $shared_opts = array(
        'timeout'    => 3,
        'sslverify'  => true,
        'user-agent' => 'AIVisibilityScanner/1.0',
    );

    // Try parallel requests via Requests library (bundled with WordPress)
    if ( class_exists( 'WpOrg\Requests\Requests' ) || class_exists( 'Requests' ) ) {
        $requests = array();
        foreach ( $urls as $key => $url ) {
            $requests[ $key ] = array(
                'url'     => $url,
                'type'    => 'GET',
                'headers' => array( 'User-Agent' => $shared_opts['user-agent'] ),
            );
        }

        $options = array(
            'timeout'   => 3,
            'verify'    => true,
        );

        $request_class = class_exists( 'WpOrg\Requests\Requests' ) ? 'WpOrg\Requests\Requests' : 'Requests';
        $responses = $request_class::request_multiple( $requests, $options );

        // Score each response using config-driven rules
        $scoring_rules = array(
            'llms_txt'      => array( 'points' => 40, 'validate_body' => true, 'min_length' => 10 ),
            'llms_json'     => array( 'points' => 40, 'validate_body' => true, 'json' => true ),
            'llms_full_txt' => array( 'points' => 40, 'validate_body' => true, 'min_length' => 10 ),
            'rss'           => array( 'points' => 10 ),
            'sitemap'       => array( 'points' => 10 ),
        );

        foreach ( $responses as $key => $response ) {
            if ( ! isset( $scoring_rules[ $key ] ) ) {
                continue;
            }
            if ( $response instanceof \WpOrg\Requests\Exception || $response instanceof \Requests_Exception ) {
                continue;
            }
            if ( $response->status_code !== 200 ) {
                continue;
            }
            $rule = $scoring_rules[ $key ];
            if ( ! empty( $rule['min_length'] ) && strlen( $response->body ) <= $rule['min_length'] ) {
                continue;
            }
            if ( ! empty( $rule['json'] ) && ! json_decode( $response->body, true ) ) {
                continue;
            }
            $score += $rule['points'];
        }
        // Cap llms-full category at 40 points (llms_json and llms_full_txt are alternatives)
        $llms_full_keys = array( 'llms_json', 'llms_full_txt' );
        $llms_full_score = 0;
        foreach ( $responses as $key => $response ) {
            if ( ! in_array( $key, $llms_full_keys, true ) ) continue;
            if ( $response instanceof \WpOrg\Requests\Exception || $response instanceof \Requests_Exception ) continue;
            if ( $response->status_code !== 200 ) continue;
            $rule = isset( $scoring_rules[ $key ] ) ? $scoring_rules[ $key ] : null;
            if ( ! $rule ) continue;
            if ( ! empty( $rule['min_length'] ) && strlen( $response->body ) <= $rule['min_length'] ) continue;
            if ( ! empty( $rule['json'] ) && ! json_decode( $response->body, true ) ) continue;
            $llms_full_score += $rule['points'];
        }
        if ( $llms_full_score > 40 ) {
            $score -= ( $llms_full_score - 40 );
        }
    } else {
        // Fallback: sequential requests with reduced timeout
        $llms_json_score     = 0;
        $llms_full_txt_score = 0;
        foreach ( $urls as $key => $url ) {
            $response = wp_remote_get( $url, $shared_opts );
            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                continue;
            }
            $body = wp_remote_retrieve_body( $response );

            switch ( $key ) {
                case 'llms_txt':
                    if ( strlen( $body ) > 10 ) $score += 40;
                    break;
                case 'llms_json':
                    if ( json_decode( $body, true ) ) {
                        $llms_json_score = 40;
                        $score += 40;
                    }
                    break;
                case 'llms_full_txt':
                    if ( strlen( $body ) > 10 ) {
                        $llms_full_txt_score = 40;
                        $score += 40;
                    }
                    break;
                case 'rss':
                    $score += 10;
                    break;
                case 'sitemap':
                    $score += 10;
                    break;
            }
        }
        // Cap: llms_json + llms_full_txt combined max 40 points
        $llms_combined = $llms_json_score + $llms_full_txt_score;
        if ( $llms_combined > 40 ) {
            $score -= ( $llms_combined - 40 );
        }
    }

    return array(
        'score' => min( 100, $score ),
    );
}

/**
 * Analyze entity density
 */
function aivs_analyze_entities( $doc, $xpath ) {
    $score = 0;
    $entities = array();

    // Get body text content
    $body = $xpath->query( '//body' );
    if ( $body->length === 0 ) {
        return array( 'score' => 0, 'entities' => array() );
    }

    $text = $body->item( 0 )->textContent;

    // Basic NER: extract capitalized multi-word phrases (proper nouns)
    preg_match_all( '/\b([A-Z][a-z]+(?:\s+[A-Z][a-z]+)+)\b/', $text, $matches );
    if ( ! empty( $matches[1] ) ) {
        // Count unique entities
        $entity_counts = array_count_values( $matches[1] );
        arsort( $entity_counts );
        $entities = array_slice( array_keys( $entity_counts ), 0, 15 );
    }

    // Check for entities in schema
    $scripts = $xpath->query( '//script[@type="application/ld+json"]' );
    foreach ( $scripts as $script ) {
        $json = json_decode( $script->textContent, true );
        if ( $json ) {
            if ( isset( $json['name'] ) ) {
                $entities[] = $json['name'];
            }
            if ( isset( $json['author']['name'] ) ) {
                $entities[] = $json['author']['name'];
            }
            if ( isset( $json['publisher']['name'] ) ) {
                $entities[] = $json['publisher']['name'];
            }
        }
    }

    $entities = array_unique( $entities );
    $entity_count = count( $entities );

    // Score based on entity count
    if ( $entity_count >= 10 ) {
        $score = 90;
    } elseif ( $entity_count >= 7 ) {
        $score = 70;
    } elseif ( $entity_count >= 4 ) {
        $score = 50;
    } elseif ( $entity_count >= 2 ) {
        $score = 30;
    } elseif ( $entity_count >= 1 ) {
        $score = 15;
    }

    // Bonus for schema-declared entities
    foreach ( $scripts as $script ) {
        $json = json_decode( $script->textContent, true );
        if ( $json && ( isset( $json['@type'] ) && in_array( $json['@type'], array( 'Organization', 'Person', 'LocalBusiness', 'Product' ), true ) ) ) {
            $score += 10;
            break;
        }
    }

    // E-E-A-T depth check: Organization and Person schema field completeness
    $eeat_fields_found   = array();
    $eeat_fields_missing = array();

    foreach ( $scripts as $script ) {
        $json = json_decode( $script->textContent, true );
        if ( ! $json ) {
            continue;
        }

        $items = array();
        if ( isset( $json['@type'] ) ) {
            $items[] = $json;
        }
        if ( isset( $json['@graph'] ) && is_array( $json['@graph'] ) ) {
            $items = array_merge( $items, $json['@graph'] );
        }

        foreach ( $items as $item ) {
            $type = isset( $item['@type'] ) ? $item['@type'] : '';

            // Organization / LocalBusiness depth
            if ( in_array( $type, array( 'Organization', 'LocalBusiness' ), true ) ) {
                foreach ( array( '@id', 'logo', 'sameAs' ) as $field ) {
                    if ( ! empty( $item[ $field ] ) ) {
                        $eeat_fields_found[] = 'Organization.' . $field;
                        $score += 3;
                    } else {
                        $eeat_fields_missing[] = 'Organization.' . $field;
                    }
                }
            }

            // Person E-E-A-T depth
            if ( $type === 'Person' ) {
                foreach ( array( 'alumniOf', 'knowsAbout', 'jobTitle', 'sameAs' ) as $field ) {
                    if ( ! empty( $item[ $field ] ) ) {
                        $eeat_fields_found[] = 'Person.' . $field;
                        $score += 3;
                    } else {
                        $eeat_fields_missing[] = 'Person.' . $field;
                    }
                }
            }
        }
    }

    $score = min( 100, $score );

    return array(
        'score'               => $score,
        'entities'            => array_slice( array_values( $entities ), 0, 10 ),
        'eeat_fields_found'   => $eeat_fields_found,
        'eeat_fields_missing' => $eeat_fields_missing,
    );
}

/**
 * Generate top 3 fix recommendations
 */
function aivs_generate_fixes( $sub_scores, $schema_data, $structure_data, $faq_data, $summary_data, $feed_data, $entity_data ) {
    $fixes = array();

    // Collect potential fixes sorted by impact
    $potential = array();

    if ( $sub_scores['faq_coverage']['score'] < 50 ) {
        $potential[] = array(
            'points'       => 12,
            'title'        => 'Add FAQ schema to your content',
            'description'  => 'Your site lacks structured FAQ markup. Adding FAQPage schema helps AI systems extract and cite your answers directly.',
            'aewp_feature' => 'faq_schema_generator',
            'aewp_cta'     => 'Fix with AEWP\'s FAQ Schema Generator',
            'factor_id'    => 'Factor 2.5',
            'layer_num'    => 3,
            'layer_name'   => 'Extractability',
            'priority'     => 100 - $sub_scores['faq_coverage']['score'],
        );
    }

    if ( $sub_scores['schema_completeness']['score'] < 50 ) {
        $potential[] = array(
            'points'       => 10,
            'title'        => 'Add comprehensive schema markup',
            'description'  => 'Your pages have limited or missing schema.org markup. Add Article, Organization, or Product schema to make your content machine-readable.',
            'aewp_feature' => 'ai_schema_generator',
            'aewp_cta'     => 'Fix with AEWP\'s 1-Click AI Schema Generator',
            'factor_id'    => 'Factor 2.1',
            'layer_num'    => 2,
            'layer_name'   => 'Understanding',
            'priority'     => 100 - $sub_scores['schema_completeness']['score'],
        );
    }

    if ( $sub_scores['summary_presence']['score'] < 50 ) {
        $potential[] = array(
            'points'       => 9,
            'title'        => 'Add structured summaries to key pages',
            'description'  => 'Your top pages lack concise opening summaries. AI systems need clear, extractable summary text to generate citations.',
            'aewp_feature' => 'answer_summary_block',
            'aewp_cta'     => 'Fix with AEWP\'s Answer Summary Block',
            'factor_id'    => 'Factor 3.4',
            'layer_num'    => 3,
            'layer_name'   => 'Extractability',
            'priority'     => 100 - $sub_scores['summary_presence']['score'],
        );
    }

    if ( $sub_scores['content_structure']['score'] < 60 ) {
        $potential[] = array(
            'points'       => 8,
            'title'        => 'Fix heading hierarchy',
            'description'  => 'Your heading structure has gaps or inconsistencies. A clean H1 > H2 > H3 hierarchy helps AI systems understand your content organization.',
            'aewp_feature' => 'gutenberg_analyzer',
            'aewp_cta'     => 'Fix with AEWP\'s Gutenberg Analyzer',
            'factor_id'    => 'Factor 3.1',
            'layer_num'    => 3,
            'layer_name'   => 'Extractability',
            'priority'     => 100 - $sub_scores['content_structure']['score'],
        );
    }

    if ( $sub_scores['feed_readiness']['score'] < 30 ) {
        $potential[] = array(
            'points'       => 7,
            'title'        => 'Create /llms.txt and /llms-full.json',
            'description'  => 'These machine-readable manifests tell AI crawlers what your site is about and how to extract your content.',
            'aewp_feature' => 'llms_txt_generator',
            'aewp_cta'     => 'Fix with AEWP\'s Dynamic LLMs.txt Generator',
            'factor_id'    => 'Factor 1.1',
            'layer_num'    => 1,
            'layer_name'   => 'Access',
            'priority'     => 100 - $sub_scores['feed_readiness']['score'],
        );
    }

    if ( $sub_scores['entity_density']['score'] < 50 ) {
        $potential[] = array(
            'points'       => 6,
            'title'        => 'Increase named entity density',
            'description'  => 'Your content has few machine-identifiable entities. Use proper nouns, organization names, and place names consistently.',
            'aewp_feature' => 'eeat_profile_enhancer',
            'aewp_cta'     => 'Fix with AEWP\'s E-E-A-T Profile Enhancer',
            'factor_id'    => 'Factor 2.3',
            'layer_num'    => 2,
            'layer_name'   => 'Understanding',
            'priority'     => 100 - $sub_scores['entity_density']['score'],
        );
    }

    if ( ! $schema_data['has_speakable'] ) {
        $potential[] = array(
            'points'       => 8,
            'title'        => 'Add Speakable markup',
            'description'  => 'Speakable schema tells AI systems which parts of your content are best suited for voice and citation use.',
            'aewp_feature' => 'speakable_injector',
            'aewp_cta'     => 'Fix with AEWP\'s Speakable Markup Injector',
            'factor_id'    => 'Factor 2.10',
            'layer_num'    => 2,
            'layer_name'   => 'Understanding',
            'priority'     => 85,
        );
    }

    // Sort by priority descending
    usort( $potential, function ( $a, $b ) {
        return $b['priority'] - $a['priority'];
    } );

    // Return top 3
    $fixes = array_slice( $potential, 0, 3 );

    // Remove priority key from output, include AEWP mapping
    return array_map( function ( $fix ) {
        return array(
            'points'       => $fix['points'],
            'title'        => $fix['title'],
            'description'  => $fix['description'],
            'aewp_feature' => isset( $fix['aewp_feature'] ) ? $fix['aewp_feature'] : '',
            'aewp_cta'     => isset( $fix['aewp_cta'] ) ? $fix['aewp_cta'] : '',
            'factor_id'    => isset( $fix['factor_id'] ) ? $fix['factor_id'] : '',
            'layer_num'    => isset( $fix['layer_num'] ) ? intval( $fix['layer_num'] ) : 0,
            'layer_name'   => isset( $fix['layer_name'] ) ? $fix['layer_name'] : '',
        );
    }, $fixes );
}

/**
 * Get list of missing items for extraction preview
 */
function aivs_get_missing_items( $schema_data, $structure_data, $faq_data, $summary_data, $feed_data, $entity_data = array(), $robots_data = array() ) {
    $missing = array();

    // Critical: robots.txt blocking AI crawlers
    if ( ! empty( $robots_data['has_critical_block'] ) ) {
        $missing[] = 'robots.txt blocks AI crawlers';
    }

    if ( ! $schema_data['has_speakable'] ) {
        $missing[] = 'Missing Factor 2.10: Speakable Schema';
    }

    if ( $feed_data['score'] < 40 ) {
        $missing[] = 'Missing Factor 1.1: LLM Feed Manifest (/llms.txt)';
    }

    if ( $faq_data['score'] < 20 ) {
        $missing[] = 'Missing Factor 2.5: FAQ Schema Coverage';
    }

    if ( $summary_data['score'] < 30 ) {
        $missing[] = 'Missing Factor 3.4: Extractable Summary Block';
    }

    if ( empty( $schema_data['types'] ) ) {
        $missing[] = 'Missing Factor 2.1: Core Schema Types';
    }

    // E-E-A-T depth
    if ( ! empty( $entity_data['eeat_fields_missing'] ) ) {
        $missing[] = 'Author schema lacks verifiable credentials';
    }

    if ( ! empty( $structure_data['issues'] ) ) {
        foreach ( array_slice( $structure_data['issues'], 0, 2 ) as $issue ) {
            $missing[] = $issue;
        }
    }

    return array_slice( $missing, 0, 8 );
}

/**
 * Generate citation simulation data
 */
function aivs_generate_citation_simulation( $url, $score, $extraction, $faq_data = array(), $structure_data = array() ) {
    $parsed = wp_parse_url( $url );
    $domain = isset( $parsed['host'] ) ? $parsed['host'] : $url;

    // Generate a contextual prompt based on content
    $prompt = 'What is ' . $domain . ' about?';
    if ( ! empty( $extraction['headlines'] ) ) {
        $h1 = $extraction['headlines'][0];
        $h1 = preg_replace( '/^H[1-6]:\s*/', '', $h1 );
        if ( strlen( $h1 ) > 10 ) {
            $prompt = $h1;
        }
    }

    $would_cite = $score >= 60;
    $reasons = array();

    if ( $would_cite ) {
        $reasons[] = 'Structured schema markup detected';
        if ( ! empty( $extraction['schema_types'] ) ) {
            $reasons[] = implode( ', ', $extraction['schema_types'] ) . ' schema present';
        }
        if ( $extraction['extractable_summaries'] > 0 ) {
            $reasons[] = 'Extractable summaries found';
        }
    } else {
        if ( empty( $extraction['schema_types'] ) ) {
            $reasons[] = 'No structured schema detected';
        }
        if ( in_array( 'No FAQ schema', $extraction['missing'], true ) ) {
            $reasons[] = 'No FAQ schema detected';
        }
        if ( in_array( 'No extractable summary', $extraction['missing'], true ) ) {
            $reasons[] = 'No extractable summary found';
        }
        if ( in_array( 'No Speakable markup', $extraction['missing'], true ) ) {
            $reasons[] = 'Missing Speakable markup on key content';
        }
    }

    // Per-heading missed citation warnings
    $missed_citations = array();
    $faq_score = isset( $faq_data['score'] ) ? $faq_data['score'] : 100;

    if ( ! empty( $extraction['headlines'] ) && $faq_score < 40 ) {
        foreach ( $extraction['headlines'] as $headline ) {
            $h_text = preg_replace( '/^H[1-6]:\s*/', '', $headline );
            if ( preg_match( '/\?$/', $h_text ) ||
                 preg_match( '/^(how|what|why|when|where|who|which|can|do|does|is|are|should)/i', $h_text ) ) {
                $missed_citations[] = $h_text;
            }
        }
    }

    return array(
        'prompt'           => $prompt,
        'would_cite'       => $would_cite,
        'reasons'          => array_slice( $reasons, 0, 4 ),
        'missed_citations' => array_slice( $missed_citations, 0, 5 ),
    );
}

/**
 * Analyze robots.txt for AI bot directives
 *
 * @param string $robots_body Raw robots.txt content.
 * @return array Analysis results.
 */
function aivs_analyze_robots( $robots_body ) {
    if ( empty( $robots_body ) ) {
        return array(
            'found'              => false,
            'raw'                => '',
            'ai_bots_blocked'    => array(),
            'ai_bots_allowed'    => array(),
            'ai_bots_no_mention' => array(),
            'has_critical_block' => false,
        );
    }

    $ai_bots = array(
        'ChatGPT-User', 'GPTBot', 'PerplexityBot', 'Claude-Web',
        'GoogleOther', 'Google-Extended', 'Applebot-Extended',
        'anthropic-ai', 'CCBot',
    );

    // Parse robots.txt into user-agent blocks
    $lines   = explode( "\n", $robots_body );
    $blocks  = array(); // agent => array of rules
    $current_agents = array();

    foreach ( $lines as $line ) {
        $line = trim( $line );
        if ( $line === '' || $line[0] === '#' ) {
            continue;
        }
        if ( preg_match( '/^user-agent:\s*(.+)/i', $line, $m ) ) {
            $agent = trim( $m[1] );
            $current_agents = array( $agent );
            if ( ! isset( $blocks[ $agent ] ) ) {
                $blocks[ $agent ] = array();
            }
        } elseif ( preg_match( '/^(allow|disallow):\s*(.*)/i', $line, $m ) ) {
            $directive = strtolower( trim( $m[1] ) );
            $path      = trim( $m[2] );
            foreach ( $current_agents as $agent ) {
                $blocks[ $agent ][] = array( 'directive' => $directive, 'path' => $path );
            }
        }
    }

    $blocked    = array();
    $allowed    = array();
    $no_mention = array();

    // Wildcard rules
    $wildcard_rules = isset( $blocks['*'] ) ? $blocks['*'] : array();
    $wildcard_blocks_all = false;
    foreach ( $wildcard_rules as $rule ) {
        if ( $rule['directive'] === 'disallow' && $rule['path'] === '/' ) {
            $wildcard_blocks_all = true;
            break;
        }
    }

    foreach ( $ai_bots as $bot ) {
        $bot_lower = strtolower( $bot );
        $found_block = false;

        // Check for specific bot block
        foreach ( $blocks as $agent => $rules ) {
            if ( strtolower( $agent ) === $bot_lower ) {
                $found_block = true;
                $is_blocked = false;
                foreach ( $rules as $rule ) {
                    if ( $rule['directive'] === 'disallow' && $rule['path'] === '/' ) {
                        $is_blocked = true;
                    }
                    if ( $rule['directive'] === 'allow' && $rule['path'] === '/' ) {
                        $is_blocked = false;
                    }
                }
                if ( $is_blocked ) {
                    $blocked[] = $bot;
                } else {
                    $allowed[] = $bot;
                }
                break;
            }
        }

        if ( ! $found_block ) {
            if ( $wildcard_blocks_all ) {
                $blocked[] = $bot;
            } else {
                $no_mention[] = $bot;
            }
        }
    }

    return array(
        'found'              => true,
        'raw'                => substr( $robots_body, 0, 2000 ),
        'ai_bots_blocked'    => $blocked,
        'ai_bots_allowed'    => $allowed,
        'ai_bots_no_mention' => $no_mention,
        'has_critical_block' => ! empty( $blocked ),
    );
}

/**
 * Detect if the page is a JavaScript-rendered SPA
 *
 * @param DOMDocument $doc  Parsed document.
 * @param DOMXPath    $xpath XPath instance.
 * @param string      $html Raw HTML.
 * @return array Detection results.
 */
function aivs_detect_spa( $doc, $xpath, $html ) {
    $body = $xpath->query( '//body' );
    $word_count = 0;
    $indicators = array();

    if ( $body->length > 0 ) {
        $word_count = str_word_count( $body->item( 0 )->textContent );
    }

    // Check for SPA shell elements
    $spa_ids = array( 'root', 'app', '__next', '__nuxt' );
    foreach ( $spa_ids as $id ) {
        $el = $xpath->query( '//*[@id="' . $id . '"]' );
        if ( $el->length > 0 ) {
            $indicators[] = 'div#' . $id . ' found';
        }
    }

    // Check for noscript tag
    $noscript = $xpath->query( '//noscript' );
    if ( $noscript->length > 0 ) {
        $indicators[] = 'noscript tag present';
    }

    // Check for JS bundle patterns in script src
    if ( preg_match( '/src=["\'][^"\']*(?:main|app|bundle|chunk)\.[a-f0-9]*\.js/i', $html ) ) {
        $indicators[] = 'JS bundle detected';
    }

    // Check for framework-specific patterns
    $framework_patterns = array( 'react', 'vue', 'angular', 'next', 'nuxt', 'svelte' );
    foreach ( $framework_patterns as $fw ) {
        if ( preg_match( '/src=["\'][^"\']*' . $fw . '/i', $html ) ) {
            $indicators[] = $fw . ' framework detected';
        }
    }

    $is_spa = $word_count < 50 && ! empty( $indicators );

    return array(
        'is_spa'         => $is_spa,
        'word_count'     => $word_count,
        'spa_indicators' => $indicators,
    );
}

/**
 * Extract raw text as AI crawlers would see it
 *
 * @param DOMDocument $doc  Parsed document.
 * @param DOMXPath    $xpath XPath instance.
 * @return array Extracted text data.
 */
function aivs_extract_raw_text( $doc, $xpath ) {
    // Try article, then main, then body
    $container = null;
    foreach ( array( '//article', '//main', '//body' ) as $query ) {
        $nodes = $xpath->query( $query );
        if ( $nodes->length > 0 ) {
            $container = $nodes->item( 0 );
            break;
        }
    }

    if ( ! $container ) {
        return array( 'raw_text' => '', 'word_count' => 0, 'char_count' => 0 );
    }

    // Clone and strip non-content elements
    $clone = $container->cloneNode( true );
    $strip_tags = array( 'script', 'style', 'nav', 'footer', 'header', 'aside', 'form' );
    foreach ( $strip_tags as $tag ) {
        $elements = $clone->getElementsByTagName( $tag );
        $to_remove = array();
        for ( $i = 0; $i < $elements->length; $i++ ) {
            $to_remove[] = $elements->item( $i );
        }
        foreach ( $to_remove as $el ) {
            $el->parentNode->removeChild( $el );
        }
    }

    $text = $clone->textContent;
    // Normalize whitespace
    $text = preg_replace( '/[ \t]+/', ' ', $text );
    $text = preg_replace( '/\n{3,}/', "\n\n", $text );
    $text = trim( $text );
    $text = substr( $text, 0, 2000 );

    return array(
        'raw_text'   => $text,
        'word_count' => str_word_count( $text ),
        'char_count' => strlen( $text ),
    );
}

/**
 * Get expected schema types for a given page type
 *
 * @param string $page_type The page type (homepage, blog_post, product_page, local_service).
 * @return array Expected and unexpected schema types.
 */
function aivs_get_expected_schema( $page_type ) {
    $matrix = array(
        'homepage' => array(
            'expected'   => array( 'Organization', 'WebSite', 'SiteNavigationElement', 'WebPage' ),
            'unexpected' => array( 'Article', 'BlogPosting', 'Product' ),
        ),
        'blog_post' => array(
            'expected'   => array( 'Article', 'BlogPosting', 'NewsArticle', 'Person', 'BreadcrumbList' ),
            'unexpected' => array( 'Product', 'Offer', 'LocalBusiness' ),
        ),
        'product_page' => array(
            'expected'   => array( 'Product', 'Offer', 'AggregateRating', 'BreadcrumbList', 'Review' ),
            'unexpected' => array( 'Article', 'BlogPosting', 'NewsArticle' ),
        ),
        'local_service' => array(
            'expected'   => array( 'LocalBusiness', 'Service', 'GeoCoordinates', 'AggregateRating', 'PostalAddress' ),
            'unexpected' => array( 'Article', 'BlogPosting', 'Product' ),
        ),
    );

    if ( isset( $matrix[ $page_type ] ) ) {
        return $matrix[ $page_type ];
    }

    return array( 'expected' => array(), 'unexpected' => array() );
}
