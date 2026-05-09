<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>
<section class="py-5 mt-5">
    <div class="container-fluid">
        <div class="row gx-4">
            <div class="col-12">
                <?php echo do_shortcode( '[edtech_dashboard]' ); ?>
            </div>
        </div>
    </div>
</section>
<?php get_footer();
