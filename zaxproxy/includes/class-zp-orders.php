<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Quotes, purchases, extensions and synchronisation with the provider.
 *
 * The provider account is shared by every customer of this store, and its
 * /order call only returns an order number. Ownership is therefore resolved
 * locally: before ordering we snapshot the proxy IDs that already exist, and
 * new IDs that show up afterwards are assigned to the oldest waiting order
 * with a matching type and country. Purchases are serialised with a DB lock.
 */
class ZP_Orders {

	/* ---------- Options for the buy form ---------- */

	public static function options( $type ) {
		if ( ! isset( zp_enabled_types()[ $type ] ) ) {
			return new WP_Error( 'zp_input', 'This proxy type is not available.' );
		}
		$days = ZP_API::days( $type );
		if ( is_wp_error( $days ) ) {
			return $days;
		}
		$out = array( 'days' => zp_filter_days( (array) $days['days'] ), 'countries' => array(), 'tariffs' => array() );

		if ( 'mobile' === $type ) {
			$tariffs = ZP_API::tariffs();
			if ( is_wp_error( $tariffs ) ) {
				return $tariffs;
			}
			foreach ( (array) $tariffs['tariffs'] as $t ) {
				$code = strtoupper( (string) $t['country'] );
				$ops  = array();
				foreach ( (array) $t['mobileOperator'] as $op ) {
					$ops[] = array( 'name' => (string) $op['name'], 'rotation' => array_map( 'intval', (array) $op['rotationTime'] ) );
				}
				$out['tariffs'][ $code ] = $ops;
				$out['countries'][]      = array( 'code' => $code, 'name' => zp_country_name( $code ), 'flag' => zp_flag( $code ) );
			}
		} else {
			$countries = ZP_API::countries( $type );
			if ( is_wp_error( $countries ) ) {
				return $countries;
			}
			foreach ( (array) $countries['countries'] as $c ) {
				$code               = strtoupper( (string) $c['alpha3code'] );
				$out['countries'][] = array( 'code' => $code, 'name' => zp_country_name( $code, isset( $c['name'] ) ? $c['name'] : '' ), 'flag' => zp_flag( $code ) );
			}
		}
		usort(
			$out['countries'],
			function ( $a, $b ) {
				return strcmp( $a['name'], $b['name'] );
			}
		);
		return $out;
	}

	/* ---------- Pricing ---------- */

	/**
	 * Selling price in the store currency for a provider cost in USD:
	 * cost x exchange rate x (1 + markup %), rounded up to a whole unit.
	 * Resellers (and orders placed in their stores) are priced with the lower reseller markup.
	 */
	public static function markup( $cost, $type, $wholesale = false ) {
		$pct = max( 0, (float) ( $wholesale ? zp_get( 'reseller_markup', 150 ) : zp_get( 'markup_' . $type, 0 ) ) );
		return (float) ceil( round( (float) $cost * zp_fx() * ( 1 + $pct / 100 ), 4 ) );
	}

	/** Validate raw buy-form input into a clean order spec. */
	public static function sanitize_spec( array $in ) {
		$type = isset( $in['type'] ) ? sanitize_key( $in['type'] ) : '';
		if ( ! isset( zp_enabled_types()[ $type ] ) ) {
			return new WP_Error( 'zp_input', 'Choose a proxy type.' );
		}
		$spec = array(
			'type'      => $type,
			'country'   => strtoupper( preg_replace( '/[^A-Za-z]/', '', isset( $in['country'] ) ? (string) $in['country'] : '' ) ),
			'days'      => isset( $in['days'] ) ? (int) $in['days'] : 0,
			'qty'       => isset( $in['qty'] ) ? (int) $in['qty'] : 0,
			'goal'      => isset( $in['goal'] ) ? sanitize_key( $in['goal'] ) : '',
			'goal_text' => isset( $in['goal_text'] ) ? sanitize_text_field( $in['goal_text'] ) : '',
			'auth_type' => isset( $in['auth_type'] ) && 'ip' === $in['auth_type'] ? 'ip' : 'login',
			'auth_ip'   => isset( $in['auth_ip'] ) ? trim( sanitize_text_field( $in['auth_ip'] ) ) : '',
			'protocol'  => isset( $in['protocol'] ) && 'HTTPS' === strtoupper( (string) $in['protocol'] ) ? 'HTTPS' : 'SOCKS5',
			'operator'  => isset( $in['operator'] ) ? sanitize_text_field( $in['operator'] ) : '',
			'rotation'  => isset( $in['rotation'] ) ? (int) $in['rotation'] : 0,
		);
		if ( 3 !== strlen( $spec['country'] ) ) {
			return new WP_Error( 'zp_input', 'Choose a country.' );
		}
		$allowed = zp_allowed_days();
		if ( ! in_array( $spec['days'], $allowed, true ) ) {
			return new WP_Error( 'zp_input', 'Choose a rental period.' );
		}
		$min = 'ipv6' === $type ? 10 : 1;
		if ( $spec['qty'] < $min || $spec['qty'] > 1000 ) {
			return new WP_Error( 'zp_input', 'ipv6' === $type ? 'IPv6 proxies are sold in batches of 10 or more.' : 'Quantity must be between 1 and 1000.' );
		}
		if ( ! isset( zp_goals()[ $spec['goal'] ] ) ) {
			return new WP_Error( 'zp_input', 'Tell us what you will use the proxies for.' );
		}
		if ( 'other' === $spec['goal'] && mb_strlen( $spec['goal_text'] ) < 5 ) {
			return new WP_Error( 'zp_input', 'Describe your use case in at least 5 characters.' );
		}
		if ( 'ip' === $spec['auth_type'] && ! filter_var( $spec['auth_ip'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			return new WP_Error( 'zp_input', 'Enter a valid IPv4 address for IP authorization.' );
		}
		if ( 'mobile' === $type && '' === $spec['operator'] ) {
			return new WP_Error( 'zp_input', 'Choose a mobile operator.' );
		}
		return $spec;
	}

	/** Provider request parameters shared by price and order calls. */
	private static function upstream_params( array $spec ) {
		$p = array(
			'proxyType' => $spec['type'],
			'days'      => $spec['days'],
			'country'   => $spec['country'],
			'count'     => $spec['qty'],
		);
		if ( 'other' === $spec['goal'] ) {
			$p[ 'mobile' === $spec['type'] ? 'customGoal' : 'goal' ] = $spec['goal_text'];
		} else {
			$p['goal'] = $spec['goal'];
		}
		if ( 'mobile' === $spec['type'] ) {
			$p['mobileOperator'] = $spec['operator'];
			$p['rotationTime']   = $spec['rotation'];
		}
		return $p;
	}

	/**
	 * Price an order for a buyer. `platform` is what the platform charges; `total` is what this
	 * buyer pays (higher when they buy through a reseller's store).
	 */
	public static function quote( array $spec, $user_id = 0 ) {
		$res = ZP_API::order_price( self::upstream_params( $spec ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$cost = isset( $res['amount'] ) ? (float) $res['amount'] : 0;
		if ( $cost <= 0 ) {
			return new WP_Error( 'zp_api', 'The provider did not return a price for this configuration.' );
		}
		$reseller = ZP_Resellers::of_user( $user_id );
		$platform = self::markup( $cost, $spec['type'], $reseller || ZP_Resellers::is_reseller( $user_id ) );
		$total    = $reseller ? ZP_Resellers::price( $platform, $reseller ) : $platform;
		return array( 'cost' => $cost, 'platform' => $platform, 'total' => $total, 'unit' => round( $total / $spec['qty'], 2 ), 'reseller' => $reseller );
	}

	/* ---------- Locking ---------- */

	private static function lock() {
		global $wpdb;
		return '1' === (string) $wpdb->get_var( $wpdb->prepare( 'SELECT GET_LOCK(%s, 25)', $wpdb->prefix . 'zp_orders' ) );
	}

	private static function unlock() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $wpdb->prefix . 'zp_orders' ) );
	}

	/* ---------- Purchase ---------- */

	/**
	 * @return int|WP_Error order id
	 */
	public static function purchase( $user_id, array $spec ) {
		global $wpdb;
		if ( ! self::lock() ) {
			return new WP_Error( 'zp_busy', 'The store is busy processing another order. Please try again in a few seconds.' );
		}
		try {
			$quote    = self::quote( $spec, $user_id );
			$reseller = is_wp_error( $quote ) ? null : $quote['reseller'];
			if ( is_wp_error( $quote ) ) {
				return self::public_error( $quote );
			}
			if ( ZP_Wallet::balance( $user_id ) < $quote['total'] ) {
				return new WP_Error( 'zp_funds', 'Your wallet balance is too low for this order. Please add funds.' );
			}
			if ( $reseller && ! self::reseller_can_pay( $reseller, $quote['platform'] ) ) {
				return new WP_Error( 'zp_unavailable', 'Ordering is temporarily unavailable. Please contact support.' );
			}
			$existing = ZP_API::proxies( $spec['type'] );
			if ( is_wp_error( $existing ) ) {
				return self::public_error( $existing );
			}
			$pre_ids = array_keys( ZP_API::flatten( $spec['type'], $existing ) );

			$goal_label = 'other' === $spec['goal'] ? $spec['goal_text'] : $spec['goal'];
			$wpdb->insert(
				zp_table( 'orders' ),
				array(
					'user_id'         => $user_id,
					'kind'            => 'purchase',
					'proxy_type'      => $spec['type'],
					'country'         => $spec['country'],
					'days'            => $spec['days'],
					'qty'             => $spec['qty'],
					'goal'            => $goal_label,
					'auth_type'       => $spec['auth_type'],
					'auth_ip'         => 'ip' === $spec['auth_type'] ? $spec['auth_ip'] : '',
					'protocol'        => 'ipv6' === $spec['type'] ? $spec['protocol'] : '',
					'mobile_operator' => 'mobile' === $spec['type'] ? $spec['operator'] : '',
					'rotation_time'   => 'mobile' === $spec['type'] ? $spec['rotation'] : 0,
					'cost'            => $quote['cost'],
					'fx_rate'         => zp_fx(),
					'amount'          => $quote['total'],
					'reseller_id'     => $reseller ? $reseller->user_id : 0,
					'status'          => 'pending',
					'pre_ids'         => wp_json_encode( $pre_ids ),
					'created_at'      => zp_now(),
					'updated_at'      => zp_now(),
				)
			);
			$order_id = (int) $wpdb->insert_id;
			if ( ! $order_id ) {
				return new WP_Error( 'zp_db', 'Could not create the order. Please try again.' );
			}
			$desc = sprintf( '%d × %s proxy, %s, %d days', $spec['qty'], zp_type_label( $spec['type'] ), zp_country_name( $spec['country'] ), $spec['days'] );
			if ( ! ZP_Wallet::debit( $user_id, $quote['total'], 'purchase', 'order-' . $order_id, $desc ) ) {
				self::set_status( $order_id, 'failed', 'Insufficient balance' );
				return new WP_Error( 'zp_funds', 'Your wallet balance is too low for this order. Please add funds.' );
			}
			if ( $reseller && ! self::charge_reseller( $order_id, $reseller, $quote['platform'], $desc ) ) {
				self::refund( $order_id, 'Store wallet could not cover the order' );
				return new WP_Error( 'zp_unavailable', 'Ordering is temporarily unavailable. Please contact support.' );
			}

			$body             = self::upstream_params( $spec );
			$body['authType'] = $spec['auth_type'];
			if ( 'ip' === $spec['auth_type'] ) {
				$body['authIp'] = $spec['auth_ip'];
			}
			if ( 'ipv6' === $spec['type'] ) {
				$body['protocol'] = $spec['protocol'];
			}
			$res = ZP_API::order( $body );

			if ( is_wp_error( $res ) ) {
				if ( 'zp_http' === $res->get_error_code() ) {
					// Outcome unknown: keep the charge on hold for staff review instead of risking a free order.
					self::set_status( $order_id, 'review', 'No confirmation from provider: ' . $res->get_error_message() );
					return $order_id;
				}
				self::refund( $order_id, $res->get_error_message() );
				$err = self::public_error( $res );
				return new WP_Error( $err->get_error_code(), 'This order could not be completed (' . rtrim( $err->get_error_message(), '.' ) . '). The amount was returned to your wallet.' );
			}

			$wpdb->update(
				zp_table( 'orders' ),
				array( 'status' => 'processing', 'upstream_order' => isset( $res['orderNumber'] ) ? (string) $res['orderNumber'] : '', 'updated_at' => zp_now() ),
				array( 'id' => $order_id )
			);
			self::sync_type( $spec['type'] );
			return $order_id;
		} finally {
			self::unlock();
		}
	}

	/** Hide provider-side account problems from customers; log them for the admin. */
	private static function public_error( WP_Error $e ) {
		$data = $e->get_error_data();
		$id   = is_array( $data ) && isset( $data['error_id'] ) ? (int) $data['error_id'] : 0;
		if ( in_array( $id, array( 1, 3, 23, 24, 25 ), true ) || 'zp_config' === $e->get_error_code() ) {
			update_option( 'zaxproxy_last_api_error', array( 'time' => zp_now(), 'message' => $e->get_error_message(), 'id' => $id ), false );
			return new WP_Error( 'zp_unavailable', 'Ordering is temporarily unavailable. Please try again later or contact support.' );
		}
		return $e;
	}

	public static function set_status( $order_id, $status, $error = null ) {
		global $wpdb;
		$data = array( 'status' => $status, 'updated_at' => zp_now() );
		if ( null !== $error ) {
			$data['error'] = $error;
		}
		$wpdb->update( zp_table( 'orders' ), $data, array( 'id' => $order_id ) );
	}

	public static function get( $order_id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'orders' ) . ' WHERE id = %d', $order_id ) );
	}

	/** Refund an order once. Returns false when it was already refunded. */
	public static function refund( $order_id, $reason = '' ) {
		global $wpdb;
		$flipped = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . zp_table( 'orders' ) . " SET refunded = 1, status = %s, error = %s, updated_at = %s WHERE id = %d AND refunded = 0",
				'failed',
				$reason,
				zp_now(),
				$order_id
			)
		);
		if ( ! $flipped ) {
			return false;
		}
		$o = self::get( $order_id );
		ZP_Wallet::credit( $o->user_id, $o->amount, 'refund', 'order-' . $order_id, 'Refund for order #' . $order_id );
		if ( $o->reseller_id && $o->reseller_cost > 0 ) {
			ZP_Wallet::credit( $o->reseller_id, $o->reseller_cost, 'refund', 'order-' . $order_id, 'Refund for customer order #' . $order_id );
		}
		return true;
	}

	/* ---------- Reseller settlement ---------- */

	/** Can the store owner's platform wallet cover this order? Records a notice for them when it cannot. */
	private static function reseller_can_pay( $reseller, $platform_price ) {
		if ( 'active' !== $reseller->status ) {
			return false;
		}
		if ( ZP_Wallet::balance( $reseller->user_id ) >= $platform_price ) {
			return true;
		}
		update_user_meta( $reseller->user_id, 'zp_reseller_blocked', array( 'time' => zp_now(), 'amount' => $platform_price ) );
		return false;
	}

	/** Charge the store owner the platform price for a customer order. */
	private static function charge_reseller( $order_id, $reseller, $platform_price, $desc ) {
		global $wpdb;
		if ( ! ZP_Wallet::debit( $reseller->user_id, $platform_price, 'store_sale', 'order-' . $order_id, 'Customer order #' . $order_id . ': ' . $desc ) ) {
			update_user_meta( $reseller->user_id, 'zp_reseller_blocked', array( 'time' => zp_now(), 'amount' => $platform_price ) );
			return false;
		}
		$wpdb->update( zp_table( 'orders' ), array( 'reseller_cost' => $platform_price ), array( 'id' => $order_id ) );
		return true;
	}

	/* ---------- Extension ---------- */

	/**
	 * Resolve the customer's selected proxy rows into provider IDs.
	 *
	 * @return array|WP_Error [ type, ids (provider ids / order ids), rows ]
	 */
	public static function extend_targets( $user_id, array $proxy_ids ) {
		global $wpdb;
		$proxy_ids = array_values( array_unique( array_filter( array_map( 'intval', $proxy_ids ) ) ) );
		if ( ! $proxy_ids ) {
			return new WP_Error( 'zp_input', 'Select at least one proxy to extend.' );
		}
		$in   = implode( ',', $proxy_ids );
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'proxies' ) . " WHERE user_id = %d AND id IN ($in)", $user_id ) ); // phpcs:ignore
		if ( count( $rows ) !== count( $proxy_ids ) ) {
			return new WP_Error( 'zp_input', 'Some of the selected proxies could not be found.' );
		}
		$types = array_unique( wp_list_pluck( $rows, 'proxy_type' ) );
		if ( 1 !== count( $types ) ) {
			return new WP_Error( 'zp_input', 'Extend one proxy type at a time.' );
		}
		$type = $types[0];
		if ( 'ipv6' === $type ) {
			$ids = array_values( array_unique( array_filter( wp_list_pluck( $rows, 'upstream_order_id' ) ) ) );
			// IPv6 is renewed per provider order: include every proxy of those orders, all must be this customer's.
			$place = implode( ',', array_fill( 0, count( $ids ), '%s' ) );
			$all   = $ids ? $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'proxies' ) . " WHERE proxy_type = 'ipv6' AND upstream_order_id IN ($place)", $ids ) ) : array(); // phpcs:ignore
			foreach ( $all as $r ) {
				if ( (int) $r->user_id !== (int) $user_id ) {
					return new WP_Error( 'zp_input', 'This IPv6 batch cannot be extended. Please contact support.' );
				}
			}
			$rows = $all;
		} else {
			$ids = array_values( wp_list_pluck( $rows, 'upstream_id' ) );
		}
		if ( ! $ids ) {
			return new WP_Error( 'zp_input', 'Nothing to extend.' );
		}
		return array( 'type' => $type, 'ids' => $ids, 'rows' => $rows );
	}

	public static function extend_quote( $user_id, array $proxy_ids, $days ) {
		$allowed = zp_allowed_days();
		if ( ! in_array( (int) $days, $allowed, true ) ) {
			return new WP_Error( 'zp_input', 'Choose a renewal period.' );
		}
		$t = self::extend_targets( $user_id, $proxy_ids );
		if ( is_wp_error( $t ) ) {
			return $t;
		}
		$res = ZP_API::extend_price( $t['type'], (int) $days, $t['ids'] );
		if ( is_wp_error( $res ) ) {
			return self::public_error( $res );
		}
		$cost = isset( $res['amount'] ) ? (float) $res['amount'] : 0;
		if ( $cost <= 0 ) {
			return new WP_Error( 'zp_api', 'The provider did not return a renewal price.' );
		}
		$reseller = ZP_Resellers::of_user( $user_id );
		$platform = self::markup( $cost, $t['type'], $reseller || ZP_Resellers::is_reseller( $user_id ) );
		return array( 'cost' => $cost, 'platform' => $platform, 'total' => $reseller ? ZP_Resellers::price( $platform, $reseller ) : $platform, 'count' => count( $t['rows'] ), 'targets' => $t, 'reseller' => $reseller );
	}

	public static function extend( $user_id, array $proxy_ids, $days ) {
		global $wpdb;
		$days = (int) $days;
		if ( ! self::lock() ) {
			return new WP_Error( 'zp_busy', 'The store is busy. Please try again in a few seconds.' );
		}
		try {
			$q = self::extend_quote( $user_id, $proxy_ids, $days );
			if ( is_wp_error( $q ) ) {
				return $q;
			}
			$t        = $q['targets'];
			$reseller = $q['reseller'];
			if ( ZP_Wallet::balance( $user_id ) < $q['total'] ) {
				return new WP_Error( 'zp_funds', 'Your wallet balance is too low for this renewal. Please add funds.' );
			}
			if ( $reseller && ! self::reseller_can_pay( $reseller, $q['platform'] ) ) {
				return new WP_Error( 'zp_unavailable', 'Renewals are temporarily unavailable. Please contact support.' );
			}
			$wpdb->insert(
				zp_table( 'orders' ),
				array(
					'user_id'    => $user_id,
					'kind'       => 'extend',
					'proxy_type' => $t['type'],
					'country'    => $t['rows'][0]->country,
					'days'       => $days,
					'qty'        => $q['count'],
					'cost'       => $q['cost'],
					'fx_rate'    => zp_fx(),
					'amount'     => $q['total'],
					'reseller_id' => $reseller ? $reseller->user_id : 0,
					'status'     => 'pending',
					'pre_ids'    => wp_json_encode( $t['ids'] ),
					'created_at' => zp_now(),
					'updated_at' => zp_now(),
				)
			);
			$order_id = (int) $wpdb->insert_id;
			$desc     = sprintf( 'Extend %d × %s proxy by %d days', $q['count'], zp_type_label( $t['type'] ), $days );
			if ( ! $order_id || ! ZP_Wallet::debit( $user_id, $q['total'], 'extend', 'order-' . $order_id, $desc ) ) {
				if ( $order_id ) {
					self::set_status( $order_id, 'failed', 'Insufficient balance' );
				}
				return new WP_Error( 'zp_funds', 'Your wallet balance is too low for this renewal. Please add funds.' );
			}
			if ( $reseller && ! self::charge_reseller( $order_id, $reseller, $q['platform'], $desc ) ) {
				self::refund( $order_id, 'Store wallet could not cover the renewal' );
				return new WP_Error( 'zp_unavailable', 'Renewals are temporarily unavailable. Please contact support.' );
			}
			$res = ZP_API::extend( $t['type'], $days, $t['ids'] );
			if ( is_wp_error( $res ) ) {
				if ( 'zp_http' === $res->get_error_code() ) {
					self::set_status( $order_id, 'review', 'No confirmation from provider: ' . $res->get_error_message() );
					return $order_id;
				}
				self::refund( $order_id, $res->get_error_message() );
				return self::public_error( $res );
			}
			self::set_status( $order_id, 'completed' );
			self::sync_type( $t['type'] );
			return $order_id;
		} finally {
			self::unlock();
		}
	}

	/* ---------- Sync ---------- */

	/**
	 * Refresh local proxies of one type from the provider and deliver
	 * newly provisioned proxies to waiting orders.
	 */
	public static function sync_type( $type ) {
		global $wpdb;
		$res = ZP_API::proxies( $type );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$remote = ZP_API::flatten( $type, $res );
		$table  = zp_table( 'proxies' );
		$known  = $wpdb->get_col( $wpdb->prepare( "SELECT upstream_id FROM $table WHERE proxy_type = %s", $type ) ); // phpcs:ignore
		$known  = array_flip( $known );

		foreach ( $remote as $uid => $row ) {
			if ( isset( $known[ $uid ] ) ) {
				$upd = $row;
				unset( $upd['upstream_id'], $upd['proxy_type'] );
				$wpdb->update( $table, $upd, array( 'proxy_type' => $type, 'upstream_id' => $uid ) );
			}
		}

		$waiting = $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . zp_table( 'orders' ) . " WHERE kind = 'purchase' AND proxy_type = %s AND status IN ('processing','review') ORDER BY id ASC", $type )
		);
		foreach ( $waiting as $o ) {
			$pre  = array_flip( (array) json_decode( (string) $o->pre_ids, true ) );
			$have = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $table WHERE order_id = %d", $o->id ) ); // phpcs:ignore
			$need = (int) $o->qty - $have;
			foreach ( $remote as $uid => $row ) {
				if ( $need <= 0 ) {
					break;
				}
				if ( isset( $known[ $uid ] ) || isset( $pre[ $uid ] ) || $row['country'] !== strtoupper( $o->country ) ) {
					continue;
				}
				$row['user_id']    = $o->user_id;
				$row['order_id']   = $o->id;
				$row['created_at'] = zp_now();
				if ( $wpdb->insert( $table, $row ) ) {
					$known[ $uid ] = true;
					--$need;
				}
			}
			if ( $need <= 0 ) {
				self::set_status( $o->id, 'completed', '' );
			}
		}
		return count( $remote );
	}

	public static function cron_sync() {
		if ( '' === trim( (string) zp_get( 'api_key' ) ) ) {
			return;
		}
		foreach ( array_keys( zp_enabled_types() ) as $type ) {
			if ( self::lock() ) {
				try {
					self::sync_type( $type );
				} finally {
					self::unlock();
				}
			}
		}
	}

	/** Lightweight on-demand sync when a customer has orders still being delivered. */
	public static function maybe_sync_user( $user_id ) {
		global $wpdb;
		$types = $wpdb->get_col(
			$wpdb->prepare( 'SELECT DISTINCT proxy_type FROM ' . zp_table( 'orders' ) . " WHERE user_id = %d AND kind = 'purchase' AND status = 'processing'", $user_id )
		);
		if ( ! $types || get_transient( 'zp_usync_' . $user_id ) ) {
			return;
		}
		set_transient( 'zp_usync_' . $user_id, 1, 45 );
		foreach ( $types as $type ) {
			if ( self::lock() ) {
				try {
					self::sync_type( $type );
				} finally {
					self::unlock();
				}
			}
		}
	}

	/* ---------- Queries ---------- */

	public static function user_proxies( $user_id, $filters = array() ) {
		global $wpdb;
		$where = array( 'user_id = %d' );
		$args  = array( $user_id );
		if ( ! empty( $filters['type'] ) ) {
			$where[] = 'proxy_type = %s';
			$args[]  = $filters['type'];
		}
		if ( ! empty( $filters['state'] ) ) {
			$where[] = 'active' === $filters['state'] ? 'date_end > %s' : 'date_end <= %s';
			$args[]  = zp_now();
		}
		if ( ! empty( $filters['q'] ) ) {
			$where[] = '(ip LIKE %s OR note LIKE %s OR country LIKE %s)';
			$like    = '%' . $wpdb->esc_like( $filters['q'] ) . '%';
			array_push( $args, $like, $like, $like );
		}
		if ( ! empty( $filters['ids'] ) ) {
			$where[] = 'id IN (' . implode( ',', array_map( 'intval', $filters['ids'] ) ) . ')';
		}
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'proxies' ) . ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY date_end DESC, id DESC LIMIT 2000', $args ) ); // phpcs:ignore
	}

	public static function proxy_state( $p ) {
		$end = $p->date_end ? strtotime( $p->date_end . ' UTC' ) : 0;
		if ( $end <= time() ) {
			return 'expired';
		}
		return $end - time() < 3 * DAY_IN_SECONDS ? 'expiring' : 'active';
	}

	public static function user_stats( $user_id ) {
		global $wpdb;
		$t   = zp_table( 'proxies' );
		$now = zp_now();
		return array(
			'active'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE user_id = %d AND date_end > %s", $user_id, $now ) ), // phpcs:ignore
			'expiring' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE user_id = %d AND date_end > %s AND date_end < %s", $user_id, $now, gmdate( 'Y-m-d H:i:s', time() + 3 * DAY_IN_SECONDS ) ) ), // phpcs:ignore
			'orders'   => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'orders' ) . ' WHERE user_id = %d', $user_id ) ),
			'spent'    => (float) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . zp_table( 'orders' ) . " WHERE user_id = %d AND refunded = 0 AND status IN ('completed','processing','review')", $user_id ) ),
		);
	}

	public static function user_orders( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'orders' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d', $user_id, $limit, $offset ) );
	}

	/** Build export lines for a set of proxy rows. */
	public static function export_lines( array $rows, $format, $protocol ) {
		$lines = array();
		foreach ( $rows as $p ) {
			$port = 'socks5' === $protocol ? ( $p->socks5_port ? $p->socks5_port : $p->https_port ) : ( $p->https_port ? $p->https_port : $p->socks5_port );
			$host = false !== strpos( $p->ip, ':' ) ? '[' . $p->ip . ']' : $p->ip;
			switch ( $format ) {
				case 'user:pass@ip:port':
					$lines[] = ( $p->login ? $p->login . ':' . $p->password . '@' : '' ) . $host . ':' . $port;
					break;
				case 'url':
					$scheme  = 'socks5' === $protocol ? 'socks5' : 'http';
					$lines[] = $scheme . '://' . ( $p->login ? rawurlencode( $p->login ) . ':' . rawurlencode( $p->password ) . '@' : '' ) . $host . ':' . $port;
					break;
				case 'ip:port':
					$lines[] = $host . ':' . $port;
					break;
				default:
					$lines[] = $host . ':' . $port . ( $p->login ? ':' . $p->login . ':' . $p->password : '' );
			}
		}
		return $lines;
	}
}
