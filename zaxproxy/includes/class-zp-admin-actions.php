<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_Admin_Actions {

	public static function login() {
		if ( zp_post( 'zp_do' ) !== 'admin_login' || ! wp_verify_nonce( zp_post( '_zpnonce' ), 'zp_admin_login' ) ) {
			zp_flash( 'Your session expired. Please try again.', 'error' );
			zp_redirect( zp_admin_url( 'login' ) );
		}
		if ( zp_throttled( 'admin_login', 6, 15 * MINUTE_IN_SECONDS ) ) {
			zp_flash( 'Too many attempts. Please wait a few minutes.', 'error' );
			zp_redirect( zp_admin_url( 'login' ) );
		}
		$pass = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
		$user = wp_authenticate( zp_post( 'login' ), $pass );
		if ( is_wp_error( $user ) || ! user_can( $user, 'manage_options' ) ) {
			zp_flash( 'Incorrect credentials or insufficient permissions.', 'error' );
			zp_redirect( zp_admin_url( 'login' ) );
		}
		wp_set_current_user( $user->ID );
		wp_set_auth_cookie( $user->ID, (bool) zp_post( 'remember' ), is_ssl() );
		zp_redirect( zp_admin_url() );
	}

	public static function handle( $page ) {
		global $wpdb;
		$do = zp_post( 'zp_do' );
		if ( ! current_user_can( 'manage_options' ) || ! wp_verify_nonce( zp_post( '_zpnonce' ), 'zp_' . $do ) ) {
			zp_flash( 'Your session expired. Please try again.', 'error' );
			zp_redirect( zp_admin_url( $page ) );
		}
		$back = wp_get_referer() ? wp_get_referer() : zp_admin_url( $page );

		switch ( $do ) {
			case 'save_settings':
				self::save_settings();
				break;

			case 'save_pricing':
				$new = array(
					'fx_rate'         => max( 0.0001, (float) zp_post( 'fx_rate' ) ),
					'currency_code'   => strtoupper( preg_replace( '/[^A-Za-z]/', '', zp_post( 'currency_code', 'NGN' ) ) ),
					'currency_symbol' => mb_substr( zp_post( 'currency_symbol', '₦' ), 0, 4 ),
				);
				foreach ( array_keys( zp_types() ) as $t ) {
					$new[ 'markup_' . $t ] = max( 0, min( 1000, (float) zp_post( 'markup_' . $t ) ) );
				}
				zp_update_settings( $new );
				delete_transient( 'zp_public_prices' );
				zp_flash( 'Pricing saved. New prices apply to every new order.' );
				break;

			case 'test_api':
				$res = ZP_API::balance();
				if ( is_wp_error( $res ) ) {
					zp_flash( 'Provider connection failed: ' . $res->get_error_message(), 'error' );
				} else {
					delete_option( 'zaxproxy_last_api_error' );
					zp_flash( sprintf( 'Connected as %s. Provider balance: %s %s.', $res['user'], number_format( (float) $res['balance'], 2 ), $res['currency'] ) );
				}
				break;

			case 'sync_all':
				ZP_API::flush_cache();
				ZP_Orders::cron_sync();
				zp_flash( 'Synchronised with the provider.' );
				break;

			case 'deposit_approve':
				$ok = ZP_Wallet::complete_deposit( (int) zp_post( 'id' ), 'Approved by ' . wp_get_current_user()->user_login );
				zp_flash( $ok ? 'Deposit approved and wallet credited.' : 'This deposit was already processed.', $ok ? 'success' : 'error' );
				break;

			case 'deposit_reject':
				$ok = ZP_Wallet::reject_deposit( (int) zp_post( 'id' ), zp_post( 'reason', 'Rejected' ) );
				zp_flash( $ok ? 'Deposit rejected.' : 'This deposit was already processed.', $ok ? 'success' : 'error' );
				break;

			case 'user_adjust':
				$uid    = (int) zp_post( 'user_id' );
				$amount = round( (float) zp_post( 'amount' ), 2 );
				$note   = zp_post( 'note', 'Balance adjustment' );
				if ( ! get_userdata( $uid ) || $amount <= 0 ) {
					zp_flash( 'Enter a valid amount.', 'error' );
				} elseif ( 'debit' === zp_post( 'direction' ) ) {
					$ok = ZP_Wallet::debit( $uid, $amount, 'adjustment', 'admin', $note );
					zp_flash( $ok ? 'Wallet debited.' : 'The user does not have enough balance.', $ok ? 'success' : 'error' );
				} else {
					ZP_Wallet::credit( $uid, $amount, 'adjustment', 'admin', $note );
					zp_flash( 'Wallet credited.' );
				}
				break;

			case 'order_refund':
				$ok = ZP_Orders::refund( (int) zp_post( 'id' ), 'Refunded by ' . wp_get_current_user()->user_login );
				zp_flash( $ok ? 'Order refunded to the customer wallet.' : 'This order was already refunded.', $ok ? 'success' : 'error' );
				break;

			case 'order_complete':
				$o = ZP_Orders::get( (int) zp_post( 'id' ) );
				if ( $o && in_array( $o->status, array( 'review', 'processing' ), true ) ) {
					ZP_Orders::set_status( $o->id, 'completed', '' );
					zp_flash( 'Order marked as completed.' );
				}
				break;

			case 'proxy_assign':
				$uid = (int) zp_post( 'user_id' );
				if ( get_userdata( $uid ) ) {
					$wpdb->update( zp_table( 'proxies' ), array( 'user_id' => $uid ), array( 'id' => (int) zp_post( 'id' ) ) );
					zp_flash( 'Proxy reassigned.' );
				} else {
					zp_flash( 'User not found.', 'error' );
				}
				break;

			case 'ticket_reply':
				$t       = ZP_Tickets::get( (int) zp_post( 'ticket_id' ) );
				$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore
				if ( $t && '' !== trim( $message ) ) {
					ZP_Tickets::add_message( $t->id, get_current_user_id(), $message, true );
					$owner = get_userdata( $t->user_id );
					if ( $owner ) {
						wp_mail( $owner->user_email, '[' . zp_get( 'brand' ) . '] Reply to ticket #' . $t->id, $message . "\n\n" . zp_url( 'tickets', array( 'id' => $t->id ) ) );
					}
					zp_flash( 'Reply sent.' );
				}
				break;

			case 'ticket_close':
				ZP_Tickets::close( (int) zp_post( 'ticket_id' ) );
				zp_flash( 'Ticket closed.' );
				break;
		}
		zp_redirect( $back );
	}

	private static function save_settings() {
		$types = isset( $_POST['types'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['types'] ) ) : array(); // phpcs:ignore
		$new   = array(
			'brand'               => zp_post( 'brand', 'ZaxProxy' ),
			'types'               => array_values( array_intersect( array_keys( zp_types() ), $types ) ),
			'registration'        => zp_post( 'registration' ) ? 1 : 0,
			'landing'             => zp_post( 'landing' ) ? 1 : 0,
			'min_deposit'         => max( 0.5, (float) zp_post( 'min_deposit' ) ),
			'max_deposit'         => max( 1, (float) zp_post( 'max_deposit' ) ),
			'manual_enabled'      => zp_post( 'manual_enabled' ) ? 1 : 0,
			'manual_title'        => zp_post( 'manual_title', 'Bank transfer' ),
			'bank_name'           => zp_post( 'bank_name' ),
			'bank_account_name'   => zp_post( 'bank_account_name' ),
			'bank_account_number' => preg_replace( '/[^0-9A-Za-z \-]/', '', zp_post( 'bank_account_number' ) ),
			'telegram'            => zp_post( 'telegram' ),
			'manual_instructions' => isset( $_POST['manual_instructions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['manual_instructions'] ) ) : '', // phpcs:ignore
			'paystack_enabled'    => zp_post( 'paystack_enabled' ) ? 1 : 0,
			'support_email'       => sanitize_email( zp_post( 'support_email' ) ),
		);
		// Secrets are write-only: an empty field keeps the stored value.
		foreach ( array( 'api_key', 'paystack_secret' ) as $secret ) {
			$val = trim( zp_post( $secret ) );
			if ( '' !== $val ) {
				$new[ $secret ] = $val;
			}
		}
		$key_changed = isset( $new['api_key'] ) && $new['api_key'] !== zp_get( 'api_key' );
		zp_update_settings( $new );
		delete_transient( 'zp_public_prices' );
		if ( $key_changed ) {
			ZP_API::flush_cache();
		}
		if ( 'test' === zp_post( 'zp_then' ) ) {
			$res = ZP_API::balance();
			if ( is_wp_error( $res ) ) {
				$hint = 'zp_config' === $res->get_error_code() ? 'Paste your proxy-ipv4.com API key first.' : $res->get_error_message();
				zp_flash( 'Settings saved, but the provider connection failed: ' . $hint, 'error' );
			} else {
				delete_option( 'zaxproxy_last_api_error' );
				zp_flash( sprintf( 'Settings saved. Connected as %s. Provider balance: %s %s.', $res['user'], number_format( (float) $res['balance'], 2 ), $res['currency'] ) );
			}
			return;
		}
		zp_flash( 'Settings saved.' );
	}
}
