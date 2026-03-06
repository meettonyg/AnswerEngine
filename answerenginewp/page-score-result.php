<?php
/**
 * Score Results Page
 *
 * Displays public, shareable scan results.
 * Loaded via rewrite rule: /score/{hash}
 *
 * @package AnswerEngineWP
 */

$hash = get_query_var( 'aewp_score_hash' );
$scan = aewp_get_scan_by_hash( $hash );

if ( ! $scan ) {
    status_header( 404 );
    get_header();
    ?>
    <div class="page-404">
        <div class="container">
            <div class="page-404__code">404</div>
            <p class="page-404__message">This scan result was not found.</p>
            <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--primary">Scan a site &rarr;</a>
        </div>
    </div>
    <?php
    get_footer();
    return;
}

$url           = get_post_meta( $scan->ID, '_aewp_url', true );
$score         = intval( get_post_meta( $scan->ID, '_aewp_score', true ) );
$sub_scores    = get_post_meta( $scan->ID, '_aewp_sub_scores', true );
$extraction    = get_post_meta( $scan->ID, '_aewp_extraction_data', true );
$fixes         = get_post_meta( $scan->ID, '_aewp_fixes', true );
$scanned_at    = get_post_meta( $scan->ID, '_aewp_scanned_at', true );
$competitor    = get_post_meta( $scan->ID, '_aewp_competitor_data', true );
$tier          = aewp_get_tier( $score );
$gauge_offset  = 283 - ( 283 * $score / 100 );

// Generate OG image if not exists
$upload_dir = wp_upload_dir();
$og_image_path = $upload_dir['basedir'] . '/aewp-og/' . $hash . '.png';
if ( ! file_exists( $og_image_path ) ) {
    aewp_generate_og_image( $scan );
}
$og_image_url = $upload_dir['baseurl'] . '/aewp-og/' . $hash . '.png';

// Custom head for OG tags
add_action( 'wp_head', function() use ( $url, $score, $tier, $hash, $og_image_url ) {
    echo '<meta property="og:title" content="' . esc_attr( $url ) . ' scored ' . $score . '/100 on the AI Visibility Score">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $tier['label'] ) . '. Scan your own site free at answerenginewp.com/scanner">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $og_image_url ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( home_url( '/score/' . $hash ) ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $url ) . ' scored ' . $score . '/100 on the AI Visibility Score">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $og_image_url ) . '">' . "\n";
} );

get_header();
?>

<section class="score-page">
    <div class="container">
        <div class="score-page__header">
            <p class="score-page__url"><?php echo esc_html( $url ); ?></p>
            <?php if ( $scanned_at ) : ?>
                <p class="score-page__date">Scanned <?php echo esc_html( date( 'F j, Y', strtotime( $scanned_at ) ) ); ?></p>
            <?php endif; ?>
        </div>

        <div class="scanner-results">
            <!-- Score Hero -->
            <div class="scanner-results__hero">
                <div class="score-gauge">
                    <svg viewBox="0 0 100 100">
                        <circle class="score-gauge__track" cx="50" cy="50" r="45" stroke-width="8"/>
                        <circle class="score-gauge__fill" cx="50" cy="50" r="45"
                                stroke="<?php echo esc_attr( $tier['color'] ); ?>"
                                stroke-dasharray="283"
                                stroke-dashoffset="<?php echo esc_attr( $gauge_offset ); ?>"
                                transform="rotate(-90 50 50)"/>
                        <text class="score-gauge__value" x="50" y="48"><?php echo esc_html( $score ); ?></text>
                        <text class="score-gauge__max" x="50" y="64">/100</text>
                    </svg>
                </div>
                <div class="scanner-results__tier">
                    <span class="pill pill--tier pill--<?php echo esc_attr( $tier['class'] ); ?>"><?php echo esc_html( $tier['label'] ); ?></span>
                </div>
                <p class="scanner-results__tier-message"><?php echo esc_html( $tier['message'] ); ?></p>
            </div>

            <!-- Sub-scores -->
            <?php if ( is_array( $sub_scores ) && ! empty( $sub_scores ) ) : ?>
            <div class="sub-scores">
                <h3 class="sub-scores__title">Score Breakdown</h3>
                <?php foreach ( $sub_scores as $key => $sub ) :
                    if ( ! is_array( $sub ) ) continue;
                    $sub_tier = aewp_get_tier( $sub['score'] );
                ?>
                    <div class="sub-score">
                        <div class="sub-score__header">
                            <span class="sub-score__label"><?php echo esc_html( $sub['label'] ); ?></span>
                            <span class="sub-score__value" style="color:<?php echo esc_attr( $sub_tier['color'] ); ?>"><?php echo intval( $sub['score'] ); ?>/100</span>
                        </div>
                        <div class="sub-score__bar">
                            <div class="sub-score__fill" style="width:<?php echo intval( $sub['score'] ); ?>%;background:<?php echo esc_attr( $sub_tier['color'] ); ?>"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Top 3 Fixes -->
            <?php if ( is_array( $fixes ) && ! empty( $fixes ) ) : ?>
            <div class="fixes">
                <h3 class="fixes__title">Your fastest path to AI visibility</h3>
                <p class="fixes__subtitle">Top 3 recommended fixes</p>
                <?php foreach ( $fixes as $fix ) :
                    if ( ! is_array( $fix ) ) continue;
                ?>
                    <div class="fix-card">
                        <div class="fix-card__header">
                            <span class="fix-card__title"><?php echo esc_html( $fix['title'] ); ?></span>
                            <span class="fix-card__points">+<?php echo intval( $fix['points'] ); ?> pts</span>
                        </div>
                        <p class="fix-card__desc"><?php echo esc_html( $fix['description'] ); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Extraction Preview -->
            <?php if ( is_array( $extraction ) ) : ?>
            <div class="extraction">
                <h3 class="extraction__title">Extraction Preview</h3>
                <div class="extraction__grid">
                    <div>
                        <div class="extraction__column-title extraction__column-title--found">Found</div>
                        <?php if ( ! empty( $extraction['schema_types'] ) ) : ?>
                            <?php foreach ( $extraction['schema_types'] as $type ) : ?>
                                <div class="extraction__item extraction__item--found">&#10003; <?php echo esc_html( $type ); ?> schema</div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <?php if ( ! empty( $extraction['entities'] ) ) : ?>
                            <?php foreach ( array_slice( $extraction['entities'], 0, 5 ) as $entity ) : ?>
                                <div class="extraction__item extraction__item--found">&#10003; Entity: <?php echo esc_html( $entity ); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="extraction__column-title extraction__column-title--missing">Missing</div>
                        <?php if ( ! empty( $extraction['missing'] ) ) : ?>
                            <?php foreach ( $extraction['missing'] as $item ) : ?>
                                <div class="extraction__item extraction__item--missing">&#10007; <?php echo esc_html( $item ); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- CTA -->
            <div class="score-page__cta">
                <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--primary">Scan your own site &rarr;</a>
            </div>
        </div>
    </div>
</section>

<?php get_footer(); ?>
