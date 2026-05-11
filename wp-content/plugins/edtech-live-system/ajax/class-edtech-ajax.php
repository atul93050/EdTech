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
        add_action( 'wp_ajax_edtech_update_student_profile', array( $this, 'handle_update_profile' ) );

        add_action( 'wp_ajax_edtech_approve_teacher', array( $this, 'approve_teacher' ) );
        add_action( 'wp_ajax_edtech_reject_teacher', array( $this, 'reject_teacher' ) );
        add_action( 'wp_ajax_edtech_update_user_status', array( $this, 'update_user_status' ) );
        add_action( 'wp_ajax_edtech_approve_student', array( $this, 'approve_student' ) );

        add_action( 'wp_ajax_edtech_create_subject', array( $this, 'create_subject' ) );
        add_action( 'wp_ajax_edtech_get_subject', array( $this, 'get_subject' ) );
        add_action( 'wp_ajax_edtech_get_admin_options', array( $this, 'get_admin_options' ) );
        add_action( 'wp_ajax_edtech_update_subject', array( $this, 'update_subject' ) );
        add_action( 'wp_ajax_edtech_update_subject_status', array( $this, 'update_subject_status' ) );
        add_action( 'wp_ajax_edtech_delete_subject', array( $this, 'delete_subject' ) );
        add_action( 'wp_ajax_edtech_create_category', array( $this, 'create_category' ) );
        add_action( 'wp_ajax_edtech_get_category', array( $this, 'get_category' ) );
        add_action( 'wp_ajax_edtech_update_category', array( $this, 'update_category' ) );
        add_action( 'wp_ajax_edtech_update_category_status', array( $this, 'update_category_status' ) );
        add_action( 'wp_ajax_edtech_delete_category', array( $this, 'delete_category' ) );
        add_action( 'wp_ajax_edtech_block_user', array( $this, 'block_user' ) );
        add_action( 'wp_ajax_edtech_unblock_user', array( $this, 'unblock_user' ) );
        add_action( 'wp_ajax_edtech_assign_subject_to_student', array( $this, 'assign_subject_to_student' ) );
        add_action( 'wp_ajax_edtech_student_enroll_subject', array( $this, 'assign_subject_to_student' ) );
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
        add_action( 'wp_ajax_edtech_save_platform_settings', array( $this, 'save_platform_settings' ) );
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

        $data = $this->collect_subject_payload();

        if ( '' === $data['title'] ) {
            wp_send_json_error( array( 'message' => 'Subject title is required.' ) );
        }

        if ( ! $this->category_exists( $data['category_id'] ) ) {
            wp_send_json_error( array( 'message' => 'Please choose a valid category or leave it uncategorized.' ) );
        }

        if ( ! $this->teachers_are_approved( $data['teacher_ids'] ) ) {
            wp_send_json_error( array( 'message' => 'Only approved teachers can be assigned to subjects.' ) );
        }

        $subject_id = $this->plugin->subjects->create_subject( $data );
        if ( $subject_id ) {
            wp_send_json_success(
                array(
                    'message'    => 'Subject created successfully.',
                    'subject_id' => $subject_id,
                )
            );
        }

        wp_send_json_error( array( 'message' => 'Could not create subject. The slug may already exist or the database insert failed.' ) );
    }

    public function get_subject() {
        $this->verify_admin_request();

        $subject_id = absint( $_POST['subject_id'] ?? 0 );
        if ( ! $subject_id ) {
            wp_send_json_error( array( 'message' => 'Subject ID is required.' ) );
        }

        $subject = $this->plugin->db->get_subject_by_id( $subject_id );
        if ( ! $subject ) {
            wp_send_json_error( array( 'message' => 'Subject not found.' ) );
        }

        $subject->teacher_ids = $this->plugin->db->get_subject_teacher_ids( $subject_id );

        wp_send_json_success( array( 'subject' => $subject ) );
    }

    public function get_admin_options() {
        $this->verify_admin_request();

        wp_send_json_success(
            array(
                'categories' => $this->plugin->db->get_subject_categories( true ),
                'teachers'   => $this->plugin->db->get_teachers_for_subject_assignment(),
            )
        );
    }

    public function update_subject() {
        $this->verify_admin_request();

        $subject_id = absint( $_POST['subject_id'] ?? 0 );
        $data       = $this->collect_subject_payload();

        if ( ! $subject_id || '' === $data['title'] ) {
            wp_send_json_error( array( 'message' => 'Subject ID and title are required.' ) );
        }

        if ( ! $this->category_exists( $data['category_id'] ) ) {
            wp_send_json_error( array( 'message' => 'Please choose a valid category or leave it uncategorized.' ) );
        }

        if ( ! $this->teachers_are_approved( $data['teacher_ids'] ) ) {
            wp_send_json_error( array( 'message' => 'Only approved teachers can be assigned to subjects.' ) );
        }

        if ( $this->plugin->subjects->update_subject( $subject_id, $data ) ) {
            wp_send_json_success( array( 'message' => 'Subject updated successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not update subject.' ) );
    }

    public function update_subject_status() {
        $this->verify_admin_request();

        $subject_id = absint( $_POST['subject_id'] ?? 0 );
        $status     = sanitize_key( $_POST['status'] ?? '' );

        if ( ! $subject_id || ! in_array( $status, array( 'active', 'inactive', 'draft' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Valid subject and status are required.' ) );
        }

        if ( $this->plugin->db->update_subject_status( $subject_id, $status ) ) {
            wp_send_json_success( array( 'message' => 'Subject status updated.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not update subject status.' ) );
    }

    public function delete_subject() {
        $this->verify_admin_request();

        $subject_id = absint( $_POST['subject_id'] ?? 0 );
        if ( ! $subject_id ) {
            wp_send_json_error( array( 'message' => 'Subject ID is required.' ) );
        }

        if ( $this->plugin->db->delete_subject( $subject_id ) ) {
            wp_send_json_success( array( 'message' => 'Subject deleted successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not delete subject.' ) );
    }

    public function create_category() {
        $this->verify_admin_request();

        $data = array(
            'name' => $this->plugin->helpers->sanitize_text( $_POST['name'] ?? '' ),
            'slug' => sanitize_title( $_POST['slug'] ?? $_POST['name'] ?? '' ),
            'status' => sanitize_text_field( $_POST['status'] ?? 'active' ),
        );

        if ( '' === $data['name'] ) {
            wp_send_json_error( array( 'message' => 'Category name is required.' ) );
        }

        if ( $this->plugin->db->create_subject_category( $data ) ) {
            wp_send_json_success( array( 'message' => 'Category created successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not create category.' ) );
    }

    public function get_category() {
        $this->verify_admin_request();

        $category_id = absint( $_POST['category_id'] ?? 0 );
        if ( ! $category_id ) {
            wp_send_json_error( array( 'message' => 'Category ID is required.' ) );
        }

        $category = $this->plugin->db->get_subject_category_by_id( $category_id );
        if ( ! $category ) {
            wp_send_json_error( array( 'message' => 'Category not found.' ) );
        }

        wp_send_json_success( array( 'category' => $category ) );
    }

    public function update_category() {
        $this->verify_admin_request();

        $category_id = absint( $_POST['category_id'] ?? 0 );
        $data = array(
            'name' => $this->plugin->helpers->sanitize_text( $_POST['name'] ?? '' ),
            'slug' => sanitize_title( $_POST['slug'] ?? $_POST['name'] ?? '' ),
            'status' => sanitize_text_field( $_POST['status'] ?? 'active' ),
        );

        if ( ! $category_id || '' === $data['name'] ) {
            wp_send_json_error( array( 'message' => 'Category ID and name are required.' ) );
        }

        if ( $this->plugin->db->update_subject_category( $category_id, $data ) ) {
            wp_send_json_success( array( 'message' => 'Category updated successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not update category.' ) );
    }

    public function update_category_status() {
        $this->verify_admin_request();

        $category_id = absint( $_POST['category_id'] ?? 0 );
        $status      = sanitize_key( $_POST['status'] ?? '' );

        if ( ! $category_id || ! in_array( $status, array( 'active', 'inactive' ), true ) ) {
            wp_send_json_error( array( 'message' => 'Valid category and status are required.' ) );
        }

        if ( $this->plugin->db->update_subject_category_status( $category_id, $status ) ) {
            wp_send_json_success( array( 'message' => 'Category status updated.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not update category status.' ) );
    }

    public function delete_category() {
        $this->verify_admin_request();

        $category_id = absint( $_POST['category_id'] ?? 0 );
        if ( ! $category_id ) {
            wp_send_json_error( array( 'message' => 'Category ID is required.' ) );
        }

        if ( $this->plugin->db->delete_subject_category( $category_id ) ) {
            wp_send_json_success( array( 'message' => 'Category deleted successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not delete category.' ) );
    }

    public function block_user() {
        $this->verify_admin_request();

        $user_id = absint( $_POST['user_id'] ?? 0 );
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'User ID is required.' ) );
        }

        if ( $this->plugin->db->update_user_status( $user_id, 'suspended' ) ) {
            wp_send_json_success( array( 'message' => 'User blocked successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not block user.' ) );
    }

    public function unblock_user() {
        $this->verify_admin_request();

        $user_id = absint( $_POST['user_id'] ?? 0 );
        if ( ! $user_id ) {
            wp_send_json_error( array( 'message' => 'User ID is required.' ) );
        }

        if ( $this->plugin->db->update_user_status( $user_id, 'approved' ) ) {
            wp_send_json_success( array( 'message' => 'User unblocked successfully.' ) );
        }

        wp_send_json_error( array( 'message' => 'Could not unblock user.' ) );
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

    public function save_platform_settings() {
        $this->verify_admin_request();

        $settings = array(
            'platform_name' => sanitize_text_field( $_POST['platform_name'] ?? '' ),
            'notification_email' => sanitize_email( $_POST['notification_email'] ?? '' ),
            'max_students_per_subject' => absint( $_POST['max_students_per_subject'] ?? 50 ),
            'default_class_duration' => absint( $_POST['default_class_duration'] ?? 60 ),
        );

        foreach ( $settings as $key => $value ) {
            $this->plugin->db->set_setting( $key, $value );
        }

        wp_send_json_success( array( 'message' => 'Platform settings saved successfully.' ) );
    }

    private function collect_subject_payload() {
        $teacher_ids = $this->get_teacher_ids_from_request();

        return array(
            'title'       => $this->plugin->helpers->sanitize_text( wp_unslash( $_POST['title'] ?? '' ) ),
            'slug'        => sanitize_title( wp_unslash( $_POST['slug'] ?? $_POST['title'] ?? '' ) ),
            'description' => $this->plugin->helpers->sanitize_textarea( wp_unslash( $_POST['description'] ?? '' ) ),
            'category_id' => absint( $_POST['category_id'] ?? 0 ),
            'teacher_id'  => ! empty( $teacher_ids ) ? absint( $teacher_ids[0] ) : 0,
            'teacher_ids' => $teacher_ids,
            'thumbnail'   => esc_url_raw( wp_unslash( $_POST['thumbnail'] ?? '' ) ),
            'icon'        => $this->plugin->helpers->sanitize_text( wp_unslash( $_POST['icon'] ?? '' ) ),
            'status'      => sanitize_key( wp_unslash( $_POST['status'] ?? 'active' ) ),
            'created_by'  => get_current_user_id(),
        );
    }

    private function get_teacher_ids_from_request() {
        $raw_ids = $_POST['teacher_ids'] ?? array();

        if ( ! is_array( $raw_ids ) ) {
            $raw_ids = explode( ',', sanitize_text_field( wp_unslash( $raw_ids ) ) );
        }

        if ( ! empty( $_POST['teacher_id'] ) ) {
            $raw_ids[] = $_POST['teacher_id'];
        }

        $teacher_ids = array_map( 'absint', $raw_ids );
        $teacher_ids = array_filter( $teacher_ids );

        return array_values( array_unique( $teacher_ids ) );
    }

    private function category_exists( $category_id ) {
        $category_id = absint( $category_id );

        if ( ! $category_id ) {
            return true;
        }

        return (bool) $this->plugin->db->get_subject_category_by_id( $category_id );
    }

    private function teachers_are_approved( $teacher_ids ) {
        if ( empty( $teacher_ids ) ) {
            return true;
        }

        $approved = array_map( 'absint', wp_list_pluck( $this->plugin->db->get_teachers_for_subject_assignment(), 'user_id' ) );

        foreach ( $teacher_ids as $teacher_id ) {
            if ( ! in_array( absint( $teacher_id ), $approved, true ) ) {
                return false;
            }
        }

        return true;
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
