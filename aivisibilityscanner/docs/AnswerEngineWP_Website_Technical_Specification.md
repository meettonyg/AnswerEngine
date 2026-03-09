# AnswerEngineWP Website — Complete Technical Specification

**Version:** 1.0
**Date:** March 5, 2026
**Purpose:** Complete build spec for Claude Code to implement the AnswerEngineWP marketing website on WordPress using the Underscores (_s) starter theme. No page builder. All templates, styles, and functionality built directly in PHP, CSS, and JavaScript.

**Reference Documents:**
- `AnswerEngineWP_Master_Strategy_v9.1.md` — Product strategy, pricing, competitive positioning
- `AnswerEngineWP_GTM_Implementation_Plan.md` — GTM phases, scanner requirements, PDF report spec
- `AnswerEngineWP_LandingPage_Copy.md` — Final landing page copy (Draft 4)
- `AnswerEngineWP_Scanner_Copy.md` — Scanner page copy (Draft 1)
- `answerenginewp-landing.html` — Approved HTML/CSS mockup (v3, post-review)

---

## Table of Contents

1. [Project Overview](#1-project-overview)
2. [Design System & Brand Standards](#2-design-system--brand-standards)
3. [WordPress Architecture](#3-wordpress-architecture)
4. [Page Templates & Routing](#4-page-templates--routing)
5. [Homepage (Landing Page)](#5-homepage-landing-page)
6. [Scanner Page](#6-scanner-page)
7. [Score Results Page](#7-score-results-page)
8. [Static Pages](#8-static-pages)
9. [Scanner Backend — REST API](#9-scanner-backend--rest-api)
10. [PDF Report Generator](#10-pdf-report-generator)
11. [Badge Embed System](#11-badge-embed-system)
12. [Analytics & Tracking](#12-analytics--tracking)
13. [SEO & Meta Tags](#13-seo--meta-tags)
14. [Performance Requirements](#14-performance-requirements)
15. [Complete Website Copy](#15-complete-website-copy)
16. [File Structure](#16-file-structure)
17. [Implementation Sequence](#17-implementation-sequence)
18. [Acceptance Criteria](#18-acceptance-criteria)

---

## 1. Project Overview

### What We're Building

A marketing website for AnswerEngineWP at `answerenginewp.com` with three core functions:

1. **Landing Page** — Convert visitors into scanner users and plugin installers
2. **Public AI Scanner** — Standalone tool that scores any URL for AI visibility (0–100)
3. **Score Results / Share Pages** — Public URLs for sharing scan results with OG previews

### What We're NOT Building

- The WordPress plugin itself (already built, lives in a separate repo)
- An admin dashboard or user accounts
- Payment processing (links to external checkout)
- Email sending infrastructure (handled by separate tool)

### Technology Stack

| Layer | Technology |
|:---|:---|
| CMS | WordPress 6.x |
| Theme | Underscores (_s) starter, heavily customized |
| CSS | Custom properties (CSS variables), no preprocessor, no Tailwind |
| JavaScript | Vanilla ES6+, no framework, no jQuery dependency |
| Scanner API | WordPress REST API (custom endpoints) |
| PDF Generation | Server-side PHP (TCPDF or Dompdf) |
| Hosting | Standard WordPress hosting (no Node.js required) |
| Analytics | Plausible Analytics (self-hosted or cloud) |

---

## 2. Design System & Brand Standards

### 2.1 Color Palette

```css
:root {
  /* Primary */
  --navy:        #0F1923;
  --navy-mid:    #1A2B3D;
  --blue:        #2563EB;
  --blue-hover:  #1D4FD7;
  --blue-light:  #EFF6FF;
  --blue-glow:   rgba(37, 99, 235, 0.15);

  /* Neutrals */
  --gray-50:     #F8FAFC;
  --gray-100:    #F1F5F9;
  --gray-200:    #E2E8F0;
  --gray-300:    #CBD5E1;
  --gray-400:    #94A3B8;
  --gray-500:    #64748B;
  --gray-700:    #334155;
  --gray-900:    #0F172A;
  --white:       #FFFFFF;

  /* Score Tier Colors */
  --tier-red:    #EF4444;   /* 0–39:  Invisible to AI */
  --tier-amber:  #EAB308;   /* 40–69: AI Readable */
  --tier-blue:   #3B82F6;   /* 70–89: AI Extractable */
  --tier-green:  #22C55E;   /* 90–100: AI Authority */

  /* Semantic */
  --green-muted: #10B981;   /* Checkmarks, positive indicators */
}
```

### 2.2 Typography

| Role | Font Family | Fallback | Weight | Usage |
|:---|:---|:---|:---|:---|
| Display | Instrument Serif | Georgia, serif | 400, 400 italic | H1, H2, score numbers, price numbers |
| Body | DM Sans | system-ui, sans-serif | 300–700 | Body text, buttons, UI elements |
| Mono | JetBrains Mono | monospace | 400, 500 | Labels, URLs, code, score tiers |

**Loading:** Google Fonts via `<link>` with `preconnect`. Font-display: swap.

```
https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=JetBrains+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap
```

### 2.3 Type Scale

| Element | Font | Size | Weight | Line Height | Color |
|:---|:---|:---|:---|:---|:---|
| H1 (hero) | Instrument Serif | clamp(42px, 5.5vw, 64px) | 400 | 1.08 | --white (on navy) |
| H2 (section) | Instrument Serif | clamp(32px, 4.5vw, 48px) | 400 | 1.15 | --gray-900 |
| H2 (problem) | Instrument Serif | clamp(30px, 4vw, 44px) | 400 | 1.15 | --gray-900 |
| Section label | JetBrains Mono | 12px | 500 | — | --blue |
| Body | DM Sans | 17px | 400 | 1.75 | --gray-700 |
| Body large | DM Sans | 19px | 400 | 1.65 | --gray-300 (on navy) |
| Small / caption | DM Sans | 14px | 400 | 1.6 | --gray-500 |
| Micro | JetBrains Mono | 12–13px | 400 | — | --gray-400 |
| Button | DM Sans | 16px | 600–700 | — | --white |
| Score number | Instrument Serif | 48–72px | 400 | 1 | Tier color |
| Price number | Instrument Serif | 40px | 400 | 1 | --gray-900 |

### 2.4 Section Label Pattern

All sections use a consistent label above the H2:

```html
<div class="section-label">LABEL TEXT</div>
<h2 class="section-heading">Heading Text</h2>
```

Label: JetBrains Mono, 12px, 500 weight, letter-spacing 0.12em, uppercase, color --blue, margin-bottom 16px.

### 2.5 Spacing System

| Variable | Value | Usage |
|:---|:---|:---|
| --section-pad | 120px | Vertical padding between major sections |
| --section-pad-mobile | 72px | Mobile section padding |
| Container max-width | 1200px | Content container |
| Container padding | 0 32px | Horizontal (20px on mobile) |

### 2.6 Button Styles

**Primary (Blue filled):**
- Background: --blue
- Color: --white
- Padding: 16px 32px
- Border-radius: 10px
- Shadow: `0 1px 3px rgba(37,99,235,.3), 0 8px 24px rgba(37,99,235,.15)`
- Hover: --blue-hover, translateY(-1px), stronger shadow

**Outline (Blue border):**
- Background: transparent
- Border: 1.5px solid --blue
- Color: --blue
- Hover: --blue-light background

**Navy (Dark filled):**
- Background: --navy
- Color: --white
- Hover: --navy-mid

**Nav CTA (Compact primary):**
- Same as Primary but: padding 10px 20px, font-size 14px, border-radius 8px

### 2.7 Card Styles

**Feature card (primary):**
- Background: --white
- Border: 1px solid --gray-200
- Border-radius: 16px
- Padding: 32px
- Hover: border-color --blue, shadow var(--blue-glow), translateY(-2px)

**Feature card (small/supporting):**
- Background: --gray-50
- Border: transparent
- Padding: 24px
- Hover: background --white

**Pricing card:**
- Background: --white
- Border: 1px solid --gray-200
- Border-radius: 16px
- Padding: 36px 32px
- Popular variant: 2px border --blue, --blue-glow shadow, "Most Popular" pill above

**Companion Mode callout:**
- Background: --blue-light
- Border-left: 4px solid --blue
- Border-radius: 0 12px 12px 0
- Padding: 28px 32px

### 2.8 Pill / Badge Styles

| Variant | Background | Color |
|:---|:---|:---|
| Free | --gray-100 | --gray-700 |
| Pro | --blue | --white |
| Agency | --navy | --white |
| Score tier | `rgba(tierColor, 0.12)` | tier color |

All pills: padding 6px 14px, border-radius 100px, font-size 13px, font-weight 500.

### 2.9 Score Tier System

This is a branded classification system used across the scanner, score pages, PDF reports, and badges.

| Range | Label | Color | CSS Variable |
|:---|:---|:---|:---|
| 0–39 | Invisible to AI | #EF4444 (Red) | --tier-red |
| 40–69 | AI Readable | #EAB308 (Amber) | --tier-amber |
| 70–89 | AI Extractable | #3B82F6 (Blue) | --tier-blue |
| 90–100 | AI Authority | #22C55E (Green) | --tier-green |

**Tier context messages (used in scanner results, PDF reports, badge tooltips):**

- **0–39:** "ChatGPT cannot reliably extract or cite your site. Better-structured competitors are more likely to be cited while your content goes unread by the systems your audience increasingly trusts."
- **40–69:** "AI systems can find your content, but they're choosing better-structured competitors to cite. You're in the room — but you're not being quoted."
- **70–89:** "AI systems can extract and structure your content effectively. You're close to becoming a preferred citation source — a few structural improvements separate you from authority status."
- **90–100:** "Your site is fully optimized for AI extraction and citation. AI systems treat your content as a trusted, authoritative source. You are an AI authority in your space."

### 2.10 Score Gauge (SVG)

Circular arc gauge used in hero scanner, results pages, and PDF reports.

```svg
<svg viewBox="0 0 100 100">
  <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.06)" stroke-width="8"/>
  <circle cx="50" cy="50" r="45" fill="none" stroke="{tierColor}" stroke-width="8"
    stroke-linecap="round" stroke-dasharray="283" stroke-dashoffset="{offset}"
    transform="rotate(-90 50 50)"/>
  <text x="50" y="50" text-anchor="middle" dominant-baseline="middle"
    font-family="Instrument Serif" font-size="48" fill="{textColor}">{score}</text>
  <text x="50" y="66" text-anchor="middle"
    font-family="JetBrains Mono" font-size="13" fill="{mutedColor}">/100</text>
</svg>
```

Offset calculation: `283 - (283 * score / 100)`. Animate with CSS `animation: scoreFill 1.8s cubic-bezier(.16,1,.3,1) forwards`.

### 2.11 Animations

| Animation | Trigger | Duration | Easing |
|:---|:---|:---|:---|
| fadeUp | Scroll into view | 0.7s | cubic-bezier(.16,1,.3,1) |
| scoreFill | Score reveal | 1.8s | cubic-bezier(.16,1,.3,1) |
| Nav background | scroll > 40px | 0.3s | ease |
| Status message fade | Loading state cycle | 0.3s opacity | ease |
| Progress bar | Loading state | 0.4s width | ease |
| Button hover lift | :hover | 0.25s | cubic-bezier(.4,0,.2,1) |

Use IntersectionObserver for scroll animations. Stagger children with `animation-delay` increments of 0.1s–0.15s.

### 2.12 Responsive Breakpoints

| Breakpoint | Behavior |
|:---|:---|
| > 1024px | Full desktop layout |
| 641–1024px | Tablet: 2-column grids collapse, hero stacks, pricing single-column |
| ≤ 640px | Mobile: single column, 72px section padding, 20px container padding, nav links hidden except CTA |

### 2.13 Brand Voice (for any copy additions)

- **Tone:** Confident, direct, technical but accessible
- **Personality:** The sharp colleague who shows you the problem with data, then hands you the fix
- **Rule:** Lead with outcomes, not architecture. Say "Get cited by AI" not "AEO Infrastructure"
- **Never use:** "revolutionary," "game-changing," "leverage," "synergy," marketing filler
- **Hedging language for AI claims:** Use "can," "reliably," "prefer," "more likely" — never promise what AI systems "will" do

---

## 3. WordPress Architecture

### 3.1 Theme Setup

Start from Underscores (_s) generated at https://underscores.me with the name `answerenginewp`.

**Modifications to _s defaults:**

- Remove all default `_s` CSS styles (replace entirely with the design system above)
- Remove jQuery dependency from `functions.php` (we use vanilla JS)
- Remove default sidebar and widget areas (not used)
- Remove comments template and functionality (not used)
- Remove search functionality (not used for marketing site)
- Keep: post type support, custom template loading, wp_enqueue system, nav menus

### 3.2 functions.php Core Setup

```php
<?php
// Theme setup
function aewp_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', ['search-form', 'gallery', 'caption', 'style', 'script']);

    register_nav_menus([
        'primary' => 'Primary Navigation',
    ]);
}
add_action('after_setup_theme', 'aewp_setup');

// Enqueue styles and scripts
function aewp_scripts() {
    // Main stylesheet
    wp_enqueue_style('aewp-fonts', 'https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,300..700;1,9..40,300..700&family=JetBrains+Mono:wght@400;500&family=Instrument+Serif:ital@0;1&display=swap', [], null);
    wp_enqueue_style('aewp-style', get_stylesheet_uri(), ['aewp-fonts'], wp_get_theme()->get('Version'));

    // Page-specific scripts
    if (is_front_page()) {
        wp_enqueue_script('aewp-home', get_template_directory_uri() . '/assets/js/home.js', [], '1.0', true);
    }
    if (is_page_template('page-scanner.php')) {
        wp_enqueue_script('aewp-scanner', get_template_directory_uri() . '/assets/js/scanner.js', [], '1.0', true);
        wp_localize_script('aewp-scanner', 'aewpScanner', [
            'apiUrl'  => rest_url('aewp/v1/scan'),
            'nonce'   => wp_create_nonce('wp_rest'),
            'siteUrl' => home_url(),
        ]);
    }

    // Dequeue jQuery (not needed)
    if (!is_admin()) {
        wp_deregister_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'aewp_scripts');

// Remove WordPress emoji script
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');

// Remove block library CSS (not using Gutenberg on frontend)
function aewp_remove_block_css() {
    wp_dequeue_style('wp-block-library');
    wp_dequeue_style('wp-block-library-theme');
    wp_dequeue_style('wc-blocks-style');
    wp_dequeue_style('global-styles');
}
add_action('wp_enqueue_scripts', 'aewp_remove_block_css', 100);

// Custom REST API endpoints
require_once get_template_directory() . '/inc/rest-api.php';

// Scanner scoring engine
require_once get_template_directory() . '/inc/scanner-engine.php';

// PDF report generator
require_once get_template_directory() . '/inc/pdf-generator.php';

// Custom rewrite rules for /score/{hash} URLs
function aewp_rewrite_rules() {
    add_rewrite_rule(
        '^score/([a-zA-Z0-9]+)/?$',
        'index.php?aewp_score_hash=$1',
        'top'
    );
}
add_action('init', 'aewp_rewrite_rules');

function aewp_query_vars($vars) {
    $vars[] = 'aewp_score_hash';
    return $vars;
}
add_filter('query_vars', 'aewp_query_vars');

function aewp_template_redirect() {
    $hash = get_query_var('aewp_score_hash');
    if ($hash) {
        include get_template_directory() . '/page-score-result.php';
        exit;
    }
}
add_action('template_redirect', 'aewp_template_redirect');
```

### 3.3 Custom Post Type: Scan Results

```php
// Register CPT for storing scan results
function aewp_register_scan_results() {
    register_post_type('aewp_scan', [
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'menu_icon'    => 'dashicons-chart-bar',
        'labels'       => ['name' => 'Scan Results', 'singular_name' => 'Scan Result'],
        'supports'     => ['title'],
    ]);
}
add_action('init', 'aewp_register_scan_results');
```

Post meta fields for `aewp_scan`:

| Meta Key | Type | Description |
|:---|:---|:---|
| `_aewp_url` | string | Scanned URL |
| `_aewp_score` | int | Overall score 0–100 |
| `_aewp_tier` | string | Tier label |
| `_aewp_sub_scores` | serialized array | Six sub-score values |
| `_aewp_extraction_data` | serialized array | Entities, headings, schemas found |
| `_aewp_fixes` | serialized array | Top 3 recommended fixes |
| `_aewp_competitor_url` | string | Competitor URL (if provided) |
| `_aewp_competitor_score` | int | Competitor score |
| `_aewp_competitor_data` | serialized array | Competitor sub-scores and extraction data |
| `_aewp_hash` | string | Unique hash for public share URL |
| `_aewp_scanned_at` | datetime | Timestamp |
| `_aewp_ip_hash` | string | Hashed IP for rate limiting |

---

## 4. Page Templates & Routing

| URL | Template File | Description |
|:---|:---|:---|
| `/` | `front-page.php` | Homepage / landing page |
| `/scanner/` | `page-scanner.php` | Public AI Scanner tool |
| `/score/{hash}` | `page-score-result.php` | Shareable score results page |
| `/methodology/` | `page-methodology.php` | Scoring methodology explanation |
| `/docs/` | `page-docs.php` | Documentation |
| `/support/` | `page-support.php` | Support / contact |
| `/badge/` | `page-badge.php` | Badge embed instructions |
| `/privacy/` | `page-privacy.php` | Privacy policy |

Create WordPress pages in the admin for: Scanner, Methodology, Documentation, Support, Badge, Privacy. Assign the corresponding page templates. The homepage uses `front-page.php` (WordPress convention for static front page).

---

## 5. Homepage (Landing Page)

**Template file:** `front-page.php`

**Section order (final, post-review):**

1. Hero (with embedded scanner)
2. The Problem
3. How It Works
4. Citation Simulation
5. Features
6. Pricing
7. Social Proof / About
8. Footer CTA

### 5.1 Navigation

**Markup:** Fixed position nav, transparent by default, adds `.nav--scrolled` class when `scrollY > 40`.

**Links:**

| Label | Destination | Notes |
|:---|:---|:---|
| Logo (AnswerEngineWP) | `/` | Left-aligned |
| Methodology | `/methodology/` | Right group |
| Download Plugin | `https://wordpress.org/plugins/answerenginewp/` | Right group, external |
| Scan Your Site (button) | `/scanner/` | Right group, primary CTA style |

Mobile: hide text links, show only logo + Scan CTA button.

### 5.2 Section-by-Section HTML Structure

Each section below documents the exact HTML structure, CSS classes, and copy. The CSS implementation should match the approved mockup (`answerenginewp-landing.html` v3).

**Build each section as a PHP template part** in `template-parts/home/`:

```
template-parts/home/hero.php
template-parts/home/problem.php
template-parts/home/how-it-works.php
template-parts/home/citation-simulation.php
template-parts/home/features.php
template-parts/home/pricing.php
template-parts/home/social-proof.php
template-parts/home/footer-cta.php
```

`front-page.php` includes them in order:

```php
<?php get_header(); ?>
<?php get_template_part('template-parts/home/hero'); ?>
<?php get_template_part('template-parts/home/problem'); ?>
<?php get_template_part('template-parts/home/how-it-works'); ?>
<?php get_template_part('template-parts/home/citation-simulation'); ?>
<?php get_template_part('template-parts/home/features'); ?>
<?php get_template_part('template-parts/home/pricing'); ?>
<?php get_template_part('template-parts/home/social-proof'); ?>
<?php get_template_part('template-parts/home/footer-cta'); ?>
<?php get_footer(); ?>
```

---

## 6. Scanner Page

**Template file:** `page-scanner.php`
**URL:** `/scanner/`

### 6.1 Page Structure

The scanner is a single-page app with three visual states managed by JavaScript. All three states exist in the DOM; JS toggles visibility.

**Navigation:** Simplified. Logo + "Methodology" + "Download Plugin" only. No Features/Pricing/How It Works links.

### 6.2 State 1: Input

```html
<section class="scanner" id="scanner">
  <div class="container">
    <div class="scanner__input-state" id="scannerInput">
      <h1 class="scanner__h1">Is your website invisible to AI?</h1>
      <p class="scanner__sub">Enter any URL. Get your AI Visibility Score in under 10 seconds.</p>

      <div class="scanner__form">
        <input type="url" id="scanUrl" class="scanner__url-input"
               placeholder="https://yoursite.com" autocomplete="off">
        <div class="scanner__error" id="scanError">
          Please enter a valid URL (e.g. https://yoursite.com)
        </div>

        <button type="button" class="scanner__compare-toggle" id="compareToggle">
          Compare against a competitor →
        </button>
        <input type="url" id="compareUrl" class="scanner__compare-input"
               placeholder="https://competitor.com" autocomplete="off">

        <button type="button" class="scanner__submit" id="scanSubmit">
          Scan Now →
        </button>
        <p class="scanner__micro">Free. No login required. Works on any website.</p>
      </div>
    </div>
  </div>
</section>
```

### 6.3 State 2: Loading

```html
<div class="scanner__loading-state" id="scannerLoading" style="display:none">
  <p class="scanner__loading-url" id="loadingUrl"></p>
  <div class="scanner__progress-track">
    <div class="scanner__progress-fill" id="progressFill"></div>
  </div>
  <p class="scanner__loading-status" id="loadingStatus">Fetching page content...</p>
</div>
```

**Status messages (cycle every 1.5s):**

1. Fetching page content...
2. Detecting schema types...
3. Analyzing content structure...
4. Checking for FAQ blocks...
5. Measuring entity density...
6. Detecting knowledge feeds...
7. Calculating your AI Visibility Score...

**If scan exceeds 10s, add:** "Still analyzing — this page has a lot of content..."

**Minimum display time:** 4 seconds. If the API responds faster, hold on final message until minimum elapses.

### 6.4 State 3: Results

Results load progressively. Score hero first, then sub-scores, then citation sim / competitor / extraction preview / CTAs.

**Sections within results (in order):**

1. **Score Hero** — Score gauge, tier label, context message
2. **Sub-Scores** — 6 horizontal bars (Schema Completeness, Content Structure, FAQ & Answer Coverage, Summary Presence, Feed & Manifest Readiness, Entity Density)
3. **Top 3 Fixes** — Cards with point gains, projected score
4. **Citation Simulation** — Mock AI chat with cited/not-cited verdict
5. **Competitor Gap** — Side-by-side table (only if competitor URL provided)
6. **Extraction Preview** — Found / Missing checklist
7. **CTAs** — Primary install CTA, secondary actions (PDF, Share, Badge, Email)

See Section 15 (Complete Website Copy) for all results copy including tier messages, fix descriptions, and CTA labels.

### 6.5 Scanner JavaScript Architecture

```
assets/js/scanner.js
```

**Responsibilities:**

1. URL validation (client-side, allows bare domains like `example.com`)
2. State management (input → loading → results)
3. API call to `POST /wp-json/aewp/v1/scan`
4. Progressive results rendering
5. Status message cycling (decoupled from actual API response time)
6. Competitor toggle show/hide
7. PDF download trigger
8. Share URL generation
9. Badge snippet generation
10. "Scan another URL" reset

**Validation rules:**
- Empty input → show error "Please enter a valid URL (e.g. https://yoursite.com)"
- Invalid URL (no `.` in hostname after adding `https://` prefix) → show error "That doesn't look like a valid URL. Try including https://"
- Auto-prepend `https://` if no protocol specified
- Strip trailing slashes for display

**Error states:**
- API timeout (>30s) → "This is taking longer than expected. The site may be slow to respond. Try again?"
- API error (4xx/5xx) → "We couldn't scan that URL. The site may be blocking our requests. Try a different URL."
- Rate limited → "You've reached the scan limit. Try again in [X] minutes."

---

## 7. Score Results Page

**Template file:** `page-score-result.php`
**URL pattern:** `/score/{hash}`

This is the public, shareable page for a specific scan result. It loads the stored scan data from the `aewp_scan` CPT by hash.

### 7.1 OG Meta (Dynamic)

```html
<meta property="og:title" content="{url} scored {score}/100 on the AI Visibility Score">
<meta property="og:description" content="{tierLabel}. Scan your own site free at answerenginewp.com/scanner">
<meta property="og:image" content="{dynamically generated OG image URL}">
<meta property="og:url" content="https://answerenginewp.com/score/{hash}">
```

### 7.2 Page Content

Same layout as scanner results state, but pre-rendered from stored data (not fetched via API). Includes a CTA to "Scan your own site →" linking to `/scanner/`.

### 7.3 OG Image Generation

Generate a 1200×630 PNG per scan showing: score gauge, tier badge, scanned URL, and "Scan yours → answerenginewp.com" CTA text. Store in `wp-content/uploads/aewp-og/`. Use PHP GD or Imagick.

---

## 8. Static Pages

### 8.1 Methodology Page

**URL:** `/methodology/`
**Template:** `page-methodology.php`

Explains how the AI Visibility Score is calculated. Content should cover:

- What each of the 6 sub-scores measures
- How sub-scores are weighted into the overall score
- What the tier labels mean
- Why structural signals matter for AI citation
- What the scanner does NOT measure (actual AI behavior, ranking positions)
- Data sources: on-page HTML analysis only, no third-party APIs

### 8.2 Documentation Page

**URL:** `/docs/`
**Template:** `page-docs.php`

Placeholder page linking to plugin documentation. Will be populated post-launch.

### 8.3 Support Page

**URL:** `/support/`
**Template:** `page-support.php`

Contact form or link to WordPress.org support forum.

### 8.4 Badge Page

**URL:** `/badge/`
**Template:** `page-badge.php`

Instructions for embedding the AI Visibility Badge. Includes a live preview and copy-to-clipboard HTML snippet.

### 8.5 Privacy Page

**URL:** `/privacy/`
**Template:** `page-privacy.php`

Standard privacy policy covering: what the scanner collects (URLs scanned, IP hashes for rate limiting), what it doesn't collect (no cookies, no tracking pixels, no personal data), data retention policy.

---

## 9. Scanner Backend — REST API

### 9.1 Endpoint

```
POST /wp-json/aewp/v1/scan
```

**Request body (JSON):**

```json
{
  "url": "https://example.com",
  "competitor_url": "https://competitor.com"  // optional
}
```

**Response (JSON):**

```json
{
  "success": true,
  "hash": "abc123def",
  "url": "example.com",
  "score": 38,
  "tier": "invisible",
  "tier_label": "Invisible to AI",
  "tier_color": "#EF4444",
  "tier_message": "ChatGPT cannot reliably extract or cite your site...",
  "sub_scores": {
    "schema_completeness": { "score": 25, "label": "Schema Completeness", "description": "How many schema.org types are present and properly structured?" },
    "content_structure": { "score": 45, "label": "Content Structure", "description": "Does your heading hierarchy and HTML structure support AI extraction?" },
    "faq_coverage": { "score": 10, "label": "FAQ & Answer Coverage", "description": "Are structured question-answer pairs available for AI to cite?" },
    "summary_presence": { "score": 30, "label": "Summary Presence", "description": "Can AI extract concise definitions and summaries from your pages?" },
    "feed_readiness": { "score": 0, "label": "Feed & Manifest Readiness", "description": "Do /llms.txt and /llms-full.json exist and validate?" },
    "entity_density": { "score": 55, "label": "Entity Density", "description": "How many named entities are machine-identifiable?" }
  },
  "fixes": [
    { "points": 12, "title": "Add FAQPage schema to 8 pages", "description": "Your site has 8 pages with question-answer content but no structured FAQ markup." },
    { "points": 9, "title": "Fix heading hierarchy on 3 pages", "description": "Three pages skip from H2 to H4." },
    { "points": 7, "title": "Add structured summaries to key pages", "description": "Your top pages lack concise opening summaries." }
  ],
  "projected_score": 66,
  "extraction": {
    "entities": ["HubSpot", "Salesforce", "Austin TX"],
    "headlines": ["H1: Best CRM Guide", "H2: For Real Estate", "H2: Pricing"],
    "structured_answers": 0,
    "extractable_summaries": 1,
    "schema_types": ["Article", "Organization"],
    "missing": ["No Speakable markup", "No /llms.txt file", "No FAQ schema"]
  },
  "competitor": {
    "url": "competitor.com",
    "score": 82,
    "tier": "extractable",
    "tier_label": "AI Extractable",
    "sub_scores": { ... },
    "extraction": { ... }
  },
  "citation_simulation": {
    "prompt": "Best CRM for real estate agents",
    "would_cite": false,
    "reasons": ["No FAQ schema detected", "No extractable summary found", "Missing Speakable markup"]
  },
  "share_url": "https://answerenginewp.com/score/abc123def",
  "pdf_url": "https://answerenginewp.com/wp-json/aewp/v1/report/abc123def"
}
```

### 9.2 Rate Limiting

- 10 scans per hour per IP (unauthenticated)
- 50 scans per hour per IP (with API key, for agencies — future)
- Store hashed IPs in transients
- Return `429 Too Many Requests` with `retry_after` value

### 9.3 Scoring Engine

**File:** `inc/scanner-engine.php`

The scoring engine fetches the target URL server-side, parses the HTML, and calculates 6 sub-scores.

**Fetch pipeline:**
1. `wp_remote_get()` with 15-second timeout
2. Parse HTML with DOMDocument
3. Extract JSON-LD, Microdata, RDFa
4. Analyze heading hierarchy
5. Detect FAQ/Q&A blocks
6. Check for `/llms.txt` and `/llms-full.json` at domain root
7. Count named entities (basic NER: proper nouns, org names, place names)
8. Calculate sub-scores and weighted overall

**Sub-score weights:**

| Sub-Score | Weight | Max Points |
|:---|:---|:---|
| Schema Completeness | 20% | 20 |
| Content Structure | 15% | 15 |
| FAQ & Answer Coverage | 20% | 20 |
| Summary Presence | 20% | 20 |
| Feed & Manifest Readiness | 10% | 10 |
| Entity Density | 15% | 15 |
| **Total** | **100%** | **100** |

### 9.4 PDF Report Endpoint

```
GET /wp-json/aewp/v1/report/{hash}
```

Returns a generated PDF. See Section 10 for PDF specification.

---

## 10. PDF Report Generator

**File:** `inc/pdf-generator.php`
**Library:** TCPDF (install via Composer) or Dompdf

### 10.1 Report Structure

| Page | Content |
|:---|:---|
| 1 | Cover: logo, "AI Visibility Audit Report", URL, date, overall score gauge with tier badge |
| 2 | Score breakdown: overall score, tier label + message, 6 sub-score horizontal bars |
| 3 | Competitor Structure Gap (if competitor provided): side-by-side comparison table |
| 4 | Extraction Preview: entities found, headings, schemas, missing items |
| 5 | Top 3 Fixes: prioritized recommendations with point gains |
| 6 | CTA: "Fix this in 60 seconds with AnswerEngineWP" + download link + scanner link |

### 10.2 Design

- Page size: US Letter (8.5" × 11")
- Margins: 1" all sides
- Brand colors throughout
- Score tier color-coding on every page
- Clean, data-forward — no marketing fluff in the body
- Footer: "Generated by AnswerEngineWP · answerenginewp.com" on every page

---

## 11. Badge Embed System

### 11.1 Badge HTML

Users who score 70+ can embed a badge on their site:

```html
<a href="https://answerenginewp.com/score/{hash}"
   title="AI Visibility Score: {score}/100 — {tierLabel}"
   style="display:inline-block;text-decoration:none">
  <img src="https://answerenginewp.com/wp-json/aewp/v1/badge/{hash}.svg"
       alt="AI Visibility Score: {score}/100"
       width="160" height="50">
</a>
```

### 11.2 Badge SVG Endpoint

```
GET /wp-json/aewp/v1/badge/{hash}.svg
```

Returns an SVG image showing: "AI Visibility" label, score number, tier color bar, AnswerEngineWP attribution.

---

## 12. Analytics & Tracking

### 12.1 Plausible Analytics

Install Plausible script in `header.php`. Track custom events:

| Event | Trigger |
|:---|:---|
| `scan_started` | User clicks "Scan Now" |
| `scan_completed` | API returns results |
| `scan_error` | API returns error |
| `competitor_added` | User expands competitor field |
| `pdf_downloaded` | User clicks "Download PDF" |
| `score_shared` | User clicks "Share" |
| `badge_copied` | User copies badge snippet |
| `plugin_cta_clicked` | User clicks "Install AnswerEngineWP" |
| `pricing_cta_clicked` | User clicks any pricing CTA (with tier label) |

### 12.2 Internal Scanner Metrics

Store in WordPress options or a custom table:

- Total scans (daily, weekly, monthly)
- Score distribution histogram
- Most common missing signals
- Competitor comparison rate
- PDF download rate

---

## 13. SEO & Meta Tags

### 13.1 Homepage Meta

```html
<title>Is your website invisible to ChatGPT? · AnswerEngineWP</title>
<meta name="description" content="Free AI Visibility Score for any website. See what ChatGPT can extract from your site — and what it can't.">
<meta property="og:title" content="Is your website invisible to ChatGPT? · AnswerEngineWP">
<meta property="og:description" content="Free AI Visibility Score for any website. See what ChatGPT can extract from your site — and what it can't.">
<meta property="og:url" content="https://answerenginewp.com">
<meta property="og:type" content="website">
<meta property="og:image" content="https://answerenginewp.com/assets/images/og-image-1200x630.png">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="Is your website invisible to ChatGPT?">
<meta name="twitter:description" content="Free AI Visibility Score for any website. Find out in 10 seconds.">
<meta name="twitter:image" content="https://answerenginewp.com/assets/images/og-image-1200x630.png">
```

### 13.2 Scanner Page Meta

```html
<title>AI Visibility Scanner — Is your website invisible to AI? · AnswerEngineWP</title>
<meta name="description" content="Free AI Visibility Score for any website. Enter your URL and see what AI systems can extract from your site in under 10 seconds.">
<meta property="og:title" content="Is your website invisible to AI? Scan free. · AnswerEngineWP">
<meta property="og:description" content="Enter any URL. Get your AI Visibility Score in under 10 seconds. Free, no login required.">
<meta property="og:image" content="https://answerenginewp.com/assets/images/og-scanner-1200x630.png">
```

### 13.3 Score Results Page Meta (Dynamic)

```html
<title>{url} scored {score}/100 — AI Visibility Score · AnswerEngineWP</title>
<meta property="og:title" content="{url} scored {score}/100 on the AI Visibility Score">
<meta property="og:description" content="{tierLabel}. Scan your own site free at answerenginewp.com/scanner">
<meta property="og:image" content="https://answerenginewp.com/wp-content/uploads/aewp-og/{hash}.png">
```

### 13.4 JSON-LD (Homepage)

```json
{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "AnswerEngineWP",
  "applicationCategory": "WebApplication",
  "operatingSystem": "WordPress",
  "description": "AI Visibility plugin for WordPress. Make your site readable, extractable, and citable by ChatGPT, Perplexity, and Google AI Overviews.",
  "url": "https://answerenginewp.com",
  "offers": [
    { "@type": "Offer", "price": "0", "priceCurrency": "USD", "name": "Free" },
    { "@type": "Offer", "price": "49", "priceCurrency": "USD", "name": "Pro" },
    { "@type": "Offer", "price": "199", "priceCurrency": "USD", "name": "Agency" }
  ]
}
```

---

## 14. Performance Requirements

| Metric | Target |
|:---|:---|
| LCP (Largest Contentful Paint) | < 2.0s |
| FID (First Input Delay) | < 100ms |
| CLS (Cumulative Layout Shift) | < 0.1 |
| Total page weight (homepage) | < 500KB |
| Total page weight (scanner) | < 300KB |
| Time to first scan result | < 10s |
| Scanner API response | < 8s |

**Optimization requirements:**
- Inline critical CSS in `<head>` for above-the-fold content
- Lazy-load below-fold sections with IntersectionObserver
- Font preconnect headers
- No render-blocking JavaScript (all scripts deferred or in footer)
- Server-side scanner caching: cache scan results for same URL for 24 hours
- Gzip/Brotli compression enabled

---

## 15. Complete Website Copy

### 15.1 Homepage — Hero

**H1:** Is your website invisible to ChatGPT?

**Subheadline:** Turn your WordPress site into something AI systems can read, extract, and cite — including ChatGPT, Perplexity, and Google AI Overviews.

**Proof Line:** Most WordPress sites score below 40/100 on the AI Visibility Score. Find out where you stand in under 10 seconds.

**Primary CTA:** Scan Your Site Free →

**Secondary link:** or download the free WordPress plugin

**Companion Mode Line:** Works alongside Yoast and Rank Math. No conflicts. No replacements. No duplicate schema.

**Scanner card label:** AI Visibility Scanner

**Scanner input placeholder:** https://yoursite.com

**Compare toggle:** Compare against a competitor →

**Compare placeholder:** https://competitor.com

**Scan button:** Scan Now →

**Microcopy:** Free. No login required. Works on any website.

**Scanner error (empty):** Please enter a valid URL (e.g. https://yoursite.com)

**Scanner error (invalid):** That doesn't look like a valid URL. Try including https://

**Result CTA:** Fix this instantly → Install AnswerEngineWP (Free)

**Reset link:** ← Scan another URL

### 15.2 Homepage — The Problem

**Section label:** The Shift

**H2:** SEO gets you ranked. But AI doesn't rank — it extracts.

**Body paragraph 1:** ChatGPT, Perplexity, and Google AI Overviews don't show ten blue links. They synthesize one answer and cite the sources they trust.

**Body paragraph 2:** Your WordPress site was built for search engines that crawl HTML and reward backlinks. AI systems need structured knowledge — entity graphs, answer blocks, machine-readable feeds. Without them, your content doesn't exist to the fastest-growing discovery channel on the internet.

**Body paragraph 3:** Most SEO plugins help you rank. They don't help you get quoted. AnswerEngineWP bridges the gap.

**Illustration — left panel label:** Traditional Search

**Illustration — left highlight:** ↑ You rank here

**Illustration — right panel label:** AI Answer Engine

**Illustration — right AI bubble:** Based on available sources, the best approach involves structured data and entity relationships for maximum visibility...

**Illustration — right citation:** yoursite.com — Cited

**Illustration — caption:** Which one drives more trust?

### 15.3 Homepage — How It Works

**Section label:** How It Works

**H2:** From invisible to cited in 4 steps.

**Step 1 title:** Scan your site

**Step 1 description:** Enter any URL and get your AI Visibility Score in under 10 seconds. Works on any website — yours, your clients', your competitors'.

**Step 2 title:** Install the free plugin

**Step 2 description:** AnswerEngineWP runs alongside Yoast or Rank Math. No conflicts, no duplicate schema. Just the AI visibility layer your SEO plugin doesn't cover.

**Step 3 title:** Add Answer Blocks

**Step 3 description:** Create structured content blocks AI systems prefer to cite — question, answer summary, and supporting facts — directly in Gutenberg.

**Step 4 title:** Track AI visibility

**Step 4 description:** See which AI bots visit your site, what they can extract, and how your score improves as you optimize.

**Companion card title:** We don't replace your SEO plugin. We upgrade it for the AI era.

**Companion card body:** AnswerEngineWP adds AI-specific structure — Speakable markup, Answer Blocks, and knowledge feeds — without duplicating the schema your SEO plugin already manages.

**Companion card logos:** Yoast SEO · Rank Math · All in One SEO

### 15.4 Homepage — Citation Simulation

**Section label:** AI Citation Simulation

**H2:** Would AI cite you — or your competitor?

**Body:** Enter your topic. See a simulated AI answer — and whether your site appears as a cited source. It's a structural stress-test: if your page has the right architecture, AI systems are more likely to extract and cite it.

**Badge (on mock chat card):** Simulated

**Mock prompt label:** User prompt

**Mock prompt:** "Best CRM for real estate agents"

**Mock AI response:** Based on available comparisons, the most recommended CRM platforms for real estate include HubSpot for teams, Follow Up Boss for solo agents, and Salesforce for enterprise brokerages. Key factors include pipeline management, automated follow-ups, and MLS integration.

**Source 1:** yoursite.com/crm-guide → ✓ Your site would likely be cited

**Source 2:** hubspot.com/real-estate

**Source 3:** competitor.com/crm-list → ✗ Not cited

**Why not cited title:** Why competitor.com was not cited

**Why not cited reasons:** No FAQ schema detected. No extractable summary found. Missing Speakable markup on key content.

**CTA:** Try it with your site →

**Disclaimer:** Citation likelihood based on structural signals, not AI ranking models. This estimates how extractable your content is — not how any specific AI system will respond to any specific query.

### 15.5 Homepage — Features

**Section label:** Features

**H2:** Everything you need to get cited by AI.

**Subheadline:** Free tools to diagnose. Pro tools to automate. Agency tools to scale.

**Primary features (4 cards, 2×2 grid):**

1. AI Visibility Score · Free — 0–100 score measuring how extractable and citable your content is. Benchmarked against competitors.
2. AI Extraction Preview · Free — See exactly what ChatGPT would pull from your page. Find the gaps before AI does.
3. AI Citation Simulation · Free — See a simulated AI answer for your topic — and whether your site gets cited or your competitor does.
4. Speakable Answer Blocks · Free — Structured content blocks AI systems prefer to quote. Built directly in the Gutenberg editor.

**Supporting features (6 cards, 3×2 grid):**

1. /llms.txt Generator · Free — Create the machine-readable manifest AI crawlers look for.
2. 1-Click AI Summaries · Pro — Auto-generate structured summaries for every page.
3. AI Crawler Analytics · Pro — Track visits from GPTBot, ClaudeBot, and PerplexityBot.
4. Elementor / Divi Integration · Pro — Grade and optimize pages built with visual editors.
5. Dynamic Knowledge Feeds · Pro — Full /llms-full.json RAG-ready feeds for AI ingestion.
6. Bulk Site Scan · Agency — Upload 50 URLs. Get a scored spreadsheet. Audit portfolios in minutes.

### 15.6 Homepage — Pricing

**Section label:** Pricing

**H2:** Priced as an add-on, not a replacement.

**Subheadline:** Your existing SEO plugin costs ~$99/year. AnswerEngineWP sits alongside it — not on top of it.

**Free tier:**

- Tier name: Free
- Price: $0
- Tagline: Get the diagnostic for free.
- Features: AI Visibility Score (benchmarked), AI Extraction Preview, AI Citation Simulation, Basic Speakable Answer Blocks, Basic /llms.txt generator
- CTA: Download Free → links to WP.org

**Pro tier:**

- Tier name: Pro
- Price: $49–$79/year
- Tagline: Automate the fix.
- Badge: Most Popular
- Features: Everything in Free, 1-Click AI Summary Generation, Elementor / Divi page grading, AI Crawler Analytics Dashboard, Dynamic /llms-full.json feeds, Priority support
- CTA: Upgrade to Pro → links to checkout

**Agency tier:**

- Tier name: Agency — 25 sites
- Price: $199–$299/year
- Tagline: Turn AI visibility into a retainer service.
- Features: Everything in Pro, Bulk Site Scan (50 URLs, CSV), Multisite entity sync, Unbranded PDF audit reports, Headless API access
- CTA: Get Agency License → links to checkout

**Context line:** Most agencies charge $500+/month for AI search readiness retainers. The Agency license pays for itself with a single client.

### 15.7 Homepage — Social Proof / About

**Section label:** About

**H2:** Built for agencies who take AI search seriously.

**Credential:** Created by the founder of Guestify — longtime WordPress developer and SEO strategist with over a decade in the ecosystem. AnswerEngineWP was built because the tools to optimize for AI citation didn't exist yet.

**Placeholder note:** Early adopter testimonials will appear here as agencies complete testing. We don't fabricate social proof.

### 15.8 Homepage — Footer CTA

**H2:** Your competitors are already being cited. Are you?

**CTA:** Scan Your Site Free →

**Footer links:** WordPress Plugin Page · Documentation · Support · AI Visibility Badge · Methodology

**Copyright:** © 2026 AnswerEngineWP

### 15.9 Scanner Page Copy

See the complete `AnswerEngineWP_Scanner_Copy.md` document for all scanner states, tier messages, sub-score descriptions, fix card templates, citation simulation copy, competitor gap copy, extraction preview copy, and all CTA labels. All copy in that document is final and should be implemented verbatim.

### 15.10 Load-Bearing Lines

These lines must not be changed without strategic review. They are the conversion backbone of the entire site:

1. "Is your website invisible to ChatGPT?" (homepage hero)
2. "Is your website invisible to AI?" (scanner hero)
3. "SEO gets you ranked. But AI doesn't rank — it extracts."
4. "Would AI cite you — or your competitor?"
5. "From invisible to cited in 4 steps."
6. "Priced as an add-on, not a replacement."
7. "Your competitors are already being cited. Are you?"
8. "Better-structured competitors are more likely to be cited while your content goes unread by the systems your audience increasingly trusts." (0–39 tier)
9. "You're in the room — but you're not being quoted." (40–69 tier)
10. "Your fastest path to AI visibility" (Top 3 Fixes)
11. "Fix this instantly → Install AnswerEngineWP (Free)"

---

## 16. File Structure

```
answerenginewp/                          # Theme root
├── style.css                            # Theme header + full CSS (design system)
├── functions.php                        # Theme setup, enqueues, REST API loader
├── header.php                           # <head>, nav, opening <body>
├── footer.php                           # Footer, closing </body>, scripts
├── front-page.php                       # Homepage template
├── page-scanner.php                     # Scanner page template
├── page-score-result.php                # Score share/results page template
├── page-methodology.php                 # Methodology page template
├── page-docs.php                        # Documentation page template
├── page-support.php                     # Support page template
├── page-badge.php                       # Badge embed page template
├── page-privacy.php                     # Privacy policy page template
├── 404.php                              # 404 page
│
├── template-parts/
│   ├── home/
│   │   ├── hero.php                     # Hero section with scanner card
│   │   ├── problem.php                  # "The Shift" section
│   │   ├── how-it-works.php             # 4-step flow + companion card
│   │   ├── citation-simulation.php      # Mock AI chat section
│   │   ├── features.php                 # Feature grid (primary + supporting)
│   │   ├── pricing.php                  # 3-tier pricing cards
│   │   ├── social-proof.php             # About / credential section
│   │   └── footer-cta.php              # Navy footer CTA section
│   ├── scanner/
│   │   ├── input-state.php              # URL input form
│   │   ├── loading-state.php            # Progress bar + status messages
│   │   └── results-state.php            # Full results template
│   └── nav/
│       ├── nav-primary.php              # Homepage nav (Methodology, Download, Scan)
│       └── nav-scanner.php              # Scanner nav (Methodology, Download only)
│
├── inc/
│   ├── rest-api.php                     # REST endpoint registration
│   ├── scanner-engine.php               # URL fetching, parsing, scoring
│   ├── pdf-generator.php                # PDF report generation
│   ├── badge-generator.php              # SVG badge generation
│   ├── og-image-generator.php           # Dynamic OG image generation
│   └── rate-limiter.php                 # IP-based rate limiting
│
├── assets/
│   ├── css/
│   │   └── (CSS lives in style.css for simplicity)
│   ├── js/
│   │   ├── home.js                      # Homepage interactions (scroll anim, hero scanner)
│   │   └── scanner.js                   # Scanner app (states, API, results rendering)
│   ├── images/
│   │   ├── og-image-1200x630.png        # Default OG image (homepage)
│   │   └── og-scanner-1200x630.png      # Scanner page OG image
│   └── fonts/                           # (empty — fonts loaded from Google CDN)
│
├── composer.json                         # TCPDF or Dompdf dependency
└── screenshot.png                        # Theme screenshot for WP admin
```

---

## 17. Implementation Sequence

Build in this order. Each phase should be testable before moving to the next.

### Phase A: Theme Foundation (Day 1)

1. Generate Underscores theme, strip defaults
2. Implement `functions.php` with enqueues, cleanup
3. Create `style.css` with complete design system (all CSS variables, typography, components, responsive breakpoints)
4. Build `header.php` and `footer.php` with primary nav
5. Create `front-page.php` with empty section includes

### Phase B: Homepage Sections (Days 2–3)

Build each template part in order:
1. `hero.php` — with embedded scanner card (visual-only demo for now)
2. `problem.php` — text + shift illustration
3. `how-it-works.php` — 4-step grid + companion card
4. `citation-simulation.php` — mock AI chat
5. `features.php` — feature card grids
6. `pricing.php` — 3-tier pricing cards
7. `social-proof.php` — credential + placeholder
8. `footer-cta.php` — navy CTA section
9. `home.js` — scroll animations, nav scroll effect, hero scanner demo

### Phase C: Scanner Page (Days 3–4)

1. Create `page-scanner.php` with three states
2. Build scanner input state, loading state, results state HTML
3. Implement `scanner.js` — full client-side state management
4. Connect to API (stubbed initially, then real)

### Phase D: Scanner Backend (Days 4–6)

1. Implement `rest-api.php` — register endpoints
2. Implement `scanner-engine.php` — URL fetching, HTML parsing
3. Implement scoring algorithm (6 sub-scores + weighted total)
4. Implement `rate-limiter.php`
5. Register `aewp_scan` CPT for storing results
6. Test full scan flow end-to-end

### Phase E: PDF & Sharing (Days 6–7)

1. Install TCPDF/Dompdf via Composer
2. Implement `pdf-generator.php` — 6-page report
3. Implement `badge-generator.php` — SVG badge endpoint
4. Implement `og-image-generator.php` — dynamic OG images
5. Create `page-score-result.php` — public share pages
6. Set up rewrite rules for `/score/{hash}` URLs

### Phase F: Static Pages & Polish (Days 7–8)

1. Create methodology, docs, support, badge, privacy pages
2. Implement all meta tags (per-page OG, Twitter, JSON-LD)
3. Performance audit — inline critical CSS, lazy-load, compress
4. Mobile testing across breakpoints
5. Cross-browser testing (Chrome, Firefox, Safari, Edge)

### Phase G: Analytics (Day 8)

1. Install Plausible Analytics
2. Implement custom event tracking
3. Verify funnel events fire correctly

---

## 18. Acceptance Criteria

### Homepage

- [ ] Hero communicates value proposition in under 5 seconds
- [ ] Scanner CTA button is above the fold on desktop
- [ ] Embedded scanner card demonstrates the input → loading → result flow
- [ ] Companion Mode messaging is visible in first viewport
- [ ] Citation Simulation appears before Features (section order correct)
- [ ] All pricing tiers are clear with no ambiguity
- [ ] Social proof section is honest (no fabricated stats or testimonials)
- [ ] Footer CTA repeats "Scan Your Site Free" with consistent styling
- [ ] All links point to real destinations (no `href="#"`)
- [ ] Mobile responsive at all breakpoints
- [ ] Page loads in under 2 seconds (LCP)
- [ ] OG/Twitter meta tags produce clean social previews with images

### Scanner

- [ ] User enters URL → sees score in under 10 seconds
- [ ] Empty URL submission shows validation error
- [ ] Invalid URL shows specific error message
- [ ] Loading state cycles through all 7 status messages
- [ ] Results show score gauge with correct tier color and label
- [ ] Sub-scores display as 6 horizontal bars
- [ ] Top 3 Fixes show point gains and projected score
- [ ] Citation simulation shows cited/not-cited verdict with reasons
- [ ] Competitor comparison displays side-by-side table (when competitor provided)
- [ ] Extraction preview shows found/missing checklist
- [ ] PDF download generates branded report
- [ ] Share URL creates functional public page
- [ ] Badge snippet generates valid HTML
- [ ] "Scan another URL" resets to input state cleanly
- [ ] Rate limiting prevents abuse (10 scans/hour/IP)
- [ ] Works on non-WordPress sites
- [ ] Mobile responsive with sticky CTA on results
- [ ] Error states handled for timeouts, blocked URLs, rate limits

### Score Share Page

- [ ] `/score/{hash}` loads with correct score data
- [ ] OG meta tags show correct score, tier, and URL
- [ ] Dynamic OG image generates with score gauge
- [ ] Page includes "Scan your own site" CTA
- [ ] 404 gracefully if hash doesn't exist

### PDF Report

- [ ] 6-page branded report generates from scan data
- [ ] Score gauge renders with correct tier color
- [ ] Sub-score bars render correctly
- [ ] Competitor comparison renders when data exists
- [ ] Recommendations are specific to the scanned URL
- [ ] CTA page has correct download/install links
- [ ] Looks professional when attached to a cold email

---

*This specification is the single source of truth for building the AnswerEngineWP marketing website. All copy, design tokens, architecture decisions, and acceptance criteria are final. Execute against it.*
