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
        // Register the setting
        register_setting(
            'cpt_rest_api_settings',                  // Option group
            $this->option_name,                       // Option name
            array( $this, 'validate_base_segment' )   // Sanitize callback
        );

        // Add settings section
        add_settings_section(
            'cpt_rest_api_section',                   // ID
            __( 'REST API Settings', 'wp-cpt-restapi' ), // Title
            array( $this, 'settings_section_callback' ), // Callback
            'cpt-rest-api'                            // Page
        );

        // Add settings field
        add_settings_field(
            'cpt_rest_api_base_segment',              // ID
            __( 'API Base Segment', 'wp-cpt-restapi' ), // Title
            array( $this, 'base_segment_field_callback' ), // Callback
            'cpt-rest-api',                           // Page
            'cpt_rest_api_section'                    // Section
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
                   pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[_-])[a-zA-Z0-9_-]{20}$"
                   title="<?php echo esc_attr__( 'Must be exactly 20 characters long and contain at least one lowercase letter, one uppercase letter, one digit, one underscore, and one dash.', 'wp-cpt-restapi' ); ?>"
            />
            <span class="tooltip">
                <span class="dashicons dashicons-editor-help"></span>
                <span class="tooltip-text">
                    <?php echo esc_html__( 'The base segment defines the namespace for your REST API endpoints. It must be exactly 20 characters long and contain at least one lowercase letter, one uppercase letter, one digit, one underscore, and one dash.', 'wp-cpt-restapi' ); ?>
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

        // Check if the input is exactly 20 characters long
        if ( strlen( $input ) !== 20 ) {
            add_settings_error(
                $this->option_name,
                'length_error',
                __( 'The base segment must be exactly 20 characters long.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );

        }

        // Check if the input contains at least one lowercase letter
        if ( ! preg_match( '/[a-z]/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'lowercase_error',
                __( 'The base segment must contain at least one lowercase letter.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );

        }

        // Check if the input contains at least one uppercase letter
        if ( ! preg_match( '/[A-Z]/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'uppercase_error',
                __( 'The base segment must contain at least one uppercase letter.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );

        }

        // Check if the input contains at least one digit
        if ( ! preg_match( '/\d/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'digit_error',
                __( 'The base segment must contain at least one digit.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );

        }

        // Check if the input contains at least one underscore
        if ( ! preg_match( '/_/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'underscore_error',
                __( 'The base segment must contain at least one underscore.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );

        }

        // Check if the input contains at least one dash
        if ( ! preg_match( '/-/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'dash_error',
                __( 'The base segment must contain at least one dash.', 'wp-cpt-restapi' ),
                'error'
            );
            return get_option( $this->option_name, $this->default_segment );

        }

        // Check if the input contains only allowed characters
        if ( ! preg_match( '/^[a-zA-Z0-9_-]+$/', $input ) ) {
            add_settings_error(
                $this->option_name,
                'invalid_chars',
                __( 'The base segment can only contain letters, numbers, underscores, and dashes.', 'wp-cpt-restapi' ),
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
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                // Output security fields
                settings_fields( 'cpt_rest_api_settings' );
                
                // Output setting sections and their fields
                do_settings_sections( 'cpt-rest-api' );
                
                // Output save settings button
                submit_button( __( 'Save Settings', 'wp-cpt-restapi' ) );
                ?>
            </form>
        </div>
        <?php
    }
}