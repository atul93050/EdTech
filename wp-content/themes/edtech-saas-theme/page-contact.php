<?php
/* Template Name: Contact Us Page */
get_header();
?>
<section class="page-hero py-5">
    <div class="container">
        <div class="row align-items-center gy-5">
            <div class="col-lg-7">
                <p class="badge bg-primary bg-opacity-15 text-primary rounded-pill px-3 py-2 mb-3">Get in touch</p>
                <h1 class="hero-headline mb-4">Need help launching your EdTech academy?</h1>
                <p class="section-description">We’re ready to assist with setup, customization and onboarding for teachers, students and administrators.</p>
            </div>
            <div class="col-lg-5">
                <div class="glass-card p-4">
                    <h4 class="mb-3">Contact details</h4>
                    <p class="text-muted mb-4">Send us a message and we’ll respond within one business day.</p>
                    <div class="mb-3 text-muted">
                        <p class="mb-2"><strong>Email</strong><br>support@example.com</p>
                        <p class="mb-2"><strong>Phone</strong><br>+91 98765 43210</p>
                        <p class="mb-0"><strong>Office</strong><br>Banglore, India</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="glass-card p-5">
            <div class="row gy-4">
                <div class="col-lg-6">
                    <h2 class="section-title">Send us a request</h2>
                    <p class="section-description">Whether you need help with configuration, custom features, or a demo, our team is available to support.</p>
                </div>
                <div class="col-lg-6">
                    <form action="#" method="post">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control form-control-dark bg-transparent border border-light" placeholder="Your full name">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control form-control-dark bg-transparent border border-light" placeholder="you@example.com">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Phone</label>
                                <input type="tel" class="form-control form-control-dark bg-transparent border border-light" placeholder="+91 98765 43210">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Message</label>
                                <textarea class="form-control form-control-dark bg-transparent border border-light" rows="5" placeholder="Tell us how we can help"></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-brand">Send Message</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php get_footer();