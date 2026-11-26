<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Proxy-Seller as a second supplier (their client API v2: https://proxy-seller.com/personal/api/).
 *
 * The rest of the store was written against proxy-ipv4.com. Rather than teach every screen two
 * formats, this class asks Proxy-Seller and hands the answer back in the proxy-ipv4.com shape, so
 * prices, orders, renewals and syncing work unchanged whichever supplier is switched on.
 *
 * Proxy and order ids from this supplier are stored with a "ps:" prefix. That keeps them apart
 * from the other supplier's ids and tells the store who to ask when a proxy is renewed.
 *
 * Contract notes (from Proxy-Seller's official client):
 *  - every answer is {status, data, errors[]}; HTTP is almost always 200, errors are in errors[];
 *  - ids are 24-character strings and must never be turned into numbers;
 *  - countries are alpha-3 codes ("USA"), periods are codes ("1m"), mobile rotation is minutes;
 *  - orders and renewals are paid from the account balance (paymentCode "balance").
 */
class ZP_ProxySeller {

	const PREFIX = 'ps:';

	/** Period codes and the number of days the store treats them as. */
	const PERIODS = array( '3d' => 3, '1w' => 7, '2w' => 14, '1m' => 30, '2m' => 60, '3m' => 90, '6m' => 180, '9m' => 270, '12m' => 360 );

	public static function base() {
		$base = defined( 'ZAXPROXY_PS_BASE' ) ? ZAXPROXY_PS_BASE : 'https://proxy-seller.com/personal/api/v2';
		return untrailingslashit( apply_filters( 'zaxproxy_ps_base', $base ) );
	}

	public static function key() {
		return trim( (string) zp_get( 'ps_api_key' ) );
	}

	public static function is_mine( $id ) {
		return 0 === strpos( (string) $id, self::PREFIX );
	}

	public static function strip( $id ) {
		return self::is_mine( $id ) ? substr( (string) $id, strlen( self::PREFIX ) ) : (string) $id;
	}

	/**
	 * One call. Returns the "data" part, or a WP_Error in the store's usual codes.
	 * $calc: price questions answer status "error" with the price still filled in when the
	 * account is short of money; for those the data is what matters.
	 */
	private static function request( $method, $path, $body = null, $query = array(), $calc = false ) {
		$key = self::key();
		if ( '' === $key ) {
			return new WP_Error( 'zp_config', 'The store is not connected to the proxy provider yet.' );
		}
		$url = self::base() . '/' . rawurlencode( $key ) . '/' . ltrim( $path, '/' );
		if ( $query ) {
			$url = add_query_arg( array_map( 'rawurlencode', $query ), $url );
		}
		$args = array(
			'method'  => $method,
			'timeout' => 'GET' === $method ? 45 : 120, // paid calls can take a while; never cut them short
			'headers' => array( 'Accept' => 'application/json', 'Content-Type' => 'application/json' ),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$res = wp_remote_request( $url, $args );
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'zp_http', 'Could not reach the proxy provider. Please try again shortly.' );
		}
		$json = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $json ) || ! isset( $json['status'] ) ) {
			return new WP_Error( 'zp_http', 'Unexpected response from the proxy provider (HTTP ' . (int) wp_remote_retrieve_response_code( $res ) . ').' );
		}
		$data   = isset( $json['data'] ) && is_array( $json['data'] ) ? $json['data'] : array();
		$errors = isset( $json['errors'] ) && is_array( $json['errors'] ) ? $json['errors'] : array();
		if ( 'success' === $json['status'] ) {
			return $data;
		}
		if ( $calc && ! $errors && isset( $data['total'] ) ) {
			return $data; // a price, with a warning that the balance is too low
		}
		$first = $errors && is_array( reset( $errors ) ) ? reset( $errors ) : array();
		$msg   = isset( $first['message'] ) ? (string) $first['message'] : ( isset( $data['warning'] ) ? (string) $data['warning'] : 'Unknown provider error' );
		$code  = isset( $first['code'] ) ? (int) $first['code'] : 0;
		// Problems with the store's own supplier account are hidden from customers by the store
		// (it treats ids 1 and 25 that way): a bad key or an empty balance is not their concern.
		$id = 0;
		if ( false !== stripos( $msg, 'api key' ) ) {
			$id = 25;
		} elseif ( 16 === $code || false !== stripos( $msg, 'insufficient' ) || false !== stripos( $msg, 'not enough' ) ) {
			$id = 1;
		}
		return new WP_Error( 'zp_api', $msg, array( 'error_id' => $id, 'ps_code' => $code ) );
	}

	/* ---------- Reference lists ---------- */

	/** The supplier's list of countries, periods and (for mobile) carriers for one proxy type. */
	private static function reference( $type ) {
		$res = self::request( 'GET', 'reference/list/' . rawurlencode( $type ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return isset( $res['items'] ) && is_array( $res['items'] ) ? $res['items'] : $res;
	}

	public static function balance() {
		$res = self::request( 'GET', 'balance/get' );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		return array( 'success' => true, 'user' => 'API key accepted', 'balance' => isset( $res['summ'] ) ? (float) $res['summ'] : 0, 'currency' => 'USD' );
	}

	public static function days( $type ) {
		$ref = self::reference( $type );
		if ( is_wp_error( $ref ) ) {
			return $ref;
		}
		$days = array();
		foreach ( isset( $ref['period'] ) ? (array) $ref['period'] : array() as $p ) {
			$code = is_array( $p ) && isset( $p['id'] ) ? (string) $p['id'] : '';
			if ( isset( self::PERIODS[ $code ] ) ) {
				$days[] = self::PERIODS[ $code ];
			}
		}
		sort( $days );
		return array( 'success' => true, 'days' => array_values( array_unique( $days ) ) );
	}

	public static function countries( $type ) {
		$ref = self::reference( $type );
		if ( is_wp_error( $ref ) ) {
			return $ref;
		}
		$out = array();
		foreach ( isset( $ref['country'] ) ? (array) $ref['country'] : array() as $c ) {
			if ( is_array( $c ) && ! empty( $c['id'] ) ) {
				$out[] = array( 'name' => isset( $c['name'] ) ? (string) $c['name'] : '', 'alpha3code' => strtoupper( (string) $c['id'] ) );
			}
		}
		return array( 'success' => true, 'countries' => $out );
	}

	/**
	 * Mobile carriers per country. The store shows and stores a carrier by its name, while the
	 * supplier wants a tag and whether the line is private or shared, so the name carries both:
	 * "EE" is the private line, "EE (shared)" the shared one. carrier() turns it back.
	 */
	public static function tariffs() {
		$ref = self::reference( 'mobile' );
		if ( is_wp_error( $ref ) ) {
			return $ref;
		}
		$out = array();
		foreach ( isset( $ref['country'] ) ? (array) $ref['country'] : array() as $c ) {
			if ( ! is_array( $c ) || empty( $c['id'] ) || empty( $c['operators'] ) || ! is_array( $c['operators'] ) ) {
				continue;
			}
			$ops = array();
			foreach ( array( 'dedicated' => '', 'shared' => ' (shared)' ) as $kind => $suffix ) {
				foreach ( isset( $c['operators'][ $kind ] ) ? (array) $c['operators'][ $kind ] : array() as $op ) {
					if ( ! is_array( $op ) || ! isset( $op['id'] ) ) {
						continue;
					}
					$mins = array();
					foreach ( isset( $op['rotations'] ) ? (array) $op['rotations'] : array() as $r ) {
						if ( is_array( $r ) && isset( $r['id'] ) && is_numeric( $r['id'] ) ) {
							$mins[] = (int) $r['id'];
						}
					}
					$ops[] = array(
						'name'         => ( isset( $op['name'] ) && '' !== (string) $op['name'] ? (string) $op['name'] : (string) $op['id'] ) . $suffix,
						'rotationTime' => $mins ? $mins : array( 0 ),
						'ps_id'        => (string) $op['id'],
						'ps_kind'      => $kind,
					);
				}
			}
			if ( $ops ) {
				$out[] = array( 'country' => strtoupper( (string) $c['id'] ), 'mobileOperator' => $ops );
			}
		}
		return array( 'success' => true, 'tariffs' => $out );
	}

	/** @return array{0:string,1:string}|null the supplier's carrier tag and line kind for a carrier name */
	private static function carrier( $country, $name ) {
		$t = ZP_API::tariffs(); // cached
		if ( is_wp_error( $t ) ) {
			return null;
		}
		foreach ( (array) $t['tariffs'] as $row ) {
			if ( strtoupper( (string) $row['country'] ) !== strtoupper( (string) $country ) ) {
				continue;
			}
			foreach ( (array) $row['mobileOperator'] as $op ) {
				if ( (string) $op['name'] === (string) $name && isset( $op['ps_id'] ) ) {
					return array( (string) $op['ps_id'], (string) $op['ps_kind'] );
				}
			}
		}
		return null;
	}

	/* ---------- Prices and orders ---------- */

	private static function period( $days ) {
		$code = array_search( (int) $days, self::PERIODS, true );
		return false === $code ? '' : (string) $code;
	}

	/** Turns the store's order details into the supplier's order fields. */
	private static function order_fields( array $p ) {
		$type = isset( $p['proxyType'] ) ? (string) $p['proxyType'] : '';
		$per  = self::period( isset( $p['days'] ) ? $p['days'] : 0 );
		if ( '' === $per ) {
			return new WP_Error( 'zp_api', 'This rental period is not offered.', array( 'error_id' => 0 ) );
		}
		$goal  = isset( $p['customGoal'] ) ? (string) $p['customGoal'] : ( isset( $p['goal'] ) ? (string) $p['goal'] : '' );
		$goals = zp_goals();
		$goal  = isset( $goals[ $goal ] ) ? (string) $goals[ $goal ] : $goal; // "instagram" -> "Instagram"
		$json  = array(
			'paymentCode' => 'balance',
			'sectionCode' => $type,
			'countryId'   => strtoupper( isset( $p['country'] ) ? (string) $p['country'] : '' ),
			'periodId'    => $per,
			'quantity'    => max( 1, isset( $p['count'] ) ? (int) $p['count'] : 1 ),
		);
		if ( 'mobile' === $type ) {
			$carrier = self::carrier( $json['countryId'], isset( $p['mobileOperator'] ) ? $p['mobileOperator'] : '' );
			if ( ! $carrier ) {
				return new WP_Error( 'zp_api', 'This mobile carrier is not available right now. Choose another one.', array( 'error_id' => 0 ) );
			}
			$json['operatorId']        = $carrier[0];
			$json['mobileServiceType'] = $carrier[1];
			$json['rotationId']        = isset( $p['rotationTime'] ) ? (int) $p['rotationTime'] : 0;
		} else {
			$json['customTargetName'] = '' !== $goal ? mb_substr( $goal, 0, 80 ) : 'General use';
			if ( 'ipv6' === $type ) {
				$json['protocol'] = isset( $p['protocol'] ) && 'SOCKS5' === strtoupper( (string) $p['protocol'] ) ? 'SOCKS5' : 'HTTPS';
			}
		}
		return $json;
	}

	public static function order_price( array $params ) {
		$json = self::order_fields( $params );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$res = self::request( 'POST', 'order/calc', $json, array(), true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$total = isset( $res['total'] ) ? (float) $res['total'] : 0;
		if ( $total <= 0 ) {
			return new WP_Error( 'zp_api', 'The provider did not return a price for this choice.', array( 'error_id' => 0 ) );
		}
		return array( 'success' => true, 'amount' => $total, 'price' => $total / $json['quantity'], 'count' => $json['quantity'], 'currency' => 'USD' );
	}

	public static function order( array $body ) {
		$json = self::order_fields( $body );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		// Access by the customer's own IP address, or a fresh username and password for this order.
		if ( isset( $body['authType'] ) && 'ip' === $body['authType'] && ! empty( $body['authIp'] ) ) {
			$json['authorization'] = (string) $body['authIp'];
			$json['generateAuth']  = 'N';
		} else {
			$json['authorization'] = '';
			$json['generateAuth']  = 'Y';
		}
		$res = self::request( 'POST', 'order/make', $json );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$oid = isset( $res['orderId'] ) ? (string) $res['orderId'] : '';
		return array( 'success' => true, 'orderNumber' => '' !== $oid ? self::PREFIX . $oid : '' );
	}

	/** What a renewal is addressed by: whole orders for IPv6, single proxies for the rest. */
	private static function renew_fields( $type, $days, array $ids ) {
		$per = self::period( $days );
		if ( '' === $per ) {
			return new WP_Error( 'zp_api', 'This renewal period is not offered.', array( 'error_id' => 0 ) );
		}
		$ids = array_values( array_unique( array_filter( array_map( array( __CLASS__, 'strip' ), array_map( 'strval', $ids ) ) ) ) );
		return array( 'paymentCode' => 'balance', 'periodId' => $per, ( 'ipv6' === $type ? 'orderIds' : 'ids' ) => $ids );
	}

	public static function extend_price( $type, $days, array $ids ) {
		$json = self::renew_fields( $type, $days, $ids );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$res = self::request( 'POST', 'prolong/calc/' . rawurlencode( $type ), $json, array(), true );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$total = isset( $res['total'] ) ? (float) $res['total'] : 0;
		if ( $total <= 0 ) {
			return new WP_Error( 'zp_api', 'The provider did not return a renewal price.', array( 'error_id' => 0 ) );
		}
		return array( 'success' => true, 'amount' => $total, 'currency' => 'USD' );
	}

	public static function extend( $type, $days, array $ids ) {
		$json = self::renew_fields( $type, $days, $ids );
		if ( is_wp_error( $json ) ) {
			return $json;
		}
		$res = self::request( 'POST', 'prolong/make/' . rawurlencode( $type ), $json );
		return is_wp_error( $res ) ? $res : array( 'success' => true );
	}

	/* ---------- The proxies the account owns ---------- */

	private static function pick( array $row, array $keys ) {
		foreach ( $keys as $k ) {
			if ( isset( $row[ $k ] ) && ! is_array( $row[ $k ] ) && '' !== (string) $row[ $k ] ) {
				return (string) $row[ $k ];
			}
		}
		return '';
	}

	/** Every proxy of one type, in the proxy-ipv4.com shape the store already understands. */
	public static function proxies( $type ) {
		$out  = array();
		$seen = array();
		for ( $page = 1; $page <= 25; $page++ ) {
			$res = self::request( 'GET', 'proxy/list/' . rawurlencode( $type ), null, array( 'page' => $page, 'per_page' => 500 ) );
			if ( is_wp_error( $res ) ) {
				return $res;
			}
			$items = isset( $res['items'] ) && is_array( $res['items'] ) ? $res['items'] : array();
			$fresh = 0;
			foreach ( $items as $it ) {
				$id = is_array( $it ) ? self::pick( $it, array( 'id', '_id' ) ) : '';
				if ( '' === $id || isset( $seen[ $id ] ) ) {
					continue;
				}
				$seen[ $id ] = true;
				$fresh++;
				$ip = self::pick( $it, array( 'ip_only', 'ip' ) );
				// Country as a three-letter code; if only a name is given, look it up.
				$country = strtoupper( self::pick( $it, array( 'country_alpha3', 'countryAlpha3', 'country_code' ) ) );
				if ( 3 !== strlen( $country ) ) {
					$country = self::country_code( $type, self::pick( $it, array( 'country' ) ) );
				}
				$oid   = self::pick( $it, array( 'order_id', 'orderId' ) );
				$out[] = array(
					'id'             => self::PREFIX . $id,
					'orderId'        => '' !== $oid ? self::PREFIX . $oid : '',
					'ip'             => $ip,
					'httpsPort'      => self::pick( $it, array( 'port_http', 'portHttp' ) ),
					'socks5Port'     => self::pick( $it, array( 'port_socks', 'portSocks' ) ),
					'protocol'       => strtoupper( self::pick( $it, array( 'protocol' ) ) ),
					'country'        => $country,
					'authInfo'       => array( 'login' => self::pick( $it, array( 'login' ) ), 'password' => self::pick( $it, array( 'password' ) ) ),
					'mobileOperator' => self::pick( $it, array( 'operator', 'mobile_operator' ) ),
					'rotationTime'   => (int) self::pick( $it, array( 'rotation' ) ),
					'rebootLink'     => self::pick( $it, array( 'link_reboot', 'linkReboot' ) ),
					'dateStart'      => self::pick( $it, array( 'date_start', 'dateStart' ) ),
					'dateEnd'        => self::pick( $it, array( 'date_end', 'dateEnd' ) ),
				);
			}
			$pages = isset( $res['metadata']['total_pages'] ) ? (int) $res['metadata']['total_pages'] : 0;
			if ( 0 === $fresh || ( $pages && $page >= $pages ) || count( $items ) < 500 ) {
				break; // the last page, or a supplier that sends everything at once
			}
		}
		return array( 'success' => true, 'proxies' => $out );
	}

	/** "France" -> "FRA", from the supplier's own country list. */
	private static function country_code( $type, $name ) {
		static $maps = array();
		$name = strtolower( trim( (string) $name ) );
		if ( '' === $name ) {
			return '';
		}
		if ( 3 === strlen( $name ) ) {
			return strtoupper( $name );
		}
		if ( ! isset( $maps[ $type ] ) ) {
			$maps[ $type ] = array();
			$list          = ZP_API::countries( $type ); // cached
			if ( ! is_wp_error( $list ) ) {
				foreach ( (array) $list['countries'] as $c ) {
					$maps[ $type ][ strtolower( (string) $c['name'] ) ] = (string) $c['alpha3code'];
				}
			}
		}
		return isset( $maps[ $type ][ $name ] ) ? $maps[ $type ][ $name ] : '';
	}
}
