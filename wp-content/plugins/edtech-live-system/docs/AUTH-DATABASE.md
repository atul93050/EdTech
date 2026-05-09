# AUTH DATABASE

## Overview

The authentication system uses WordPress core tables combined with custom LMS tables to store user data, roles, and extended profiles. The database design supports role-based access control and user management.

## WordPress Core Tables

### wp_users
**Purpose**: Core user authentication data
**Auth-Related Fields**:
- `ID`: Primary key, used as foreign key in LMS tables
- `user_login`: Username (email for LMS users)
- `user_email`: User email address
- `user_pass`: Hashed password
- `user_registered`: Registration timestamp
- `user_status`: Account status (0 = active)

### wp_usermeta
**Purpose**: User metadata storage
**Auth-Related Meta Keys**:
- `wp_capabilities`: Serialized array of user roles
- `first_name`, `last_name`: Name components
- `edtech_profile_complete`: Profile completion status
- `edtech_last_login`: Last login timestamp
- `edtech_login_count`: Login attempt counter

### wp_user_roles (via wp_options)
**Purpose**: Role definitions and capabilities
**Structure**:
```php
// Stored in wp_options with key 'wp_user_roles'
[
    'edtech_student' => [
        'name' => 'Student',
        'capabilities' => ['read' => true]
    ],
    'edtech_teacher' => [
        'name' => 'Teacher',
        'capabilities' => ['read' => true]
    ],
    'edtech_super_admin' => [
        'name' => 'Super Admin',
        'capabilities' => [
            'read' => true,
            'edit_posts' => true,
            'manage_options' => true
        ]
    ]
]
```

---

## Custom LMS Tables

### wp_lms_students
**Purpose**: Extended student profile data
**Schema**:

```sql
CREATE TABLE wp_lms_students (
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
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_email (email),
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Field Descriptions**:
- `user_id`: Links to WordPress user
- `full_name`: Student's full name
- `email`: Must match wp_users.user_email
- `status`: Account status (students auto-approved)
- `grade`: Student grade/class level
- `parent_name/phone`: Parent contact information

### wp_lms_teachers
**Purpose**: Extended teacher profile data
**Schema**:

```sql
CREATE TABLE wp_lms_teachers (
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
    
    FOREIGN KEY (user_id) REFERENCES wp_users(ID) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id),
    UNIQUE KEY unique_email (email),
    INDEX idx_status (status),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Field Descriptions**:
- `user_id`: Links to WordPress user
- `full_name`: Teacher's full name
- `qualification`: Academic/professional qualifications
- `experience`: Years or type of experience
- `status`: Requires admin approval (default: pending)

---

## Database Operations

### User Creation Process

#### Student Registration
```php
public function create_student($data) {
    global $wpdb;
    
    // 1. Create WordPress user
    $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
    
    // 2. Set role
    $user = new WP_User($user_id);
    $user->set_role('edtech_student');
    
    // 3. Create extended profile
    $wpdb->insert('wp_lms_students', [
        'user_id' => $user_id,
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'grade' => $data['grade'],
        'city' => $data['city'],
        'parent_name' => $data['parent_name'],
        'parent_phone' => $data['parent_phone'],
        'bio' => $data['bio'],
        'status' => 'approved'
    ]);
    
    return $user_id;
}
```

#### Teacher Registration
```php
public function create_teacher($data) {
    global $wpdb;
    
    // 1. Create WordPress user
    $user_id = wp_create_user($data['email'], $data['password'], $data['email']);
    
    // 2. Set role
    $user = new WP_User($user_id);
    $user->set_role('edtech_teacher');
    
    // 3. Create extended profile
    $wpdb->insert('wp_lms_teachers', [
        'user_id' => $user_id,
        'full_name' => $data['full_name'],
        'email' => $data['email'],
        'phone' => $data['phone'],
        'qualification' => $data['qualification'],
        'experience' => $data['experience'],
        'bio' => $data['bio'],
        'status' => 'pending' // Requires approval
    ]);
    
    return $user_id;
}
```

### User Authentication Queries

#### Login Validation
```php
public function authenticate_user($email, $password) {
    // WordPress handles authentication
    $user = wp_authenticate($email, $password);
    
    if (is_wp_error($user)) {
        return $user;
    }
    
    // Check role and status
    $profile = $this->get_user_profile($user->ID);
    
    if (!$profile) {
        return new WP_Error('no_profile', 'User profile not found');
    }
    
    if ($profile->status !== 'approved') {
        return new WP_Error('pending_approval', 'Account pending approval');
    }
    
    return $user;
}
```

#### Profile Retrieval
```php
public function get_user_profile($user_id) {
    global $wpdb;
    
    $user = get_user_by('id', $user_id);
    if (!$user) return false;
    
    $table = '';
    if (in_array('edtech_student', $user->roles)) {
        $table = 'wp_lms_students';
    } elseif (in_array('edtech_teacher', $user->roles)) {
        $table = 'wp_lms_teachers';
    } elseif (in_array('edtech_super_admin', $user->roles)) {
        // Super admins don't have extended profiles
        return (object) ['status' => 'approved'];
    }
    
    if (!$table) return false;
    
    return $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM {$table} WHERE user_id = %d", $user_id)
    );
}
```

### Admin Operations

#### Teacher Approval
```php
public function approve_teacher($teacher_id) {
    global $wpdb;
    
    return $wpdb->update(
        'wp_lms_teachers',
        ['status' => 'approved'],
        ['id' => $teacher_id],
        ['%s'],
        ['%d']
    );
}
```

#### User Status Updates
```php
public function update_user_status($user_id, $status) {
    global $wpdb;
    
    $user = get_user_by('id', $user_id);
    $table = $this->get_profile_table($user);
    
    if (!$table) return false;
    
    return $wpdb->update(
        $table,
        ['status' => $status],
        ['user_id' => $user_id],
        ['%s'],
        ['%d']
    );
}
```

---

## Database Schema Management

### Version Control
```php
class Edtech_Db {
    const LMS_DB_VERSION = '1.0.0';
    
    public function init() {
        $current_version = get_option('lms_db_version', '0');
        
        if (version_compare($current_version, self::LMS_DB_VERSION, '<')) {
            $this->create_tables();
            $this->run_migrations($current_version);
            update_option('lms_db_version', self::LMS_DB_VERSION);
        }
    }
}
```

### Table Creation
```php
public function create_missing_tables() {
    global $wpdb;
    
    $tables = $this->get_table_sql();
    
    foreach ($tables as $table_name => $sql) {
        $full_table_name = $wpdb->prefix . 'lms_' . $table_name;
        
        if ($wpdb->get_var("SHOW TABLES LIKE '$full_table_name'") != $full_table_name) {
            dbDelta($sql);
        }
    }
}
```

### Migrations Framework
```php
private function run_migrations($current_version) {
    $migrations = [
        '0.9.0' => [$this, 'migrate_0_9_0_to_1_0_0'],
        // Future migrations
    ];
    
    foreach ($migrations as $version => $callback) {
        if (version_compare($current_version, $version, '<')) {
            call_user_func($callback);
        }
    }
}

private function migrate_0_9_0_to_1_0_0() {
    global $wpdb;
    
    // Example migration: Add new column
    $wpdb->query("ALTER TABLE {$wpdb->prefix}lms_students ADD COLUMN city VARCHAR(100) AFTER grade");
}
```

---

## Data Integrity

### Foreign Key Constraints
- All LMS tables reference `wp_users.ID`
- CASCADE DELETE ensures cleanup on user deletion
- Maintains referential integrity

### Unique Constraints
- `user_id`: One profile per WordPress user
- `email`: Unique email across LMS users
- Prevents duplicate registrations

### Data Validation
```php
private function validate_user_data($data, $role) {
    $errors = [];
    
    // Required fields
    $required = ['full_name', 'email'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            $errors[] = "$field is required";
        }
    }
    
    // Email format
    if (!is_email($data['email'])) {
        $errors[] = "Invalid email format";
    }
    
    // Role-specific validation
    if ($role === 'edtech_student') {
        // Student-specific validation
    } elseif ($role === 'edtech_teacher') {
        if (empty($data['qualification'])) {
            $errors[] = "Qualification is required for teachers";
        }
    }
    
    return $errors;
}
```

---

## Performance Optimization

### Indexes
- Primary keys on all tables
- Foreign key indexes for joins
- Status and email indexes for queries
- Composite indexes where needed

### Query Optimization
```php
// Efficient profile lookup
public function get_user_profile_cached($user_id) {
    $cache_key = "lms_user_profile_{$user_id}";
    $profile = wp_cache_get($cache_key);
    
    if ($profile === false) {
        $profile = $this->get_user_profile($user_id);
        wp_cache_set($cache_key, $profile, '', 300); // 5 min cache
    }
    
    return $profile;
}
```

### Prepared Statements
```php
// Always use prepared statements
$profile = $wpdb->get_row(
    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}lms_students WHERE user_id = %d", $user_id)
);
```

---

## Backup and Recovery

### Export Functions
```php
public function export_user_data($user_id) {
    $user = get_user_by('id', $user_id);
    $profile = $this->get_user_profile($user_id);
    
    return [
        'wp_user' => $user->data,
        'profile' => $profile,
        'meta' => get_user_meta($user_id)
    ];
}
```

### Data Cleanup
```php
public function cleanup_orphaned_profiles() {
    global $wpdb;
    
    // Remove profiles for deleted WordPress users
    $wpdb->query("
        DELETE ls FROM {$wpdb->prefix}lms_students ls
        LEFT JOIN {$wpdb->prefix}users u ON ls.user_id = u.ID
        WHERE u.ID IS NULL
    ");
    
    // Same for teachers
    $wpdb->query("
        DELETE lt FROM {$wpdb->prefix}lms_teachers lt
        LEFT JOIN {$wpdb->prefix}users u ON lt.user_id = u.ID
        WHERE u.ID IS NULL
    ");
}
```

---

## Security Considerations

### SQL Injection Prevention
- All queries use `$wpdb->prepare()`
- Input sanitization before database operations
- Parameterized queries only

### Data Privacy
- Sensitive data encrypted where necessary
- GDPR compliance for user data
- Data retention policies

### Audit Logging
```php
private function log_auth_event($user_id, $event, $data = []) {
    global $wpdb;
    
    $wpdb->insert('wp_lms_auth_log', [
        'user_id' => $user_id,
        'event' => $event,
        'data' => json_encode($data),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'created_at' => current_time('mysql')
    ]);
}
```

This database design provides a robust foundation for user authentication and profile management while maintaining WordPress compatibility and security standards.