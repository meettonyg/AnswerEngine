<?php
/**
 * Blog Archive Template
 *
 * @package AnswerEngineWP
 */

get_header();
?>

<main>
<div class="page-content">
    <div class="container">
        <div class="section-label">Blog</div>
        <h1>AI Visibility Insights</h1>
        <p class="blog-archive__intro">Guides, strategies, and updates on making your WordPress site visible to AI systems.</p>

        <?php if ( have_posts() ) : ?>
        <div class="blog-archive__grid">
            <?php while ( have_posts() ) : the_post(); ?>
            <article class="blog-card">
                <?php if ( has_post_thumbnail() ) : ?>
                <a href="<?php the_permalink(); ?>" class="blog-card__image">
                    <?php the_post_thumbnail( 'medium_large' ); ?>
                </a>
                <?php endif; ?>
                <div class="blog-card__content">
                    <time class="blog-card__date" datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                    <h2 class="blog-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                    <p class="blog-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
                    <a href="<?php the_permalink(); ?>" class="blog-card__link">Read more &rarr;</a>
                </div>
            </article>
            <?php endwhile; ?>
        </div>

        <div class="blog-archive__pagination">
            <?php the_posts_pagination( array(
                'mid_size'  => 2,
                'prev_text' => '&larr; Previous',
                'next_text' => 'Next &rarr;',
            ) ); ?>
        </div>

        <?php else : ?>
        <p class="blog-archive__empty">No posts yet. Check back soon for AI visibility guides and updates.</p>
        <?php endif; ?>
    </div>
</div>
</main>

<?php get_footer(); ?>
