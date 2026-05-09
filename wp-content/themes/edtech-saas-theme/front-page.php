<?php
/*
Template Name: Home Page
*/
get_header();

$features = array(
    array( 'fa-video', 'Live classrooms', 'Launch scheduled sessions, share secure meeting links, and show live status across student dashboards.' ),
    array( 'fa-chart-line', 'Learning analytics', 'Track attendance, active subjects, recordings, and learner momentum from a clean dashboard.' ),
    array( 'fa-user-check', 'Role approvals', 'Approve teachers, protect student access, and keep every workflow inside frontend SaaS screens.' ),
    array( 'fa-book-open-reader', 'Recorded lessons', 'Publish YouTube-based recordings with notes, filters, and student watch history.' ),
    array( 'fa-layer-group', 'Subject workflows', 'Assign teachers and students to subjects without exposing WordPress admin screens.' ),
    array( 'fa-shield-halved', 'Secure auth', 'Separate admin, teacher, student, and WordPress backend login flows using WordPress auth.' ),
);

$stats = array(
    array( '12.8K+', 'Student enrollments' ),
    array( '38K+', 'Classes completed' ),
    array( '184', 'Active teachers' ),
    array( '91K', 'Watch hours' ),
);

$testimonials = array(
    array( 'https://randomuser.me/api/portraits/women/44.jpg', 'Mira Shah', 'Academy Director', 'The dashboard feels polished enough for our leadership team and simple enough for teachers to use daily.' ),
    array( 'https://randomuser.me/api/portraits/men/32.jpg', 'Arjun Mehta', 'Physics Teacher', 'Going live, assigning subjects, and sharing recordings now feels like one smooth product workflow.' ),
    array( 'https://randomuser.me/api/portraits/women/68.jpg', 'Priya Rao', 'Student Mentor', 'Students find live classes faster, and the interface feels calm on mobile during busy class hours.' ),
);

$plans = array(
    array( 'Starter', 'For new coaching cohorts', 'Free', array( 'Student onboarding', 'Live class discovery', 'Basic attendance view' ), false ),
    array( 'Growth', 'For active academies', '$29', array( 'Teacher approvals', 'Recorded lessons', 'Subject assignments', 'Analytics widgets' ), true ),
    array( 'Scale', 'For multi-branch teams', 'Custom', array( 'Admin operations', 'Advanced reporting', 'Priority onboarding', 'Custom workflows' ), false ),
);
?>
<div class="edtech-landing">
    <section class="page-section pt-5 mt-5">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-6">
                    <span class="section-kicker mb-4"><i class="fa-solid fa-sparkles"></i> Premium EdTech SaaS</span>
                    <h1 class="hero-headline mb-4">Run live learning with a <span class="gradient-text">startup-grade</span> coaching platform.</h1>
                    <p class="lead section-description mb-4">A modern WordPress-powered EdTech experience with frontend dashboards, secure multi-auth, live classes, recordings, and clean admin workflows.</p>
                    <div class="d-flex flex-wrap gap-3 mb-5">
                        <a href="<?php echo esc_url( site_url( '/student-register' ) ); ?>" class="btn btn-brand btn-lg px-4">Start Learning</a>
                        <a href="<?php echo esc_url( site_url( '/teacher-register' ) ); ?>" class="btn btn-outline-secondary btn-lg px-4">Become Teacher</a>
                    </div>
                    <div class="d-flex flex-wrap align-items-center gap-3">
                        <div class="avatar-stack">
                            <img src="https://randomuser.me/api/portraits/women/12.jpg" alt="Student">
                            <img src="https://randomuser.me/api/portraits/men/18.jpg" alt="Teacher">
                            <img src="https://randomuser.me/api/portraits/women/28.jpg" alt="Admin">
                        </div>
                        <div>
                            <div class="fw-bold">Trusted by 120+ learning teams</div>
                            <div class="text-muted small">Live classes, analytics, and student growth in one calm workspace.</div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-mockup">
                        <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80" alt="Modern classroom dashboard preview">
                        <div class="mockup-panel">
                            <div>
                                <div class="small text-muted">Today’s live session</div>
                                <div class="fw-bold">Algebra Masterclass</div>
                            </div>
                            <span class="badge rounded-pill text-bg-light text-success px-3 py-2"><i class="fa-solid fa-circle me-1 small"></i> Live</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="pb-5">
        <div class="container">
            <div class="row row-cols-2 row-cols-md-3 row-cols-lg-6 g-3 align-items-center">
                <?php foreach ( array( 'Northstar', 'Learnly', 'AcademIQ', 'SkillGrid', 'MentorHub', 'ClassPeak' ) as $logo ) : ?>
                    <div class="col"><div class="trust-logo"><?php echo esc_html( $logo ); ?></div></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="page-section page-section-soft">
        <div class="container">
            <div class="row justify-content-between align-items-end gy-3 mb-5">
                <div class="col-lg-7">
                    <span class="section-kicker mb-3">Platform features</span>
                    <h2 class="section-title mb-3">Everything a modern learning team needs, without UI clutter.</h2>
                </div>
                <div class="col-lg-4">
                    <p class="section-description mb-0">Clean role-based tools for admins, teachers, and students with responsive layouts and smooth daily workflows.</p>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ( $features as $feature ) : ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="premium-card p-4 h-100">
                            <div class="feature-icon"><i class="fa-solid <?php echo esc_attr( $feature[0] ); ?>"></i></div>
                            <h5 class="fw-bold mb-2"><?php echo esc_html( $feature[1] ); ?></h5>
                            <p class="text-muted mb-0"><?php echo esc_html( $feature[2] ); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="row align-items-center gy-5">
                <div class="col-lg-5">
                    <span class="section-kicker mb-3">Case study</span>
                    <h2 class="section-title mb-3">A sharper operating system for growing academies.</h2>
                    <p class="section-description">Use frontend dashboards to keep operations light: approve teachers, track subjects, launch live sessions, and guide students through recordings.</p>
                    <a class="btn btn-outline-secondary mt-3" href="<?php echo esc_url( home_url( '/features' ) ); ?>">Explore Features</a>
                </div>
                <div class="col-lg-7">
                    <div class="row g-4">
                        <?php foreach ( $stats as $stat ) : ?>
                            <div class="col-sm-6">
                                <div class="premium-card metric-card p-4 h-100">
                                    <h3 class="display-6 fw-black mb-2"><?php echo esc_html( $stat[0] ); ?></h3>
                                    <p class="text-muted mb-0"><?php echo esc_html( $stat[1] ); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="page-section page-section-soft">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-kicker mb-3">People love the flow</span>
                <h2 class="section-title mx-auto mb-3" style="max-width:760px;">Built for teachers, students, and operators who want less friction.</h2>
            </div>
            <div class="row g-4">
                <?php foreach ( $testimonials as $item ) : ?>
                    <div class="col-md-4">
                        <div class="premium-card p-4 h-100">
                            <p class="text-muted mb-4">"<?php echo esc_html( $item[3] ); ?>"</p>
                            <div class="d-flex align-items-center gap-3">
                                <img class="testimonial-avatar" src="<?php echo esc_url( $item[0] ); ?>" alt="<?php echo esc_attr( $item[1] ); ?>">
                                <div>
                                    <div class="fw-bold"><?php echo esc_html( $item[1] ); ?></div>
                                    <div class="text-muted small"><?php echo esc_html( $item[2] ); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="text-center mb-5">
                <span class="section-kicker mb-3">Pricing</span>
                <h2 class="section-title mb-3">Plans for every stage of your learning business.</h2>
                <p class="section-description mx-auto">Simple packages with premium dashboards, secure access, and responsive student experiences.</p>
            </div>
            <div class="row g-4 align-items-stretch">
                <?php foreach ( $plans as $plan ) : ?>
                    <div class="col-lg-4">
                        <div class="premium-card pricing-card <?php echo $plan[4] ? 'is-featured' : ''; ?>">
                            <div class="d-flex justify-content-between align-items-start gap-3 mb-4">
                                <div>
                                    <h3 class="h4 fw-bold mb-1"><?php echo esc_html( $plan[0] ); ?></h3>
                                    <p class="text-muted mb-0"><?php echo esc_html( $plan[1] ); ?></p>
                                </div>
                                <?php if ( $plan[4] ) : ?><span class="pricing-badge">Popular</span><?php endif; ?>
                            </div>
                            <div class="display-6 fw-black mb-4"><?php echo esc_html( $plan[2] ); ?></div>
                            <ul class="list-unstyled d-grid gap-3 mb-4">
                                <?php foreach ( $plan[3] as $line ) : ?>
                                    <li><i class="fa-solid fa-check text-info me-2"></i><?php echo esc_html( $line ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <a class="btn <?php echo $plan[4] ? 'btn-brand' : 'btn-outline-secondary'; ?> w-100" href="<?php echo esc_url( site_url( '/student-register' ) ); ?>">Choose Plan</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="page-section page-section-soft">
        <div class="container">
            <div class="row gx-5 gy-4 align-items-start">
                <div class="col-lg-5">
                    <span class="section-kicker mb-3">FAQ</span>
                    <h2 class="section-title mb-3">Questions before launch?</h2>
                    <p class="section-description">A few essentials about live lessons, dashboards, and mobile responsiveness.</p>
                </div>
                <div class="col-lg-7">
                    <div class="accordion d-grid gap-3" id="faqAccordion">
                        <?php
                        $faqs = array(
                            array( 'Does it support live classes?', 'Yes. Teachers can create live sessions, mark them live, and students can join from their dashboard.' ),
                            array( 'Can teachers register from the frontend?', 'Yes. Teacher registration uses WordPress auth internally and stays pending until admin approval.' ),
                            array( 'Is it mobile friendly?', 'Yes. Navigation, cards, tables, forms, dashboards, and auth views are responsive and touch-friendly.' ),
                        );
                        foreach ( $faqs as $index => $faq ) :
                            $target = 'faqItem' . $index;
                        ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo esc_attr( $target ); ?>" aria-expanded="false" aria-controls="<?php echo esc_attr( $target ); ?>">
                                        <?php echo esc_html( $faq[0] ); ?>
                                    </button>
                                </h2>
                                <div id="<?php echo esc_attr( $target ); ?>" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body"><?php echo esc_html( $faq[1] ); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="page-section">
        <div class="container">
            <div class="cta-band p-4 p-md-5 text-center">
                <div class="py-md-4 mx-auto" style="max-width:760px;">
                    <h2 class="section-title mb-3">Ready to make your learning platform feel premium?</h2>
                    <p class="section-description mx-auto mb-4">Create a student account, onboard teachers, and run a clean modern EdTech workflow from day one.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-3">
                        <a class="btn btn-light btn-lg px-4" href="<?php echo esc_url( site_url( '/student-register' ) ); ?>">Create Account</a>
                        <a class="btn btn-outline-light btn-lg px-4" href="<?php echo esc_url( site_url( '/admin-login' ) ); ?>">Admin Login</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
<?php get_footer();
