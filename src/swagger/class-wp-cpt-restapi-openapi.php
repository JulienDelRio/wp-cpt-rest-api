<?php
/**
 * OpenAPI 3.0.3 specification generator for the Custom Post Types REST API plugin.
 *
 * @since      0.1
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * OpenAPI 3.0.3 specification generator class.
 *
 * Generates dynamic OpenAPI 3.0.3 specifications based on active CPTs and plugin settings.
 */
class WP_CPT_RestAPI_OpenAPI {

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
     * The option name for Toolset relationship support.
     *
     * @since    0.1
     * @access   private
     * @var      string    $toolset_option_name    The option name for Toolset relationship support.
     */
    private $toolset_option_name = 'cpt_rest_api_toolset_relationships';

    /**
     * Generate the complete OpenAPI 3.0.3 specification.
     *
     * @since    0.1
     * @return   array    The OpenAPI specification array.
     */
    public function generate_openapi_spec() {
        $base_segment = get_option( $this->option_name, $this->default_segment );
        $site_url = get_site_url();
        
        $spec = array(
            'openapi' => '3.0.3',
            'info' => array(
                'title' => 'WordPress Custom Post Types REST API',
                'description' => 'A robust WordPress plugin that extends the native REST API functionalities to provide comprehensive endpoints for Custom Post Types and their associated metadata.',
                'version' => WP_CPT_RESTAPI_VERSION,
                'contact' => array(
                    'name' => 'Julien DELRIO',
                    'url' => 'https://juliendelrio.fr'
                ),
                'license' => array(
                    'name' => 'Apache 2.0',
                    'url' => 'https://www.apache.org/licenses/LICENSE-2.0.html'
                )
            ),
            'servers' => array(
                array(
                    'url' => $site_url . '/wp-json/' . $base_segment . '/v1',
                    'description' => 'WordPress Custom Post Types REST API Server'
                )
            ),
            'security' => array(
                array(
                    'bearerAuth' => array()
                )
            ),
            'components' => $this->generate_components(),
            'paths' => $this->generate_paths()
        );

        return $spec;
    }

    /**
     * Generate OpenAPI components (schemas, security schemes, etc.).
     *
     * @since    0.1
     * @return   array    The components array.
     */
    private function generate_components() {
        return array(
            'securitySchemes' => array(
                'bearerAuth' => array(
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'API Key',
                    'description' => 'API Key authentication using Bearer token'
                )
            ),
            'schemas' => array(
                'Post' => array(
                    'type' => 'object',
                    'properties' => array(
                        'id' => array(
                            'type' => 'integer',
                            'description' => 'Unique identifier for the post'
                        ),
                        'title' => array(
                            'type' => 'string',
                            'description' => 'The title of the post'
                        ),
                        'content' => array(
                            'type' => 'string',
                            'description' => 'The content of the post (HTML allowed)'
                        ),
                        'excerpt' => array(
                            'type' => 'string',
                            'description' => 'The excerpt of the post'
                        ),
                        'slug' => array(
                            'type' => 'string',
                            'description' => 'The slug of the post'
                        ),
                        'status' => array(
                            'type' => 'string',
                            'enum' => array('publish', 'draft', 'private', 'pending'),
                            'description' => 'The status of the post'
                        ),
                        'type' => array(
                            'type' => 'string',
                            'description' => 'The post type'
                        ),
                        'date' => array(
                            'type' => 'string',
                            'format' => 'date-time',
                            'description' => 'The date the post was published'
                        ),
                        'modified' => array(
                            'type' => 'string',
                            'format' => 'date-time',
                            'description' => 'The date the post was last modified'
                        ),
                        'author' => array(
                            'type' => 'string',
                            'description' => 'The author ID of the post'
                        ),
                        'featured_media' => array(
                            'type' => 'integer',
                            'description' => 'The featured media ID'
                        ),
                        'meta' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                            'description' => 'Custom meta fields for the post'
                        )
                    )
                ),
                'PostInput' => array(
                    'type' => 'object',
                    'properties' => array(
                        'title' => array(
                            'type' => 'string',
                            'description' => 'The title of the post'
                        ),
                        'content' => array(
                            'type' => 'string',
                            'description' => 'The content of the post (HTML allowed)'
                        ),
                        'excerpt' => array(
                            'type' => 'string',
                            'description' => 'The excerpt of the post'
                        ),
                        'status' => array(
                            'type' => 'string',
                            'enum' => array('publish', 'draft', 'private', 'pending'),
                            'default' => 'publish',
                            'description' => 'The status of the post'
                        ),
                        'meta' => array(
                            'type' => 'object',
                            'additionalProperties' => true,
                            'description' => 'Custom meta fields for the post'
                        )
                    ),
                    'additionalProperties' => true
                ),
                'PostList' => array(
                    'type' => 'object',
                    'properties' => array(
                        'posts' => array(
                            'type' => 'array',
                            'items' => array(
                                '$ref' => '#/components/schemas/Post'
                            )
                        ),
                        'pagination' => array(
                            'type' => 'object',
                            'properties' => array(
                                'total' => array(
                                    'type' => 'integer',
                                    'description' => 'Total number of posts'
                                ),
                                'pages' => array(
                                    'type' => 'integer',
                                    'description' => 'Total number of pages'
                                ),
                                'current_page' => array(
                                    'type' => 'integer',
                                    'description' => 'Current page number'
                                ),
                                'per_page' => array(
                                    'type' => 'integer',
                                    'description' => 'Number of posts per page'
                                )
                            )
                        )
                    )
                ),
                'NamespaceInfo' => array(
                    'type' => 'object',
                    'properties' => array(
                        'namespace' => array(
                            'type' => 'string',
                            'description' => 'The API namespace'
                        ),
                        'description' => array(
                            'type' => 'string',
                            'description' => 'Description of the API'
                        ),
                        'version' => array(
                            'type' => 'string',
                            'description' => 'API version'
                        )
                    )
                ),
                'Error' => array(
                    'type' => 'object',
                    'properties' => array(
                        'code' => array(
                            'type' => 'string',
                            'description' => 'Error code'
                        ),
                        'message' => array(
                            'type' => 'string',
                            'description' => 'Error message'
                        ),
                        'data' => array(
                            'type' => 'object',
                            'properties' => array(
                                'status' => array(
                                    'type' => 'integer',
                                    'description' => 'HTTP status code'
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Generate OpenAPI paths based on active CPTs and settings.
     *
     * @since    0.1
     * @return   array    The paths array.
     */
    private function generate_paths() {
        $paths = array();
        
        // Add namespace info endpoint
        $paths['/'] = array(
            'get' => array(
                'summary' => 'Get namespace information',
                'description' => 'Returns information about the API namespace',
                'operationId' => 'getNamespaceInfo',
                'security' => array(),
                'responses' => array(
                    '200' => array(
                        'description' => 'Namespace information',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    '$ref' => '#/components/schemas/NamespaceInfo'
                                )
                            )
                        )
                    )
                )
            )
        );

        // Add OpenAPI specification endpoint
        $paths['/openapi'] = array(
            'get' => array(
                'summary' => 'Get OpenAPI specification',
                'description' => 'Returns the complete OpenAPI 3.1 specification for this API',
                'operationId' => 'getOpenAPISpec',
                'security' => array(),
                'responses' => array(
                    '200' => array(
                        'description' => 'OpenAPI 3.1 specification',
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'description' => 'OpenAPI 3.1 specification document'
                                )
                            )
                        )
                    )
                )
            )
        );

        // Add CPT endpoints - only for active CPTs (matching REST API behavior)
        $active_cpts = $this->get_active_cpts();
        if ( ! empty( $active_cpts ) ) {
            foreach ( $active_cpts as $cpt ) {
                $paths = array_merge( $paths, $this->generate_cpt_paths( $cpt ) );
            }
        }

        // Add Toolset relationship endpoints if enabled
        if ( $this->is_toolset_relationships_enabled() ) {
            $paths = array_merge( $paths, $this->generate_toolset_paths() );
        }

        return $paths;
    }

    /**
     * Generate paths for a specific CPT.
     *
     * @since    0.1
     * @param    string    $cpt    The CPT name.
     * @return   array             The paths array for the CPT.
     */
    private function generate_cpt_paths( $cpt ) {
        $post_type_obj = get_post_type_object( $cpt );
        $cpt_label = $post_type_obj ? $post_type_obj->labels->name : ucfirst( $cpt );
        
        return array(
            '/' . $cpt => array(
                'get' => array(
                    'summary' => 'List ' . $cpt_label,
                    'description' => 'Returns a paginated list of ' . $cpt_label,
                    'operationId' => 'get' . ucfirst( $cpt ) . 'Posts',
                    'parameters' => array(
                        array(
                            'name' => 'per_page',
                            'in' => 'query',
                            'description' => 'Number of posts per page (max: 100)',
                            'required' => false,
                            'schema' => array(
                                'type' => 'integer',
                                'default' => 10,
                                'minimum' => 1,
                                'maximum' => 100
                            )
                        ),
                        array(
                            'name' => 'page',
                            'in' => 'query',
                            'description' => 'Page number',
                            'required' => false,
                            'schema' => array(
                                'type' => 'integer',
                                'default' => 1,
                                'minimum' => 1
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => 'List of ' . $cpt_label,
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/PostList'
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '403' => array(
                            'description' => 'Forbidden - CPT not available via API',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                ),
                'post' => array(
                    'summary' => 'Create ' . $cpt_label,
                    'description' => 'Creates a new ' . $cpt_label,
                    'operationId' => 'create' . ucfirst( $cpt ) . 'Post',
                    'requestBody' => array(
                        'required' => true,
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    '$ref' => '#/components/schemas/PostInput'
                                )
                            )
                        )
                    ),
                    'responses' => array(
                        '201' => array(
                            'description' => $cpt_label . ' created successfully',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Post'
                                    )
                                )
                            )
                        ),
                        '400' => array(
                            'description' => 'Bad Request',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '500' => array(
                            'description' => 'Internal Server Error',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            '/' . $cpt . '/{id}' => array(
                'get' => array(
                    'summary' => 'Get single ' . $cpt_label,
                    'description' => 'Returns a specific ' . $cpt_label . ' by ID',
                    'operationId' => 'get' . ucfirst( $cpt ) . 'Post',
                    'parameters' => array(
                        array(
                            'name' => 'id',
                            'in' => 'path',
                            'description' => 'Post ID',
                            'required' => true,
                            'schema' => array(
                                'type' => 'integer',
                                'minimum' => 1
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => $cpt_label . ' details',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Post'
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '404' => array(
                            'description' => 'Post not found',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                ),
                'put' => array(
                    'summary' => 'Update ' . $cpt_label . ' (full update)',
                    'description' => 'Updates an existing ' . $cpt_label . ' with full replacement',
                    'operationId' => 'update' . ucfirst( $cpt ) . 'Post',
                    'parameters' => array(
                        array(
                            'name' => 'id',
                            'in' => 'path',
                            'description' => 'Post ID',
                            'required' => true,
                            'schema' => array(
                                'type' => 'integer',
                                'minimum' => 1
                            )
                        )
                    ),
                    'requestBody' => array(
                        'required' => true,
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    '$ref' => '#/components/schemas/PostInput'
                                )
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => $cpt_label . ' updated successfully',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Post'
                                    )
                                )
                            )
                        ),
                        '400' => array(
                            'description' => 'Bad Request',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '404' => array(
                            'description' => 'Post not found',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '500' => array(
                            'description' => 'Internal Server Error',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                ),
                'patch' => array(
                    'summary' => 'Update ' . $cpt_label . ' (partial update)',
                    'description' => 'Updates an existing ' . $cpt_label . ' with partial data',
                    'operationId' => 'patch' . ucfirst( $cpt ) . 'Post',
                    'parameters' => array(
                        array(
                            'name' => 'id',
                            'in' => 'path',
                            'description' => 'Post ID',
                            'required' => true,
                            'schema' => array(
                                'type' => 'integer',
                                'minimum' => 1
                            )
                        )
                    ),
                    'requestBody' => array(
                        'required' => true,
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    '$ref' => '#/components/schemas/PostInput'
                                )
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => $cpt_label . ' updated successfully',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Post'
                                    )
                                )
                            )
                        ),
                        '400' => array(
                            'description' => 'Bad Request',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '404' => array(
                            'description' => 'Post not found',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '500' => array(
                            'description' => 'Internal Server Error',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                ),
                'delete' => array(
                    'summary' => 'Delete ' . $cpt_label,
                    'description' => 'Permanently deletes a specific ' . $cpt_label . ' by ID',
                    'operationId' => 'delete' . ucfirst( $cpt ) . 'Post',
                    'parameters' => array(
                        array(
                            'name' => 'id',
                            'in' => 'path',
                            'description' => 'Post ID',
                            'required' => true,
                            'schema' => array(
                                'type' => 'integer',
                                'minimum' => 1
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => $cpt_label . ' deleted successfully',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'deleted' => array(
                                                'type' => 'boolean',
                                                'description' => 'Whether the post was deleted'
                                            ),
                                            'previous' => array(
                                                '$ref' => '#/components/schemas/Post'
                                            )
                                        )
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '403' => array(
                            'description' => 'Forbidden - CPT not enabled or insufficient permissions',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '404' => array(
                            'description' => 'Post not found',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Generate paths for Toolset relationships.
     *
     * @since    0.1
     * @return   array    The paths array for Toolset relationships.
     */
    private function generate_toolset_paths() {
        return array(
            '/relations' => array(
                'get' => array(
                    'summary' => 'List all Toolset relationships',
                    'description' => 'Returns all available Toolset relationships',
                    'operationId' => 'getToolsetRelationships',
                    'responses' => array(
                        '200' => array(
                            'description' => 'List of Toolset relationships',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'relationships' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'slug' => array('type' => 'string'),
                                                        'name' => array('type' => 'string'),
                                                        'parent_types' => array(
                                                            'type' => 'array',
                                                            'items' => array('type' => 'string')
                                                        ),
                                                        'child_types' => array(
                                                            'type' => 'array',
                                                            'items' => array('type' => 'string')
                                                        ),
                                                        'cardinality' => array(
                                                            'type' => 'object',
                                                            'properties' => array(
                                                                'parent_max' => array('type' => 'integer'),
                                                                'child_max' => array('type' => 'integer')
                                                            )
                                                        ),
                                                        'is_active' => array('type' => 'boolean')
                                                    )
                                                )
                                            ),
                                            'count' => array('type' => 'integer')
                                        )
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '503' => array(
                            'description' => 'Toolset not available',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            '/relations/{relation_slug}' => array(
                'get' => array(
                    'summary' => 'Get relationship instances',
                    'description' => 'Returns all instances of a specific relationship',
                    'operationId' => 'getToolsetRelationshipInstances',
                    'parameters' => array(
                        array(
                            'name' => 'relation_slug',
                            'in' => 'path',
                            'description' => 'Relationship slug',
                            'required' => true,
                            'schema' => array(
                                'type' => 'string',
                                'pattern' => '^[a-zA-Z0-9_-]+$'
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => 'Relationship instances',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'relation_slug' => array('type' => 'string'),
                                            'instances' => array(
                                                'type' => 'array',
                                                'items' => array(
                                                    'type' => 'object',
                                                    'properties' => array(
                                                        'relationship_id' => array('type' => 'string'),
                                                        'parent_id' => array('type' => 'integer'),
                                                        'child_id' => array('type' => 'integer'),
                                                        'relation_slug' => array('type' => 'string')
                                                    )
                                                )
                                            ),
                                            'count' => array('type' => 'integer')
                                        )
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '404' => array(
                            'description' => 'Relationship not found',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                ),
                'post' => array(
                    'summary' => 'Create relationship instance',
                    'description' => 'Creates a new relationship instance between two posts',
                    'operationId' => 'createToolsetRelationshipInstance',
                    'parameters' => array(
                        array(
                            'name' => 'relation_slug',
                            'in' => 'path',
                            'description' => 'Relationship slug',
                            'required' => true,
                            'schema' => array(
                                'type' => 'string',
                                'pattern' => '^[a-zA-Z0-9_-]+$'
                            )
                        )
                    ),
                    'requestBody' => array(
                        'required' => true,
                        'content' => array(
                            'application/json' => array(
                                'schema' => array(
                                    'type' => 'object',
                                    'required' => array('parent_id', 'child_id'),
                                    'properties' => array(
                                        'parent_id' => array(
                                            'type' => 'integer',
                                            'minimum' => 1,
                                            'description' => 'ID of the parent post'
                                        ),
                                        'child_id' => array(
                                            'type' => 'integer',
                                            'minimum' => 1,
                                            'description' => 'ID of the child post'
                                        )
                                    )
                                )
                            )
                        )
                    ),
                    'responses' => array(
                        '201' => array(
                            'description' => 'Relationship created successfully',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'success' => array('type' => 'boolean'),
                                            'relationship_id' => array('type' => 'string'),
                                            'parent_id' => array('type' => 'integer'),
                                            'child_id' => array('type' => 'integer'),
                                            'relation_slug' => array('type' => 'string'),
                                            'message' => array('type' => 'string')
                                        )
                                    )
                                )
                            )
                        ),
                        '400' => array(
                            'description' => 'Bad Request',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '409' => array(
                            'description' => 'Conflict - Relationship already exists',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '500' => array(
                            'description' => 'Internal Server Error',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                )
            ),
            '/relations/{relation_slug}/{relationship_id}' => array(
                'delete' => array(
                    'summary' => 'Delete relationship instance',
                    'description' => 'Deletes a specific relationship instance',
                    'operationId' => 'deleteToolsetRelationshipInstance',
                    'parameters' => array(
                        array(
                            'name' => 'relation_slug',
                            'in' => 'path',
                            'description' => 'Relationship slug',
                            'required' => true,
                            'schema' => array(
                                'type' => 'string',
                                'pattern' => '^[a-zA-Z0-9_-]+$'
                            )
                        ),
                        array(
                            'name' => 'relationship_id',
                            'in' => 'path',
                            'description' => 'Relationship ID',
                            'required' => true,
                            'schema' => array(
                                'type' => 'string'
                            )
                        )
                    ),
                    'responses' => array(
                        '200' => array(
                            'description' => 'Relationship deleted successfully',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        'type' => 'object',
                                        'properties' => array(
                                            'success' => array('type' => 'boolean'),
                                            'relationship_id' => array('type' => 'string'),
                                            'parent_id' => array('type' => 'integer'),
                                            'child_id' => array('type' => 'integer'),
                                            'relation_slug' => array('type' => 'string'),
                                            'message' => array('type' => 'string')
                                        )
                                    )
                                )
                            )
                        ),
                        '401' => array(
                            'description' => 'Unauthorized',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        ),
                        '404' => array(
                            'description' => 'Relationship not found',
                            'content' => array(
                                'application/json' => array(
                                    'schema' => array(
                                        '$ref' => '#/components/schemas/Error'
                                    )
                                )
                            )
                        )
                    )
                )
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
        
        // Validate that the CPTs still exist
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
            
            // Include CPTs that are either public, publicly queryable, or show in admin UI
            if ( $post_type_obj && (
                $post_type_obj->public ||
                $post_type_obj->publicly_queryable ||
                $post_type_obj->show_ui
            ) ) {
                $available_cpts[] = $post_type;
            }
        }
        
        // Only return CPTs that are both active and still available
        return array_intersect( $active_cpts, $available_cpts );
    }

    /**
     * Check if Toolset relationships are enabled.
     *
     * @since    0.1
     * @return   bool    True if Toolset relationships are enabled, false otherwise.
     */
    private function is_toolset_relationships_enabled() {
        return (bool) get_option( $this->toolset_option_name, false );
    }
}