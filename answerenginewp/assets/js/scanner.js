/**
 * AnswerEngineWP Scanner
 *
 * Full client-side state management for the scanner page.
 * States: input -> loading -> results
 */

(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Config
  // ---------------------------------------------------------------------------
  var API_URL = (typeof aewpScanner !== 'undefined') ? aewpScanner.apiUrl : '/wp-json/aewp/v1/scan';
  var API_NONCE = (typeof aewpScanner !== 'undefined') ? aewpScanner.nonce : '';
  var EMAIL_URL = (typeof aewpScanner !== 'undefined') ? aewpScanner.emailUrl : '/wp-json/aewp/v1/email';
  var SITE_URL = (typeof aewpScanner !== 'undefined') ? aewpScanner.siteUrl : '';

  var STATUS_MESSAGES = [
    'Fetching page content\u2026',
    'Detecting schema types\u2026',
    'Analyzing content structure\u2026',
    'Checking for FAQ blocks\u2026',
    'Measuring entity density\u2026',
    'Detecting knowledge feeds\u2026',
    'Calculating your AI Visibility Score\u2026'
  ];
  var STATUS_OVERTIME = 'Still analyzing \u2014 this page has a lot of content\u2026';
  var MIN_LOADING_MS = 4000;
  var STATUS_INTERVAL_MS = 1500;

  var TIER_CONFIG = (typeof aewpScanner !== 'undefined' && aewpScanner.tierConfig) ? aewpScanner.tierConfig : [
    { min: 90, key: 'authority', label: 'AI Authority', color: '#22C55E', class: 'tier-green' },
    { min: 70, key: 'extractable', label: 'AI Extractable', color: '#3B82F6', class: 'tier-blue' },
    { min: 40, key: 'readable', label: 'AI Readable', color: '#EAB308', class: 'tier-amber' },
    { min: 0, key: 'invisible', label: 'Invisible to AI', color: '#EF4444', class: 'tier-red' }
  ];

  function getTierForScore(score) {
    for (var i = 0; i < TIER_CONFIG.length; i++) {
      if (score >= TIER_CONFIG[i].min) return TIER_CONFIG[i];
    }
    return TIER_CONFIG[TIER_CONFIG.length - 1];
  }

  // Legacy lookup by key for backwards compatibility with API responses.
  var TIER_COLORS = {};
  TIER_CONFIG.forEach(function(t) { TIER_COLORS[t.key] = t.color; });

  // ---------------------------------------------------------------------------
  // DOM refs
  // ---------------------------------------------------------------------------
  var els = {};

  function cacheDom() {
    els.inputState   = document.getElementById('scannerInput');
    els.loadingState = document.getElementById('scannerLoading');
    els.resultsState = document.getElementById('scannerResults');
    els.scanUrl      = document.getElementById('scanUrl');
    els.scanError    = document.getElementById('scanError');
    els.compareToggle = document.getElementById('compareToggle');
    els.compareUrl   = document.getElementById('compareUrl');
    els.scanSubmit   = document.getElementById('scanSubmit');
    els.loadingUrl   = document.getElementById('loadingUrl');
    els.progressFill = document.getElementById('progressFill');
    els.loadingStatus = document.getElementById('loadingStatus');
    els.scoreGaugeFill = document.getElementById('scoreGaugeFill');
    els.scoreValue   = document.getElementById('scoreValue');
    els.scoreTier    = document.getElementById('scoreTier');
    els.scoreTierMsg = document.getElementById('scoreTierMessage');
    els.subScoresList = document.getElementById('subScoresList');
    els.fixesList    = document.getElementById('fixesList');
    els.projectedScore = document.getElementById('projectedScore');
    els.citationPrompt = document.getElementById('citationPrompt');
    els.citationVerdict = document.getElementById('citationVerdict');
    els.citationReasons = document.getElementById('citationReasons');
    els.competitorGap = document.getElementById('competitorGap');
    els.compTableBody = document.getElementById('competitorTableBody');
    els.compYourUrl  = document.getElementById('compYourUrl');
    els.compTheirUrl = document.getElementById('compTheirUrl');
    els.extractionFound = document.getElementById('extractionFound');
    els.extractionMissing = document.getElementById('extractionMissing');
    els.downloadPdf  = document.getElementById('downloadPdf');
    els.shareScore   = document.getElementById('shareScore');
    els.copyBadge    = document.getElementById('copyBadge');
    els.scanReset    = document.getElementById('scanReset');
    els.installCta   = document.getElementById('installCta');
    els.emailInput   = document.getElementById('emailInput');
    els.emailSubmit  = document.getElementById('emailSubmit');
    els.emailSuccess = document.getElementById('emailSuccess');
    els.emailCapture = document.getElementById('emailCapture');
  }

  // ---------------------------------------------------------------------------
  // State management
  // ---------------------------------------------------------------------------
  function showState(state) {
    els.inputState.style.display   = state === 'input'   ? '' : 'none';
    els.loadingState.style.display = state === 'loading' ? '' : 'none';
    els.resultsState.style.display = state === 'results' ? '' : 'none';
  }

  // ---------------------------------------------------------------------------
  // URL Validation
  // ---------------------------------------------------------------------------
  function normalizeUrl(val) {
    val = val.trim();
    if (!/^https?:\/\//i.test(val)) {
      val = 'https://' + val;
    }
    return val;
  }

  function isValidUrl(val) {
    if (!val.trim()) return false;
    try {
      var parsed = new URL(normalizeUrl(val));
      return parsed.hostname.indexOf('.') !== -1;
    } catch (e) {
      return false;
    }
  }

  function showError(msg) {
    els.scanError.textContent = msg;
    els.scanError.classList.add('is-visible');
  }

  function clearError() {
    els.scanError.classList.remove('is-visible');
  }

  // ---------------------------------------------------------------------------
  // Loading state
  // ---------------------------------------------------------------------------
  var statusTimer = null;
  var statusIndex = 0;

  function startLoading(url) {
    showState('loading');
    els.loadingUrl.textContent = url;
    els.progressFill.style.width = '0%';
    statusIndex = 0;
    els.loadingStatus.textContent = STATUS_MESSAGES[0];

    // Progress bar animation
    var progress = 0;
    var progressTimer = setInterval(function () {
      progress += Math.random() * 8 + 2;
      if (progress > 90) progress = 90;
      els.progressFill.style.width = progress + '%';
    }, 400);

    // Status message cycling
    statusTimer = setInterval(function () {
      statusIndex++;
      if (statusIndex < STATUS_MESSAGES.length) {
        els.loadingStatus.style.opacity = '0';
        setTimeout(function () {
          els.loadingStatus.textContent = STATUS_MESSAGES[statusIndex];
          els.loadingStatus.style.opacity = '1';
        }, 150);
      } else {
        els.loadingStatus.style.opacity = '0';
        setTimeout(function () {
          els.loadingStatus.textContent = STATUS_OVERTIME;
          els.loadingStatus.style.opacity = '1';
        }, 150);
      }
    }, STATUS_INTERVAL_MS);

    return function stopLoading() {
      clearInterval(progressTimer);
      clearInterval(statusTimer);
      els.progressFill.style.width = '100%';
    };
  }

  // ---------------------------------------------------------------------------
  // API call
  // ---------------------------------------------------------------------------
  function scanUrl(url, competitorUrl) {
    var body = { url: url };
    if (competitorUrl) {
      body.competitor_url = competitorUrl;
    }

    var headers = { 'Content-Type': 'application/json' };
    if (API_NONCE) {
      headers['X-WP-Nonce'] = API_NONCE;
    }

    var fetchOpts = {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(body)
    };

    // 30s timeout via AbortController
    var controller = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var timeoutId = null;
    if (controller) {
      fetchOpts.signal = controller.signal;
      timeoutId = setTimeout(function () { controller.abort(); }, 30000);
    }

    return fetch(API_URL, fetchOpts).then(function (res) {
      if (timeoutId) clearTimeout(timeoutId);
      if (res.status === 429) {
        return res.json().then(function (data) {
          throw new Error('RATE_LIMITED:' + (data.retry_after || 60));
        });
      }
      if (!res.ok) {
        throw new Error('API_ERROR:' + res.status);
      }
      return res.json();
    }).catch(function (err) {
      if (timeoutId) clearTimeout(timeoutId);
      if (err.name === 'AbortError') {
        throw new Error('TIMEOUT');
      }
      throw err;
    });
  }

  // ---------------------------------------------------------------------------
  // Render results
  // ---------------------------------------------------------------------------
  function getTierPillClass(tier) {
    var map = {
      invisible: 'pill--tier-red',
      readable: 'pill--tier-amber',
      extractable: 'pill--tier-blue',
      authority: 'pill--tier-green'
    };
    return map[tier] || 'pill--tier-red';
  }

  function renderResults(data) {
    // Score gauge
    var offset = 283 - (283 * data.score / 100);
    var color = data.tier_color || TIER_COLORS[data.tier] || '#EF4444';
    els.scoreGaugeFill.style.stroke = color;
    els.scoreGaugeFill.style.strokeDashoffset = offset;
    els.scoreValue.textContent = data.score;

    // Tier badge + message
    els.scoreTier.innerHTML = '<span class="pill pill--tier ' + getTierPillClass(data.tier) + '">' +
      escapeHtml(data.tier_label) + '</span>';
    els.scoreTierMsg.textContent = data.tier_message;

    // Sub-scores
    renderSubScores(data.sub_scores);

    // Fixes
    renderFixes(data.fixes, data.projected_score);

    // Citation simulation
    renderCitation(data.citation_simulation);

    // Competitor gap
    if (data.competitor) {
      renderCompetitor(data, data.competitor);
    }

    // Extraction preview
    renderExtraction(data.extraction);

    // Wire up CTAs
    if (data.pdf_url) {
      els.downloadPdf.onclick = function () {
        trackEvent('pdf_downloaded');
        window.open(data.pdf_url, '_blank');
      };
    }

    if (data.share_url) {
      els.shareScore.onclick = function () {
        trackEvent('score_shared');
        if (navigator.clipboard) {
          navigator.clipboard.writeText(data.share_url).then(function () {
            els.shareScore.textContent = 'Link copied!';
            setTimeout(function () { els.shareScore.textContent = 'Share Score'; }, 2000);
          });
        } else {
          prompt('Copy this link:', data.share_url);
        }
      };
    }

    // Badge snippet — available for all scores.
    if (data.hash) {
      els.copyBadge.style.display = '';
      els.copyBadge.onclick = function () {
        trackEvent('badge_copied');
        var snippet = '<a href="' + SITE_URL + '/score/' + data.hash + '" title="AI Visibility Score: ' +
          data.score + '/100 — ' + data.tier_label + '" style="display:inline-block;text-decoration:none">' +
          '<img src="' + SITE_URL + '/wp-json/aewp/v1/badge/' + data.hash + '.svg?variant=small" alt="AI Visibility Score: ' +
          data.score + '/100" width="220" height="60"></a>';
        if (navigator.clipboard) {
          navigator.clipboard.writeText(snippet).then(function () {
            els.copyBadge.textContent = 'Copied!';
            setTimeout(function () { els.copyBadge.textContent = 'Copy Badge Snippet'; }, 2000);
          });
        } else {
          prompt('Copy this HTML:', snippet);
        }
      };
    } else {
      els.copyBadge.style.display = 'none';
    }

    // Email capture
    if (els.emailSubmit && data.hash) {
      els.emailSubmit.onclick = function () {
        var email = els.emailInput ? els.emailInput.value.trim() : '';
        if (!email || email.indexOf('@') === -1) {
          els.emailInput.style.borderColor = '#EF4444';
          return;
        }
        els.emailInput.style.borderColor = '';
        els.emailSubmit.disabled = true;
        els.emailSubmit.textContent = 'Sending…';

        var emailUrl = EMAIL_URL;
        fetch(emailUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ email: email, hash: data.hash })
        }).then(function (res) { return res.json(); })
          .then(function (result) {
            if (result.success) {
              els.emailCapture.querySelector('.email-capture__form').style.display = 'none';
              els.emailCapture.querySelector('.email-capture__note').style.display = 'none';
              els.emailSuccess.style.display = '';
              trackEvent('email_captured');
            } else {
              els.emailSubmit.disabled = false;
              els.emailSubmit.textContent = 'Send Report';
              els.emailInput.style.borderColor = '#EF4444';
            }
          }).catch(function () {
            els.emailSubmit.disabled = false;
            els.emailSubmit.textContent = 'Send Report';
          });
      };
    }

    showState('results');
    trackEvent('scan_completed');
  }

  function renderSubScores(subScores) {
    var html = '';
    var keys = ['schema_completeness', 'content_structure', 'faq_coverage', 'summary_presence', 'feed_readiness', 'entity_density'];
    keys.forEach(function (key) {
      var sub = subScores[key];
      if (!sub) return;
      var color = getSubScoreColor(sub.score);
      html += '<div class="sub-score">' +
        '<div class="sub-score__header">' +
          '<span class="sub-score__label">' + escapeHtml(sub.label) + '</span>' +
          '<span class="sub-score__value" style="color:' + color + '">' + sub.score + '/100</span>' +
        '</div>' +
        '<div class="sub-score__bar">' +
          '<div class="sub-score__fill" style="width:' + sub.score + '%;background:' + color + '"></div>' +
        '</div>' +
      '</div>';
    });
    els.subScoresList.innerHTML = html;
  }

  function getSubScoreColor(score) {
    return getTierForScore(score).color;
  }

  function renderFixes(fixes, projectedScore) {
    if (!fixes || !fixes.length) {
      document.getElementById('fixes').style.display = 'none';
      return;
    }
    var html = '';
    fixes.forEach(function (fix) {
      html += '<div class="fix-card">' +
        '<div class="fix-card__header">' +
          '<span class="fix-card__title">' + escapeHtml(fix.title) + '</span>' +
          '<span class="fix-card__points">+' + fix.points + ' pts</span>' +
        '</div>' +
        '<p class="fix-card__desc">' + escapeHtml(fix.description) + '</p>' +
      '</div>';
    });
    els.fixesList.innerHTML = html;

    if (projectedScore) {
      els.projectedScore.innerHTML = 'Projected score after fixes: <strong>' + projectedScore + '/100</strong>';
    }
  }

  function renderCitation(citation) {
    if (!citation) {
      document.getElementById('citationSim').style.display = 'none';
      return;
    }
    els.citationPrompt.textContent = '"' + citation.prompt + '"';

    var verdict = citation.would_cite
      ? 'Based on your page structure, AI systems would likely cite your content for this query.'
      : 'Based on your page structure, AI systems would likely skip your content for this query.';
    els.citationVerdict.textContent = verdict;

    var reasonsHtml = '';
    if (citation.reasons && citation.reasons.length) {
      citation.reasons.forEach(function (reason) {
        var iconClass = citation.would_cite ? 'citation-sim__source--cited' : 'citation-sim__source--not-cited';
        var icon = citation.would_cite
          ? '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M5 7l1.5 1.5L9 5.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
          : '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M5 5l4 4M9 5l-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
        reasonsHtml += '<div class="citation-sim__source ' + iconClass + '">' + icon + ' ' + escapeHtml(reason) + '</div>';
      });
    }
    els.citationReasons.innerHTML = reasonsHtml;
  }

  function renderCompetitor(data, competitor) {
    els.competitorGap.style.display = '';
    els.compYourUrl.textContent = data.url || 'Your Site';
    els.compTheirUrl.textContent = competitor.url || 'Competitor';

    var subKeys = ['schema_completeness', 'content_structure', 'faq_coverage', 'summary_presence', 'feed_readiness', 'entity_density'];

    // Render bar chart comparison above the table.
    var barsContainer = document.getElementById('comparisonBars');
    if (barsContainer) {
      barsContainer.innerHTML = renderComparisonBars(data, competitor, subKeys);
    }

    // Keep the table as detail view.
    var html = '<tr><td><strong>Overall Score</strong></td>' +
      '<td class="score-cell" style="color:' + (data.tier_color || '#EF4444') + '">' + data.score + '</td>' +
      '<td class="score-cell" style="color:' + (TIER_COLORS[competitor.tier] || '#EF4444') + '">' + competitor.score + '</td></tr>';

    subKeys.forEach(function (key) {
      var yours = data.sub_scores[key];
      var theirs = competitor.sub_scores ? competitor.sub_scores[key] : null;
      if (!yours) return;
      html += '<tr><td>' + escapeHtml(yours.label) + '</td>' +
        '<td class="score-cell">' + yours.score + '</td>' +
        '<td class="score-cell">' + (theirs ? theirs.score : '—') + '</td></tr>';
    });

    els.compTableBody.innerHTML = html;
  }

  function renderComparisonBars(data, competitor, subKeys) {
    var rows = [{ label: 'Overall Score', yours: data.score, theirs: competitor.score }];
    subKeys.forEach(function (key) {
      var yours = data.sub_scores[key];
      var theirs = competitor.sub_scores ? competitor.sub_scores[key] : null;
      if (!yours) return;
      rows.push({
        label: yours.label,
        yours: yours.score,
        theirs: theirs ? theirs.score : 0
      });
    });

    var html = '<div class="comparison-chart">';
    html += '<div class="comparison-chart__legend">';
    html += '<span class="comparison-chart__legend-item"><span class="comparison-chart__legend-dot" style="background:#2563EB"></span>' + escapeHtml(data.url || 'Your Site') + '</span>';
    html += '<span class="comparison-chart__legend-item"><span class="comparison-chart__legend-dot" style="background:#64748B"></span>' + escapeHtml(competitor.url || 'Competitor') + '</span>';
    html += '</div>';

    rows.forEach(function (row) {
      var delta = row.yours - row.theirs;
      var deltaStr = delta > 0 ? '+' + delta : '' + delta;
      var deltaClass = delta > 0 ? 'comparison-row__delta--positive' : (delta < 0 ? 'comparison-row__delta--negative' : 'comparison-row__delta--neutral');
      var tint = delta < 0 ? 'background:rgba(239,68,68,0.05)' : (delta > 0 ? 'background:rgba(34,197,94,0.05)' : '');

      html += '<div class="comparison-row" style="' + tint + '">';
      html += '<div class="comparison-row__label">' + escapeHtml(row.label) + '</div>';
      html += '<div class="comparison-row__bars">';
      html += '<div class="comparison-row__bar-group"><div class="comparison-row__bar-track"><div class="comparison-row__bar-fill comparison-row__bar-fill--yours" style="width:' + row.yours + '%"></div></div><span class="comparison-row__bar-value">' + row.yours + '</span></div>';
      html += '<div class="comparison-row__bar-group"><div class="comparison-row__bar-track"><div class="comparison-row__bar-fill comparison-row__bar-fill--comp" style="width:' + row.theirs + '%"></div></div><span class="comparison-row__bar-value">' + row.theirs + '</span></div>';
      html += '</div>';
      html += '<div class="comparison-row__delta ' + deltaClass + '">' + deltaStr + '</div>';
      html += '</div>';
    });

    html += '</div>';
    return html;
  }

  function renderExtraction(extraction) {
    if (!extraction) {
      document.getElementById('extraction').style.display = 'none';
      return;
    }

    var foundHtml = '';
    if (extraction.schema_types && extraction.schema_types.length) {
      extraction.schema_types.forEach(function (t) {
        foundHtml += '<div class="extraction__item extraction__item--found">' +
          '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#10B981" stroke-width="1.5"/><path d="M5 7l1.5 1.5L9 5.5" stroke="#10B981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
          escapeHtml(t) + ' schema</div>';
      });
    }
    if (extraction.entities && extraction.entities.length) {
      extraction.entities.forEach(function (e) {
        foundHtml += '<div class="extraction__item extraction__item--found">' +
          '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#10B981" stroke-width="1.5"/><path d="M5 7l1.5 1.5L9 5.5" stroke="#10B981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
          'Entity: ' + escapeHtml(e) + '</div>';
      });
    }
    if (extraction.headlines && extraction.headlines.length) {
      extraction.headlines.forEach(function (h) {
        foundHtml += '<div class="extraction__item extraction__item--found">' +
          '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#10B981" stroke-width="1.5"/><path d="M5 7l1.5 1.5L9 5.5" stroke="#10B981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>' +
          escapeHtml(h) + '</div>';
      });
    }
    els.extractionFound.innerHTML = foundHtml || '<p class="text-small" style="color:var(--gray-500)">No extractable signals found.</p>';

    var missingHtml = '';
    if (extraction.missing && extraction.missing.length) {
      extraction.missing.forEach(function (m) {
        missingHtml += '<div class="extraction__item extraction__item--missing">' +
          '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#EF4444" stroke-width="1.5"/><path d="M5 5l4 4M9 5l-4 4" stroke="#EF4444" stroke-width="1.5" stroke-linecap="round"/></svg>' +
          escapeHtml(m) + '</div>';
      });
    }
    els.extractionMissing.innerHTML = missingHtml || '<p class="text-small" style="color:var(--gray-500)">Nothing missing — great job!</p>';
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------
  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function trackEvent(name) {
    if (typeof plausible !== 'undefined') {
      plausible(name);
    }
  }

  // ---------------------------------------------------------------------------
  // Main scan flow
  // ---------------------------------------------------------------------------
  function handleScan() {
    var urlVal = els.scanUrl.value.trim();

    if (!urlVal) {
      showError('Please enter a valid URL (e.g. https://yoursite.com)');
      return;
    }

    if (!isValidUrl(urlVal)) {
      showError("That doesn't look like a valid URL. Try including https://");
      return;
    }

    clearError();

    var url = normalizeUrl(urlVal);
    var competitorUrl = null;
    if (els.compareUrl && els.compareUrl.classList.contains('is-visible') && els.compareUrl.value.trim()) {
      competitorUrl = normalizeUrl(els.compareUrl.value);
    }

    trackEvent('scan_started');
    if (competitorUrl) trackEvent('competitor_added');

    var stopLoading = startLoading(url);
    var loadingStarted = Date.now();

    scanUrl(url, competitorUrl)
      .then(function (data) {
        var elapsed = Date.now() - loadingStarted;
        var remaining = MIN_LOADING_MS - elapsed;
        if (remaining > 0) {
          return new Promise(function (resolve) {
            setTimeout(function () { resolve(data); }, remaining);
          });
        }
        return data;
      })
      .then(function (data) {
        stopLoading();
        if (data.success) {
          try {
            renderResults(data);
          } catch (renderErr) {
            showState('input');
            showError('Something went wrong displaying your results. Please try again.');
          }
        } else {
          showState('input');
          showError(data.message || 'Something went wrong. Try again.');
        }
      })
      .catch(function (err) {
        stopLoading();
        showState('input');
        trackEvent('scan_error');

        var msg = err.message || '';
        if (msg.indexOf('RATE_LIMITED') === 0) {
          var minutes = Math.ceil(parseInt(msg.split(':')[1], 10) / 60);
          showError("You've reached the scan limit. Try again in " + minutes + ' minutes.');
        } else if (msg === 'TIMEOUT') {
          showError('The scan timed out after 30 seconds. The site may be too slow to respond. Try again?');
        } else if (msg.indexOf('API_ERROR') === 0) {
          showError("We couldn't scan that URL. The site may be blocking our requests. Try a different URL.");
        } else {
          showError('This is taking longer than expected. The site may be slow to respond. Try again?');
        }
      });
  }

  // ---------------------------------------------------------------------------
  // Init
  // ---------------------------------------------------------------------------
  function init() {
    cacheDom();

    // Nav scroll
    var nav = document.getElementById('siteNav');
    if (nav) {
      var scrolled = false;
      window.addEventListener('scroll', function () {
        var should = window.scrollY > 40;
        if (should !== scrolled) {
          scrolled = should;
          nav.classList.toggle('site-nav--scrolled', scrolled);
        }
      }, { passive: true });
    }

    // Compare toggle
    if (els.compareToggle && els.compareUrl) {
      els.compareToggle.addEventListener('click', function () {
        els.compareUrl.classList.toggle('is-visible');
        if (els.compareUrl.classList.contains('is-visible')) {
          els.compareUrl.focus();
        }
      });
    }

    // Scan submit
    if (els.scanSubmit) {
      els.scanSubmit.addEventListener('click', handleScan);
    }

    // Enter key
    if (els.scanUrl) {
      els.scanUrl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') handleScan();
      });
      els.scanUrl.addEventListener('input', clearError);
    }

    // Reset
    if (els.scanReset) {
      els.scanReset.addEventListener('click', function () {
        showState('input');
        els.scanUrl.value = '';
        if (els.compareUrl) {
          els.compareUrl.value = '';
          els.compareUrl.classList.remove('is-visible');
        }
        els.scanUrl.focus();
      });
    }

    // Check for URL params (from hero scanner redirect)
    var params = new URLSearchParams(window.location.search);
    if (params.get('url')) {
      els.scanUrl.value = params.get('url');
      if (params.get('competitor') && els.compareUrl) {
        els.compareUrl.value = params.get('competitor');
        els.compareUrl.classList.add('is-visible');
      }
      // Auto-start scan
      setTimeout(handleScan, 300);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
