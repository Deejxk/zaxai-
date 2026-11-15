<?php
/**
 * Customer wallets: Wi-Fi customers keep a balance with a hotspot (phone number + PIN),
 * top it up through the owner's Flutterwave account and buy plans from it.
 */
defined( 'ABSPATH' ) || exit;

function zxc_customer( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'customers' ) . ' WHERE id = %d', $id ) );
}

function zxc_customer_by_phone( $tenant_id, $phone ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'customers' ) . ' WHERE tenant_id = %d AND phone = %s', $tenant_id, $phone ) );
}

function zxc_customer_balance( $customer_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'cledger' ) . " WHERE customer_id = %d AND status = 'success'", $customer_id ) );
}

function zxc_customer_history( $customer_id, $limit = 30 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'cledger' ) . " WHERE customer_id = %d AND status <> 'pending' ORDER BY id DESC LIMIT %d", $customer_id, $limit ) );
}

function zxc_cledger_row( $ref ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'cledger' ) . ' WHERE reference = %s', $ref ) );
}

/* ---------- Session (signed cookie per hotspot) ---------- */

function zxc_customer_cookie_name( $t ) {
	return 'zxc_cw_' . (int) $t->id;
}

function zxc_customer_session( $t ) {
	$name = zxc_customer_cookie_name( $t );
	if ( empty( $_COOKIE[ $name ] ) ) {
		return null;
	}
	$parts = explode( '.', sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) );
	if ( 3 !== count( $parts ) || (int) $parts[1] < time() ) {
		return null;
	}
	$c = zxc_customer( (int) $parts[0] );
	if ( ! $c || (int) $c->tenant_id !== (int) $t->id ) {
		return null;
	}
	$sig = hash_hmac( 'sha256', $parts[0] . '.' . $parts[1] . '.' . $c->pin_hash, wp_salt( 'auth' ) );
	return hash_equals( $sig, $parts[2] ) ? $c : null;
}

function zxc_customer_set_session( $t, $c ) {
	$exp = time() + 30 * DAY_IN_SECONDS;
	$sig = hash_hmac( 'sha256', $c->id . '.' . $exp . '.' . $c->pin_hash, wp_salt( 'auth' ) );
	setcookie( zxc_customer_cookie_name( $t ), $c->id . '.' . $exp . '.' . $sig, array( 'expires' => $exp, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
}

function zxc_customer_clear_session( $t ) {
	setcookie( zxc_customer_cookie_name( $t ), '', array( 'expires' => time() - 3600, 'path' => '/', 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
}

/**
 * Sign in, or open a wallet on first use. Returns array( customer|null, error ).
 */
function zxc_customer_auth( $t, $phone, $pin, $name = '' ) {
	global $wpdb;
	if ( ! zxc_valid_phone( $phone ) ) {
		return array( null, 'Please enter a valid Nigerian phone number.' );
	}
	if ( ! preg_match( '/^\d{4}$/', $pin ) ) {
		return array( null, 'Your PIN must be exactly 4 digits.' );
	}
	if ( zxc_throttled( 'cwpin' . $t->id . $phone, 6, 900 ) ) {
		return array( null, 'Too many attempts. Please wait 15 minutes and try again.' );
	}
	$c = zxc_customer_by_phone( $t->id, $phone );
	if ( ! $c ) {
		$wpdb->insert(
			zxc_table( 'customers' ),
			array( 'tenant_id' => $t->id, 'phone' => $phone, 'name' => substr( $name, 0, 120 ), 'pin_hash' => wp_hash_password( $pin ), 'created' => time(), 'last_login' => time() )
		);
		return array( zxc_customer( $wpdb->insert_id ), '' );
	}
	if ( '' === $c->pin_hash ) {
		$wpdb->update( zxc_table( 'customers' ), array( 'pin_hash' => wp_hash_password( $pin ) ), array( 'id' => $c->id ) );
	} elseif ( ! wp_check_password( $pin, $c->pin_hash ) ) {
		return array( null, 'That PIN is not correct for this phone number.' );
	}
	$wpdb->update( zxc_table( 'customers' ), array( 'last_login' => time() ), array( 'id' => $c->id ) );
	return array( zxc_customer( $c->id ), '' );
}

/* ---------- Ledger ---------- */

/** Add a completed ledger entry (negative = debit). False when funds are short. */
function zxc_customer_entry( $tenant_id, $customer_id, $type, $amount, $note, $gateway = 'wallet' ) {
	global $wpdb;
	$lock = 'zxc_cw_' . (int) $customer_id;
	$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $lock ) );
	try {
		if ( $amount < 0 && zxc_customer_balance( $customer_id ) + $amount < 0 ) {
			return false;
		}
		$wpdb->insert(
			zxc_table( 'cledger' ),
			array(
				'tenant_id'   => (int) $tenant_id,
				'customer_id' => (int) $customer_id,
				'type'        => $type,
				'amount'      => (int) $amount,
				'reference'   => zxc_reference( 'ZXL' ),
				'status'      => 'success',
				'gateway'     => $gateway,
				'note'        => substr( $note, 0, 190 ),
				'created'     => time(),
				'completed'   => time(),
			)
		);
		return true;
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

function zxc_customer_create_deposit( $t, $c, $amount ) {
	global $wpdb;
	$ref = zxc_reference( 'ZXC' );
	$wpdb->insert(
		zxc_table( 'cledger' ),
		array(
			'tenant_id'   => $t->id,
			'customer_id' => $c->id,
			'type'        => 'deposit',
			'amount'      => (int) $amount,
			'reference'   => $ref,
			'gateway'     => zxc_gateway_ready( $t ) ? 'flutterwave' : 'demo',
			'note'        => 'Wallet top-up',
			'created'     => time(),
		)
	);
	return zxc_cledger_row( $ref );
}

function zxc_customer_complete_deposit( $ref, $amount_paid = null, $channel = '' ) {
	global $wpdb;
	$w = zxc_cledger_row( $ref );
	if ( ! $w || 'pending' !== $w->status ) {
		return $w;
	}
	if ( null !== $amount_paid && $amount_paid < (int) $w->amount ) {
		return $w;
	}
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . zxc_table( 'cledger' ) . " SET status = 'success', channel = %s, completed = %d WHERE id = %d AND status = 'pending'", $channel, time(), $w->id ) );
	return zxc_cledger_row( $ref );
}

function zxc_customer_verify_deposit( $ref ) {
	global $wpdb;
	$w = zxc_cledger_row( $ref );
	if ( ! $w || 'pending' !== $w->status || 'flutterwave' !== $w->gateway ) {
		return $w;
	}
	$t = zxc_tenant( $w->tenant_id );
	$r = zxc_flw_verify( $t->flw_secret, $ref );
	if ( 'success' === $r['status'] && ( '' === $r['currency'] || 'NGN' === $r['currency'] ) ) {
		return zxc_customer_complete_deposit( $ref, $r['amount'], $r['channel'] );
	}
	if ( 'failed' === $r['status'] ) {
		$wpdb->update( zxc_table( 'cledger' ), array( 'status' => 'failed' ), array( 'id' => $w->id ) );
	}
	return zxc_cledger_row( $ref );
}

/**
 * Buy a plan from the wallet. Records a normal sale (gateway "wallet") so it shows in
 * sales and analytics. Returns the order, or an error string.
 */
function zxc_customer_buy_plan( $t, $c, $plan, array $ctx ) {
	if ( zxc_voucher_allowance( $t ) < 1 ) {
		return 'This hotspot has reached its monthly sales limit. Please contact the owner.';
	}
	if ( zxc_customer_balance( $c->id ) < (int) $plan->price ) {
		return 'Your balance is too low for this plan. Please top up first.';
	}
	$order = zxc_create_order(
		$t,
		$plan,
		array(
			'phone'     => $c->phone,
			'email'     => '',
			'mac'       => $ctx['mac'] ?? '',
			'login_url' => $ctx['login'] ?? '',
			'dst'       => $ctx['dst'] ?? '',
			'gateway'   => 'wallet',
		)
	);
	if ( ! zxc_customer_entry( $t->id, $c->id, 'purchase', -(int) $plan->price, $plan->name . ' · ' . $order->reference ) ) {
		zxc_fail_order( $order->reference );
		return 'Your balance is too low for this plan. Please top up first.';
	}
	return zxc_fulfill_order( $order->reference, 'wallet' );
}

/* ---------- Owner views ---------- */

function zxc_customer_wallets( $tenant_id, $q = '', $limit = 200 ) {
	global $wpdb;
	$where = $wpdb->prepare( 'c.tenant_id = %d', $tenant_id );
	if ( '' !== $q ) {
		$like   = '%' . $wpdb->esc_like( $q ) . '%';
		$where .= $wpdb->prepare( ' AND (c.phone LIKE %s OR c.name LIKE %s)', $like, $like );
	}
	return $wpdb->get_results(
		'SELECT c.*, COALESCE(SUM(CASE WHEN l.status = \'success\' THEN l.amount ELSE 0 END),0) AS balance,
		COALESCE(SUM(CASE WHEN l.status = \'success\' AND l.amount > 0 THEN l.amount ELSE 0 END),0) AS topped,
		MAX(l.created) AS last_tx
		FROM ' . zxc_table( 'customers' ) . ' c LEFT JOIN ' . zxc_table( 'cledger' ) . ' l ON l.customer_id = c.id
		WHERE ' . $where . ' GROUP BY c.id ORDER BY balance DESC, c.id DESC LIMIT ' . (int) $limit // phpcs:ignore WordPress.DB.PreparedSQL
	);
}

function zxc_customer_wallet_totals( $tenant_id ) {
	global $wpdb;
	$l = zxc_table( 'cledger' );
	return array(
		'held'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $l WHERE tenant_id = %d AND status = 'success'", $tenant_id ) ),
		'deposits' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $l WHERE tenant_id = %d AND status = 'success' AND amount > 0", $tenant_id ) ),
		'spent'    => (int) -$wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $l WHERE tenant_id = %d AND status = 'success' AND type = 'purchase'", $tenant_id ) ),
		'wallets'  => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'customers' ) . ' WHERE tenant_id = %d', $tenant_id ) ),
	);
}
