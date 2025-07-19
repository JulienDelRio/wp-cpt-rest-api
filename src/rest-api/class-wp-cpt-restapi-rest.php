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
     * The option name for the active CPTs.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $cpt_option_name    The option name for the active CPTs.
     */
    private $cpt_option_name = 'cpt_rest_api_active_cpts';

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
     * Register the REST API namespace and CPT endpoints.
     *
     * This function registers the REST API namespace and creates endpoints for active CPTs.
     *
     * @since    1.0.0
     */
    public function register_rest_namespace() {
        // Get the configured base segment or use the default
        $base_segment = get_option( $this->option_name, $this->default_segment );
        
        // Register the REST API namespace info endpoint
        register_rest_route(
            $base_segment . '/v1',
            '/',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'namespace_info' ),
                'permission_callback' => '__return_true',
            )
        );
        
        // Register endpoints for active CPTs
        $this->register_cpt_endpoints();
    }

    /**
     * Register REST API endpoints for active CPTs.
     *
     * @since    1.0.0
     */
    private function register_cpt_endpoints() {
        // Get the configured base segment
        $base_segment = get_option( $this->option_name, $this->default_segment );
        
        // Get active CPTs
        $active_cpts = $this->get_active_cpts();
        
        foreach ( $active_cpts as $cpt_name ) {
            // Register endpoint for listing CPT posts
            register_rest_route(
                $base_segment . '/v1',
                '/' . $cpt_name,
                array(
                    'methods'  => 'GET',
                    'callback' => array( $this, 'get_cpt_posts' ),
                    'args'     => array(
                        'cpt' => array(
                            'default' => $cpt_name,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'per_page' => array(
                            'default' => 10,
                            'sanitize_callback' => 'absint',
                        ),
                        'page' => array(
                            'default' => 1,
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                    'permission_callback' => '__return_true',
                )
            );
            
            // Register endpoint for getting a specific CPT post
            register_rest_route(
                $base_segment . '/v1',
                '/' . $cpt_name . '/(?P<id>\d+)',
                array(
                    'methods'  => 'GET',
                    'callback' => array( $this, 'get_cpt_post' ),
                    'args'     => array(
                        'cpt' => array(
                            'default' => $cpt_name,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'id' => array(
                            'required' => true,
                            'sanitize_callback' => 'absint',
                        ),
                    ),
                    'permission_callback' => '__return_true',
                )
            );
        }
    }

    /**
     * Get active CPTs from the admin settings.
     *
     * @since    1.0.0
     * @return   array    Array of active CPT names.
     */
    private function get_active_cpts() {
        $active_cpts = get_option( $this->cpt_option_name, array() );
        
        // Ensure it's an array
        if ( ! is_array( $active_cpts ) ) {
            return array();
        }
        
        // Validate that the CPTs still exist
        $available_cpts = get_post_types( array( 'public' => true ), 'names' );
        $core_types = array( 'post', 'page', 'attachment' );
        
        // Remove core types from available CPTs
        foreach ( $core_types as $core_type ) {
            unset( $available_cpts[ $core_type ] );
        }
        
        // Only return CPTs that are both active and still available
        return array_intersect( $active_cpts, array_keys( $available_cpts ) );
    }

    /**
     * Get posts for a specific CPT.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function get_cpt_posts( $request ) {
        $cpt = $request->get_param( 'cpt' );
        $per_page = $request->get_param( 'per_page' );
        $page = $request->get_param( 'page' );
        
        // Validate CPT is active
        $active_cpts = $this->get_active_cpts();
        if ( ! in_array( $cpt, $active_cpts, true ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'This Custom Post Type is not available via the API.', 'wp-cpt-restapi' ),
                array( 'status' => 403 )
            );
        }
        
        // Query posts
        $args = array(
            'post_type'      => $cpt,
            'post_status'    => 'publish',
            'posts_per_page' => min( $per_page, 100 ), // Limit to 100 posts per page
            'paged'          => $page,
            'orderby'        => 'date',
            'order'          => 'DESC',
        );
        
        $query = new WP_Query( $args );
        
        $posts = array();
        foreach ( $query->posts as $post ) {
            $posts[] = $this->prepare_post_data( $post );
        }
        
        $response = array(
            'posts' => $posts,
            'pagination' => array(
                'total' => $query->found_posts,
                'pages' => $query->max_num_pages,
                'current_page' => $page,
                'per_page' => $per_page,
            ),
        );
        
        return rest_ensure_response( $response );
    }

    /**
     * Get a specific post from a CPT.
     *
     * @since    1.0.0
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function get_cpt_post( $request ) {
        $cpt = $request->get_param( 'cpt' );
        $id = $request->get_param( 'id' );
        
        // Validate CPT is active
        $active_cpts = $this->get_active_cpts();
        if ( ! in_array( $cpt, $active_cpts, true ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'This Custom Post Type is not available via the API.', 'wp-cpt-restapi' ),
                array( 'status' => 403 )
            );
        }
        
        // Get the post
        $post = get_post( $id );
        
        if ( ! $post || $post->post_type !== $cpt || $post->post_status !== 'publish' ) {
            return new WP_Error(
                'rest_post_invalid_id',
                __( 'Invalid post ID.', 'wp-cpt-restapi' ),
                array( 'status' => 404 )
            );
        }
        
        return rest_ensure_response( $this->prepare_post_data( $post ) );
    }

    /**
     * Prepare post data for API response.
     *
     * @since    1.0.0
     * @param    WP_Post    $post    The post object.
     * @return   array               The prepared post data.
     */
    private function prepare_post_data( $post ) {
        $data = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'excerpt' => $post->post_excerpt,
            'slug' => $post->post_name,
            'status' => $post->post_status,
            'type' => $post->post_type,
            'date' => $post->post_date,
            'modified' => $post->post_modified,
            'author' => $post->post_author,
            'featured_media' => get_post_thumbnail_id( $post->ID ),
        );
        
        // Add custom fields (meta)
        $meta = get_post_meta( $post->ID );
        $data['meta'] = array();
        
        foreach ( $meta as $key => $value ) {
            // Skip private meta fields (starting with _)
            if ( strpos( $key, '_' ) !== 0 ) {
                $data['meta'][ $key ] = is_array( $value ) && count( $value ) === 1 ? $value[0] : $value;
            }
        }
        
        return $data;
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