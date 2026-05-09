<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h2 class="section-title mb-4"><?php esc_html_e( 'Forgot Password', 'edtech-live-system' ); ?></h2>
<form id="edtech-forgot-password-form" class="row g-3 edtech-auth-form" method="post">
    <div class="col-12">
        <label class="form-label" for="edtech-forgot-email"><?php esc_html_e( 'Email', 'edtech-live-system' ); ?></label>
        <input id="edtech-forgot-email" type="email" name="email" class="form-control form-control-dark bg-transparent border border-light" autocomplete="email" required>
    </div>
    <div class="col-12">
        <?php wp_nonce_field( 'edtech_live_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="edtech_forgot_password">
        <button type="submit" class="btn btn-brand w-100"><?php esc_html_e( 'Send Reset Link', 'edtech-live-system' ); ?></button>
    </div>
</form>
<p class="text-muted mt-3 mb-0"><a class="text-info" href="<?php echo esc_url( home_url( '/student-login' ) ); ?>"><?php esc_html_e( 'Back to login', 'edtech-live-system' ); ?></a></p>
