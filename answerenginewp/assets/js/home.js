/**
 * AnswerEngineWP Homepage Interactions
 *
 * - Scroll-triggered fade-up animations
 * - Nav scroll effect
 * - Hero scanner card (redirects to /scanner/ on submit)
 */

(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Scroll Animations (IntersectionObserver)
  // ---------------------------------------------------------------------------
  function initScrollAnimations() {
    var elements = document.querySelectorAll('.fade-up, .fade-up-stagger');
    if (!elements.length) return;

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.15, rootMargin: '0px 0px -40px 0px' }
    );

    elements.forEach(function (el) {
      observer.observe(el);
    });
  }

  // ---------------------------------------------------------------------------
  // Nav Scroll Effect
  // ---------------------------------------------------------------------------
  function initNavScroll() {
    var nav = document.getElementById('siteNav');
    if (!nav) return;

    var scrolled = false;

    function checkScroll() {
      var shouldScroll = window.scrollY > 40;
      if (shouldScroll !== scrolled) {
        scrolled = shouldScroll;
        nav.classList.toggle('site-nav--scrolled', scrolled);
      }
    }

    window.addEventListener('scroll', checkScroll, { passive: true });
    checkScroll();
  }

  // ---------------------------------------------------------------------------
  // Hero Scanner Card
  // ---------------------------------------------------------------------------
  function initHeroScanner() {
    var input = document.getElementById('heroScanUrl');
    var btn = document.getElementById('heroScanBtn');
    var error = document.getElementById('heroScanError');
    var compareToggle = document.getElementById('heroCompareToggle');
    var compareInput = document.getElementById('heroCompareUrl');

    if (!input || !btn) return;

    // Compare toggle
    if (compareToggle && compareInput) {
      compareToggle.addEventListener('click', function () {
        compareInput.classList.toggle('is-visible');
        if (compareInput.classList.contains('is-visible')) {
          compareInput.focus();
        }
      });
    }

    function validateUrl(value) {
      if (!value.trim()) return false;
      var url = value.trim();
      if (!/^https?:\/\//i.test(url)) {
        url = 'https://' + url;
      }
      try {
        var parsed = new URL(url);
        return parsed.hostname.indexOf('.') !== -1;
      } catch (e) {
        return false;
      }
    }

    function handleScan() {
      var val = input.value.trim();

      if (!val) {
        error.textContent = 'Please enter a valid URL (e.g. https://yoursite.com)';
        error.classList.add('is-visible');
        return;
      }

      if (!validateUrl(val)) {
        error.textContent = "That doesn't look like a valid URL. Try including https://";
        error.classList.add('is-visible');
        return;
      }

      error.classList.remove('is-visible');

      // Build scanner URL with pre-filled values
      var scannerUrl = (typeof aewpHome !== 'undefined' && aewpHome.scannerUrl) ? aewpHome.scannerUrl : '/scanner/';
      var url = val;
      if (!/^https?:\/\//i.test(url)) {
        url = 'https://' + url;
      }
      scannerUrl += '?url=' + encodeURIComponent(url);

      if (compareInput && compareInput.classList.contains('is-visible') && compareInput.value.trim()) {
        var compUrl = compareInput.value.trim();
        if (!/^https?:\/\//i.test(compUrl)) {
          compUrl = 'https://' + compUrl;
        }
        scannerUrl += '&competitor=' + encodeURIComponent(compUrl);
      }

      window.location.href = scannerUrl;
    }

    btn.addEventListener('click', handleScan);
    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') {
        handleScan();
      }
    });

    // Clear error on input
    input.addEventListener('input', function () {
      error.classList.remove('is-visible');
    });
  }

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    initScrollAnimations();
    initNavScroll();
    initHeroScanner();
  });
})();
