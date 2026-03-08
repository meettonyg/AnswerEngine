<?php
/**
 * Top AI-Visible Websites
 *
 * Aggregate ranking page showing top-scoring scanned domains.
 * Loaded via rewrite rule: /top-ai-visible-websites
 *
 * @package AnswerEngineWP
 */

$entries = aewp_get_leaderboard( '', 100 );

// Meta tags and JSON-LD.
add_action( 'wp_head', function() use ( $entries ) {
    $title       = 'Top AI-Visible Websites — AI Visibility Rankings';
    $description = 'Rankings of the most AI-visible websites. See which sites score highest for ChatGPT, Perplexity, and Google AI visibility. Updated as new sites are scanned.';
    $canonical   = home_url( '/top-ai-visible-websites/' );

    echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<link rel="canonical" href="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $description ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( $canonical ) . '">' . "\n";
    echo '<meta property="og:type" content="website">' . "\n";
    echo '<meta name="twitter:card" content="summary">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $title ) . '">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $description ) . '">' . "\n";

    // JSON-LD ItemList.
    $list_items = array();
    foreach ( $entries as $entry ) {
        $slug = aewp_url_to_slug( $entry['domain'] );
        $list_items[] = array(
            '@type'    => 'ListItem',
            'position' => $entry['rank'],
            'name'     => $entry['domain'] . ' (Score: ' . $entry['score'] . '/100)',
            'url'      => home_url( '/report/' . $slug . '/' ),
        );
    }

    $jsonld = array(
        '@context'        => 'https://schema.org',
        '@type'           => 'ItemList',
        'name'            => $title,
        'description'     => $description,
        'url'             => $canonical,
        'numberOfItems'   => count( $entries ),
        'itemListElement' => $list_items,
    );
    echo '<script type="application/ld+json">' . wp_json_encode( $jsonld, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
} );

get_header();
?>

<main>
<section class="score-page top-sites-page">
    <div class="container">
        <div class="score-page__header">
            <h1 class="score-page__domain">Top AI-Visible Websites</h1>
            <p class="score-page__url">Ranked by AI Visibility Score. Updated as new sites are scanned.</p>
        </div>

        <?php if ( empty( $entries ) ) : ?>
            <p>No sites have been scanned yet. <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>">Be the first to scan a site.</a></p>
        <?php else : ?>
            <div class="top-sites-list">
                <table class="top-sites-table">
                    <thead>
                        <tr>
                            <th class="top-sites-table__rank">Rank</th>
                            <th class="top-sites-table__domain">Domain</th>
                            <th class="top-sites-table__score">Score</th>
                            <th class="top-sites-table__tier">Tier</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $entries as $entry ) :
                            $slug     = aewp_url_to_slug( $entry['domain'] );
                            $is_top_3 = $entry['rank'] <= 3;
                        ?>
                            <tr class="<?php echo $is_top_3 ? 'top-sites-table__row--highlight' : ''; ?>">
                                <td class="top-sites-table__rank">
                                    <?php if ( $entry['rank'] === 1 ) : ?>
                                        <span class="top-sites-table__medal">&#129351;</span>
                                    <?php elseif ( $entry['rank'] === 2 ) : ?>
                                        <span class="top-sites-table__medal">&#129352;</span>
                                    <?php elseif ( $entry['rank'] === 3 ) : ?>
                                        <span class="top-sites-table__medal">&#129353;</span>
                                    <?php else : ?>
                                        <?php echo intval( $entry['rank'] ); ?>
                                    <?php endif; ?>
                                </td>
                                <td class="top-sites-table__domain">
                                    <a href="<?php echo esc_url( home_url( '/report/' . $slug . '/' ) ); ?>"><?php echo esc_html( $entry['domain'] ); ?></a>
                                </td>
                                <td class="top-sites-table__score" style="color:<?php echo esc_attr( $entry['tier_color'] ); ?>">
                                    <?php echo intval( $entry['score'] ); ?>/100
                                </td>
                                <td class="top-sites-table__tier">
                                    <span style="color:<?php echo esc_attr( $entry['tier_color'] ); ?>"><?php echo esc_html( $entry['tier_label'] ); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="scanner-results__ctas">
            <div class="scanner-results__cta-primary">
                <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--primary">Scan your site and join the rankings &rarr;</a>
            </div>
            <div class="scanner-results__cta-actions">
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--outline" target="_blank" rel="noopener">Install AnswerEngineWP (Free)</a>
            </div>
        </div>
    </div>
</section>
</main>

<?php get_footer(); ?>
