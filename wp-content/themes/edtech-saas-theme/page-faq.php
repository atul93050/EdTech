<?php
/* Template Name: FAQ Page */
get_header();
?>
<section class="page-hero py-5">
    <div class="container">
        <div class="row justify-content-center text-center">
            <div class="col-lg-8">
                <p class="badge bg-primary bg-opacity-15 text-primary rounded-pill px-3 py-2 mb-3">FAQ</p>
                <h1 class="hero-headline mb-4">Questions teachers, students and admins ask most</h1>
                <p class="section-description">Learn how registration, live classes, approvals and dashboards work in this EdTech SaaS platform.</p>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="accordion" id="faqPageAccordion">
                    <?php
                    $faqs = array(
                        array('How do students join a live class?', 'After logging in, students can open their enrolled subject and click the live class button when the teacher starts the session.'),
                        array('What does the admin dashboard manage?', 'Admins approve teacher registrations, verify teacher profiles, and monitor subject assignments from the frontend admin page.'),
                        array('Can teachers create new subjects?', 'Teachers can only assign themselves to existing subjects. Admins manage the master list of subjects and categories.'),
                        array('Is there a role-based login system?', 'Yes. Teachers and students have separate login pages, and admins have additional backend approval controls.'),
                        array('Does it work on mobile devices?', 'Yes. Every page in the theme is built with responsive Bootstrap layout and mobile-friendly cards, buttons, and forms.'),
                    );
                    foreach ( $faqs as $index => $faq ) : ?>
                        <div class="accordion-item glass-card border-0 mb-3">
                            <h2 class="accordion-header" id="faqHeading<?php echo esc_attr( $index ); ?>">
                                <button class="accordion-button collapsed bg-transparent text-dark" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?php echo esc_attr( $index ); ?>" aria-expanded="false" aria-controls="faqCollapse<?php echo esc_attr( $index ); ?>">
                                    <?php echo esc_html( $faq[0] ); ?>
                                </button>
                            </h2>
                            <div id="faqCollapse<?php echo esc_attr( $index ); ?>" class="accordion-collapse collapse" aria-labelledby="faqHeading<?php echo esc_attr( $index ); ?>" data-bs-parent="#faqPageAccordion">
                                <div class="accordion-body text-muted"><?php echo esc_html( $faq[1] ); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="glass-card p-5 h-100">
                    <h3 class="mb-3">Need a faster answer?</h3>
                    <p class="text-muted">Reach out through the contact page if you want a walkthrough, deployment support, or custom theme/plugin setup.</p>
                    <div class="mt-4">
                        <p class="mb-2"><strong>Email:</strong> support@example.com</p>
                        <p class="mb-0"><strong>Phone:</strong> +91 98765 43210</p>
                    </div>
                    <a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-brand btn-sm mt-4">Contact Support</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer();