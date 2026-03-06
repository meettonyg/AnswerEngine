<?php
/**
 * Template Name: Front Page
 *
 * @package AnswerEngineWP
 */

get_header();
?>
<main>
<?php
get_template_part( 'template-parts/home/hero' );
get_template_part( 'template-parts/home/problem' );
get_template_part( 'template-parts/home/how-it-works' );
get_template_part( 'template-parts/home/citation-simulation' );
get_template_part( 'template-parts/home/features' );
get_template_part( 'template-parts/home/pricing' );
get_template_part( 'template-parts/home/social-proof' );
get_template_part( 'template-parts/home/footer-cta' );
?>
</main>
<?php
get_footer();
