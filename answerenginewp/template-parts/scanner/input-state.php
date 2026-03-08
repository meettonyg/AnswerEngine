<div class="scanner__input-state" id="scannerInput">
    <div class="scanner__modes" id="scannerModes">
        <button type="button" class="scanner__mode scanner__mode--active" data-mode="self">Scan Your Site</button>
        <button type="button" class="scanner__mode" data-mode="prospect">Scan a Prospect</button>
        <button type="button" class="scanner__mode" data-mode="compare">Compare Competitors</button>
    </div>

    <h1 class="scanner__h1" id="scannerH1">Is your website invisible to AI?</h1>
    <p class="scanner__sub" id="scannerSub">Enter any URL. Get your AI Visibility Score in under 10 seconds.</p>

    <div class="scanner__form">
        <label class="scanner__url-label" id="scanUrlLabel" style="display:none">Website URL</label>
        <input type="url" id="scanUrl" class="scanner__url-input"
               placeholder="https://yoursite.com" autocomplete="off">
        <div class="scanner__error" id="scanError">
            Please enter a valid URL (e.g. https://yoursite.com)
        </div>

        <button type="button" class="scanner__compare-toggle" id="compareToggle">
            Compare against a competitor &rarr;
        </button>
        <label class="scanner__compare-label" id="compareUrlLabel" style="display:none">Competitor URL</label>
        <input type="url" id="compareUrl" class="scanner__compare-input"
               placeholder="https://competitor.com" autocomplete="off">

        <button type="button" class="scanner__submit" id="scanSubmit">
            Scan Now &rarr;
        </button>
        <p class="scanner__micro" id="scannerMicro">Free. No login required. Works on any website.</p>
    </div>
</div>
