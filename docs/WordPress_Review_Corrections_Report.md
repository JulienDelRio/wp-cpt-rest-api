# WordPress Plugin Review - Corrections Report

**Plugin:** Custom Post Types RestAPI
**Version:** 1.1.0
**Review Date:** 2024-12-07
**Status:** Pending Corrections

---

## Summary

The WordPress Plugin Review Team has flagged three issues that must be addressed before approval:

| Issue | Severity | Status | Effort |
|-------|----------|--------|--------|
| Prefix naming collision | High (Red) | To Do | High |
| Text domain mismatch | Medium | To Do | Medium |
| Permission callback clarification | Low | To Verify | Low |

---

## Issue #1: Prefix Naming Collision (CRITICAL)

### Problem

Using `WP_CPT_` or `wp-cpt-` as prefix is flagged because "wp" is considered a common/reserved word that could conflict with WordPress core.

### WordPress Review Team Requirements

A prefix must be:
- At least **4 characters long**
- **Distinct and unique** to the plugin (no common words like "wp", "wordpress", "plugin")
- Separated by underscore or dash

### Recommended New Prefix

**Suggested prefix:** `cptapi_` / `CPTAPI_` / `cptapi-`

Alternative options:
- `custpoty_` (WordPress suggestion)
- `cptrest_`
- `jdrcpt_` (author initials + cpt)

### Files Requiring Changes

#### Constants (4 occurrences)
| File | Line | Current | New |
|------|------|---------|-----|
| `wp-cpt-rest-api.php` | 26 | `WP_CPT_RESTAPI_VERSION` | `CPTAPI_VERSION` |
| `wp-cpt-rest-api.php` | 27 | `WP_CPT_RESTAPI_PLUGIN_DIR` | `CPTAPI_PLUGIN_DIR` |
| `wp-cpt-rest-api.php` | 28 | `WP_CPT_RESTAPI_PLUGIN_URL` | `CPTAPI_PLUGIN_URL` |
| `wp-cpt-rest-api.php` | 29 | `WP_CPT_RESTAPI_PLUGIN_BASENAME` | `CPTAPI_PLUGIN_BASENAME` |
| `wp-cpt-rest-api.php` | 39 | `WP_CPT_RESTAPI_DEV_MODE` | `CPTAPI_DEV_MODE` |

#### Classes (5 classes)
| File | Line | Current | New |
|------|------|---------|-----|
| `includes/class-wp-cpt-restapi.php` | 20 | `WP_CPT_RestAPI` | `CPTAPI_Core` |
| `includes/class-wp-cpt-restapi-loader.php` | 21 | `WP_CPT_RestAPI_Loader` | `CPTAPI_Loader` |
| `includes/class-wp-cpt-restapi-api-keys.php` | 19 | `WP_CPT_RestAPI_API_Keys` | `CPTAPI_API_Keys` |
| `admin/class-wp-cpt-restapi-admin.php` | 20 | `WP_CPT_RestAPI_Admin` | `CPTAPI_Admin` |
| `rest-api/class-wp-cpt-restapi-rest.php` | 20 | `WP_CPT_RestAPI_REST` | `CPTAPI_REST` |
| `swagger/class-wp-cpt-restapi-openapi.php` | 19 | `WP_CPT_RestAPI_OpenAPI` | `CPTAPI_OpenAPI` |

#### Functions (3 functions)
| File | Line | Current | New |
|------|------|---------|-----|
| `wp-cpt-rest-api.php` | 45 | `activate_wp_cpt_restapi()` | `cptapi_activate()` |
| `wp-cpt-rest-api.php` | 77 | `deactivate_wp_cpt_restapi()` | `cptapi_deactivate()` |
| `wp-cpt-rest-api.php` | 97 | `run_wp_cpt_restapi()` | `cptapi_run()` |

#### Enqueued Assets (2 occurrences)
| File | Line | Current | New |
|------|------|---------|-----|
| `admin/class-wp-cpt-restapi-admin.php` | 124 | `wp_enqueue_style('wp-cpt-restapi-admin', ...)` | `wp_enqueue_style('cptapi-admin', ...)` |
| `admin/class-wp-cpt-restapi-admin.php` | 154 | `wp_enqueue_script('wp-cpt-restapi-admin', ...)` | `wp_enqueue_script('cptapi-admin', ...)` |

#### Localized Script Handle
| File | Line | Current | New |
|------|------|---------|-----|
| `admin/class-wp-cpt-restapi-admin.php` | ~160 | `wp_localize_script('wp-cpt-restapi-admin', ...)` | `wp_localize_script('cptapi-admin', ...)` |

### Files to Rename

| Current Filename | New Filename |
|-----------------|--------------|
| `class-wp-cpt-restapi.php` | `class-cptapi-core.php` |
| `class-wp-cpt-restapi-loader.php` | `class-cptapi-loader.php` |
| `class-wp-cpt-restapi-api-keys.php` | `class-cptapi-api-keys.php` |
| `class-wp-cpt-restapi-admin.php` | `class-cptapi-admin.php` |
| `class-wp-cpt-restapi-rest.php` | `class-cptapi-rest.php` |
| `class-wp-cpt-restapi-openapi.php` | `class-cptapi-openapi.php` |
| `wp-cpt-restapi-admin.css` | `cptapi-admin.css` |
| `wp-cpt-restapi-admin.js` | `cptapi-admin.js` |

### Options Naming

Current options use `cpt_rest_api_` prefix which is acceptable but could be updated for consistency:

| Current Option | New Option (Optional) |
|---------------|----------------------|
| `cpt_rest_api_base_segment` | Keep as-is or `cptapi_base_segment` |
| `cpt_rest_api_active_cpts` | Keep as-is or `cptapi_active_cpts` |
| `cpt_rest_api_keys` | Keep as-is or `cptapi_keys` |
| `cpt_rest_api_toolset_relationships` | Keep as-is or `cptapi_toolset_relationships` |
| `cpt_rest_api_include_nonpublic_cpts` | Keep as-is or `cptapi_include_nonpublic_cpts` |

**Note:** If options are renamed, migration code is required to preserve existing user data.

---

## Issue #2: Text Domain Mismatch

### Problem

The plugin uses text domain `wp-cpt-rest-api` but the WordPress.org plugin slug is `custom-post-types-restapi`.

### Solution

Change ALL text domain references from `wp-cpt-rest-api` to `custom-post-types-restapi`.

### Scope of Changes

| File | Occurrences |
|------|-------------|
| `admin/class-wp-cpt-restapi-admin.php` | 143 |
| `rest-api/class-wp-cpt-restapi-rest.php` | 20 |
| `includes/class-wp-cpt-restapi-api-keys.php` | 2 |
| `wp-cpt-rest-api.php` | 2 |
| `readme.txt` | 3 |
| `languages/*.pot` | 1 |
| `languages/*.po` | 1 |
| **Total** | **~175 occurrences** |

### Files Requiring Changes

1. **Main plugin file header:**
   ```php
   // Change in wp-cpt-rest-api.php line 14
   * Text Domain: custom-post-types-restapi
   ```

2. **All translation function calls:**
   ```php
   // Change all occurrences like:
   esc_html__( 'text', 'wp-cpt-rest-api' )
   // To:
   esc_html__( 'text', 'custom-post-types-restapi' )
   ```

3. **Language files:**
   - Rename `wp-cpt-rest-api.pot` to `custom-post-types-restapi.pot`
   - Rename `wp-cpt-rest-api-fr_FR.po` to `custom-post-types-restapi-fr_FR.po`
   - Rename any `.mo` files similarly
   - Update Project-Id-Version and domain references inside files

---

## Issue #3: Permission Callback (FALSE POSITIVE)

### Problem Reported

The review mentioned `permission_callback` issues for these routes:
```php
register_rest_route($base_segment . '/v1', '/', [...]);
register_rest_route($base_segment . '/v1', '/openapi', [...]);
```

### Analysis

**This appears to be a FALSE POSITIVE.** Both routes correctly use:
```php
'permission_callback' => '__return_true'
```

Using `__return_true` is the correct approach for intentionally public endpoints. The namespace info and OpenAPI spec endpoints are meant to be publicly accessible without authentication.

### Action Required

- **Option A:** No change needed - explain this in the reply email
- **Option B:** Add a comment explaining the intentional public access:
  ```php
  'permission_callback' => '__return_true' // Intentionally public endpoint
  ```

### Suggested Reply to Reviewer

> "The namespace info (`/`) and OpenAPI spec (`/openapi`) endpoints intentionally use `'permission_callback' => '__return_true'` as they are meant to be publicly accessible. All other endpoints require API key authentication via the `rest_authentication_errors` filter. This is by design for API discovery purposes."

---

## Implementation Plan

### Phase 1: Prefix Corrections (High Priority)

- [ ] **Task 1.1:** Choose and document final prefix (`cptapi_` recommended)
- [ ] **Task 1.2:** Update all constants in main plugin file
- [ ] **Task 1.3:** Rename and update class files
- [ ] **Task 1.4:** Update class references throughout codebase
- [ ] **Task 1.5:** Update function names
- [ ] **Task 1.6:** Update asset handles and file names
- [ ] **Task 1.7:** Update all constant references in all files
- [ ] **Task 1.8:** Test plugin activation/deactivation

### Phase 2: Text Domain Corrections (Medium Priority)

- [ ] **Task 2.1:** Update text domain in plugin header
- [ ] **Task 2.2:** Find/replace all text domain occurrences in PHP files
- [ ] **Task 2.3:** Rename language files
- [ ] **Task 2.4:** Regenerate POT file with new text domain
- [ ] **Task 2.5:** Update PO/MO files with new domain

### Phase 3: Testing & Verification

- [ ] **Task 3.1:** Test plugin activation on fresh WordPress install
- [ ] **Task 3.2:** Verify all admin pages function correctly
- [ ] **Task 3.3:** Test all REST API endpoints
- [ ] **Task 3.4:** Verify translations load correctly
- [ ] **Task 3.5:** Run WordPress Plugin Check (if available)
- [ ] **Task 3.6:** Test upgrade path from previous version

### Phase 4: Documentation & Submission

- [ ] **Task 4.1:** Update CLAUDE.md with new naming conventions
- [ ] **Task 4.2:** Update changelog in readme.txt
- [ ] **Task 4.3:** Update version number (1.1.1)
- [ ] **Task 4.4:** Create new distribution ZIP
- [ ] **Task 4.5:** Upload to WordPress.org
- [ ] **Task 4.6:** Reply to review email

---

## Migration Considerations

### Backward Compatibility

If changing option names:
1. Add migration code to check for old option names
2. Copy data from old to new option names
3. Delete old options after successful migration
4. Consider keeping old options readable for one version

### Example Migration Code

```php
function cptapi_migrate_options() {
    $old_to_new = array(
        'cpt_rest_api_base_segment' => 'cptapi_base_segment',
        'cpt_rest_api_active_cpts'  => 'cptapi_active_cpts',
        // ... etc
    );

    foreach ($old_to_new as $old_key => $new_key) {
        $old_value = get_option($old_key);
        if ($old_value !== false && get_option($new_key) === false) {
            update_option($new_key, $old_value);
            delete_option($old_key);
        }
    }
}
register_activation_hook(__FILE__, 'cptapi_migrate_options');
```

---

## Follow-Up Checklist

### Before Resubmission

- [ ] All prefix changes completed
- [ ] All text domain changes completed
- [ ] Plugin tested on WordPress 6.0+ and 6.8
- [ ] Plugin tested on PHP 7.4 and 8.x
- [ ] No PHP errors or warnings
- [ ] All REST endpoints functional
- [ ] Admin interface functional
- [ ] Translations loading correctly

### After Approval

- [ ] Announce release on GitHub
- [ ] Update any external documentation
- [ ] Monitor for user issues
- [ ] Plan next version features

---

## Reply Email Template

```
Hi,

Thank you for the review feedback. I have addressed all the flagged issues:

1. **Prefix Naming:** Changed all prefixes from `WP_CPT_*` / `wp-cpt-*` to
   `CPTAPI_*` / `cptapi-*` to avoid collision with WordPress core naming.

2. **Text Domain:** Updated text domain from `wp-cpt-rest-api` to
   `custom-post-types-restapi` to match the plugin slug. Updated all
   ~175 occurrences and renamed language files.

3. **Permission Callback:** The namespace info (/) and OpenAPI (/openapi)
   endpoints intentionally use `__return_true` as they are designed to be
   publicly accessible for API discovery. All other endpoints require API
   key authentication.

Updated plugin files have been uploaded. Thank you for your time.

Best regards,
Julien DELRIO
```

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0 | 2024-12-07 | Initial report created |

---

*Report generated for WordPress.org Plugin Review submission*
