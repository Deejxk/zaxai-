<?php
/**
 * Plugin Name: ZaxProxy
 * Description: Standalone proxy store: customers buy, manage and extend IPv4 / IPv6 / ISP / mobile proxies (proxy-ipv4.com API) from a dedicated user panel, with a separate admin panel outside wp-admin.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Author: ZaxProxy
 * License: GPL-2.0-or-later
 * Text Domain: zaxproxy
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ZP_VERSION', '1.0.0' );
define( 'ZP_FILE', __FILE__ );
define( 'ZP_DIR', plugin_dir_path( __FILE__ ) );
define( 'ZP_URL', plugin_dir_url( __FILE__ ) );
define( 'ZP_USER_BASE', 'panel' );
define( 'ZP_ADMIN_BASE', 'zax-admin' );

require_once ZP_DIR . 'includes/helpers.php';
require_once ZP_DIR . 'includes/class-zp-install.php';
require_once ZP_DIR . 'includes/class-zp-api.php';
require_once ZP_DIR . 'includes/class-zp-wallet.php';
require_once ZP_DIR . 'includes/class-zp-orders.php';
require_once ZP_DIR . 'includes/class-zp-tickets.php';
require_once ZP_DIR . 'includes/class-zp-gateways.php';
require_once ZP_DIR . 'includes/class-zp-user-actions.php';
require_once ZP_DIR . 'includes/class-zp-admin-actions.php';
require_once ZP_DIR . 'includes/class-zp-router.php';

register_activation_hook( __FILE__, array( 'ZP_Install', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ZP_Install', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'ZP_Install', 'maybe_upgrade' ) );
add_action( 'init', array( 'ZP_Router', 'dispatch' ), 20 );
add_action( 'zp_sync_cron', array( 'ZP_Orders', 'cron_sync' ) );
add_filter( 'cron_schedules', array( 'ZP_Install', 'cron_schedules' ) );

// Keep customers out of wp-admin entirely; they live in the user panel.
add_action(
	'admin_init',
	function () {
		if ( wp_doing_ajax() || ! is_user_logged_in() ) {
			return;
		}
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_safe_redirect( zp_url( 'dashboard' ) );
			exit;
		}
	}
);
add_filter(
	'show_admin_bar',
	function ( $show ) {
		return current_user_can( 'edit_posts' ) ? $show : false;
	}
);

// Shortcut inside wp-admin to the standalone admin panel.
add_action(
	'admin_menu',
	function () {
		add_menu_page( 'ZaxProxy', 'ZaxProxy', 'manage_options', 'zaxproxy', '__return_null', 'dashicons-shield', 3 );
	}
);
add_action(
	'admin_init',
	function () {
		if ( isset( $_GET['page'] ) && 'zaxproxy' === $_GET['page'] && current_user_can( 'manage_options' ) ) { // phpcs:ignore
			wp_safe_redirect( zp_admin_url() );
			exit;
		}
	}
);
add_filter(
	'plugin_action_links_' . plugin_basename( __FILE__ ),
	function ( $links ) {
		array_unshift( $links, '<a href="' . esc_url( zp_admin_url() ) . '">Open admin panel</a>' );
		return $links;
	}
);
