# 0.1 — Public AI Scanner (Web App): Status Assessment

**Assessment Date:** 2026-03-06
**Overall Status:** SUBSTANTIALLY BUILT (~90% complete)

---

## What Exists (Built & Functional)

### Core Engine
- **Scanner scoring engine** (`inc/scanner-engine.php`) — Full `aewp_scan_url()` with 6 weighted sub-scores:
  - Schema Completeness (20%) — JSON-LD, Microdata, Speakable detection
  - Content Structure (15%) — Heading hierarchy, semantic HTML, paragraph analysis
  - FAQ Coverage (20%) — FAQPage/QAPage schema, question headings, details/summary, dl/dt
  - Summary Presence (20%) — Meta description, opening paragraphs, OG tags, JSON-LD descriptions
  - Feed Readiness (10%) — /llms.txt, /llms-full.json, RSS, sitemap.xml
  - Entity Density (15%) — Named entity extraction, schema-declared entities
- **Fix generation** — Priority-sorted Top 3 fixes with projected score improvement
- **Citation simulation** — Predicts whether AI would cite the scanned page
- **Server-side fetching** — `wp_remote_get()` with 15s timeout, works on ANY external URL

### PDF Report
- **PDF generation** (`inc/pdf-generator.php`) — 6-page Dompdf report with HTML fallback:
  1. Cover page with score, tier label, tier message
  2. Score Breakdown with all 6 sub-scores
  3. Competitor Structure Gap (side-by-side table)
  4. Extraction Preview (found vs missing signals)
  5. Top 3 Recommended Fixes (with point values)
  6. CTA page

### Web UI
- **Scanner page** (`page-scanner.php` + `template-parts/scanner/`) — 3-state flow:
  - Input state: URL field, competitor toggle, "Scan Now" button
  - Loading state: Progress bar, rotating status messages
  - Results state: Score gauge, sub-scores, fixes, citation sim, extraction preview, CTAs
- **URL validation** — Client-side: auto-adds https://, validates hostname. Error display inline.
- **Score display** — Animated SVG gauge with tier-colored fill

### Tier System
- **4 tiers** (`functions.php:236-275`):
  - 0–39: "Invisible to AI" (red #EF4444)
  - 40–69: "AI Readable" (amber #EAB308)
  - 70–89: "AI Extractable" (blue #3B82F6)
  - 90–100: "AI Authority" (green #22C55E)

### Competitor Comparison
- **Secondary URL input** with toggle in scanner UI
- **Backend comparison** scans both URLs, returns side-by-side data
- **UI rendering** of comparison table in both scanner results and PDF

### Shareable Score URLs
- **Rewrite rule** `/score/{hash}` → `page-score-result.php`
- **Server-rendered results** with full score breakdown, fixes, extraction preview
- **OG/Twitter meta tags** with auto-generated OG images for social sharing
- **Custom page titles** for SEO

### Badge System
- **SVG badge generation** (`inc/badge-generator.php`) for scores 70+
- **REST endpoint** `GET /wp-json/aewp/v1/badge/{hash}.svg`
- **Copy-to-clipboard** snippet in scanner results
- **Dedicated badge page** (`page-badge.php`) with documentation and guidelines

### Rate Limiting
- **IP-based** (`inc/rate-limiter.php`) — 10 scans/hour per IP
- **WordPress transients** for storage, hashed IP for privacy
- **429 response** with retry_after countdown
- **Client-side handling** of rate limit errors with friendly messages

### REST API
- `POST /aewp/v1/scan` — Main scan endpoint with rate limiting, 24h caching
- `GET /aewp/v1/report/{hash}` — PDF download
- `GET /aewp/v1/badge/{hash}.svg` — Badge SVG

### Infrastructure
- **Custom post type** `aewp_scan` for persisting results
- **24-hour caching** via transients
- **Auto-page creation** on theme activation
- **Plausible analytics** integration with event tracking
- **Mobile responsive** CSS with breakpoints at 1024px, 768px, 640px

---

## What's Missing / Needs Work

### 1. Email Capture — NOT BUILT
- No email collection form in scanner flow
- Spec'd as "optional, non-blocking"
- Suggested: Add optional email field on results page before PDF download
- Effort: Low (1-2 hours)

### 2. Performance Optimization — NEEDS WORK
- Feed checks (/llms.txt, /llms-full.json, /feed/, /sitemap.xml) run sequentially
- Each has 5s timeout = worst case 20s just for feeds + 15s for main page = 35s total
- **Fix:** Parallelize feed HTTP requests or reduce timeouts to 2-3s each
- Target: Score visible in under 10 seconds
- Effort: Medium (2-4 hours)

### 3. Non-WordPress Site Validation — NEEDS TESTING
- Engine code is platform-agnostic (parses raw HTML)
- `/feed/` check assumes WordPress-style RSS path
- Should test against diverse non-WP sites: static sites, React SPAs, Shopify, Squarespace, etc.
- Effort: Low (testing only, 1-2 hours)

### 4. Dompdf Deployment — NEEDS VERIFICATION
- PDF generation requires Composer autoload (`vendor/autoload.php`)
- Falls back to HTML if Dompdf not installed
- Verify `composer install` is run on production
- Effort: Minimal

---

## Action Items (Original List vs Reality)

| Original Action Item | Status |
|---|---|
| Adapt ExtractionScorer/SchemaCompletenessChecker for server-side use on external URLs | DONE — `aewp_scan_url()` works on any URL |
| Build the web UI (URL input -> score display -> PDF download -> CTA) | DONE — Full 3-state UI |
| Implement rate limiting | DONE — 10/hour per IP |
| Test on non-WordPress sites | NEEDS VALIDATION |
| Performance test: score in under 10 seconds | NEEDS OPTIMIZATION (feed parallelization) |

---

## Recommended Priority for Remaining Work

1. **Performance optimization** (Critical) — Parallelize feed checks to hit <10s target
2. **Non-WordPress testing** (Important) — Validate on 10+ non-WP sites
3. **Dompdf verification** (Quick win) — Ensure Composer deps are deployed
4. **Email capture** (Nice to have) — Optional field, non-blocking
