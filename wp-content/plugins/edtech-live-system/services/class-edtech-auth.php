<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Auth {
    private $db;
    private $helpers;
    private $validator;
    private $service;
    private $controller;

    public function __construct( $db, $helpers ) {
        $this->db         = $db;
        $this->helpers    = $helpers;
        $this->validator  = new Edtech_Auth_Validator( $db, $helpers );
        $this->service    = new Edtech_Auth_Service( $db, $helpers, $this->validator );
        $this->controller = new Edtech_Auth_Controller( $db, $helpers, $this->service );
    }

    public function render_auth_form( $atts ) {
        return $this->controller->render_auth_form( $atts );
    }

    public function ajax_register() {
        $this->controller->ajax_register();
    }

    public function ajax_login() {
        $this->controller->ajax_login();
    }

    public function ajax_logout() {
        $this->controller->ajax_logout();
    }

    public function ajax_forgot_password() {
        $this->controller->ajax_forgot_password();
    }

    public function ajax_reset_password() {
        $this->controller->ajax_reset_password();
    }

    public function ajax_update_profile() {
        $this->controller->ajax_update_profile();
    }

    public function get_role_based_redirect( $user = null ) {
        return $this->controller->get_role_based_redirect( $user );
    }

    public function validate_password_strength( $password ) {
        return ! is_wp_error( $this->validator->validate_password( $password ) );
    }

    public function check_user_access( $required_role = '' ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        if ( '' === $required_role ) {
            return true;
        }

        $user = wp_get_current_user();
        return in_array( $required_role, (array) $user->roles, true );
    }
}
