<?php
/**
 * Plugin Name:       Easy Folder Gallery
 * Plugin URI:        https://github.com/el-seirh/easy-folder-gallery
 * Description:       A file-based photo gallery: your folders are your albums and galleries. No database entries, no upload UI — just folders of images.
 * Version:           0.1.3
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            Luba McLean
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       easy-folder-gallery
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EFG_VERSION', '0.1.3' );
define( 'EFG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EFG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once EFG_PLUGIN_DIR . 'includes/class-efg-settings.php';
require_once EFG_PLUGIN_DIR . 'includes/class-efg-renderer.php';

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'easy-folder-gallery', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
);

EFG_Settings::register();
EFG_Renderer::register();
