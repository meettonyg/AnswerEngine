<section class="hero">
    <div class="container">
        <div class="hero__content fade-up">
            <h1 class="hero__h1">Is your website invisible to ChatGPT?</h1>
            <p class="hero__sub">Turn your WordPress site into something AI systems can read, extract, and cite — including ChatGPT, Perplexity, and Google AI Overviews.</p>
            <p class="hero__proof">Most WordPress sites score below 40/100 on the AI Visibility Score. Find out where you stand in under 10 seconds.</p>

            <div class="hero__ctas">
                <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--primary">Scan Your Site Free &rarr;</a>
                <p class="hero__secondary-link">or <a href="https://wordpress.org/plugins/answerenginewp/" target="_blank" rel="noopener">download the free WordPress plugin</a></p>
            </div>

            <p class="hero__companion">Works alongside Yoast and Rank Math. No conflicts. No replacements. No duplicate schema.</p>
        </div>

        <div class="hero-scanner fade-up" id="heroScanner">
            <div class="hero-scanner__label">AI Visibility Scanner</div>

            <div class="hero-scanner__input-wrap">
                <input type="url" id="heroScanUrl" class="hero-scanner__input" placeholder="https://yoursite.com" autocomplete="off">
                <button type="button" class="hero-scanner__btn" id="heroScanBtn">Scan Now &rarr;</button>
            </div>

            <div class="hero-scanner__error" id="heroScanError"></div>

            <button type="button" class="hero-scanner__compare" id="heroCompareToggle">Compare against a competitor &rarr;</button>
            <input type="url" id="heroCompareUrl" class="hero-scanner__compare-input" placeholder="https://competitor.com" autocomplete="off">

            <p class="hero-scanner__micro">Free. No login required. Works on any website.</p>
            <p class="hero-scanner__agency-link"><a href="<?php echo esc_url( home_url( '/scanner/?mode=prospect' ) ); ?>">Agency? Run a prospect audit &rarr;</a></p>
        </div>
    </div>
</section>
