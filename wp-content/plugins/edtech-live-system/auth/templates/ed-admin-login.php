<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h2 class="section-title mb-4"><?php esc_html_e( 'WordPress Admin Login', 'edtech-live-system' ); ?></h2>
<form id="edtech-login-form" class="row g-3 edtech-auth-form" method="post">
    <div class="col-12">
        <label class="form-label" for="edtech-wp-admin-email"><?php esc_html_e( 'Username or Email', 'edtech-live-system' ); ?></label>
        <input id="edtech-wp-admin-email" type="text" name="email" class="form-control form-control-dark bg-transparent border border-light" autocomplete="username" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="edtech-wp-admin-password"><?php esc_html_e( 'Password', 'edtech-live-system' ); ?></label>
        <input id="edtech-wp-admin-password" type="password" name="password" class="form-control form-control-dark bg-transparent border border-light" autocomplete="current-password" required>
    </div>
    <div class="col-12 d-flex justify-content-between align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="edtech-wp-admin-remember" value="1">
            <label class="form-check-label" for="edtech-wp-admin-remember"><?php esc_html_e( 'Remember me', 'edtech-live-system' ); ?></label>
        </div>
        <a class="text-info" href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'edtech-live-system' ); ?></a>
    </div>
    <div class="col-12">
        <?php wp_nonce_field( 'edtech_live_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="edtech_login">
        <input type="hidden" name="auth_type" value="ed_admin_login">
        <button type="submit" class="btn btn-brand w-100"><?php esc_html_e( 'Login to wp-admin', 'edtech-live-system' ); ?></button>
    </div>
</form>
<p class="text-muted mt-3 mb-0"><a class="text-info" href="<?php echo esc_url( home_url( '/admin-login' ) ); ?>"><?php esc_html_e( 'Frontend admin login', 'edtech-live-system' ); ?></a></p>
