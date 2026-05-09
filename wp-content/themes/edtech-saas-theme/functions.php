<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'after_setup_theme', function() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption' ) );
    register_nav_menus( array(
        'header_menu' => __( 'Header Menu', 'edtech-saas-theme' ),
        'footer_menu' => __( 'Footer Menu', 'edtech-saas-theme' ),
    ) );
} );

add_action( 'wp_enqueue_scripts', function() {
    wp_enqueue_style( 'edtech-saas-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Manrope:wght@600;700;800&display=swap', array(), null );
    wp_enqueue_style( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css', array(), '5.3.2' );
    wp_enqueue_style( 'edtech-saas-theme-style', get_stylesheet_uri(), array( 'bootstrap', 'edtech-saas-fonts' ), '1.2.0' );
    wp_enqueue_style( 'edtech-saas-icons', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css', array(), '6.5.0' );
    wp_enqueue_script( 'bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js', array( 'jquery' ), '5.3.2', true );
    wp_enqueue_script( 'edtech-theme-scripts', get_template_directory_uri() . '/assets/js/theme.js', array( 'jquery' ), '1.0.0', true );
    wp_localize_script( 'edtech-theme-scripts', 'EDTECH_THEME', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
    ) );
} );

add_filter( 'body_class', function( $classes ) {
    $classes[] = 'edtech-saas-theme';
    return $classes;
} );

function edtech_render_navigation() {
    ?>
    <nav class="navbar navbar-expand-xl navbar-light navbar-glass fixed-top shadow-sm eds-navbar-wrap">
        <div class="container eds-navbar-container">
            <a class="navbar-brand fw-bold eds-brand-wrap" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <span class="text-primary">EdTech</span><span class="text-secondary">SaaS</span>
            </a>
            <button class="navbar-toggler eds-mobile-toggle" type="button" data-bs-toggle="collapse" data-bs-target="#edtechNav"
                    aria-controls="edtechNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse eds-navbar-collapse" id="edtechNav">
                <?php
                wp_nav_menu( array(
                    'theme_location' => 'header_menu',
                    'container' => false,
                    'menu_class' => 'navbar-nav mx-auto my-2 my-lg-0',
                    'items_wrap' => '%3$s',
                    'depth' => 1,
                    'fallback_cb' => 'edtech_default_menu',
                    'link_before' => '',
                    'link_after' => '',
                ) );
                ?>
              
                <div class="d-flex gap-2 mt-3 mt-xl-0 align-items-center eds-nav-actions">
                    <button id="theme-toggle" class="btn btn-outline-primary btn-sm me-2" title="Toggle theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <?php if ( is_user_logged_in() ) : ?>
                        <?php
                        $user = wp_get_current_user();
                        $dashboard_url = home_url( '/dashboard' );
                        if ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
                            $dashboard_url = home_url( '/teacher-dashboard' );
                        } elseif ( in_array( 'edtech_student', (array) $user->roles, true ) ) {
                            $dashboard_url = home_url( '/student-dashboard' );
                        } elseif ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true ) ) {
                            $dashboard_url = home_url( '/admin-dashboard' );
                        }
                        ?>
                        <a href="<?php echo esc_url( $dashboard_url ); ?>" class="btn btn-brand btn-sm eds-btn-primary">Dashboard</a>
                        <button type="button" class="btn btn-outline-secondary btn-sm edtech-logout-button eds-btn-secondary">Logout</button>
                    <?php else : ?>
                        <a href="<?php echo esc_url( site_url( '/student-login' ) ); ?>" class="btn btn-outline-secondary btn-sm eds-btn-secondary">Sign In</a>
                        <a href="<?php echo esc_url( site_url( '/student-register' ) ); ?>" class="btn btn-brand btn-sm">Get Started</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
    <?php
}

// function edtech_default_menu() {
//     $items = array(
//         array( 'Home', home_url( '/' ) ),
//         array( 'Features', home_url( '/features' ) ),
//         array( 'Pricing', home_url( '/pricing' ) ),
//         array( 'About', home_url( '/about' ) ),
//         array( 'FAQ', home_url( '/faq' ) ),
//         array( 'Contact', home_url( '/contact' ) ),
//     );

//     foreach ( $items as $item ) {
//         printf(
//             '<li class="nav-item"><a class="nav-link px-3" href="%s">%s</a></li>',
//             esc_url( $item[1] ),
//             esc_html( $item[0] )
//         );
//     }
// }
function edtech_default_menu()
{
    $items = array(
        array('Home', home_url('/')),
        array('Features', home_url('/features')),
        array('Pricing', home_url('/pricing')),
        array('About', home_url('/about')),
        array('FAQ', home_url('/faq')),
        array('Contact', home_url('/contact')),
    );

    echo '<ul class="navbar-nav mx-auto my-2 my-lg-0 eds-navbar-menu">';

    foreach ($items as $item) {
        printf(
            '<li class="nav-item">
                <a class="nav-link px-3" href="%s">%s</a>
            </li>',
            esc_url($item[1]),
            esc_html($item[0])
        );
    }

    echo '</ul>';
}
function edtech_render_footer() {
    ?>
    <footer class="footer-glass py-5 mt-5 text-light">
        <div class="container">
            <div class="row gy-4">
                <div class="col-lg-4">
                    <h5>EdTech SaaS</h5>
                    <p class="text-muted">Premium coaching platform UI designed for live classes, attendance, real-time analytics and modern dashboards.</p>
                </div>
                <div class="col-lg-2">
                    <h6>Quick Links</h6>
                    <ul class="list-unstyled text-muted">
                        <li><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Home</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/features' ) ); ?>">Features</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/pricing' ) ); ?>">Pricing</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/faq' ) ); ?>">FAQ</a></li>
                    </ul>
                </div>
                <div class="col-lg-2">
                    <h6>Resources</h6>
                    <ul class="list-unstyled text-muted">
                        <li><a href="<?php echo esc_url( home_url( '/about' ) ); ?>">About</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/privacy-policy' ) ); ?>">Privacy</a></li>
                        <li><a href="<?php echo esc_url( home_url( '/terms-conditions' ) ); ?>">Terms</a></li>
                    </ul>
                </div>
                <div class="col-lg-4">
                    <h6>Join the platform</h6>
                    <p class="text-muted">Subscribe for release updates, live class announcements, and premium content notifications.</p>
                    <form class="row g-2" action="#" method="post">
                        <div class="col-8">
                            <input class="form-control form-control-dark bg-transparent border border-light" type="email" placeholder="Email address" aria-label="Email" />
                        </div>
                        <div class="col-4">
                            <button class="btn btn-brand w-100" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </footer>
    <?php
}
