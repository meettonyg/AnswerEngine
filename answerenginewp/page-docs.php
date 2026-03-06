<?php
/**
 * Template Name: Documentation
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<div class="page-content">
    <div class="container">
        <div class="section-label">Documentation</div>
        <h1>AnswerEngineWP Documentation</h1>
        <p>Comprehensive documentation for the AnswerEngineWP plugin is available on our WordPress.org plugin page and will be expanded here as the plugin matures.</p>

        <h2>Getting Started</h2>
        <p>Install the free AnswerEngineWP plugin from the WordPress plugin directory to begin optimizing your site for AI visibility.</p>
        <p><a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener">Download from WordPress.org &rarr;</a></p>

        <h2>Plugin Features</h2>
        <ul>
            <li><strong>AI Visibility Score</strong> &mdash; Get a 0&ndash;100 score measuring how extractable and citable your content is.</li>
            <li><strong>AI Extraction Preview</strong> &mdash; See exactly what AI systems can extract from your pages.</li>
            <li><strong>Speakable Answer Blocks</strong> &mdash; Create structured content blocks optimized for AI citation.</li>
            <li><strong>/llms.txt Generator</strong> &mdash; Generate machine-readable manifests for AI crawlers.</li>
        </ul>

        <h2>Compatibility</h2>
        <p>AnswerEngineWP is designed to work alongside existing SEO plugins without conflicts:</p>
        <ul>
            <li>Yoast SEO</li>
            <li>Rank Math</li>
            <li>All in One SEO</li>
        </ul>

        <h2>Need Help?</h2>
        <p>Visit our <a href="<?php echo esc_url( home_url( '/support/' ) ); ?>">support page</a> for assistance or post on the <a href="https://wordpress.org/support/plugin/answerenginewp/" target="_blank" rel="noopener">WordPress.org support forum</a>.</p>
    </div>
</div>

<?php get_footer(); ?>
