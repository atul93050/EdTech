<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h2 class="section-title mb-4"><?php esc_html_e( 'Teacher Login', 'edtech-live-system' ); ?></h2>
<form id="edtech-login-form" class="row g-3 edtech-auth-form" method="post">
    <div class="col-12">
        <label class="form-label" for="edtech-teacher-email"><?php esc_html_e( 'Username or Email', 'edtech-live-system' ); ?></label>
        <input id="edtech-teacher-email" type="text" name="email" class="form-control form-control-dark bg-transparent border border-light" autocomplete="username" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="edtech-teacher-password"><?php esc_html_e( 'Password', 'edtech-live-system' ); ?></label>
        <input id="edtech-teacher-password" type="password" name="password" class="form-control form-control-dark bg-transparent border border-light" autocomplete="current-password" required>
    </div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="edtech-teacher-remember" value="1">
            <label class="form-check-label" for="edtech-teacher-remember"><?php esc_html_e( 'Remember me', 'edtech-live-system' ); ?></label>
        </div>
        <a class="text-info" href="<?php echo esc_url( home_url( '/forgot-password' ) ); ?>"><?php esc_html_e( 'Forgot password?', 'edtech-live-system' ); ?></a>
    </div>
    <div class="col-12">
        <?php wp_nonce_field( 'edtech_live_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="edtech_login">
        <input type="hidden" name="auth_type" value="teacher_login">
        <button type="submit" class="btn btn-brand w-100"><?php esc_html_e( 'Login', 'edtech-live-system' ); ?></button>
    </div>
</form>
<p class="text-muted mt-3 mb-0"><?php esc_html_e( 'New teacher?', 'edtech-live-system' ); ?> <a class="text-info" href="<?php echo esc_url( home_url( '/teacher-register' ) ); ?>"><?php esc_html_e( 'Register', 'edtech-live-system' ); ?></a></p>
