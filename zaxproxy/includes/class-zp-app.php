<?php
/**
 * Service for the ZaxProxy mobile app.
 *
 *   POST /zp-app/login    login + password  ->  a device token
 *   GET  /zp-app/proxies  (token)           ->  the user's active proxies
 *   POST /zp-app/logout   (token)           ->  the token stops working
 *
 * The app never keeps the account password. It keeps a random token; only a hash of the token
 * is stored here, tokens expire after 90 days without use, and all of a user's tokens are
 * cancelled when their password changes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_App {

	const META = 'zp_app_tokens';
	const LIFE = 7776000; // 90 days
	const MAX  = 5;       // devices per account

	public static function boot() {
		// A new password signs every device out.
		add_action( 'after_password_reset', array( __CLASS__, 'revoke_all' ) );
		add_action(
			'profile_update',
			function ( $user_id, $old ) {
				$now = get_userdata( $user_id );
				if ( $now && $old && $now->user_pass !== $old->user_pass ) {
					self::revoke_all( $user_id );
				}
			},
			10,
			2
		);
	}

	public static function revoke_all( $user ) {
		delete_user_meta( is_object( $user ) ? $user->ID : (int) $user, self::META );
	}

	private static function out( $data, $status = 200 ) {
		nocache_headers();
		wp_send_json( $data, $status );
	}

	private static function body() {
		$raw  = (string) file_get_contents( 'php://input' ); // phpcs:ignore
		$json = '' !== $raw ? json_decode( $raw, true ) : null;
		return is_array( $json ) ? $json : wp_unslash( $_POST ); // phpcs:ignore
	}

	private static function token_from_request() {
		$h = '';
		if ( ! empty( $_SERVER['HTTP_X_ZP_TOKEN'] ) ) {
			$h = (string) wp_unslash( $_SERVER['HTTP_X_ZP_TOKEN'] ); // phpcs:ignore
		} elseif ( ! empty( $_SERVER['HTTP_AUTHORIZATION'] ) && 0 === stripos( (string) $_SERVER['HTTP_AUTHORIZATION'], 'Bearer ' ) ) { // phpcs:ignore
			$h = substr( (string) wp_unslash( $_SERVER['HTTP_AUTHORIZATION'] ), 7 ); // phpcs:ignore
		}
		return preg_match( '/^(\d{1,12})\.([A-Za-z0-9]{48})$/', trim( $h ), $m ) ? array( (int) $m[1], $m[2] ) : null;
	}

	/** @return WP_User|null the signed-in app user; refreshes the token's "last used" time. */
	private static function user( $revoke = false ) {
		$t = self::token_from_request();
		if ( ! $t ) {
			return null;
		}
		list( $uid, $secret ) = $t;
		$user = get_userdata( $uid );
		$list = $user ? get_user_meta( $uid, self::META, true ) : null;
		if ( ! $user || ! is_array( $list ) ) {
			return null;
		}
		$hash  = hash_hmac( 'sha256', $secret, wp_salt( 'auth' ) );
		$found = false;
		$keep  = array();
		foreach ( $list as $row ) {
			if ( empty( $row['h'] ) || empty( $row['t'] ) || $row['t'] < time() - self::LIFE ) {
				continue; // expired
			}
			if ( hash_equals( $row['h'], $hash ) ) {
				$found = true;
				if ( $revoke ) {
					continue;
				}
				if ( $row['t'] < time() - HOUR_IN_SECONDS ) {
					$row['t'] = time();
				}
			}
			$keep[] = $row;
		}
		if ( $keep !== $list ) {
			update_user_meta( $uid, self::META, $keep );
		}
		return $found ? $user : null;
	}

	public static function endpoint( $action ) {
		if ( ! is_ssl() && ! ( defined( 'ZP_APP_ALLOW_HTTP' ) && ZP_APP_ALLOW_HTTP ) ) {
			self::out( array( 'ok' => false, 'error' => 'A secure connection is required.' ), 400 );
		}
		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) : 'GET'; // phpcs:ignore

		if ( 'download' === $action && 'GET' === $method ) {
			self::download();
		}
		if ( 'login' === $action && 'POST' === $method ) {
			self::login();
		}
		if ( 'logout' === $action && 'POST' === $method ) {
			self::user( true );
			self::out( array( 'ok' => true ) );
		}
		if ( 'proxies' === $action && 'GET' === $method ) {
			$user = self::user();
			if ( ! $user ) {
				self::out( array( 'ok' => false, 'error' => 'Please sign in again.', 'signed_out' => true ), 401 );
			}
			if ( zp_throttled( 'applist' . $user->ID, 60, MINUTE_IN_SECONDS, false ) ) {
				self::out( array( 'ok' => false, 'error' => 'Too many requests. Please wait a moment.' ), 429 );
			}
			self::out( array( 'ok' => true ) + self::payload( $user ) );
		}
		self::out( array( 'ok' => false, 'error' => 'Not found' ), 404 );
	}

	/** Is the Android app offered to this visitor? Staff always; customers once the owner switches it on. */
	public static function offered() {
		return is_readable( ZP_DIR . 'assets/app/ZaxProxy.apk' ) && ( zp_get( 'app_public' ) || current_user_can( 'manage_options' ) );
	}

	public static function download_url() {
		return zp_route_url( 'zp-app', 'download' );
	}

	private static function download() {
		$file = ZP_DIR . 'assets/app/ZaxProxy.apk';
		if ( ! self::offered() ) {
			ZP_Shield::not_found();
		}
		nocache_headers();
		header( 'Content-Type: application/vnd.android.package-archive' );
		header( 'Content-Disposition: attachment; filename="ZaxProxy.apk"' );
		header( 'Content-Length: ' . filesize( $file ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file ); // phpcs:ignore
		exit;
	}

	private static function login() {
		$in    = self::body();
		$login = isset( $in['login'] ) ? sanitize_text_field( (string) $in['login'] ) : '';
		$pass  = isset( $in['password'] ) ? (string) $in['password'] : '';
		$wait  = 'Too many attempts. Please wait a few minutes and try again.';
		if ( zp_throttled( 'applogin', 10, 15 * MINUTE_IN_SECONDS ) || ( '' !== $login && zp_throttled( 'acct' . md5( strtolower( $login ) ), 12, 15 * MINUTE_IN_SECONDS, false ) ) ) {
			self::out( array( 'ok' => false, 'error' => $wait ), 429 );
		}
		if ( '' === $login || '' === $pass ) {
			self::out( array( 'ok' => false, 'error' => 'Enter your email and password.' ), 400 );
		}
		$user = wp_authenticate( $login, $pass );
		if ( is_wp_error( $user ) ) {
			self::out( array( 'ok' => false, 'error' => 'Incorrect email or password.' ), 401 );
		}
		$store = ZP_Resellers::of_user( $user->ID );
		if ( $store && ! ZP_Resellers::is_live( $store ) ) {
			self::out( array( 'ok' => false, 'error' => 'This account is not available right now. Please contact support.' ), 403 );
		}
		$secret = wp_generate_password( 48, false, false );
		$list   = get_user_meta( $user->ID, self::META, true );
		$list   = is_array( $list ) ? $list : array();
		$device = isset( $in['device'] ) ? mb_substr( sanitize_text_field( (string) $in['device'] ), 0, 60 ) : '';
		$list[] = array( 'h' => hash_hmac( 'sha256', $secret, wp_salt( 'auth' ) ), 't' => time(), 'c' => time(), 'd' => $device );
		$list   = array_slice( $list, -self::MAX ); // the oldest device drops off
		update_user_meta( $user->ID, self::META, $list );
		self::out( array( 'ok' => true, 'token' => $user->ID . '.' . $secret ) + self::payload( $user ) );
	}

	private static function payload( $user ) {
		$store = ZP_Resellers::of_user( $user->ID );
		$rows  = array();
		foreach ( ZP_Orders::user_proxies( $user->ID, array( 'state' => 'active' ) ) as $p ) {
			if ( '' === (string) $p->ip || '' === (string) $p->socks5_port ) {
				continue; // the app connects over SOCKS5
			}
			$end    = $p->date_end ? strtotime( $p->date_end . ' UTC' ) : 0;
			$rows[] = array(
				'id'       => (int) $p->id,
				'type'     => zp_type_label( $p->proxy_type ),
				'country'  => zp_country_name( $p->country ),
				'flag'     => zp_flag( $p->country ),
				'ip'       => (string) $p->ip,
				'port'     => (int) $p->socks5_port,
				'username' => (string) $p->login,
				'password' => (string) $p->password,
				'expires'  => $end,
				'left'     => zp_time_left( $p->date_end ),
				'state'    => ZP_Orders::proxy_state( $p ),
				'note'     => (string) $p->note,
			);
		}
		return array(
			'user'    => array( 'name' => $user->display_name ? $user->display_name : $user->user_login, 'email' => $user->user_email ),
			'brand'   => $store ? (string) $store->brand : (string) zp_get( 'brand' ),
			'buy_url' => zp_route_url( ZP_USER_BASE, 'buy' ),
			'proxies' => $rows,
		);
	}
}
