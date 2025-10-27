<?php
/**
 * Plugin Name: Custom Post Types RestAPI
 * Plugin URI: https://github.com/JulienDelRio/wp-cpt-rest-api
 * Description: A robust WordPress plugin that extends the native REST API functionalities to provide comprehensive endpoints for Custom Post Types and their associated metadata.
 * Version: 1.0.0
 * Author: Julien DELRIO
 * Author URI: https://juliendelrio.fr
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.0
 * Tested up to: 6.8
 * Requires PHP: 7.4
 * Text Domain: wp-cpt-rest-api
 * Domain Path: /languages
 *
 * @package WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define plugin constants
define( 'WP_CPT_RESTAPI_VERSION', '1.0.0' );
define( 'WP_CPT_RESTAPI_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_CPT_RESTAPI_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WP_CPT_RESTAPI_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
function activate_wp_cpt_restapi() {
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
function deactivate_wp_cpt_restapi() {
    // Deactivation code here
}

register_activation_hook( __FILE__, 'activate_wp_cpt_restapi' );
register_deactivation_hook( __FILE__, 'deactivate_wp_cpt_restapi' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once WP_CPT_RESTAPI_PLUGIN_DIR . 'includes/class-wp-cpt-restapi.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 */
function run_wp_cpt_restapi() {
    // Initialize the plugin
    if ( class_exists( 'WP_CPT_RestAPI' ) ) {
        $plugin = new WP_CPT_RestAPI();
        $plugin->run();
    }
}

/**
 * Load plugin text domain for translations.
 *
 * Note: As of WordPress 4.6+, WordPress.org automatically loads translations
 * for plugins hosted in the directory. This function is maintained for backwards
 * compatibility and for installations from other sources (e.g., GitHub, manual installs).
 *
 * @since 0.1
 */
function wp_cpt_restapi_load_textdomain() {
	load_plugin_textdomain(
		'wp-cpt-rest-api',
		false,
		dirname( plugin_basename( __FILE__ ) ) . '/languages'
	);
}
add_action( 'plugins_loaded', 'wp_cpt_restapi_load_textdomain' );

// Start the plugin
add_action( 'plugins_loaded', 'run_wp_cpt_restapi' );