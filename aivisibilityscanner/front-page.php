<?php
/**
 * Scanner homepage — uses the same scanner form as /scan.
 *
 * @package AIVisibilityScanner
 */

get_header();
?>
<main>

<section class="scanner scanner--home" id="scanner">
    <div class="container">
        <?php get_template_part( 'template-parts/scanner/input-state' ); ?>
        <?php get_template_part( 'template-parts/scanner/loading-state' ); ?>
        <?php get_template_part( 'template-parts/scanner/results-state' ); ?>
    </div>
</section>

<section class="scanner-landing__how" id="scannerHowSection">
    <div class="container">
        <h2 class="scanner-landing__how-title">How the AI Visibility Score works</h2>
        <div class="scanner-landing__how-grid">
            <div class="scanner-landing__how-item">
                <div class="scanner-landing__how-num">1</div>
                <h3>Enter any URL</h3>
                <p>We fetch and analyze your page in real time — no login or install needed.</p>
            </div>
            <div class="scanner-landing__how-item">
                <div class="scanner-landing__how-num">2</div>
                <h3>8 AI signals analyzed</h3>
                <p>Crawl access, schema, content structure, FAQ coverage, summaries, feeds, entity density, and content richness.</p>
            </div>
            <div class="scanner-landing__how-item">
                <div class="scanner-landing__how-num">3</div>
                <h3>Score &amp; actionable fixes</h3>
                <p>Get your 0&ndash;100 score, tier ranking, and the top 3 fixes to improve visibility.</p>
            </div>
        </div>
    </div>
</section>

<section class="scanner-landing__tiers">
    <div class="container">
        <h2 class="scanner-landing__tiers-title">AI Visibility Tiers</h2>
        <div class="scanner-landing__tiers-grid">
            <div class="scanner-landing__tier-card" style="border-color: #EF4444;">
                <div class="scanner-landing__tier-range" style="color: #EF4444;">0&ndash;39</div>
                <div class="scanner-landing__tier-label" style="color: #EF4444;">Invisible to AI</div>
                <p>AI systems cannot reliably extract or cite your content.</p>
            </div>
            <div class="scanner-landing__tier-card" style="border-color: #EAB308;">
                <div class="scanner-landing__tier-range" style="color: #EAB308;">40&ndash;69</div>
                <div class="scanner-landing__tier-label" style="color: #EAB308;">AI Readable</div>
                <p>AI can find your content but struggles to structure it.</p>
            </div>
            <div class="scanner-landing__tier-card" style="border-color: #3B82F6;">
                <div class="scanner-landing__tier-range" style="color: #3B82F6;">70&ndash;89</div>
                <div class="scanner-landing__tier-label" style="color: #3B82F6;">AI Extractable</div>
                <p>AI systems can extract and structure your content reliably.</p>
            </div>
            <div class="scanner-landing__tier-card" style="border-color: #22C55E;">
                <div class="scanner-landing__tier-range" style="color: #22C55E;">90&ndash;100</div>
                <div class="scanner-landing__tier-label" style="color: #22C55E;">AI Authority</div>
                <p>Your site is fully optimized for AI extraction and citation.</p>
            </div>
        </div>
    </div>
</section>

</main>
<?php
get_footer();
