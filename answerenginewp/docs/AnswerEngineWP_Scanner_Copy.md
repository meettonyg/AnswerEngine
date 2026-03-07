# AnswerEngineWP — Scanner Page Copy

**URL:** answerenginewp.com/scanner
**Version:** Draft 1
**Date:** March 5, 2026

---

## How to Read This Document

- **WEB COPY** — The exact words that appear on the page. Under section headers.
- **NOTES** — Rationale, visual direction, developer/designer instructions. Under `### Notes:` headers. Do not appear on the website.

---

## What This Page Does

The scanner is the top-of-funnel engine for the entire business. It is a standalone web app — not part of the WordPress plugin. It must work independently of WordPress.org approval.

The user flow: enter URL → see score → feel urgency → install plugin to fix.

The scanner has three visual states: Input, Loading, and Results. Each is documented below with all copy.

---

# STATE 1: INPUT

**Headline**

Is your website invisible to AI?

**Subheadline**

Enter any URL. Get your AI Visibility Score in under 10 seconds.

**Input Field**

Placeholder text: `https://yoursite.com`

**Competitor Input (collapsible)**

Link text: Compare against a competitor →

*Clicking reveals a second URL input field.*

Second field placeholder: `https://competitor.com`

**CTA Button**

Scan Now →

**Microcopy (below button)**

Free. No login required. Works on any website.

---

### Notes: Input State

**Visual direction:**

- Centered, minimal, hero-like layout. Navy (`#1A2332`) or white background — test both.
- Large headline, single URL input field with generous sizing (tall input, large text)
- "Compare against a competitor" is a text link that expands to show the second field. Collapsed by default — keeps the initial view clean.
- CTA button: Primary Blue (`#2563EB`), large, prominent
- Microcopy in muted text (`#64748B`) below the button
- Mobile: stack vertically, full-width input and button

**Copy rationale:**

- Headline uses "AI" instead of "ChatGPT" (landing page hero uses ChatGPT). This is intentional — the scanner headline should be broader because it works on any website, not just WordPress sites. Visitors arriving directly (not from the landing page) need a universal hook.
- "Under 10 seconds" sets the time expectation — critical for preventing abandonment during the loading state.
- "No login required" kills the friction of account creation. "Works on any website" signals this isn't WordPress-only — agencies can audit any prospect or client site.
- Competitor input is hidden by default because most first-time users will scan their own site first. The comparison is a power feature for return visits and agency use.

---

# STATE 2: LOADING

**Animated progress with status messages (cycle through in sequence):**

1. Fetching page content...
2. Detecting schema types...
3. Analyzing content structure...
4. Checking for FAQ blocks...
5. Measuring entity density...
6. Detecting knowledge feeds...
7. Calculating your AI Visibility Score...

---

### Notes: Loading State

**Visual direction:**

- Same frame as the input state — the URL field and button remain visible but disabled/grayed
- Animated progress bar or circular scanner animation below the input
- Status messages cycle every ~1.5 seconds, replacing each other (not stacking)
- Use a subtle fade transition between messages
- Final message ("Calculating your AI Visibility Score...") should hold slightly longer (~2 seconds) to build anticipation before results appear

**Technical direction (developer):**

- Each status message corresponds to an actual Phase A pipeline step. They are not fake — they reflect real processing.
- However, the timing of the messages is decoupled from actual processing time. Messages cycle at a fixed pace for UX consistency. If the score calculates faster than the message cycle, hold on the final message until the minimum display time (3–4 seconds total) elapses. A scan that finishes too fast feels untrustworthy — it should feel like real analysis is happening.
- If the scan takes longer than 10 seconds, add a "Still analyzing — this page has a lot of content..." message to prevent anxiety.

**Copy rationale:**

- Messages are written in plain language, not developer jargon. "Detecting schema types" not "Parsing JSON-LD and Microdata." The audience is SEO professionals, not engineers.
- The progression feels like a checklist being completed — each step builds the impression of thoroughness.
- "Calculating your AI Visibility Score" uses "your" to personalize the moment before the big reveal.

---

# STATE 3: RESULTS

The results page loads progressively. The score hero appears first. Sub-scores fill in. Citation simulation, competitor gap, and PDF button load asynchronously.

---

## 3.1 — Score Hero

**Score Display**

Large number (72px+), color-coded by tier. Tier label directly below in matching color.

**Tier Labels and Context Messages:**

---

### 0–39: Invisible to AI

**Label:** Invisible to AI

**Context Message:**

ChatGPT cannot reliably extract or cite your site. Better-structured competitors are more likely to be cited while your content goes unread by the systems your audience increasingly trusts.

**Color:** Red (`#EF4444`)

---

### 40–69: AI Readable

**Label:** AI Readable

**Context Message:**

AI systems can find your content, but they're choosing better-structured competitors to cite. You're in the room — but you're not being quoted.

**Color:** Yellow (`#EAB308`)

---

### 70–89: AI Extractable

**Label:** AI Extractable

**Context Message:**

AI systems can extract and structure your content effectively. You're close to becoming a preferred citation source — a few structural improvements separate you from authority status.

**Color:** Blue (`#3B82F6`)

---

### 90–100: AI Authority

**Label:** AI Authority

**Context Message:**

Your site is fully optimized for AI extraction and citation. AI systems treat your content as a trusted, authoritative source. You are an AI authority in your space.

**Color:** Green (`#22C55E`)

---

### Notes: Score Hero

**Visual direction:**

- Full-width card at top of results, background tinted to tier color (light wash, not solid)
- Score number: 48–72px, Inter Black or tabular lining numerals, centered
- Tier label: directly below score, matching tier color, bold
- Context message: 2–3 lines below tier label, Text Dark (`#111827`), normal weight
- Score gauge animation: ring/arc fills from 0 to final score on load (~1.5 seconds). Satisfying motion.
- Scanned URL displayed above the score: `yoursite.com` in muted text

**Copy rationale:**

- The 0–39 and 40–69 messages are where conversion happens. They lead with consequences ("cannot reliably cite," "choosing competitors to cite"), not technical descriptions. The emotional discomfort drives action.
- 70–89 uses "a few structural improvements" to signal the gap is closeable — the user is close, they just need the plugin. This is the "almost there" motivator.
- 90–100 reinforces the achievement and uses "authority" — tying back to the tier name. This is the message badge-worthy users see before they embed their score.
- Every tier message avoids absolute claims about what AI systems "will" do. Language uses "can," "reliably," "prefer" — defensible hedging that doesn't weaken emotional impact.

---

## 3.2 — Sub-Scores

**Section Label**

Score Breakdown

**Sub-scores (each displayed as a 0–100 bar or radial):**

**Schema Completeness**
How many schema.org types are present and properly structured?

**Content Structure**
Does your heading hierarchy and HTML structure support AI extraction?

**FAQ & Answer Coverage**
Are structured question-answer pairs available for AI to cite?

**Summary Presence**
Can AI extract concise definitions and summaries from your pages?

**Feed & Manifest Readiness**
Do `/llms.txt` and `/llms-full.json` exist and validate?

**Entity Density**
How many named entities (people, products, organizations, places) are machine-identifiable?

---

### Notes: Sub-Scores

**Visual direction:**

- Horizontal bar chart (preferred) or 6 radial indicators in a row
- Each bar: labeled left, score number right, bar fill color-coded by individual sub-score tier
- Bars animate sequentially (stagger ~200ms each) after the main score loads
- Compact layout — this section should not dominate the page. It's supporting detail, not the main event.

**Technical direction:**

- Sub-score descriptions are static copy (shown above). The numerical values are dynamic from the scoring engine.
- Each sub-score maps to a specific scoring signal from the methodology (Section 6 of Phase 0 guide). The descriptions here are plain-language translations.

**Copy rationale:**

- Descriptions are written as questions, not statements. Questions make the user self-evaluate: "Do I have this? No? That's a problem." This is micro-tension that feeds into the Top 3 Fixes panel below.
- "Machine-identifiable" in Entity Density avoids the word "readable" (already used elsewhere) and sounds more precise for a technical audience.

---

## 3.3 — Top 3 Fixes

**Section Headline**

Your fastest path to AI visibility

**Fix Cards (3 cards, ranked by point gain):**

Each card contains:

- **Point gain** (large, green): +[N] points
- **Fix title** (bold): what to do
- **Explanation** (1 line): why it matters

**Example output (dynamic — generated by scoring engine):**

**+12 points** — Add FAQPage schema to 8 pages
Your site has 8 pages with question-answer content but no structured FAQ markup. Adding schema makes these directly extractable by AI.

**+9 points** — Fix heading hierarchy on 3 pages
Three pages skip from H2 to H4. Proper nesting helps AI systems parse your content structure correctly.

**+7 points** — Add structured summaries to key pages
Your top pages lack concise opening summaries. AI systems preferentially extract definitions and summaries over long prose.

---

**Projected Score (below fix cards):**

Current score: **38**/100
Estimated after fixes: **66**/100

**CTA (directly below projected score)**

Get these fixes → Install AnswerEngineWP (Free)

**Disclaimer (small text)**

Estimates based on structural analysis. Actual improvement depends on implementation.

---

### Notes: Top 3 Fixes

**Visual direction:**

- Three cards in a row (desktop) / stacked (mobile)
- Each card: large green `+N` number (24–32px), bold fix title, muted explanation text
- Below cards: dual-gauge visualization — current score (red/yellow gauge, left) → arrow → projected score (blue/green gauge, right)
- The projected score gauge should be visually brighter/more positive than the current score gauge
- CTA button: Primary Blue, full width below the gauges

**Technical direction:**

- Point estimates calculated from sub-score gaps: if a sub-score is at 30/100 and the fix would bring it to ~70/100, the weighted contribution = (70-30) × signal weight. Round to nearest whole number.
- Estimates should be conservative. Underpromise so actual improvement feels like a win.
- Fix descriptions are generated dynamically based on what the scoring engine detected as missing. The examples above are representative — the actual text will vary by site.

**Copy rationale:**

- This panel is the diagnostic-to-prescription bridge. It transforms the scanner from "you have a problem" into "here's exactly how to fix it — and by how much."
- The projected score is the single biggest conversion lever on the results page. A user at 38 who sees "estimated 66 after fixes" has a concrete, quantified reason to install.
- CTA says "Get these fixes" — it frames the plugin as the delivery mechanism for the specific fixes just shown, not a generic product pitch.
- Disclaimer is necessary but kept short. One line. Small text. It does its legal job without undermining the emotional momentum.

---

## 3.4 — AI Citation Simulation

**Section Headline**

Would AI cite you — or your competitor?

**Display elements (all dynamically generated):**

- **Simulated prompt:** auto-generated from the page's primary topic/title (e.g., "Best CRM for real estate agents")
- **Simulated AI answer:** 3–4 sentence paragraph in AI chat bubble style
- **Sources cited:** 3–4 URLs listed below the answer
- **User's site status:**
  - If cited: green highlight, checkmark icon, "Your site would likely be cited"
  - If not cited: red highlight, X icon, "Your site was not cited — here's why" (followed by 1–2 structural reasons)
- **Competitor status (if provided):** shown in the sources list for direct comparison

**Mandatory Disclaimer (small text)**

Citation likelihood based on structural signals, not AI ranking models. This estimates how extractable your content is — not how any specific AI system will respond to any specific query.

---

### Notes: Citation Simulation

**Visual direction:**

- Card-style layout resembling a familiar AI chat UI (rounded bubbles, clean typography)
- Prompt in a "user message" bubble (right-aligned or top)
- Answer in an "AI response" bubble (left-aligned or below)
- Sources list: clean URL list, user's site highlighted green or red
- Clear "Simulated" badge in the top-right corner of the card — visible but not dominant
- This card must look good as a screenshot. Agencies will screenshot it and share on Slack/LinkedIn/client emails.

**Technical direction:**

- The simulation is generated from structural analysis, NOT an actual LLM call. It analyzes schema types, FAQ presence, entity density, and summary availability to estimate citation likelihood.
- The simulated answer text can be a generic template paragraph that references the page's detected topic. It does not need to be high-quality prose — it's a visual prop for the citation list below it.
- The "here's why" reasons for non-citation should pull from the same data as the Top 3 Fixes (e.g., "No FAQ schema detected," "No extractable summary found").

**Copy rationale:**

- "Would AI cite you" uses "would" (conditional) not "will" (predictive). Defensible language.
- The disclaimer is load-bearing and non-negotiable. "Structural signals" = verifiable. "Not AI ranking models" = clear boundary. This manages the inevitable complaint when simulation doesn't match actual ChatGPT results.
- If the user's site IS cited (green), this becomes a shareable win — screenshot material. If it's NOT cited (red), this becomes the deepest emotional trigger on the page — sharper than the score alone because it shows a concrete scenario where the competitor wins.

---

## 3.5 — Competitor Gap

*Only displayed if a second URL was entered.*

**Section Headline**

How you compare

**Comparison Table:**

Side-by-side table with rows for each sub-metric:

| Metric | Your Site | Competitor |
|:---|:---|:---|
| Overall Score | [34]/100 | [82]/100 |
| Schema Completeness | [25] | [88] |
| Content Structure | [45] | [72] |
| FAQ & Answer Coverage | [10] | [90] |
| Summary Presence | [30] | [75] |
| Feed & Manifest Readiness | [0] | [85] |
| Entity Density | [55] | [68] |

*Values are dynamic. Red highlight on rows where user is behind. Green where ahead.*

**Loss Aversion Line (below table)**

[Competitor.com] scores [82]/100. You score [34]/100. They have [47] structured FAQ blocks, [12] extractable summaries, and a complete knowledge feed. You have [0].

---

### Notes: Competitor Gap

**Visual direction:**

- Two-column comparison table, clean layout
- Row-level color coding: red background tint where user loses, green where user wins
- Loss aversion line: larger text (18–20px), bold, below the table. This is the gut-punch.
- If no competitor URL was entered, this entire section is hidden.

**Implementation requirement (developer — critical):**

Every metric in the loss aversion line must be:
- Directly measurable from the scan data
- Traceable to a visible sub-score or extraction preview item on the same results page
- Impossible to interpret as fabricated

If a metric cannot be verified by the user on-page, do not include it in the loss aversion line. This line is so load-bearing that a single unverifiable claim will destroy trust in the entire scanner.

**Copy rationale:**

- The loss aversion line is the single most important conversion copy in the entire funnel. It must be specific (real numbers from the scan), verifiable (the user can check), and emotionally sharp (no hedging).
- The table itself is supporting evidence. The line below it is the emotional payload.
- Metric names match the sub-score section (3.2) exactly — consistency prevents confusion.

---

## 3.6 — Extraction Preview

**Section Headline**

What AI sees when it visits your page

**Display (structured checklist):**

✅ **Entities detected:** [list of entities found — e.g., "HubSpot, Salesforce, Austin TX, Jane Smith"]
✅ **Headlines extracted:** [H1, H2 hierarchy summary]
✅ **Structured answers found:** [count of FAQ/Q&A blocks detected]
✅ **Extractable summaries:** [count and quality indicator]
✅ **Schema types present:** [list — e.g., "Article, Organization, FAQPage"]

❌ **Missing:** [list of gaps — e.g., "No Speakable markup," "No /llms.txt file," "No Product schema"]

---

### Notes: Extraction Preview

**Visual direction:**

- Card layout with two columns: "Found" (green checkmarks, left) and "Missing" (red Xs, right)
- Found items feel reassuring. Missing items feel like gaps to fill.
- Keep this compact — it's supporting detail for technical users who want to see the raw data behind the score.

**Copy rationale:**

- "What AI sees when it visits your page" frames the preview from the AI's perspective, not the user's. This reinforces the mental model: you need to think about your site the way a machine does.
- The Found/Missing split creates a natural task list — the missing items ARE the fixes the user needs to make.

---

## 3.7 — CTAs (Bottom of Results)

**Primary CTA**

Fix this instantly → Install AnswerEngineWP (Free)

**If WP.org plugin not yet approved:**

Get notified when the plugin launches →

*[Replaces the install CTA with an email capture field: "Enter your email" + "Notify Me" button]*

**Secondary CTAs:**

**Download PDF Report**
Get the full audit as a branded PDF.

**Share Your Score**
*[Reveals shareable URL + social share buttons for Twitter, LinkedIn, copy link]*

**Embed AI Visibility Badge**
*[Reveals HTML snippet the user can copy-paste onto their site]*

**Email This Report**
Get this report sent to your inbox.
*[Email input + "Send Report" button]*

---

### Notes: CTAs

**Visual direction:**

- Primary CTA: large blue button, full-width or near-full-width, prominent at the bottom of results
- Pre-approval CTA: same size/position, different label and email capture UX
- Secondary CTAs: row of smaller buttons or text links below the primary CTA. Icons for each (PDF icon, share icon, badge icon, email icon).
- On mobile: primary CTA should also appear as a sticky bottom bar that persists while scrolling through results.

**Technical direction:**

- The install CTA link should be configurable: WP.org URL when approved, email capture when not. This switch should be a single environment variable or config flag — not a code change.
- PDF download button shows a spinner ("Generating...") until Phase B async PDF generation completes, then transitions to "Download PDF."
- Share button generates a public score URL (format: `answerenginewp.com/score/[hash]`) with OG meta tags so the score card renders on social media.
- Badge embed generates an HTML snippet with the score, tier, and link back to AnswerEngineWP.

**Copy rationale:**

- "Fix this instantly" frames the plugin as the immediate solution to the problem just diagnosed. The word "instantly" creates urgency.
- "Install AnswerEngineWP (Free)" includes "(Free)" to eliminate the price objection at the moment of highest intent.
- The pre-approval fallback ("Get notified when the plugin launches") preserves the conversion opportunity even before WP.org approval. This is critical — the scanner launches first, the plugin follows. The funnel cannot break during the approval gap.
- Secondary CTAs serve different conversion paths: PDF for outreach (agencies email it to clients), Share for virality (social spread), Badge for long-term referral traffic, Email for lead capture.

**Pro upsell note (post-install, not on scanner page):**

The scanner's job is free-install conversion. The Pro upsell should NOT compete for attention on this page. However, the strategy doc's paywall triggers (auto-fix, Elementor grading, crawler analytics) must activate inside the plugin after install. The user journey is: scanner → free install → experience value → hit paywall trigger → Pro upgrade. Plan for this as a separate in-plugin UX deliverable, not a scanner copy item.

---

# PERSISTENT ELEMENTS

## Navigation Bar

**Logo:** AnswerEngineWP (links to landing page)
**Right-side links:** Methodology · Download Plugin

---

## Footer (Scanner Page)

**Links:** Home · Methodology · WordPress.org Plugin · Documentation · Support · Privacy

**Copyright:** © 2026 AnswerEngineWP

---

### Notes: Persistent Elements

- Navigation is intentionally minimal. The scanner is a focused tool — its job is scan completion, not browsing. "How It Works" and "Pricing" are omitted from scanner nav to prevent exits before the scan happens. Those links live on the landing page.
- "Methodology" stays in both nav and footer — skeptical users need to verify the scoring model before trusting results.
- "Download Plugin" stays as a secondary conversion path for users who skip the results CTAs.

---

# PAGE-LEVEL META

| Tag | Value |
|:---|:---|
| `title` | AI Visibility Scanner — Is your website invisible to AI? · AnswerEngineWP |
| `meta description` | Free AI Visibility Score for any website. Enter your URL and see what AI systems can extract from your site in under 10 seconds. |
| `og:title` | Is your website invisible to AI? Scan free. · AnswerEngineWP |
| `og:description` | Enter any URL. Get your AI Visibility Score in under 10 seconds. Free, no login required. |
| `og:image` | 1200×630 card showing scanner input field with "Scan Now" button and sample score gauge |
| `twitter:card` | summary_large_image |

---

# SCORE URL PAGE META (for shareable score links)

When a user shares their score URL (`answerenginewp.com/score/[hash]`), these OG tags render:

| Tag | Value |
|:---|:---|
| `og:title` | [URL] scored [X]/100 on the AI Visibility Score |
| `og:description` | [Tier label]. Scan your own site free at answerenginewp.com/scanner |
| `og:image` | Auto-generated card showing the score gauge with tier color, scanned URL, and "Scan yours →" CTA |

---

### Notes: Score URL Meta

- The OG image for shared score URLs must be auto-generated per scan. Each image shows the specific score and tier for that URL.
- This is what appears when someone pastes their score link into Twitter, LinkedIn, Slack, or iMessage. It needs to be visually compelling and include enough context to make the viewer want to scan their own site.
- "Scan your own site free" in the description creates the viral loop: see someone's score → wonder about your own → click through → scan.

---

# DOCUMENT SUMMARY

| Component | Purpose | Status |
|:---|:---|:---|
| Input State | Hook + URL capture | ✅ Draft 1 |
| Loading State | Build anticipation + signal thoroughness | ✅ Draft 1 |
| Score Hero | Emotional reveal — tier messages drive action | ✅ Draft 1 |
| Sub-Scores | Supporting detail — breakdown by signal | ✅ Draft 1 |
| Top 3 Fixes | Diagnostic → prescription bridge | ✅ Draft 1 |
| Citation Simulation | Emotional centerpiece — screenshot asset | ✅ Draft 1 |
| Competitor Gap | Loss aversion — sharpest conversion copy | ✅ Draft 1 |
| Extraction Preview | Technical detail for power users | ✅ Draft 1 |
| CTAs | Conversion paths (install, PDF, share, badge, email) | ✅ Draft 1 |
| Navigation / Footer | Persistent elements | ✅ Draft 1 |
| Page Meta | SEO + social sharing tags | ✅ Draft 1 |
| Score URL Meta | Viral loop for shared scores | ✅ Draft 1 |

---

## Load-Bearing Copy (Scanner)

Do not change without strategic review:

1. "Is your website invisible to AI?"
2. "Better-structured competitors are more likely to be cited while your content goes unread by the systems your audience increasingly trusts." (0–39 tier)
3. "You're in the room — but you're not being quoted." (40–69 tier)
4. "Your fastest path to AI visibility" (Top 3 Fixes)
5. "Would AI cite you — or your competitor?" (Citation Simulation)
6. "[Competitor.com] scores [82]/100. You score [34]/100." (Loss aversion line)
7. "Fix this instantly → Install AnswerEngineWP (Free)"

---

## Next Deliverable

WordPress.org Plugin Listing Copy (Phase 0 Implementation Guide, Section 8)
