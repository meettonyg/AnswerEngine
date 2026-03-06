<?php
/**
 * Template Name: Documentation
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <div class="section-label">Documentation</div>
        <h1>Getting Started with AnswerEngineWP</h1>
        <p>AnswerEngineWP works alongside your existing SEO plugin to add AI-specific structure that makes your content extractable and citable by AI systems.</p>

        <h2>Installation</h2>
        <ol>
            <li>Download AnswerEngineWP from the WordPress plugin directory</li>
            <li>Activate the plugin &mdash; it automatically detects Yoast, Rank Math, or All in One SEO and enters Companion Mode</li>
            <li>Run your first scan from the AI Visibility dashboard to see your baseline score</li>
            <li>Follow the recommended fixes to improve your score</li>
        </ol>

        <h2>Core Concepts</h2>

        <h3>AI Visibility Score</h3>
        <p>A 0&ndash;100 measure of how well AI systems can extract and cite your content. Calculated from six structural signals: schema completeness, content structure, FAQ coverage, summary presence, feed readiness, and entity density.</p>

        <h3>Companion Mode</h3>
        <p>AnswerEngineWP only adds AI-specific markup (Speakable, FAQ Answer Blocks, knowledge feeds). It never duplicates the schema your SEO plugin already manages.</p>

        <h3>Answer Blocks</h3>
        <p>Structured content blocks you add in the Gutenberg editor. Each block contains a question, answer summary, and supporting facts &mdash; the format AI systems prefer to cite.</p>

        <h3>Knowledge Feeds</h3>
        <p>Machine-readable files (<code>/llms.txt</code> and <code>/llms-full.json</code>) that AI crawlers use to understand your site&rsquo;s content and structure.</p>

        <p>Full documentation is being expanded. Check back soon or reach out via the <a href="<?php echo esc_url( home_url( '/support/' ) ); ?>">support page</a> if you have questions.</p>
    </div>
</div>
</main>

<?php get_footer(); ?>
