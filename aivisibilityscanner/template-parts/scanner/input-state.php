<div class="scanner__input-state" id="scannerInput">
    <h1 class="scanner__h1">Is your website invisible to AI?</h1>
    <p class="scanner__sub">Enter any URL. Get your AI Visibility Score in under 10 seconds.</p>

    <div class="scanner__form">
        <input type="url" id="scanUrl" class="scanner__url-input"
               placeholder="https://yoursite.com" autocomplete="off">
        <div class="scanner__error" id="scanError">
            Please enter a valid URL (e.g. https://yoursite.com)
        </div>

        <div class="scanner__page-type">
            <label for="pageType" class="scanner__page-type-label">What type of page is this?</label>
            <select id="pageType" class="scanner__page-type-select">
                <option value="auto" selected>Auto-detect</option>
                <option value="homepage">Homepage / Brand Page</option>
                <option value="blog_post">Blog Post / Article</option>
                <option value="product_page">Product Page</option>
                <option value="local_service">Local Service Page</option>
            </select>
        </div>

        <button type="button" class="scanner__compare-toggle" id="compareToggle">
            Compare against a competitor &rarr;
        </button>
        <input type="url" id="compareUrl" class="scanner__compare-input"
               placeholder="https://competitor.com" autocomplete="off">

        <button type="button" class="scanner__submit" id="scanSubmit">
            Scan Now &rarr;
        </button>
        <p class="scanner__micro">Free. No login required. Works on any website.</p>
    </div>
</div>
