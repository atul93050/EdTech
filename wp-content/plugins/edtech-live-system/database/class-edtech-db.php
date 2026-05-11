<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! defined( 'LMS_DB_VERSION' ) ) {
    define( 'LMS_DB_VERSION', '1.3.0' );
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
            'lms_subject_categories',
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

            case 'lms_subject_categories':
                return "CREATE TABLE {$this->prefix}lms_subject_categories (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    name VARCHAR(191) NOT NULL,
                    slug VARCHAR(191) NOT NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY slug (slug),
                    KEY status (status)
                ) {$this->charset}";

            case 'lms_subjects':
                return "CREATE TABLE {$this->prefix}lms_subjects (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    title VARCHAR(191) NOT NULL,
                    slug VARCHAR(191) NOT NULL,
                    description TEXT,
                    category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    teacher_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    thumbnail VARCHAR(500) DEFAULT '',
                    icon VARCHAR(255) DEFAULT '',
                    created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY slug (slug),
                    KEY category_id (category_id),
                    KEY teacher_id (teacher_id),
                    KEY status (status)
                ) {$this->charset}";

            case 'lms_student_subjects':
                return "CREATE TABLE {$this->prefix}lms_student_subjects (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    student_id BIGINT(20) UNSIGNED NOT NULL,
                    subject_id BIGINT(20) UNSIGNED NOT NULL,
                    enrollment_status VARCHAR(20) NOT NULL DEFAULT 'active',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY student_subject (student_id, subject_id),
                    KEY student_id (student_id),
                    KEY subject_id (subject_id),
                    KEY enrollment_status (enrollment_status)
                ) {$this->charset}";

            case 'lms_teacher_subjects':
                return "CREATE TABLE {$this->prefix}lms_teacher_subjects (
                    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
                    teacher_id BIGINT(20) UNSIGNED NOT NULL,
                    subject_id BIGINT(20) UNSIGNED NOT NULL,
                    assigned_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    assigned_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY  (id),
                    UNIQUE KEY teacher_subject (teacher_id, subject_id),
                    KEY teacher_id (teacher_id),
                    KEY subject_id (subject_id),
                    KEY assigned_by (assigned_by)
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
        $this->backfill_lms_slugs();

        foreach ( array( 'lms_subject_categories', 'lms_subjects' ) as $table ) {
            $sql = $this->get_table_sql( $table );
            if ( $sql ) {
                dbDelta( $sql );
            }
        }
    }

    private function ensure_auth_columns() {
        $this->ensure_column( 'lms_students', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'approved'" );
        $this->ensure_column( 'lms_students', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_teachers', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'pending'" );
        $this->ensure_column( 'lms_teachers', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_subject_categories', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'active'" );
        $this->ensure_column( 'lms_subject_categories', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_subjects', 'category_id', 'category_id BIGINT(20) UNSIGNED NOT NULL DEFAULT 0' );
        $this->ensure_column( 'lms_subjects', 'slug', "slug VARCHAR(191) NOT NULL DEFAULT ''" );
        $this->ensure_column( 'lms_subjects', 'thumbnail', "thumbnail VARCHAR(500) DEFAULT ''" );
        $this->ensure_column( 'lms_subjects', 'icon', "icon VARCHAR(255) DEFAULT ''" );
        $this->ensure_column( 'lms_subjects', 'created_by', 'created_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0' );
        $this->ensure_column( 'lms_subjects', 'status', "status VARCHAR(20) NOT NULL DEFAULT 'active'" );
        $this->ensure_column( 'lms_subjects', 'updated_at', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_student_subjects', 'enrollment_status', "enrollment_status VARCHAR(20) NOT NULL DEFAULT 'active'" );
        $this->ensure_column( 'lms_student_subjects', 'created_at', 'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' );

        $this->ensure_column( 'lms_teacher_subjects', 'assigned_by', 'assigned_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0' );
        $this->ensure_column( 'lms_teacher_subjects', 'created_at', 'created_at DATETIME DEFAULT CURRENT_TIMESTAMP' );

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

    private function normalize_status( $status, $allowed = array( 'active', 'inactive', 'pending', 'approved', 'blocked', 'suspended', 'rejected', 'scheduled', 'live', 'ended', 'published', 'draft' ), $fallback = 'active' ) {
        $status = sanitize_key( $status );
        return in_array( $status, $allowed, true ) ? $status : $fallback;
    }

    private function make_unique_slug( $table, $slug, $source, $exclude_id = 0 ) {
        global $wpdb;

        $base = sanitize_title( $slug ?: $source );
        if ( '' === $base ) {
            $base = 'item';
        }

        $candidate = $base;
        $suffix    = 2;

        while ( $this->slug_exists( $table, $candidate, $exclude_id ) ) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function slug_exists( $table, $slug, $exclude_id = 0 ) {
        global $wpdb;

        $sql    = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table} WHERE slug = %s";
        $params = array( $slug );

        if ( $exclude_id ) {
            $sql     .= ' AND id <> %d';
            $params[] = absint( $exclude_id );
        }

        return (bool) $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
    }

    private function normalize_user_ids( $ids ) {
        if ( ! is_array( $ids ) ) {
            $ids = array( $ids );
        }

        $ids = array_map( 'absint', $ids );
        $ids = array_filter( $ids );

        return array_values( array_unique( $ids ) );
    }

    private function backfill_lms_slugs() {
        global $wpdb;

        $targets = array(
            'lms_subject_categories' => 'name',
            'lms_subjects'           => 'title',
        );

        foreach ( $targets as $table => $label_column ) {
            if ( ! $this->table_exists( $table ) || ! $this->column_exists( $table, 'slug' ) || ! $this->column_exists( $table, $label_column ) ) {
                continue;
            }

            $rows = $wpdb->get_results( "SELECT id, {$label_column} AS label, slug FROM {$wpdb->prefix}{$table} ORDER BY id ASC" );
            foreach ( $rows as $row ) {
                $current_slug = sanitize_title( $row->slug );
                if ( '' !== $current_slug && ! $this->slug_exists( $table, $current_slug, absint( $row->id ) ) ) {
                    continue;
                }

                $wpdb->update(
                    $wpdb->prefix . $table,
                    array( 'slug' => $this->make_unique_slug( $table, $current_slug, $row->label, absint( $row->id ) ) ),
                    array( 'id' => absint( $row->id ) ),
                    array( '%s' ),
                    array( '%d' )
                );
            }
        }
    }

    public function get_subject_categories( $active_only = true ) {
        global $wpdb;
        $where = $active_only ? "WHERE c.status = 'active'" : '';
        return $wpdb->get_results(
            "SELECT c.*, COALESCE(subject_counts.subjects_count, 0) AS subjects_count
            FROM {$wpdb->prefix}lms_subject_categories c
            LEFT JOIN (
                SELECT category_id, COUNT(*) AS subjects_count
                FROM {$wpdb->prefix}lms_subjects
                GROUP BY category_id
            ) subject_counts ON subject_counts.category_id = c.id
            {$where}
            ORDER BY c.name ASC"
        );
    }

    public function get_subject_category_by_id( $category_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lms_subject_categories WHERE id = %d", $category_id ) );
    }

    public function create_subject_category( $data ) {
        global $wpdb;

        $name = sanitize_text_field( $data['name'] ?? '' );
        if ( '' === $name ) {
            return false;
        }

        $result = $wpdb->insert(
            $wpdb->prefix . 'lms_subject_categories',
            array(
                'name'       => $name,
                'slug'       => $this->make_unique_slug( 'lms_subject_categories', $data['slug'] ?? '', $name ),
                'status'     => $this->normalize_status( $data['status'] ?? 'active', array( 'active', 'inactive' ), 'active' ),
                'created_at' => current_time( 'mysql' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%s', '%s' )
        );

        return false !== $result ? absint( $wpdb->insert_id ) : false;
    }

    public function update_subject_category( $category_id, $data ) {
        global $wpdb;
        $category_id = absint( $category_id );
        $name        = sanitize_text_field( $data['name'] ?? '' );

        if ( ! $category_id || '' === $name ) {
            return false;
        }

        $result = $wpdb->update(
            $wpdb->prefix . 'lms_subject_categories',
            array(
                'name'       => $name,
                'slug'       => $this->make_unique_slug( 'lms_subject_categories', $data['slug'] ?? '', $name, $category_id ),
                'status'     => $this->normalize_status( $data['status'] ?? 'active', array( 'active', 'inactive' ), 'active' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $category_id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        return false !== $result;
    }

    public function delete_subject_category( $category_id ) {
        global $wpdb;
        $category_id = absint( $category_id );

        if ( ! $category_id ) {
            return false;
        }

        $wpdb->update(
            $wpdb->prefix . 'lms_subjects',
            array(
                'category_id' => 0,
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( 'category_id' => $category_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        return false !== $wpdb->delete( $wpdb->prefix . 'lms_subject_categories', array( 'id' => $category_id ), array( '%d' ) );
    }

    public function create_subject( $data ) {
        global $wpdb;

        $title = sanitize_text_field( $data['title'] ?? '' );
        if ( '' === $title ) {
            return false;
        }

        $teacher_ids = $this->normalize_user_ids( $data['teacher_ids'] ?? array_filter( array( $data['teacher_id'] ?? 0 ) ) );
        $teacher_id  = ! empty( $teacher_ids ) ? absint( $teacher_ids[0] ) : 0;

        $result = $wpdb->insert(
            $wpdb->prefix . 'lms_subjects',
            array(
                'title'       => $title,
                'slug'        => $this->make_unique_slug( 'lms_subjects', $data['slug'] ?? '', $title ),
                'description' => sanitize_textarea_field( $data['description'] ?? '' ),
                'category_id' => absint( $data['category_id'] ?? 0 ),
                'teacher_id'  => $teacher_id,
                'thumbnail'   => esc_url_raw( $data['thumbnail'] ?? '' ),
                'icon'        => sanitize_text_field( $data['icon'] ?? '' ),
                'created_by'  => absint( $data['created_by'] ?? get_current_user_id() ),
                'status'      => $this->normalize_status( $data['status'] ?? 'active', array( 'active', 'inactive', 'draft' ), 'active' ),
                'created_at'  => current_time( 'mysql' ),
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        $subject_id = absint( $wpdb->insert_id );
        $this->sync_subject_teachers( $subject_id, $teacher_ids, absint( $data['created_by'] ?? get_current_user_id() ) );

        return $subject_id;
    }

    public function get_subjects_for_admin( $include_inactive = true ) {
        global $wpdb;
        $where = $include_inactive ? '' : "WHERE s.status = 'active'";
        return $wpdb->get_results(
            "SELECT s.*, c.name AS category_name, u.display_name AS creator_name, teacher_map.teacher_names, teacher_map.teacher_ids
            FROM {$wpdb->prefix}lms_subjects s
            LEFT JOIN {$wpdb->prefix}lms_subject_categories c ON s.category_id = c.id
            LEFT JOIN {$wpdb->users} u ON s.created_by = u.ID
            LEFT JOIN (
                SELECT ts.subject_id,
                    GROUP_CONCAT(DISTINCT COALESCE(t.full_name, tu.display_name) ORDER BY COALESCE(t.full_name, tu.display_name) SEPARATOR ', ') AS teacher_names,
                    GROUP_CONCAT(DISTINCT ts.teacher_id ORDER BY ts.teacher_id SEPARATOR ',') AS teacher_ids
                FROM {$wpdb->prefix}lms_teacher_subjects ts
                LEFT JOIN {$wpdb->prefix}lms_teachers t ON ts.teacher_id = t.user_id
                LEFT JOIN {$wpdb->users} tu ON ts.teacher_id = tu.ID
                GROUP BY ts.subject_id
            ) teacher_map ON s.id = teacher_map.subject_id
            {$where}
            ORDER BY s.created_at DESC"
        );
    }

    public function get_subject_by_id( $subject_id ) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT s.*, c.name AS category_name
                FROM {$wpdb->prefix}lms_subjects s
                LEFT JOIN {$wpdb->prefix}lms_subject_categories c ON s.category_id = c.id
                WHERE s.id = %d",
                absint( $subject_id )
            )
        );
    }

    public function update_subject( $subject_id, $data ) {
        global $wpdb;
        $subject_id = absint( $subject_id );
        $title      = sanitize_text_field( $data['title'] ?? '' );

        if ( ! $subject_id || '' === $title ) {
            return false;
        }

        $teacher_ids = $this->normalize_user_ids( $data['teacher_ids'] ?? array_filter( array( $data['teacher_id'] ?? 0 ) ) );
        $teacher_id  = ! empty( $teacher_ids ) ? absint( $teacher_ids[0] ) : 0;

        $result = $wpdb->update(
            $wpdb->prefix . 'lms_subjects',
            array(
                'title'       => $title,
                'slug'        => $this->make_unique_slug( 'lms_subjects', $data['slug'] ?? '', $title, $subject_id ),
                'description' => sanitize_textarea_field( $data['description'] ?? '' ),
                'category_id' => absint( $data['category_id'] ?? 0 ),
                'teacher_id'  => $teacher_id,
                'thumbnail'   => esc_url_raw( $data['thumbnail'] ?? '' ),
                'icon'        => sanitize_text_field( $data['icon'] ?? '' ),
                'status'      => $this->normalize_status( $data['status'] ?? 'active', array( 'active', 'inactive', 'draft' ), 'active' ),
                'updated_at'  => current_time( 'mysql' ),
            ),
            array( 'id' => $subject_id ),
            array( '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false === $result ) {
            return false;
        }

        $this->sync_subject_teachers( $subject_id, $teacher_ids, get_current_user_id() );

        return true;
    }

    public function update_subject_status( $subject_id, $status ) {
        global $wpdb;

        $subject_id = absint( $subject_id );
        if ( ! $subject_id ) {
            return false;
        }

        return false !== $wpdb->update(
            $wpdb->prefix . 'lms_subjects',
            array(
                'status'     => $this->normalize_status( $status, array( 'active', 'inactive', 'draft' ), 'active' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $subject_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    public function update_subject_category_status( $category_id, $status ) {
        global $wpdb;

        $category_id = absint( $category_id );
        if ( ! $category_id ) {
            return false;
        }

        return false !== $wpdb->update(
            $wpdb->prefix . 'lms_subject_categories',
            array(
                'status'     => $this->normalize_status( $status, array( 'active', 'inactive' ), 'active' ),
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $category_id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    public function delete_subject( $subject_id ) {
        global $wpdb;
        $subject_id = absint( $subject_id );

        if ( ! $subject_id ) {
            return false;
        }

        $wpdb->delete( $wpdb->prefix . 'lms_teacher_subjects', array( 'subject_id' => $subject_id ), array( '%d' ) );
        $wpdb->delete( $wpdb->prefix . 'lms_student_subjects', array( 'subject_id' => $subject_id ), array( '%d' ) );

        return false !== $wpdb->delete( $wpdb->prefix . 'lms_subjects', array( 'id' => $subject_id ), array( '%d' ) );
    }

    public function get_all_students( $limit = 150, $offset = 0 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT s.*, u.user_email, u.display_name FROM {$wpdb->prefix}lms_students s
            LEFT JOIN {$wpdb->users} u ON s.user_id = u.ID
            ORDER BY s.created_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ) );
    }

    public function get_all_teachers( $limit = 150, $offset = 0 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT t.*, u.user_email, u.display_name FROM {$wpdb->prefix}lms_teachers t
            LEFT JOIN {$wpdb->users} u ON t.user_id = u.ID
            ORDER BY t.created_at DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ) );
    }

    public function get_enrollments( $limit = 100, $offset = 0 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT ss.id, ss.student_id, ss.subject_id, ss.enrollment_status, COALESCE(ss.created_at, ss.assigned_at) AS created_at, s.title AS subject_title, u.display_name AS student_name, u.user_email AS student_email
            FROM {$wpdb->prefix}lms_student_subjects ss
            LEFT JOIN {$wpdb->prefix}lms_subjects s ON ss.subject_id = s.id
            LEFT JOIN {$wpdb->users} u ON ss.student_id = u.ID
            ORDER BY COALESCE(ss.created_at, ss.assigned_at) DESC LIMIT %d OFFSET %d",
            $limit, $offset
        ) );
    }

    public function get_admin_attendance_summary() {
        global $wpdb;
        return array(
            'records' => absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_attendance" ) ),
            'students' => absint( $wpdb->get_var( "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}lms_attendance" ) ),
            'classes' => absint( $wpdb->get_var( "SELECT COUNT(DISTINCT class_id) FROM {$wpdb->prefix}lms_attendance" ) ),
        );
    }

    public function get_recent_activity( $limit = 8 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lms_activity_logs ORDER BY created_at DESC LIMIT %d", $limit ) );
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

    public function get_all_live_classes( $limit = 50 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT lc.*, s.title AS subject_title, u.display_name AS teacher_name
                FROM {$wpdb->prefix}lms_live_classes lc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON lc.subject_id = s.id
                LEFT JOIN {$wpdb->users} u ON lc.teacher_id = u.ID
                ORDER BY COALESCE(lc.start_time, lc.scheduled_at, lc.created_at) DESC
                LIMIT %d",
                $limit
            )
        );
    }

    public function get_all_recorded_classes( $limit = 50 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT rc.*, s.title AS subject_title, u.display_name AS teacher_name
                FROM {$wpdb->prefix}lms_recorded_classes rc
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON rc.subject_id = s.id
                LEFT JOIN {$wpdb->users} u ON rc.teacher_id = u.ID
                ORDER BY rc.created_at DESC
                LIMIT %d",
                absint( $limit )
            )
        );
    }

    public function get_attendance_records( $limit = 50 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name AS student_name, lc.title AS class_title
                FROM {$wpdb->prefix}lms_attendance a
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                LEFT JOIN {$wpdb->prefix}lms_live_classes lc ON a.class_id = lc.id
                ORDER BY COALESCE(a.attended_at, a.joined_at, a.created_at) DESC
                LIMIT %d",
                absint( $limit )
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

    public function get_student_attendance_records( $student_id, $limit = 50 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, lc.title AS class_title, s.title AS subject_title
                FROM {$wpdb->prefix}lms_attendance a
                LEFT JOIN {$wpdb->prefix}lms_live_classes lc ON a.class_id = lc.id
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON lc.subject_id = s.id
                WHERE a.user_id = %d
                ORDER BY a.attended_at DESC
                LIMIT %d",
                $student_id,
                absint( $limit )
            )
        );
    }

    public function get_student_notifications( $student_id, $limit = 10 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lms_notifications
                WHERE recipient_id = %d OR user_id = %d
                ORDER BY created_at DESC
                LIMIT %d",
                $student_id,
                $student_id,
                absint( $limit )
            )
        );
    }

    public function get_student_tasks( $student_id, $type = 'assignment', $limit = 10 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lms_notifications
                WHERE (recipient_id = %d OR user_id = %d)
                AND type = %s
                ORDER BY created_at DESC
                LIMIT %d",
                $student_id,
                $student_id,
                $type,
                absint( $limit )
            )
        );
    }

    public function get_student_message_threads( $student_id, $limit = 20 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}lms_notifications
                WHERE recipient_id = %d
                AND type IN ('message','chat','announcement')
                ORDER BY created_at DESC
                LIMIT %d",
                $student_id,
                absint( $limit )
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

        $data = array(
            'student_id'         => $student_id,
            'subject_id'         => $subject_id,
            'enrollment_status'  => 'active',
            'created_at'         => current_time( 'mysql' ),
            'assigned_at'        => current_time( 'mysql' ),
        );
        $formats = array(
            'student_id'        => '%d',
            'subject_id'        => '%d',
            'enrollment_status' => '%s',
            'created_at'        => '%s',
            'assigned_at'       => '%s',
        );

        foreach ( array_keys( $data ) as $column ) {
            if ( ! $this->column_exists( 'lms_student_subjects', $column ) ) {
                unset( $data[ $column ], $formats[ $column ] );
            }
        }

        return false !== $wpdb->insert( "{$wpdb->prefix}lms_student_subjects", $data, array_values( $formats ) );
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

        $data = array(
            'teacher_id'  => $teacher_id,
            'subject_id'  => $subject_id,
            'assigned_by' => get_current_user_id(),
            'created_at'  => current_time( 'mysql' ),
            'assigned_at' => current_time( 'mysql' ),
        );
        $formats = array(
            'teacher_id'  => '%d',
            'subject_id'  => '%d',
            'assigned_by' => '%d',
            'created_at'  => '%s',
            'assigned_at' => '%s',
        );

        foreach ( array_keys( $data ) as $column ) {
            if ( ! $this->column_exists( 'lms_teacher_subjects', $column ) ) {
                unset( $data[ $column ], $formats[ $column ] );
            }
        }

        $result = $wpdb->insert( "{$wpdb->prefix}lms_teacher_subjects", $data, array_values( $formats ) );

        if ( false !== $result ) {
            $wpdb->update(
                "{$wpdb->prefix}lms_subjects",
                array(
                    'teacher_id' => $teacher_id,
                    'updated_at' => current_time( 'mysql' ),
                ),
                array( 'id' => $subject_id ),
                array( '%d', '%s' ),
                array( '%d' )
            );
        }

        return false !== $result;
    }

    public function get_subject_teacher_ids( $subject_id ) {
        global $wpdb;

        return array_map(
            'absint',
            $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT teacher_id FROM {$wpdb->prefix}lms_teacher_subjects WHERE subject_id = %d ORDER BY teacher_id ASC",
                    absint( $subject_id )
                )
            )
        );
    }

    public function sync_subject_teachers( $subject_id, $teacher_ids, $assigned_by = 0 ) {
        global $wpdb;

        $subject_id  = absint( $subject_id );
        $teacher_ids = $this->normalize_user_ids( $teacher_ids );

        if ( ! $subject_id ) {
            return false;
        }

        $wpdb->delete( "{$wpdb->prefix}lms_teacher_subjects", array( 'subject_id' => $subject_id ), array( '%d' ) );

        foreach ( $teacher_ids as $teacher_id ) {
            $data = array(
                'teacher_id'  => $teacher_id,
                'subject_id'  => $subject_id,
                'assigned_by' => absint( $assigned_by ),
                'created_at'  => current_time( 'mysql' ),
                'assigned_at' => current_time( 'mysql' ),
            );
            $formats = array(
                'teacher_id'  => '%d',
                'subject_id'  => '%d',
                'assigned_by' => '%d',
                'created_at'  => '%s',
                'assigned_at' => '%s',
            );

            foreach ( array_keys( $data ) as $column ) {
                if ( ! $this->column_exists( 'lms_teacher_subjects', $column ) ) {
                    unset( $data[ $column ], $formats[ $column ] );
                }
            }

            $wpdb->insert( "{$wpdb->prefix}lms_teacher_subjects", $data, array_values( $formats ) );
        }

        $wpdb->update(
            "{$wpdb->prefix}lms_subjects",
            array(
                'teacher_id' => ! empty( $teacher_ids ) ? absint( $teacher_ids[0] ) : 0,
                'updated_at' => current_time( 'mysql' ),
            ),
            array( 'id' => $subject_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );

        return true;
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

    public function count_teacher_students( $teacher_id ) {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(DISTINCT ss.student_id)
                    FROM {$wpdb->prefix}lms_student_subjects ss
                    INNER JOIN {$wpdb->prefix}lms_teacher_subjects ts ON ss.subject_id = ts.subject_id
                    WHERE ts.teacher_id = %d",
                    $teacher_id
                )
            )
        );
    }

    public function get_teacher_students( $teacher_id, $limit = 80 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT DISTINCT u.ID AS user_id, COALESCE(s.full_name, u.display_name) AS full_name, COALESCE(s.email, u.user_email) AS email, s.grade, s.city, s.status, sb.title AS subject_title
                FROM {$wpdb->prefix}lms_student_subjects ss
                INNER JOIN {$wpdb->prefix}lms_teacher_subjects ts ON ss.subject_id = ts.subject_id
                INNER JOIN {$wpdb->prefix}lms_subjects sb ON ss.subject_id = sb.id
                INNER JOIN {$wpdb->users} u ON ss.student_id = u.ID
                LEFT JOIN {$wpdb->prefix}lms_students s ON ss.student_id = s.user_id
                WHERE ts.teacher_id = %d
                ORDER BY COALESCE(s.full_name, u.display_name) ASC
                LIMIT %d",
                $teacher_id,
                absint( $limit )
            )
        );
    }

    public function get_teacher_attendance_records( $teacher_id, $limit = 80 ) {
        global $wpdb;

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT a.*, u.display_name AS student_name, lc.title AS class_title, s.title AS subject_title
                FROM {$wpdb->prefix}lms_attendance a
                INNER JOIN {$wpdb->prefix}lms_live_classes lc ON a.class_id = lc.id
                LEFT JOIN {$wpdb->prefix}lms_subjects s ON lc.subject_id = s.id
                LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
                WHERE lc.teacher_id = %d
                ORDER BY a.attended_at DESC
                LIMIT %d",
                $teacher_id,
                absint( $limit )
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
