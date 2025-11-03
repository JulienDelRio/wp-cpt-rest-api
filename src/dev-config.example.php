<?php
/**
 * Development Configuration Example
 *
 * Copy this file to 'dev-config.php' and modify as needed for local development.
 * The 'dev-config.php' file is ignored by git and will not be committed.
 *
 * @package WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Development mode flag
 *
 * Set to true during local development to enable automatic cache busting:
 * - CSS and JavaScript files will use file modification time as version
 * - Changes to assets will be immediately visible without clearing browser cache
 * - Useful when actively developing and testing
 *
 * Set to false for production environments:
 * - Assets will use plugin version number for versioning
 * - More stable and predictable for end users
 * - Complies with WordPress.org plugin directory standards
 *
 * Usage:
 * 1. Copy this file to 'dev-config.php' in the same directory
 * 2. Set WP_CPT_RESTAPI_DEV_MODE to true for development
 * 3. Set WP_CPT_RESTAPI_DEV_MODE to false (or delete the file) for production
 *
 * @var bool
 */
define( 'WP_CPT_RESTAPI_DEV_MODE', true );
