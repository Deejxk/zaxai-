<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Client for the proxy-ipv4.com reseller API.
 * Docs: https://proxy-ipv4.com/en/documentation-api/
 *
 * Every method returns the decoded response array or a WP_Error.
 * Error codes: "zp_api" (provider answered success=false; data has error_id),
 * "zp_http" (transport problem; the outcome of a write is unknown),
 * "zp_config" (no API key).
 */
class ZP_API {

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
		return self::request( 'GET', '/get/balance' );
	}

	public static function proxies( $type ) {
		return self::request( 'GET', '/get/proxies', array( 'proxyType' => $type ) );
	}

	public static function days( $type ) {
		return self::cached( 'days_' . $type, '/get/days', array( 'proxyType' => $type ) );
	}

	public static function countries( $type ) {
		return self::cached( 'countries_' . $type, '/get/countries', array( 'proxyType' => $type ) );
	}

	public static function tariffs() {
		return self::cached( 'tariffs', '/get/tariffs', array() );
	}

	private static function cached( $name, $path, $params ) {
		$tkey = 'zp_api_' . $name;
		$hit  = get_transient( $tkey );
		if ( is_array( $hit ) ) {
			return $hit;
		}
		$res = self::request( 'GET', $path, $params );
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
		foreach ( array_keys( zp_types() ) as $t ) {
			delete_transient( 'zp_api_days_' . $t );
			delete_transient( 'zp_api_countries_' . $t );
		}
		delete_transient( 'zp_api_tariffs' );
	}

	public static function order_price( array $params ) {
		return self::request( 'GET', '/get/order/price', $params );
	}

	/** @param string[] $ids proxy IDs, or order IDs for ipv6. */
	public static function extend_price( $type, $days, array $ids ) {
		$field = 'ipv6' === $type ? 'orderId' : 'IpId';
		return self::request( 'GET', '/get/extend/price', array( 'proxyType' => $type, 'days' => (int) $days, $field => implode( ',', $ids ) ) );
	}

	public static function order( array $body ) {
		return self::request( 'POST', '/order', $body );
	}

	public static function extend( $type, $days, array $ids ) {
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
