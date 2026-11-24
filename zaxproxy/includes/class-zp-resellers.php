<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resellers run a branded store on top of the platform.
 *
 * - A reseller is a normal customer with a row in zp_resellers (brand, store slug, markup).
 * - Opening a store costs a one-off activation fee that covers the first 30 days; after that a
 *   monthly fee is taken from the reseller's wallet automatically. An unpaid store is `expired`:
 *   it stops selling and loses the reseller rate until the fee is paid.
 * - Their customers are WordPress users tagged with user meta `zp_reseller_id`.
 * - A customer's wallet is store credit issued by the reseller (the reseller is paid directly
 *   and approves the deposit). When the customer buys, the customer's wallet is charged the
 *   reseller's price and the reseller's own platform wallet is charged the platform price.
 *   No funds in the reseller's wallet means the customer cannot buy.
 */
class ZP_Resellers {

	const RESERVED = array( 'admin', 'panel', 'login', 'register', 'zaxproxy', 'zax', 'support', 'api', 'store', 'www', 'pricing', 'help' );

	public static function get( $user_id ) {
		global $wpdb;
		static $cache = array();
		$user_id = (int) $user_id;
		if ( ! $user_id ) {
			return null;
		}
		if ( ! array_key_exists( $user_id, $cache ) || ! empty( $GLOBALS['zp_resellers_dirty'] ) ) {
			$GLOBALS['zp_resellers_dirty'] = false;
			$cache[ $user_id ]             = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'resellers' ) . ' WHERE user_id = %d', $user_id ) );
		}
		return $cache[ $user_id ];
	}

	public static function by_slug( $slug ) {
		global $wpdb;
		$slug = sanitize_title( $slug );
		return $slug ? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'resellers' ) . ' WHERE slug = %s', $slug ) ) : null;
	}

	/** Site administrators run a store without paying the activation, monthly or domain fees. */
	public static function is_free( $user_id ) {
		return user_can( (int) $user_id, 'manage_options' );
	}

	/** True for an account with an active, paid-up store: it buys at the reseller rate. */
	public static function is_reseller( $user_id ) {
		return self::is_live( self::get( $user_id ) );
	}

	/** A store that may sell right now: not suspended and its subscription is paid. Renews it first when due. */
	public static function is_live( $r ) {
		if ( ! $r ) {
			return false;
		}
		$r = self::bill( $r );
		return 'active' === $r->status && $r->paid_until && strtotime( $r->paid_until . ' UTC' ) > time();
	}

	/**
	 * Take the monthly fee when a store's paid period has ended.
	 * Success extends the period by 30 days; an empty wallet marks the store expired.
	 * Returns the (possibly updated) store row.
	 */
	public static function bill( $r, $force = false ) {
		global $wpdb;
		if ( ! $r || 'suspended' === $r->status || ! $r->paid_until || strtotime( $r->paid_until . ' UTC' ) > time() ) {
			return $r; // suspended, never activated, or still paid up
		}
		$lock = 'zp_rbill_' . (int) $r->user_id;
		if ( ! $force && 'expired' === $r->status && get_transient( $lock ) ) {
			return $r; // already tried a moment ago
		}
		set_transient( $lock, 1, 10 * MINUTE_IN_SECONDS );
		$fee = self::is_free( $r->user_id ) ? 0 : (float) zp_get( 'reseller_monthly', 15000 );
		// Claim the renewal first so two requests cannot both charge for the same month.
		$until   = gmdate( 'Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS );
		$claimed = $wpdb->query( $wpdb->prepare( 'UPDATE ' . zp_table( 'resellers' ) . ' SET paid_until = %s WHERE user_id = %d AND paid_until = %s', $until, $r->user_id, $r->paid_until ) );
		if ( ! $claimed ) {
			$GLOBALS['zp_resellers_dirty'] = true;
			return self::get( $r->user_id );
		}
		if ( $fee <= 0 || ZP_Wallet::debit( $r->user_id, $fee, 'reseller_fee', 'store', 'Reseller store: monthly fee' ) ) {
			$wpdb->update( zp_table( 'resellers' ), array( 'status' => 'active' ), array( 'user_id' => $r->user_id ) );
		} else {
			$wpdb->update( zp_table( 'resellers' ), array( 'status' => 'expired', 'paid_until' => $r->paid_until ), array( 'user_id' => $r->user_id ) );
		}
		$GLOBALS['zp_resellers_dirty'] = true;
		return self::get( $r->user_id );
	}

	/** Cron: renew every store whose period has ended. */
	public static function bill_due() {
		global $wpdb;
		$due = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'resellers' ) . " WHERE status IN ('active','expired') AND paid_until IS NOT NULL AND paid_until <= %s LIMIT 200", zp_now() ) );
		foreach ( $due as $r ) {
			self::bill( $r );
		}
	}

	/**
	 * Pay the one-off activation fee and start the first 30 days.
	 *
	 * @return true|WP_Error
	 */
	public static function activate( $user_id ) {
		global $wpdb;
		$r = self::get( $user_id );
		if ( ! $r ) {
			return new WP_Error( 'zp_input', 'Create your store first.' );
		}
		if ( $r->paid_until ) {
			return true;
		}
		$fee = self::is_free( $user_id ) ? 0 : (float) zp_get( 'reseller_fee', 45000 );
		if ( $fee > 0 && ! ZP_Wallet::debit( $user_id, $fee, 'reseller_fee', 'store', 'Reseller store: activation' ) ) {
			return new WP_Error( 'zp_funds', sprintf( 'You need %s in your wallet to activate your reseller account. Please add funds.', zp_money( $fee ) ) );
		}
		$wpdb->update( zp_table( 'resellers' ), array( 'status' => 'active', 'paid_until' => gmdate( 'Y-m-d H:i:s', time() + 30 * DAY_IN_SECONDS ) ), array( 'user_id' => $user_id ) );
		$GLOBALS['zp_resellers_dirty'] = true;
		return true;
	}

	/** The reseller a user buys from, or null for direct platform customers. */
	public static function of_user( $user_id ) {
		$rid = (int) get_user_meta( (int) $user_id, 'zp_reseller_id', true );
		if ( ! $rid || $rid === (int) $user_id ) {
			return null;
		}
		return self::get( $rid );
	}

	public static function is_customer_of( $user_id, $reseller_id ) {
		return (int) get_user_meta( (int) $user_id, 'zp_reseller_id', true ) === (int) $reseller_id && (int) $user_id !== (int) $reseller_id;
	}

	/** Customer price for a platform price: platform price plus the reseller's markup, rounded up. */
	public static function price( $platform_price, $reseller ) {
		return (float) ceil( round( (float) $platform_price * ( 1 + max( 0, (float) $reseller->markup ) / 100 ), 4 ) );
	}

	public static function store_url( $reseller, $page = '' ) {
		if ( $reseller->domain && 'active' === $reseller->domain_status ) {
			return 'https://' . $reseller->domain . '/' . ( $page ? $page . '/' : '' );
		}
		$ds = zp_domain_store();
		if ( $ds && (int) $ds->user_id === (int) $reseller->user_id ) {
			return home_url( '/' . ( $page ? $page . '/' : '' ) );
		}
		return zp_route_url( 's', $reseller->slug . ( $page ? '/' . $page : '' ) );
	}

	/* ---------- Store design ---------- */

	/** Brand colour of a store, or '' for the default look. */
	public static function color( $r ) {
		return $r && ! empty( $r->color ) && preg_match( '/^#[0-9a-f]{6}$/', $r->color ) ? $r->color : '';
	}

	/**
	 * Handle the logo field of the settings form: upload a new image or remove the current one.
	 * Only raster images are accepted (an SVG can carry scripts).
	 *
	 * @return true|WP_Error
	 */
	public static function save_logo( $user_id ) {
		global $wpdb;
		$r = self::get( $user_id );
		if ( ! $r ) {
			return true;
		}
		$remove = ! empty( $_POST['remove_logo'] ); // phpcs:ignore
		$file   = isset( $_FILES['logo'] ) && ! empty( $_FILES['logo']['name'] ) ? $_FILES['logo'] : null; // phpcs:ignore
		if ( ! $file && ! $remove ) {
			return true;
		}
		$new_url = '';
		if ( $file ) {
			if ( ! empty( $file['error'] ) || $file['size'] > 1024 * 1024 ) {
				return new WP_Error( 'zp_input', 'Your logo must be an image smaller than 1 MB.' );
			}
			require_once ABSPATH . 'wp-admin/includes/file.php';
			$mimes = array( 'png' => 'image/png', 'jpg|jpeg' => 'image/jpeg', 'webp' => 'image/webp' );
			$check = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'], $mimes );
			if ( empty( $check['ext'] ) || empty( $check['type'] ) || ! @getimagesize( $file['tmp_name'] ) ) { // phpcs:ignore
				return new WP_Error( 'zp_input', 'Upload your logo as a PNG, JPG or WebP image.' );
			}
			$file['name'] = 'store-' . (int) $user_id . '-' . wp_generate_password( 8, false ) . '.' . $check['ext'];
			$up           = wp_handle_upload( $file, array( 'test_form' => false, 'mimes' => $mimes ) );
			if ( isset( $up['error'] ) || empty( $up['url'] ) ) {
				return new WP_Error( 'zp_input', 'The logo could not be uploaded. Please try another image.' );
			}
			$new_url = esc_url_raw( $up['url'] );
		}
		if ( $r->logo_url ) {
			$uploads = wp_get_upload_dir();
			$old     = str_replace( $uploads['baseurl'], $uploads['basedir'], $r->logo_url );
			if ( 0 === strpos( $old, $uploads['basedir'] ) && false !== strpos( basename( $old ), 'store-' . (int) $user_id . '-' ) && is_file( $old ) ) {
				wp_delete_file( $old );
			}
		}
		$wpdb->update( zp_table( 'resellers' ), array( 'logo_url' => $new_url ), array( 'user_id' => $user_id ) );
		$GLOBALS['zp_resellers_dirty'] = true;
		return true;
	}

	/** A store's own policy text, or a sensible default written in the store's name. */
	public static function policy( $r, $doc ) {
		$own = isset( $r->$doc ) ? trim( (string) $r->$doc ) : '';
		if ( '' !== $own ) {
			return $own;
		}
		$b = $r->brand;
		if ( 'refund' === $doc ) {
			return "We want you to be happy with every proxy you buy from {$b}.\n\nOrders that cannot be delivered\nIf an order fails or cannot be delivered, the full amount is returned to your wallet automatically.\n\nProxies that do not work\nIf a proxy does not connect at all, contact us within 24 hours of delivery with the proxy IP or order number. We will replace it or refund it to your wallet.\n\nWhen a refund is not available\nRefunds are not given when a proxy works but a particular website blocks it, when you no longer need the proxy, when you ordered the wrong type, country or period, or for unused time on a rental.\n\nWallet balance\nRefunds are credited to your wallet. To ask for unused wallet funds to be returned, contact us.\n\nHow to ask\nOpen a support ticket from your dashboard with your order number and a short description of the problem.";
		}
		return "{$b} respects your privacy. This policy explains what we collect and why.\n\nWhat we collect\nYour name, email address and password (stored encrypted), your orders and wallet history, the payment details you send us, and any messages you send to support.\n\nHow we use it\nTo run your account, deliver the proxies you order, confirm your payments, answer your questions and prevent abuse.\n\nWho we share it with\nWe do not sell your data. Order details are passed to our infrastructure provider so that your proxies can be delivered. We may share information with authorities when the law requires it.\n\nHow long we keep it\nFor as long as your account is open, and afterwards for as long as records must be kept by law.\n\nYour rights\nYou can ask to see, correct or delete your personal data at any time by contacting us.\n\nCookies\nWe use only the cookies needed to keep you signed in and keep your account secure.";
	}

	/* ---------- Branded .cloud domains ---------- */

	/** Normalise "My-Brand" or "mybrand.cloud" to "mybrand.cloud"; WP_Error when it is not a usable name. */
	public static function clean_domain( $raw ) {
		$name = strtolower( trim( (string) $raw ) );
		$name = preg_replace( '#^https?://#', '', $name );
		$name = preg_replace( '/^www\./', '', $name );
		$name = preg_replace( '/\.cloud\/?$/', '', $name );
		if ( ! preg_match( '/^[a-z0-9]([a-z0-9\-]{1,38}[a-z0-9])$/', $name ) || false !== strpos( $name, '--' ) ) {
			return new WP_Error( 'zp_input', 'Enter a domain name of 3 to 40 letters, numbers or dashes. It will end in .cloud.' );
		}
		return $name . '.cloud';
	}

	/** The active store that owns the host of the current request, if it is not the main site. */
	public static function match_request_domain() {
		global $wpdb;
		if ( 'cli' === PHP_SAPI || empty( $_SERVER['HTTP_HOST'] ) ) {
			return null;
		}
		$host = preg_replace( '/^www\./', '', strtolower( preg_replace( '/:\d+$/', '', sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) ) );
		if ( ! preg_match( '/^[a-z0-9\-]+\.cloud$/', $host ) ) {
			return null;
		}
		$main = preg_replace( '/^www\./', '', strtolower( (string) wp_parse_url( get_option( 'home' ), PHP_URL_HOST ) ) );
		if ( $host === $main ) {
			return null;
		}
		$table = zp_table( 'resellers' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return null;
		}
		$wpdb->suppress_errors( true ); // the domain columns do not exist until the upgrade has run
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table WHERE domain = %s AND domain_status = 'active'", $host ) ); // phpcs:ignore
		$wpdb->suppress_errors( false );
		return $row ? $row : null;
	}

	/**
	 * Request a branded domain: charges the domain fee and queues it for connection by staff.
	 *
	 * @return true|WP_Error
	 */
	public static function request_domain( $user_id, $raw ) {
		global $wpdb;
		$r = self::get( $user_id );
		if ( ! $r || ! $r->paid_until ) {
			return new WP_Error( 'zp_input', 'Unlock your reseller account first.' );
		}
		if ( $r->domain && in_array( $r->domain_status, array( 'pending', 'active' ), true ) ) {
			return new WP_Error( 'zp_input', 'You already have a branded domain on this store.' );
		}
		$domain = self::clean_domain( $raw );
		if ( is_wp_error( $domain ) ) {
			return $domain;
		}
		$main = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
		$used = $wpdb->get_var( $wpdb->prepare( 'SELECT user_id FROM ' . zp_table( 'resellers' ) . " WHERE domain = %s AND domain_status IN ('pending','active') AND user_id <> %d", $domain, $user_id ) );
		if ( $used || $domain === $main ) {
			return new WP_Error( 'zp_input', 'That domain is already taken. Please choose another name.' );
		}
		$fee = self::is_free( $user_id ) ? 0 : (float) zp_get( 'reseller_domain_fee', 12000 );
		if ( $fee > 0 && ! ZP_Wallet::debit( $user_id, $fee, 'domain_fee', 'store', 'Branded domain: ' . $domain ) ) {
			return new WP_Error( 'zp_funds', sprintf( 'A branded domain costs %s. Please add funds to your wallet.', zp_money( $fee ) ) );
		}
		$wpdb->update( zp_table( 'resellers' ), array( 'domain' => $domain, 'domain_status' => 'pending', 'domain_fee' => $fee ), array( 'user_id' => $user_id ) );
		$GLOBALS['zp_resellers_dirty'] = true;
		$to = zp_get( 'support_email' );
		if ( is_email( $to ) ) {
			wp_mail( $to, '[' . zp_get( 'brand' ) . '] Branded domain requested: ' . $domain, "Store: {$r->brand}\nDomain: {$domain}\n\nRegister the domain, point it at this website, then mark it connected:\n" . zp_admin_url( 'resellers' ) );
		}
		return true;
	}

	/** Staff: the domain is registered and pointed at the site. */
	public static function connect_domain( $user_id ) {
		global $wpdb;
		$wpdb->update( zp_table( 'resellers' ), array( 'domain_status' => 'active' ), array( 'user_id' => (int) $user_id, 'domain_status' => 'pending' ) );
		$GLOBALS['zp_resellers_dirty'] = true;
	}

	/** Staff: the domain cannot be provided. Refunds the fee once. */
	public static function reject_domain( $user_id ) {
		global $wpdb;
		$r = self::get( $user_id );
		if ( ! $r || 'pending' !== $r->domain_status ) {
			return false;
		}
		$done = $wpdb->query( $wpdb->prepare( 'UPDATE ' . zp_table( 'resellers' ) . " SET domain_status = 'rejected' WHERE user_id = %d AND domain_status = 'pending'", $user_id ) );
		if ( $done && $r->domain_fee > 0 ) {
			ZP_Wallet::credit( $user_id, $r->domain_fee, 'refund', 'store', 'Refund: branded domain ' . $r->domain . ' not available' );
		}
		$GLOBALS['zp_resellers_dirty'] = true;
		return (bool) $done;
	}

	/**
	 * Create or update the store of $user_id.
	 *
	 * @return true|WP_Error
	 */
	public static function save( $user_id, array $in ) {
		global $wpdb;
		$existing = self::get( $user_id );
		if ( self::of_user( $user_id ) ) {
			return new WP_Error( 'zp_input', 'This account cannot open a reseller store.' );
		}
		$brand  = trim( sanitize_text_field( isset( $in['brand'] ) ? $in['brand'] : '' ) );
		$slug   = sanitize_title( isset( $in['slug'] ) ? $in['slug'] : '' );
		$markup = round( (float) ( isset( $in['markup'] ) ? $in['markup'] : 0 ), 2 );
		if ( mb_strlen( $brand ) < 2 || mb_strlen( $brand ) > 40 ) {
			return new WP_Error( 'zp_input', 'Enter a brand name between 2 and 40 characters.' );
		}
		if ( ! preg_match( '/^[a-z0-9][a-z0-9\-]{1,28}[a-z0-9]$/', $slug ) || in_array( $slug, self::RESERVED, true ) ) {
			return new WP_Error( 'zp_input', 'Choose a store link of 3 to 30 letters, numbers or dashes.' );
		}
		$owner = self::by_slug( $slug );
		if ( $owner && (int) $owner->user_id !== (int) $user_id ) {
			return new WP_Error( 'zp_input', 'That store link is already taken. Try another one.' );
		}
		if ( $markup < 0 || $markup > 500 ) {
			return new WP_Error( 'zp_input', 'Your markup must be between 0% and 500%.' );
		}
		$data = array(
			'brand'               => $brand,
			'slug'                => $slug,
			'markup'              => $markup,
			'bank_name'           => mb_substr( sanitize_text_field( isset( $in['bank_name'] ) ? $in['bank_name'] : '' ), 0, 80 ),
			'bank_account_name'   => mb_substr( sanitize_text_field( isset( $in['bank_account_name'] ) ? $in['bank_account_name'] : '' ), 0, 120 ),
			'bank_account_number' => preg_replace( '/[^0-9A-Za-z \-]/', '', isset( $in['bank_account_number'] ) ? (string) $in['bank_account_number'] : '' ),
			'telegram'            => mb_substr( sanitize_text_field( isset( $in['telegram'] ) ? $in['telegram'] : '' ), 0, 80 ),
			'updated_at'          => zp_now(),
		);
		// Store design and policies: only touched when the settings form sends them.
		if ( isset( $in['color'] ) ) {
			$color         = strtolower( trim( (string) $in['color'] ) );
			$data['color'] = preg_match( '/^#[0-9a-f]{6}$/', $color ) ? $color : '';
		}
		if ( isset( $in['headline'] ) ) {
			$data['headline'] = mb_substr( sanitize_text_field( $in['headline'] ), 0, 160 );
		}
		if ( isset( $in['tagline'] ) ) {
			$data['tagline'] = mb_substr( sanitize_textarea_field( $in['tagline'] ), 0, 400 );
		}
		foreach ( array( 'privacy', 'refund' ) as $doc ) {
			if ( isset( $in[ $doc ] ) ) {
				$data[ $doc ] = mb_substr( sanitize_textarea_field( $in[ $doc ] ), 0, 20000 );
			}
		}
		if ( $existing ) {
			$wpdb->update( zp_table( 'resellers' ), $data, array( 'user_id' => $user_id ) );
		} else {
			$data['user_id']    = $user_id;
			$data['status']     = 'pending'; // becomes active once the activation fee is paid
			$data['created_at'] = zp_now();
			$wpdb->insert( zp_table( 'resellers' ), $data );
		}
		$GLOBALS['zp_resellers_dirty'] = true;
		return true;
	}

	public static function set_status( $user_id, $status ) {
		global $wpdb;
		$r = self::get( $user_id );
		if ( ! $r ) {
			return;
		}
		$new = 'suspended' === $status ? 'suspended' : ( $r->paid_until ? 'active' : 'pending' );
		$wpdb->update( zp_table( 'resellers' ), array( 'status' => $new ), array( 'user_id' => (int) $user_id ) );
		$GLOBALS['zp_resellers_dirty'] = true;
	}

	public static function customer_ids( $reseller_id ) {
		global $wpdb;
		return array_map(
			'intval',
			$wpdb->get_col( $wpdb->prepare( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'zp_reseller_id' AND meta_value = %s AND user_id <> %d", (string) (int) $reseller_id, $reseller_id ) )
		);
	}

	public static function stats( $reseller_id ) {
		global $wpdb;
		$o    = zp_table( 'orders' );
		$paid = "refunded = 0 AND status IN ('completed','processing','review')";
		$row  = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) n, COALESCE(SUM(amount),0) sales, COALESCE(SUM(reseller_cost),0) cost FROM $o WHERE reseller_id = %d AND $paid", $reseller_id ) ); // phpcs:ignore
		return array(
			'customers' => count( self::customer_ids( $reseller_id ) ),
			'orders'    => (int) $row->n,
			'sales'     => (float) $row->sales,
			'cost'      => (float) $row->cost,
			'profit'    => (float) $row->sales - (float) $row->cost,
			'pending'   => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'deposits' ) . " WHERE reseller_id = %d AND status = 'pending'", $reseller_id ) ),
			'tickets'   => (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'tickets' ) . " WHERE reseller_id = %d AND status = 'open'", $reseller_id ) ),
		);
	}

	/* ---------- Dashboard actions (called from the user panel POST handler) ---------- */

	public static function handle( $do, $user ) {
		global $wpdb;
		$back = zp_url( 'reseller', array( 'tab' => sanitize_key( zp_post( 'tab', 'overview' ) ) ) );

		if ( 'reseller_save' === $do ) {
			$before = self::get( $user->ID );
			$new    = ! $before || ! $before->paid_until;
			$free   = self::is_free( $user->ID );
			$fee    = $free ? 0 : (float) zp_get( 'reseller_fee', 45000 );
			$domain = $new ? trim( zp_post( 'domain' ) ) : '';
			if ( '' !== $domain ) {
				$clean = self::clean_domain( $domain );
				if ( is_wp_error( $clean ) ) {
					zp_flash( $clean->get_error_message(), 'error' );
					zp_redirect( zp_url( 'reseller' ) );
				}
				$fee += $free ? 0 : (float) zp_get( 'reseller_domain_fee', 12000 ); // activation and the domain are paid together
			}
			if ( $new && ZP_Wallet::balance( $user->ID ) < $fee ) {
				zp_flash( sprintf( 'Activation costs %s and your wallet has %s. Add funds, then come back to unlock your reseller account.', zp_money( $fee ), zp_money( ZP_Wallet::balance( $user->ID ) ) ), 'error' );
				zp_redirect( zp_url( 'wallet' ) );
			}
			$res = self::save( $user->ID, wp_unslash( $_POST ) ); // phpcs:ignore
			if ( ! is_wp_error( $res ) && ! $new ) {
				$res = self::save_logo( $user->ID );
			}
			if ( ! is_wp_error( $res ) && $new ) {
				$res = self::activate( $user->ID );
			}
			if ( ! is_wp_error( $res ) && '' !== $domain ) {
				$dres = self::request_domain( $user->ID, $domain );
				if ( is_wp_error( $dres ) ) {
					zp_flash( 'Reseller account unlocked, but the domain was not ordered: ' . $dres->get_error_message(), 'error' );
					zp_redirect( zp_url( 'reseller', array( 'tab' => 'settings' ) ) );
				}
			}
			if ( is_wp_error( $res ) ) {
				zp_flash( $res->get_error_message(), 'error' );
				zp_redirect( $new ? zp_url( 'reseller' ) : $back );
			}
			zp_flash( $new ? 'Reseller account unlocked. Your store is live: share your link and start selling.' : 'Store settings saved.' );
			zp_redirect( $new ? zp_url( 'reseller' ) : $back );
		}
		if ( 'reseller_domain' === $do ) {
			$res = self::request_domain( $user->ID, zp_post( 'domain' ) );
			if ( is_wp_error( $res ) ) {
				zp_flash( $res->get_error_message(), 'error' );
				zp_redirect( 'zp_funds' === $res->get_error_code() ? zp_url( 'wallet' ) : zp_url( 'reseller', array( 'tab' => 'settings' ) ) );
			}
			zp_flash( 'Domain ordered. We are setting it up and will connect it to your store, usually within 24 hours.' );
			zp_redirect( zp_url( 'reseller', array( 'tab' => 'settings' ) ) );
		}
		if ( 'reseller_renew' === $do ) {
			$r = self::get( $user->ID );
			$r = $r ? self::bill( $r, true ) : null;
			if ( $r && 'active' === $r->status ) {
				zp_flash( 'Monthly fee paid. Your store is active again.' );
			} else {
				zp_flash( sprintf( 'Your wallet needs %s to renew. Please add funds.', zp_money( zp_get( 'reseller_monthly', 15000 ) ) ), 'error' );
				zp_redirect( zp_url( 'wallet' ) );
			}
			zp_redirect( zp_url( 'reseller' ) );
		}

		$me = self::get( $user->ID );
		if ( ! $me || ! $me->paid_until ) {
			zp_redirect( zp_url( 'reseller' ) );
		}

		switch ( $do ) {
			case 'reseller_deposit_approve':
			case 'reseller_deposit_reject':
				$d = ZP_Wallet::deposit( (int) zp_post( 'id' ) );
				if ( ! $d || (int) $d->reseller_id !== $user->ID ) {
					zp_flash( 'Deposit not found.', 'error' );
					break;
				}
				if ( 'reseller_deposit_approve' === $do ) {
					$ok = ZP_Wallet::complete_deposit( $d->id, 'Approved by store owner' );
					zp_flash( $ok ? 'Deposit approved. The customer wallet has been credited.' : 'This deposit was already processed.', $ok ? 'success' : 'error' );
				} else {
					$ok = ZP_Wallet::reject_deposit( $d->id, zp_post( 'reason', 'Payment not received' ) );
					zp_flash( $ok ? 'Deposit rejected.' : 'This deposit was already processed.', $ok ? 'success' : 'error' );
				}
				break;

			case 'reseller_adjust':
				$cid    = (int) zp_post( 'customer_id' );
				$amount = round( (float) zp_post( 'amount' ), 2 );
				if ( ! self::is_customer_of( $cid, $user->ID ) || $amount <= 0 ) {
					zp_flash( 'Choose one of your customers and enter a valid amount.', 'error' );
				} elseif ( 'debit' === zp_post( 'direction' ) ) {
					$ok = ZP_Wallet::debit( $cid, $amount, 'adjustment', 'store', zp_post( 'note', 'Balance adjustment' ) );
					zp_flash( $ok ? 'Customer wallet debited.' : 'The customer does not have enough balance.', $ok ? 'success' : 'error' );
				} else {
					ZP_Wallet::credit( $cid, $amount, 'adjustment', 'store', zp_post( 'note', 'Balance adjustment' ) );
					zp_flash( 'Customer wallet credited.' );
				}
				break;

			case 'reseller_ticket_reply':
			case 'reseller_ticket_close':
				$t = ZP_Tickets::get( (int) zp_post( 'ticket_id' ) );
				if ( ! $t || (int) $t->reseller_id !== $user->ID ) {
					break;
				}
				if ( 'reseller_ticket_close' === $do ) {
					ZP_Tickets::close( $t->id );
					zp_flash( 'Ticket closed.' );
					break;
				}
				$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore
				if ( '' !== trim( $message ) ) {
					ZP_Tickets::add_message( $t->id, $user->ID, $message, true );
					zp_flash( 'Reply sent.' );
				}
				zp_redirect( zp_url( 'reseller', array( 'tab' => 'tickets', 'id' => $t->id ) ) );
				break;
		}
		zp_redirect( $back );
	}
}
