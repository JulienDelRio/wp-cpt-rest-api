<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @link       https://github.com/JulienDelRio/wp-cpt-rest-api
 * @since      0.2
 *
 * @package    WP_CPT_RestAPI
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete all plugin options
delete_option( 'cpt_rest_api_base_segment' );
delete_option( 'cpt_rest_api_active_cpts' );
delete_option( 'cpt_rest_api_keys' );
delete_option( 'cpt_rest_api_toolset_relationships' );
delete_option( 'cpt_rest_api_include_nonpublic_cpts' );

// For multisite installations
if ( is_multisite() ) {
	$blog_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $blog_ids as $blog_id ) {
		switch_to_blog( $blog_id );
		delete_option( 'cpt_rest_api_base_segment' );
		delete_option( 'cpt_rest_api_active_cpts' );
		delete_option( 'cpt_rest_api_keys' );
		delete_option( 'cpt_rest_api_toolset_relationships' );
		delete_option( 'cpt_rest_api_include_nonpublic_cpts' );
		restore_current_blog();
	}
}
