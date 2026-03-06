<?php
/**
 * Template Name: Support
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <div class="section-label">Support</div>
        <h1>Get Help with AnswerEngineWP</h1>
        <p>We&rsquo;re a small team building something new. Here&rsquo;s how to get help.</p>

        <h2>WordPress.org Support Forum</h2>
        <p>For bug reports, feature requests, and general questions, use the official WordPress.org support forum. This is the fastest way to get a response and helps other users who may have the same question.</p>
        <p><a href="https://wordpress.org/support/plugin/answerenginewp/" target="_blank" rel="noopener">&rarr; Visit the Support Forum</a></p>

        <h2>Common Questions</h2>

        <h3>&ldquo;Does this replace Yoast or Rank Math?&rdquo;</h3>
        <p>No. AnswerEngineWP runs alongside your existing SEO plugin in Companion Mode. It adds AI-specific structure without duplicating the schema your SEO plugin already manages.</p>

        <h3>&ldquo;How is the AI Visibility Score calculated?&rdquo;</h3>
        <p>The score is based on six structural signals analyzed directly from your page&rsquo;s HTML: schema completeness, content structure, FAQ coverage, summary presence, feed readiness, and entity density. See the <a href="<?php echo esc_url( home_url( '/methodology/' ) ); ?>">full methodology</a>.</p>

        <h3>&ldquo;My score seems wrong.&rdquo;</h3>
        <p>The score measures structural signals &mdash; how extractable your content is &mdash; not how any specific AI system actually responds. If you believe there&rsquo;s a bug, please report it on the support forum with your URL and a description of the issue.</p>

        <h3>&ldquo;I need help with the Agency tier.&rdquo;</h3>
        <p>For Agency-tier support, reach out directly at <a href="mailto:hello@answerenginewp.com">hello@answerenginewp.com</a>. Include your license key and a description of the issue.</p>
    </div>
</div>
</main>

<?php get_footer(); ?>
