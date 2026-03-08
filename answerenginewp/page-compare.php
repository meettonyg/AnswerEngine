<?php
/**
 * Comparison Page
 *
 * Side-by-side AI Visibility comparison of two domains.
 * Loaded via rewrite rule: /compare/{slug-a}-vs-{slug-b}
 *
 * @package AnswerEngineWP
 */

$slug_a = get_query_var( 'aewp_compare_a' );
$slug_b = get_query_var( 'aewp_compare_b' );
$scan_a = aewp_get_scan_by_slug( $slug_a );
$scan_b = aewp_get_scan_by_slug( $slug_b );

if ( ! $scan_a || ! $scan_b ) {
    status_header( 404 );
    get_header();
    ?>
    <main>
    <div class="page-404">
        <div class="container">
            <div class="page-404__code">404</div>
            <p class="page-404__message">
                <?php if ( ! $scan_a && ! $scan_b ) : ?>
                    Neither domain has been scanned yet.
                <?php elseif ( ! $scan_a ) : ?>
                    No scan data found for the first domain.
                <?php else : ?>
                    No scan data found for the second domain.
                <?php endif; ?>
            </p>
            <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--primary">Scan a site &rarr;</a>
        </div>
    </div>
    </main>
    <?php
    get_footer();
    return;
}

// Load data for both scans.
$url_a        = get_post_meta( $scan_a->ID, '_aewp_url', true );
$score_a      = intval( get_post_meta( $scan_a->ID, '_aewp_score', true ) );
$sub_scores_a = get_post_meta( $scan_a->ID, '_aewp_sub_scores', true );
$extraction_a = get_post_meta( $scan_a->ID, '_aewp_extraction_data', true );
$tier_a       = aewp_get_tier( $score_a );
$domain_a     = aewp_format_domain( $url_a );

$url_b        = get_post_meta( $scan_b->ID, '_aewp_url', true );
$score_b      = intval( get_post_meta( $scan_b->ID, '_aewp_score', true ) );
$sub_scores_b = get_post_meta( $scan_b->ID, '_aewp_sub_scores', true );
$extraction_b = get_post_meta( $scan_b->ID, '_aewp_extraction_data', true );
$tier_b       = aewp_get_tier( $score_b );
$domain_b     = aewp_format_domain( $url_b );

$gauge_offset_a = 283 - ( 283 * $score_a / 100 );
$gauge_offset_b = 283 - ( 283 * $score_b / 100 );

// Determine winner.
if ( $score_a > $score_b ) {
    $winner_domain = $domain_a;
    $winner_score  = $score_a;
} elseif ( $score_b > $score_a ) {
    $winner_domain = $domain_b;
    $winner_score  = $score_b;
} else {
    $winner_domain = null;
}

// Meta tags and JSON-LD.
add_action( 'wp_head', function() use ( $domain_a, $domain_b, $score_a, $score_b, $slug_a, $slug_b ) {
    $title       = ucfirst( $domain_a ) . ' vs ' . ucfirst( $domain_b ) . ' — AI Visibility Comparison';
    $description = 'AI Visibility comparison: ' . $domain_a . ' (' . $score_a . '/100) vs ' . $domain_b . ' (' . $score_b . '/100). See which site is more visible to AI systems like ChatGPT and Google AI.';
    $canonical   = home_url( '/compare/' . $slug_a . '-vs-' . $slug_b . '/' );

    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta property="og:type" content="article">' . "\n";
    echo '<meta name="twitter:card" content="summary">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

    $jsonld = array(
        '@context'    => 'https://schema.org',
        '@type'       => 'WebPage',
        'name'        => $title,
        'description' => $description,
        'url'         => $canonical,
        'breadcrumb'  => array(
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array(
                array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ),
                array( '@type' => 'ListItem', 'position' => 2, 'name' => 'AI Visibility Reports', 'item' => home_url( '/top-ai-visible-websites/' ) ),
                array( '@type' => 'ListItem', 'position' => 3, 'name' => $title ),
            ),
        ),
    );
    echo '<script type="application/ld+json">' . wp_json_encode( $jsonld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
} );

get_header();
?>

<main>
<section class="score-page compare-page">
    <div class="container">
        <div class="score-page__header">
            <h1 class="score-page__domain"><?php echo esc_html( ucfirst( $domain_a ) ); ?> vs <?php echo esc_html( ucfirst( $domain_b ) ); ?></h1>
            <p class="score-page__url">AI Visibility Comparison</p>
        </div>

        <!-- Side-by-side Score Gauges -->
        <div class="compare-heroes">
            <div class="compare-hero">
                <h2 class="compare-hero__domain"><?php echo esc_html( $domain_a ); ?></h2>
                <div class="score-gauge">
                    <svg viewBox="0 0 100 100">
                        <circle class="score-gauge__track" cx="50" cy="50" r="45" stroke-width="8"/>
                        <circle class="score-gauge__fill" cx="50" cy="50" r="45"
                                stroke="<?php echo esc_attr( $tier_a['color'] ); ?>"
                                stroke-dasharray="283"
                                stroke-dashoffset="<?php echo esc_attr( $gauge_offset_a ); ?>"
                                transform="rotate(-90 50 50)"/>
                        <text class="score-gauge__value" x="50" y="45"><?php echo esc_html( $score_a ); ?></text>
                        <text class="score-gauge__max" x="50" y="68">/100</text>
                    </svg>
                </div>
                <span class="pill pill--tier pill--<?php echo esc_attr( $tier_a['class'] ); ?>"><?php echo esc_html( $tier_a['label'] ); ?></span>
            </div>

            <div class="compare-hero__vs">vs</div>

            <div class="compare-hero">
                <h2 class="compare-hero__domain"><?php echo esc_html( $domain_b ); ?></h2>
                <div class="score-gauge">
                    <svg viewBox="0 0 100 100">
                        <circle class="score-gauge__track" cx="50" cy="50" r="45" stroke-width="8"/>
                        <circle class="score-gauge__fill" cx="50" cy="50" r="45"
                                stroke="<?php echo esc_attr( $tier_b['color'] ); ?>"
                                stroke-dasharray="283"
                                stroke-dashoffset="<?php echo esc_attr( $gauge_offset_b ); ?>"
                                transform="rotate(-90 50 50)"/>
                        <text class="score-gauge__value" x="50" y="45"><?php echo esc_html( $score_b ); ?></text>
                        <text class="score-gauge__max" x="50" y="68">/100</text>
                    </svg>
                </div>
                <span class="pill pill--tier pill--<?php echo esc_attr( $tier_b['class'] ); ?>"><?php echo esc_html( $tier_b['label'] ); ?></span>
            </div>
        </div>

        <?php if ( $winner_domain ) : ?>
            <p class="compare-verdict"><strong><?php echo esc_html( $winner_domain ); ?></strong> is more visible to AI systems.</p>
        <?php else : ?>
            <p class="compare-verdict">Both sites have equal AI visibility.</p>
        <?php endif; ?>

        <!-- Detailed Comparison Bars -->
        <div class="scanner-results">
            <div class="competitor-gap">
                <h2 class="competitor-gap__title">Category-by-Category Comparison</h2>
                <?php
                $data_b = array(
                    'url'        => aewp_clean_url_for_display( $url_b ),
                    'score'      => $score_b,
                    'tier'       => $tier_b['key'],
                    'tier_label' => $tier_b['label'],
                    'sub_scores' => $sub_scores_b,
                    'extraction' => $extraction_b,
                );
                echo aewp_render_comparison_bars(
                    array( 'score' => $score_a, 'sub_scores' => $sub_scores_a ),
                    $data_b,
                    $domain_a,
                    $domain_b
                );
                ?>
            </div>

            <!-- CTAs -->
            <div class="scanner-results__ctas">
                <div class="scanner-results__cta-primary">
                    <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener">
                        Improve your score &rarr; Install AnswerEngineWP (Free)
                    </a>
                </div>
                <div class="scanner-results__cta-actions">
                    <a href="<?php echo esc_url( home_url( '/report/' . $slug_a . '/' ) ); ?>" class="btn btn--outline">View <?php echo esc_html( $domain_a ); ?> Report</a>
                    <a href="<?php echo esc_url( home_url( '/report/' . $slug_b . '/' ) ); ?>" class="btn btn--outline">View <?php echo esc_html( $domain_b ); ?> Report</a>
                    <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--outline">Scan Your Site</a>
                </div>
            </div>
        </div>
    </div>
</section>
</main>

<?php get_footer(); ?>
