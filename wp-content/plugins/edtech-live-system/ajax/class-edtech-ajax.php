<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Ajax {
    private $plugin;

    public function __construct( $plugin ) {
        $this->plugin = $plugin;

        add_action( 'wp_ajax_nopriv_edtech_register', array( $this, 'handle_register' ) );
        add_action( 'wp_ajax_nopriv_edtech_login', array( $this, 'handle_login' ) );
        add_action( 'wp_ajax_nopriv_edtech_forgot_password', array( $this, 'handle_forgot_password' ) );
        add_action( 'wp_ajax_nopriv_edtech_reset_password', array( $this, 'handle_reset_password' ) );
        add_action( 'wp_ajax_edtech_logout', array( $this, 'handle_logout' ) );
        add_action( 'wp_ajax_edtech_update_profile', array( $this, 'handle_update_profile' ) );

        add_action( 'wp_ajax_edtech_approve_teacher', array( $this, 'approve_teacher' ) );
        add_action( 'wp_ajax_edtech_reject_teacher', array( $this, 'reject_teacher' ) );
        add_action( 'wp_ajax_edtech_update_user_status', array( $this, 'update_user_status' ) );
        add_action( 'wp_ajax_edtech_approve_student', array( $this, 'approve_student' ) );

        add_action( 'wp_ajax_edtech_create_subject', array( $this, 'create_subject' ) );
        add_action( 'wp_ajax_edtech_assign_subject_to_student', array( $this, 'assign_subject_to_student' ) );
        add_action( 'wp_ajax_edtech_assign_subject_to_teacher', array( $this, 'assign_subject_to_teacher' ) );
        add_action( 'wp_ajax_edtech_create_live_class', array( $this, 'create_live_class' ) );
        add_action( 'wp_ajax_edtech_mark_live', array( $this, 'mark_live_class' ) );
        add_action( 'wp_ajax_edtech_end_live', array( $this, 'end_live_class' ) );

        add_action( 'wp_ajax_edtech_save_recorded_class', array( $this, 'save_recorded_class' ) );
        add_action( 'wp_ajax_edtech_delete_recorded_class', array( $this, 'delete_recorded_class' ) );
        add_action( 'wp_ajax_edtech_search_videos', array( $this, 'search_videos' ) );
        add_action( 'wp_ajax_edtech_save_watch_history', array( $this, 'save_watch_history' ) );

        add_action( 'wp_ajax_edtech_refresh_routes', array( $this, 'refresh_routes' ) );
        add_action( 'wp_ajax_edtech_reinitialize_platform', array( $this, 'reinitialize_platform' ) );
    }

    public function handle_register() {
        $this->plugin->auth->ajax_register();
    }

    public function handle_login() {
        $this->plugin->auth->ajax_login();
    }

    public function handle_forgot_password() {
        $this->plugin->auth->ajax_forgot_password();
    }

    public function handle_reset_password() {
        $this->plugin->auth->ajax_reset_password();
    }

    public function handle_logout() {
        $this->plugin->auth->ajax_logout();
    }

    public function handle_update_profile() {
        $this->plugin->auth->ajax_update_profile();
    }

    public function approve_teacher() {
        $this->verify_admin_request();

        $user_id = $this->get_user_id_from_request( 'teacher' );
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Teacher ID is required.' ) );
        }

        if ( $this->plugin->db->approve_teacher( $user_id ) ) {
            wp_send_json_success( array( 'message' => 'Teacher approved successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not approve teacher.' ) );
    }

    public function reject_teacher() {
        $this->verify_admin_request();

        $user_id = $this->get_user_id_from_request( 'teacher' );
        $reason  = $this->plugin->helpers->sanitize_textarea( $_POST['reason'] ?? '' );

        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Teacher ID is required.' ) );
        }

        if ( $this->plugin->db->reject_teacher( $user_id, $reason ) ) {
            wp_send_json_success( array( 'message' => 'Teacher rejected successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not reject teacher.' ) );
    }

    public function approve_student() {
        $this->verify_admin_request();

        $user_id = $this->get_user_id_from_request( 'student' );
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'Student ID is required.' ) );
        }

        if ( $this->plugin->db->approve_student( $user_id ) ) {
            wp_send_json_success( array( 'message' => 'Student approved successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not approve student.' ) );
    }

    public function update_user_status() {
        $this->verify_admin_request();

        $user_id = absint( $_POST['user_id'] ?? 0 );
        $status  = sanitize_key( $_POST['status'] ?? '' );

        if ( ! $user_id || ! in_array( $status, array( 'approved', 'pending', 'rejected', 'suspended' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Valid user ID and status are required.' ) );
        }

        if ( $this->plugin->db->update_user_status( $user_id, $status ) ) {
            wp_send_json_success( array( 'message' => 'User status updated successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not update user status.' ) );
    }

    public function create_subject() {
        $this->verify_admin_request();

        $title       = $this->plugin->helpers->sanitize_text( $_POST['title'] ?? '' );
        $description = $this->plugin->helpers->sanitize_textarea( $_POST['description'] ?? '' );
        $teacher_id  = absint( $_POST['teacher_id'] ?? 0 );

        if ( '' === $title ) {
            wp_send_json_error( array( 'message' => 'Subject title is required.' ) );
        }

        $success = $this->plugin->subjects->create_subject( $title, $description, $teacher_id );
        if ( $success ) {
            if ( $teacher_id ) {
                $this->plugin->db->assign_subject_to_teacher( $teacher_id, $this->get_last_insert_id() );
            }
            wp_send_json_success( array( 'message' => 'Subject created successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not create subject.' ) );
    }

    public function assign_subject_to_student() {
        $this->verify_nonce();

        $student_id = absint( $_POST['student_id'] ?? 0 );
        $subject_id = absint( $_POST['subject_id'] ?? 0 );

        if ( ! $student_id && $this->plugin->helpers->is_student() ) {
            $student_id = get_current_user_id();
        }

        if ( $student_id !== get_current_user_id() ) {
            $this->require_admin_capability();
        }

        if ( ! $student_id || ! $subject_id ) {
            wp_send_json_error( array( 'message' => 'Student and subject are required.' ) );
        }

        if ( $this->plugin->db->assign_subject_to_student( $student_id, $subject_id ) ) {
            wp_send_json_success( array( 'message' => 'Subject assigned successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not assign subject.' ) );
    }

    public function assign_subject_to_teacher() {
        $this->verify_nonce();

        $teacher_id = absint( $_POST['teacher_id'] ?? 0 );
        $subject_id = absint( $_POST['subject_id'] ?? 0 );

        if ( ! $teacher_id && $this->plugin->helpers->is_teacher() ) {
            $teacher_id = get_current_user_id();
        }

        if ( $teacher_id !== get_current_user_id() ) {
            $this->require_admin_capability();
        }

        if ( ! $teacher_id || ! $subject_id ) {
            wp_send_json_error( array( 'message' => 'Teacher and subject are required.' ) );
        }

        if ( $this->plugin->db->assign_subject_to_teacher( $teacher_id, $subject_id ) ) {
            wp_send_json_success( array( 'message' => 'Subject assigned successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not assign subject.' ) );
    }

    public function create_live_class() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_teacher() || ! $this->plugin->db->is_profile_approved( get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $title        = $this->plugin->helpers->sanitize_text( $_POST['title'] ?? '' );
        $subject_id   = absint( $_POST['subject_id'] ?? 0 );
        $meeting_link = esc_url_raw( $_POST['meeting_link'] ?? '' );
        $start_time   = sanitize_text_field( $_POST['start_time'] ?? '' );

        if ( '' === $title || ! $subject_id || '' === $meeting_link || '' === $start_time ) {
            wp_send_json_error( array( 'message' => 'All fields are required.' ) );
        }

        if ( $this->plugin->liveclasses->create_live_class( $title, $subject_id, get_current_user_id(), $meeting_link, $start_time ) ) {
            wp_send_json_success( array( 'message' => 'Live class created successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not create live class.' ) );
    }

    public function mark_live_class() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_teacher() ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $class_id = absint( $_POST['class_id'] ?? 0 );
        if ( $this->plugin->liveclasses->mark_live( $class_id ) ) {
            wp_send_json_success( array( 'message' => 'Class is now live.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not start live class.' ) );
    }

    public function end_live_class() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_teacher() ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $class_id = absint( $_POST['class_id'] ?? 0 );
        if ( $this->plugin->liveclasses->end_live( $class_id ) ) {
            wp_send_json_success( array( 'message' => 'Class ended successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not end live class.' ) );
    }

    public function save_recorded_class() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_teacher() || ! $this->plugin->db->is_profile_approved( get_current_user_id() ) ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $video_id    = absint( $_POST['video_id'] ?? 0 );
        $teacher_id  = get_current_user_id();
        $title       = $this->plugin->helpers->sanitize_text( $_POST['title'] ?? '' );
        $subject_id  = absint( $_POST['subject_id'] ?? 0 );
        $youtube_url = esc_url_raw( $_POST['youtube_url'] ?? '' );
        $description = $this->plugin->helpers->sanitize_textarea( $_POST['description'] ?? '' );
        $duration    = $this->plugin->helpers->sanitize_text( $_POST['duration'] ?? '' );
        $tags        = $this->plugin->helpers->sanitize_text( $_POST['tags'] ?? '' );
        $visibility  = in_array( $_POST['visibility'] ?? 'published', array( 'published', 'draft' ), true ) ? sanitize_key( $_POST['visibility'] ) : 'published';

        if ( '' === $title || ! $subject_id || '' === $youtube_url ) {
            wp_send_json_error( array( 'message' => 'Title, subject, and YouTube URL are required.' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        $thumbnail_url = $this->handle_upload_url( 'thumbnail' );
        $notes_url     = $this->handle_upload_url( 'notes_file' );

        $data = array(
            'teacher_id'   => $teacher_id,
            'subject_id'   => $subject_id,
            'title'        => $title,
            'description'  => $description,
            'youtube_url'  => $youtube_url,
            'duration'     => $duration,
            'tags'         => $tags,
            'visibility'   => $visibility,
        );

        if ( $thumbnail_url ) {
            $data['thumbnail'] = $thumbnail_url;
        }

        if ( $notes_url ) {
            $data['notes_file'] = $notes_url;
        }

        if ( $video_id ) {
            $video = $this->plugin->db->get_recorded_video_by_id( $video_id );
            if ( ! $video || absint( $video->teacher_id ) !== $teacher_id ) {
                wp_send_json_error( array( 'message' => 'Video not found or permission denied.' ) );
            }

            $success = $this->plugin->db->update_recorded_video( $video_id, $data );
            $message = 'Recorded class updated successfully.';
        } else {
            $success = $this->plugin->db->create_recorded_video( $data );
            $message = 'Recorded class saved successfully.';
        }

        if ( $success ) {
            wp_send_json_success( array( 'message' => $message ) );
        }

        wp_send_json_error( array( 'message' => 'Could not save recorded class.' ) );
    }

    public function delete_recorded_class() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_teacher() ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }

        $video_id = absint( $_POST['video_id'] ?? 0 );
        $video    = $this->plugin->db->get_recorded_video_by_id( $video_id );

        if ( ! $video || absint( $video->teacher_id ) !== get_current_user_id() ) {
            wp_send_json_error( array( 'message' => 'Video not found or permission denied.' ) );
        }

        if ( $this->plugin->db->delete_recorded_video( $video_id ) ) {
            wp_send_json_success( array( 'message' => 'Recorded class deleted.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not delete recorded class.' ) );
    }

    public function search_videos() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_student() ) {
            wp_send_json_error( array( 'message' => 'Student login required.' ) );
        }

        $videos = $this->plugin->db->get_recorded_videos_for_student(
            get_current_user_id(),
            array(
                'search'     => sanitize_text_field( $_POST['search'] ?? '' ),
                'subject_id' => absint( $_POST['subject_id'] ?? 0 ),
                'teacher_id' => absint( $_POST['teacher_id'] ?? 0 ),
                'limit'      => 24,
            )
        );

        wp_send_json_success( array( 'videos' => $videos ) );
    }

    public function save_watch_history() {
        $this->verify_nonce();

        if ( ! $this->plugin->helpers->is_student() ) {
            wp_send_json_error( array( 'message' => 'Only students can save watch history.' ) );
        }

        $video_id = absint( $_POST['video_id'] ?? 0 );
        $progress = absint( $_POST['progress'] ?? 0 );

        if ( ! $video_id ) {
            wp_send_json_error( array( 'message' => 'Video ID is required.' ) );
        }

        if ( $this->plugin->db->record_video_history( get_current_user_id(), $video_id, $progress ) ) {
            wp_send_json_success( array( 'message' => 'Watch history saved.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not save history.' ) );
    }

    public function refresh_routes() {
        $this->verify_admin_request();

        flush_rewrite_rules( false );
        update_option( 'edtech_auth_route_version', Edtech_Auth_Routes::ROUTE_VERSION );

        wp_send_json_success( array( 'message' => 'System routes refreshed successfully.' ) );
    }

    public function reinitialize_platform() {
        $this->verify_admin_request();

        Edtech_Live_System::create_roles();
        $this->plugin->db->create_tables();
        flush_rewrite_rules( false );

        if ( function_exists( 'wp_cache_flush' ) ) {
            wp_cache_flush();
        }

        wp_send_json_success( array( 'message' => 'Platform reinitialized successfully.' ) );
    }

    private function verify_nonce() {
        check_ajax_referer( 'edtech_live_nonce', 'nonce' );
    }

    private function verify_admin_request() {
        $this->verify_nonce();
        $this->require_admin_capability();
    }

    private function require_admin_capability() {
        if ( ! $this->plugin->helpers->is_super_admin() ) {
            wp_send_json_error( array( 'message' => 'Permission denied.' ) );
        }
    }

    private function get_user_id_from_request( $type ) {
        global $wpdb;

        $user_id = absint( $_POST['user_id'] ?? 0 );
        if ( $user_id ) {
            return $user_id;
        }

        $profile_id = absint( $_POST[ $type . '_id' ] ?? 0 );
        if ( ! $profile_id ) {
            return 0;
        }

        $table = 'teacher' === $type ? "{$wpdb->prefix}lms_teachers" : "{$wpdb->prefix}lms_students";
        return absint( $wpdb->get_var( $wpdb->prepare( "SELECT user_id FROM {$table} WHERE id = %d", $profile_id ) ) );
    }

    private function get_last_insert_id() {
        global $wpdb;
        return absint( $wpdb->insert_id );
    }

    private function handle_upload_url( $field ) {
        if ( empty( $_FILES[ $field ]['name'] ) ) {
            return '';
        }

        $upload = wp_handle_upload( $_FILES[ $field ], array( 'test_form' => false ) );
        return isset( $upload['url'] ) ? esc_url_raw( $upload['url'] ) : '';
    }
}
