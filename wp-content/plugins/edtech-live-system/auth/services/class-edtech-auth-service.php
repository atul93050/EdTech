<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Auth_Service {
    private $db;
    private $helpers;
    private $validator;

    public function __construct( $db, $helpers, $validator ) {
        $this->db        = $db;
        $this->helpers   = $helpers;
        $this->validator = $validator;
    }

    public function login( $data ) {
        $data = $this->validator->validate_login( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $identifier = $this->db->get_client_ip() . '|' . strtolower( $data['login'] );
        $limit      = absint( $this->db->get_setting( 'max_login_attempts', 5 ) );

        if ( ! $this->db->check_rate_limit( 'login', $identifier, $limit, HOUR_IN_SECONDS ) ) {
            $this->db->log_security_event( 0, 'login_rate_limit_exceeded', array( 'login' => $data['login'] ) );
            return new WP_Error( 'rate_limited', __( 'Too many login attempts. Please try again later.', 'edtech-live-system' ) );
        }

        $user_login = $this->resolve_user_login( $data['login'] );

        $user = wp_signon(
            array(
                'user_login'    => $user_login,
                'user_password' => $data['password'],
                'remember'      => $data['remember'],
            ),
            is_ssl()
        );

        if ( is_wp_error( $user ) ) {
            $this->db->log_security_event( 0, 'failed_login', array( 'login' => $data['login'] ) );
            return new WP_Error( 'invalid_credentials', __( 'Invalid login credentials.', 'edtech-live-system' ) );
        }

        $role_error = $this->validate_login_role( $user, $data['auth_type'] );
        if ( is_wp_error( $role_error ) ) {
            $this->logout_current_user();
            $this->db->log_security_event( $user->ID, 'login_wrong_role', array( 'auth_type' => $data['auth_type'] ) );
            return $role_error;
        }

        $approval_error = $this->validate_login_approval( $user, $data['auth_type'] );
        if ( is_wp_error( $approval_error ) ) {
            $this->logout_current_user();
            $this->db->log_security_event( $user->ID, 'login_pending_approval', array( 'auth_type' => $data['auth_type'] ) );
            return $approval_error;
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, $data['remember'], is_ssl() );

        $this->db->clear_rate_limit( 'login', $identifier );
        $this->db->log_security_event( $user->ID, 'login_success', array( 'auth_type' => $data['auth_type'] ) );

        return array(
            'message'  => __( 'Login successful.', 'edtech-live-system' ),
            'redirect' => $this->get_redirect_for_auth_type( $user, $data['auth_type'] ),
        );
    }

    public function register( $data ) {
        $data = $this->validator->validate_registration( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $identifier = $this->db->get_client_ip() . '|' . strtolower( $data['email'] );
        if ( ! $this->db->check_rate_limit( 'registration', $identifier, 3, HOUR_IN_SECONDS ) ) {
            $this->db->log_security_event( 0, 'registration_rate_limit_exceeded', array( 'email' => $data['email'] ) );
            return new WP_Error( 'rate_limited', __( 'Too many registration attempts. Please try again later.', 'edtech-live-system' ) );
        }

        $user_id = wp_insert_user(
            array(
                'user_login'   => $this->generate_username( $data['email'] ),
                'user_pass'    => $data['password'],
                'user_email'   => $data['email'],
                'display_name' => $data['full_name'],
                'first_name'   => $data['full_name'],
                'role'         => $data['role'],
            )
        );

        if ( is_wp_error( $user_id ) ) {
            $this->db->log_security_event( 0, 'registration_failed', array( 'email' => $data['email'], 'error' => $user_id->get_error_code() ) );
            return $user_id;
        }

        update_user_meta( $user_id, 'full_name', $data['full_name'] );
        update_user_meta( $user_id, 'edtech_role', $data['role'] );

        $profile_created = 'edtech_teacher' === $data['role']
            ? $this->db->create_teacher_profile( $user_id, $data )
            : $this->db->create_student_profile( $user_id, $data );

        if ( ! $profile_created ) {
            $this->delete_user_safely( $user_id );
            return new WP_Error( 'profile_create_failed', __( 'Could not create the account profile. Please try again.', 'edtech-live-system' ) );
        }

        if ( ! empty( $data['subject_ids'] ) ) {
            foreach ( $data['subject_ids'] as $subject_id ) {
                if ( 'edtech_teacher' === $data['role'] ) {
                    $this->db->assign_subject_to_teacher( $user_id, $subject_id );
                } else {
                    $this->db->assign_subject_to_student( $user_id, $subject_id );
                }
            }
            update_user_meta( $user_id, 'edtech_subject_ids', $data['subject_ids'] );
        }

        $this->db->clear_rate_limit( 'registration', $identifier );
        $this->db->log_security_event( $user_id, 'registration_success', array( 'role' => $data['role'] ) );

        if ( 'edtech_teacher' === $data['role'] ) {
            $this->notify_admin_of_teacher_registration( $user_id );
            return array(
                'message'  => __( 'Registration successful. Your teacher account is pending admin approval.', 'edtech-live-system' ),
                'redirect' => home_url( '/teacher-login' ),
            );
        }

        return array(
            'message'  => __( 'Registration successful. You can now log in.', 'edtech-live-system' ),
            'redirect' => home_url( '/student-login' ),
        );
    }

    public function forgot_password( $data ) {
        $data = $this->validator->validate_password_reset_request( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $identifier = $this->db->get_client_ip() . '|' . strtolower( $data['email'] );
        if ( ! $this->db->check_rate_limit( 'password_reset', $identifier, 3, HOUR_IN_SECONDS ) ) {
            $this->db->log_security_event( 0, 'password_reset_rate_limit_exceeded', array( 'email' => $data['email'] ) );
            return new WP_Error( 'rate_limited', __( 'Too many password reset requests. Please try again later.', 'edtech-live-system' ) );
        }

        $safe_response = array(
            'message' => __( 'If an account with that email exists, reset instructions have been sent.', 'edtech-live-system' ),
        );

        $user = get_user_by( 'email', $data['email'] );
        if ( ! $user ) {
            $this->db->log_security_event( 0, 'password_reset_unknown_email', array( 'email' => $data['email'] ) );
            return $safe_response;
        }

        if ( ! $this->can_reset_password( $user ) ) {
            $this->db->log_security_event( $user->ID, 'password_reset_blocked_unapproved', array() );
            return $safe_response;
        }

        $key = get_password_reset_key( $user );
        if ( is_wp_error( $key ) ) {
            $this->db->log_security_event( $user->ID, 'password_reset_key_failed', array( 'error' => $key->get_error_code() ) );
            return new WP_Error( 'reset_key_failed', __( 'Could not generate a reset link. Please try again.', 'edtech-live-system' ) );
        }

        $reset_url = add_query_arg(
            array(
                'key'   => $key,
                'login' => rawurlencode( $user->user_login ),
            ),
            home_url( '/reset-password' )
        );

        $sent = wp_mail(
            $user->user_email,
            sprintf(
                /* translators: %s: site name */
                __( 'Password Reset Request - %s', 'edtech-live-system' ),
                $this->db->get_setting( 'platform_name', get_bloginfo( 'name' ) )
            ),
            $this->get_password_reset_email( $user, $reset_url ),
            array( 'Content-Type: text/html; charset=UTF-8' )
        );

        if ( ! $sent ) {
            $this->db->log_security_event( $user->ID, 'password_reset_email_failed', array() );
            return new WP_Error( 'mail_failed', __( 'Could not send reset email. Please try again.', 'edtech-live-system' ) );
        }

        $this->db->clear_rate_limit( 'password_reset', $identifier );
        $this->db->log_security_event( $user->ID, 'password_reset_email_sent', array() );

        return $safe_response;
    }

    public function reset_password( $data ) {
        $data = $this->validator->validate_password_reset( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        $user = check_password_reset_key( $data['key'], $data['login'] );
        if ( is_wp_error( $user ) ) {
            $this->db->log_security_event( 0, 'password_reset_invalid_key', array( 'login' => $data['login'] ) );
            return new WP_Error( 'invalid_reset_key', __( 'Invalid or expired reset link.', 'edtech-live-system' ) );
        }

        if ( ! $this->can_reset_password( $user ) ) {
            $this->db->log_security_event( $user->ID, 'password_reset_blocked_unapproved', array() );
            return new WP_Error( 'account_not_approved', __( 'This account is not approved for password reset.', 'edtech-live-system' ) );
        }

        reset_password( $user, $data['password'] );
        $this->db->log_security_event( $user->ID, 'password_reset_success', array() );

        return array(
            'message'  => __( 'Password updated successfully.', 'edtech-live-system' ),
            'redirect' => $this->get_login_url_for_user( $user ),
        );
    }

    public function logout() {
        $user_id = get_current_user_id();
        if ( $user_id ) {
            $this->db->log_security_event( $user_id, 'logout', array() );
        }

        $this->logout_current_user();

        return array(
            'message'  => __( 'You have been logged out.', 'edtech-live-system' ),
            'redirect' => home_url( '/' ),
        );
    }

    public function update_profile( $data ) {
        $data = $this->validator->validate_profile_update( $data );
        if ( is_wp_error( $data ) ) {
            return $data;
        }

        global $wpdb;

        $user = get_user_by( 'id', $data['user_id'] );
        if ( ! $user ) {
            return new WP_Error( 'missing_user', __( 'User not found.', 'edtech-live-system' ) );
        }

        wp_update_user(
            array(
                'ID'           => $data['user_id'],
                'display_name' => $data['full_name'] ?: $user->display_name,
            )
        );
        update_user_meta( $data['user_id'], 'full_name', $data['full_name'] );

        if ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
            $wpdb->update(
                "{$wpdb->prefix}lms_teachers",
                array(
                    'full_name'     => $data['full_name'],
                    'phone'         => $data['phone'],
                    'qualification' => $data['qualification'],
                    'experience'    => $data['experience'],
                    'bio'           => $data['bio'],
                    'updated_at'    => current_time( 'mysql' ),
                ),
                array( 'user_id' => $data['user_id'] ),
                array( '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        } elseif ( in_array( 'edtech_student', (array) $user->roles, true ) ) {
            $wpdb->update(
                "{$wpdb->prefix}lms_students",
                array(
                    'full_name'    => $data['full_name'],
                    'phone'        => $data['phone'],
                    'grade'        => $data['grade'],
                    'city'         => $data['city'],
                    'parent_name'  => $data['parent_name'],
                    'parent_phone' => $data['parent_phone'],
                    'bio'          => $data['bio'],
                    'updated_at'   => current_time( 'mysql' ),
                ),
                array( 'user_id' => $data['user_id'] ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' ),
                array( '%d' )
            );
        }

        if ( ! empty( $data['subject_ids'] ) ) {
            foreach ( $data['subject_ids'] as $subject_id ) {
                if ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
                    $this->db->assign_subject_to_teacher( $data['user_id'], $subject_id );
                } elseif ( in_array( 'edtech_student', (array) $user->roles, true ) ) {
                    $this->db->assign_subject_to_student( $data['user_id'], $subject_id );
                }
            }
            update_user_meta( $data['user_id'], 'edtech_subject_ids', $data['subject_ids'] );
        }

        $this->db->log_security_event( $data['user_id'], 'profile_updated', array() );

        return array(
            'message' => __( 'Profile updated successfully.', 'edtech-live-system' ),
        );
    }

    public function get_role_based_redirect( $user = null ) {
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

        return home_url( '/dashboard' );
    }

    public function get_login_url_for_user( $user ) {
        if ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
            return home_url( '/teacher-login' );
        }

        if ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true ) ) {
            return home_url( '/admin-login' );
        }

        return home_url( '/student-login' );
    }

    private function validate_login_role( $user, $auth_type ) {
        $roles = (array) $user->roles;

        if ( 'ed_admin_login' === $auth_type && ! in_array( 'administrator', $roles, true ) ) {
            return new WP_Error( 'admin_only', __( 'Only WordPress administrators can use this login.', 'edtech-live-system' ) );
        }

        if ( 'admin_login' === $auth_type && ! in_array( 'administrator', $roles, true ) && ! in_array( 'edtech_super_admin', $roles, true ) ) {
            return new WP_Error( 'frontend_admin_only', __( 'Only platform admins can use this login.', 'edtech-live-system' ) );
        }

        if ( 'teacher_login' === $auth_type && ! in_array( 'edtech_teacher', $roles, true ) ) {
            return new WP_Error( 'teacher_only', __( 'Please use a teacher account for this login.', 'edtech-live-system' ) );
        }

        if ( 'student_login' === $auth_type && ! in_array( 'edtech_student', $roles, true ) ) {
            return new WP_Error( 'student_only', __( 'Please use a student account for this login.', 'edtech-live-system' ) );
        }

        return true;
    }

    private function resolve_user_login( $login ) {
        if ( is_email( $login ) ) {
            $user = get_user_by( 'email', $login );
            if ( $user ) {
                return $user->user_login;
            }
        }

        return $login;
    }

    private function validate_login_approval( $user, $auth_type ) {
        if ( in_array( $auth_type, array( 'ed_admin_login', 'admin_login' ), true ) ) {
            return true;
        }

        if ( $this->db->is_profile_approved( $user->ID ) ) {
            return true;
        }

        return new WP_Error( 'pending_approval', __( 'Your account is pending admin approval.', 'edtech-live-system' ) );
    }

    private function can_reset_password( $user ) {
        $roles = (array) $user->roles;

        if ( in_array( 'administrator', $roles, true ) || in_array( 'edtech_super_admin', $roles, true ) ) {
            return true;
        }

        return $this->db->is_profile_approved( $user->ID );
    }

    private function get_redirect_for_auth_type( $user, $auth_type ) {
        if ( 'ed_admin_login' === $auth_type ) {
            return admin_url();
        }

        return $this->get_role_based_redirect( $user );
    }

    private function logout_current_user() {
        wp_destroy_current_session();
        wp_clear_auth_cookie();
        wp_set_current_user( 0 );
        wp_logout();
    }

    private function generate_username( $email ) {
        $parts = explode( '@', $email );
        $base  = sanitize_user( $parts[0], true );

        if ( '' === $base ) {
            $base = 'edtech_user';
        }

        $username = $base;
        $suffix   = 1;

        while ( username_exists( $username ) ) {
            $username = $base . $suffix;
            $suffix++;
        }

        return $username;
    }

    private function delete_user_safely( $user_id ) {
        if ( ! function_exists( 'wp_delete_user' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        wp_delete_user( $user_id );
    }

    private function notify_admin_of_teacher_registration( $user_id ) {
        $admin_email = $this->db->get_setting( 'notification_email', get_option( 'admin_email' ) );
        $user        = get_user_by( 'id', $user_id );

        if ( ! $admin_email || ! $user ) {
            return;
        }

        wp_mail(
            $admin_email,
            __( 'New teacher registration pending approval', 'edtech-live-system' ),
            sprintf(
                "A new teacher has registered and is waiting for approval.\n\nName: %s\nEmail: %s\nDashboard: %s",
                $user->display_name,
                $user->user_email,
                home_url( '/admin-dashboard' )
            )
        );
    }

    private function get_password_reset_email( $user, $reset_url ) {
        $platform_name = esc_html( $this->db->get_setting( 'platform_name', get_bloginfo( 'name' ) ) );

        ob_start();
        ?>
        <div style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
            <h2><?php echo $platform_name; ?> password reset</h2>
            <p>Hello <?php echo esc_html( $user->display_name ?: $user->user_login ); ?>,</p>
            <p>Use the button below to choose a new password.</p>
            <p><a href="<?php echo esc_url( $reset_url ); ?>" style="display:inline-block;background:#2563eb;color:#fff;padding:12px 18px;text-decoration:none;border-radius:6px;">Reset Password</a></p>
            <p>If the button does not work, open this link:</p>
            <p style="word-break: break-all;"><?php echo esc_url( $reset_url ); ?></p>
            <p>If you did not request this, you can ignore this email.</p>
        </div>
        <?php
        return ob_get_clean();
    }
}
