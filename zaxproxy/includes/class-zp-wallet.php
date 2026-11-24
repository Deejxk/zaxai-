<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_Wallet {

	public static function balance( $user_id ) {
		global $wpdb;
		return (float) $wpdb->get_var( $wpdb->prepare( 'SELECT balance FROM ' . zp_table( 'wallets' ) . ' WHERE user_id = %d', $user_id ) );
	}

	private static function ensure( $user_id ) {
		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'INSERT IGNORE INTO ' . zp_table( 'wallets' ) . ' (user_id, balance) VALUES (%d, 0)', $user_id ) );
	}

	private static function log( $user_id, $type, $amount, $reference, $description ) {
		global $wpdb;
		$wpdb->insert(
			zp_table( 'transactions' ),
			array(
				'user_id'       => $user_id,
				'type'          => $type,
				'amount'        => $amount,
				'balance_after' => self::balance( $user_id ),
				'reference'     => $reference,
				'description'   => $description,
				'created_at'    => zp_now(),
			)
		);
	}

	public static function credit( $user_id, $amount, $type, $reference = '', $description = '' ) {
		global $wpdb;
		$amount = round( (float) $amount, 2 );
		if ( $amount <= 0 ) {
			return false;
		}
		self::ensure( $user_id );
		$wpdb->query( $wpdb->prepare( 'UPDATE ' . zp_table( 'wallets' ) . ' SET balance = balance + %f WHERE user_id = %d', $amount, $user_id ) );
		self::log( $user_id, $type, $amount, $reference, $description );
		return true;
	}

	/** Atomic debit: fails (returns false) when funds are insufficient. */
	public static function debit( $user_id, $amount, $type, $reference = '', $description = '' ) {
		global $wpdb;
		$amount = round( (float) $amount, 2 );
		if ( $amount <= 0 ) {
			return false;
		}
		self::ensure( $user_id );
		$ok = $wpdb->query(
			$wpdb->prepare( 'UPDATE ' . zp_table( 'wallets' ) . ' SET balance = balance - %f WHERE user_id = %d AND balance >= %f', $amount, $user_id, $amount )
		);
		if ( ! $ok ) {
			return false;
		}
		self::log( $user_id, $type, -$amount, $reference, $description );
		return true;
	}

	public static function transactions( $user_id, $limit = 20, $offset = 0 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . zp_table( 'transactions' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d OFFSET %d', $user_id, $limit, $offset )
		);
	}

	public static function count_transactions( $user_id ) {
		global $wpdb;
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'transactions' ) . ' WHERE user_id = %d', $user_id ) );
	}

	/* ---------- Deposits ---------- */

	public static function create_deposit( $user_id, $gateway, $amount, $note = '', $gw_amount = 0, $gw_currency = 'NGN' ) {
		global $wpdb;
		$ref = 'ZP' . strtoupper( wp_generate_password( 14, false ) );
		$wpdb->insert(
			zp_table( 'deposits' ),
			array(
				'user_id'     => $user_id,
				'gateway'     => $gateway,
				'amount'      => round( (float) $amount, 2 ),
				'gw_amount'   => round( (float) $gw_amount, 2 ),
				'gw_currency' => $gw_currency,
				'reference'   => $ref,
				'status'      => 'pending',
				'note'        => $note,
				'created_at'  => zp_now(),
				'updated_at'  => zp_now(),
			)
		);
		return $wpdb->insert_id ? $ref : false;
	}

	public static function deposit_by_ref( $ref ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'deposits' ) . ' WHERE reference = %s', $ref ) );
	}

	public static function deposit( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'deposits' ) . ' WHERE id = %d', $id ) );
	}

	/** Idempotent: only the first caller to flip pending -> completed credits the wallet. */
	public static function complete_deposit( $id, $admin_note = '' ) {
		global $wpdb;
		$flipped = $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . zp_table( 'deposits' ) . " SET status = 'completed', admin_note = %s, updated_at = %s WHERE id = %d AND status = 'pending'",
				$admin_note,
				zp_now(),
				$id
			)
		);
		if ( ! $flipped ) {
			return false;
		}
		$d = self::deposit( $id );
		self::credit( $d->user_id, $d->amount, 'deposit', $d->reference, 'Wallet top-up via ' . self::gateway_label( $d->gateway ) );
		return true;
	}

	public static function reject_deposit( $id, $admin_note = '' ) {
		global $wpdb;
		return (bool) $wpdb->query(
			$wpdb->prepare(
				'UPDATE ' . zp_table( 'deposits' ) . " SET status = 'rejected', admin_note = %s, updated_at = %s WHERE id = %d AND status = 'pending'",
				$admin_note,
				zp_now(),
				$id
			)
		);
	}

	public static function gateway_label( $gateway ) {
		$labels = array( 'manual' => zp_get( 'manual_title', 'Manual payment' ), 'paystack' => 'Paystack', 'admin' => 'Admin' );
		return isset( $labels[ $gateway ] ) ? $labels[ $gateway ] : ucfirst( $gateway );
	}

	public static function user_deposits( $user_id, $limit = 10 ) {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare( 'SELECT * FROM ' . zp_table( 'deposits' ) . ' WHERE user_id = %d ORDER BY id DESC LIMIT %d', $user_id, $limit )
		);
	}
}
