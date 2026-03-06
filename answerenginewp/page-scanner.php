<?php
/**
 * Template Name: Scanner
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<section class="scanner" id="scanner">
    <div class="container">
        <?php get_template_part( 'template-parts/scanner/input-state' ); ?>
        <?php get_template_part( 'template-parts/scanner/loading-state' ); ?>
        <?php get_template_part( 'template-parts/scanner/results-state' ); ?>
    </div>
</section>
</main>

<?php get_footer(); ?>
