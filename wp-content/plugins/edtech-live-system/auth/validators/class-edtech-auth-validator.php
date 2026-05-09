<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Auth_Validator {
    private $db;
    private $helpers;

    public function __construct( $db, $helpers ) {
        $this->db      = $db;
        $this->helpers = $helpers;
    }

    public function validate_login( $data ) {
        $login     = sanitize_text_field( trim( (string) ( $data['email'] ?? $data['login'] ?? '' ) ) );
        $password  = (string) ( $data['password'] ?? '' );
        $auth_type = sanitize_key( $data['auth_type'] ?? 'student_login' );

        if ( '' === $login || '' === $password ) {
            return new WP_Error( 'missing_credentials', __( 'Username/email and password are required.', 'edtech-live-system' ) );
        }

        if ( is_email( $login ) ) {
            $login = sanitize_email( $login );
        } else {
            $login = sanitize_user( $login, true );
        }

        if ( '' === $login ) {
            return new WP_Error( 'invalid_login', __( 'Please enter a valid username or email address.', 'edtech-live-system' ) );
        }

        if ( ! in_array( $auth_type, array( 'ed_admin_login', 'admin_login', 'teacher_login', 'student_login' ), true ) ) {
            return new WP_Error( 'invalid_auth_type', __( 'Invalid login type.', 'edtech-live-system' ) );
        }

        return array(
            'login'     => $login,
            'password'  => $password,
            'remember'  => ! empty( $data['remember'] ),
            'auth_type' => $auth_type,
        );
    }

    public function validate_registration( $data ) {
        $role      = sanitize_key( $data['role'] ?? 'edtech_student' );
        $email     = $this->helpers->sanitize_email( $data['email'] ?? '' );
        $password  = (string) ( $data['password'] ?? '' );
        $full_name = $this->helpers->sanitize_text( $data['full_name'] ?? '' );

        if ( ! in_array( $role, array( 'edtech_student', 'edtech_teacher' ), true ) ) {
            return new WP_Error( 'invalid_role', __( 'Invalid registration type.', 'edtech-live-system' ) );
        }

        if ( '' === $full_name || '' === $email || '' === $password ) {
            return new WP_Error( 'missing_fields', __( 'Full name, email, and password are required.', 'edtech-live-system' ) );
        }

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'edtech-live-system' ) );
        }

        $password_error = $this->validate_password( $password );
        if ( is_wp_error( $password_error ) ) {
            return $password_error;
        }

        if ( email_exists( $email ) ) {
            return new WP_Error( 'email_exists', __( 'An account already exists for that email address.', 'edtech-live-system' ) );
        }

        $sanitized = array(
            'role'         => $role,
            'email'        => $email,
            'password'     => $password,
            'full_name'    => $full_name,
            'phone'        => $this->helpers->sanitize_text( $data['phone'] ?? '' ),
            'bio'          => $this->helpers->sanitize_textarea( $data['bio'] ?? '' ),
            'subject_ids'  => $this->sanitize_subject_ids( $data['subject_ids'] ?? array() ),
        );

        if ( 'edtech_teacher' === $role ) {
            $sanitized['qualification'] = $this->helpers->sanitize_text( $data['qualification'] ?? '' );
            $sanitized['experience']    = $this->helpers->sanitize_text( $data['experience'] ?? '' );
        } else {
            $sanitized['grade']        = $this->helpers->sanitize_text( $data['grade'] ?? '' );
            $sanitized['city']         = $this->helpers->sanitize_text( $data['city'] ?? '' );
            $sanitized['parent_name']  = $this->helpers->sanitize_text( $data['parent_name'] ?? '' );
            $sanitized['parent_phone'] = $this->helpers->sanitize_text( $data['parent_phone'] ?? '' );
        }

        return $sanitized;
    }

    public function validate_password_reset_request( $data ) {
        $email = $this->helpers->sanitize_email( $data['email'] ?? '' );

        if ( '' === $email ) {
            return new WP_Error( 'missing_email', __( 'Email is required.', 'edtech-live-system' ) );
        }

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Please enter a valid email address.', 'edtech-live-system' ) );
        }

        return array( 'email' => $email );
    }

    public function validate_password_reset( $data ) {
        $password = (string) ( $data['password'] ?? '' );
        $confirm  = (string) ( $data['confirm_password'] ?? '' );
        $key      = sanitize_text_field( $data['key'] ?? '' );
        $login    = sanitize_text_field( $data['login'] ?? '' );

        if ( '' === $password || '' === $confirm || '' === $key || '' === $login ) {
            return new WP_Error( 'missing_fields', __( 'All reset fields are required.', 'edtech-live-system' ) );
        }

        if ( $password !== $confirm ) {
            return new WP_Error( 'password_mismatch', __( 'Passwords do not match.', 'edtech-live-system' ) );
        }

        $password_error = $this->validate_password( $password );
        if ( is_wp_error( $password_error ) ) {
            return $password_error;
        }

        return array(
            'password' => $password,
            'key'      => $key,
            'login'    => $login,
        );
    }

    public function validate_profile_update( $data ) {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return new WP_Error( 'not_logged_in', __( 'You must be logged in to update your profile.', 'edtech-live-system' ) );
        }

        return array(
            'user_id'      => $user_id,
            'full_name'    => $this->helpers->sanitize_text( $data['full_name'] ?? '' ),
            'phone'        => $this->helpers->sanitize_text( $data['phone'] ?? '' ),
            'bio'          => $this->helpers->sanitize_textarea( $data['bio'] ?? '' ),
            'grade'        => $this->helpers->sanitize_text( $data['grade'] ?? '' ),
            'city'         => $this->helpers->sanitize_text( $data['city'] ?? '' ),
            'parent_name'  => $this->helpers->sanitize_text( $data['parent_name'] ?? '' ),
            'parent_phone' => $this->helpers->sanitize_text( $data['parent_phone'] ?? '' ),
            'qualification'=> $this->helpers->sanitize_text( $data['qualification'] ?? '' ),
            'experience'   => $this->helpers->sanitize_text( $data['experience'] ?? '' ),
            'subject_ids'  => $this->sanitize_subject_ids( $data['subject_ids'] ?? array() ),
        );
    }

    public function validate_password( $password ) {
        $min_length = absint( $this->db->get_setting( 'password_min_length', 8 ) );

        if ( strlen( $password ) < $min_length ) {
            return new WP_Error(
                'weak_password',
                sprintf(
                    /* translators: %d: minimum password length */
                    __( 'Password must be at least %d characters long.', 'edtech-live-system' ),
                    $min_length
                )
            );
        }

        if ( ! preg_match( '/[A-Z]/', $password ) || ! preg_match( '/[a-z]/', $password ) || ! preg_match( '/[0-9]/', $password ) ) {
            return new WP_Error( 'weak_password', __( 'Password must include uppercase, lowercase, and numeric characters.', 'edtech-live-system' ) );
        }

        return true;
    }

    private function sanitize_subject_ids( $subject_ids ) {
        if ( ! is_array( $subject_ids ) ) {
            $subject_ids = array( $subject_ids );
        }

        $subject_ids = array_map( 'absint', $subject_ids );
        $subject_ids = array_filter( $subject_ids );

        return array_values( array_unique( $subject_ids ) );
    }
}
