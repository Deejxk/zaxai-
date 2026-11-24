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

function zp_get( $key, $default = '' ) {
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
		'failed' => 'bad', 'rejected' => 'bad', 'expired' => 'bad', 'refunded' => 'mute', 'closed' => 'mute',
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
	if ( null === $svg ) {
		$svg = (string) file_get_contents( ZP_DIR . 'assets/logo-mark.svg' ); // phpcs:ignore
	}
	// Each copy gets its own gradient id: a gradient defined inside a hidden element (the splash) cannot paint visible copies.
	static $n = 0;
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
			if ( ! in_array( (int) $row['days'], zp_allowed_days(), true ) ) {
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
				$out[ $type ] = array( 'price' => ZP_Orders::markup( (float) $res['amount'], $type ) / $qty, 'days' => $days, 'countries' => count( $codes ) );
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
