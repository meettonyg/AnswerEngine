<?php
/**
 * Methodology page — auto-loaded for slug "methodology".
 *
 * @package AIVisibilityScanner
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <div class="section-label">Methodology</div>
        <h1>How the AI Visibility Stack Works</h1>
        <p>The AI Visibility Score is a 0&ndash;100 metric that measures how extractable and citable your website content is by AI systems like ChatGPT, Perplexity, and Google AI Overviews. It is built on the AI Visibility Stack&thinsp;&mdash;&thinsp;a 5-layer model that maps how AI systems decide which sources to cite.</p>

        <!-- ============================================================
             Section 1: The AI Visibility Stack
             ============================================================ -->
        <h2>The AI Visibility Stack</h2>
        <p>AI citation is not random. Every time an AI system generates an answer, it moves through a predictable decision stack. If a lower layer fails, the upper layers never activate&thinsp;&mdash;&thinsp;your content is invisible regardless of its quality.</p>

        <div class="stack-diagram">
            <div class="stack-diagram__layer stack-diagram__layer--authority">
                <div>
                    <span class="stack-diagram__layer-name">Layer 5: AUTHORITY</span><br>
                    <span class="stack-diagram__layer-question">Do external sources validate you?</span>
                </div>
                <span class="stack-diagram__layer-status stack-diagram__layer-status--upgrade">Premium</span>
            </div>
            <div class="stack-diagram__layer stack-diagram__layer--trust">
                <div>
                    <span class="stack-diagram__layer-name">Layer 4: TRUST</span><br>
                    <span class="stack-diagram__layer-question">Does AI trust who is saying it?</span>
                </div>
                <span class="stack-diagram__layer-status stack-diagram__layer-status--upgrade">Premium</span>
            </div>
            <div class="stack-diagram__layer stack-diagram__layer--extractability">
                <div>
                    <span class="stack-diagram__layer-name">Layer 3: EXTRACTABILITY</span><br>
                    <span class="stack-diagram__layer-question">Can AI isolate a usable answer?</span>
                </div>
                <span class="stack-diagram__layer-status stack-diagram__layer-status--measured">Measured</span>
            </div>
            <div class="stack-diagram__layer stack-diagram__layer--understanding">
                <div>
                    <span class="stack-diagram__layer-name">Layer 2: UNDERSTANDING</span><br>
                    <span class="stack-diagram__layer-question">Can AI parse what it finds?</span>
                </div>
                <span class="stack-diagram__layer-status stack-diagram__layer-status--measured">Measured</span>
            </div>
            <div class="stack-diagram__layer stack-diagram__layer--access">
                <div>
                    <span class="stack-diagram__layer-name">Layer 1: ACCESS</span><br>
                    <span class="stack-diagram__layer-question">Can AI reach your content?</span>
                </div>
                <span class="stack-diagram__layer-status stack-diagram__layer-status--measured">Measured</span>
            </div>
            <p class="stack-diagram__dependency-note">
                &uarr; Each layer depends on the ones below it. If a lower layer fails, upper layers are irrelevant.
            </p>
        </div>

        <p>The free scanner measures Layers 1&ndash;3 (Access, Understanding, and Extractability). Layers 4&ndash;5 (Trust and Authority) require off-site signals like author credentials, backlinks, and third-party reviews&thinsp;&mdash;&thinsp;these are available in the premium report.</p>

        <!-- ============================================================
             Section 2: What the Scanner Measures
             ============================================================ -->
        <h2>What the Scanner Measures</h2>
        <p>The scanner performs real-time, on-page HTML analysis of any URL. It evaluates 8 structural signals, each weighted by its relative importance for AI citation likelihood. The overall AI Visibility Score is the weighted average of all 8 sub-scores.</p>

        <table class="methodology-signals-table">
            <thead>
                <tr>
                    <th>Signal</th>
                    <th>Weight</th>
                    <th>Stack Layer</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Crawl Access</td>
                    <td>15%</td>
                    <td>Layer 1: Access</td>
                </tr>
                <tr>
                    <td>Feed &amp; Manifest Readiness</td>
                    <td>8%</td>
                    <td>Layer 1: Access</td>
                </tr>
                <tr>
                    <td>Schema Completeness</td>
                    <td>15%</td>
                    <td>Layer 2: Understanding</td>
                </tr>
                <tr>
                    <td>Entity Density</td>
                    <td>10%</td>
                    <td>Layer 2: Understanding</td>
                </tr>
                <tr>
                    <td>Content Structure</td>
                    <td>12%</td>
                    <td>Layer 3: Extractability</td>
                </tr>
                <tr>
                    <td>FAQ &amp; Answer Coverage</td>
                    <td>12%</td>
                    <td>Layer 3: Extractability</td>
                </tr>
                <tr>
                    <td>Summary Presence</td>
                    <td>12%</td>
                    <td>Layer 3: Extractability</td>
                </tr>
                <tr>
                    <td>Content Richness</td>
                    <td>16%</td>
                    <td>Layer 3: Extractability</td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>100%</strong></td>
                    <td></td>
                </tr>
            </tbody>
        </table>

        <h3>1. Crawl Access (15%) &mdash; Layer 1: Access</h3>
        <p>Can AI crawlers reach and render your content? We check whether robots.txt blocks known AI user-agents (GPTBot, ClaudeBot, PerplexityBot), verify canonical tag presence, measure server response time (TTFB), and detect client-side rendering that would make content invisible to crawlers.</p>

        <h3>2. Feed &amp; Manifest Readiness (8%) &mdash; Layer 1: Access</h3>
        <p>Do machine-readable manifests exist at your domain root? We check for /llms.txt (a plain-text manifest for AI crawlers), /llms-full.json (a structured JSON feed for RAG systems), RSS feeds, and XML sitemaps. These files tell AI crawlers what content is available and how to consume it.</p>

        <h3>3. Schema Completeness (15%) &mdash; Layer 2: Understanding</h3>
        <p>How many schema.org types are present and properly structured? We check for JSON-LD and Microdata markup, including high-value types like Article, FAQPage, Product, Organization, and Person. We also evaluate schema graph depth, Person schema with sameAs links, and Speakable markup for voice-assistant compatibility.</p>

        <h3>4. Entity Density (10%) &mdash; Layer 2: Understanding</h3>
        <p>How many named entities are machine-identifiable? We perform basic named entity recognition to detect proper nouns (ORG, PERSON, PRODUCT, GPE) and check for entity declarations in structured data. Higher entity density helps AI systems connect your content to knowledge graph entries.</p>

        <h3>5. Content Structure (12%) &mdash; Layer 3: Extractability</h3>
        <p>Does your heading hierarchy support AI extraction? We analyze your H1&ndash;H6 heading hierarchy for proper nesting, check for semantic HTML elements like &lt;article&gt;, &lt;main&gt;, and &lt;section&gt;, evaluate list and table usage with graduated scoring, and identify question-patterned headings that prime AI systems for Q&amp;A extraction.</p>

        <h3>6. FAQ &amp; Answer Coverage (12%) &mdash; Layer 3: Extractability</h3>
        <p>Are structured question-answer pairs available for AI to cite? We look for FAQPage and QAPage schema, question-patterned headings, &lt;details&gt;/&lt;summary&gt; accordion elements, and definition lists. These structured Q&amp;A patterns are among the most commonly cited content formats in AI answers.</p>

        <h3>7. Summary Presence (12%) &mdash; Layer 3: Extractability</h3>
        <p>Can AI extract concise definitions and summaries from your pages? We check for meta descriptions, opening paragraph quality and length, og:description tags, JSON-LD descriptions, and definitional sentences. AI systems need clear, extractable summary text to generate accurate citations.</p>

        <h3>8. Content Richness (16%) &mdash; Layer 3: Extractability</h3>
        <p>Does your content include the data points AI systems prefer to cite? We measure the presence of statistics and numerical claims, evaluate citation and source formatting quality, check for front-loaded answers in opening paragraphs, and assess question-heading density. Content with concrete data and proper source attribution is significantly more likely to be cited by AI.</p>

        <!-- ============================================================
             Section 2b: Layer Mapping
             ============================================================ -->
        <h2>How Signals Map to the Stack</h2>
        <div class="methodology-layer-map">Layer 1: ACCESS (23% combined weight)
  &bull; Crawl Access (15%)
    Checks: robots.txt AI-bot rules, canonical tag, TTFB, SSR detection
  &bull; Feed &amp; Manifest Readiness (8%)
    Checks: /llms.txt, /llms-full.json, sitemap.xml, RSS feeds

Layer 2: UNDERSTANDING (25% combined weight)
  &bull; Schema Completeness (15%)
    Checks: JSON-LD, Microdata, @graph traversal, Person/sameAs, Speakable
  &bull; Entity Density (10%)
    Checks: Named entities (ORG, PERSON, PRODUCT, GPE) via NER

Layer 3: EXTRACTABILITY (52% combined weight)
  &bull; Content Structure (12%)
    Checks: H1&rarr;H2&rarr;H3 nesting, semantic HTML, lists/tables, question headings
  &bull; FAQ &amp; Answer Coverage (12%)
    Checks: FAQPage schema, question headings, details/summary, definition lists
  &bull; Summary Presence (12%)
    Checks: Meta descriptions, opening paragraphs, og:description, JSON-LD descriptions
  &bull; Content Richness (16%)
    Checks: Statistics/numbers, citation formatting, front-loaded answers, question density

Layers 4&ndash;5: TRUST &amp; AUTHORITY (Premium Report)
  &bull; Available in the full-site premium audit
    Trust: Author attribution, dateModified, credentials, E-E-A-T signals
    Authority: Backlinks, reviews, media mentions, domain reputation</div>

        <!-- ============================================================
             Section 3: Score Tiers
             ============================================================ -->
        <h2>Score Tiers</h2>

        <div class="methodology-tier methodology-tier--invisible">
            <strong>0&ndash;39: Invisible to AI</strong><br>
            ChatGPT cannot reliably extract or cite your site. Better-structured competitors are more likely to be cited.
        </div>

        <div class="methodology-tier methodology-tier--readable">
            <strong>40&ndash;69: AI Readable</strong><br>
            AI systems can find your content, but they're choosing better-structured competitors to cite. You're in the room&thinsp;&mdash;&thinsp;but you're not being quoted.
        </div>

        <div class="methodology-tier methodology-tier--extractable">
            <strong>70&ndash;89: AI Extractable</strong><br>
            AI systems can extract and structure your content effectively. A few structural improvements separate you from authority status.
        </div>

        <div class="methodology-tier methodology-tier--authority">
            <strong>90&ndash;100: AI Authority</strong><br>
            Your site is fully optimized for AI extraction and citation. AI systems treat your content as a trusted, authoritative source.
        </div>

        <!-- ============================================================
             Section 4: What We Don't Measure
             ============================================================ -->
        <h2>What We Don&rsquo;t Measure (Free Scan)</h2>
        <ul>
            <li>Actual AI system behavior or responses (no live AI queries)</li>
            <li>Search ranking positions or backlink profiles</li>
            <li>Content quality, accuracy, or relevance</li>
            <li>Domain authority or traffic metrics</li>
            <li>Off-site trust signals (Layer 4) &mdash; author credentials, E-E-A-T depth</li>
            <li>Off-site authority signals (Layer 5) &mdash; backlinks, reviews, media mentions</li>
            <li>Platform-specific behaviors &mdash; each AI system has its own ranking model</li>
        </ul>
        <p>The free AI Visibility Score measures structural readiness for AI extraction&thinsp;&mdash;&thinsp;the technical foundation that makes citation possible. Layers 4&ndash;5 are available in the premium full-site audit. <a href="#" class="aewp-waitlist-trigger">Join the waitlist &rarr;</a></p>

        <!-- ============================================================
             Section 5: The Full AEO Factor Taxonomy
             ============================================================ -->
        <h2>The Full AEO Factor Taxonomy</h2>
        <p>The scanner measures a focused subset of the 93 known factors that influence AI citation. The full AEO Master Factor Taxonomy (v1.2) catalogues every signal across all 5 layers of the AI Visibility Stack&thinsp;&mdash;&thinsp;from basic crawlability to third-party authority signals.</p>
        <p>The free scanner measures 27 core factors from Layers 1&ndash;3. The premium report covers all 5 layers for teams building a comprehensive AEO strategy.</p>

        <!-- ============================================================
             Section 6: Data Sources & Transparency
             ============================================================ -->
        <h2>Data Sources &amp; Transparency</h2>
        <ul>
            <li>On-page HTML analysis only &mdash; we fetch the target URL server-side and parse the DOM</li>
            <li>No third-party APIs, cookies, or tracking pixels</li>
            <li>Each scan is stateless and independent</li>
            <li>Scoring methodology based on the AEO Master Factor Taxonomy v1.2</li>
        </ul>
    </div>
</div>
</main>

<?php get_footer(); ?>
