# AUTH TROUBLESHOOTING

## Overview

This guide provides solutions for common authentication issues, debugging techniques, and maintenance procedures for the EdTech authentication system.

## Common Issues & Solutions

### Login Issues

#### Issue: "Invalid email or password" error
**Symptoms**: Users cannot log in with correct credentials
**Possible Causes**:
1. Incorrect password hashing
2. Database connection issues
3. User role not set correctly
4. Account suspended

**Debug Steps**:
```php
// Check user exists
$user = get_user_by('email', $email);
if (!$user) {
    error_log("User not found: $email");
    return;
}

// Check password
$check = wp_check_password($password, $user->user_pass, $user->ID);
error_log("Password check result: " . ($check ? 'true' : 'false'));

// Check user role
$user_obj = new WP_User($user->ID);
error_log("User roles: " . implode(', ', $user_obj->roles));

// Check account status
$profile = $this->db->get_user_profile($user->ID);
error_log("Account status: " . ($profile ? $profile->status : 'no profile'));
```

**Solutions**:
1. **Reset password**: Use WordPress password reset
2. **Check database**: Verify user exists in wp_users
3. **Role assignment**: Ensure correct role is assigned
4. **Profile status**: Check if account is approved

#### Issue: Login redirects to wrong page
**Symptoms**: After login, user goes to incorrect dashboard
**Debug Code**:
```php
public function debug_redirect($user) {
    error_log("Login redirect debug:");
    error_log("User ID: " . $user->ID);
    error_log("User roles: " . implode(', ', $user->roles));
    
    $redirect = $this->get_role_redirect($user);
    error_log("Calculated redirect: $redirect");
    
    return $redirect;
}
```

**Common Causes**:
- Incorrect role detection
- Wrong redirect logic
- Plugin conflicts

### Registration Issues

#### Issue: Registration fails silently
**Symptoms**: Form submits but user account not created
**Debug Steps**:
```php
// Enable error logging
add_action('wp_ajax_nopriv_edtech_register', function() {
    error_log("Registration attempt started");
    
    // Call original handler
    $auth = new Edtech_Auth();
    $result = $auth->ajax_register();
    
    error_log("Registration result: " . json_encode($result));
}, 1);
```

**Check Points**:
1. **Nonce validation**: Ensure nonce is valid
2. **Input validation**: Check all required fields
3. **Email uniqueness**: Verify email doesn't exist
4. **Database errors**: Check for DB insertion failures

#### Issue: Teacher registration stuck on "pending"
**Symptoms**: Teachers cannot login after registration
**Debug Query**:
```sql
SELECT u.user_email, t.status, t.created_at
FROM wp_users u
JOIN wp_lms_teachers t ON u.ID = t.user_id
WHERE t.status = 'pending';
```

**Solutions**:
- Manually approve via admin panel
- Check approval email system
- Verify admin notification settings

### Password Reset Issues

#### Issue: Reset emails not sending
**Symptoms**: Password reset requests don't send emails
**Debug Steps**:
```php
// Test email sending
add_action('init', function() {
    if (isset($_GET['test_email'])) {
        $result = wp_mail('test@example.com', 'Test', 'Test message');
        error_log("Email test result: " . ($result ? 'sent' : 'failed'));
    }
});
```

**Common Causes**:
- SMTP configuration issues
- WordPress mail settings
- Email template problems
- Server firewall blocking

#### Issue: Reset links don't work
**Symptoms**: Reset links return "invalid token" error
**Debug Code**:
```php
public function debug_reset_link($key, $login) {
    error_log("Reset link debug:");
    error_log("Key: $key");
    error_log("Login: $login");
    
    $user = check_password_reset_key($key, $login);
    if (is_wp_error($user)) {
        error_log("Error: " . $user->get_error_message());
    } else {
        error_log("Valid user: " . $user->user_email);
    }
}
```

**Causes**:
- Expired reset keys (default 24 hours)
- Used reset keys
- Incorrect URL generation

### AJAX Issues

#### Issue: AJAX requests return 400/500 errors
**Symptoms**: Forms don't submit, console shows network errors
**Debug Steps**:
```javascript
// Enhanced AJAX error logging
function edtechSubmitForm(formData, action, callback) {
    console.log('AJAX Request:', action, formData);
    
    fetch(edtechAjax.ajax_url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            callback(data.data);
        } else {
            console.error('AJAX Error:', data.data);
        }
    })
    .catch(error => {
        console.error('Network Error:', error);
    });
}
```

**Server-Side Debug**:
```php
// Log all AJAX requests
add_action('wp_ajax_nopriv_edtech_login', function() {
    error_log('AJAX Login Request: ' . json_encode($_POST));
}, 1);
```

**Common Causes**:
- Invalid nonces
- Missing action handlers
- Server errors
- Plugin conflicts

### Database Issues

#### Issue: Profile data not saving
**Symptoms**: User accounts created but profile data missing
**Debug Query**:
```sql
-- Check for orphaned users
SELECT u.ID, u.user_email, u.user_registered
FROM wp_users u
LEFT JOIN wp_lms_students s ON u.ID = s.user_id
LEFT JOIN wp_lms_teachers t ON u.ID = t.user_id
WHERE s.user_id IS NULL AND t.user_id IS NULL
AND u.user_registered > DATE_SUB(NOW(), INTERVAL 1 DAY);
```

**Solutions**:
- Check database table existence
- Verify foreign key constraints
- Check for DB errors during insertion

#### Issue: Database version conflicts
**Symptoms**: Plugin updates break existing data
**Debug Code**:
```php
// Check current DB version
$current_version = get_option('lms_db_version', '0');
$plugin_version = Edtech_Db::LMS_DB_VERSION;

error_log("Current DB version: $current_version");
error_log("Plugin DB version: $plugin_version");

if (version_compare($current_version, $plugin_version, '<')) {
    error_log("Database upgrade needed");
}
```

## Debugging Tools

### Debug Logging

#### Enable Debug Mode
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);

// Plugin specific
define('EDTECH_DEBUG', true);
```

#### Custom Debug Functions
```php
class Edtech_Debug {
    public static function log($message, $data = null) {
        if (!defined('EDTECH_DEBUG') || !EDTECH_DEBUG) return;
        
        $log_message = "[EdTech Auth] $message";
        if ($data) {
            $log_message .= " | Data: " . json_encode($data);
        }
        
        error_log($log_message);
    }
    
    public static function dump_user($user_id) {
        $user = get_user_by('id', $user_id);
        $profile = get_user_profile($user_id);
        
        self::log("User dump", [
            'user' => $user->data,
            'roles' => $user->roles,
            'profile' => $profile
        ]);
    }
}
```

### Browser Developer Tools

#### Network Tab Debugging
1. Open browser dev tools
2. Go to Network tab
3. Attempt login/registration
4. Check AJAX request/response
5. Look for failed requests

#### Console Debugging
```javascript
// Add to plugin.js for debugging
console.log('EdTech Auth Debug: Form submission');
console.log('Form data:', formData);
console.log('AJAX URL:', edtechAjax.ajax_url);
```

### Database Debugging

#### Query Monitor Plugin
- Install Query Monitor plugin
- Check for slow queries
- Monitor database errors
- View AJAX request logs

#### Manual Database Checks
```sql
-- Check table structure
DESCRIBE wp_lms_students;
DESCRIBE wp_lms_teachers;

-- Check data integrity
SELECT COUNT(*) as total_users FROM wp_users WHERE user_email LIKE '%@%';
SELECT COUNT(*) as total_students FROM wp_lms_students;
SELECT COUNT(*) as total_teachers FROM wp_lms_teachers;

-- Find orphaned records
SELECT u.user_email FROM wp_users u
LEFT JOIN wp_lms_students s ON u.ID = s.user_id
WHERE s.user_id IS NULL AND u.user_registered > '2024-01-01';
```

## Maintenance Procedures

### Regular Maintenance

#### Database Cleanup
```php
public function cleanup_auth_data() {
    global $wpdb;
    
    // Remove old failed login attempts
    $wpdb->query("DELETE FROM wp_lms_security_log WHERE event = 'failed_login' AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    
    // Remove expired sessions
    $wpdb->query("DELETE FROM wp_lms_sessions WHERE expires < NOW()");
    
    // Remove unverified accounts after 7 days
    $wpdb->query("DELETE u FROM wp_users u JOIN wp_lms_teachers t ON u.ID = t.user_id WHERE t.status = 'pending' AND u.user_registered < DATE_SUB(NOW(), INTERVAL 7 DAY)");
}
```

#### Security Audit
```php
public function run_security_audit() {
    // Check for weak passwords
    $weak_passwords = $this->find_weak_passwords();
    
    // Check for old sessions
    $old_sessions = $this->find_old_sessions();
    
    // Check for suspicious login patterns
    $suspicious_activity = $this->detect_suspicious_activity();
    
    // Generate audit report
    $this->generate_audit_report($weak_passwords, $old_sessions, $suspicious_activity);
}
```

### Emergency Procedures

#### Account Lockout Recovery
```php
public function emergency_unlock_account($user_id) {
    // Remove lockout
    delete_user_meta($user_id, 'edtech_account_locked');
    
    // Reset failed attempts
    delete_transient("edtech_rate_limit_login_" . get_user_meta($user_id, 'edtech_last_ip', true));
    
    // Log emergency action
    $this->log_security_event($user_id, 'emergency_unlock');
}
```

#### Mass Password Reset
```php
public function emergency_password_reset($role = null) {
    $args = ['role' => $role ?: ''];
    $users = get_users($args);
    
    foreach ($users as $user) {
        // Generate new password
        $new_password = wp_generate_password(12, true);
        
        // Update password
        wp_set_password($new_password, $user->ID);
        
        // Send notification email
        $this->send_emergency_password_email($user, $new_password);
        
        // Log action
        $this->log_security_event($user->ID, 'emergency_password_reset');
    }
}
```

### Performance Monitoring

#### Auth Performance Metrics
```php
public function monitor_auth_performance() {
    // Track login times
    $start_time = microtime(true);
    // ... login process ...
    $end_time = microtime(true);
    
    $duration = $end_time - $start_time;
    if ($duration > 2.0) { // Log slow logins
        error_log("Slow login detected: {$duration}s for user {$user->ID}");
    }
    
    // Track AJAX response times
    // Track database query performance
}
```

## Plugin Conflicts

### Common Conflicts

#### Security Plugins
- WordPress Security plugins may block AJAX requests
- Disable security features temporarily for testing
- Whitelist plugin AJAX actions

#### Caching Plugins
- Cache AJAX endpoints
- Clear cache after auth changes
- Exclude auth pages from caching

#### Other Auth Plugins
- Multiple auth plugins can conflict
- Disable other plugins for testing
- Check hook priorities

### Conflict Resolution

#### Hook Priority Issues
```php
// Adjust hook priorities
add_action('wp_ajax_nopriv_edtech_login', [$this, 'ajax_login'], 5);
add_filter('template_include', [$this, 'maybe_load_auth_template'], 5);
```

#### JavaScript Conflicts
```javascript
// Use jQuery noConflict mode
(function($) {
    // Plugin code here
})(jQuery);
```

## Support Resources

### Log File Locations
- WordPress debug log: `/wp-content/debug.log`
- PHP error log: `/php_error.log`
- Web server logs: `/var/log/apache2/error.log`

### Useful Queries
```sql
-- Recent auth activity
SELECT * FROM wp_lms_security_log 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;

-- Failed login attempts by IP
SELECT ip_address, COUNT(*) as attempts 
FROM wp_lms_security_log 
WHERE event = 'failed_login' 
AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY ip_address 
ORDER BY attempts DESC;
```

### Emergency Contacts
- Check plugin documentation
- Review WordPress support forums
- Contact plugin developer
- Check server admin

This troubleshooting guide provides comprehensive solutions for maintaining and debugging the authentication system effectively.