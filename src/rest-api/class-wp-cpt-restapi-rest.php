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
                        'permission_callback' => '__return_true',
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
                        'permission_callback' => '__return_true',
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
                        'permission_callback' => '__return_true',
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
                        'permission_callback' => '__return_true',
                    ),
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
     * Create a new post for a specific CPT.
     *
     * @since    1.0.0
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
     * Update an existing post for a specific CPT.
     *
     * @since    1.0.0
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
     * @since    1.0.0
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
     * @since    1.0.0
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
     * @since    1.0.0
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
     * @since    1.0.0
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
     * @since    1.0.0
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