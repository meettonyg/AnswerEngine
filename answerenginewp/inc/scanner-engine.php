<?php
/**
 * Scanner Scoring Engine
 *
 * Fetches a URL, parses HTML, and calculates 6 sub-scores
 * for the AI Visibility Score (0-100).
 *
 * @package AnswerEngineWP
 */

/**
 * Scan a URL and return scoring data
 *
 * @param string $url The URL to scan.
 * @return array|WP_Error Scan results or error.
 */
function aewp_scan_url( $url ) {
    // Fetch the page
    $response = wp_remote_get( $url, array(
        'timeout'    => 15,
        'user-agent' => 'AnswerEngineWP Scanner/1.0 (+https://answerenginewp.com)',
        'sslverify'  => false,
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

    // Calculate sub-scores
    $schema_data       = aewp_analyze_schema( $doc, $xpath, $html );
    $structure_data    = aewp_analyze_structure( $doc, $xpath );
    $faq_data          = aewp_analyze_faq( $doc, $xpath, $html );
    $summary_data      = aewp_analyze_summaries( $doc, $xpath );
    $feed_data         = aewp_analyze_feeds( $domain_root );
    $entity_data       = aewp_analyze_entities( $doc, $xpath );

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
    );

    // Weighted overall score
    $overall = round(
        $schema_data['score']    * 0.20 +
        $structure_data['score'] * 0.15 +
        $faq_data['score']       * 0.20 +
        $summary_data['score']   * 0.20 +
        $feed_data['score']      * 0.10 +
        $entity_data['score']    * 0.15
    );
    $overall = max( 0, min( 100, $overall ) );

    $tier_data = aewp_get_tier( $overall );

    // Generate fixes
    $fixes = aewp_generate_fixes( $sub_scores, $schema_data, $structure_data, $faq_data, $summary_data, $feed_data, $entity_data );

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
        'missing'               => aewp_get_missing_items( $schema_data, $structure_data, $faq_data, $summary_data, $feed_data ),
    );

    // Citation simulation
    $citation = aewp_generate_citation_simulation( $url, $overall, $extraction );

    return array(
        'score'               => $overall,
        'tier'                => $tier_data['key'],
        'tier_label'          => $tier_data['label'],
        'sub_scores'          => $sub_scores,
        'fixes'               => $fixes,
        'projected_score'     => $projected,
        'extraction'          => $extraction,
        'citation_simulation' => $citation,
    );
}

/**
 * Analyze schema.org markup (JSON-LD, Microdata, RDFa)
 */
function aewp_analyze_schema( $doc, $xpath, $html ) {
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
    $score = min( 100, $score );

    return array(
        'score' => $score,
        'types' => array_values( $types ),
        'has_speakable' => stripos( $html, 'speakable' ) !== false,
    );
}

/**
 * Analyze content structure (headings, HTML hierarchy)
 */
function aewp_analyze_structure( $doc, $xpath ) {
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

    $score = min( 100, $score );

    return array(
        'score'     => $score,
        'headlines' => array_slice( $headlines, 0, 10 ),
        'issues'    => $issues,
    );
}

/**
 * Analyze FAQ and Q&A blocks
 */
function aewp_analyze_faq( $doc, $xpath, $html ) {
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

    $score = min( 100, $score );

    return array(
        'score' => $score,
        'count' => $count,
    );
}

/**
 * Analyze summary presence
 */
function aewp_analyze_summaries( $doc, $xpath ) {
    $score = 0;
    $count = 0;

    // Check for meta description
    $meta_desc = $xpath->query( '//meta[@name="description"]/@content' );
    if ( $meta_desc->length > 0 && strlen( $meta_desc->item( 0 )->value ) > 50 ) {
        $score += 15;
        $count++;
    }

    // Check for article opening summaries (first <p> in <article> or after <h1>)
    $first_p = $xpath->query( '//article/p[1] | //main/p[1]' );
    if ( $first_p->length > 0 ) {
        $text = trim( $first_p->item( 0 )->textContent );
        if ( strlen( $text ) >= 80 && strlen( $text ) <= 300 ) {
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
 */
function aewp_analyze_feeds( $domain_root ) {
    $score = 0;

    // Check /llms.txt
    $llms_txt = wp_remote_get( $domain_root . '/llms.txt', array(
        'timeout'    => 5,
        'sslverify'  => false,
        'user-agent' => 'AnswerEngineWP Scanner/1.0',
    ) );
    if ( ! is_wp_error( $llms_txt ) && wp_remote_retrieve_response_code( $llms_txt ) === 200 ) {
        $body = wp_remote_retrieve_body( $llms_txt );
        if ( strlen( $body ) > 10 ) {
            $score += 40;
        }
    }

    // Check /llms-full.json
    $llms_json = wp_remote_get( $domain_root . '/llms-full.json', array(
        'timeout'    => 5,
        'sslverify'  => false,
        'user-agent' => 'AnswerEngineWP Scanner/1.0',
    ) );
    if ( ! is_wp_error( $llms_json ) && wp_remote_retrieve_response_code( $llms_json ) === 200 ) {
        $body = wp_remote_retrieve_body( $llms_json );
        $json = json_decode( $body, true );
        if ( $json ) {
            $score += 40;
        }
    }

    // Check RSS feed
    $rss = wp_remote_get( $domain_root . '/feed/', array(
        'timeout'    => 5,
        'sslverify'  => false,
        'user-agent' => 'AnswerEngineWP Scanner/1.0',
    ) );
    if ( ! is_wp_error( $rss ) && wp_remote_retrieve_response_code( $rss ) === 200 ) {
        $score += 10;
    }

    // Check sitemap
    $sitemap = wp_remote_get( $domain_root . '/sitemap.xml', array(
        'timeout'    => 5,
        'sslverify'  => false,
        'user-agent' => 'AnswerEngineWP Scanner/1.0',
    ) );
    if ( ! is_wp_error( $sitemap ) && wp_remote_retrieve_response_code( $sitemap ) === 200 ) {
        $score += 10;
    }

    return array(
        'score' => min( 100, $score ),
    );
}

/**
 * Analyze entity density
 */
function aewp_analyze_entities( $doc, $xpath ) {
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

    $score = min( 100, $score );

    return array(
        'score'    => $score,
        'entities' => array_slice( array_values( $entities ), 0, 10 ),
    );
}

/**
 * Generate top 3 fix recommendations
 */
function aewp_generate_fixes( $sub_scores, $schema_data, $structure_data, $faq_data, $summary_data, $feed_data, $entity_data ) {
    $fixes = array();

    // Collect potential fixes sorted by impact
    $potential = array();

    if ( $sub_scores['faq_coverage']['score'] < 50 ) {
        $potential[] = array(
            'points'      => 12,
            'title'       => 'Add FAQ schema to your content',
            'description' => 'Your site lacks structured FAQ markup. Adding FAQPage schema helps AI systems extract and cite your answers directly.',
            'priority'    => 100 - $sub_scores['faq_coverage']['score'],
        );
    }

    if ( $sub_scores['schema_completeness']['score'] < 50 ) {
        $potential[] = array(
            'points'      => 10,
            'title'       => 'Add comprehensive schema markup',
            'description' => 'Your pages have limited or missing schema.org markup. Add Article, Organization, or Product schema to make your content machine-readable.',
            'priority'    => 100 - $sub_scores['schema_completeness']['score'],
        );
    }

    if ( $sub_scores['summary_presence']['score'] < 50 ) {
        $potential[] = array(
            'points'      => 9,
            'title'       => 'Add structured summaries to key pages',
            'description' => 'Your top pages lack concise opening summaries. AI systems need clear, extractable summary text to generate citations.',
            'priority'    => 100 - $sub_scores['summary_presence']['score'],
        );
    }

    if ( $sub_scores['content_structure']['score'] < 60 ) {
        $potential[] = array(
            'points'      => 8,
            'title'       => 'Fix heading hierarchy',
            'description' => 'Your heading structure has gaps or inconsistencies. A clean H1 > H2 > H3 hierarchy helps AI systems understand your content organization.',
            'priority'    => 100 - $sub_scores['content_structure']['score'],
        );
    }

    if ( $sub_scores['feed_readiness']['score'] < 30 ) {
        $potential[] = array(
            'points'      => 7,
            'title'       => 'Create /llms.txt and /llms-full.json',
            'description' => 'These machine-readable manifests tell AI crawlers what your site is about and how to extract your content.',
            'priority'    => 100 - $sub_scores['feed_readiness']['score'],
        );
    }

    if ( $sub_scores['entity_density']['score'] < 50 ) {
        $potential[] = array(
            'points'      => 6,
            'title'       => 'Increase named entity density',
            'description' => 'Your content has few machine-identifiable entities. Use proper nouns, organization names, and place names consistently.',
            'priority'    => 100 - $sub_scores['entity_density']['score'],
        );
    }

    if ( ! $schema_data['has_speakable'] ) {
        $potential[] = array(
            'points'      => 8,
            'title'       => 'Add Speakable markup',
            'description' => 'Speakable schema tells AI systems which parts of your content are best suited for voice and citation use.',
            'priority'    => 85,
        );
    }

    // Sort by priority descending
    usort( $potential, function ( $a, $b ) {
        return $b['priority'] - $a['priority'];
    } );

    // Return top 3
    $fixes = array_slice( $potential, 0, 3 );

    // Remove priority key from output
    return array_map( function ( $fix ) {
        return array(
            'points'      => $fix['points'],
            'title'       => $fix['title'],
            'description' => $fix['description'],
        );
    }, $fixes );
}

/**
 * Get list of missing items for extraction preview
 */
function aewp_get_missing_items( $schema_data, $structure_data, $faq_data, $summary_data, $feed_data ) {
    $missing = array();

    if ( ! $schema_data['has_speakable'] ) {
        $missing[] = 'No Speakable markup';
    }

    if ( $feed_data['score'] < 40 ) {
        $missing[] = 'No /llms.txt file';
    }

    if ( $faq_data['score'] < 20 ) {
        $missing[] = 'No FAQ schema';
    }

    if ( $summary_data['score'] < 30 ) {
        $missing[] = 'No extractable summary';
    }

    if ( empty( $schema_data['types'] ) ) {
        $missing[] = 'No schema.org types detected';
    }

    if ( ! empty( $structure_data['issues'] ) ) {
        foreach ( array_slice( $structure_data['issues'], 0, 2 ) as $issue ) {
            $missing[] = $issue;
        }
    }

    return array_slice( $missing, 0, 6 );
}

/**
 * Generate citation simulation data
 */
function aewp_generate_citation_simulation( $url, $score, $extraction ) {
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

    return array(
        'prompt'     => $prompt,
        'would_cite' => $would_cite,
        'reasons'    => array_slice( $reasons, 0, 4 ),
    );
}
