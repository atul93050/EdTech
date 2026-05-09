# AUTH DEVELOPER GUIDE

## Overview

This guide provides developers with comprehensive information for working with the EdTech authentication system. It covers architecture, development practices, customization, and extension points.

## Architecture Deep Dive

### Service Layer Architecture

The authentication system follows a service-oriented architecture with clear separation of concerns:

```
Edtech_Auth (Main Service)
├── Edtech_Db (Data Access)
├── Edtech_Helpers (Utilities)
├── Edtech_Ajax (AJAX Handlers)
└── Edtech_Config (Configuration)
```

#### Service Responsibilities

**Edtech_Auth**:
- Authentication logic
- Form rendering
- User session management
- Redirect handling

**Edtech_Db**:
- Database operations
- Schema management
- Data validation
- Migration handling

**Edtech_Helpers**:
- Role checking utilities
- Permission validation
- Data formatting
- Common functions

**Edtech_Ajax**:
- AJAX endpoint registration
- Request routing
- Response formatting

### Dependency Injection

Services are instantiated with dependencies:

```php
class Edtech_Plugin {
    private $db;
    private $helpers;
    private $auth;
    private $ajax;
    
    public function __construct() {
        $this->db = new Edtech_Db();
        $this->helpers = new Edtech_Helpers();
        $this->auth = new Edtech_Auth($this->db, $this->helpers, $this->ajax);
        $this->ajax = new Edtech_Ajax($this->auth, $this->db);
    }
}
```

## Development Environment Setup

### Local Development

#### Required Tools
- PHP 8.0+
- MySQL 5.7+
- WordPress 6.0+
- Node.js (for asset compilation)
- Git

#### Development Configuration
```php
// wp-config.php for development
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
define('EDTECH_DEBUG', true);
define('SAVEQUERIES', true); // For query monitoring
```

#### Database Setup
```sql
-- Create development database
CREATE DATABASE edtech_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Import WordPress tables
-- Run plugin activation to create LMS tables
```

### Testing Environment

#### Unit Testing Setup
```php
// tests/bootstrap.php
require_once dirname(__FILE__) . '/../../edtech-live-system.php';

class Auth_Test_Case extends WP_UnitTestCase {
    public function setUp() {
        parent::setUp();
        $this->factory = new WP_UnitTest_Factory();
    }
    
    public function tearDown() {
        parent::tearDown();
    }
}
```

#### Test Examples
```php
class Test_Edtech_Auth extends Auth_Test_Case {
    public function test_student_registration() {
        $data = [
            'email' => 'student@test.com',
            'password' => 'password123',
            'full_name' => 'Test Student'
        ];
        
        $result = $this->auth->register_student($data);
        
        $this->assertTrue($result);
        $this->assertUserExists('student@test.com');
        $this->assertUserHasRole('student@test.com', 'edtech_student');
    }
}
```

## Customization Guide

### Adding Custom User Fields

#### Database Schema Extension
```php
// In Edtech_Db::get_table_sql()
'students' => "CREATE TABLE {$this->prefix}lms_students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT(20) UNSIGNED NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    grade VARCHAR(50),
    city VARCHAR(100),
    custom_field VARCHAR(255), -- Your custom field
    status ENUM('approved', 'pending', 'suspended') DEFAULT 'approved',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES {$this->wp_prefix}users(ID) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",
```

#### Form Template Extension
```php
// In templates/parts/auth-forms.php
<?php if ($auth_type === 'student_register'): ?>
    <div class="form-group">
        <label for="custom_field">Custom Field</label>
        <input type="text" id="custom_field" name="custom_field" required>
    </div>
<?php endif; ?>
```

#### Validation Extension
```php
// In Edtech_Auth::validate_registration_data()
private function validate_registration_data($data, $role) {
    $errors = [];
    
    // Existing validation...
    
    // Custom field validation
    if ($role === 'edtech_student' && empty($data['custom_field'])) {
        $errors[] = 'Custom field is required for students';
    }
    
    return empty($errors) ? $data : new WP_Error('validation_error', implode(', ', $errors));
}
```

### Custom Redirect Logic

#### Role-Based Redirects
```php
// Extend Edtech_Auth::get_role_redirect()
private function get_role_redirect($user) {
    if ($this->helpers->is_super_admin($user->ID)) {
        return home_url('/admin-dashboard');
    }
    
    if ($this->helpers->is_teacher($user->ID)) {
        // Custom teacher redirect logic
        $profile = $this->db->get_user_profile($user->ID);
        if ($profile->custom_redirect) {
            return $profile->custom_redirect;
        }
        return home_url('/teacher-dashboard');
    }
    
    if ($this->helpers->is_student($user->ID)) {
        return home_url('/student-dashboard');
    }
    
    return home_url('/');
}
```

### Custom Authentication Methods

#### Social Login Integration
```php
class Edtech_Social_Auth extends Edtech_Auth {
    public function authenticate_google($google_token) {
        // Verify Google token
        $google_user = $this->verify_google_token($google_token);
        
        // Find or create user
        $user = $this->find_or_create_user_from_google($google_user);
        
        // Log the user in
        wp_set_auth_cookie($user->ID);
        
        return $this->get_role_redirect($user);
    }
    
    private function verify_google_token($token) {
        // Google API verification logic
        $response = wp_remote_get("https://oauth2.googleapis.com/tokeninfo?id_token=$token");
        return json_decode(wp_remote_retrieve_body($response));
    }
}
```

## Extension Points

### Action Hooks

#### Authentication Hooks
```php
// Before authentication
do_action('edtech_before_auth', $credentials, $auth_type);

// After successful authentication
do_action('edtech_auth_success', $user, $auth_type);

// After failed authentication
do_action('edtech_auth_failed', $credentials, $error, $auth_type);

// Before user registration
do_action('edtech_before_register', $data, $role);

// After user registration
do_action('edtech_after_register', $user_id, $data, $role);
```

#### Usage Examples
```php
// Custom logging
add_action('edtech_auth_success', function($user, $auth_type) {
    error_log("User {$user->user_email} logged in via $auth_type");
});

// Custom user setup
add_action('edtech_after_register', function($user_id, $data, $role) {
    // Send welcome email
    wp_mail($data['email'], 'Welcome!', 'Welcome to our platform!');
    
    // Set up user preferences
    update_user_meta($user_id, 'edtech_preferences', ['theme' => 'default']);
});
```

### Filter Hooks

#### Form Customization
```php
// Modify registration form fields
add_filter('edtech_registration_fields', function($fields, $role) {
    if ($role === 'edtech_student') {
        $fields['custom_field'] = [
            'type' => 'text',
            'label' => 'Custom Field',
            'required' => true
        ];
    }
    return $fields;
}, 10, 2);

// Modify login redirect
add_filter('edtech_login_redirect', function($redirect, $user) {
    // Custom redirect logic
    if (get_user_meta($user->ID, 'first_login', true)) {
        update_user_meta($user->ID, 'first_login', false);
        return home_url('/welcome');
    }
    return $redirect;
}, 10, 2);
```

#### Validation Filters
```php
// Custom password validation
add_filter('edtech_validate_password', function($is_valid, $password, $user_data) {
    // Additional password rules
    if (!preg_match('/[!@#$%^&*()]/', $password)) {
        return new WP_Error('weak_password', 'Password must contain a special character');
    }
    return $is_valid;
}, 10, 3);

// Custom email validation
add_filter('edtech_validate_email', function($is_valid, $email, $context) {
    // Check against custom blacklist
    $blacklist = get_option('edtech_email_blacklist', []);
    $domain = substr(strrchr($email, "@"), 1);
    
    if (in_array($domain, $blacklist)) {
        return new WP_Error('blacklisted_email', 'This email domain is not allowed');
    }
    return $is_valid;
}, 10, 3);
```

## API Development

### REST API Endpoints

#### Custom Auth Endpoints
```php
class Edtech_REST_API {
    public function register_routes() {
        register_rest_route('edtech/v1', '/auth/login', [
            'methods' => 'POST',
            'callback' => [$this, 'api_login'],
            'permission_callback' => '__return_true',
            'args' => [
                'email' => [
                    'required' => true,
                    'validate_callback' => function($value) {
                        return is_email($value);
                    }
                ],
                'password' => [
                    'required' => true
                ]
            ]
        ]);
    }
    
    public function api_login($request) {
        $email = $request->get_param('email');
        $password = $request->get_param('password');
        
        // Use existing auth logic
        $result = $this->auth->authenticate_user($email, $password);
        
        if (is_wp_error($result)) {
            return new WP_Error('auth_failed', $result->get_error_message(), ['status' => 401]);
        }
        
        // Generate API token
        $token = $this->generate_api_token($result->ID);
        
        return [
            'success' => true,
            'user' => $result->data,
            'token' => $token
        ];
    }
}
```

### AJAX API Extensions

#### Custom AJAX Actions
```php
// Register custom action
add_action('wp_ajax_edtech_custom_action', [$this, 'handle_custom_action']);

// Handler
public function handle_custom_action() {
    check_ajax_referer('edtech_live_nonce', 'nonce');
    
    $user_id = get_current_user_id();
    $action_data = $_POST['action_data'];
    
    // Process custom action
    $result = $this->process_custom_action($user_id, $action_data);
    
    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }
    
    wp_send_json_success(['data' => $result]);
}
```

## Theme Integration

### Template Overrides

#### Custom Auth Templates
```php
// Theme: page-auth.php
<?php get_header(); ?>

<div class="auth-container">
    <div class="auth-form-wrapper">
        <?php
        global $edtech_auth;
        $auth_type = get_query_var('edtech_auth');
        $edtech_auth->render_auth_form($auth_type);
        ?>
    </div>
    
    <div class="auth-sidebar">
        <!-- Custom content -->
    </div>
</div>

<?php get_footer(); ?>
```

#### Form Partial Overrides
```php
// Theme: edtech/auth-forms/login.php
<div class="custom-login-form">
    <h2><?php _e('Welcome Back', 'edtech'); ?></h2>
    
    <form id="edtech-login-form" method="post">
        <div class="form-group">
            <label for="email"><?php _e('Email', 'edtech'); ?></label>
            <input type="email" id="email" name="email" required>
        </div>
        
        <div class="form-group">
            <label for="password"><?php _e('Password', 'edtech'); ?></label>
            <input type="password" id="password" name="password" required>
        </div>
        
        <button type="submit" class="btn btn-primary">
            <?php _e('Sign In', 'edtech'); ?>
        </button>
    </form>
</div>
```

### Styling Customization

#### CSS Variables
```css
/* Theme: style.css */
:root {
    --edtech-primary-color: #007cba;
    --edtech-secondary-color: #f0f0f0;
    --edtech-text-color: #333;
    --edtech-border-radius: 4px;
}

.auth-form {
    background: var(--edtech-secondary-color);
    border-radius: var(--edtech-border-radius);
    color: var(--edtech-text-color);
}

.auth-form .btn-primary {
    background: var(--edtech-primary-color);
}
```

## Performance Optimization

### Database Optimization

#### Query Optimization
```php
// Efficient user lookup
public function get_user_profile_cached($user_id) {
    $cache_key = "edtech_user_profile_{$user_id}";
    $profile = wp_cache_get($cache_key);
    
    if ($profile === false) {
        $profile = $this->get_user_profile($user_id);
        wp_cache_set($cache_key, $profile, 'edtech_auth', 300);
    }
    
    return $profile;
}

// Batch operations
public function bulk_update_user_status($user_ids, $status) {
    global $wpdb;
    
    $placeholders = implode(',', array_fill(0, count($user_ids), '%d'));
    $query = $wpdb->prepare("
        UPDATE {$wpdb->prefix}lms_students 
        SET status = %s 
        WHERE user_id IN ($placeholders)
    ", array_merge([$status], $user_ids));
    
    return $wpdb->query($query);
}
```

### Caching Strategies

#### Object Caching
```php
// User role caching
public function get_user_roles_cached($user_id) {
    $cache_key = "edtech_user_roles_{$user_id}";
    $roles = wp_cache_get($cache_key);
    
    if ($roles === false) {
        $user = get_user_by('id', $user_id);
        $roles = $user->roles;
        wp_cache_set($cache_key, $roles, 'edtech_auth', 600); // 10 minutes
    }
    
    return $roles;
}

// Clear cache on role change
add_action('set_user_role', function($user_id, $role, $old_roles) {
    wp_cache_delete("edtech_user_roles_{$user_id}");
});
```

### Asset Optimization

#### JavaScript Optimization
```javascript
// Async loading
wp_enqueue_script('edtech-auth', plugin_dir_url(__FILE__) . 'assets/js/auth.js', ['jquery'], '1.0.0', true);

// Code splitting
const authModule = {
    login: () => import('./modules/login.js'),
    register: () => import('./modules/register.js')
};
```

## Security Best Practices

### Input Validation

#### Comprehensive Validation
```php
private function validate_all_inputs($data) {
    $validated = [];
    
    foreach ($data as $key => $value) {
        switch ($key) {
            case 'email':
                $validated[$key] = $this->validate_email($value);
                break;
            case 'password':
                $validated[$key] = $this->validate_password($value);
                break;
            case 'full_name':
                $validated[$key] = $this->validate_name($value);
                break;
            default:
                $validated[$key] = sanitize_text_field($value);
        }
    }
    
    return $validated;
}
```

### Secure Coding

#### CSRF Protection
```php
// All forms include nonce
wp_nonce_field('edtech_auth_action', 'edtech_nonce');

// AJAX requests validate nonce
check_ajax_referer('edtech_auth_action', 'nonce');
```

#### XSS Prevention
```php
// Output escaping
echo esc_html($user->display_name);
echo esc_attr($form_value);
echo esc_url($redirect_url);

// JSON responses
wp_send_json_success(['message' => esc_html($message)]);
```

## Testing Strategies

### Unit Testing

#### Auth Logic Testing
```php
class Edtech_Auth_Test extends WP_UnitTestCase {
    public function test_password_validation() {
        $auth = new Edtech_Auth();
        
        // Test valid password
        $result = $auth->validate_password('StrongPass123!');
        $this->assertTrue($result);
        
        // Test weak password
        $result = $auth->validate_password('weak');
        $this->assertWPError($result);
    }
    
    public function test_user_creation() {
        $data = [
            'email' => 'test@example.com',
            'password' => 'TestPass123!',
            'full_name' => 'Test User'
        ];
        
        $user_id = $this->auth->create_student($data);
        
        $this->assertIsInt($user_id);
        $this->assertUserExists('test@example.com');
    }
}
```

### Integration Testing

#### Full Auth Flow Testing
```php
public function test_complete_auth_flow() {
    // Register user
    $register_data = [
        'email' => 'integration@test.com',
        'password' => 'TestPass123!',
        'full_name' => 'Integration Test'
    ];
    
    $user_id = $this->auth->create_student($register_data);
    $this->assertIsInt($user_id);
    
    // Attempt login
    $login_result = $this->auth->authenticate_user('integration@test.com', 'TestPass123!');
    $this->assertInstanceOf(WP_User::class, $login_result);
    
    // Check redirect
    $redirect = $this->auth->get_role_redirect($login_result);
    $this->assertEquals(home_url('/dashboard'), $redirect);
}
```

### E2E Testing

#### Browser Automation
```javascript
// test/e2e/auth.spec.js
describe('Authentication Flow', () => {
    it('should allow student registration and login', () => {
        cy.visit('/student-register');
        
        cy.get('#email').type('e2e@test.com');
        cy.get('#password').type('TestPass123!');
        cy.get('#full_name').type('E2E Test User');
        
        cy.get('form').submit();
        
        cy.url().should('include', '/');
        cy.contains('Registration successful');
        
        // Test login
        cy.visit('/student-login');
        cy.get('#email').type('e2e@test.com');
        cy.get('#password').type('TestPass123!');
        cy.get('form').submit();
        
        cy.url().should('include', '/dashboard');
    });
});
```

## Deployment Guide

### Production Deployment

#### Environment Configuration
```php
// Production wp-config.php
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('ENFORCE_SSL', true);
define('EDTECH_DEBUG', false);

// Security constants
define('AUTH_KEY', 'your-unique-auth-key');
define('SECURE_AUTH_KEY', 'your-secure-auth-key');
```

#### Database Migration
```php
// Production database setup
public function deploy_database() {
    // Create tables
    $this->create_missing_tables();
    
    // Run migrations
    $this->run_migrations();
    
    // Set production version
    update_option('lms_db_version', self::LMS_DB_VERSION);
    
    // Create indexes for performance
    $this->create_performance_indexes();
}
```

### Monitoring & Maintenance

#### Health Checks
```php
public function auth_system_health_check() {
    $checks = [
        'database_connection' => $this->check_db_connection(),
        'tables_exist' => $this->check_tables_exist(),
        'ajax_endpoints' => $this->check_ajax_endpoints(),
        'email_sending' => $this->check_email_sending()
    ];
    
    $failed_checks = array_filter($checks, function($check) {
        return $check !== true;
    });
    
    if (!empty($failed_checks)) {
        $this->send_health_alert($failed_checks);
    }
    
    return $checks;
}
```

#### Backup Strategies
```php
public function create_auth_backup() {
    // Backup user data
    $this->backup_user_tables();
    
    // Backup settings
    $this->backup_auth_settings();
    
    // Backup logs
    $this->backup_security_logs();
    
    // Encrypt backup
    $this->encrypt_backup_file();
}
```

This developer guide provides comprehensive information for extending, customizing, and maintaining the authentication system effectively.