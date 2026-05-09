<?php
get_header();
?>
<section class="py-5 mt-5">
    <div class="container">
        <div class="glass-card p-5">
            <?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
                <h1 class="section-title"><?php the_title(); ?></h1>
                <div class="text-muted mt-4"><?php the_content(); ?></div>
            <?php endwhile; endif; ?>
        </div>
    </div>
</section>
<?php get_footer();