<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$per   = 40;
$pg    = zp_page_num();
$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . zp_table( 'transactions' ) );
$rows  = $wpdb->get_results( $wpdb->prepare( 'SELECT t.*, u.user_email FROM ' . zp_table( 'transactions' ) . " t LEFT JOIN {$wpdb->users} u ON u.ID = t.user_id ORDER BY t.id DESC LIMIT %d OFFSET %d", $per, ( $pg - 1 ) * $per ) );
?>
<section class="zp-card zp-anim">
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No transactions', 'Every wallet movement is logged here.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Date</th><th>Customer</th><th>Type</th><th>Description</th><th class="zp-right">Amount</th><th class="zp-right">Balance after</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $t ) : ?>
			<tr>
				<td data-label="Date"><?php echo esc_html( zp_date( $t->created_at ) ); ?></td>
				<td data-label="Customer"><a href="<?php echo esc_url( zp_admin_url( 'users', array( 'id' => $t->user_id ) ) ); ?>"><?php echo esc_html( $t->user_email ? $t->user_email : '#' . $t->user_id ); ?></a></td>
				<td data-label="Type"><?php echo esc_html( ucfirst( $t->type ) ); ?></td>
				<td data-label="Description"><?php echo esc_html( $t->description ); ?></td>
				<td data-label="Amount" class="zp-right"><strong class="<?php echo $t->amount >= 0 ? 'zp-pos' : ''; ?>"><?php echo esc_html( ( $t->amount >= 0 ? '+' : '' ) . zp_money( $t->amount ) ); ?></strong></td>
				<td data-label="Balance after" class="zp-right"><?php echo esc_html( zp_money( $t->balance_after ) ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php echo zp_paginate( $total, $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</section>
