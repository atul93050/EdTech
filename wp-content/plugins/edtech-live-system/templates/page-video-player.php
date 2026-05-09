<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<section class="py-5 mt-5">
    <div class="container">
        <?php echo do_shortcode( '[edtech_video_player]' ); ?>
    </div>
</section>
<?php get_footer();
