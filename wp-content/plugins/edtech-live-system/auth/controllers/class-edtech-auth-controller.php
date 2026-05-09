<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Auth_Controller {
    private $db;
    private $helpers;
    private $service;

    public function __construct( $db, $helpers, $service ) {
        $this->db      = $db;
        $this->helpers = $helpers;
        $this->service = $service;
    }

    public function render_auth_form( $atts = array() ) {
        $atts      = shortcode_atts( array( 'type' => 'student_login' ), $atts, 'edtech_auth' );
        $auth_type = sanitize_key( $atts['type'] );
        $subjects  = $this->db->get_subjects( 200 );

        $templates = array(
            'ed_admin_login'  => 'ed-admin-login.php',
            'admin_login'     => 'admin-login.php',
            'teacher_login'   => 'teacher-login.php',
            'teacher_register'=> 'teacher-register.php',
            'student_login'   => 'student-login.php',
            'student_register'=> 'student-register.php',
            'forgot_password' => 'forgot-password.php',
            'reset_password'  => 'reset-password.php',
        );

        if ( ! isset( $templates[ $auth_type ] ) ) {
            $auth_type = 'student_login';
        }

        return $this->render_template(
            $templates[ $auth_type ],
            array(
                'auth_type' => $auth_type,
                'subjects'  => $subjects,
            )
        );
    }

    public function ajax_login() {
        $this->verify_nonce();
        $this->send_service_response( $this->service->login( wp_unslash( $_POST ) ) );
    }

    public function ajax_register() {
        $this->verify_nonce();
        $this->send_service_response( $this->service->register( wp_unslash( $_POST ) ) );
    }

    public function ajax_forgot_password() {
        $this->verify_nonce();
        $this->send_service_response( $this->service->forgot_password( wp_unslash( $_POST ) ) );
    }

    public function ajax_reset_password() {
        $this->verify_nonce();
        $this->send_service_response( $this->service->reset_password( wp_unslash( $_POST ) ) );
    }

    public function ajax_logout() {
        $this->verify_nonce();
        $this->send_service_response( $this->service->logout() );
    }

    public function ajax_update_profile() {
        $this->verify_nonce();
        $this->send_service_response( $this->service->update_profile( wp_unslash( $_POST ) ) );
    }

    public function get_role_based_redirect( $user = null ) {
        return $this->service->get_role_based_redirect( $user );
    }

    public function get_login_url_for_user( $user ) {
        return $this->service->get_login_url_for_user( $user );
    }

    private function render_template( $template, $vars = array() ) {
        $path = EDTECH_PLUGIN_DIR . 'auth/templates/' . $template;

        if ( ! file_exists( $path ) ) {
            return '<div class="edtech-alert alert alert-danger">' . esc_html__( 'Authentication template missing.', 'edtech-live-system' ) . '</div>';
        }

        ob_start();
        extract( $vars, EXTR_SKIP );
        include $path;
        return ob_get_clean();
    }

    private function verify_nonce() {
        check_ajax_referer( 'edtech_live_nonce', 'nonce' );
    }

    private function send_service_response( $result ) {
        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                    'code'    => $result->get_error_code(),
                )
            );
        }

        wp_send_json_success( $result );
    }
}
