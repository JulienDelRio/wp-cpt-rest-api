<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @since      1.0.0
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
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
        
        // Add AJAX handlers for API key management
        add_action( 'wp_ajax_cpt_rest_api_add_key', array( $this, 'ajax_add_key' ) );
        add_action( 'wp_ajax_cpt_rest_api_delete_key', array( $this, 'ajax_delete_key' ) );
        
        // Add AJAX handler for CPT reset
        add_action( 'wp_ajax_cpt_rest_api_reset_cpts', array( $this, 'ajax_reset_cpts' ) );
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
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
     * @since    1.0.0
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
                    'emptyLabel'   => __( 'Please enter a label for the API key.', 'wp-cpt-restapi' ),
                    'generating'   => __( 'Generating...', 'wp-cpt-restapi' ),
                    'generateKey'  => __( 'Generate API Key', 'wp-cpt-restapi' ),
                    'copy'         => __( 'Copy', 'wp-cpt-restapi' ),
                    'copied'       => __( 'Copied!', 'wp-cpt-restapi' ),
                    'copyFailed'   => __( 'Failed to copy. Please try again.', 'wp-cpt-restapi' ),
                    'ajaxError'    => __( 'An error occurred. Please try again.', 'wp-cpt-restapi' ),
                ),
            )
        );
    }

    /**
     * Add the settings page to the WordPress admin menu.
     *
     * @since    1.0.0
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
     * @since    1.0.0
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

        // Add settings section for REST API configuration
        add_settings_section(
            'cpt_rest_api_section',                   // ID
            __( 'REST API Settings', 'wp-cpt-restapi' ), // Title
            array( $this, 'settings_section_callback' ), // Callback
            'cpt-rest-api'                            // Page
        );

        // Add settings field for base segment
        add_settings_field(
            'cpt_rest_api_base_segment',              // ID
            __( 'API Base Segment', 'wp-cpt-restapi' ), // Title
            array( $this, 'base_segment_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_section'                    // Section
        );

        // Add settings section for CPT Management
        add_settings_section(
            'cpt_rest_api_cpts_section',              // ID
            __( 'Custom Post Types', 'wp-cpt-restapi' ), // Title
            array( $this, 'cpts_section_callback' ),  // Callback
            'cpt-rest-api'                            // Page
        );

        // Add settings field for CPT selection
        add_settings_field(
            'cpt_rest_api_active_cpts',               // ID
            __( 'Active Post Types', 'wp-cpt-restapi' ), // Title
            array( $this, 'cpts_field_callback' ),    // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_cpts_section'               // Section
        );
        
        // Add settings section for API Keys
        add_settings_section(
            'cpt_rest_api_keys_section',              // ID
            __( 'API Keys', 'wp-cpt-restapi' ),       // Title
            array( $this, 'api_keys_section_callback' ), // Callback
            'cpt-rest-api'                            // Page
        );
        
        // Add settings field for API Keys management
        add_settings_field(
            'cpt_rest_api_keys_management',           // ID
            __( 'Manage API Keys', 'wp-cpt-restapi' ), // Title
            array( $this, 'api_keys_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_keys_section'               // Section
        );
    }

    /**
     * Render the settings section description.
     *
     * @since    1.0.0
     */
    public function settings_section_callback() {
        echo '<p>' . esc_html__( 'Configure the base segment for the Custom Post Types REST API.', 'wp-cpt-restapi' ) . '</p>';
    }

    /**
     * Render the base segment field.
     *
     * @since    1.0.0
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
                   title="<?php echo esc_attr__( 'Must be between 1 and 120 characters long and can only contain lowercase letters, digits, and hyphens.', 'wp-cpt-restapi' ); ?>"
            />
            <span class="tooltip">
                <span class="dashicons dashicons-editor-help"></span>
                <span class="tooltip-text">
                    <?php echo esc_html__( 'The base segment defines the namespace for your REST API endpoints. It must be between 1 and 120 characters long and can only contain lowercase letters (a-z), digits (0-9), and hyphens (-).', 'wp-cpt-restapi' ); ?>
                </span>
            </span>
        </div>
        <p class="description">
            <?php echo esc_html__( 'Full REST API URL:', 'wp-cpt-restapi' ); ?> 
            <code id="rest-api-preview"><?php echo esc_url( $rest_url ); ?></code>
        </p>
        <?php
    }

    /**
     * Validate the base segment field.
     *
     * @since    1.0.0
     * @param    string    $input    The input to validate.
     * @return   string              The validated input.
     */
    public function validate_base_segment( $input ) {
        // Check if the input is empty
        if ( empty( $input ) ) {
            add_settings_error(
                $this->option_name,
                'empty_segment',
                __( 'The base segment cannot be empty.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );
        }

        // Check if the input length is between 1 and 120 characters
        if ( strlen( $input ) < 1 || strlen( $input ) > 120 ) {
            add_settings_error(
                $this->option_name,
                'length_error',
                __( 'The base segment must be between 1 and 120 characters long.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );
        }

        // Check if the input contains only allowed characters (lowercase letters, digits, and hyphens)
        if ( ! preg_match( '/^[a-z0-9-]+$/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'invalid_chars',
                __( 'The base segment can only contain lowercase letters, digits, and hyphens.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );
        }

        // If all validations pass, add a success message
        add_settings_error(
            $this->option_name,
            'settings_updated',
            __( 'Settings saved successfully.', 'wp-cpt-restapi' ),
            'updated'
        );

        return $input;
    }

    /**
     * Validate the active CPTs field.
     *
     * @since    1.0.0
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
            __( 'Custom Post Types settings saved successfully.', 'wp-cpt-restapi' ),
            'updated'
        );

        return $validated_cpts;
    }

    /**
     * Get all available public CPTs (excluding core types).
     *
     * @since    1.0.0
     * @return   array    Array of CPT objects keyed by post type name.
     */
    private function get_available_cpts() {
        // Get all public post types
        $post_types = get_post_types( array( 'public' => true ), 'objects' );
        
        // Remove core post types
        $core_types = array( 'post', 'page', 'attachment' );
        foreach ( $core_types as $core_type ) {
            unset( $post_types[ $core_type ] );
        }

        return $post_types;
    }

    /**
     * Render the CPTs section description.
     *
     * @since    1.0.0
     */
    public function cpts_section_callback() {
        echo '<p>' . esc_html__( 'Select which Custom Post Types should be available through the REST API.', 'wp-cpt-restapi' ) . '</p>';
    }

    /**
     * Render the CPTs selection field.
     *
     * @since    1.0.0
     */
    public function cpts_field_callback() {
        // Get available CPTs
        $available_cpts = $this->get_available_cpts();
        
        // Get currently active CPTs
        $active_cpts = get_option( $this->cpt_option_name, array() );
        
        if ( empty( $available_cpts ) ) {
            echo '<p>' . esc_html__( 'No Custom Post Types found. Custom Post Types will appear here once they are registered.', 'wp-cpt-restapi' ) . '</p>';
            return;
        }

        ?>
        <div class="cpt-rest-api-cpts-container">
            <table class="widefat cpt-rest-api-cpts-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__( 'Post Type', 'wp-cpt-restapi' ); ?></th>
                        <th><?php echo esc_html__( 'Description', 'wp-cpt-restapi' ); ?></th>
                        <th><?php echo esc_html__( 'Slug', 'wp-cpt-restapi' ); ?></th>
                        <th><?php echo esc_html__( 'Status', 'wp-cpt-restapi' ); ?></th>
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
                                    <span class="description"><?php echo esc_html__( 'No description available', 'wp-cpt-restapi' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <code><?php echo esc_html( $cpt_name ); ?></code>
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
                                        <?php echo esc_html__( 'Activate', 'wp-cpt-restapi' ); ?>
                                    </span>
                                </label>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cpt-rest-api-cpts-actions">
                <button type="button" class="button cpt-rest-api-reset-cpts">
                    <?php echo esc_html__( 'Reset All', 'wp-cpt-restapi' ); ?>
                </button>
                <p class="description">
                    <?php echo esc_html__( 'Reset All will deactivate all Custom Post Types.', 'wp-cpt-restapi' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Display the settings page content.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'CPT REST API', 'wp-cpt-restapi' ); ?></h1>
            
            <!-- Section 1: API Settings -->
            <h2><?php echo esc_html__( 'API Settings', 'wp-cpt-restapi' ); ?></h2>
            
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields( 'cpt_rest_api_settings' );
                ?>
                
                <!-- REST API Base Segment Section -->
                <h3><?php echo esc_html__( 'REST API Base Segment', 'wp-cpt-restapi' ); ?></h3>
                <p><?php echo esc_html__( 'Configure the base segment for the Custom Post Types REST API.', 'wp-cpt-restapi' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->base_segment_field_callback(); ?>
                </div>
                
                <!-- Custom Post Types Section -->
                <h3><?php echo esc_html__( 'Custom Post Types', 'wp-cpt-restapi' ); ?></h3>
                <p><?php echo esc_html__( 'Select which Custom Post Types should be available through the REST API.', 'wp-cpt-restapi' ); ?></p>
                <div class="cpt-rest-api-field-wrapper">
                    <?php $this->cpts_field_callback(); ?>
                </div>
                
                <?php
                // Output save settings button
                submit_button( __( 'Save Settings', 'wp-cpt-restapi' ) );
                ?>
            </form>
            
            <hr>
            
            <!-- Section 2: API Keys Management -->
            <h2><?php echo esc_html__( 'API Keys Management', 'wp-cpt-restapi' ); ?></h2>
            
            <div class="cpt-rest-api-section-separator">
                <h3><?php echo esc_html__( 'API Keys', 'wp-cpt-restapi' ); ?></h3>
                <p><?php echo esc_html__( 'Create and manage API keys for accessing the REST API endpoints.', 'wp-cpt-restapi' ); ?></p>
                <p><?php echo esc_html__( 'API keys can be used to authenticate requests to the REST API using the Bearer authentication method.', 'wp-cpt-restapi' ); ?></p>
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
     * @since    1.0.0
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
            echo "<h3>{$section['title']}</h3>\n";
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
     * @since    1.0.0
     */
    public function api_keys_section_callback() {
        echo '<p>' . esc_html__( 'Create and manage API keys for accessing the REST API endpoints.', 'wp-cpt-restapi' ) . '</p>';
        echo '<p>' . esc_html__( 'API keys can be used to authenticate requests to the REST API using the Bearer authentication method.', 'wp-cpt-restapi' ) . '</p>';
    }
    
    /**
     * Render the API Keys management field.
     *
     * @since    1.0.0
     */
    public function api_keys_field_callback() {
        // Get all API keys
        $keys = $this->api_keys->get_keys();
        
        ?>
        <div class="cpt-rest-api-keys-container">
            <!-- API Keys List -->
            <div class="cpt-rest-api-keys-list">
                <h3><?php echo esc_html__( 'Your API Keys', 'wp-cpt-restapi' ); ?></h3>
                
                <?php if ( empty( $keys ) ) : ?>
                    <p class="cpt-rest-api-no-keys"><?php echo esc_html__( 'No API keys found. Create your first key below.', 'wp-cpt-restapi' ); ?></p>
                <?php else : ?>
                    <table class="widefat striped">
                        <thead>
                            <tr>
                                <th><?php echo esc_html__( 'Label', 'wp-cpt-restapi' ); ?></th>
                                <th><?php echo esc_html__( 'API Key', 'wp-cpt-restapi' ); ?></th>
                                <th><?php echo esc_html__( 'Created', 'wp-cpt-restapi' ); ?></th>
                                <th><?php echo esc_html__( 'Actions', 'wp-cpt-restapi' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $keys as $key ) : ?>
                                <tr>
                                    <td><?php echo esc_html( $key['label'] ); ?></td>
                                    <td>
                                        <code class="api-key-code"><?php echo esc_html( $key['key'] ); ?></code>
                                    </td>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $key['created_at'] ) ) ); ?></td>
                                    <td>
                                        <button
                                            type="button"
                                            class="button button-small cpt-rest-api-delete-key"
                                            data-id="<?php echo esc_attr( $key['id'] ); ?>"
                                            data-confirm="<?php echo esc_attr__( 'Are you sure you want to delete this API key? This action cannot be undone.', 'wp-cpt-restapi' ); ?>"
                                        >
                                            <?php echo esc_html__( 'Delete', 'wp-cpt-restapi' ); ?>
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
                <h3><?php echo esc_html__( 'Create a New API Key', 'wp-cpt-restapi' ); ?></h3>
                
                <div class="cpt-rest-api-create-key-form">
                    <label for="cpt_rest_api_key_label"><?php echo esc_html__( 'Label', 'wp-cpt-restapi' ); ?></label>
                    <input
                        type="text"
                        id="cpt_rest_api_key_label"
                        name="cpt_rest_api_key_label"
                        placeholder="<?php echo esc_attr__( 'Enter a label for your API key', 'wp-cpt-restapi' ); ?>"
                        required
                    />
                    <p class="description">
                        <?php echo esc_html__( 'A descriptive name to help you identify this key.', 'wp-cpt-restapi' ); ?>
                    </p>
                    
                    <button type="button" class="button button-primary cpt-rest-api-generate-key">
                        <?php echo esc_html__( 'Generate API Key', 'wp-cpt-restapi' ); ?>
                    </button>
                </div>
                
                <div class="cpt-rest-api-key-generated" style="display: none;">
                    <h4><?php echo esc_html__( 'API Key Generated', 'wp-cpt-restapi' ); ?></h4>
                    <p class="description">
                        <?php echo esc_html__( 'Make sure to copy your API key now. You won\'t be able to see it again!', 'wp-cpt-restapi' ); ?>
                    </p>
                    <div class="cpt-rest-api-key-display">
                        <code id="cpt_rest_api_new_key"></code>
                        <button type="button" class="button cpt-rest-api-copy-key">
                            <?php echo esc_html__( 'Copy', 'wp-cpt-restapi' ); ?>
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
     * @since    1.0.0
     */
    public function ajax_add_key() {
        // Check nonce
        check_ajax_referer( 'cpt_rest_api', 'nonce' );
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-cpt-restapi' ) ) );
        }
        
        // Get the label from the request
        $label = isset( $_POST['label'] ) ? sanitize_text_field( wp_unslash( $_POST['label'] ) ) : '';
        
        // Validate the label
        if ( empty( $label ) ) {
            wp_send_json_error( array( 'message' => __( 'Label is required.', 'wp-cpt-restapi' ) ) );
        }
        
        // Add the new key
        $new_key = $this->api_keys->add_key( $label );
        
        if ( $new_key ) {
            wp_send_json_success( array(
                'key' => $new_key,
                'message' => __( 'API key created successfully.', 'wp-cpt-restapi' ),
            ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to create API key.', 'wp-cpt-restapi' ) ) );
        }
    }
    
    /**
     * AJAX handler for deleting an API key.
     *
     * @since    1.0.0
     */
    public function ajax_delete_key() {
        // Check nonce
        check_ajax_referer( 'cpt_rest_api', 'nonce' );
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-cpt-restapi' ) ) );
        }
        
        // Get the key ID from the request
        $key_id = isset( $_POST['id'] ) ? sanitize_text_field( wp_unslash( $_POST['id'] ) ) : '';
        
        // Validate the key ID
        if ( empty( $key_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Key ID is required.', 'wp-cpt-restapi' ) ) );
        }
        
        // Delete the key
        $deleted = $this->api_keys->delete_key( $key_id );
        
        if ( $deleted ) {
            wp_send_json_success( array( 'message' => __( 'API key deleted successfully.', 'wp-cpt-restapi' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to delete API key.', 'wp-cpt-restapi' ) ) );
        }
    }

    /**
     * AJAX handler for resetting CPTs.
     *
     * @since    1.0.0
     */
    public function ajax_reset_cpts() {
        // Check nonce
        check_ajax_referer( 'cpt_rest_api', 'nonce' );
        
        // Check user capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'You do not have permission to perform this action.', 'wp-cpt-restapi' ) ) );
        }
        
        // Reset CPTs by saving an empty array
        $updated = update_option( $this->cpt_option_name, array() );
        
        if ( $updated !== false ) {
            wp_send_json_success( array( 'message' => __( 'All Custom Post Types have been deactivated.', 'wp-cpt-restapi' ) ) );
        } else {
            wp_send_json_error( array( 'message' => __( 'Failed to reset Custom Post Types.', 'wp-cpt-restapi' ) ) );
        }
    }
}