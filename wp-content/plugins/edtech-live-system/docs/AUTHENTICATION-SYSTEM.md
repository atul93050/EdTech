# AUTHENTICATION SYSTEM

## Overview

The EdTech SaaS platform implements a comprehensive authentication system that combines WordPress core authentication with custom frontend interfaces. This hybrid approach provides role-based access control while maintaining a modern SaaS user experience.

## Architecture

### Core Components

1. **WordPress Core Auth**: Uses `wp_signon()`, `wp_create_user()`, `wp_logout()` for authentication
2. **Custom Frontend**: Role-specific login forms with AJAX submission
3. **Route Protection**: Custom rewrite rules and template filtering
4. **Role-Based Access**: Separate dashboards for students, teachers, and admins
5. **Session Management**: WordPress sessions with custom redirects

### Authentication Types

#### 1. WordPress Backend Admin Login (`/ed-admin-login`)
- Purpose: Direct access to WordPress admin panel
- Users: Only administrators and super admins
- Flow: Custom route → WordPress login → wp-admin redirect

#### 2. Frontend Admin Login (`/admin-login`)
- Purpose: SaaS-style admin dashboard access
- Users: Super admins for platform management
- Flow: Frontend form → AJAX auth → dashboard redirect

#### 3. Teacher Authentication
- Routes: `/teacher-login`, `/teacher-register`, `/teacher-forgot-password`
- Purpose: Teacher role management and class access
- Flow: Registration → Pending approval → Dashboard access

#### 4. Student Authentication
- Routes: `/student-login`, `/student-register`, `/student-forgot-password`
- Purpose: Student learning platform access
- Flow: Self-registration → Dashboard access

## WordPress Integration

### User Storage
- **wp_users table**: Core user data (username, email, password hash)
- **wp_usermeta table**: User metadata (full_name, role-specific data)
- **wp_lms_students/wp_lms_teachers**: Extended profile data

### Role System
```php
// Custom roles defined in plugin activation
add_role('edtech_student', 'Student', ['read' => true]);
add_role('edtech_teacher', 'Teacher', ['read' => true]);
add_role('edtech_super_admin', 'Super Admin', [
    'read' => true,
    'edit_posts' => true,
    'manage_options' => true
]);
```

### Session Handling
- Uses WordPress `wp_set_auth_cookie()` for session management
- Custom redirects based on user role
- Admin bar conditionally shown based on route

## Login Flow

### Internal Process

1. **Form Submission**: AJAX POST to `edtech_login` action
2. **Validation**: Nonce check, input sanitization
3. **WordPress Auth**: `wp_signon()` with credentials
4. **Role Check**: Verify user has required role
5. **Status Check**: For teachers/students, check approval status
6. **Redirect**: Role-based dashboard redirect

### Code Example
```php
public function ajax_login() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    $creds = [
        'user_login' => sanitize_email($_POST['email']),
        'user_password' => sanitize_text_field($_POST['password']),
        'remember' => isset($_POST['remember'])
    ];
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid credentials']);
    }
    
    // Role-based redirect logic
    $redirect = $this->get_role_redirect($user);
    
    wp_send_json_success(['redirect' => $redirect]);
}
```

## Registration Flow

### Student/Teacher Registration

1. **Form Validation**: Required fields, email uniqueness
2. **WordPress User Creation**: `wp_create_user()` with email/password
3. **Role Assignment**: Set appropriate role
4. **Profile Creation**: Insert into lms_students/lms_teachers table
5. **Status Setting**: Students auto-approved, teachers pending
6. **Email Notification**: Admin notification for approvals

### Code Example
```php
$user_id = wp_create_user($email, $password, $email);
$user = new WP_User($user_id);
$user->set_role('edtech_student');

// Create extended profile
$wpdb->insert('wp_lms_students', [
    'user_id' => $user_id,
    'full_name' => $full_name,
    'status' => 'approved'
]);
```

## Password Reset Flow

### Forgot Password Process

1. **Email Validation**: Check if user exists
2. **Reset Key Generation**: `get_password_reset_key()`
3. **Email Sending**: Custom reset URL generation
4. **Token Validation**: `check_password_reset_key()` on reset
5. **Password Update**: `reset_password()` function

## Logout Flow

### Session Termination

1. **AJAX Request**: `edtech_logout` action
2. **WordPress Logout**: `wp_logout()` clears session
3. **Redirect**: Back to homepage

## Route Protection

### Admin Access Control

```php
public function restrict_admin_access() {
    if (is_admin() && !defined('DOING_AJAX')) {
        $user = wp_get_current_user();
        if (!in_array('administrator', $user->roles)) {
            wp_redirect(home_url('/'));
            exit;
        }
    }
}
```

### Frontend Route Protection

- Custom rewrite rules for auth routes
- Template filtering to load auth templates
- 404 handling for auth URLs

## Security Features

### Nonce Protection
- All AJAX requests use `wp_create_nonce('edtech_live_nonce')`
- Server-side validation with `check_ajax_referer()`

### Input Sanitization
- Email: `sanitize_email()`
- Text: `sanitize_text_field()`
- Textarea: `sanitize_textarea_field()`

### XSS Prevention
- All output escaped with `esc_html()`, `esc_attr()`
- Template literals use proper escaping

### CSRF Protection
- Nonce tokens on all forms
- AJAX requests include nonce validation

## Role-Based Redirects

### After Login Logic
```php
private function get_role_redirect($user) {
    if (current_user_can('manage_options')) {
        return admin_url();
    }
    
    if ($this->helpers->is_super_admin()) {
        return home_url('/dashboard');
    }
    
    if ($this->helpers->is_teacher()) {
        return home_url('/dashboard');
    }
    
    if ($this->helpers->is_student()) {
        return home_url('/dashboard');
    }
    
    return home_url('/');
}
```

## Session Management

### WordPress Sessions
- Automatic session creation on login
- Cookie-based persistence
- Configurable remember me functionality

### Custom Session Data
- User role stored in session
- Dashboard preferences cached
- Last activity tracking

## Error Handling

### Login Errors
- Invalid credentials
- Account pending approval
- Role not assigned

### Registration Errors
- Email already exists
- Missing required fields
- Validation failures

### AJAX Error Responses
```php
wp_send_json_error([
    'message' => 'Error description',
    'code' => 'error_code'
]);
```

## Performance Considerations

### Database Queries
- Prepared statements for all DB operations
- Indexed user_id columns
- Efficient role checking

### Caching
- User data cached in transients
- Role capabilities cached
- Session data optimized

## Development Notes

### Why This Architecture?
1. **WordPress Integration**: Leverages proven auth system
2. **Security**: Built-in WordPress security features
3. **Scalability**: Handles multiple user types
4. **Maintainability**: Modular service-based design

### Key Design Decisions
- Frontend-only auth for role users
- Backend admin access restricted
- AJAX-driven forms for better UX
- Role-based dashboard separation

This authentication system provides a robust, secure, and user-friendly access control layer for the EdTech platform.