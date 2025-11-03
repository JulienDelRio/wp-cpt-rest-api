# Issue #4: Improve Security on Bearers - Implementation Report

**Issue URL**: https://github.com/JulienDelRio/wp-cpt-rest-api/issues/4

**Issue Description**: Bearer tokens should be stored hashed and not visible more than once after creation in the admin panel. Bearer verification during API requests should therefore be based on the token hash. The main goal is to ensure that tokens cannot be retrieved from the database, similar to passwords.

**Date**: 2025-10-28

---

## Executive Summary

This report outlines the necessary changes to implement secure hashing for API bearer tokens in the WordPress Custom Post Types REST API plugin. The current implementation stores API keys in plaintext in the WordPress database, which poses a security risk if the database is compromised. The proposed changes will implement industry-standard password hashing using WordPress's built-in `wp_hash_password()` and `wp_check_password()` functions, ensuring tokens are only visible once upon creation.

---

## Current Implementation Analysis

### Storage Location
- **Option Name**: `cpt_rest_api_keys`
- **Data Structure**: Array of key objects with the following fields:
  - `id`: Unique identifier (generated with `uniqid('key_')`)
  - `label`: User-provided description
  - `key`: **Plaintext API key (32 characters)**
  - `created_at`: Timestamp (MySQL format)

### Current Flow

#### 1. Key Generation ([class-wp-cpt-restapi-api-keys.php:63-93](../src/includes/class-wp-cpt-restapi-api-keys.php#L63-L93))
- Generates 32-character random string
- Characters: lowercase letters (a-z), digits (0-9), hyphens (-)
- Uses `wp_rand()` for randomness
- Ensures at least one lowercase letter, one digit, and one hyphen

#### 2. Key Storage ([class-wp-cpt-restapi-api-keys.php:102-124](../src/includes/class-wp-cpt-restapi-api-keys.php#L102-L124))
- Stores plaintext key in database
- Returns complete key object including plaintext key

#### 3. Key Display ([class-wp-cpt-restapi-admin.php:853-937](../src/admin/class-wp-cpt-restapi-admin.php#L853-L937))
- Admin panel displays all keys in plaintext
- Keys remain visible after creation
- Copy button allows copying keys at any time

#### 4. Key Validation ([class-wp-cpt-restapi-api-keys.php:195-211](../src/includes/class-wp-cpt-restapi-api-keys.php#L195-L211))
- Uses `hash_equals()` for constant-time comparison
- Compares plaintext token against plaintext stored keys

#### 5. Authentication ([class-wp-cpt-restapi-rest.php:90-147](../src/rest-api/class-wp-cpt-restapi-rest.php#L90-L147))
- Extracts Bearer token from Authorization header
- Passes plaintext token to `validate_key()` method

---

## Security Vulnerabilities

### Current Risk Factors

1. **Database Compromise**: If an attacker gains read access to the WordPress database, all API keys are immediately exposed in plaintext
2. **Backup Exposure**: Database backups contain plaintext keys
3. **Admin Access**: Any user with database access can read all API keys
4. **Log Files**: Keys may appear in plaintext in debug logs or error logs
5. **No Forward Secrecy**: Past keys can be recovered even after deletion if database backups exist

### Security Best Practices Violated

- **OWASP A02:2021 - Cryptographic Failures**: Sensitive data stored without encryption/hashing
- **CWE-256**: Plaintext Storage of a Password
- **PCI DSS 3.2.1**: Requirement 8.2.1 mandates hashing of authentication credentials

---

## Proposed Solution

### Overview

Implement one-way hashing for API keys using WordPress's built-in password hashing functions, which use bcrypt by default. Keys will be hashed before storage and only the hash will be retained. The plaintext key will be shown only once upon creation.

### Technical Approach

#### 1. Hashing Algorithm
- **Function**: `wp_hash_password($key)` - WordPress wrapper around PHP's `password_hash()`
- **Algorithm**: bcrypt (default) with automatic salt generation
- **Verification**: `wp_check_password($plaintext, $hash)` - WordPress wrapper around `password_verify()`
- **Benefits**:
  - Industry-standard algorithm
  - Automatic salt generation
  - Resistant to rainbow table attacks
  - Constant-time comparison built-in

#### 2. Data Structure Changes

**Before**:
```php
array(
    'id'         => 'key_abc123',
    'label'      => 'Production API',
    'key'        => 'abc123xyz789...', // Plaintext
    'created_at' => '2025-10-28 10:00:00'
)
```

**After**:
```php
array(
    'id'         => 'key_abc123',
    'label'      => 'Production API',
    'key_hash'   => '$2y$10$...', // bcrypt hash
    'key_prefix' => 'abc1', // First 4 chars for display
    'created_at' => '2025-10-28 10:00:00'
)
```

**Rationale for `key_prefix`**:
- Allows displaying partial key in admin UI for identification
- Does not compromise security (only 4 characters = 36^4 = 1,679,616 combinations)
- Helps users identify which key is which without seeing full plaintext

---

## Implementation Progress

### Completed Tasks
- ✅ **Task 1**: Update API Keys Class - Key Generation (Completed: 2025-11-03)
- ✅ **Task 2**: Update API Keys Class - Key Validation (Completed: 2025-11-03)
- ✅ **Task 3**: Update Admin Class - Table Display (Completed: 2025-11-03)
- ✅ **Task 4**: Update Admin Class - Key Creation Display (Completed: 2025-11-03)
- ✅ **Task 5**: Create Migration Function (Completed: 2025-11-03)
- ✅ **Task 6**: Add Migration Admin Notice (Completed: 2025-11-03)

### In Progress
- ⏳ Task 7: Add Migration Handler
- ⏳ Task 8: Update JavaScript
- ⏳ Task 9: Update Documentation

---

## Implementation Tasks

### Task 1: Update API Keys Class - Key Generation ✅ COMPLETED
**File**: `src/includes/class-wp-cpt-restapi-api-keys.php`
**Method**: `add_key()`
**Lines**: 102-124

**Changes Required**:
1. Hash the generated key using `wp_hash_password()`
2. Store hash in `key_hash` field instead of `key`
3. Extract first 4 characters as `key_prefix`
4. Return plaintext key ONLY in return value (not stored)

**Code Snippet**:
```php
public function add_key($label) {
    // ... validation code ...

    $key = $this->generate_key();

    // Hash the key before storage
    $key_hash = wp_hash_password($key);
    $key_prefix = substr($key, 0, 4);

    $new_key = array(
        'id'         => uniqid('key_'),
        'label'      => sanitize_text_field($label),
        'key_hash'   => $key_hash,      // NEW: Store hash
        'key_prefix' => $key_prefix,    // NEW: Store prefix
        'created_at' => current_time('mysql'),
    );

    $keys[] = $new_key;
    $updated = update_option($this->option_name, $keys);

    if ($updated) {
        $new_key['key'] = $key; // Return plaintext ONLY
        return $new_key;
    }

    return false;
}
```

---

### Task 2: Update API Keys Class - Key Validation ✅ COMPLETED
**File**: `src/includes/class-wp-cpt-restapi-api-keys.php`
**Method**: `validate_key()`
**Lines**: 195-211

**Changes Required**:
1. Replace `hash_equals()` with `wp_check_password()`
2. Compare against `key_hash` field instead of `key`

**Code Snippet**:
```php
public function validate_key($key) {
    $keys = $this->get_keys();

    if ( empty( $keys ) ) {
        return false;
    }

    foreach ($keys as $key_data) {
        // Use wp_check_password for bcrypt verification
        if ( isset($key_data['key_hash']) && wp_check_password( $key, $key_data['key_hash'] ) ) {
            return true;
        }
    }

    return false;
}
```

---

### Task 3: Update Admin Class - Table Display ✅ COMPLETED
**File**: `src/admin/class-wp-cpt-restapi-admin.php`
**Method**: `api_keys_field_callback()`
**Lines**: 866-897

**Changes Required**:
1. Replace API Key column header with "Key Prefix"
2. Display masked key with prefix
3. Remove copy button from table rows
4. Add explanatory text

**Code Snippet**:
```php
<thead>
    <tr>
        <th><?php echo esc_html__( 'Label', 'wp-cpt-rest-api' ); ?></th>
        <th><?php echo esc_html__( 'Key Prefix', 'wp-cpt-rest-api' ); ?></th>
        <th><?php echo esc_html__( 'Created', 'wp-cpt-rest-api' ); ?></th>
        <th><?php echo esc_html__( 'Actions', 'wp-cpt-rest-api' ); ?></th>
    </tr>
</thead>
<tbody>
    <?php foreach ( $keys as $key ) : ?>
        <tr>
            <td><?php echo esc_html( $key['label'] ); ?></td>
            <td>
                <code class="api-key-prefix">
                    <?php
                    $prefix = isset($key['key_prefix']) ? $key['key_prefix'] : '****';
                    echo esc_html( $prefix );
                    ?>••••••••••••••••••••••••••••
                </code>
                <span class="description" style="display: block; margin-top: 5px; font-style: italic;">
                    <?php echo esc_html__( 'Full key hidden for security', 'wp-cpt-rest-api' ); ?>
                </span>
            </td>
            <!-- ... rest of table ... -->
```

---

### Task 4: Update Admin Class - Key Creation Display ✅ COMPLETED
**File**: `src/admin/class-wp-cpt-restapi-admin.php`
**Method**: `api_keys_field_callback()`
**Lines**: 922-934

**Changes Required**:
1. Add prominent security warning
2. Emphasize one-time visibility
3. Update messaging for better clarity

**Code Snippet**:
```php
<div class="cpt-rest-api-key-generated" style="display: none;">
    <div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 12px;">
        <h4 style="margin-top: 0;">
            <span class="dashicons dashicons-warning" style="color: #f56e28;"></span>
            <?php echo esc_html__( 'Important: Save Your API Key Now', 'wp-cpt-rest-api' ); ?>
        </h4>
        <p style="margin: 8px 0;">
            <strong><?php echo esc_html__( 'This key will only be displayed once and cannot be recovered.', 'wp-cpt-rest-api' ); ?></strong>
        </p>
        <p style="margin: 8px 0 0 0;">
            <?php echo esc_html__( 'Copy it now and store it securely. If you lose this key, you will need to generate a new one.', 'wp-cpt-rest-api' ); ?>
        </p>
    </div>
    <div class="cpt-rest-api-key-display">
        <code id="cpt_rest_api_new_key" style="display: block; padding: 10px; background: #f0f0f1; font-size: 14px; word-break: break-all;"></code>
        <button type="button" class="button cpt-rest-api-copy-key" style="margin-top: 10px;">
            <span class="dashicons dashicons-clipboard"></span>
            <?php echo esc_html__( 'Copy Key', 'wp-cpt-rest-api' ); ?>
        </button>
    </div>
</div>
```

---

### Task 5: Create Migration Function ✅ COMPLETED
**File**: `src/includes/class-wp-cpt-restapi-api-keys.php`
**New Method**: `migrate_to_hashed_keys()`

**Purpose**: Handle upgrade from plaintext to hashed keys

**Code Snippet**:
```php
/**
 * Migrate existing plaintext keys to hashed format.
 *
 * WARNING: This is a destructive operation. All existing plaintext keys
 * will be deleted. Users must regenerate their API keys.
 *
 * @since    0.3
 * @return   array    Migration results.
 */
public function migrate_to_hashed_keys() {
    $keys = $this->get_keys();
    $plaintext_count = 0;

    // Check for old format (plaintext 'key' field)
    foreach ($keys as $key_data) {
        if (isset($key_data['key']) && !isset($key_data['key_hash'])) {
            $plaintext_count++;
        }
    }

    // Delete all plaintext keys
    if ($plaintext_count > 0) {
        update_option($this->option_name, array());

        // Log migration
        if (defined('WP_DEBUG_LOG') && WP_DEBUG_LOG) {
            error_log(sprintf(
                '[CPT REST API Security] Migration: Deleted %d plaintext API keys',
                $plaintext_count
            ));
        }

        return array(
            'success' => true,
            'deleted_count' => $plaintext_count,
            'message' => sprintf(
                _n(
                    'Security update: %d plaintext key was deleted. Please regenerate your API keys.',
                    'Security update: %d plaintext keys were deleted. Please regenerate your API keys.',
                    $plaintext_count,
                    'wp-cpt-rest-api'
                ),
                $plaintext_count
            )
        );
    }

    return array(
        'success' => false,
        'deleted_count' => 0,
        'message' => __('No migration needed.', 'wp-cpt-rest-api')
    );
}
```

---

### Task 6: Add Migration Admin Notice ✅ COMPLETED
**File**: `src/admin/class-wp-cpt-restapi-admin.php`
**New Method**: `display_migration_notice()`

**Purpose**: Warn admins about required migration

**Code Snippet**:
```php
/**
 * Display admin notice for required key migration.
 *
 * @since    0.3
 */
public function display_migration_notice() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Check if migration needed
    $keys = $this->api_keys->get_keys();
    $needs_migration = false;

    foreach ($keys as $key_data) {
        if (isset($key_data['key']) && !isset($key_data['key_hash'])) {
            $needs_migration = true;
            break;
        }
    }

    if (!$needs_migration) {
        return;
    }

    ?>
    <div class="notice notice-error">
        <h3 style="margin-top: 12px;">
            <span class="dashicons dashicons-shield-alt" style="color: #d63638;"></span>
            <?php echo esc_html__('Critical Security Update Required - CPT REST API', 'wp-cpt-rest-api'); ?>
        </h3>
        <p>
            <strong><?php echo esc_html__('Your API keys are stored insecurely and must be migrated.', 'wp-cpt-rest-api'); ?></strong>
        </p>
        <p>
            <?php echo esc_html__('This plugin now uses secure hashing for API keys. Your existing keys are stored in plaintext and vulnerable.', 'wp-cpt-rest-api'); ?>
        </p>
        <h4><?php echo esc_html__('What will happen:', 'wp-cpt-rest-api'); ?></h4>
        <ul style="list-style: disc; margin-left: 25px;">
            <li><?php echo esc_html__('All existing API keys will be permanently deleted', 'wp-cpt-rest-api'); ?></li>
            <li><?php echo esc_html__('You must regenerate new secure keys', 'wp-cpt-rest-api'); ?></li>
            <li><?php echo esc_html__('All services using the API must be updated with new keys', 'wp-cpt-rest-api'); ?></li>
            <li><?php echo esc_html__('New keys will only be visible once upon creation', 'wp-cpt-rest-api'); ?></li>
        </ul>
        <form method="post" action="" style="margin-top: 15px;">
            <?php wp_nonce_field('cpt_rest_api_migrate_keys', 'cpt_rest_api_migrate_nonce'); ?>
            <input type="hidden" name="cpt_rest_api_migrate_keys" value="1">
            <p>
                <button type="submit" class="button button-primary button-large">
                    <?php echo esc_html__('Migrate to Secure Keys Now', 'wp-cpt-rest-api'); ?>
                </button>
            </p>
        </form>
    </div>
    <?php
}
```

---

### Task 7: Add Migration Handler
**File**: `src/admin/class-wp-cpt-restapi-admin.php`
**Method**: Update `__construct()` and add `handle_key_migration()`

**Changes to Constructor**:
```php
public function __construct() {
    // ... existing code ...

    // Add migration hooks
    add_action('admin_init', array($this, 'handle_key_migration'));
    add_action('admin_notices', array($this, 'display_migration_notice'));
}
```

**New Method**:
```php
/**
 * Handle key migration form submission.
 *
 * @since    0.3
 */
public function handle_key_migration() {
    if (!isset($_POST['cpt_rest_api_migrate_keys'])) {
        return;
    }

    // Verify nonce
    if (!isset($_POST['cpt_rest_api_migrate_nonce']) ||
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cpt_rest_api_migrate_nonce'])), 'cpt_rest_api_migrate_keys')) {
        wp_die(__('Security check failed.', 'wp-cpt-rest-api'));
    }

    // Check capabilities
    if (!current_user_can('manage_options')) {
        wp_die(__('Insufficient permissions.', 'wp-cpt-rest-api'));
    }

    // Perform migration
    $result = $this->api_keys->migrate_to_hashed_keys();

    // Show result
    add_settings_error(
        'cpt_rest_api_migration',
        'migration_complete',
        $result['message'],
        $result['success'] ? 'updated' : 'info'
    );

    // Redirect
    wp_redirect(admin_url('options-general.php?page=cpt-rest-api'));
    exit;
}
```

---

### Task 8: Update JavaScript
**File**: `src/assets/js/wp-cpt-restapi-admin.js`

**Changes Required**:
1. Update AJAX success handler to emphasize one-time display
2. Add auto-scroll to new key
3. Optional: Add "key copied" confirmation

---

### Task 9: Update Documentation
**Files**:
- `src/API_ENDPOINTS.md` - Update authentication section
- `README.md` - Add security information
- `src/readme.txt` - Add upgrade notice
- `CLAUDE.md` - Update security notes

---

## Testing Checklist

### Functional Testing
- [ ] Generate new API key and verify it's hashed in database
- [ ] Verify key is displayed only once after creation
- [ ] Verify API authentication works with hashed keys
- [ ] Verify key deletion works
- [ ] Verify key prefix is displayed correctly in table
- [ ] Verify copy button works for new keys

### Migration Testing
- [ ] Create plaintext keys with old version
- [ ] Upgrade to new version
- [ ] Verify migration notice appears
- [ ] Run migration
- [ ] Verify old keys are deleted
- [ ] Generate new secure keys
- [ ] Verify API works with new keys

### Security Testing
- [ ] Verify no plaintext keys in database
- [ ] Verify hash format is bcrypt
- [ ] Verify authentication timing is constant
- [ ] Verify key generation randomness

---

## Rollout Strategy

### Version 0.3 (or 1.1)
1. **Pre-release**: Beta testing with migration
2. **Release**: Deploy with clear upgrade notice in readme.txt
3. **Post-release**: Monitor support requests

### Communication
- Update WordPress.org plugin page with upgrade warning
- Add prominent notice in changelog
- Document migration process in README

---

## Estimated Effort

- Implementation: 8-10 hours
- Testing: 4-6 hours
- Documentation: 2-3 hours
- **Total**: 14-19 hours

---

## Conclusion

This implementation will transform API key storage from plaintext to industry-standard bcrypt hashing, significantly improving security. While the migration requires users to regenerate keys, this is an acceptable trade-off for the security benefits.

**Recommendation**: Implement for next minor version release with clear upgrade documentation and migration path.
