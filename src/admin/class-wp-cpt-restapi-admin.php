<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      0.1
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for
 * the admin area of the site.
 */
class WP_CPT_RestAPI_Admin {

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
     * The option name for Toolset relationship support.
     *
     * @since    0.1
     * @access   private
     * @var      string    $toolset_option_name    The option name for Toolset relationship support.
     */
    private $toolset_option_name = 'cpt_rest_api_toolset_relationships';

    /**
     * The option name for including non-public CPTs.
     *
     * @since    0.1
     * @access   private
     * @var      string    $include_nonpublic_option_name    The option name for including non-public CPTs.
     */
    private $include_nonpublic_option_name = 'cpt_rest_api_include_nonpublic_cpts';

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
     * Initialize the class and set its properties.
     *
     * @since    0.1
     */
    public function __construct() {
        // Initialize API Keys manager
        $this->api_keys = new WP_CPT_RestAPI_API_Keys();

        // Add AJAX handlers for API key management
        add_action( 'wp_ajax_cpt_rest_api_add_key', array( $this, 'ajax_add_key' ) );
        add_action( 'wp_ajax_cpt_rest_api_delete_key', array( $this, 'ajax_delete_key' ) );

        // Add AJAX handler for CPT reset
        add_action( 'wp_ajax_cpt_rest_api_reset_cpts', array( $this, 'ajax_reset_cpts' ) );

        // Add AJAX handler for dismissing admin notices
        add_action( 'wp_ajax_cpt_rest_api_dismiss_notice', array( $this, 'ajax_dismiss_notice' ) );

        // Add admin notices for configuration guidance
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

        // Add migration hooks for API key security update
        add_action( 'admin_init', array( $this, 'handle_key_migration' ) );
        add_action( 'admin_notices', array( $this, 'display_migration_notice' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    0.1
     * @param    string    $hook    The current admin page.
     */
    public function enqueue_styles( $hook ) {
        // Only enqueue on our settings page
        if ( 'settings_page_cpt-rest-api' !== $hook ) {
            return;
        }

        wp_enqueue_style(
            'wp-cpt-restapi-admin',
            WP_CPT_RESTAPI_PLUGIN_URL . 'assets/css/wp-cpt-restapi-admin.css',
            array(),
            WP_CPT_RESTAPI_VERSION,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    0.1
     * @param    string    $hook    The current admin page.
     */
    public function enqueue_scripts( $hook ) {
        // Only enqueue on our settings page
        if ( 'settings_page_cpt-rest-api' !== $hook ) {
            return;
        }

        wp_enqueue_script(
            'wp-cpt-restapi-admin',
            WP_CPT_RESTAPI_PLUGIN_URL . 'assets/js/wp-cpt-restapi-admin.js',
            array( 'jquery' ),
            WP_CPT_RESTAPI_VERSION,
            false
        );
        
        // Localize the script with data for AJAX operations
        wp_localize_script(
            'wp-cpt-restapi-admin',
            'cptRestApiAdmin',
            array(
                'nonce'  => wp_create_nonce( 'cpt_rest_api' ),
                'i18n'   => array(
                    'emptyLabel'   => esc_js( __( 'Please enter a label for the API key.', 'wp-cpt-rest-api' ) ),
                    'generating'   => esc_js( __( 'Generating...', 'wp-cpt-rest-api' ) ),
                    'generateKey'  => esc_js( __( 'Generate API Key', 'wp-cpt-rest-api' ) ),
                    'copy'         => esc_js( __( 'Copy', 'wp-cpt-rest-api' ) ),
                    'copied'       => esc_js( __( 'Copied!', 'wp-cpt-rest-api' ) ),
                    'copyFailed'   => esc_js( __( 'Failed to copy. Please try again.', 'wp-cpt-rest-api' ) ),
                    'ajaxError'    => esc_js( __( 'An error occurred. Please try again.', 'wp-cpt-rest-api' ) ),
                ),
            )
        );
    }

    /**
     * Add the settings page to the WordPress admin menu.
     *
     * @since    0.1
     */
    public function add_settings_page() {
        add_submenu_page(
            'options-general.php',                 // Parent slug
            'CPT REST API',                        // Page title
            'CPT REST API',                        // Menu title
            'manage_options',                      // Capability
            'cpt-rest-api',                        // Menu slug
            array( $this, 'display_settings_page' ) // Callback function
        );
    }

    /**
     * Register the settings for the admin page.
     *
     * @since    0.1
     */
    public function register_settings() {
        // Register the setting for base segment
        register_setting(
            'cpt_rest_api_settings',                  // Option group
            $this->option_name,                       // Option name
            array( $this, 'validate_base_segment' )   // Sanitize callback
        );

        // Register the setting for active CPTs
        register_setting(
            'cpt_rest_api_settings',                  // Option group
            $this->cpt_option_name,                   // Option name
            array( $this, 'validate_active_cpts' )    // Sanitize callback
        );

        // Register the setting for Toolset relationship support
        register_setting(
            'cpt_rest_api_settings',                  // Option group
            $this->toolset_option_name,               // Option name
            array( $this, 'validate_toolset_relationships' ) // Sanitize callback
        );

        // Register the setting for including non-public CPTs
        register_setting(
            'cpt_rest_api_settings',                  // Option group
            $this->include_nonpublic_option_name,     // Option name
            array( $this, 'validate_include_nonpublic_cpts' ) // Sanitize callback
        );

        // Add settings section for REST API configuration
        add_settings_section(
            'cpt_rest_api_section',                   // ID
            __( 'REST API Settings', 'wp-cpt-rest-api' ), // Title
            array( $this, 'settings_section_callback' ), // Callback
            'cpt-rest-api'                            // Page
        );

        // Add settings field for base segment
        add_settings_field(
            'cpt_rest_api_base_segment',              // ID
            __( 'API Base Segment', 'wp-cpt-rest-api' ), // Title
            array( $this, 'base_segment_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_section'                    // Section
        );

        // Add settings field for Toolset relationship support
        add_settings_field(
            'cpt_rest_api_toolset_relationships',     // ID
            __( 'Enable Toolset relationship support', 'wp-cpt-rest-api' ), // Title
            array( $this, 'toolset_relationships_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_section'                    // Section
        );

        // Add settings field for including non-public CPTs
        add_settings_field(
            'cpt_rest_api_include_nonpublic_cpts',    // ID
            __( 'Include non-public Custom Post Types', 'wp-cpt-rest-api' ), // Title
            array( $this, 'include_nonpublic_cpts_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_section'                    // Section
        );

        // Add settings section for CPT Management
        add_settings_section(
            'cpt_rest_api_cpts_section',              // ID
            __( 'Custom Post Types', 'wp-cpt-rest-api' ), // Title
            array( $this, 'cpts_section_callback' ),  // Callback
            'cpt-rest-api'                            // Page
        );

        // Add settings field for CPT selection
        add_settings_field(
            'cpt_rest_api_active_cpts',               // ID
            __( 'Active Post Types', 'wp-cpt-rest-api' ), // Title
            array( $this, 'cpts_field_callback' ),    // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_cpts_section'               // Section
        );
        
        // Add settings section for API Keys
        add_settings_section(
            'cpt_rest_api_keys_section',              // ID
            __( 'API Keys', 'wp-cpt-rest-api' ),       // Title
            array( $this, 'api_keys_section_callback' ), // Callback
            'cpt-rest-api'                            // Page
        );
        
        // Add settings field for API Keys management
        add_settings_field(
            'cpt_rest_api_keys_management',           // ID
            __( 'Manage API Keys', 'wp-cpt-rest-api' ), // Title
            array( $this, 'api_keys_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_keys_section'               // Section
        );
    }

    /**
     * Render the settings section description.
     *
     * @since    0.1
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure the base segment for the Custom Post Types REST API.', 'wp-cpt-rest-api' ) . '</p>';
    }

    /**
     * Render the base segment field.
     *
     * @since    0.1
     */
    public function base_segment_field_callback() {
        // Get the current value or use default
        $value = get_option( $this->option_name, $this->default_segment );
        
        // Get the site URL for the preview
        $site_url = get_site_url();
        $rest_url = trailingslashit( $site_url ) . 'wp-json/' . esc_attr( $value ) . '/v1/';
        
        ?>
        <div class="cpt-rest-api-field-container">
            <input type="text" 
                   id="cpt_rest_api_base_segment" 
                   name="<?php echo esc_attr( $this->option_name ); ?>" 
                   value="<?php echo esc_attr( $value ); ?>" 
                   class="regular-text" 
                   required
                   pattern="^[a-z0-9-]{1,120}$"
                   title="<?php echo esc_attr__( 'Must be between 1 and 120 characters long and can only contain lowercase letters, digits, and hyphens.', 'wp-cpt-rest-api' ); ?>"
            />
            <span class="tooltip">
                <span class="dashicons dashicons-editor-help"></span>
                <span class="tooltip-text">
                    <?php echo esc_html__( 'The base segment defines the namespace for your REST API endpoints. It must be between 1 and 120 characters long and can only contain lowercase letters (a-z), digits (0-9), and hyphens (-).', 'wp-cpt-rest-api' ); ?>
                </span>
            </span>
        </div>
        <p class="description">
            <?php echo esc_html__( 'Full REST API URL:', 'wp-cpt-rest-api' ); ?> 
            <code id="rest-api-preview"><?php echo esc_url( $rest_url ); ?></code>
        </p>
        <?php
    }

    /**
     * Render the Toolset relationships field.
     *
     * @since    0.1
     */
    public function toolset_relationships_field_callback() {
        // Get the current value or use default (false)
        $value = get_option( $this->toolset_option_name, false );
        
        ?>
        <div class="cpt-rest-api-field-container">
            <label class="cpt-rest-api-toggle-switch">
                <input type="checkbox"
                       id="cpt_rest_api_toolset_relationships"
                       name="<?php echo esc_attr( $this->toolset_option_name ); ?>"
                       value="1"
                       <?php checked( $value, true ); ?>
                />
                <span class="cpt-rest-api-toggle-slider"></span>
                <span class="cpt-rest-api-toggle-label">
                    <?php echo esc_html__( 'Enable', 'wp-cpt-rest-api' ); ?>
                </span>
            </label>
            <span class="tooltip">
                <span class="dashicons dashicons-editor-help"></span>
                <span class="tooltip-text">
                    <?php echo esc_html__( 'When enabled, this will add REST API endpoints for managing Toolset relationships between Custom Post Types. Requires Toolset Types plugin to be installed and active.', 'wp-cpt-rest-api' ); ?>
                </span>
            </span>
        </div>
        <p class="description">
            <?php echo esc_html__( 'Enable this option to include Toolset relationship functionality in the REST API endpoints.', 'wp-cpt-rest-api' ); ?>
        </p>
        <?php
    }

    /**
     * Validate the base segment field.
     *
     * @since    0.1
     * @param    string    $input    The input to validate.
     * @return   string              The validated input.
     */
    public function validate_base_segment( $input ) {
        // Check if the input is empty
        if ( empty( $input ) ) {
            add_settings_error(
                $this->option_name,
                'empty_segment',
                __( 'The base segment cannot be empty.', 'wp-cpt-rest-api' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );
        }

        // Check if the input length is between 1 and 120 characters
        if ( strlen( $input ) < 1 || strlen( $input ) > 120 ) {
            add_settings_error(
                $this->option_name,
                'length_error',
                __( 'The base segment must be between 1 and 120 characters long.', 'wp-cpt-rest-api' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );
        }

        // Check if the input contains only allowed characters (lowercase letters, digits, and hyphens)
        if ( ! preg_match( '/^[a-z0-9-]+$/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'invalid_chars',
                __( 'The base segment can only contain lowercase letters, digits, and hyphens.', 'wp-cpt-rest-api' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );
        }

        // If all validations pass, add a success message
        add_settings_error(
            $this->option_name,
            'settings_updated',
            __( 'Settings saved successfully.', 'wp-cpt-rest-api' ),
            'updated'
        );

        return $input;
    }

    /**
     * Validate the active CPTs field.
     *
     * @since    0.1
     * @param    array    $input    The input to validate.
     * @return   array              The validated input.
     */
    public function validate_active_cpts( $input ) {
        // If input is not an array, return empty array
        if ( ! is_array( $input ) ) {
            return array();
        }

        // Get all available CPTs to validate against
        $available_cpts = $this->get_available_cpts();
        $validated_cpts = array();

        // Only keep CPTs that exist and are valid
        foreach ( $input as $cpt ) {
            if ( isset( $available_cpts[ $cpt ] ) ) {
                $validated_cpts[] = sanitize_text_field( $cpt );
            }
        }

        // Add success message
        add_settings_error(
            $this->cpt_option_name,
            'cpts_updated',
            __( 'Custom Post Types settings saved successfully.', 'wp-cpt-rest-api' ),
            'updated'
        );

        return $validated_cpts;
    }

    /**
     * Validate the Toolset relationships field.
     *
     * @since    0.1
     * @param    mixed    $input    The input to validate.
     * @return   bool               The validated input.
     */
    public function validate_toolset_relationships( $input ) {
        // Convert to boolean - checkbox will send '1' if checked, null if not
        $validated = ! empty( $input ) && $input === '1';
        
        // Add success message
        add_settings_error(
            $this->toolset_option_name,
            'toolset_updated',
            __( 'Toolset relationship settings saved successfully.', 'wp-cpt-rest-api' ),
            'updated'
        );

        return $validated;
    }

    /**
     * Validate the include non-public CPTs field.
     *
     * @since    0.1
     * @param    mixed    $input    The input to validate.
     * @return   bool               The validated input.
     */
    public function validate_include_nonpublic_cpts( $input ) {
        // Handle array of selected visibility types
        $validated = array();
        
        if ( is_array( $input ) ) {
            $allowed_types = array( 'publicly_queryable', 'show_ui', 'private' );
            
            foreach ( $input as $type ) {
                if ( in_array( $type, $allowed_types, true ) ) {
                    $validated[] = sanitize_text_field( $type );
                }
            }
        }
        
        // Add success message
        add_settings_error(
            $this->include_nonpublic_option_name,
            'nonpublic_updated',
            __( 'Non-public CPT visibility settings saved successfully.', 'wp-cpt-rest-api' ),
            'updated'
        );

        return $validated;
    }

    /**
     * Render the include non-public CPTs field.
     *
     * @since    0.1
     */
    public function include_nonpublic_cpts_field_callback() {
        // Get the current value or use default (empty array)
        $selected_types = get_option( $this->include_nonpublic_option_name, array() );
        if ( ! is_array( $selected_types ) ) {
            $selected_types = array();
        }
        
        // Define available visibility types
        $visibility_types = array(
            'publicly_queryable' => __( 'Publicly Queryable', 'wp-cpt-rest-api' ),
            'show_ui'           => __( 'Admin Only (Show UI)', 'wp-cpt-rest-api' ),
            'private'           => __( 'Private', 'wp-cpt-rest-api' ),
        );
        
        ?>
        <div class="cpt-rest-api-field-container">
            <fieldset>
                <legend class="screen-reader-text"><?php echo esc_html__( 'Select non-public CPT types to include', 'wp-cpt-rest-api' ); ?></legend>
                <p class="description" style="margin-bottom: 10px;">
                    <?php echo esc_html__( 'Select which types of non-public Custom Post Types should be available for selection:', 'wp-cpt-rest-api' ); ?>
                </p>
                
                <?php foreach ( $visibility_types as $type => $label ) : ?>
                    <label style="display: block; margin-bottom: 8px;">
                        <input type="checkbox"
                               name="<?php echo esc_attr( $this->include_nonpublic_option_name ); ?>[]"
                               value="<?php echo esc_attr( $type ); ?>"
                               <?php checked( in_array( $type, $selected_types, true ) ); ?>
                        />
                        <?php echo esc_html( $label ); ?>
                    </label>
                <?php endforeach; ?>
                
                <p class="description" style="margin-top: 10px;">
                    <strong><?php echo esc_html__( 'Note:', 'wp-cpt-rest-api' ); ?></strong>
                    <?php echo esc_html__( 'Public CPTs are always available. Select additional visibility types to include in the list below.', 'wp-cpt-rest-api' ); ?>
                </p>
            </fieldset>
            
            <span class="tooltip">
                <span class="dashicons dashicons-editor-help"></span>
                <span class="tooltip-text">
                    <?php echo esc_html__( 'Choose which types of non-public CPTs to make available for API exposure. Publicly Queryable CPTs can be queried but aren\'t fully public. Admin Only CPTs show in WordPress admin. Private CPTs are completely hidden from public access.', 'wp-cpt-rest-api' ); ?>
                </span>
            </span>
        </div>
        <?php
    }

    /**
     * Get all available CPTs (excluding core types).
     *
     * @since    0.1
     * @return   array    Array of CPT objects keyed by post type name.
     */
    private function get_available_cpts() {
        // Get all registered post types
        $all_post_types = get_post_types( array(), 'objects' );
        $core_types = array( 'post', 'page', 'attachment' );
        $available_cpts = array();
        
        // Get selected non-public visibility types
        $selected_types = get_option( $this->include_nonpublic_option_name, array() );
        if ( ! is_array( $selected_types ) ) {
            $selected_types = array();
        }
        
        foreach ( $all_post_types as $post_type_name => $post_type_obj ) {
            // Skip core post types
            if ( in_array( $post_type_name, $core_types, true ) ) {
                continue;
            }
            
            // Always include public CPTs
            if ( $post_type_obj->public ) {
                $available_cpts[ $post_type_name ] = $post_type_obj;
                continue;
            }
            
            // Check if non-public CPT matches selected visibility types
            $include_cpt = false;
            
            // Check for publicly queryable
            if ( in_array( 'publicly_queryable', $selected_types, true ) &&
                 $post_type_obj->publicly_queryable && ! $post_type_obj->public ) {
                $include_cpt = true;
            }
            
            // Check for show_ui (admin only)
            if ( in_array( 'show_ui', $selected_types, true ) &&
                 $post_type_obj->show_ui && ! $post_type_obj->public && ! $post_type_obj->publicly_queryable ) {
                $include_cpt = true;
            }
            
            // Check for private (completely private CPTs)
            if ( in_array( 'private', $selected_types, true ) &&
                 ! $post_type_obj->public && ! $post_type_obj->publicly_queryable && ! $post_type_obj->show_ui ) {
                $include_cpt = true;
            }
            
            if ( $include_cpt ) {
                $available_cpts[ $post_type_name ] = $post_type_obj;
            }
        }

        return $available_cpts;
    }

    /**
     * Render the CPTs section description.
     *
     * @since    0.1
     */
    public function cpts_section_callback() {
        echo '<p>' . esc_html__( 'Select which Custom Post Types should be available through the REST API. Use the option above to include non-public CPTs in the selection.', 'wp-cpt-rest-api' ) . '</p>';
    }

    /**
     * Render the CPTs selection field.
     *
     * @since    0.1
     */
    public function cpts_field_callback() {
        // Get available CPTs
        $available_cpts = $this->get_available_cpts();
        
        // Get currently active CPTs
        $active_cpts = get_option( $this->cpt_option_name, array() );
        
        if ( empty( $available_cpts ) ) {
            echo '<p>' . esc_html__( 'No Custom Post Types found. Custom Post Types will appear here once they are registered.', 'wp-cpt-rest-api' ) . '</p>';
            return;
        }

        ?>
        <div class="cpt-rest-api-cpts-container">
            <table class="widefat cpt-rest-api-cpts-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Post Type', 'wp-cpt-rest-api' ); ?></th>
                        <th><?php echo esc_html__( 'Description', 'wp-cpt-rest-api' ); ?></th>
                        <th><?php echo esc_html__( 'Slug', 'wp-cpt-rest-api' ); ?></th>
                        <th><?php echo esc_html__( 'Visibility', 'wp-cpt-rest-api' ); ?></th>
                        <th><?php echo esc_html__( 'Status', 'wp-cpt-rest-api' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $available_cpts as $cpt_name => $cpt_object ) : ?>
                        <tr>
                            <td>
                                <strong><?php echo esc_html( $cpt_object->labels->name ); ?></strong>
                            </td>
                            <td>
                                <?php if ( ! empty( $cpt_object->description ) ) : ?>
                                    <?php echo esc_html( $cpt_object->description ); ?>
                                <?php else : ?>
                                    <span class="description"><?php echo esc_html__( 'No description available', 'wp-cpt-rest-api' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html( $cpt_name ); ?></code>
                            </td>
                            <td>
                                <?php
                                // Determine visibility status
                                if ( $cpt_object->public ) {
                                    echo '<span class="cpt-visibility-public">' . esc_html__( 'Public', 'wp-cpt-rest-api' ) . '</span>';
                                } elseif ( $cpt_object->publicly_queryable ) {
                                    echo '<span class="cpt-visibility-queryable">' . esc_html__( 'Publicly Queryable', 'wp-cpt-rest-api' ) . '</span>';
                                } elseif ( $cpt_object->show_ui ) {
                                    echo '<span class="cpt-visibility-admin">' . esc_html__( 'Admin Only', 'wp-cpt-rest-api' ) . '</span>';
                                } else {
                                    echo '<span class="cpt-visibility-private">' . esc_html__( 'Private', 'wp-cpt-rest-api' ) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <label class="cpt-rest-api-toggle-switch">
                                    <input
                                        type="checkbox"
                                        name="<?php echo esc_attr( $this->cpt_option_name ); ?>[]"
                                        value="<?php echo esc_attr( $cpt_name ); ?>"
                                        <?php checked( in_array( $cpt_name, $active_cpts, true ) ); ?>
                                    />
                                    <span class="cpt-rest-api-toggle-slider"></span>
                                    <span class="cpt-rest-api-toggle-label">
                                        <?php echo esc_html__( 'Activate', 'wp-cpt-rest-api' ); ?>
                                    </span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cpt-rest-api-cpts-actions">
                <button type="button" class="button cpt-rest-api-reset-cpts">
                    <?php echo esc_html__( 'Reset All', 'wp-cpt-rest-api' ); ?>
                </button>
                <p class="description">
                    <?php echo esc_html__( 'Reset All will deactivate all Custom Post Types.', 'wp-cpt-rest-api' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Display the settings page content.
     *
     * @since    0.1
     */
    public function display_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'CPT REST API', 'wp-cpt-rest-api' ); ?></h1>
            
            <!-- Section 1: API Settings -->
            <h2><?php echo esc_html__( 'API Settings', 'wp-cpt-rest-api' ); ?></h2>
            
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields( 'cpt_rest_api_settings' );
                ?>
                
                <!-- REST API Base Segment Section -->
                <h3><?php echo esc_html__( 'REST API Base Segment', 'wp-cpt-rest-api' ); ?></h3>
                <p><?php echo esc_html__( 'Configure the base segment for the Custom Post Types REST API.', 'wp-cpt-rest-api' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->base_segment_field_callback(); ?>
                </div>
                
                <!-- Toolset Relationships Section -->
                <h3><?php echo esc_html__( 'Toolset Relationships', 'wp-cpt-rest-api' ); ?></h3>
                <p><?php echo esc_html__( 'Enable support for Toolset relationship functionality in the REST API.', 'wp-cpt-rest-api' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->toolset_relationships_field_callback(); ?>
                </div>
                
                <!-- Include Non-Public CPTs Section -->
                <h3><?php echo esc_html__( 'Non-Public Custom Post Types', 'wp-cpt-rest-api' ); ?></h3>
                <p><?php echo esc_html__( 'Control whether non-public Custom Post Types should be available for selection.', 'wp-cpt-rest-api' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->include_nonpublic_cpts_field_callback(); ?>
                </div>
                
                <!-- Custom Post Types Section -->
                <h3><?php echo esc_html__( 'Custom Post Types', 'wp-cpt-rest-api' ); ?></h3>
                <p><?php echo esc_html__( 'Select which Custom Post Types should be available through the REST API.', 'wp-cpt-rest-api' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->cpts_field_callback(); ?>
                </div>
                
                <?php
                // Output save settings button
                submit_button( __( 'Save Settings', 'wp-cpt-rest-api' ) );
                ?>
            </form>
            
            <hr>
            
            <!-- Section 2: API Keys Management -->
            <h2><?php echo esc_html__( 'API Keys Management', 'wp-cpt-rest-api' ); ?></h2>
            
            <div class="cpt-rest-api-section-separator">
                <h3><?php echo esc_html__( 'API Keys', 'wp-cpt-rest-api' ); ?></h3>
                <p><?php echo esc_html__( 'Create and manage API keys for accessing the REST API endpoints.', 'wp-cpt-rest-api' ); ?></p>
                <p><?php echo esc_html__( 'API keys can be used to authenticate requests to the REST API using the Bearer authentication method.', 'wp-cpt-rest-api' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->api_keys_field_callback(); ?>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Output a specific settings section and its fields.
     *
     * @since    0.1
     * @param    string    $section_id    The section ID to output.
     */
    private function output_settings_section( $section_id ) {
        global $wp_settings_sections, $wp_settings_fields;
        
        if ( ! isset( $wp_settings_sections['cpt-rest-api'][$section_id] ) ) {
            return;
        }
        
        $section = $wp_settings_sections['cpt-rest-api'][$section_id];
        
        // Output section title and callback
        if ( $section['title'] ) {
            echo '<h3>' . esc_html( $section['title'] ) . '</h3>';
        }
        
        if ( $section['callback'] ) {
            call_user_func( $section['callback'], $section );
        }
        
        // Output section fields directly without WordPress table structure
        if ( ! isset( $wp_settings_fields['cpt-rest-api'][$section_id] ) ) {
            return;
        }
        
        // Call field callbacks directly to avoid table wrapper
        foreach ( $wp_settings_fields['cpt-rest-api'][$section_id] as $field ) {
            echo '<div class="cpt-rest-api-field-wrapper">';
            call_user_func( $field['callback'], $field['args'] );
            echo '</div>';
        }
    }
    
    /**
     * Render the API Keys section description.
     *
     * @since    0.1
     */
    public function api_keys_section_callback() {
        echo '<p>' . esc_html__( 'Create and manage API keys for accessing the REST API endpoints.', 'wp-cpt-rest-api' ) . '</p>';
        echo '<p>' . esc_html__( 'API keys can be used to authenticate requests to the REST API using the Bearer authentication method.', 'wp-cpt-rest-api' ) . '</p>';

        // Security warning
        echo '<div class="notice notice-warning inline" style="margin: 15px 0; padding: 10px;">';
        echo '<p><strong>⚠️ ' . esc_html__( 'Security Notice:', 'wp-cpt-rest-api' ) . '</strong></p>';
        echo '<ul style="margin-left: 20px; margin-top: 5px;">';
        echo '<li>' . esc_html__( 'API keys grant full access to all enabled Custom Post Types', 'wp-cpt-rest-api' ) . '</li>';
        echo '<li>' . esc_html__( 'Keys can perform all operations: create, read, update, and delete', 'wp-cpt-rest-api' ) . '</li>';
        echo '<li>' . esc_html__( 'Treat API keys like passwords - never share them publicly or commit them to version control', 'wp-cpt-rest-api' ) . '</li>';
        echo '<li>' . esc_html__( 'Regenerate keys immediately if you suspect they have been compromised', 'wp-cpt-rest-api' ) . '</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    /**
     * Render the API Keys management field.
     *
     * @since    0.1
     */
    public function api_keys_field_callback() {
        // Get all API keys
        $keys = $this->api_keys->get_keys();
        
        ?>
        <div class="cpt-rest-api-keys-container">
            <!-- API Keys List -->
            <div class="cpt-rest-api-keys-list">
                <h3><?php echo esc_html__( 'Your API Keys', 'wp-cpt-rest-api' ); ?></h3>
                
                <?php if ( empty( $keys ) ) : ?>
                    <p class="cpt-rest-api-no-keys"><?php echo esc_html__( 'No API keys found. Create your first key below.', 'wp-cpt-rest-api' ); ?></p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__( 'Label', 'wp-cpt-rest-api' ); ?></th>
                                <th><?php echo esc_html__( 'Key Prefix', 'wp-cpt-rest-api' ); ?></th>
                                <th><?php echo esc_html__( 'Created', 'wp-cpt-rest-api' ); ?></th>
                                <th><?php echo esc_html__( 'Actions', 'wp-cpt-rest-api' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $keys as $key ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $key['label'] ); ?></td>
                                    <td>
                                        <code class="api-key-prefix">
                                            <?php
                                            $prefix = isset($key['key_prefix']) ? $key['key_prefix'] : '****';
                                            echo esc_html( $prefix );
                                            ?>••••••••••••••••••••••••••••
                                        </code>
                                        <span class="description" style="display: block; margin-top: 5px; font-style: italic;">
                                            <?php echo esc_html__( 'Full key hidden for security', 'wp-cpt-rest-api' ); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $key['created_at'] ) ) ); ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="button button-small cpt-rest-api-delete-key"
                                            data-id="<?php echo esc_attr( $key['id'] ); ?>"
                                            data-confirm="<?php echo esc_attr__( 'Are you sure you want to delete this API key? This action cannot be undone.', 'wp-cpt-rest-api' ); ?>"
                                        >
                                            <?php echo esc_html__( 'Delete', 'wp-cpt-rest-api' ); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            
            <!-- Create New API Key Form -->
            <div class="cpt-rest-api-create-key">
                <h3><?php echo esc_html__( 'Create a New API Key', 'wp-cpt-rest-api' ); ?></h3>
                
                <div class="cpt-rest-api-create-key-form">
                    <label for="cpt_rest_api_key_label"><?php echo esc_html__( 'Label', 'wp-cpt-rest-api' ); ?></label>
                    <input
                        type="text"
                        id="cpt_rest_api_key_label"
                        name="cpt_rest_api_key_label"
                        placeholder="<?php echo esc_attr__( 'Enter a label for your API key', 'wp-cpt-rest-api' ); ?>"
                        required
                    />
                    <p class="description">
                        <?php echo esc_html__( 'A descriptive name to help you identify this key.', 'wp-cpt-rest-api' ); ?>
                    </p>
                    
                    <button type="button" class="button button-primary cpt-rest-api-generate-key">
                        <?php echo esc_html__( 'Generate API Key', 'wp-cpt-rest-api' ); ?>
                    </button>
                </div>
                
                <div class="cpt-rest-api-key-generated" style="display: none;">
                    <div class="notice notice-warning inline" style="margin: 0 0 15px 0; padding: 12px;">
                        <h4 style="margin-top: 0;">
                            <span class="dashicons dashicons-warning" style="color: #f56e28;"></span>
                            <?php echo esc_html__( 'Important: Save Your API Key Now', 'wp-cpt-rest-api' ); ?>
                        </h4>
                        <p style="margin: 8px 0;">
                            <strong><?php echo esc_html__( 'This key will only be displayed once and cannot be recovered.', 'wp-cpt-rest-api' ); ?></strong>
                        </p>
                        <p style="margin: 8px 0 0 0;">
                            <?php echo esc_html__( 'Copy it now and store it securely. If you lose this key, you will need to generate a new one.', 'wp-cpt-rest-api' ); ?>
                        </p>
                    </div>
                    <div class="cpt-rest-api-key-display">
                        <code id="cpt_rest_api_new_key" style="display: block; padding: 10px; background: #f0f0f1; font-size: 14px; word-break: break-all;"></code>
                        <button type="button" class="button cpt-rest-api-copy-key" style="margin-top: 10px;">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php echo esc_html__( 'Copy Key', 'wp-cpt-rest-api' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * AJAX handler for adding a new API key.
     *
     * @since    0.1
     */
    public function ajax_add_key() {
        // Check nonce with proper sanitization
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cpt_rest_api' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-cpt-rest-api' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-cpt-rest-api' ) ) );
        }

        // Rate limiting: max 10 keys per hour per user
        $user_id = get_current_user_id();
        $transient_key = 'cpt_rest_api_key_generation_' . $user_id;
        $generation_count = get_transient( $transient_key );

        if ( $generation_count && $generation_count >= 10 ) {
            wp_send_json_error( array(
                'message' => __( 'Rate limit exceeded. Please wait before generating more keys.', 'wp-cpt-rest-api' )
            ) );
        }

        // Increment counter
        $new_count = $generation_count ? $generation_count + 1 : 1;
        set_transient( $transient_key, $new_count, HOUR_IN_SECONDS );

        // Get the label from the request
        $label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';

        // Validate the label
        if ( empty( $label ) ) {
            wp_send_json_error( array( 'message' => __( 'Label is required.', 'wp-cpt-rest-api' ) ) );
        }

        // Validate label length (max 100 characters)
        if ( strlen( $label ) > 100 ) {
            wp_send_json_error( array( 'message' => __( 'Label must be 100 characters or less.', 'wp-cpt-rest-api' ) ) );
        }
        
        // Add the new key
        $new_key = $this->api_keys->add_key( $label );

        if ( $new_key ) {
            // Log successful API key creation
            $this->log_security_event( 'key_created', array(
                'label' => $label,
                'key_id' => $new_key['id'],
                'user' => wp_get_current_user()->user_login,
            ) );

            wp_send_json_success( array(
                'key' => $new_key,
                'message' => __( 'API key created successfully.', 'wp-cpt-rest-api' ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to create API key.', 'wp-cpt-rest-api' ) ) );
        }
    }
    
    /**
     * AJAX handler for deleting an API key.
     *
     * @since    0.1
     */
    public function ajax_delete_key() {
        // Check nonce with proper sanitization
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cpt_rest_api' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-cpt-rest-api' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-cpt-rest-api' ) ) );
        }
        
        // Get the key ID from the request
        $key_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        
        // Validate the key ID
        if ( empty( $key_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Key ID is required.', 'wp-cpt-rest-api' ) ) );
        }
        
        // Get key info before deletion for logging
        $key_info = $this->api_keys->get_key( $key_id );

        // Delete the key
        $deleted = $this->api_keys->delete_key( $key_id );

        if ( $deleted ) {
            // Log successful API key deletion
            $this->log_security_event( 'key_deleted', array(
                'key_id' => $key_id,
                'label' => $key_info ? $key_info['label'] : 'unknown',
                'user' => wp_get_current_user()->user_login,
            ) );

            wp_send_json_success( array( 'message' => __( 'API key deleted successfully.', 'wp-cpt-rest-api' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete API key.', 'wp-cpt-rest-api' ) ) );
        }
    }

    /**
     * AJAX handler for resetting CPTs.
     *
     * @since    0.1
     */
    public function ajax_reset_cpts() {
        // Check nonce with proper sanitization
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cpt_rest_api' ) ) {
            wp_send_json_error( array( 'message' => __( 'Security check failed.', 'wp-cpt-rest-api' ) ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-cpt-rest-api' ) ) );
        }
        
        // Reset CPTs by saving an empty array
        $updated = update_option( $this->cpt_option_name, array() );
        
        if ( $updated !== false ) {
            wp_send_json_success( array( 'message' => __( 'All Custom Post Types have been deactivated.', 'wp-cpt-rest-api' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to reset Custom Post Types.', 'wp-cpt-rest-api' ) ) );
        }
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
     * Display admin notices for configuration guidance.
     *
     * Shows helpful notices when:
     * - No Custom Post Types are enabled
     * - No API keys have been created
     *
     * @since    0.2.1
     * @return   void
     */
    public function display_admin_notices() {
        // Only show notices to users who can manage options
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get current screen
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Only show on plugin settings page and dashboard
        $show_on_pages = array(
            'settings_page_cpt-rest-api-settings', // Plugin settings page
            'dashboard',                             // Dashboard
        );

        if ( ! in_array( $screen->id, $show_on_pages, true ) ) {
            return;
        }

        // Check if user has dismissed notices
        $dismissed_notices = get_user_meta( get_current_user_id(), 'cpt_rest_api_dismissed_notices', true );
        if ( ! is_array( $dismissed_notices ) ) {
            $dismissed_notices = array();
        }

        // Notice for no CPTs enabled
        $active_cpts = get_option( $this->cpt_option_name, array() );
        if ( empty( $active_cpts ) && ! in_array( 'no_cpts', $dismissed_notices, true ) ) {
            $settings_url = admin_url( 'options-general.php?page=cpt-rest-api-settings' );
            ?>
            <div class="notice notice-warning is-dismissible" data-notice-id="no_cpts">
                <p>
                    <strong><?php esc_html_e( 'CPT REST API:', 'wp-cpt-rest-api' ); ?></strong>
                    <?php
                    printf(
                        /* translators: %s: Settings page URL */
                        esc_html__( 'No Custom Post Types are currently enabled for the REST API. %s to get started.', 'wp-cpt-rest-api' ),
                        '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Configure settings', 'wp-cpt-rest-api' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        // Notice for no API keys created
        $api_keys = $this->api_keys->get_keys();
        if ( empty( $api_keys ) && ! in_array( 'no_keys', $dismissed_notices, true ) ) {
            $settings_url = admin_url( 'options-general.php?page=cpt-rest-api-settings#api-keys' );
            ?>
            <div class="notice notice-info is-dismissible" data-notice-id="no_keys">
                <p>
                    <strong><?php esc_html_e( 'CPT REST API:', 'wp-cpt-rest-api' ); ?></strong>
                    <?php
                    printf(
                        /* translators: %s: Settings page URL */
                        esc_html__( 'No API keys have been created yet. You need at least one API key to access the REST API endpoints. %s', 'wp-cpt-rest-api' ),
                        '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Create an API key', 'wp-cpt-rest-api' ) . '</a>'
                    );
                    ?>
                </p>
            </div>
            <?php
        }

        // Add inline script to handle notice dismissal
        if ( ( empty( $active_cpts ) && ! in_array( 'no_cpts', $dismissed_notices, true ) ) ||
             ( empty( $api_keys ) && ! in_array( 'no_keys', $dismissed_notices, true ) ) ) {
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('.notice[data-notice-id]').on('click', '.notice-dismiss', function() {
                    var noticeId = $(this).parent().data('notice-id');
                    $.post(ajaxurl, {
                        action: 'cpt_rest_api_dismiss_notice',
                        notice_id: noticeId,
                        nonce: '<?php echo esc_js( wp_create_nonce( 'cpt_rest_api_dismiss_notice' ) ); ?>'
                    });
                });
            });
            </script>
            <?php
        }
    }

    /**
     * AJAX handler for dismissing admin notices.
     *
     * @since    0.2.1
     * @return   void
     */
    public function ajax_dismiss_notice() {
        // Check nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'cpt_rest_api_dismiss_notice' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'wp-cpt-rest-api' ) );
        }

        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this action.', 'wp-cpt-rest-api' ) );
        }

        // Get notice ID
        $notice_id = isset( $_POST['notice_id'] ) ? sanitize_text_field( wp_unslash( $_POST['notice_id'] ) ) : '';

        if ( empty( $notice_id ) ) {
            wp_die( esc_html__( 'Invalid notice ID.', 'wp-cpt-rest-api' ) );
        }

        // Get current dismissed notices
        $dismissed_notices = get_user_meta( get_current_user_id(), 'cpt_rest_api_dismissed_notices', true );
        if ( ! is_array( $dismissed_notices ) ) {
            $dismissed_notices = array();
        }

        // Add notice to dismissed list
        if ( ! in_array( $notice_id, $dismissed_notices, true ) ) {
            $dismissed_notices[] = $notice_id;
            update_user_meta( get_current_user_id(), 'cpt_rest_api_dismissed_notices', $dismissed_notices );
        }

        wp_die(); // Success
    }

    /**
     * Log security events to WordPress debug log.
     *
     * Only logs when WP_DEBUG_LOG is enabled. Creates standardized
     * security event log entries for audit trail purposes.
     *
     * @since    0.2.1
     * @param    string    $event_type    The type of security event (key_created, key_deleted).
     * @param    array     $context       Additional context data for the event.
     * @return   void
     */
    private function log_security_event( $event_type, $context = array() ) {
        // Only log when WordPress debug logging is enabled
        if ( ! defined( 'WP_DEBUG_LOG' ) || ! WP_DEBUG_LOG ) {
            return;
        }

        $event_messages = array(
            'key_created' => 'API key created',
            'key_deleted' => 'API key deleted',
        );

        $message = isset( $event_messages[ $event_type ] ) ? $event_messages[ $event_type ] : 'Security event';

        // Build context string
        $context_parts = array();
        foreach ( $context as $key => $value ) {
            $context_parts[] = sprintf( '%s=%s', $key, $value );
        }
        $context_string = ! empty( $context_parts ) ? ' | ' . implode( ', ', $context_parts ) : '';

        // Log the event
        error_log( sprintf(
            '[CPT REST API Security] %s%s',
            $message,
            $context_string
        ) );
    }

    /**
     * Handle key migration form submission.
     *
     * @since    0.3
     */
    public function handle_key_migration() {
        if (!isset($_POST['cpt_rest_api_migrate_keys'])) {
            return;
        }

        // Verify nonce
        if (!isset($_POST['cpt_rest_api_migrate_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cpt_rest_api_migrate_nonce'])), 'cpt_rest_api_migrate_keys')) {
            wp_die(__('Security check failed.', 'wp-cpt-rest-api'));
        }

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('Insufficient permissions.', 'wp-cpt-rest-api'));
        }

        // Perform migration
        $result = $this->api_keys->migrate_to_hashed_keys();

        // Show result
        add_settings_error(
            'cpt_rest_api_migration',
            'migration_complete',
            $result['message'],
            $result['success'] ? 'updated' : 'info'
        );

        // Redirect
        wp_redirect(admin_url('options-general.php?page=cpt-rest-api'));
        exit;
    }

    /**
     * Display admin notice for required key migration.
     *
     * @since    0.3
     */
    public function display_migration_notice() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if migration needed
        $keys = $this->api_keys->get_keys();
        $needs_migration = false;

        foreach ($keys as $key_data) {
            if (isset($key_data['key']) && !isset($key_data['key_hash'])) {
                $needs_migration = true;
                break;
            }
        }

        if (!$needs_migration) {
            return;
        }

        ?>
        <div class="notice notice-error">
            <h3 style="margin-top: 12px;">
                <span class="dashicons dashicons-shield-alt" style="color: #d63638;"></span>
                <?php echo esc_html__('Critical Security Update Required - CPT REST API', 'wp-cpt-rest-api'); ?>
            </h3>
            <p>
                <strong><?php echo esc_html__('Your API keys are stored insecurely and must be migrated.', 'wp-cpt-rest-api'); ?></strong>
            </p>
            <p>
                <?php echo esc_html__('This plugin now uses secure hashing for API keys. Your existing keys are stored in plaintext and vulnerable.', 'wp-cpt-rest-api'); ?>
            </p>
            <h4><?php echo esc_html__('What will happen:', 'wp-cpt-rest-api'); ?></h4>
            <ul style="list-style: disc; margin-left: 25px;">
                <li><?php echo esc_html__('All existing API keys will be permanently deleted', 'wp-cpt-rest-api'); ?></li>
                <li><?php echo esc_html__('You must regenerate new secure keys', 'wp-cpt-rest-api'); ?></li>
                <li><?php echo esc_html__('All services using the API must be updated with new keys', 'wp-cpt-rest-api'); ?></li>
                <li><?php echo esc_html__('New keys will only be visible once upon creation', 'wp-cpt-rest-api'); ?></li>
            </ul>
            <form method="post" action="" style="margin-top: 15px;">
                <?php wp_nonce_field('cpt_rest_api_migrate_keys', 'cpt_rest_api_migrate_nonce'); ?>
                <input type="hidden" name="cpt_rest_api_migrate_keys" value="1">
                <p>
                    <button type="submit" class="button button-primary button-large">
                        <?php echo esc_html__('Migrate to Secure Keys Now', 'wp-cpt-rest-api'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
    }

}