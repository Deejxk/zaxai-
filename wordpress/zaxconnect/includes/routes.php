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
	if ( ! get_role( 'zx_platform' ) ) {
		add_role( 'zx_platform', 'ZaxConnect Admin', array( 'read' => true, 'zx_platform' => true ) );
	}
	$admin = get_role( 'administrator' );
	if ( $admin && ! $admin->has_cap( 'zx_platform' ) ) {
		$admin->add_cap( 'zx_platform' );
	}
}
add_action( 'init', function () {
	if ( get_option( 'zxc_roles_version' ) !== ZXC_VERSION ) {
		zxc_add_role();
		update_option( 'zxc_roles_version', ZXC_VERSION, false );
	}
} );

/** Can this user use the ZaxConnect platform admin (/zx-admin)? */
function zxc_is_platform_admin( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	return $user && $user->exists() && ( user_can( $user, 'zx_platform' ) || user_can( $user, 'manage_options' ) );
}

/** Users who must never see wp-admin: hotspot owners and ZaxConnect staff (not WordPress administrators). */
function zxc_is_owner_only( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	if ( ! $user || ! $user->exists() || user_can( $user, 'manage_options' ) ) {
		return false;
	}
	return (bool) array_intersect( array( 'zx_owner', 'zx_platform' ), (array) $user->roles );
}

function zxc_home_panel( $user = null ) {
	$user = $user ? $user : wp_get_current_user();
	if ( zxc_tenant_for_user( $user->ID ) ) {
		return home_url( '/dashboard' );
	}
	return zxc_is_platform_admin( $user ) ? home_url( '/zx-admin' ) : home_url( '/signup' );
}

add_action( 'admin_init', function () {
	if ( zxc_is_owner_only() && ! wp_doing_ajax() && ! ( defined( 'DOING_CRON' ) && DOING_CRON ) ) {
		wp_safe_redirect( zxc_home_panel() );
		exit;
	}
} );

add_filter( 'show_admin_bar', function ( $show ) {
	return zxc_is_owner_only() ? false : $show;
} );

add_filter( 'login_redirect', function ( $redirect, $requested, $user ) {
	if ( $user instanceof WP_User && zxc_is_owner_only( $user ) ) {
		return zxc_home_panel( $user );
	}
	return $redirect;
}, 10, 3 );

/* -------------------------------------------------------------------------
 * Rewrites
 * ---------------------------------------------------------------------- */

function zxc_register_rewrites() {
	add_rewrite_rule( '^(features|pricing|about|contact|faq|guide|terms|privacy|refunds|forgot)/?$', 'index.php?zxc=page&zxc_path=$matches[1]', 'top' );
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
		case 'page':
			zxc_ctl_page( $path );
			break;
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
					$ref = sanitize_text_field( $ref );
					0 === strpos( $ref, 'ZXW' ) ? zxc_wallet_verify( $ref ) : zxc_verify_subscription( $ref );
				}
				return array( 'ok' => true );
			}
			$t = zxc_tenant_by_slug( $req['slug'] );
			if ( ! $t || ! zxc_flw_webhook_valid( $t->flw_hash, $req ) ) {
				return new WP_REST_Response( array( 'ok' => false ), 401 );
			}
			if ( $ref && 0 === strpos( $ref, 'ZXC' ) ) {
				$w = zxc_cledger_row( sanitize_text_field( $ref ) );
				if ( $w && (int) $w->tenant_id === (int) $t->id ) {
					zxc_customer_verify_deposit( $w->reference );
				}
			} elseif ( $ref ) {
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

/* -------------------------------------------------------------------------
 * Installable web app: manifest, service worker and offline page
 * ---------------------------------------------------------------------- */

add_action( 'init', 'zxc_pwa_routes', 1 );
function zxc_pwa_routes() {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
	$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
	$rel  = '/' . ltrim( substr( $path, strlen( rtrim( $base, '/' ) ) ), '/' );
	if ( '/manifest.webmanifest' === $rel ) {
		zxc_pwa_manifest();
	} elseif ( '/zx-sw.js' === $rel ) {
		zxc_pwa_worker();
	} elseif ( '/zx-offline' === $rel ) {
		nocache_headers();
		echo zxc_page( 'Offline', '<main class="wrap narrow" style="min-height:100vh;display:grid;place-items:center"><div class="card stack center" style="justify-items:center;padding:32px"><span class="ico lg">' . zxc_icon( 'wifi', 'a-float' ) . '</span><h1 style="font-size:24px">You’re offline</h1><p class="muted">Check your Wi-Fi or mobile data, then try again.</p><button class="btn btn-primary btn-block" onclick="location.reload()">Try again</button></div></main>', array( 'robots' => 'noindex', 'splash' => false ) ); // phpcs:ignore
		exit;
	}
}

function zxc_pwa_manifest() {
	$t    = ! empty( $_GET['t'] ) ? zxc_tenant_by_slug( sanitize_title( wp_unslash( $_GET['t'] ) ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification
	$name = $t ? $t->name : zxc_setting( 'platform_name' );
	$img  = ZXC_URL . 'assets/img/';
	$m    = array(
		'id'               => $t ? '/h/' . $t->slug : '/',
		'name'             => $name,
		'short_name'       => mb_substr( $name, 0, 14 ),
		'description'      => $t ? 'Buy Wi-Fi plans at ' . $name : 'Run your Wi-Fi hotspot business: plans, vouchers, sales and analytics.',
		'start_url'        => $t ? zxc_portal_url( $t ) . '?source=app' : home_url( '/dashboard?source=app' ),
		'scope'            => home_url( '/' ),
		'display'          => 'standalone',
		'display_override' => array( 'standalone', 'minimal-ui' ),
		'orientation'      => 'portrait',
		'background_color' => '#15183F',
		'theme_color'      => '#171A45',
		'categories'       => array( 'business', 'utilities' ),
		'icons'            => array(
			array( 'src' => $img . 'icon-192.png', 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => $img . 'icon-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
			array( 'src' => $img . 'maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
		),
	);
	if ( ! $t ) {
		$m['shortcuts'] = array(
			array( 'name' => 'New vouchers', 'url' => home_url( '/dashboard/vouchers' ), 'icons' => array( array( 'src' => $img . 'icon-192.png', 'sizes' => '192x192' ) ) ),
			array( 'name' => 'Sales', 'url' => home_url( '/dashboard/sales' ), 'icons' => array( array( 'src' => $img . 'icon-192.png', 'sizes' => '192x192' ) ) ),
			array( 'name' => 'Analytics', 'url' => home_url( '/dashboard/analytics' ), 'icons' => array( array( 'src' => $img . 'icon-192.png', 'sizes' => '192x192' ) ) ),
		);
	}
	header( 'Content-Type: application/manifest+json; charset=utf-8' );
	header( 'Cache-Control: public, max-age=3600' );
	echo wp_json_encode( $m, JSON_UNESCAPED_SLASHES );
	exit;
}

function zxc_pwa_worker() {
	header( 'Content-Type: application/javascript; charset=utf-8' );
	header( 'Service-Worker-Allowed: /' );
	header( 'Cache-Control: no-cache' );
	$assets = array( 'css/app.css', 'js/ui.js', 'fonts/inter.woff2', 'fonts/sora.woff2', 'img/icon-192.png', 'img/favicon.svg' );
	$urls   = array_map( function ( $a ) { return zxc_asset( $a ); }, $assets );
	$urls   = array_map( 'html_entity_decode', $urls );
	?>
const VERSION = 'zx-<?php echo esc_js( ZXC_VERSION ); ?>';
const PRECACHE = <?php echo wp_json_encode( array_values( $urls ), JSON_UNESCAPED_SLASHES ); ?>;
const OFFLINE = <?php echo wp_json_encode( home_url( '/zx-offline' ), JSON_UNESCAPED_SLASHES ); ?>;
self.addEventListener('install', (e) => {
  e.waitUntil(caches.open(VERSION).then((c) => c.addAll(PRECACHE.concat([OFFLINE]))).then(() => self.skipWaiting()));
});
self.addEventListener('activate', (e) => {
  e.waitUntil(caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== VERSION).map((k) => caches.delete(k)))).then(() => self.clients.claim()));
});
self.addEventListener('fetch', (e) => {
  const req = e.request;
  if (req.method !== 'GET') return;
  const url = new URL(req.url);
  if (url.origin !== location.origin) return;
  if (url.pathname.indexOf('/wp-json/') !== -1 || url.pathname.indexOf('/wp-admin') !== -1) return;
  if (req.mode === 'navigate') {
    e.respondWith(fetch(req).catch(() => caches.match(OFFLINE)));
    return;
  }
  if (url.pathname.indexOf('/wp-content/plugins/zaxconnect/assets/') !== -1) {
    e.respondWith(caches.match(req).then((hit) => hit || fetch(req).then((res) => {
      if (res.ok) { const copy = res.clone(); caches.open(VERSION).then((c) => c.put(req, copy)); }
      return res;
    })));
  }
});
	<?php
	exit;
}
