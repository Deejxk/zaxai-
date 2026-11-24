<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ---------- Settings ---------- */

function zp_defaults() {
	return array(
		'brand'               => 'ZaxProxy',
		'api_key'             => '',
		'types'               => array( 'ipv4', 'ipv6', 'isp', 'mobile' ),
		'markup_ipv4'         => 30,
		'markup_ipv6'         => 30,
		'markup_isp'          => 30,
		'markup_mobile'       => 30,
		'registration'        => 1,
		'landing'             => 1,
		'currency_code'       => 'NGN',
		'currency_symbol'     => '₦',
		'fx_rate'             => 1600,
		'reseller_markup'     => 150,
		'reseller_fee'        => 45000,
		'reseller_monthly'    => 15000,
		'reseller_domain_fee' => 12000,
		'hostinger_token'     => '',
		'hostinger_username'  => '',
		'min_deposit'         => 1000,
		'max_deposit'         => 5000000,
		'manual_enabled'      => 1,
		'manual_title'        => 'Bank transfer',
		'manual_instructions' => 'Transfer the exact amount, then send your receipt on Telegram. Your wallet is credited as soon as we confirm the payment.',
		'bank_name'           => '',
		'bank_account_name'   => '',
		'bank_account_number' => '',
		'telegram'            => '',
		'paystack_enabled'    => 0,
		'paystack_secret'     => '',
		'support_email'       => get_option( 'admin_email' ),
	);
}

function zp_settings() {
	static $cache = null;
	if ( null === $cache || ! empty( $GLOBALS['zp_settings_dirty'] ) ) {
		$cache = wp_parse_args( (array) get_option( 'zaxproxy_settings', array() ), zp_defaults() );
		$GLOBALS['zp_settings_dirty'] = false;
	}
	return $cache;
}

/** The store whose own domain this request came in on, or null on the main site. */
function zp_domain_store() {
	return isset( $GLOBALS['zp_domain_store'] ) ? $GLOBALS['zp_domain_store'] : null;
}

/** The reseller whose branded store is being served, or null on the platform itself. */
function zp_reseller_ctx() {
	return isset( $GLOBALS['zp_reseller_ctx'] ) ? $GLOBALS['zp_reseller_ctx'] : null;
}

function zp_get( $key, $default = '' ) {
	$r = zp_reseller_ctx();
	if ( $r ) {
		// Inside a reseller's store the brand and payment details are the reseller's own.
		if ( in_array( $key, array( 'brand', 'bank_name', 'bank_account_name', 'bank_account_number', 'telegram' ), true ) ) {
			return (string) $r->$key;
		}
		if ( 'paystack_enabled' === $key ) {
			return 0;
		}
		if ( 'manual_enabled' === $key ) {
			return 1;
		}
		if ( 'manual_title' === $key ) {
			return 'Bank transfer';
		}
		if ( 'manual_instructions' === $key ) {
			return 'Transfer the exact amount, then send your receipt so your wallet can be credited.';
		}
	}
	$s = zp_settings();
	return isset( $s[ $key ] ) ? $s[ $key ] : $default;
}

function zp_update_settings( array $new ) {
	$s = array_merge( zp_settings(), $new );
	update_option( 'zaxproxy_settings', $s, false );
	$GLOBALS['zp_settings_dirty'] = true;
}

/* ---------- URLs ---------- */

function zp_pretty() {
	return (bool) get_option( 'permalink_structure' );
}

function zp_route_url( $base, $page = '', $args = array() ) {
	$page = trim( (string) $page, '/' );
	if ( zp_pretty() ) {
		$url = home_url( '/' . $base . ( $page ? '/' . $page : '' ) . '/' );
	} else {
		$url = add_query_arg( array( 'zp' => $base, 'zpp' => $page ), home_url( '/' ) );
	}
	return $args ? add_query_arg( array_map( 'rawurlencode', $args ), $url ) : $url;
}

function zp_url( $page = 'dashboard', $args = array() ) {
	$r = zp_reseller_ctx();
	if ( $r && in_array( $page, array( 'login', 'register', 'forgot', 'reset' ), true ) ) {
		// On the store's own domain these pages sit at the root: brand.cloud/login/
		return zp_domain_store() ? zp_route_url( $page, '', $args ) : zp_route_url( 's', $r->slug . '/' . $page, $args );
	}
	return zp_route_url( ZP_USER_BASE, $page, $args );
}

function zp_admin_url( $page = '', $args = array() ) {
	return zp_route_url( ZP_ADMIN_BASE, $page, $args );
}

function zp_asset( $path ) {
	return ZP_URL . 'assets/' . $path . '?v=' . ZP_VERSION;
}

/* ---------- Formatting ---------- */

/** Store currency (what customers see and pay in). */
function zp_money( $amount ) {
	$amount = (float) $amount;
	$whole  = abs( $amount - round( $amount ) ) < 0.005;
	return ( $amount < 0 ? '-' : '' ) . zp_get( 'currency_symbol', '₦' ) . number_format( abs( $amount ), $whole ? 0 : 2 );
}

/** Provider currency: the proxy-ipv4.com API always quotes USD. */
function zp_usd( $amount ) {
	return '$' . number_format( (float) $amount, 2 );
}

function zp_fx() {
	return max( 0.0001, (float) zp_get( 'fx_rate', 1 ) );
}

function zp_table( $name ) {
	global $wpdb;
	return $wpdb->prefix . 'zp_' . $name;
}

function zp_now() {
	return gmdate( 'Y-m-d H:i:s' );
}

function zp_date( $mysql, $with_time = true ) {
	if ( ! $mysql || '0000-00-00 00:00:00' === $mysql ) {
		return '—';
	}
	$ts = strtotime( $mysql . ' UTC' );
	return wp_date( $with_time ? 'M j, Y H:i' : 'M j, Y', $ts );
}

function zp_time_left( $mysql ) {
	$diff = strtotime( $mysql . ' UTC' ) - time();
	if ( $diff <= 0 ) {
		return 'Expired';
	}
	if ( $diff >= DAY_IN_SECONDS ) {
		return floor( $diff / DAY_IN_SECONDS ) . 'd ' . floor( ( $diff % DAY_IN_SECONDS ) / HOUR_IN_SECONDS ) . 'h';
	}
	return floor( $diff / HOUR_IN_SECONDS ) . 'h ' . floor( ( $diff % HOUR_IN_SECONDS ) / 60 ) . 'm';
}

function zp_types() {
	return array(
		'ipv4'   => array( 'label' => 'IPv4', 'desc' => 'Dedicated datacenter IPs. Fast, stable, one owner.', 'icon' => 'server' ),
		'ipv6'   => array( 'label' => 'IPv6', 'desc' => 'Low-cost IPs in bulk for IPv6-ready sites.', 'icon' => 'layers' ),
		'isp'    => array( 'label' => 'ISP', 'desc' => 'Static residential IPs from real providers.', 'icon' => 'home' ),
		'mobile' => array( 'label' => 'Mobile', 'desc' => '4G/LTE carrier IPs with rotation.', 'icon' => 'phone' ),
	);
}

function zp_enabled_types() {
	return array_intersect_key( zp_types(), array_flip( (array) zp_get( 'types', array() ) ) );
}

function zp_type_label( $type ) {
	$t = zp_types();
	return isset( $t[ $type ] ) ? $t[ $type ]['label'] : strtoupper( $type );
}

function zp_goals() {
	return apply_filters(
		'zaxproxy_goals',
		array(
			'instagram' => 'Instagram',
			'facebook'  => 'Facebook',
			'tiktok'    => 'TikTok',
			'twitter'   => 'X / Twitter',
			'telegram'  => 'Telegram',
			'youtube'   => 'YouTube',
			'google'    => 'Google',
			'amazon'    => 'Amazon',
			'scraping'  => 'Web scraping',
			'gaming'    => 'Gaming',
			'seo'       => 'SEO tools',
			'other'     => 'Other (describe)',
		)
	);
}

/** alpha3 => [alpha2, name] for flags and friendly names. */
function zp_country_map() {
	return array(
		'USA' => array( 'US', 'United States' ), 'GBR' => array( 'GB', 'United Kingdom' ), 'FRA' => array( 'FR', 'France' ),
		'DEU' => array( 'DE', 'Germany' ), 'NLD' => array( 'NL', 'Netherlands' ), 'CAN' => array( 'CA', 'Canada' ),
		'AUS' => array( 'AU', 'Australia' ), 'ITA' => array( 'IT', 'Italy' ), 'ESP' => array( 'ES', 'Spain' ),
		'POL' => array( 'PL', 'Poland' ), 'UKR' => array( 'UA', 'Ukraine' ), 'RUS' => array( 'RU', 'Russia' ),
		'IND' => array( 'IN', 'India' ), 'JPN' => array( 'JP', 'Japan' ), 'CHN' => array( 'CN', 'China' ),
		'BRA' => array( 'BR', 'Brazil' ), 'TUR' => array( 'TR', 'Turkey' ), 'SWE' => array( 'SE', 'Sweden' ),
		'CHE' => array( 'CH', 'Switzerland' ), 'AUT' => array( 'AT', 'Austria' ), 'BEL' => array( 'BE', 'Belgium' ),
		'CZE' => array( 'CZ', 'Czechia' ), 'PRT' => array( 'PT', 'Portugal' ), 'ROU' => array( 'RO', 'Romania' ),
		'BGR' => array( 'BG', 'Bulgaria' ), 'NOR' => array( 'NO', 'Norway' ), 'FIN' => array( 'FI', 'Finland' ),
		'DNK' => array( 'DK', 'Denmark' ), 'IRL' => array( 'IE', 'Ireland' ), 'SGP' => array( 'SG', 'Singapore' ),
		'HKG' => array( 'HK', 'Hong Kong' ), 'KOR' => array( 'KR', 'South Korea' ), 'IDN' => array( 'ID', 'Indonesia' ),
		'MYS' => array( 'MY', 'Malaysia' ), 'THA' => array( 'TH', 'Thailand' ), 'VNM' => array( 'VN', 'Vietnam' ),
		'PHL' => array( 'PH', 'Philippines' ), 'ARE' => array( 'AE', 'UAE' ), 'SAU' => array( 'SA', 'Saudi Arabia' ),
		'ISR' => array( 'IL', 'Israel' ), 'EGY' => array( 'EG', 'Egypt' ), 'ZAF' => array( 'ZA', 'South Africa' ),
		'NGA' => array( 'NG', 'Nigeria' ), 'KEN' => array( 'KE', 'Kenya' ), 'MEX' => array( 'MX', 'Mexico' ),
		'ARG' => array( 'AR', 'Argentina' ), 'CHL' => array( 'CL', 'Chile' ), 'COL' => array( 'CO', 'Colombia' ),
		'KAZ' => array( 'KZ', 'Kazakhstan' ), 'LVA' => array( 'LV', 'Latvia' ), 'LTU' => array( 'LT', 'Lithuania' ),
		'EST' => array( 'EE', 'Estonia' ), 'GRC' => array( 'GR', 'Greece' ), 'HUN' => array( 'HU', 'Hungary' ),
		'SVK' => array( 'SK', 'Slovakia' ), 'SRB' => array( 'RS', 'Serbia' ), 'MDA' => array( 'MD', 'Moldova' ),
		'GEO' => array( 'GE', 'Georgia' ), 'ARM' => array( 'AM', 'Armenia' ), 'BLR' => array( 'BY', 'Belarus' ),
		'NZL' => array( 'NZ', 'New Zealand' ), 'PAK' => array( 'PK', 'Pakistan' ), 'BGD' => array( 'BD', 'Bangladesh' ),
		'TWN' => array( 'TW', 'Taiwan' ), 'LUX' => array( 'LU', 'Luxembourg' ), 'HRV' => array( 'HR', 'Croatia' ),
		'SVN' => array( 'SI', 'Slovenia' ), 'CYP' => array( 'CY', 'Cyprus' ), 'ISL' => array( 'IS', 'Iceland' ),
	);
}

function zp_flag( $alpha3 ) {
	$map = zp_country_map();
	$a3  = strtoupper( (string) $alpha3 );
	if ( ! isset( $map[ $a3 ] ) ) {
		return '🌐';
	}
	$a2 = $map[ $a3 ][0];
	return html_entity_decode( '&#' . ( 127397 + ord( $a2[0] ) ) . ';&#' . ( 127397 + ord( $a2[1] ) ) . ';', ENT_NOQUOTES, 'UTF-8' );
}

function zp_country_name( $alpha3, $fallback = '' ) {
	$map = zp_country_map();
	$a3  = strtoupper( (string) $alpha3 );
	if ( isset( $map[ $a3 ] ) ) {
		return $map[ $a3 ][1];
	}
	return $fallback ? ucwords( strtolower( $fallback ) ) : $a3;
}

/* ---------- Flash messages (cookie based, survives PRG redirect) ---------- */

function zp_flash( $message, $type = 'success' ) {
	$val = base64_encode( wp_json_encode( array( 't' => $type, 'm' => $message ) ) );
	setcookie( 'zp_flash', $val, time() + 60, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	$_COOKIE['zp_flash'] = $val;
}

function zp_take_flash() {
	if ( empty( $_COOKIE['zp_flash'] ) ) {
		return null;
	}
	$data = json_decode( (string) base64_decode( wp_unslash( $_COOKIE['zp_flash'] ), true ), true ); // phpcs:ignore
	if ( ! headers_sent() ) {
		setcookie( 'zp_flash', '', time() - 3600, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true );
	}
	unset( $_COOKIE['zp_flash'] );
	if ( ! is_array( $data ) || empty( $data['m'] ) ) {
		return null;
	}
	return array(
		'type'    => in_array( $data['t'], array( 'success', 'error', 'info' ), true ) ? $data['t'] : 'info',
		'message' => (string) $data['m'],
	);
}

function zp_redirect( $url ) {
	wp_safe_redirect( $url );
	exit;
}

/* ---------- Forms ---------- */

function zp_nonce_field( $action ) {
	echo '<input type="hidden" name="zp_do" value="' . esc_attr( $action ) . '">';
	wp_nonce_field( 'zp_' . $action, '_zpnonce', false );
}

function zp_post( $key, $default = '' ) {
	return isset( $_POST[ $key ] ) && ! is_array( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : $default; // phpcs:ignore
}

function zp_query( $key, $default = '' ) {
	return isset( $_GET[ $key ] ) && ! is_array( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : $default; // phpcs:ignore
}

function zp_client_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
}

/** Simple attempt limiter. Returns true when the caller is over the limit. */
function zp_throttled( $bucket, $max, $window ) {
	$key  = 'zp_rl_' . md5( $bucket . '|' . zp_client_ip() );
	$hits = (int) get_transient( $key );
	if ( $hits >= $max ) {
		return true;
	}
	set_transient( $key, $hits + 1, $window );
	return false;
}

function zp_badge( $status ) {
	$map = array(
		'completed' => 'ok', 'active' => 'ok', 'answered' => 'ok', 'success' => 'ok',
		'processing' => 'warn', 'pending' => 'warn', 'open' => 'warn', 'review' => 'warn', 'expiring' => 'warn',
		'failed' => 'bad', 'rejected' => 'bad', 'expired' => 'bad', 'refunded' => 'mute', 'closed' => 'mute', 'suspended' => 'bad',
	);
	$cls = isset( $map[ $status ] ) ? $map[ $status ] : 'mute';
	return '<span class="zp-badge zp-badge--' . $cls . '">' . esc_html( ucfirst( $status ) ) . '</span>';
}

function zp_paginate( $total, $per_page, $current ) {
	$pages = (int) ceil( $total / $per_page );
	if ( $pages < 2 ) {
		return '';
	}
	$out = '<nav class="zp-pager">';
	for ( $i = 1; $i <= $pages; $i++ ) {
		if ( $i > 2 && $i < $pages - 1 && abs( $i - $current ) > 1 ) {
			if ( $i === 3 || $i === $pages - 2 ) {
				$out .= '<span>…</span>';
			}
			continue;
		}
		$out .= '<a class="' . ( $i === $current ? 'is-active' : '' ) . '" href="' . esc_url( add_query_arg( 'pg', $i ) ) . '">' . $i . '</a>';
	}
	return $out . '</nav>';
}

function zp_page_num() {
	return max( 1, (int) zp_query( 'pg', 1 ) );
}

/* ---------- Icons (inline SVG, stroke based) ---------- */

function zp_icon( $name, $size = 20 ) {
	static $paths = null;
	if ( null === $paths ) {
		$paths = array(
			'home'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h5v-6h4v6h5V9.5"/>',
			'grid'     => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
			'cart'     => '<circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h3l2.6 12.2a1.5 1.5 0 0 0 1.5 1.2h8a1.5 1.5 0 0 0 1.5-1.2L20.5 7H6.4"/>',
			'plus'     => '<path d="M12 5v14M5 12h14"/>',
			'globe'    => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18"/><path d="M12 3c2.5 2.6 3.8 5.6 3.8 9S14.5 18.4 12 21c-2.5-2.6-3.8-5.6-3.8-9S9.5 5.6 12 3Z"/>',
			'list'     => '<path d="M8 6h13M8 12h13M8 18h13"/><circle cx="3.5" cy="6" r="1"/><circle cx="3.5" cy="12" r="1"/><circle cx="3.5" cy="18" r="1"/>',
			'wallet'   => '<path d="M3 7.5A2.5 2.5 0 0 1 5.5 5H18a2 2 0 0 1 2 2v2"/><path d="M3 7.5V17a3 3 0 0 0 3 3h13a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2H5.5A2.5 2.5 0 0 1 3 7.5Z"/><circle cx="16.5" cy="14.5" r="1.2"/>',
			'ticket'   => '<path d="M21 12a8 8 0 0 1-8 8H5l-2 2V12a8 8 0 0 1 8-8h2a8 8 0 0 1 8 8Z"/><path d="M8 11h8M8 15h5"/>',
			'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2 20a7 7 0 0 1 14 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14.5a6.5 6.5 0 0 1 4 5.5"/>',
			'help'     => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.3a2.6 2.6 0 0 1 5 .9c0 1.8-2.5 2.2-2.5 4"/><circle cx="12" cy="17.3" r=".6" fill="currentColor"/>',
			'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.9 2.900l-.1-.1a1.7 1.7 0 0 0-2.8 1.200V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-2.8-1.200l-.1.1a2 2 0 1 1-2.9-2.900l.1-.1a1.7 1.7 0 0 0-1.2-2.800H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.2-2.800l-.1-.1a2 2 0 1 1 2.9-2.900l.1.1a1.7 1.7 0 0 0 2.8-1.200V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 2.8 1.200l.1-.1a2 2 0 1 1 2.9 2.900l-.1.1a1.7 1.7 0 0 0-.3 1.8 1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1Z"/>',
			'logout'   => '<path d="M9 21H6a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h3"/><path d="m16 17 5-5-5-5M21 12H9"/>',
			'sun'      => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.900l1.4 1.400M17.7 17.700l1.4 1.400M2 12h2M20 12h2M4.9 19.100l1.4-1.400M17.7 6.300l1.4-1.4"/>',
			'moon'     => '<path d="M20.5 14.500A8.5 8.5 0 0 1 9.5 3.500a8.5 8.5 0 1 0 11 11Z"/>',
			'copy'     => '<rect x="9" y="9" width="11" height="11" rx="2"/><path d="M5 15V6a2 2 0 0 1 2-2h9"/>',
			'download' => '<path d="M12 3v12m0 0-4.5-4.500M12 15l4.5-4.5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
			'bell'     => '<path d="M6 9a6 6 0 0 1 12 0c0 6 2.5 7.5 2.5 7.5h-17S6 15 6 9"/><path d="M10 20a2.2 2.2 0 0 0 4 0"/>',
			'refresh'  => '<path d="M20 11A8 8 0 0 0 6 6.300L4 8"/><path d="M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14 4.700l2-1.7"/><path d="M20 20v-4h-4"/>',
			'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
			'check'    => '<path d="m5 12.5 4.5 4.500L19 7.5"/>',
			'x'        => '<path d="M6 6l12 12M18 6 6 18"/>',
			'menu'     => '<path d="M4 6h16M4 12h16M4 18h16"/>',
			'more'     => '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
			'server'   => '<rect x="3" y="4" width="18" height="7" rx="2"/><rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 7.500h.01M7 16.500h.01"/>',
			'layers'   => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
			'phone'    => '<rect x="7" y="2.5" width="10" height="19" rx="2.5"/><path d="M11 18h2"/>',
			'shield'   => '<path d="M12 3 4 6v6c0 4.5 3.2 7.9 8 9 4.8-1.1 8-4.5 8-9V6l-8-3Z"/><path d="m9 12 2.2 2.200L15 10.5"/>',
			'zap'      => '<path d="M13 2 4 14h7l-1 8 9-12h-7l1-8Z"/>',
			'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
			'card'     => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6.5 15h4"/>',
			'back'     => '<path d="M19 12H5m0 0 5-5m-5 5 5 5"/>',
			'arrow'    => '<path d="M5 12h14m0 0-5-5m5 5-5 5"/>',
			'search'   => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
			'eye'      => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
			'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m3.5 7 8.5 6 8.5-6"/>',
			'lock'     => '<rect x="4.5" y="10.5" width="15" height="10" rx="2.5"/><path d="M8 10.500V7a4 4 0 0 1 8 0v3.5"/>',
			'receipt'  => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6"/>',
			'send'     => '<path d="M21.5 3 2.8 10.3c-.8.3-.8 1.4 0 1.7l4.7 1.7 1.8 5.6c.2.7 1.1.9 1.6.4l2.7-2.6 4.6 3.4c.6.4 1.4.1 1.6-.6L22.7 4c.2-.8-.5-1.3-1.2-1Z"/><path d="m7.5 13.7 10-6.7-7.2 7.7"/>',
			'swap'     => '<path d="M7 4 3 8l4 4M3 8h14M17 20l4-4-4-4M21 16H7"/>',
		);
	}
	$p = isset( $paths[ $name ] ) ? $paths[ $name ] : $paths['grid'];
	return '<svg class="zp-ico" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function zp_logo( $size = 30 ) {
	static $svg = null;
	static $n   = 0;
	$r = zp_reseller_ctx();
	if ( $r ) {
		// Reseller stores show a monogram of their own brand instead of the platform mark.
		++$n;
		if ( ! empty( $r->logo_url ) ) {
			return '<img class="zp-logo-mark zp-logo-img" src="' . esc_url( set_url_scheme( $r->logo_url ) ) . '" width="' . (int) $size . '" height="' . (int) $size . '" alt="">';
		}
		$c1     = ZP_Resellers::color( $r ) ? ZP_Resellers::color( $r ) : '#6d5efc';
		$c2     = ZP_Resellers::color( $r ) ? $c1 : '#22d3ee';
		$letter = esc_html( strtoupper( mb_substr( $r->brand, 0, 1 ) ) );
		return '<svg xmlns="http://www.w3.org/2000/svg" class="zp-logo-mark" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="zpMono' . $n . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="' . $c1 . '"/><stop offset="1" stop-color="' . $c2 . '" stop-opacity="' . ( $c1 === $c2 ? '.72' : '1' ) . '"/></linearGradient></defs><rect x="4" y="4" width="56" height="56" rx="17" fill="url(#zpMono' . $n . ')"/><text x="32" y="43" text-anchor="middle" font-family="Sora,Inter,Arial,sans-serif" font-size="30" font-weight="800" fill="#fff">' . $letter . '</text></svg>';
	}
	if ( null === $svg ) {
		$svg = (string) file_get_contents( ZP_DIR . 'assets/logo-mark.svg' ); // phpcs:ignore
	}
	// Each copy gets its own gradient id: a gradient defined inside a hidden element (the splash) cannot paint visible copies.
	++$n;
	return str_replace( array( 'width="64" height="64"', 'gZPL' ), array( 'width="' . (int) $size . '" height="' . (int) $size . '"', 'zpLogo' . $n ), $svg );
}

/** Brand wordmark: the trailing "Proxy" picks up the brand gradient. */
function zp_wordmark() {
	$brand = (string) zp_get( 'brand' );
	if ( preg_match( '/^(.+?)(proxy)$/i', $brand, $m ) ) {
		return '<span class="zp-wordmark">' . esc_html( $m[1] ) . '<b>' . esc_html( $m[2] ) . '</b></span>';
	}
	return '<span class="zp-wordmark">' . esc_html( $brand ) . '</span>';
}

/** Animated illustrations used on landing, auth and empty states. */
function zp_illustration( $name ) {
	$file = ZP_DIR . 'templates/illustrations/' . preg_replace( '/[^a-z0-9\-]/', '', $name ) . '.svg';
	return is_readable( $file ) ? file_get_contents( $file ) : ''; // phpcs:ignore
}

function zp_empty( $illustration, $title, $text, $cta_url = '', $cta_label = '' ) {
	$out  = '<div class="zp-empty"><div class="zp-empty__art">' . zp_illustration( $illustration ) . '</div>';
	$out .= '<h3>' . esc_html( $title ) . '</h3><p>' . esc_html( $text ) . '</p>';
	if ( $cta_url ) {
		$out .= '<a class="zp-btn zp-btn--primary" href="' . esc_url( $cta_url ) . '">' . esc_html( $cta_label ) . '</a>';
	}
	return $out . '</div>';
}

function zp_faqs() {
	return array(
		'How fast are proxies delivered?'            => 'Most orders are delivered to your dashboard within a few minutes of payment. During busy periods setup can take a little longer; the order shows as Processing until your proxies appear.',
		'Which protocols are supported?'             => 'IPv4, ISP and mobile proxies work over both HTTP(S) and SOCKS5 using separate ports. For IPv6 you choose the protocol when ordering.',
		'How do I authenticate?'                     => 'Choose username and password, or whitelist your own IPv4 address when ordering so no credentials are needed.',
		'Are the proxies private?'                   => 'Yes. Each proxy is issued to a single customer for the whole rental period.',
		'Can I extend a proxy before it expires?'    => 'Yes. Open My proxies, select the proxies and choose Extend. The new period is added to the current expiry date. IPv6 batches are renewed as a whole order.',
		'What payment methods can I use?'            => 'Add funds to your wallet with any of the methods shown on the Wallet page, then pay for orders from your balance.',
		'What is your refund policy?'                => 'If an order cannot be fulfilled the full amount returns to your wallet automatically. For a proxy that does not work, contact support within 24 hours of purchase for a replacement or refund.',
		'What am I not allowed to do?'               => 'Anything illegal, including fraud, spam, attacks on networks, or accessing systems without permission. See the Acceptable Use Policy for details.',
	);
}

/**
 * "From" prices for the public pricing page: cheapest per-proxy rate per type
 * for a reference country, cached so visitors never trigger provider calls in bulk.
 */
function zp_public_prices() {
	$cached = get_transient( 'zp_public_prices' );
	if ( is_array( $cached ) ) {
		foreach ( $cached as $row ) {
			if ( ! isset( $row['wholesale'] ) || ! in_array( (int) $row['days'], zp_allowed_days(), true ) ) {
				$cached = false;
				break;
			}
		}
	}
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$out = array();
	if ( '' !== trim( (string) zp_get( 'api_key' ) ) ) {
		foreach ( array_keys( zp_enabled_types() ) as $type ) {
			$opts = ZP_Orders::options( $type );
			if ( is_wp_error( $opts ) || ! $opts['countries'] || ! $opts['days'] ) {
				continue;
			}
			$codes   = wp_list_pluck( $opts['countries'], 'code' );
			$country = in_array( 'USA', $codes, true ) ? 'USA' : $codes[0];
			$days    = in_array( 30, $opts['days'], true ) ? 30 : max( $opts['days'] );
			$qty     = 'ipv6' === $type ? 10 : 1;
			$params  = array( 'proxyType' => $type, 'days' => $days, 'goal' => 'instagram', 'country' => $country, 'count' => $qty );
			if ( 'mobile' === $type && ! empty( $opts['tariffs'][ $country ][0] ) ) {
				$params['mobileOperator'] = $opts['tariffs'][ $country ][0]['name'];
				$params['rotationTime']   = $opts['tariffs'][ $country ][0]['rotation'] ? $opts['tariffs'][ $country ][0]['rotation'][0] : 0;
			}
			$res = ZP_API::order_price( $params );
			if ( ! is_wp_error( $res ) && ! empty( $res['amount'] ) ) {
				$out[ $type ] = array( 'price' => ZP_Orders::markup( (float) $res['amount'], $type ) / $qty, 'wholesale' => ZP_Orders::markup( (float) $res['amount'], $type, true ) / $qty, 'days' => $days, 'countries' => count( $codes ) );
			}
		}
	}
	set_transient( 'zp_public_prices', $out, $out ? 2 * HOUR_IN_SECONDS : 5 * MINUTE_IN_SECONDS );
	return $out;
}

/** Telegram chat link for the support handle saved in settings, optionally with a prefilled message. */
function zp_telegram_url( $text = '' ) {
	$handle = trim( (string) zp_get( 'telegram' ) );
	$handle = preg_replace( '#^(https?://)?(t\.me|telegram\.me)/#i', '', $handle );
	$handle = preg_replace( '/[^A-Za-z0-9_+]/', '', ltrim( $handle, '@' ) );
	if ( '' === $handle ) {
		return '';
	}
	$url = 'https://t.me/' . $handle;
	return $text ? $url . '?text=' . rawurlencode( $text ) : $url;
}

function zp_receipt_message( $deposit, $user ) {
	return sprintf(
		"Hello %s, I just made a bank transfer to fund my wallet.\n\nAmount: %s\nDeposit reference: %s\nAccount email: %s\nSender name: %s\n\nPlease find my payment receipt attached.",
		zp_get( 'brand' ),
		zp_money( $deposit->amount ),
		$deposit->reference,
		$user->user_email,
		$deposit->note
	);
}

/** Rental periods the store sells. Shorter periods (3, 7, 14 days) are not offered anywhere. */
function zp_allowed_days() {
	return array( 30, 60, 90 );
}

/** Keep only the provider periods the store sells. */
function zp_filter_days( array $provider_days ) {
	$provider_days = array_map( 'intval', $provider_days );
	$allowed       = zp_allowed_days();
	return array_values( array_intersect( $provider_days, $allowed ) );
}

/** Full FAQ, grouped by topic, for the public FAQ page. */
function zp_faq_groups() {
	$brand = zp_get( 'brand' );
	$cur   = zp_get( 'currency_code' );
	return array(
		'Getting started'      => array(
			'What is a proxy?'                               => 'A proxy is a server with its own IP address that sits between you and the internet. Websites see the proxy\'s IP address instead of yours. People use proxies to manage several accounts, see content from other countries, collect public data and protect their privacy.',
			'What does ' . $brand . ' sell?'                 => 'We rent private IPv4, IPv6, ISP and mobile proxies for 30, 60 or 90 days. Each proxy is issued to one customer only and is delivered to your dashboard with its IP, port and login details.',
			'Which proxy type should I choose?'              => 'IPv4 is the everyday choice and works everywhere. IPv6 is the cheapest per IP and suits large volumes on IPv6-ready sites. ISP proxies look like ordinary home connections and suit accounts that need extra trust. Mobile proxies come from 4G carriers and are trusted most by apps and social platforms.',
			'Do I need technical knowledge?'                 => 'No. You copy the IP, port, username and password from your dashboard into your browser, phone or tool. Our Help center explains the steps.',
			'Are the proxies private?'                       => 'Yes. Each proxy is issued to a single customer for the whole rental period.',
		),
		'Orders and delivery'  => array(
			'How fast are proxies delivered?'                => 'Most orders are delivered to your dashboard within a few minutes of payment. During busy periods setup can take longer; the order shows as Processing until your proxies appear.',
			'What rental periods are available?'             => 'You can rent for 30, 60 or 90 days.',
			'Can I extend a proxy before it expires?'        => 'Yes. Open My proxies, select the proxies and choose Extend. The new period is added to the current expiry date, so you keep the same IP. IPv6 batches are renewed as a whole order.',
			'What happens when a proxy expires?'             => 'It stops working and the IP is released. Extend before the expiry date if you want to keep the same address.',
			'Can I choose the country?'                      => 'Yes. You pick the exact country when you order. The list depends on the proxy type.',
			'How many proxies can I buy at once?'            => 'From 1 to 1,000 in a single order. IPv6 is sold in batches of 10 or more.',
		),
		'Payments and wallet'  => array(
			'How do I pay?'                                  => 'You add funds to your wallet in ' . $cur . ' by bank transfer, then pay for orders from your balance. After you transfer, send your receipt on Telegram and we credit your wallet once the payment is confirmed.',
			'How long does a top-up take?'                   => 'Your wallet is credited as soon as our team confirms the transfer. This is usually quick during working hours.',
			'Is there a minimum top-up?'                     => 'Yes, the minimum is ' . zp_money( zp_get( 'min_deposit' ) ) . '.',
			'Are there any hidden fees?'                     => 'No. The price you see before you confirm is the price taken from your wallet.',
			'What is your refund policy?'                    => 'If an order cannot be delivered, the full amount returns to your wallet automatically. If a proxy does not work at all, contact support within 24 hours of delivery for a replacement or refund. See the Refund Policy for full details.',
		),
		'Using your proxies'   => array(
			'Which protocols are supported?'                 => 'IPv4, ISP and mobile proxies work over both HTTP(S) and SOCKS5 using separate ports. For IPv6 you choose the protocol when ordering.',
			'How do I authenticate?'                         => 'Choose username and password, or whitelist your own IPv4 address when ordering so no credentials are needed.',
			'How do I export my proxies?'                    => 'In My proxies you can copy or download your list as ip:port:user:pass, user:pass@ip:port, a full URL, or ip:port.',
			'How do mobile proxies rotate?'                  => 'Depending on the plan, the IP changes on a timer or on demand using the rotate button on the proxy card.',
			'A website is blocking my proxy. What can I do?' => 'Some websites block certain IP ranges; this is outside our control. Try a different proxy type or country, and contact support if you need advice on which works best for your target.',
			'What am I not allowed to do?'                   => 'Anything illegal, including fraud, spam, attacks on networks, or accessing systems without permission. See the Acceptable Use Policy for details.',
		),
		'Reseller program'     => array(
			'What is the reseller program?'                  => 'It lets you run your own proxy store under your own brand. You buy at the reseller price, set your own prices and keep the difference, while we deliver the proxies to your customers.',
			'How much does it cost?'                         => 'Activation is ' . zp_money( zp_get( 'reseller_fee', 45000 ) ) . ' once, which includes the first 30 days. After that it is ' . zp_money( zp_get( 'reseller_monthly', 15000 ) ) . ' per month, taken from your wallet automatically.',
			'How do I get paid by my customers?'             => 'Your customers pay directly into your own bank account and send you the receipt. You approve the payment in your reseller dashboard and their wallet is credited.',
			'Why must I keep my wallet funded?'              => 'Each time one of your customers buys, the reseller price of that order comes out of your wallet. If your wallet is empty, their order is refused.',
			'Can I use my own domain name?'                  => 'Yes. You can add your own .cloud domain for ' . zp_money( zp_get( 'reseller_domain_fee', 12000 ) ) . '. We register it and connect it to your store.',
			'What happens if I miss the monthly fee?'        => 'Your store pauses and you return to regular prices until you renew. Your customers and their proxies are kept.',
		),
		'Account and security' => array(
			'How do I reset my password?'                    => 'Choose Forgot password on the sign-in page and we will email you a reset link.',
			'Can I install the app on my phone?'             => 'Yes. On Android, tap the install icon in the dashboard header. On iPhone, tap Share in Safari and choose Add to Home Screen.',
			'Is my data safe?'                               => 'We use encrypted connections and hashed passwords, collect only what we need, and never sell your data. See the Privacy Policy.',
			'How do I contact support?'                      => 'Open a ticket from your dashboard, message us on Telegram, or use the Contact page.',
		),
	);
}

/* ---------- Colour maths (keeps custom brand colours readable) ---------- */

function zp_hex_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );
	return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
}

/** WCAG relative luminance, 0 (black) to 1 (white). */
function zp_luminance( $hex ) {
	$out = array();
	foreach ( zp_hex_rgb( $hex ) as $c ) {
		$c     = $c / 255;
		$out[] = $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
	}
	return 0.2126 * $out[0] + 0.7152 * $out[1] + 0.0722 * $out[2];
}

/** WCAG contrast ratio between two colours, 1 (none) to 21. */
function zp_contrast( $a, $b ) {
	$la = zp_luminance( $a );
	$lb = zp_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/** Blend $hex towards $target by $t (0 to 1). */
function zp_mix( $hex, $target, $t ) {
	$a = zp_hex_rgb( $hex );
	$b = zp_hex_rgb( $target );
	return sprintf( '#%02x%02x%02x', round( $a[0] + ( $b[0] - $a[0] ) * $t ), round( $a[1] + ( $b[1] - $a[1] ) * $t ), round( $a[2] + ( $b[2] - $a[2] ) * $t ) );
}

/** Nudge $hex towards white or black until it stands out from $against by at least $min. */
function zp_fit_contrast( $hex, $against, $min ) {
	$toward = zp_luminance( $against ) < 0.4 ? '#ffffff' : '#000000';
	$out    = $hex;
	for ( $i = 1; $i <= 20 && zp_contrast( $out, $against ) < $min; $i++ ) {
		$out = zp_mix( $hex, $toward, $i * 0.05 );
	}
	return $out;
}

/** Black or white, whichever reads better on $hex. */
function zp_ink_on( $hex ) {
	return zp_contrast( $hex, '#ffffff' ) >= zp_contrast( $hex, '#0b0d14' ) ? '#ffffff' : '#0b0d14';
}
