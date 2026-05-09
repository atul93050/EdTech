<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Helpers {
    public function sanitize_text( $value ) {
        return sanitize_text_field( trim( $value ) );
    }

    public function sanitize_email( $value ) {
        return sanitize_email( trim( $value ) );
    }

    public function sanitize_textarea( $value ) {
        return sanitize_textarea_field( trim( $value ) );
    }

    public function get_role_label( $role ) {
        switch ( $role ) {
            case 'edtech_teacher':
                return 'Teacher';
            case 'edtech_student':
                return 'Student';
            case 'edtech_super_admin':
                return 'Super Admin';
            default:
                return 'User';
        }
    }

    public function is_teacher() {
        $user = wp_get_current_user();
        return in_array( 'edtech_teacher', (array) $user->roles, true );
    }

    public function is_student() {
        $user = wp_get_current_user();
        return in_array( 'edtech_student', (array) $user->roles, true );
    }

    public function is_super_admin() {
        $user = wp_get_current_user();
        return in_array( 'edtech_super_admin', (array) $user->roles, true ) || in_array( 'administrator', (array) $user->roles, true );
    }
}
