<?php
/**
 * Template Name: AI Visibility Badge
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <div class="section-label">AI Visibility Badge</div>
        <h1>Embed Your AI Visibility Score</h1>
        <p>Scored 70 or above? Display your AI Visibility Badge on your site as a credibility signal.</p>

        <h2>How It Works</h2>
        <ol>
            <li>Scan your site at <a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>">answerenginewp.com/scanner</a></li>
            <li>If you score 70+ (AI Extractable or AI Authority), you&rsquo;ll see the option to embed a badge</li>
            <li>Copy the HTML snippet below and paste it into your site&rsquo;s footer, sidebar, or about page</li>
        </ol>
        <p>The badge displays your score, updates automatically when you rescan, and links back to your public score page.</p>

        <h2>Embed Code</h2>
        <p>Copy and paste this HTML into your site:</p>
<pre><code>&lt;a href="https://answerenginewp.com/report/YOUR-DOMAIN-SLUG"
   title="AI Visibility Score &mdash; Verified by AnswerEngineWP"
   style="display:inline-block;text-decoration:none"&gt;
  &lt;img src="https://answerenginewp.com/wp-json/aewp/v1/badge/YOUR_HASH.svg"
       alt="AI Visibility Score"
       width="160" height="50"&gt;
&lt;/a&gt;</code></pre>
        <p>Replace <code>YOUR-DOMAIN-SLUG</code> with your domain in slug format (e.g., <code>example-com</code>) and <code>YOUR_HASH</code> with the hash from your scan results page URL.</p>
        <p>Your badge links to your public report page at <code>/report/your-domain/</code>, which helps build backlinks and brand visibility.</p>

        <h2>Badge Guidelines</h2>
        <ul>
            <li>Display the badge as-is. Don&rsquo;t modify the SVG or alter the score.</li>
            <li>The badge is for sites that have been scanned and scored. Don&rsquo;t embed it for sites that haven&rsquo;t been analyzed.</li>
            <li>The badge links to your public score page, where visitors can verify your score and scan their own site.</li>
        </ul>
    </div>
</div>
</main>

<?php get_footer(); ?>
