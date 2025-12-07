# WordPress Plugin Review - Corrections Report

**Plugin:** Custom Post Types RestAPI
**Version:** 1.1.0
**Review Date:** 2024-12-07
**Status:** In Progress

---

## Summary

The WordPress Plugin Review Team has flagged three issues that must be addressed before approval:

| Issue | Severity | Status | Effort |
|-------|----------|--------|--------|
| Prefix naming collision | High (Red) | **COMPLETED** | High |
| Text domain mismatch | Medium | **COMPLETED** | Medium |
| Permission callback clarification | Low | **COMPLETED** | Low |

---

## Issue #1: Prefix Naming Collision (CRITICAL) - COMPLETED

### Problem

Using `WP_CPT_` or `wp-cpt-` as prefix is flagged because "wp" is considered a common/reserved word that could conflict with WordPress core.

### WordPress Review Team Requirements

A prefix must be:
- At least **4 characters long**
- **Distinct and unique** to the plugin (no common words like "wp", "wordpress", "plugin")
- Separated by underscore or dash

### Chosen Prefix

**Selected prefix:** `cptrest_` / `CPTREST_` / `cptrest-`

### Changes Applied

#### Constants (5 occurrences) - DONE
| File | Current | New |
|------|---------|-----|
| `wp-cpt-rest-api.php` | `WP_CPT_RESTAPI_VERSION` | `CPTREST_VERSION` |
| `wp-cpt-rest-api.php` | `WP_CPT_RESTAPI_PLUGIN_DIR` | `CPTREST_PLUGIN_DIR` |
| `wp-cpt-rest-api.php` | `WP_CPT_RESTAPI_PLUGIN_URL` | `CPTREST_PLUGIN_URL` |
| `wp-cpt-rest-api.php` | `WP_CPT_RESTAPI_PLUGIN_BASENAME` | `CPTREST_PLUGIN_BASENAME` |
| `wp-cpt-rest-api.php` | `WP_CPT_RESTAPI_DEV_MODE` | `CPTREST_DEV_MODE` |

#### Classes (6 classes) - DONE
| Old File | New File | Old Class | New Class |
|----------|----------|-----------|-----------|
| `class-wp-cpt-restapi.php` | `class-cptrest-core.php` | `WP_CPT_RestAPI` | `CPTREST_Core` |
| `class-wp-cpt-restapi-loader.php` | `class-cptrest-loader.php` | `WP_CPT_RestAPI_Loader` | `CPTREST_Loader` |
| `class-wp-cpt-restapi-api-keys.php` | `class-cptrest-api-keys.php` | `WP_CPT_RestAPI_API_Keys` | `CPTREST_API_Keys` |
| `class-wp-cpt-restapi-admin.php` | `class-cptrest-admin.php` | `WP_CPT_RestAPI_Admin` | `CPTREST_Admin` |
| `class-wp-cpt-restapi-rest.php` | `class-cptrest-rest.php` | `WP_CPT_RestAPI_REST` | `CPTREST_REST` |
| `class-wp-cpt-restapi-openapi.php` | `class-cptrest-openapi.php` | `WP_CPT_RestAPI_OpenAPI` | `CPTREST_OpenAPI` |

#### Functions (3 functions) - DONE
| File | Old | New |
|------|-----|-----|
| `wp-cpt-rest-api.php` | `activate_wp_cpt_restapi()` | `cptrest_activate()` |
| `wp-cpt-rest-api.php` | `deactivate_wp_cpt_restapi()` | `cptrest_deactivate()` |
| `wp-cpt-rest-api.php` | `run_wp_cpt_restapi()` | `cptrest_run()` |

#### Asset Files - DONE
| Old Filename | New Filename |
|--------------|--------------|
| `wp-cpt-restapi-admin.css` | `cptrest-admin.css` |
| `wp-cpt-restapi-admin.js` | `cptrest-admin.js` |

#### Asset Handles - DONE
| Context | Old Handle | New Handle |
|---------|------------|------------|
| CSS | `wp-cpt-restapi-admin` | `cptrest-admin` |
| JS | `wp-cpt-restapi-admin` | `cptrest-admin` |

#### Package Names - DONE
All `@package WP_CPT_RestAPI` changed to `@package CPTREST`

### Options Naming

Options retained with `cpt_rest_api_` prefix (acceptable to WordPress):
- `cpt_rest_api_base_segment`
- `cpt_rest_api_active_cpts`
- `cpt_rest_api_keys`
- `cpt_rest_api_toolset_relationships`
- `cpt_rest_api_include_nonpublic_cpts`

---

## Issue #2: Text Domain Mismatch - COMPLETED

### Problem

The plugin uses text domain `wp-cpt-rest-api` but the WordPress.org plugin slug is `custom-post-types-restapi`.

### Solution

Change ALL text domain references from `wp-cpt-rest-api` to `custom-post-types-restapi`.

### Changes Applied

| File | Changes |
|------|---------|
| `wp-cpt-rest-api.php` | Updated Text Domain header to `custom-post-types-restapi` |
| `admin/class-cptrest-admin.php` | All 143 text domain occurrences updated |
| `rest-api/class-cptrest-rest.php` | All 20 text domain occurrences updated |
| `includes/class-cptrest-api-keys.php` | All 2 text domain occurrences updated |
| `readme.txt` | Updated text domain reference in changelog |
| `languages/README.md` | Updated documentation with new text domain |
| `languages/*.pot` | Renamed from `wp-cpt-rest-api.pot` to `custom-post-types-restapi.pot` |
| `languages/*.po` | Renamed from `wp-cpt-rest-api-fr_FR.po` to `custom-post-types-restapi-fr_FR.po` |
| `languages/*.mo` | Renamed from `wp-cpt-rest-api-fr_FR.mo` to `custom-post-types-restapi-fr_FR.mo` |

---

## Issue #3: Permission Callback (FALSE POSITIVE) - COMPLETED

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

### Solution Applied (Option B)

Added explanatory comments to clarify the intentional public access:

```php
// Register the REST API namespace info endpoint
// Intentionally public: This endpoint provides API discovery information
// and does not expose sensitive data. Public access enables API consumers
// to discover available endpoints without authentication.
register_rest_route(
    $base_segment . '/v1',
    '/',
    array(
        'methods'  => 'GET',
        'callback' => array( $this, 'namespace_info' ),
        'permission_callback' => '__return_true', // Intentionally public for API discovery
    )
);

// Register the OpenAPI specification endpoint
// Intentionally public: The OpenAPI spec is documentation that helps
// developers understand and integrate with the API. Public access follows
// standard practice for API documentation endpoints.
register_rest_route(
    $base_segment . '/v1',
    '/openapi',
    array(
        'methods'  => 'GET',
        'callback' => array( $this, 'get_openapi_spec' ),
        'permission_callback' => '__return_true', // Intentionally public for API documentation
    )
);
```

### Suggested Reply to Reviewer

> "The namespace info (`/`) and OpenAPI spec (`/openapi`) endpoints intentionally use `'permission_callback' => '__return_true'` as they are meant to be publicly accessible. All other endpoints require API key authentication via the `rest_authentication_errors` filter. This is by design for API discovery purposes."

---

## Implementation Plan

### Phase 1: Prefix Corrections (High Priority) - COMPLETED

- [x] **Task 1.1:** Choose and document final prefix (`cptrest_` selected)
- [x] **Task 1.2:** Update all constants in main plugin file
- [x] **Task 1.3:** Rename and update class files
- [x] **Task 1.4:** Update class references throughout codebase
- [x] **Task 1.5:** Update function names
- [x] **Task 1.6:** Update asset handles and file names
- [x] **Task 1.7:** Update all constant references in all files
- [ ] **Task 1.8:** Test plugin activation/deactivation

### Phase 2: Text Domain Corrections (Medium Priority) - COMPLETED

- [x] **Task 2.1:** Update text domain in plugin header
- [x] **Task 2.2:** Find/replace all text domain occurrences in PHP files
- [x] **Task 2.3:** Rename language files
- [x] **Task 2.4:** Update POT file with new text domain
- [x] **Task 2.5:** Update PO/MO files with new domain

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

## Follow-Up Checklist

### Before Resubmission

- [x] All prefix changes completed
- [x] All text domain changes completed
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
   `CPTREST_*` / `cptrest-*` to avoid collision with WordPress core naming.

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
| 1.1 | 2024-12-07 | Issue #1 (Prefix) completed with `cptrest_` prefix |
| 1.2 | 2025-12-07 | Issue #2 (Text Domain) completed - changed to `custom-post-types-restapi` |
| 1.3 | 2025-12-07 | Issue #3 (Permission Callback) completed - added explanatory comments |

---

*Report generated for WordPress.org Plugin Review submission*
