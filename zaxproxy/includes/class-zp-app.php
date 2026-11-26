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
	const FILES = array(
		'android' => array( 'ZaxProxy-Connect.apk', 'ZaxProxy-Connect.apk', 'application/vnd.android.package-archive' ),
		'windows' => array( 'ZaxProxy-Connect-Windows.zip', 'ZaxProxy-Connect-Windows.zip', 'application/zip' ), // zipped: browsers often refuse a bare .exe
	);

	public static function offered( $os = 'android' ) {
		return isset( self::FILES[ $os ] ) && is_readable( ZP_DIR . 'assets/app/' . self::FILES[ $os ][0] ) && ( zp_get( 'app_public' ) || current_user_can( 'manage_options' ) );
	}

	public static function download_url( $os = 'android' ) {
		return zp_route_url( 'zp-app', 'download', 'android' === $os ? array() : array( 'os' => $os ) );
	}

	private static function download() {
		$os = sanitize_key( zp_query( 'os', 'android' ) );
		if ( ! self::offered( $os ) ) {
			ZP_Shield::not_found();
		}
		$file = ZP_DIR . 'assets/app/' . self::FILES[ $os ][0];
		nocache_headers();
		header( 'Content-Type: ' . self::FILES[ $os ][2] );
		header( 'Content-Disposition: attachment; filename="' . self::FILES[ $os ][1] . '"' );
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

	/**
	 * City and state/region of a proxy IP, from a public IP-location directory.
	 *
	 * Looked up once per IP and remembered. Only the proxy's IP address is sent to the directory;
	 * nothing about the customer. Locations from such directories are approximate.
	 *
	 * @param int $budget how many new look-ups this request may still make (passed by reference).
	 * @return array [ city, region ] (empty strings when unknown)
	 */
	public static function geo( $ip, &$budget ) {
		static $map = null;
		if ( null === $map ) {
			$map = get_option( 'zp_geo' );
			$map = is_array( $map ) ? $map : array();
		}
		$ip = (string) $ip;
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
			return array( '', '', null, null );
		}
		// Each entry: city, region, when it was looked up, latitude, longitude.
		$hit   = isset( $map[ $ip ] ) ? $map[ $ip ] : null;
		$known = $hit ? array( $hit[0], $hit[1], isset( $hit[3] ) ? $hit[3] : null, isset( $hit[4] ) ? $hit[4] : null ) : array( '', '', null, null );
		// Entries saved before map positions existed (three values) are looked up again once.
		if ( $hit && array_key_exists( 3, $hit ) && ( ( '' !== $hit[0] . $hit[1] && null !== $known[2] ) || $hit[2] > time() - DAY_IN_SECONDS ) ) {
			return $known; // complete, or tried within the last day
		}
		if ( $budget <= 0 ) {
			return $known;
		}
		--$budget;
		$url  = apply_filters( 'zaxproxy_geo_url', 'https://ipwho.is/' . rawurlencode( $ip ) . '?fields=success,city,region,latitude,longitude', $ip );
		$res  = wp_remote_get( $url, array( 'timeout' => 4, 'redirection' => 0 ) );
		$data = is_wp_error( $res ) ? null : json_decode( (string) wp_remote_retrieve_body( $res ), true );
		$city = is_array( $data ) && ! empty( $data['city'] ) ? mb_substr( sanitize_text_field( (string) $data['city'] ), 0, 60 ) : '';
		$reg  = is_array( $data ) && ! empty( $data['region'] ) ? mb_substr( sanitize_text_field( (string) $data['region'] ), 0, 60 ) : '';
		$lat  = is_array( $data ) && isset( $data['latitude'] ) && is_numeric( $data['latitude'] ) && abs( (float) $data['latitude'] ) <= 90 ? round( (float) $data['latitude'], 4 ) : null;
		$lng  = is_array( $data ) && isset( $data['longitude'] ) && is_numeric( $data['longitude'] ) && abs( (float) $data['longitude'] ) <= 180 ? round( (float) $data['longitude'], 4 ) : null;
		if ( null === $lat || null === $lng ) {
			$lat = null;
			$lng = null;
		}
		if ( '' === $city . $reg && $hit ) { // a failed retry must not wipe what was known
			$city = $hit[0];
			$reg  = $hit[1];
		}
		$map[ $ip ] = array( $city, $reg, time(), $lat, $lng );
		if ( count( $map ) > 3000 ) {
			$map = array_slice( $map, -2500, null, true );
		}
		update_option( 'zp_geo', $map, false );
		return array( $city, $reg, $lat, $lng );
	}

	private static function payload( $user ) {
		$store  = ZP_Resellers::of_user( $user->ID );
		$rows   = array();
		$budget = 6; // new location look-ups per request; the rest fill in on the next refresh
		foreach ( ZP_Orders::user_proxies( $user->ID, array( 'state' => 'active' ) ) as $p ) {
			if ( '' === (string) $p->ip || '' === (string) $p->socks5_port ) {
				continue; // the app connects over SOCKS5
			}
			$end    = $p->date_end ? strtotime( $p->date_end . ' UTC' ) : 0;
			$geo    = self::geo( $p->ip, $budget );
			$rows[] = array(
				'id'       => (int) $p->id,
				'type'     => zp_type_label( $p->proxy_type ),
				'country'  => zp_country_name( $p->country ),
				'flag'     => zp_flag( $p->country ),
				'ip'       => (string) $p->ip,
				'city'     => $geo[0],
				'region'   => $geo[1],
				'place'    => implode( ', ', array_filter( array( $geo[0], $geo[1] !== $geo[0] ? $geo[1] : '' ) ) ),
				'lat'      => $geo[2], // where the address is on the map (null until known)
				'lng'      => $geo[3],
				'port'     => (int) $p->socks5_port,
				'username' => (string) $p->login,
				'password' => (string) $p->password,
				'expires'  => $end,
				'left'     => zp_time_left( $p->date_end ),
				'state'    => ZP_Orders::proxy_state( $p ),
				'note'     => (string) $p->note,
			);
		}
		// Proxies that ran out in the last 30 days: listed so the customer can see them, never connectable.
		$gone = array();
		$none = 0;
		foreach ( ZP_Orders::user_proxies( $user->ID, array( 'state' => 'expired' ) ) as $p ) {
			$end = $p->date_end ? strtotime( $p->date_end . ' UTC' ) : 0;
			if ( '' === (string) $p->ip || $end < time() - 30 * DAY_IN_SECONDS || count( $gone ) >= 20 ) {
				continue;
			}
			$geo    = self::geo( $p->ip, $none ); // only what is already known
			$gone[] = array(
				'id'      => (int) $p->id,
				'type'    => zp_type_label( $p->proxy_type ),
				'country' => zp_country_name( $p->country ),
				'flag'    => zp_flag( $p->country ),
				'ip'      => (string) $p->ip,
				'place'   => implode( ', ', array_filter( array( $geo[0], $geo[1] !== $geo[0] ? $geo[1] : '' ) ) ),
				'ended'   => zp_date( $p->date_end, false ),
			);
		}
		return array(
			'expired' => $gone,
			'renew_url' => zp_route_url( ZP_USER_BASE, 'proxies' ),
			'user'    => array( 'name' => $user->display_name ? $user->display_name : $user->user_login, 'email' => $user->user_email ),
			'brand'   => $store ? (string) $store->brand : (string) zp_get( 'brand' ),
			'buy_url' => zp_route_url( ZP_USER_BASE, 'buy' ),
			'proxies' => $rows,
		);
	}
}
