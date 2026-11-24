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
<?php
$auto   = ZP_Hosting::configured();
$stores = array_filter(
	$rows,
	function ( $r ) {
		return (bool) $r->paid_until;
	}
);
?>
<section class="zp-card zp-anim">
	<div class="zp-card__head"><h3><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?> Connect a domain to a store</h3><?php echo zp_badge( $auto ? 'active' : 'pending' ); // phpcs:ignore ?></div>
	<?php if ( $auto ) : ?>
		<p class="zp-muted" style="margin-bottom:14px">Buy the <span class="zp-mono">.cloud</span> domain in your Hostinger account, then enter it here and choose the store. It is added to this website's hosting and switched on for the store automatically.</p>
	<?php else : ?>
		<div class="zp-alert zp-alert--info" style="margin-bottom:14px"><?php echo zp_icon( 'settings', 18 ); // phpcs:ignore ?><span>To make this fully automatic, save a Hostinger API token in Settings. Until then, after connecting here you must also add the domain as a parked domain on <span class="zp-mono"><?php echo esc_html( $main ); ?></span> in hPanel.</span><a class="zp-btn zp-btn--sm zp-btn--primary" href="<?php echo esc_url( zp_admin_url( 'settings' ) ); ?>">Open settings</a></div>
	<?php endif; ?>
	<?php if ( ! $stores ) : ?>
		<p class="zp-muted">No activated stores yet.</p>
	<?php else : ?>
		<form method="post" class="zp-form">
			<?php zp_nonce_field( 'reseller_domain_link' ); ?>
			<div class="zp-fieldrow" style="margin-top:0">
				<label class="zp-field"><span>Domain you purchased</span><div class="zp-input"><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?><input type="text" name="domain" maxlength="46" required placeholder="brandname" autocapitalize="none"><b class="zp-prefix">.cloud</b></div></label>
				<label class="zp-field"><span>Store</span><select name="user_id" class="zp-select" required>
					<option value="">Choose a store…</option>
					<?php foreach ( $stores as $r ) : ?>
						<option value="<?php echo (int) $r->user_id; ?>"><?php echo esc_html( $r->brand . ' · ' . $r->user_email . ( $r->domain && 'rejected' !== $r->domain_status ? ' · ' . ( 'pending' === $r->domain_status ? 'requested ' : 'has ' ) . $r->domain : '' ) ); ?></option>
					<?php endforeach; ?>
				</select></label>
			</div>
			<button class="zp-btn zp-btn--primary" type="submit"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Connect domain</button>
		</form>
	<?php endif; ?>
</section>

<?php if ( $pending ) : ?>
<section class="zp-card zp-anim" style="--d:1">
	<div class="zp-card__head"><h3><?php echo zp_icon( 'clock', 18 ); // phpcs:ignore ?> Domains resellers have paid for</h3><?php echo zp_badge( 'pending' ); // phpcs:ignore ?></div>
	<p class="zp-muted" style="margin-bottom:14px">Buy the exact name in your hosting account, then press Connect<?php echo $auto ? '. It is added to your hosting and switched on automatically.' : ' and add it as a parked domain in hPanel.'; ?></p>
	<ul class="zp-list">
		<?php foreach ( $pending as $r ) : ?>
			<li>
				<span class="zp-list__ico zp-tone-amber"><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?></span>
				<div class="zp-list__main"><strong class="zp-mono"><?php echo esc_html( $r->domain ); ?></strong><span><?php echo esc_html( $r->brand . ' · ' . $r->user_email . ' · paid ' . zp_money( $r->domain_fee ) ); ?></span></div>
				<div class="zp-list__side zp-list__side--row">
					<button type="button" class="zp-btn zp-btn--soft zp-btn--sm" data-zp-copy="<?php echo esc_attr( $r->domain ); ?>" data-zp-copied="Domain copied"><?php echo zp_icon( 'copy', 16 ); // phpcs:ignore ?><span>Copy</span></button>
					<form method="post" onsubmit="return confirm('Have you bought <?php echo esc_js( $r->domain ); ?> in your hosting account?')"><?php zp_nonce_field( 'reseller_domain_connect' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><button class="zp-btn zp-btn--primary zp-btn--sm" type="submit">Connect</button></form>
					<form method="post" onsubmit="return confirm('Reject this domain and refund the reseller?')"><?php zp_nonce_field( 'reseller_domain_reject' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><button class="zp-btn zp-btn--danger zp-btn--sm" type="submit">Reject &amp; refund</button></form>
				</div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<section class="zp-card zp-anim" style="--d:2">
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
				<td data-label="Domain"><?php if ( $r->domain && 'rejected' !== $r->domain_status ) : ?><span class="zp-mono"><?php echo esc_html( $r->domain ); ?></span><span class="zp-sub"><?php echo 'active' === $r->domain_status ? 'Connected' : 'Waiting to be connected'; ?></span><?php if ( 'active' === $r->domain_status ) : ?><form method="post" onsubmit="return confirm('Disconnect <?php echo esc_js( $r->domain ); ?> from this store?')" style="margin-top:4px"><?php zp_nonce_field( 'reseller_domain_disconnect' ); ?><input type="hidden" name="user_id" value="<?php echo (int) $r->user_id; ?>"><button class="zp-btn zp-btn--ghost zp-btn--sm" type="submit">Disconnect</button></form><?php endif; ?><?php else : ?>—<?php endif; ?></td>
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
