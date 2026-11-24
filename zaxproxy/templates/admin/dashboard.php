<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$o        = zp_table( 'orders' );
$paid     = "refunded = 0 AND status IN ('completed','processing','review')";
$revenue  = (float) $wpdb->get_var( "SELECT COALESCE(SUM(amount),0) FROM $o WHERE $paid" ); // phpcs:ignore
$cost     = (float) $wpdb->get_var( "SELECT COALESCE(SUM(cost * fx_rate),0) FROM $o WHERE $paid" ); // phpcs:ignore
$month    = (float) $wpdb->get_var( $wpdb->prepare( "SELECT COALESCE(SUM(amount),0) FROM $o WHERE $paid AND created_at >= %s", gmdate( 'Y-m-01 00:00:00' ) ) ); // phpcs:ignore
$users    = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $wpdb->users );
$active   = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'proxies' ) . ' WHERE date_end > %s', zp_now() ) );
$wallets  = (float) $wpdb->get_var( 'SELECT COALESCE(SUM(balance),0) FROM ' . zp_table( 'wallets' ) );
$recent   = $wpdb->get_results( "SELECT * FROM $o ORDER BY id DESC LIMIT 8" ); // phpcs:ignore
$has_key  = '' !== trim( (string) zp_get( 'api_key' ) );
$upstream = $has_key ? ZP_API::balance() : null;
$last_err = get_option( 'zaxproxy_last_api_error' );
$days     = array();
for ( $i = 13; $i >= 0; $i-- ) {
	$days[ gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS ) ] = 0;
}
foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) d, SUM(amount) s FROM $o WHERE $paid AND created_at >= %s GROUP BY DATE(created_at)", gmdate( 'Y-m-d 00:00:00', time() - 13 * DAY_IN_SECONDS ) ) ) as $r ) { // phpcs:ignore
	if ( isset( $days[ $r->d ] ) ) {
		$days[ $r->d ] = (float) $r->s;
	}
}
$max = max( 1, max( $days ) );
?>
<?php if ( ! $has_key ) : ?>
	<div class="zp-alert zp-alert--info"><?php echo zp_icon( 'settings', 18 ); // phpcs:ignore ?><span>Connect your proxy-ipv4.com API key to start selling.</span><a class="zp-btn zp-btn--sm zp-btn--primary" href="<?php echo esc_url( zp_admin_url( 'settings' ) ); ?>">Open settings</a></div>
<?php elseif ( is_wp_error( $upstream ) ) : ?>
	<div class="zp-alert zp-alert--error"><?php echo zp_icon( 'x', 18 ); // phpcs:ignore ?><span>Provider API error: <?php echo esc_html( $upstream->get_error_message() ); ?></span><a class="zp-btn zp-btn--sm zp-btn--soft" href="<?php echo esc_url( zp_admin_url( 'settings' ) ); ?>">Check settings</a></div>
<?php elseif ( is_array( $last_err ) ) : ?>
	<div class="zp-alert zp-alert--error"><?php echo zp_icon( 'x', 18 ); // phpcs:ignore ?><span>A customer order was blocked on <?php echo esc_html( zp_date( $last_err['time'] ) ); ?>: <?php echo esc_html( $last_err['message'] ); ?>. Top up or check your provider account.</span></div>
<?php endif; ?>

<section class="zp-stats">
	<div class="zp-stat zp-anim" style="--d:1"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'chart' ); // phpcs:ignore ?></span><span class="zp-stat__label">Revenue (all time)</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( $revenue ) ); ?></strong><span class="zp-sub">Profit <?php echo esc_html( zp_money( $revenue - $cost ) ); ?> · This month <?php echo esc_html( zp_money( $month ) ); ?></span></div>
	<div class="zp-stat zp-anim" style="--d:2"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'users' ); // phpcs:ignore ?></span><span class="zp-stat__label">Customers</span><strong class="zp-stat__value"><?php echo (int) $users; ?></strong><span class="zp-sub">Wallets hold <?php echo esc_html( zp_money( $wallets ) ); ?></span></div>
	<div class="zp-stat zp-anim" style="--d:3"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'globe' ); // phpcs:ignore ?></span><span class="zp-stat__label">Active proxies</span><strong class="zp-stat__value"><?php echo (int) $active; ?></strong><span class="zp-sub">Across all customers</span></div>
	<div class="zp-stat zp-anim" style="--d:4"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'wallet' ); // phpcs:ignore ?></span><span class="zp-stat__label">Provider balance</span><strong class="zp-stat__value"><?php echo is_array( $upstream ) ? esc_html( zp_usd( $upstream['balance'] ) ) : '—'; ?></strong><span class="zp-sub"><?php echo is_array( $upstream ) ? esc_html( $upstream['user'] ) : 'Not connected'; ?></span></div>
</section>

<div class="zp-grid2 zp-grid2--wide">
	<section class="zp-card zp-anim" style="--d:5">
		<div class="zp-card__head"><h3>Sales, last 14 days</h3></div>
		<div class="zp-bars">
			<?php foreach ( $days as $d => $v ) : ?>
				<div class="zp-bars__col" title="<?php echo esc_attr( $d . ': ' . zp_money( $v ) ); ?>"><i style="height:<?php echo esc_attr( max( 3, round( $v / $max * 100 ) ) ); ?>%"></i><span><?php echo esc_html( gmdate( 'j', strtotime( $d ) ) ); ?></span></div>
			<?php endforeach; ?>
		</div>
	</section>
	<section class="zp-card zp-anim" style="--d:6">
		<div class="zp-card__head"><h3>Needs attention</h3></div>
		<ul class="zp-list zp-list--link">
			<li><a href="<?php echo esc_url( zp_admin_url( 'deposits', array( 'status' => 'pending' ) ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'card', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Deposits to confirm</strong><span>Manual payments awaiting approval</span></div><div class="zp-list__side"><strong><?php echo (int) $pending_deposits; ?></strong></div></a></li>
			<li><a href="<?php echo esc_url( zp_admin_url( 'orders', array( 'status' => 'review' ) ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'receipt', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Orders to review</strong><span>No confirmation from provider</span></div><div class="zp-list__side"><strong><?php echo (int) $review_orders; ?></strong></div></a></li>
			<li><a href="<?php echo esc_url( zp_admin_url( 'tickets' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Open tickets</strong><span>Waiting for a reply</span></div><div class="zp-list__side"><strong><?php echo (int) $open_tickets; ?></strong></div></a></li>
		</ul>
	</section>
</div>

<section class="zp-card zp-anim" style="--d:7">
	<div class="zp-card__head"><h3>Latest orders</h3><a href="<?php echo esc_url( zp_admin_url( 'orders' ) ); ?>">View all</a></div>
	<?php if ( ! $recent ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No orders yet', 'Customer purchases will appear here.' ); // phpcs:ignore ?>
	<?php else : ?>
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Order</th><th>Customer</th><th>Details</th><th class="zp-right">Charged</th><th class="zp-right">Cost</th><th>Status</th></tr></thead>
		<tbody>
		<?php
		foreach ( $recent as $r ) :
			$u = get_userdata( $r->user_id );
			?>
			<tr>
				<td data-label="Order"><strong>#<?php echo (int) $r->id; ?></strong><span class="zp-sub"><?php echo esc_html( zp_date( $r->created_at ) ); ?></span></td>
				<td data-label="Customer"><?php echo $u ? '<a href="' . esc_url( zp_admin_url( 'users', array( 'id' => $u->ID ) ) ) . '">' . esc_html( $u->user_email ) . '</a>' : '—'; ?></td>
				<td data-label="Details"><?php echo esc_html( ( 'extend' === $r->kind ? 'Renew ' : '' ) . $r->qty . ' × ' . zp_type_label( $r->proxy_type ) . ' · ' . $r->country . ' · ' . $r->days . 'd' ); ?></td>
				<td data-label="Charged" class="zp-right"><strong><?php echo esc_html( zp_money( $r->amount ) ); ?></strong></td>
				<td data-label="Cost" class="zp-right"><?php echo esc_html( zp_usd( $r->cost ) ); ?></td>
				<td data-label="Status"><?php echo zp_badge( $r->status ); // phpcs:ignore ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php endif; ?>
</section>
