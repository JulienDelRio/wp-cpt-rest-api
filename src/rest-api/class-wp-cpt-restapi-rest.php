<?php
/**
 * The REST API-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The REST API-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the REST API functionality of the site.
 */
class WP_CPT_RestAPI_REST {

    /**
     * The option name for the REST API base segment.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $option_name    The option name for the REST API base segment.
     */
    private $option_name = 'cpt_rest_api_base_segment';

    /**
     * The default value for the REST API base segment.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $default_segment    The default value for the REST API base segment.
     */
    private $default_segment = 'cpt';

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Constructor code
    }

    /**
     * Register the REST API namespace.
     *
     * This function registers the REST API namespace based on the configured segment.
     * It does not create any endpoints, but prepares the namespace for future route declarations.
     *
     * @since    1.0.0
     */
    public function register_rest_namespace() {
        // Get the configured base segment or use the default
        $base_segment = get_option( $this->option_name, $this->default_segment );
        
        // Register the REST API namespace
        register_rest_route(
            $base_segment . '/v1',
            '/',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'namespace_info' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Return information about the namespace.
     *
     * This is a simple endpoint that returns information about the namespace.
     * It serves as a placeholder for the namespace and can be used to verify that the namespace is registered.
     *
     * @since    1.0.0
     * @return   array    Information about the namespace.
     */
    public function namespace_info() {
        return array(
            'namespace' => get_option( $this->option_name, $this->default_segment ) . '/v1',
            'description' => __( 'WordPress Custom Post Types REST API', 'wp-cpt-restapi' ),
            'version' => WP_CPT_RESTAPI_VERSION,
        );
    }
}