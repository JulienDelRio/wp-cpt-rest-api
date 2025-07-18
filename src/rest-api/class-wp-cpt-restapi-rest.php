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
     * The API Keys manager instance.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_CPT_RestAPI_API_Keys    $api_keys    Handles API key management.
     */
    private $api_keys;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     */
    public function __construct() {
        // Initialize API Keys manager
        $this->api_keys = new WP_CPT_RestAPI_API_Keys();
        
        // Add filter for REST API authentication
        add_filter( 'rest_authentication_errors', array( $this, 'authenticate_api_key' ), 10, 1 );
    }
    
    /**
     * Authenticate API key for REST API requests.
     *
     * This function checks for a valid API key in the Authorization header
     * and allows or denies access to the REST API endpoints accordingly.
     *
     * @since    1.0.0
     * @param    WP_Error|null|bool    $result    The current authentication status.
     * @return   WP_Error|null|bool               The updated authentication status.
     */
    public function authenticate_api_key( $result ) {
        // If authentication has already been performed, return the result
        if ( $result !== null ) {
            return $result;
        }
        
        // Check if this is a request to our namespace
        $base_segment = get_option( $this->option_name, $this->default_segment );
        $current_route = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        
        // Only apply authentication to our namespace
        if ( strpos( $current_route, '/wp-json/' . $base_segment . '/v1/' ) === false ) {
            return $result;
        }
        
        // Get the Authorization header
        $auth_header = isset( $_SERVER['HTTP_AUTHORIZATION'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ) ) : '';
        
        // Check if the Authorization header is present and starts with 'Bearer '
        if ( empty( $auth_header ) || strpos( $auth_header, 'Bearer ' ) !== 0 ) {
            return new WP_Error(
                'rest_not_logged_in',
                __( 'You are not logged in and no valid API key was provided.', 'wp-cpt-restapi' ),
                array( 'status' => 401 )
            );
        }
        
        // Extract the token from the Authorization header
        $token = trim( substr( $auth_header, 7 ) );
        
        // Validate the token
        if ( ! $this->api_keys->validate_key( $token ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Invalid API key.', 'wp-cpt-restapi' ),
                array( 'status' => 403 )
            );
        }
        
        // If we get here, the API key is valid
        return true;
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