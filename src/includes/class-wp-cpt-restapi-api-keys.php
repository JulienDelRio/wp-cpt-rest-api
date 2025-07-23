<?php
/**
 * The API Keys management functionality of the plugin.
 *
 * @since      0.1
 * @package    WP_CPT_RestAPI
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * The API Keys management functionality of the plugin.
 *
 * Handles the creation, storage, retrieval, and deletion of API keys.
 */
class WP_CPT_RestAPI_API_Keys {

    /**
     * The option name for storing API keys.
     *
     * @since    0.1
     * @access   private
     * @var      string    $option_name    The option name for storing API keys.
     */
    private $option_name = 'cpt_rest_api_keys';

    /**
     * Initialize the class and set its properties.
     *
     * @since    0.1
     */
    public function __construct() {
        // Constructor code
    }

    /**
     * Runs on plugin activation.
     * 
     * Creates the option for storing API keys if it doesn't exist.
     *
     * @since    0.1
     */
    public function activate() {
        if ( ! get_option( $this->option_name ) ) {
            add_option( $this->option_name, array() );
        }
    }

    /**
     * Generate a new API key.
     *
     * Generates a random string that meets the required format:
     * - 32 characters long (for better security)
     * - Contains lowercase letters (a-z), digits (0-9), and hyphens (-)
     * - Uses cryptographically secure random generation
     *
     * @since    0.1
     * @return   string    The generated API key.
     */
    public function generate_key() {
        $length = 32;
        
        // Define character sets (following current validation rules)
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $digits = '0123456789';
        $special = '-';
        
        // Combine all allowed characters
        $all_chars = $lowercase . $digits . $special;
        
        // Generate a cryptographically secure random key
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $all_chars[wp_rand(0, strlen($all_chars) - 1)];
        }
        
        // Ensure the key contains at least one of each required type
        // If not, replace some characters to meet requirements
        if (!preg_match('/[a-z]/', $key)) {
            $key[0] = $lowercase[wp_rand(0, strlen($lowercase) - 1)];
        }
        if (!preg_match('/[0-9]/', $key)) {
            $key[1] = $digits[wp_rand(0, strlen($digits) - 1)];
        }
        if (!preg_match('/[-]/', $key)) {
            $key[2] = '-';
        }
        
        return $key;
    }
    
    /**
     * Get a random character from a string.
     *
     * @since    0.1
     * @param    string    $chars    The string of characters to choose from.
     * @return   string              A random character from the string.
     */
    private function get_random_char($chars) {
        $index = rand(0, strlen($chars) - 1);
        return $chars[$index];
    }

    /**
     * Add a new API key.
     *
     * @since    0.1
     * @param    string    $label    The label for the API key.
     * @return   array               The newly created API key data, or false on failure.
     */
    public function add_key($label) {
        if (empty($label)) {
            return false;
        }
        
        $keys = $this->get_keys();
        $key = $this->generate_key();
        
        // Create a new key entry
        $new_key = array(
            'id'         => uniqid('key_'),
            'label'      => sanitize_text_field($label),
            'key'        => $key,
            'created_at' => current_time('mysql'),
        );
        
        $keys[] = $new_key;
        
        // Update the option
        $updated = update_option($this->option_name, $keys);
        
        return $updated ? $new_key : false;
    }

    /**
     * Get all API keys.
     *
     * @since    0.1
     * @return   array    The list of API keys.
     */
    public function get_keys() {
        $keys = get_option($this->option_name, array());
        return is_array($keys) ? $keys : array();
    }

    /**
     * Get a specific API key by ID.
     *
     * @since    0.1
     * @param    string    $id    The ID of the API key.
     * @return   array            The API key data, or false if not found.
     */
    public function get_key($id) {
        $keys = $this->get_keys();
        
        foreach ($keys as $key) {
            if ($key['id'] === $id) {
                return $key;
            }
        }
        
        return false;
    }

    /**
     * Delete an API key.
     *
     * @since    0.1
     * @param    string    $id    The ID of the API key to delete.
     * @return   boolean          True if the key was deleted, false otherwise.
     */
    public function delete_key($id) {
        $keys = $this->get_keys();
        $found = false;
        
        foreach ($keys as $index => $key) {
            if ($key['id'] === $id) {
                unset($keys[$index]);
                $found = true;
                break;
            }
        }
        
        if ($found) {
            // Reindex the array
            $keys = array_values($keys);
            return update_option($this->option_name, $keys);
        }
        
        return false;
    }

    /**
     * Validate an API key.
     *
     * @since    0.1
     * @param    string    $key    The API key to validate.
     * @return   boolean           True if the key is valid, false otherwise.
     */
    public function validate_key($key) {
        $keys = $this->get_keys();
        
        foreach ($keys as $key_data) {
            if ($key_data['key'] === $key) {
                return true;
            }
        }
        
        return false;
    }
}