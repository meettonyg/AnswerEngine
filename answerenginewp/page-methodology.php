<?php
/**
 * Template Name: Methodology
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<div class="page-content">
    <div class="container">
        <div class="section-label">Methodology</div>
        <h1>How the AI Visibility Score Works</h1>
        <p>The AI Visibility Score is a 0&ndash;100 metric that measures how extractable and citable your website content is by AI systems like ChatGPT, Perplexity, and Google AI Overviews.</p>

        <h2>What We Measure</h2>
        <p>The scanner performs real-time, on-page HTML analysis of any URL. It does not use third-party APIs or AI ranking models. Every score is based entirely on the structural signals present in your page markup.</p>

        <h2>The 6 Sub-Scores</h2>

        <h3>1. Schema Completeness (20%)</h3>
        <p>How many schema.org types are present and properly structured? We check for JSON-LD, Microdata, and RDFa markup, including specific high-value types like Article, FAQPage, Organization, and Speakable. More diverse and complete schema markup means AI systems can better understand and categorize your content.</p>

        <h3>2. Content Structure (15%)</h3>
        <p>Does your heading hierarchy and HTML structure support AI extraction? We analyze your H1-H6 heading hierarchy for proper nesting (no skipping levels), check for semantic HTML elements like &lt;article&gt;, &lt;main&gt;, and &lt;section&gt;, and evaluate paragraph structure. Clean structure helps AI systems parse and segment your content accurately.</p>

        <h3>3. FAQ &amp; Answer Coverage (20%)</h3>
        <p>Are structured question-answer pairs available for AI to cite? We look for FAQPage and QAPage schema, question-patterned headings, &lt;details&gt;/&lt;summary&gt; accordion elements, and definition lists. These structured Q&amp;A patterns are among the most commonly cited content formats in AI answers.</p>

        <h3>4. Summary Presence (20%)</h3>
        <p>Can AI extract concise definitions and summaries from your pages? We check for meta descriptions, opening paragraph quality and length, og:description tags, JSON-LD descriptions, and Speakable markup. AI systems need clear, extractable summary text to generate accurate citations.</p>

        <h3>5. Feed &amp; Manifest Readiness (10%)</h3>
        <p>Do /llms.txt and /llms-full.json exist and validate? We check for the presence of machine-readable manifests at your domain root, including /llms.txt (a plain-text manifest for AI crawlers), /llms-full.json (a structured JSON feed for RAG systems), RSS feeds, and XML sitemaps.</p>

        <h3>6. Entity Density (15%)</h3>
        <p>How many named entities are machine-identifiable? We perform basic named entity recognition to detect proper nouns, organization names, and place names. We also check for entity declarations in structured data. Higher entity density helps AI systems connect your content to knowledge graph entries.</p>

        <h2>How Scores Are Calculated</h2>
        <p>Each sub-score is calculated independently on a 0&ndash;100 scale. The overall AI Visibility Score is a weighted average of all six sub-scores, with weights reflecting each signal's relative importance for AI citation likelihood.</p>

        <h2>Score Tiers</h2>
        <p><strong>0&ndash;39: Invisible to AI</strong> &mdash; ChatGPT cannot reliably extract or cite your site. Better-structured competitors are more likely to be cited.</p>
        <p><strong>40&ndash;69: AI Readable</strong> &mdash; AI systems can find your content, but they're choosing better-structured competitors to cite. You're in the room &mdash; but you're not being quoted.</p>
        <p><strong>70&ndash;89: AI Extractable</strong> &mdash; AI systems can extract and structure your content effectively. A few structural improvements separate you from authority status.</p>
        <p><strong>90&ndash;100: AI Authority</strong> &mdash; Your site is fully optimized for AI extraction and citation. AI systems treat your content as a trusted, authoritative source.</p>

        <h2>What We Don't Measure</h2>
        <ul>
            <li>Actual AI system behavior or responses (no live AI queries)</li>
            <li>Search ranking positions or backlink profiles</li>
            <li>Content quality, accuracy, or relevance</li>
            <li>Domain authority or traffic metrics</li>
        </ul>
        <p>The AI Visibility Score measures structural readiness for AI extraction &mdash; the technical foundation that makes citation possible. It does not predict or guarantee any specific AI system's behavior.</p>

        <h2>Data Sources</h2>
        <p>The scanner uses on-page HTML analysis only. We fetch the target URL server-side, parse the DOM, and evaluate structural signals. No third-party APIs, no cookies, no tracking pixels. Each scan is stateless and independent.</p>
    </div>
</div>

<?php get_footer(); ?>
