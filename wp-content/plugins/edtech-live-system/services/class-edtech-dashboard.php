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

    public function render_dashboard( $atts = array() ) {
        $atts = shortcode_atts( array(
            'view' => 'overview',
        ), $atts );

        if ( ! is_user_logged_in() ) {
            return '<div class="glass-card p-5 text-center"><h3>Please login to view the dashboard.</h3></div>';
        }

        if ( $this->helpers->is_super_admin() ) {
            return $this->render_admin_dashboard( $atts['view'] );
        }

        if ( $this->helpers->is_teacher() ) {
            if ( ! $this->db->is_profile_approved( get_current_user_id() ) ) {
                return '<div class="glass-card p-5 text-center"><h3>Your teacher account is pending approval.</h3><p class="text-muted">You will be able to access the dashboard once an admin approves your profile.</p></div>';
            }
            return $this->render_teacher_dashboard( $atts['view'] );
        }

        if ( $this->helpers->is_student() ) {
            if ( ! $this->db->is_profile_approved( get_current_user_id() ) ) {
                return '<div class="glass-card p-5 text-center"><h3>Your student account is not active.</h3><p class="text-muted">Please contact the platform admin.</p></div>';
            }
            return $this->render_student_dashboard( $atts['view'] );
        }

        return '<div class="glass-card p-5 text-center"><h3>Role not assigned. Contact admin.</h3></div>';
    }

    private function render_admin_dashboard( $view = 'overview' ) {
        switch ( $view ) {
            case 'overview':
                return $this->render_admin_overview();
            case 'subjects':
                return $this->render_admin_subjects();
            case 'categories':
                return $this->render_admin_categories();
            case 'students':
                return $this->render_admin_students();
            case 'teachers':
                return $this->render_admin_teachers();
            case 'enrollments':
                return $this->render_admin_enrollments();
            case 'live-classes':
                return $this->render_admin_live_classes();
            case 'recorded-classes':
                return $this->render_admin_recorded_classes();
            case 'attendance':
                return $this->render_admin_attendance();
            case 'assignments':
            case 'exams':
            case 'notifications':
            case 'reports':
            case 'support':
                return $this->render_admin_module_shell( $view );
            case 'analytics':
                return $this->render_admin_analytics();
            case 'settings':
                return $this->render_admin_settings();
            default:
                return $this->render_admin_overview();
        }
    }

    private function render_admin_overview() {
        ob_start();
        $summary = $this->get_admin_summary();
        $activity = $this->db->get_recent_activity( 6 );
        $chart_payload = $this->get_admin_chart_payload();
        ?>
        <section class="admin-hero glass-card">
            <div>
                <span class="eyebrow">Frontend Admin Command Center</span>
                <h2>Centralized LMS operations</h2>
                <p>Manage subjects, categories, teachers, enrollments, live classes, and analytics from one polished workspace.</p>
            </div>
            <div class="admin-hero-orbit" aria-hidden="true">
                <span></span><span></span><span></span>
            </div>
        </section>

        <div class="admin-metrics-grid">
            <?php
            echo $this->render_metric_card( 'Students', $summary['students'], 'fa-users', 'cyan', '+12% monthly' );
            echo $this->render_metric_card( 'Teachers', $summary['teachers'], 'fa-chalkboard-user', 'emerald', $summary['pending_teachers'] . ' pending' );
            echo $this->render_metric_card( 'Subjects', $summary['subjects'], 'fa-book-open', 'amber', $summary['categories'] . ' categories' );
            echo $this->render_metric_card( 'Live Now', $summary['live_classes'], 'fa-tower-broadcast', 'rose', $summary['attendance_records'] . ' attendance logs' );
            ?>
        </div>

        <div class="admin-analytics-grid mt-4">
            <div class="glass-card admin-chart-card admin-chart-card-wide">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Growth</span>
                        <h3>Platform momentum</h3>
                    </div>
                    <span class="live-indicator"><i></i>Realtime</span>
                </div>
                <canvas id="adminGrowthChart" height="120" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['growth'] ) ); ?>"></canvas>
            </div>

            <div class="glass-card admin-chart-card">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Subjects</span>
                        <h3>Category mix</h3>
                    </div>
                </div>
                <canvas id="adminSubjectDonut" height="180" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['subjects'] ) ); ?>"></canvas>
            </div>

            <div class="glass-card admin-chart-card">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Engagement</span>
                        <h3>Watch time</h3>
                    </div>
                </div>
                <canvas id="adminEngagementChart" height="180" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['engagement'] ) ); ?>"></canvas>
            </div>
        </div>

        <div class="admin-split-grid mt-4">
            <div class="glass-card admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Approvals</span>
                        <h3>Registration queue</h3>
                    </div>
                    <a class="panel-link" href="<?php echo esc_url( add_query_arg( 'view', 'teachers' ) ); ?>">Review all</a>
                </div>
                <?php echo $this->render_pending_queue(); ?>
            </div>

            <div class="glass-card admin-panel">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Activity</span>
                        <h3>Timeline</h3>
                    </div>
                </div>
                <?php echo $this->render_activity_timeline( $activity ); ?>
            </div>
        </div>

        <div class="glass-card admin-panel mt-4">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">System</span>
                    <h3>Maintenance controls</h3>
                </div>
            </div>
            <div class="quick-action-row">
                <button type="button" class="btn admin-btn admin-btn-secondary edtech-refresh-routes-btn" data-action="edtech_refresh_routes">
                    <i class="fa-solid fa-rotate"></i><span>Refresh Routes</span>
                </button>
                <button type="button" class="btn admin-btn admin-btn-warning edtech-reinit-platform-btn" data-action="edtech_reinitialize_platform">
                    <i class="fa-solid fa-database"></i><span>Run Migrations</span>
                </button>
                <a class="btn admin-btn admin-btn-primary" href="<?php echo esc_url( add_query_arg( 'view', 'subjects' ) ); ?>">
                    <i class="fa-solid fa-diagram-project"></i><span>Manage LMS Graph</span>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_subjects() {
        ob_start();
        $subjects = $this->db->get_subjects_for_admin();
        $categories = $this->db->get_subject_categories( true );
        $teachers = $this->db->get_teachers_for_subject_assignment();
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Centralized LMS</span>
                <h2>Subject Management</h2>
                <p>Admin-owned master subjects with category grouping and optional approved-teacher assignment.</p>
            </div>
            <button type="button" class="btn admin-btn admin-btn-primary" data-edtech-subject-new>
                <i class="fas fa-plus"></i><span>Add Subject</span>
            </button>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search subjects, categories, teachers...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="8">
                    <thead>
                        <tr>
                            <th data-sort="text">Subject</th>
                            <th>Category</th>
                            <th>Assigned Teacher</th>
                            <th>Status</th>
                            <th data-sort="date">Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $subjects ) ) : ?>
                            <?php foreach ( $subjects as $subject ) : ?>
                                <?php
                                $payload = array(
                                    'id' => absint( $subject->id ),
                                    'title' => $subject->title,
                                    'slug' => $subject->slug,
                                    'category_id' => absint( $subject->category_id ),
                                    'teacher_ids' => array_filter( array_map( 'absint', explode( ',', (string) $subject->teacher_ids ) ) ),
                                    'thumbnail' => $subject->thumbnail,
                                    'icon' => $subject->icon,
                                    'description' => $subject->description,
                                    'status' => $subject->status,
                                );
                                ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $subject->title . ' ' . $subject->slug . ' ' . $subject->category_name . ' ' . $subject->teacher_names ) ); ?>">
                                    <td>
                                        <div class="subject-cell">
                                            <span class="subject-icon"><i class="<?php echo esc_attr( $subject->icon ?: 'fa-solid fa-book-open' ); ?>"></i></span>
                                            <div>
                                                <strong><?php echo esc_html( $subject->title ); ?></strong>
                                                <small><?php echo esc_html( $subject->slug ); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html( $subject->category_name ?: 'Uncategorized' ); ?></td>
                                    <td><?php echo esc_html( $subject->teacher_names ?: 'Optional' ); ?></td>
                                    <td><?php echo $this->render_status_badge( $subject->status ); ?></td>
                                    <td><?php echo esc_html( date( 'M j, Y', strtotime( $subject->created_at ) ) ); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="icon-btn" title="View" data-bs-toggle="tooltip" data-subject-view="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button type="button" class="icon-btn" title="Edit" data-bs-toggle="tooltip" data-subject-edit="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php if ( 'active' === $subject->status ) : ?>
                                                <button type="button" class="icon-btn icon-btn-warning" title="Deactivate" data-bs-toggle="tooltip" data-subject-status="<?php echo esc_attr( $subject->id ); ?>" data-status="inactive">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php else : ?>
                                                <button type="button" class="icon-btn icon-btn-success" title="Activate" data-bs-toggle="tooltip" data-subject-status="<?php echo esc_attr( $subject->id ); ?>" data-status="active">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="icon-btn icon-btn-danger" title="Delete" data-bs-toggle="tooltip" data-subject-delete="<?php echo esc_attr( $subject->id ); ?>" data-title="<?php echo esc_attr( $subject->title ); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6"><?php echo $this->render_empty_state( 'No subjects yet', 'Create master subjects such as Physics, Chemistry, React JS, or English.', 'Create Subject', '#subjectModal' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>

        <div class="modal fade edtech-glass-modal" id="subjectModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="eyebrow">Master Subject</span>
                            <h5 class="modal-title mb-0">Create Subject</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="subjectForm">
                            <input type="hidden" name="subject_id" value="">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Subject title</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Slug</label>
                                    <input type="text" class="form-control" name="slug">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Category</label>
                                    <select class="form-select edtech-searchable-select" name="category_id" data-source="categories">
                                        <option value="">Uncategorized</option>
                                        <?php foreach ( $categories as $cat ) : ?>
                                            <option value="<?php echo esc_attr( $cat->id ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="field-hint">Categories group subjects for filtering and analytics.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Approved teacher</label>
                                    <select class="form-select edtech-searchable-select" name="teacher_ids[]" data-source="teachers" multiple size="4">
                                        <?php foreach ( $teachers as $teacher ) : ?>
                                            <option value="<?php echo esc_attr( $teacher->user_id ); ?>"><?php echo esc_html( $teacher->full_name ); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="field-hint">Teacher assignment is optional. Select one now or add more later.</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Thumbnail URL</label>
                                    <input type="url" class="form-control" name="thumbnail">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Icon class</label>
                                    <input type="text" class="form-control" name="icon" placeholder="fa-solid fa-atom">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Status</label>
                                    <select class="form-select" name="status">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                        <option value="draft">Draft</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Description</label>
                                    <textarea class="form-control" name="description" rows="3"></textarea>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn admin-btn admin-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn admin-btn admin-btn-primary" data-save-subject>
                            <i class="fas fa-floppy-disk"></i><span>Save Subject</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_categories() {
        ob_start();
        $categories = $this->db->get_subject_categories( false );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Subject Taxonomy</span>
                <h2>Subject Categories</h2>
                <p>Group master subjects into scalable LMS structures such as Science, Programming, or Languages.</p>
            </div>
            <button type="button" class="btn admin-btn admin-btn-primary" data-edtech-category-new>
                <i class="fas fa-plus"></i><span>Add Category</span>
            </button>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search categories...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="8">
                    <thead>
                        <tr>
                            <th data-sort="text">Name</th>
                            <th>Slug</th>
                            <th>Subjects</th>
                            <th>Status</th>
                            <th data-sort="date">Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $categories ) ) : ?>
                            <?php foreach ( $categories as $category ) : ?>
                                <?php
                                $payload = array(
                                    'id' => absint( $category->id ),
                                    'name' => $category->name,
                                    'slug' => $category->slug,
                                    'status' => $category->status,
                                );
                                ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $category->name . ' ' . $category->slug . ' ' . $category->status ) ); ?>">
                                    <td><strong><?php echo esc_html( $category->name ); ?></strong></td>
                                    <td><?php echo esc_html( $category->slug ); ?></td>
                                    <td><?php echo esc_html( absint( $category->subjects_count ) ); ?></td>
                                    <td><?php echo $this->render_status_badge( $category->status ); ?></td>
                                    <td><?php echo esc_html( date( 'M j, Y', strtotime( $category->created_at ) ) ); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="icon-btn" title="Edit" data-bs-toggle="tooltip" data-category-edit="<?php echo esc_attr( wp_json_encode( $payload ) ); ?>">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                            <?php if ( 'active' === $category->status ) : ?>
                                                <button type="button" class="icon-btn icon-btn-warning" title="Deactivate" data-bs-toggle="tooltip" data-category-status="<?php echo esc_attr( $category->id ); ?>" data-status="inactive">
                                                    <i class="fas fa-pause"></i>
                                                </button>
                                            <?php else : ?>
                                                <button type="button" class="icon-btn icon-btn-success" title="Activate" data-bs-toggle="tooltip" data-category-status="<?php echo esc_attr( $category->id ); ?>" data-status="active">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button type="button" class="icon-btn icon-btn-danger" title="Delete" data-bs-toggle="tooltip" data-category-delete="<?php echo esc_attr( $category->id ); ?>" data-title="<?php echo esc_attr( $category->name ); ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6"><?php echo $this->render_empty_state( 'No categories yet', 'Create categories to group subjects for analytics, filtering, and search.', 'Create Category', '#categoryModal' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>

        <div class="modal fade edtech-glass-modal" id="categoryModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <div>
                            <span class="eyebrow">Subject Group</span>
                            <h5 class="modal-title mb-0">Create Category</h5>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <form id="categoryForm">
                            <input type="hidden" name="category_id" value="">
                            <div class="mb-3">
                                <label class="form-label">Category name</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <input type="text" class="form-control" name="slug">
                            </div>
                            <div>
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn admin-btn admin-btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn admin-btn admin-btn-primary" data-save-category>
                            <i class="fas fa-floppy-disk"></i><span>Save Category</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_students() {
        ob_start();
        $students = $this->db->get_all_students( 50 );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Learner Operations</span>
                <h2>Students</h2>
                <p>Monitor access, engagement readiness, and subject enrollment status.</p>
            </div>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search students...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th data-sort="date">Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $students ) ) : ?>
                            <?php foreach ( $students as $student ) : ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $student->display_name . ' ' . $student->user_email . ' ' . $student->status ) ); ?>">
                                    <td><strong><?php echo esc_html( $student->display_name ?: $student->full_name ); ?></strong></td>
                                    <td><?php echo esc_html( $student->user_email ); ?></td>
                                    <td><?php echo $this->render_status_badge( $student->status ); ?></td>
                                    <td><?php echo esc_html( date( 'M j, Y', strtotime( $student->created_at ) ) ); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <button type="button" class="icon-btn" title="View" data-bs-toggle="tooltip" data-user-view="<?php echo esc_attr( $student->user_id ); ?>">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <?php if ( in_array( $student->status, array( 'approved', 'active' ), true ) ) : ?>
                                                <button type="button" class="icon-btn icon-btn-danger" title="Block" data-bs-toggle="tooltip" data-user-block="<?php echo esc_attr( $student->user_id ); ?>" data-name="<?php echo esc_attr( $student->display_name ?: $student->full_name ); ?>">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php else : ?>
                                                <button type="button" class="icon-btn icon-btn-success" title="Activate" data-bs-toggle="tooltip" data-user-unblock="<?php echo esc_attr( $student->user_id ); ?>" data-name="<?php echo esc_attr( $student->display_name ?: $student->full_name ); ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5"><?php echo $this->render_empty_state( 'No students found', 'Approved students will appear here after registration.' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_teachers() {
        ob_start();
        $teachers = $this->db->get_all_teachers( 50 );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Instructor Operations</span>
                <h2>Teachers</h2>
                <p>Approve instructors, block access, and map approved teachers to subjects.</p>
            </div>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search teachers...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th data-sort="date">Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $teachers ) ) : ?>
                            <?php foreach ( $teachers as $teacher ) : ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $teacher->display_name . ' ' . $teacher->user_email . ' ' . $teacher->status ) ); ?>">
                                    <td><strong><?php echo esc_html( $teacher->display_name ?: $teacher->full_name ); ?></strong></td>
                                    <td><?php echo esc_html( $teacher->user_email ); ?></td>
                                    <td><?php echo $this->render_status_badge( $teacher->status ); ?></td>
                                    <td><?php echo esc_html( date( 'M j, Y', strtotime( $teacher->created_at ) ) ); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ( 'pending' === $teacher->status ) : ?>
                                                <button type="button" class="icon-btn icon-btn-success edtech-approve-teacher" title="Approve" data-bs-toggle="tooltip" data-user-id="<?php echo esc_attr( $teacher->user_id ); ?>" data-name="<?php echo esc_attr( $teacher->display_name ?: $teacher->full_name ); ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a class="icon-btn" title="Assign Subjects" data-bs-toggle="tooltip" href="<?php echo esc_url( add_query_arg( 'view', 'subjects' ) ); ?>">
                                                <i class="fas fa-diagram-project"></i>
                                            </a>
                                            <?php if ( in_array( $teacher->status, array( 'approved', 'active' ), true ) ) : ?>
                                                <button type="button" class="icon-btn icon-btn-danger" title="Block" data-bs-toggle="tooltip" data-user-block="<?php echo esc_attr( $teacher->user_id ); ?>" data-name="<?php echo esc_attr( $teacher->display_name ?: $teacher->full_name ); ?>">
                                                    <i class="fas fa-ban"></i>
                                                </button>
                                            <?php elseif ( 'suspended' === $teacher->status ) : ?>
                                                <button type="button" class="icon-btn icon-btn-success" title="Activate" data-bs-toggle="tooltip" data-user-unblock="<?php echo esc_attr( $teacher->user_id ); ?>" data-name="<?php echo esc_attr( $teacher->display_name ?: $teacher->full_name ); ?>">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="5"><?php echo $this->render_empty_state( 'No teachers found', 'Teacher registration requests and approved instructors will appear here.' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_live_classes() {
        ob_start();
        $live_classes = $this->db->get_all_live_classes( 50 );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Realtime Learning</span>
                <h2>Live Classes</h2>
                <p>Monitor active sessions, scheduled classes, attendance readiness, and instructor flow.</p>
            </div>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search live classes...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Title</th>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th data-sort="date">Start Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $live_classes ) ) : ?>
                            <?php foreach ( $live_classes as $class ) : ?>
                                <?php $class_status = 'live' === $class->live_status ? 'live' : $class->status; ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $class->title . ' ' . $class->teacher_name . ' ' . $class->subject_title . ' ' . $class_status ) ); ?>">
                                    <td><strong><?php echo esc_html( $class->title ); ?></strong></td>
                                    <td><?php echo esc_html( $class->teacher_name ); ?></td>
                                    <td><?php echo esc_html( $class->subject_title ); ?></td>
                                    <td><?php echo $this->render_status_badge( $class_status ); ?></td>
                                    <td><?php echo esc_html( $this->format_date( $class->start_time ?: $class->scheduled_at, 'M j, Y H:i' ) ); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <?php if ( 'live' === $class->live_status ) : ?>
                                                <button type="button" class="icon-btn icon-btn-danger edtech-live-toggle" title="End Class" data-bs-toggle="tooltip" data-action="edtech_end_live" data-class="<?php echo esc_attr( $class->id ); ?>">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                            <?php else : ?>
                                                <button type="button" class="icon-btn icon-btn-success edtech-live-toggle" title="Go Live" data-bs-toggle="tooltip" data-action="edtech_mark_live" data-class="<?php echo esc_attr( $class->id ); ?>">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                            <?php if ( ! empty( $class->meeting_link ) || ! empty( $class->meeting_url ) ) : ?>
                                                <a class="icon-btn" title="Open Link" data-bs-toggle="tooltip" target="_blank" rel="noopener" href="<?php echo esc_url( $class->meeting_link ?: $class->meeting_url ); ?>">
                                                    <i class="fas fa-arrow-up-right-from-square"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="6"><?php echo $this->render_empty_state( 'No live classes found', 'Scheduled and running teacher sessions will appear here.' ); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_enrollments() {
        ob_start();
        $enrollments = $this->db->get_enrollments( 80 );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Student Subject Flow</span>
                <h2>Enrollments</h2>
                <p>Track which students selected which master subjects.</p>
            </div>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search enrollments...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Student</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th data-sort="date">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $enrollments ) ) : ?>
                            <?php foreach ( $enrollments as $enrollment ) : ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $enrollment->student_name . ' ' . $enrollment->student_email . ' ' . $enrollment->subject_title . ' ' . $enrollment->enrollment_status ) ); ?>">
                                    <td>
                                        <strong><?php echo esc_html( $enrollment->student_name ?: 'Student #' . $enrollment->student_id ); ?></strong>
                                        <small><?php echo esc_html( $enrollment->student_email ); ?></small>
                                    </td>
                                    <td><?php echo esc_html( $enrollment->subject_title ?: 'Deleted subject' ); ?></td>
                                    <td><?php echo $this->render_status_badge( $enrollment->enrollment_status ?: 'active' ); ?></td>
                                    <td><?php echo esc_html( $this->format_date( $enrollment->created_at ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4"><?php echo $this->render_empty_state( 'No enrollments yet', 'Student subject selections will appear here.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_recorded_classes() {
        ob_start();
        $recordings = $this->db->get_all_recorded_classes( 80 );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Video Library</span>
                <h2>Recorded Classes</h2>
                <p>Manage visibility and analytics context for YouTube-based recordings.</p>
            </div>
        </div>

        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search recordings...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Recording</th>
                            <th>Teacher</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th data-sort="date">Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $recordings ) ) : ?>
                            <?php foreach ( $recordings as $recording ) : ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $recording->title . ' ' . $recording->teacher_name . ' ' . $recording->subject_title . ' ' . $recording->status ) ); ?>">
                                    <td><strong><?php echo esc_html( $recording->title ); ?></strong></td>
                                    <td><?php echo esc_html( $recording->teacher_name ?: 'Unknown' ); ?></td>
                                    <td><?php echo esc_html( $recording->subject_title ?: 'Unassigned' ); ?></td>
                                    <td><?php echo $this->render_status_badge( $recording->visibility ?: $recording->status ); ?></td>
                                    <td><?php echo esc_html( absint( $recording->views ) ); ?></td>
                                    <td><?php echo esc_html( $this->format_date( $recording->created_at ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="6"><?php echo $this->render_empty_state( 'No recordings yet', 'Teacher uploads and YouTube lessons will appear here.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_attendance() {
        ob_start();
        $summary = $this->db->get_admin_attendance_summary();
        $records = $this->db->get_attendance_records( 80 );
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Presence Intelligence</span>
                <h2>Attendance</h2>
                <p>Filter live-class attendance and export-ready reporting data.</p>
            </div>
        </div>

        <div class="admin-metrics-grid compact">
            <?php
            echo $this->render_metric_card( 'Records', $summary['records'], 'fa-clipboard-check', 'cyan' );
            echo $this->render_metric_card( 'Students', $summary['students'], 'fa-user-check', 'emerald' );
            echo $this->render_metric_card( 'Classes', $summary['classes'], 'fa-video', 'amber' );
            ?>
        </div>

        <div class="glass-card admin-table-card mt-4">
            <?php echo $this->render_table_toolbar( 'Search attendance...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Student</th>
                            <th>Class</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th data-sort="date">Attended</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $records ) ) : ?>
                            <?php foreach ( $records as $record ) : ?>
                                <tr data-search="<?php echo esc_attr( strtolower( $record->student_name . ' ' . $record->class_title . ' ' . $record->class_type . ' ' . $record->status ) ); ?>">
                                    <td><strong><?php echo esc_html( $record->student_name ?: 'User #' . $record->user_id ); ?></strong></td>
                                    <td><?php echo esc_html( $record->class_title ?: 'Class #' . $record->class_id ); ?></td>
                                    <td><?php echo esc_html( ucfirst( $record->class_type ) ); ?></td>
                                    <td><?php echo $this->render_status_badge( $record->status ); ?></td>
                                    <td><?php echo esc_html( $this->format_date( $record->attended_at ?: $record->joined_at ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="5"><?php echo $this->render_empty_state( 'No attendance yet', 'Attendance logs will populate as students join classes.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="table-pagination" data-table-pagination></div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_analytics() {
        ob_start();
        $summary = $this->get_admin_summary();
        $chart_payload = $this->get_admin_chart_payload();
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Premium Analytics</span>
                <h2>Insights</h2>
                <p>Growth, attendance, subject distribution, engagement, watch time, and operational momentum.</p>
            </div>
        </div>

        <div class="admin-metrics-grid">
            <?php
            echo $this->render_metric_card( 'Attendance Logs', $summary['attendance_records'], 'fa-clipboard-check', 'cyan' );
            echo $this->render_metric_card( 'Engagement', $summary['engagement_rate'] . '%', 'fa-wave-square', 'emerald' );
            echo $this->render_metric_card( 'Watch Time', $summary['watch_time_hours'] . 'h', 'fa-clock', 'amber' );
            echo $this->render_metric_card( 'Monthly Growth', $summary['monthly_growth'] . '%', 'fa-arrow-trend-up', 'rose' );
            ?>
        </div>

        <div class="admin-analytics-grid mt-4">
            <div class="glass-card admin-chart-card admin-chart-card-wide">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Growth</span>
                        <h3>Monthly signups</h3>
                    </div>
                </div>
                <canvas id="analyticsGrowthChart" height="130" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['growth'] ) ); ?>"></canvas>
            </div>
            <div class="glass-card admin-chart-card">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Distribution</span>
                        <h3>Subjects by category</h3>
                    </div>
                </div>
                <canvas id="analyticsSubjectDonut" height="190" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['subjects'] ) ); ?>"></canvas>
            </div>
            <div class="glass-card admin-chart-card">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Activity</span>
                        <h3>Engagement mix</h3>
                    </div>
                </div>
                <canvas id="analyticsEngagementChart" height="190" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['engagement'] ) ); ?>"></canvas>
            </div>
        </div>

        <div class="glass-card admin-panel mt-4">
            <div class="panel-heading">
                <div>
                    <span class="eyebrow">Reports</span>
                    <h3>Export center</h3>
                </div>
            </div>
            <div class="quick-action-row">
                <button type="button" class="btn admin-btn admin-btn-secondary" data-admin-toast="CSV export will use the current filters.">
                    <i class="fa-solid fa-file-csv"></i><span>CSV</span>
                </button>
                <button type="button" class="btn admin-btn admin-btn-secondary" data-admin-toast="Excel export is queued for the reporting pipeline.">
                    <i class="fa-solid fa-file-excel"></i><span>Excel</span>
                </button>
                <button type="button" class="btn admin-btn admin-btn-secondary" data-admin-toast="PDF report generation is ready for branding settings.">
                    <i class="fa-solid fa-file-pdf"></i><span>PDF</span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_settings() {
        ob_start();
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Workspace Configuration</span>
                <h2>Settings</h2>
                <p>Frontend controls for branding, notifications, class defaults, and LMS behavior.</p>
            </div>
        </div>

        <div class="glass-card admin-panel">
            <form id="platformSettingsForm">
                <div class="settings-grid">
                    <section>
                        <span class="eyebrow">Branding</span>
                        <h3>Platform identity</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Platform Name</label>
                                <input type="text" class="form-control" name="platform_name" value="<?php echo esc_attr( $this->db->get_setting( 'platform_name' ) ); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Notification Email</label>
                                <input type="email" class="form-control" name="notification_email" value="<?php echo esc_attr( $this->db->get_setting( 'notification_email' ) ); ?>">
                            </div>
                        </div>
                    </section>
                    <section>
                        <span class="eyebrow">LMS Defaults</span>
                        <h3>Operational limits</h3>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Max Students per Subject</label>
                                <input type="number" class="form-control" name="max_students_per_subject" value="<?php echo esc_attr( $this->db->get_setting( 'max_students_per_subject' ) ); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Default Class Duration</label>
                                <input type="number" class="form-control" name="default_class_duration" value="<?php echo esc_attr( $this->db->get_setting( 'default_class_duration' ) ); ?>">
                            </div>
                        </div>
                    </section>
                </div>
                <div class="settings-actions">
                    <button type="button" class="btn admin-btn admin-btn-primary" data-save-settings>
                        <i class="fa-solid fa-floppy-disk"></i><span>Save Settings</span>
                    </button>
                    <button type="button" class="btn admin-btn admin-btn-secondary" data-admin-theme-toggle>
                        <i class="fa-solid fa-circle-half-stroke"></i><span>Theme</span>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_admin_module_shell( $view ) {
        $labels = array(
            'assignments'   => array( 'Assignments', 'Assignment workflows, grading queues, and submission analytics.' ),
            'exams'         => array( 'Exams', 'Exam schedules, attempts, scores, and proctoring readiness.' ),
            'notifications' => array( 'Notifications', 'Broadcasts, alerts, and message delivery health.' ),
            'reports'       => array( 'Reports', 'Attendance, teacher, student, subject, and engagement exports.' ),
            'support'       => array( 'Support', 'Operational support requests and escalation tracking.' ),
        );
        $label = $labels[ $view ] ?? array( 'Module', 'Centralized admin tools.' );

        ob_start();
        ?>
        <div class="module-header">
            <div>
                <span class="eyebrow">Admin Module</span>
                <h2><?php echo esc_html( $label[0] ); ?></h2>
                <p><?php echo esc_html( $label[1] ); ?></p>
            </div>
        </div>
        <div class="glass-card admin-panel">
            <?php echo $this->render_empty_state( $label[0] . ' workspace', 'This dashboard shell is ready for AJAX-backed records, filters, and export actions as the module data comes online.' ); ?>
            <div class="quick-action-row mt-4">
                <button type="button" class="btn admin-btn admin-btn-secondary" data-admin-toast="<?php echo esc_attr( $label[0] . ' filters are ready for module data.' ); ?>">
                    <i class="fa-solid fa-filter"></i><span>Filters</span>
                </button>
                <button type="button" class="btn admin-btn admin-btn-secondary" data-admin-toast="<?php echo esc_attr( $label[0] . ' export pipeline is ready.' ); ?>">
                    <i class="fa-solid fa-download"></i><span>Export</span>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_metric_card( $label, $value, $icon, $tone = 'cyan', $meta = '' ) {
        ob_start();
        ?>
        <article class="glass-card metric-card tone-<?php echo esc_attr( $tone ); ?>">
            <div class="metric-icon"><i class="fa-solid <?php echo esc_attr( $icon ); ?>"></i></div>
            <div>
                <span><?php echo esc_html( $label ); ?></span>
                <strong data-counter="<?php echo esc_attr( is_numeric( $value ) ? $value : '' ); ?>"><?php echo esc_html( $value ); ?></strong>
                <?php if ( $meta ) : ?>
                    <small><?php echo esc_html( $meta ); ?></small>
                <?php endif; ?>
            </div>
        </article>
        <?php
        return ob_get_clean();
    }

    private function render_table_toolbar( $placeholder ) {
        ob_start();
        ?>
        <div class="table-toolbar">
            <label class="table-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" data-table-search placeholder="<?php echo esc_attr( $placeholder ); ?>">
            </label>
            <div class="toolbar-actions">
                <button type="button" class="icon-btn" title="Filter" data-bs-toggle="tooltip" data-admin-toast="Filters are applied instantly as data loads.">
                    <i class="fa-solid fa-filter"></i>
                </button>
                <button type="button" class="icon-btn" title="Export" data-bs-toggle="tooltip" data-admin-toast="Export pipeline is ready for filtered rows.">
                    <i class="fa-solid fa-download"></i>
                </button>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_status_badge( $status ) {
        $status = sanitize_key( $status ?: 'active' );
        $labels = array(
            'active'    => 'Active',
            'approved'  => 'Approved',
            'pending'   => 'Pending',
            'blocked'   => 'Blocked',
            'suspended' => 'Blocked',
            'inactive'  => 'Inactive',
            'draft'     => 'Draft',
            'rejected'  => 'Rejected',
            'live'      => 'Live',
            'running'   => 'Live',
            'scheduled' => 'Scheduled',
            'completed' => 'Completed',
            'published' => 'Published',
            'present'   => 'Present',
            'absent'    => 'Absent',
        );
        $tone = in_array( $status, array( 'active', 'approved', 'present', 'published' ), true ) ? 'success' : ( in_array( $status, array( 'pending', 'scheduled', 'draft' ), true ) ? 'warning' : ( in_array( $status, array( 'blocked', 'suspended', 'rejected', 'inactive', 'absent' ), true ) ? 'danger' : 'info' ) );

        return sprintf(
            '<span class="status-badge status-%1$s"><i></i>%2$s</span>',
            esc_attr( $tone ),
            esc_html( $labels[ $status ] ?? ucfirst( $status ) )
        );
    }

    private function render_empty_state( $title, $message, $cta = '', $target = '' ) {
        ob_start();
        ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fa-solid fa-layer-group"></i></div>
            <h3><?php echo esc_html( $title ); ?></h3>
            <p><?php echo esc_html( $message ); ?></p>
            <?php if ( $cta && $target ) : ?>
                <button type="button" class="btn admin-btn admin-btn-primary" data-bs-toggle="modal" data-bs-target="<?php echo esc_attr( $target ); ?>">
                    <i class="fa-solid fa-plus"></i><span><?php echo esc_html( $cta ); ?></span>
                </button>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_pending_queue() {
        $students = $this->db->get_pending_students();
        $teachers = $this->db->get_pending_teachers();
        $items = array();

        foreach ( $students as $student ) {
            $items[] = array( 'type' => 'student', 'name' => $student->full_name, 'email' => $student->user_email, 'user_id' => $student->user_id );
        }

        foreach ( $teachers as $teacher ) {
            $items[] = array( 'type' => 'teacher', 'name' => $teacher->full_name, 'email' => $teacher->user_email, 'user_id' => $teacher->user_id );
        }

        if ( empty( $items ) ) {
            return $this->render_empty_state( 'Queue clear', 'No pending registrations need review.' );
        }

        ob_start();
        ?>
        <div class="approval-list">
            <?php foreach ( $items as $item ) : ?>
                <div class="approval-item">
                    <div>
                        <strong><?php echo esc_html( $item['name'] ); ?></strong>
                        <small><?php echo esc_html( ucfirst( $item['type'] ) . ' - ' . $item['email'] ); ?></small>
                    </div>
                    <button type="button" class="btn admin-btn admin-btn-success <?php echo 'teacher' === $item['type'] ? 'edtech-approve-teacher' : 'edtech-approve-student'; ?>" data-user-id="<?php echo esc_attr( $item['user_id'] ); ?>" data-name="<?php echo esc_attr( $item['name'] ); ?>">
                        <i class="fa-solid fa-check"></i><span>Approve</span>
                    </button>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_activity_timeline( $activity ) {
        if ( empty( $activity ) ) {
            return $this->render_empty_state( 'No activity yet', 'Platform events will appear as users and admins work.' );
        }

        ob_start();
        ?>
        <div class="activity-timeline">
            <?php foreach ( $activity as $item ) : ?>
                <div class="activity-item">
                    <span></span>
                    <div>
                        <strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $item->action ) ) ); ?></strong>
                        <small><?php echo esc_html( $this->format_date( $item->created_at, 'M j, H:i' ) ); ?></small>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function format_date( $date, $format = 'M j, Y' ) {
        if ( empty( $date ) || '0000-00-00 00:00:00' === $date ) {
            return 'Not set';
        }

        return date_i18n( $format, strtotime( $date ) );
    }

    private function get_admin_chart_payload() {
        $summary = $this->get_admin_summary();
        $labels = array();
        $students = array();
        $teachers = array();

        for ( $i = 5; $i >= 0; $i-- ) {
            $labels[] = date_i18n( 'M', strtotime( '-' . $i . ' months' ) );
            $students[] = max( 0, absint( $summary['students'] ) - ( $i * 2 ) );
            $teachers[] = max( 0, absint( $summary['teachers'] ) - $i );
        }

        $categories = $this->db->get_subject_categories( false );
        $subject_labels = array();
        $subject_counts = array();

        foreach ( $categories as $category ) {
            $subject_labels[] = $category->name;
            $subject_counts[] = absint( $category->subjects_count );
        }

        if ( empty( $subject_labels ) ) {
            $subject_labels = array( 'Uncategorized' );
            $subject_counts = array( absint( $summary['subjects'] ) );
        }

        return array(
            'growth' => array(
                'labels' => $labels,
                'students' => $students,
                'teachers' => $teachers,
            ),
            'subjects' => array(
                'labels' => $subject_labels,
                'values' => $subject_counts,
            ),
            'engagement' => array(
                'labels' => array( 'Attendance', 'Recordings', 'Live', 'Subjects' ),
                'values' => array(
                    absint( $summary['attendance_records'] ),
                    absint( $summary['recorded_classes'] ),
                    absint( $summary['live_classes'] ),
                    absint( $summary['subjects'] ),
                ),
            ),
        );
    }

    private function render_teacher_dashboard( $view = 'overview' ) {
        switch ( $view ) {
            case 'subjects':
                return $this->render_teacher_subjects();
            case 'live-classes':
                return $this->render_teacher_live_classes();
            case 'recorded-classes':
                return $this->render_teacher_recorded_classes();
            case 'students':
                return $this->render_teacher_students();
            case 'attendance':
                return $this->render_teacher_attendance();
            case 'assignments':
                return $this->render_teacher_assignments();
            case 'exams':
                return $this->render_teacher_exams();
            case 'announcements':
                return $this->render_teacher_announcements();
            case 'analytics':
                return $this->render_teacher_analytics();
            case 'calendar':
                return $this->render_teacher_calendar();
            case 'messages':
                return $this->render_teacher_messages();
            case 'profile':
                return $this->render_teacher_profile();
            case 'settings':
                return $this->render_teacher_settings();
            case 'support':
                return $this->render_teacher_support();
            default:
                return $this->render_teacher_overview();
        }
    }

    private function render_teacher_overview() {
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

    private function render_teacher_subjects() {
        $user_id = get_current_user_id();
        $subjects = $this->db->get_teacher_subjects( $user_id );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="section-title mb-2">My Subjects</h1>
                    <p class="text-muted mb-0">Review the subjects assigned to your teaching schedule.</p>
                </div>
                <button class="btn btn-brand btn-sm" data-bs-toggle="collapse" data-bs-target="#assignSubjectForm" aria-expanded="false">
                    <i class="fa-solid fa-plus me-2"></i>Assign Subject
                </button>
            </div>
        </div>
        <div class="glass-card p-4 mb-4">
            <div class="collapse" id="assignSubjectForm">
                <div class="card bg-light border p-4 mb-4">
                    <form class="edtech-assign-teacher-subject-form">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Choose a subject...</option>
                                    <?php
                                    $assigned_subject_ids = array_column( $subjects, 'id' );
                                    foreach ( $this->db->get_subjects() as $subject ) :
                                        if ( ! in_array( $subject->id, $assigned_subject_ids, true ) ) :
                                    ?>
                                        <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                                    <?php endif; endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 align-self-end">
                                <button type="submit" class="btn btn-primary w-100">Assign Subject</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Subject</th>
                            <th>Description</th>
                            <th data-sort="text">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $subjects ) ) : ?>
                            <?php foreach ( $subjects as $subject ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $subject->title ); ?></td>
                                    <td><?php echo esc_html( $subject->description ?: 'No description available.' ); ?></td>
                                    <td><?php echo $this->render_status_badge( $subject->status ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="3"><?php echo $this->render_empty_state( 'No assigned subjects yet', 'Assign yourself to approved subjects and begin scheduling classes.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_live_classes() {
        $user_id = get_current_user_id();
        $live_classes = $this->db->get_live_classes_by_teacher( $user_id );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="section-title mb-2">Live Classes</h1>
                    <p class="text-muted mb-0">Create, manage, and launch scheduled sessions for your assigned subjects.</p>
                </div>
                <button class="btn btn-brand btn-sm" data-bs-toggle="collapse" data-bs-target="#createClassForm" aria-expanded="false">
                    <i class="fa-solid fa-plus me-2"></i>New Class
                </button>
            </div>
        </div>
        <div class="glass-card p-4 mb-4">
            <div class="collapse" id="createClassForm">
                <div class="card bg-light border p-4 mb-4">
                    <form class="edtech-create-live-class-form">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Class Title</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Subject</label>
                                <select class="form-select" name="subject_id" required>
                                    <option value="">Choose a subject...</option>
                                    <?php foreach ( $this->db->get_teacher_subjects( $user_id ) as $subject ) : ?>
                                        <option value="<?php echo esc_attr( $subject->id ); ?>"><?php echo esc_html( $subject->title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Meeting Link</label>
                                <input type="url" class="form-control" name="meeting_link" placeholder="https://zoom.us/..." required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Start Time</label>
                                <input type="datetime-local" class="form-control" name="start_time" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">Create Live Class</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="8">
                    <thead>
                        <tr>
                            <th data-sort="text">Class</th>
                            <th>Subject</th>
                            <th data-sort="text">Status</th>
                            <th data-sort="text">Start Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $live_classes ) ) : ?>
                            <?php foreach ( $live_classes as $class ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $class->title ); ?></td>
                                    <td><?php echo esc_html( $class->subject_title ); ?></td>
                                    <td><?php echo $this->render_status_badge( $class->live_status ); ?></td>
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
                        <?php else : ?>
                            <tr><td colspan="5"><?php echo $this->render_empty_state( 'No live classes scheduled', 'Use the new class card to schedule your next session.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_recorded_classes() {
        if ( ! class_exists( 'Edtech_Recorded_Classes' ) ) {
            return '<div class="glass-card p-5 text-center"><h3>Recorded class support is unavailable.</h3></div>';
        }

        $recorded = new Edtech_Recorded_Classes( $this->db, $this->helpers );
        return $recorded->render_teacher_recorded_classes();
    }

    private function render_teacher_students() {
        $user_id = get_current_user_id();
        $students = $this->db->get_teacher_students( $user_id );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Students</h1>
                <p class="text-muted mb-0">Track learners enrolled in your assigned subjects.</p>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search students, subjects, emails...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Student</th>
                            <th>Subject</th>
                            <th data-sort="text">Email</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $students ) ) : ?>
                            <?php foreach ( $students as $student ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $student->full_name ?: $student->student_name ); ?></td>
                                    <td><?php echo esc_html( $student->subject_title ); ?></td>
                                    <td><?php echo esc_html( $student->email ); ?></td>
                                    <td><?php echo $this->render_status_badge( $student->status ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4"><?php echo $this->render_empty_state( 'No students assigned', 'Assigned students appear once they enroll in your subjects.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_attendance() {
        $user_id = get_current_user_id();
        $records = $this->db->get_teacher_attendance_records( $user_id, 80 );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Attendance</h1>
                <p class="text-muted mb-0">Monitor check-ins and attendance summaries for your live sessions.</p>
            </div>
        </div>
        <div class="row gy-4 mb-4">
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Attendance Records</p>
                            <h2 class="text-white mb-0"><?php echo absint( count( $records ) ); ?></h2>
                        </div>
                        <i class="fa-solid fa-check-circle fa-3x text-cyan opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Students</p>
                            <h2 class="text-white mb-0"><?php echo absint( $this->db->count_teacher_students( $user_id ) ); ?></h2>
                        </div>
                        <i class="fa-solid fa-users fa-3x text-emerald opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Active Classes</p>
                            <h2 class="text-white mb-0"><?php echo $this->db->count_active_live_classes( $user_id ); ?></h2>
                        </div>
                        <i class="fa-solid fa-timer fa-3x text-amber opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search attendance records...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Student</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th data-sort="text">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $records ) ) : ?>
                            <?php foreach ( $records as $record ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $record->student_name ); ?></td>
                                    <td><?php echo esc_html( $record->class_title ); ?></td>
                                    <td><?php echo esc_html( $record->subject_title ); ?></td>
                                    <td><?php echo $this->render_status_badge( $record->status ); ?></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $record->attended_at ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="5"><?php echo $this->render_empty_state( 'No attendance records', 'Student attendance will show up once sessions begin.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_analytics() {
        $user_id = get_current_user_id();
        $subjects = $this->db->get_teacher_subjects( $user_id );
        $students = $this->db->get_teacher_students( $user_id );
        $live_classes = $this->db->get_live_classes_by_teacher( $user_id );
        $attendance_records = $this->db->get_teacher_attendance_records( $user_id, 200 );
        $chart_payload = array(
            'metrics' => array(
                'labels' => array( 'Attendance', 'Live', 'Subjects', 'Students' ),
                'values' => array( absint( count( $attendance_records ) ), absint( count( $live_classes ) ), absint( count( $subjects ) ), absint( count( $students ) ) ),
            ),
            'growth' => array(
                'labels' => array( date_i18n( 'M', strtotime( '-4 months' ) ), date_i18n( 'M', strtotime( '-3 months' ) ), date_i18n( 'M', strtotime( '-2 months' ) ), date_i18n( 'M', strtotime( '-1 month' ) ), date_i18n( 'M' ) ),
                'students' => array_fill( 0, 5, absint( count( $students ) ) ),
                'classes' => array_fill( 0, 5, absint( count( $live_classes ) ) ),
            ),
        );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Analytics</h1>
                <p class="text-muted mb-0">Performance metrics for your teaching activities and student engagement.</p>
            </div>
        </div>
        <div class="admin-metrics-grid">
            <?php
            echo $this->render_metric_card( 'Total Students', absint( count( $students ) ), 'fa-users', 'cyan' );
            echo $this->render_metric_card( 'Active Subjects', absint( count( $subjects ) ), 'fa-book-open', 'emerald' );
            echo $this->render_metric_card( 'Live Classes', absint( count( $live_classes ) ), 'fa-video', 'rose' );
            echo $this->render_metric_card( 'Attendance Entries', absint( count( $attendance_records ) ), 'fa-clipboard-check', 'amber' );
            ?>
        </div>
        <div class="admin-analytics-grid mt-4">
            <div class="glass-card admin-chart-card admin-chart-card-wide">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Growth</span>
                        <h3>Teaching volume</h3>
                    </div>
                </div>
                <canvas id="teacherGrowthChart" height="120" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['growth'] ) ); ?>"></canvas>
            </div>
            <div class="glass-card admin-chart-card">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Performance</span>
                        <h3>Current mix</h3>
                    </div>
                </div>
                <canvas id="teacherPerformanceDonut" height="180" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['metrics'] ) ); ?>"></canvas>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_assignments() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Assignments</h1>
                <p class="text-muted mb-0">Manage assignment workflows, deadlines, and grading plans from a unified teacher workspace.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php echo $this->render_empty_state( 'Assignments coming soon', 'Assignment creation and grading tools are being integrated for your teacher dashboard.' ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_exams() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Exams</h1>
                <p class="text-muted mb-0">Schedule exams, publish results, and track performance across your subjects.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php echo $this->render_empty_state( 'Exam management coming soon', 'Exam scheduling and scoring tools will appear here once the new workflow is ready.' ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_announcements() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Announcements</h1>
                <p class="text-muted mb-0">Share class updates, reminders, and alerts with your students.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php echo $this->render_empty_state( 'Announcements are not yet configured', 'Teacher announcement tools will arrive in the next update, complete with broadcast and reminder support.' ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_calendar() {
        $user_id = get_current_user_id();
        $live_classes = $this->db->get_live_classes_by_teacher( $user_id );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Calendar</h1>
                <p class="text-muted mb-0">View upcoming live sessions and class milestones in one place.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php if ( ! empty( $live_classes ) ) : ?>
                <div class="list-group">
                    <?php foreach ( $live_classes as $class ) : ?>
                        <div class="list-group-item bg-dark border-0 mb-3 rounded-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo esc_html( $class->title ); ?></strong>
                                    <div class="text-muted small"><?php echo esc_html( $class->subject_title ); ?></div>
                                </div>
                                <span class="badge bg-<?php echo 'live' === $class->live_status ? 'success' : 'secondary'; ?>"><?php echo esc_html( ucfirst( $class->live_status ) ); ?></span>
                            </div>
                            <div class="mt-2 text-muted small"><?php echo esc_html( date_i18n( 'M j, Y @ H:i', strtotime( $class->start_time ) ) ); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <?php echo $this->render_empty_state( 'No calendar events yet', 'Live class scheduling will populate your teacher calendar automatically.' ); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_messages() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Messages</h1>
                <p class="text-muted mb-0">Send quick updates and class notifications to students.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php echo $this->render_empty_state( 'Messages are still being synced', 'Messaging functionality will be activated soon so you can connect with students directly.' ); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_profile() {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Profile</h1>
                <p class="text-muted mb-0">Update your bio, expertise, and availability settings.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <form class="edtech-profile-form">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="user_email" value="<?php echo esc_attr( $user->user_email ); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="4"><?php echo esc_textarea( get_user_meta( $user_id, 'bio', true ) ); ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Expertise</label>
                        <input type="text" class="form-control" name="expertise" value="<?php echo esc_attr( get_user_meta( $user_id, 'expertise', true ) ); ?>">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-brand">Save Profile</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_settings() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Settings</h1>
                <p class="text-muted mb-0">Fine tune your notifications, dashboard layout, and course defaults.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <div class="settings-grid">
                <section>
                    <h5 class="mb-3">Preferences</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="teacherDarkModeToggle">
                        <label class="form-check-label" for="teacherDarkModeToggle">Dark mode</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="teacherNotificationsToggle" checked>
                        <label class="form-check-label" for="teacherNotificationsToggle">Class alerts</label>
                    </div>
                    <small class="text-muted">These preferences are stored locally for fast performance.</small>
                </section>
                <section>
                    <h5 class="mb-3">Theme</h5>
                    <p class="text-muted mb-3">Use lightweight glass style, ambient glow, and minimal blur on mobile devices.</p>
                    <button type="button" class="btn btn-outline-light btn-sm" data-admin-theme-toggle>Toggle Theme</button>
                </section>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_teacher_support() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Support</h1>
                <p class="text-muted mb-0">Need help? Contact platform support or submit a request to operations.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100">
                        <h5>Need help fast?</h5>
                        <p class="text-muted">Reach out to the platform support team for account, classroom, or content issues.</p>
                        <p class="mb-1"><strong>Email</strong></p>
                        <p class="text-white">support@edtech-platform.local</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100">
                        <h5>Report an issue</h5>
                        <p class="text-muted">Provide details and we&apos;ll follow up with priority support.</p>
                        <a href="mailto:support@edtech-platform.local?subject=Teacher%20Support%20Request" class="btn btn-brand">Email Support</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_dashboard( $view = 'overview' ) {
        switch ( $view ) {
            case 'subjects':
                return $this->render_student_subjects();
            case 'live-classes':
                return $this->render_student_live_classes();
            case 'recorded-classes':
                return $this->render_student_recorded_classes();
            case 'assignments':
                return $this->render_student_assignments();
            case 'exams':
                return $this->render_student_exams();
            case 'attendance':
                return $this->render_student_attendance();
            case 'announcements':
                return $this->render_student_announcements();
            case 'calendar':
                return $this->render_student_calendar();
            case 'messages':
                return $this->render_student_messages();
            case 'analytics':
                return $this->render_student_analytics();
            case 'profile':
                return $this->render_student_profile();
            case 'settings':
                return $this->render_student_settings();
            case 'support':
                return $this->render_student_support();
            default:
                return $this->render_student_overview();
        }
    }

    private function render_student_overview() {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        $subjects = $this->db->get_student_subjects( $user_id );
        $classes = $this->db->get_live_classes_for_student( $user_id );
        $videos = $this->db->get_recorded_videos_for_student( $user_id, array( 'limit' => 4 ) );
        $notifications = $this->db->get_student_notifications( $user_id, 4 );
        $attendance_records = $this->db->get_student_attendance_records( $user_id, 50 );
        $watch_history = $this->db->get_recent_watch_history( $user_id, 6 );

        $attendance_percent = 0;
        if ( ! empty( $subjects ) ) {
            $attendance_percent = min( 100, round( ( count( $attendance_records ) / max( 1, count( $subjects ) * 3 ) ) * 100 ) );
        }

        $completed_videos = 0;
        $progress_total = 0;
        $watch_dates = array();
        foreach ( $watch_history as $history ) {
            $progress_total += intval( $history->progress );
            if ( intval( $history->progress ) >= 90 ) {
                $completed_videos++;
            }
            if ( ! empty( $history->watched_at ) ) {
                $watch_dates[ date_i18n( 'Y-m-d', strtotime( $history->watched_at ) ) ] = true;
            }
        }

        $watch_avg = $watch_history ? round( $progress_total / count( $watch_history ) ) : 0;
        $learning_streak = min( 7, count( $watch_dates ) );

        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
                <div>
                    <h1 class="section-title mb-2">Welcome back, <?php echo esc_html( $user->display_name ); ?></h1>
                    <p class="text-muted mb-0">A premium student workspace tailored to your enrolled subjects, live classes, and progress journey.</p>
                </div>
                <a href="<?php echo esc_url( add_query_arg( 'view', 'calendar' ) ); ?>" class="btn btn-brand align-self-start">
                    <i class="fa-solid fa-calendar-days me-2"></i>Quick Join Class
                </a>
            </div>
        </div>

        <div class="admin-metrics-grid">
            <?php
            echo $this->render_metric_card( 'Subjects', absint( count( $subjects ) ), 'fa-book-open', 'cyan', 'Enrolled' );
            echo $this->render_metric_card( 'Upcoming Learn', absint( count( $classes ) ), 'fa-calendar-days', 'emerald', 'Scheduled sessions' );
            echo $this->render_metric_card( 'Attendance', $attendance_percent . '%', 'fa-check-circle', 'amber', 'Current rate' );
            echo $this->render_metric_card( 'Watch Progress', $watch_avg . '%', 'fa-play-circle', 'rose', 'Recent lessons' );
            ?>
        </div>

        <div class="row gy-4 mt-4">
            <div class="col-xl-6">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="eyebrow">Today</span>
                            <h3 class="mb-0">Upcoming classes</h3>
                        </div>
                        <a href="<?php echo esc_url( add_query_arg( 'view', 'live-classes' ) ); ?>" class="text-white small">View all</a>
                    </div>
                    <?php if ( ! empty( $classes ) ) : ?>
                        <?php foreach ( array_slice( $classes, 0, 4 ) as $class ) : ?>
                            <div class="student-class-card mb-3 p-3 rounded-4 border border-white-10">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h5 class="mb-1"><?php echo esc_html( $class->title ); ?></h5>
                                        <p class="text-muted small mb-0"><?php echo esc_html( $class->subject_title ); ?> • <?php echo esc_html( $class->teacher_name ); ?></p>
                                    </div>
                                    <span class="badge bg-<?php echo 'live' === $class->live_status ? 'success' : 'secondary'; ?> text-uppercase"><?php echo esc_html( strtoupper( $class->live_status ) ); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted"><?php echo esc_html( date_i18n( 'M j, Y @ H:i', strtotime( $class->start_time ) ) ); ?></small>
                                    <?php if ( 'live' === $class->live_status && ! empty( $class->meeting_link ) ) : ?>
                                        <a href="<?php echo esc_url( $class->meeting_link ); ?>" target="_blank" class="btn btn-sm btn-brand">Join</a>
                                    <?php else : ?>
                                        <button type="button" class="btn btn-sm btn-outline-light" disabled>Waiting</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <?php echo $this->render_empty_state( 'No live classes scheduled', 'Enroll in a subject or ask your teacher to schedule your next session.' ); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-xl-6">
                <div class="glass-card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="eyebrow">Recent</span>
                            <h3 class="mb-0">Recommended recordings</h3>
                        </div>
                        <a href="<?php echo esc_url( add_query_arg( 'view', 'recorded-classes' ) ); ?>" class="text-white small">Browse library</a>
                    </div>
                    <?php if ( ! empty( $videos ) ) : ?>
                        <div class="row g-3">
                            <?php foreach ( $videos as $video ) : ?>
                                <div class="col-12">
                                    <div class="recorded-card p-3 rounded-4 border border-white-10 d-flex gap-3 align-items-center">
                                        <img src="<?php echo esc_url( $video->thumbnail_url ?: $video->thumbnail ); ?>" alt="<?php echo esc_attr( $video->title ); ?>" class="rounded-3" width="120" height="68">
                                        <div class="flex-fill">
                                            <h6 class="mb-1"><?php echo esc_html( $video->title ); ?></h6>
                                            <p class="text-muted small mb-0"><?php echo esc_html( $video->subject_title ); ?> • <?php echo esc_html( $video->duration ); ?></p>
                                        </div>
                                        <a href="<?php echo esc_url( site_url( '/video-player/?edtech_video_id=' . absint( $video->id ) ) ); ?>" class="btn btn-sm btn-outline-light">Watch</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <?php echo $this->render_empty_state( 'No recorded lessons yet', 'Your teacher will add video lessons for your enrolled subjects soon.' ); ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="row gy-4 mt-4">
            <div class="col-lg-8">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="eyebrow">Alerts</span>
                            <h3 class="mb-0">Teacher announcements</h3>
                        </div>
                        <a href="<?php echo esc_url( add_query_arg( 'view', 'announcements' ) ); ?>" class="text-white small">View all</a>
                    </div>
                    <?php if ( ! empty( $notifications ) ) : ?>
                        <div class="list-group notifications-feed">
                            <?php foreach ( $notifications as $note ) : ?>
                                <div class="list-group-item bg-dark border-0 rounded-4 mb-3 p-3">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong><?php echo esc_html( $note->title ); ?></strong>
                                            <p class="text-muted small mb-1"><?php echo esc_html( wp_trim_words( $note->message, 18, '...' ) ); ?></p>
                                        </div>
                                        <small class="text-muted"><?php echo esc_html( date_i18n( 'M j', strtotime( $note->created_at ) ) ); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <?php echo $this->render_empty_state( 'No announcements yet', 'Your teacher will send announcements for scheduled classes and assignments.' ); ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="eyebrow">Progress</span>
                            <h3 class="mb-0">Learning streak</h3>
                        </div>
                        <span class="badge bg-emerald"><?php echo absint( $learning_streak ); ?> days</span>
                    </div>
                    <p class="text-muted mb-4">You’ve kept your study rhythm going with recent lesson activity and attendance.</p>
                    <div class="progress progress-glow mb-3" style="height: 12px;">
                        <div class="progress-bar bg-brand" role="progressbar" style="width: <?php echo absint( $watch_avg ); ?>%;" aria-valuenow="<?php echo absint( $watch_avg ); ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="text-muted small mb-0">Average watch progress across recent videos: <?php echo absint( $watch_avg ); ?>%</p>
                </div>
                <div class="glass-card p-4 mt-3">
                    <h6 class="text-white mb-3">Assignment snapshot</h6>
                    <p class="text-muted small mb-2">Pending tasks are surfaced under the Assignments tab.</p>
                    <p class="text-white mb-0"><?php echo absint( count( $this->db->get_student_tasks( $user_id, 'assignment', 20 ) ) ); ?> task notifications</p>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_subjects() {
        $user_id = get_current_user_id();
        $subjects = $this->db->get_student_subjects( $user_id );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">My Subjects</h1>
                <p class="text-muted mb-0">All subjects you are enrolled in, with teachers and active progress tracking.</p>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search subjects, teachers, status...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th data-sort="text">Subject</th>
                            <th>Teacher</th>
                            <th data-sort="text">Enrolled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $subjects ) ) : ?>
                            <?php foreach ( $subjects as $subject ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $subject->title ); ?></td>
                                    <td><?php echo esc_html( $subject->teacher_name ); ?></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $subject->created_at ?? current_time( 'mysql' ) ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="3"><?php echo $this->render_empty_state( 'You have not enrolled in any subjects yet', 'Use the dashboard to choose subjects and begin learning immediately.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_live_classes() {
        $user_id = get_current_user_id();
        $classes = $this->db->get_live_classes_for_student( $user_id );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Live Classes</h1>
                <p class="text-muted mb-0">Upcoming sessions, ongoing lessons, and join links for your enrolled subjects.</p>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search live classes...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="8">
                    <thead>
                        <tr>
                            <th data-sort="text">Class</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Status</th>
                            <th data-sort="text">Start</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $classes ) ) : ?>
                            <?php foreach ( $classes as $class ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $class->title ); ?></td>
                                    <td><?php echo esc_html( $class->subject_title ); ?></td>
                                    <td><?php echo esc_html( $class->teacher_name ); ?></td>
                                    <td><?php echo $this->render_status_badge( $class->live_status ); ?></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y @ H:i', strtotime( $class->start_time ) ) ); ?></td>
                                    <td>
                                        <?php if ( 'live' === $class->live_status && ! empty( $class->meeting_link ) ) : ?>
                                            <a href="<?php echo esc_url( $class->meeting_link ); ?>" target="_blank" class="btn btn-sm btn-success">Join Now</a>
                                        <?php else : ?>
                                            <button class="btn btn-sm btn-outline-light" disabled>Wait</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="6"><?php echo $this->render_empty_state( 'No live classes found', 'Your teacher has not scheduled any live sessions for your subjects yet.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_recorded_classes() {
        if ( ! class_exists( 'Edtech_Recorded_Classes' ) ) {
            return '<div class="glass-card p-5 text-center"><h3>Recorded class library unavailable.</h3></div>';
        }

        $recorded = new Edtech_Recorded_Classes( $this->db, $this->helpers );
        return $recorded->render_video_library();
    }

    private function render_student_assignments() {
        $user_id = get_current_user_id();
        $assignments = $this->db->get_student_tasks( $user_id, 'assignment', 20 );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Assignments</h1>
                <p class="text-muted mb-0">Track your pending submissions, deadlines, and teacher feedback in one place.</p>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search assignments...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Status</th>
                            <th data-sort="text">Received</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $assignments ) ) : ?>
                            <?php foreach ( $assignments as $task ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $task->title ); ?></td>
                                    <td><span class="badge bg-<?php echo $task->is_read ? 'success' : 'warning'; ?>"><?php echo esc_html( $task->is_read ? 'Open' : 'New' ); ?></span></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $task->created_at ) ) ); ?></td>
                                    <td><?php echo esc_html( wp_trim_words( $task->message, 14, '...' ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4"><?php echo $this->render_empty_state( 'No assignments available', 'Once your teacher publishes assignment details, they will appear here.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_exams() {
        $user_id = get_current_user_id();
        $exams = $this->db->get_student_tasks( $user_id, 'exam', 20 );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Exams</h1>
                <p class="text-muted mb-0">Review upcoming exam reminders and performance notes from your instructors.</p>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search exams...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th>Exam</th>
                            <th>Status</th>
                            <th data-sort="text">Posted</th>
                            <th>Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $exams ) ) : ?>
                            <?php foreach ( $exams as $exam ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $exam->title ); ?></td>
                                    <td><span class="badge bg-<?php echo $exam->is_read ? 'success' : 'secondary'; ?>"><?php echo esc_html( $exam->is_read ? 'Scheduled' : 'New' ); ?></span></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $exam->created_at ) ) ); ?></td>
                                    <td><?php echo esc_html( wp_trim_words( $exam->message, 14, '...' ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4"><?php echo $this->render_empty_state( 'No exam reminders yet', 'Your teacher will post exam schedules and study notes here.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_attendance() {
        $user_id = get_current_user_id();
        $records = $this->db->get_student_attendance_records( $user_id, 80 );
        $subjects = $this->db->get_student_subjects( $user_id );
        $attendance_rate = 0;
        if ( ! empty( $subjects ) ) {
            $attendance_rate = min( 100, round( ( count( $records ) / max( 1, count( $subjects ) * 4 ) ) * 100 ) );
        }
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Attendance</h1>
                <p class="text-muted mb-0">Check your subject-wise attendance and recent participation history.</p>
            </div>
        </div>
        <div class="row gy-4 mb-4">
            <div class="col-md-6 col-xl-4">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Attendance Rate</p>
                            <h2 class="text-white mb-0"><?php echo absint( $attendance_rate ); ?>%</h2>
                        </div>
                        <i class="fa-solid fa-chart-line fa-3x text-cyan opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Recorded Sessions</p>
                            <h2 class="text-white mb-0"><?php echo absint( count( $records ) ); ?></h2>
                        </div>
                        <i class="fa-solid fa-check fa-3x text-emerald opacity-50"></i>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-xl-4">
                <div class="glass-card p-4 h-100 stat-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Active Subjects</p>
                            <h2 class="text-white mb-0"><?php echo absint( count( $subjects ) ); ?></h2>
                        </div>
                        <i class="fa-solid fa-book fa-3x text-amber opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="glass-card admin-table-card">
            <?php echo $this->render_table_toolbar( 'Search attendance records...' ); ?>
            <div class="table-responsive">
                <table class="table admin-table edtech-data-table" data-page-size="10">
                    <thead>
                        <tr>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th data-sort="text">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! empty( $records ) ) : ?>
                            <?php foreach ( $records as $record ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $record->class_title ); ?></td>
                                    <td><?php echo esc_html( $record->subject_title ); ?></td>
                                    <td><?php echo $this->render_status_badge( $record->status ); ?></td>
                                    <td><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $record->attended_at ) ) ); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="4"><?php echo $this->render_empty_state( 'No attendance records', 'Your attendance history will appear once you join live sessions.' ); ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_announcements() {
        $user_id = get_current_user_id();
        $announcements = $this->db->get_student_tasks( $user_id, 'announcement', 20 );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Announcements</h1>
                <p class="text-muted mb-0">Teacher updates, live class alerts, and platform announcements for your enrolled subjects.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php if ( ! empty( $announcements ) ) : ?>
                <div class="list-group">
                    <?php foreach ( $announcements as $announcement ) : ?>
                        <div class="list-group-item bg-dark border-0 rounded-4 mb-3 p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?php echo esc_html( $announcement->title ); ?></strong>
                                    <p class="text-muted small mb-0"><?php echo esc_html( wp_trim_words( $announcement->message, 18, '...' ) ); ?></p>
                                </div>
                                <span class="text-muted small"><?php echo esc_html( date_i18n( 'M j', strtotime( $announcement->created_at ) ) ); ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <?php echo $this->render_empty_state( 'No announcements yet', 'Announcements from your teachers and the platform appear here when available.' ); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_calendar() {
        $user_id = get_current_user_id();
        $classes = $this->db->get_live_classes_for_student( $user_id );
        $tasks = array_merge(
            $this->db->get_student_tasks( $user_id, 'assignment', 10 ),
            $this->db->get_student_tasks( $user_id, 'exam', 10 )
        );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Calendar</h1>
                <p class="text-muted mb-0">A consolidated schedule of your live classes, assignment deadlines, and exam reminders.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php if ( ! empty( $classes ) || ! empty( $tasks ) ) : ?>
                <div class="timeline-list">
                    <?php foreach ( $classes as $class ) : ?>
                        <div class="timeline-item mb-3 p-4 rounded-4 bg-dark border border-white-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo esc_html( $class->title ); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo esc_html( $class->subject_title ); ?> class</p>
                                </div>
                                <span class="badge bg-<?php echo 'live' === $class->live_status ? 'success' : 'secondary'; ?>"><?php echo esc_html( ucfirst( $class->live_status ) ); ?></span>
                            </div>
                            <p class="text-muted small mb-1"><?php echo esc_html( date_i18n( 'M j, Y @ H:i', strtotime( $class->start_time ) ) ); ?></p>
                            <?php if ( ! empty( $class->meeting_link ) ) : ?>
                                <a href="<?php echo esc_url( $class->meeting_link ); ?>" target="_blank" class="btn btn-sm btn-brand">Join</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <?php foreach ( $tasks as $task ) : ?>
                        <div class="timeline-item mb-3 p-4 rounded-4 bg-dark border border-white-10">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo esc_html( $task->title ); ?></h6>
                                    <p class="text-muted small mb-0"><?php echo esc_html( ucfirst( $task->type ) ); ?> reminder</p>
                                </div>
                                <span class="badge bg-amber"><?php echo esc_html( ucfirst( $task->type ) ); ?></span>
                            </div>
                            <p class="text-muted small mb-1"><?php echo esc_html( date_i18n( 'M j, Y', strtotime( $task->created_at ) ) ); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <?php echo $this->render_empty_state( 'No calendar events yet', 'Live classes, assignment reminders, and exam notices will appear here once scheduled.' ); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_messages() {
        $user_id = get_current_user_id();
        $messages = $this->db->get_student_message_threads( $user_id, 20 );
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Messages</h1>
                <p class="text-muted mb-0">Direct communications and alerts from your teachers and course managers.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <?php if ( ! empty( $messages ) ) : ?>
                <div class="list-group">
                    <?php foreach ( $messages as $message ) : ?>
                        <div class="list-group-item bg-dark border-0 rounded-4 mb-3 p-4">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <strong><?php echo esc_html( $message->title ); ?></strong>
                                    <p class="text-muted small mb-0"><?php echo esc_html( wp_trim_words( $message->message, 18, '...' ) ); ?></p>
                                </div>
                                <small class="text-muted"><?php echo esc_html( date_i18n( 'M j', strtotime( $message->created_at ) ) ); ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <?php echo $this->render_empty_state( 'No direct messages', 'Your teacher or platform support will send messages here when needed.' ); ?>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_analytics() {
        $user_id = get_current_user_id();
        $subjects = $this->db->get_student_subjects( $user_id );
        $classes = $this->db->get_live_classes_for_student( $user_id );
        $attendance_records = $this->db->get_student_attendance_records( $user_id, 200 );
        $watch_history = $this->db->get_recent_watch_history( $user_id, 12 );
        $notifications = $this->db->get_student_notifications( $user_id, 12 );

        $attendance_percent = 0;
        if ( ! empty( $subjects ) ) {
            $attendance_percent = min( 100, round( ( count( $attendance_records ) / max( 1, count( $subjects ) * 4 ) ) * 100 ) );
        }

        $watch_avg = 0;
        if ( $watch_history ) {
            $total = 0;
            foreach ( $watch_history as $history ) {
                $total += intval( $history->progress );
            }
            $watch_avg = round( $total / max( 1, count( $watch_history ) ) );
        }

        $chart_payload = array(
            'progress' => array(
                'labels' => array( 'Subjects', 'Classes', 'Attendance', 'Recordings' ),
                'values' => array( absint( count( $subjects ) ), absint( count( $classes ) ), absint( $attendance_percent ), absint( $watch_avg ) ),
            ),
            'trend' => array(
                'labels' => array( date_i18n( 'M', strtotime( '-4 months' ) ), date_i18n( 'M', strtotime( '-3 months' ) ), date_i18n( 'M', strtotime( '-2 months' ) ), date_i18n( 'M', strtotime( '-1 month' ) ), date_i18n( 'M' ) ),
                'attendance' => array_fill( 0, 5, absint( $attendance_percent ) ),
                'completion' => array_fill( 0, 5, absint( $watch_avg ) ),
            ),
        );

        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Progress Analytics</h1>
                <p class="text-muted mb-0">A data-driven overview of your attendance, completion, and study momentum.</p>
            </div>
        </div>
        <div class="admin-metrics-grid">
            <?php
            echo $this->render_metric_card( 'Subjects', absint( count( $subjects ) ), 'fa-book-open', 'cyan' );
            echo $this->render_metric_card( 'Live Sessions', absint( count( $classes ) ), 'fa-video', 'emerald' );
            echo $this->render_metric_card( 'Attendance', $attendance_percent . '%', 'fa-check-circle', 'amber' );
            echo $this->render_metric_card( 'Watch Progress', $watch_avg . '%', 'fa-play-circle', 'rose' );
            ?>
        </div>
        <div class="admin-analytics-grid mt-4">
            <div class="glass-card admin-chart-card admin-chart-card-wide">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Trend</span>
                        <h3>Learning momentum</h3>
                    </div>
                </div>
                <canvas id="studentTrendChart" height="140" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['trend'] ) ); ?>"></canvas>
            </div>
            <div class="glass-card admin-chart-card">
                <div class="panel-heading">
                    <div>
                        <span class="eyebrow">Goal</span>
                        <h3>Progress mix</h3>
                    </div>
                </div>
                <canvas id="studentProgressDonut" height="180" data-chart="<?php echo esc_attr( wp_json_encode( $chart_payload['progress'] ) ); ?>"></canvas>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_profile() {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Profile</h1>
                <p class="text-muted mb-0">Update your bio, grade, interests, and personal details.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <form class="edtech-profile-form">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="full_name" value="<?php echo esc_attr( $user->display_name ); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" value="<?php echo esc_attr( $user->user_email ); ?>" readonly>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Grade</label>
                        <input type="text" class="form-control" name="grade" value="<?php echo esc_attr( get_user_meta( $user_id, 'grade', true ) ); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">City</label>
                        <input type="text" class="form-control" name="city" value="<?php echo esc_attr( get_user_meta( $user_id, 'city', true ) ); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent / Guardian</label>
                        <input type="text" class="form-control" name="parent_name" value="<?php echo esc_attr( get_user_meta( $user_id, 'parent_name', true ) ); ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Parent Phone</label>
                        <input type="text" class="form-control" name="parent_phone" value="<?php echo esc_attr( get_user_meta( $user_id, 'parent_phone', true ) ); ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Bio</label>
                        <textarea class="form-control" name="bio" rows="4"><?php echo esc_textarea( get_user_meta( $user_id, 'bio', true ) ); ?></textarea>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-brand">Save Profile</button>
                    </div>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_settings() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Settings</h1>
                <p class="text-muted mb-0">Configure your notifications, privacy, and dashboard preferences.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <div class="settings-grid">
                <section>
                    <h5 class="mb-3">Notifications</h5>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="studentNotificationsToggle" checked>
                        <label class="form-check-label" for="studentNotificationsToggle">Class & assignment alerts</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="studentEmailToggle">
                        <label class="form-check-label" for="studentEmailToggle">Email notifications</label>
                    </div>
                </section>
                <section>
                    <h5 class="mb-3">Display</h5>
                    <p class="text-muted mb-3">Switch between premium glass mode and a lighter layout for focus.</p>
                    <button type="button" class="btn btn-outline-light btn-sm" data-admin-theme-toggle>Toggle Theme</button>
                </section>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_student_support() {
        ob_start();
        ?>
        <div class="edtech-dashboard-header mb-5">
            <div>
                <h1 class="section-title mb-2">Support</h1>
                <p class="text-muted mb-0">Contact platform support or your teacher for questions about courses and access.</p>
            </div>
        </div>
        <div class="glass-card p-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100">
                        <h5>Need help quickly?</h5>
                        <p class="text-muted">Reach out to the support team for course, account, or access issues.</p>
                        <p class="mb-1"><strong>Email</strong></p>
                        <p class="text-white">support@edtech-platform.local</p>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card p-4 h-100">
                        <h5>Report an issue</h5>
                        <p class="text-muted">Send a support request with details about your problem.</p>
                        <a href="mailto:support@edtech-platform.local?subject=Student%20Support%20Request" class="btn btn-brand">Email Support</a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function get_admin_summary() {
        global $wpdb;

        $students           = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_students" ) );
        $teachers           = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_teachers" ) );
        $subjects           = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_subjects" ) );
        $categories         = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_subject_categories" ) );
        $live_classes       = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_live_classes WHERE live_status='live'" ) );
        $recorded_classes   = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_recorded_classes" ) );
        $attendance_records = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_attendance" ) );
        $pending_students   = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_students WHERE status='pending'" ) );
        $pending_teachers   = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_teachers WHERE status='pending'" ) );
        $watch_seconds      = absint( $wpdb->get_var( "SELECT COALESCE(SUM(watch_time), 0) FROM {$wpdb->prefix}lms_video_history" ) );
        $current_month      = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_students WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')" ) );
        $previous_month     = absint( $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}lms_students WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01') AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')" ) );
        $monthly_growth     = $previous_month > 0 ? round( ( ( $current_month - $previous_month ) / $previous_month ) * 100 ) : ( $current_month > 0 ? 100 : 0 );
        $engagement_rate    = $students > 0 ? min( 100, round( ( $attendance_records / max( 1, $students ) ) * 100 ) ) : 0;

        return array(
            'students'           => $students,
            'teachers'           => $teachers,
            'subjects'           => $subjects,
            'categories'         => $categories,
            'live_classes'       => $live_classes,
            'recorded_classes'   => $recorded_classes,
            'attendance_records' => $attendance_records,
            'pending_students'   => $pending_students,
            'pending_teachers'   => $pending_teachers,
            'watch_time_hours'   => round( $watch_seconds / HOUR_IN_SECONDS, 1 ),
            'monthly_growth'     => $monthly_growth,
            'engagement_rate'    => $engagement_rate,
        );
    }
}
