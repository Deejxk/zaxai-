<?php
/**
 * Request handlers for every page. Each one either redirects or renders a view and exits.
 */
defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Small helpers
 * ---------------------------------------------------------------------- */

function zxc_is_post() {
	return 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' );
}

function zxc_check_nonce( $action ) {
	$nonce = isset( $_POST['_zxc'] ) ? sanitize_text_field( wp_unslash( $_POST['_zxc'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, $action ) ) {
		wp_die( 'This form has expired. Please go back, refresh the page and try again.', 'Session expired', array( 'response' => 403, 'back_link' => true ) );
	}
}

function zxc_flash( $text, $type = 'ok' ) {
	set_transient( 'zxc_flash_' . get_current_user_id(), array( 'text' => $text, 'type' => $type ), 120 );
}

function zxc_take_flash() {
	$key = 'zxc_flash_' . get_current_user_id();
	$f   = get_transient( $key );
	if ( $f ) {
		delete_transient( $key );
	}
	return $f ? $f : null;
}

/** Save an uploaded logo to the media folder; returns URL, '' when none, or WP_Error. */
function zxc_handle_logo_upload( $field ) {
	if ( empty( $_FILES[ $field ] ) || empty( $_FILES[ $field ]['name'] ) ) {
		return '';
	}
	if ( (int) $_FILES[ $field ]['size'] > 500 * 1024 ) {
		return new WP_Error( 'zxc_logo', 'Logo must be smaller than 500 KB.' );
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	$res = wp_handle_upload(
		$_FILES[ $field ], // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		array(
			'test_form' => false,
			'mimes'     => array( 'png' => 'image/png', 'jpg|jpeg' => 'image/jpeg', 'webp' => 'image/webp' ),
		)
	);
	if ( isset( $res['error'] ) ) {
		return new WP_Error( 'zxc_logo', 'Logo upload failed: ' . $res['error'] );
	}
	return esc_url_raw( $res['url'] );
}

/* -------------------------------------------------------------------------
 * Sign-up & login
 * ---------------------------------------------------------------------- */

function zxc_ctl_signup() {
	if ( is_user_logged_in() && zxc_tenant_for_user( get_current_user_id() ) ) {
		zxc_redirect( home_url( '/dashboard' ) );
	}
	$tier = zxc_get( 'plan', 20 );
	$tier = isset( zxc_tiers()[ $tier ] ) ? $tier : 'business';
	if ( ! zxc_is_post() ) {
		zxc_render( zxc_view_signup( array( 'tier' => $tier ) ) );
	}
	zxc_check_nonce( 'zxc_signup' );
	$v = array(
		'owner_name'    => zxc_post( 'owner_name', 60 ),
		'email'         => sanitize_email( zxc_post( 'email', 190 ) ),
		'business_name' => zxc_post( 'business_name', 50 ),
		'brand_color'   => zxc_post( 'brand_color', 7 ),
		'price_hour'    => absint( zxc_post( 'price_hour', 9 ) ),
		'price_daily'   => absint( zxc_post( 'price_daily', 9 ) ),
		'flw_public'    => zxc_post( 'flw_public', 200 ),
		'flw_secret'    => zxc_post( 'flw_secret', 200 ),
		'flw_hash'      => zxc_post( 'flw_hash', 200 ),
		'location'      => zxc_post( 'location', 60 ),
		'tier'          => isset( zxc_tiers()[ zxc_post( 'tier', 20 ) ] ) ? zxc_post( 'tier', 20 ) : 'business',
	);
	$password = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	$fail     = function ( $msg, $step ) use ( $v ) {
		zxc_render( zxc_view_signup( array( 'error' => $msg, 'values' => $v, 'tier' => $v['tier'], 'start' => $step ) ), 400 );
	};

	if ( '' === $v['owner_name'] ) {
		$fail( 'Please enter your name.', 0 );
	}
	if ( ! is_email( $v['email'] ) ) {
		$fail( 'Please enter a valid email address.', 0 );
	}
	if ( email_exists( $v['email'] ) ) {
		$fail( 'An account with this email already exists. <a href="' . esc_url( home_url( '/login' ) ) . '">Log in instead</a>.', 0 );
	}
	if ( strlen( $password ) < 8 ) {
		$fail( 'Your password must be at least 8 characters.', 0 );
	}
	if ( '' === $v['business_name'] ) {
		$fail( 'Please enter your business name.', 1 );
	}
	if ( ! zxc_is_hex( $v['brand_color'] ) ) {
		$v['brand_color'] = '#4F3BF0';
	}
	if ( ( $v['flw_public'] && ! preg_match( '/^FLWPUBK(_TEST)?-/', $v['flw_public'] ) ) || ( $v['flw_secret'] && ! preg_match( '/^FLWSECK(_TEST)?-/', $v['flw_secret'] ) ) ) {
		$fail( 'Flutterwave keys should start with FLWPUBK- and FLWSECK-. You can also leave them empty for now.', 3 );
	}
	if ( '' === $v['location'] ) {
		$fail( 'Please name your first location.', 4 );
	}
	if ( empty( $_POST['terms'] ) ) {
		$fail( 'Please accept the terms of service.', 4 );
	}
	$logo = zxc_handle_logo_upload( 'logo_file' );
	if ( is_wp_error( $logo ) ) {
		$fail( $logo->get_error_message(), 1 );
	}

	$login = sanitize_user( strtolower( strstr( $v['email'], '@', true ) ), true );
	$login = $login ? $login : 'owner';
	$base  = $login;
	for ( $i = 2; username_exists( $login ); $i++ ) {
		$login = $base . $i;
	}
	$user_id = wp_insert_user(
		array(
			'user_login'   => $login,
			'user_email'   => $v['email'],
			'user_pass'    => $password,
			'display_name' => $v['owner_name'],
			'first_name'   => $v['owner_name'],
			'role'         => 'zx_owner',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		$fail( $user_id->get_error_message(), 0 );
	}
	zxc_create_tenant(
		array(
			'owner_id'    => $user_id,
			'name'        => $v['business_name'],
			'brand_color' => $v['brand_color'],
			'logo_url'    => $logo,
			'location'    => $v['location'],
			'flw_public'  => $v['flw_public'],
			'flw_secret'  => $v['flw_secret'],
			'flw_hash'    => $v['flw_hash'],
			'tier'        => $v['tier'],
			'price_hour'  => $v['price_hour'],
			'price_daily' => $v['price_daily'],
		)
	);
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id, true, is_ssl() );
	zxc_flash( 'Welcome to ' . zxc_setting( 'platform_name' ) . '! Your hotspot brand is ready. Follow this guide to go live.' );
	zxc_redirect( home_url( '/dashboard/docs?welcome=1' ) );
}

function zxc_ctl_login() {
	if ( is_user_logged_in() ) {
		zxc_redirect( zxc_home_panel() );
	}
	if ( ! zxc_is_post() ) {
		zxc_render( zxc_view_login( array() ) );
	}
	zxc_check_nonce( 'zxc_login' );
	$email = zxc_post( 'email', 190 );
	$user  = wp_signon(
		array(
			'user_login'    => $email,
			'user_password' => isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
			'remember'      => ! empty( $_POST['remember'] ), // phpcs:ignore
		),
		is_ssl()
	);
	if ( is_wp_error( $user ) ) {
		zxc_render( zxc_view_login( array( 'error' => 'Incorrect email or password.', 'email' => $email ) ), 401 );
	}
	wp_set_current_user( $user->ID );
	zxc_redirect( zxc_home_panel( $user ) );
}

/* -------------------------------------------------------------------------
 * Customer portal
 * ---------------------------------------------------------------------- */

function zxc_ctl_portal( $t, $path ) {
	$seg = $path ? explode( '/', $path ) : array( '' );
	$ctx = zxc_capture_hotspot_ctx( $t );
	$sub = zxc_subscription( $t );

	switch ( $seg[0] ) {
		case '':
			zxc_render(
				zxc_view_portal_home(
					$t,
					array(
						'plans' => zxc_plans( $t->id, true ),
						'ctx'   => $ctx,
						'error' => zxc_portal_error( zxc_get( 'error', 200 ) ),
					)
				)
			);
			break;

		case 'buy':
			$plan = isset( $seg[1] ) ? zxc_plan( $t->id, (int) $seg[1] ) : null;
			if ( ! $plan || ! $plan->active ) {
				zxc_redirect( zxc_portal_url( $t ) );
			}
			$demo   = ! zxc_gateway_ready( $t );
			$manual = zxc_manual_ready( $t );
			$online = ! $demo || $t->demo_payments;
			$args   = array( 'plan' => $plan, 'ctx' => $ctx, 'demo' => $demo, 'manual' => $manual, 'online' => $online, 'closed' => ! $sub['active'] || ( ! $online && ! $manual ) );
			if ( ! zxc_is_post() ) {
				zxc_render( zxc_view_portal_checkout( $t, $args ) );
			}
			$phone = zxc_normalize_phone( zxc_post( 'phone', 20 ) );
			$email = sanitize_email( zxc_post( 'email', 190 ) );
			$args['values'] = array( 'phone' => zxc_post( 'phone', 20 ), 'email' => $email, 'method' => zxc_post( 'method', 10 ) );
			if ( $args['closed'] ) {
				$args['error'] = 'Online payment is not available right now. Please buy a voucher from the attendant.';
				zxc_render( zxc_view_portal_checkout( $t, $args ), 400 );
			}
			if ( ! zxc_valid_phone( $phone ) ) {
				$args['error'] = 'Please enter a valid Nigerian phone number, e.g. 0803 123 4567.';
				zxc_render( zxc_view_portal_checkout( $t, $args ), 400 );
			}
			if ( zxc_voucher_allowance( $t ) < 1 ) {
				$args['error'] = 'This hotspot has reached its monthly sales limit. Please contact the owner.';
				zxc_render( zxc_view_portal_checkout( $t, $args ), 400 );
			}
			if ( ! $email ) {
				$email = $phone . '@customers.' . wp_parse_url( home_url(), PHP_URL_HOST ); // Flutterwave requires an email
			}
			$use_manual = $manual && ( 'manual' === zxc_post( 'method', 10 ) || ! $online );
			$order      = zxc_create_order( $t, $plan, array( 'phone' => $phone, 'email' => $email, 'mac' => $ctx['mac'] ?? '', 'login_url' => $ctx['login'] ?? '', 'dst' => $ctx['dst'] ?? '', 'gateway' => $use_manual ? 'manual' : ( $demo ? 'demo' : 'flutterwave' ) ) );
			if ( $use_manual ) {
				zxc_redirect( zxc_portal_url( $t, 'pay/manual/' . $order->reference ) );
			}
			if ( $demo ) {
				zxc_redirect( zxc_portal_url( $t, 'pay/demo/' . $order->reference ) );
			}
			$link = zxc_flw_checkout(
				$t->flw_secret,
				array(
					'reference' => $order->reference,
					'amount'    => $order->amount,
					'email'     => $email,
					'phone'     => $phone,
					'redirect'  => zxc_portal_url( $t, 'pay/return' ),
					'title'     => $t->name . ' · ' . $plan->name,
					'logo'      => $t->logo_url,
					'meta'      => array( 'hotspot' => $t->slug, 'plan' => $plan->name ),
				)
			);
			if ( is_wp_error( $link ) ) {
				zxc_fail_order( $order->reference );
				$args['error'] = 'We could not reach the payment provider. Please try again in a moment.';
				zxc_render( zxc_view_portal_checkout( $t, $args ), 502 );
			}
			wp_redirect( $link, 303 ); // phpcs:ignore WordPress.Security.SafeRedirect -- Flutterwave checkout host
			exit;

		case 'pay':
			if ( 'manual' === ( $seg[1] ?? '' ) ) {
				$o = zxc_order( $seg[2] ?? '', $t->id );
				if ( ! $o || 'manual' !== $o->gateway ) {
					zxc_redirect( zxc_portal_url( $t ) );
				}
				if ( 'success' === $o->status ) {
					zxc_redirect( zxc_portal_url( $t, 'success/' . $o->reference ) );
				}
				if ( zxc_is_post() && 'pending' === $o->status ) {
					zxc_check_nonce( 'zxc_manual' );
					$payer = zxc_post( 'payer_name', 120 );
					if ( '' === $payer ) {
						zxc_render( zxc_view_portal_manual( $t, $o, 'Please enter the name on the account you paid from.' ), 400 );
					}
					global $wpdb;
					$wpdb->update( zxc_table( 'transactions' ), array( 'payer_name' => $payer, 'channel' => 'submitted' ), array( 'id' => $o->id, 'status' => 'pending' ) );
					zxc_redirect( zxc_portal_url( $t, 'pay/manual/' . $o->reference ) . '?sent=1' );
				}
				zxc_render( zxc_view_portal_manual( $t, $o ) );
			}
			if ( 'demo' === ( $seg[1] ?? '' ) ) {
				$o = zxc_order( $seg[2] ?? '', $t->id );
				if ( ! $o || 'demo' !== $o->gateway ) {
					zxc_redirect( zxc_portal_url( $t ) );
				}
				if ( zxc_is_post() && 'pending' === $o->status && ! zxc_gateway_ready( $t ) ) {
					if ( 'success' === zxc_post( 'result', 10 ) ) {
						zxc_fulfill_order( $o->reference, 'demo' );
					} else {
						zxc_fail_order( $o->reference );
					}
					zxc_redirect( zxc_portal_url( $t, 'pay/check/' . $o->reference ) );
				}
				if ( 'pending' !== $o->status ) {
					zxc_redirect( zxc_portal_url( $t, 'pay/check/' . $o->reference ) );
				}
				zxc_render( zxc_view_portal_demo_pay( $t, $o ) );
			}
			// Flutterwave redirect (?tx_ref=...) or our own re-check (/pay/check/REF).
			$ref = 'check' === ( $seg[1] ?? '' ) ? ( $seg[2] ?? '' ) : zxc_get( 'tx_ref', 40 );
			$o   = zxc_order( sanitize_text_field( $ref ), $t->id );
			if ( ! $o ) {
				zxc_redirect( zxc_portal_url( $t ) );
			}
			if ( 'pending' === $o->status && 'flutterwave' === $o->gateway ) {
				$o = zxc_verify_order( $o->reference );
				if ( 'pending' === $o->status && 'cancelled' === zxc_get( 'status', 20 ) ) {
					zxc_fail_order( $o->reference );
					$o = zxc_order( $o->reference );
				}
			}
			if ( 'success' === $o->status ) {
				zxc_redirect( zxc_portal_url( $t, 'success/' . $o->reference ) );
			}
			if ( 'manual' === $o->gateway && 'pending' === $o->status ) {
				zxc_redirect( zxc_portal_url( $t, 'pay/manual/' . $o->reference ) );
			}
			zxc_render( in_array( $o->status, array( 'failed', 'declined' ), true ) ? zxc_view_portal_failed( $t, $o ) : zxc_view_portal_pending( $t, $o ) );
			break;

		case 'success':
			$o = zxc_order( $seg[1] ?? '', $t->id );
			if ( ! $o || 'success' !== $o->status ) {
				zxc_redirect( zxc_portal_url( $t ) );
			}
			$v     = zxc_voucher_by_id( $o->voucher_id );
			$lctx  = ! empty( $ctx['login'] ) ? $ctx : array( 'login' => zxc_login_url_allowed( (string) $o->login_url, $t ) ? $o->login_url : '' );
			$link  = zxc_login_link( $lctx, $v->code, zxc_portal_url( $t, 'connected' ) . '?code=' . $v->code );
			$ready = zxc_voucher_on_router( $v ) || ! zxc_router_online( $t->id );
			zxc_render( zxc_view_portal_success( $t, array( 'order' => $o, 'voucher' => $v, 'link' => $link, 'ready' => $ready ) ) );
			break;

		case 'voucher': // no-JS fallback for the voucher form
			$code = zxc_normalize_code( zxc_post( 'code', 20 ) );
			$v    = $code ? zxc_voucher_by_code( $code, $t->id ) : null;
			if ( ! $v ) {
				zxc_redirect( zxc_portal_url( $t, 'plan' ) . '?tab=voucher&error=' . rawurlencode( 'That voucher code is not valid.' ) );
			}
			$link = zxc_login_link( $ctx, $code, zxc_portal_url( $t, 'connected' ) . '?code=' . $code );
			$st   = zxc_voucher_state( $v );
			if ( $link && in_array( $st['state'], array( 'unused', 'active' ), true ) ) {
				wp_redirect( $link ); // phpcs:ignore WordPress.Security.SafeRedirect -- validated router URL
				exit;
			}
			zxc_redirect( zxc_portal_url( $t, 'plan' ) . '?code=' . $code );
			break;

		case 'plan':
		case 'status':
		case 'connected':
			$code = zxc_normalize_code( zxc_get( 'code', 20 ) );
			$v    = $code ? zxc_voucher_by_code( $code, $t->id ) : null;
			zxc_render(
				zxc_view_portal_plan(
					$t,
					array(
						'code'   => $code,
						'v'      => $v,
						'ctx'    => $ctx,
						'online' => 'connected' === $seg[0],
						'tab'    => zxc_get( 'tab', 10 ) === 'voucher' || ! $code ? 'voucher' : 'status',
						'error'  => $code && ! $v ? 'We could not find that voucher code.' : zxc_get( 'error', 200 ),
					)
				)
			);
			break;

		case 'recover':
			$args = array( 'ctx' => $ctx );
			if ( zxc_is_post() ) {
				global $wpdb;
				$phone = zxc_normalize_phone( zxc_post( 'phone', 20 ) );
				$ref   = strtoupper( zxc_post( 'reference', 40 ) );
				$args['phone'] = $phone;
				$args['ref']   = $ref;
				if ( ! zxc_valid_phone( $phone ) ) {
					$args['error'] = 'Please enter a valid phone number.';
				} else {
					$rows = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT t.reference, t.mac, t.paid_at, v.code, p.name AS plan_name FROM ' . zxc_table( 'transactions' ) . ' t
							JOIN ' . zxc_table( 'vouchers' ) . ' v ON v.id = t.voucher_id JOIN ' . zxc_table( 'plans' ) . " p ON p.id = t.plan_id
							WHERE t.tenant_id = %d AND t.phone = %s AND t.status = 'success' ORDER BY t.paid_at DESC LIMIT 20",
							$t->id, $phone
						)
					);
					$args['results'] = array_values(
						array_filter(
							$rows,
							function ( $r ) use ( $ref, $ctx ) {
								return ( $ref && $r->reference === $ref ) || ( ! empty( $ctx['mac'] ) && $r->mac === $ctx['mac'] );
							}
						)
					);
					if ( ! $args['results'] ) {
						$args['error'] = 'No vouchers found. Check the phone number, or enter the payment reference from your receipt.';
					}
				}
			}
			zxc_render( zxc_view_portal_recover( $t, $args ) );
			break;

		case 'wallet':
			zxc_portal_wallet( $t, $seg, $ctx, $sub );
			break;

		case 'plans':
			zxc_render( zxc_view_portal_plans( $t, array( 'plans' => zxc_plans( $t->id, true ), 'ctx' => $ctx ) ) );
			break;

		case 'help':
			$args = array();
			if ( zxc_is_post() ) {
				$name  = zxc_post( 'name', 120 );
				$phone = zxc_normalize_phone( zxc_post( 'phone', 20 ) );
				$body  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( zxc_throttled( 'help' . $t->id, 4 ) ) {
					$args['error'] = 'You have sent several messages already. Please wait a few minutes.';
				} elseif ( '' === $name || ! zxc_valid_phone( $phone ) || strlen( $body ) < 5 ) {
					$args['error'] = 'Please enter your name, a valid phone number and your message.';
				} else {
					zxc_add_message( array( 'tenant_id' => $t->id, 'from_type' => 'customer', 'name' => $name, 'phone' => $phone, 'subject' => zxc_post( 'topic', 80 ) ? zxc_post( 'topic', 80 ) : 'Support request', 'body' => $body ) );
					$args['sent'] = true;
				}
				$args['values'] = array( 'name' => $name, 'phone' => zxc_post( 'phone', 20 ), 'message' => $body );
			}
			zxc_render( zxc_view_portal_help( $t, $args ) );
			break;

		case 'terms':
			zxc_render( zxc_view_portal_terms( $t ) );
			break;

		default:
			zxc_redirect( zxc_portal_url( $t ) );
	}
}

function zxc_portal_error( $msg ) {
	if ( '' === $msg ) {
		return '';
	}
	$map = array(
		'/expired|uptime/i'          => 'Your plan has expired. Please buy a new plan.',
		'/limit|traffic|exhaust/i'   => 'Your data bundle is exhausted. Please buy a new plan.',
		'/no more sessions|in use/i' => 'This voucher is already in use on the maximum number of devices.',
		'/invalid|password|user/i'   => 'That voucher code is not valid. Please check and try again.',
	);
	foreach ( $map as $re => $text ) {
		if ( preg_match( $re, $msg ) ) {
			return $text;
		}
	}
	return substr( $msg, 0, 160 );
}

/* -------------------------------------------------------------------------
 * Owner dashboard
 * ---------------------------------------------------------------------- */

function zxc_ctl_dashboard( $path ) {
	if ( ! is_user_logged_in() ) {
		zxc_redirect( home_url( '/login' ) );
	}
	$t = zxc_dashboard_tenant();
	if ( ! $t ) {
		if ( zxc_is_platform_admin() ) {
			zxc_flash( 'Choose a business below to open its owner dashboard.' );
			zxc_redirect( home_url( '/zx-admin/businesses' ) );
		}
		zxc_redirect( home_url( '/signup' ) );
	}
	global $wpdb;
	$seg  = $path ? explode( '/', $path ) : array( '' );
	$page = $seg[0];
	$base = home_url( '/dashboard' );

	if ( zxc_is_post() ) {
		zxc_check_nonce( 'zxc_dash' );
		zxc_dashboard_post( $t, $seg );
	}

	switch ( $page ) {
		case '':
			zxc_render( zxc_view_dash_home( $t, zxc_dashboard_stats( $t, zxc_get( 'range', 3 ) ), zxc_analytics( $t, 30 ) ) );
			break;
		case 'plans':
			if ( isset( $seg[1] ) ) {
				$plan = 'new' === $seg[1] ? null : zxc_plan( $t->id, (int) $seg[1] );
				if ( 'new' !== $seg[1] && ! $plan ) {
					zxc_redirect( $base . '/plans' );
				}
				zxc_render( zxc_view_dash_plan_form( $t, $plan ) );
			}
			$plans = $wpdb->get_results( $wpdb->prepare( 'SELECT p.*, (SELECT COUNT(*) FROM ' . zxc_table( 'transactions' ) . " x WHERE x.plan_id = p.id AND x.status = 'success') AS sold FROM " . zxc_table( 'plans' ) . ' p WHERE p.tenant_id = %d ORDER BY p.sort_order, p.price', $t->id ) );
			zxc_render( zxc_view_dash_plans( $t, $plans ) );
			break;
		case 'vouchers':
			if ( 'print' === ( $seg[1] ?? '' ) ) {
				$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT v.*, p.name AS plan_name, p.price, p.duration_min, p.data_mb FROM ' . zxc_table( 'vouchers' ) . ' v JOIN ' . zxc_table( 'plans' ) . ' p ON p.id = v.plan_id WHERE v.tenant_id = %d AND v.batch = %s ORDER BY v.id', $t->id, sanitize_text_field( $seg[2] ?? '' ) ) );
				zxc_render( zxc_view_dash_print( $t, sanitize_text_field( $seg[2] ?? '' ), $rows ) );
			}
			$status = in_array( zxc_get( 'status', 10 ), array( 'unused', 'active', 'expired', 'disabled' ), true ) ? zxc_get( 'status', 10 ) : 'all';
			$q      = zxc_get( 'q', 40 );
			$where  = $wpdb->prepare( 'v.tenant_id = %d', $t->id );
			if ( 'all' !== $status ) {
				$where .= $wpdb->prepare( ' AND v.status = %s', $status );
			}
			if ( '' !== $q ) {
				$like   = '%' . $wpdb->esc_like( $q ) . '%';
				$where .= $wpdb->prepare( ' AND (v.code LIKE %s OR v.phone LIKE %s OR v.batch LIKE %s)', '%' . $wpdb->esc_like( zxc_normalize_code( $q ) ) . '%', $like, $like );
			}
			$rows    = $wpdb->get_results( 'SELECT v.*, p.name AS plan_name, p.devices FROM ' . zxc_table( 'vouchers' ) . ' v JOIN ' . zxc_table( 'plans' ) . " p ON p.id = v.plan_id WHERE $where ORDER BY v.id DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL
			$batches = $wpdb->get_results( $wpdb->prepare( 'SELECT v.batch, p.name AS plan_name, COUNT(*) AS cnt, SUM(v.status = \'unused\') AS unused, MAX(v.created) AS created FROM ' . zxc_table( 'vouchers' ) . ' v JOIN ' . zxc_table( 'plans' ) . ' p ON p.id = v.plan_id WHERE v.tenant_id = %d AND v.batch IS NOT NULL GROUP BY v.batch, p.name ORDER BY created DESC LIMIT 8', $t->id ) );
			zxc_render( zxc_view_dash_vouchers( $t, array( 'rows' => $rows, 'batches' => $batches, 'status' => $status, 'q' => $q, 'plans' => zxc_plans( $t->id ) ) ) );
			break;
		case 'sales':
			$status = in_array( zxc_get( 'status', 10 ), array( 'success', 'pending', 'failed' ), true ) ? zxc_get( 'status', 10 ) : 'all';
			$q      = zxc_get( 'q', 40 );
			$where  = $wpdb->prepare( 't.tenant_id = %d', $t->id );
			if ( 'all' !== $status ) {
				$where .= $wpdb->prepare( ' AND t.status = %s', $status );
			}
			if ( '' !== $q ) {
				$like   = '%' . $wpdb->esc_like( $q ) . '%';
				$where .= $wpdb->prepare( ' AND (t.phone LIKE %s OR t.reference LIKE %s)', $like, strtoupper( $like ) );
			}
			$sql = 'SELECT t.*, p.name AS plan_name, v.code AS voucher_code FROM ' . zxc_table( 'transactions' ) . ' t JOIN ' . zxc_table( 'plans' ) . ' p ON p.id = t.plan_id LEFT JOIN ' . zxc_table( 'vouchers' ) . " v ON v.id = t.voucher_id WHERE $where ORDER BY t.created DESC";
			if ( 'export' === ( $seg[1] ?? '' ) ) {
				zxc_export_sales_csv( $wpdb->get_results( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL
			}
			$rows   = $wpdb->get_results( $sql . ' LIMIT 300' ); // phpcs:ignore WordPress.DB.PreparedSQL
			$totals = $wpdb->get_row( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM ' . zxc_table( 'transactions' ) . " WHERE tenant_id = %d AND status = 'success'", $t->id ) );
			zxc_render( zxc_view_dash_sales( $t, array( 'rows' => $rows, 'totals' => $totals, 'status' => $status, 'q' => $q ) ) );
			break;
		case 'online':
			$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT o.*, r.name AS router_name, v.expires_at, p.name AS plan_name FROM ' . zxc_table( 'online' ) . ' o JOIN ' . zxc_table( 'routers' ) . ' r ON r.id = o.router_id LEFT JOIN ' . zxc_table( 'vouchers' ) . ' v ON v.code = o.code LEFT JOIN ' . zxc_table( 'plans' ) . ' p ON p.id = v.plan_id WHERE o.tenant_id = %d AND o.seen_at >= %d ORDER BY o.first_seen DESC', $t->id, time() - 90 ) );
			zxc_render( zxc_view_dash_online( $t, $rows ) );
			break;
		case 'locations':
			if ( isset( $seg[1], $seg[2] ) && in_array( $seg[2], array( 'login.html', 'alogin.html' ), true ) ) {
				nocache_headers();
				header( 'Content-Type: text/html; charset=utf-8' );
				header( 'Content-Disposition: attachment; filename="' . $seg[2] . '"' );
				echo 'login.html' === $seg[2] ? zxc_login_html( $t ) : zxc_alogin_html( $t ); // phpcs:ignore WordPress.Security.EscapeOutput
				exit;
			}
			zxc_render( zxc_view_dash_locations( $t, zxc_routers( $t->id ) ) );
			break;
		case 'branding':
			zxc_render( zxc_view_dash_branding( $t ) );
			break;
		case 'payments':
			zxc_render( zxc_view_dash_payments( $t ) );
			break;
		case 'billing':
			if ( 'demo' === ( $seg[1] ?? '' ) ) {
				$s = zxc_subscription_row( $seg[2] ?? '' );
				if ( $s && (int) $s->tenant_id === (int) $t->id && 'pending' === $s->status ) {
					zxc_render( zxc_view_dash_billing_demo( $t, $s ) );
				}
				zxc_redirect( $base . '/billing' );
			}
			if ( 'return' === ( $seg[1] ?? '' ) ) {
				$s = zxc_subscription_row( zxc_get( 'tx_ref', 40 ) );
				if ( $s && (int) $s->tenant_id === (int) $t->id ) {
					$s = zxc_verify_subscription( $s->reference );
					zxc_flash( 'success' === $s->status ? 'Payment received. Your subscription is active.' : 'We have not received your payment yet. If you were charged, it will activate shortly.', 'success' === $s->status ? 'ok' : 'warn' );
				}
				zxc_redirect( $base . '/billing' );
			}
			$history = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'subscriptions' ) . ' WHERE tenant_id = %d ORDER BY id DESC LIMIT 12', $t->id ) );
			zxc_render( zxc_view_dash_billing( $t, $history ) );
			break;
		case 'account':
			zxc_render( zxc_view_dash_account( $t, wp_get_current_user() ) );
			break;
		case 'customers':
			$q    = zxc_get( 'q', 40 );
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT x.phone, COUNT(*) AS purchases, SUM(x.amount) AS spent, MAX(x.paid_at) AS last_paid, MIN(x.paid_at) AS first_paid,
					SUBSTRING_INDEX(GROUP_CONCAT(p.name ORDER BY x.paid_at DESC SEPARATOR "|"), "|", 1) AS last_plan
					FROM ' . zxc_table( 'transactions' ) . ' x JOIN ' . zxc_table( 'plans' ) . " p ON p.id = x.plan_id
					WHERE x.tenant_id = %d AND x.status = 'success' AND x.phone LIKE %s GROUP BY x.phone ORDER BY last_paid DESC LIMIT 300",
					$t->id, $like
				)
			);
			$wmap = array();
			foreach ( zxc_customer_wallets( $t->id, '', 1000 ) as $w ) {
				$wmap[ $w->phone ] = (int) $w->balance;
			}
			zxc_render( zxc_view_dash_customers( $t, $rows, $q, $wmap ) );
			break;
		case 'transfers':
			$view = in_array( zxc_get( 'view', 10 ), array( 'pending', 'unsent', 'history' ), true ) ? zxc_get( 'view', 10 ) : 'pending';
			zxc_render( zxc_view_dash_transfers( $t, zxc_manual_orders( $t->id, $view ), $view ) );
			break;
		case 'docs':
			zxc_render( zxc_view_dash_docs( $t, '1' === zxc_get( 'welcome', 2 ) ) );
			break;
		case 'cwallets':
			$q = zxc_get( 'q', 40 );
			zxc_render( zxc_view_dash_cwallets( $t, zxc_customer_wallets( $t->id, $q ), zxc_customer_wallet_totals( $t->id ), $q ) );
			break;
		case 'reports':
		case 'analytics':
			zxc_render( zxc_view_dash_analytics( $t, zxc_analytics( $t, (int) zxc_get( 'range', 3 ) ) ) );
			break;
		case 'wallet':
			if ( 'demo' === ( $seg[1] ?? '' ) ) {
				$w = zxc_wallet_row( $seg[2] ?? '' );
				if ( $w && (int) $w->tenant_id === (int) $t->id && 'pending' === $w->status && 'demo' === $w->gateway ) {
					zxc_render( zxc_view_dash_wallet_demo( $t, $w ) );
				}
				zxc_redirect( $base . '/wallet' );
			}
			if ( 'return' === ( $seg[1] ?? '' ) ) {
				$w = zxc_wallet_row( zxc_get( 'tx_ref', 40 ) );
				if ( $w && (int) $w->tenant_id === (int) $t->id ) {
					$w = zxc_wallet_verify( $w->reference );
					zxc_flash( 'success' === $w->status ? zxc_money( $w->amount ) . ' added to your wallet.' : ( 'failed' === $w->status ? 'Your top-up did not go through.' : 'We are confirming your payment. Your balance updates shortly.' ), 'success' === $w->status ? 'ok' : 'warn' );
				}
				zxc_redirect( $base . '/wallet' );
			}
			zxc_render( zxc_view_dash_wallet( $t, zxc_wallet_balance( $t->id ), zxc_wallet_history( $t->id ) ) );
			break;
		case 'support':
			$msgs = zxc_messages( $t->id );
			zxc_mark_read( $t->id );
			zxc_render( zxc_view_dash_support( $t, $msgs ) );
			break;
		default:
			zxc_redirect( $base );
	}
}

/** Handle every dashboard form; always redirects. */
function zxc_dashboard_post( $t, $seg ) {
	global $wpdb;
	$base   = home_url( '/dashboard' );
	$action = zxc_post( 'action', 40 );
	switch ( $action ) {
		case 'plan_save':
			$mult = array( 'minutes' => 1, 'hours' => 60, 'days' => 1440 );
			$unit = zxc_post( 'duration_unit', 10 );
			$data = array(
				'name'          => zxc_post( 'name', 80 ),
				'description'   => zxc_post( 'description', 160 ),
				'price'         => absint( zxc_post( 'price', 9 ) ),
				'duration_min'  => max( 1, absint( zxc_post( 'duration_value', 6 ) ) * ( $mult[ $unit ] ?? 1 ) ),
				'data_mb'       => absint( zxc_post( 'data_mb', 9 ) ),
				'download_mbps' => max( 0, (float) zxc_post( 'download_mbps', 8 ) ),
				'upload_mbps'   => max( 0, (float) zxc_post( 'upload_mbps', 8 ) ),
				'devices'       => min( 10, max( 1, absint( zxc_post( 'devices', 2 ) ) ) ),
				'popular'       => empty( $_POST['popular'] ) ? 0 : 1, // phpcs:ignore WordPress.Security.NonceVerification
				'active'        => empty( $_POST['active'] ) ? 0 : 1, // phpcs:ignore WordPress.Security.NonceVerification
				'sort_order'    => (int) zxc_post( 'sort_order', 5 ),
			);
			if ( '' === $data['name'] ) {
				zxc_flash( 'Plan name is required.', 'bad' );
				zxc_redirect( wp_get_referer() ? wp_get_referer() : $base . '/plans' );
			}
			$id = absint( zxc_post( 'plan_id', 12 ) );
			if ( $id && zxc_plan( $t->id, $id ) ) {
				$wpdb->update( zxc_table( 'plans' ), $data, array( 'id' => $id, 'tenant_id' => $t->id ) );
				zxc_flash( 'Plan “' . $data['name'] . '” saved.' );
			} else {
				$wpdb->insert( zxc_table( 'plans' ), $data + array( 'tenant_id' => $t->id, 'created' => time() ) );
				zxc_flash( 'Plan “' . $data['name'] . '” created.' );
			}
			zxc_redirect( $base . '/plans' );
			break;
		case 'plan_toggle':
			$wpdb->query( $wpdb->prepare( 'UPDATE ' . zxc_table( 'plans' ) . ' SET active = 1 - active WHERE id = %d AND tenant_id = %d', absint( zxc_post( 'plan_id', 12 ) ), $t->id ) );
			zxc_redirect( $base . '/plans' );
			break;
		case 'vouchers_generate':
			$plan = zxc_plan( $t->id, absint( zxc_post( 'plan_id', 12 ) ) );
			$qty  = min( 500, max( 1, absint( zxc_post( 'quantity', 4 ) ) ) );
			$qty  = min( $qty, zxc_voucher_allowance( $t ) );
			if ( ! $plan || $qty < 1 ) {
				zxc_flash( $plan ? 'You have reached this month’s voucher limit on your plan. Upgrade for unlimited vouchers.' : 'Choose a plan.', 'bad' );
				zxc_redirect( $base . '/vouchers' );
			}
			$batch = wp_date( 'dm', null, new DateTimeZone( 'Africa/Lagos' ) ) . '-' . strtoupper( bin2hex( random_bytes( 2 ) ) );
			for ( $i = 0; $i < $qty; $i++ ) {
				zxc_create_voucher( $t->id, $plan->id, 'batch', $batch );
			}
			zxc_flash( $qty . ' “' . $plan->name . '” vouchers generated in batch ' . $batch . '. They reach your router within a minute.' );
			zxc_redirect( $base . '/vouchers?q=' . rawurlencode( $batch ) );
			break;
		case 'voucher_toggle':
			$v = zxc_voucher_by_id( absint( zxc_post( 'voucher_id', 12 ) ) );
			if ( $v && (int) $v->tenant_id === (int) $t->id ) {
				if ( 'disabled' === $v->status ) {
					$wpdb->update( zxc_table( 'vouchers' ), array( 'status' => $v->activated_at ? 'active' : 'unused', 'ended_at' => -time() ), array( 'id' => $v->id ) );
					zxc_flash( 'Voucher ' . zxc_format_code( $v->code ) . ' enabled.' );
				} else {
					$wpdb->update( zxc_table( 'vouchers' ), array( 'status' => 'disabled', 'ended_at' => time() ), array( 'id' => $v->id ) );
					zxc_flash( 'Voucher ' . zxc_format_code( $v->code ) . ' disabled. It is removed from your router within seconds.' );
				}
			}
			zxc_redirect( wp_get_referer() ? wp_get_referer() : $base . '/vouchers' );
			break;
		case 'location_add':
			if ( ! zxc_can_add_router( $t ) ) {
				zxc_flash( 'Your plan’s location limit is reached. Upgrade to add more routers.', 'bad' );
			} else {
				zxc_create_router( $t->id, zxc_post( 'name', 80 ) ? zxc_post( 'name', 80 ) : 'New location' );
				zxc_flash( 'Location added. Paste its setup script into that router.' );
			}
			zxc_redirect( $base . '/locations' );
			break;
		case 'location_delete':
			$rid = absint( zxc_post( 'router_id', 12 ) );
			if ( count( zxc_routers( $t->id ) ) > 1 ) {
				$wpdb->delete( zxc_table( 'routers' ), array( 'id' => $rid, 'tenant_id' => $t->id ) );
				zxc_flash( 'Location removed.' );
			} else {
				zxc_flash( 'You need at least one location.', 'bad' );
			}
			zxc_redirect( $base . '/locations' );
			break;
		case 'branding':
			$values = array(
				'name'             => zxc_post( 'name', 80 ) ? zxc_post( 'name', 80 ) : $t->name,
				'tagline'          => zxc_post( 'tagline', 160 ),
				'brand_color'      => zxc_is_hex( zxc_post( 'brand_color', 7 ) ) ? zxc_post( 'brand_color', 7 ) : $t->brand_color,
				'support_phone'    => zxc_post( 'support_phone', 30 ),
				'support_whatsapp' => zxc_post( 'support_whatsapp', 30 ),
				'location'         => zxc_post( 'location', 120 ),
				'hotspot_dns'      => preg_replace( '/[^a-z0-9.-]/', '', strtolower( zxc_post( 'hotspot_dns', 120 ) ) ) ? preg_replace( '/[^a-z0-9.-]/', '', strtolower( zxc_post( 'hotspot_dns', 120 ) ) ) : 'login.wifi',
			);
			$logo = zxc_handle_logo_upload( 'logo_file' );
			if ( is_wp_error( $logo ) ) {
				zxc_flash( $logo->get_error_message(), 'bad' );
				zxc_redirect( $base . '/branding' );
			}
			if ( $logo ) {
				$values['logo_url'] = $logo;
			}
			if ( ! empty( $_POST['remove_logo'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$values['logo_url'] = '';
			}
			zxc_update_tenant( $t->id, $values );
			zxc_flash( 'Branding saved. Your portal is updated.' );
			zxc_redirect( $base . '/branding' );
			break;
		case 'transfer_review':
			$ref = zxc_post( 'ref', 40 );
			$o   = zxc_order( $ref, $t->id );
			if ( ! $o || 'manual' !== $o->gateway || 'pending' !== $o->status ) {
				zxc_flash( 'That payment was already handled.', 'warn' );
				zxc_redirect( $base . '/transfers' );
			}
			if ( 'approve' === zxc_post( 'decision', 10 ) ) {
				if ( zxc_voucher_allowance( $t ) < 1 ) {
					zxc_flash( 'You have reached your monthly voucher limit. Upgrade your plan to approve more payments.', 'bad' );
					zxc_redirect( $base . '/transfers' );
				}
				$o = zxc_fulfill_order( $o->reference, 'bank transfer' );
				zxc_flash( 'Approved. ' . $o->phone . ' is now connected with voucher ' . $o->voucher_code . '.' );
			} else {
				zxc_decline_order( $o->reference );
				zxc_flash( 'Payment ' . $o->reference . ' declined.', 'warn' );
			}
			zxc_redirect( $base . '/transfers' );
			break;
		case 'payments':
			$values = array(
				'flw_public'    => zxc_post( 'flw_public', 200 ),
				'flw_hash'      => zxc_post( 'flw_hash', 200 ),
				'demo_payments' => empty( $_POST['demo_payments'] ) ? 0 : 1, // phpcs:ignore WordPress.Security.NonceVerification
			);
			$secret = zxc_post( 'flw_secret', 200 );
			if ( '' !== $secret ) {
				$values['flw_secret'] = $secret;
			}
			if ( ( $values['flw_public'] && ! preg_match( '/^FLWPUBK(_TEST)?-/', $values['flw_public'] ) ) || ( $secret && ! preg_match( '/^FLWSECK(_TEST)?-/', $secret ) ) ) {
				zxc_flash( 'Flutterwave keys should start with FLWPUBK- (public) and FLWSECK- (secret).', 'bad' );
				zxc_redirect( $base . '/payments' );
			}
			$values['manual_payments'] = empty( $_POST['manual_payments'] ) ? 0 : 1; // phpcs:ignore WordPress.Security.NonceVerification
			$values['bank_name']       = zxc_post( 'bank_name', 80 );
			$values['bank_account']    = preg_replace( '/\D/', '', zxc_post( 'bank_account', 20 ) );
			$values['bank_holder']     = zxc_post( 'bank_holder', 120 );
			$wa                        = zxc_post( 'support_whatsapp', 30 );
			if ( '' !== $wa ) {
				$values['support_whatsapp'] = $wa;
			}
			if ( $values['manual_payments'] && ( '' === $values['bank_name'] || ! preg_match( '/^\d{10}$/', $values['bank_account'] ) || '' === $values['bank_holder'] ) ) {
				zxc_flash( 'To accept bank transfers, enter your bank name, 10-digit account number and account name.', 'bad' );
				zxc_redirect( $base . '/payments' );
			}
			if ( $values['manual_payments'] && '' === preg_replace( '/\D/', '', $wa ? $wa : $t->support_whatsapp . $t->support_phone ) ) {
				zxc_flash( 'Add your WhatsApp number so customers can send you their proof of payment.', 'bad' );
				zxc_redirect( $base . '/payments' );
			}
			zxc_update_tenant( $t->id, $values );
			zxc_flash( 'Payment settings saved.' );
			zxc_redirect( $base . '/payments' );
			break;
		case 'subscribe':
			$tier = zxc_post( 'tier', 20 );
			$tiers = zxc_tiers();
			if ( ! isset( $tiers[ $tier ] ) ) {
				zxc_redirect( $base . '/billing' );
			}
			$months = in_array( (int) zxc_post( 'months', 2 ), array( 1, 3, 6, 12 ), true ) ? (int) zxc_post( 'months', 2 ) : 1;
			$s      = zxc_create_subscription( $t, $tier, $months );
			if ( 'demo' === $s->gateway ) {
				zxc_redirect( $base . '/billing/demo/' . $s->reference );
			}
			$user = wp_get_current_user();
			$link = zxc_flw_checkout(
				zxc_setting( 'flw_secret' ),
				array(
					'reference' => $s->reference,
					'amount'    => $s->amount,
					'email'     => $user->user_email,
					'name'      => $user->display_name,
					'redirect'  => $base . '/billing/return',
					'title'     => zxc_setting( 'platform_name' ) . ' ' . $tiers[ $tier ]['name'] . ' plan',
					'meta'      => array( 'hotspot' => $t->slug, 'tier' => $tier ),
				)
			);
			if ( is_wp_error( $link ) ) {
				zxc_flash( 'We could not reach Flutterwave. Please try again shortly.', 'bad' );
				zxc_redirect( $base . '/billing' );
			}
			wp_redirect( $link, 303 ); // phpcs:ignore WordPress.Security.SafeRedirect
			exit;
		case 'subscribe_demo':
			$s = zxc_subscription_row( zxc_post( 'reference', 40 ) );
			if ( $s && (int) $s->tenant_id === (int) $t->id && 'demo' === $s->gateway && ! zxc_billing_ready() && '1' === zxc_setting( 'demo_billing' ) ) {
				zxc_activate_subscription( $s->reference );
				zxc_flash( 'Demo payment complete. Your subscription is active.' );
			}
			zxc_redirect( $base . '/billing' );
			break;
		case 'wallet_deposit':
			$amount = absint( zxc_post( 'amount', 9 ) );
			if ( $amount < 500 || $amount > 2000000 ) {
				zxc_flash( 'Enter an amount between ₦500 and ₦2,000,000.', 'bad' );
				zxc_redirect( $base . '/wallet' );
			}
			if ( ! zxc_billing_ready() && '1' !== zxc_setting( 'demo_billing' ) ) {
				zxc_flash( 'Wallet top-ups are not available yet. Please contact support.', 'bad' );
				zxc_redirect( $base . '/wallet' );
			}
			$w = zxc_wallet_create_deposit( $t, $amount );
			if ( 'demo' === $w->gateway ) {
				zxc_redirect( $base . '/wallet/demo/' . $w->reference );
			}
			$u    = wp_get_current_user();
			$link = zxc_flw_checkout( zxc_setting( 'flw_secret' ), array( 'reference' => $w->reference, 'amount' => $amount, 'email' => $u->user_email, 'name' => $u->display_name, 'redirect' => $base . '/wallet/return', 'title' => zxc_setting( 'platform_name' ) . ' wallet top-up', 'meta' => array( 'hotspot' => $t->slug, 'type' => 'wallet' ) ) );
			if ( is_wp_error( $link ) ) {
				zxc_flash( 'We could not reach Flutterwave. Please try again shortly.', 'bad' );
				zxc_redirect( $base . '/wallet' );
			}
			wp_redirect( $link, 303 ); // phpcs:ignore WordPress.Security.SafeRedirect
			exit;
		case 'cwallet_adjust':
			$phone  = zxc_normalize_phone( zxc_post( 'phone', 20 ) );
			$amount = (int) zxc_post( 'amount', 10 );
			$type   = 'debit' === zxc_post( 'type', 10 ) ? 'debit' : 'credit';
			$note   = zxc_post( 'note', 150 );
			$cust   = zxc_valid_phone( $phone ) ? zxc_customer_by_phone( $t->id, $phone ) : null;
			if ( ! $cust ) {
				zxc_flash( 'No wallet found for that phone number. The customer must open a wallet on your portal first.', 'bad' );
			} elseif ( $amount < 1 || $amount > 500000 ) {
				zxc_flash( 'Enter an amount between ₦1 and ₦500,000.', 'bad' );
			} elseif ( zxc_customer_entry( $t->id, $cust->id, $type, 'debit' === $type ? -$amount : $amount, $note ? $note : ( 'debit' === $type ? 'Adjustment by ' : 'Cash top-up at ' ) . $t->name, 'owner' ) ) {
				zxc_flash( ( 'debit' === $type ? 'Removed ' : 'Added ' ) . zxc_money( $amount ) . ( 'debit' === $type ? ' from ' : ' to ' ) . zxc_mask_phone( $phone ) . '’s wallet.' );
			} else {
				zxc_flash( 'That customer does not have enough balance for this debit.', 'bad' );
			}
			zxc_redirect( $base . '/cwallets' );
			break;
		case 'wallet_demo':
			$w = zxc_wallet_row( zxc_post( 'reference', 40 ) );
			if ( $w && (int) $w->tenant_id === (int) $t->id && 'demo' === $w->gateway && ! zxc_billing_ready() ) {
				zxc_wallet_complete( $w->reference, null, 'demo' );
				zxc_flash( zxc_money( $w->amount ) . ' added to your wallet (test payment).' );
			}
			zxc_redirect( $base . '/wallet' );
			break;
		case 'subscribe_wallet':
			$tier   = zxc_post( 'tier', 20 );
			$months = in_array( (int) zxc_post( 'months', 2 ), array( 1, 3, 6, 12 ), true ) ? (int) zxc_post( 'months', 2 ) : 1;
			if ( isset( zxc_tiers()[ $tier ] ) ) {
				if ( zxc_wallet_pay_subscription( $t, $tier, $months ) ) {
					zxc_flash( 'Paid from your wallet. Your ' . zxc_tiers()[ $tier ]['name'] . ' plan is active.' );
				} else {
					zxc_flash( 'Your wallet balance is too low. Top up first.', 'bad' );
					zxc_redirect( $base . '/wallet' );
				}
			}
			zxc_redirect( $base . '/billing' );
			break;
		case 'support_send':
			$body = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( strlen( $body ) < 5 ) {
				zxc_flash( 'Please write your message.', 'bad' );
			} else {
				$u = wp_get_current_user();
				zxc_add_message( array( 'tenant_id' => null, 'from_type' => 'owner', 'name' => $u->display_name . ' · ' . $t->name, 'email' => $u->user_email, 'subject' => zxc_post( 'subject', 150 ) ? zxc_post( 'subject', 150 ) : 'Support request', 'body' => $body ) );
				zxc_notify_platform( 'New support request from ' . $t->name, $body, $u->user_email );
				zxc_flash( 'Message sent. The ' . zxc_setting( 'platform_name' ) . ' team will reply by email.' );
			}
			zxc_redirect( $base . '/support' );
			break;
		case 'account':
			$user = wp_get_current_user();
			$name = zxc_post( 'display_name', 60 );
			if ( $name ) {
				wp_update_user( array( 'ID' => $user->ID, 'display_name' => $name, 'first_name' => $name ) );
			}
			$current = isset( $_POST['current'] ) ? (string) wp_unslash( $_POST['current'] ) : ''; // phpcs:ignore
			$new     = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
			if ( '' !== $new ) {
				if ( ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
					zxc_flash( 'Your current password is incorrect.', 'bad' );
					zxc_redirect( $base . '/account' );
				}
				if ( strlen( $new ) < 8 || zxc_post( 'confirm', 200 ) !== $new ) {
					zxc_flash( 'New passwords must match and be at least 8 characters.', 'bad' );
					zxc_redirect( $base . '/account' );
				}
				wp_set_password( $new, $user->ID );
				wp_set_auth_cookie( $user->ID, true, is_ssl() );
			}
			zxc_flash( 'Account updated.' );
			zxc_redirect( $base . '/account' );
			break;
	}
	zxc_redirect( $base );
}

/** Numbers for the dashboard overview. */
function zxc_dashboard_stats( $t, $range ) {
	global $wpdb;
	$tx    = zxc_table( 'transactions' );
	$days  = in_array( (int) $range, array( 7, 14, 30 ), true ) ? (int) $range : 14;
	$today = zxc_lagos_day_start();
	$sum   = function ( $since, $until = null ) use ( $wpdb, $tx, $t ) {
		$until = $until ? $until : PHP_INT_MAX;
		return $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d AND paid_at < %d", $t->id, $since, $until ) ); // phpcs:ignore
	};
	$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT paid_at, amount FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d", $t->id, $today - ( $days - 1 ) * DAY_IN_SECONDS ) ); // phpcs:ignore
	$series = array();
	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$start = $today - $i * DAY_IN_SECONDS;
		$total = 0;
		$count = 0;
		foreach ( $rows as $r ) {
			if ( $r->paid_at >= $start && $r->paid_at < $start + DAY_IN_SECONDS ) {
				$total += (int) $r->amount;
				$count++;
			}
		}
		$series[] = array( 'label' => wp_date( 'D j M', $start + 3600, new DateTimeZone( 'UTC' ) ), 'total' => $total, 'count' => $count );
	}
	return array(
		'today'     => $sum( $today ),
		'yesterday' => $sum( $today - DAY_IN_SECONDS, $today ),
		'month'     => $sum( zxc_lagos_month_start() ),
		'series'    => $series,
		'range'     => $days,
		'online'    => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'online' ) . ' WHERE tenant_id = %d AND seen_at >= %d', $t->id, time() - 90 ) ),
		'active'    => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'vouchers' ) . " WHERE tenant_id = %d AND status = 'active'", $t->id ) ),
		'unused'    => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'vouchers' ) . " WHERE tenant_id = %d AND status = 'unused'", $t->id ) ),
		'top'       => $wpdb->get_results( $wpdb->prepare( "SELECT p.name, SUM(x.amount) AS total, COUNT(*) AS cnt FROM $tx x JOIN " . zxc_table( 'plans' ) . " p ON p.id = x.plan_id WHERE x.tenant_id = %d AND x.status = 'success' AND x.paid_at >= %d GROUP BY p.id, p.name ORDER BY total DESC LIMIT 5", $t->id, time() - 30 * DAY_IN_SECONDS ) ), // phpcs:ignore
		'recent'    => $wpdb->get_results( $wpdb->prepare( "SELECT x.*, p.name AS plan_name, v.code AS voucher_code FROM $tx x JOIN " . zxc_table( 'plans' ) . ' p ON p.id = x.plan_id LEFT JOIN ' . zxc_table( 'vouchers' ) . ' v ON v.id = x.voucher_id WHERE x.tenant_id = %d ORDER BY x.created DESC LIMIT 6', $t->id ) ), // phpcs:ignore
		'routers'   => zxc_routers( $t->id ),
	);
}

function zxc_export_sales_csv( $rows ) {
	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="zaxconnect-sales.csv"' );
	$out = fopen( 'php://output', 'w' );
	fputcsv( $out, array( 'reference', 'plan', 'amount', 'phone', 'email', 'status', 'channel', 'gateway', 'voucher', 'created', 'paid' ) );
	$cell = function ( $v ) {
		$v = (string) $v;
		return preg_match( '/^[=+\-@]/', $v ) ? "'" . $v : $v;
	};
	foreach ( $rows as $r ) {
		fputcsv( $out, array_map( $cell, array( $r->reference, $r->plan_name, $r->amount, $r->phone, $r->email, $r->status, $r->channel, $r->gateway, $r->voucher_code ? zxc_format_code( $r->voucher_code ) : '', gmdate( 'c', (int) $r->created ), $r->paid_at ? gmdate( 'c', (int) $r->paid_at ) : '' ) ) );
	}
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

/* -------------------------------------------------------------------------
 * Reports & notifications
 * ---------------------------------------------------------------------- */

/** Detailed analytics for one business over the last $days days (with the previous period for comparison). */
function zxc_analytics( $t, $days = 30 ) {
	global $wpdb;
	$tx    = zxc_table( 'transactions' );
	$vt    = zxc_table( 'vouchers' );
	$pt    = zxc_table( 'plans' );
	$ot    = zxc_table( 'online' );
	$days  = in_array( (int) $days, array( 7, 30, 90 ), true ) ? (int) $days : 30;
	$today = zxc_lagos_day_start();
	$from  = $today - ( $days - 1 ) * DAY_IN_SECONDS;
	$prev  = $from - $days * DAY_IN_SECONDS;
	$tid   = (int) $t->id;

	$period = function ( $a, $b ) use ( $wpdb, $tx, $tid ) {
		return $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt, COUNT(DISTINCT phone) AS customers FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d AND paid_at < %d", $tid, $a, $b ) ); // phpcs:ignore
	};
	$cur = $period( $from, PHP_INT_MAX );
	$old = $period( $prev, $from );

	// Checkout conversion (orders created in the period).
	$orders = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS started, SUM(status = 'success') AS paid, SUM(status = 'failed') AS failed FROM $tx WHERE tenant_id = %d AND created >= %d", $tid, $from ) ); // phpcs:ignore
	$orders_old = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS started, SUM(status = 'success') AS paid FROM $tx WHERE tenant_id = %d AND created >= %d AND created < %d", $tid, $prev, $from ) ); // phpcs:ignore

	// Daily series.
	$rows   = $wpdb->get_results( $wpdb->prepare( "SELECT paid_at, amount, phone FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d", $tid, $from ) ); // phpcs:ignore
	$daily  = array();
	$dow    = array_fill( 0, 7, 0 );
	$hours  = array_fill( 0, 24, 0 );
	for ( $i = 0; $i < $days; $i++ ) {
		$daily[ $i ] = array( 'label' => wp_date( 'j M', $from + $i * DAY_IN_SECONDS + 3600, new DateTimeZone( 'UTC' ) ), 'total' => 0, 'count' => 0 );
	}
	foreach ( $rows as $r ) {
		$i = (int) floor( ( $r->paid_at - $from ) / DAY_IN_SECONDS );
		if ( isset( $daily[ $i ] ) ) {
			$daily[ $i ]['total'] += (int) $r->amount;
			$daily[ $i ]['count']++;
		}
		$local                       = (int) $r->paid_at + 3600;
		$dow[ (int) gmdate( 'N', $local ) - 1 ] += (int) $r->amount;
		$hours[ (int) gmdate( 'G', $local ) ]++;
	}
	$daily = array_values( $daily );

	// New vs returning customers (returning = bought before this period).
	$returning = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT c.phone) FROM $tx c WHERE c.tenant_id = %d AND c.status = 'success' AND c.paid_at >= %d AND EXISTS (SELECT 1 FROM $tx o WHERE o.tenant_id = c.tenant_id AND o.phone = c.phone AND o.status = 'success' AND o.paid_at < %d)", $tid, $from, $from ) ); // phpcs:ignore
	$repeat    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM (SELECT phone FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d GROUP BY phone HAVING COUNT(*) > 1) r", $tid, $from ) ); // phpcs:ignore
	$top_customers = $wpdb->get_results( $wpdb->prepare( "SELECT phone, COUNT(*) AS cnt, SUM(amount) AS total, MAX(paid_at) AS last_paid FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d GROUP BY phone ORDER BY total DESC LIMIT 5", $tid, $from ) ); // phpcs:ignore

	// Plans, channels.
	$plans = $wpdb->get_results( $wpdb->prepare( "SELECT p.name, p.price, COUNT(x.id) AS cnt, COALESCE(SUM(x.amount),0) AS total FROM $pt p LEFT JOIN $tx x ON x.plan_id = p.id AND x.status = 'success' AND x.paid_at >= %d WHERE p.tenant_id = %d GROUP BY p.id, p.name, p.price ORDER BY total DESC, p.sort_order", $from, $tid ) ); // phpcs:ignore
	$chan  = $wpdb->get_results( $wpdb->prepare( "SELECT COALESCE(NULLIF(channel,''),'other') AS channel, COUNT(*) AS cnt, SUM(amount) AS total FROM $tx WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d GROUP BY channel ORDER BY total DESC", $tid, $from ) ); // phpcs:ignore

	// Voucher funnel & data.
	$vouch = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS issued, SUM(activated_at > 0) AS activated, SUM(status = 'expired') AS finished, SUM(source = 'online') AS online, SUM(source = 'batch') AS printed, COALESCE(SUM(bytes_used),0) AS bytes, COALESCE(AVG(NULLIF(bytes_used,0)),0) AS avg_bytes FROM $vt WHERE tenant_id = %d AND created >= %d", $tid, $from ) ); // phpcs:ignore
	$data_served = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(bytes_used),0) FROM $vt WHERE tenant_id = %d AND last_seen >= %d", $tid, $from ) ); // phpcs:ignore

	// Sessions from router check-ins.
	$sess = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS sessions, COUNT(DISTINCT mac) AS devices, COALESCE(AVG(seen_at - first_seen),0) AS avg_secs FROM $ot WHERE tenant_id = %d AND first_seen >= %d", $tid, $from ) ); // phpcs:ignore
	$locations = $wpdb->get_results( $wpdb->prepare( "SELECT r.name, r.last_seen, COUNT(o.id) AS sessions, COUNT(DISTINCT o.mac) AS devices, COALESCE(SUM(o.bytes),0) AS bytes FROM " . zxc_table( 'routers' ) . " r LEFT JOIN $ot o ON o.router_id = r.id AND o.first_seen >= %d WHERE r.tenant_id = %d GROUP BY r.id, r.name, r.last_seen ORDER BY sessions DESC", $from, $tid ) ); // phpcs:ignore
	$online_now = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $ot WHERE tenant_id = %d AND seen_at >= %d", $tid, time() - 90 ) ); // phpcs:ignore

	$pct = function ( $a, $b ) {
		return $b > 0 ? round( ( $a - $b ) / $b * 100 ) : null;
	};
	$conv     = $orders->started ? round( $orders->paid / $orders->started * 100 ) : null;
	$conv_old = $orders_old->started ? round( $orders_old->paid / $orders_old->started * 100 ) : null;
	$peak_h   = array_search( max( $hours ), $hours, true );
	$day_names = array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' );

	return array(
		'days'        => $days,
		'revenue'     => (int) $cur->total,
		'revenue_chg' => $pct( $cur->total, $old->total ),
		'sales'       => (int) $cur->cnt,
		'sales_chg'   => $pct( $cur->cnt, $old->cnt ),
		'customers'   => (int) $cur->customers,
		'cust_chg'    => $pct( $cur->customers, $old->customers ),
		'avg'         => $cur->cnt ? (int) round( $cur->total / $cur->cnt ) : 0,
		'avg_chg'     => $pct( $cur->cnt ? $cur->total / $cur->cnt : 0, $old->cnt ? $old->total / $old->cnt : 0 ),
		'per_day'     => (int) round( $cur->total / $days ),
		'conversion'  => $conv,
		'conv_chg'    => ( null !== $conv && null !== $conv_old ) ? $conv - $conv_old : null,
		'orders'      => $orders,
		'returning'   => $returning,
		'new_cust'    => max( 0, (int) $cur->customers - $returning ),
		'repeat'      => $repeat,
		'top_customers' => $top_customers,
		'daily'       => $daily,
		'dow'         => array_map( function ( $v, $k ) use ( $day_names ) { return array( 'label' => $day_names[ $k ], 'total' => $v ); }, $dow, array_keys( $dow ) ),
		'hours'       => $hours,
		'peak_hour'   => max( $hours ) > 0 ? $peak_h : null,
		'best_day'    => max( $dow ) > 0 ? $day_names[ array_search( max( $dow ), $dow, true ) ] : null,
		'plans'       => $plans,
		'channels'    => $chan,
		'vouchers'    => $vouch,
		'data_served' => $data_served,
		'sessions'    => $sess,
		'locations'   => $locations,
		'online_now'  => $online_now,
	);
}

function zxc_notify_platform( $subject, $body, $reply_to = '' ) {
	$to = zxc_setting( 'support_email' ) ? zxc_setting( 'support_email' ) : get_option( 'admin_email' );
	if ( $to ) {
		wp_mail( $to, '[' . zxc_setting( 'platform_name' ) . '] ' . $subject, $body, $reply_to ? array( 'Reply-To: ' . $reply_to ) : array() );
	}
}

/* -------------------------------------------------------------------------
 * Public marketing pages
 * ---------------------------------------------------------------------- */

function zxc_ctl_page( $page ) {
	switch ( $page ) {
		case 'contact':
			$args = array();
			if ( zxc_is_post() ) {
				zxc_check_nonce( 'zxc_contact' );
				$name  = zxc_post( 'name', 120 );
				$email = sanitize_email( zxc_post( 'email', 190 ) );
				$body  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';
				$args['values'] = array( 'name' => $name, 'email' => $email, 'phone' => zxc_post( 'phone', 30 ), 'topic' => zxc_post( 'topic', 60 ), 'message' => $body );
				if ( ! empty( $_POST['website'] ) || zxc_throttled( 'contact', 5 ) ) { // honeypot + throttle
					$args['error'] = 'Please wait a few minutes before sending another message.';
				} elseif ( '' === $name || ! is_email( $email ) || strlen( $body ) < 10 ) {
					$args['error'] = 'Please enter your name, a valid email and a message of at least 10 characters.';
				} else {
					zxc_add_message( array( 'from_type' => 'visitor', 'name' => $name, 'email' => $email, 'phone' => zxc_post( 'phone', 30 ), 'subject' => zxc_post( 'topic', 60 ) ? zxc_post( 'topic', 60 ) : 'Website enquiry', 'body' => $body ) );
					zxc_notify_platform( 'New message from ' . $name, $body . "\n\n— " . $name . ' <' . $email . '>', $email );
					$args = array( 'sent' => true );
				}
			}
			zxc_render( zxc_view_contact( $args ) );
			break;
		case 'forgot':
			$args = array();
			if ( zxc_is_post() ) {
				zxc_check_nonce( 'zxc_forgot' );
				$email = sanitize_email( zxc_post( 'email', 190 ) );
				if ( ! zxc_throttled( 'forgot', 5 ) && is_email( $email ) && email_exists( $email ) ) {
					retrieve_password( $email );
				}
				$args['sent'] = true; // same answer whether or not the account exists
			}
			zxc_render( zxc_view_forgot( $args ) );
			break;
		default:
			$fn = 'zxc_view_page_' . $page;
			if ( function_exists( $fn ) ) {
				zxc_render( $fn() );
			}
			zxc_redirect( home_url( '/' ) );
	}
}

/* -------------------------------------------------------------------------
 * Platform admin (/zx-admin) — its own login, separate from wp-admin
 * ---------------------------------------------------------------------- */

function zxc_ctl_platform( $path ) {
	global $wpdb;
	$base = home_url( '/zx-admin' );
	$seg  = $path ? explode( '/', $path ) : array( '' );

	if ( 'login' === $seg[0] ) {
		if ( zxc_is_platform_admin() ) {
			zxc_redirect( $base );
		}
		$args = array();
		if ( zxc_is_post() ) {
			zxc_check_nonce( 'zxc_admin_login' );
			$email = zxc_post( 'email', 190 );
			$user  = zxc_throttled( 'adminlogin', 10, 900 ) ? new WP_Error( 'throttled' ) : wp_signon( array( 'user_login' => $email, 'user_password' => isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : '', 'remember' => true ), is_ssl() ); // phpcs:ignore
			if ( is_wp_error( $user ) || ! zxc_is_platform_admin( $user ) ) {
				if ( ! is_wp_error( $user ) ) {
					wp_logout();
				}
				$args = array( 'error' => 'Incorrect email or password, or this account is not a platform admin.', 'email' => $email );
			} else {
				zxc_redirect( $base );
			}
		}
		zxc_render( zxc_view_admin_login( $args ), empty( $args['error'] ) ? 200 : 401 );
	}
	if ( 'logout' === $seg[0] ) {
		wp_logout();
		zxc_redirect( $base . '/login' );
	}
	if ( ! zxc_is_platform_admin() ) {
		zxc_redirect( $base . '/login' );
	}
	// Open a business's owner dashboard (support mode), or return to your own.
	if ( 'view' === $seg[0] ) {
		check_admin_referer( 'zxc_view_tenant' );
		$vt = isset( $seg[1] ) ? zxc_tenant( (int) $seg[1] ) : null;
		$own = zxc_tenant_for_user( get_current_user_id() );
		if ( $vt && ( ! $own || (int) $own->id !== (int) $vt->id ) ) {
			update_user_meta( get_current_user_id(), 'zxc_view_tenant', (int) $vt->id );
			zxc_flash( 'You are viewing ' . $vt->name . '’s dashboard as an admin.' );
		} else {
			delete_user_meta( get_current_user_id(), 'zxc_view_tenant' );
		}
		zxc_redirect( home_url( '/dashboard' ) );
	}
	if ( 'exit-view' === $seg[0] ) {
		check_admin_referer( 'zxc_view_tenant' );
		$vt = zxc_admin_view_tenant();
		delete_user_meta( get_current_user_id(), 'zxc_view_tenant' );
		zxc_redirect( $vt ? $base . '/businesses/' . $vt->id : $base );
	}

	if ( zxc_is_post() ) {
		zxc_check_nonce( 'zxc_platform' );
		zxc_platform_post( $seg );
	}

	switch ( $seg[0] ) {
		case '':
			zxc_render( zxc_view_admin_overview( zxc_platform_stats() ) );
			break;
		case 'businesses':
			if ( ! empty( $seg[1] ) ) {
				$t = zxc_tenant( (int) $seg[1] );
				if ( ! $t ) {
					zxc_redirect( $base . '/businesses' );
				}
				$owner   = get_userdata( $t->owner_id );
				$stats   = zxc_dashboard_stats( $t, 14 );
				$subs    = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'subscriptions' ) . ' WHERE tenant_id = %d ORDER BY id DESC LIMIT 10', $t->id ) );
				zxc_render( zxc_view_admin_business( $t, $owner, $stats, $subs ) );
			}
			$q    = zxc_get( 'q', 60 );
			$like = '%' . $wpdb->esc_like( $q ) . '%';
			zxc_render( zxc_view_admin_businesses( zxc_platform_tenants( $q ? $wpdb->prepare( '(t.name LIKE %s OR t.slug LIKE %s OR u.user_email LIKE %s)', $like, $like, $like ) : '1=1' ), $q ) );
			break;
		case 'subscriptions':
			$rows = $wpdb->get_results( 'SELECT s.*, t.name AS tenant_name, t.slug FROM ' . zxc_table( 'subscriptions' ) . ' s JOIN ' . zxc_table( 'tenants' ) . ' t ON t.id = s.tenant_id ORDER BY s.id DESC LIMIT 300' );
			zxc_render( zxc_view_admin_subscriptions( $rows ) );
			break;
		case 'inbox':
			$msgs = zxc_messages( null, 200 );
			zxc_mark_read( null );
			zxc_render( zxc_view_admin_inbox( $msgs ) );
			break;
		case 'team':
			$team = get_users( array( 'role__in' => array( 'zx_platform', 'administrator' ), 'orderby' => 'registered' ) );
			zxc_render( zxc_view_admin_team( $team ) );
			break;
		case 'settings':
			zxc_render( zxc_view_admin_settings() );
			break;
		case 'account':
			zxc_render( zxc_view_admin_account( wp_get_current_user() ) );
			break;
		default:
			zxc_redirect( $base );
	}
}

function zxc_platform_post( $seg ) {
	$base   = home_url( '/zx-admin' );
	$action = zxc_post( 'action', 40 );
	$back   = wp_get_referer() ? wp_get_referer() : $base;
	if ( 'settings' === $action ) {
		$vals = array(
			'platform_name'    => zxc_post( 'platform_name', 60 ) ? zxc_post( 'platform_name', 60 ) : 'ZaxConnect',
			'support_email'    => sanitize_email( zxc_post( 'support_email', 190 ) ),
			'support_phone'    => zxc_post( 'support_phone', 30 ),
			'support_whatsapp' => zxc_post( 'support_whatsapp', 30 ),
			'address'          => zxc_post( 'address', 160 ),
			'flw_public'       => zxc_post( 'flw_public', 200 ),
			'flw_hash'         => zxc_post( 'flw_hash', 200 ),
			'demo_billing'     => empty( $_POST['demo_billing'] ) ? '0' : '1', // phpcs:ignore
			'takeover_home'    => empty( $_POST['takeover_home'] ) ? '0' : '1', // phpcs:ignore
			'price_starter'    => (string) absint( zxc_post( 'price_starter', 9 ) ),
			'price_business'   => (string) absint( zxc_post( 'price_business', 9 ) ),
			'price_enterprise' => (string) absint( zxc_post( 'price_enterprise', 9 ) ),
			'trial_days'       => (string) max( 1, absint( zxc_post( 'trial_days', 3 ) ) ),
		);
		if ( zxc_post( 'flw_secret', 200 ) ) {
			$vals['flw_secret'] = zxc_post( 'flw_secret', 200 );
		}
		zxc_update_settings( $vals );
		zxc_flash( 'Platform settings saved.' );
		zxc_redirect( $base . '/settings' );
	}
	if ( 'team_add' === $action ) {
		$email = sanitize_email( zxc_post( 'email', 190 ) );
		$name  = zxc_post( 'name', 80 );
		$pass  = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
		if ( ! is_email( $email ) || email_exists( $email ) || strlen( $pass ) < 10 || '' === $name ) {
			zxc_flash( 'Enter a name, an email not already in use, and a password of at least 10 characters.', 'bad' );
		} else {
			$login = sanitize_user( strtolower( strstr( $email, '@', true ) ), true ) . wp_rand( 10, 99 );
			$id    = wp_insert_user( array( 'user_login' => $login, 'user_email' => $email, 'user_pass' => $pass, 'display_name' => $name, 'role' => 'zx_platform' ) );
			zxc_flash( is_wp_error( $id ) ? $id->get_error_message() : $name . ' can now sign in at ' . $base . '/login.', is_wp_error( $id ) ? 'bad' : 'ok' );
		}
		zxc_redirect( $base . '/team' );
	}
	if ( 'team_remove' === $action ) {
		$uid  = absint( zxc_post( 'user_id', 12 ) );
		$user = get_userdata( $uid );
		if ( $user && in_array( 'zx_platform', (array) $user->roles, true ) && get_current_user_id() !== $uid ) {
			$user->remove_role( 'zx_platform' );
			zxc_flash( $user->display_name . ' no longer has admin access.' );
		}
		zxc_redirect( $base . '/team' );
	}
	if ( 'account' === $action ) {
		$user = wp_get_current_user();
		if ( zxc_post( 'display_name', 60 ) ) {
			wp_update_user( array( 'ID' => $user->ID, 'display_name' => zxc_post( 'display_name', 60 ) ) );
		}
		$new = isset( $_POST['password'] ) ? (string) wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore
		if ( '' !== $new ) {
			$cur = isset( $_POST['current'] ) ? (string) wp_unslash( $_POST['current'] ) : ''; // phpcs:ignore
			if ( ! wp_check_password( $cur, $user->user_pass, $user->ID ) || strlen( $new ) < 10 || zxc_post( 'confirm', 200 ) !== $new ) {
				zxc_flash( 'Check your current password. New passwords must match and be at least 10 characters.', 'bad' );
				zxc_redirect( $base . '/account' );
			}
			wp_set_password( $new, $user->ID );
			wp_set_auth_cookie( $user->ID, true, is_ssl() );
		}
		zxc_flash( 'Account updated.' );
		zxc_redirect( $base . '/account' );
	}
	$t = zxc_tenant( absint( zxc_post( 'tenant_id', 12 ) ) );
	if ( $t ) {
		if ( 'suspend' === $action ) {
			zxc_update_tenant( $t->id, array( 'status' => 'suspended' ) );
			zxc_flash( $t->name . ' suspended. Its portal is offline.' );
		} elseif ( 'activate' === $action ) {
			zxc_update_tenant( $t->id, array( 'status' => 'active' ) );
			zxc_flash( $t->name . ' re-activated.' );
		} elseif ( 'extend' === $action ) {
			zxc_update_tenant( $t->id, array( 'sub_ends' => max( time(), (int) $t->sub_ends ) + 30 * DAY_IN_SECONDS ) );
			zxc_flash( $t->name . ' extended by 30 days.' );
		} elseif ( 'wallet_credit' === $action ) {
			$amt = (int) zxc_post( 'amount', 10 );
			if ( 0 !== $amt && zxc_wallet_entry( $t->id, $amt > 0 ? 'credit' : 'debit', $amt, zxc_post( 'note', 150 ) ? zxc_post( 'note', 150 ) : 'Adjustment by ' . zxc_setting( 'platform_name' ) ) ) {
				zxc_flash( ( $amt > 0 ? 'Credited ' : 'Debited ' ) . zxc_money( abs( $amt ) ) . ( $amt > 0 ? ' to ' : ' from ' ) . $t->name . '’s wallet.' );
			} else {
				zxc_flash( 'Could not adjust the wallet (check the amount and balance).', 'bad' );
			}
		} elseif ( 'tier' === $action && isset( zxc_tiers()[ zxc_post( 'tier', 20 ) ] ) ) {
			zxc_update_tenant( $t->id, array( 'tier' => zxc_post( 'tier', 20 ) ) );
			zxc_flash( $t->name . ' moved to the ' . zxc_tiers()[ zxc_post( 'tier', 20 ) ]['name'] . ' plan.' );
		}
	}
	zxc_redirect( $back );
}

function zxc_platform_tenants( $where = '1=1' ) {
	global $wpdb;
	return $wpdb->get_results(
		'SELECT t.*, u.user_email, u.display_name,
		(SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'transactions' ) . " x WHERE x.tenant_id = t.id AND x.status = 'success') AS revenue,
		(SELECT COUNT(*) FROM " . zxc_table( 'transactions' ) . " x WHERE x.tenant_id = t.id AND x.status = 'success') AS sales,
		(SELECT MAX(last_seen) FROM " . zxc_table( 'routers' ) . " r WHERE r.tenant_id = t.id) AS router_seen
		FROM " . zxc_table( 'tenants' ) . " t LEFT JOIN {$wpdb->users} u ON u.ID = t.owner_id WHERE $where ORDER BY t.id DESC LIMIT 500" // phpcs:ignore
	);
}

function zxc_platform_stats() {
	global $wpdb;
	$tenants = zxc_platform_tenants();
	$tiers   = zxc_tiers();
	$s       = array( 'tenants' => $tenants, 'mrr' => 0, 'paying' => 0, 'trials' => 0, 'expired' => 0, 'online_routers' => 0 );
	foreach ( $tenants as $t ) {
		$sub = zxc_subscription( $t );
		if ( 'active' === $sub['state'] ) {
			$s['paying']++;
			$s['mrr'] += $tiers[ $t->tier ]['price'] ?? 0;
		} elseif ( 'trial' === $sub['state'] ) {
			$s['trials']++;
		} else {
			$s['expired']++;
		}
		if ( $t->router_seen >= time() - 120 ) {
			$s['online_routers']++;
		}
	}
	$s['gmv']      = (int) $wpdb->get_var( 'SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'transactions' ) . " WHERE status = 'success'" );
	$s['subs_rev'] = (int) $wpdb->get_var( 'SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'subscriptions' ) . " WHERE status = 'success'" );
	$s['signups']  = array();
	for ( $i = 11; $i >= 0; $i-- ) {
		$start         = zxc_lagos_day_start() - $i * 7 * DAY_IN_SECONDS - 6 * DAY_IN_SECONDS;
		$s['signups'][] = array( 'label' => 'Week of ' . gmdate( 'j M', $start + 3600 ), 'count' => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'tenants' ) . ' WHERE created >= %d AND created < %d', $start, $start + 7 * DAY_IN_SECONDS ) ) );
	}
	$s['unread']   = zxc_unread_count( null );
	$s['recent_subs'] = $wpdb->get_results( 'SELECT s.*, t.name AS tenant_name FROM ' . zxc_table( 'subscriptions' ) . ' s JOIN ' . zxc_table( 'tenants' ) . " t ON t.id = s.tenant_id WHERE s.status = 'success' ORDER BY s.paid_at DESC LIMIT 6" );
	return $s;
}

/** Customer wallet on the hotspot portal: /h/{slug}/wallet[/demo/REF|/return|/logout] */
function zxc_portal_wallet( $t, array $seg, array $ctx, array $sub ) {
	$base = zxc_portal_url( $t, 'wallet' );
	$c    = zxc_customer_session( $t );
	$demo = ! zxc_gateway_ready( $t );
	$open = $sub['active'] && ( ! $demo || $t->demo_payments );
	$sub2 = $seg[1] ?? '';

	if ( 'logout' === $sub2 ) {
		zxc_customer_clear_session( $t );
		zxc_redirect( $base );
	}
	if ( 'return' === $sub2 ) { // Flutterwave redirect after a top-up
		$w = zxc_cledger_row( sanitize_text_field( zxc_get( 'tx_ref', 40 ) ) );
		if ( $w && (int) $w->tenant_id === (int) $t->id ) {
			$w = zxc_customer_verify_deposit( $w->reference );
			if ( 'pending' === $w->status && 'cancelled' === zxc_get( 'status', 20 ) ) {
				global $wpdb;
				$wpdb->update( zxc_table( 'cledger' ), array( 'status' => 'failed' ), array( 'id' => $w->id, 'status' => 'pending' ) );
				$w->status = 'failed';
			}
			zxc_redirect( $base . '?topup=' . ( 'success' === $w->status ? 'ok' : ( 'failed' === $w->status ? 'failed' : 'pending' ) ) );
		}
		zxc_redirect( $base );
	}
	if ( 'demo' === $sub2 ) {
		$w = zxc_cledger_row( sanitize_text_field( $seg[2] ?? '' ) );
		if ( ! $c || ! $w || (int) $w->customer_id !== (int) $c->id || 'demo' !== $w->gateway || 'pending' !== $w->status ) {
			zxc_redirect( $base );
		}
		if ( zxc_is_post() && $demo ) {
			if ( 'success' === zxc_post( 'result', 10 ) ) {
				zxc_customer_complete_deposit( $w->reference, null, 'demo' );
				zxc_redirect( $base . '?topup=ok' );
			}
			global $wpdb;
			$wpdb->update( zxc_table( 'cledger' ), array( 'status' => 'failed' ), array( 'id' => $w->id ) );
			zxc_redirect( $base . '?topup=failed' );
		}
		zxc_render( zxc_view_portal_wallet_demo( $t, $w ) );
	}

	$args = array( 'ctx' => $ctx, 'open' => $open, 'demo' => $demo );
	if ( zxc_is_post() ) {
		$action = zxc_post( 'action', 20 );
		if ( 'login' === $action ) {
			$phone = zxc_normalize_phone( zxc_post( 'phone', 20 ) );
			list( $nc, $err ) = zxc_customer_auth( $t, $phone, zxc_post( 'pin', 10 ), zxc_post( 'name', 120 ) );
			if ( $nc ) {
				zxc_customer_set_session( $t, $nc );
				zxc_redirect( $base . ( zxc_get( 'buy', 10 ) ? '?buy=' . (int) zxc_get( 'buy', 10 ) : '' ) );
			}
			$args['error']  = $err;
			$args['values'] = array( 'phone' => zxc_post( 'phone', 20 ), 'name' => zxc_post( 'name', 120 ) );
			zxc_render( zxc_view_portal_wallet_login( $t, $args ), 400 );
		}
		if ( ! $c ) {
			zxc_redirect( $base );
		}
		if ( 'topup' === $action ) {
			$amount = (int) zxc_post( 'amount', 10 );
			if ( ! $open ) {
				$args['error'] = 'Online top-up is not available at this hotspot right now.';
			} elseif ( $amount < 100 || $amount > 200000 ) {
				$args['error'] = 'Enter an amount between ₦100 and ₦200,000.';
			} else {
				$w = zxc_customer_create_deposit( $t, $c, $amount );
				if ( 'demo' === $w->gateway ) {
					zxc_redirect( $base . '/demo/' . $w->reference );
				}
				$link = zxc_flw_checkout(
					$t->flw_secret,
					array(
						'reference' => $w->reference,
						'amount'    => $amount,
						'email'     => $c->phone . '@customers.' . wp_parse_url( home_url(), PHP_URL_HOST ),
						'phone'     => $c->phone,
						'redirect'  => $base . '/return',
						'title'     => $t->name . ' · Wallet top-up',
						'logo'      => $t->logo_url,
						'meta'      => array( 'hotspot' => $t->slug, 'type' => 'wallet' ),
					)
				);
				if ( ! is_wp_error( $link ) ) {
					wp_redirect( $link, 303 ); // phpcs:ignore WordPress.Security.SafeRedirect -- Flutterwave checkout host
					exit;
				}
				$args['error'] = 'We could not reach the payment provider. Please try again in a moment.';
			}
		} elseif ( 'buy' === $action ) {
			$plan = zxc_plan( $t->id, (int) zxc_post( 'plan', 10 ) );
			if ( ! $sub['active'] ) {
				$args['error'] = 'Online sales are paused at this hotspot. Please ask the attendant.';
			} elseif ( ! $plan || ! $plan->active ) {
				$args['error'] = 'That plan is no longer available.';
			} else {
				$r = zxc_customer_buy_plan( $t, $c, $plan, $ctx );
				if ( is_object( $r ) && 'success' === $r->status ) {
					zxc_redirect( zxc_portal_url( $t, 'success/' . $r->reference ) );
				}
				$args['error'] = is_string( $r ) ? $r : 'Something went wrong. Your balance has not been charged.';
			}
		}
	}
	if ( ! $c ) {
		zxc_render( zxc_view_portal_wallet_login( $t, $args ) );
	}
	$args['customer'] = $c;
	$args['balance']  = zxc_customer_balance( $c->id );
	$args['history']  = zxc_customer_history( $c->id );
	$args['plans']    = zxc_plans( $t->id, true );
	$args['buy']      = (int) zxc_get( 'buy', 10 );
	$args['topup']    = zxc_get( 'topup', 10 );
	zxc_render( zxc_view_portal_wallet( $t, $args ) );
}
