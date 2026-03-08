/**
 * Bulk Scan — Private admin tool for scanning multiple URLs.
 *
 * Reads from aewpBulk localized object: { apiUrl, nonce }
 */
(function () {
  'use strict';

  /* ── DOM refs ─────────────────────────────────────────── */
  var textarea     = document.getElementById('bulkUrls');
  var urlCountEl   = document.getElementById('urlCount');
  var startBtn     = document.getElementById('bulkStart');
  var pauseBtn     = document.getElementById('bulkPause');
  var progressWrap = document.getElementById('bulkProgress');
  var progressFill = document.getElementById('progressFill');
  var progressText = document.getElementById('progressText');
  var resultsWrap  = document.getElementById('bulkResults');
  var resultsCount = document.getElementById('resultsCount');
  var tableBody    = document.getElementById('bulkTableBody');
  var exportBtn    = document.getElementById('bulkExportCsv');
  var resetBtn     = document.getElementById('bulkReset');

  /* ── State ────────────────────────────────────────────── */
  var queue   = [];
  var results = [];
  var running = false;
  var paused  = false;
  var done    = 0;
  var total   = 0;
  var CONCURRENCY = 2; // Two parallel scans at a time
  var activeCount  = 0;
  var queueIdx     = 0;

  /* ── Helpers ──────────────────────────────────────────── */
  function parseUrls(text) {
    return text
      .split(/[\n\r]+/)
      .map(function (l) { return l.trim(); })
      .filter(function (l) { return l.length > 0; })
      .map(function (u) {
        if (!/^https?:\/\//i.test(u)) u = 'https://' + u;
        return u;
      });
  }

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  function subScoreValue(sub, key) {
    if (!sub || !sub[key]) return '-';
    return sub[key].score != null ? sub[key].score : '-';
  }

  function tierPill(label, color) {
    return '<span class="bulk-scan__tier-pill" style="background:' + color + '20;color:' + color + '">' + escapeHtml(label) + '</span>';
  }

  function scorePill(score, color) {
    return '<span class="bulk-scan__score-pill" style="background:' + color + '">' + score + '</span>';
  }

  /* ── URL counter ──────────────────────────────────────── */
  textarea.addEventListener('input', function () {
    var count = parseUrls(textarea.value).length;
    urlCountEl.textContent = count + ' URL' + (count !== 1 ? 's' : '');
  });

  /* ── Start ────────────────────────────────────────────── */
  startBtn.addEventListener('click', function () {
    var urls = parseUrls(textarea.value);
    if (urls.length === 0) return;

    // Deduplicate
    var seen = {};
    queue = [];
    urls.forEach(function (u) {
      var key = u.toLowerCase();
      if (!seen[key]) { seen[key] = true; queue.push(u); }
    });

    total    = queue.length;
    done     = 0;
    results  = [];
    queueIdx = 0;
    paused   = false;
    running  = true;

    tableBody.innerHTML = '';
    progressWrap.style.display = '';
    resultsWrap.style.display  = '';
    startBtn.disabled = true;
    textarea.disabled = true;
    pauseBtn.textContent = 'Pause';
    updateProgress();

    // Kick off workers
    for (var i = 0; i < CONCURRENCY; i++) {
      processNext();
    }
  });

  /* ── Pause / Resume ───────────────────────────────────── */
  pauseBtn.addEventListener('click', function () {
    if (!running) return;
    paused = !paused;
    pauseBtn.textContent = paused ? 'Resume' : 'Pause';
    if (!paused) {
      for (var i = 0; i < CONCURRENCY; i++) {
        processNext();
      }
    }
  });

  /* ── Process queue ────────────────────────────────────── */
  function processNext() {
    if (paused || !running) return;
    if (queueIdx >= total) {
      if (activeCount === 0) onAllDone();
      return;
    }

    var idx = queueIdx++;
    var url = queue[idx];
    activeCount++;

    scanUrl(url, idx)
      .then(function (row) {
        results.push(row);
        appendRow(row, idx);
      })
      .catch(function (err) {
        var row = { index: idx, url: url, error: err.message || 'Scan failed' };
        results.push(row);
        appendErrorRow(row, idx);
      })
      .finally(function () {
        done++;
        activeCount--;
        updateProgress();
        processNext();
      });
  }

  function scanUrl(url) {
    return fetch(aewpBulk.apiUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-WP-Nonce': aewpBulk.nonce,
      },
      body: JSON.stringify({ url: url }),
    }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok || !data.success) {
          throw new Error(data.message || 'HTTP ' + res.status);
        }
        return data;
      });
    });
  }

  /* ── UI updates ───────────────────────────────────────── */
  function updateProgress() {
    var pct = total ? Math.round((done / total) * 100) : 0;
    progressFill.style.width = pct + '%';
    progressText.textContent = done + ' / ' + total + ' scanned';
    resultsCount.textContent = '(' + done + '/' + total + ')';
  }

  function appendRow(data, idx) {
    var tr = document.createElement('tr');
    var topFix = (data.fixes && data.fixes.length) ? escapeHtml(data.fixes[0].title) : '-';
    tr.innerHTML =
      '<td>' + (idx + 1) + '</td>' +
      '<td title="' + escapeHtml(data.url) + '">' + escapeHtml(data.domain || data.url) + '</td>' +
      '<td>' + scorePill(data.score, data.tier_color) + '</td>' +
      '<td>' + tierPill(data.tier_label, data.tier_color) + '</td>' +
      '<td>' + subScoreValue(data.sub_scores, 'schema_completeness') + '</td>' +
      '<td>' + subScoreValue(data.sub_scores, 'content_structure') + '</td>' +
      '<td>' + subScoreValue(data.sub_scores, 'faq_coverage') + '</td>' +
      '<td>' + subScoreValue(data.sub_scores, 'summary_presence') + '</td>' +
      '<td>' + subScoreValue(data.sub_scores, 'feed_readiness') + '</td>' +
      '<td>' + subScoreValue(data.sub_scores, 'entity_density') + '</td>' +
      '<td>' + topFix + '</td>' +
      '<td><a href="' + escapeHtml(data.report_url) + '" class="bulk-scan__link" target="_blank">View</a></td>';
    tableBody.appendChild(tr);
  }

  function appendErrorRow(data, idx) {
    var tr = document.createElement('tr');
    tr.className = 'bulk-scan__error-row';
    tr.innerHTML =
      '<td>' + (idx + 1) + '</td>' +
      '<td>' + escapeHtml(data.url) + '</td>' +
      '<td colspan="10">' + escapeHtml(data.error) + '</td>';
    tableBody.appendChild(tr);
  }

  function onAllDone() {
    running = false;
    startBtn.disabled = false;
    textarea.disabled = false;
    progressText.textContent = 'Done — ' + done + ' URLs scanned';
    pauseBtn.style.display = 'none';
  }

  /* ── CSV export ───────────────────────────────────────── */
  exportBtn.addEventListener('click', function () {
    if (!results.length) return;

    var headers = ['URL', 'Score', 'Tier', 'Schema', 'Structure', 'FAQ', 'Summary', 'Feeds', 'Entities', 'Top Fix', 'Report URL'];
    var rows = [headers.join(',')];

    results.forEach(function (r) {
      if (r.error) {
        rows.push(csvLine([r.url, 'ERROR', r.error, '', '', '', '', '', '', '', '']));
        return;
      }
      rows.push(csvLine([
        r.url || r.domain,
        r.score,
        r.tier_label,
        subScoreValue(r.sub_scores, 'schema_completeness'),
        subScoreValue(r.sub_scores, 'content_structure'),
        subScoreValue(r.sub_scores, 'faq_coverage'),
        subScoreValue(r.sub_scores, 'summary_presence'),
        subScoreValue(r.sub_scores, 'feed_readiness'),
        subScoreValue(r.sub_scores, 'entity_density'),
        (r.fixes && r.fixes.length) ? r.fixes[0].title : '',
        r.report_url || '',
      ]));
    });

    var blob = new Blob([rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'bulk-scan-results-' + new Date().toISOString().slice(0, 10) + '.csv';
    a.click();
  });

  function csvLine(fields) {
    return fields.map(function (f) {
      var s = String(f == null ? '' : f);
      if (s.indexOf(',') > -1 || s.indexOf('"') > -1 || s.indexOf('\n') > -1) {
        return '"' + s.replace(/"/g, '""') + '"';
      }
      return s;
    }).join(',');
  }

  /* ── Reset ────────────────────────────────────────────── */
  resetBtn.addEventListener('click', function () {
    textarea.value = '';
    textarea.disabled = false;
    startBtn.disabled = false;
    tableBody.innerHTML = '';
    progressWrap.style.display = 'none';
    resultsWrap.style.display  = 'none';
    pauseBtn.style.display = '';
    pauseBtn.textContent = 'Pause';
    urlCountEl.textContent = '0 URLs';
    results = [];
    queue   = [];
    running = false;
    paused  = false;
  });
})();
