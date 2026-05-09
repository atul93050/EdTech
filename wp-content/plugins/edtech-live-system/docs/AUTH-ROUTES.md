# AUTH ROUTES

## Route Overview

The authentication system uses custom WordPress rewrite rules to provide clean, SEO-friendly URLs for all authentication flows. Routes are registered in the main plugin file and handled through template filtering.

## Route Registration

### Code Location
**File**: `edtech-live-system.php`
**Method**: `register_routes()`

```php
public function register_routes() {
    add_rewrite_rule('^student-login/?$', 'index.php?edtech_auth=student_login', 'top');
    add_rewrite_rule('^teacher-login/?$', 'index.php?edtech_auth=teacher_login', 'top');
    add_rewrite_rule('^student-register/?$', 'index.php?edtech_auth=student_register', 'top');
    add_rewrite_rule('^teacher-register/?$', 'index.php?edtech_auth=teacher_register', 'top');
    add_rewrite_rule('^ed-admin-login/?$', 'index.php?edtech_auth=ed_admin_login', 'top');
    add_rewrite_rule('^forgot-password/?$', 'index.php?edtech_auth=forgot_password', 'top');
    add_rewrite_rule('^reset-password/?$', 'index.php?edtech_auth=reset_password', 'top');
    add_rewrite_rule('^video-player/([0-9]+)/?$', 'index.php?edtech_video_id=$matches[1]', 'top');
}
```

---

## /ed-admin-login

### Purpose
Custom route for WordPress backend admin panel access. Provides a branded login experience while maintaining direct wp-admin access for administrators.

### Accessible By
- Guests (not logged in)
- Administrators (redirects to wp-admin)
- Super Admins (redirects to wp-admin)

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('ed_admin_login')`

### Template
**Theme Template**: `page-auth.php` (if exists)
**Fallback Template**: `templates/page-auth.php` (plugin)

### Redirect After Login
- **Success**: `admin_url()` (wp-admin dashboard)
- **Failure**: Stay on login page with error

### Middleware
- `restrict_admin_access()` - Blocks non-admin users from wp-admin
- `show_admin_bar_conditionally()` - Shows admin bar for admins

### Validation
- WordPress core authentication
- Admin role verification
- Account status check

### Security Notes
- Only administrators can access wp-admin
- Non-admin users redirected to frontend
- Maintains WordPress admin functionality

---

## /admin-login

### Purpose
Frontend SaaS-style admin login for platform management. Provides access to the Super Admin dashboard without exposing WordPress admin interface.

### Accessible By
- Guests
- Super Admins (redirects to dashboard)

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('ed_admin_login')` (shared with backend admin)

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### Redirect After Login
- **Success**: `home_url('/dashboard')` (Super Admin dashboard)
- **Failure**: Stay on login page

### Middleware
- Role-based redirect logic
- Frontend-only access restriction

### Notes
- Uses same form as `/ed-admin-login`
- Redirects to frontend dashboard instead of wp-admin
- Part of the SaaS admin experience

---

## /teacher-login

### Purpose
Teacher authentication page for accessing the teacher dashboard and managing classes.

### Accessible By
- Guests only
- Logged-in users redirected to dashboard

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('teacher_login')`

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### Form Fields
- Email (required)
- Password (required)
- Remember Me (checkbox)

### Redirect After Login
- **Success**: `home_url('/dashboard')` (Teacher dashboard)
- **Pending Approval**: Error message
- **Invalid Credentials**: Error message

### Validation
- Email format validation
- Password required
- Teacher role verification
- Account approval status check

### AJAX Endpoint
**Action**: `edtech_login`
**Payload**: `{email, password, remember, auth_type: 'teacher_login'}`

---

## /teacher-register

### Purpose
Teacher registration page for new teacher accounts. Requires admin approval before dashboard access.

### Accessible By
- Guests only
- Logged-in users redirected to dashboard

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('teacher_register')`

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### Form Fields
- Full Name (required)
- Email (required)
- Phone (optional)
- Qualification (optional)
- Experience (optional)
- Password (required)
- Bio (textarea, optional)

### Redirect After Registration
- **Success**: `home_url('/')` with success message
- **Email Exists**: Error message
- **Validation Error**: Field-specific errors

### Validation
- Required fields: full_name, email, password
- Email uniqueness check
- Password strength (WordPress default)
- XSS sanitization on all inputs

### Process Flow
1. Form validation
2. WordPress user creation
3. Teacher role assignment
4. Extended profile creation in `wp_lms_teachers`
5. Status set to 'pending'
6. Admin notification (if implemented)

### AJAX Endpoint
**Action**: `edtech_register`
**Payload**: `{role: 'edtech_teacher', full_name, email, phone, qualification, experience, password, bio}`

---

## /student-login

### Purpose
Student authentication page for accessing the learning platform and enrolled courses.

### Accessible By
- Guests only
- Logged-in users redirected to dashboard

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('student_login')`

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### Form Fields
- Email (required)
- Password (required)
- Remember Me (checkbox)

### Redirect After Login
- **Success**: `home_url('/dashboard')` (Student dashboard)
- **Invalid Credentials**: Error message

### Validation
- Email format validation
- Password required
- Student role verification

### AJAX Endpoint
**Action**: `edtech_login`
**Payload**: `{email, password, remember, auth_type: 'student_login'}`

---

## /student-register

### Purpose
Student self-registration page for immediate platform access.

### Accessible By
- Guests only
- Logged-in users redirected to dashboard

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('student_register')`

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### Form Fields
- Full Name (required)
- Email (required)
- Phone (optional)
- Class/Grade (optional)
- City (optional)
- Parent Name (optional)
- Parent Phone (optional)
- Password (required)
- Bio (textarea, optional)

### Redirect After Registration
- **Success**: `home_url('/')` with success message
- **Email Exists**: Error message

### Validation
- Required fields: full_name, email, password
- Email uniqueness check
- Automatic approval (status: 'approved')

### Process Flow
1. Form validation
2. WordPress user creation
3. Student role assignment
4. Extended profile creation in `wp_lms_students`
5. Status set to 'approved'
6. Immediate dashboard access

### AJAX Endpoint
**Action**: `edtech_register`
**Payload**: `{role: 'edtech_student', full_name, email, phone, grade, city, password, bio, parent_name, parent_phone}`

---

## /forgot-password

### Purpose
Password recovery page for all user types. Sends reset link to email.

### Accessible By
- All users (guests and logged-in)

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('forgot_password')`

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### Form Fields
- Email (required)

### Process Flow
1. Email validation
2. User lookup by email
3. Reset key generation
4. Reset URL creation
5. Email sending
6. Success message display

### Redirect After Submission
- **Success**: Stay on page with success message
- **User Not Found**: Generic error (security)

### AJAX Endpoint
**Action**: `edtech_forgot_password`
**Payload**: `{email}`

---

## /reset-password

### Purpose
Password reset page accessed via email link. Updates password and logs user in.

### Accessible By
- Users with valid reset token
- Accessed via email link

### Controller
**Class**: `Edtech_Auth`
**Method**: `render_auth_form('reset_password')`

### Template
**Theme Template**: `page-auth.php`
**Fallback Template**: `templates/page-auth.php`

### URL Parameters
- `key`: Reset token
- `login`: Username

### Form Fields
- New Password (required)
- Confirm Password (required)

### Process Flow
1. Token validation
2. Password matching check
3. Password update via `reset_password()`
4. Automatic login
5. Redirect to dashboard

### Redirect After Reset
- **Success**: Role-based dashboard
- **Invalid Token**: Error message
- **Password Mismatch**: Error message

### AJAX Endpoint
**Action**: `edtech_reset_password`
**Payload**: `{password, confirm_password, key, login}`

---

## /logout

### Purpose
Session termination endpoint. Logs out current user and redirects to homepage.

### Accessible By
- Logged-in users only

### Implementation
**AJAX Endpoint**: `edtech_logout`
**Method**: POST

### Process Flow
1. Nonce validation
2. `wp_logout()` call
3. Session cleanup
4. Redirect to homepage

### Response
```json
{
    "success": true,
    "data": {
        "message": "You have been logged out.",
        "redirect": "/"
    }
}
```

---

## Route Protection

### 404 Handling
**Method**: `render_auth_template_on_404()`
- Intercepts 404 errors for auth routes
- Loads appropriate auth template
- Prevents 404 pages for valid auth URLs

### Template Loading
**Method**: `maybe_load_auth_template()`
- Filters `template_include`
- Detects auth query vars
- Loads theme or plugin auth template

### Query Vars
**Registered**: `edtech_auth`, `edtech_video_id`
**Used for**: Route identification and template selection

## Development Notes

### Adding New Routes
1. Add rewrite rule in `register_routes()`
2. Add auth type mapping in template methods
3. Create form rendering in `Edtech_Auth` class
4. Add AJAX handler if needed

### Route Priority
- Rules registered with `'top'` priority
- Flush rewrite rules after changes
- Use `flush_rewrite_rules()` in activation/deactivation

### URL Structure
- Clean URLs without query parameters
- SEO-friendly route names
- Consistent naming convention

This routing system provides clean, maintainable URL structures for all authentication flows while maintaining WordPress compatibility.