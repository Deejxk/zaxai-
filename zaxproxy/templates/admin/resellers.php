<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$rows = $wpdb->get_results( 'SELECT r.*, u.user_email FROM ' . zp_table( 'resellers' ) . " r LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id ORDER BY r.created_at DESC LIMIT 300" );
?>
<section class="zp-card zp-anim">
	<div class="zp-card__head"><h3>Reseller stores</h3></div>
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-tickets', 'No resellers yet', 'Customers can open a branded store from the Reseller menu in their panel.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Store</th><th>Owner</th><th class="zp-right">Markup</th><th class="zp-right">Customers</th><th class="zp-right">Store sales</th><th class="zp-right">Paid to you</th><th class="zp-right">Wallet</th><th>Status</th><th></th></tr></thead>
		<tbody>
		<?php
		foreach ( $rows as $r ) :
			$st = ZP_Resellers::stats( $r->user_id );
			?>
			<tr>
				<td data-label="Store"><strong><?php echo esc_html( $r->brand ); ?></strong><span class="zp-sub"><a href="<?php echo esc_url( ZP_Resellers::store_url( $r ) ); ?>" target="_blank" rel="noopener">/s/<?php echo esc_html( $r->slug ); ?></a></span></td>
				<td data-label="Owner"><a href="<?php echo esc_url( zp_admin_url( 'users', array( 'id' => $r->user_id ) ) ); ?>"><?php echo esc_html( $r->user_email ); ?></a></td>
				<td data-label="Markup" class="zp-right"><?php echo esc_html( rtrim( rtrim( number_format( (float) $r->markup, 2 ), '0' ), '.' ) ); ?>%</td>
				<td data-label="Customers" class="zp-right"><?php echo (int) $st['customers']; ?></td>
				<td data-label="Store sales" class="zp-right"><?php echo esc_html( zp_money( $st['sales'] ) ); ?></td>
				<td data-label="Paid to you" class="zp-right"><strong><?php echo esc_html( zp_money( $st['cost'] ) ); ?></strong></td>
				<td data-label="Wallet" class="zp-right"><?php echo esc_html( zp_money( ZP_Wallet::balance( $r->user_id ) ) ); ?></td>
				<td data-label="Status"><?php echo zp_badge( 'active' === $r->status ? 'active' : 'closed' ); // phpcs:ignore ?></td>
				<td data-label=""><form method="post"><?php zp_nonce_field( 'reseller_status' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><input type="hidden" name="status" value="<?php echo 'active' === $r->status ? 'suspended' : 'active'; ?>"><button class="zp-btn zp-btn--sm <?php echo 'active' === $r->status ? 'zp-btn--danger' : 'zp-btn--soft'; ?>" type="submit"><?php echo 'active' === $r->status ? 'Suspend' : 'Reactivate'; ?></button></form></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php endif; ?>
</section>
