<?php
/**
 * Plugin Name: Custom Post Types RestAPI
 * Plugin URI: https://github.com/JulienDelRio/wp-cpt-rest-api
 * Description: A robust WordPress plugin that extends the native REST API functionalities to provide comprehensive endpoints for Custom Post Types and their associated metadata.
 * Version: 1.1.1
 * Author: Julien DELRIO
 * Author URI: https://juliendelrio.fr
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 * Text Domain: custom-post-types-restapi
 * Domain Path: /languages
 *
 * @package CPTREST
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'CPTREST_VERSION', '1.1.1' );
define( 'CPTREST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CPTREST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'CPTREST_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load development configuration if it exists (not tracked in version control)
$dev_config_file = CPTREST_PLUGIN_DIR . 'dev-config.php';
if ( file_exists( $dev_config_file ) ) {
    require_once $dev_config_file;
}

// Set default development mode if not defined
if ( ! defined( 'CPTREST_DEV_MODE' ) ) {
    define( 'CPTREST_DEV_MODE', false );
}

/**
 * The code that runs during plugin activation.
 */
function cptrest_activate() {
    // Activation code here
    
    // Initialize API Keys option
    if ( ! get_option( 'cpt_rest_api_keys' ) ) {
        add_option( 'cpt_rest_api_keys', array() );
    }
    
    // Initialize Active CPTs option
    if ( ! get_option( 'cpt_rest_api_active_cpts' ) ) {
        add_option( 'cpt_rest_api_active_cpts', array() );
    }
    
    // Initialize Toolset relationships option (default to disabled)
    if ( ! get_option( 'cpt_rest_api_toolset_relationships' ) ) {
        add_option( 'cpt_rest_api_toolset_relationships', false );
    }

    // Initialize base segment option
    if ( ! get_option( 'cpt_rest_api_base_segment' ) ) {
        add_option( 'cpt_rest_api_base_segment', 'cpt' );
    }

    // Initialize include non-public CPTs option
    if ( ! get_option( 'cpt_rest_api_include_nonpublic_cpts' ) ) {
        add_option( 'cpt_rest_api_include_nonpublic_cpts', array() );
    }
}

/**
 * The code that runs during plugin deactivation.
 */
function cptrest_deactivate() {
    // Deactivation code here
}

register_activation_hook( __FILE__, 'cptrest_activate' );
register_deactivation_hook( __FILE__, 'cptrest_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once CPTREST_PLUGIN_DIR . 'includes/class-cptrest-core.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function cptrest_run() {
    // Initialize the plugin
    if ( class_exists( 'CPTREST_Core' ) ) {
        $plugin = new CPTREST_Core();
        $plugin->run();
    }
}

/**
 * Note: As of WordPress 4.6+, WordPress.org automatically loads translations
 * for plugins hosted in the directory. No manual textdomain loading is required
 * for WordPress.org hosted plugins.
 *
 * Translation files are located in the /languages/ directory and are automatically
 * loaded by WordPress when the plugin is activated from the WordPress.org repository.
 *
 * @since 0.1
 * @since 1.1.1 Removed load_plugin_textdomain() call for WordPress.org compliance
 */

// Start the plugin
add_action( 'plugins_loaded', 'cptrest_run' );