# AnswerEngineWP Marketing Website

WordPress theme for the AnswerEngineWP marketing site at `answerenginewp.com`.

Built on the Underscores (_s) starter theme with custom PHP, CSS, and vanilla JavaScript. No page builder, no jQuery, no CSS preprocessor.

## What This Is

A marketing website with three core functions:

1. **Landing Page** — Convert visitors into scanner users and plugin installers
2. **Public AI Scanner** — Standalone tool that scores any URL for AI visibility (0–100)
3. **Score Results / Share Pages** — Public URLs for sharing scan results with OG previews

## Tech Stack

| Layer | Technology |
|-------|-----------|
| CMS | WordPress 6.x |
| Theme | Underscores (_s), heavily customized |
| CSS | Custom properties (CSS variables), no preprocessor |
| JavaScript | Vanilla ES6+, no framework |
| Scanner API | WordPress REST API (custom endpoints) |
| PDF Generation | Dompdf (via Composer) |
| Hosting | Standard WordPress hosting |

## File Structure

```
answerenginewp/
├── style.css                    # Design system + all CSS
├── functions.php                # Theme setup, enqueues, REST API loader
├── header.php / footer.php      # Site chrome
├── front-page.php               # Homepage (8 template-part sections)
├── page-scanner.php             # Scanner page (3-state UI)
├── page-score-result.php        # Shareable score results
├── page-methodology.php         # Scoring methodology
├── page-docs.php                # Documentation
├── page-support.php             # Support / contact
├── page-badge.php               # Badge embed instructions
├── page-privacy.php             # Privacy policy
├── 404.php                      # 404 page
├── template-parts/
│   ├── home/                    # 8 homepage sections
│   ├── scanner/                 # Scanner input/loading/results states
│   └── nav/                     # Primary and scanner nav variants
├── inc/
│   ├── rest-api.php             # REST endpoint registration
│   ├── scanner-engine.php       # URL fetching, parsing, 6 sub-score calculation
│   ├── pdf-generator.php        # PDF report generation (Dompdf)
│   ├── badge-generator.php      # SVG badge generation
│   ├── og-image-generator.php   # Dynamic OG image generation (GD)
│   └── rate-limiter.php         # IP-based rate limiting (10/hour)
├── assets/js/
│   ├── home.js                  # Homepage scroll animations, nav effects
│   └── scanner.js               # Scanner app (states, API, results rendering)
└── composer.json                # Dompdf dependency
```

## Scanner Scoring

The AI Visibility Score (0–100) is calculated from 6 weighted sub-scores:

| Sub-Score | Weight | What It Measures |
|-----------|--------|-----------------|
| Schema Completeness | 20% | JSON-LD, Microdata, Speakable markup |
| Content Structure | 15% | Heading hierarchy, semantic HTML |
| FAQ & Answer Coverage | 20% | FAQ schema, Q&A blocks, structured answers |
| Summary Presence | 20% | Meta descriptions, opening summaries, extractable text |
| Feed & Manifest Readiness | 10% | /llms.txt, /llms-full.json, RSS, sitemap |
| Entity Density | 15% | Named entities, schema-declared organizations |

### Score Tiers

| Range | Label | Color |
|-------|-------|-------|
| 0–39 | Invisible to AI | Red |
| 40–69 | AI Readable | Amber |
| 70–89 | AI Extractable | Blue |
| 90–100 | AI Authority | Green |

## REST API Endpoints

- `POST /wp-json/aewp/v1/scan` — Scan a URL, returns score + sub-scores + fixes
- `GET /wp-json/aewp/v1/report/{hash}` — Download PDF report
- `GET /wp-json/aewp/v1/badge/{hash}.svg` — SVG badge image

## Setup

1. Upload the `answerenginewp/` folder to `wp-content/themes/`
2. Activate the theme in WP Admin → Appearance → Themes
3. Run `composer install` inside the theme directory for Dompdf
4. Create WordPress pages and assign templates:
   - Scanner → `page-scanner.php`
   - Methodology → `page-methodology.php`
   - Documentation → `page-docs.php`
   - Support → `page-support.php`
   - Badge → `page-badge.php`
   - Privacy → `page-privacy.php`
5. Set homepage display to static page (Settings → Reading)
6. Go to Settings → Permalinks and click Save to flush rewrite rules

## License

GPL v2 or later.
