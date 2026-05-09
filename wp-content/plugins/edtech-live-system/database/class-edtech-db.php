<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'LMS_DB_VERSION' ) ) {
    define( 'LMS_DB_VERSION', '1.2.0' );
}

class Edtech_DB {
    private $charset;
    private $prefix;
    private $known_columns = array();

    public function __construct() {
        global $wpdb;

        $this->charset = $wpdb->get_charset_collate();
        $this->prefix  = $wpdb->prefix;

        $this->init();
    }

    private function init() {
        $this->create_missing_tables();
        $this->run_migrations();
        $this->initialize_default_data();
        update_option( 'lms_db_version', LMS_DB_VERSION );
    }

    public function create_tables() {
        $this->create_missing_tables();
        $this->run_migrations();
        $this->initialize_default_data();
    }

    private function create_missing_tables() {
        foreach ( $this->get_required_tables() as $table ) {
            if ( ! $this->table_exists( $table ) ) {
                $this->create_table( $table );
            }
        }
    }

    private function get_required_tables() {
        return array(
            'lms_students',
            'lms_teachers',
            'lms_subjects',
            'lms_student_subjects',
            'lms_teacher_subjects',
            'lms_live_classes',
            'lms_recorded_classes',
            'lms_video_history',
            'lms_notifications',
            'lms_attendance',
            'lms_activity_logs',
            'lms_security_log',
            'lms_settings',
        );
    }

    private function table_exists( $table ) {
        global $wpdb;

        $table_name = $this->prefix . $table;
        return $table_name === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
    }

    private function create_table( $table ) {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = $this->get_table_sql( $table );
        if ( $sql ) {
            dbDelta( $sql );
            $this->log_activity( 0, 'table_created', $table );
        }
    }

    private function get_table_sql( $table ) {
        switch ( $table ) {
            case 'lms_students':
                return "CREATE TABLE {$this->prefix}lms_students (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) UNSIGNED NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(30) DEFAULT '',
                    grade VARCHAR(100) DEFAULT '',
                    city VARCHAR(100) DEFAULT '',
                    parent_name VARCHAR(255) DEFAULT '',
                    parent_phone VARCHAR(30) DEFAULT '',
                    bio TEXT,
                    status VARCHAR(20) NOT NULL DEFAULT 'approved',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY unique_user (user_id),
                    KEY status (status),
                    KEY email (email)
                ) {$this->charset}";

            case 'lms_teachers':
                return "CREATE TABLE {$this->prefix}lms_teachers (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) UNSIGNED NOT NULL,
                    full_name VARCHAR(255) NOT NULL,
                    email VARCHAR(255) NOT NULL,
                    phone VARCHAR(30) DEFAULT '',
                    qualification VARCHAR(255) DEFAULT '',
                    experience VARCHAR(255) DEFAULT '',
                    bio TEXT,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY unique_user (user_id),
                    KEY status (status),
                    KEY email (email)
                ) {$this->charset}";

            case 'lms_subjects':
                return "CREATE TABLE {$this->prefix}lms_subjects (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    title VARCHAR(191) NOT NULL,
                    description TEXT,
                    teacher_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY teacher_id (teacher_id),
                    KEY status (status)
                ) {$this->charset}";

            case 'lms_student_subjects':
                return "CREATE TABLE {$this->prefix}lms_student_subjects (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    student_id BIGINT(20) UNSIGNED NOT NULL,
                    subject_id BIGINT(20) UNSIGNED NOT NULL,
                    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY student_subject (student_id, subject_id),
                    KEY student_id (student_id),
                    KEY subject_id (subject_id)
                ) {$this->charset}";

            case 'lms_teacher_subjects':
                return "CREATE TABLE {$this->prefix}lms_teacher_subjects (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    teacher_id BIGINT(20) UNSIGNED NOT NULL,
                    subject_id BIGINT(20) UNSIGNED NOT NULL,
                    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY teacher_subject (teacher_id, subject_id),
                    KEY teacher_id (teacher_id),
                    KEY subject_id (subject_id)
                ) {$this->charset}";

            case 'lms_live_classes':
                return "CREATE TABLE {$this->prefix}lms_live_classes (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    title VARCHAR(191) NOT NULL,
                    description TEXT,
                    subject_id BIGINT(20) UNSIGNED NOT NULL,
                    teacher_id BIGINT(20) UNSIGNED NOT NULL,
                    meeting_link VARCHAR(500) DEFAULT '',
                    meeting_url VARCHAR(500) DEFAULT '',
                    status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
                    live_status VARCHAR(20) NOT NULL DEFAULT 'offline',
                    start_time DATETIME DEFAULT NULL,
                    scheduled_at DATETIME DEFAULT NULL,
                    end_time DATETIME DEFAULT NULL,
                    duration INT DEFAULT 60,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY subject_id (subject_id),
                    KEY teacher_id (teacher_id),
                    KEY live_status (live_status),
                    KEY start_time (start_time)
                ) {$this->charset}";

            case 'lms_recorded_classes':
                return "CREATE TABLE {$this->prefix}lms_recorded_classes (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    teacher_id BIGINT(20) UNSIGNED NOT NULL,
                    subject_id BIGINT(20) UNSIGNED NOT NULL,
                    title VARCHAR(191) NOT NULL,
                    description TEXT,
                    youtube_url VARCHAR(500) DEFAULT '',
                    video_url VARCHAR(500) DEFAULT '',
                    thumbnail VARCHAR(500) DEFAULT '',
                    thumbnail_url VARCHAR(500) DEFAULT '',
                    duration VARCHAR(50) DEFAULT '',
                    tags TEXT,
                    notes_file VARCHAR(500) DEFAULT '',
                    visibility VARCHAR(20) NOT NULL DEFAULT 'published',
                    status VARCHAR(20) NOT NULL DEFAULT 'published',
                    views BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY teacher_id (teacher_id),
                    KEY subject_id (subject_id),
                    KEY visibility (visibility)
                ) {$this->charset}";

            case 'lms_video_history':
                return "CREATE TABLE {$this->prefix}lms_video_history (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    student_id BIGINT(20) UNSIGNED NOT NULL,
                    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    video_id BIGINT(20) UNSIGNED NOT NULL,
                    watched_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    last_watched DATETIME DEFAULT CURRENT_TIMESTAMP,
                    progress INT NOT NULL DEFAULT 0,
                    watch_time INT NOT NULL DEFAULT 0,
                    completed TINYINT(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id),
                    UNIQUE KEY student_video (student_id, video_id),
                    KEY user_id (user_id),
                    KEY video_id (video_id)
                ) {$this->charset}";

            case 'lms_notifications':
                return "CREATE TABLE {$this->prefix}lms_notifications (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    recipient_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    title VARCHAR(255) DEFAULT '',
                    message TEXT,
                    type VARCHAR(50) DEFAULT 'info',
                    is_read TINYINT(1) NOT NULL DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'unread',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY user_id (user_id),
                    KEY recipient_id (recipient_id),
                    KEY status (status)
                ) {$this->charset}";

            case 'lms_attendance':
                return "CREATE TABLE {$this->prefix}lms_attendance (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) UNSIGNED NOT NULL,
                    class_id BIGINT(20) UNSIGNED NOT NULL,
                    class_type VARCHAR(20) NOT NULL DEFAULT 'live',
                    status VARCHAR(20) NOT NULL DEFAULT 'present',
                    joined_at DATETIME DEFAULT NULL,
                    attended_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY user_id (user_id),
                    KEY class_id (class_id)
                ) {$this->charset}";

            case 'lms_activity_logs':
                return "CREATE TABLE {$this->prefix}lms_activity_logs (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    action VARCHAR(191) NOT NULL,
                    details LONGTEXT,
                    context LONGTEXT,
                    ip_address VARCHAR(45) DEFAULT '',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY user_id (user_id),
                    KEY action (action),
                    KEY created_at (created_at)
                ) {$this->charset}";

            case 'lms_security_log':
                return "CREATE TABLE {$this->prefix}lms_security_log (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    event VARCHAR(100) NOT NULL,
                    data LONGTEXT,
                    ip_address VARCHAR(45) DEFAULT '',
                    user_agent TEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    KEY event (event),
                    KEY user_id (user_id),
                    KEY created_at (created_at)
                ) {$this->charset}";

            case 'lms_settings':
                return "CREATE TABLE {$this->prefix}lms_settings (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    setting_key VARCHAR(191) NOT NULL,
                    setting_value LONGTEXT,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY setting_key (setting_key)
                ) {$this->charset}";
        }

        return '';
    }

    private function run_migrations() {
        foreach ( $this->get_required_tables() as $table ) {
            if ( ! $this->table_exists( $table ) ) {
                continue;
            }

            $sql = $this->get_table_sql( $table );
            if ( $sql ) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta( $sql );
            }
        }

        $this->ensure_auth_columns();
    }

    private function ensure_auth_columns() {
        $this->ensure_column( 'lms_students', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'approved'" );
        $this->ensure_column( 'lms_students', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_teachers', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'pending'" );
        $this->ensure_column( 'lms_teachers', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_subjects', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'active'" );
        $this->ensure_column( 'lms_subjects', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_live_classes', 'meeting_link', "meeting_link VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_live_classes', 'meeting_url', "meeting_url VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_live_classes', 'live_status', "live_status VARCHAR(20) NOT NULL DEFAULT 'offline'" );
        $this->ensure_column( 'lms_live_classes', 'start_time', 'start_time DATETIME DEFAULT NULL' );
        $this->ensure_column( 'lms_live_classes', 'scheduled_at', 'scheduled_at DATETIME DEFAULT NULL' );
        $this->ensure_column( 'lms_live_classes', 'end_time', 'end_time DATETIME DEFAULT NULL' );
        $this->ensure_column( 'lms_live_classes', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_recorded_classes', 'youtube_url', "youtube_url VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_recorded_classes', 'video_url', "video_url VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_recorded_classes', 'thumbnail', "thumbnail VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_recorded_classes', 'thumbnail_url', "thumbnail_url VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_recorded_classes', 'notes_file', "notes_file VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_recorded_classes', 'visibility', "visibility VARCHAR(20) NOT NULL DEFAULT 'published'" );
        $this->ensure_column( 'lms_recorded_classes', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_video_history', 'student_id', 'student_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0' );
        $this->ensure_column( 'lms_video_history', 'user_id', 'user_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0' );
        $this->ensure_column( 'lms_video_history', 'watched_at', 'watched_at DATETIME DEFAULT CURRENT_TIMESTAMP' );
        $this->ensure_column( 'lms_video_history', 'progress', 'progress INT NOT NULL DEFAULT 0' );

        $this->ensure_column( 'lms_activity_logs', 'details', 'details LONGTEXT' );
        $this->ensure_column( 'lms_activity_logs', 'context', 'context LONGTEXT' );
        $this->ensure_column( 'lms_activity_logs', 'ip_address', "ip_address VARCHAR(45) DEFAULT ''" );

        $this->ensure_column( 'lms_settings', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->modify_column_if_exists( 'lms_students', "status VARCHAR(20) NOT NULL DEFAULT 'approved'" );
        $this->modify_column_if_exists( 'lms_teachers', "status VARCHAR(20) NOT NULL DEFAULT 'pending'" );
        $this->modify_column_if_exists( 'lms_live_classes', 'scheduled_at DATETIME DEFAULT NULL' );
        $this->modify_column_if_exists( 'lms_recorded_classes', "video_url VARCHAR(500) DEFAULT ''" );
    }

    private function ensure_column( $table, $column, $definition ) {
        global $wpdb;

        if ( ! $this->table_exists( $table ) || $this->column_exists( $table, $column ) ) {
            return;
        }

        $wpdb->query( 'ALTER TABLE ' . $this->table_name( $table ) . ' ADD COLUMN ' . $definition );
        unset( $this->known_columns[ $table ] );
    }

    private function modify_column_if_exists( $table, $definition ) {
        global $wpdb;

        $column = trim( strtok( $definition, ' ' ) );
        if ( ! $this->table_exists( $table ) || ! $this->column_exists( $table, $column ) ) {
            return;
        }

        $wpdb->query( 'ALTER TABLE ' . $this->table_name( $table ) . ' MODIFY COLUMN ' . $definition );
        unset( $this->known_columns[ $table ] );
    }

    private function table_name( $table ) {
        return '`' . esc_sql( $this->prefix . $table ) . '`';
    }

    private function column_exists( $table, $column ) {
        return in_array( $column, $this->get_columns( $table ), true );
    }

    private function get_columns( $table ) {
        global $wpdb;

        if ( isset( $this->known_columns[ $table ] ) ) {
            return $this->known_columns[ $table ];
        }

        if ( ! $this->table_exists( $table ) ) {
            return array();
        }

        $rows = $wpdb->get_results( 'SHOW COLUMNS FROM ' . $this->table_name( $table ), ARRAY_A );
        $this->known_columns[ $table ] = wp_list_pluck( $rows, 'Field' );

        return $this->known_columns[ $table ];
    }

    private function initialize_default_data() {
        $defaults = array(
            'platform_name'            => get_bloginfo( 'name' ) ?: 'EdTech Learning Platform',
            'notification_email'       => get_option( 'admin_email' ),
            'max_students_per_subject' => 50,
            'default_class_duration'   => 60,
            'timezone'                 => wp_timezone_string(),
            'max_login_attempts'       => 5,
            'password_min_length'      => 8,
        );

        foreach ( $defaults as $key => $value ) {
            if ( null === $this->get_setting( $key, null ) ) {
                $this->set_setting( $key, $value );
            }
        }
    }

    public function get_live_classes_by_teacher( $teacher_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT lc.*, s.title AS subject_title
                FROM {$wpdb->prefix}lms_live_classes lc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON lc.subject_id = s.id
                WHERE lc.teacher_id = %d
                ORDER BY COALESCE(lc.start_time, lc.scheduled_at, lc.created_at) DESC
                LIMIT 20",
                $teacher_id
            )
        );
    }

    public function count_active_live_classes( $teacher_id ) {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}lms_live_classes WHERE teacher_id = %d AND live_status = 'live'",
                    $teacher_id
                )
            )
        );
    }

    public function count_teacher_subjects( $teacher_id ) {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT subject_id)
                    FROM {$wpdb->prefix}lms_teacher_subjects
                    WHERE teacher_id = %d",
                    $teacher_id
                )
            )
        );
    }

    public function get_live_classes_for_student( $student_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT lc.*, s.title AS subject_title, u.display_name AS teacher_name
                FROM {$wpdb->prefix}lms_live_classes lc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON lc.subject_id = s.id
                LEFT JOIN {$wpdb->users} u ON lc.teacher_id = u.ID
                INNER JOIN {$wpdb->prefix}lms_student_subjects ss ON ss.subject_id = lc.subject_id
                WHERE ss.student_id = %d AND lc.status IN ('scheduled','running','live') AND lc.live_status IN ('live','offline','scheduled')
                ORDER BY COALESCE(lc.start_time, lc.scheduled_at, lc.created_at) ASC
                LIMIT 12",
                $student_id
            )
        );
    }

    public function count_student_subjects( $student_id ) {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}lms_student_subjects WHERE student_id = %d",
                    $student_id
                )
            )
        );
    }

    public function count_student_attendance( $student_id ) {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}lms_attendance WHERE user_id = %d",
                    $student_id
                )
            )
        );
    }

    public function get_pending_students() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT s.*, u.user_email
            FROM {$wpdb->prefix}lms_students s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.status = 'pending'
            ORDER BY s.created_at DESC
            LIMIT 25"
        );
    }

    public function get_pending_teachers() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT t.*, u.user_email
            FROM {$wpdb->prefix}lms_teachers t
            LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
            WHERE t.status = 'pending'
            ORDER BY t.created_at DESC
            LIMIT 25"
        );
    }

    public function get_subjects( $limit = 100 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, COALESCE(u.display_name, '') AS teacher_name
                FROM {$wpdb->prefix}lms_subjects s
                LEFT JOIN {$wpdb->users} u ON s.teacher_id = u.ID
                WHERE s.status IN ('active','approved')
                ORDER BY s.title ASC
                LIMIT %d",
                absint( $limit )
            )
        );
    }

    public function approve_student( $user_id ) {
        return $this->update_profile_status( $user_id, 'student', 'approved' );
    }

    public function approve_teacher( $user_id ) {
        return $this->update_profile_status( $user_id, 'teacher', 'approved' );
    }

    public function reject_teacher( $user_id, $reason = '' ) {
        $result = $this->update_profile_status( $user_id, 'teacher', 'rejected' );
        if ( $result ) {
            $this->log_security_event( $user_id, 'teacher_rejected', array( 'reason' => $reason ) );
        }
        return $result;
    }

    public function update_user_status( $user_id, $status ) {
        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            return false;
        }

        if ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
            return $this->update_profile_status( $user_id, 'teacher', $status );
        }

        if ( in_array( 'edtech_student', (array) $user->roles, true ) ) {
            return $this->update_profile_status( $user_id, 'student', $status );
        }

        return false;
    }

    private function update_profile_status( $user_id, $type, $status ) {
        global $wpdb;

        $table  = 'teacher' === $type ? "{$wpdb->prefix}lms_teachers" : "{$wpdb->prefix}lms_students";
        $result = $wpdb->update(
            $table,
            array(
                'status'     => $status,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'user_id' => absint( $user_id ) ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $result ) {
            update_user_meta( $user_id, 'edtech_status', $status );
            $this->log_activity( $user_id, $type . '_status_updated', $status );
            return true;
        }

        return false;
    }

    public function assign_subject_to_student( $student_id, $subject_id ) {
        global $wpdb;

        $student_id = absint( $student_id );
        $subject_id = absint( $subject_id );

        if ( ! $student_id || ! $subject_id ) {
            return false;
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}lms_student_subjects WHERE student_id = %d AND subject_id = %d",
                $student_id,
                $subject_id
            )
        );

        if ( $exists ) {
            return true;
        }

        return false !== $wpdb->insert(
            "{$wpdb->prefix}lms_student_subjects",
            array(
                'student_id'  => $student_id,
                'subject_id'  => $subject_id,
                'assigned_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s' )
        );
    }

    public function assign_subject_to_teacher( $teacher_id, $subject_id ) {
        global $wpdb;

        $teacher_id = absint( $teacher_id );
        $subject_id = absint( $subject_id );

        if ( ! $teacher_id || ! $subject_id ) {
            return false;
        }

        $exists = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}lms_teacher_subjects WHERE teacher_id = %d AND subject_id = %d",
                $teacher_id,
                $subject_id
            )
        );

        if ( $exists ) {
            return true;
        }

        return false !== $wpdb->insert(
            "{$wpdb->prefix}lms_teacher_subjects",
            array(
                'teacher_id'  => $teacher_id,
                'subject_id'  => $subject_id,
                'assigned_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s' )
        );
    }

    public function get_students_for_subject_assignment() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT s.id, s.user_id, s.full_name, u.user_email
            FROM {$wpdb->prefix}lms_students s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            WHERE s.status IN ('approved','active')
            ORDER BY s.full_name ASC"
        );
    }

    public function get_teachers_for_subject_assignment() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT t.id, t.user_id, t.full_name, u.user_email
            FROM {$wpdb->prefix}lms_teachers t
            LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
            WHERE t.status IN ('approved','active')
            ORDER BY t.full_name ASC"
        );
    }

    public function get_teacher_subjects( $teacher_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, ts.subject_id
                FROM {$wpdb->prefix}lms_subjects s
                INNER JOIN {$wpdb->prefix}lms_teacher_subjects ts ON s.id = ts.subject_id
                WHERE ts.teacher_id = %d AND s.status IN ('active','approved')
                ORDER BY s.title ASC",
                $teacher_id
            )
        );
    }

    public function get_student_subjects( $student_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT s.*, ss.subject_id, COALESCE(u.display_name, '') AS teacher_name
                FROM {$wpdb->prefix}lms_subjects s
                INNER JOIN {$wpdb->prefix}lms_student_subjects ss ON s.id = ss.subject_id
                LEFT JOIN {$wpdb->prefix}lms_teacher_subjects ts ON s.id = ts.subject_id
                LEFT JOIN {$wpdb->users} u ON ts.teacher_id = u.ID
                WHERE ss.student_id = %d AND s.status IN ('active','approved')
                GROUP BY s.id
                ORDER BY s.title ASC",
                $student_id
            )
        );
    }

    public function get_recorded_videos_by_teacher( $teacher_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rc.*, s.title AS subject_title
                FROM {$wpdb->prefix}lms_recorded_classes rc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON rc.subject_id = s.id
                WHERE rc.teacher_id = %d
                ORDER BY rc.created_at DESC",
                $teacher_id
            )
        );
    }

    public function get_recorded_video_by_id( $video_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT rc.*, s.title AS subject_title, u.display_name AS teacher_name
                FROM {$wpdb->prefix}lms_recorded_classes rc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON rc.subject_id = s.id
                LEFT JOIN {$wpdb->users} u ON rc.teacher_id = u.ID
                WHERE rc.id = %d
                LIMIT 1",
                $video_id
            )
        );
    }

    public function get_recorded_videos_for_student( $student_id, $args = array() ) {
        global $wpdb;

        $defaults = array(
            'search'     => '',
            'subject_id' => 0,
            'teacher_id' => 0,
            'tags'       => '',
            'order'      => 'rc.created_at DESC',
            'limit'      => 12,
            'offset'     => 0,
        );
        $args = wp_parse_args( $args, $defaults );

        $allowed_orders = array( 'rc.created_at DESC', 'rc.created_at ASC', 'rc.title ASC', 'rc.title DESC' );
        $order          = in_array( $args['order'], $allowed_orders, true ) ? $args['order'] : 'rc.created_at DESC';
        $where          = "WHERE ss.student_id = %d AND rc.visibility = 'published'";
        $params         = array( absint( $student_id ) );

        if ( $args['subject_id'] ) {
            $where   .= ' AND rc.subject_id = %d';
            $params[] = absint( $args['subject_id'] );
        }

        if ( $args['teacher_id'] ) {
            $where   .= ' AND rc.teacher_id = %d';
            $params[] = absint( $args['teacher_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $term     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where   .= ' AND (rc.title LIKE %s OR rc.tags LIKE %s OR s.title LIKE %s OR u.display_name LIKE %s)';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if ( ! empty( $args['tags'] ) ) {
            $where   .= ' AND rc.tags LIKE %s';
            $params[] = '%' . $wpdb->esc_like( sanitize_text_field( $args['tags'] ) ) . '%';
        }

        $params[] = absint( $args['limit'] );
        $params[] = absint( $args['offset'] );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rc.*, s.title AS subject_title, u.display_name AS teacher_name
                FROM {$wpdb->prefix}lms_recorded_classes rc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON rc.subject_id = s.id
                LEFT JOIN {$wpdb->users} u ON rc.teacher_id = u.ID
                INNER JOIN {$wpdb->prefix}lms_student_subjects ss ON ss.subject_id = rc.subject_id
                {$where}
                ORDER BY {$order}
                LIMIT %d OFFSET %d",
                $params
            )
        );
    }

    public function get_related_recorded_videos( $video_id, $subject_id ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rc.*, s.title AS subject_title
                FROM {$wpdb->prefix}lms_recorded_classes rc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON rc.subject_id = s.id
                WHERE rc.subject_id = %d AND rc.id <> %d AND rc.visibility = 'published'
                ORDER BY rc.created_at DESC
                LIMIT 5",
                $subject_id,
                $video_id
            )
        );
    }

    public function get_recent_watch_history( $student_id, $limit = 6 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT vh.*, rc.title, rc.thumbnail, rc.duration, rc.subject_id, rc.teacher_id, rc.notes_file, rc.youtube_url, rc.visibility, s.title AS subject_title
                FROM {$wpdb->prefix}lms_video_history vh
                LEFT JOIN {$wpdb->prefix}lms_recorded_classes rc ON rc.id = vh.video_id
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON rc.subject_id = s.id
                WHERE vh.student_id = %d AND rc.visibility = 'published'
                ORDER BY vh.watched_at DESC
                LIMIT %d",
                $student_id,
                $limit
            )
        );
    }

    public function get_video_history_item( $student_id, $video_id ) {
        global $wpdb;

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lms_video_history WHERE student_id = %d AND video_id = %d ORDER BY watched_at DESC LIMIT 1",
                $student_id,
                $video_id
            )
        );
    }

    public function create_recorded_video( $data ) {
        global $wpdb;

        return false !== $wpdb->insert(
            "{$wpdb->prefix}lms_recorded_classes",
            array(
                'teacher_id'    => absint( $data['teacher_id'] ),
                'subject_id'    => absint( $data['subject_id'] ),
                'title'         => sanitize_text_field( $data['title'] ),
                'description'   => sanitize_textarea_field( $data['description'] ?? '' ),
                'youtube_url'   => esc_url_raw( $data['youtube_url'] ),
                'video_url'     => esc_url_raw( $data['youtube_url'] ),
                'thumbnail'     => esc_url_raw( $data['thumbnail'] ?? '' ),
                'thumbnail_url' => esc_url_raw( $data['thumbnail'] ?? '' ),
                'duration'      => sanitize_text_field( $data['duration'] ?? '' ),
                'tags'          => sanitize_text_field( $data['tags'] ?? '' ),
                'notes_file'    => esc_url_raw( $data['notes_file'] ?? '' ),
                'visibility'    => sanitize_text_field( $data['visibility'] ?? 'published' ),
                'status'        => sanitize_text_field( $data['visibility'] ?? 'published' ),
                'created_at'    => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function update_recorded_video( $video_id, $data ) {
        global $wpdb;

        $thumbnail = esc_url_raw( $data['thumbnail'] ?? '' );
        $notes     = esc_url_raw( $data['notes_file'] ?? '' );
        $update    = array(
            'subject_id'  => absint( $data['subject_id'] ),
            'title'       => sanitize_text_field( $data['title'] ),
            'description' => sanitize_textarea_field( $data['description'] ?? '' ),
            'youtube_url' => esc_url_raw( $data['youtube_url'] ),
            'video_url'   => esc_url_raw( $data['youtube_url'] ),
            'duration'    => sanitize_text_field( $data['duration'] ?? '' ),
            'tags'        => sanitize_text_field( $data['tags'] ?? '' ),
            'visibility'  => sanitize_text_field( $data['visibility'] ?? 'published' ),
            'status'      => sanitize_text_field( $data['visibility'] ?? 'published' ),
            'updated_at'  => current_time( 'mysql' ),
        );
        $formats   = array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' );

        if ( $thumbnail ) {
            $update['thumbnail']     = $thumbnail;
            $update['thumbnail_url'] = $thumbnail;
            $formats[]               = '%s';
            $formats[]               = '%s';
        }

        if ( $notes ) {
            $update['notes_file'] = $notes;
            $formats[]            = '%s';
        }

        return false !== $wpdb->update(
            "{$wpdb->prefix}lms_recorded_classes",
            $update,
            array( 'id' => absint( $video_id ) ),
            $formats,
            array( '%d' )
        );
    }

    public function delete_recorded_video( $video_id ) {
        global $wpdb;

        return false !== $wpdb->delete( "{$wpdb->prefix}lms_recorded_classes", array( 'id' => absint( $video_id ) ), array( '%d' ) );
    }

    public function record_video_history( $student_id, $video_id, $progress = 0 ) {
        global $wpdb;

        $student_id = absint( $student_id );
        $video_id   = absint( $video_id );
        $progress   = min( 100, max( 0, absint( $progress ) ) );

        $existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}lms_video_history WHERE student_id = %d AND video_id = %d LIMIT 1",
                $student_id,
                $video_id
            )
        );

        if ( $existing ) {
            return false !== $wpdb->update(
                "{$wpdb->prefix}lms_video_history",
                array(
                    'progress'     => $progress,
                    'watched_at'   => current_time( 'mysql' ),
                    'last_watched' => current_time( 'mysql' ),
                    'user_id'      => $student_id,
                ),
                array( 'id' => absint( $existing ) ),
                array( '%d', '%s', '%s', '%d' ),
                array( '%d' )
            );
        }

        return false !== $wpdb->insert(
            "{$wpdb->prefix}lms_video_history",
            array(
                'student_id'   => $student_id,
                'user_id'      => $student_id,
                'video_id'     => $video_id,
                'progress'     => $progress,
                'watched_at'   => current_time( 'mysql' ),
                'last_watched' => current_time( 'mysql' ),
            ),
            array( '%d', '%d', '%d', '%d', '%s', '%s' )
        );
    }

    public function get_active_teachers() {
        global $wpdb;

        return $wpdb->get_results(
            "SELECT t.id, t.user_id, t.full_name
            FROM {$wpdb->prefix}lms_teachers t
            WHERE t.status IN ('approved','active')
            ORDER BY t.full_name ASC"
        );
    }

    public function create_student_profile( $user_id, $data ) {
        global $wpdb;

        $result = $wpdb->insert(
            "{$wpdb->prefix}lms_students",
            array(
                'user_id'      => absint( $user_id ),
                'full_name'    => sanitize_text_field( $data['full_name'] ),
                'email'        => sanitize_email( $data['email'] ),
                'phone'        => sanitize_text_field( $data['phone'] ?? '' ),
                'grade'        => sanitize_text_field( $data['grade'] ?? '' ),
                'city'         => sanitize_text_field( $data['city'] ?? '' ),
                'parent_name'  => sanitize_text_field( $data['parent_name'] ?? '' ),
                'parent_phone' => sanitize_text_field( $data['parent_phone'] ?? '' ),
                'bio'          => sanitize_textarea_field( $data['bio'] ?? '' ),
                'status'       => 'approved',
                'created_at'   => current_time( 'mysql' ),
                'updated_at'   => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            update_user_meta( $user_id, 'edtech_status', 'approved' );
            $this->log_activity( $user_id, 'student_profile_created', 'Student profile created' );
        }

        return false !== $result;
    }

    public function create_teacher_profile( $user_id, $data ) {
        global $wpdb;

        $result = $wpdb->insert(
            "{$wpdb->prefix}lms_teachers",
            array(
                'user_id'       => absint( $user_id ),
                'full_name'     => sanitize_text_field( $data['full_name'] ),
                'email'         => sanitize_email( $data['email'] ),
                'phone'         => sanitize_text_field( $data['phone'] ?? '' ),
                'qualification' => sanitize_text_field( $data['qualification'] ?? '' ),
                'experience'    => sanitize_text_field( $data['experience'] ?? '' ),
                'bio'           => sanitize_textarea_field( $data['bio'] ?? '' ),
                'status'        => 'pending',
                'created_at'    => current_time( 'mysql' ),
                'updated_at'    => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( $result ) {
            update_user_meta( $user_id, 'edtech_status', 'pending' );
            $this->log_activity( $user_id, 'teacher_profile_created', 'Teacher profile created - pending approval' );
        }

        return false !== $result;
    }

    public function get_user_profile( $user_id, $type = null ) {
        global $wpdb;

        if ( ! $type ) {
            $user = get_user_by( 'id', $user_id );
            if ( ! $user ) {
                return false;
            }

            if ( in_array( 'edtech_student', (array) $user->roles, true ) ) {
                $type = 'student';
            } elseif ( in_array( 'edtech_teacher', (array) $user->roles, true ) ) {
                $type = 'teacher';
            } elseif ( in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true ) ) {
                return (object) array( 'status' => 'approved' );
            } else {
                return false;
            }
        }

        if ( ! in_array( $type, array( 'student', 'teacher' ), true ) ) {
            return false;
        }

        $table = 'student' === $type ? "{$wpdb->prefix}lms_students" : "{$wpdb->prefix}lms_teachers";
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", absint( $user_id ) ) );
    }

    public function is_profile_approved( $user_id ) {
        $profile = $this->get_user_profile( $user_id );
        return $profile && in_array( $profile->status, array( 'approved', 'active' ), true );
    }

    public function log_security_event( $user_id, $event, $data = array() ) {
        global $wpdb;

        if ( ! $this->table_exists( 'lms_security_log' ) ) {
            return false;
        }

        return false !== $wpdb->insert(
            "{$wpdb->prefix}lms_security_log",
            array(
                'user_id'    => absint( $user_id ),
                'event'      => sanitize_key( $event ),
                'data'       => wp_json_encode( $data ),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '',
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    public function check_rate_limit( $action, $identifier, $limit = 5, $window = 3600 ) {
        $transient_key = 'edtech_rate_limit_' . md5( sanitize_key( $action ) . '_' . sanitize_text_field( $identifier ) );
        $attempts      = absint( get_transient( $transient_key ) );

        if ( $attempts >= absint( $limit ) ) {
            return false;
        }

        set_transient( $transient_key, $attempts + 1, absint( $window ) );
        return true;
    }

    public function clear_rate_limit( $action, $identifier ) {
        delete_transient( 'edtech_rate_limit_' . md5( sanitize_key( $action ) . '_' . sanitize_text_field( $identifier ) ) );
    }

    public function get_failed_logins_last_hour() {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}lms_security_log WHERE event = 'failed_login' AND created_at > %s",
                    gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
                )
            )
        );
    }

    public function get_client_ip() {
        $headers = array(
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        );

        foreach ( $headers as $header ) {
            if ( empty( $_SERVER[ $header ] ) ) {
                continue;
            }

            $value = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
            $ip    = trim( explode( ',', $value )[0] );

            if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                return $ip;
            }
        }

        return '127.0.0.1';
    }

    public function set_setting( $key, $value ) {
        global $wpdb;

        return false !== $wpdb->replace(
            "{$wpdb->prefix}lms_settings",
            array(
                'setting_key'   => sanitize_key( $key ),
                'setting_value' => maybe_serialize( $value ),
                'updated_at'    => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s' )
        );
    }

    public function get_setting( $key, $default = null ) {
        global $wpdb;

        if ( ! $this->table_exists( 'lms_settings' ) ) {
            return $default;
        }

        $value = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT setting_value FROM {$wpdb->prefix}lms_settings WHERE setting_key = %s",
                sanitize_key( $key )
            )
        );

        return null !== $value ? maybe_unserialize( $value ) : $default;
    }

    public function log_activity( $user_id, $action = '', $details = '' ) {
        global $wpdb;

        if ( ! $this->table_exists( 'lms_activity_logs' ) ) {
            return false;
        }

        $data = array(
            'user_id'    => absint( $user_id ),
            'action'     => sanitize_key( $action ?: 'system' ),
            'details'    => is_scalar( $details ) ? sanitize_textarea_field( (string) $details ) : wp_json_encode( $details ),
            'context'    => is_scalar( $details ) ? sanitize_textarea_field( (string) $details ) : wp_json_encode( $details ),
            'ip_address' => $this->get_client_ip(),
            'created_at' => current_time( 'mysql' ),
        );
        $formats = array(
            'user_id'    => '%d',
            'action'     => '%s',
            'details'    => '%s',
            'context'    => '%s',
            'ip_address' => '%s',
            'created_at' => '%s',
        );
        $columns = $this->get_columns( 'lms_activity_logs' );

        foreach ( array_keys( $data ) as $column ) {
            if ( ! in_array( $column, $columns, true ) ) {
                unset( $data[ $column ], $formats[ $column ] );
            }
        }

        if ( empty( $data ) ) {
            return false;
        }

        return false !== $wpdb->insert(
            "{$wpdb->prefix}lms_activity_logs",
            $data,
            array_values( $formats )
        );
    }
}
