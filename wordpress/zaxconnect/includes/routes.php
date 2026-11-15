<?php
/**
 * URL routing, REST endpoints and access rules.
 *
 *  /                      marketing landing page (optional homepage takeover)
 *  /signup, /login        owner sign-up wizard and login (outside wp-admin)
 *  /dashboard/...         hotspot owner dashboard
 *  /zx-admin/...          platform operator panel (WordPress administrators)
 *  /h/{slug}/...          a business's branded customer portal
 *  /wp-json/zaxconnect/v1 router sync, Flutterwave webhooks, portal AJAX
 */
defined( 'ABSPATH' ) || exit;

require_once ZXC_DIR . 'includes/controllers.php';

/* -------------------------------------------------------------------------
 * Roles: owners never see wp-admin
 * ---------------------------------------------------------------------- */

function zxc_add_role() {
	if ( ! get_role( 'zx_owner' ) ) {
		add_role( 'zx_owner', 'Hotspot Owner', array( 'read' => true ) );
	}
}

function zxc_is_owner_only( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user && $user->exists() && in_array( 'zx_owner', (array) $user->roles, true ) && ! user_can( $user, 'manage_options' );
}

add_action( 'admin_init', function () {
	if ( zxc_is_owner_only() && ! wp_doing_ajax() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		wp_safe_redirect( home_url( '/dashboard' ) );
		exit;
	}
} );

add_filter( 'show_admin_bar', function ( $show ) {
	return zxc_is_owner_only() ? false : $show;
} );

add_filter( 'login_redirect', function ( $redirect, $requested, $user ) {
	if ( $user instanceof WP_User && zxc_is_owner_only( $user ) ) {
		return home_url( '/dashboard' );
	}
	return $redirect;
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Rewrites
 * ---------------------------------------------------------------------- */

function zxc_register_rewrites() {
	add_rewrite_rule( '^signup/?$', 'index.php?zxc=signup', 'top' );
	add_rewrite_rule( '^login/?$', 'index.php?zxc=login', 'top' );
	add_rewrite_rule( '^logout/?$', 'index.php?zxc=logout', 'top' );
	add_rewrite_rule( '^dashboard(?:/(.*?))?/?$', 'index.php?zxc=dashboard&zxc_path=$matches[1]', 'top' );
	add_rewrite_rule( '^zx-admin(?:/(.*?))?/?$', 'index.php?zxc=platform&zxc_path=$matches[1]', 'top' );
	add_rewrite_rule( '^h/([a-z0-9-]+)(?:/(.*?))?/?$', 'index.php?zxc=portal&zxc_slug=$matches[1]&zxc_path=$matches[2]', 'top' );
}
add_action( 'init', 'zxc_register_rewrites' );

add_filter( 'query_vars', function ( $vars ) {
	return array_merge( $vars, array( 'zxc', 'zxc_path', 'zxc_slug' ) );
} );

// Flush rules once after an update changes them.
add_action( 'init', function () {
	if ( get_option( 'zxc_rewrite_version' ) !== ZXC_VERSION ) {
		flush_rewrite_rules( false );
		update_option( 'zxc_rewrite_version', ZXC_VERSION, false );
	}
}, 99 );

/** Tell page caches (LiteSpeed on Hostinger, etc.) not to cache dynamic pages. */
function zxc_no_cache() {
	if ( ! defined( 'DONOTCACHEPAGE' ) ) {
		define( 'DONOTCACHEPAGE', true );
	}
	do_action( 'litespeed_control_set_nocache', 'zaxconnect dynamic page' );
	nocache_headers();
}

add_action( 'template_redirect', function () {
	$route = get_query_var( 'zxc' );
	$path  = trim( (string) get_query_var( 'zxc_path' ), '/' );

	if ( ! $route ) {
		if ( is_front_page() && '1' === zxc_setting( 'takeover_home' ) && ! isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			zxc_render( zxc_view_landing() );
		}
		return;
	}
	zxc_no_cache();
	switch ( $route ) {
		case 'signup':
			zxc_ctl_signup();
			break;
		case 'login':
			zxc_ctl_login();
			break;
		case 'logout':
			wp_logout();
			wp_safe_redirect( home_url( '/login' ) );
			exit;
		case 'dashboard':
			zxc_ctl_dashboard( $path );
			break;
		case 'platform':
			zxc_ctl_platform( $path );
			break;
		case 'portal':
			$t = zxc_tenant_by_slug( get_query_var( 'zxc_slug' ) );
			if ( ! $t || 'suspended' === $t->status ) {
				zxc_render( zxc_view_portal_missing(), 404 );
			}
			zxc_ctl_portal( $t, $path );
			break;
	}
}, 0 );

/** Print a full page and stop WordPress from loading the theme. */
function zxc_render( $html, $status = 200 ) {
	status_header( $status );
	header( 'Content-Type: text/html; charset=utf-8' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: same-origin' );
	echo $html; // phpcs:ignore WordPress.Security.EscapeOutput -- views escape their own output
	exit;
}

function zxc_redirect( $url ) {
	wp_safe_redirect( $url, 303 );
	exit;
}

/* -------------------------------------------------------------------------
 * REST API
 * ---------------------------------------------------------------------- */

add_action( 'rest_api_init', function () {
	$ns = 'zaxconnect/v1';

	// Router check-in (MikroTik /tool fetch). Authenticated by the router token.
	register_rest_route( $ns, '/sync', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$body = zxc_router_sync( (string) $req->get_param( 't' ), (int) $req->get_param( 'a' ), (string) $req->get_param( 'p' ), (string) $req->get_param( 'o' ) );
			zxc_no_cache();
			header( 'Content-Type: text/plain; charset=utf-8' );
			if ( null === $body ) {
				status_header( 404 );
				echo "# unknown router\n";
			} else {
				echo $body; // phpcs:ignore WordPress.Security.EscapeOutput -- plain text for RouterOS
			}
			exit;
		},
	) );

	// Portal: order status (polled while a payment or router sync is pending).
	register_rest_route( $ns, '/order/(?P<ref>[A-Z0-9]+)', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$o = zxc_order( $req['ref'] );
			if ( ! $o ) {
				return new WP_REST_Response( array( 'status' => 'unknown' ), 404 );
			}
			if ( 'pending' === $o->status && 'flutterwave' === $o->gateway && $o->created < time() - 20 ) {
				$o = zxc_verify_order( $o->reference );
			}
			$ready = false;
			if ( 'success' === $o->status ) {
				$v     = zxc_voucher_by_id( $o->voucher_id );
				$ready = $v && ( zxc_voucher_on_router( $v ) || ! zxc_router_online( $o->tenant_id ) );
			}
			return array( 'status' => $o->status, 'ready' => $ready );
		},
	) );

	// Portal: check a voucher code before logging in through the router.
	register_rest_route( $ns, '/voucher/(?P<slug>[a-z0-9-]+)', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => 'zxc_rest_voucher_check',
	) );

	// Flutterwave webhooks: one per business, plus the platform's own.
	register_rest_route( $ns, '/flutterwave/(?P<slug>[a-z0-9-]+)', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true',
		'callback'            => function ( WP_REST_Request $req ) {
			$body = $req->get_json_params();
			$ref  = is_array( $body ) ? ( $body['data']['tx_ref'] ?? ( $body['txRef'] ?? '' ) ) : '';
			if ( 'platform' === $req['slug'] ) {
				if ( ! zxc_flw_webhook_valid( zxc_setting( 'flw_hash' ), $req ) ) {
					return new WP_REST_Response( array( 'ok' => false ), 401 );
				}
				if ( $ref ) {
					zxc_verify_subscription( sanitize_text_field( $ref ) );
				}
				return array( 'ok' => true );
			}
			$t = zxc_tenant_by_slug( $req['slug'] );
			if ( ! $t || ! zxc_flw_webhook_valid( $t->flw_hash, $req ) ) {
				return new WP_REST_Response( array( 'ok' => false ), 401 );
			}
			if ( $ref ) {
				$o = zxc_order( sanitize_text_field( $ref ), $t->id );
				if ( $o ) {
					zxc_verify_order( $o->reference );
				}
			}
			return array( 'ok' => true );
		},
	) );
} );

function zxc_rest_voucher_check( WP_REST_Request $req ) {
	$t = zxc_tenant_by_slug( $req['slug'] );
	if ( ! $t ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'Unknown hotspot.' ), 404 );
	}
	$code = zxc_normalize_code( (string) $req->get_param( 'code' ) );
	$v    = $code ? zxc_voucher_by_code( $code, $t->id ) : null;
	if ( ! $v ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => 'That voucher code is not valid. Please check and try again.' ), 404 );
	}
	$state    = zxc_voucher_state( $v );
	$messages = array(
		'expired'   => 'This voucher has expired. Please buy a new plan.',
		'exhausted' => 'This voucher has used up its data. Please buy a new plan.',
		'disabled'  => 'This voucher has been disabled. Please contact support.',
	);
	if ( isset( $messages[ $state['state'] ] ) ) {
		return new WP_REST_Response( array( 'ok' => false, 'message' => $messages[ $state['state'] ] ), 409 );
	}
	$link = zxc_login_link( zxc_hotspot_ctx( $t ), $code, zxc_portal_url( $t, 'connected' ) . '?code=' . $code );
	return array(
		'ok'        => true,
		'loginUrl'  => $link ? $link : null,
		'statusUrl' => zxc_portal_url( $t, 'status' ) . '?code=' . $code,
		'message'   => $link ? 'Connecting…' : 'Voucher is valid. Connect to the hotspot Wi-Fi to use it.',
	);
}

/* -------------------------------------------------------------------------
 * Hotspot context (device MAC + router login URL), kept in a cookie per business
 * ---------------------------------------------------------------------- */

function zxc_login_url_allowed( $url, $t ) {
	$p = wp_parse_url( $url );
	if ( empty( $p['host'] ) || ! in_array( $p['scheme'] ?? '', array( 'http', 'https' ), true ) ) {
		return false;
	}
	$h = strtolower( $p['host'] );
	if ( $t->hotspot_dns && strtolower( $t->hotspot_dns ) === $h ) {
		return true;
	}
	if ( preg_match( '/^(\d+)\.(\d+)\.\d+\.\d+$/', $h, $m ) ) {
		$a = (int) $m[1];
		$b = (int) $m[2];
		return 10 === $a || ( 172 === $a && $b >= 16 && $b <= 31 ) || ( 192 === $a && 168 === $b ) || ( 100 === $a && $b >= 64 && $b <= 127 );
	}
	return false === strpos( $h, '.' ) || (bool) preg_match( '/\.(wifi|lan|local|hotspot)$/', $h );
}

function zxc_hotspot_ctx( $t ) {
	$name = 'zxc_hs_' . (int) $t->id;
	if ( empty( $_COOKIE[ $name ] ) ) {
		return array();
	}
	$ctx = json_decode( base64_decode( sanitize_text_field( wp_unslash( $_COOKIE[ $name ] ) ) ), true );
	if ( ! is_array( $ctx ) ) {
		return array();
	}
	if ( ! empty( $ctx['login'] ) && ! zxc_login_url_allowed( $ctx['login'], $t ) ) {
		$ctx['login'] = '';
	}
	return $ctx;
}

function zxc_capture_hotspot_ctx( $t ) {
	if ( empty( $_GET['mac'] ) && empty( $_GET['link-login-only'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		return zxc_hotspot_ctx( $t );
	}
	$ctx = array(
		'mac'   => strtoupper( substr( preg_replace( '/[^0-9A-Fa-f:]/', '', zxc_get( 'mac', 40 ) ), 0, 17 ) ),
		'login' => esc_url_raw( zxc_get( 'link-login-only', 300 ) ),
		'dst'   => esc_url_raw( zxc_get( 'dst', 500 ) ),
	);
	if ( $ctx['login'] && ! zxc_login_url_allowed( $ctx['login'], $t ) ) {
		$ctx['login'] = '';
	}
	setcookie( 'zxc_hs_' . (int) $t->id, base64_encode( wp_json_encode( $ctx ) ), array(
		'expires'  => time() + 6 * HOUR_IN_SECONDS,
		'path'     => '/',
		'secure'   => is_ssl(),
		'httponly' => true,
		'samesite' => 'Lax',
	) );
	return $ctx;
}

/** The router login link that signs a device in with a voucher. */
function zxc_login_link( $ctx, $code, $dst = '' ) {
	if ( empty( $ctx['login'] ) ) {
		return '';
	}
	$args = array_filter( array( 'username' => $code, 'password' => $code, 'dst' => $dst ? $dst : ( $ctx['dst'] ?? '' ) ) );
	return add_query_arg( array_map( 'rawurlencode', $args ), $ctx['login'] );
}
