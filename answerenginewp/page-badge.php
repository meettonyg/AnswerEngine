<?php
/**
 * Template Name: Badge
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<div class="page-content">
    <div class="container">
        <div class="section-label">AI Visibility Badge</div>
        <h1>Show Your AI Visibility Score</h1>
        <p>Sites scoring 70 or above on the AI Visibility Score can embed a badge to showcase their AI-readiness to visitors.</p>

        <h2>How to Get Your Badge</h2>
        <ol>
            <li><a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>">Scan your site</a> with the AI Visibility Scanner</li>
            <li>If your score is 70+, you'll see a "Copy Badge Snippet" button in the results</li>
            <li>Paste the HTML snippet into your site's footer, sidebar, or about page</li>
        </ol>

        <h2>Badge Preview</h2>
        <div style="background: var(--gray-50); border-radius: 12px; padding: 32px; text-align: center; margin: 24px 0;">
            <svg xmlns="http://www.w3.org/2000/svg" width="160" height="50" viewBox="0 0 160 50">
                <rect width="160" height="50" rx="8" fill="#0F172A"/>
                <rect x="0" y="0" width="4" height="50" rx="2" fill="#3B82F6"/>
                <text x="24" y="22" font-family="Georgia, serif" font-size="18" font-weight="bold" fill="#3B82F6">78</text>
                <text x="48" y="22" font-family="Arial, sans-serif" font-size="10" fill="#94A3B8">/100</text>
                <text x="24" y="36" font-family="Arial, sans-serif" font-size="9" fill="#94A3B8">AI Visibility Score</text>
                <text x="24" y="46" font-family="Arial, sans-serif" font-size="7" fill="#64748B">answerenginewp.com</text>
            </svg>
            <p class="text-small" style="margin-top: 16px;">Example badge for a score of 78/100</p>
        </div>

        <h2>Embed Code Format</h2>
        <p>The badge embed code looks like this:</p>
        <pre style="background: var(--gray-50); border-radius: 8px; padding: 20px; overflow-x: auto; font-family: 'JetBrains Mono', monospace; font-size: 13px; line-height: 1.6;"><code>&lt;a href="https://answerenginewp.com/score/{hash}"
   title="AI Visibility Score: {score}/100 — {tierLabel}"
   style="display:inline-block;text-decoration:none"&gt;
  &lt;img src="https://answerenginewp.com/wp-json/aewp/v1/badge/{hash}.svg"
       alt="AI Visibility Score: {score}/100"
       width="160" height="50"&gt;
&lt;/a&gt;</code></pre>

        <h2>Badge Guidelines</h2>
        <ul>
            <li>Badges are available for scores of 70 and above</li>
            <li>The badge links to your public score page, which shows your full results</li>
            <li>Badge scores update when you re-scan your site (new hash is generated)</li>
            <li>Do not modify the badge SVG or misrepresent your score</li>
        </ul>

        <p style="margin-top: 32px;"><a href="<?php echo esc_url( home_url( '/scanner/' ) ); ?>" class="btn btn--primary">Scan Your Site to Get a Badge &rarr;</a></p>
    </div>
</div>

<?php get_footer(); ?>
