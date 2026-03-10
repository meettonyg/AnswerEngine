<div class="scanner__results-state" id="scannerResults" style="display:none">
    <div class="scanner-results">
        <!-- Score Hero -->
        <div class="scanner-results__hero">
            <div class="score-gauge" id="scoreGauge">
                <svg viewBox="0 0 100 100">
                    <circle class="score-gauge__track" cx="50" cy="50" r="45" stroke-width="8"/>
                    <circle class="score-gauge__fill" cx="50" cy="50" r="45" id="scoreGaugeFill"
                            stroke-dasharray="283" stroke-dashoffset="283"
                            transform="rotate(-90 50 50)"/>
                    <text class="score-gauge__value" x="50" y="45" id="scoreValue">0</text>
                    <text class="score-gauge__max" x="50" y="68">/100</text>
                </svg>
            </div>
            <div class="scanner-results__tier" id="scoreTier"></div>
            <p class="scanner-results__tier-message" id="scoreTierMessage"></p>
        </div>

        <!-- Critical Alerts (robots.txt + SPA) -->
        <div class="critical-alerts" id="criticalAlerts" style="display:none">
            <div class="critical-alert critical-alert--robots" id="robotsAlert" style="display:none">
                <div class="critical-alert__icon">&#9888;</div>
                <div class="critical-alert__content">
                    <h4 class="critical-alert__title">robots.txt Blocks AI Crawlers</h4>
                    <p class="critical-alert__desc" id="robotsAlertDesc"></p>
                    <a href="https://wordpress.org/plugins/answerenginewp/" class="critical-alert__cta" target="_blank" rel="noopener">
                        Fix with AEWP's Bot Manager &rarr;
                    </a>
                </div>
            </div>
            <div class="critical-alert critical-alert--spa" id="spaAlert" style="display:none">
                <div class="critical-alert__icon">&#9888;</div>
                <div class="critical-alert__content">
                    <h4 class="critical-alert__title">Client-Side Rendering Detected</h4>
                    <p class="critical-alert__desc">Your content is invisible to AI crawlers. Only <span id="spaWordCount">0</span> words of body text were found.</p>
                </div>
            </div>
        </div>

        <!-- Page Type Context -->
        <div class="page-type-context" id="pageTypeContext" style="display:none">
            <p class="page-type-context__info" id="pageTypeInfo"></p>
        </div>

        <!-- Sub-scores -->
        <div class="sub-scores" id="subScores">
            <h3 class="sub-scores__title">Score Breakdown</h3>
            <div id="subScoresList"></div>
        </div>

        <!-- Top 3 Fixes -->
        <div class="fixes" id="fixes">
            <h3 class="fixes__title">Your fastest path to AI visibility</h3>
            <p class="fixes__subtitle">Top 3 recommended fixes</p>
            <div id="fixesList"></div>
            <p class="fixes__projected" id="projectedScore"></p>
        </div>

        <!-- Citation Simulation -->
        <div class="citation-sim__card" id="citationSim" style="margin-bottom:48px">
            <span class="citation-sim__badge">Simulated</span>
            <div class="citation-sim__prompt">
                <div class="citation-sim__prompt-label">AI prompt</div>
                <p class="citation-sim__prompt-text" id="citationPrompt"></p>
            </div>
            <div class="citation-sim__response">
                <p class="citation-sim__response-text" id="citationVerdict"></p>
                <div class="citation-sim__sources" id="citationReasons"></div>
            </div>
        </div>

        <!-- Competitor Gap -->
        <div class="competitor-gap" id="competitorGap" style="display:none">
            <h3 class="competitor-gap__title">Competitor Structure Gap</h3>
            <div id="comparisonBars"></div>
            <table class="competitor-gap__table">
                <thead>
                    <tr>
                        <th>Signal</th>
                        <th id="compYourUrl">Your Site</th>
                        <th id="compTheirUrl">Competitor</th>
                    </tr>
                </thead>
                <tbody id="competitorTableBody"></tbody>
            </table>
        </div>

        <!-- Extraction Preview -->
        <div class="extraction" id="extraction">
            <h3 class="extraction__title">Extraction Preview</h3>
            <div class="extraction__grid">
                <div>
                    <div class="extraction__column-title extraction__column-title--found">Found</div>
                    <div id="extractionFound"></div>
                </div>
                <div>
                    <div class="extraction__column-title extraction__column-title--missing">Missing</div>
                    <div id="extractionMissing"></div>
                </div>
            </div>
        </div>

        <!-- How AI Sees You (Raw Text View) -->
        <div class="raw-text-view" id="rawTextView" style="display:none">
            <h3 class="raw-text-view__title">
                How AI Sees Your Page
                <button type="button" class="raw-text-view__toggle" id="rawTextToggle">Show &darr;</button>
            </h3>
            <div class="raw-text-view__content" id="rawTextContent" style="display:none">
                <pre class="raw-text-view__pre" id="rawTextPre"></pre>
            </div>
            <p class="raw-text-view__cta">Is this a mess? <a href="https://wordpress.org/plugins/answerenginewp/" target="_blank" rel="noopener">Install AnswerEngineWP</a> to generate a clean /llms-docs/ feed.</p>
        </div>

        <!-- Blindspot Upsell -->
        <div class="blindspot" id="blindspot" style="display:none">
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

        <!-- Email Capture — gates PDF download -->
        <div class="email-capture" id="emailCapture">
            <p class="email-capture__heading">Enter your email to unlock the PDF report</p>
            <div class="email-capture__form">
                <input type="email" id="emailInput" class="email-capture__input"
                       placeholder="you@company.com" autocomplete="email">
                <button type="button" class="email-capture__submit" id="emailSubmit">Unlock PDF Report</button>
            </div>
            <p class="email-capture__note">We'll email your PDF report + 3 actionable tips. No spam, ever.</p>
            <p class="email-capture__success" id="emailSuccess" style="display:none">&#10003; PDF unlocked! Check your inbox for the full report.</p>
        </div>

        <!-- CTAs -->
        <div class="scanner-results__ctas">
            <div class="scanner-results__cta-primary">
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener" id="installCta">
                    Improve your score &rarr; Get AnswerEngineWP
                </a>
            </div>
            <div class="scanner-results__cta-actions">
                <button type="button" class="btn btn--outline btn--locked" id="downloadPdf">&#128274; Download PDF Report</button>
                <button type="button" class="btn btn--outline" id="shareScore">Share Score</button>
                <button type="button" class="btn btn--outline" id="copyBadge">Copy Badge Snippet</button>
            </div>
            <button type="button" class="scanner-results__reset" id="scanReset">&larr; Scan another URL</button>
        </div>
    </div>
</div>
