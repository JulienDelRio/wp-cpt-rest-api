=== Custom Post Types RestAPI ===
Contributors: juliendelrio
Donate link: https://juliendelrio.fr
Tags: RestAPI, Rest, Custom Post Types
Requires at least: 6.0
Tested up to: 6.6
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A robust WordPress plugin that extends the native REST API functionalities to provide comprehensive endpoints for Custom Post Types and their associated metadata.

== Description ==

Custom Post Types RestAPI is a powerful plugin that enhances the WordPress REST API by providing comprehensive endpoints for Custom Post Types and their associated metadata.

This plugin allows developers to:

* Access Custom Post Types via the REST API with full CRUD operations
* Retrieve and manipulate custom fields and metadata
* Filter and search Custom Post Types with advanced query parameters
* Customize response formats for better integration with frontend applications

= Features =

* Full REST API support for all registered Custom Post Types
* Metadata integration for complete data access
* Customizable endpoints and response formats
* Comprehensive documentation and examples
* Developer-friendly with filters and hooks for extensibility

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wp-cpt-rest-api` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the plugin to access your Custom Post Types via the REST API

== Frequently Asked Questions ==

= How do I access my Custom Post Types via the REST API? =

After activating the plugin, your Custom Post Types will be available at `/wp-json/wp/v2/your-post-type`.

= Does this plugin work with custom fields? =

Yes, the plugin provides access to all associated metadata for your Custom Post Types.

== Changelog ==

= 1.0.0 =
* Official stable release - Production ready
* Packaging: Fixed ZIP structure to use forward slashes for cross-platform compatibility
* Documentation: Added support section with GitHub issues link
* All features from 1.0.0-RC1 included and verified

= 1.0.0-RC1 =
* Release Candidate 1 - Production-ready release with comprehensive improvements
* Security: Comprehensive security event logging for authentication and key operations
* Security: Enhanced input validation (100-character limit for API key labels)
* Security: Standardized error messages with consistent codes across all endpoints
* UX: Added admin notices for configuration guidance (dismissible, per-user)
* Documentation: Enhanced PHPDoc with precise return type specifications (array|false)
* Code Quality: Centralized error response system with professional error handling
* Code Quality: Client IP detection with proxy/load balancer support
* i18n: Complete internationalization infrastructure with languages directory
* All critical security issues resolved (SQL injection, XSS, nonce validation)
* All WordPress Coding Standards compliance issues resolved
* Exceeds WordPress.org plugin directory requirements
* 100% resolution of all applicable audit issues (21/21)

= 0.2 =
* Added API key authentication system for secure API access
* Added Toolset relationships support with full CRUD operations
* Added OpenAPI 3.0.3 specification endpoint for API documentation
* Added support for non-public CPTs selection in admin interface
* Enhanced meta field handling (both root-level and nested formats)
* Security improvements: SQL injection fixes, nonce sanitization, proper input validation
* Internationalization: Added text domain loading and proper i18n headers
* Added comprehensive uninstall handler for database cleanup
* Improved WordPress Coding Standards compliance

= 0.1 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Official stable release! Production-ready with all security improvements, professional error handling, security event logging, and enhanced user experience. Fixed packaging for proper WordPress installation. Recommended for all users.

= 1.0.0-RC1 =
First Release Candidate! Production-ready with comprehensive security improvements, professional error handling, security event logging, and enhanced user experience. All critical issues resolved. Recommended upgrade for all users preparing for WordPress.org submission.

= 0.2 =
Major update with API key authentication, Toolset relationships support, OpenAPI documentation, and critical security improvements. Recommended upgrade for all users.

= 0.1 =
Initial release of the plugin.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Screenshots are stored in the /assets/screenshots directory.

== Privacy Policy ==

This plugin does not collect any personal data.

== Support ==

Support is not guaranteed but issues can be opened on the GitHub repository:
https://github.com/JulienDelRio/wp-cpt-rest-api/issues

Please provide as much detail as possible when reporting issues, including:
* WordPress version
* PHP version
* Plugin version
* Steps to reproduce the issue
* Expected behavior vs actual behavior