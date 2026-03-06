<?php
/**
 * Template Name: Support
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<div class="page-content">
    <div class="container">
        <div class="section-label">Support</div>
        <h1>Get Help with AnswerEngineWP</h1>
        <p>We're here to help you get the most out of AnswerEngineWP. Choose the support channel that works best for you.</p>

        <h2>WordPress.org Support Forum</h2>
        <p>For plugin-related questions, bug reports, and feature requests, visit our support forum on WordPress.org.</p>
        <p><a href="https://wordpress.org/support/plugin/answerenginewp/" class="btn btn--outline" target="_blank" rel="noopener">Visit Support Forum &rarr;</a></p>

        <h2>Scanner Issues</h2>
        <p>If you're experiencing issues with the AI Visibility Scanner on this site:</p>
        <ul>
            <li>Ensure the URL you're scanning is publicly accessible</li>
            <li>Check that the site isn't blocking automated requests</li>
            <li>Try again in a few minutes if you've hit the rate limit (10 scans per hour)</li>
            <li>Some sites with very heavy JavaScript rendering may return incomplete results</li>
        </ul>

        <h2>Pro &amp; Agency Support</h2>
        <p>Pro and Agency license holders receive priority support. Include your license key when contacting us for faster resolution.</p>

        <h2>Common Questions</h2>
        <h3>Does AnswerEngineWP conflict with Yoast or Rank Math?</h3>
        <p>No. AnswerEngineWP is built specifically to complement existing SEO plugins. It adds AI-specific structure (Speakable markup, Answer Blocks, knowledge feeds) without duplicating schema your SEO plugin already manages.</p>

        <h3>Does the scanner work on non-WordPress sites?</h3>
        <p>Yes. The AI Visibility Scanner works on any publicly accessible URL, regardless of the underlying platform.</p>

        <h3>How often should I re-scan my site?</h3>
        <p>We recommend scanning after making structural changes to your site. The scanner caches results for 24 hours for the same URL.</p>
    </div>
</div>

<?php get_footer(); ?>
