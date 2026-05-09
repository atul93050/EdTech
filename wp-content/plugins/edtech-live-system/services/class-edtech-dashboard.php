<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Edtech_Dashboard {
    private $db;
    private $helpers;

    public function __construct( $db, $helpers ) {
        $this->db = $db;
        $this->helpers = $helpers;
    }

    public function render_dashboard() {
        if ( ! is_user_logged_in() ) {
            return '<div class="glass-card p-5 text-center"><h3>Please login to view the dashboard.</h3></div>';
        }
        if ( $this->helpers->is_super_admin() ) {
            return $this->render_admin_dashboard();
        }
        if ( $this->helpers->is_teacher() ) {
            if ( ! $this->db->is_profile_approved( get_current_user_id() ) ) {
                return '<div class="glass-card p-5 text-center"><h3>Your teacher account is pending approval.</h3><p class="text-muted">You will be able to access the dashboard once an admin approves your profile.</p></div>';
            }
            return $this->render_teacher_dashboard();
        }
        if ( $this->helpers->is_student() ) {
            if ( ! $this->db->is_profile_approved( get_current_user_id() ) ) {
                return '<div class="glass-card p-5 text-center"><h3>Your student account is not active.</h3><p class="text-muted">Please contact the platform admin.</p></div>';
            }
            return $this->render_student_dashboard();
        }
        return '<div class="glass-card p-5 text-center"><h3>Role not assigned. Contact admin.</h3></div>';
    }

    private function render_admin_dashboard() {
        ob_start();
        $summary = $this->get_admin_summary();
        $user = wp_get_current_user();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="section-title mb-2">Welcome back, <?php echo esc_html( $user->display_name ); ?></h1>
                    <p class="text-muted mb-0">Manage students, teachers, subjects and live classes from one dashboard</p>
                </div>
                <button type="button" class="btn btn-outline-light align-self-start edtech-logout-button">Logout</button>
            </div>
        </div>
        <div class="row gy-4">
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Students</p>
                            <h2 class="text-white mb-0"><?php echo esc_html( $summary['students'] ); ?></h2>
                        </div>
                        <i class="fa-solid fa-users fa-3x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Teachers</p>
                            <h2 class="text-white mb-0"><?php echo esc_html( $summary['teachers'] ); ?></h2>
                        </div>
                        <i class="fa-solid fa-chalkboard-user fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Subjects</p>
                            <h2 class="text-white mb-0"><?php echo esc_html( $summary['subjects'] ); ?></h2>
                        </div>
                        <i class="fa-solid fa-book fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Live Classes</p>
                            <h2 class="text-white mb-0"><?php echo esc_html( $summary['live_classes'] ); ?></h2>
                        </div>
                        <i class="fa-solid fa-video fa-3x text-danger opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="row gy-4 mt-4">
            <div class="col-12">
                <div class="glass-card p-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                        <div>
                            <h5 class="text-white mb-2">System Controls</h5>
                            <p class="text-muted small mb-0">Run route refresh or platform reinitialize tools when authentication routes or dashboard behavior need to be reset.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-light edtech-refresh-routes-btn" data-action="edtech_refresh_routes">
                                <i class="fa-solid fa-sync-alt me-2"></i>Refresh System Routes
                            </button>
                            <button type="button" class="btn btn-outline-warning edtech-reinit-platform-btn" data-action="edtech_reinitialize_platform">
                                <i class="fa-solid fa-toolbox me-2"></i>Reinitialize Platform
                            </button>
                        </div>
                    </div>
                    <p class="text-muted small mt-3 mb-0">These actions rebuild rewrite rules, reload platform roles, and clear cached route state for the frontend admin experience.</p>
                </div>
            </div>
        </div>
        <div class="row gy-4 mt-4">
            <div class="col-lg-6">
                <div class="glass-card p-4">
                    <h5 class="text-white mb-3"><i class="fa-solid fa-hourglass-end me-2 text-info"></i>Pending Student Registrations</h5>
                    <p class="text-muted small mb-3">Awaiting approval from admin</p>
                    <div class="table-responsive">
                        <table class="table table-borderless text-white mb-0">
                            <tbody>
                            <?php $pending_students = $this->db->get_pending_students();
                            if ( ! empty( $pending_students ) ) {
                                foreach ( $pending_students as $student ) : ?>
                                    <tr class="border-bottom border-light border-opacity-5">
                                        <td class="small"><?php echo esc_html( $student->full_name ); ?></td>
                                        <td class="text-muted small text-end">
                                            <?php echo esc_html( $student->user_email ); ?>
                                            <button type="button" class="btn btn-sm btn-success ms-2 edtech-approve-student" data-user-id="<?php echo esc_attr( $student->user_id ); ?>" data-name="<?php echo esc_attr( $student->full_name ); ?>">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            } else {
                                echo '<tr><td colspan="2" class="text-muted text-center py-3 small">No pending registrations</td></tr>';
                            } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-4">
                    <h5 class="text-white mb-3"><i class="fa-solid fa-hourglass-end me-2 text-warning"></i>Pending Teacher Registrations</h5>
                    <p class="text-muted small mb-3">Awaiting approval from admin</p>
                    <div class="table-responsive">
                        <table class="table table-borderless text-white mb-0">
                            <tbody>
                            <?php $pending_teachers = $this->db->get_pending_teachers();
                            if ( ! empty( $pending_teachers ) ) {
                                foreach ( $pending_teachers as $teacher ) : ?>
                                    <tr class="border-bottom border-light border-opacity-5">
                                        <td class="small"><?php echo esc_html( $teacher->full_name ); ?></td>
                                        <td class="text-muted small text-end">
                                            <?php echo esc_html( $teacher->user_email ); ?>
                                            <button type="button" class="btn btn-sm btn-success ms-2 edtech-approve-teacher" data-user-id="<?php echo esc_attr( $teacher->user_id ); ?>" data-name="<?php echo esc_attr( $teacher->full_name ); ?>">
                                                <i class="fa-solid fa-check"></i> Approve
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach;
                            } else {
                                echo '<tr><td colspan="2" class="text-muted text-center py-3 small">No pending registrations</td></tr>';
                            } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_dashboard() {
        $user_id = get_current_user_id();
        $live_classes = $this->db->get_live_classes_by_teacher( $user_id );
        $user = wp_get_current_user();
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="section-title mb-2">Teacher Dashboard</h1>
                    <p class="text-muted mb-0">Manage your classes, subjects and student interactions</p>
                </div>
                <button type="button" class="btn btn-outline-light align-self-start edtech-logout-button">Logout</button>
            </div>
        </div>
        <div class="row gy-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Classes</p>
                            <h2 class="text-white mb-0"><?php echo count( $live_classes ); ?></h2>
                        </div>
                        <i class="fa-solid fa-book-open fa-3x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Active Sessions</p>
                            <h2 class="text-white mb-0"><?php echo $this->db->count_active_live_classes( $user_id ); ?></h2>
                        </div>
                        <i class="fa-solid fa-video fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Assigned Subjects</p>
                            <h2 class="text-white mb-0"><?php echo $this->db->count_teacher_subjects( $user_id ); ?></h2>
                        </div>
                        <i class="fa-solid fa-graduation-cap fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card p-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-white">My Subjects</h5>
                <button class="btn btn-brand btn-sm" data-bs-toggle="collapse" data-bs-target="#assignSubjectForm" aria-expanded="false">
                    <i class="fa-solid fa-plus me-2"></i>Assign Subject
                </button>
            </div>
            <div class="collapse mb-4" id="assignSubjectForm">
                <div class="card bg-light border">
                    <div class="card-body">
                        <h6 class="text-dark mb-3">Assign Yourself to a Subject</h6>
                        <form class="edtech-assign-teacher-subject-form">
                            <div class="mb-3">
                                <label class="form-label text-dark">Select Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Choose a subject...</option>
                                    <?php
                                    $assigned_subject_ids = array_column($this->db->get_teacher_subjects($user_id), 'id');
                                    foreach ( $this->db->get_subjects() as $subject ) :
                                        if (!in_array($subject->id, $assigned_subject_ids)) :
                                    ?>
                                        <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus me-2"></i>Assign Subject
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless text-white align-middle">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Description</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $this->db->get_teacher_subjects($user_id) as $subject ) : ?>
                            <tr>
                                <td><?php echo esc_html( $subject->title ); ?></td>
                                <td><?php echo esc_html( $subject->description ?: 'No description' ); ?></td>
                                <td><span class="badge bg-success">Assigned</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
            <div class="collapse mb-4" id="createClassForm">
                <div class="card bg-light border">
                    <div class="card-body">
                        <h6 class="text-dark mb-3">Create New Live Class</h6>
                        <form class="edtech-create-live-class-form">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-dark">Class Title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-dark">Subject</label>
                                    <select class="form-select" name="subject_id" required>
                                        <option value="">Choose a subject...</option>
                                        <?php foreach ( $this->db->get_teacher_subjects( $user_id ) as $subject ) : ?>
                                            <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-dark">Meeting Link</label>
                                    <input type="url" class="form-control" name="meeting_link" placeholder="https://zoom.us/..." required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-dark">Start Time</label>
                                    <input type="datetime-local" class="form-control" name="start_time" required>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus me-2"></i>Create Live Class
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless text-white align-middle">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Start Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $live_classes as $class ) : ?>
                            <tr>
                                <td><?php echo esc_html( $class->title ); ?></td>
                                <td><?php echo esc_html( $class->subject_title ); ?></td>
                                <td><span class="badge bg-<?php echo 'live' === $class->live_status ? 'success' : 'secondary'; ?>"><?php echo esc_html( ucfirst( $class->live_status ) ); ?></span></td>
                                <td><?php echo esc_html( $class->start_time ); ?></td>
                                <td>
                                    <?php if ( 'offline' === $class->live_status ) : ?>
                                        <button class="btn btn-sm btn-success edtech-live-toggle" data-action="edtech_mark_live" data-class="<?php echo esc_attr( $class->id ); ?>">
                                            <i class="fa-solid fa-play"></i> Go Live
                                        </button>
                                    <?php else : ?>
                                        <button class="btn btn-sm btn-danger edtech-live-toggle" data-action="edtech_end_live" data-class="<?php echo esc_attr( $class->id ); ?>">
                                            <i class="fa-solid fa-stop"></i> End Class
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_dashboard() {
        $user_id = get_current_user_id();
        $classes = $this->db->get_live_classes_for_student( $user_id );
        $user = wp_get_current_user();
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="section-title mb-2">Welcome, <?php echo esc_html( $user->display_name ); ?></h1>
                    <p class="text-muted mb-0">Join live classes, track your progress and manage your subjects</p>
                </div>
                <button type="button" class="btn btn-outline-light align-self-start edtech-logout-button">Logout</button>
            </div>
        </div>
        <div class="row gy-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Your Subjects</p>
                            <h2 class="text-white mb-0"><?php echo $this->db->count_student_subjects( $user_id ); ?></h2>
                        </div>
                        <i class="fa-solid fa-books fa-3x text-info opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Upcoming Classes</p>
                            <h2 class="text-white mb-0"><?php echo count( $classes ); ?></h2>
                        </div>
                        <i class="fa-solid fa-calendar fa-3x text-success opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Your Attendance</p>
                            <h2 class="text-white mb-0"><?php echo $this->db->count_student_attendance( $user_id ); ?></h2>
                        </div>
                        <i class="fa-solid fa-check-circle fa-3x text-warning opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card p-4 mt-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="text-white">My Subjects</h5>
                <button class="btn btn-brand btn-sm" data-bs-toggle="collapse" data-bs-target="#assignStudentSubjectForm" aria-expanded="false">
                    <i class="fa-solid fa-plus me-2"></i>Enroll in Subject
                </button>
            </div>
            <div class="collapse mb-4" id="assignStudentSubjectForm">
                <div class="card bg-light border">
                    <div class="card-body">
                        <h6 class="text-dark mb-3">Enroll in a Subject</h6>
                        <form class="edtech-assign-student-subject-form">
                            <div class="mb-3">
                                <label class="form-label text-dark">Select Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Choose a subject...</option>
                                    <?php
                                    $enrolled_subject_ids = array_column($this->db->get_student_subjects($user_id), 'subject_id');
                                    foreach ( $this->db->get_subjects() as $subject ) :
                                        if (!in_array($subject->id, $enrolled_subject_ids)) :
                                    ?>
                                        <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?> (<?php echo esc_html( $subject->teacher_name ); ?>)</option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="fa-solid fa-plus me-2"></i>Enroll
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-borderless text-white align-middle">
                    <thead>
                        <tr>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $student_subjects = $this->db->get_student_subjects($user_id);
                        foreach ( $student_subjects as $subject ) : ?>
                            <tr>
                                <td><?php echo esc_html( $subject->title ); ?></td>
                                <td><?php echo esc_html( $subject->teacher_name ); ?></td>
                                <td><span class="badge bg-success">Enrolled</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row gy-4 mt-4">
            <?php foreach ( $classes as $class ) : ?>
                <div class="col-lg-6">
                    <div class="glass-card p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="text-white mb-1"><?php echo esc_html( $class->title ); ?></h5>
                                <small class="text-muted">Subject: <?php echo esc_html( $class->subject_title ); ?></small>
                            </div>
                            <?php if ( 'live' === $class->live_status ) : ?>
                                <span class="badge bg-success">LIVE</span>
                            <?php else : ?>
                                <span class="badge bg-secondary">Scheduled</span>
                            <?php endif; ?>
                        </div>
                        <p class="text-muted mb-3">Teacher: <?php echo esc_html( $class->teacher_name ); ?></p>
                        <?php if ( ! empty( $class->meeting_link ) ) : ?>
                            <a href="<?php echo esc_url( $class->meeting_link ); ?>" target="_blank" class="btn btn-brand btn-sm">Join Now</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_admin_summary() {
        global $wpdb;
        return array(
            'students' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_students" ),
            'teachers' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_teachers" ),
            'subjects' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_subjects" ),
            'live_classes' => $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_live_classes WHERE live_status='live'" ),
        );
    }
}
