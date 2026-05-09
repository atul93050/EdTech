# EdTech SaaS Platform Installation Guide

## Overview
This repository includes a custom WordPress theme and plugin designed to build a premium EdTech SaaS platform inside an existing WordPress installation.

## Theme
- `wp-content/themes/edtech-saas-theme`
- Handles UI, landing pages, navbar, footer, auth layout, dashboard shell, and responsive SaaS design.

## Plugin
- `wp-content/plugins/edtech-live-system`
- Handles authentication, user roles, student/teacher management, subjects, live classes, notifications, attendance, AJAX, and database schema.

## Installation Steps
1. Copy or move the theme folder to `wp-content/themes/edtech-saas-theme`.
2. Copy or move the plugin folder to `wp-content/plugins/edtech-live-system`.
3. Log in to WordPress admin as an administrator.
4. Activate the `EdTech Live System` plugin.
5. Activate the `EdTech SaaS Theme` theme.
6. Visit `Settings > Permalinks` and click Save to flush rewrite rules.
7. Create the following pages and assign the templates where needed:
   - Home: assign the `Home Page` template.
   - Features: assign the `Features Page` template.
   - About: assign the `About Us Page` template.
   - Contact: assign the `Contact Us Page` template.
   - Pricing: assign the `Pricing Page` template.
   - FAQ: assign the `FAQ Page` template.
   - Privacy Policy: assign the `Privacy Policy Page` template.
   - Terms & Conditions: assign the `Terms & Conditions Page` template.
   - Student Login: assign the `Auth Page` template and set slug to `student-login`.
   - Teacher Login: assign the `Auth Page` template and set slug to `teacher-login`.
   - Student Register: assign the `Auth Page` template and set slug to `student-register`.
   - Teacher Register: assign the `Auth Page` template and set slug to `teacher-register`.
   - Forgot Password: assign the `Auth Page` template and set slug to `forgot-password`.
   - Reset Password: assign the `Auth Page` template and set slug to `reset-password`.
   - Dashboard: assign the `Dashboard Page` template and set slug to `dashboard`.

## Recommended Workflow
- Use the front-end dashboard page for daily admin, teacher and student workflows.
- Only administrators should access wp-admin for plugin/theme settings and advanced configuration.
- Create users by registration and approve them manually by updating WordPress user roles.

## Additional Notes
- The plugin creates custom tables for students, teachers, subjects, live classes, notifications, attendance, activity logs and settings.
- The theme is intentionally separated and contains only styling, templates and UI.
- AJAX endpoints are provided by the plugin to support authentication, live class controls, and notifications.

## Verification
- After activation, register a student and a teacher account from the front-end pages.
- Login as a student and as a teacher to confirm dashboard rendering.
- Verify that non-admin users are redirected away from `wp-admin`.

## Future Upgrades
This architecture supports future extensions such as:
- Zoom/Jitsi integration
- Recorded classes
- Assignments and exams
- Payments and subscription plans
- Mobile app API endpoints
- Multi-school and multi-tenant support
