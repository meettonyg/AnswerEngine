<?php
/**
 * Pricing page — auto-loaded for slug "pricing" via WordPress template hierarchy.
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content pricing-page">
    <div class="container">
        <div class="pricing-page__header fade-up">
            <div class="section-label">Pricing</div>
            <h1>Priced as an add-on, not a replacement.</h1>
            <p class="pricing-page__sub">Your existing SEO plugin costs ~$99/year. AnswerEngineWP sits alongside it — not on top of it.</p>
        </div>

        <div class="pricing__grid fade-up-stagger">
            <!-- Free -->
            <div class="card--pricing">
                <div class="pricing-card__tier">Free</div>
                <div class="pricing-card__price">$0</div>
                <p class="pricing-card__tagline">Get the diagnostic for free.</p>
                <ul class="pricing-card__features">
                    <li class="pricing-card__feature">AI Visibility Score (benchmarked)</li>
                    <li class="pricing-card__feature">AI Extraction Preview</li>
                    <li class="pricing-card__feature">AI Citation Simulation</li>
                    <li class="pricing-card__feature">Basic Speakable Answer Blocks</li>
                    <li class="pricing-card__feature">Basic /llms.txt generator</li>
                </ul>
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--outline pricing-card__cta" target="_blank" rel="noopener">Download Free &rarr;</a>
            </div>

            <!-- Pro -->
            <div class="card--pricing is-popular">
                <span class="popular-badge">Most Popular</span>
                <div class="pricing-card__tier">Pro</div>
                <div class="pricing-card__price">$49<span class="pricing-card__period">&ndash;$79/year</span></div>
                <p class="pricing-card__tagline">Automate the fix.</p>
                <ul class="pricing-card__features">
                    <li class="pricing-card__feature">Everything in Free</li>
                    <li class="pricing-card__feature">1-Click AI Summary Generation</li>
                    <li class="pricing-card__feature">Elementor / Divi page grading</li>
                    <li class="pricing-card__feature">AI Crawler Analytics Dashboard</li>
                    <li class="pricing-card__feature">Dynamic /llms-full.json feeds</li>
                    <li class="pricing-card__feature">Priority support</li>
                </ul>
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary pricing-card__cta" target="_blank" rel="noopener">Upgrade to Pro &rarr;</a>
            </div>

            <!-- Agency -->
            <div class="card--pricing">
                <div class="pricing-card__tier">Agency — 25 sites</div>
                <div class="pricing-card__price">$199<span class="pricing-card__period">&ndash;$299/year</span></div>
                <p class="pricing-card__tagline">Turn AI visibility into a retainer service.</p>
                <ul class="pricing-card__features">
                    <li class="pricing-card__feature">Everything in Pro</li>
                    <li class="pricing-card__feature">Bulk Site Scan (50 URLs, CSV)</li>
                    <li class="pricing-card__feature">Multisite entity sync</li>
                    <li class="pricing-card__feature">Unbranded PDF audit reports</li>
                    <li class="pricing-card__feature">Headless API access</li>
                </ul>
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--navy pricing-card__cta" target="_blank" rel="noopener">Get Agency License &rarr;</a>
            </div>
        </div>

        <p class="pricing__context fade-up">Most agencies charge $500+/month for AI search readiness retainers. The Agency license pays for itself with a single client.</p>

        <div class="pricing-page__faq fade-up">
            <h2>Frequently Asked Questions</h2>

            <div class="pricing-faq">
                <h3 class="pricing-faq__q">Does AnswerEngineWP replace Yoast or Rank Math?</h3>
                <p class="pricing-faq__a">No. AnswerEngineWP works alongside your existing SEO plugin. It adds AI-specific structure (Speakable markup, Answer Blocks, knowledge feeds) without duplicating the schema your SEO plugin already manages.</p>
            </div>

            <div class="pricing-faq">
                <h3 class="pricing-faq__q">What's included in the free version?</h3>
                <p class="pricing-faq__a">The free plugin includes your AI Visibility Score, AI Extraction Preview, Citation Simulation, basic Answer Blocks, and a basic /llms.txt generator. It's a fully functional plugin — not a trial.</p>
            </div>

            <div class="pricing-faq">
                <h3 class="pricing-faq__q">Can I test my site before installing?</h3>
                <p class="pricing-faq__a">Yes. Use the free <a href="https://aivisibilityscanner.com" target="_blank" rel="noopener">AI Visibility Scanner</a> to get your score without installing anything. It works on any website.</p>
            </div>

            <div class="pricing-faq">
                <h3 class="pricing-faq__q">Is there a money-back guarantee?</h3>
                <p class="pricing-faq__a">Yes. 30-day full refund, no questions asked. If AnswerEngineWP doesn't improve your AI visibility score, you get your money back.</p>
            </div>

            <div class="pricing-faq">
                <h3 class="pricing-faq__q">What does the Agency license include?</h3>
                <p class="pricing-faq__a">The Agency license covers 25 sites, includes bulk scanning (50 URLs via CSV), multisite entity sync, unbranded PDF audit reports you can share with clients, and headless API access for custom integrations.</p>
            </div>
        </div>
    </div>
</div>
</main>

<?php get_footer(); ?>
