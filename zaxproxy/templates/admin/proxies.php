<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$q     = zp_query( 'q' );
$per   = 30;
$pg    = zp_page_num();
$t     = zp_table( 'proxies' );
$where = '';
if ( $q ) {
	$like  = '%' . $wpdb->esc_like( $q ) . '%';
	$where = $wpdb->prepare( "WHERE p.ip LIKE %s OR p.upstream_id LIKE %s OR u.user_email LIKE %s", $like, $like, $like );
}
$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id $where" ); // phpcs:ignore
$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT p.*, u.user_email FROM $t p LEFT JOIN {$wpdb->users} u ON u.ID = p.user_id $where ORDER BY p.id DESC LIMIT %d OFFSET %d", $per, ( $pg - 1 ) * $per ) ); // phpcs:ignore
?>
<form class="zp-toolbar zp-anim" method="get" action="<?php echo esc_url( zp_admin_url( 'proxies' ) ); ?>">
	<?php if ( ! zp_pretty() ) : ?><input type="hidden" name="zp" value="<?php echo esc_attr( ZP_ADMIN_BASE ); ?>"><input type="hidden" name="zpp" value="proxies"><?php endif; ?>
	<div class="zp-input zp-input--search"><?php echo zp_icon( 'search', 18 ); // phpcs:ignore ?><input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search IP, provider ID or customer email"></div>
	<button class="zp-btn zp-btn--soft" type="submit">Search</button>
</form>
<section class="zp-card zp-anim" style="--d:1">
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-proxies', 'No proxies found', 'Delivered proxies are listed here.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Proxy</th><th>Type</th><th>Customer</th><th>Order</th><th>Expires</th><th>Status</th><th>Reassign</th></tr></thead>
		<tbody>
		<?php foreach ( $rows as $p ) : ?>
			<tr>
				<td data-label="Proxy"><strong class="zp-mono"><?php echo esc_html( $p->ip ); ?></strong><span class="zp-sub"><?php echo esc_html( $p->upstream_id ); ?></span></td>
				<td data-label="Type"><span class="zp-flag zp-flag--sm"><?php echo esc_html( zp_flag( $p->country ) ); ?></span> <?php echo esc_html( zp_type_label( $p->proxy_type ) . ' · ' . $p->country ); ?></td>
				<td data-label="Customer"><a href="<?php echo esc_url( zp_admin_url( 'users', array( 'id' => $p->user_id ) ) ); ?>"><?php echo esc_html( $p->user_email ? $p->user_email : '#' . $p->user_id ); ?></a></td>
				<td data-label="Order">#<?php echo (int) $p->order_id; ?></td>
				<td data-label="Expires"><?php echo esc_html( zp_date( $p->date_end ) ); ?></td>
				<td data-label="Status"><?php echo zp_badge( ZP_Orders::proxy_state( $p ) ); // phpcs:ignore ?></td>
				<td data-label="Reassign"><form method="post" class="zp-inline"><?php zp_nonce_field( 'proxy_assign' ); ?><input type="hidden" name="id" value="<?php echo (int) $p->id; ?>"><input type="number" name="user_id" placeholder="User ID" min="1" required style="width:90px"><button class="zp-btn zp-btn--soft zp-btn--sm" type="submit">Move</button></form></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php echo zp_paginate( $total, $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</section>
