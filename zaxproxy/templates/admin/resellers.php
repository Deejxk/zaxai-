<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$rows    = $wpdb->get_results( 'SELECT r.*, u.user_email FROM ' . zp_table( 'resellers' ) . " r LEFT JOIN {$wpdb->users} u ON u.ID = r.user_id ORDER BY (r.domain_status = 'pending') DESC, r.created_at DESC LIMIT 300" );
$pending = array_filter(
	$rows,
	function ( $r ) {
		return 'pending' === $r->domain_status;
	}
);
$main    = wp_parse_url( home_url(), PHP_URL_HOST );
?>
<?php if ( $pending ) : ?>
<section class="zp-card zp-anim">
	<div class="zp-card__head"><h3><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?> Domains to connect</h3><?php echo zp_badge( 'pending' ); // phpcs:ignore ?></div>
	<ol class="zp-rsteps" style="margin-bottom:16px">
		<li><b>1</b><div><strong>Buy the domain in your hosting account</strong><span>Register the exact name shown below (it has already been paid for by the reseller).</span></div></li>
		<li><b>2</b><div><strong>Point it at this website</strong><span>In hPanel open <span class="zp-mono"><?php echo esc_html( $main ); ?></span> → Domains → Parked domains, and add the new domain so it loads this site. Wait until SSL is active.</span></div></li>
		<li><b>3</b><div><strong>Press Connect</strong><span>The store then opens on its own address and the reseller is shown the link.</span></div></li>
	</ol>
	<ul class="zp-list">
		<?php foreach ( $pending as $r ) : ?>
			<li>
				<span class="zp-list__ico zp-tone-amber"><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?></span>
				<div class="zp-list__main"><strong class="zp-mono"><?php echo esc_html( $r->domain ); ?></strong><span><?php echo esc_html( $r->brand . ' · ' . $r->user_email . ' · paid ' . zp_money( $r->domain_fee ) ); ?></span></div>
				<div class="zp-list__side zp-list__side--row">
					<button type="button" class="zp-btn zp-btn--soft zp-btn--sm" data-zp-copy="<?php echo esc_attr( $r->domain ); ?>" data-zp-copied="Domain copied"><?php echo zp_icon( 'copy', 16 ); // phpcs:ignore ?><span>Copy</span></button>
					<form method="post" onsubmit="return confirm('Is <?php echo esc_js( $r->domain ); ?> registered and pointing at this site?')"><?php zp_nonce_field( 'reseller_domain_connect' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><button class="zp-btn zp-btn--primary zp-btn--sm" type="submit">Connect</button></form>
					<form method="post" onsubmit="return confirm('Reject this domain and refund the reseller?')"><?php zp_nonce_field( 'reseller_domain_reject' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><button class="zp-btn zp-btn--danger zp-btn--sm" type="submit">Reject &amp; refund</button></form>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<section class="zp-card zp-anim" style="--d:1">
	<div class="zp-card__head"><h3>Reseller stores</h3></div>
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-tickets', 'No resellers yet', 'Customers can unlock a branded store from the Reseller menu in their panel.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Store</th><th>Owner</th><th>Domain</th><th class="zp-right">Markup</th><th class="zp-right">Customers</th><th class="zp-right">Store sales</th><th class="zp-right">Paid to you</th><th class="zp-right">Wallet</th><th>Status</th><th></th></tr></thead>
		<tbody>
		<?php
		foreach ( $rows as $r ) :
			$st = ZP_Resellers::stats( $r->user_id );
			?>
			<tr>
				<td data-label="Store"><strong><?php echo esc_html( $r->brand ); ?></strong><span class="zp-sub"><a href="<?php echo esc_url( zp_route_url( 's', $r->slug ) ); ?>" target="_blank" rel="noopener">/s/<?php echo esc_html( $r->slug ); ?></a></span></td>
				<td data-label="Owner"><a href="<?php echo esc_url( zp_admin_url( 'users', array( 'id' => $r->user_id ) ) ); ?>"><?php echo esc_html( $r->user_email ); ?></a></td>
				<td data-label="Domain"><?php if ( $r->domain && 'rejected' !== $r->domain_status ) : ?><span class="zp-mono"><?php echo esc_html( $r->domain ); ?></span><span class="zp-sub"><?php echo 'active' === $r->domain_status ? 'Connected' : 'Waiting to be connected'; ?></span><?php else : ?>—<?php endif; ?></td>
				<td data-label="Markup" class="zp-right"><?php echo esc_html( rtrim( rtrim( number_format( (float) $r->markup, 2 ), '0' ), '.' ) ); ?>%</td>
				<td data-label="Customers" class="zp-right"><?php echo (int) $st['customers']; ?></td>
				<td data-label="Store sales" class="zp-right"><?php echo esc_html( zp_money( $st['sales'] ) ); ?></td>
				<td data-label="Paid to you" class="zp-right"><strong><?php echo esc_html( zp_money( $st['cost'] ) ); ?></strong></td>
				<td data-label="Wallet" class="zp-right"><?php echo esc_html( zp_money( ZP_Wallet::balance( $r->user_id ) ) ); ?></td>
				<td data-label="Status"><?php echo zp_badge( $r->status ); // phpcs:ignore ?><span class="zp-sub"><?php echo $r->paid_until ? esc_html( 'Paid to ' . zp_date( $r->paid_until, false ) ) : 'Not activated'; ?></span></td>
				<td data-label=""><form method="post"><?php zp_nonce_field( 'reseller_status' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><input type="hidden" name="status" value="<?php echo 'suspended' === $r->status ? 'active' : 'suspended'; ?>"><button class="zp-btn zp-btn--sm <?php echo 'suspended' === $r->status ? 'zp-btn--soft' : 'zp-btn--danger'; ?>" type="submit"><?php echo 'suspended' === $r->status ? 'Reactivate' : 'Suspend'; ?></button></form></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php endif; ?>
</section>
