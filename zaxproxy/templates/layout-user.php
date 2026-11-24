<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $view @var WP_User $user @var float $balance */
$titles = array(
	'dashboard' => 'Dashboard',
	'buy'       => 'Buy proxies',
	'proxies'   => 'My proxies',
	'orders'    => 'Orders',
	'wallet'    => 'Wallet',
	'tickets'   => 'Support',
	'reseller'  => 'Reseller',
	'profile'   => 'Account',
	'help'      => 'Help center',
);
$nav = array(
	'dashboard' => array( 'Dashboard', 'grid' ),
	'buy'       => array( 'Buy proxies', 'cart' ),
	'proxies'   => array( 'My proxies', 'globe' ),
	'orders'    => array( 'Orders', 'receipt' ),
	'wallet'    => array( 'Wallet', 'wallet' ),
	'tickets'   => array( 'Support', 'ticket' ),
	'reseller'  => array( 'Reseller', 'users' ),
	'help'      => array( 'Help center', 'help' ),
	'profile'   => array( 'Account', 'user' ),
);
$tabs = array(
	'dashboard' => array( 'Home', 'home' ),
	'proxies'   => array( 'Proxies', 'globe' ),
	'buy'       => array( 'Buy', 'plus' ),
	'wallet'    => array( 'Wallet', 'wallet' ),
);
if ( zp_reseller_ctx() ) {
	unset( $nav['reseller'] ); // customers of a reseller's store cannot open stores of their own
}
$shell = array(
	'admin'    => false,
	'title'    => $titles[ $view ],
	'nav'      => $nav,
	'tabs'     => $tabs,
	'fab'      => 'buy',
	'url'      => 'zp_url',
	'template' => ZP_DIR . 'templates/user/' . $view . '.php',
	'chip'     => '<a class="zp-chip" href="' . esc_url( zp_url( 'wallet' ) ) . '" title="Wallet balance">' . zp_icon( 'wallet', 16 ) . '<span>' . esc_html( zp_money( $balance ) ) . '</span></a>',
);
include ZP_DIR . 'templates/shell.php';
