<footer class="site-footer">
    <div class="container">
        <div class="site-footer__links">
            <a href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>" class="site-footer__link">Methodology</a>
            <a href="<?php echo esc_url( home_url( '/leaderboard/' ) ); ?>" class="site-footer__link">Leaderboard</a>
            <a href="<?php echo esc_url( home_url( '/badge/' ) ); ?>" class="site-footer__link">AI Visibility Badge</a>
            <a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>" class="site-footer__link">Privacy</a>
            <a href="#" class="site-footer__link aewp-waitlist-trigger">Powered by AnswerEngineWP</a>
        </div>
        <p class="site-footer__copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> AI Visibility Scanner</p>
    </div>
</footer>

<!-- AnswerEngineWP Waitlist Modal -->
<div class="aewp-modal" id="aewpWaitlistModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="aewpModalTitle">
    <div class="aewp-modal__backdrop" id="aewpModalBackdrop"></div>
    <div class="aewp-modal__content">
        <button type="button" class="aewp-modal__close" id="aewpModalClose" aria-label="Close">&times;</button>
        <div class="aewp-modal__icon">
            <svg width="48" height="48" viewBox="0 0 28 28" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="28" height="28" rx="6" fill="#2563EB"/>
                <circle cx="14" cy="12" r="7" fill="none" stroke="white" stroke-width="2"/>
                <circle cx="14" cy="12" r="3" fill="white" opacity="0.7"/>
                <line x1="14" y1="19" x2="14" y2="24" stroke="white" stroke-width="2" stroke-linecap="round"/>
            </svg>
        </div>
        <h3 class="aewp-modal__title" id="aewpModalTitle">Get early access to AnswerEngineWP</h3>
        <p class="aewp-modal__desc">The WordPress plugin that fixes your AI visibility score automatically. Join the waitlist to be first in line when we launch.</p>
        <div class="aewp-modal__form" id="aewpWaitlistForm">
            <input type="email" id="aewpWaitlistEmail" class="aewp-modal__input" placeholder="you@company.com" autocomplete="email">
            <button type="button" class="aewp-modal__submit" id="aewpWaitlistSubmit">Join the Waitlist</button>
        </div>
        <p class="aewp-modal__note">No spam. We'll notify you the moment it's ready.</p>
        <p class="aewp-modal__error" id="aewpWaitlistError" style="display:none"></p>
        <p class="aewp-modal__success" id="aewpWaitlistSuccess" style="display:none">&#10003; You're on the list! We'll email you when AnswerEngineWP launches.</p>
    </div>
</div>

<script>
/* Modal open/close only — submit handler is in the post-wp_footer script */
(function() {
    var modal = document.getElementById('aewpWaitlistModal');
    var backdrop = document.getElementById('aewpModalBackdrop');
    var closeBtn = document.getElementById('aewpModalClose');
    var input = document.getElementById('aewpWaitlistEmail');

    function openModal(e) {
        if (e) e.preventDefault();
        modal.classList.add('is-open');
        document.body.style.overflow = 'hidden';
        setTimeout(function() { if (input) input.focus(); }, 100);
    }

    function closeModal() {
        modal.classList.remove('is-open');
        document.body.style.overflow = '';
    }

    document.addEventListener('click', function(e) {
        var trigger = e.target.closest('.aewp-waitlist-trigger');
        if (trigger) openModal(e);
    });

    if (backdrop) backdrop.addEventListener('click', closeModal);
    if (closeBtn) closeBtn.addEventListener('click', closeModal);

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.classList.contains('is-open')) closeModal();
    });
})();
</script>

<?php wp_footer(); ?>

<script>
/* Waitlist submit — runs after wp_footer() so aivsScanner nonce is available */
(function() {
    var form = document.getElementById('aewpWaitlistForm');
    var input = document.getElementById('aewpWaitlistEmail');
    var submitBtn = document.getElementById('aewpWaitlistSubmit');
    var success = document.getElementById('aewpWaitlistSuccess');
    var errorEl = document.getElementById('aewpWaitlistError');
    var noteEl = document.querySelector('.aewp-modal__note');

    var WAITLIST_URL = (typeof aivsScanner !== 'undefined' && aivsScanner.waitlistUrl)
        ? aivsScanner.waitlistUrl
        : '<?php echo esc_url_raw( rest_url( 'aivs/v1/waitlist' ) ); ?>';
    var NONCE = (typeof aivsScanner !== 'undefined' && aivsScanner.nonce)
        ? aivsScanner.nonce
        : '<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>';

    if (!submitBtn) return;

    function showFormError(msg) {
        if (errorEl) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
        if (input) input.style.borderColor = '#EF4444';
    }

    submitBtn.addEventListener('click', function() {
        var email = input ? input.value.trim() : '';
        if (!email || email.indexOf('@') === -1) {
            if (input) { input.style.borderColor = '#EF4444'; input.focus(); }
            return;
        }
        if (input) input.style.borderColor = '';
        if (errorEl) errorEl.style.display = 'none';
        submitBtn.disabled = true;
        submitBtn.textContent = 'Joining\u2026';

        var ctx = { source_page: window.location.pathname };
        var hashMeta = document.querySelector('meta[name="aivs-scan-hash"]');
        if (hashMeta) ctx.scan_hash = hashMeta.getAttribute('content');

        fetch(WAITLIST_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
            body: JSON.stringify({ email: email, context: ctx })
        })
        .then(function(r) {
            if (!r.ok) throw new Error('Server returned ' + r.status);
            return r.json();
        })
        .then(function(data) {
            if (data.success) {
                if (form) form.style.display = 'none';
                if (noteEl) noteEl.style.display = 'none';
                if (success) success.style.display = 'block';
            } else {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Join the Waitlist';
                showFormError((data && data.message) ? data.message : 'Something went wrong. Please try again.');
            }
        })
        .catch(function(err) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Join the Waitlist';
            showFormError('Connection error. Please try again.');
            console.error('Waitlist error:', err);
        });
    });

    if (input) {
        input.addEventListener('input', function() {
            input.style.borderColor = '';
            if (errorEl) errorEl.style.display = 'none';
        });
        input.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') submitBtn.click();
        });
    }
})();
</script>
</body>
</html>
