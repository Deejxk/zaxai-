<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$status = sanitize_key( zp_query( 'status' ) );
$per    = 25;
$pg     = zp_page_num();
$where  = $status ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';
$total  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . zp_table( 'deposits' ) . " $where" ); // phpcs:ignore
$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'deposits' ) . " $where ORDER BY (status = 'pending' AND gateway = 'manual') DESC, id DESC LIMIT %d OFFSET %d", $per, ( $pg - 1 ) * $per ) ); // phpcs:ignore
?>
<div class="zp-toolbar zp-anim"><div class="zp-pills zp-pills--sm">
	<?php foreach ( array( '' => 'All', 'pending' => 'Pending', 'completed' => 'Completed', 'rejected' => 'Rejected' ) as $k => $label ) : ?>
		<a class="zp-pill<?php echo $status === $k ? ' is-active' : ''; ?>" href="<?php echo esc_url( zp_admin_url( 'deposits', $k ? array( 'status' => $k ) : array() ) ); ?>"><?php echo esc_html( $label ); ?></a>
	<?php endforeach; ?>
</div></div>
<section class="zp-card zp-anim" style="--d:1">
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No deposits', 'Wallet top-ups will be listed here.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Deposit</th><th>Customer</th><th>Method</th><th>Sender name</th><th class="zp-right">Amount</th><th>Status</th><th>Actions</th></tr></thead>
		<tbody>
		<?php
		foreach ( $rows as $d ) :
			$u = get_userdata( $d->user_id );
			?>
			<tr>
				<td data-label="Deposit"><strong class="zp-mono"><?php echo esc_html( $d->reference ); ?></strong><span class="zp-sub"><?php echo esc_html( zp_date( $d->created_at ) ); ?></span></td>
				<td data-label="Customer"><?php echo $u ? '<a href="' . esc_url( zp_admin_url( 'users', array( 'id' => $u->ID ) ) ) . '">' . esc_html( $u->user_email ) . '</a>' : '—'; ?></td>
				<td data-label="Method"><?php echo esc_html( ZP_Wallet::gateway_label( $d->gateway ) ); ?></td>
				<td data-label="Sender name"><?php echo esc_html( $d->note ); ?><?php echo $d->admin_note ? '<span class="zp-sub">' . esc_html( $d->admin_note ) . '</span>' : ''; ?></td>
				<td data-label="Amount" class="zp-right"><strong><?php echo esc_html( zp_money( $d->amount ) ); ?></strong></td>
				<td data-label="Status"><?php echo zp_badge( $d->status ); // phpcs:ignore ?></td>
				<td data-label="Actions"><?php if ( 'pending' === $d->status ) : ?><div class="zp-inline">
					<form method="post" onsubmit="return confirm('Confirm you received this payment and credit the wallet?')"><?php zp_nonce_field( 'deposit_approve' ); ?><input type="hidden" name="id" value="<?php echo (int) $d->id; ?>"><button class="zp-btn zp-btn--primary zp-btn--sm" type="submit">Approve</button></form>
					<form method="post" class="zp-inline"><?php zp_nonce_field( 'deposit_reject' ); ?><input type="hidden" name="id" value="<?php echo (int) $d->id; ?>"><input type="text" name="reason" placeholder="Reason" maxlength="150"><button class="zp-btn zp-btn--danger zp-btn--sm" type="submit">Reject</button></form>
				</div><?php endif; ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php echo zp_paginate( $total, $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</section>
