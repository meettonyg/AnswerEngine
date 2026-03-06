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

        <!-- Email Capture (optional, non-blocking) -->
        <div class="email-capture" id="emailCapture">
            <p class="email-capture__heading">Get your full report + improvement tips by email</p>
            <div class="email-capture__form">
                <input type="email" id="emailInput" class="email-capture__input"
                       placeholder="you@company.com" autocomplete="email">
                <button type="button" class="email-capture__submit" id="emailSubmit">Send Report</button>
            </div>
            <p class="email-capture__note">Optional. We'll send your PDF report and 3 actionable tips. No spam, ever.</p>
            <p class="email-capture__success" id="emailSuccess" style="display:none">Sent! Check your inbox.</p>
        </div>

        <!-- CTAs -->
        <div class="scanner-results__ctas">
            <div class="scanner-results__cta-primary">
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener" id="installCta">
                    Fix this instantly &rarr; Install AnswerEngineWP (Free)
                </a>
            </div>
            <div class="scanner-results__cta-actions">
                <button type="button" class="btn btn--outline" id="downloadPdf">Download PDF Report</button>
                <button type="button" class="btn btn--outline" id="shareScore">Share Score</button>
                <button type="button" class="btn btn--outline" id="copyBadge">Copy Badge Snippet</button>
            </div>
            <button type="button" class="scanner-results__reset" id="scanReset">&larr; Scan another URL</button>
        </div>
    </div>
</div>
