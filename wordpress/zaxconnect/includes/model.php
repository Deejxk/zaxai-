<?php
/**
 * Data model: tenants (hotspot businesses), plans, routers, vouchers.
 */
defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Subscription tiers
 * ---------------------------------------------------------------------- */

function zxc_tiers() {
	$t = array(
		'starter'    => array(
			'name'     => 'Starter',
			'desc'     => 'For a single small hotspot',
			'locations' => 1,
			'vouchers' => 200,
			'features' => array( '1 router location', 'Branded captive portal', 'Flutterwave payments', 'Up to 200 vouchers / month', 'Email support' ),
		),
		'business'   => array(
			'name'     => 'Business',
			'desc'     => 'For growing hotspot businesses',
			'locations' => 5,
			'vouchers' => 0,
			'features' => array( 'Up to 5 router locations', 'Unlimited vouchers', 'Printable voucher cards', 'Sales export (CSV)', 'WhatsApp support' ),
		),
		'enterprise' => array(
			'name'     => 'Enterprise',
			'desc'     => 'For ISPs and large estates',
			'locations' => 0,
			'vouchers' => 0,
			'features' => array( 'Unlimited locations', 'Unlimited vouchers', 'Priority onboarding', 'Dedicated account manager', 'Priority support' ),
		),
	);
	foreach ( $t as $k => &$v ) {
		$v['key']   = $k;
		$v['price'] = (int) zxc_setting( 'price_' . $k );
	}
	return $t;
}

/* -------------------------------------------------------------------------
 * Tenants
 * ---------------------------------------------------------------------- */

function zxc_reserved_slugs() {
	return array( 'www', 'app', 'api', 'admin', 'platform', 'static', 'login', 'signup', 'dashboard', 'help', 'support', 'billing', 'wp' );
}

function zxc_unique_slug( $name ) {
	global $wpdb;
	$base = sanitize_title( $name );
	$base = substr( preg_replace( '/[^a-z0-9-]/', '', $base ), 0, 40 );
	$base = trim( $base, '-' );
	if ( '' === $base ) {
		$base = 'hotspot';
	}
	if ( in_array( $base, zxc_reserved_slugs(), true ) ) {
		$base .= '-wifi';
	}
	$slug = $base;
	for ( $i = 2; $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . zxc_table( 'tenants' ) . ' WHERE slug = %s', $slug ) ); $i++ ) {
		$slug = $base . '-' . $i;
	}
	return $slug;
}

function zxc_tenant( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'tenants' ) . ' WHERE id = %d', $id ) );
}

function zxc_tenant_by_slug( $slug ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'tenants' ) . ' WHERE slug = %s', strtolower( $slug ) ) );
}

function zxc_tenant_for_user( $user_id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'tenants' ) . ' WHERE owner_id = %d ORDER BY id LIMIT 1', $user_id ) );
}

function zxc_default_plans() {
	// name, description, price, minutes, data MB, down, up, devices, popular
	return array(
		array( 'Quick Hour', 'Perfect for a quick browse', 100, 60, 0, 5, 2, 1, 0 ),
		array( '3 Hours', 'Stream, work and chat', 200, 180, 0, 5, 2, 1, 0 ),
		array( 'Daily Pass', 'Unlimited access for a full day', 350, 1440, 0, 8, 3, 1, 1 ),
		array( '2GB Bundle', '2GB to use within 3 days', 500, 4320, 2048, 10, 4, 1, 0 ),
		array( 'Weekly Pass', '7 days of unlimited browsing', 1500, 10080, 0, 8, 3, 2, 0 ),
		array( 'Monthly Pass', '30 days, share with 2 devices', 5000, 43200, 0, 10, 4, 2, 0 ),
	);
}

function zxc_create_tenant( array $a ) {
	global $wpdb;
	$now   = time();
	$days  = max( 1, (int) zxc_setting( 'trial_days' ) );
	$tiers = zxc_tiers();
	$wpdb->insert(
		zxc_table( 'tenants' ),
		array(
			'owner_id'    => (int) $a['owner_id'],
			'slug'        => zxc_unique_slug( $a['name'] ),
			'name'        => $a['name'],
			'tagline'     => 'Fast Wi-Fi. Pay small, browse big.',
			'brand_color' => zxc_is_hex( $a['brand_color'] ?? '' ) ? $a['brand_color'] : '#4F3BF0',
			'logo_url'    => $a['logo_url'] ?? '',
			'location'    => $a['location'] ?? '',
			'flw_public'  => $a['flw_public'] ?? '',
			'flw_secret'  => $a['flw_secret'] ?? '',
			'flw_hash'    => $a['flw_hash'] ?? '',
			'tier'        => isset( $tiers[ $a['tier'] ?? '' ] ) ? $a['tier'] : 'business',
			'trial_ends'  => $now + $days * DAY_IN_SECONDS,
			'created'     => $now,
		)
	);
	$tenant_id = (int) $wpdb->insert_id;
	$order     = 0;
	foreach ( zxc_default_plans() as $p ) {
		$price = $p[2];
		if ( 'Quick Hour' === $p[0] && ! empty( $a['price_hour'] ) ) {
			$price = (int) $a['price_hour'];
		}
		if ( 'Daily Pass' === $p[0] && ! empty( $a['price_daily'] ) ) {
			$price = (int) $a['price_daily'];
		}
		$wpdb->insert(
			zxc_table( 'plans' ),
			array(
				'tenant_id'     => $tenant_id,
				'name'          => $p[0],
				'description'   => $p[1],
				'price'         => $price,
				'duration_min'  => $p[3],
				'data_mb'       => $p[4],
				'download_mbps' => $p[5],
				'upload_mbps'   => $p[6],
				'devices'       => $p[7],
				'popular'       => $p[8],
				'sort_order'    => ++$order,
				'created'       => $now,
			)
		);
	}
	zxc_create_router( $tenant_id, ! empty( $a['location'] ) ? $a['location'] : 'Main location' );
	return zxc_tenant( $tenant_id );
}

function zxc_update_tenant( $id, array $values ) {
	global $wpdb;
	$allowed = array( 'name', 'tagline', 'brand_color', 'logo_url', 'support_phone', 'support_whatsapp', 'location', 'hotspot_dns', 'flw_public', 'flw_secret', 'flw_hash', 'demo_payments', 'tier', 'status', 'trial_ends', 'sub_ends' );
	$values  = array_intersect_key( $values, array_flip( $allowed ) );
	if ( $values ) {
		$wpdb->update( zxc_table( 'tenants' ), $values, array( 'id' => (int) $id ) );
	}
}

/** Subscription state of a tenant: trial, active, expired or suspended. */
function zxc_subscription( $t ) {
	$now   = time();
	$tiers = zxc_tiers();
	if ( 'suspended' === $t->status ) {
		return array( 'state' => 'suspended', 'active' => false, 'label' => 'Suspended', 'days' => 0, 'progress' => 1 );
	}
	if ( (int) $t->sub_ends > $now ) {
		$days = (int) ceil( ( $t->sub_ends - $now ) / DAY_IN_SECONDS );
		return array( 'state' => 'active', 'active' => true, 'label' => $tiers[ $t->tier ]['name'] . ' plan', 'days' => $days, 'ends' => (int) $t->sub_ends, 'progress' => max( 0, min( 1, 1 - $days / 30 ) ) );
	}
	if ( (int) $t->trial_ends > $now ) {
		$days  = (int) ceil( ( $t->trial_ends - $now ) / DAY_IN_SECONDS );
		$total = max( 1, (int) zxc_setting( 'trial_days' ) );
		return array( 'state' => 'trial', 'active' => true, 'label' => 'Free trial', 'days' => $days, 'ends' => (int) $t->trial_ends, 'progress' => max( 0, min( 1, 1 - $days / $total ) ) );
	}
	return array( 'state' => 'expired', 'active' => false, 'label' => 'Subscription expired', 'days' => 0, 'progress' => 1 );
}

function zxc_gateway_ready( $t ) {
	return '' !== $t->flw_public && '' !== $t->flw_secret;
}

function zxc_portal_url( $t, $path = '' ) {
	return home_url( '/h/' . $t->slug . ( $path ? '/' . ltrim( $path, '/' ) : '' ) );
}

function zxc_vouchers_this_month( $tenant_id ) {
	global $wpdb;
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'vouchers' ) . ' WHERE tenant_id = %d AND created >= %d', $tenant_id, zxc_lagos_month_start() ) );
}

/** Vouchers the tenant may still issue this month (PHP_INT_MAX when unlimited). */
function zxc_voucher_allowance( $t ) {
	$tiers = zxc_tiers();
	$limit = (int) ( $tiers[ $t->tier ]['vouchers'] ?? 0 );
	return $limit ? max( 0, $limit - zxc_vouchers_this_month( $t->id ) ) : PHP_INT_MAX;
}

function zxc_can_add_router( $t ) {
	global $wpdb;
	$tiers = zxc_tiers();
	$limit = (int) ( $tiers[ $t->tier ]['locations'] ?? 0 );
	if ( ! $limit ) {
		return true;
	}
	return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'routers' ) . ' WHERE tenant_id = %d', $t->id ) ) < $limit;
}

/* -------------------------------------------------------------------------
 * Plans & routers
 * ---------------------------------------------------------------------- */

function zxc_plans( $tenant_id, $only_active = false ) {
	global $wpdb;
	$sql = 'SELECT * FROM ' . zxc_table( 'plans' ) . ' WHERE tenant_id = %d' . ( $only_active ? ' AND active = 1' : '' ) . ' ORDER BY sort_order, price';
	return $wpdb->get_results( $wpdb->prepare( $sql, $tenant_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL
}

function zxc_plan( $tenant_id, $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'plans' ) . ' WHERE tenant_id = %d AND id = %d', $tenant_id, $id ) );
}

function zxc_plan_category( $p ) {
	$m = (int) $p->duration_min;
	if ( $m < 1440 ) {
		return 'hourly';
	}
	if ( $m < 10080 ) {
		return 'daily';
	}
	if ( $m < 43200 ) {
		return 'weekly';
	}
	return 'monthly';
}

function zxc_create_router( $tenant_id, $name ) {
	global $wpdb;
	$wpdb->insert(
		zxc_table( 'routers' ),
		array(
			'tenant_id' => (int) $tenant_id,
			'name'      => substr( $name, 0, 80 ),
			'token'     => 'zx' . strtolower( wp_generate_password( 22, false, false ) ),
			'created'   => time(),
		)
	);
	return (int) $wpdb->insert_id;
}

function zxc_routers( $tenant_id ) {
	global $wpdb;
	return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'routers' ) . ' WHERE tenant_id = %d ORDER BY id', $tenant_id ) );
}

/* -------------------------------------------------------------------------
 * Vouchers
 * ---------------------------------------------------------------------- */

function zxc_normalize_code( $c ) {
	return preg_replace( '/[^A-Z0-9]/', '', strtoupper( (string) $c ) );
}

function zxc_format_code( $c ) {
	$c = zxc_normalize_code( $c );
	return 8 === strlen( $c ) ? substr( $c, 0, 4 ) . '-' . substr( $c, 4 ) : $c;
}

function zxc_create_voucher( $tenant_id, $plan_id, $source = 'online', $batch = null, $phone = null ) {
	global $wpdb;
	$alphabet = '23456789ABCDEFGHJKMNPQRSTUVWXYZ';
	for ( $attempt = 0; $attempt < 10; $attempt++ ) {
		$code = '';
		for ( $i = 0; $i < 8; $i++ ) {
			$code .= $alphabet[ random_int( 0, strlen( $alphabet ) - 1 ) ];
		}
		$ok = $wpdb->insert(
			zxc_table( 'vouchers' ),
			array(
				'tenant_id' => (int) $tenant_id,
				'code'      => $code,
				'plan_id'   => (int) $plan_id,
				'source'    => $source,
				'batch'     => $batch,
				'phone'     => $phone,
				'created'   => time(),
			)
		);
		if ( $ok ) {
			return zxc_voucher_by_id( (int) $wpdb->insert_id );
		}
	}
	return null;
}

function zxc_voucher_by_id( $id ) {
	global $wpdb;
	return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'vouchers' ) . ' WHERE id = %d', $id ) );
}

/** A voucher with its plan details, optionally restricted to one tenant. */
function zxc_voucher_by_code( $code, $tenant_id = null ) {
	global $wpdb;
	$v = $wpdb->get_row(
		$wpdb->prepare(
			'SELECT v.*, p.name AS plan_name, p.duration_min, p.data_mb, p.download_mbps, p.upload_mbps, p.devices, p.price
			FROM ' . zxc_table( 'vouchers' ) . ' v JOIN ' . zxc_table( 'plans' ) . ' p ON p.id = v.plan_id WHERE v.code = %s',
			zxc_normalize_code( $code )
		)
	);
	if ( $v && null !== $tenant_id && (int) $v->tenant_id !== (int) $tenant_id ) {
		return null;
	}
	return $v;
}

/** What is left on a voucher (for status pages). */
function zxc_voucher_state( $v ) {
	$now   = time();
	$cap   = $v->data_mb > 0 ? (int) $v->data_mb * 1048576 : 0;
	$state = $v->status;
	if ( 'active' === $state && $v->expires_at && $v->expires_at <= $now ) {
		$state = 'expired';
	}
	if ( 'active' === $state && $cap && $v->bytes_used >= $cap ) {
		$state = 'exhausted';
	}
	$total = (int) $v->duration_min * 60;
	$left  = $v->expires_at ? max( 0, $v->expires_at - $now ) : $total;
	return array(
		'state'     => $state,
		'left'      => $left,
		'total'     => $total,
		'ratio'     => $total ? min( 1, $left / $total ) : 0,
		'cap'       => $cap,
		'used'      => (int) $v->bytes_used,
		'expires'   => (int) $v->expires_at,
		'activated' => (int) $v->activated_at,
	);
}

/** Expire vouchers whose time is up (called on every router sync and hourly). */
function zxc_expire_vouchers( $tenant_id = null ) {
	global $wpdb;
	$now = time();
	if ( $tenant_id ) {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . zxc_table( 'vouchers' ) . " SET status = 'expired', ended_at = %d WHERE tenant_id = %d AND status = 'active' AND expires_at > 0 AND expires_at <= %d", $now, $tenant_id, $now ) );
	} else {
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . zxc_table( 'vouchers' ) . " SET status = 'expired', ended_at = %d WHERE status = 'active' AND expires_at > 0 AND expires_at <= %d", $now, $now ) );
	}
}

add_action( 'zxc_housekeeping', function () {
	global $wpdb;
	zxc_expire_vouchers();
	$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . zxc_table( 'online' ) . ' WHERE seen_at < %d', time() - 7 * DAY_IN_SECONDS ) );
	$wpdb->query( $wpdb->prepare( 'UPDATE ' . zxc_table( 'transactions' ) . " SET status = 'failed' WHERE status = 'pending' AND created < %d", time() - DAY_IN_SECONDS ) );
} );
