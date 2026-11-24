<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hostinger hosting API: used to point a reseller's branded domain at this website
 * (a "parked domain") without the admin leaving the panel.
 * Docs: https://developers.hostinger.com
 */
class ZP_Hosting {

	public static function configured() {
		return '' !== trim( (string) zp_get( 'hostinger_token' ) );
	}

	private static function base() {
		return untrailingslashit( defined( 'ZAXPROXY_HOSTINGER_BASE' ) ? ZAXPROXY_HOSTINGER_BASE : 'https://developers.hostinger.com' );
	}

	/** The site's real host name, even when the request came in on a reseller's domain. */
	public static function main_host() {
		remove_all_filters( 'option_home', 99 );
		$host = strtolower( (string) wp_parse_url( get_option( 'home' ), PHP_URL_HOST ) );
		return preg_replace( '/^www\./', '', $host );
	}

	private static function request( $method, $path, $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 40,
			'headers' => array( 'Authorization' => 'Bearer ' . trim( (string) zp_get( 'hostinger_token' ) ), 'Accept' => 'application/json', 'Content-Type' => 'application/json' ),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$res = wp_remote_request( self::base() . $path, $args );
		if ( is_wp_error( $res ) ) {
			return new WP_Error( 'zp_host', 'Could not reach the hosting API. Please try again.' );
		}
		$code = (int) wp_remote_retrieve_response_code( $res );
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( $code >= 400 ) {
			$msg = is_array( $data ) && ! empty( $data['message'] ) ? (string) $data['message'] : 'HTTP ' . $code;
			if ( 401 === $code || 403 === $code ) {
				$msg = 'The Hostinger API token was not accepted. Check it in Settings.';
			}
			return new WP_Error( 'zp_host', $msg, array( 'status' => $code ) );
		}
		return is_array( $data ) ? $data : array();
	}

	/** Hosting account username that owns this website (looked up once, then remembered). */
	public static function username() {
		$saved = (string) zp_get( 'hostinger_username' );
		if ( '' !== $saved ) {
			return $saved;
		}
		$res = self::request( 'GET', '/api/hosting/v1/websites?domain=' . rawurlencode( self::main_host() ) );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$list = isset( $res['data'] ) ? $res['data'] : $res;
		foreach ( (array) $list as $site ) {
			if ( isset( $site['domain'], $site['username'] ) && strtolower( $site['domain'] ) === self::main_host() ) {
				zp_update_settings( array( 'hostinger_username' => sanitize_text_field( $site['username'] ) ) );
				return (string) $site['username'];
			}
		}
		return new WP_Error( 'zp_host', 'This website was not found in the Hostinger account the token belongs to.' );
	}

	public static function parked() {
		$user = self::username();
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$res = self::request( 'GET', '/api/hosting/v1/accounts/' . rawurlencode( $user ) . '/websites/' . rawurlencode( self::main_host() ) . '/parked-domains' );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$out = array();
		foreach ( (array) ( isset( $res['data'] ) ? $res['data'] : $res ) as $row ) {
			if ( isset( $row['domain'] ) ) {
				$out[] = strtolower( (string) $row['domain'] );
			}
		}
		return $out;
	}

	/**
	 * Point $domain at this website. Safe to call again for a domain that is already parked.
	 *
	 * @return true|WP_Error
	 */
	public static function park( $domain ) {
		if ( ! self::configured() ) {
			return new WP_Error( 'zp_host_off', 'No Hostinger API token is saved.' );
		}
		$user = self::username();
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		$res = self::request( 'POST', '/api/hosting/v1/accounts/' . rawurlencode( $user ) . '/websites/' . rawurlencode( self::main_host() ) . '/parked-domains', array( 'parked_domain' => $domain ) );
		if ( ! is_wp_error( $res ) ) {
			return true;
		}
		// Already parked here counts as success.
		$list = self::parked();
		if ( is_array( $list ) && in_array( strtolower( $domain ), $list, true ) ) {
			return true;
		}
		return $res;
	}
}
