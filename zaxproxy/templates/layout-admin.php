<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $view @var WP_User $user */
global $wpdb;
$pending_deposits = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . zp_table( 'deposits' ) . " WHERE status = 'pending' AND gateway = 'manual'" );
$open_tickets     = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . zp_table( 'tickets' ) . " WHERE status = 'open'" );
$review_orders    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . zp_table( 'orders' ) . " WHERE status = 'review'" );

$nav = array(
	'dashboard'    => array( 'Overview', 'chart' ),
	'pricing'      => array( 'Pricing', 'zap' ),
	'orders'       => array( 'Orders', 'receipt', $review_orders ),
	'proxies'      => array( 'Proxies', 'globe' ),
	'users'        => array( 'Customers', 'users' ),
	'deposits'     => array( 'Deposits', 'card', $pending_deposits ),
	'transactions' => array( 'Transactions', 'swap' ),
	'tickets'      => array( 'Tickets', 'ticket', $open_tickets ),
	'settings'     => array( 'Settings', 'settings' ),
);
$tabs = array(
	'dashboard' => array( 'Overview', 'chart' ),
	'orders'    => array( 'Orders', 'receipt' ),
	'deposits'  => array( 'Deposits', 'card' ),
	'users'     => array( 'Customers', 'users' ),
);
$shell = array(
	'admin'    => true,
	'title'    => $nav[ $view ][0],
	'nav'      => $nav,
	'tabs'     => $tabs,
	'fab'      => '',
	'url'      => 'zp_admin_url',
	'template' => ZP_DIR . 'templates/admin/' . $view . '.php',
	'chip'     => '<span class="zp-chip zp-chip--admin">' . zp_icon( 'shield', 16 ) . '<span>Admin</span></span>',
);
include ZP_DIR . 'templates/shell.php';
