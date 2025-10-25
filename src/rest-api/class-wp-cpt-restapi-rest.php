<?php
/**
 * The REST API-specific functionality of the plugin.
 *
 * @since      0.1
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
     * @since    0.1
     * @access   private
     * @var      string    $option_name    The option name for the REST API base segment.
     */
    private $option_name = 'cpt_rest_api_base_segment';

    /**
     * The option name for the active CPTs.
     *
     * @since    0.1
     * @access   private
     * @var      string    $cpt_option_name    The option name for the active CPTs.
     */
    private $cpt_option_name = 'cpt_rest_api_active_cpts';

    /**
     * The default value for the REST API base segment.
     *
     * @since    0.1
     * @access   private
     * @var      string    $default_segment    The default value for the REST API base segment.
     */
    private $default_segment = 'cpt';
    
    /**
     * The API Keys manager instance.
     *
     * @since    0.1
     * @access   private
     * @var      WP_CPT_RestAPI_API_Keys    $api_keys    Handles API key management.
     */
    private $api_keys;

    /**
     * The option name for Toolset relationship support.
     *
     * @since    0.1
     * @access   private
     * @var      string    $toolset_option_name    The option name for Toolset relationship support.
     */
    private $toolset_option_name = 'cpt_rest_api_toolset_relationships';

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.1
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
     * @since    0.1
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
        // Use esc_url_raw for REQUEST_URI as it's a URL
        $current_route = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // Only apply authentication to our namespace
        if ( strpos( $current_route, '/wp-json/' . $base_segment . '/v1/' ) === false ) {
            return $result;
        }

        // Allow public access to the root endpoint and OpenAPI specification endpoint
        if ( strpos( $current_route, '/wp-json/' . $base_segment . '/v1/openapi' ) !== false ||
             preg_match( '#/wp-json/' . preg_quote( $base_segment, '#' ) . '/v1/?$#', $current_route ) ) {
            return $result;
        }

        // Get the Authorization header - preserve Bearer token format
        $auth_header = '';
        if ( isset( $_SERVER['HTTP_AUTHORIZATION'] ) ) {
            $auth_header = wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] );
        } elseif ( isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
            // Some server configs use REDIRECT_HTTP_AUTHORIZATION
            $auth_header = wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
        }
        
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
     * Check if request has read access to CPT posts.
     *
     * AUTHORIZATION MODEL:
     * This plugin uses a binary API key authentication model where valid API keys
     * grant full access to all enabled Custom Post Types. There are no granular
     * permissions per key (e.g., read-only keys are not supported).
     *
     * - API keys are validated at the rest_authentication_errors filter level
     * - Valid API key = full access to ALL operations (GET, POST, PUT, PATCH, DELETE)
     * - Valid API key = access to ALL enabled CPTs (configured in Settings > CPT REST API)
     * - This intentional design supports external API integration use cases
     * - For security: generate separate keys per service, revoke if compromised
     *
     * @since    0.2
     * @param    WP_REST_Request    $request    Full details about the request.
     * @return   bool|WP_Error                  True if the request has read access, WP_Error object otherwise.
     */
    public function get_items_permissions_check( $request ) {
        // API key already validated in authenticate_api_key()
        // Valid key provides full access by design (see authorization model above)
        return true;
    }

    /**
     * Check if request has access to create CPT posts.
     *
     * AUTHORIZATION MODEL:
     * Valid API keys grant full access to create posts in all enabled CPTs.
     * See get_items_permissions_check() for complete authorization model documentation.
     *
     * @since    0.2
     * @param    WP_REST_Request    $request    Full details about the request.
     * @return   bool|WP_Error                  True if the request has access to create, WP_Error object otherwise.
     */
    public function create_item_permissions_check( $request ) {
        // API key already validated in authenticate_api_key()
        // Valid key provides full access by design (see authorization model in get_items_permissions_check)
        return true;
    }

    /**
     * Check if request has access to update a CPT post.
     *
     * AUTHORIZATION MODEL:
     * Valid API keys grant full access to update posts in all enabled CPTs.
     * See get_items_permissions_check() for complete authorization model documentation.
     *
     * @since    0.2
     * @param    WP_REST_Request    $request    Full details about the request.
     * @return   bool|WP_Error                  True if the request has access to update, WP_Error object otherwise.
     */
    public function update_item_permissions_check( $request ) {
        $post = get_post( $request['id'] );
        if ( ! $post ) {
            return new WP_Error(
                'rest_post_invalid_id',
                __( 'Invalid post ID.', 'wp-cpt-restapi' ),
                array( 'status' => 404 )
            );
        }
        // API key already validated in authenticate_api_key()
        // Valid key provides full access by design (see authorization model in get_items_permissions_check)
        return true;
    }

    /**
     * Check if request has access to delete a CPT post.
     *
     * AUTHORIZATION MODEL:
     * Valid API keys grant full access to delete posts in all enabled CPTs.
     * See get_items_permissions_check() for complete authorization model documentation.
     *
     * @since    0.2
     * @param    WP_REST_Request    $request    Full details about the request.
     * @return   bool|WP_Error                  True if the request has access to delete, WP_Error object otherwise.
     */
    public function delete_item_permissions_check( $request ) {
        $post = get_post( $request['id'] );
        if ( ! $post ) {
            return new WP_Error(
                'rest_post_invalid_id',
                __( 'Invalid post ID.', 'wp-cpt-restapi' ),
                array( 'status' => 404 )
            );
        }
        // API key already validated in authenticate_api_key()
        // Valid key provides full access by design (see authorization model in get_items_permissions_check)
        return true;
    }

    /**
     * Register the REST API namespace and CPT endpoints.
     *
     * This function registers the REST API namespace and creates endpoints for active CPTs.
     *
     * @since    0.1
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
        
        // Register the OpenAPI specification endpoint
        register_rest_route(
            $base_segment . '/v1',
            '/openapi',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_openapi_spec' ),
                'permission_callback' => '__return_true',
            )
        );
        
        // Register endpoints for ALL available CPTs, but validate access dynamically
        $this->register_all_cpt_endpoints();
        
        // Register Toolset relationships endpoint if enabled
        if ( $this->is_toolset_relationships_enabled() ) {
            $this->register_toolset_relationships_endpoints();
        }
    }

    /**
     * Register REST API endpoints for ALL available CPTs with dynamic validation.
     *
     * This method registers endpoints for all available CPTs, but the actual access
     * is validated dynamically in each callback method based on current settings.
     *
     * @since    0.1
     */
    private function register_all_cpt_endpoints() {
        // Get the configured base segment
        $base_segment = get_option( $this->option_name, $this->default_segment );
        
        // Get only CPTs that are enabled in admin panel
        $active_cpts = $this->get_active_cpts();
        
        foreach ( $active_cpts as $cpt_name ) {
            // Register endpoint for listing CPT posts (GET) and creating new posts (POST)
            register_rest_route(
                $base_segment . '/v1',
                '/' . $cpt_name,
                array(
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
                        'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    ),
                    array(
                        'methods'  => 'POST',
                        'callback' => array( $this, 'create_cpt_post' ),
                        'args'     => array(
                            'cpt' => array(
                                'default' => $cpt_name,
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'title' => array(
                                'required' => false,
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'content' => array(
                                'required' => false,
                                'sanitize_callback' => 'wp_kses_post',
                            ),
                            'excerpt' => array(
                                'required' => false,
                                'sanitize_callback' => 'sanitize_textarea_field',
                            ),
                            'status' => array(
                                'required' => false,
                                'default' => 'publish',
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'meta' => array(
                                'required' => false,
                                'validate_callback' => array( $this, 'validate_meta_field' ),
                            ),
                        ),
                        'permission_callback' => array( $this, 'create_item_permissions_check' ),
                    ),
                )
            );
            
            // Register endpoint for getting and updating a specific CPT post
            register_rest_route(
                $base_segment . '/v1',
                '/' . $cpt_name . '/(?P<id>\d+)',
                array(
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
                        'permission_callback' => array( $this, 'get_items_permissions_check' ),
                    ),
                    array(
                        'methods'  => array( 'PUT', 'PATCH' ),
                        'callback' => array( $this, 'update_cpt_post' ),
                        'args'     => array(
                            'cpt' => array(
                                'default' => $cpt_name,
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'id' => array(
                                'required' => true,
                                'sanitize_callback' => 'absint',
                            ),
                            'title' => array(
                                'required' => false,
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'content' => array(
                                'required' => false,
                                'sanitize_callback' => 'wp_kses_post',
                            ),
                            'excerpt' => array(
                                'required' => false,
                                'sanitize_callback' => 'sanitize_textarea_field',
                            ),
                            'status' => array(
                                'required' => false,
                                'sanitize_callback' => 'sanitize_text_field',
                            ),
                            'meta' => array(
                                'required' => false,
                                'validate_callback' => array( $this, 'validate_meta_field' ),
                            ),
                        ),
                        'permission_callback' => array( $this, 'update_item_permissions_check' ),
                    ),
                    array(
                        'methods'  => 'DELETE',
                        'callback' => array( $this, 'delete_cpt_post' ),
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
                        'permission_callback' => array( $this, 'delete_item_permissions_check' ),
                    ),
                )
            );
        }
    }

    /**
     * Register REST API endpoints for Toolset relationships.
     *
     * This method registers endpoints for Toolset relationship functionality
     * when the Toolset relationships support is enabled.
     *
     * @since    0.1
     */
    private function register_toolset_relationships_endpoints() {
        // Get the configured base segment
        $base_segment = get_option( $this->option_name, $this->default_segment );
        
        // Register endpoint for listing all Toolset relationships
        register_rest_route(
            $base_segment . '/v1',
            '/relations',
            array(
                'methods'  => 'GET',
                'callback' => array( $this, 'get_toolset_relationships' ),
                'permission_callback' => array( $this, 'get_items_permissions_check' ),
            )
        );
        
        // Register endpoint for managing specific relationship by slug
        register_rest_route(
            $base_segment . '/v1',
            '/relations/(?P<relation_slug>[a-zA-Z0-9_-]+)',
            array(
                array(
                    'methods'  => 'GET',
                    'callback' => array( $this, 'get_toolset_relationship_instances' ),
                    'args'     => array(
                        'relation_slug' => array(
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => array( $this, 'validate_relation_slug' ),
                        ),
                    ),
                    'permission_callback' => array( $this, 'get_items_permissions_check' ),
                ),
                array(
                    'methods'  => 'POST',
                    'callback' => array( $this, 'create_toolset_relationship_instance' ),
                    'args'     => array(
                        'relation_slug' => array(
                            'required' => true,
                            'sanitize_callback' => 'sanitize_text_field',
                            'validate_callback' => array( $this, 'validate_relation_slug' ),
                        ),
                        'parent_id' => array(
                            'required' => true,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                        'child_id' => array(
                            'required' => true,
                            'sanitize_callback' => 'absint',
                            'validate_callback' => array( $this, 'validate_post_id' ),
                        ),
                    ),
                    'permission_callback' => array( $this, 'create_item_permissions_check' ),
                ),
            )
        );
        
        // Register endpoint for deleting specific relationship instance
        register_rest_route(
            $base_segment . '/v1',
            '/relations/(?P<relation_slug>[a-zA-Z0-9_-]+)/(?P<relationship_id>[a-zA-Z0-9_-]+)',
            array(
                'methods'  => 'DELETE',
                'callback' => array( $this, 'delete_toolset_relationship_instance' ),
                'args'     => array(
                    'relation_slug' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                        'validate_callback' => array( $this, 'validate_relation_slug' ),
                    ),
                    'relationship_id' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                ),
                'permission_callback' => array( $this, 'delete_item_permissions_check' ),
            )
        );
    }

    /**
     * Get active CPTs from the admin settings.
     *
     * @since    0.1
     * @return   array    Array of active CPT names.
     */
    private function get_active_cpts() {
        $active_cpts = get_option( $this->cpt_option_name, array() );
        
        // Ensure it's an array
        if ( ! is_array( $active_cpts ) ) {
            return array();
        }
        
        // Validate that the CPTs still exist using the same logic as get_all_available_cpts()
        $all_available_cpts = $this->get_all_available_cpts();
        
        // Only return CPTs that are both active and still available
        return array_intersect( $active_cpts, $all_available_cpts );
    }

    /**
     * Get all available CPTs (excluding core types).
     *
     * This method returns all available CPTs regardless of admin settings,
     * used for registering endpoints that will be validated dynamically.
     *
     * @since    0.1
     * @return   array    Array of all available CPT names.
     */
    private function get_all_available_cpts() {
        // Get all registered post types (not just public ones)
        // This ensures we include CPTs that might not be public but should be available via API
        $all_post_types = get_post_types( array(), 'names' );
        $core_types = array( 'post', 'page', 'attachment' );
        $available_cpts = array();
        
        foreach ( $all_post_types as $post_type ) {
            // Skip core post types
            if ( in_array( $post_type, $core_types, true ) ) {
                continue;
            }
            
            // Get post type object to check its properties
            $post_type_obj = get_post_type_object( $post_type );
            
            // Include CPTs that are either:
            // 1. Public, OR
            // 2. Publicly queryable, OR
            // 3. Show in admin UI
            if ( $post_type_obj && (
                $post_type_obj->public ||
                $post_type_obj->publicly_queryable ||
                $post_type_obj->show_ui
            ) ) {
                $available_cpts[] = $post_type;
            }
        }
        
        return $available_cpts;
    }

    /**
     * Get posts for a specific CPT.
     *
     * @since    0.1
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
     * @since    0.1
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
     * Create a new post for a specific CPT.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function create_cpt_post( $request ) {
        $cpt = $request->get_param( 'cpt' );
        
        // Validate CPT is active
        $active_cpts = $this->get_active_cpts();
        if ( ! in_array( $cpt, $active_cpts, true ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'This Custom Post Type is not available via the API.', 'wp-cpt-restapi' ),
                array( 'status' => 403 )
            );
        }
        
        // Prepare post data
        $post_data = array(
            'post_type' => $cpt,
            'post_status' => $this->sanitize_post_status( $request->get_param( 'status' ) ),
        );
        
        // Add optional fields if provided
        if ( $request->get_param( 'title' ) ) {
            $post_data['post_title'] = $request->get_param( 'title' );
        }
        
        if ( $request->get_param( 'content' ) ) {
            $post_data['post_content'] = $request->get_param( 'content' );
        }
        
        if ( $request->get_param( 'excerpt' ) ) {
            $post_data['post_excerpt'] = $request->get_param( 'excerpt' );
        }
        
        // Create the post
        $post_id = wp_insert_post( $post_data, true );
        
        if ( is_wp_error( $post_id ) ) {
            return new WP_Error(
                'rest_cannot_create',
                __( 'The post cannot be created.', 'wp-cpt-restapi' ),
                array( 'status' => 500 )
            );
        }
        
        // Handle meta fields from both 'meta' object and root-level fields
        $this->handle_meta_fields( $request, $post_id, $cpt );
        
        // Get the created post
        $created_post = get_post( $post_id );
        if ( ! $created_post ) {
            return new WP_Error(
                'rest_cannot_read',
                __( 'The post was created but cannot be read.', 'wp-cpt-restapi' ),
                array( 'status' => 500 )
            );
        }
        
        // Return the created post data with 201 status
        $response = rest_ensure_response( $this->prepare_post_data( $created_post ) );
        $response->set_status( 201 );
        
        return $response;
    }
    /**
     * Delete an existing post for a specific CPT.
     *
     * @since    0.2
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function delete_cpt_post( $request ) {
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

        // Get the existing post
        $existing_post = get_post( $id );

        if ( ! $existing_post || $existing_post->post_type !== $cpt ) {
            return new WP_Error(
                'rest_post_invalid_id',
                __( 'Invalid post ID or post does not belong to this Custom Post Type.', 'wp-cpt-restapi' ),
                array( 'status' => 404 )
            );
        }

        // Check if post is published (only allow deleting published posts for security)
        if ( $existing_post->post_status !== 'publish' ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'Sorry, you are not allowed to delete this post.', 'wp-cpt-restapi' ),
                array( 'status' => 403 )
            );
        }

        // Prepare the post data before deletion for the response
        $deleted_post_data = $this->prepare_post_data( $existing_post );

        // Delete the post permanently
        $deleted = wp_delete_post( $id, true );

        if ( ! $deleted ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'The post cannot be deleted.', 'wp-cpt-restapi' ),
                array( 'status' => 500 )
            );
        }

        // Return the deleted post data with 200 status
        return rest_ensure_response( $deleted_post_data );
    }

    /**
     * Update an existing post for a specific CPT.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function update_cpt_post( $request ) {
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
        
        // Get the existing post
        $existing_post = get_post( $id );
        
        if ( ! $existing_post || $existing_post->post_type !== $cpt ) {
            return new WP_Error(
                'rest_post_invalid_id',
                __( 'Invalid post ID or post does not belong to this Custom Post Type.', 'wp-cpt-restapi' ),
                array( 'status' => 404 )
            );
        }
        
        // Check if post is published (only allow updating published posts for security)
        if ( $existing_post->post_status !== 'publish' ) {
            return new WP_Error(
                'rest_cannot_edit',
                __( 'Sorry, you are not allowed to edit this post.', 'wp-cpt-restapi' ),
                array( 'status' => 403 )
            );
        }
        
        // Prepare post data for update
        $post_data = array(
            'ID' => $id,
        );
        
        // Add fields if provided (partial update support)
        if ( $request->get_param( 'title' ) !== null ) {
            $post_data['post_title'] = $request->get_param( 'title' );
        }
        
        if ( $request->get_param( 'content' ) !== null ) {
            $post_data['post_content'] = $request->get_param( 'content' );
        }
        
        if ( $request->get_param( 'excerpt' ) !== null ) {
            $post_data['post_excerpt'] = $request->get_param( 'excerpt' );
        }
        
        if ( $request->get_param( 'status' ) !== null ) {
            $post_data['post_status'] = $this->sanitize_post_status( $request->get_param( 'status' ) );
        }
        
        // Update the post
        $updated_post_id = wp_update_post( $post_data, true );
        
        if ( is_wp_error( $updated_post_id ) ) {
            return new WP_Error(
                'rest_cannot_update',
                __( 'The post cannot be updated.', 'wp-cpt-restapi' ),
                array( 'status' => 500 )
            );
        }
        
        // Handle meta fields using the same flexible approach as create
        $this->handle_meta_fields( $request, $id, $cpt );
        
        // Get the updated post
        $updated_post = get_post( $id );
        if ( ! $updated_post ) {
            return new WP_Error(
                'rest_cannot_read',
                __( 'The post was updated but cannot be read.', 'wp-cpt-restapi' ),
                array( 'status' => 500 )
            );
        }
        
        // Return the updated post data with 200 status
        return rest_ensure_response( $this->prepare_post_data( $updated_post ) );
    }


    /**
     * Sanitize post status value.
     *
     * @since    0.1
     * @param    string    $status    The post status to sanitize.
     * @return   string               The sanitized post status.
     */
    private function sanitize_post_status( $status ) {
        $allowed_statuses = array( 'publish', 'draft', 'private', 'pending' );
        
        if ( empty( $status ) || ! in_array( $status, $allowed_statuses, true ) ) {
            return 'publish';
        }
        
        return $status;
    }

    /**
     * Handle meta fields from both 'meta' object and root-level fields.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @param    int                $post_id    The post ID.
     * @param    string             $cpt        The custom post type.
     */
    private function handle_meta_fields( $request, $post_id, $cpt ) {
        $all_meta_data = array();
        
        // Get meta fields from nested 'meta' object
        $nested_meta = $request->get_param( 'meta' );
        if ( is_array( $nested_meta ) && ! empty( $nested_meta ) ) {
            $all_meta_data = array_merge( $all_meta_data, $nested_meta );
        }
        
        // Get meta fields from root level (excluding standard post fields)
        $standard_fields = array( 'title', 'content', 'excerpt', 'status', 'meta', 'cpt' );
        $all_params = $request->get_params();
        
        foreach ( $all_params as $key => $value ) {
            if ( ! in_array( $key, $standard_fields, true ) ) {
                // This is potentially a meta field at root level
                $all_meta_data[ $key ] = $value;
            }
        }
        
        // Update meta fields if any were found
        if ( ! empty( $all_meta_data ) ) {
            $this->update_post_meta_fields( $post_id, $all_meta_data, $cpt );
        }
    }

    /**
     * Update post meta fields for a CPT post.
     *
     * @since    0.1
     * @param    int       $post_id     The post ID.
     * @param    array     $meta_data   The meta data to update.
     * @param    string    $cpt         The custom post type.
     */
    private function update_post_meta_fields( $post_id, $meta_data, $cpt ) {
        // Get registered meta fields for this post type
        $registered_meta = get_registered_meta_keys( 'post', $cpt );
        
        foreach ( $meta_data as $meta_key => $meta_value ) {
            // Skip private meta fields (starting with _)
            if ( strpos( $meta_key, '_' ) === 0 ) {
                continue;
            }
            
            // Only update if it's a registered meta field or if no specific meta fields are registered
            if ( empty( $registered_meta ) || isset( $registered_meta[ $meta_key ] ) ) {
                // Sanitize the meta value
                $sanitized_value = $this->sanitize_meta_value( $meta_value );
                
                if ( $sanitized_value !== null ) {
                    update_post_meta( $post_id, $meta_key, $sanitized_value );
                }
            }
        }
    }

    /**
     * Sanitize meta field value.
     *
     * @since    0.1
     * @param    mixed    $value    The meta value to sanitize.
     * @return   mixed             The sanitized meta value, or null if invalid.
     */
    private function sanitize_meta_value( $value ) {
        if ( is_string( $value ) ) {
            return sanitize_text_field( $value );
        } elseif ( is_numeric( $value ) ) {
            return $value;
        } elseif ( is_array( $value ) ) {
            return array_map( array( $this, 'sanitize_meta_value' ), $value );
        } elseif ( is_bool( $value ) ) {
            return $value;
        }
        
        // For other types, convert to string and sanitize
        return sanitize_text_field( (string) $value );
    }

    /**
     * Validate meta field parameter.
     *
     * @since    0.1
     * @param    mixed             $value      The meta field value.
     * @param    WP_REST_Request   $request    The REST request object.
     * @param    string            $param      The parameter name.
     * @return   bool                          True if valid, false otherwise.
     */
    public function validate_meta_field( $value, $request, $param ) {
        // Meta field should be an array or null
        return is_array( $value ) || is_null( $value );
    }

    /**
     * Prepare post data for API response.
     *
     * @since    0.1
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
     * @since    0.1
     * @return   array    Information about the namespace.
     */
    public function namespace_info() {
        return array(
            'namespace' => get_option( $this->option_name, $this->default_segment ) . '/v1',
            'description' => __( 'WordPress Custom Post Types REST API', 'wp-cpt-restapi' ),
            'version' => WP_CPT_RESTAPI_VERSION,
        );
    }

    /**
     * Get the OpenAPI 3.0.3 specification.
     *
     * This endpoint returns the complete OpenAPI 3.0.3 specification for the Custom Post Types REST API.
     * The specification is dynamically generated based on active CPTs and plugin settings.
     *
     * @since    0.1
     * @return   WP_REST_Response    The OpenAPI specification.
     */
    public function get_openapi_spec() {
        $openapi_generator = new WP_CPT_RestAPI_OpenAPI();
        $spec = $openapi_generator->generate_openapi_spec();
        
        $response = rest_ensure_response( $spec );
        $response->header( 'Content-Type', 'application/json' );
        
        return $response;
    }

    /**
     * Check if Toolset relationship support is enabled.
     *
     * @since    0.1
     * @return   bool    True if Toolset relationship support is enabled, false otherwise.
     */
    public function is_toolset_relationships_enabled() {
        return (bool) get_option( $this->toolset_option_name, false );
    }

    /**
     * Get all Toolset relationships.
     *
     * This method fetches all registered Toolset relationships and returns
     * them in a structured format for the REST API.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function get_toolset_relationships( $request ) {
        // Check if Toolset is active and available
        if ( ! $this->is_toolset_available() ) {
            return new WP_Error(
                'toolset_not_available',
                __( 'Toolset plugin is not active or available.', 'wp-cpt-restapi' ),
                array( 'status' => 503 )
            );
        }

        try {
            // Get all relationship definitions using Toolset API
            $relationships = $this->fetch_toolset_relationships();
            
            // Format the response
            $response = array(
                'relationships' => $relationships,
                'count' => count( $relationships ),
            );
            
            return rest_ensure_response( $response );
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'toolset_error',
                __( 'Error fetching Toolset relationships: ', 'wp-cpt-restapi' ) . $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Check if Toolset is available and active.
     *
     * @since    0.1
     * @return   bool    True if Toolset is available, false otherwise.
     */
    private function is_toolset_available() {
        // Check if Toolset Types is active - try multiple detection methods
        return class_exists( 'Types_Main' ) ||
               class_exists( 'Toolset_Common_Bootstrap' ) ||
               defined( 'TYPES_VERSION' ) ||
               function_exists( 'wpcf_init' );
    }

    /**
     * Fetch Toolset relationships using the Toolset API.
     *
     * @since    0.1
     * @return   array    Array of formatted relationship data.
     */
    private function fetch_toolset_relationships() {
        $relationships = array();
        
        // Try different methods to get Toolset relationships
        
        // Method 1: Try the newer Toolset API
        if ( class_exists( 'Toolset_Relationship_Definition_Repository' ) ) {
            try {
                $repository = Toolset_Relationship_Definition_Repository::get_instance();
                if ( method_exists( $repository, 'get_definitions' ) ) {
                    $toolset_relationships = $repository->get_definitions();
                    foreach ( $toolset_relationships as $relationship ) {
                        $relationships[] = $this->format_relationship_data( $relationship );
                    }
                }
            } catch ( Exception $e ) {
                // Continue to next method
            }
        }
        
        // Method 2: Try legacy Types API if no relationships found
        if ( empty( $relationships ) && function_exists( 'wpcf_pr_get_belongs' ) ) {
            // Get legacy post relationships
            $legacy_relationships = wpcf_pr_get_belongs();
            if ( is_array( $legacy_relationships ) ) {
                foreach ( $legacy_relationships as $slug => $relationship ) {
                    $relationships[] = $this->format_legacy_relationship_data( $slug, $relationship );
                }
            }
        }
        
        // Method 3: Try to get from database directly if still empty
        if ( empty( $relationships ) ) {
            global $wpdb;
            $table_name = $wpdb->prefix . 'toolset_relationships';
            if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
                $results = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}toolset_relationships WHERE active = %d", 1 ) );
                foreach ( $results as $relationship ) {
                    $relationships[] = $this->format_db_relationship_data( $relationship );
                }
            }
        }
        
        return $relationships;
    }

    /**
     * Format relationship data for API response.
     *
     * @since    0.1
     * @param    mixed    $relationship    The Toolset relationship object.
     * @return   array                     Formatted relationship data.
     */
    private function format_relationship_data( $relationship ) {
        $formatted = array(
            'slug' => '',
            'name' => '',
            'parent_types' => array(),
            'child_types' => array(),
            'cardinality' => array(),
            'is_active' => true,
        );

        try {
            // Get relationship slug/key
            if ( method_exists( $relationship, 'get_slug' ) ) {
                $formatted['slug'] = sanitize_text_field( $relationship->get_slug() );
            }

            // Get relationship display name
            if ( method_exists( $relationship, 'get_display_name' ) ) {
                $formatted['name'] = sanitize_text_field( $relationship->get_display_name() );
            } elseif ( method_exists( $relationship, 'get_slug' ) ) {
                $formatted['name'] = sanitize_text_field( $relationship->get_slug() );
            }

            // Get parent post types
            if ( method_exists( $relationship, 'get_parent_type' ) ) {
                $parent_type = $relationship->get_parent_type();
                if ( method_exists( $parent_type, 'get_types' ) ) {
                    $formatted['parent_types'] = array_map( 'sanitize_text_field', $parent_type->get_types() );
                }
            }

            // Get child post types
            if ( method_exists( $relationship, 'get_child_type' ) ) {
                $child_type = $relationship->get_child_type();
                if ( method_exists( $child_type, 'get_types' ) ) {
                    $formatted['child_types'] = array_map( 'sanitize_text_field', $child_type->get_types() );
                }
            }

            // Get cardinality information
            if ( method_exists( $relationship, 'get_cardinality' ) ) {
                $cardinality = $relationship->get_cardinality();
                if ( method_exists( $cardinality, 'get_parent_max' ) && method_exists( $cardinality, 'get_child_max' ) ) {
                    $formatted['cardinality'] = array(
                        'parent_max' => $cardinality->get_parent_max(),
                        'child_max' => $cardinality->get_child_max(),
                    );
                }
            }
            // Check if relationship is active
            if ( method_exists( $relationship, 'is_active' ) ) {
                $formatted['is_active'] = (bool) $relationship->is_active();
            }

        } catch ( Exception $e ) {
            // If there's an error getting relationship data, return basic info
            $formatted['error'] = 'Error retrieving relationship details: ' . $e->getMessage();
        }

        return $formatted;
    }

    /**
     * Format legacy relationship data for API response.
     *
     * @since    0.1
     * @param    string    $slug           The relationship slug.
     * @param    array     $relationship   The legacy relationship data.
     * @return   array                     Formatted relationship data.
     */
    private function format_legacy_relationship_data( $slug, $relationship ) {
        return array(
            'slug' => sanitize_text_field( $slug ),
            'name' => isset( $relationship['name'] ) ? sanitize_text_field( $relationship['name'] ) : $slug,
            'parent_types' => isset( $relationship['parent'] ) ? array( sanitize_text_field( $relationship['parent'] ) ) : array(),
            'child_types' => isset( $relationship['child'] ) ? array( sanitize_text_field( $relationship['child'] ) ) : array(),
            'cardinality' => array(
                'parent_max' => isset( $relationship['cardinality']['parent'] ) ? (int) $relationship['cardinality']['parent'] : -1,
                'child_max' => isset( $relationship['cardinality']['child'] ) ? (int) $relationship['cardinality']['child'] : -1,
            ),
            'is_active' => true,
            'type' => 'legacy'
        );
    }

    /**
     * Format database relationship data for API response.
     *
     * @since    0.1
     * @param    object    $relationship    The database relationship object.
     * @return   array                      Formatted relationship data.
     */
    private function format_db_relationship_data( $relationship ) {
        return array(
            'slug' => isset( $relationship->slug ) ? sanitize_text_field( $relationship->slug ) : '',
            'name' => isset( $relationship->display_name_plural ) ? sanitize_text_field( $relationship->display_name_plural ) :
                     (isset( $relationship->slug ) ? sanitize_text_field( $relationship->slug ) : ''),
            'parent_types' => array(), // Would need additional query to get post types
            'child_types' => array(),  // Would need additional query to get post types
            'cardinality' => array(
                'parent_max' => isset( $relationship->parent_max ) ? (int) $relationship->parent_max : -1,
                'child_max' => isset( $relationship->child_max ) ? (int) $relationship->child_max : -1,
            ),
            'is_active' => isset( $relationship->active ) ? (bool) $relationship->active : true,
            'type' => 'database'
        );
    }

    /**
     * Validate relation slug parameter.
     *
     * @since    0.1
     * @param    string            $value      The relation slug value.
     * @param    WP_REST_Request   $request    The REST request object.
     * @param    string            $param      The parameter name.
     * @return   bool                          True if valid, false otherwise.
     */
    public function validate_relation_slug( $value, $request, $param ) {
        if ( empty( $value ) || ! is_string( $value ) ) {
            return false;
        }
        
        // Check if the relation slug exists
        if ( ! $this->is_toolset_available() ) {
            return false;
        }
        
        // Get all available relationships and check if this slug exists
        try {
            $relationships = $this->fetch_toolset_relationships();
            foreach ( $relationships as $relationship ) {
                if ( isset( $relationship['slug'] ) && $relationship['slug'] === $value ) {
                    return true;
                }
            }
        } catch ( Exception $e ) {
            return false;
        }
        
        return false;
    }

    /**
     * Validate post ID parameter.
     *
     * @since    0.1
     * @param    int               $value      The post ID value.
     * @param    WP_REST_Request   $request    The REST request object.
     * @param    string            $param      The parameter name.
     * @return   bool                          True if valid, false otherwise.
     */
    public function validate_post_id( $value, $request, $param ) {
        if ( ! is_numeric( $value ) || $value <= 0 ) {
            return false;
        }
        
        $post = get_post( absint( $value ) );
        return $post && $post->post_status === 'publish';
    }

    /**
     * Get relationship instances for a specific relation slug.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function get_toolset_relationship_instances( $request ) {
        $relation_slug = $request->get_param( 'relation_slug' );
        
        // Check if Toolset is available
        if ( ! $this->is_toolset_available() ) {
            return new WP_Error(
                'toolset_not_available',
                __( 'Toolset plugin is not active or available.', 'wp-cpt-restapi' ),
                array( 'status' => 503 )
            );
        }

        try {
            $instances = array();
            
            // Try different methods to get relationship instances
            
            // Method 1: Try newer Toolset API
            if ( function_exists( 'toolset_get_related_posts' ) ) {
                // Get all posts and find relationships
                $all_posts = get_posts( array(
                    'post_type' => 'any',
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids'
                ) );
                
                foreach ( $all_posts as $post_id ) {
                    // Try as parent role
                    try {
                        $related_posts_as_parent = toolset_get_related_posts( $post_id, $relation_slug, 'parent' );
                        if ( ! empty( $related_posts_as_parent ) ) {
                            foreach ( $related_posts_as_parent as $related_id ) {
                                $relationship_id = $this->generate_relationship_id( $post_id, $related_id, $relation_slug );
                                $instances[] = array(
                                    'relationship_id' => $relationship_id,
                                    'parent_id' => $post_id,
                                    'child_id' => $related_id,
                                    'relation_slug' => $relation_slug,
                                );
                            }
                        }
                    } catch ( Exception $e ) {
                        // Continue to next method if this fails
                    }
                    
                    // Try as child role
                    try {
                        $related_posts_as_child = toolset_get_related_posts( $post_id, $relation_slug, 'child' );
                        if ( ! empty( $related_posts_as_child ) ) {
                            foreach ( $related_posts_as_child as $related_id ) {
                                $relationship_id = $this->generate_relationship_id( $related_id, $post_id, $relation_slug );
                                // Avoid duplicates by checking if this relationship already exists
                                $duplicate = false;
                                foreach ( $instances as $existing_instance ) {
                                    if ( $existing_instance['relationship_id'] === $relationship_id ) {
                                        $duplicate = true;
                                        break;
                                    }
                                }
                                if ( ! $duplicate ) {
                                    $instances[] = array(
                                        'relationship_id' => $relationship_id,
                                        'parent_id' => $related_id,
                                        'child_id' => $post_id,
                                        'relation_slug' => $relation_slug,
                                    );
                                }
                            }
                        }
                    } catch ( Exception $e ) {
                        // Continue to next method if this fails
                    }
                }
            }
            
            // Method 2: Try database query if no instances found
            if ( empty( $instances ) ) {
                global $wpdb;
                $table_name = $wpdb->prefix . 'toolset_associations';
                
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) === $table_name ) {
                    // Get relationship definition ID
                    $relationship_def = $wpdb->get_row( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}toolset_relationships WHERE slug = %s AND active = %d",
                        $relation_slug,
                        1
                    ) );

                    if ( $relationship_def ) {
                        $associations = $wpdb->get_results( $wpdb->prepare(
                            "SELECT parent_id, child_id FROM {$wpdb->prefix}toolset_associations WHERE relationship_id = %d",
                            $relationship_def->id
                        ) );
                        
                        foreach ( $associations as $association ) {
                            $relationship_id = $this->generate_relationship_id( $association->parent_id, $association->child_id, $relation_slug );
                            $instances[] = array(
                                'relationship_id' => $relationship_id,
                                'parent_id' => (int) $association->parent_id,
                                'child_id' => (int) $association->child_id,
                                'relation_slug' => $relation_slug,
                            );
                        }
                    }
                }
            }
            
            $response = array(
                'relation_slug' => $relation_slug,
                'instances' => $instances,
                'count' => count( $instances ),
            );
            
            return rest_ensure_response( $response );
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'toolset_error',
                __( 'Error fetching relationship instances: ', 'wp-cpt-restapi' ) . $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Create a new relationship instance.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function create_toolset_relationship_instance( $request ) {
        $relation_slug = $request->get_param( 'relation_slug' );
        $parent_id = $request->get_param( 'parent_id' );
        $child_id = $request->get_param( 'child_id' );
        
        // Check if Toolset is available
        if ( ! $this->is_toolset_available() ) {
            return new WP_Error(
                'toolset_not_available',
                __( 'Toolset plugin is not active or available.', 'wp-cpt-restapi' ),
                array( 'status' => 503 )
            );
        }

        try {
            $success = false;
            
            // Method 1: Try newer Toolset API
            if ( function_exists( 'toolset_connect_posts' ) ) {
                $success = toolset_connect_posts( $relation_slug, $parent_id, $child_id );
            }
            
            // Method 2: Try legacy API if newer one failed
            if ( ! $success && function_exists( 'wpcf_pr_add_belongs' ) ) {
                $success = wpcf_pr_add_belongs( $child_id, $parent_id, $relation_slug );
            }
            
            // Method 3: Direct database insertion as last resort
            if ( ! $success ) {
                global $wpdb;
                $relationship_def_table = $wpdb->prefix . 'toolset_relationships';
                $associations_table = $wpdb->prefix . 'toolset_associations';

                // Check if tables exist
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $relationship_def_table ) ) === $relationship_def_table &&
                     $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $associations_table ) ) === $associations_table ) {

                    // Get relationship definition ID
                    $relationship_def = $wpdb->get_row( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}toolset_relationships WHERE slug = %s AND active = %d",
                        $relation_slug,
                        1
                    ) );

                    if ( $relationship_def ) {
                        // Check if relationship already exists
                        $existing = $wpdb->get_var( $wpdb->prepare(
                            "SELECT id FROM {$wpdb->prefix}toolset_associations WHERE relationship_id = %d AND parent_id = %d AND child_id = %d",
                            $relationship_def->id,
                            $parent_id,
                            $child_id
                        ) );

                        if ( ! $existing ) {
                            $result = $wpdb->insert(
                                $wpdb->prefix . 'toolset_associations',
                                array(
                                    'relationship_id' => $relationship_def->id,
                                    'parent_id' => $parent_id,
                                    'child_id' => $child_id,
                                ),
                                array( '%d', '%d', '%d' )
                            );
                            
                            $success = $result !== false;
                        } else {
                            return new WP_Error(
                                'relationship_exists',
                                __( 'Relationship already exists between these posts.', 'wp-cpt-restapi' ),
                                array( 'status' => 409 )
                            );
                        }
                    }
                }
            }
            
            if ( $success ) {
                $relationship_id = $this->generate_relationship_id( $parent_id, $child_id, $relation_slug );
                
                $response = array(
                    'success' => true,
                    'relationship_id' => $relationship_id,
                    'parent_id' => $parent_id,
                    'child_id' => $child_id,
                    'relation_slug' => $relation_slug,
                    'message' => __( 'Relationship created successfully.', 'wp-cpt-restapi' ),
                );
                
                $rest_response = rest_ensure_response( $response );
                $rest_response->set_status( 201 );
                return $rest_response;
            } else {
                return new WP_Error(
                    'relationship_creation_failed',
                    __( 'Failed to create relationship.', 'wp-cpt-restapi' ),
                    array( 'status' => 500 )
                );
            }
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'toolset_error',
                __( 'Error creating relationship: ', 'wp-cpt-restapi' ) . $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Delete a specific relationship instance.
     *
     * @since    0.1
     * @param    WP_REST_Request    $request    The REST request object.
     * @return   WP_REST_Response|WP_Error      The response or error.
     */
    public function delete_toolset_relationship_instance( $request ) {
        $relation_slug = $request->get_param( 'relation_slug' );
        $relationship_id = $request->get_param( 'relationship_id' );
        
        // Check if Toolset is available
        if ( ! $this->is_toolset_available() ) {
            return new WP_Error(
                'toolset_not_available',
                __( 'Toolset plugin is not active or available.', 'wp-cpt-restapi' ),
                array( 'status' => 503 )
            );
        }

        try {
            // Parse relationship ID to get parent and child IDs
            $parsed_ids = $this->parse_relationship_id( $relationship_id, $relation_slug );
            if ( ! $parsed_ids ) {
                return new WP_Error(
                    'invalid_relationship_id',
                    __( 'Invalid relationship ID format.', 'wp-cpt-restapi' ),
                    array( 'status' => 400 )
                );
            }
            
            $parent_id = $parsed_ids['parent_id'];
            $child_id = $parsed_ids['child_id'];
            $success = false;
            
            // Method 1: Try newer Toolset API
            if ( function_exists( 'toolset_disconnect_posts' ) ) {
                $success = toolset_disconnect_posts( $relation_slug, $parent_id, $child_id );
            }
            
            // Method 2: Try legacy API if newer one failed
            if ( ! $success && function_exists( 'wpcf_pr_del_belongs' ) ) {
                $success = wpcf_pr_del_belongs( $child_id, $parent_id, $relation_slug );
            }
            
            // Method 3: Direct database deletion as last resort
            if ( ! $success ) {
                global $wpdb;
                $relationship_def_table = $wpdb->prefix . 'toolset_relationships';
                $associations_table = $wpdb->prefix . 'toolset_associations';

                // Check if tables exist
                if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $relationship_def_table ) ) === $relationship_def_table &&
                     $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $associations_table ) ) === $associations_table ) {

                    // Get relationship definition ID
                    $relationship_def = $wpdb->get_row( $wpdb->prepare(
                        "SELECT id FROM {$wpdb->prefix}toolset_relationships WHERE slug = %s AND active = %d",
                        $relation_slug,
                        1
                    ) );

                    if ( $relationship_def ) {
                        $result = $wpdb->delete(
                            $wpdb->prefix . 'toolset_associations',
                            array(
                                'relationship_id' => $relationship_def->id,
                                'parent_id' => $parent_id,
                                'child_id' => $child_id,
                            ),
                            array( '%d', '%d', '%d' )
                        );
                        
                        $success = $result !== false && $result > 0;
                    }
                }
            }
            
            if ( $success ) {
                $response = array(
                    'success' => true,
                    'relationship_id' => $relationship_id,
                    'parent_id' => $parent_id,
                    'child_id' => $child_id,
                    'relation_slug' => $relation_slug,
                    'message' => __( 'Relationship deleted successfully.', 'wp-cpt-restapi' ),
                );
                
                return rest_ensure_response( $response );
            } else {
                return new WP_Error(
                    'relationship_not_found',
                    __( 'Relationship not found or could not be deleted.', 'wp-cpt-restapi' ),
                    array( 'status' => 404 )
                );
            }
            
        } catch ( Exception $e ) {
            return new WP_Error(
                'toolset_error',
                __( 'Error deleting relationship: ', 'wp-cpt-restapi' ) . $e->getMessage(),
                array( 'status' => 500 )
            );
        }
    }

    /**
     * Generate a unique relationship ID from parent ID, child ID, and relation slug.
     *
     * @since    0.1
     * @param    int       $parent_id      The parent post ID.
     * @param    int       $child_id       The child post ID.
     * @param    string    $relation_slug  The relation slug.
     * @return   string                    The generated relationship ID.
     */
    private function generate_relationship_id( $parent_id, $child_id, $relation_slug ) {
        return base64_encode( $parent_id . ':' . $child_id . ':' . $relation_slug );
    }

    /**
     * Parse a relationship ID to extract parent ID, child ID, and relation slug.
     *
     * @since    0.1
     * @param    string    $relationship_id  The relationship ID to parse.
     * @param    string    $relation_slug    The expected relation slug for validation.
     * @return   array|false               Array with parent_id, child_id, and relation_slug, or false if invalid.
     */
    private function parse_relationship_id( $relationship_id, $relation_slug ) {
        $decoded = base64_decode( $relationship_id, true );
        if ( $decoded === false ) {
            return false;
        }
        
        $parts = explode( ':', $decoded );
        if ( count( $parts ) !== 3 ) {
            return false;
        }
        
        $parent_id = absint( $parts[0] );
        $child_id = absint( $parts[1] );
        $decoded_slug = sanitize_text_field( $parts[2] );
        
        // Validate that the slug matches
        if ( $decoded_slug !== $relation_slug ) {
            return false;
        }
        
        // Validate that IDs are valid
        if ( $parent_id <= 0 || $child_id <= 0 ) {
            return false;
        }
        
        return array(
            'parent_id' => $parent_id,
            'child_id' => $child_id,
            'relation_slug' => $decoded_slug,
        );
    }

}