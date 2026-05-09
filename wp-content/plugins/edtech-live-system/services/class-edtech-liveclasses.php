<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Live_Classes {
    private $db;
    private $helpers;

    public function __construct( $db, $helpers ) {
        $this->db = $db;
        $this->helpers = $helpers;
    }

    public function create_live_class( $title, $subject_id, $teacher_id, $meeting_link, $start_time ) {
        global $wpdb;
        return $wpdb->insert( $wpdb->prefix . 'lms_live_classes', array(
            'title' => $this->helpers->sanitize_text( $title ),
            'subject_id' => absint( $subject_id ),
            'teacher_id' => absint( $teacher_id ),
            'meeting_link' => esc_url_raw( $meeting_link ),
            'meeting_url' => esc_url_raw( $meeting_link ),
            'status' => 'scheduled',
            'live_status' => 'offline',
            'start_time' => sanitize_text_field( $start_time ),
            'scheduled_at' => sanitize_text_field( $start_time ),
        ), array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' ) );
    }

    public function mark_live( $class_id ) {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . 'lms_live_classes', array(
            'live_status' => 'live',
            'status' => 'running',
            'start_time' => current_time( 'mysql' ),
        ), array( 'id' => absint( $class_id ) ), array( '%s', '%s', '%s' ), array( '%d' ) );
    }

    public function end_live( $class_id ) {
        global $wpdb;
        return $wpdb->update( $wpdb->prefix . 'lms_live_classes', array(
            'live_status' => 'offline',
            'status' => 'completed',
            'end_time' => current_time( 'mysql' ),
        ), array( 'id' => absint( $class_id ) ), array( '%s', '%s', '%s' ), array( '%d' ) );
    }
}
