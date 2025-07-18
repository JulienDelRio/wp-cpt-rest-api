<?php
/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks,
 * and REST API functionality.
 *
 * @since      1.0.0
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The core plugin class.
 */
class WP_CPT_RestAPI {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WP_CPT_RestAPI_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The API Keys manager instance.
     *
     * @since    1.0.0
     * @access   protected
     * @var      WP_CPT_RestAPI_API_Keys    $api_keys    Handles API key management.
     */
    protected $api_keys;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the REST API functionality.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_rest_api_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - WP_CPT_RestAPI_Loader. Orchestrates the hooks of the plugin.
     * - WP_CPT_RestAPI_Admin. Defines all hooks for the admin area.
     * - WP_CPT_RestAPI_REST. Defines all hooks for the REST API functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once WP_CPT_RESTAPI_PLUGIN_DIR . 'includes/class-wp-cpt-restapi-loader.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once WP_CPT_RESTAPI_PLUGIN_DIR . 'admin/class-wp-cpt-restapi-admin.php';

        /**
         * The class responsible for defining all REST API functionality.
         */
        require_once WP_CPT_RESTAPI_PLUGIN_DIR . 'rest-api/class-wp-cpt-restapi-rest.php';

        /**
         * The class responsible for API key management.
         */
        require_once WP_CPT_RESTAPI_PLUGIN_DIR . 'includes/class-wp-cpt-restapi-api-keys.php';

        $this->loader = new WP_CPT_RestAPI_Loader();
        $this->api_keys = new WP_CPT_RestAPI_API_Keys();
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new WP_CPT_RestAPI_Admin();

        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_settings_page' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
    }

    /**
     * Register all of the hooks related to the REST API functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_rest_api_hooks() {
        $plugin_rest = new WP_CPT_RestAPI_REST();

        $this->loader->add_action( 'rest_api_init', $plugin_rest, 'register_rest_namespace' );
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}