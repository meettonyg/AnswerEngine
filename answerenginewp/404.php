<?php
/**
 * 404 Page Template
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<div class="page-404">
    <div class="container">
        <div class="page-404__code">404</div>
        <p class="page-404__message">This page doesn't exist. Maybe it's invisible to us, too.</p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">Back to Home &rarr;</a>
    </div>
</div>

<?php get_footer(); ?>
