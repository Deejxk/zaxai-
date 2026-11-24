<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$uid = (int) zp_query( 'id' );
if ( $uid ) :
	$u = get_userdata( $uid );
	if ( ! $u ) {
		echo zp_empty( 'empty-tickets', 'Customer not found', '', zp_admin_url( 'users' ), 'Back to customers' ); // phpcs:ignore
		return;
	}
	$stats = ZP_Orders::user_stats( $uid );
	$tx    = ZP_Wallet::transactions( $uid, 12 );
	$ords  = ZP_Orders::user_orders( $uid, 8 );
	?>
	<a class="zp-back" href="<?php echo esc_url( zp_admin_url( 'users' ) ); ?>">← All customers</a>
	<div class="zp-grid2">
		<section class="zp-card zp-anim">
			<div class="zp-profile"><span class="zp-avatar zp-avatar--lg"><?php echo esc_html( strtoupper( mb_substr( $u->display_name, 0, 1 ) ) ); ?></span><div><strong><?php echo esc_html( $u->display_name ); ?> <span class="zp-sub" style="display:inline">#<?php echo (int) $u->ID; ?></span></strong><span><?php echo esc_html( $u->user_email ); ?> · Joined <?php echo esc_html( zp_date( $u->user_registered, false ) ); ?></span></div></div>
			<div class="zp-stats" style="grid-template-columns:repeat(3,minmax(0,1fr))">
				<div class="zp-stat"><span class="zp-stat__label">Balance</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( ZP_Wallet::balance( $uid ) ) ); ?></strong></div>
				<div class="zp-stat"><span class="zp-stat__label">Active proxies</span><strong class="zp-stat__value"><?php echo (int) $stats['active']; ?></strong></div>
				<div class="zp-stat"><span class="zp-stat__label">Spent</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( $stats['spent'] ) ); ?></strong></div>
			</div>
		</section>
		<section class="zp-card zp-anim" style="--d:1">
			<div class="zp-card__head"><h3>Adjust wallet</h3></div>
			<form method="post" class="zp-form">
				<?php zp_nonce_field( 'user_adjust' ); ?>
				<input type="hidden" name="user_id" value="<?php echo (int) $uid; ?>">
				<div class="zp-seg"><label><input type="radio" name="direction" value="credit" checked><span>Credit</span></label><label><input type="radio" name="direction" value="debit"><span>Debit</span></label></div>
				<label class="zp-field"><span>Amount (<?php echo esc_html( zp_get( 'currency_code' ) ); ?>)</span><div class="zp-input zp-input--amount"><b><?php echo esc_html( zp_get( 'currency_symbol' ) ); ?></b><input type="number" name="amount" step="0.01" min="0.01" required></div></label>
				<label class="zp-field"><span>Note shown to the customer</span><div class="zp-input"><input type="text" name="note" value="Balance adjustment" maxlength="150"></div></label>
				<button class="zp-btn zp-btn--primary" type="submit">Apply</button>
			</form>
		</section>
	</div>
	<div class="zp-grid2">
		<section class="zp-card zp-anim" style="--d:2"><div class="zp-card__head"><h3>Recent orders</h3></div>
			<ul class="zp-list"><?php foreach ( $ords as $o ) : ?><li><div class="zp-list__main"><strong>#<?php echo (int) $o->id; ?> · <?php echo esc_html( $o->qty . ' × ' . zp_type_label( $o->proxy_type ) . ' · ' . $o->country ); ?></strong><span><?php echo esc_html( zp_date( $o->created_at ) ); ?></span></div><div class="zp-list__side"><strong><?php echo esc_html( zp_money( $o->amount ) ); ?></strong><?php echo zp_badge( $o->status ); // phpcs:ignore ?></div></li><?php endforeach; ?></ul>
			<?php echo $ords ? '' : '<p class="zp-muted">No orders.</p>'; ?>
		</section>
		<section class="zp-card zp-anim" style="--d:3"><div class="zp-card__head"><h3>Wallet history</h3></div>
			<ul class="zp-list"><?php foreach ( $tx as $t ) : ?><li><div class="zp-list__main"><strong><?php echo esc_html( $t->description ? $t->description : ucfirst( $t->type ) ); ?></strong><span><?php echo esc_html( zp_date( $t->created_at ) ); ?></span></div><div class="zp-list__side"><strong class="<?php echo $t->amount >= 0 ? 'zp-pos' : ''; ?>"><?php echo esc_html( ( $t->amount >= 0 ? '+' : '' ) . zp_money( $t->amount ) ); ?></strong><span><?php echo esc_html( zp_money( $t->balance_after ) ); ?></span></div></li><?php endforeach; ?></ul>
			<?php echo $tx ? '' : '<p class="zp-muted">No transactions.</p>'; ?>
		</section>
	</div>
	<?php
	return;
endif;

$q     = zp_query( 'q' );
$per   = 25;
$pg    = zp_page_num();
$args  = array( 'number' => $per, 'offset' => ( $pg - 1 ) * $per, 'orderby' => 'registered', 'order' => 'DESC', 'count_total' => true );
if ( $q ) {
	$args['search']         = '*' . $q . '*';
	$args['search_columns'] = array( 'user_email', 'user_login', 'display_name' );
}
$query = new WP_User_Query( $args );
$w     = zp_table( 'wallets' );
?>
<form class="zp-toolbar zp-anim" method="get" action="<?php echo esc_url( zp_admin_url( 'users' ) ); ?>">
	<?php if ( ! zp_pretty() ) : ?><input type="hidden" name="zp" value="<?php echo esc_attr( ZP_ADMIN_BASE ); ?>"><input type="hidden" name="zpp" value="users"><?php endif; ?>
	<div class="zp-input zp-input--search"><?php echo zp_icon( 'search', 18 ); // phpcs:ignore ?><input type="search" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search name or email"></div>
	<button class="zp-btn zp-btn--soft" type="submit">Search</button>
</form>
<section class="zp-card zp-anim" style="--d:1">
	<div class="zp-tablewrap"><table class="zp-table">
		<thead><tr><th>Customer</th><th>Joined</th><th class="zp-right">Balance</th><th class="zp-right">Proxies</th><th></th></tr></thead>
		<tbody>
		<?php foreach ( $query->get_results() as $u ) : ?>
			<tr>
				<td data-label="Customer"><strong><?php echo esc_html( $u->display_name ); ?></strong><span class="zp-sub"><?php echo esc_html( $u->user_email ); ?> · #<?php echo (int) $u->ID; ?></span></td>
				<td data-label="Joined"><?php echo esc_html( zp_date( $u->user_registered, false ) ); ?></td>
				<td data-label="Balance" class="zp-right"><strong><?php echo esc_html( zp_money( ZP_Wallet::balance( $u->ID ) ) ); ?></strong></td>
				<td data-label="Proxies" class="zp-right"><?php echo (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'proxies' ) . ' WHERE user_id = %d AND date_end > %s', $u->ID, zp_now() ) ); ?></td>
				<td data-label="" class="zp-right"><a class="zp-btn zp-btn--soft zp-btn--sm" href="<?php echo esc_url( zp_admin_url( 'users', array( 'id' => $u->ID ) ) ); ?>">Manage</a></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table></div>
	<?php echo zp_paginate( $query->get_total(), $per, $pg ); // phpcs:ignore ?>
</section>
