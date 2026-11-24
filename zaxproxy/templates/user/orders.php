<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user */
$per    = 15;
$pg     = zp_page_num();
$stats  = ZP_Orders::user_stats( $user->ID );
$orders = ZP_Orders::user_orders( $user->ID, $per, ( $pg - 1 ) * $per );
?>
<div class="zp-card zp-anim">
	<div class="zp-card__head"><h3>Order history</h3><a class="zp-btn zp-btn--primary zp-btn--sm" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><?php echo zp_icon( 'plus', 16 ); // phpcs:ignore ?>New order</a></div>
	<?php if ( ! $orders ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No orders yet', 'When you buy or extend proxies, the receipt appears here.', zp_url( 'buy' ), 'Buy proxies' ); // phpcs:ignore ?>
	<?php else : ?>
		<div class="zp-tablewrap">
			<table class="zp-table">
				<thead><tr><th>Order</th><th>Details</th><th>Period</th><th>Date</th><th class="zp-right">Amount</th><th>Status</th></tr></thead>
				<tbody>
				<?php foreach ( $orders as $o ) : ?>
					<tr>
						<td data-label="Order"><strong>#<?php echo (int) $o->id; ?></strong><span class="zp-sub"><?php echo 'extend' === $o->kind ? 'Renewal' : 'Purchase'; ?></span></td>
						<td data-label="Details"><span class="zp-flag zp-flag--sm"><?php echo esc_html( zp_flag( $o->country ) ); ?></span> <?php echo esc_html( $o->qty . ' × ' . zp_type_label( $o->proxy_type ) . ' · ' . zp_country_name( $o->country ) ); ?></td>
						<td data-label="Period"><?php echo (int) $o->days; ?> days</td>
						<td data-label="Date"><?php echo esc_html( zp_date( $o->created_at ) ); ?></td>
						<td data-label="Amount" class="zp-right"><strong><?php echo esc_html( zp_money( $o->amount ) ); ?></strong><?php echo $o->refunded ? '<span class="zp-sub">Refunded</span>' : ''; ?></td>
						<td data-label="Status"><?php echo zp_badge( $o->status ); // phpcs:ignore ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php echo zp_paginate( $stats['orders'], $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</div>
