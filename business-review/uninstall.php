<?php
/**
 * Business Review – uninstall cleanup.
 *
 * Removes the plugin's settings, cached data and (optionally) its custom
 * posts when the plugin is deleted from the WordPress admin. This ensures
 * that stored API keys / access tokens do not linger in the database.
 *
 * @package business-review
 */

// Only run when WordPress is uninstalling this plugin.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete all plugin data for the current site.
 */
function grbb_uninstall_cleanup() {
	// Options (includes grbb_apis, which stores Google/Yelp keys and FB tokens).
	$options = array(
		'grbb_apis',
		'grbb_api_errors',
		'grbb_is_premium',
	);
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Cached review data.
	$transients = array(
		'grbb_yelp_reviews',
		'grbb_google_reviews',
		'grbb_fb_reviews',
	);
	foreach ( $transients as $transient ) {
		delete_transient( $transient );
	}

	// Only remove user-created review posts when the site opted in.
	if ( get_option( 'grbb_delete_data_on_uninstall' ) ) {
		$posts = get_posts(
			array(
				'post_type'        => 'grbb',
				'post_status'      => 'any',
				'numberposts'      => -1,
				'fields'           => 'ids',
				'suppress_filters' => true,
			)
		);
		foreach ( $posts as $post_id ) {
			wp_delete_post( $post_id, true );
		}
	}

	delete_option( 'grbb_delete_data_on_uninstall' );
}

if ( is_multisite() ) {
	$site_ids = get_sites( array( 'fields' => 'ids' ) );
	foreach ( $site_ids as $site_id ) {
		switch_to_blog( $site_id );
		grbb_uninstall_cleanup();
		restore_current_blog();
	}
} else {
	grbb_uninstall_cleanup();
}
