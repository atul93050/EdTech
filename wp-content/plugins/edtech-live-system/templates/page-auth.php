<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
$auth_type = get_query_var( 'edtech_auth', false );
if ( ! $auth_type ) {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH ) : '';
    $path = trim( $path, '/' );
    $home_path = trim( wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
    if ( $home_path && strpos( $path, $home_path ) === 0 ) {
        $path = ltrim( substr( $path, strlen( $home_path ) ), '/' );
    }

    $auth_routes = array(
        'student-login' => 'student_login',
        'teacher-login' => 'teacher_login',
        'student-register' => 'student_register',
        'teacher-register' => 'teacher_register',
        'ed-admin-login' => 'ed_admin_login',
        'admin-login' => 'admin_login',
        'forgot-password' => 'forgot_password',
        'reset-password' => 'reset_password',
    );

    if ( isset( $auth_routes[ $path ] ) ) {
        $auth_type = $auth_routes[ $path ];
    } else {
        $auth_type = 'student_login';
    }
}
?>
<section class="py-5 mt-5">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <div class="glass-card p-5">
                    <?php echo do_shortcode( '[edtech_auth type="' . esc_attr( $auth_type ) . '"]' ); ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <h2 class="section-title">Secure frontend access</h2>
                <p class="section-description">Beautiful split-form authentication with student and teacher workflows. No wp-admin access for role-based users.</p>
                <img src="https://via.placeholder.com/620x420.png?text=Auth+Illustration" alt="Auth illustration" class="img-fluid mt-4 rounded-4 shadow-lg">
            </div>
        </div>
    </div>
</section>
<?php get_footer();
