<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The store's connection to its proxy supplier.
 *
 * Two suppliers are supported: proxy-ipv4.com (spoken directly by this class,
 * docs: https://proxy-ipv4.com/en/documentation-api/) and Proxy-Seller (ZP_ProxySeller, which
 * answers in the same shape). One of them is "active": new orders, prices and the lists of
 * countries and periods come from it. Proxies bought earlier from the other one keep working and
 * can still be renewed, as long as its API key stays saved: every proxy remembers its supplier.
 *
 * Every method returns the decoded response array or a WP_Error.
 * Error codes: "zp_api" (provider answered success=false; data has error_id),
 * "zp_http" (transport problem; the outcome of a write is unknown),
 * "zp_config" (no API key).
 */
class ZP_API {

	const PROVIDERS = array( 'proxyipv4' => 'proxy-ipv4.com', 'proxyseller' => 'Proxy-Seller' );

	/** The supplier new orders go to. */
	public static function provider() {
		$p = (string) zp_get( 'provider' );
		return isset( self::PROVIDERS[ $p ] ) ? $p : 'proxyipv4';
	}

	public static function has_key( $provider ) {
		return '' !== trim( (string) zp_get( 'proxyseller' === $provider ? 'ps_api_key' : 'api_key' ) );
	}

	/** True when the active supplier has an API key saved. */
	public static function connected() {
		return self::has_key( self::provider() );
	}

	private static function seller() {
		return 'proxyseller' === self::provider();
	}

	public static function base() {
		$base = defined( 'ZAXPROXY_API_BASE' ) ? ZAXPROXY_API_BASE : 'https://proxy-ipv4.com/client-api/v1';
		return untrailingslashit( apply_filters( 'zaxproxy_api_base', $base ) );
	}

	private static function request( $method, $path, $params = array() ) {
		$key = trim( (string) zp_get( 'api_key' ) );
		if ( '' === $key ) {
			return new WP_Error( 'zp_config', 'The store is not connected to the proxy provider yet.' );
		}
		$url  = self::base() . '/' . rawurlencode( $key ) . $path;
		$args = array(
			'timeout' => 45,
			'headers' => array( 'Accept' => 'application/json', 'Content-Type' => 'application/json' ),
		);
		if ( 'GET' === $method ) {
			if ( $params ) {
				$url = add_query_arg( array_map( 'rawurlencode', $params ), $url );
			}
			$res = wp_remote_get( $url, $args );
		} else {
			$args['body'] = wp_json_encode( $params );
			$res          = wp_remote_post( $url, $args );
		}
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'zp_http', 'Could not reach the proxy provider. Please try again shortly.' );
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) ) {
			return new WP_Error( 'zp_http', 'Unexpected response from the proxy provider (HTTP ' . (int) wp_remote_retrieve_response_code( $res ) . ').' );
		}
		if ( empty( $data['success'] ) ) {
			$id  = isset( $data['error_id'] ) ? (int) $data['error_id'] : 0;
			$msg = isset( $data['error'] ) ? (string) $data['error'] : 'Unknown provider error';
			return new WP_Error( 'zp_api', $msg, array( 'error_id' => $id ) );
		}
		return $data;
	}

	public static function balance() {
		return self::seller() ? ZP_ProxySeller::balance() : self::request( 'GET', '/get/balance' );
	}

	/**
	 * Every proxy of one type the store owns, from both suppliers when both have a key saved.
	 * The active supplier must answer; the other one is skipped quietly when it does not.
	 */
	public static function proxies( $type ) {
		$active = self::provider();
		$all    = array();
		foreach ( array_keys( self::PROVIDERS ) as $p ) {
			if ( ! self::has_key( $p ) && $p !== $active ) {
				continue;
			}
			$res = 'proxyseller' === $p ? ZP_ProxySeller::proxies( $type ) : self::request( 'GET', '/get/proxies', array( 'proxyType' => $type ) );
			if ( is_wp_error( $res ) ) {
				if ( $p === $active ) {
					return $res;
				}
				continue;
			}
			$all = array_merge( $all, isset( $res['proxies'] ) && is_array( $res['proxies'] ) ? $res['proxies'] : array() );
		}
		return array( 'success' => true, 'proxies' => $all );
	}

	public static function days( $type ) {
		return self::cached( 'days_' . $type, '/get/days', array( 'proxyType' => $type ), array( 'ZP_ProxySeller', 'days' ), array( $type ) );
	}

	public static function countries( $type ) {
		return self::cached( 'countries_' . $type, '/get/countries', array( 'proxyType' => $type ), array( 'ZP_ProxySeller', 'countries' ), array( $type ) );
	}

	public static function tariffs() {
		return self::cached( 'tariffs', '/get/tariffs', array(), array( 'ZP_ProxySeller', 'tariffs' ), array() );
	}

	/** Lists that rarely change are kept for a few hours, separately for each supplier. */
	private static function cached( $name, $path, $params, $seller_call = null, $seller_args = array() ) {
		$name = ( self::seller() ? 'ps_' : '' ) . $name;
		$tkey = 'zp_api_' . $name;
		$hit  = get_transient( $tkey );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		$res = self::seller() ? call_user_func_array( $seller_call, $seller_args ) : self::request( 'GET', $path, $params );
		if ( ! is_wp_error( $res ) ) {
			unset( $res['balance'], $res['user'] );
			set_transient( $tkey, $res, 3 * HOUR_IN_SECONDS );
			update_option( 'zp_last_' . $name, $res, false ); // kept as a fallback for when the provider is slow or down
			return $res;
		}
		// The provider did not answer: show the last list we know rather than an empty page.
		$last = get_option( 'zp_last_' . $name );
		if ( is_array( $last ) && 'zp_config' !== $res->get_error_code() ) {
			set_transient( $tkey, $last, 5 * MINUTE_IN_SECONDS );
			return $last;
		}
		return $res;
	}

	public static function flush_cache() {
		foreach ( array( '', 'ps_' ) as $pre ) {
			foreach ( array_keys( zp_types() ) as $t ) {
				delete_transient( 'zp_api_' . $pre . 'days_' . $t );
				delete_transient( 'zp_api_' . $pre . 'countries_' . $t );
			}
			delete_transient( 'zp_api_' . $pre . 'tariffs' );
		}
	}

	public static function order_price( array $params ) {
		return self::seller() ? ZP_ProxySeller::order_price( $params ) : self::request( 'GET', '/get/order/price', $params );
	}

	public static function order( array $body ) {
		return self::seller() ? ZP_ProxySeller::order( $body ) : self::request( 'POST', '/order', $body );
	}

	/**
	 * Which supplier a set of proxies (or IPv6 orders) came from. A renewal goes to the supplier
	 * that sold the proxy, whichever one is active today. Returns its name, or a WP_Error when the
	 * selection mixes both or that supplier's key is no longer saved.
	 */
	private static function owner( array $ids ) {
		$mine = 0;
		foreach ( $ids as $id ) {
			$mine += ZP_ProxySeller::is_mine( $id ) ? 1 : 0;
		}
		if ( $mine && $mine !== count( $ids ) ) {
			return new WP_Error( 'zp_input', 'These proxies come from two different batches. Please extend them in two separate steps.' );
		}
		$p = $mine ? 'proxyseller' : 'proxyipv4';
		if ( ! self::has_key( $p ) ) {
			update_option( 'zaxproxy_last_api_error', array( 'time' => zp_now(), 'message' => 'A renewal was asked for proxies bought from ' . self::PROVIDERS[ $p ] . ', but its API key is not saved.', 'id' => 0 ), false );
			return new WP_Error( 'zp_unavailable', 'These proxies cannot be renewed right now. Please contact support.' );
		}
		return $p;
	}

	/** @param string[] $ids proxy IDs, or order IDs for ipv6. */
	public static function extend_price( $type, $days, array $ids ) {
		$p = self::owner( $ids );
		if ( is_wp_error( $p ) ) {
			return $p;
		}
		if ( 'proxyseller' === $p ) {
			return ZP_ProxySeller::extend_price( $type, $days, $ids );
		}
		$field = 'ipv6' === $type ? 'orderId' : 'IpId';
		return self::request( 'GET', '/get/extend/price', array( 'proxyType' => $type, 'days' => (int) $days, $field => implode( ',', $ids ) ) );
	}

	public static function extend( $type, $days, array $ids ) {
		$p = self::owner( $ids );
		if ( is_wp_error( $p ) ) {
			return $p;
		}
		if ( 'proxyseller' === $p ) {
			return ZP_ProxySeller::extend( $type, $days, $ids );
		}
		$field = 'ipv6' === $type ? 'orderId' : 'IpId';
		return self::request( 'POST', '/extend', array( 'proxyType' => $type, 'days' => (int) $days, $field => array_values( $ids ) ) );
	}

	/**
	 * Flatten a /get/proxies response into rows keyed by upstream proxy id.
	 */
	public static function flatten( $type, array $response ) {
		$rows = array();
		$list = isset( $response['proxies'] ) && is_array( $response['proxies'] ) ? $response['proxies'] : array();
		foreach ( $list as $item ) {
			if ( 'ipv6' === $type && isset( $item['ips'] ) ) {
				foreach ( (array) $item['ips'] as $ip ) {
					$ip['orderId'] = isset( $item['orderId'] ) ? $item['orderId'] : '';
					$row           = self::normalize( $type, $ip );
					if ( $row ) {
						$rows[ $row['upstream_id'] ] = $row;
					}
				}
			} else {
				$row = self::normalize( $type, $item );
				if ( $row ) {
					$rows[ $row['upstream_id'] ] = $row;
				}
			}
		}
		return $rows;
	}

	private static function normalize( $type, $p ) {
		if ( ! is_array( $p ) || empty( $p['id'] ) ) {
			return null;
		}
		$ip    = isset( $p['ip'] ) ? (string) $p['ip'] : '';
		$https = isset( $p['httpsPort'] ) ? (string) $p['httpsPort'] : '';
		$socks = isset( $p['socks5Port'] ) ? (string) $p['socks5Port'] : '';
		$proto = isset( $p['protocol'] ) ? strtoupper( (string) $p['protocol'] ) : '';
		// IPv6 entries come as "host:port" with a single protocol.
		if ( '' === $https && '' === $socks && preg_match( '/^(.+):(\d{2,5})$/', $ip, $m ) ) {
			$ip = $m[1];
			if ( 'SOCKS5' === $proto ) {
				$socks = $m[2];
			} else {
				$https = $m[2];
			}
		}
		return array(
			'upstream_id'       => (string) $p['id'],
			'upstream_order_id' => isset( $p['orderId'] ) ? (string) $p['orderId'] : '',
			'proxy_type'        => $type,
			'ip'                => $ip,
			'country'           => isset( $p['country'] ) ? strtoupper( (string) $p['country'] ) : '',
			'https_port'        => $https,
			'socks5_port'       => $socks,
			'protocol'          => $proto,
			'login'             => isset( $p['authInfo']['login'] ) ? (string) $p['authInfo']['login'] : '',
			'password'          => isset( $p['authInfo']['password'] ) ? (string) $p['authInfo']['password'] : '',
			'mobile_operator'   => isset( $p['mobileOperator'] ) ? (string) $p['mobileOperator'] : '',
			'rotation_time'     => isset( $p['rotationTime'] ) ? (int) $p['rotationTime'] : 0,
			'reboot_link'       => isset( $p['rebootLink'] ) ? esc_url_raw( (string) $p['rebootLink'] ) : '',
			'date_start'        => self::to_mysql( isset( $p['dateStart'] ) ? $p['dateStart'] : '' ),
			'date_end'          => self::to_mysql( isset( $p['dateEnd'] ) ? $p['dateEnd'] : '' ),
		);
	}

	private static function to_mysql( $iso ) {
		$ts = $iso ? strtotime( (string) $iso ) : false;
		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : null;
	}
}
