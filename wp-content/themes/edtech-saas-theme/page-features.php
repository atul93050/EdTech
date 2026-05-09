<?php
/* Template Name: Features Page */
get_header();
?>
<section class="page-hero py-5">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-6">
                <p class="badge bg-primary bg-opacity-15 text-primary rounded-pill px-3 py-2 mb-3">Features</p>
                <h1 class="hero-headline mb-4">Everything you need for a seamless learning platform</h1>
                <p class="section-description mb-4">Our feature set is designed to support live lessons, secure access, analytics and user-friendly workflows for each role.</p>
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-4 hero-card">
                    <h4 class="mb-3">Built for everyday education use</h4>
                    <p class="text-muted mb-0">The platform combines polished landing pages with powerful dashboard controls for teachers and students.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <?php $items = array(
                array('Live Class Studio','Teachers can start, update and end live sessions with instant notifications.'),
                array('Student Dashboard','Students view assigned subjects, attendance and live session details.'),
                array('Teacher Workflow','Manage subjects, launch sessions, and track participation.'),
                array('Admin Control','Approve profiles, assign subjects, and review usage reports.'),
                array('Secure Access','Role-based login with frontend dashboards for teachers and students.'),
                array('Realtime Alerts','Stay informed with live class badges, notifications and reminders.'),
            );
            foreach ($items as $item) : ?>
                <div class="col-lg-4">
                    <div class="glass-card p-4 h-100">
                        <h4 class="mb-3 text-white"><?php echo esc_html( $item[0] ); ?></h4>
                        <p class="text-muted mb-0"><?php echo esc_html( $item[1] ); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-primary-soft">
    <div class="container text-center">
        <div class="glass-card p-5 mx-auto" style="max-width:800px;">
            <h2 class="section-title">Why this platform works for coaching academies</h2>
            <p class="section-description">It delivers a premium, modern SaaS experience while keeping your WordPress installation fast, secure and easy to manage.</p>
        </div>
    </div>
</section>

<?php get_footer();