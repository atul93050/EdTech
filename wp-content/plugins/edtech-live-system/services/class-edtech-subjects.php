<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Subjects {
    private $db;
    private $helpers;

    public function __construct( $db, $helpers ) {
        $this->db = $db;
        $this->helpers = $helpers;
    }

    public function create_subject( $title, $description, $teacher_id ) {
        global $wpdb;
        return $wpdb->insert( $wpdb->prefix . 'lms_subjects', array(
            'title' => $this->helpers->sanitize_text( $title ),
            'description' => $this->helpers->sanitize_textarea( $description ),
            'teacher_id' => absint( $teacher_id ),
        ), array( '%s', '%s', '%d' ) );
    }

    public function assign_student_to_subject( $student_id, $subject_id ) {
        global $wpdb;
        return $wpdb->insert( $wpdb->prefix . 'lms_student_subjects', array(
            'student_id' => absint( $student_id ),
            'subject_id' => absint( $subject_id ),
        ), array( '%d', '%d' ) );
    }

    public function get_subjects_by_teacher( $teacher_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}lms_subjects WHERE teacher_id = %d AND status = 'active'", $teacher_id ) );
    }
}
