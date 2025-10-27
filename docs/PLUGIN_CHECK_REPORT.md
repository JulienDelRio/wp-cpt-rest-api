# WordPress Plugin Check Report
**Date**: October 27, 2025
**Plugin**: Custom Post Types RestAPI v1.0.0
**Analysis**: Pre-submission validation for WordPress.org

---

## Executive Summary

The Plugin Check tool identified **139 issues** that must be resolved before WordPress.org submission:
- **137 ERRORS** (blocking issues)
- **17 WARNINGS** (recommended fixes)

### Critical Issues Overview
1. **Text Domain Mismatch** - 135 errors across multiple files
2. **Outdated "Tested up to" Header** - Must update to WordPress 6.8
3. **Hidden Files** - `.gitkeep` file not permitted
4. **Deprecated Function** - `load_plugin_textdomain()` usage discouraged

---

## Issues by Priority

### 🔴 PRIORITY 1: BLOCKING ERRORS (Must Fix Before Submission)

These errors will cause **automatic rejection** by WordPress.org:

#### Issue 1.1: Text Domain Mismatch (135 errors)
**Severity**: CRITICAL
**Impact**: Translations will not work correctly; blocks WordPress.org approval

**Problem**:
- Plugin slug: `wp-cpt-rest-api` (based on Plugin Name)
- Current text domain: `wp-cpt-restapi` (no hyphen)
- Expected text domain: `wp-cpt-rest-api` (with hyphen)

**Affected Files**:
- `src/admin/class-wp-cpt-restapi-admin.php` - 115 instances
- `src/rest-api/class-wp-cpt-restapi-rest.php` - 20 instances
- `src/wp-cpt-rest-api.php` - Header text domain field

**Solution**:
Replace ALL instances of `'wp-cpt-restapi'` with `'wp-cpt-rest-api'` in:
- All `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `_x()` function calls
- Main plugin file header (`Text Domain:` field)
- `load_plugin_textdomain()` call

**Estimated Time**: 30 minutes (can use find/replace)

---

#### Issue 1.2: Outdated "Tested up to" Header
**Severity**: CRITICAL
**Impact**: Plugin will not show up in WordPress.org searches

**Problem**:
```
Current: Tested up to: 6.6
Required: Tested up to: 6.8 (or latest WordPress version)
```

**Affected Files**:
- `src/readme.txt` - Line with "Tested up to: 6.6"
- `src/wp-cpt-rest-api.php` - Plugin header "Tested up to: 6.6"

**Solution**:
Update both files to:
```
Tested up to: 6.8
```

**Note**: You should actually test your plugin with WordPress 6.8 before claiming compatibility.

**Estimated Time**: 5 minutes + testing time

---

#### Issue 1.3: Hidden Files Not Permitted
**Severity**: CRITICAL
**Impact**: Will cause validation failure

**Problem**:
```
File: src/languages/.gitkeep
Error: Hidden files are not permitted
```

**Solution**:
Remove the `.gitkeep` file from `src/languages/` directory before packaging.

**Note**: The languages directory will be created automatically by WordPress when needed.

**Estimated Time**: 2 minutes

---

### 🟡 PRIORITY 2: DISCOURAGED PATTERNS (Should Fix)

These won't block submission but may cause reviewer questions:

#### Issue 2.1: Deprecated load_plugin_textdomain()
**Severity**: HIGH
**Impact**: Unnecessary code for WordPress.org hosted plugins

**Problem**:
```php
// Line 98-102 in wp-cpt-rest-api.php
function wp_cpt_restapi_load_textdomain() {
    load_plugin_textdomain(
        'wp-cpt-restapi',  // Will need text domain fix too
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages'
    );
}
add_action( 'plugins_loaded', 'wp_cpt_restapi_load_textdomain' );
```

**Solution**:
WordPress.org automatically loads translations for hosted plugins since WP 4.6. You can:
1. **Option A (Recommended)**: Remove the entire function and action hook
2. **Option B**: Keep it for backwards compatibility with self-hosted users

**Recommendation**: Keep it but update the text domain to match the slug.

**Estimated Time**: 5 minutes

---

#### Issue 2.2: Trademarked Term Warning
**Severity**: MEDIUM
**Impact**: Plugin name/slug restrictions

**Problem**:
```
Plugin slug: "wp-cpt-rest-api" contains restricted term "wp"
Warning: Cannot use "WordPress" in full plugin name
```

**Current State**:
- Plugin Name: "Custom Post Types RestAPI" ✓ (no "WordPress" in name)
- Plugin Slug: "wp-cpt-rest-api" ⚠️ (contains "wp" prefix)

**Solution**:
This is actually **ACCEPTABLE** because:
- Your full plugin name doesn't contain "WordPress"
- The "wp" prefix is allowed in the slug
- Warning states: "can be used within the plugin slug, as long as you don't use the full name in the plugin name"

**Action Required**: NONE - This is informational only

**Estimated Time**: 0 minutes

---

### 🟠 PRIORITY 3: CODE QUALITY WARNINGS (Optional)

These are recommendations for best practices:

#### Issue 3.1: error_log() in Production Code
**Severity**: LOW
**Impact**: Debug code should be conditional

**Affected Files**:
- `src/admin/class-wp-cpt-restapi-admin.php:1252`
- `src/rest-api/class-wp-cpt-restapi-rest.php:295`

**Current Implementation**:
```php
if ( defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
    error_log( '...' );
}
```

**Issue**: Plugin Check flags any `error_log()` usage as a warning.

**Solution Options**:
1. Keep as-is (already conditional on WP_DEBUG_LOG) - **RECOMMENDED**
2. Use `do_action()` hooks for logging extensibility
3. Remove logging entirely

**Recommendation**: Keep current implementation. The conditional check makes this acceptable.

**Estimated Time**: 0 minutes (no change needed)

---

#### Issue 3.2: $_SERVER Variables Not Sanitized
**Severity**: LOW
**Impact**: Minor security best practice

**Affected Lines**:
- `src/rest-api/class-wp-cpt-restapi-rest.php:115` - `$_SERVER['HTTP_AUTHORIZATION']`
- `src/rest-api/class-wp-cpt-restapi-rest.php:118` - `$_SERVER['REDIRECT_HTTP_AUTHORIZATION']`
- `src/rest-api/class-wp-cpt-restapi-rest.php:244` - `$_SERVER['HTTP_CLIENT_IP']` (missing wp_unslash)
- `src/rest-api/class-wp-cpt-restapi-rest.php:254` - `$_SERVER['REMOTE_ADDR']` (missing wp_unslash)

**Current Code (Lines 115-118)**:
```php
$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] )
    ? $_SERVER['HTTP_AUTHORIZATION']
    : '';
if ( ! $auth_header && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
    $auth_header = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
}
```

**Solution**:
```php
$auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] )
    ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) )
    : '';
if ( ! $auth_header && isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
    $auth_header = sanitize_text_field( wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) );
}
```

**Current Code (Lines 244, 254)**:
```php
$client_ip = sanitize_text_field( $_SERVER['HTTP_CLIENT_IP'] );
// ...
$client_ip = sanitize_text_field( $_SERVER['REMOTE_ADDR'] );
```

**Solution**:
```php
$client_ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
// ...
$client_ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
```

**Estimated Time**: 10 minutes

---

#### Issue 3.3: Direct Database Queries (Toolset Integration)
**Severity**: LOW
**Impact**: Performance recommendation

**Affected**: All Toolset relationship queries (8 instances)

**Issue**: Direct `$wpdb->get_results()` calls without WordPress object caching

**Current Implementation**:
```php
$relationships = $wpdb->get_results(
    $wpdb->prepare("SELECT * FROM {$wpdb->prefix}toolset_relationships WHERE active = %d", 1)
);
```

**Recommendation**:
This is **ACCEPTABLE** because:
- These queries are for Toolset's custom tables (not WordPress core tables)
- There's no WordPress caching API for third-party plugin tables
- Queries are properly prepared with `$wpdb->prepare()`

**Action Required**: NONE - This is informational only

**Estimated Time**: 0 minutes

---

#### Issue 3.4: Short Description Too Long
**Severity**: LOW
**Impact**: Description will be truncated in plugin directory

**Problem**:
```
Current short description: 163 characters
Maximum allowed: 150 characters
```

**Affected**: `src/readme.txt` - Line 12 (short description after plugin headers)

**Current**:
> "A robust WordPress plugin that extends the native REST API functionalities to provide comprehensive endpoints for Custom Post Types and their associated metadata."

**Solution** (143 characters):
> "Extends WordPress REST API with comprehensive endpoints for Custom Post Types and metadata. Secure API key authentication included."

**Estimated Time**: 5 minutes

---

## Task Checklist

### Must Complete Before Submission ✅

- [ ] **Task 1**: Fix text domain mismatch (135 instances)
  - [ ] Update `src/wp-cpt-rest-api.php` header `Text Domain: wp-cpt-rest-api`
  - [ ] Find/replace `'wp-cpt-restapi'` → `'wp-cpt-rest-api'` in `src/admin/class-wp-cpt-restapi-admin.php`
  - [ ] Find/replace `'wp-cpt-restapi'` → `'wp-cpt-rest-api'` in `src/rest-api/class-wp-cpt-restapi-rest.php`
  - [ ] Update `load_plugin_textdomain()` call in `src/wp-cpt-rest-api.php:99`

- [ ] **Task 2**: Update "Tested up to" header to 6.8
  - [ ] Update `src/readme.txt` → `Tested up to: 6.8`
  - [ ] Update `src/wp-cpt-rest-api.php` header → `Tested up to: 6.8`
  - [ ] Test plugin with WordPress 6.8

- [ ] **Task 3**: Remove hidden files
  - [ ] Delete `src/languages/.gitkeep`
  - [ ] Update packaging script if needed

### Recommended Improvements 📋

- [ ] **Task 4**: Sanitize $_SERVER variables with wp_unslash()
  - [ ] Fix HTTP_AUTHORIZATION handling (lines 115, 118)
  - [ ] Fix HTTP_CLIENT_IP handling (line 244)
  - [ ] Fix REMOTE_ADDR handling (line 254)

- [ ] **Task 5**: Shorten readme.txt short description to ≤150 characters

### No Action Required ℹ️

- ✓ **error_log() usage** - Already conditional on WP_DEBUG_LOG
- ✓ **Direct database queries** - Necessary for Toolset third-party tables
- ✓ **"wp" in plugin slug** - Acceptable per WordPress.org guidelines

---

## Testing Checklist After Fixes

Before resubmitting to Plugin Check:

1. [ ] Run find/replace verification for text domain changes
2. [ ] Test plugin activation/deactivation
3. [ ] Test REST API endpoints still function
4. [ ] Test admin interface loads correctly
5. [ ] Verify translations load properly (if available)
6. [ ] Test with WordPress 6.8
7. [ ] Run Plugin Check again to verify all errors resolved
8. [ ] Package plugin ZIP without hidden files

---

## Estimated Total Time

| Priority | Time Required | Status |
|----------|---------------|--------|
| Priority 1 (CRITICAL) | ~40 minutes | **Must complete** |
| Priority 2 (SHOULD FIX) | ~5 minutes | **Recommended** |
| Priority 3 (OPTIONAL) | ~15 minutes | Optional |
| **TOTAL** | **~60 minutes** | **To be submission-ready** |

---

## Next Steps

1. **Immediate Actions** (Priority 1):
   - Fix text domain mismatch across all files
   - Update "Tested up to" headers
   - Remove .gitkeep file

2. **Before Submission**:
   - Run Plugin Check again
   - Ensure 0 ERRORS remain
   - Test plugin functionality

3. **After These Fixes**:
   - Plugin will be **READY FOR SUBMISSION** to WordPress.org
   - Expected review time: 1-10 business days
   - High probability of approval on first submission

---

## Additional Notes

### Text Domain Best Practice
The text domain MUST match your plugin slug exactly. WordPress.org automatically generates the slug from your "Plugin Name" header, converting it to lowercase and replacing spaces with hyphens.

Your plugin name "Custom Post Types RestAPI" generates the slug `custom-post-types-restapi`, so your text domain should also be `custom-post-types-restapi`.

However, Plugin Check is reporting the expected text domain as `wp-cpt-rest-api`, which suggests WordPress.org may assign a different slug based on your submission.

**Recommendation**: Use `wp-cpt-rest-api` as the text domain to match what Plugin Check expects.

### Language Directory
The empty `languages/` directory with `.gitkeep` is not needed for WordPress.org hosted plugins. WordPress.org's translation system (GlotPress) automatically creates and manages translation files.

---

**Report Generated**: October 27, 2025
**Plugin Version**: 1.0.0
**WordPress Compatibility**: 6.0 - 6.8 (after updates)
**PHP Compatibility**: 7.4+
