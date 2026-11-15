<?php
/**
 * Payments: Flutterwave Standard checkout for customer orders (paid to the hotspot owner's
 * Flutterwave account) and for owner subscriptions (paid to the platform's account).
 */
defined( 'ABSPATH' ) || exit;

function zxc_reference( $prefix = 'ZX' ) {
	return $prefix . strtoupper( base_convert( (string) time(), 10, 36 ) ) . strtoupper( bin2hex( random_bytes( 3 ) ) );
}

/* -------------------------------------------------------------------------
 * Flutterwave API
 * ---------------------------------------------------------------------- */

function zxc_flw_request( $method, $path, $secret, $body = null ) {
	$args = array(
		'method'  => $method,
		'timeout' => 25,
		'headers' => array(
			'Authorization' => 'Bearer ' . $secret,
			'Content-Type'  => 'application/json',
		),
	);
	if ( null !== $body ) {
		$args['body'] = wp_json_encode( $body );
	}
	$res = wp_remote_request( 'https://api.flutterwave.com/v3' . $path, $args );
	if ( is_wp_error( $res ) ) {
		return array( 'ok' => false, 'message' => $res->get_error_message(), 'json' => array() );
	}
	$json = json_decode( wp_remote_retrieve_body( $res ), true );
	$code = wp_remote_retrieve_response_code( $res );
	return array(
		'ok'      => $code >= 200 && $code < 300 && is_array( $json ) && 'success' === ( $json['status'] ?? '' ),
		'message' => is_array( $json ) ? ( $json['message'] ?? '' ) : '',
		'json'    => is_array( $json ) ? $json : array(),
	);
}

/** Start a hosted checkout and return the payment link. */
function zxc_flw_checkout( $secret, array $o ) {
	$r = zxc_flw_request(
		'POST',
		'/payments',
		$secret,
		array(
			'tx_ref'          => $o['reference'],
			'amount'          => (int) $o['amount'],
			'currency'        => 'NGN',
			'redirect_url'    => $o['redirect'],
			'payment_options' => 'card,banktransfer,ussd',
			'customer'        => array_filter(
				array(
					'email'       => $o['email'],
					'phonenumber' => $o['phone'] ?? '',
					'name'        => $o['name'] ?? ( $o['phone'] ?? $o['email'] ),
				)
			),
			'customizations'  => array_filter(
				array(
					'title' => $o['title'] ?? 'Wi-Fi access',
					'logo'  => ( ! empty( $o['logo'] ) && 0 === strpos( $o['logo'], 'https://' ) ) ? $o['logo'] : '',
				)
			),
			'meta'            => $o['meta'] ?? array(),
		)
	);
	if ( ! $r['ok'] || empty( $r['json']['data']['link'] ) ) {
		return new WP_Error( 'zxc_flw', $r['message'] ? $r['message'] : 'Flutterwave could not start the payment.' );
	}
	return $r['json']['data']['link'];
}

/** Verify a transaction by our reference. Returns status success|failed|pending. */
function zxc_flw_verify( $secret, $reference ) {
	$r = zxc_flw_request( 'GET', '/transactions/verify_by_reference?tx_ref=' . rawurlencode( $reference ), $secret );
	if ( ! $r['ok'] || empty( $r['json']['data'] ) ) {
		return array( 'status' => 'pending' ); // e.g. "No transaction was found" until the customer pays
	}
	$d      = $r['json']['data'];
	$status = 'pending';
	if ( 'successful' === ( $d['status'] ?? '' ) ) {
		$status = 'success';
	} elseif ( 'failed' === ( $d['status'] ?? '' ) ) {
		$status = 'failed';
	}
	return array(
		'status'   => $status,
		'amount'   => (float) ( $d['amount'] ?? 0 ),
		'currency' => $d['currency'] ?? '',
		'channel'  => $d['payment_type'] ?? '',
	);
}

/** Flutterwave sends the secret hash set in its dashboard in the verif-hash header. */
function zxc_flw_webhook_valid( $expected_hash, WP_REST_Request $req ) {
	$got = (string) $req->get_header( 'verif-hash' );
	return '' !== $expected_hash && '' !== $got && hash_equals( $expected_hash, $got );
}

/* -------------------------------------------------------------------------
 * Customer orders
 * ---------------------------------------------------------------------- */

function zxc_create_order( $t, $plan, array $a ) {
	global $wpdb;
	$ref = zxc_reference( 'ZX' );
	$wpdb->insert(
		zxc_table( 'transactions' ),
		array(
			'tenant_id' => $t->id,
			'reference' => $ref,
			'plan_id'   => $plan->id,
			'amount'    => (int) $plan->price,
			'phone'     => $a['phone'],
			'email'     => $a['email'],
			'mac'       => $a['mac'] ?? '',
			'login_url' => $a['login_url'] ?? '',
			'dst'       => $a['dst'] ?? '',
			'gateway'   => $a['gateway'],
			'created'   => time(),
		)
	);
	return zxc_order( $ref );
}

function zxc_order( $ref, $tenant_id = null ) {
	global $wpdb;
	$o = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT t.*, p.name AS plan_name, v.code AS voucher_code, v.id AS voucher_row
			FROM ' . zxc_table( 'transactions' ) . ' t JOIN ' . zxc_table( 'plans' ) . ' p ON p.id = t.plan_id
			LEFT JOIN ' . zxc_table( 'vouchers' ) . ' v ON v.id = t.voucher_id WHERE t.reference = %s',
			$ref
		)
	);
	if ( $o && null !== $tenant_id && (int) $o->tenant_id !== (int) $tenant_id ) {
		return null;
	}
	return $o;
}

/** Mark an order paid and issue its voucher exactly once. */
function zxc_fulfill_order( $ref, $channel = '', $amount_paid = null ) {
	global $wpdb;
	$lock = 'zxc_fulfill_' . md5( $ref );
	$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $lock ) );
	try {
		$o = zxc_order( $ref );
		if ( ! $o || 'success' === $o->status ) {
			return $o;
		}
		if ( null !== $amount_paid && $amount_paid < (int) $o->amount ) {
			return $o; // underpaid: leave pending for manual review
		}
		$v = zxc_create_voucher( $o->tenant_id, $o->plan_id, 'online', null, $o->phone );
		$wpdb->update(
			zxc_table( 'transactions' ),
			array( 'status' => 'success', 'voucher_id' => $v->id, 'channel' => $channel, 'paid_at' => time() ),
			array( 'id' => $o->id )
		);
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
	return zxc_order( $ref );
}

function zxc_fail_order( $ref ) {
	global $wpdb;
	$wpdb->update( zxc_table( 'transactions' ), array( 'status' => 'failed' ), array( 'reference' => $ref, 'status' => 'pending' ) );
}

/** Ask Flutterwave about a pending order and fulfil it if paid. */
function zxc_verify_order( $ref ) {
	$o = zxc_order( $ref );
	if ( ! $o || 'pending' !== $o->status || 'flutterwave' !== $o->gateway ) {
		return $o;
	}
	$t = zxc_tenant( $o->tenant_id );
	$r = zxc_flw_verify( $t->flw_secret, $ref );
	if ( 'success' === $r['status'] && ( '' === $r['currency'] || 'NGN' === $r['currency'] ) ) {
		return zxc_fulfill_order( $ref, $r['channel'], $r['amount'] );
	}
	if ( 'failed' === $r['status'] ) {
		zxc_fail_order( $ref );
	}
	return zxc_order( $ref );
}

/* -------------------------------------------------------------------------
 * Owner subscriptions (platform billing)
 * ---------------------------------------------------------------------- */

function zxc_billing_ready() {
	return '' !== zxc_setting( 'flw_public' ) && '' !== zxc_setting( 'flw_secret' );
}

function zxc_create_subscription( $t, $tier, $months = 1 ) {
	global $wpdb;
	$tiers = zxc_tiers();
	$ref   = zxc_reference( 'ZXS' );
	$wpdb->insert(
		zxc_table( 'subscriptions' ),
		array(
			'tenant_id' => $t->id,
			'reference' => $ref,
			'tier'      => $tier,
			'amount'    => $tiers[ $tier ]['price'] * $months,
			'months'    => $months,
			'gateway'   => zxc_billing_ready() ? 'flutterwave' : 'demo',
			'created'   => time(),
		)
	);
	return zxc_subscription_row( $ref );
}

function zxc_subscription_row( $ref ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'subscriptions' ) . ' WHERE reference = %s', $ref ) );
}

function zxc_activate_subscription( $ref, $amount_paid = null ) {
	global $wpdb;
	$s = zxc_subscription_row( $ref );
	if ( ! $s || 'success' === $s->status ) {
		return $s;
	}
	if ( null !== $amount_paid && $amount_paid < (int) $s->amount ) {
		return $s;
	}
	$t    = zxc_tenant( $s->tenant_id );
	$from = max( time(), (int) $t->sub_ends );
	$wpdb->update( zxc_table( 'subscriptions' ), array( 'status' => 'success', 'paid_at' => time() ), array( 'id' => $s->id ) );
	zxc_update_tenant( $t->id, array( 'tier' => $s->tier, 'sub_ends' => $from + $s->months * 30 * DAY_IN_SECONDS ) );
	return zxc_subscription_row( $ref );
}

function zxc_verify_subscription( $ref ) {
	$s = zxc_subscription_row( $ref );
	if ( ! $s || 'pending' !== $s->status || 'flutterwave' !== $s->gateway ) {
		return $s;
	}
	$r = zxc_flw_verify( zxc_setting( 'flw_secret' ), $ref );
	if ( 'success' === $r['status'] ) {
		return zxc_activate_subscription( $ref, $r['amount'] );
	}
	if ( 'failed' === $r['status'] ) {
		global $wpdb;
		$wpdb->update( zxc_table( 'subscriptions' ), array( 'status' => 'failed' ), array( 'id' => $s->id ) );
	}
	return zxc_subscription_row( $ref );
}

/* -------------------------------------------------------------------------
 * Owner wallet: deposits via Flutterwave, spend on subscriptions
 * ---------------------------------------------------------------------- */

function zxc_wallet_balance( $tenant_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'wallet' ) . " WHERE tenant_id = %d AND status = 'success'", $tenant_id ) );
}

function zxc_wallet_row( $ref ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'wallet' ) . ' WHERE reference = %s', $ref ) );
}

function zxc_wallet_history( $tenant_id, $limit = 100 ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'wallet' ) . ' WHERE tenant_id = %d ORDER BY id DESC LIMIT %d', $tenant_id, $limit ) );
}

function zxc_wallet_create_deposit( $t, $amount ) {
	global $wpdb;
	$ref = zxc_reference( 'ZXW' );
	$wpdb->insert(
		zxc_table( 'wallet' ),
		array(
			'tenant_id' => $t->id,
			'type'      => 'deposit',
			'amount'    => (int) $amount,
			'reference' => $ref,
			'gateway'   => zxc_billing_ready() ? 'flutterwave' : 'demo',
			'note'      => 'Wallet top-up',
			'created'   => time(),
		)
	);
	return zxc_wallet_row( $ref );
}

/** Mark a deposit successful exactly once. */
function zxc_wallet_complete( $ref, $amount_paid = null, $channel = '' ) {
	global $wpdb;
	$w = zxc_wallet_row( $ref );
	if ( ! $w || 'pending' !== $w->status ) {
		return $w;
	}
	if ( null !== $amount_paid && $amount_paid < (int) $w->amount ) {
		return $w;
	}
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . zxc_table( 'wallet' ) . " SET status = 'success', channel = %s, completed = %d WHERE id = %d AND status = 'pending'", $channel, time(), $w->id ) );
	return zxc_wallet_row( $ref );
}

function zxc_wallet_verify( $ref ) {
	$w = zxc_wallet_row( $ref );
	if ( ! $w || 'pending' !== $w->status || 'flutterwave' !== $w->gateway ) {
		return $w;
	}
	$r = zxc_flw_verify( zxc_setting( 'flw_secret' ), $ref );
	if ( 'success' === $r['status'] ) {
		return zxc_wallet_complete( $ref, $r['amount'], $r['channel'] );
	}
	if ( 'failed' === $r['status'] ) {
		global $wpdb;
		$wpdb->update( zxc_table( 'wallet' ), array( 'status' => 'failed' ), array( 'id' => $w->id ) );
	}
	return zxc_wallet_row( $ref );
}

/** Add a ledger entry (negative = debit). Returns false when funds are insufficient. */
function zxc_wallet_entry( $tenant_id, $type, $amount, $note, $gateway = 'wallet' ) {
	global $wpdb;
	$lock = 'zxc_wallet_' . (int) $tenant_id;
	$wpdb->query( $wpdb->prepare( 'SELECT GET_LOCK(%s, 10)', $lock ) );
	try {
		if ( $amount < 0 && zxc_wallet_balance( $tenant_id ) + $amount < 0 ) {
			return false;
		}
		$wpdb->insert(
			zxc_table( 'wallet' ),
			array(
				'tenant_id' => (int) $tenant_id,
				'type'      => $type,
				'amount'    => (int) $amount,
				'reference' => zxc_reference( 'ZXL' ),
				'status'    => 'success',
				'gateway'   => $gateway,
				'note'      => substr( $note, 0, 190 ),
				'created'   => time(),
				'completed' => time(),
			)
		);
		return true;
	} finally {
		$wpdb->query( $wpdb->prepare( 'SELECT RELEASE_LOCK(%s)', $lock ) );
	}
}

/** Pay a subscription from the wallet balance. */
function zxc_wallet_pay_subscription( $t, $tier, $months ) {
	$tiers  = zxc_tiers();
	$amount = $tiers[ $tier ]['price'] * $months;
	if ( ! zxc_wallet_entry( $t->id, 'subscription', -$amount, $tiers[ $tier ]['name'] . ' plan · ' . $months . ' month' . ( $months > 1 ? 's' : '' ) ) ) {
		return false;
	}
	global $wpdb;
	$ref = zxc_reference( 'ZXS' );
	$wpdb->insert( zxc_table( 'subscriptions' ), array( 'tenant_id' => $t->id, 'reference' => $ref, 'tier' => $tier, 'amount' => $amount, 'months' => $months, 'gateway' => 'wallet', 'created' => time() ) );
	zxc_activate_subscription( $ref );
	return true;
}
