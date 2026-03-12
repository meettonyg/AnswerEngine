/**
 * AI Visibility Scanner
 *
 * Full client-side state management for the scanner page.
 * States: input -> loading -> results
 */

(function () {
  'use strict';

  // ---------------------------------------------------------------------------
  // Config
  // ---------------------------------------------------------------------------
  var API_URL = (typeof aivsScanner !== 'undefined') ? aivsScanner.apiUrl : '/wp-json/aivs/v1/scan';
  var API_NONCE = (typeof aivsScanner !== 'undefined') ? aivsScanner.nonce : '';
  var EMAIL_URL = (typeof aivsScanner !== 'undefined') ? aivsScanner.emailUrl : '/wp-json/aivs/v1/email';
  var SITE_URL = (typeof aivsScanner !== 'undefined') ? aivsScanner.siteUrl : '';

  var STATUS_MESSAGES = [
    'Fetching page content\u2026',
    'Checking robots.txt for AI bot access\u2026',
    'Detecting schema types\u2026',
    'Analyzing content structure\u2026',
    'Checking for FAQ blocks\u2026',
    'Measuring entity density\u2026',
    'Detecting knowledge feeds\u2026',
    'Simulating AI extraction\u2026',
    'Calculating your AI Visibility Score\u2026'
  ];
  var STATUS_OVERTIME = 'Still analyzing \u2014 this page has a lot of content\u2026';
  var MIN_LOADING_MS = 4000;
  var STATUS_INTERVAL_MS = 1500;

  var TIER_CONFIG = (typeof aivsScanner !== 'undefined' && aivsScanner.tierConfig) ? aivsScanner.tierConfig : [
    { min: 90, key: 'authority', label: 'AI Authority', color: '#22C55E', class: 'tier-green' },
    { min: 70, key: 'extractable', label: 'AI Extractable', color: '#3B82F6', class: 'tier-blue' },
    { min: 40, key: 'readable', label: 'AI Readable', color: '#EAB308', class: 'tier-amber' },
    { min: 0, key: 'invisible', label: 'Invisible to AI', color: '#EF4444', class: 'tier-red' }
  ];

  // ---------------------------------------------------------------------------
  // AI Visibility Stack — Layer mapping
  // Fallback used only when the API doesn't include layer metadata.
  // Prefer API-provided sub.layer / sub.layer_name when available.
  // ---------------------------------------------------------------------------
  var LAYER_MAP_FALLBACK = {
    'crawl_access':        { layer: 1, label: 'Access' },
    'feed_readiness':      { layer: 1, label: 'Access' },
    'schema_completeness': { layer: 2, label: 'Understanding' },
    'entity_density':      { layer: 2, label: 'Understanding' },
    'content_structure':   { layer: 3, label: 'Extractability' },
    'faq_coverage':        { layer: 3, label: 'Extractability' },
    'summary_presence':    { layer: 3, label: 'Extractability' },
    'content_richness':    { layer: 3, label: 'Extractability' }
  };

  function getLayerInfo(key, sub) {
    // Prefer API-provided layer data (Gemini feedback: backend-driven)
    if (sub && sub.layer && sub.layer_name) {
      return { layer: sub.layer, label: sub.layer_name };
    }
    return LAYER_MAP_FALLBACK[key] || null;
  }

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
    els.pageType       = document.getElementById('pageType');
    els.criticalAlerts = document.getElementById('criticalAlerts');
    els.robotsAlert    = document.getElementById('robotsAlert');
    els.robotsAlertDesc = document.getElementById('robotsAlertDesc');
    els.spaAlert       = document.getElementById('spaAlert');
    els.spaWordCount   = document.getElementById('spaWordCount');
    els.rawTextView    = document.getElementById('rawTextView');
    els.rawTextToggle  = document.getElementById('rawTextToggle');
    els.rawTextContent = document.getElementById('rawTextContent');
    els.rawTextPre     = document.getElementById('rawTextPre');
    els.pageTypeContext = document.getElementById('pageTypeContext');
    els.pageTypeInfo   = document.getElementById('pageTypeInfo');
    els.blindspot      = document.getElementById('blindspot');
    els.terminalBody   = document.getElementById('terminalBody');
    els.stackSummary   = document.getElementById('stackSummary');
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

    // Clear and populate terminal
    if (els.terminalBody) {
      els.terminalBody.innerHTML = '';
      var line = document.createElement('div');
      line.className = 'terminal__line';
      line.textContent = STATUS_MESSAGES[0];
      els.terminalBody.appendChild(line);
    }

    // Progress bar animation
    var progress = 0;
    var progressTimer = setInterval(function () {
      progress += Math.random() * 8 + 2;
      if (progress > 90) progress = 90;
      els.progressFill.style.width = progress + '%';
    }, 400);

    // Status message cycling + terminal lines
    statusTimer = setInterval(function () {
      statusIndex++;
      var msg = statusIndex < STATUS_MESSAGES.length ? STATUS_MESSAGES[statusIndex] : STATUS_OVERTIME;
      els.loadingStatus.style.opacity = '0';
      setTimeout(function () {
        els.loadingStatus.textContent = msg;
        els.loadingStatus.style.opacity = '1';
      }, 150);

      // Append to terminal
      if (els.terminalBody) {
        var tLine = document.createElement('div');
        tLine.className = 'terminal__line';
        tLine.textContent = msg;
        els.terminalBody.appendChild(tLine);
        els.terminalBody.scrollTop = els.terminalBody.scrollHeight;
      }
    }, STATUS_INTERVAL_MS);

    return function stopLoading() {
      clearInterval(progressTimer);
      clearInterval(statusTimer);
      els.progressFill.style.width = '100%';

      // Add completion line to terminal
      if (els.terminalBody) {
        var doneLine = document.createElement('div');
        doneLine.className = 'terminal__line';
        doneLine.textContent = 'Analysis complete!';
        els.terminalBody.appendChild(doneLine);
      }
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
    if (els.pageType && els.pageType.value && els.pageType.value !== 'auto') {
      body.page_type = els.pageType.value;
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
        return res.json().then(function (data) {
          throw new Error('API_ERROR:' + (data.message || res.status));
        }).catch(function (parseErr) {
          if (parseErr.message && parseErr.message.indexOf('API_ERROR') === 0) throw parseErr;
          throw new Error('API_ERROR:' + res.status);
        });
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

    // Critical alerts (robots.txt + SPA detection)
    renderCriticalAlerts(data);

    // Page type context
    renderPageTypeContext(data);

    // Sub-scores
    renderSubScores(data.sub_scores);

    // AI Visibility Stack summary
    renderStackSummary(data.sub_scores);

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

    // How AI Sees You (raw text view)
    renderRawTextView(data);

    // Blindspot upsell
    if (els.blindspot) {
      els.blindspot.style.display = '';
    }

    // Wire up CTAs — PDF is gated behind email capture
    var pdfUnlocked = false;
    var storedPdfUrl = data.pdf_url || '';

    if (els.downloadPdf) {
      els.downloadPdf.onclick = function () {
        if (pdfUnlocked && storedPdfUrl) {
          trackEvent('pdf_downloaded');
          window.open(storedPdfUrl, '_blank');
          return;
        }
        // Scroll to email capture and highlight it
        if (els.emailCapture) {
          els.emailCapture.scrollIntoView({ behavior: 'smooth', block: 'center' });
          els.emailCapture.classList.add('email-capture--highlight');
          setTimeout(function () {
            els.emailCapture.classList.remove('email-capture--highlight');
          }, 2000);
          if (els.emailInput) els.emailInput.focus();
        }
      };
    }

    if (data.share_url && els.shareScore) {
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
    if (data.hash && els.copyBadge) {
      els.copyBadge.style.display = '';
      els.copyBadge.onclick = function () {
        trackEvent('badge_copied');
        var snippet = '<a href="' + SITE_URL + '/report/' + data.domain + '" title="AI Visibility Score: ' +
          data.score + '/100 — ' + data.tier_label + '" style="display:inline-block;text-decoration:none">' +
          '<img src="' + SITE_URL + '/wp-json/aivs/v1/badge/' + data.hash + '.svg?variant=small" alt="AI Visibility Score: ' +
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
    } else if (els.copyBadge) {
      els.copyBadge.style.display = 'none';
    }

    // Email capture — gates PDF download
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

              // Unlock PDF download
              pdfUnlocked = true;
              if (els.downloadPdf) {
                els.downloadPdf.textContent = 'Download PDF Report';
                els.downloadPdf.classList.remove('btn--locked');
              }

              // Auto-open the PDF after a brief delay
              if (storedPdfUrl) {
                setTimeout(function () {
                  trackEvent('pdf_downloaded');
                  window.open(storedPdfUrl, '_blank');
                }, 600);
              }
            } else {
              els.emailSubmit.disabled = false;
              els.emailSubmit.textContent = 'Unlock PDF Report';
              els.emailInput.style.borderColor = '#EF4444';
            }
          }).catch(function () {
            els.emailSubmit.disabled = false;
            els.emailSubmit.textContent = 'Unlock PDF Report';
          });
      };
    }

    showState('results');

    // Auto-scroll to results so the score gauge animation is front-and-center
    setTimeout(function () {
      els.resultsState.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }, 100);

    trackEvent('scan_completed');
  }

  function renderSubScores(subScores) {
    var html = '';
    var keys = ['crawl_access', 'feed_readiness', 'schema_completeness', 'entity_density', 'content_structure', 'faq_coverage', 'summary_presence', 'content_richness'];
    keys.forEach(function (key) {
      var sub = subScores[key];
      if (!sub) return;
      var color = getSubScoreColor(sub.score);
      var layerInfo = getLayerInfo(key, sub);
      var layerHtml = layerInfo
        ? ' <span class="sub-score__layer">Layer ' + escapeHtml(layerInfo.layer) + '</span>'
        : '';
      html += '<div class="sub-score">' +
        '<div class="sub-score__header">' +
          '<span class="sub-score__label">' + escapeHtml(sub.label) + layerHtml + '</span>' +
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

  function renderStackSummary(subScores) {
    var stackEl = document.getElementById('stackSummary');
    if (!stackEl) return;

    // Layer 1: Access = avg(crawl_access, feed_readiness)
    var crawlScore = subScores.crawl_access ? subScores.crawl_access.score : 0;
    var feedScore = subScores.feed_readiness ? subScores.feed_readiness.score : 0;
    var layer1 = subScores.crawl_access ? Math.round((crawlScore + feedScore) / 2) : feedScore;

    // Layer 2: Understanding = avg(schema_completeness, entity_density)
    var schemaScore = subScores.schema_completeness ? subScores.schema_completeness.score : 0;
    var entityScore = subScores.entity_density ? subScores.entity_density.score : 0;
    var layer2 = Math.round((schemaScore + entityScore) / 2);

    // Layer 3: Extractability = avg(content_structure, faq_coverage, summary_presence, content_richness)
    var structScore = subScores.content_structure ? subScores.content_structure.score : 0;
    var faqScore = subScores.faq_coverage ? subScores.faq_coverage.score : 0;
    var summaryScore = subScores.summary_presence ? subScores.summary_presence.score : 0;
    var richnessScore = subScores.content_richness ? subScores.content_richness.score : 0;
    var l3Count = 3 + (subScores.content_richness ? 1 : 0);
    var layer3 = Math.round((structScore + faqScore + summaryScore + richnessScore) / l3Count);

    var layers = [
      { id: 'stackScore1', score: layer1 },
      { id: 'stackScore2', score: layer2 },
      { id: 'stackScore3', score: layer3 }
    ];

    layers.forEach(function (l) {
      var el = document.getElementById(l.id);
      if (el) {
        var tier = getTierForScore(l.score);
        el.textContent = l.score + '/100';
        el.style.color = tier.color;
      }
    });

    stackEl.style.display = '';
  }

  // SYNC WARNING: This fallback logic is duplicated in page-report.php.
  // If you change the keyword list here, update it there too.
  // Ideally, the backend API should provide layer_num/layer_name directly.
  function inferFixLayer(fix) {
    // Prefer API-provided layer data (Gemini feedback: backend-driven)
    if (fix.layer_num && fix.layer_name) {
      return { num: fix.layer_num, label: fix.layer_name };
    }
    // Fallback: keyword-based inference (only used if API doesn't provide layer)
    var title = (fix.title || '').toLowerCase();
    if (title.indexOf('feed') !== -1 || title.indexOf('llms.txt') !== -1 ||
        title.indexOf('sitemap') !== -1 || title.indexOf('robots') !== -1 ||
        title.indexOf('manifest') !== -1 || title.indexOf('crawl') !== -1 ||
        title.indexOf('canonical') !== -1 || title.indexOf('ttfb') !== -1 ||
        title.indexOf('server response') !== -1) {
      return { num: 1, label: 'Access' };
    }
    if (title.indexOf('schema') !== -1 || title.indexOf('entity') !== -1 ||
        title.indexOf('speakable') !== -1 || title.indexOf('organization') !== -1 ||
        title.indexOf('person') !== -1 || title.indexOf('json-ld') !== -1) {
      return { num: 2, label: 'Understanding' };
    }
    return { num: 3, label: 'Extractability' };
  }

  function renderFixes(fixes, projectedScore) {
    if (!fixes || !fixes.length) {
      document.getElementById('fixes').style.display = 'none';
      return;
    }
    var html = '';
    fixes.forEach(function (fix) {
      var ctaHtml = '';
      if (fix.aewp_cta) {
        ctaHtml = '<a href="#" class="fix-card__aewp-cta aewp-waitlist-trigger">' +
          escapeHtml(fix.aewp_cta) + ' &rarr;</a>';
      }
      var fixLayer = inferFixLayer(fix);
      var layerBadge = '<span class="fix-card__layer">Layer ' + escapeHtml(fixLayer.num) + ': ' + escapeHtml(fixLayer.label) + '</span>';
      html += '<div class="fix-card">' +
        '<div class="fix-card__header">' +
          '<span class="fix-card__title">' + escapeHtml(fix.title) + layerBadge + '</span>' +
          '<span class="fix-card__points">+' + fix.points + ' pts</span>' +
        '</div>' +
        '<p class="fix-card__desc">' + escapeHtml(fix.description) + '</p>' +
        ctaHtml +
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

    // Missed citation warnings (per-heading)
    if (citation.missed_citations && citation.missed_citations.length) {
      reasonsHtml += '<div class="citation-sim__missed-heading">Missed Citation Opportunities</div>';
      citation.missed_citations.forEach(function (heading) {
        reasonsHtml += '<div class="citation-sim__source citation-sim__source--not-cited">' +
          '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="currentColor" stroke-width="1.5"/><path d="M5 5l4 4M9 5l-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>' +
          ' &ldquo;' + escapeHtml(heading) + '&rdquo; has no FAQ/HowTo schema</div>';
      });
    }

    els.citationReasons.innerHTML = reasonsHtml;
  }

  function renderCompetitor(data, competitor) {
    els.competitorGap.style.display = '';
    els.compYourUrl.textContent = data.url || 'Your Site';
    els.compTheirUrl.textContent = competitor.url || 'Competitor';

    var subKeys = ['crawl_access', 'feed_readiness', 'schema_completeness', 'entity_density', 'content_structure', 'faq_coverage', 'summary_presence', 'content_richness'];

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

    var html = `<div class="comparison-chart">
      <div class="comparison-chart__legend">
        <span class="comparison-chart__legend-item"><span class="comparison-chart__legend-dot" style="background:#2563EB"></span>${escapeHtml(data.url || 'Your Site')}</span>
        <span class="comparison-chart__legend-item"><span class="comparison-chart__legend-dot" style="background:#64748B"></span>${escapeHtml(competitor.url || 'Competitor')}</span>
      </div>`;

    rows.forEach(function (row) {
      var delta = row.yours - row.theirs;
      var deltaStr = delta > 0 ? '+' + delta : '' + delta;
      var deltaClass = delta > 0 ? 'comparison-row__delta--positive' : (delta < 0 ? 'comparison-row__delta--negative' : 'comparison-row__delta--neutral');
      var tint = delta < 0 ? 'background:rgba(239,68,68,0.05)' : (delta > 0 ? 'background:rgba(34,197,94,0.05)' : '');

      html += `<div class="comparison-row" style="${tint}">
        <div class="comparison-row__label">${escapeHtml(row.label)}</div>
        <div class="comparison-row__bars">
          <div class="comparison-row__bar-group"><div class="comparison-row__bar-track"><div class="comparison-row__bar-fill comparison-row__bar-fill--yours" style="width:${row.yours}%"></div></div><span class="comparison-row__bar-value">${row.yours}</span></div>
          <div class="comparison-row__bar-group"><div class="comparison-row__bar-track"><div class="comparison-row__bar-fill comparison-row__bar-fill--comp" style="width:${row.theirs}%"></div></div><span class="comparison-row__bar-value">${row.theirs}</span></div>
        </div>
        <div class="comparison-row__delta ${deltaClass}">${deltaStr}</div>
      </div>`;
    });

    html += '</div>';
    return html;
  }

  function renderExtraction(extraction) {
    if (!extraction) {
      document.getElementById('extraction').style.display = 'none';
      return;
    }

    var checkSvg = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#10B981" stroke-width="1.5"/><path d="M5 7l1.5 1.5L9 5.5" stroke="#10B981" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    var foundHtml = '';
    if (extraction.schema_types && extraction.schema_types.length) {
      foundHtml += '<div class="extraction__group-header">Schema Types Found</div>';
      extraction.schema_types.forEach(function (t) {
        foundHtml += '<div class="extraction__item extraction__item--found">' + checkSvg + escapeHtml(t) + '</div>';
      });
    }
    if (extraction.entities && extraction.entities.length) {
      foundHtml += '<div class="extraction__group-header">Entities Detected</div>';
      extraction.entities.forEach(function (e) {
        foundHtml += '<div class="extraction__item extraction__item--found">' + checkSvg + escapeHtml(e) + '</div>';
      });
    }
    if (extraction.headlines && extraction.headlines.length) {
      foundHtml += '<div class="extraction__group-header">Headings Found</div>';
      extraction.headlines.forEach(function (h) {
        foundHtml += '<div class="extraction__item extraction__item--found">' + checkSvg + escapeHtml(h) + '</div>';
      });
    }
    // Crawl & Richness Signals
    var signalItems = [];
    if (extraction.ttfb_ms && extraction.ttfb_ms > 0) {
      signalItems.push({ found: extraction.ttfb_ms < 2000, text: Math.round(extraction.ttfb_ms) + 'ms TTFB' });
    }
    if (extraction.has_canonical) signalItems.push({ found: true, text: 'Canonical tag present' });
    if (extraction.is_ssr) signalItems.push({ found: true, text: 'Server-side rendered' });
    if (extraction.stat_count) signalItems.push({ found: true, text: extraction.stat_count + ' statistics/data points' });
    if (extraction.quality_citations) signalItems.push({ found: true, text: extraction.quality_citations + ' quality citation(s)' });
    if (extraction.front_loaded_count) signalItems.push({ found: true, text: extraction.front_loaded_count + ' front-loaded answer(s)' });
    if (extraction.question_heading_count) signalItems.push({ found: true, text: extraction.question_heading_count + ' question heading(s)' });
    if (extraction.list_count) signalItems.push({ found: true, text: extraction.list_count + ' list(s) found' });
    if (extraction.table_count) signalItems.push({ found: true, text: extraction.table_count + ' table(s) found' });
    if (signalItems.length) {
      foundHtml += '<div class="extraction__group-header">Crawl &amp; Richness Signals</div>';
      var xSvg = '<svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#EF4444" stroke-width="1.5"/><path d="M5 5l4 4M9 5l-4 4" stroke="#EF4444" stroke-width="1.5" stroke-linecap="round"/></svg>';
      signalItems.forEach(function (si) {
        var cls = si.found ? 'extraction__item--found' : 'extraction__item--missing';
        var icon = si.found ? checkSvg : xSvg;
        foundHtml += '<div class="extraction__item ' + cls + '">' + icon + escapeHtml(si.text) + '</div>';
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
  // New render functions
  // ---------------------------------------------------------------------------
  function renderCriticalAlerts(data) {
    if (!els.criticalAlerts) return;
    var showAlerts = false;

    // Robots.txt alert
    if (els.robotsAlert && data.robots && data.robots.has_critical_block) {
      els.robotsAlert.style.display = '';
      var blocked = data.robots.ai_bots_blocked || [];
      if (els.robotsAlertDesc) {
        els.robotsAlertDesc.textContent = 'Your robots.txt blocks these AI crawlers: ' + blocked.join(', ') +
          '. These bots cannot index your content.';
      }
      showAlerts = true;
    }

    // SPA detection alert
    if (els.spaAlert && data.spa_detection && data.spa_detection.is_spa) {
      els.spaAlert.style.display = '';
      if (els.spaWordCount) {
        els.spaWordCount.textContent = data.spa_detection.word_count;
      }
      showAlerts = true;
    }

    if (showAlerts) {
      els.criticalAlerts.style.display = '';
    }
  }

  function renderRawTextView(data) {
    if (!els.rawTextView || !data.raw_text || !data.raw_text.raw_text) return;
    els.rawTextView.style.display = '';
    if (els.rawTextPre) {
      els.rawTextPre.textContent = data.raw_text.raw_text;
    }

    if (els.rawTextToggle && els.rawTextContent) {
      els.rawTextToggle.onclick = function () {
        var isHidden = els.rawTextContent.style.display === 'none';
        els.rawTextContent.style.display = isHidden ? '' : 'none';
        els.rawTextToggle.innerHTML = isHidden ? 'Hide &uarr;' : 'Show &darr;';
      };
    }
  }

  function renderPageTypeContext(data) {
    if (!els.pageTypeContext || !els.pageTypeInfo) return;
    if (!data.page_type || data.page_type === 'auto') {
      els.pageTypeContext.style.display = 'none';
      return;
    }

    var labels = {
      homepage: 'Homepage / Brand Page',
      blog_post: 'Blog Post / Article',
      product_page: 'Product Page',
      local_service: 'Local Service Page'
    };

    var html = '<strong>Page type:</strong> ' + escapeHtml(labels[data.page_type] || data.page_type);

    if (data.page_type_matches && data.page_type_matches.length) {
      html += '<br><span style="color:#22C55E">\u2713 Found expected: ' + data.page_type_matches.map(escapeHtml).join(', ') + '</span>';
    }

    if (data.page_type_mismatches && data.page_type_mismatches.length) {
      data.page_type_mismatches.forEach(function (m) {
        html += '<br><span style="color:#EAB308">\u26A0 ' + escapeHtml(m) + '</span>';
      });
    }

    els.pageTypeInfo.innerHTML = html;
    els.pageTypeContext.style.display = '';
  }

  // ---------------------------------------------------------------------------
  // Helpers
  // ---------------------------------------------------------------------------
  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function isSafeUrl(url) {
    if (!url || typeof url !== 'string') return false;
    try {
      var parsed = new URL(url);
      return parsed.protocol === 'https:' || parsed.protocol === 'http:';
    } catch (e) {
      return false;
    }
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
          var apiMsg = msg.substring(10);
          showError(apiMsg || "We couldn't scan that URL. Try a different URL.");
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

    // Nav scroll (only toggle on homepage; inner pages keep solid background)
    var nav = document.getElementById('siteNav');
    if (nav && !nav.classList.contains('site-nav--scrolled')) {
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
      if (params.get('page_type') && els.pageType) {
        els.pageType.value = params.get('page_type');
      }
      // Auto-start scan
      setTimeout(handleScan, 300);
    }
  }

  document.addEventListener('DOMContentLoaded', init);
})();
