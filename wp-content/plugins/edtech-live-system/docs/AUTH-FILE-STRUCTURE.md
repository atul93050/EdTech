# AUTH FILE STRUCTURE

## Overview

The authentication system is organized across multiple files following a modular service-based architecture. This structure separates concerns and makes the codebase maintainable and extensible.

## Core Plugin Files

### edtech-live-system.php
**Location**: `wp-content/plugins/edtech-live-system/edtech-live-system.php`
**Purpose**: Main plugin bootstrap and initialization
**Auth-Related Code**:

```php
// Route registration
public function register_routes() {
    add_rewrite_rule('^student-login/?$', 'index.php?edtech_auth=student_login', 'top');
    // ... other auth routes
}

// Template filtering
public function maybe_load_auth_template($template) {
    if (get_query_var('edtech_auth')) {
        $auth_template = locate_template('page-auth.php');
        if (!$auth_template) {
            $auth_template = plugin_dir_path(__FILE__) . 'templates/page-auth.php';
        }
        return $auth_template;
    }
    return $template;
}

// Activation hook
register_activation_hook(__FILE__, [$this, 'activate']);
```

**Key Methods**:
- `register_routes()`: Registers all auth rewrite rules
- `maybe_load_auth_template()`: Filters template loading for auth pages
- `activate()`: Plugin activation with role creation

---

## Service Classes

### services/class-edtech-auth.php
**Location**: `wp-content/plugins/edtech-live-system/services/class-edtech-auth.php`
**Purpose**: Core authentication service handling login, registration, and form rendering
**Class Structure**:

```php
class Edtech_Auth {
    private $db;
    private $helpers;
    private $ajax;

    public function __construct($db, $helpers, $ajax) {
        $this->db = $db;
        $this->helpers = $helpers;
        $this->ajax = $ajax;
    }

    // AJAX handlers
    public function ajax_login() { /* ... */ }
    public function ajax_register() { /* ... */ }
    public function ajax_forgot_password() { /* ... */ }
    public function ajax_reset_password() { /* ... */ }
    public function ajax_logout() { /* ... */ }

    // Form rendering
    public function render_auth_form($auth_type) { /* ... */ }
    public function render_login_form($auth_type) { /* ... */ }
    public function render_register_form($auth_type) { /* ... */ }
    public function render_forgot_password_form() { /* ... */ }
    public function render_reset_password_form() { /* ... */ }

    // Helper methods
    private function get_role_redirect($user) { /* ... */ }
    private function validate_registration_data($data, $role) { /* ... */ }
    private function create_user_profile($user_id, $data, $role) { /* ... */ }
}
```

**Key Methods**:
- `ajax_login()`: Handles login AJAX requests
- `ajax_register()`: Handles registration AJAX requests
- `render_auth_form()`: Main form rendering dispatcher
- `get_role_redirect()`: Determines post-login redirects

---

### ajax/class-edtech-ajax.php
**Location**: `wp-content/plugins/edtech-live-system/ajax/class-edtech-ajax.php`
**Purpose**: AJAX endpoint registration and handling
**Auth-Related Code**:

```php
class Edtech_Ajax {
    private $auth;
    private $db;

    public function __construct($auth, $db) {
        $this->auth = $auth;
        $this->db = $db;
    }

    public function register_ajax_actions() {
        // Auth actions
        add_action('wp_ajax_nopriv_edtech_login', [$this->auth, 'ajax_login']);
        add_action('wp_ajax_nopriv_edtech_register', [$this->auth, 'ajax_register']);
        add_action('wp_ajax_nopriv_edtech_forgot_password', [$this->auth, 'ajax_forgot_password']);
        add_action('wp_ajax_nopriv_edtech_reset_password', [$this->auth, 'ajax_reset_password']);
        add_action('wp_ajax_edtech_logout', [$this->auth, 'ajax_logout']);
    }
}
```

**Key Methods**:
- `register_ajax_actions()`: Registers all AJAX action hooks
- Handles both logged-in and non-logged-in auth actions

---

### database/class-edtech-db.php
**Location**: `wp-content/plugins/edtech-live-system/database/class-edtech-db.php`
**Purpose**: Database operations and table management
**Auth-Related Tables**:

```php
private function get_table_sql() {
    $tables = [
        'students' => "CREATE TABLE {$this->prefix}lms_students (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            grade VARCHAR(50),
            city VARCHAR(100),
            parent_name VARCHAR(255),
            parent_phone VARCHAR(20),
            bio TEXT,
            status ENUM('approved', 'pending', 'suspended') DEFAULT 'approved',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wp_prefix}users(ID) ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id),
            UNIQUE KEY unique_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;",

        'teachers' => "CREATE TABLE {$this->prefix}lms_teachers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            phone VARCHAR(20),
            qualification VARCHAR(255),
            experience VARCHAR(255),
            bio TEXT,
            status ENUM('approved', 'pending', 'suspended') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES {$this->wp_prefix}users(ID) ON DELETE CASCADE,
            UNIQUE KEY unique_user (user_id),
            UNIQUE KEY unique_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
    ];
    return $tables;
}
```

**Auth-Related Methods**:
- `create_student_profile()`: Creates student extended profile
- `create_teacher_profile()`: Creates teacher extended profile
- `get_user_profile()`: Retrieves user profile data
- `update_user_status()`: Updates approval status

---

## Helper Classes

### helpers/class-edtech-helpers.php
**Location**: `wp-content/plugins/edtech-live-system/helpers/class-edtech-helpers.php`
**Purpose**: Utility functions and role checking
**Auth-Related Methods**:

```php
class Edtech_Helpers {
    public function is_student($user_id = null) {
        return $this->has_role('edtech_student', $user_id);
    }

    public function is_teacher($user_id = null) {
        return $this->has_role('edtech_teacher', $user_id);
    }

    public function is_super_admin($user_id = null) {
        return $this->has_role('edtech_super_admin', $user_id);
    }

    public function has_role($role, $user_id = null) {
        $user = $user_id ? get_user_by('id', $user_id) : wp_get_current_user();
        return in_array($role, $user->roles);
    }

    public function get_user_profile($user_id = null) {
        // Returns user profile from appropriate table
    }

    public function is_user_approved($user_id = null) {
        // Checks approval status for teachers/students
    }
}
```

---

## Template Files

### templates/page-auth.php
**Location**: `wp-content/plugins/edtech-live-system/templates/page-auth.php`
**Purpose**: Main authentication template
**Structure**:

```php
<?php
/**
 * Template Name: Auth Page
 * Description: Authentication forms template
 */

get_header();

// Get auth type from query var
$auth_type = get_query_var('edtech_auth');

// Initialize auth service
global $edtech_auth;
$edtech_auth->render_auth_form($auth_type);

get_footer();
?>
```

### templates/parts/auth-forms.php
**Location**: `wp-content/plugins/edtech-live-system/templates/parts/auth-forms.php`
**Purpose**: Individual form templates
**Contains**:
- Login form HTML
- Registration form HTML
- Forgot password form HTML
- Reset password form HTML

---

## JavaScript Files

### assets/js/plugin.js
**Location**: `wp-content/plugins/edtech-live-system/assets/js/plugin.js`
**Purpose**: Frontend JavaScript for auth forms
**Auth-Related Functions**:

```javascript
// AJAX form submission
function edtechSubmitForm(formData, action, callback) {
    formData.append('action', action);
    formData.append('nonce', edtechAjax.nonce);

    fetch(edtechAjax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            callback(data.data);
        } else {
            showError(data.data.message);
        }
    });
}

// Login form handler
function handleLogin(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    edtechSubmitForm(formData, 'edtech_login', (data) => {
        window.location.href = data.redirect;
    });
}

// Registration form handler
function handleRegister(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    edtechSubmitForm(formData, 'edtech_register', (data) => {
        showSuccess('Registration successful!');
        setTimeout(() => window.location.href = '/', 2000);
    });
}
```

---

## CSS Files

### assets/css/auth.css
**Location**: `wp-content/plugins/edtech-live-system/assets/css/auth.css`
**Purpose**: Authentication form styling
**Contains**:
- Form layouts
- Button styles
- Error/success message styles
- Responsive design

---

## Configuration Files

### includes/class-edtech-config.php
**Location**: `wp-content/plugins/edtech-live-system/includes/class-edtech-config.php`
**Purpose**: Plugin configuration constants
**Auth-Related Constants**:

```php
class Edtech_Config {
    const LMS_DB_VERSION = '1.0.0';
    const STUDENT_ROLE = 'edtech_student';
    const TEACHER_ROLE = 'edtech_teacher';
    const SUPER_ADMIN_ROLE = 'edtech_super_admin';
    const AUTH_ROUTES = [
        'student-login',
        'teacher-login',
        'student-register',
        'teacher-register',
        'admin-login',
        'ed-admin-login',
        'forgot-password',
        'reset-password'
    ];
}
```

---

## Admin Files

### admin/class-edtech-admin.php
**Location**: `wp-content/plugins/edtech-live-system/admin/class-edtech-admin.php`
**Purpose**: Admin panel integration
**Auth-Related Features**:
- User management interface
- Teacher approval system
- Role assignment tools
- User status management

---

## File Dependencies

### Service Dependencies
```
Edtech_Auth
├── Edtech_Db (database operations)
├── Edtech_Helpers (utility functions)
└── Edtech_Ajax (AJAX registration)

Edtech_Ajax
├── Edtech_Auth (auth handlers)
└── Edtech_Db (data operations)
```

### Template Hierarchy
```
page-auth.php (main template)
├── parts/auth-forms.php (form HTML)
├── assets/css/auth.css (styling)
└── assets/js/plugin.js (interactivity)
```

## Development Notes

### Adding New Auth Features
1. **Backend**: Add method to `Edtech_Auth` class
2. **AJAX**: Register action in `Edtech_Ajax::register_ajax_actions()`
3. **Frontend**: Add handler in `plugin.js`
4. **Template**: Update form in `auth-forms.php`
5. **Routes**: Add rewrite rule in main plugin file

### File Naming Convention
- Classes: `class-edtech-{service}.php`
- Templates: `page-{purpose}.php`
- Assets: `{type}/{filename}.{ext}`
- AJAX actions: `edtech_{action}`

### Security Considerations
- All AJAX endpoints validate nonces
- Input sanitization in all handlers
- Role checking before sensitive operations
- XSS prevention in templates

This modular file structure ensures clean separation of concerns and makes the authentication system maintainable and extensible.