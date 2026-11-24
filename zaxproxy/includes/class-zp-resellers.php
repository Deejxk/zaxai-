<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resellers run a branded store on top of the platform.
 *
 * - A reseller is a normal customer with a row in zp_resellers (brand, store slug, markup).
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
		return zp_route_url( 's', $reseller->slug . ( $page ? '/' . $page : '' ) );
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
		if ( $existing ) {
			$wpdb->update( zp_table( 'resellers' ), $data, array( 'user_id' => $user_id ) );
		} else {
			$data['user_id']    = $user_id;
			$data['status']     = 'active';
			$data['created_at'] = zp_now();
			$wpdb->insert( zp_table( 'resellers' ), $data );
		}
		$GLOBALS['zp_resellers_dirty'] = true;
		return true;
	}

	public static function set_status( $user_id, $status ) {
		global $wpdb;
		$wpdb->update( zp_table( 'resellers' ), array( 'status' => 'suspended' === $status ? 'suspended' : 'active' ), array( 'user_id' => (int) $user_id ) );
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
			$new = ! self::get( $user->ID );
			$res = self::save( $user->ID, wp_unslash( $_POST ) ); // phpcs:ignore
			if ( is_wp_error( $res ) ) {
				zp_flash( $res->get_error_message(), 'error' );
				zp_redirect( $new ? zp_url( 'reseller' ) : $back );
			}
			zp_flash( $new ? 'Your store is live. Share your link and start selling.' : 'Store settings saved.' );
			zp_redirect( $new ? zp_url( 'reseller' ) : $back );
		}

		$me = self::get( $user->ID );
		if ( ! $me ) {
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
