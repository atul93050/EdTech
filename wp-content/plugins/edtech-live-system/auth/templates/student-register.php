<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<h2 class="section-title mb-4"><?php esc_html_e( 'Student Register', 'edtech-live-system' ); ?></h2>
<form id="edtech-student-register-form" class="row g-3 edtech-auth-form" method="post">
    <div class="col-md-6">
        <label class="form-label" for="edtech-student-name"><?php esc_html_e( 'Full Name', 'edtech-live-system' ); ?></label>
        <input id="edtech-student-name" type="text" name="full_name" class="form-control form-control-dark bg-transparent border border-light" autocomplete="name" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-student-register-email"><?php esc_html_e( 'Email', 'edtech-live-system' ); ?></label>
        <input id="edtech-student-register-email" type="email" name="email" class="form-control form-control-dark bg-transparent border border-light" autocomplete="email" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-student-phone"><?php esc_html_e( 'Phone', 'edtech-live-system' ); ?></label>
        <input id="edtech-student-phone" type="text" name="phone" class="form-control form-control-dark bg-transparent border border-light" autocomplete="tel">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-student-grade"><?php esc_html_e( 'Class / Grade', 'edtech-live-system' ); ?></label>
        <input id="edtech-student-grade" type="text" name="grade" class="form-control form-control-dark bg-transparent border border-light">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-student-city"><?php esc_html_e( 'City', 'edtech-live-system' ); ?></label>
        <input id="edtech-student-city" type="text" name="city" class="form-control form-control-dark bg-transparent border border-light" autocomplete="address-level2">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-student-register-password"><?php esc_html_e( 'Password', 'edtech-live-system' ); ?></label>
        <input id="edtech-student-register-password" type="password" name="password" class="form-control form-control-dark bg-transparent border border-light" autocomplete="new-password" required>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-parent-name"><?php esc_html_e( 'Parent Name', 'edtech-live-system' ); ?></label>
        <input id="edtech-parent-name" type="text" name="parent_name" class="form-control form-control-dark bg-transparent border border-light">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="edtech-parent-phone"><?php esc_html_e( 'Parent Phone', 'edtech-live-system' ); ?></label>
        <input id="edtech-parent-phone" type="text" name="parent_phone" class="form-control form-control-dark bg-transparent border border-light">
    </div>
    <?php if ( ! empty( $subjects ) ) : ?>
        <div class="col-12">
            <label class="form-label" for="edtech-student-subjects"><?php esc_html_e( 'Subjects', 'edtech-live-system' ); ?></label>
            <select id="edtech-student-subjects" name="subject_ids[]" class="form-select form-control-dark bg-transparent border border-light" multiple>
                <?php foreach ( $subjects as $subject ) : ?>
                    <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>
    <div class="col-12">
        <label class="form-label" for="edtech-student-bio"><?php esc_html_e( 'Bio', 'edtech-live-system' ); ?></label>
        <textarea id="edtech-student-bio" name="bio" rows="3" class="form-control form-control-dark bg-transparent border border-light"></textarea>
    </div>
    <div class="col-12">
        <?php wp_nonce_field( 'edtech_live_nonce', 'nonce' ); ?>
        <input type="hidden" name="action" value="edtech_register">
        <input type="hidden" name="role" value="edtech_student">
        <button type="submit" class="btn btn-brand w-100"><?php esc_html_e( 'Register', 'edtech-live-system' ); ?></button>
    </div>
</form>
<p class="text-muted mt-3 mb-0"><?php esc_html_e( 'Already registered?', 'edtech-live-system' ); ?> <a class="text-info" href="<?php echo esc_url( home_url( '/student-login' ) ); ?>"><?php esc_html_e( 'Login', 'edtech-live-system' ); ?></a></p>
