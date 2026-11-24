<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_Tickets {

	public static function create( $user_id, $subject, $message ) {
		global $wpdb;
		$reseller = ZP_Resellers::of_user( $user_id );
		$wpdb->insert(
			zp_table( 'tickets' ),
			array( 'user_id' => $user_id, 'subject' => $subject, 'status' => 'open', 'reseller_id' => $reseller ? $reseller->user_id : 0, 'created_at' => zp_now(), 'updated_at' => zp_now() )
		);
		$id = (int) $wpdb->insert_id;
		if ( $id && $reseller ) {
			// Tickets from a reseller's customers are answered by the reseller, not by platform staff.
			self::add_message( $id, $user_id, $message, false );
			ZP_Notify::add( $reseller->user_id, 'ticket', 'New customer message', ZP_Notify::name( $user_id ) . ': ' . $subject, ZP_Notify::user_path( 'reseller', array( 'tab' => 'tickets', 'id' => $id ) ) );
			$owner = get_userdata( $reseller->user_id );
			if ( $owner ) {
				wp_mail( $owner->user_email, '[' . $reseller->brand . '] New ticket #' . $id . ': ' . $subject, $message . "\n\n" . zp_route_url( ZP_USER_BASE, 'reseller', array( 'tab' => 'tickets', 'id' => $id ) ) );
			}
			return $id;
		}
		if ( $id ) {
			self::add_message( $id, $user_id, $message, false );
			ZP_Notify::admins( 'ticket', 'New support ticket #' . $id, ZP_Notify::name( $user_id ) . ': ' . $subject, ZP_Notify::admin_path( 'tickets', array( 'id' => $id ) ) );
			$to = zp_get( 'support_email' );
			if ( is_email( $to ) ) {
				wp_mail( $to, '[' . zp_get( 'brand' ) . '] New ticket #' . $id . ': ' . $subject, $message . "\n\n" . zp_admin_url( 'tickets', array( 'id' => $id ) ) );
			}
		}
		return $id;
	}

	public static function add_message( $ticket_id, $user_id, $message, $is_admin ) {
		global $wpdb;
		$wpdb->insert(
			zp_table( 'ticket_messages' ),
			array( 'ticket_id' => $ticket_id, 'user_id' => $user_id, 'is_admin' => $is_admin ? 1 : 0, 'message' => $message, 'created_at' => zp_now() )
		);
		$wpdb->update( zp_table( 'tickets' ), array( 'status' => $is_admin ? 'answered' : 'open', 'updated_at' => zp_now() ), array( 'id' => $ticket_id ) );
	}

	public static function get( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'tickets' ) . ' WHERE id = %d', $id ) );
	}

	public static function messages( $ticket_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'ticket_messages' ) . ' WHERE ticket_id = %d ORDER BY id ASC', $ticket_id ) );
	}

	public static function for_user( $user_id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'tickets' ) . ' WHERE user_id = %d ORDER BY updated_at DESC LIMIT 100', $user_id ) );
	}

	public static function close( $id ) {
		global $wpdb;
		$wpdb->update( zp_table( 'tickets' ), array( 'status' => 'closed', 'updated_at' => zp_now() ), array( 'id' => $id ) );
	}
}
