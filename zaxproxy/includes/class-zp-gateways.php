<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Payment gateways for wallet top-ups.
 * - manual: customer submits a payment reference, staff approves it.
 * - paystack: hosted checkout, confirmed by server-side verification (callback + webhook).
 */
class ZP_Gateways {

	public static function available() {
		$g = array();
		if ( zp_get( 'paystack_enabled' ) && zp_get( 'paystack_secret' ) ) {
			$g['paystack'] = array( 'label' => 'Card / Bank (Paystack)', 'desc' => 'Instant. Pay with card, bank transfer or USSD.' );
		}
		if ( zp_get( 'manual_enabled' ) ) {
			$g['manual'] = array( 'label' => zp_get( 'manual_title' ), 'desc' => 'Credited after our team confirms your payment.' );
		}
		return $g;
	}

	private static function paystack( $method, $path, $body = null ) {
		$args = array(
			'method'  => $method,
			'timeout' => 30,
			'headers' => array( 'Authorization' => 'Bearer ' . zp_get( 'paystack_secret' ), 'Content-Type' => 'application/json' ),
		);
		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}
		$res = wp_remote_request( 'https://api.paystack.co' . $path, $args );
		if ( is_wp_error( $res ) ) {
			return $res;
		}
		$data = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! is_array( $data ) || empty( $data['status'] ) ) {
			return new WP_Error( 'zp_pay', isset( $data['message'] ) ? (string) $data['message'] : 'Payment provider error.' );
		}
		return $data['data'];
	}

	/** @return string|WP_Error checkout URL */
	public static function paystack_start( $user, $amount ) {
		// The wallet is held in the store currency, so the customer is charged exactly what is credited.
		$currency  = strtoupper( (string) zp_get( 'currency_code', 'NGN' ) );
		$gw_amount = round( (float) $amount, 2 );
		$ref       = ZP_Wallet::create_deposit( $user->ID, 'paystack', $amount, '', $gw_amount, $currency );
		if ( ! $ref ) {
			return new WP_Error( 'zp_pay', 'Could not start the payment.' );
		}
		$data = self::paystack(
			'POST',
			'/transaction/initialize',
			array(
				'email'        => $user->user_email,
				'amount'       => (int) round( $gw_amount * 100 ),
				'currency'     => $currency,
				'reference'    => $ref,
				'callback_url' => zp_url( 'wallet', array( 'zp_verify' => $ref ) ),
			)
		);
		if ( is_wp_error( $data ) ) {
			ZP_Wallet::reject_deposit( ZP_Wallet::deposit_by_ref( $ref )->id, $data->get_error_message() );
			return new WP_Error( 'zp_pay', 'Could not start the payment: ' . $data->get_error_message() );
		}
		return $data['authorization_url'];
	}

	/** Verify a Paystack reference server-side and credit the wallet once. */
	public static function paystack_verify( $ref ) {
		$d = ZP_Wallet::deposit_by_ref( $ref );
		if ( ! $d || 'paystack' !== $d->gateway ) {
			return new WP_Error( 'zp_pay', 'Unknown payment reference.' );
		}
		if ( 'completed' === $d->status ) {
			return true;
		}
		if ( 'pending' !== $d->status ) {
			return new WP_Error( 'zp_pay', 'This payment was not successful.' );
		}
		$data = self::paystack( 'GET', '/transaction/verify/' . rawurlencode( $ref ) );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		$paid_ok = isset( $data['status'] ) && 'success' === $data['status']
			&& (int) $data['amount'] >= (int) round( $d->gw_amount * 100 )
			&& strtoupper( (string) $data['currency'] ) === strtoupper( $d->gw_currency );
		if ( ! $paid_ok ) {
			if ( isset( $data['status'] ) && in_array( $data['status'], array( 'failed', 'abandoned', 'reversed' ), true ) ) {
				ZP_Wallet::reject_deposit( $d->id, 'Paystack status: ' . $data['status'] );
			}
			return new WP_Error( 'zp_pay', 'Payment not completed yet.' );
		}
		ZP_Wallet::complete_deposit( $d->id, 'Verified with Paystack' );
		return true;
	}

	/** Webhook endpoint: /zp-webhook/paystack */
	public static function paystack_webhook() {
		$raw = file_get_contents( 'php://input' ); // phpcs:ignore
		$sig = isset( $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ) ) : '';
		$key = (string) zp_get( 'paystack_secret' );
		if ( '' === $key || ! hash_equals( hash_hmac( 'sha512', $raw, $key ), $sig ) ) {
			status_header( 401 );
			exit;
		}
		$event = json_decode( $raw, true );
		if ( isset( $event['event'], $event['data']['reference'] ) && 'charge.success' === $event['event'] ) {
			self::paystack_verify( sanitize_text_field( $event['data']['reference'] ) );
		}
		status_header( 200 );
		exit;
	}
}
