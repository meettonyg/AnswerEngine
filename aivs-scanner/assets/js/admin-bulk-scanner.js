/**
 * Bulk Scanner Admin JS
 *
 * Reads URL list, scans sequentially via AJAX, populates results table,
 * supports CSV export.
 */
(function () {
  'use strict';

  var urls = [];
  var results = [];
  var current = 0;
  var stopped = false;

  var els = {};

  function init() {
    els.textarea = document.getElementById('aivsBulkUrls');
    els.startBtn = document.getElementById('aivsBulkStart');
    els.stopBtn = document.getElementById('aivsBulkStop');
    els.progress = document.getElementById('aivsBulkProgress');
    els.bar = document.getElementById('aivsBulkBar');
    els.status = document.getElementById('aivsBulkStatus');
    els.resultsWrap = document.getElementById('aivsBulkResults');
    els.tbody = document.getElementById('aivsBulkBody');
    els.exportBtn = document.getElementById('aivsBulkExport');

    if (!els.startBtn) return;

    els.startBtn.addEventListener('click', startScan);
    els.stopBtn.addEventListener('click', function () { stopped = true; });
    els.exportBtn.addEventListener('click', exportCSV);
  }

  function startScan() {
    var raw = els.textarea.value.trim();
    if (!raw) return;

    // Parse URLs — one per line, skip blanks
    urls = raw.split('\n').map(function (u) { return u.trim(); }).filter(function (u) { return u.length > 0; });

    // Auto-add https:// if missing
    urls = urls.map(function (u) {
      if (!/^https?:\/\//i.test(u)) return 'https://' + u;
      return u;
    });

    // Cap at 50
    if (urls.length > 50) {
      urls = urls.slice(0, 50);
      alert('Capped at 50 URLs.');
    }

    if (urls.length === 0) return;

    // Reset state
    results = [];
    current = 0;
    stopped = false;
    els.tbody.innerHTML = '';
    els.bar.style.width = '0%';

    // Show UI
    els.progress.style.display = 'block';
    els.resultsWrap.style.display = 'block';
    els.startBtn.disabled = true;
    els.stopBtn.style.display = 'inline-block';
    els.textarea.disabled = true;

    // Pre-fill rows
    urls.forEach(function (url, i) {
      var tr = document.createElement('tr');
      tr.id = 'bulk-row-' + i;
      tr.innerHTML =
        '<td>' + (i + 1) + '</td>' +
        '<td>' + escHtml(url.replace(/^https?:\/\//i, '')) + '</td>' +
        '<td>—</td>' +
        '<td>—</td>' +
        '<td style="color:#999;">Pending</td>' +
        '<td>—</td>';
      els.tbody.appendChild(tr);
    });

    scanNext();
  }

  function scanNext() {
    if (current >= urls.length || stopped) {
      finish();
      return;
    }

    var idx = current;
    var url = urls[idx];
    updateRow(idx, null, null, null, 'Scanning…', '#2271b1');

    var pct = Math.round((idx / urls.length) * 100);
    els.bar.style.width = pct + '%';
    els.status.textContent = 'Scanning ' + (idx + 1) + ' of ' + urls.length + '…';

    var fd = new FormData();
    fd.append('action', 'aivs_bulk_scan_single');
    fd.append('nonce', aivsBulk.nonce);
    fd.append('url', url);

    fetch(aivsBulk.ajaxUrl, { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function (res) { return res.json(); })
      .then(function (json) {
        if (json.success) {
          var d = json.data;
          results.push({
            url: url,
            domain: d.domain,
            score: d.score,
            tier: d.tier_label,
            reportUrl: d.report_url
          });
          updateRow(idx, d.score, d.tier_label, d.tier_color, 'Done', '#46b450', d.report_url);
        } else {
          var msg = (json.data && json.data.message) || 'Error';
          results.push({ url: url, domain: url.replace(/^https?:\/\//i, ''), score: '—', tier: '—', reportUrl: '' });
          updateRow(idx, '—', '—', null, msg, '#dc3232');
        }
      })
      .catch(function () {
        results.push({ url: url, domain: url.replace(/^https?:\/\//i, ''), score: '—', tier: '—', reportUrl: '' });
        updateRow(idx, '—', '—', null, 'Network error', '#dc3232');
      })
      .finally(function () {
        current++;
        scanNext();
      });
  }

  function updateRow(idx, score, tier, tierColor, status, statusColor, reportUrl) {
    var tr = document.getElementById('bulk-row-' + idx);
    if (!tr) return;
    var cells = tr.children;
    if (score !== null) cells[2].textContent = score;
    if (tier !== null) {
      cells[3].textContent = tier;
      if (tierColor) cells[3].style.color = tierColor;
    }
    cells[4].textContent = status;
    cells[4].style.color = statusColor || '';
    if (reportUrl) {
      cells[5].innerHTML = '<a href="' + escHtml(reportUrl) + '" target="_blank">View</a>';
    }
  }

  function finish() {
    els.bar.style.width = '100%';
    els.status.textContent = 'Complete — ' + results.length + ' of ' + urls.length + ' scanned.';
    els.startBtn.disabled = false;
    els.stopBtn.style.display = 'none';
    els.textarea.disabled = false;
  }

  function exportCSV() {
    if (results.length === 0) return;
    var rows = [['URL', 'Domain', 'Score', 'Tier', 'Report URL']];
    results.forEach(function (r) {
      rows.push([r.url, r.domain, r.score, r.tier, r.reportUrl]);
    });
    var csv = rows.map(function (row) {
      return row.map(function (cell) {
        return '"' + String(cell).replace(/"/g, '""') + '"';
      }).join(',');
    }).join('\n');

    var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    var link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    var d = new Date();
    link.download = 'ai-visibility-bulk-scan-' + d.toISOString().slice(0, 10) + '.csv';
    link.click();
  }

  function escHtml(str) {
    var div = document.createElement('div');
    div.appendChild(document.createTextNode(str));
    return div.innerHTML;
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
