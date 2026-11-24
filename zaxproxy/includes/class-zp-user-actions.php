<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_User_Actions {

	private static function verify( $action ) {
		if ( zp_post( 'zp_do' ) !== $action || ! wp_verify_nonce( zp_post( '_zpnonce' ), 'zp_' . $action ) ) {
			zp_flash( 'Your session expired. Please try again.', 'error' );
			return false;
		}
		return true;
	}

	/* ---------- Guest: login / register / password reset ---------- */

	public static function handle_guest( $page ) {
		$do = zp_post( 'zp_do' );
		if ( ! self::verify( $do ) ) {
			zp_redirect( zp_url( $page ) );
		}
		switch ( $do ) {
			case 'login':
				self::login();
				break;
			case 'register':
				self::register();
				break;
			case 'forgot':
				self::forgot();
				break;
			case 'reset':
				self::reset();
				break;
		}
		zp_redirect( zp_url( $page ) );
	}

	private static function login() {
		if ( zp_throttled( 'login', 10, 15 * MINUTE_IN_SECONDS ) ) {
			zp_flash( 'Too many attempts. Please wait a few minutes and try again.', 'error' );
			zp_redirect( zp_url( 'login' ) );
		}
		$login = zp_post( 'login' );
		$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
		$user  = wp_signon( array( 'user_login' => $login, 'user_password' => $pass, 'remember' => (bool) zp_post( 'remember' ) ), is_ssl() );
		if ( is_wp_error( $user ) ) {
			zp_flash( 'Incorrect email or password.', 'error' );
			zp_redirect( zp_url( 'login' ) );
		}
		$ds = zp_domain_store();
		if ( $ds && ! ZP_Resellers::is_customer_of( $user->ID, $ds->user_id ) ) {
			// A store's own domain is only for that store's customers.
			wp_logout();
			zp_flash( 'This account does not belong to this store.', 'error' );
			zp_redirect( zp_url( 'login' ) );
		}
		zp_redirect( zp_url( 'dashboard' ) );
	}

	private static function register() {
		if ( ! zp_get( 'registration' ) ) {
			zp_flash( 'Registration is currently closed.', 'error' );
			zp_redirect( zp_url( 'login' ) );
		}
		if ( zp_throttled( 'register', 5, HOUR_IN_SECONDS ) ) {
			zp_flash( 'Too many sign-ups from your network. Please try again later.', 'error' );
			zp_redirect( zp_url( 'register' ) );
		}
		$name  = zp_post( 'name' );
		$email = sanitize_email( zp_post( 'email' ) );
		$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
		$error = '';
		if ( ! empty( $_POST['website'] ) ) { // phpcs:ignore -- honeypot
			$error = 'Registration failed.';
		} elseif ( mb_strlen( $name ) < 2 ) {
			$error = 'Please enter your name.';
		} elseif ( ! is_email( $email ) ) {
			$error = 'Please enter a valid email address.';
		} elseif ( email_exists( $email ) ) {
			$error = 'An account with this email already exists. Try signing in.';
		} elseif ( strlen( $pass ) < 8 ) {
			$error = 'Your password must be at least 8 characters.';
		} elseif ( ! zp_post( 'terms' ) ) {
			$error = 'Please accept the terms to continue.';
		}
		if ( $error ) {
			zp_flash( $error, 'error' );
			zp_redirect( zp_url( 'register' ) );
		}
		$base     = sanitize_user( strstr( $email, '@', true ), true );
		$username = $base ? $base : 'user';
		while ( username_exists( $username ) ) {
			$username = $base . wp_rand( 100, 99999 );
		}
		$uid = wp_insert_user(
			array( 'user_login' => $username, 'user_email' => $email, 'user_pass' => $pass, 'display_name' => $name, 'first_name' => $name, 'role' => 'zaxproxy_customer' )
		);
		if ( is_wp_error( $uid ) ) {
			zp_flash( 'Could not create your account. Please try again.', 'error' );
			zp_redirect( zp_url( 'register' ) );
		}
		if ( zp_reseller_ctx() ) {
			update_user_meta( $uid, 'zp_reseller_id', (int) zp_reseller_ctx()->user_id );
		}
		wp_set_current_user( $uid );
		wp_set_auth_cookie( $uid, true, is_ssl() );
		zp_flash( 'Welcome to ' . zp_get( 'brand' ) . '! Add funds to buy your first proxy.' );
		zp_redirect( zp_url( 'dashboard' ) );
	}

	private static function forgot() {
		$generic = 'If that email is registered, a reset link is on its way.';
		if ( zp_throttled( 'forgot', 5, HOUR_IN_SECONDS ) ) {
			zp_flash( $generic, 'info' );
			zp_redirect( zp_url( 'forgot' ) );
		}
		$user = get_user_by( 'email', sanitize_email( zp_post( 'email' ) ) );
		if ( $user ) {
			$key = get_password_reset_key( $user );
			if ( ! is_wp_error( $key ) ) {
				$link = zp_url( 'reset', array( 'key' => $key, 'login' => $user->user_login ) );
				wp_mail(
					$user->user_email,
					'[' . zp_get( 'brand' ) . '] Reset your password',
					"Hi {$user->display_name},\n\nUse the link below to choose a new password. It expires in 24 hours.\n\n{$link}\n\nIf you did not request this, you can ignore this email."
				);
			}
		}
		zp_flash( $generic, 'info' );
		zp_redirect( zp_url( 'login' ) );
	}

	private static function reset() {
		$key   = zp_post( 'key' );
		$login = zp_post( 'login' );
		$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
		$user  = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			zp_flash( 'This reset link is invalid or has expired. Request a new one.', 'error' );
			zp_redirect( zp_url( 'forgot' ) );
		}
		if ( strlen( $pass ) < 8 ) {
			zp_flash( 'Your password must be at least 8 characters.', 'error' );
			zp_redirect( zp_url( 'reset', array( 'key' => $key, 'login' => $login ) ) );
		}
		reset_password( $user, $pass );
		zp_flash( 'Password updated. You can sign in now.' );
		zp_redirect( zp_url( 'login' ) );
	}

	/* ---------- Authenticated actions ---------- */

	public static function handle( $page ) {
		$do   = zp_post( 'zp_do' );
		$user = wp_get_current_user();
		if ( ! self::verify( $do ) ) {
			zp_redirect( zp_url( $page ) );
		}
		switch ( $do ) {
			case 'buy':
				$spec = ZP_Orders::sanitize_spec( wp_unslash( $_POST ) ); // phpcs:ignore
				$res  = is_wp_error( $spec ) ? $spec : ZP_Orders::purchase( $user->ID, $spec );
				if ( is_wp_error( $res ) ) {
					zp_flash( $res->get_error_message(), 'error' );
					zp_redirect( zp_url( 'zp_funds' === $res->get_error_code() ? 'wallet' : 'buy' ) );
				}
				$o = ZP_Orders::get( $res );
				zp_flash( 'completed' === $o->status ? 'Order complete. Your proxies are ready.' : 'Order placed. Your proxies are being set up and will appear here shortly.' );
				zp_redirect( zp_url( 'proxies' ) );
				break;

			case 'extend':
				$ids = isset( $_POST['proxy_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['proxy_ids'] ) ) : array(); // phpcs:ignore
				$res = ZP_Orders::extend( $user->ID, $ids, (int) zp_post( 'days' ) );
				if ( is_wp_error( $res ) ) {
					zp_flash( $res->get_error_message(), 'error' );
					zp_redirect( zp_url( 'zp_funds' === $res->get_error_code() ? 'wallet' : 'proxies' ) );
				}
				zp_flash( 'Renewal successful. New expiry dates are shown below.' );
				zp_redirect( zp_url( 'proxies' ) );
				break;

			case 'note':
				global $wpdb;
				$wpdb->update(
					zp_table( 'proxies' ),
					array( 'note' => mb_substr( zp_post( 'note' ), 0, 190 ) ),
					array( 'id' => (int) zp_post( 'proxy_id' ), 'user_id' => $user->ID )
				);
				zp_flash( 'Label saved.' );
				zp_redirect( zp_url( 'proxies' ) );
				break;

			case 'deposit':
				self::deposit( $user );
				break;

			case 'ticket_new':
				$subject = mb_substr( zp_post( 'subject' ), 0, 190 );
				$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore
				if ( mb_strlen( $subject ) < 3 || mb_strlen( $message ) < 10 ) {
					zp_flash( 'Please add a subject and describe your issue in a little more detail.', 'error' );
					zp_redirect( zp_url( 'tickets' ) );
				}
				if ( zp_throttled( 'ticket' . $user->ID, 10, HOUR_IN_SECONDS ) ) {
					zp_flash( 'You are opening tickets too quickly. Please wait a little.', 'error' );
					zp_redirect( zp_url( 'tickets' ) );
				}
				$id = ZP_Tickets::create( $user->ID, $subject, $message );
				zp_flash( 'Ticket opened. We will reply as soon as possible.' );
				zp_redirect( zp_url( 'tickets', array( 'id' => $id ) ) );
				break;

			case 'ticket_reply':
			case 'ticket_close':
				$t = ZP_Tickets::get( (int) zp_post( 'ticket_id' ) );
				if ( ! $t || (int) $t->user_id !== $user->ID ) {
					zp_redirect( zp_url( 'tickets' ) );
				}
				if ( 'ticket_close' === $do ) {
					ZP_Tickets::close( $t->id );
					zp_flash( 'Ticket closed.' );
					zp_redirect( zp_url( 'tickets' ) );
				}
				$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore
				if ( '' !== trim( $message ) ) {
					ZP_Tickets::add_message( $t->id, $user->ID, $message, false );
				}
				zp_redirect( zp_url( 'tickets', array( 'id' => $t->id ) ) );
				break;

			case 'reseller_save':
			case 'reseller_renew':
			case 'reseller_domain':
			case 'reseller_deposit_approve':
			case 'reseller_deposit_reject':
			case 'reseller_adjust':
			case 'reseller_ticket_reply':
			case 'reseller_ticket_close':
				if ( zp_reseller_ctx() ) {
					zp_redirect( zp_url( 'dashboard' ) );
				}
				ZP_Resellers::handle( $do, $user );
				break;

			case 'profile':
				$name  = zp_post( 'name' );
				$email = sanitize_email( zp_post( 'email' ) );
				if ( mb_strlen( $name ) < 2 || ! is_email( $email ) ) {
					zp_flash( 'Enter a valid name and email address.', 'error' );
					zp_redirect( zp_url( 'profile' ) );
				}
				$owner = email_exists( $email );
				if ( $owner && (int) $owner !== $user->ID ) {
					zp_flash( 'That email is already used by another account.', 'error' );
					zp_redirect( zp_url( 'profile' ) );
				}
				wp_update_user( array( 'ID' => $user->ID, 'display_name' => $name, 'first_name' => $name, 'user_email' => $email ) );
				zp_flash( 'Profile updated.' );
				zp_redirect( zp_url( 'profile' ) );
				break;

			case 'password':
				$current = isset( $_POST['current'] ) ? (string) wp_unslash( $_POST['current'] ) : ''; // phpcs:ignore
				$new     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
				if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
					zp_flash( 'Your current password is incorrect.', 'error' );
				} elseif ( strlen( $new ) < 8 ) {
					zp_flash( 'Your new password must be at least 8 characters.', 'error' );
				} else {
					wp_set_password( $new, $user->ID );
					wp_set_auth_cookie( $user->ID, true, is_ssl() );
					zp_flash( 'Password changed.' );
				}
				zp_redirect( zp_url( 'profile' ) );
				break;
		}
		zp_redirect( zp_url( $page ) );
	}

	private static function deposit( $user ) {
		$gateways = ZP_Gateways::available();
		$gateway  = sanitize_key( zp_post( 'gateway' ) );
		$amount   = round( (float) zp_post( 'amount' ), 2 );
		$min      = (float) zp_get( 'min_deposit' );
		$max      = (float) zp_get( 'max_deposit' );
		if ( ! isset( $gateways[ $gateway ] ) ) {
			zp_flash( 'Choose a payment method.', 'error' );
			zp_redirect( zp_url( 'wallet' ) );
		}
		if ( $amount < $min || $amount > $max ) {
			zp_flash( sprintf( 'Enter an amount between %s and %s.', zp_money( $min ), zp_money( $max ) ), 'error' );
			zp_redirect( zp_url( 'wallet' ) );
		}
		if ( 'paystack' === $gateway ) {
			$url = ZP_Gateways::paystack_start( $user, $amount );
			if ( is_wp_error( $url ) ) {
				zp_flash( $url->get_error_message(), 'error' );
				zp_redirect( zp_url( 'wallet' ) );
			}
			wp_redirect( esc_url_raw( $url ) ); // phpcs:ignore -- external hosted checkout
			exit;
		}
		$note = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : ''; // phpcs:ignore
		if ( mb_strlen( $note ) < 3 ) {
			zp_flash( 'Enter the name on the bank account you paid from so we can match your transfer.', 'error' );
			zp_redirect( zp_url( 'wallet' ) );
		}
		global $wpdb;
		$open = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'deposits' ) . " WHERE user_id = %d AND status = 'pending' AND gateway = 'manual'", $user->ID ) );
		if ( $open >= 5 ) {
			zp_flash( 'You already have several payments awaiting confirmation.', 'error' );
			zp_redirect( zp_url( 'wallet' ) );
		}
		$reseller = ZP_Resellers::of_user( $user->ID );
		$ref      = ZP_Wallet::create_deposit( $user->ID, 'manual', $amount, $note, $amount, strtoupper( (string) zp_get( 'currency_code', 'NGN' ) ), $reseller ? $reseller->user_id : 0 );
		zp_flash( zp_telegram_url() ? 'Deposit recorded. Send your payment receipt on Telegram so we can credit your wallet.' : 'Deposit recorded. Your wallet will be credited once the payment is confirmed.' );
		zp_redirect( zp_url( 'wallet', array( 'sent' => $ref ) ) . '#zp-receipts' );
	}

	/* ---------- AJAX (JSON) ---------- */

	public static function ajax() {
		if ( ! wp_verify_nonce( isset( $_REQUEST['nonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ) : '', 'zp_ajax' ) ) { // phpcs:ignore
			wp_send_json( array( 'ok' => false, 'error' => 'Session expired. Reload the page.' ), 403 );
		}
		$act  = sanitize_key( isset( $_REQUEST['act'] ) ? $_REQUEST['act'] : '' ); // phpcs:ignore
		$user = wp_get_current_user();
		$res  = new WP_Error( 'zp_input', 'Unknown action.' );

		if ( 'options' === $act ) {
			$res = ZP_Orders::options( sanitize_key( zp_query( 'type' ) ) );
		} elseif ( 'quote' === $act ) {
			$spec = ZP_Orders::sanitize_spec( wp_unslash( $_POST ) ); // phpcs:ignore
			$res  = is_wp_error( $spec ) ? $spec : ZP_Orders::quote( $spec, $user->ID );
			if ( ! is_wp_error( $res ) ) {
				unset( $res['cost'], $res['platform'], $res['reseller'] );
				$res['balance'] = ZP_Wallet::balance( $user->ID );
			}
		} elseif ( 'extend_quote' === $act ) {
			$ids = isset( $_POST['proxy_ids'] ) ? array_map( 'intval', (array) wp_unslash( $_POST['proxy_ids'] ) ) : array(); // phpcs:ignore
			$res = ZP_Orders::extend_quote( $user->ID, $ids, (int) zp_post( 'days' ) );
			if ( ! is_wp_error( $res ) ) {
				$res = array( 'total' => $res['total'], 'count' => $res['count'], 'balance' => ZP_Wallet::balance( $user->ID ) );
			}
		} elseif ( 'extend_days' === $act ) {
			$res = ZP_API::days( sanitize_key( zp_query( 'type' ) ) );
			if ( ! is_wp_error( $res ) ) {
				$res = array( 'days' => zp_filter_days( (array) $res['days'] ) );
			}
		}
		if ( is_wp_error( $res ) ) {
			$msg = in_array( $res->get_error_code(), array( 'zp_config', 'zp_http' ), true ) ? 'The store is temporarily unavailable. Please try again later.' : $res->get_error_message();
			wp_send_json( array( 'ok' => false, 'error' => $msg ) );
		}
		wp_send_json( array( 'ok' => true, 'data' => $res ) );
	}

	/* ---------- Export ---------- */

	public static function export() {
		if ( ! wp_verify_nonce( zp_query( '_zpnonce' ), 'zp_export' ) ) {
			zp_redirect( zp_url( 'proxies' ) );
		}
		$user    = wp_get_current_user();
		$filters = array( 'type' => sanitize_key( zp_query( 'type' ) ), 'state' => 'active' );
		if ( zp_query( 'ids' ) ) {
			$filters = array( 'ids' => array_filter( array_map( 'intval', explode( ',', zp_query( 'ids' ) ) ) ) );
		}
		$rows   = ZP_Orders::user_proxies( $user->ID, $filters );
		$format = zp_query( 'format', 'ip:port:user:pass' );
		$proto  = 'socks5' === zp_query( 'protocol' ) ? 'socks5' : 'http';
		$lines  = ZP_Orders::export_lines( $rows, $format, $proto );
		nocache_headers();
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="proxies-' . gmdate( 'Ymd-His' ) . '.txt"' );
		echo implode( "\r\n", $lines ); // phpcs:ignore
		exit;
	}
}
