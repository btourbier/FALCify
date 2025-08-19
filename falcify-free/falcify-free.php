<?php
/**
 * Plugin Name:       FALCify Free
 * Plugin URI:        https://github.com/your-account/falcify-free
 * Description:       Ajoute une version FALC (Facile À Lire et À Comprendre) des contenus WordPress avec bascule front accessible. Version gratuite (MVP).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            TECHNIWEB - https://www.techniweb-agence.fr/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       falcify-free
 * Domain Path:       /languages
 *
 * @package Falcify_Free
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'FALCIFY_FREE_VERSION', '1.0.0' );
define( 'FALCIFY_FREE_FILE', __FILE__ );
define( 'FALCIFY_FREE_DIR', plugin_dir_path( __FILE__ ) );
define( 'FALCIFY_FREE_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load textdomain.
 */
function falcify_free_load_textdomain() {
	load_plugin_textdomain( 'falcify-free', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'falcify_free_load_textdomain' );

// Includes.
require_once FALCIFY_FREE_DIR . 'includes/class-falcify-free-admin.php';
require_once FALCIFY_FREE_DIR . 'includes/class-falcify-free-frontend.php';

/**
 * Initialize plugin modules.
 */
function falcify_free_init() {
	\Falcify_Free\Admin::init();
	\Falcify_Free\Frontend::init();
}
add_action( 'plugins_loaded', 'falcify_free_init', 20 );
