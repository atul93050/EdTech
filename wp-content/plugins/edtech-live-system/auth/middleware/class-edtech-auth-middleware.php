<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Auth_Middleware {
    private $db;
    private $helpers;
    private $routes;

    public function __construct( $db, $helpers, $routes ) {
        $this->db      = $db;
        $this->helpers = $helpers;
        $this->routes  = $routes;
    }

    public function protect_frontend_routes() {
        if ( is_admin() || wp_doing_ajax() ) {
            return;
        }

        $path           = $this->routes->get_current_path();
        $auth_type      = get_query_var( 'edtech_auth' ) ?: $this->routes->get_auth_type_from_path( $path );
        $dashboard_type = get_query_var( 'edtech_dashboard' ) ?: $this->routes->get_dashboard_type_from_path( $path );

        if ( $auth_type ) {
            $this->redirect_if_logged_in( $auth_type );
            return;
        }

        if ( ! $dashboard_type ) {
            return;
        }

        switch ( $dashboard_type ) {
            case 'admin':
                $this->require_admin();
                break;
            case 'teacher':
                $this->require_teacher();
                break;
            case 'student':
                $this->require_student();
                break;
            case 'any':
                $this->require_lms_user();
                break;
        }
    }

    public function restrict_wp_admin() {
        if ( ! is_admin() || wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
            return;
        }

        if ( ! is_user_logged_in() ) {
            return;
        }

        $user = wp_get_current_user();
        if ( in_array( 'administrator', (array) $user->roles, true ) ) {
            return;
        }

        wp_safe_redirect( $this->get_dashboard_url_for_user( $user ) );
        exit;
    }

    public function show_admin_bar_conditionally( $show ) {
        if ( is_admin() ) {
            return $show;
        }

        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();
        return in_array( 'administrator', (array) $user->roles, true );
    }

    public function require_admin() {
        if ( ! is_user_logged_in() ) {
            $this->redirect_to( home_url( '/admin-login' ) );
        }

        $user = wp_get_current_user();
        if ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true ) ) {
            return true;
        }

        $this->redirect_to( $this->get_dashboard_url_for_user( $user ) );
    }

    public function require_teacher() {
        if ( ! is_user_logged_in() ) {
            $this->redirect_to( home_url( '/teacher-login' ) );
        }

        $user = wp_get_current_user();
        if ( $this->helpers->is_teacher() && $this->db->is_profile_approved( $user->ID ) ) {
            return true;
        }

        if ( $this->helpers->is_teacher() ) {
            $this->logout_for_unapproved_account();
            $this->redirect_to( home_url( '/teacher-login' ) );
        }

        $this->redirect_to( $this->get_dashboard_url_for_user( $user ) );
    }

    public function require_student() {
        if ( ! is_user_logged_in() ) {
            $this->redirect_to( home_url( '/student-login' ) );
        }

        $user = wp_get_current_user();
        if ( $this->helpers->is_student() && $this->db->is_profile_approved( $user->ID ) ) {
            return true;
        }

        if ( $this->helpers->is_student() ) {
            $this->logout_for_unapproved_account();
            $this->redirect_to( home_url( '/student-login' ) );
        }

        $this->redirect_to( $this->get_dashboard_url_for_user( $user ) );
    }

    public function require_lms_user() {
        if ( ! is_user_logged_in() ) {
            $this->redirect_to( home_url( '/student-login' ) );
        }

        $user = wp_get_current_user();

        if ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true ) ) {
            return true;
        }

        if ( ( $this->helpers->is_teacher() || $this->helpers->is_student() ) && $this->db->is_profile_approved( $user->ID ) ) {
            return true;
        }

        if ( $this->helpers->is_teacher() || $this->helpers->is_student() ) {
            $this->logout_for_unapproved_account();
        }

        $this->redirect_to( home_url( '/student-login' ) );
    }

    public function redirect_if_logged_in( $auth_type = '' ) {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $user = wp_get_current_user();

        if ( ( in_array( 'edtech_teacher', (array) $user->roles, true ) || in_array( 'edtech_student', (array) $user->roles, true ) ) && ! $this->db->is_profile_approved( $user->ID ) ) {
            $this->logout_for_unapproved_account();
            return false;
        }

        if ( 'ed_admin_login' === $auth_type && in_array( 'administrator', (array) $user->roles, true ) ) {
            $this->redirect_to( admin_url() );
        }

        if ( in_array( $auth_type, array( 'admin_login', 'teacher_login', 'teacher_register', 'student_login', 'student_register' ), true ) ) {
            $this->redirect_to( $this->get_dashboard_url_for_user( $user ) );
        }

        return false;
    }

    public function get_dashboard_url_for_user( $user = null ) {
        $user = $user instanceof WP_User ? $user : wp_get_current_user();

        if ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
            return home_url( '/teacher-dashboard' );
        }

        if ( in_array( 'edtech_student', (array) $user->roles, true ) ) {
            return home_url( '/student-dashboard' );
        }

        if ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true ) ) {
            return home_url( '/admin-dashboard' );
        }

        return home_url( '/' );
    }

    private function redirect_to( $url ) {
        wp_safe_redirect( $url );
        exit;
    }

    private function logout_for_unapproved_account() {
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        wp_set_current_user( 0 );
        wp_logout();
    }
}
