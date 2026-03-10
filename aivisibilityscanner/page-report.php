<?php
/**
 * Report Page
 *
 * Displays public, shareable scan results.
 * Loaded via rewrite rules: /report/{domain} or /score/{hash} (backward compat)
 *
 * @package AIVisibilityScanner
 */

// Guard: require plugin for scan data functions.
if ( ! function_exists( 'aivs_get_tier' ) ) {
	get_header();
	echo '<main class="report"><div class="container" style="padding:4rem 1rem;text-align:center;">';
	echo '<h1>Scanner Plugin Required</h1>';
	echo '<p style="color:var(--gray-400);max-width:480px;margin:1rem auto;">The AI Visibility Scanner plugin must be installed and activated to view reports.</p>';
	echo '<a href="' . esc_url( home_url( '/' ) ) . '" class="btn btn--primary">Back to Home</a>';
	echo '</div></main>';
	get_footer();
	return;
}

// Try domain-based lookup first, then fall back to hash
$domain = get_query_var( 'aivs_report_domain' );
$hash   = get_query_var( 'aivs_score_hash' );
$scan   = null;

if ( $domain ) {
    $scan = aivs_get_latest_scan_for_domain( $domain );
} elseif ( $hash ) {
    $scan = aivs_get_scan_by_hash( $hash );
}

if ( ! $scan ) {
    status_header( 404 );
    get_header();
    ?>
    <main>
    <div class="page-404">
        <div class="container">
            <div class="page-404__code">404</div>
            <p class="page-404__message">No scan results found<?php echo $domain ? ' for ' . esc_html( $domain ) : ''; ?>.</p>
            <a href="<?php echo esc_url( home_url( '/scan/' ) ); ?>" class="btn btn--primary">Scan a site &rarr;</a>
        </div>
    </div>
    </main>
    <?php
    get_footer();
    return;
}

$url           = get_post_meta( $scan->ID, '_aivs_url', true );
$score         = intval( get_post_meta( $scan->ID, '_aivs_score', true ) );
$sub_scores    = get_post_meta( $scan->ID, '_aivs_sub_scores', true );
$extraction    = get_post_meta( $scan->ID, '_aivs_extraction_data', true );
$fixes         = get_post_meta( $scan->ID, '_aivs_fixes', true );
$scanned_at    = get_post_meta( $scan->ID, '_aivs_scanned_at', true );
$competitor    = get_post_meta( $scan->ID, '_aivs_competitor_data', true );
$robots_data   = get_post_meta( $scan->ID, '_aivs_robots_data', true );
$spa_detection = get_post_meta( $scan->ID, '_aivs_spa_detection', true );
$raw_text_data = get_post_meta( $scan->ID, '_aivs_raw_text', true );
$missed_citations = get_post_meta( $scan->ID, '_aivs_missed_citations', true );
$page_type     = get_post_meta( $scan->ID, '_aivs_page_type', true );
$tier          = aivs_get_tier( $score );
$gauge_offset  = 283 - ( 283 * $score / 100 );
$scan_hash     = get_post_meta( $scan->ID, '_aivs_hash', true );
$display_domain = aivs_clean_url_for_display( $url );

// Generate OG image if not exists
$upload_dir = wp_upload_dir();
$og_image_path = $upload_dir['basedir'] . '/aivs-og/' . $scan_hash . '.png';
if ( ! file_exists( $og_image_path ) ) {
    aivs_generate_og_image( $scan );
}
$og_image_url = $upload_dir['baseurl'] . '/aivs-og/' . $scan_hash . '.png';

// Custom head for OG tags
add_action( 'wp_head', function() use ( $display_domain, $score, $tier, $og_image_url ) {
    echo '<meta property="og:title" content="' . esc_attr( $display_domain ) . ' scored ' . $score . '/100 on the AI Visibility Score">' . "\n";
    echo '<meta property="og:description" content="' . esc_attr( $tier['label'] ) . '. Scan your own site free at aivisibilityscanner.com">' . "\n";
    echo '<meta property="og:image" content="' . esc_url( $og_image_url ) . '">' . "\n";
    echo '<meta property="og:url" content="' . esc_url( home_url( '/report/' . $display_domain ) ) . '">' . "\n";
    echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr( $display_domain ) . ' scored ' . $score . '/100 on the AI Visibility Score">' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr( $tier['label'] ) . '. Scan your own site free at aivisibilityscanner.com">' . "\n";
    echo '<meta name="twitter:image" content="' . esc_url( $og_image_url ) . '">' . "\n";
} );

get_header();
?>

<main>
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
                        <text class="score-gauge__value" x="50" y="45"><?php echo esc_html( $score ); ?></text>
                        <text class="score-gauge__max" x="50" y="68">/100</text>
                    </svg>
                </div>
                <div class="scanner-results__tier">
                    <span class="pill pill--tier pill--<?php echo esc_attr( $tier['class'] ); ?>"><?php echo esc_html( $tier['label'] ); ?></span>
                </div>
                <p class="scanner-results__tier-message"><?php echo esc_html( $tier['message'] ); ?></p>
            </div>

            <!-- Critical Alerts -->
            <?php if ( ( is_array( $robots_data ) && ! empty( $robots_data['has_critical_block'] ) ) ||
                       ( is_array( $spa_detection ) && ! empty( $spa_detection['is_spa'] ) ) ) : ?>
            <div class="critical-alerts">
                <?php if ( ! empty( $robots_data['has_critical_block'] ) ) : ?>
                <div class="critical-alert critical-alert--robots">
                    <div class="critical-alert__icon">&#9888;</div>
                    <div class="critical-alert__content">
                        <h4 class="critical-alert__title">robots.txt Blocks AI Crawlers</h4>
                        <p class="critical-alert__desc">
                            Your robots.txt blocks: <?php echo ! empty( $robots_data['ai_bots_blocked'] ) && is_array( $robots_data['ai_bots_blocked'] ) ? esc_html( implode( ', ', $robots_data['ai_bots_blocked'] ) ) : 'AI crawlers'; ?>
                        </p>
                        <a href="https://wordpress.org/plugins/answerenginewp/" class="critical-alert__cta" target="_blank" rel="noopener">
                            Fix with AEWP's Bot Manager &rarr;
                        </a>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ( ! empty( $spa_detection['is_spa'] ) ) : ?>
                <div class="critical-alert critical-alert--spa">
                    <div class="critical-alert__icon">&#9888;</div>
                    <div class="critical-alert__content">
                        <h4 class="critical-alert__title">Client-Side Rendering Detected</h4>
                        <p class="critical-alert__desc">Only <?php echo intval( $spa_detection['word_count'] ); ?> words of body text found.</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- Page Type Context -->
            <?php if ( ! empty( $page_type ) && 'auto' !== $page_type ) : ?>
            <div class="page-type-context">
                <p class="page-type-context__info">
                    Scored as: <strong><?php echo esc_html( str_replace( '_', ' ', ucfirst( $page_type ) ) ); ?></strong>
                </p>
            </div>
            <?php endif; ?>

            <!-- Sub-scores -->
            <?php if ( is_array( $sub_scores ) && ! empty( $sub_scores ) ) : ?>
            <div class="sub-scores">
                <h3 class="sub-scores__title">Score Breakdown</h3>
                <?php foreach ( $sub_scores as $key => $sub ) :
                    if ( ! is_array( $sub ) ) continue;
                    $sub_tier = aivs_get_tier( $sub['score'] );
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
                        <?php if ( ! empty( $fix['aewp_cta'] ) ) : ?>
                            <a href="https://wordpress.org/plugins/answerenginewp/" class="fix-card__aewp-cta" target="_blank" rel="noopener">
                                <?php echo esc_html( $fix['aewp_cta'] ); ?> &rarr;
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Competitor Gap -->
            <?php if ( is_array( $competitor ) && ! empty( $competitor ) ) : ?>
            <div class="competitor-gap">
                <h3 class="competitor-gap__title">Competitor Structure Gap</h3>
                <?php
                echo aivs_render_comparison_bars(
                    array( 'score' => $score, 'sub_scores' => $sub_scores ),
                    $competitor,
                    aivs_format_domain( $url ),
                    isset( $competitor['url'] ) ? $competitor['url'] : 'Competitor'
                );
                ?>
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
                        <?php if ( ! empty( $extraction['headlines'] ) ) : ?>
                            <?php foreach ( array_slice( $extraction['headlines'], 0, 5 ) as $headline ) : ?>
                                <div class="extraction__item extraction__item--found">&#10003; <?php echo esc_html( $headline ); ?></div>
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

            <!-- How AI Sees Your Page -->
            <?php if ( is_array( $raw_text_data ) && ! empty( $raw_text_data['raw_text'] ) ) : ?>
            <div class="raw-text-view">
                <h3 class="raw-text-view__title">How AI Sees Your Page</h3>
                <pre class="raw-text-view__pre"><?php echo esc_html( $raw_text_data['raw_text'] ); ?></pre>
                <p class="raw-text-view__cta">Is this a mess? <a href="https://wordpress.org/plugins/answerenginewp/" target="_blank" rel="noopener">Install AnswerEngineWP</a> to generate a clean /llms-docs/ feed.</p>
            </div>
            <?php endif; ?>

            <!-- Blindspot Upsell -->
            <div class="blindspot">
                <div class="blindspot__icon">&#128269;</div>
                <h3 class="blindspot__title">You just scanned 1 page</h3>
                <p class="blindspot__desc">
                    Your site has dozens (or hundreds) of pages. Each one needs its own schema,
                    structure, and AI-ready signals. This scanner checked just one.
                </p>
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener">
                    Audit your entire site with AnswerEngineWP &rarr;
                </a>
            </div>

            <!-- Email Gate for PDF -->
            <div class="email-capture" id="reportEmailCapture">
                <p class="email-capture__heading">Enter your email to unlock the PDF report</p>
                <div class="email-capture__form" id="reportEmailForm">
                    <input type="email" id="reportEmailInput" class="email-capture__input"
                           placeholder="you@company.com" autocomplete="email">
                    <button type="button" class="email-capture__submit" id="reportEmailSubmit">Unlock PDF Report</button>
                </div>
                <p class="email-capture__note" id="reportEmailNote">We'll email your PDF report + 3 actionable tips. No spam, ever.</p>
                <p class="email-capture__success" id="reportEmailSuccess" style="display:none">&#10003; PDF unlocked! Check your inbox for the full report.</p>
            </div>

            <!-- CTAs -->
            <div class="scanner-results__ctas">
                <div class="scanner-results__cta-primary">
                    <a href="https://answerenginewp.com" class="btn btn--primary" target="_blank" rel="noopener">
                        Improve your score &rarr; Get AnswerEngineWP
                    </a>
                </div>
                <div class="scanner-results__cta-actions">
                    <button type="button" class="btn btn--outline btn--locked" id="reportDownloadPdf"
                            data-pdf-url="<?php echo esc_url( rest_url( 'aivs/v1/report/' . $scan_hash ) ); ?>">&#128274; Download PDF Report</button>
                    <button type="button" class="btn btn--outline" id="shareScoreBtn" data-url="<?php echo esc_url( home_url( '/report/' . $display_domain ) ); ?>">Share Score</button>
                    <button type="button" class="btn btn--outline" id="copyBadgeBtn"
                            data-snippet="<?php echo esc_attr( '<a href="' . home_url( '/report/' . $display_domain ) . '" title="AI Visibility Score: ' . $score . '/100 — ' . $tier['label'] . '" style="display:inline-block;text-decoration:none"><img src="' . rest_url( 'aivs/v1/badge/' . $scan_hash . '.svg?variant=small' ) . '" alt="' . aivs_generate_alt_text( aivs_format_domain( $url ), $score, $tier['label'] ) . '" width="220" height="60"></a>' ); ?>">Copy Badge Snippet</button>
                </div>
                <a href="<?php echo esc_url( home_url( '/scan/' ) ); ?>" class="scanner-results__reset">&larr; Scan another site</a>
            </div>
        </div>
    </div>
</section>
</main>

<script>
(function() {
    var pdfUnlocked = false;

    // PDF button — gated behind email
    var pdfBtn = document.getElementById('reportDownloadPdf');
    var emailCapture = document.getElementById('reportEmailCapture');
    var emailInput = document.getElementById('reportEmailInput');
    var emailSubmit = document.getElementById('reportEmailSubmit');
    var emailForm = document.getElementById('reportEmailForm');
    var emailNote = document.getElementById('reportEmailNote');
    var emailSuccess = document.getElementById('reportEmailSuccess');
    var scanHash = <?php echo wp_json_encode( $scan_hash ); ?>;
    var emailUrl = <?php echo wp_json_encode( rest_url( 'aivs/v1/email' ) ); ?>;

    if (pdfBtn) {
        pdfBtn.addEventListener('click', function () {
            if (pdfUnlocked) {
                window.open(pdfBtn.getAttribute('data-pdf-url'), '_blank');
                return;
            }
            if (emailCapture) {
                emailCapture.scrollIntoView({ behavior: 'smooth', block: 'center' });
                emailCapture.classList.add('email-capture--highlight');
                setTimeout(function () {
                    emailCapture.classList.remove('email-capture--highlight');
                }, 2000);
                if (emailInput) emailInput.focus();
            }
        });
    }

    if (emailSubmit) {
        emailSubmit.addEventListener('click', function () {
            var email = emailInput ? emailInput.value.trim() : '';
            if (!email || email.indexOf('@') === -1) {
                if (emailInput) emailInput.style.borderColor = '#EF4444';
                return;
            }
            emailInput.style.borderColor = '';
            emailSubmit.disabled = true;
            emailSubmit.textContent = 'Sending\u2026';

            fetch(emailUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ email: email, hash: scanHash })
            }).then(function (res) { return res.json(); })
              .then(function (result) {
                if (result.success) {
                    if (emailForm) emailForm.style.display = 'none';
                    if (emailNote) emailNote.style.display = 'none';
                    if (emailSuccess) emailSuccess.style.display = '';

                    // Unlock PDF
                    pdfUnlocked = true;
                    if (pdfBtn) {
                        pdfBtn.textContent = 'Download PDF Report';
                        pdfBtn.classList.remove('btn--locked');
                    }

                    // Auto-open PDF
                    setTimeout(function () {
                        window.open(pdfBtn.getAttribute('data-pdf-url'), '_blank');
                    }, 600);
                } else {
                    emailSubmit.disabled = false;
                    emailSubmit.textContent = 'Unlock PDF Report';
                    if (emailInput) emailInput.style.borderColor = '#EF4444';
                }
            }).catch(function () {
                emailSubmit.disabled = false;
                emailSubmit.textContent = 'Unlock PDF Report';
            });
        });
    }

    // Share button
    var shareBtn = document.getElementById('shareScoreBtn');
    if (shareBtn) {
        shareBtn.addEventListener('click', function() {
            var url = this.getAttribute('data-url');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function() {
                    shareBtn.textContent = 'Link copied!';
                    setTimeout(function() { shareBtn.textContent = 'Share Score'; }, 2000);
                });
            } else {
                prompt('Copy this link:', url);
            }
        });
    }

    // Badge button
    var badgeBtn = document.getElementById('copyBadgeBtn');
    if (badgeBtn) {
        badgeBtn.addEventListener('click', function() {
            var snippet = this.getAttribute('data-snippet');
            if (navigator.clipboard) {
                navigator.clipboard.writeText(snippet).then(function() {
                    badgeBtn.textContent = 'Copied!';
                    setTimeout(function() { badgeBtn.textContent = 'Copy Badge Snippet'; }, 2000);
                });
            } else {
                prompt('Copy this HTML:', snippet);
            }
        });
    }
})();
</script>

<?php get_footer(); ?>
