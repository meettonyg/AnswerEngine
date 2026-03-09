/**
 * AnswerEngineWP Homepage Interactions
 *
 * - Scroll-triggered fade-up animations
 * - Nav scroll effect
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

    // Only toggle nav background on the homepage; inner pages keep it solid
    if (nav.classList.contains('site-nav--scrolled')) return;

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
  // Init
  // ---------------------------------------------------------------------------
  document.addEventListener('DOMContentLoaded', function () {
    initScrollAnimations();
    initNavScroll();
  });
})();
