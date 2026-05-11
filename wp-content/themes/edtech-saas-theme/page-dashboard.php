<?php
/* Template Name: Dashboard Page */
get_header();

$dashboard_type = get_query_var( 'edtech_dashboard', 'any' );
$user           = wp_get_current_user();
$is_admin_user  = in_array( 'administrator', (array) $user->roles, true ) || in_array( 'edtech_super_admin', (array) $user->roles, true );
$is_teacher_user = in_array( 'edtech_teacher', (array) $user->roles, true );
$is_student_user = in_array( 'edtech_student', (array) $user->roles, true );

$is_admin_shell   = ( 'admin' === $dashboard_type || ( 'any' === $dashboard_type && $is_admin_user ) );
$is_teacher_shell = ( 'teacher' === $dashboard_type || ( 'any' === $dashboard_type && $is_teacher_user ) );
$is_student_shell = ( 'student' === $dashboard_type || ( 'any' === $dashboard_type && $is_student_user ) );
$show_dashboard_shell = $is_admin_shell || $is_teacher_shell || $is_student_shell;

if ( ! $show_dashboard_shell ) :
    ?>
    <section class="py-5 mt-5">
        <div class="container">
            <?php echo do_shortcode( '[edtech_dashboard]' ); ?>
        </div>
    </section>
    <?php
    get_footer();
    return;
endif;

$current_view = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'overview';
$titles       = array(
    'overview'         => 'Dashboard',
    'students'         => 'Students',
    'teachers'         => 'Teachers',
    'subjects'         => 'Subjects',
    'categories'       => 'Subject Categories',
    'enrollments'      => 'Enrollments',
    'live-classes'     => 'Live Classes',
    'recorded-classes' => 'Recorded Classes',
    'attendance'       => 'Attendance',
    'assignments'      => 'Assignments',
    'exams'            => 'Exams',
    'announcements'    => 'Announcements',
    'analytics'        => 'Analytics',
    'calendar'         => 'Calendar',
    'messages'         => 'Messages',
    'profile'          => 'Profile',
    'settings'         => 'Settings',
    'support'          => 'Support',
);

if ( $is_teacher_shell ) {
    $nav_groups = array(
        'Workspace' => array(
            array( 'overview', 'Dashboard', 'fa-house' ),
            array( 'subjects', 'My Subjects', 'fa-book-open' ),
            array( 'live-classes', 'Live Classes', 'fa-video' ),
            array( 'recorded-classes', 'Recorded Classes', 'fa-play-circle' ),
            array( 'students', 'Students', 'fa-users' ),
            array( 'attendance', 'Attendance', 'fa-clipboard-check' ),
            array( 'assignments', 'Assignments', 'fa-file-lines' ),
            array( 'exams', 'Exams', 'fa-pencil' ),
            array( 'announcements', 'Announcements', 'fa-bullhorn' ),
            array( 'analytics', 'Analytics', 'fa-chart-line' ),
            array( 'calendar', 'Calendar', 'fa-calendar-days' ),
            array( 'messages', 'Messages', 'fa-envelope' ),
        ),
        'Personal' => array(
            array( 'profile', 'Profile', 'fa-user' ),
            array( 'settings', 'Settings', 'fa-gear' ),
            array( 'support', 'Support', 'fa-life-ring' ),
        ),
    );
} elseif ( $is_student_shell ) {
    $nav_groups = array(
        'Learning' => array(
            array( 'overview', 'Dashboard', 'fa-house' ),
            array( 'subjects', 'My Subjects', 'fa-book-open' ),
            array( 'live-classes', 'Live Classes', 'fa-video' ),
            array( 'recorded-classes', 'Recorded Classes', 'fa-play-circle' ),
            array( 'assignments', 'Assignments', 'fa-file-lines' ),
            array( 'exams', 'Exams', 'fa-pencil' ),
        ),
        'Progress' => array(
            array( 'attendance', 'Attendance', 'fa-clipboard-check' ),
            array( 'announcements', 'Announcements', 'fa-bullhorn' ),
            array( 'calendar', 'Calendar', 'fa-calendar-days' ),
            array( 'analytics', 'Analytics', 'fa-chart-line' ),
        ),
        'Connect' => array(
            array( 'messages', 'Messages', 'fa-envelope' ),
        ),
        'Account' => array(
            array( 'profile', 'Profile', 'fa-user' ),
            array( 'settings', 'Settings', 'fa-gear' ),
            array( 'support', 'Support', 'fa-life-ring' ),
        ),
    );
} else {
    $nav_groups = array(
        'Command' => array(
            array( 'overview', 'Dashboard', 'fa-gauge-high' ),
            array( 'students', 'Students', 'fa-user-graduate' ),
            array( 'teachers', 'Teachers', 'fa-chalkboard-user' ),
        ),
        'LMS' => array(
            array( 'subjects', 'Subjects', 'fa-book-open' ),
            array( 'categories', 'Subject Categories', 'fa-layer-group' ),
            array( 'enrollments', 'Enrollments', 'fa-diagram-project' ),
            array( 'live-classes', 'Live Classes', 'fa-video' ),
            array( 'recorded-classes', 'Recorded Classes', 'fa-circle-play' ),
            array( 'attendance', 'Attendance', 'fa-clipboard-check' ),
        ),
        'Assess' => array(
            array( 'assignments', 'Assignments', 'fa-file-signature' ),
            array( 'exams', 'Exams', 'fa-pen-nib' ),
        ),
        'Ops' => array(
            array( 'notifications', 'Notifications', 'fa-bell' ),
            array( 'reports', 'Reports', 'fa-file-export' ),
            array( 'analytics', 'Analytics', 'fa-chart-line' ),
        ),
    );
}
?>
<div class="edtech-admin-shell" data-admin-shell>
    <div class="admin-mobile-scrim" data-sidebar-close></div>

    <aside class="admin-sidebar" data-admin-sidebar>
        <div class="sidebar-profile">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="sidebar-logo">
                <?php echo edtech_saas_get_logo(); ?>
            </a>
            <div class="profile-chip">
                <span class="profile-avatar"><?php echo esc_html( strtoupper( substr( $user->display_name ?: 'A', 0, 1 ) ) ); ?></span>
                <div>
                    <strong><?php echo esc_html( $user->display_name ?: 'User' ); ?></strong>
                    <small><?php 
                        if ( $is_admin_shell ) echo 'Super Admin';
                        elseif ( $is_teacher_shell ) echo 'Teacher';
                        elseif ( $is_student_shell ) echo 'Student';
                    ?></small>
                </div>
            </div>
        </div>

        <nav class="sidebar-nav" aria-label="Admin navigation">
            <?php foreach ( $nav_groups as $group => $items ) : ?>
                <div class="nav-group">
                    <span><?php echo esc_html( $group ); ?></span>
                    <?php foreach ( $items as $item ) : ?>
                        <?php
                        list( $view, $label, $icon ) = $item;
                        $active = $current_view === $view;
                        ?>
                        <a class="<?php echo $active ? 'is-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'view', $view ) ); ?>" data-tooltip="<?php echo esc_attr( $label ); ?>">
                            <i class="fa-solid <?php echo esc_attr( $icon ); ?>"></i>
                            <span><?php echo esc_html( $label ); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </nav>

        <div class="sidebar-bottom">
            <a href="<?php echo esc_url( add_query_arg( 'view', 'settings' ) ); ?>" class="<?php echo 'settings' === $current_view ? 'is-active' : ''; ?>" data-tooltip="Settings">
                <i class="fa-solid fa-gear"></i><span>Settings</span>
            </a>
            <a href="<?php echo esc_url( add_query_arg( 'view', 'support' ) ); ?>" class="<?php echo 'support' === $current_view ? 'is-active' : ''; ?>" data-tooltip="Support">
                <i class="fa-solid fa-life-ring"></i><span>Support</span>
            </a>
            <button type="button" data-admin-theme-toggle data-tooltip="Theme">
                <i class="fa-solid fa-circle-half-stroke"></i><span>Theme</span>
            </button>
            <button type="button" class="edtech-logout-button" data-tooltip="Logout">
                <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
            </button>
        </div>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div class="topbar-left">
                <button type="button" class="icon-btn sidebar-toggle" data-sidebar-toggle aria-label="Open navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <button type="button" class="icon-btn sidebar-collapse" data-sidebar-collapse aria-label="Collapse navigation">
                    <i class="fa-solid fa-table-columns"></i>
                </button>
                <div class="breadcrumbs">
                    <span><?php 
                        if ( $is_admin_shell ) echo 'Admin';
                        elseif ( $is_teacher_shell ) echo 'Teacher';
                        elseif ( $is_student_shell ) echo 'Student';
                    ?></span>
                    <i class="fa-solid fa-chevron-right"></i>
                    <strong><?php echo esc_html( $titles[ $current_view ] ?? 'Dashboard' ); ?></strong>
                </div>
            </div>

            <div class="topbar-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" data-admin-global-search placeholder="Search dashboard">
            </div>

            <div class="topbar-actions">
                <?php if ( $is_admin_shell ) : ?>
                    <button type="button" class="icon-btn" title="Quick Add Subject" data-edtech-subject-new>
                        <i class="fa-solid fa-plus"></i>
                    </button>
                <?php endif; ?>
                <button type="button" class="icon-btn" title="Notifications" data-admin-toast="No unread notifications.">
                    <i class="fa-solid fa-bell"></i>
                </button>
                <div class="profile-menu">
                    <span><?php echo esc_html( strtoupper( substr( $user->display_name ?: 'U', 0, 1 ) ) ); ?></span>
                </div>
            </div>
        </header>

        <div class="admin-content">
            <?php echo do_shortcode( '[edtech_dashboard view="' . esc_attr( $current_view ) . '"]' ); ?>
        </div>
    </main>
</div>
<?php get_footer(); ?>
