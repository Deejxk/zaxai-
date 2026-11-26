<?php
/**
 * Notifications: the bell in the top header, and alerts on the user's phone.
 *
 * Every notification is stored for the bell. If the user has turned on phone alerts on a
 * device, the same message is also delivered there as a push notification (Web Push,
 * encrypted for that device), so the phone rings even when the app is closed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_Notify {

	/** Push messages waiting to be sent once the page has been delivered. */
	private static $queue = array();

	/* ---------- Creating ---------- */

	/**
	 * @param int    $user_id Who receives it.
	 * @param string $type    sale | deposit | wallet | order | ticket | store | alert | user.
	 * @param string $path    Where it opens, relative to the site root (e.g. "panel/wallet/").
	 */
	public static function add( $user_id, $type, $title, $body = '', $path = '' ) {
		global $wpdb;
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return 0;
		}
		$wpdb->insert(
			zp_table( 'notifications' ),
			array(
				'user_id'    => $user_id,
				'type'       => sanitize_key( $type ),
				'title'      => mb_substr( wp_strip_all_tags( (string) $title ), 0, 140 ),
				'body'       => mb_substr( wp_strip_all_tags( (string) $body ), 0, 300 ),
				'url'        => ltrim( (string) $path, '/' ),
				'is_read'    => 0,
				'created_at' => zp_now(),
			)
		);
		$id = (int) $wpdb->insert_id;
		if ( $id ) {
			self::$queue[] = $id;
			if ( 1 === count( self::$queue ) ) {
				add_action( 'shutdown', array( __CLASS__, 'flush' ), 1 );
			}
		}
		return $id;
	}

	/** Notify every platform administrator. */
	public static function admins( $type, $title, $body = '', $path = '', $skip = 0 ) {
		static $ids = null;
		if ( null === $ids ) {
			$ids = get_users( array( 'role' => 'administrator', 'fields' => 'ID' ) );
		}
		foreach ( $ids as $id ) {
			if ( (int) $id === (int) $skip ) {
				continue; // already told in their own words
			}
			self::add( (int) $id, $type, $title, $body, $path );
		}
	}

	/** Site-relative paths for the pages notifications open. */
	public static function user_path( $page, $args = array() ) {
		return self::rel( zp_route_url( ZP_USER_BASE, $page, $args ) );
	}

	public static function admin_path( $page = '', $args = array() ) {
		return self::rel( zp_route_url( ZP_ADMIN_BASE, $page, $args ) );
	}

	private static function rel( $url ) {
		$home = home_url( '/' );
		return 0 === strpos( $url, $home ) ? substr( $url, strlen( $home ) ) : ltrim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );
	}

	public static function name( $user_id ) {
		$u = get_userdata( (int) $user_id );
		return $u ? ( $u->display_name ? $u->display_name : $u->user_login ) : 'A customer';
	}

	/* ---------- Reading ---------- */

	public static function unread( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'notifications' ) . ' WHERE user_id = %d AND is_read = 0', $user_id ) );
	}

	public static function recent( $user_id, $limit = 25 ) {
		global $wpdb;
		return (array) $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'notifications' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d', $user_id, $limit ) );
	}

	public static function mark_read( $user_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . zp_table( 'notifications' ) . ' SET is_read = 1 WHERE user_id = %d AND is_read = 0', $user_id ) );
	}

	/** Keep the table small: old read notifications are removed by the daily clean-up. */
	public static function prune() {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . zp_table( 'notifications' ) . ' WHERE created_at < %s', gmdate( 'Y-m-d H:i:s', time() - 45 * DAY_IN_SECONDS ) ) );
	}

	private static function ago( $mysql ) {
		$d = max( 0, time() - strtotime( $mysql . ' UTC' ) );
		if ( $d < 60 ) {
			return 'Just now';
		}
		if ( $d < 3600 ) {
			return floor( $d / 60 ) . ' min ago';
		}
		if ( $d < 86400 ) {
			return floor( $d / 3600 ) . ' hr ago';
		}
		if ( $d < 7 * 86400 ) {
			return floor( $d / 86400 ) . 'd ago';
		}
		return zp_date( $mysql, false );
	}

	private static function payload( $user_id ) {
		$items = array();
		foreach ( self::recent( $user_id ) as $n ) {
			$items[] = array(
				'id'    => (int) $n->id,
				'type'  => $n->type,
				'title' => $n->title,
				'body'  => $n->body,
				'url'   => $n->url ? home_url( '/' . $n->url ) : '',
				'time'  => self::ago( $n->created_at ),
				'read'  => (int) $n->is_read,
			);
		}
		return array( 'unread' => self::unread( $user_id ), 'items' => $items, 'push' => self::public_key(), 'ajax' => wp_create_nonce( 'zp_ajax' ), 'nonce' => wp_create_nonce( 'zp_notify' ) );
	}

	/* ---------- Endpoint: /zp-notify/ ---------- */

	/** The visitor's own address as the server sees it (shown back to them only; never used for security). */
	private static function visitor_ip() {
		$ok = function ( $ip ) {
			return (bool) filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE );
		};
		$ip = zp_client_ip();
		if ( $ok( $ip ) ) {
			return $ip;
		}
		// Behind a local relay the real address arrives in a header instead.
		foreach ( array( 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR' ) as $h ) {
			$first = isset( $_SERVER[ $h ] ) ? trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $h ] ) ) )[0] ) : '';
			if ( $ok( $first ) ) {
				return $first;
			}
		}
		return '';
	}

	/** "Where am I": the address this device is browsing from, and roughly where that address is. */
	private static function whereami() {
		$ip  = self::visitor_ip();
		$out = array( 'ok' => true, 'ip' => $ip, 'place' => '', 'country' => '', 'isp' => '' );
		if ( '' === $ip ) {
			return $out;
		}
		// Only ever the caller's own address, remembered for hours: one lookup per address at most.
		$key = 'zp_here_' . md5( $ip );
		$hit = get_transient( $key );
		if ( ! is_array( $hit ) ) {
			if ( zp_throttled( 'whereami', 40, HOUR_IN_SECONDS ) ) {
				return $out;
			}
			$res  = wp_remote_get( apply_filters( 'zaxproxy_here_url', 'https://ipwho.is/' . rawurlencode( $ip ) . '?fields=success,city,region,country,connection', $ip ), array( 'timeout' => 4, 'redirection' => 0 ) );
			$data = is_wp_error( $res ) ? null : json_decode( (string) wp_remote_retrieve_body( $res ), true );
			$get  = function ( $v ) {
				return is_string( $v ) ? mb_substr( sanitize_text_field( $v ), 0, 60 ) : '';
			};
			$hit  = array( '', '', '' );
			if ( is_array( $data ) && ! empty( $data['success'] ) ) {
				$city = $get( isset( $data['city'] ) ? $data['city'] : '' );
				$reg  = $get( isset( $data['region'] ) ? $data['region'] : '' );
				$hit  = array(
					implode( ', ', array_unique( array_filter( array( $city, $reg ) ) ) ),
					$get( isset( $data['country'] ) ? $data['country'] : '' ),
					$get( isset( $data['connection']['isp'] ) ? $data['connection']['isp'] : '' ),
				);
			}
			set_transient( $key, $hit, '' === $hit[1] ? 10 * MINUTE_IN_SECONDS : 6 * HOUR_IN_SECONDS );
		}
		list( $out['place'], $out['country'], $out['isp'] ) = $hit;
		return $out;
	}

	/** Turns a GPS position into "area, state, country". The position is rounded first (about 1 km). */
	private static function gps_place( $lat, $lng ) {
		$out = array( 'ok' => true, 'area' => '', 'state' => '', 'country' => '' );
		if ( ! is_numeric( $lat ) || ! is_numeric( $lng ) || abs( (float) $lat ) > 90 || abs( (float) $lng ) > 180 ) {
			return array( 'ok' => false ) + $out;
		}
		$lat = number_format( (float) $lat, 2, '.', '' );
		$lng = number_format( (float) $lng, 2, '.', '' );
		$key = 'zp_gps_' . md5( $lat . ',' . $lng );
		$hit = get_transient( $key );
		if ( ! is_array( $hit ) ) {
			if ( zp_throttled( 'gpsplace', 30, HOUR_IN_SECONDS ) ) {
				return $out;
			}
			$url  = apply_filters( 'zaxproxy_gps_url', 'https://nominatim.openstreetmap.org/reverse?format=jsonv2&zoom=10&accept-language=en&lat=' . $lat . '&lon=' . $lng );
			$res  = wp_remote_get( $url, array( 'timeout' => 6, 'redirection' => 0, 'user-agent' => 'ZaxProxy/' . ZP_VERSION . ' (' . wp_parse_url( home_url(), PHP_URL_HOST ) . ')' ) );
			$data = is_wp_error( $res ) ? null : json_decode( (string) wp_remote_retrieve_body( $res ), true );
			$addr = is_array( $data ) && isset( $data['address'] ) && is_array( $data['address'] ) ? $data['address'] : array();
			$pick = function ( $keys ) use ( $addr ) {
				foreach ( $keys as $k ) {
					if ( ! empty( $addr[ $k ] ) && is_string( $addr[ $k ] ) ) {
						return mb_substr( sanitize_text_field( $addr[ $k ] ), 0, 60 );
					}
				}
				return '';
			};
			$hit  = array( $pick( array( 'city', 'town', 'village', 'municipality', 'county', 'state_district' ) ), $pick( array( 'state', 'region', 'province' ) ), $pick( array( 'country' ) ) );
			set_transient( $key, $hit, '' === $hit[2] ? 10 * MINUTE_IN_SECONDS : 30 * DAY_IN_SECONDS );
		}
		list( $out['area'], $out['state'], $out['country'] ) = $hit;
		return $out;
	}

	public static function endpoint() {
		nocache_headers();
		$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : ''; // phpcs:ignore
		if ( 'whereami' === $do ) {
			wp_send_json( self::whereami() );
		}
		if ( 'gpsplace' === $do && is_user_logged_in() ) {
			wp_send_json( self::gps_place( isset( $_GET['lat'] ) ? wp_unslash( $_GET['lat'] ) : '', isset( $_GET['lng'] ) ? wp_unslash( $_GET['lng'] ) : '' ) ); // phpcs:ignore
		}
		if ( ! is_user_logged_in() ) {
			wp_send_json( array( 'ok' => false ), 401 );
		}
		$uid = get_current_user_id();
		if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET' ) ) { // phpcs:ignore
			if ( ! wp_verify_nonce( zp_post( '_zpnonce' ), 'zp_notify' ) ) {
				wp_send_json( array( 'ok' => false, 'error' => 'expired' ), 403 );
			}
			switch ( zp_post( 'do' ) ) {
				case 'read':
					self::mark_read( $uid );
					break;
				case 'delete':
					$wpdb_n = $GLOBALS['wpdb'];
					$wpdb_n->delete( zp_table( 'notifications' ), array( 'id' => (int) zp_post( 'id' ), 'user_id' => $uid ) ); // only ever the user's own
					break;
				case 'clear':
					$GLOBALS['wpdb']->delete( zp_table( 'notifications' ), array( 'user_id' => $uid ) );
					break;
				case 'subscribe':
					$ok = self::subscribe( $uid, isset( $_POST['endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['endpoint'] ) ) : '', zp_post( 'p256dh' ), zp_post( 'auth' ) ); // phpcs:ignore
					if ( ! $ok ) {
						wp_send_json( array( 'ok' => false ), 400 );
					}
					if ( zp_post( 'hello' ) ) {
						self::add( $uid, 'alert', 'Phone alerts are on', 'This is how ' . zp_get( 'brand' ) . ' will notify you on this device.', self::user_path( 'dashboard' ) );
					}
					break;
				case 'unsubscribe':
					self::unsubscribe( isset( $_POST['endpoint'] ) ? esc_url_raw( wp_unslash( $_POST['endpoint'] ) ) : '' ); // phpcs:ignore
					break;
			}
		}
		wp_send_json( array( 'ok' => true ) + self::payload( $uid ) );
	}

	/* ---------- Phone alerts (Web Push) ---------- */

	private static function b64( $bin ) {
		return rtrim( strtr( base64_encode( $bin ), '+/', '-_' ), '=' ); // phpcs:ignore
	}

	private static function unb64( $txt ) {
		return (string) base64_decode( strtr( (string) $txt, '-_', '+/' ) ); // phpcs:ignore
	}

	/** The server's signing keys, created once. Returns null when the host cannot create them. */
	private static function keys() {
		static $keys = null;
		if ( null !== $keys ) {
			return $keys ? $keys : null;
		}
		$keys = get_option( 'zp_vapid' );
		if ( is_array( $keys ) && ! empty( $keys['private'] ) && ! empty( $keys['public'] ) ) {
			return $keys;
		}
		$keys = false;
		if ( ! function_exists( 'openssl_pkey_new' ) || ! function_exists( 'openssl_pkey_derive' ) ) {
			return null;
		}
		$res = openssl_pkey_new( array( 'curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC ) );
		if ( ! $res || ! openssl_pkey_export( $res, $pem ) ) {
			return null;
		}
		$d = openssl_pkey_get_details( $res );
		if ( empty( $d['ec']['x'] ) || empty( $d['ec']['y'] ) ) {
			return null;
		}
		$keys = array(
			'private' => $pem,
			'public'  => self::b64( "\x04" . str_pad( $d['ec']['x'], 32, "\0", STR_PAD_LEFT ) . str_pad( $d['ec']['y'], 32, "\0", STR_PAD_LEFT ) ),
		);
		update_option( 'zp_vapid', $keys, false );
		return $keys;
	}

	public static function public_key() {
		$k = self::keys();
		return $k ? $k['public'] : '';
	}

	public static function subscribe( $user_id, $endpoint, $p256dh, $auth ) {
		global $wpdb;
		$host = strtolower( (string) wp_parse_url( $endpoint, PHP_URL_HOST ) );
		if ( ! self::push_host_ok( $host ) ) {
			return false; // only the browsers' own push services, never an address a user makes up
		}
		if ( 0 !== strpos( $endpoint, 'https://' ) || ! $host || strlen( $endpoint ) > 1000 || 65 !== strlen( self::unb64( $p256dh ) ) || strlen( self::unb64( $auth ) ) < 12 ) {
			return false;
		}
		$t    = zp_table( 'push' );
		$hash = md5( $endpoint );
		$wpdb->query( $wpdb->prepare( "DELETE FROM $t WHERE hash = %s AND user_id = %d", $hash, $user_id ) ); // phpcs:ignore
		if ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE hash = %s", $hash ) ) ) { // phpcs:ignore
			return false; // this device address is registered to someone else
		}
		$wpdb->insert( $t, array( 'user_id' => (int) $user_id, 'hash' => $hash, 'endpoint' => $endpoint, 'p256dh' => $p256dh, 'auth' => $auth, 'created_at' => zp_now() ) );
		// A user keeps at most 8 devices; the oldest ones drop off.
		$old = $wpdb->get_col( $wpdb->prepare( "SELECT id FROM $t WHERE user_id = %d ORDER BY id DESC LIMIT 8, 50", $user_id ) ); // phpcs:ignore
		if ( $old ) {
			$wpdb->query( "DELETE FROM $t WHERE id IN (" . implode( ',', array_map( 'intval', $old ) ) . ')' ); // phpcs:ignore
		}
		return true;
	}

	/** Push messages may only be handed to the push services run by the browser makers. */
	private static function push_host_ok( $host ) {
		if ( defined( 'ZP_PUSH_TEST_HOST' ) && ZP_PUSH_TEST_HOST === $host ) {
			return true; // automated tests only
		}
		foreach ( array( 'fcm.googleapis.com', 'android.googleapis.com', 'push.services.mozilla.com', 'notify.windows.com', 'push.apple.com' ) as $ok ) {
			if ( $host === $ok || substr( $host, -strlen( '.' . $ok ) ) === '.' . $ok ) {
				return true;
			}
		}
		return false;
	}

	public static function unsubscribe( $endpoint ) {
		global $wpdb;
		$wpdb->delete( zp_table( 'push' ), array( 'hash' => md5( (string) $endpoint ), 'user_id' => get_current_user_id() ) );
	}

	public static function devices( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'push' ) . ' WHERE user_id = %d', $user_id ) );
	}

	/** Deliver queued notifications to phones after the response has been sent to the browser. */
	public static function flush() {
		global $wpdb;
		$ids         = array_map( 'intval', self::$queue );
		self::$queue = array();
		if ( ! $ids || ! self::keys() ) {
			return;
		}
		$rows = $wpdb->get_results( 'SELECT n.*, p.id AS push_id, p.endpoint, p.p256dh, p.auth FROM ' . zp_table( 'notifications' ) . ' n JOIN ' . zp_table( 'push' ) . ' p ON p.user_id = n.user_id WHERE n.id IN (' . implode( ',', $ids ) . ') ORDER BY n.id ASC LIMIT 60' ); // phpcs:ignore
		if ( ! $rows ) {
			return;
		}
		if ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request(); // the visitor does not wait for the phone alerts
		}
		ignore_user_abort( true );
		$dead = array();
		foreach ( $rows as $r ) {
			if ( isset( $dead[ $r->push_id ] ) ) {
				continue;
			}
			$code = self::send(
				$r,
				array( 'id' => (int) $r->id, 'title' => $r->title, 'body' => $r->body, 'url' => $r->url, 'type' => $r->type )
			);
			if ( 404 === $code || 410 === $code ) {
				$dead[ $r->push_id ] = true; // the device removed the app or turned alerts off
			}
		}
		if ( $dead ) {
			$wpdb->query( 'DELETE FROM ' . zp_table( 'push' ) . ' WHERE id IN (' . implode( ',', array_map( 'intval', array_keys( $dead ) ) ) . ')' ); // phpcs:ignore
		}
	}

	/** Encrypt one message for one device (RFC 8291, aes128gcm) and hand it to the push service. */
	private static function send( $sub, array $message ) {
		$keys = self::keys();
		$ua   = self::unb64( $sub->p256dh );
		$auth = self::unb64( $sub->auth );
		if ( ! $keys || 65 !== strlen( $ua ) ) {
			return 0;
		}
		$body = self::encrypt( wp_json_encode( $message ), $ua, $auth );
		if ( ! $body ) {
			return 0;
		}
		$p   = wp_parse_url( $sub->endpoint );
		$aud = $p['scheme'] . '://' . $p['host'] . ( isset( $p['port'] ) ? ':' . $p['port'] : '' );
		$jwt = self::jwt( $aud, $keys['private'] );
		if ( ! $jwt ) {
			return 0;
		}
		if ( ! self::push_host_ok( strtolower( (string) $p['host'] ) ) ) {
			return 410; // not a push service: drop it
		}
		$res = wp_safe_remote_post(
			$sub->endpoint,
			array(
				'timeout'     => 5,
				'redirection' => 0,
				'headers' => array(
					'Authorization'    => 'vapid t=' . $jwt . ', k=' . $keys['public'],
					'Content-Type'     => 'application/octet-stream',
					'Content-Encoding' => 'aes128gcm',
					'TTL'              => '86400',
					'Urgency'          => 'high',
				),
				'body'    => $body,
			)
		);
		return is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	}

	private static function jwt( $aud, $private_pem ) {
		$mail = zp_get( 'support_email' );
		$head = self::b64( '{"typ":"JWT","alg":"ES256"}' );
		$body = self::b64( wp_json_encode( array( 'aud' => $aud, 'exp' => time() + 12 * HOUR_IN_SECONDS, 'sub' => 'mailto:' . ( is_email( $mail ) ? $mail : get_option( 'admin_email' ) ) ) ) );
		if ( ! openssl_sign( $head . '.' . $body, $der, $private_pem, OPENSSL_ALGO_SHA256 ) ) {
			return '';
		}
		// DER (30 len 02 len r 02 len s) -> raw r||s, 32 bytes each.
		$pos = 2 + ( ord( $der[1] ) > 0x80 ? ord( $der[1] ) - 0x80 : 0 );
		$out = '';
		for ( $i = 0; $i < 2; $i++ ) {
			$len  = ord( $der[ $pos + 1 ] );
			$int  = ltrim( substr( $der, $pos + 2, $len ), "\0" );
			$out .= str_pad( $int, 32, "\0", STR_PAD_LEFT );
			$pos += 2 + $len;
		}
		return $head . '.' . $body . '.' . self::b64( $out );
	}

	private static function encrypt( $plain, $ua_public, $auth ) {
		$local = openssl_pkey_new( array( 'curve_name' => 'prime256v1', 'private_key_type' => OPENSSL_KEYTYPE_EC ) );
		if ( ! $local ) {
			return '';
		}
		$d         = openssl_pkey_get_details( $local );
		$as_public = "\x04" . str_pad( $d['ec']['x'], 32, "\0", STR_PAD_LEFT ) . str_pad( $d['ec']['y'], 32, "\0", STR_PAD_LEFT );
		// Wrap the device's raw P-256 point in the standard public-key envelope so OpenSSL can read it.
		$pem  = "-----BEGIN PUBLIC KEY-----\n" . chunk_split( base64_encode( hex2bin( '3059301306072a8648ce3d020106082a8648ce3d030107034200' ) . $ua_public ), 64, "\n" ) . "-----END PUBLIC KEY-----\n"; // phpcs:ignore
		$peer = openssl_pkey_get_public( $pem );
		if ( ! $peer ) {
			return '';
		}
		$shared = openssl_pkey_derive( $peer, $local, 32 );
		if ( ! $shared ) {
			return '';
		}
		$ikm   = hash_hkdf( 'sha256', $shared, 32, "WebPush: info\0" . $ua_public . $as_public, $auth );
		$salt  = random_bytes( 16 );
		$cek   = hash_hkdf( 'sha256', $ikm, 16, "Content-Encoding: aes128gcm\0", $salt );
		$nonce = hash_hkdf( 'sha256', $ikm, 12, "Content-Encoding: nonce\0", $salt );
		$tag   = '';
		$ct    = openssl_encrypt( $plain . "\x02", 'aes-128-gcm', $cek, OPENSSL_RAW_DATA, $nonce, $tag );
		if ( false === $ct ) {
			return '';
		}
		return $salt . pack( 'N', 4096 ) . chr( 65 ) . $as_public . $ct . $tag;
	}
}
