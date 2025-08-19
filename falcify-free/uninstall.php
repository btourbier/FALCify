<?php
/**
 * Uninstall cleanup for FALCify Free.
 *
 * @package Falcify_Free
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Delete option.
delete_option( 'falcify_free_settings' );

// Clean post meta.
global $wpdb;
/* phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching */
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s", '_falcify_falc' ) );
