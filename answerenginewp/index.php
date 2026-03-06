<?php
/**
 * Main index template (fallback).
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <?php if ( have_posts() ) : ?>
            <?php while ( have_posts() ) : the_post(); ?>
                <h1><?php the_title(); ?></h1>
                <?php the_content(); ?>
            <?php endwhile; ?>
        <?php else : ?>
            <h1>Nothing Found</h1>
            <p>The page you're looking for doesn't exist.</p>
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="btn btn--primary">Back to Home &rarr;</a>
        <?php endif; ?>
    </div>
</div>
</main>

<?php get_footer(); ?>
