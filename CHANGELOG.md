# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2025-11-04

### Added

- Complete French translation support (fr_FR) with 95%+ coverage.
- Comprehensive i18n workflow for POT/PO/MO generation and compilation.
- Development mode configuration system via `dev-config.php` for local development.
- Configurable asset versioning: filemtime-based for development, plugin version for production.
- Comprehensive development mode documentation.
- `dev-config.example.php` template for local development settings.
- Two-step API key migration UX with review process and existing keys table.
- Migration notice now links to settings page for review before migration.

### Changed

- French translation file (fr_FR.po) updated with 60+ missing UI strings.
- Packaging scripts now automatically extract version from `readme.txt`.
- Improved `CLAUDE.md` with detailed API key storage security information.
- Updated `.gitignore` to exclude `dev-config.php` from version control.

### Fixed

- JavaScript hardcoded strings now properly localized (3 strings in CPT reset functionality).
- Removed automatic page reload after API key creation to allow adequate time for copying keys.
- Regenerated POT file with all 143 translatable strings to fix translation workflow.

### Security

- API keys now stored as bcrypt hashes using WordPress `wp_hash_password()`.
- Keys only visible once upon creation and cannot be recovered later.
- Automatic migration system for upgrading plaintext keys to hashed storage.
- Enhanced security notices and warnings for key management.
- Only first 4 characters (key prefix) displayed in admin for identification.

## [1.0.0] - 2025-10-26

Initial production-ready release.

### Added

- Full REST API support for Custom Post Types with CRUD operations (GET, POST, PUT/PATCH, DELETE).
- API key authentication system with Bearer token support.
- OpenAPI 3.0.3 specification with auto-generated documentation at `/openapi` endpoint (publicly accessible).
- Toolset relationships support with dedicated endpoints for relationship management.
- Comprehensive WordPress admin interface at **Settings > CPT REST API**.
- Security event logging for authentication failures and key operations.
- Complete internationalization (i18n) infrastructure with text domain support.
- Cross-platform packaging scripts (PowerShell for Windows, Bash for Linux/Mac).
- Configurable namespace `/{base_segment}/v1/` for REST API endpoints.
- CPT selection including non-public CPTs with granular visibility options.
- API key management interface (generation, labeling, revocation).
- Configurable API base segment via admin settings.
- Toolset relationships toggle for enabling/disabling relationship endpoints.
- Dismissible admin notices for configuration guidance (per-user).
- Meta field support with both nested `meta` object and root-level fields.
- Private meta fields (prefixed with `_`) automatically filtered out.
- Pagination support for list endpoints.
- Advanced query parameters for filtering.
- Nonce validation for all AJAX operations.
- SQL injection prevention with prepared statements.
- XSS prevention with proper output escaping.
- Rate limiting on API key generation (10 keys per hour per user).
- Constant-time API key comparison using `hash_equals()`.
- Activation hook that initializes plugin options with defaults.
- Deactivation hook with cleanup placeholder for future use.
- Uninstall handler for complete database cleanup (options, user meta).
- Comprehensive API documentation in `API_ENDPOINTS.md` with cURL and JavaScript examples.
- OpenAPI specification documentation in `OPENAPI.md`.
- Security policy and vulnerability reporting guidelines in `SECURITY.md`.
- Developer guide in `CLAUDE.md` for working with the codebase.
- Complete project specifications in `docs/SPECS.md`.
- WordPress.org compatible plugin readme in `readme.txt`.
- Packaging and distribution guide in `PACKAGING.md`.

### WordPress Integration

WordPress options added:
- `cpt_rest_api_base_segment` - API base URL segment (default: "cpt")
- `cpt_rest_api_active_cpts` - Array of enabled Custom Post Types
- `cpt_rest_api_keys` - API keys storage
- `cpt_rest_api_toolset_relationships` - Toolset support toggle
- `cpt_rest_api_include_nonpublic_cpts` - Non-public CPT visibility options

Core classes:
- `WP_CPT_RestAPI` - Main plugin orchestrator
- `WP_CPT_RestAPI_Admin` - Admin interface handler
- `WP_CPT_RestAPI_Rest` - REST endpoint implementation
- `WP_CPT_RestAPI_API_Keys` - API key management
- `WP_CPT_RestAPI_OpenAPI` - OpenAPI specification generator
- `WP_CPT_RestAPI_Loader` - WordPress hooks loader

### Requirements

- WordPress 6.0 or higher
- PHP 7.4 or higher
- Tested up to WordPress 6.8

[unreleased]: https://github.com/JulienDelRio/wp-cpt-rest-api/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/JulienDelRio/wp-cpt-rest-api/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/JulienDelRio/wp-cpt-rest-api/releases/tag/v1.0.0
