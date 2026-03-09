<nav class="site-nav<?php if ( ! is_front_page() ) echo ' site-nav--scrolled'; ?>" id="siteNav">
    <div class="container">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-nav__logo">
            <svg class="site-nav__logo-icon" width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="28" height="28" rx="6" fill="#2563EB"/>
                <rect x="6" y="14" width="16" height="3" rx="1.5" fill="rgba(255,255,255,0.5)"/>
                <rect x="6" y="9" width="16" height="3" rx="1.5" fill="rgba(255,255,255,0.75)"/>
                <rect x="6" y="4" width="16" height="3" rx="1.5" fill="#fff"/>
            </svg>
            AnswerEngineWP
        </a>
        <button class="site-nav__toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Toggle navigation">
            <span class="site-nav__toggle-bar"></span>
            <span class="site-nav__toggle-bar"></span>
            <span class="site-nav__toggle-bar"></span>
        </button>
        <div class="site-nav__links" id="navLinks">
            <a href="<?php echo esc_url( home_url( '/docs/' ) ); ?>" class="site-nav__link">Docs</a>
            <a href="<?php echo esc_url( home_url( '/pricing/' ) ); ?>" class="site-nav__link">Pricing</a>
            <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="site-nav__link">Blog</a>
            <a href="https://aivisibilityscanner.com" class="site-nav__link" target="_blank" rel="noopener">Test Your Site &rarr;</a>
            <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--nav" target="_blank" rel="noopener">Download Plugin</a>
        </div>
    </div>
</nav>
