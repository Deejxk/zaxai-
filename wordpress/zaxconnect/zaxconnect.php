<?php
/**
 * Plugin Name:       ZaxConnect – Hotspot Billing SaaS
 * Plugin URI:        https://zaxconnect.cloud
 * Description:       Run a Wi-Fi hotspot billing platform on WordPress: hotspot owners sign up, brand their own captive portal, sell plans with Flutterwave and manage MikroTik routers from their own dashboard (separate from wp-admin).
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            ZaxConnect
 * License:           GPL-2.0-or-later
 * Text Domain:       zaxconnect
 */

defined( 'ABSPATH' ) || exit;

define( 'ZXC_VERSION', '1.0.0' );
define( 'ZXC_DB_VERSION', '1' );
define( 'ZXC_FILE', __FILE__ );
define( 'ZXC_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZXC_URL', plugin_dir_url( __FILE__ ) );

require_once ZXC_DIR . 'includes/core.php';
require_once ZXC_DIR . 'includes/model.php';
require_once ZXC_DIR . 'includes/payments.php';
require_once ZXC_DIR . 'includes/router.php';
require_once ZXC_DIR . 'includes/routes.php';
require_once ZXC_DIR . 'views/layout.php';
require_once ZXC_DIR . 'views/marketing.php';
require_once ZXC_DIR . 'views/portal.php';
require_once ZXC_DIR . 'views/dashboard.php';
require_once ZXC_DIR . 'views/platform.php';

register_activation_hook( __FILE__, 'zxc_activate' );
register_deactivation_hook( __FILE__, 'zxc_deactivate' );

function zxc_activate() {
	zxc_install_db();
	zxc_add_role();
	// Pretty links are required for /signup, /dashboard and /h/{slug}.
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}
	zxc_register_rewrites();
	flush_rewrite_rules();
	if ( ! wp_next_scheduled( 'zxc_housekeeping' ) ) {
		wp_schedule_event( time() + 60, 'hourly', 'zxc_housekeeping' );
	}
}

function zxc_deactivate() {
	wp_clear_scheduled_hook( 'zxc_housekeeping' );
	flush_rewrite_rules();
}
