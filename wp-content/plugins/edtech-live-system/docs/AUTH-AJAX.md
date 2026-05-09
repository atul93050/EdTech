# AUTH AJAX

## Overview

The authentication system uses AJAX for all form submissions to provide a modern, responsive user experience. AJAX endpoints handle login, registration, password reset, and logout operations with proper security validation.

## AJAX Action Registration

### Registration Location
**File**: `ajax/class-edtech-ajax.php`
**Method**: `register_ajax_actions()`

```php
public function register_ajax_actions() {
    // Authentication actions (available to non-logged-in users)
    add_action('wp_ajax_nopriv_edtech_login', [$this->auth, 'ajax_login']);
    add_action('wp_ajax_nopriv_edtech_register', [$this->auth, 'ajax_register']);
    add_action('wp_ajax_nopriv_edtech_forgot_password', [$this->auth, 'ajax_forgot_password']);
    add_action('wp_ajax_nopriv_edtech_reset_password', [$this->auth, 'ajax_reset_password']);
    
    // Logout action (requires login)
    add_action('wp_ajax_edtech_logout', [$this->auth, 'ajax_logout']);
    
    // Admin actions
    add_action('wp_ajax_edtech_approve_teacher', [$this->admin, 'ajax_approve_teacher']);
    add_action('wp_ajax_edtech_suspend_user', [$this->admin, 'ajax_suspend_user']);
}
```

**Action Naming Convention**:
- `wp_ajax_nopriv_{action}`: Available to non-logged-in users
- `wp_ajax_{action}`: Requires user to be logged in
- `edtech_` prefix: Namespace to avoid conflicts

## Frontend AJAX Implementation

### JavaScript AJAX Handler
**File**: `assets/js/plugin.js`
**Function**: `edtechSubmitForm()`

```javascript
function edtechSubmitForm(formData, action, callback) {
    // Add nonce for security
    formData.append('nonce', edtechAjax.nonce);
    formData.append('action', action);
    
    // Show loading state
    showLoading(true);
    
    fetch(edtechAjax.ajax_url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response error');
        }
        return response.json();
    })
    .then(data => {
        showLoading(false);
        
        if (data.success) {
            callback(data.data);
        } else {
            showError(data.data.message || 'An error occurred');
        }
    })
    .catch(error => {
        showLoading(false);
        console.error('AJAX Error:', error);
        showError('Network error. Please check your connection and try again.');
    });
}
```

**Features**:
- Automatic nonce inclusion
- Loading state management
- Error handling
- JSON response parsing

### Localized Script Data
**File**: `edtech-live-system.php`
**Method**: `enqueue_scripts()`

```php
wp_localize_script('edtech-plugin-js', 'edtechAjax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('edtech_live_nonce'),
    'strings' => [
        'loading' => __('Loading...', 'edtech-live-system'),
        'error' => __('An error occurred', 'edtech-live-system'),
        'success' => __('Success!', 'edtech-live-system')
    ]
]);
```

**Localized Data**:
- AJAX URL endpoint
- Security nonce
- Translated strings
- Configuration options

## Login AJAX Endpoint

### Action: `edtech_login`
**Method**: POST
**Access**: Non-logged-in users only

#### Request Parameters
```javascript
const formData = new FormData();
formData.append('email', email.value);
formData.append('password', password.value);
formData.append('remember', remember.checked);
formData.append('auth_type', 'student_login'); // or 'teacher_login'
```

#### Server Processing
**File**: `services/class-edtech-auth.php`
**Method**: `ajax_login()`

```php
public function ajax_login() {
    // Security validation
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    // Rate limiting
    $rate_check = $this->check_rate_limit('login', $_SERVER['REMOTE_ADDR']);
    if (is_wp_error($rate_check)) {
        wp_send_json_error(['message' => $rate_check->get_error_message()]);
        return;
    }
    
    // Input sanitization
    $email = sanitize_email($_POST['email']);
    $password = sanitize_text_field($_POST['password']);
    $remember = isset($_POST['remember']);
    $auth_type = sanitize_text_field($_POST['auth_type']);
    
    // WordPress authentication
    $creds = [
        'user_login' => $email,
        'user_password' => $password,
        'remember' => $remember
    ];
    
    $user = wp_signon($creds, false);
    
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid email or password']);
        return;
    }
    
    // Role validation based on auth_type
    if (!$this->validate_auth_type($user, $auth_type)) {
        wp_send_json_error(['message' => 'Access denied for this login type']);
        return;
    }
    
    // Status check for teachers
    if ($this->helpers->is_teacher($user->ID)) {
        $profile = $this->db->get_user_profile($user->ID);
        if ($profile->status !== 'approved') {
            wp_send_json_error(['message' => 'Your account is pending approval']);
            return;
        }
    }
    
    // Success response
    $redirect = $this->get_role_redirect($user);
    wp_send_json_success(['redirect' => $redirect]);
}
```

#### Response Format
**Success**:
```json
{
    "success": true,
    "data": {
        "redirect": "/dashboard"
    }
}
```

**Error**:
```json
{
    "success": false,
    "data": {
        "message": "Invalid email or password"
    }
}
```

## Registration AJAX Endpoint

### Action: `edtech_register`
**Method**: POST
**Access**: Non-logged-in users only

#### Request Parameters
```javascript
const formData = new FormData();
formData.append('role', 'edtech_student'); // or 'edtech_teacher'
formData.append('full_name', 'John Doe');
formData.append('email', 'john@example.com');
formData.append('password', 'password123');
// Additional fields based on role...
```

#### Server Processing
**File**: `services/class-edtech-auth.php`
**Method**: `ajax_register()`

```php
public function ajax_register() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    // Rate limiting
    $rate_check = $this->check_rate_limit('register', $_SERVER['REMOTE_ADDR']);
    if (is_wp_error($rate_check)) {
        wp_send_json_error(['message' => $rate_check->get_error_message()]);
        return;
    }
    
    // Input validation and sanitization
    $data = $this->validate_registration_data($_POST, $_POST['role']);
    if (is_wp_error($data)) {
        wp_send_json_error(['message' => $data->get_error_message()]);
        return;
    }
    
    // Check email uniqueness
    if (email_exists($data['email'])) {
        wp_send_json_error(['message' => 'This email is already registered']);
        return;
    }
    
    // Create WordPress user
    $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Registration failed. Please try again.']);
        return;
    }
    
    // Set user role
    $user = new WP_User($user_id);
    $user->set_role($data['role']);
    
    // Create extended profile
    $profile_result = $this->create_user_profile($user_id, $data, $data['role']);
    if (is_wp_error($profile_result)) {
        wp_delete_user($user_id); // Cleanup
        wp_send_json_error(['message' => 'Profile creation failed']);
        return;
    }
    
    // Success response
    wp_send_json_success([
        'message' => 'Registration successful! You can now log in.',
        'redirect' => home_url('/')
    ]);
}
```

## Password Reset AJAX Endpoints

### Action: `edtech_forgot_password`
**Method**: POST
**Access**: All users

#### Request Parameters
```javascript
formData.append('email', 'user@example.com');
```

#### Server Processing
```php
public function ajax_forgot_password() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    $user = get_user_by('email', $email);
    
    // Don't reveal if email exists for security
    if (!$user) {
        wp_send_json_success(['message' => 'If the email exists, a reset link has been sent.']);
        return;
    }
    
    // Generate reset key
    $reset_key = get_password_reset_key($user);
    
    // Create reset URL
    $reset_url = add_query_arg([
        'action' => 'rp',
        'key' => $reset_key,
        'login' => $user->user_login
    ], home_url('/reset-password'));
    
    // Send email
    $subject = 'Password Reset - EdTech Platform';
    $message = "Click here to reset your password: $reset_url";
    $sent = wp_mail($email, $subject, $message);
    
    if ($sent) {
        wp_send_json_success(['message' => 'Password reset link sent to your email.']);
    } else {
        wp_send_json_error(['message' => 'Failed to send email. Please try again.']);
    }
}
```

### Action: `edtech_reset_password`
**Method**: POST
**Access**: Non-logged-in users

#### Request Parameters
```javascript
formData.append('password', 'newpassword123');
formData.append('confirm_password', 'newpassword123');
formData.append('key', resetKey);
formData.append('login', username);
```

#### Server Processing
```php
public function ajax_reset_password() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    $password = sanitize_text_field($_POST['password']);
    $confirm = sanitize_text_field($_POST['confirm_password']);
    $key = sanitize_text_field($_POST['key']);
    $login = sanitize_user($_POST['login']);
    
    // Validate passwords match
    if ($password !== $confirm) {
        wp_send_json_error(['message' => 'Passwords do not match']);
        return;
    }
    
    // Validate reset key
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid or expired reset link']);
        return;
    }
    
    // Reset password
    $result = reset_password($user, $password);
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => 'Password reset failed']);
        return;
    }
    
    // Auto login
    wp_set_auth_cookie($user->ID, true);
    
    // Get redirect URL
    $redirect = $this->get_role_redirect($user);
    wp_send_json_success([
        'message' => 'Password updated successfully!',
        'redirect' => $redirect
    ]);
}
```

## Logout AJAX Endpoint

### Action: `edtech_logout`
**Method**: POST
**Access**: Logged-in users only

#### Request Parameters
```javascript
// No additional parameters needed
```

#### Server Processing
```php
public function ajax_logout() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    // Log the logout event
    $user_id = get_current_user_id();
    $this->log_user_event($user_id, 'logout');
    
    // Perform logout
    wp_logout();
    
    wp_send_json_success([
        'message' => 'You have been logged out.',
        'redirect' => home_url('/')
    ]);
}
```

## Admin AJAX Endpoints

### Action: `edtech_approve_teacher`
**Method**: POST
**Access**: Administrators only

#### Server Processing
```php
public function ajax_approve_teacher() {
    // Admin capability check
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Access denied']);
        return;
    }
    
    $teacher_id = intval($_POST['teacher_id']);
    
    // Approve teacher
    $result = $this->db->approve_teacher($teacher_id);
    
    if ($result) {
        // Send approval email
        $this->send_approval_email($teacher_id);
        wp_send_json_success(['message' => 'Teacher approved successfully']);
    } else {
        wp_send_json_error(['message' => 'Failed to approve teacher']);
    }
}
```

## Error Handling

### Standardized Error Responses
```php
private function handle_ajax_error($error, $code = 'general_error') {
    $message = is_wp_error($error) ? $error->get_error_message() : $error;
    
    // Log error
    error_log("EdTech AJAX Error [$code]: $message");
    
    // Sanitize error message
    if ($code === 'db_error') {
        $message = 'A system error occurred. Please try again.';
    }
    
    wp_send_json_error([
        'message' => $message,
        'code' => $code
    ]);
}
```

### Client-Side Error Handling
```javascript
function showError(message) {
    const errorDiv = document.getElementById('auth-error');
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
        errorDiv.style.display = 'none';
    }, 5000);
}

function showLoading(show) {
    const loader = document.getElementById('auth-loading');
    loader.style.display = show ? 'block' : 'none';
}
```

## Security Features

### Nonce Validation
- All requests include WordPress nonces
- Server validates nonce on every request
- Prevents CSRF attacks

### Rate Limiting
- IP-based rate limiting
- Separate limits per action type
- Automatic cleanup

### Input Validation
- Server-side sanitization
- Type validation
- Length limits

### XSS Prevention
- Output escaping in responses
- Input sanitization
- Content Security Policy headers

## Performance Optimization

### Response Caching
- Avoid caching auth responses (security)
- Cache static assets
- Minimize response size

### Database Optimization
- Use prepared statements
- Index optimization
- Query result caching

### Frontend Optimization
- Debounced form submissions
- Loading states
- Progressive enhancement

This AJAX architecture provides secure, efficient, and user-friendly authentication operations while maintaining WordPress best practices and security standards.