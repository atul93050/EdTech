# AUTH MIDDLEWARE

## Overview

The authentication system implements multiple layers of middleware to protect routes, validate requests, and manage user sessions. Middleware functions are executed at various points in the request lifecycle to ensure security and proper access control.

## Route Protection Middleware

### Admin Access Restriction

**Location**: `edtech-live-system.php`
**Purpose**: Prevents non-admin users from accessing WordPress admin panel
**Code**:

```php
public function restrict_admin_access() {
    // Only run on admin pages, not AJAX
    if (is_admin() && !defined('DOING_AJAX')) {
        $user = wp_get_current_user();
        
        // Allow administrators and super admins
        if (!current_user_can('manage_options') && !$this->helpers->is_super_admin()) {
            wp_redirect(home_url('/'));
            exit;
        }
    }
}

// Hook registration
add_action('admin_init', [$this, 'restrict_admin_access']);
```

**Logic Flow**:
1. Check if current page is admin area
2. Exclude AJAX requests (allow admin-ajax.php)
3. Verify user has admin capabilities
4. Redirect unauthorized users to homepage

### Frontend Route Protection

**Location**: `services/class-edtech-auth.php`
**Purpose**: Protects auth routes based on user login status
**Code**:

```php
public function protect_auth_routes() {
    $auth_routes = [
        'student-login', 'teacher-login', 'admin-login',
        'student-register', 'teacher-register'
    ];
    
    $current_route = get_query_var('edtech_auth');
    
    if (in_array($current_route, $auth_routes) && is_user_logged_in()) {
        // Redirect logged-in users to their dashboard
        $user = wp_get_current_user();
        $redirect = $this->get_role_redirect($user);
        wp_redirect($redirect);
        exit;
    }
}

// Hook registration
add_action('template_redirect', [$this, 'protect_auth_routes']);
```

**Protection Rules**:
- Logged-in users cannot access login/register pages
- Automatic redirect to appropriate dashboard
- Prevents duplicate sessions

## AJAX Security Middleware

### Nonce Validation

**Location**: All AJAX handlers in `services/class-edtech-auth.php`
**Purpose**: Prevents CSRF attacks on AJAX requests
**Code**:

```php
public function ajax_login() {
    // First line of defense: Nonce validation
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    // Rest of login logic...
}

// Applied to all auth AJAX actions
add_action('wp_ajax_nopriv_edtech_login', [$this->auth, 'ajax_login']);
add_action('wp_ajax_nopriv_edtech_register', [$this->auth, 'ajax_register']);
add_action('wp_ajax_edtech_logout', [$this->auth, 'ajax_logout']);
```

**Security Features**:
- Unique nonce per session
- Time-limited validity
- Action-specific nonces
- Automatic expiration

### Rate Limiting

**Location**: `services/class-edtech-auth.php`
**Purpose**: Prevents brute force attacks
**Code**:

```php
private function check_rate_limit($action, $identifier) {
    $key = "edtech_rate_limit_{$action}_{$identifier}";
    $attempts = get_transient($key);
    
    if ($attempts >= 5) { // 5 attempts per hour
        return new WP_Error('rate_limited', 'Too many attempts. Please try again later.');
    }
    
    set_transient($key, ($attempts ?: 0) + 1, HOUR_IN_SECONDS);
    return true;
}

public function ajax_login() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    // Rate limiting check
    $rate_check = $this->check_rate_limit('login', $_SERVER['REMOTE_ADDR']);
    if (is_wp_error($rate_check)) {
        wp_send_json_error(['message' => $rate_check->get_error_message()]);
        return;
    }
    
    // Continue with login...
}
```

**Rate Limit Rules**:
- 5 login attempts per IP per hour
- Separate limits for registration and password reset
- Automatic cleanup via transients

## Input Validation Middleware

### Sanitization Layer

**Location**: `services/class-edtech-auth.php`
**Purpose**: Cleans and validates all user inputs
**Code**:

```php
private function sanitize_login_data($data) {
    return [
        'email' => sanitize_email($data['email'] ?? ''),
        'password' => sanitize_text_field($data['password'] ?? ''),
        'remember' => isset($data['remember']),
        'auth_type' => sanitize_text_field($data['auth_type'] ?? '')
    ];
}

private function sanitize_registration_data($data) {
    return [
        'role' => sanitize_text_field($data['role'] ?? ''),
        'full_name' => sanitize_text_field($data['full_name'] ?? ''),
        'email' => sanitize_email($data['email'] ?? ''),
        'phone' => sanitize_text_field($data['phone'] ?? ''),
        'password' => sanitize_text_field($data['password'] ?? ''),
        'grade' => sanitize_text_field($data['grade'] ?? ''),
        'qualification' => sanitize_text_field($data['qualification'] ?? ''),
        'experience' => sanitize_textarea_field($data['experience'] ?? ''),
        'bio' => sanitize_textarea_field($data['bio'] ?? '')
    ];
}
```

**Sanitization Rules**:
- Email: `sanitize_email()` - validates email format
- Text: `sanitize_text_field()` - removes HTML/tags
- Textarea: `sanitize_textarea_field()` - allows safe HTML
- Numbers: Type casting and range validation

### Validation Layer

**Location**: `services/class-edtech-auth.php`
**Purpose**: Business logic validation
**Code**:

```php
private function validate_registration_data($data, $role) {
    $errors = [];
    
    // Required fields validation
    $required_fields = ['full_name', 'email', 'password'];
    if ($role === 'edtech_teacher') {
        $required_fields[] = 'qualification';
    }
    
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
        }
    }
    
    // Email format validation
    if (!empty($data['email']) && !is_email($data['email'])) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Password strength validation
    if (!empty($data['password']) && strlen($data['password']) < 8) {
        $errors[] = 'Password must be at least 8 characters long';
    }
    
    // Email uniqueness check
    if (!empty($data['email']) && email_exists($data['email'])) {
        $errors[] = 'This email address is already registered';
    }
    
    return empty($errors) ? $data : new WP_Error('validation_error', implode(', ', $errors));
}
```

**Validation Rules**:
- Required field checking
- Email format validation
- Password strength requirements
- Uniqueness constraints
- Role-specific validations

## Session Management Middleware

### Session Validation

**Location**: `services/class-edtech-auth.php`
**Purpose**: Validates user sessions on each request
**Code**:

```php
public function validate_user_session() {
    if (!is_user_logged_in()) {
        return;
    }
    
    $user = wp_get_current_user();
    
    // Check if user still has required role
    if (!$this->helpers->is_valid_lms_user($user->ID)) {
        wp_logout();
        wp_redirect(home_url('/?logged_out=invalid_role'));
        exit;
    }
    
    // Check account status
    $profile = $this->db->get_user_profile($user->ID);
    if ($profile && $profile->status === 'suspended') {
        wp_logout();
        wp_redirect(home_url('/?logged_out=suspended'));
        exit;
    }
}

// Hook registration
add_action('init', [$this, 'validate_user_session']);
```

**Session Checks**:
- User still exists and has valid role
- Account not suspended
- Profile data integrity
- Automatic logout for invalid sessions

### Role-Based Access Control

**Location**: `helpers/class-edtech-helpers.php`
**Purpose**: Checks user permissions for various actions
**Code**:

```php
public function can_access_dashboard($user_id = null) {
    $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
    
    return $this->is_student($user->ID) || 
           $this->is_teacher($user->ID) || 
           $this->is_super_admin($user->ID);
}

public function can_manage_users($user_id = null) {
    return $this->has_role('edtech_super_admin', $user_id) || 
           current_user_can('manage_options');
}

public function can_view_student_data($student_id, $user_id = null) {
    $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
    
    // Super admins can view all
    if ($this->is_super_admin($user->ID)) {
        return true;
    }
    
    // Teachers can view their students
    if ($this->is_teacher($user->ID)) {
        return $this->is_teacher_student($user->ID, $student_id);
    }
    
    // Students can only view their own data
    return $user->ID === $student_id;
}
```

**RBAC Rules**:
- Hierarchical permissions
- Context-aware access
- Data ownership validation
- Admin override capabilities

## Template Loading Middleware

### Auth Template Resolver

**Location**: `edtech-live-system.php`
**Purpose**: Loads appropriate templates for auth routes
**Code**:

```php
public function maybe_load_auth_template($template) {
    $auth_type = get_query_var('edtech_auth');
    
    if ($auth_type) {
        // Try theme template first
        $theme_template = locate_template("page-auth.php");
        
        if ($theme_template) {
            return $theme_template;
        }
        
        // Fallback to plugin template
        $plugin_template = plugin_dir_path(__FILE__) . 'templates/page-auth.php';
        
        if (file_exists($plugin_template)) {
            return $plugin_template;
        }
    }
    
    return $template;
}

// Hook registration
add_filter('template_include', [$this, 'maybe_load_auth_template']);
```

**Template Resolution**:
1. Check for theme override
2. Fall back to plugin template
3. Maintain WordPress template hierarchy

### 404 Prevention

**Location**: `edtech-live-system.php`
**Purpose**: Prevents 404 errors on auth routes
**Code**:

```php
public function render_auth_template_on_404($template) {
    $auth_type = get_query_var('edtech_auth');
    
    if (is_404() && $auth_type) {
        // Load auth template instead of 404
        return $this->maybe_load_auth_template($template);
    }
    
    return $template;
}

// Hook registration
add_filter('404_template', [$this, 'render_auth_template_on_404']);
```

**404 Handling**:
- Intercepts 404 errors for auth routes
- Serves appropriate auth template
- Maintains clean URLs

## Admin Bar Control

### Conditional Admin Bar Display

**Location**: `edtech-live-system.php`
**Purpose**: Shows/hides admin bar based on user role and route
**Code**:

```php
public function show_admin_bar_conditionally() {
    if (!is_user_logged_in()) {
        return false;
    }
    
    $user = wp_get_current_user();
    
    // Show for admins and super admins
    if (current_user_can('manage_options') || $this->helpers->is_super_admin()) {
        return true;
    }
    
    // Hide for students and teachers on frontend
    if (!is_admin()) {
        return false;
    }
    
    return true;
}

// Hook registration
add_filter('show_admin_bar', [$this, 'show_admin_bar_conditionally']);
```

**Admin Bar Rules**:
- Visible for administrators
- Hidden for LMS users on frontend
- Context-aware display

## Security Headers Middleware

### Content Security Policy

**Location**: `edtech-live-system.php`
**Purpose**: Adds security headers for auth pages
**Code**:

```php
public function add_security_headers() {
    if (get_query_var('edtech_auth')) {
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}

// Hook registration
add_action('send_headers', [$this, 'add_security_headers']);
```

**Security Headers**:
- Prevents clickjacking
- Prevents MIME type sniffing
- Controls referrer information

## Error Handling Middleware

### AJAX Error Handler

**Location**: `services/class-edtech-auth.php`
**Purpose**: Standardized error responses
**Code**:

```php
private function handle_ajax_error($error, $code = 'general_error') {
    $message = is_wp_error($error) ? $error->get_error_message() : $error;
    
    // Log error for debugging
    error_log("EdTech Auth Error [$code]: $message");
    
    // Don't expose sensitive information
    if ($code === 'db_error') {
        $message = 'A system error occurred. Please try again.';
    }
    
    wp_send_json_error([
        'message' => $message,
        'code' => $code
    ]);
}
```

**Error Handling**:
- Consistent error format
- Sensitive data protection
- Debug logging
- User-friendly messages

## Performance Middleware

### Caching Layer

**Location**: `database/class-edtech-db.php`
**Purpose**: Caches frequently accessed user data
**Code**:

```php
public function get_user_profile_cached($user_id) {
    $cache_key = "edtech_user_profile_{$user_id}";
    $profile = wp_cache_get($cache_key);
    
    if ($profile === false) {
        $profile = $this->get_user_profile($user_id);
        wp_cache_set($cache_key, $profile, 'edtech_auth', 300); // 5 minutes
    }
    
    return $profile;
}
```

**Caching Strategy**:
- User profile data caching
- Role and permission caching
- Automatic cache invalidation on updates

This middleware architecture provides comprehensive protection, validation, and performance optimization for the authentication system while maintaining clean, maintainable code.