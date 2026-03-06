<?php
/**
 * Template Name: Privacy Policy
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <div class="section-label">Privacy</div>
        <h1>Privacy Policy</h1>
        <p><em>Last updated: March 2026</em></p>
        <p>AnswerEngineWP is committed to minimal data collection. Here&rsquo;s exactly what we collect and why.</p>

        <h2>What the Scanner Collects</h2>
        <p>When you use the public AI Scanner at answerenginewp.com/scanner:</p>
        <ul>
            <li>The URL you submit is fetched and analyzed server-side. We store the URL, the scan results, and a hash of your IP address (for rate limiting only). We do not store your full IP address.</li>
            <li>If you provide a competitor URL, it is processed and stored the same way.</li>
            <li>Scan results are stored to enable shareable score URLs and PDF report downloads. Results may be deleted after 90 days.</li>
        </ul>

        <h2>What the Plugin Collects</h2>
        <p>The AnswerEngineWP WordPress plugin operates entirely on your server. It does not send data to our servers unless you explicitly use the public scanner or a feature that requires an API call (such as AI Summary Generation in the Pro tier). When API calls are made, only the page content necessary for analysis is transmitted.</p>

        <h2>What We Don&rsquo;t Collect</h2>
        <ul>
            <li>No cookies on the marketing site (we use Plausible Analytics, which is cookieless)</li>
            <li>No tracking pixels or third-party ad scripts</li>
            <li>No personal information, email addresses, or account data (unless you voluntarily provide an email for report delivery)</li>
            <li>No browsing history or cross-site tracking</li>
        </ul>

        <h2>Third-Party Services</h2>
        <ul>
            <li><strong>Plausible Analytics</strong> &mdash; privacy-respecting, cookieless web analytics</li>
            <li><strong>Google Fonts</strong> &mdash; loaded from Google&rsquo;s CDN for typography</li>
        </ul>

        <h2>Data Retention</h2>
        <ul>
            <li>Scanner results: 90 days</li>
            <li>Rate-limiting IP hashes: 1 hour</li>
            <li>Analytics data: managed by Plausible&rsquo;s retention policy</li>
        </ul>

        <h2>Contact</h2>
        <p>Questions about this policy: <a href="mailto:hello@answerenginewp.com">hello@answerenginewp.com</a></p>
    </div>
</div>
</main>

<?php get_footer(); ?>
