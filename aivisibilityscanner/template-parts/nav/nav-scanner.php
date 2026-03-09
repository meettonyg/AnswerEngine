<nav class="site-nav" id="siteNav">
    <div class="container">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="site-nav__logo">
            <svg class="site-nav__logo-icon" width="28" height="28" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="28" height="28" rx="6" fill="#2563EB"/>
                <circle cx="14" cy="12" r="7" fill="none" stroke="white" stroke-width="2"/>
                <circle cx="14" cy="12" r="3" fill="white" opacity="0.7"/>
                <line x1="14" y1="19" x2="14" y2="24" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
            AI Visibility Scanner
        </a>
        <button class="site-nav__toggle" id="navToggle" aria-expanded="false" aria-controls="navLinks" aria-label="Toggle navigation">
            <span class="site-nav__toggle-bar"></span>
            <span class="site-nav__toggle-bar"></span>
            <span class="site-nav__toggle-bar"></span>
        </button>
        <div class="site-nav__links" id="navLinks">
            <a href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>" class="site-nav__link">Methodology</a>
            <a href="<?php echo esc_url( home_url( '/leaderboard/' ) ); ?>" class="site-nav__link">Leaderboard</a>
            <a href="https://answerenginewp.com" class="site-nav__link" target="_blank" rel="noopener">Fix Your Score</a>
        </div>
    </div>
</nav>
