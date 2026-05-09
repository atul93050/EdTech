<?php
/* Template Name: Pricing Page */
get_header();
?>
<section class="page-hero py-5">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-7">
                <p class="badge bg-primary bg-opacity-15 text-primary rounded-pill px-3 py-2 mb-3">Pricing</p>
                <h1 class="hero-headline mb-4">Pricing built for student growth and academy scale</h1>
                <p class="section-description mb-4">Choose the plan that matches your coaching needs: from free student onboarding to a full enterprise school experience.</p>
            </div>
            <div class="col-lg-5">
                <div class="glass-card p-4 hero-card">
                    <h4 class="mb-3">Flexible plans</h4>
                    <p class="text-muted mb-0">No lock-in, no hidden fees. Upgrade as your education program grows.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="glass-card p-5 h-100">
                    <h3 class="text-primary">Free</h3>
                    <p class="text-muted">Start with student access, onboarding and live class discovery.</p>
                    <div class="display-5 my-4">$0</div>
                    <ul class="list-unstyled text-muted">
                        <li>Student registration</li>
                        <li>Live class view</li>
                        <li>Attendance overview</li>
                        <li>Basic support access</li>
                    </ul>
                    <a class="btn btn-outline-primary mt-4" href="#">Start Free</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card p-5 h-100 border border-info">
                    <span class="badge bg-info bg-opacity-15 text-info mb-3">Most popular</span>
                    <h3 class="text-info">Pro</h3>
                    <p class="text-muted">Teacher tools, subject assignment, live sessions, and analytics for active academies.</p>
                    <div class="display-5 my-4">$29<span class="fs-6 text-muted">/month</span></div>
                    <ul class="list-unstyled text-muted">
                        <li>Teacher approval flows</li>
                        <li>Subject management</li>
                        <li>Live lesson scheduling</li>
                        <li>Advanced analytics</li>
                    </ul>
                    <a class="btn btn-brand mt-4" href="#">Upgrade Now</a>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="glass-card p-5 h-100">
                    <h3 class="text-white">Enterprise</h3>
                    <p class="text-muted">Custom academy deployments and priority support for multi-location education teams.</p>
                    <div class="display-5 my-4">Custom</div>
                    <ul class="list-unstyled text-muted">
                        <li>Custom onboarding</li>
                        <li>Dedicated support</li>
                        <li>Attendance reports</li>
                        <li>Priority feature requests</li>
                    </ul>
                    <a class="btn btn-outline-primary mt-4" href="#">Contact Sales</a>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-primary-soft">
    <div class="container">
        <div class="row align-items-center gy-4">
            <div class="col-lg-8">
                <h2 class="section-title">What you get with every plan</h2>
                <p class="section-description">Every plan includes secure user roles, responsive dashboards, and reliable frontend management.</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <a href="<?php echo esc_url( site_url( '/contact' ) ); ?>" class="btn btn-brand btn-sm">Talk to sales</a>
            </div>
        </div>
    </div>
</section>

<?php get_footer();