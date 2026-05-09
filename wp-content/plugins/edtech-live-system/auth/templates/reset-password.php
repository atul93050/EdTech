<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
$login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : '';
?>
<h2 class="section-title mb-4"><?php esc_html_e( 'Reset Password', 'edtech-live-system' ); ?></h2>
<form id="edtech-reset-password-form" class="row g-3 edtech-auth-form" method="post">
    <div class="col-12">
        <label class="form-label" for="edtech-reset-password"><?php esc_html_e( 'New Password', 'edtech-live-system' ); ?></label>
        <input id="edtech-reset-password" type="password" name="password" class="form-control form-control-dark bg-transparent border border-light" autocomplete="new-password" required>
    </div>
    <div class="col-12">
        <label class="form-label" for="edtech-reset-confirm-password"><?php esc_html_e( 'Confirm Password', 'edtech-live-system' ); ?></label>
        <input id="edtech-reset-confirm-password" type="password" name="confirm_password" class="form-control form-control-dark bg-transparent border border-light" autocomplete="new-password" required>
    </div>
    <div class="col-12">
        <?php wp_nonce_field( 'edtech_live_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="edtech_reset_password">
        <input type="hidden" name="key" value="<?php echo esc_attr( $key ); ?>">
        <input type="hidden" name="login" value="<?php echo esc_attr( $login ); ?>">
        <button type="submit" class="btn btn-brand w-100"><?php esc_html_e( 'Reset Password', 'edtech-live-system' ); ?></button>
    </div>
</form>
