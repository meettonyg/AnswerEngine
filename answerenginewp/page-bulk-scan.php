<?php
/**
 * Bulk Scan — Private admin-only page for outreach campaigns.
 *
 * Paste a list of URLs, scan them all, view results in a table, export CSV.
 * Not indexed by search engines. Requires WordPress admin login.
 *
 * @package AnswerEngineWP
 */

// Gate: must be logged-in admin.
if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
    wp_redirect( wp_login_url( home_url( '/bulk-scan/' ) ) );
    exit;
}

get_header();
?>

<main class="bulk-scan">
<section class="bulk-scan__hero">
    <div class="container">
        <h1 class="bulk-scan__title">Bulk AI Visibility Scanner</h1>
        <p class="bulk-scan__sub">Paste URLs (one per line), scan them all, and export results for outreach.</p>
    </div>
</section>

<section class="bulk-scan__workspace">
    <div class="container">
        <!-- Input panel -->
        <div class="bulk-scan__input-panel" id="bulkInputPanel">
            <label for="bulkUrls" class="bulk-scan__label">URLs to scan</label>
            <textarea id="bulkUrls" class="bulk-scan__textarea"
                      placeholder="https://example.com&#10;https://anothersite.com&#10;https://thirdsite.org"
                      rows="10"></textarea>
            <div class="bulk-scan__input-row">
                <span class="bulk-scan__url-count" id="urlCount">0 URLs</span>
                <button type="button" class="bulk-scan__btn bulk-scan__btn--primary" id="bulkStart">
                    Start Scanning &rarr;
                </button>
            </div>
        </div>

        <!-- Progress -->
        <div class="bulk-scan__progress" id="bulkProgress" style="display:none;">
            <div class="bulk-scan__progress-bar">
                <div class="bulk-scan__progress-fill" id="progressFill" style="width:0%"></div>
            </div>
            <p class="bulk-scan__progress-text" id="progressText">0 / 0 scanned</p>
            <button type="button" class="bulk-scan__btn bulk-scan__btn--secondary" id="bulkPause">
                Pause
            </button>
        </div>

        <!-- Results table -->
        <div class="bulk-scan__results" id="bulkResults" style="display:none;">
            <div class="bulk-scan__results-header">
                <h2 class="bulk-scan__results-title">
                    Results <span id="resultsCount"></span>
                </h2>
                <div class="bulk-scan__results-actions">
                    <button type="button" class="bulk-scan__btn bulk-scan__btn--secondary" id="bulkExportCsv">
                        Export CSV
                    </button>
                    <button type="button" class="bulk-scan__btn bulk-scan__btn--secondary" id="bulkReset">
                        New Batch
                    </button>
                </div>
            </div>

            <div class="bulk-scan__table-wrap">
                <table class="bulk-scan__table" id="bulkTable">
                    <thead>
                        <tr>
                            <th class="bulk-scan__th">#</th>
                            <th class="bulk-scan__th bulk-scan__th--url">URL</th>
                            <th class="bulk-scan__th">Score</th>
                            <th class="bulk-scan__th">Tier</th>
                            <th class="bulk-scan__th">Schema</th>
                            <th class="bulk-scan__th">Structure</th>
                            <th class="bulk-scan__th">FAQ</th>
                            <th class="bulk-scan__th">Summary</th>
                            <th class="bulk-scan__th">Feeds</th>
                            <th class="bulk-scan__th">Entities</th>
                            <th class="bulk-scan__th">Top Fix</th>
                            <th class="bulk-scan__th">Report</th>
                        </tr>
                    </thead>
                    <tbody id="bulkTableBody"></tbody>
                </table>
            </div>
        </div>
    </div>
</section>
</main>

<style>
/* Bulk Scan — Private Page Styles */
.bulk-scan__hero {
    padding: 60px 0 32px;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}
.bulk-scan__title {
    font-family: 'Instrument Serif', Georgia, serif;
    font-size: 2.5rem;
    font-weight: 400;
    color: var(--gray-900);
    margin-bottom: 8px;
}
.bulk-scan__sub {
    color: var(--gray-500);
    font-size: 1.1rem;
}
.bulk-scan__workspace {
    padding: 40px 0 80px;
}
.bulk-scan__label {
    display: block;
    font-weight: 500;
    color: var(--gray-700);
    margin-bottom: 8px;
    font-size: 0.95rem;
}
.bulk-scan__textarea {
    width: 100%;
    font-family: 'JetBrains Mono', monospace;
    font-size: 0.88rem;
    line-height: 1.7;
    padding: 16px;
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    resize: vertical;
    background: var(--white);
    color: var(--gray-900);
    transition: border-color 0.2s;
}
.bulk-scan__textarea:focus {
    outline: none;
    border-color: var(--blue);
    box-shadow: 0 0 0 3px var(--blue-glow);
}
.bulk-scan__input-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 12px;
}
.bulk-scan__url-count {
    font-size: 0.9rem;
    color: var(--gray-400);
    font-variant-numeric: tabular-nums;
}
.bulk-scan__btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 10px 24px;
    border: none;
    border-radius: 8px;
    font-family: inherit;
    font-size: 0.95rem;
    font-weight: 500;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.bulk-scan__btn--primary {
    background: var(--blue);
    color: var(--white);
}
.bulk-scan__btn--primary:hover {
    background: var(--blue-hover);
}
.bulk-scan__btn--primary:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.bulk-scan__btn--secondary {
    background: var(--gray-100);
    color: var(--gray-700);
}
.bulk-scan__btn--secondary:hover {
    background: var(--gray-200);
}

/* Progress */
.bulk-scan__progress {
    margin-top: 24px;
}
.bulk-scan__progress-bar {
    height: 6px;
    background: var(--gray-200);
    border-radius: 3px;
    overflow: hidden;
}
.bulk-scan__progress-fill {
    height: 100%;
    background: var(--blue);
    border-radius: 3px;
    transition: width 0.3s ease;
}
.bulk-scan__progress-text {
    font-size: 0.9rem;
    color: var(--gray-500);
    margin-top: 8px;
    font-variant-numeric: tabular-nums;
}

/* Results */
.bulk-scan__results {
    margin-top: 32px;
}
.bulk-scan__results-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    flex-wrap: wrap;
    gap: 12px;
}
.bulk-scan__results-title {
    font-family: 'Instrument Serif', Georgia, serif;
    font-size: 1.5rem;
    font-weight: 400;
    color: var(--gray-900);
}
.bulk-scan__results-actions {
    display: flex;
    gap: 8px;
}
.bulk-scan__table-wrap {
    overflow-x: auto;
    border: 1px solid var(--gray-200);
    border-radius: 10px;
}
.bulk-scan__table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    white-space: nowrap;
}
.bulk-scan__th {
    padding: 10px 14px;
    text-align: left;
    font-weight: 600;
    color: var(--gray-500);
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
    font-size: 0.8rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.bulk-scan__th--url {
    min-width: 220px;
}
.bulk-scan__table td {
    padding: 10px 14px;
    border-bottom: 1px solid var(--gray-100);
    color: var(--gray-700);
    vertical-align: middle;
}
.bulk-scan__table tr:last-child td {
    border-bottom: none;
}
.bulk-scan__table tr:hover td {
    background: var(--gray-50);
}
.bulk-scan__score-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--white);
}
.bulk-scan__tier-pill {
    display: inline-block;
    padding: 2px 10px;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 500;
}
.bulk-scan__link {
    color: var(--blue);
    font-size: 0.85rem;
}
.bulk-scan__error-row td {
    color: var(--tier-red);
    font-style: italic;
}

/* No-index signal */
.bulk-scan { position: relative; }

@media (max-width: 768px) {
    .bulk-scan__title { font-size: 1.8rem; }
    .bulk-scan__input-row { flex-direction: column; gap: 12px; align-items: stretch; }
    .bulk-scan__results-header { flex-direction: column; align-items: flex-start; }
}
</style>

<?php get_footer(); ?>
