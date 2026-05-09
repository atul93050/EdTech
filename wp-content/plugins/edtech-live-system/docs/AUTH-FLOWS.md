# AUTH FLOWS

## Overview

The authentication system implements comprehensive user flows for login, registration, password recovery, and session management. Each flow includes frontend interaction, backend processing, and appropriate redirects.

## Student Login Flow

### User Journey
1. **Access**: User navigates to `/student-login`
2. **Form Display**: Login form renders with email/password fields
3. **Submission**: User submits credentials via AJAX
4. **Validation**: Server validates input and credentials
5. **Authentication**: WordPress `wp_signon()` authenticates user
6. **Role Check**: Verifies user has `edtech_student` role
7. **Redirect**: Successful login redirects to student dashboard

### Code Flow

#### Frontend (plugin.js)
```javascript
function handleStudentLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    formData.append('auth_type', 'student_login');
    
    edtechSubmitForm(formData, 'edtech_login', (data) => {
        if (data.redirect) {
            window.location.href = data.redirect;
        }
    });
}
```

#### Backend (class-edtech-auth.php)
```php
public function ajax_login() {
    // 1. Nonce validation
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    // 2. Input sanitization
    $email = sanitize_email($_POST['email']);
    $password = sanitize_text_field($_POST['password']);
    $remember = isset($_POST['remember']);
    
    // 3. WordPress authentication
    $creds = [
        'user_login' => $email,
        'user_password' => $password,
        'remember' => $remember
    ];
    
    $user = wp_signon($creds, false);
    
    // 4. Error handling
    if (is_wp_error($user)) {
        wp_send_json_error(['message' => 'Invalid credentials']);
        return;
    }
    
    // 5. Role verification
    if (!$this->helpers->is_student($user->ID)) {
        wp_send_json_error(['message' => 'Access denied']);
        return;
    }
    
    // 6. Success response with redirect
    $redirect = home_url('/dashboard');
    wp_send_json_success(['redirect' => $redirect]);
}
```

### Error Scenarios
- **Invalid Credentials**: "Invalid email or password"
- **Wrong Role**: "Access denied for this login type"
- **Account Suspended**: "Your account has been suspended"
- **Network Error**: AJAX failure handling

---

## Teacher Login Flow

### User Journey
1. **Access**: Navigate to `/teacher-login`
2. **Form Display**: Login form with email/password
3. **Submission**: AJAX form submission
4. **Validation**: Server-side validation
5. **Authentication**: WordPress authentication
6. **Role Check**: Verify `edtech_teacher` role
7. **Status Check**: Verify teacher approval status
8. **Redirect**: Approved teachers go to dashboard

### Additional Validation
```php
// Check teacher approval status
$profile = $this->db->get_user_profile($user->ID, 'teacher');
if ($profile->status !== 'approved') {
    wp_send_json_error(['message' => 'Your account is pending approval']);
    return;
}
```

### Error Scenarios
- **Pending Approval**: "Your account is pending approval by an administrator"
- **Suspended**: "Your account has been suspended"
- **Invalid Credentials**: Standard error message

---

## Student Registration Flow

### User Journey
1. **Access**: Navigate to `/student-register`
2. **Form Display**: Registration form with required fields
3. **Validation**: Client-side validation before submission
4. **Submission**: AJAX form submission
5. **Server Validation**: Email uniqueness, required fields
6. **User Creation**: WordPress user creation
7. **Profile Creation**: Extended student profile
8. **Success Message**: Registration confirmation

### Code Flow

#### Frontend Validation
```javascript
function validateStudentRegistration(form) {
    const required = ['full_name', 'email', 'password'];
    for (let field of required) {
        if (!form[field].value.trim()) {
            showError(`${field.replace('_', ' ')} is required`);
            return false;
        }
    }
    return true;
}
```

#### Backend Processing
```php
public function ajax_register() {
    // 1. Nonce and role validation
    check_ajax_referer('edtech_live_nonce', 'nonce');
    $role = sanitize_text_field($_POST['role']);
    
    if ($role !== 'edtech_student') {
        wp_send_json_error(['message' => 'Invalid registration type']);
        return;
    }
    
    // 2. Input validation
    $data = $this->validate_registration_data($_POST, $role);
    if (is_wp_error($data)) {
        wp_send_json_error(['message' => $data->get_error_message()]);
        return;
    }
    
    // 3. Check email uniqueness
    if (email_exists($data['email'])) {
        wp_send_json_error(['message' => 'Email already exists']);
        return;
    }
    
    // 4. Create WordPress user
    $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => 'Registration failed']);
        return;
    }
    
    // 5. Set user role
    $user = new WP_User($user_id);
    $user->set_role($role);
    
    // 6. Create extended profile
    $profile_result = $this->create_user_profile($user_id, $data, 'student');
    if (is_wp_error($profile_result)) {
        wp_delete_user($user_id); // Cleanup
        wp_send_json_error(['message' => 'Profile creation failed']);
        return;
    }
    
    // 7. Success response
    wp_send_json_success([
        'message' => 'Registration successful! You can now log in.',
        'redirect' => home_url('/')
    ]);
}
```

### Database Operations
```php
private function create_user_profile($user_id, $data, $type) {
    global $wpdb;
    $table = $type === 'student' ? $wpdb->prefix . 'lms_students' : $wpdb->prefix . 'lms_teachers';
    
    $result = $wpdb->insert($table, [
        'user_id' => $user_id,
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'phone' => $data['phone'] ?? null,
        'grade' => $data['grade'] ?? null,
        'city' => $data['city'] ?? null,
        'parent_name' => $data['parent_name'] ?? null,
        'parent_phone' => $data['parent_phone'] ?? null,
        'bio' => $data['bio'] ?? null,
        'status' => 'approved' // Students auto-approved
    ]);
    
    return $result ? true : new WP_Error('db_error', 'Failed to create profile');
}
```

---

## Teacher Registration Flow

### User Journey
1. **Access**: Navigate to `/teacher-register`
2. **Form Display**: Extended registration form
3. **Validation**: Client and server-side validation
4. **Submission**: AJAX form submission
5. **Processing**: User creation with pending status
6. **Notification**: Admin notification (if implemented)
7. **Confirmation**: Success message with pending approval note

### Key Differences from Student Registration
- **Status**: Set to 'pending' instead of 'approved'
- **Additional Fields**: qualification, experience
- **Approval Required**: Cannot login until admin approval
- **Admin Notification**: Email sent to administrators

### Processing Code
```php
// Teacher profile creation
$result = $wpdb->insert($wpdb->prefix . 'lms_teachers', [
    'user_id' => $user_id,
    'full_name' => $data['full_name'],
    'email' => $data['email'],
    'phone' => $data['phone'] ?? null,
    'qualification' => $data['qualification'] ?? null,
    'experience' => $data['experience'] ?? null,
    'bio' => $data['bio'] ?? null,
    'status' => 'pending' // Teachers require approval
]);
```

---

## Password Reset Flow

### Forgot Password Process

#### User Journey
1. **Access**: Navigate to `/forgot-password`
2. **Form Display**: Email input form
3. **Submission**: Enter email address
4. **Validation**: Check if user exists
5. **Email Sending**: Reset link sent to email
6. **Confirmation**: Success message displayed

#### Backend Processing
```php
public function ajax_forgot_password() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    $email = sanitize_email($_POST['email']);
    $user = get_user_by('email', $email);
    
    if (!$user) {
        // Don't reveal if email exists for security
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
    wp_mail($email, $subject, $message);
    
    wp_send_json_success(['message' => 'Password reset link sent to your email.']);
}
```

### Reset Password Process

#### User Journey
1. **Email Link**: Click reset link from email
2. **Access**: Navigate to `/reset-password?key=XXX&login=YYY`
3. **Form Display**: New password form
4. **Validation**: Token validation, password matching
5. **Update**: Password updated in database
6. **Auto Login**: User automatically logged in
7. **Redirect**: Redirect to dashboard

#### Backend Processing
```php
public function ajax_reset_password() {
    $key = sanitize_text_field($_POST['key']);
    $login = sanitize_user($_POST['login']);
    $password = sanitize_text_field($_POST['password']);
    $confirm = sanitize_text_field($_POST['confirm_password']);
    
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
    reset_password($user, $password);
    
    // Auto login
    wp_set_auth_cookie($user->ID, true);
    
    // Get role-based redirect
    $redirect = $this->get_role_redirect($user);
    
    wp_send_json_success([
        'message' => 'Password updated successfully!',
        'redirect' => $redirect
    ]);
}
```

---

## Logout Flow

### User Journey
1. **Trigger**: Click logout button/link
2. **AJAX Request**: Logout request sent
3. **Session Cleanup**: WordPress session terminated
4. **Redirect**: Redirect to homepage

### Code Implementation
```php
public function ajax_logout() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    wp_logout();
    
    wp_send_json_success([
        'message' => 'You have been logged out.',
        'redirect' => home_url('/')
    ]);
}
```

---

## Admin Approval Flow

### Teacher Approval Process
1. **Registration**: Teacher registers with pending status
2. **Admin Notification**: Admin receives notification
3. **Admin Review**: Admin reviews teacher application
4. **Approval**: Admin updates status to 'approved'
5. **Email Notification**: Teacher receives approval email
6. **Access**: Teacher can now login

### Admin Interface Code
```php
// Approve teacher
public function approve_teacher($teacher_id) {
    global $wpdb;
    $result = $wpdb->update(
        $wpdb->prefix . 'lms_teachers',
        ['status' => 'approved'],
        ['id' => $teacher_id]
    );
    
    if ($result) {
        // Send approval email
        $this->send_approval_email($teacher_id);
    }
    
    return $result;
}
```

---

## Error Handling Flows

### Network Errors
- **AJAX Failure**: Show generic error message
- **Timeout**: Retry mechanism or timeout message
- **Server Error**: "Something went wrong, please try again"

### Validation Errors
- **Field Required**: Highlight field with error message
- **Invalid Format**: Show format requirements
- **Duplicate Email**: "This email is already registered"

### Security Errors
- **Invalid Nonce**: "Security check failed, please refresh the page"
- **Suspicious Activity**: Account temporarily locked
- **Rate Limiting**: "Too many attempts, please wait"

---

## Session Management Flow

### Login Session Creation
```php
// After successful authentication
wp_set_auth_cookie($user->ID, $remember);
$_SESSION['edtech_user_role'] = $user->roles[0];
$_SESSION['edtech_login_time'] = time();
```

### Session Validation
```php
// On each page load
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $role = $this->helpers->get_user_role($user->ID);
    
    // Redirect if accessing wrong dashboard
    if ($this->is_wrong_dashboard($role)) {
        wp_redirect($this->get_correct_dashboard($role));
        exit;
    }
}
```

### Session Expiration
- WordPress handles automatic expiration
- Custom session timeout for security
- Automatic logout on inactivity

---

## Development Notes

### Flow Testing Checklist
- [ ] Happy path works end-to-end
- [ ] Error scenarios handled gracefully
- [ ] Network failures managed
- [ ] Session persistence verified
- [ ] Role-based redirects correct
- [ ] Security validations in place

### Common Issues
- **Race Conditions**: Multiple simultaneous logins
- **Session Hijacking**: Proper session handling
- **CSRF Attacks**: Nonce validation
- **Brute Force**: Rate limiting implementation

### Performance Considerations
- Minimize database queries during auth
- Cache user profile data
- Optimize redirect logic
- Use AJAX for better UX

These flows ensure secure, user-friendly authentication while maintaining WordPress compatibility and following security best practices.