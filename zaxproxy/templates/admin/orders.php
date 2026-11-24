<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$status = sanitize_key( zp_query( 'status' ) );
$per    = 25;
$pg     = zp_page_num();
$where  = $status ? $wpdb->prepare( 'WHERE status = %s', $status ) : '';
$total  = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . zp_table( 'orders' ) . " $where" ); // phpcs:ignore
$rows   = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'orders' ) . " $where ORDER BY id DESC LIMIT %d OFFSET %d", $per, ( $pg - 1 ) * $per ) ); // phpcs:ignore
?>
<div class="zp-toolbar zp-anim">
	<div class="zp-pills zp-pills--sm">
		<?php foreach ( array( '' => 'All', 'processing' => 'Processing', 'review' => 'Review', 'completed' => 'Completed', 'failed' => 'Failed' ) as $k => $label ) : ?>
			<a class="zp-pill<?php echo $status === $k ? ' is-active' : ''; ?>" href="<?php echo esc_url( zp_admin_url( 'orders', $k ? array( 'status' => $k ) : array() ) ); ?>"><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</div>
	<form method="post" style="margin-left:auto"><?php zp_nonce_field( 'sync_all' ); ?><button class="zp-btn zp-btn--soft zp-btn--sm" type="submit"><?php echo zp_icon( 'refresh', 16 ); // phpcs:ignore ?>Sync with provider</button></form>
</div>
<section class="zp-card zp-anim" style="--d:1">
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No orders here', 'Nothing matches this filter.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Order</th><th>Customer</th><th>Details</th><th class="zp-right">Charged / cost</th><th>Status</th><th>Actions</th></tr></thead>
		<tbody>
		<?php
		foreach ( $rows as $r ) :
			$u = get_userdata( $r->user_id );
			?>
			<tr>
				<td data-label="Order"><strong>#<?php echo (int) $r->id; ?></strong><span class="zp-sub"><?php echo esc_html( zp_date( $r->created_at ) ); ?></span></td>
				<td data-label="Customer"><?php echo $u ? '<a href="' . esc_url( zp_admin_url( 'users', array( 'id' => $u->ID ) ) ) . '">' . esc_html( $u->user_email ) . '</a>' : '—'; ?></td>
				<td data-label="Details"><?php echo esc_html( ( 'extend' === $r->kind ? 'Renew ' : '' ) . $r->qty . ' × ' . zp_type_label( $r->proxy_type ) . ' · ' . $r->country . ' · ' . $r->days . 'd' ); ?>
					<span class="zp-sub"><?php echo esc_html( trim( ( $r->upstream_order ? 'Ref ' . $r->upstream_order . ' ' : '' ) . ( $r->error ? '· ' . $r->error : '' ) ) ); ?></span></td>
				<td data-label="Charged / cost" class="zp-right"><strong><?php echo esc_html( zp_money( $r->reseller_id ? $r->reseller_cost : $r->amount ) ); ?></strong><?php echo $r->reseller_id ? '<span class="zp-sub">Store sale ' . esc_html( zp_money( $r->amount ) ) . '</span>' : ''; ?><span class="zp-sub">Cost <?php echo esc_html( zp_usd( $r->cost ) ); ?></span></td>
				<td data-label="Status"><?php echo zp_badge( $r->status ); // phpcs:ignore ?><?php echo $r->refunded ? ' ' . zp_badge( 'refunded' ) : ''; // phpcs:ignore ?></td>
				<td data-label="Actions"><div class="zp-inline">
					<?php if ( in_array( $r->status, array( 'review', 'processing' ), true ) ) : ?>
						<form method="post"><?php zp_nonce_field( 'order_complete' ); ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><button class="zp-btn zp-btn--soft zp-btn--sm" type="submit">Mark done</button></form>
					<?php endif; ?>
					<?php if ( ! $r->refunded ) : ?>
						<form method="post" onsubmit="return confirm('Refund this order to the customer wallet?')"><?php zp_nonce_field( 'order_refund' ); ?><input type="hidden" name="id" value="<?php echo (int) $r->id; ?>"><button class="zp-btn zp-btn--danger zp-btn--sm" type="submit">Refund</button></form>
					<?php endif; ?>
				</div></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php echo zp_paginate( $total, $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</section>
