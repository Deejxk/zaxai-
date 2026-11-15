<?php
/**
 * Core: settings, database schema, formatting helpers.
 */
defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Platform settings (stored in one option)
 * ---------------------------------------------------------------------- */

function zxc_settings_defaults() {
	return array(
		'platform_name'    => 'ZaxConnect',
		'support_email'    => '',
		'flw_public'       => '', // platform Flutterwave account: collects owners' subscription fees
		'flw_secret'       => '',
		'flw_hash'         => '',
		'demo_billing'     => '1',
		'price_starter'    => '5000',
		'price_business'   => '15000',
		'price_enterprise' => '45000',
		'trial_days'       => '14',
		'takeover_home'    => '1', // show the ZaxConnect landing page as the site homepage
		'support_phone'    => '',
		'support_whatsapp' => '',
		'address'          => 'Lagos, Nigeria',
	);
}

function zxc_settings() {
	static $cache = null;
	if ( null === $cache || doing_action( 'zxc_settings_changed' ) ) {
		$cache = wp_parse_args( (array) get_option( 'zxc_settings', array() ), zxc_settings_defaults() );
	}
	return $cache;
}

function zxc_setting( $key ) {
	$s = zxc_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : '';
}

function zxc_update_settings( array $values ) {
	$current = (array) get_option( 'zxc_settings', array() );
	update_option( 'zxc_settings', array_merge( $current, $values ), false );
	do_action( 'zxc_settings_changed' );
	zxc_settings();
}

/* -------------------------------------------------------------------------
 * Database
 * ---------------------------------------------------------------------- */

function zxc_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'zx_' . $name;
}

function zxc_install_db() {
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$c = $wpdb->get_charset_collate();
	$t = 'zxc_table';

	dbDelta( "CREATE TABLE {$t('tenants')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  owner_id bigint(20) unsigned NOT NULL,
  slug varchar(60) NOT NULL,
  name varchar(80) NOT NULL,
  tagline varchar(160) NOT NULL DEFAULT '',
  brand_color varchar(7) NOT NULL DEFAULT '#4F3BF0',
  logo_url varchar(500) NOT NULL DEFAULT '',
  support_phone varchar(30) NOT NULL DEFAULT '',
  support_whatsapp varchar(30) NOT NULL DEFAULT '',
  location varchar(120) NOT NULL DEFAULT '',
  hotspot_dns varchar(120) NOT NULL DEFAULT 'login.wifi',
  flw_public varchar(200) NOT NULL DEFAULT '',
  flw_secret varchar(200) NOT NULL DEFAULT '',
  flw_hash varchar(200) NOT NULL DEFAULT '',
  demo_payments tinyint(1) NOT NULL DEFAULT 1,
  tier varchar(20) NOT NULL DEFAULT 'business',
  status varchar(20) NOT NULL DEFAULT 'active',
  trial_ends bigint(20) NOT NULL DEFAULT 0,
  sub_ends bigint(20) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY slug (slug),
  KEY owner_id (owner_id)
) $c;" );

	dbDelta( "CREATE TABLE {$t('routers')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  name varchar(80) NOT NULL,
  token varchar(40) NOT NULL,
  ack_id bigint(20) unsigned NOT NULL DEFAULT 0,
  last_seen bigint(20) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY token (token),
  KEY tenant_id (tenant_id)
) $c;" );

	dbDelta( "CREATE TABLE {$t('plans')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  name varchar(80) NOT NULL,
  description varchar(160) NOT NULL DEFAULT '',
  price int(11) NOT NULL DEFAULT 0,
  duration_min int(11) NOT NULL DEFAULT 60,
  data_mb int(11) NOT NULL DEFAULT 0,
  download_mbps decimal(8,2) NOT NULL DEFAULT 0,
  upload_mbps decimal(8,2) NOT NULL DEFAULT 0,
  devices int(11) NOT NULL DEFAULT 1,
  popular tinyint(1) NOT NULL DEFAULT 0,
  active tinyint(1) NOT NULL DEFAULT 1,
  sort_order int(11) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY tenant_id (tenant_id)
) $c;" );

	dbDelta( "CREATE TABLE {$t('vouchers')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  code varchar(16) NOT NULL,
  plan_id bigint(20) unsigned NOT NULL,
  source varchar(20) NOT NULL DEFAULT 'online',
  batch varchar(40) DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'unused',
  phone varchar(30) DEFAULT NULL,
  mac varchar(32) DEFAULT NULL,
  bytes_used bigint(20) unsigned NOT NULL DEFAULT 0,
  activated_at bigint(20) NOT NULL DEFAULT 0,
  expires_at bigint(20) NOT NULL DEFAULT 0,
  ended_at bigint(20) NOT NULL DEFAULT 0,
  last_seen bigint(20) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY code (code),
  KEY tenant_status (tenant_id,status),
  KEY batch (batch)
) $c;" );

	dbDelta( "CREATE TABLE {$t('transactions')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  reference varchar(40) NOT NULL,
  plan_id bigint(20) unsigned NOT NULL,
  amount int(11) NOT NULL,
  phone varchar(30) DEFAULT NULL,
  email varchar(190) DEFAULT NULL,
  mac varchar(32) DEFAULT NULL,
  login_url varchar(300) DEFAULT NULL,
  dst varchar(500) DEFAULT NULL,
  gateway varchar(20) NOT NULL DEFAULT 'flutterwave',
  status varchar(20) NOT NULL DEFAULT 'pending',
  voucher_id bigint(20) unsigned DEFAULT NULL,
  channel varchar(40) DEFAULT NULL,
  paid_at bigint(20) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY reference (reference),
  KEY tenant_status (tenant_id,status,paid_at)
) $c;" );

	dbDelta( "CREATE TABLE {$t('subscriptions')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  reference varchar(40) NOT NULL,
  tier varchar(20) NOT NULL,
  amount int(11) NOT NULL,
  months int(11) NOT NULL DEFAULT 1,
  gateway varchar(20) NOT NULL DEFAULT 'flutterwave',
  status varchar(20) NOT NULL DEFAULT 'pending',
  paid_at bigint(20) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY reference (reference),
  KEY tenant_id (tenant_id)
) $c;" );

	dbDelta( "CREATE TABLE {$t('online')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  router_id bigint(20) unsigned NOT NULL,
  code varchar(16) NOT NULL,
  mac varchar(32) NOT NULL DEFAULT '',
  bytes bigint(20) unsigned NOT NULL DEFAULT 0,
  first_seen bigint(20) NOT NULL DEFAULT 0,
  seen_at bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY router_code_mac (router_id,code,mac),
  KEY tenant_seen (tenant_id,seen_at)
) $c;" );

	dbDelta( "CREATE TABLE {$t('messages')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned DEFAULT NULL,
  from_type varchar(20) NOT NULL DEFAULT 'visitor',
  name varchar(120) NOT NULL DEFAULT '',
  email varchar(190) NOT NULL DEFAULT '',
  phone varchar(40) NOT NULL DEFAULT '',
  subject varchar(190) NOT NULL DEFAULT '',
  body text NOT NULL,
  is_read tinyint(1) NOT NULL DEFAULT 0,
  created bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  KEY tenant_read (tenant_id,is_read)
) $c;" );

	dbDelta( "CREATE TABLE {$t('wallet')} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  tenant_id bigint(20) unsigned NOT NULL,
  type varchar(20) NOT NULL DEFAULT 'deposit',
  amount bigint(20) NOT NULL DEFAULT 0,
  reference varchar(40) DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'pending',
  gateway varchar(20) NOT NULL DEFAULT 'flutterwave',
  channel varchar(40) DEFAULT NULL,
  note varchar(190) NOT NULL DEFAULT '',
  created bigint(20) NOT NULL DEFAULT 0,
  completed bigint(20) NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY reference (reference),
  KEY tenant_status (tenant_id,status)
) $c;" );

	update_option( 'zxc_db_version', ZXC_DB_VERSION, false );
}

add_action( 'plugins_loaded', function () {
	if ( get_option( 'zxc_db_version' ) !== ZXC_DB_VERSION ) {
		zxc_install_db();
	}
} );

/* -------------------------------------------------------------------------
 * Formatting helpers
 * ---------------------------------------------------------------------- */

function zxc_money( $n ) {
	return '₦' . number_format( (float) $n );
}

function zxc_money_short( $n ) {
	$n = (float) $n;
	if ( $n >= 1000000 ) {
		return '₦' . rtrim( rtrim( number_format( $n / 1000000, 1 ), '0' ), '.' ) . 'm';
	}
	if ( $n >= 10000 ) {
		return '₦' . rtrim( rtrim( number_format( $n / 1000, $n >= 100000 ? 0 : 1 ), '0' ), '.' ) . 'k';
	}
	return zxc_money( $n );
}

function zxc_duration( $min ) {
	$min = (int) $min;
	$pl  = function ( $n, $w ) {
		return $n . ' ' . $w . ( 1 === (int) $n ? '' : 's' );
	};
	if ( $min < 60 ) {
		return $pl( $min, 'minute' );
	}
	if ( $min < 1440 && 0 === $min % 60 ) {
		return $pl( $min / 60, 'hour' );
	}
	if ( $min < 1440 ) {
		return floor( $min / 60 ) . 'h ' . ( $min % 60 ) . 'm';
	}
	if ( 0 === $min % 1440 ) {
		return $pl( $min / 1440, 'day' );
	}
	return floor( $min / 1440 ) . 'd ' . round( ( $min % 1440 ) / 60 ) . 'h';
}

function zxc_data_label( $mb ) {
	$mb = (int) $mb;
	if ( ! $mb ) {
		return 'Unlimited data';
	}
	return $mb >= 1024 ? rtrim( rtrim( number_format( $mb / 1024, 1 ), '0' ), '.' ) . 'GB data' : $mb . 'MB data';
}

function zxc_speed_label( $mbps ) {
	return (float) $mbps > 0 ? ( 0 + (float) $mbps ) . ' Mbps' : 'Full speed';
}

function zxc_bytes( $n ) {
	$n = (float) $n;
	$u = array( 'B', 'KB', 'MB', 'GB', 'TB' );
	$i = 0;
	while ( $n >= 1024 && $i < 4 ) {
		$n /= 1024;
		$i++;
	}
	return ( 0 === $i ? $n : number_format( $n, $n >= 100 ? 0 : 1 ) ) . ' ' . $u[ $i ];
}

function zxc_remaining( $secs ) {
	$s = max( 0, (int) $secs );
	$d = intdiv( $s, 86400 );
	$h = intdiv( $s % 86400, 3600 );
	$m = intdiv( $s % 3600, 60 );
	if ( $d ) {
		return "{$d}d {$h}h";
	}
	if ( $h ) {
		return "{$h}h {$m}m";
	}
	return "{$m}m " . ( $s % 60 ) . 's';
}

function zxc_ago( $ts ) {
	if ( ! $ts ) {
		return 'never';
	}
	$s = time() - (int) $ts;
	if ( $s < 60 ) {
		return 'just now';
	}
	if ( $s < 3600 ) {
		return intdiv( $s, 60 ) . ' min ago';
	}
	if ( $s < 86400 ) {
		return intdiv( $s, 3600 ) . ' hr ago';
	}
	return intdiv( $s, 86400 ) . ' days ago';
}

function zxc_date( $ts ) {
	if ( ! $ts ) {
		return '—';
	}
	return wp_date( 'j M, g:i a', (int) $ts, new DateTimeZone( 'Africa/Lagos' ) );
}

function zxc_mask_phone( $p ) {
	return ( $p && strlen( $p ) >= 8 ) ? substr( $p, 0, 4 ) . ' ••• ' . substr( $p, -4 ) : ( $p ? $p : '—' );
}

/** Start of the current day/month in Lagos time (UTC+1, no DST). */
function zxc_lagos_day_start( $ts = null ) {
	$ts = null === $ts ? time() : $ts;
	return (int) ( floor( ( $ts + 3600 ) / 86400 ) * 86400 - 3600 );
}

function zxc_lagos_month_start() {
	$d = gmdate( 'Y-m', time() + 3600 );
	return strtotime( $d . '-01 00:00:00 UTC' ) - 3600;
}

function zxc_normalize_phone( $p ) {
	$p = preg_replace( '/[^0-9+]/', '', (string) $p );
	if ( 0 === strpos( $p, '+234' ) ) {
		$p = '0' . substr( $p, 4 );
	} elseif ( 0 === strpos( $p, '234' ) && 13 === strlen( $p ) ) {
		$p = '0' . substr( $p, 3 );
	} elseif ( preg_match( '/^[789][01]\d{8}$/', $p ) ) {
		$p = '0' . $p;
	}
	return $p;
}

function zxc_valid_phone( $p ) {
	return (bool) preg_match( '/^0[789][01]\d{8}$/', $p );
}

/** Read a request field as a trimmed string. */
function zxc_post( $key, $max = 500 ) {
	return isset( $_POST[ $key ] ) ? substr( trim( sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) ), 0, $max ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

function zxc_get( $key, $max = 500 ) {
	return isset( $_GET[ $key ] ) ? substr( trim( sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) ), 0, $max ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
}

/** Text colour that reads on top of a brand colour. */
function zxc_accent_ink( $hex ) {
	if ( ! preg_match( '/^#?([0-9a-f]{6})$/i', (string) $hex, $m ) ) {
		return '#FFFFFF';
	}
	$n   = hexdec( $m[1] );
	$lin = function ( $c ) {
		$c /= 255;
		return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	};
	$l = 0.2126 * $lin( ( $n >> 16 ) & 255 ) + 0.7152 * $lin( ( $n >> 8 ) & 255 ) + 0.0722 * $lin( $n & 255 );
	return ( 1.05 / ( $l + 0.05 ) ) >= 4.5 ? '#FFFFFF' : '#0B1020';
}

function zxc_is_hex( $v ) {
	return (bool) preg_match( '/^#[0-9a-f]{6}$/i', (string) $v );
}
