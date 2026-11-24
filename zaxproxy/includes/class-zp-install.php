<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_Install {

	const DB_VERSION = '4';

	public static function activate() {
		self::tables();
		add_role( 'zaxproxy_customer', 'ZaxProxy Customer', array( 'read' => true ) );
		if ( false === get_option( 'zaxproxy_settings' ) ) {
			add_option( 'zaxproxy_settings', zp_defaults(), '', false );
		}
		if ( ! get_option( 'permalink_structure' ) ) {
			update_option( 'permalink_structure', '/%postname%/' );
		}
		flush_rewrite_rules( true );
		if ( ! wp_next_scheduled( 'zp_sync_cron' ) ) {
			wp_schedule_event( time() + 300, 'zp_ten_minutes', 'zp_sync_cron' );
		}
		update_option( 'zaxproxy_db_version', self::DB_VERSION, false );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'zp_sync_cron' );
	}

	public static function maybe_upgrade() {
		if ( get_option( 'zaxproxy_db_version' ) !== self::DB_VERSION ) {
			self::activate();
		}
	}

	public static function cron_schedules( $schedules ) {
		$schedules['zp_ten_minutes'] = array( 'interval' => 600, 'display' => 'Every 10 minutes (ZaxProxy)' );
		return $schedules;
	}

	private static function tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();

		dbDelta(
			'CREATE TABLE ' . zp_table( 'wallets' ) . " (
			user_id bigint(20) unsigned NOT NULL,
			balance decimal(14,2) NOT NULL DEFAULT 0.00,
			PRIMARY KEY  (user_id)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'transactions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			type varchar(20) NOT NULL,
			amount decimal(14,2) NOT NULL,
			balance_after decimal(14,2) NOT NULL DEFAULT 0.00,
			reference varchar(100) NOT NULL DEFAULT '',
			description varchar(255) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'deposits' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			gateway varchar(30) NOT NULL,
			amount decimal(14,2) NOT NULL,
			gw_amount decimal(14,2) NOT NULL DEFAULT 0.00,
			gw_currency varchar(10) NOT NULL DEFAULT 'NGN',
			reference varchar(100) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			note text NULL,
			admin_note text NULL,
			reseller_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY reference (reference),
			KEY user_id (user_id),
			KEY status (status)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'orders' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			kind varchar(10) NOT NULL DEFAULT 'purchase',
			proxy_type varchar(10) NOT NULL,
			country varchar(10) NOT NULL DEFAULT '',
			days int(11) NOT NULL DEFAULT 0,
			qty int(11) NOT NULL DEFAULT 0,
			goal varchar(100) NOT NULL DEFAULT '',
			auth_type varchar(10) NOT NULL DEFAULT 'login',
			auth_ip varchar(64) NOT NULL DEFAULT '',
			protocol varchar(10) NOT NULL DEFAULT '',
			mobile_operator varchar(100) NOT NULL DEFAULT '',
			rotation_time int(11) NOT NULL DEFAULT 0,
			cost decimal(14,2) NOT NULL DEFAULT 0.00,
			fx_rate decimal(14,4) NOT NULL DEFAULT 1.0000,
			amount decimal(14,2) NOT NULL DEFAULT 0.00,
			status varchar(20) NOT NULL DEFAULT 'pending',
			refunded tinyint(1) NOT NULL DEFAULT 0,
			reseller_id bigint(20) unsigned NOT NULL DEFAULT 0,
			reseller_cost decimal(14,2) NOT NULL DEFAULT 0.00,
			upstream_order varchar(100) NOT NULL DEFAULT '',
			pre_ids longtext NULL,
			error text NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'proxies' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			order_id bigint(20) unsigned NOT NULL DEFAULT 0,
			upstream_id varchar(100) NOT NULL,
			upstream_order_id varchar(100) NOT NULL DEFAULT '',
			proxy_type varchar(10) NOT NULL,
			ip varchar(100) NOT NULL DEFAULT '',
			country varchar(10) NOT NULL DEFAULT '',
			https_port varchar(10) NOT NULL DEFAULT '',
			socks5_port varchar(10) NOT NULL DEFAULT '',
			protocol varchar(10) NOT NULL DEFAULT '',
			login varchar(100) NOT NULL DEFAULT '',
			password varchar(100) NOT NULL DEFAULT '',
			mobile_operator varchar(100) NOT NULL DEFAULT '',
			rotation_time int(11) NOT NULL DEFAULT 0,
			reboot_link varchar(255) NOT NULL DEFAULT '',
			date_start datetime NULL,
			date_end datetime NULL,
			note varchar(190) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY upstream (proxy_type,upstream_id),
			KEY user_id (user_id),
			KEY order_id (order_id)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'tickets' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			subject varchar(190) NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'open',
			reseller_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY status (status)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'resellers' ) . " (
			user_id bigint(20) unsigned NOT NULL,
			slug varchar(40) NOT NULL,
			brand varchar(60) NOT NULL,
			markup decimal(7,2) NOT NULL DEFAULT 0.00,
			bank_name varchar(80) NOT NULL DEFAULT '',
			bank_account_name varchar(120) NOT NULL DEFAULT '',
			bank_account_number varchar(40) NOT NULL DEFAULT '',
			telegram varchar(80) NOT NULL DEFAULT '',
			status varchar(20) NOT NULL DEFAULT 'active',
			paid_until datetime NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (user_id),
			UNIQUE KEY slug (slug)
		) $c;"
		);

		dbDelta(
			'CREATE TABLE ' . zp_table( 'ticket_messages' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			ticket_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
			is_admin tinyint(1) NOT NULL DEFAULT 0,
			message text NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY ticket_id (ticket_id)
		) $c;"
		);
	}
}
