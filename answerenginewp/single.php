<?php
/**
 * Single Blog Post Template
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<article class="page-content blog-single">
    <div class="container">
        <div class="blog-single__header">
            <a href="<?php echo esc_url( home_url( '/blog/' ) ); ?>" class="blog-single__back">&larr; Back to Blog</a>
            <time class="blog-single__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
            <h1 class="blog-single__title"><?php the_title(); ?></h1>
        </div>

        <?php if ( has_post_thumbnail() ) : ?>
        <div class="blog-single__featured">
            <?php the_post_thumbnail( 'large' ); ?>
        </div>
        <?php endif; ?>

        <div class="blog-single__body">
            <?php the_content(); ?>
        </div>

        <div class="blog-single__footer">
            <div class="blog-single__cta">
                <h3>Improve your AI visibility</h3>
                <p>Install AnswerEngineWP to automatically add AI-visible structure to your WordPress site.</p>
                <a href="https://wordpress.org/plugins/answerenginewp/" class="btn btn--primary" target="_blank" rel="noopener">Install Free Plugin &rarr;</a>
            </div>
        </div>
    </div>
</article>
</main>

<?php get_footer(); ?>
