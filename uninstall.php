<?php
/**
 * Removes the plugin's stored settings. Gallery folders and generated
 * thumbnails are user content and are deliberately left untouched.
 *
 * @package easy-folder-gallery
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'efg_settings' );
