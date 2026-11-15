<?php
/**
 * Hotspot owner dashboard (front-end, separate from wp-admin).
 */
defined( 'ABSPATH' ) || exit;

function zxc_dash_url( $page = '' ) {
	return home_url( '/dashboard' . ( $page ? '/' . $page : '' ) );
}

function zxc_dash_nav_groups() {
	return array(
		'Overview' => array( '' => array( 'dashboard', 'Dashboard' ), 'analytics' => array( 'chart', 'Analytics' ) ),
		'Sell'     => array( 'plans' => array( 'wifi', 'Plans' ), 'vouchers' => array( 'ticket', 'Vouchers' ), 'sales' => array( 'receipt', 'Sales' ), 'customers' => array( 'users', 'Customers' ) ),
		'Network'  => array( 'online' => array( 'zap', 'Online now' ), 'locations' => array( 'router', 'Locations & routers' ) ),
		'Money'    => array(
			'cwallets' => array( 'users', 'Customer wallets' ), 'wallet' => array( 'wallet', 'My wallet' ), 'payments' => array( 'card', 'Payment settings' ), 'billing' => array( 'star', 'Subscription' ) ),
		'Settings' => array( 'branding' => array( 'palette', 'Portal & branding' ), 'support' => array( 'message', 'Support' ), 'account' => array( 'user', 'Account' ) ),
	);
}

function zxc_dash_page( $t, $active, $title, $content, array $o = array() ) {
	global $wpdb;
	$sub    = zxc_subscription( $t );
	$online = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'online' ) . ' WHERE tenant_id = %d AND seen_at >= %d', $t->id, time() - 90 ) );
	$unread = zxc_unread_count( $t->id );
	$tiers  = zxc_tiers();
	$wbal   = zxc_wallet_balance( $t->id );
	$today  = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'transactions' ) . " WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d", $t->id, zxc_lagos_day_start() ) );
	$plan_l = 'trial' === $sub['state'] ? 'Free trial · ' . $sub['days'] . ' day' . ( 1 === $sub['days'] ? '' : 's' ) . ' left' : ( 'active' === $sub['state'] ? ( $tiers[ $t->tier ]['name'] ?? 'Business' ) . ' · renews in ' . $sub['days'] . 'd' : $sub['label'] );
	$help   = zxc_setting( 'support_whatsapp' ) ? 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', zxc_setting( 'support_whatsapp' ) ) ) : zxc_dash_url( 'support' );
	ob_start();
	?>
<div class="app">
  <aside class="side" data-side>
    <?php echo zxc_logo( zxc_dash_url() ); // phpcs:ignore ?>
    <a class="tenant" href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" style="<?php echo esc_attr( zxc_brand_style( $t ) ); ?>">
      <?php echo zxc_brand_mark( $t ); // phpcs:ignore ?>
      <span class="grow"><b><?php echo esc_html( $t->name ); ?></b><small>/h/<?php echo esc_html( $t->slug ); ?></small></span>
      <?php echo zxc_icon( 'switch' ); // phpcs:ignore ?>
    </a>
    <?php foreach ( zxc_dash_nav_groups() as $group => $items ) : ?>
      <div><div class="side-label"><?php echo esc_html( $group ); ?></div>
        <nav class="side-nav" aria-label="<?php echo esc_attr( $group ); ?>" style="margin-top:6px">
          <?php foreach ( $items as $key => $n ) : ?>
            <a href="<?php echo esc_url( zxc_dash_url( $key ) ); ?>" class="<?php echo $active === $key ? 'on' : ''; ?>"<?php echo $active === $key ? ' aria-current="page"' : ''; ?>><?php echo zxc_icon( $n[0] ); // phpcs:ignore ?><span><?php echo esc_html( $n[1] ); ?></span>
              <?php if ( 'online' === $key && $online ) : ?><span class="count"><?php echo (int) $online; ?></span><?php endif; ?>
              <?php if ( 'support' === $key && $unread ) : ?><span class="count accent"><?php echo (int) $unread; ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
        </nav>
      </div>
    <?php endforeach; ?>
    <div class="side-foot side-nav">
      <a href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>"><?php echo zxc_icon( 'globe' ); // phpcs:ignore ?><span>View my portal</span></a>
      <?php if ( zxc_is_platform_admin() ) : ?><a href="<?php echo esc_url( home_url( '/zx-admin' ) ); ?>"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?><span>Admin panel</span></a><?php endif; ?>
      <a href="#install" data-install><?php echo zxc_icon( 'download' ); // phpcs:ignore ?><span>Install app</span></a>
      <a href="<?php echo esc_url( home_url( '/logout' ) ); ?>"><?php echo zxc_icon( 'logout' ); // phpcs:ignore ?><span>Sign out</span></a>
    </div>
    <div class="bal-box">
      <div class="row between"><span class="side-label" style="padding:0">Your balance</span><span class="row" style="gap:6px"><button type="button" class="mini-btn" data-hide-toggle aria-label="Show or hide balances"><?php echo zxc_icon( 'eye' ); // phpcs:ignore ?></button><a class="mini-btn accent" href="<?php echo esc_url( zxc_dash_url( 'wallet' ) ); ?>" aria-label="Top up wallet"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></a></span></div>
      <a class="bal-row" href="<?php echo esc_url( zxc_dash_url( 'wallet' ) ); ?>"><span class="bi"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?></span><span><small>Wallet balance</small><b class="sens"><?php echo esc_html( zxc_money( $wbal ) ); ?></b></span></a>
      <a class="bal-row" href="<?php echo esc_url( zxc_dash_url( 'sales' ) ); ?>"><span class="bi teal"><?php echo zxc_icon( 'trend' ); // phpcs:ignore ?></span><span><small>Sales today</small><b class="sens"><?php echo esc_html( zxc_money( $today ) ); ?></b></span></a>
      <a class="bal-plan" href="<?php echo esc_url( zxc_dash_url( 'billing' ) ); ?>"><span class="dotp <?php echo $sub['active'] ? '' : 'off'; ?>"></span><?php echo esc_html( $plan_l ); ?><?php echo zxc_icon( 'chevron' ); // phpcs:ignore ?></a>
    </div>
  </aside>
  <div class="backdrop" data-backdrop></div>
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn menu-btn" data-menu aria-label="Open menu"><?php echo zxc_icon( 'menu' ); // phpcs:ignore ?></button>
      <div class="title"><h1><?php echo esc_html( $title ); ?></h1><?php if ( ! empty( $o['sub'] ) ) : ?><div class="sub"><?php echo esc_html( $o['sub'] ); ?></div><?php endif; ?></div>
      <?php if ( $unread ) : ?>
        <a class="top-pill desk" href="<?php echo esc_url( zxc_dash_url( 'support' ) ); ?>"><span class="tp-ic"><?php echo zxc_icon( 'message' ); // phpcs:ignore ?></span><?php echo (int) $unread; ?> new message<?php echo 1 === $unread ? '' : 's'; ?><b>View</b></a>
      <?php elseif ( 'active' !== $sub['state'] ) : ?>
        <a class="top-pill desk" href="<?php echo esc_url( zxc_dash_url( 'billing' ) ); ?>"><span class="tp-ic"><?php echo zxc_icon( 'star' ); // phpcs:ignore ?></span><?php echo esc_html( $plan_l ); ?><b>Upgrade</b></a>
      <?php endif; ?>
      <?php echo zxc_switch_buttons( 'dashboard', $t ); // phpcs:ignore ?>
      <a class="icon-btn" href="<?php echo esc_url( zxc_dash_url( 'support' ) ); ?>" aria-label="Messages" data-tip="Messages"><?php echo zxc_icon( 'bell', $unread ? 'a-bell' : '' ); // phpcs:ignore ?><?php if ( $unread ) : ?><span class="dot"></span><?php endif; ?></a>
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
      <?php echo $o['actions'] ?? ''; // phpcs:ignore ?>
    </header>
    <div class="content">
      <?php if ( ! $sub['active'] ) : ?><?php echo zxc_alert( 'warn', 'Your ' . strtolower( $sub['label'] ) . '. Vouchers already sold keep working, but online sales are paused. <a href="' . esc_url( zxc_dash_url( 'billing' ) ) . '">Choose a plan</a> to continue.', true ); // phpcs:ignore ?><?php endif; ?>
      <?php echo $content; // phpcs:ignore ?>
    </div>
  </div>
</div>
<a class="assist-fab" href="<?php echo esc_url( $help ); ?>" aria-label="Help and support" data-tip="Need help?"><?php echo zxc_logo_mark(); // phpcs:ignore ?></a>
	<?php
	echo zxc_toast( zxc_take_flash() ); // phpcs:ignore
	echo zxc_dock( // phpcs:ignore
		array(
			array( zxc_dash_url(), 'home', 'Home', '' === $active ),
			array( zxc_dash_url( 'sales' ), 'receipt', 'Sales', 'sales' === $active ),
			array( zxc_dash_url( 'vouchers' ), 'plus', 'Voucher', 'vouchers' === $active, true ),
			array( zxc_dash_url( 'online' ), 'zap', 'Online', 'online' === $active ),
			array( 'button:data-menu', 'menu', 'More', ! in_array( $active, array( '', 'sales', 'vouchers', 'online' ), true ) ),
		),
		'Dashboard'
	);
	return zxc_page( $title . ' · ' . $t->name, ob_get_clean(), array( 'body_class' => 'has-dock', 'robots' => 'noindex' ) );
}

function zxc_sales_table( $rows, $compact = false ) {
	if ( ! $rows ) {
		return '<div class="empty"><span class="ico lg">' . zxc_icon( 'receipt' ) . '</span><b>No sales yet</b><span>Share your portal link to get your first customers.</span></div>';
	}
	ob_start();
	?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Customer</th><th>Plan</th><?php if ( ! $compact ) : ?><th>Voucher</th><th>Reference</th><?php endif; ?><th>Status</th><th class="r">Amount</th></tr></thead>
  <tbody><?php foreach ( $rows as $r ) : ?><tr>
    <td><b><?php echo esc_html( zxc_mask_phone( $r->phone ) ); ?></b><small><?php echo esc_html( zxc_ago( $r->created ) . ( $r->channel ? ' · ' . ucwords( str_replace( '_', ' ', $r->channel ) ) : '' ) ); ?></small></td>
    <td><?php echo esc_html( $r->plan_name ); ?></td>
    <?php if ( ! $compact ) : ?><td class="mono"><?php echo esc_html( $r->voucher_code ? zxc_format_code( $r->voucher_code ) : '—' ); ?></td><td class="mono fine"><?php echo esc_html( $r->reference ); ?></td><?php endif; ?>
    <td><?php echo zxc_status_badge( $r->status ); // phpcs:ignore ?></td>
    <td class="r"><b><?php echo esc_html( zxc_money( $r->amount ) ); ?></b></td>
  </tr><?php endforeach; ?></tbody>
</table></div>
	<?php
	return ob_get_clean();
}

function zxc_view_dash_home( $t, array $s, array $an = array() ) {
	$user  = wp_get_current_user();
	$hour  = (int) wp_date( 'G', null, new DateTimeZone( 'Africa/Lagos' ) );
	$greet = $hour < 12 ? 'Good morning' : ( $hour < 17 ? 'Good afternoon' : 'Good evening' );
	$first = explode( ' ', trim( $user->display_name ) )[0];
	$delta = $s['yesterday']->total > 0 ? round( ( $s['today']->total - $s['yesterday']->total ) / $s['yesterday']->total * 100 ) : null;
	$series = array_map( function ( $d ) { return array( 'label' => $d['label'], 'total' => $d['total'], 'count' => $d['count'] ); }, $s['series'] );
	$router_ok = false;
	foreach ( $s['routers'] as $r ) {
		$router_ok = $router_ok || $r->last_seen >= time() - 120;
	}
	$checks = array(
		array( zxc_gateway_ready( $t ), 'card', 'Connect Flutterwave', 'Receive card, transfer and USSD payments.', zxc_dash_url( 'payments' ) ),
		array( (bool) array_filter( $s['routers'], function ( $r ) { return $r->last_seen > 0; } ), 'router', 'Connect your MikroTik router', 'Paste the setup script into your router.', zxc_dash_url( 'locations' ) ),
		array( '' !== $t->support_whatsapp || '' !== $t->support_phone || '' !== $t->logo_url, 'palette', 'Personalise your portal', 'Add your logo and support contacts.', zxc_dash_url( 'branding' ) ),
	);
	$done = count( array_filter( array_column( $checks, 0 ) ) );
	global $wpdb;
	$lm_start  = strtotime( '-1 month', zxc_lagos_month_start() );
	$last_m    = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COALESCE(SUM(amount),0) FROM ' . zxc_table( 'transactions' ) . " WHERE tenant_id = %d AND status = 'success' AND paid_at >= %d AND paid_at < %d", $t->id, $lm_start, zxc_lagos_month_start() ) );
	$month     = (int) $s['month']->total;
	$m_chg     = $last_m > 0 ? round( ( $month - $last_m ) / $last_m * 100 ) : null;
	$m_prog    = $last_m > 0 ? min( 100, round( $month / $last_m * 100 ) ) : ( $month > 0 ? 100 : 0 );
	$wbal      = zxc_wallet_balance( $t->id );
	$last_sale = $s['recent'] ? $s['recent'][0] : null;
	$slides    = array(
		array( 'printer', 'Voucher cards', 'Sell offline with printed vouchers', 'Generate a batch and print branded cards for your shop counter.', zxc_dash_url( 'vouchers' ), 'Generate vouchers', 'c1' ),
		array( 'wallet', 'Customer wallets', 'Let customers keep a Wi-Fi balance', 'Customers load once and buy plans in one tap. You can load cash top-ups too.', zxc_dash_url( 'cwallets' ), 'Open wallets', 'c2' ),
		array( 'card', 'Flutterwave payouts', 'Get paid straight to your bank', 'Connect Flutterwave to accept card, transfer and USSD on your portal.', zxc_dash_url( 'payments' ), 'Connect now', 'c3' ),
		array( 'palette', 'Your brand', 'Make the portal unmistakably yours', 'Upload your logo, choose colours and add WhatsApp support for customers.', zxc_dash_url( 'branding' ), 'Customise portal', 'c4' ),
	);
	ob_start();
	?>
<section class="welcome a-rise">
  <div class="wl-text">
    <span class="date-pill"><?php echo esc_html( wp_date( 'l, j F', null, new DateTimeZone( 'Africa/Lagos' ) ) ); ?></span>
    <h2><?php echo esc_html( $greet . ', ' . ( $first ? $first : 'there' ) ); ?></h2>
    <p><?php echo esc_html( $t->name ); ?><?php echo $t->location ? ' · ' . esc_html( $t->location ) : ''; ?></p>
    <div class="wl-stats">
      <span><i class="live <?php echo $router_ok ? '' : 'off'; ?>"></i><?php echo $router_ok ? 'Router online' : 'Router offline'; ?></span>
      <span><?php echo zxc_icon( 'zap' ); // phpcs:ignore ?><?php echo (int) $s['online']; ?> online now</span>
      <span><?php echo zxc_icon( 'receipt' ); // phpcs:ignore ?><?php echo (int) $s['today']->cnt; ?> sales today</span>
    </div>
  </div>
  <div class="orbit" aria-hidden="true">
    <span class="ring"></span><span class="ring r2"></span>
    <span class="core"><?php echo zxc_logo_mark(); // phpcs:ignore ?></span>
    <span class="sat s1"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?></span>
    <span class="sat s2"><?php echo zxc_icon( 'card' ); // phpcs:ignore ?></span>
    <span class="sat s3"><?php echo zxc_icon( 'users' ); // phpcs:ignore ?></span>
    <span class="sat s4"><?php echo zxc_icon( 'chart' ); // phpcs:ignore ?></span>
    <span class="orbit-pill"><?php echo zxc_icon( 'sparkles' ); // phpcs:ignore ?><?php echo $last_sale ? 'New sale ' . esc_html( zxc_money( $last_sale->amount ) ) : 'Ready to sell'; ?></span>
  </div>
</section>
<section class="card earn a-rise2">
  <div class="row between" style="align-items:flex-start">
    <div class="row"><span class="ico grad"><?php echo zxc_icon( 'sparkles' ); // phpcs:ignore ?></span><div><div class="card-title">Earnings this month</div><div class="card-sub">Revenue from all hotspot sales</div></div></div>
    <div class="row" style="gap:8px"><button type="button" class="icon-btn" data-hide-toggle aria-label="Show or hide balances"><?php echo zxc_icon( 'eye' ); // phpcs:ignore ?></button><a class="icon-btn" href="<?php echo esc_url( zxc_dash_url( 'vouchers' ) ); ?>" aria-label="New vouchers"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></a></div>
  </div>
  <div class="earn-fig"><b class="sens"><?php echo esc_html( zxc_money( $month ) ); ?></b><span>earned<small>Wallet balance · <span class="sens"><?php echo esc_html( zxc_money( $wbal ) ); ?></span></small></span></div>
  <div class="earn-track"><span class="a-growx" style="width:<?php echo (int) $m_prog; ?>%"></span></div>
  <div class="row between wrap-row" style="gap:8px"><span class="fine"><span class="hid-note">Balance details are hidden.</span><span class="shw-note"><?php echo null === $m_chg ? 'First month of sales tracking' : ( $m_chg >= 0 ? '+' : '' ) . (int) $m_chg . '% vs last month (' . esc_html( zxc_money_short( $last_m ) ) . ')'; ?></span></span><a class="link-u" href="<?php echo esc_url( zxc_dash_url( 'wallet' ) ); ?>">Top up wallet</a></div>
</section>
<section class="card featured a-rise3" data-carousel>
  <div class="row between"><span class="side-label" style="padding:0">Featured services</span>
    <div class="fc-ctrl"><button type="button" data-prev aria-label="Previous"><?php echo zxc_icon( 'back' ); // phpcs:ignore ?></button><span class="fc-dots"><?php foreach ( $slides as $i => $sl ) : ?><i class="<?php echo 0 === $i ? 'on' : ''; ?>"></i><?php endforeach; ?></span><button type="button" data-next aria-label="Next"><?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></button></div></div>
  <div class="fc-view"><div class="fc-track">
    <?php foreach ( $slides as $sl ) : ?>
      <div class="fc-slide">
        <span class="fc-art <?php echo esc_attr( $sl[6] ); ?>"><?php echo zxc_icon( $sl[0] ); // phpcs:ignore ?><i></i><i></i></span>
        <div class="grow"><div class="eyebrow"><?php echo esc_html( $sl[1] ); ?></div><h3><?php echo esc_html( $sl[2] ); ?></h3><p><?php echo esc_html( $sl[3] ); ?></p></div>
        <a class="link-u fc-go" href="<?php echo esc_url( $sl[4] ); ?>"><?php echo esc_html( $sl[5] ); ?> <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a>
      </div>
    <?php endforeach; ?>
  </div></div>
</section>
<?php if ( $done < count( $checks ) ) : ?>
<section class="card a-rise">
  <div class="card-head"><div><div class="card-title">Finish setting up</div><div class="card-sub"><?php echo (int) $done; ?> of <?php echo count( $checks ); ?> done — you’re almost live.</div></div><div class="bar" style="width:140px;margin-top:8px"><span style="width:<?php echo (int) round( $done / count( $checks ) * 100 ); ?>%"></span></div></div>
  <div class="checklist"><?php foreach ( $checks as $c ) : ?><div class="item <?php echo $c[0] ? 'done' : ''; ?>"><span class="cd"><?php echo $c[0] ? zxc_icon( 'check' ) : ''; // phpcs:ignore ?></span><span class="ico sm"><?php echo zxc_icon( $c[1] ); // phpcs:ignore ?></span><div class="grow"><b style="font-size:14.5px;font-weight:600"><?php echo esc_html( $c[2] ); ?></b><p><?php echo esc_html( $c[3] ); ?></p></div><?php if ( ! $c[0] ) : ?><a class="btn btn-soft btn-sm" href="<?php echo esc_url( $c[4] ); ?>">Set up</a><?php endif; ?></div><?php endforeach; ?></div>
</section>
<?php endif; ?>
<section class="kpis a-rise">
  <?php
  echo zxc_kpi( 'Revenue today', zxc_money( $s['today']->total ), ( null !== $delta ? '<b>' . ( $delta >= 0 ? '+' : '' ) . $delta . '%</b> vs yesterday · ' : '' ) . (int) $s['today']->cnt . ' sales', 'trend', '', true ); // phpcs:ignore
  echo zxc_kpi( 'This month', zxc_money_short( $s['month']->total ), (int) $s['month']->cnt . ' sales', 'wallet', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Online now', (string) $s['online'], 'connected devices', 'zap', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Active vouchers', (string) $s['active'], $s['unused'] . ' unused in stock', 'ticket', 'amber' ); // phpcs:ignore
  ?>
</section>
<?php if ( $an ) : ?>
<section class="card">
  <div class="card-head"><div><div class="card-title">Analytics snapshot</div><div class="card-sub">Last 30 days</div></div><a class="btn btn-soft btn-sm" href="<?php echo esc_url( zxc_dash_url( 'analytics' ) ); ?>"><?php echo zxc_icon( 'chart' ); // phpcs:ignore ?> Full analytics</a></div>
  <div class="insights">
    <div><span class="ico sm pink"><?php echo zxc_icon( 'target' ); // phpcs:ignore ?></span><small>Checkout conversion</small><b><?php echo null === $an['conversion'] ? '—' : (int) $an['conversion'] . '%'; ?></b></div>
    <div><span class="ico sm blue"><?php echo zxc_icon( 'users' ); // phpcs:ignore ?></span><small>Customers · returning</small><b><?php echo (int) $an['customers']; ?> · <?php echo (int) $an['returning']; ?></b></div>
    <div><span class="ico sm amber"><?php echo zxc_icon( 'clock' ); // phpcs:ignore ?></span><small>Peak hour · best day</small><b><?php echo null === $an['peak_hour'] ? '—' : esc_html( gmdate( 'ga', mktime( $an['peak_hour'], 0, 0 ) ) ); ?> · <?php echo esc_html( $an['best_day'] ? $an['best_day'] : '—' ); ?></b></div>
    <div><span class="ico sm teal"><?php echo zxc_icon( 'data' ); // phpcs:ignore ?></span><small>Data served</small><b><?php echo esc_html( zxc_bytes( $an['data_served'] ) ); ?></b></div>
    <div><span class="ico sm"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?></span><small>Revenue · vs prior</small><b><?php echo esc_html( zxc_money_short( $an['revenue'] ) ); ?> <?php echo null === $an['revenue_chg'] ? '' : '<span class="chg ' . ( $an['revenue_chg'] >= 0 ? 'up' : 'down' ) . '">' . ( $an['revenue_chg'] >= 0 ? '+' : '' ) . (int) $an['revenue_chg'] . '%</span>'; // phpcs:ignore ?></b></div>
  </div>
</section>
<?php endif; ?>
<div class="split">
  <section class="card wide">
    <div class="card-head"><div><div class="card-title">Revenue</div><div class="card-sub"><?php echo esc_html( zxc_money( array_sum( array_column( $series, 'total' ) ) ) . ' · last ' . $s['range'] . ' days' ); ?></div></div>
      <nav class="seg-sm" aria-label="Range"><?php foreach ( array( 7, 14, 30 ) as $r ) : ?><a href="<?php echo esc_url( zxc_dash_url() . '?range=' . $r ); ?>" class="<?php echo $s['range'] === $r ? 'on' : ''; ?>"><?php echo (int) $r; ?>d</a><?php endforeach; ?></nav></div>
    <?php echo zxc_bar_chart( $series ); // phpcs:ignore ?>
  </section>
  <div class="side-col">
    <section class="card">
      <div class="card-head"><div><div class="card-title">Top plans</div><div class="card-sub">Last 30 days</div></div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_dash_url( 'analytics' ) ); ?>">Analytics</a></div>
      <?php if ( $s['top'] ) : ?><div class="rank"><?php foreach ( $s['top'] as $p ) : ?><div><div class="rank-row"><span><?php echo esc_html( $p->name ); ?></span><b><?php echo esc_html( zxc_money( $p->total ) ); ?></b></div><div class="bar"><span class="a-growx" style="width:<?php echo (int) max( 4, round( $p->total / $s['top'][0]->total * 100 ) ); ?>%"></span></div><div class="fine"><?php echo (int) $p->cnt; ?> sales</div></div><?php endforeach; ?></div>
      <?php else : ?><div class="empty" style="padding:20px"><span class="ico"><?php echo zxc_icon( 'chart' ); // phpcs:ignore ?></span>No sales in the last 30 days.</div><?php endif; ?>
    </section>
    <section class="card">
      <div class="row"><span class="live <?php echo $router_ok ? '' : 'off'; ?>"></span><b style="font-weight:600"><?php echo $router_ok ? 'Router connected' : 'Router not connected'; ?></b></div>
      <div class="kv" style="margin-top:14px"><?php foreach ( $s['routers'] as $r ) : ?><div><span><?php echo esc_html( $r->name ); ?></span><b><?php echo esc_html( $r->last_seen ? 'seen ' . zxc_ago( $r->last_seen ) : 'waiting' ); ?></b></div><?php endforeach; ?></div>
      <a class="btn btn-glass btn-sm btn-block" style="margin-top:14px" href="<?php echo esc_url( zxc_dash_url( 'locations' ) ); ?>">Manage locations</a>
    </section>
  </div>
</div>
<div class="split">
  <section class="card flush wide"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div class="card-title">Recent sales</div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_dash_url( 'sales' ) ); ?>">View all</a></div><?php echo zxc_sales_table( $s['recent'], true ); // phpcs:ignore ?></section>
  <div class="side-col"><section class="card"><div class="card-title" style="margin-bottom:14px">Quick actions</div>
    <div class="links-list">
      <a href="<?php echo esc_url( zxc_dash_url( 'vouchers' ) ); ?>"><span class="ico sm"><?php echo zxc_icon( 'printer' ); // phpcs:ignore ?></span>Print voucher cards<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      <a href="<?php echo esc_url( zxc_dash_url( 'plans/new' ) ); ?>"><span class="ico sm teal"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></span>Add a new plan<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      <a href="<?php echo esc_url( zxc_dash_url( 'branding' ) ); ?>"><span class="ico sm pink"><?php echo zxc_icon( 'palette' ); // phpcs:ignore ?></span>Customise your portal<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      <a href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>"><span class="ico sm amber"><?php echo zxc_icon( 'globe' ); // phpcs:ignore ?></span>View my portal<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
    </div></section></div>
</div>
	<?php
	return zxc_dash_page( $t, '', 'Dashboard', ob_get_clean(), array( 'sub' => 'Here’s how ' . $t->name . ' is doing.', 'actions' => '<a class="btn btn-primary desk" href="' . esc_url( zxc_dash_url( 'vouchers' ) ) . '">' . zxc_icon( 'plus' ) . 'New vouchers</a>' ) );
}

function zxc_chg( $v, $suffix = '%', $label = 'vs previous period' ) {
	if ( null === $v ) {
		return '<span class="chg flat">New</span> <span class="faint">' . esc_html( $label ) . '</span>';
	}
	$cls = $v > 0 ? 'up' : ( $v < 0 ? 'down' : 'flat' );
	return '<span class="chg ' . $cls . '">' . ( $v > 0 ? '▲ +' : ( $v < 0 ? '▼ ' : '' ) ) . esc_html( $v . $suffix ) . '</span> <span class="faint">' . esc_html( $label ) . '</span>';
}

function zxc_donut( array $parts, $center, $sub ) {
	$total = array_sum( array_column( $parts, 1 ) );
	$stops = array();
	$acc   = 0;
	foreach ( $parts as $p ) {
		$from    = $total ? $acc / $total * 100 : 0;
		$acc    += $p[1];
		$to      = $total ? $acc / $total * 100 : 0;
		$stops[] = $p[2] . ' ' . round( $from, 2 ) . '% ' . round( $to, 2 ) . '%';
	}
	$bg = $total ? 'conic-gradient(' . implode( ',', $stops ) . ')' : 'conic-gradient(var(--track) 0 100%)';
	$h  = '<div class="donut-wrap"><div class="donut" style="background:' . esc_attr( $bg ) . '"><div><b>' . esc_html( $center ) . '</b><small>' . esc_html( $sub ) . '</small></div></div><div class="legend">';
	foreach ( $parts as $p ) {
		$h .= '<div><span class="sw" style="background:' . esc_attr( $p[2] ) . '"></span><span class="grow">' . esc_html( $p[0] ) . '</span><b>' . esc_html( $p[3] ?? $p[1] ) . '</b></div>';
	}
	return $h . '</div></div>';
}

function zxc_view_dash_analytics( $t, array $a ) {
	$chan_names = array( 'card' => 'Card', 'bank_transfer' => 'Bank transfer', 'ussd' => 'USSD', 'demo' => 'Test payments', 'other' => 'Other', 'account' => 'Bank account', 'mobilemoneyng' => 'Mobile money' );
	$max_h      = max( 1, max( $a['hours'] ) );
	$v          = $a['vouchers'];
	$issued     = (int) $v->issued;
	$plan_total = max( 1, array_sum( array_map( function ( $p ) { return (int) $p->total; }, $a['plans'] ) ) );
	$fmt_h      = function ( $h ) { return null === $h ? '—' : gmdate( 'ga', mktime( $h, 0, 0 ) ); };
	$avg_min    = (int) round( $a['sessions']->avg_secs / 60 );
	ob_start();
	?>
<div class="row between wrap-row"><div class="fine">Showing the last <b style="color:var(--ink)"><?php echo (int) $a['days']; ?> days</b> compared with the <?php echo (int) $a['days']; ?> days before.</div>
  <nav class="seg-sm" aria-label="Date range"><?php foreach ( array( 7, 30, 90 ) as $r ) : ?><a href="<?php echo esc_url( zxc_dash_url( 'analytics' ) . '?range=' . $r ); ?>" class="<?php echo $a['days'] === $r ? 'on' : ''; ?>"><?php echo (int) $r; ?> days</a><?php endforeach; ?></nav></div>

<section class="kpis a-rise">
  <?php
  echo zxc_kpi( 'Revenue', zxc_money( $a['revenue'] ), zxc_chg( $a['revenue_chg'] ), 'wallet', '', true ); // phpcs:ignore
  echo zxc_kpi( 'Sales', (string) $a['sales'], zxc_chg( $a['sales_chg'] ), 'receipt', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Customers', (string) $a['customers'], zxc_chg( $a['cust_chg'] ), 'users', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Average sale', zxc_money( $a['avg'] ), zxc_chg( $a['avg_chg'] ), 'tag', 'amber' ); // phpcs:ignore
  ?>
</section>
<section class="kpis">
  <?php
  echo zxc_kpi( 'Checkout conversion', null === $a['conversion'] ? '—' : $a['conversion'] . '%', null === $a['conversion'] ? 'no checkouts yet' : zxc_chg( $a['conv_chg'], ' pts' ), 'target', 'pink' ); // phpcs:ignore
  echo zxc_kpi( 'Revenue per day', zxc_money( $a['per_day'] ), 'average', 'trend', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Data served', zxc_bytes( $a['data_served'] ), 'by active vouchers', 'data', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Avg. session', $avg_min ? $avg_min . ' min' : '—', (int) $a['sessions']->devices . ' unique devices', 'clock', 'amber' ); // phpcs:ignore
  ?>
</section>

<section class="card"><div class="card-head"><div><div class="card-title">Revenue trend</div><div class="card-sub">Daily revenue · <?php echo esc_html( zxc_money( $a['revenue'] ) ); ?> total</div></div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_dash_url( 'sales/export' ) ); ?>"><?php echo zxc_icon( 'download' ); // phpcs:ignore ?> CSV</a></div><?php echo zxc_bar_chart( $a['daily'] ); // phpcs:ignore ?></section>

<div class="grid g2">
  <section class="card"><div class="card-title">Customers</div><div class="card-sub" style="margin-bottom:18px">New vs returning in this period</div>
    <?php echo zxc_donut( array( array( 'New customers', $a['new_cust'], 'var(--accent)' ), array( 'Returning customers', $a['returning'], 'var(--accent-3)' ) ), (string) $a['customers'], 'customers' ); // phpcs:ignore ?>
    <div class="kv" style="margin-top:18px"><div><span>Bought more than once</span><b><?php echo (int) $a['repeat']; ?></b></div><div><span>Repeat rate</span><b><?php echo $a['customers'] ? (int) round( $a['repeat'] / $a['customers'] * 100 ) . '%' : '—'; ?></b></div></div>
  </section>
  <section class="card"><div class="card-title">Checkout funnel</div><div class="card-sub" style="margin-bottom:18px">Customers who started paying</div>
    <?php $st = max( 1, (int) $a['orders']->started ); ?>
    <div class="funnel">
      <?php foreach ( array( array( 'Started checkout', (int) $a['orders']->started, 'var(--accent-3)' ), array( 'Paid', (int) $a['orders']->paid, 'var(--accent)' ), array( 'Failed or abandoned', (int) $a['orders']->started - (int) $a['orders']->paid, 'var(--bad)' ) ) as $f ) : ?>
        <div><div class="row between"><span><?php echo esc_html( $f[0] ); ?></span><b><?php echo (int) $f[1]; ?> <span class="faint" style="font-weight:500">· <?php echo (int) round( $f[1] / $st * 100 ); ?>%</span></b></div><div class="bar"><span class="a-growx" style="width:<?php echo (int) round( $f[1] / $st * 100 ); ?>%;background:<?php echo esc_attr( $f[2] ); ?>"></span></div></div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="grid g2">
  <section class="card"><div class="card-head"><div><div class="card-title">Busiest hours</div><div class="card-sub">Sales by hour (Lagos time)</div></div><?php if ( null !== $a['peak_hour'] ) : ?><span class="badge badge-soft">Peak <?php echo esc_html( $fmt_h( $a['peak_hour'] ) ); ?></span><?php endif; ?></div>
    <div class="hours"><?php foreach ( $a['hours'] as $h => $n ) : ?><span class="a-grow" title="<?php echo esc_attr( sprintf( '%s · %d sales', $fmt_h( $h ), $n ) ); ?>" style="height:<?php echo esc_attr( max( 4, round( $n / $max_h * 100 ) ) ); ?>%;animation-delay:<?php echo esc_attr( $h * 0.02 ); ?>s;<?php echo $h === $a['peak_hour'] ? 'background:linear-gradient(180deg,var(--accent-2),var(--accent))' : 'opacity:.55'; ?>"></span><?php endforeach; ?></div>
    <div class="legend-x"><span>12am</span><span>6am</span><span>12pm</span><span>6pm</span><span>11pm</span></div></section>
  <section class="card"><div class="card-head"><div><div class="card-title">Best days</div><div class="card-sub">Revenue by day of the week</div></div><?php if ( $a['best_day'] ) : ?><span class="badge badge-soft">Best: <?php echo esc_html( $a['best_day'] ); ?></span><?php endif; ?></div>
    <?php $maxd = max( 1, max( array_column( $a['dow'], 'total' ) ) ); ?>
    <div class="dow"><?php foreach ( $a['dow'] as $d ) : ?><div><span class="dbar"><i class="a-grow" style="height:<?php echo esc_attr( max( 4, round( $d['total'] / $maxd * 100 ) ) ); ?>%"></i></span><small><?php echo esc_html( $d['label'] ); ?></small><b><?php echo esc_html( zxc_money_short( $d['total'] ) ); ?></b></div><?php endforeach; ?></div></section>
</div>

<section class="card flush"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div><div class="card-title">Plan performance</div><div class="card-sub">Which plans earn the most</div></div></div>
  <div class="table-wrap"><table class="table"><thead><tr><th>Plan</th><th>Price</th><th>Sales</th><th>Share of revenue</th><th class="r">Revenue</th></tr></thead><tbody>
  <?php foreach ( $a['plans'] as $p ) : ?><?php $share = (int) round( $p->total / $plan_total * 100 ); ?><tr><td><b><?php echo esc_html( $p->name ); ?></b></td><td><?php echo esc_html( zxc_money( $p->price ) ); ?></td><td><?php echo (int) $p->cnt; ?></td><td style="min-width:160px"><div class="row" style="gap:10px"><div class="bar grow" style="height:8px"><span style="width:<?php echo (int) $share; ?>%"></span></div><span class="fine" style="width:36px"><?php echo (int) $share; ?>%</span></div></td><td class="r"><b><?php echo esc_html( zxc_money( $p->total ) ); ?></b></td></tr><?php endforeach; ?>
  </tbody></table></div></section>

<div class="grid g2">
  <section class="card"><div class="card-title">Payment methods</div><div class="card-sub" style="margin-bottom:18px">How customers pay</div>
    <?php if ( $a['channels'] ) : ?>
      <?php $palette = array( 'var(--accent)', 'var(--accent-3)', '#F59E0B', '#EC4899', '#10B981' ); $parts = array(); foreach ( $a['channels'] as $i => $c ) { $parts[] = array( $chan_names[ $c->channel ] ?? ucwords( str_replace( '_', ' ', $c->channel ) ), (int) $c->total, $palette[ $i % 5 ], zxc_money_short( $c->total ) ); } ?>
      <?php echo zxc_donut( $parts, (string) array_sum( array_map( function ( $c ) { return (int) $c->cnt; }, $a['channels'] ) ), 'payments' ); // phpcs:ignore ?>
    <?php else : ?><div class="empty" style="padding:20px">No payments in this period.</div><?php endif; ?></section>
  <section class="card"><div class="card-title">Voucher journey</div><div class="card-sub" style="margin-bottom:18px">Vouchers created in this period</div>
    <div class="funnel">
      <?php foreach ( array( array( 'Issued', $issued ), array( 'Used (activated)', (int) $v->activated ), array( 'Finished', (int) $v->finished ) ) as $f ) : ?>
        <div><div class="row between"><span><?php echo esc_html( $f[0] ); ?></span><b><?php echo (int) $f[1]; ?></b></div><div class="bar"><span class="a-growx" style="width:<?php echo (int) ( $issued ? round( $f[1] / $issued * 100 ) : 0 ); ?>%"></span></div></div>
      <?php endforeach; ?>
    </div>
    <div class="kv" style="margin-top:18px"><div><span>Sold online</span><b><?php echo (int) $v->online; ?></b></div><div><span>Printed cards</span><b><?php echo (int) $v->printed; ?></b></div><div><span>Avg. data per voucher</span><b><?php echo esc_html( zxc_bytes( $v->avg_bytes ) ); ?></b></div></div></section>
</div>

<div class="grid g2">
  <section class="card flush"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div><div class="card-title">Locations</div><div class="card-sub">Sessions and data per router</div></div></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Location</th><th>Sessions</th><th>Devices</th><th class="r">Data</th></tr></thead><tbody><?php foreach ( $a['locations'] as $l ) : ?><tr><td><span class="row" style="gap:8px"><span class="live <?php echo $l->last_seen >= time() - 120 ? '' : 'off'; ?>"></span><b><?php echo esc_html( $l->name ); ?></b></span></td><td><?php echo (int) $l->sessions; ?></td><td><?php echo (int) $l->devices; ?></td><td class="r"><?php echo esc_html( zxc_bytes( $l->bytes ) ); ?></td></tr><?php endforeach; ?></tbody></table></div></section>
  <section class="card flush"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div><div class="card-title">Top customers</div><div class="card-sub">Highest spenders in this period</div></div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_dash_url( 'customers' ) ); ?>">All</a></div>
    <?php if ( $a['top_customers'] ) : ?><div class="table-wrap"><table class="table"><tbody><?php foreach ( $a['top_customers'] as $i => $c ) : ?><tr><td><div class="row" style="gap:10px"><span class="avatar"><?php echo (int) $i + 1; ?></span><div><b><?php echo esc_html( zxc_mask_phone( $c->phone ) ); ?></b><small><?php echo (int) $c->cnt; ?> purchases · <?php echo esc_html( zxc_ago( $c->last_paid ) ); ?></small></div></div></td><td class="r"><b><?php echo esc_html( zxc_money( $c->total ) ); ?></b></td></tr><?php endforeach; ?></tbody></table></div>
    <?php else : ?><div class="empty" style="padding:24px">No customers in this period.</div><?php endif; ?></section>
</div>
	<?php
	return zxc_dash_page( $t, 'analytics', 'Analytics', ob_get_clean(), array( 'sub' => 'Detailed insight into sales, customers and your network.' ) );
}

function zxc_view_dash_customers( $t, $rows, $q, $wmap = array() ) {
	ob_start();
	?>
<section class="card flush">
  <form class="filters" method="get" style="padding:18px 22px"><input class="input" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search by phone number"><button class="btn btn-glass" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Search</button></form>
  <?php if ( $rows ) : ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Customer</th><th>Purchases</th><th>Wallet</th><th class="hide-sm">Last plan</th><th class="hide-sm">First seen</th><th>Last purchase</th><th class="r">Total spent</th></tr></thead><tbody>
    <?php foreach ( $rows as $c ) : ?><tr>
      <td><div class="row" style="gap:10px"><span class="avatar"><?php echo esc_html( substr( preg_replace( '/\D/', '', (string) $c->phone ), -2 ) ); ?></span><div><b><?php echo esc_html( zxc_mask_phone( $c->phone ) ); ?></b><small><?php echo (int) $c->purchases > 1 ? 'Repeat customer' : 'New customer'; ?></small></div></div></td>
      <td><?php echo (int) $c->purchases; ?></td><td><?php echo isset( $wmap[ $c->phone ] ) ? '<span class="badge badge-soft">' . esc_html( zxc_money( $wmap[ $c->phone ] ) ) . '</span>' : '<span class="faint">—</span>'; // phpcs:ignore ?></td><td class="hide-sm"><?php echo esc_html( $c->last_plan ); ?></td><td class="fine hide-sm"><?php echo esc_html( zxc_date( $c->first_paid ) ); ?></td><td class="fine"><?php echo esc_html( zxc_ago( $c->last_paid ) ); ?></td>
      <td class="r"><b><?php echo esc_html( zxc_money( $c->spent ) ); ?></b></td>
    </tr><?php endforeach; ?></tbody></table></div>
  <?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'users' ); // phpcs:ignore ?></span><b>No customers yet</b><span>Customers appear here after their first purchase.</span></div><?php endif; ?>
</section>
	<?php
	return zxc_dash_page( $t, 'customers', 'Customers', ob_get_clean(), array( 'sub' => count( $rows ) . ' customers who bought online.' ) );
}

function zxc_view_dash_plans( $t, $plans ) {
	ob_start();
	?>
<div class="plan-grid">
  <?php foreach ( $plans as $p ) : ?>
    <article class="card plan-card hover-lift <?php echo $p->active ? '' : 'off'; ?>">
      <div class="row between" style="align-items:flex-start"><div><b style="font-family:var(--display);font-size:17px;font-weight:650"><?php echo esc_html( $p->name ); ?></b><div class="fine"><?php echo esc_html( $p->description ); ?></div></div><div class="row" style="gap:6px;flex-wrap:wrap;justify-content:flex-end"><?php if ( $p->popular ) : ?><span class="badge badge-soft">Popular</span><?php endif; ?><span class="badge <?php echo $p->active ? 'badge-ok' : ''; ?>"><?php echo $p->active ? 'Live' : 'Hidden'; ?></span></div></div>
      <div class="price"><?php echo esc_html( zxc_money( $p->price ) ); ?></div>
      <div class="spec">
        <div><span>Validity</span><b><?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></b></div>
        <div><span>Data</span><b><?php echo esc_html( $p->data_mb ? str_replace( ' data', '', zxc_data_label( $p->data_mb ) ) : 'Unlimited' ); ?></b></div>
        <div><span>Speed</span><b><?php echo esc_html( (float) $p->download_mbps > 0 ? ( 0 + $p->download_mbps ) . '↓ / ' . ( (float) $p->upload_mbps > 0 ? 0 + $p->upload_mbps : '∞' ) . '↑ Mbps' : 'Unlimited' ); ?></b></div>
        <div><span>Devices</span><b><?php echo (int) $p->devices; ?></b></div>
        <div><span>Sold</span><b><?php echo (int) $p->sold; ?></b></div>
      </div>
      <div class="row"><a class="btn btn-soft btn-sm" href="<?php echo esc_url( zxc_dash_url( 'plans/' . $p->id ) ); ?>">Edit</a><form method="post"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="plan_toggle"><input type="hidden" name="plan_id" value="<?php echo (int) $p->id; ?>"><button class="btn btn-ghost btn-sm" type="submit"><?php echo $p->active ? 'Hide' : 'Publish'; ?></button></form></div>
    </article>
  <?php endforeach; ?>
  <a class="card plan-card hover-lift center" style="place-content:center;justify-items:center;border-style:dashed;min-height:240px" href="<?php echo esc_url( zxc_dash_url( 'plans/new' ) ); ?>"><span class="ico lg"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></span><b style="font-family:var(--display)">Add a plan</b><span class="fine">Hourly, daily, bundles…</span></a>
</div>
	<?php
	return zxc_dash_page( $t, 'plans', 'Plans', ob_get_clean(), array( 'sub' => 'What customers can buy on your portal.', 'actions' => '<a class="btn btn-primary" href="' . esc_url( zxc_dash_url( 'plans/new' ) ) . '">' . zxc_icon( 'plus' ) . '<span class="desk">New plan</span></a>' ) );
}

function zxc_view_dash_plan_form( $t, $plan ) {
	$p      = $plan ? $plan : (object) array( 'id' => 0, 'name' => '', 'description' => '', 'price' => '', 'duration_min' => 60, 'data_mb' => 0, 'download_mbps' => 5, 'upload_mbps' => 2, 'devices' => 1, 'popular' => 0, 'active' => 1, 'sort_order' => 0 );
	$unit   = 0 === $p->duration_min % 1440 ? 'days' : ( 0 === $p->duration_min % 60 ? 'hours' : 'minutes' );
	$amount = 'days' === $unit ? $p->duration_min / 1440 : ( 'hours' === $unit ? $p->duration_min / 60 : $p->duration_min );
	ob_start();
	?>
<form method="post" class="card form-grid" action="<?php echo esc_url( zxc_dash_url( 'plans' ) ); ?>">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="plan_save"><input type="hidden" name="plan_id" value="<?php echo (int) $p->id; ?>">
  <label class="field span-2">Plan name<input class="input" name="name" value="<?php echo esc_attr( $p->name ); ?>" placeholder="e.g. Daily Pass" required maxlength="80"></label>
  <label class="field span-2">Short description<input class="input" name="description" value="<?php echo esc_attr( $p->description ); ?>" placeholder="e.g. Unlimited access for a full day" maxlength="160"></label>
  <label class="field">Price (₦)<input class="input" type="number" name="price" min="0" value="<?php echo esc_attr( $p->price ); ?>" required></label>
  <div class="field">Validity <em>starts at first login</em><div class="row" style="gap:8px"><input class="input" type="number" name="duration_value" min="1" value="<?php echo esc_attr( $amount ); ?>" required><select class="input" name="duration_unit" style="max-width:140px"><?php foreach ( array( 'minutes', 'hours', 'days' ) as $u ) : ?><option <?php selected( $u, $unit ); ?>><?php echo esc_html( $u ); ?></option><?php endforeach; ?></select></div></div>
  <label class="field">Data cap (MB) <em>0 = unlimited</em><input class="input" type="number" name="data_mb" min="0" value="<?php echo esc_attr( $p->data_mb ); ?>"></label>
  <label class="field">Devices per voucher<input class="input" type="number" name="devices" min="1" max="10" value="<?php echo esc_attr( $p->devices ); ?>"></label>
  <label class="field">Download speed (Mbps) <em>0 = unlimited</em><input class="input" type="number" step="0.5" name="download_mbps" min="0" value="<?php echo esc_attr( 0 + $p->download_mbps ); ?>"></label>
  <label class="field">Upload speed (Mbps) <em>0 = unlimited</em><input class="input" type="number" step="0.5" name="upload_mbps" min="0" value="<?php echo esc_attr( 0 + $p->upload_mbps ); ?>"></label>
  <label class="field">Display order<input class="input" type="number" name="sort_order" value="<?php echo esc_attr( $p->sort_order ); ?>"></label>
  <div class="field">Options<label class="check"><input type="checkbox" name="active" value="1" <?php checked( (int) $p->active, 1 ); ?>> Show on portal</label><label class="check"><input type="checkbox" name="popular" value="1" <?php checked( (int) $p->popular, 1 ); ?>> Highlight as “Popular”</label></div>
  <div class="span-2 actions"><a class="btn btn-ghost" href="<?php echo esc_url( zxc_dash_url( 'plans' ) ); ?>">Cancel</a><button class="btn btn-primary" type="submit"><?php echo $plan ? 'Save changes' : 'Create plan'; ?></button></div>
</form>
	<?php
	return zxc_dash_page( $t, 'plans', $plan ? 'Edit ' . $plan->name : 'New plan', ob_get_clean() );
}

function zxc_view_dash_vouchers( $t, array $a ) {
	$allowance = zxc_voucher_allowance( $t );
	ob_start();
	?>
<div class="split">
  <section class="card flush wide">
    <form class="filters" method="get" style="padding:18px 22px">
      <select class="input" name="status" onchange="this.form.submit()"><?php foreach ( array( 'all' => 'All vouchers', 'unused' => 'Unused', 'active' => 'Active', 'expired' => 'Expired', 'disabled' => 'Disabled' ) as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $a['status'], $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
      <input class="input" name="q" value="<?php echo esc_attr( $a['q'] ); ?>" placeholder="Code, phone or batch">
      <button class="btn btn-glass" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?><span class="sr">Search</span></button>
    </form>
    <?php if ( $a['rows'] ) : ?>
      <div class="table-wrap"><table class="table"><thead><tr><th>Code</th><th>Plan</th><th>Status</th><th>Usage</th><th>Time left</th><th></th></tr></thead><tbody>
      <?php foreach ( $a['rows'] as $v ) : ?><tr>
        <td><b class="mono"><?php echo esc_html( zxc_format_code( $v->code ) ); ?></b><small><?php echo esc_html( 'online' === $v->source ? ( $v->phone ? zxc_mask_phone( $v->phone ) : 'Online sale' ) : 'Batch ' . $v->batch ); ?></small></td>
        <td><?php echo esc_html( $v->plan_name ); ?></td>
        <td><?php echo zxc_status_badge( $v->status ); // phpcs:ignore ?></td>
        <td><?php echo esc_html( zxc_bytes( $v->bytes_used ) ); ?><small><?php echo esc_html( $v->mac ? $v->mac : 'No device yet' ); ?></small></td>
        <td class="fine"><?php echo esc_html( $v->expires_at ? ( $v->expires_at > time() ? zxc_remaining( $v->expires_at - time() ) : 'Ended ' . zxc_date( $v->expires_at ) ) : 'Not started' ); ?></td>
        <td class="r"><form method="post"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="voucher_toggle"><input type="hidden" name="voucher_id" value="<?php echo (int) $v->id; ?>"><button class="btn btn-ghost btn-sm" type="submit"><?php echo 'disabled' === $v->status ? 'Enable' : 'Disable'; ?></button></form></td>
      </tr><?php endforeach; ?></tbody></table></div>
    <?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'ticket' ); // phpcs:ignore ?></span><b>No vouchers match</b><span>Generate cards or make your first online sale.</span></div><?php endif; ?>
  </section>
  <div class="side-col">
    <section class="card"><div class="row"><span class="ico"><?php echo zxc_icon( 'printer' ); // phpcs:ignore ?></span><div><div class="card-title">Generate voucher cards</div><div class="card-sub">Sell offline through attendants or shops.</div></div></div>
      <form method="post" class="stack" style="margin-top:18px"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="vouchers_generate">
        <label class="field">Plan<select class="input" name="plan_id" required><?php foreach ( $a['plans'] as $p ) : ?><option value="<?php echo (int) $p->id; ?>"><?php echo esc_html( $p->name . ' · ' . zxc_money( $p->price ) ); ?></option><?php endforeach; ?></select></label>
        <label class="field">Quantity<input class="input" type="number" name="quantity" min="1" max="<?php echo (int) min( 500, $allowance ); ?>" value="<?php echo (int) min( 20, $allowance ); ?>" required></label>
        <?php if ( PHP_INT_MAX !== $allowance ) : ?><p class="fine"><?php echo (int) $allowance; ?> vouchers left this month on your plan.</p><?php endif; ?>
        <button class="btn btn-primary btn-block" type="submit" <?php disabled( $allowance < 1 ); ?>><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?> Generate</button>
      </form></section>
    <section class="card"><div class="card-title" style="margin-bottom:14px">Recent batches</div>
      <?php if ( $a['batches'] ) : ?><div class="links-list"><?php foreach ( $a['batches'] as $b ) : ?><a href="<?php echo esc_url( zxc_dash_url( 'vouchers/print/' . rawurlencode( $b->batch ) ) ); ?>"><span class="ico sm"><?php echo zxc_icon( 'printer' ); // phpcs:ignore ?></span><span class="grow"><b style="display:block;font-weight:600"><?php echo esc_html( $b->batch ); ?></b><small class="fine"><?php echo esc_html( $b->plan_name . ' · ' . $b->cnt . ' cards · ' . (int) $b->unused . ' unused' ); ?></small></span><?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a><?php endforeach; ?></div>
      <?php else : ?><div class="empty" style="padding:16px">No batches yet.</div><?php endif; ?></section>
  </div>
</div>
	<?php
	return zxc_dash_page( $t, 'vouchers', 'Vouchers', ob_get_clean(), array( 'sub' => 'Every voucher sold online or printed.' ) );
}

function zxc_view_dash_print( $t, $batch, $rows ) {
	ob_start();
	?>
<div class="print-bar"><b><?php echo count( $rows ); ?> voucher cards · <?php echo esc_html( $batch ); ?></b><div class="row"><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_dash_url( 'vouchers' ) ); ?>"><?php echo zxc_icon( 'back' ); // phpcs:ignore ?> Back</a><button class="btn btn-primary btn-sm" onclick="window.print()"><?php echo zxc_icon( 'printer' ); // phpcs:ignore ?> Print</button></div></div>
<div class="sheet"><?php foreach ( $rows as $v ) : ?><div class="vcard">
  <div class="row between"><span class="brand"><?php echo zxc_brand_mark( $t ); // phpcs:ignore ?><b style="font-size:13px;font-weight:600"><?php echo esc_html( $t->name ); ?></b></span><b style="font-family:var(--display);font-size:17px"><?php echo esc_html( zxc_money( $v->price ) ); ?></b></div>
  <div style="font-size:12px;font-weight:500;color:#586085"><?php echo esc_html( $v->plan_name . ' · ' . zxc_duration( $v->duration_min ) . ( $v->data_mb ? ' · ' . zxc_data_label( $v->data_mb ) : '' ) ); ?></div>
  <div class="vcard-code"><?php echo esc_html( zxc_format_code( $v->code ) ); ?></div>
  <div style="font-size:10.5px;color:#586085">Join <b><?php echo esc_html( $t->name ); ?></b> Wi-Fi → tap “Voucher” → enter code.</div>
</div><?php endforeach; ?></div>
	<?php
	return zxc_page( 'Voucher cards ' . $batch, ob_get_clean(), array( 'body_class' => 'print-body', 'style' => zxc_brand_style( $t ), 'robots' => 'noindex', 'aurora' => false ) );
}

function zxc_view_dash_sales( $t, array $a ) {
	ob_start();
	?>
<section class="kpis">
  <?php
  echo zxc_kpi( 'Successful sales', (string) $a['totals']->cnt, 'all time', 'checkCirc', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Total revenue', zxc_money( $a['totals']->total ), 'all time', 'trend', '', true ); // phpcs:ignore
  echo zxc_kpi( 'Average sale', zxc_money( $a['totals']->cnt ? round( $a['totals']->total / $a['totals']->cnt ) : 0 ), 'per transaction', 'receipt', 'amber' ); // phpcs:ignore
  ?>
</section>
<section class="card flush">
  <form class="filters" method="get" style="padding:18px 22px">
    <select class="input" name="status" onchange="this.form.submit()"><?php foreach ( array( 'all' => 'All statuses', 'success' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed' ) as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $a['status'], $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select>
    <input class="input" name="q" value="<?php echo esc_attr( $a['q'] ); ?>" placeholder="Search phone or reference">
    <button class="btn btn-glass" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?><span class="sr">Search</span></button>
  </form>
  <?php echo zxc_sales_table( $a['rows'] ); // phpcs:ignore ?>
</section>
	<?php
	return zxc_dash_page( $t, 'sales', 'Sales', ob_get_clean(), array( 'actions' => '<a class="btn btn-glass" href="' . esc_url( zxc_dash_url( 'sales/export' ) ) . '">' . zxc_icon( 'download' ) . '<span class="desk">Export CSV</span></a>' ) );
}

function zxc_view_dash_online( $t, $rows ) {
	ob_start();
	?>
<section class="card flush">
  <?php if ( $rows ) : ?>
    <div class="table-wrap"><table class="table"><thead><tr><th>Voucher</th><th>Plan</th><th>Device</th><th>Location</th><th class="r">Data used</th><th>Time left</th></tr></thead><tbody>
    <?php foreach ( $rows as $r ) : ?><tr><td><div class="row" style="gap:10px"><span class="live"></span><div><b class="mono"><?php echo esc_html( zxc_format_code( $r->code ) ); ?></b><small>online <?php echo esc_html( zxc_ago( $r->first_seen ) ); ?></small></div></div></td><td><?php echo esc_html( $r->plan_name ? $r->plan_name : '—' ); ?></td><td class="mono fine"><?php echo esc_html( $r->mac ); ?></td><td><?php echo esc_html( $r->router_name ); ?></td><td class="r"><?php echo esc_html( zxc_bytes( $r->bytes ) ); ?></td><td class="fine"><?php echo esc_html( $r->expires_at ? zxc_remaining( $r->expires_at - time() ) : '—' ); ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
  <?php else : ?><div class="empty"><span class="ico lg blue"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span><b>Nobody is online right now</b><span>Connected customers appear here within seconds of logging in.</span></div><?php endif; ?>
</section>
	<?php
	return zxc_dash_page( $t, 'online', 'Online now', ob_get_clean(), array( 'actions' => '<span class="badge badge-ok"><span class="live" style="width:6px;height:6px"></span>' . count( $rows ) . ' online</span>' ) );
}

function zxc_view_dash_locations( $t, $routers ) {
	ob_start();
	?>
<section class="card">
  <div class="card-title">How your network connects</div>
  <div class="topology"><div class="node"><b>Internet</b><small>MTN ODU, fibre, Starlink</small></div><div class="ln"></div><div class="node hl"><b>MikroTik router</b><small>Hotspot + walled garden</small></div><div class="ln"></div><div class="node"><b>Access points</b><small>Customers’ Wi-Fi</small></div></div>
  <p class="muted" style="font-size:14px">Every 10 seconds your router checks in with <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?>, picks up new vouchers and enforces time, speed and data limits. Works behind MTN’s network — no public IP needed.</p>
</section>
<?php foreach ( $routers as $r ) : ?>
  <?php $on = $r->last_seen >= time() - 120; ?>
  <section class="card stack" style="gap:18px">
    <div class="card-head" style="margin:0"><div class="row"><span class="ico <?php echo $on ? 'ok' : 'amber'; ?>"><?php echo zxc_icon( 'router' ); // phpcs:ignore ?></span><div><div class="card-title"><?php echo esc_html( $r->name ); ?></div><div class="card-sub"><?php echo esc_html( $r->last_seen ? 'Last check-in ' . zxc_ago( $r->last_seen ) : 'Waiting for the router to check in' ); ?></div></div></div>
      <div class="row"><span class="badge <?php echo $on ? 'badge-ok' : 'badge-warn'; ?>"><span class="live <?php echo $on ? '' : 'off'; ?>" style="width:6px;height:6px"></span><?php echo $on ? 'Connected' : 'Not connected'; ?></span>
      <?php if ( count( $routers ) > 1 ) : ?><form method="post" data-confirm="Remove this location? Its router stops receiving vouchers."><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="location_delete"><input type="hidden" name="router_id" value="<?php echo (int) $r->id; ?>"><button class="icon-btn" type="submit" aria-label="Remove location"><?php echo zxc_icon( 'trash' ); // phpcs:ignore ?></button></form><?php endif; ?></div></div>
    <ol class="steps-list">
      <li>Connect your internet source to <b>ether1</b> on the MikroTik and your access points to the other ports.</li>
      <li>In WinBox open <b>IP → DHCP Client</b> and add one on <b>ether1</b>.</li>
      <li>Open <b>IP → Hotspot → Hotspot Setup</b> on your LAN bridge and use <code><?php echo esc_html( $t->hotspot_dns ); ?></code> as the DNS name. Skip the local user.</li>
      <li>Open <b>New Terminal</b> and paste the script below.</li>
      <li>Download <a class="grad-text" style="font-weight:600" href="<?php echo esc_url( zxc_dash_url( 'locations/' . $r->id . '/login.html' ) ); ?>">login.html</a> and <a class="grad-text" style="font-weight:600" href="<?php echo esc_url( zxc_dash_url( 'locations/' . $r->id . '/alogin.html' ) ); ?>">alogin.html</a> and drag both into the <code>hotspot</code> folder in <b>Files</b>.</li>
    </ol>
    <div><div class="row between" style="margin-bottom:10px"><b style="font-size:14px;font-weight:600">Router setup script</b><button type="button" class="btn btn-soft btn-sm" data-copy="#script-<?php echo (int) $r->id; ?>"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy</button></div><pre class="code-block" id="script-<?php echo (int) $r->id; ?>"><?php echo esc_html( zxc_router_script( $t, $r ) ); ?></pre></div>
  </section>
<?php endforeach; ?>
<section class="card"><div class="card-title">Add another location</div><p class="card-sub" style="margin-bottom:14px">Each location gets its own router script. Vouchers work at all your locations.</p>
  <?php if ( zxc_can_add_router( $t ) ) : ?><form method="post" class="filters"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="location_add"><input class="input" name="name" placeholder="e.g. Ikeja branch" required maxlength="80"><button class="btn btn-primary" type="submit"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?> Add location</button></form>
  <?php else : ?><?php echo zxc_alert( 'info', 'Your plan includes ' . count( $routers ) . ' location' . ( count( $routers ) > 1 ? 's' : '' ) . '. <a href="' . esc_url( zxc_dash_url( 'billing' ) ) . '">Upgrade</a> to add more.', true ); // phpcs:ignore ?><?php endif; ?>
</section>
	<?php
	return zxc_dash_page( $t, 'locations', 'Locations & routers', ob_get_clean(), array( 'sub' => 'Connect a MikroTik router at each hotspot location.' ) );
}

function zxc_view_dash_branding( $t ) {
	ob_start();
	?>
<form method="post" enctype="multipart/form-data" class="split">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="branding">
  <section class="card form-grid wide" style="align-content:start">
    <label class="field">Business name<input class="input" name="name" value="<?php echo esc_attr( $t->name ); ?>" required maxlength="80"></label>
    <label class="field">Location <em>optional</em><input class="input" name="location" value="<?php echo esc_attr( $t->location ); ?>" placeholder="e.g. Lekki Phase 1, Lagos"></label>
    <label class="field span-2">Headline on your portal<input class="input" name="tagline" value="<?php echo esc_attr( $t->tagline ); ?>" maxlength="160"></label>
    <div class="field span-2">Brand colour<div class="swatches" data-swatches="#brand_color"><?php foreach ( zxc_swatches() as $c => $n ) : ?><button type="button" class="swatch <?php echo strtolower( $c ) === strtolower( $t->brand_color ) ? 'on' : ''; ?>" data-color="<?php echo esc_attr( $c ); ?>" style="background:<?php echo esc_attr( $c ); ?>;--sw:<?php echo esc_attr( $c ); ?>" aria-label="<?php echo esc_attr( $n ); ?>"></button><?php endforeach; ?><input class="input mono" id="brand_color" name="brand_color" value="<?php echo esc_attr( $t->brand_color ); ?>" pattern="#[0-9A-Fa-f]{6}" style="width:120px;height:42px" aria-label="Custom colour (hex)"></div></div>
    <div class="logo-drop span-2"><?php echo zxc_brand_mark( $t, 'style="width:56px;height:56px;border-radius:16px;font-size:24px"' ); // phpcs:ignore ?><div class="grow"><b style="font-size:14px;font-weight:600">Logo</b><div class="fine" id="logo-msg">PNG, JPG or WebP, under 500 KB</div><?php if ( $t->logo_url ) : ?><label class="check" style="font-size:13px;margin-top:4px"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label><?php endif; ?></div><label class="btn btn-glass btn-sm">Upload<input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" class="sr" data-logo-input="#logo_preview" data-logo-msg="#logo-msg"></label><input type="hidden" id="logo_preview"></div>
    <label class="field">Support phone<input class="input" name="support_phone" value="<?php echo esc_attr( $t->support_phone ); ?>" placeholder="0803 123 4567"></label>
    <label class="field">WhatsApp number<input class="input" name="support_whatsapp" value="<?php echo esc_attr( $t->support_whatsapp ); ?>" placeholder="0803 123 4567"></label>
    <label class="field span-2">Hotspot DNS name <em>advanced — must match your MikroTik hotspot</em><input class="input mono" name="hotspot_dns" value="<?php echo esc_attr( $t->hotspot_dns ); ?>"></label>
    <div class="span-2 actions"><a class="btn btn-ghost" href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>"><?php echo zxc_icon( 'globe' ); // phpcs:ignore ?> View portal</a><button class="btn btn-primary" type="submit">Save branding</button></div>
  </section>
  <div class="side-col"><section class="card"><div class="card-title">Your portal link</div><p class="card-sub" style="margin-bottom:12px">Customers land here when they join your Wi-Fi.</p><div class="code" style="font-size:13px;letter-spacing:0;padding:12px;word-break:break-all" id="portal-link"><?php echo esc_html( zxc_portal_url( $t ) ); ?></div><button type="button" class="btn btn-soft btn-sm btn-block" style="margin-top:10px" data-copy="#portal-link"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy link</button></section></div>
</form>
	<?php
	return zxc_dash_page( $t, 'branding', 'Portal & branding', ob_get_clean(), array( 'sub' => 'How your customer portal looks.' ) );
}

function zxc_view_dash_payments( $t ) {
	$mask = function ( $v ) {
		return $v ? substr( $v, 0, 10 ) . '••••••' . substr( $v, -4 ) : '';
	};
	ob_start();
	?>
<form method="post" class="split">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="payments">
  <section class="card stack wide">
    <div class="row"><span class="ico amber"><?php echo zxc_icon( 'card', 'a-float' ); // phpcs:ignore ?></span><div class="grow"><div class="card-title">Flutterwave</div><div class="card-sub">Customer payments go straight to your own Flutterwave account.</div></div><span class="badge <?php echo zxc_gateway_ready( $t ) ? 'badge-ok' : 'badge-warn'; ?>"><?php echo zxc_gateway_ready( $t ) ? 'Connected' : 'Not connected'; ?></span></div>
    <label class="field">Public key<input class="input mono" name="flw_public" value="<?php echo esc_attr( $t->flw_public ); ?>" placeholder="FLWPUBK-…" autocomplete="off"></label>
    <label class="field">Secret key <?php echo $t->flw_secret ? '<em>saved: ' . esc_html( $mask( $t->flw_secret ) ) . '</em>' : ''; ?><input class="input mono" type="password" name="flw_secret" placeholder="<?php echo $t->flw_secret ? 'Leave blank to keep the saved key' : 'FLWSECK-…'; ?>" autocomplete="off"></label>
    <label class="field">Webhook secret hash <em>recommended</em><input class="input mono" name="flw_hash" value="<?php echo esc_attr( $t->flw_hash ); ?>" autocomplete="off"></label>
    <label class="check"><input type="checkbox" name="demo_payments" value="1" <?php checked( (int) $t->demo_payments, 1 ); ?>> Allow simulated test payments while Flutterwave is not connected</label>
    <div class="actions"><button class="btn btn-primary" type="submit">Save payment settings</button></div>
  </section>
  <div class="side-col"><section class="card"><div class="card-title" style="margin-bottom:10px">Setup steps</div>
    <ol class="steps-list" style="font-size:14px"><li>Sign in at <b>dashboard.flutterwave.com</b>.</li><li>Open <b>Settings → API Keys</b> and copy both keys here.</li><li>Open <b>Settings → Webhooks</b>, paste the URL below, type a secret hash, and paste the same hash here.</li></ol>
    <div class="code" style="font-size:12px;letter-spacing:0;padding:12px;margin-top:12px;word-break:break-all" id="wh"><?php echo esc_html( rest_url( 'zaxconnect/v1/flutterwave/' . $t->slug ) ); ?></div>
    <button type="button" class="btn btn-soft btn-sm btn-block" style="margin-top:10px" data-copy="#wh"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy webhook URL</button></section></div>
</form>
	<?php
	return zxc_dash_page( $t, 'payments', 'Payments', ob_get_clean() );
}

function zxc_view_dash_billing( $t, $history ) {
	$sub   = zxc_subscription( $t );
	$tiers = zxc_tiers();
	ob_start();
	?>
<section class="card row wrap-row" style="gap:16px"><span class="ico lg"><?php echo zxc_icon( 'star', 'a-float' ); // phpcs:ignore ?></span><div class="grow"><div class="card-title"><?php echo esc_html( $sub['label'] ); ?></div><div class="card-sub"><?php echo esc_html( isset( $sub['ends'] ) ? ( 'trial' === $sub['state'] ? 'Trial ends ' : 'Paid until ' ) . zxc_date( $sub['ends'] ) . ' · ' . $sub['days'] . ' days left' : 'Choose a plan below to keep selling online.' ); ?></div></div><span class="badge <?php echo $sub['active'] ? 'badge-ok' : 'badge-bad'; ?>"><?php echo esc_html( $tiers[ $t->tier ]['name'] ?? '' ); ?></span></section>
<div class="grid">
  <?php foreach ( $tiers as $k => $tier ) : ?>
    <form method="post" class="tier <?php echo $t->tier === $k ? 'best' : ''; ?>"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="subscribe"><input type="hidden" name="tier" value="<?php echo esc_attr( $k ); ?>">
      <div class="row between"><b style="font-family:var(--display);font-size:18px;font-weight:650"><?php echo esc_html( $tier['name'] ); ?></b><?php if ( $t->tier === $k ) : ?><span class="badge badge-soft">Current</span><?php endif; ?></div>
      <p class="muted" style="margin-top:6px;font-size:14px"><?php echo esc_html( $tier['desc'] ); ?></p>
      <div class="tier-price"><b><?php echo esc_html( zxc_money( $tier['price'] ) ); ?></b><span class="muted">/ month</span></div>
      <label class="field" style="margin-top:16px">Pay for<select class="input" name="months"><option value="1">1 month</option><option value="3">3 months</option><option value="6">6 months</option><option value="12">12 months</option></select></label>
      <button class="btn <?php echo $t->tier === $k ? 'btn-primary' : 'btn-glass'; ?> btn-block" style="margin-top:14px" type="submit"><?php echo zxc_icon( 'card' ); // phpcs:ignore ?> <?php echo $t->tier === $k && 'active' === $sub['state'] ? 'Renew' : 'Choose ' . esc_html( $tier['name'] ); ?></button>
      <button class="btn btn-ghost btn-block" style="margin-top:8px" type="submit" name="action" value="subscribe_wallet"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?> Pay from wallet (<?php echo esc_html( zxc_money_short( zxc_wallet_balance( $t->id ) ) ); ?>)</button>
      <ul><?php foreach ( $tier['features'] as $f ) : ?><li><?php echo zxc_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $f ); ?></span></li><?php endforeach; ?></ul>
    </form>
  <?php endforeach; ?>
</div>
<?php if ( $history ) : ?><section class="card flush"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div class="card-title">Billing history</div></div><div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Plan</th><th>Reference</th><th>Status</th><th class="r">Amount</th></tr></thead><tbody><?php foreach ( $history as $h ) : ?><tr><td><?php echo esc_html( zxc_date( $h->created ) ); ?></td><td><?php echo esc_html( ( $tiers[ $h->tier ]['name'] ?? $h->tier ) . ' · ' . $h->months . ' mo' ); ?></td><td class="mono fine"><?php echo esc_html( $h->reference ); ?></td><td><?php echo zxc_status_badge( $h->status ); // phpcs:ignore ?></td><td class="r"><b><?php echo esc_html( zxc_money( $h->amount ) ); ?></b></td></tr><?php endforeach; ?></tbody></table></div></section><?php endif; ?>
	<?php
	return zxc_dash_page( $t, 'billing', 'Subscription', ob_get_clean(), array( 'sub' => 'Your ' . zxc_setting( 'platform_name' ) . ' plan.' ) );
}

function zxc_view_dash_billing_demo( $t, $s ) {
	ob_start();
	?>
<form method="post" class="card stack center narrow" style="justify-items:center;padding:30px"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="subscribe_demo"><input type="hidden" name="reference" value="<?php echo esc_attr( $s->reference ); ?>">
  <span class="badge badge-warn">Test payment</span><h1 style="font-size:34px"><?php echo esc_html( zxc_money( $s->amount ) ); ?></h1><p class="muted">The platform has not connected Flutterwave yet, so this subscription payment is simulated.</p>
  <button class="btn btn-primary btn-lg btn-block" type="submit">Simulate successful payment</button><a class="btn btn-ghost btn-block" href="<?php echo esc_url( zxc_dash_url( 'billing' ) ); ?>">Cancel</a></form>
	<?php
	return zxc_dash_page( $t, 'billing', 'Subscription payment', ob_get_clean() );
}

function zxc_view_dash_support( $t, $msgs ) {
	$pname = zxc_setting( 'platform_name' );
	ob_start();
	?>
<div class="split">
  <section class="card wide"><div class="card-head"><div><div class="card-title">Customer messages</div><div class="card-sub">Sent from the Help page on your portal.</div></div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_portal_url( $t, 'help' ) ); ?>">View help page</a></div>
    <?php if ( $msgs ) : ?><div><?php foreach ( $msgs as $m ) : ?><div class="msg <?php echo $m->is_read ? '' : 'unread'; ?>"><span class="avatar"><?php echo esc_html( strtoupper( mb_substr( $m->name, 0, 1 ) ) ); ?></span><div class="grow"><div class="row between wrap-row" style="gap:6px"><b class="subj" style="font-weight:600"><?php echo esc_html( $m->subject ); ?></b><span class="fine"><?php echo esc_html( zxc_ago( $m->created ) ); ?></span></div><div class="fine" style="margin-top:2px"><?php echo esc_html( $m->name . ( $m->phone ? ' · ' . $m->phone : '' ) ); ?></div><p style="margin-top:8px;font-size:14px;white-space:pre-line;color:var(--ink-2)"><?php echo esc_html( $m->body ); ?></p>
      <?php if ( $m->phone ) : ?><div class="row" style="margin-top:10px;gap:8px"><a class="btn btn-soft btn-sm" href="tel:<?php echo esc_attr( $m->phone ); ?>"><?php echo zxc_icon( 'call' ); // phpcs:ignore ?> Call</a><a class="btn btn-soft btn-sm" href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', $m->phone ) ) ); ?>"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a></div><?php endif; ?>
    </div></div><?php endforeach; ?></div>
    <?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'inbox' ); // phpcs:ignore ?></span><b>No messages yet</b><span>Customer questions from your portal will appear here.</span></div><?php endif; ?>
  </section>
  <div class="side-col">
    <form method="post" class="card stack"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="support_send">
      <div class="row"><span class="ico"><?php echo zxc_icon( 'message' ); // phpcs:ignore ?></span><div><div class="card-title">Contact <?php echo esc_html( $pname ); ?></div><div class="card-sub">We reply by email.</div></div></div>
      <label class="field">Subject<input class="input" name="subject" placeholder="e.g. Router not connecting" required></label>
      <label class="field">Message<textarea class="input" name="message" required></textarea></label>
      <button class="btn btn-primary btn-block" type="submit"><?php echo zxc_icon( 'message' ); // phpcs:ignore ?> Send to support</button>
    </form>
    <section class="card"><div class="card-title" style="margin-bottom:12px">Helpful links</div><div class="links-list">
      <a href="<?php echo esc_url( home_url( '/guide' ) ); ?>"><span class="ico sm"><?php echo zxc_icon( 'book' ); // phpcs:ignore ?></span>Setup guide<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      <a href="<?php echo esc_url( home_url( '/faq' ) ); ?>"><span class="ico sm teal"><?php echo zxc_icon( 'help' ); // phpcs:ignore ?></span>FAQ<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      <?php if ( zxc_setting( 'support_whatsapp' ) ) : ?><a href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', zxc_setting( 'support_whatsapp' ) ) ) ); ?>"><span class="ico sm ok"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?></span>WhatsApp support<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a><?php endif; ?>
    </div></section>
  </div>
</div>
	<?php
	return zxc_dash_page( $t, 'support', 'Support', ob_get_clean(), array( 'sub' => 'Customer messages and help from ' . $pname . '.' ) );
}

function zxc_view_dash_account( $t, $user ) {
	ob_start();
	?>
<form method="post" class="card form-grid"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="account">
  <div class="span-2 row"><span class="avatar" style="width:52px;height:52px;font-size:20px;border-radius:16px"><?php echo esc_html( strtoupper( mb_substr( $user->display_name, 0, 1 ) ) ); ?></span><div><div class="card-title"><?php echo esc_html( $user->display_name ); ?></div><div class="card-sub"><?php echo esc_html( $user->user_email ); ?></div></div></div>
  <label class="field">Your name<input class="input" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required></label>
  <label class="field">Email <em>contact support to change</em><input class="input" value="<?php echo esc_attr( $user->user_email ); ?>" disabled></label>
  <div class="span-2"><hr class="divider"><div class="card-title" style="margin-top:12px">Change password</div><p class="card-sub">Leave blank to keep your current password.</p></div>
  <label class="field">Current password<input class="input" type="password" name="current" autocomplete="current-password"></label><span></span>
  <label class="field">New password<input class="input" type="password" name="password" minlength="8" autocomplete="new-password"></label>
  <label class="field">Confirm new password<input class="input" type="password" name="confirm" minlength="8" autocomplete="new-password"></label>
  <div class="span-2 actions"><button class="btn btn-primary" type="submit">Save account</button></div>
</form>
	<?php
	return zxc_dash_page( $t, 'account', 'Account', ob_get_clean() );
}

function zxc_wallet_sparkline( $history, $balance ) {
	// Rebuild a 30-day running balance from the ledger (newest first).
	$days  = 30;
	$today = zxc_lagos_day_start();
	$net   = array_fill( 0, $days, 0 );
	foreach ( $history as $h ) {
		if ( 'success' !== $h->status ) {
			continue;
		}
		$i = (int) floor( ( (int) ( $h->completed ? $h->completed : $h->created ) - ( $today - ( $days - 1 ) * DAY_IN_SECONDS ) ) / DAY_IN_SECONDS );
		if ( $i >= 0 && $i < $days ) {
			$net[ $i ] += (int) $h->amount;
		}
	}
	$points = array();
	$run    = $balance;
	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$points[ $i ] = $run;
		$run         -= $net[ $i ];
	}
	ksort( $points );
	$max = max( 1, max( $points ) );
	$min = min( 0, min( $points ) );
	$w   = 600;
	$h   = 150;
	$xy  = array();
	foreach ( $points as $i => $v ) {
		$xy[] = round( $i / ( $days - 1 ) * $w, 1 ) . ',' . round( $h - 10 - ( $v - $min ) / ( $max - $min ) * ( $h - 30 ), 1 );
	}
	$line = implode( ' ', $xy );
	return '<svg class="spark" viewBox="0 0 ' . $w . ' ' . $h . '" preserveAspectRatio="none" role="img" aria-label="Wallet balance, last 30 days"><defs><linearGradient id="sg" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="var(--accent)" stop-opacity=".35"/><stop offset="1" stop-color="var(--accent)" stop-opacity="0"/></linearGradient><linearGradient id="sl" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="var(--accent-3)"/><stop offset="1" stop-color="var(--accent-2)"/></linearGradient></defs><polygon points="0,' . $h . ' ' . $line . ' ' . $w . ',' . $h . '" fill="url(#sg)"/><polyline class="spark-line" points="' . $line . '" fill="none" stroke="url(#sl)" stroke-width="3" stroke-linejoin="round" stroke-linecap="round" vector-effect="non-scaling-stroke"/></svg>';
}

function zxc_view_dash_wallet( $t, $balance, $history ) {
	$user      = wp_get_current_user();
	$deposited = 0;
	$spent     = 0;
	$last      = null;
	foreach ( $history as $h ) {
		if ( 'success' !== $h->status ) {
			continue;
		}
		if ( $h->amount > 0 ) {
			$deposited += (int) $h->amount;
			$last       = $last ? $last : $h;
		} else {
			$spent += abs( (int) $h->amount );
		}
	}
	$types = array(
		'deposit'      => array( 'download', 'ok', 'Top-up' ),
		'credit'       => array( 'sparkles', 'ok', 'Credit' ),
		'subscription' => array( 'star', '', 'Subscription' ),
		'debit'        => array( 'receipt', 'amber', 'Debit' ),
	);
	$tiers = zxc_tiers();
	$price = $tiers[ $t->tier ]['price'] ?? 0;
	ob_start();
	?>
<div class="split">
  <div class="wide stack" style="gap:20px">
    <section class="bank-card a-rise" aria-label="Wallet balance">
      <div class="row between"><span class="row" style="gap:10px"><?php echo zxc_logo_mark(); // phpcs:ignore ?><b style="font-family:var(--display);font-weight:600"><?php echo esc_html( zxc_setting( 'platform_name' ) ); ?> Wallet</b></span><span class="chip-ic" aria-hidden="true"></span></div>
      <div><small>Available balance</small><div class="bal" data-count="<?php echo (int) $balance; ?>"><?php echo esc_html( zxc_money( $balance ) ); ?></div></div>
      <div class="row between wrap-row" style="gap:10px"><span class="mono" style="letter-spacing:.18em;opacity:.85">ZX •••• <?php echo esc_html( strtoupper( substr( $t->slug, 0, 4 ) ) ); ?></span><span style="opacity:.85;font-size:13px"><?php echo esc_html( $user->display_name ); ?></span></div>
    </section>
    <section class="kpis" style="grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr))">
      <?php
      echo zxc_kpi( 'Total topped up', zxc_money( $deposited ), 'all time', 'download', 'teal' ); // phpcs:ignore
      echo zxc_kpi( 'Total spent', zxc_money( $spent ), 'subscriptions & debits', 'receipt', 'amber' ); // phpcs:ignore
      echo zxc_kpi( 'Last top-up', $last ? zxc_money( $last->amount ) : '—', $last ? zxc_ago( $last->completed ) : 'no top-ups yet', 'clock', 'blue' ); // phpcs:ignore
      ?>
    </section>
    <section class="card"><div class="card-head"><div><div class="card-title">Balance history</div><div class="card-sub">Last 30 days</div></div><span class="badge badge-soft"><?php echo esc_html( zxc_money( $balance ) ); ?></span></div><?php echo zxc_wallet_sparkline( $history, $balance ); // phpcs:ignore ?></section>
  </div>
  <div class="side-col">
    <form method="post" class="card stack"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="wallet_deposit">
      <div class="row"><span class="ico"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></span><div><div class="card-title">Top up wallet</div><div class="card-sub">Card, bank transfer or USSD</div></div></div>
      <label class="field">Amount (₦)<span class="input-group"><span class="addon">₦</span><input class="input" type="number" name="amount" min="500" max="2000000" step="100" value="5000" required data-amount style="font-family:var(--display);font-size:20px;font-weight:700"></span></label>
      <div class="chips" style="flex-wrap:wrap"><?php foreach ( array( 1000, 5000, 10000, 20000, 50000 ) as $q ) : ?><button type="button" class="chip" data-quick="<?php echo (int) $q; ?>"><?php echo esc_html( zxc_money_short( $q ) ); ?></button><?php endforeach; ?></div>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'lock' ); // phpcs:ignore ?> Top up securely</button>
      <div class="row" style="justify-content:center;gap:16px;color:var(--faint)"><?php foreach ( array( 'card', 'bank', 'ussd' ) as $m ) : ?><?php echo zxc_icon( $m ); // phpcs:ignore ?><?php endforeach; ?></div>
      <?php if ( ! zxc_billing_ready() ) : ?><?php echo zxc_alert( 'warn', 'Test mode: top-ups are simulated until the platform connects Flutterwave.' ); // phpcs:ignore ?><?php else : ?><div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?><span>Secured by Flutterwave</span></div><?php endif; ?>
    </form>
    <section class="card"><div class="row"><span class="ico teal"><?php echo zxc_icon( 'star' ); // phpcs:ignore ?></span><div class="grow"><div class="card-title">Pay with your wallet</div><div class="card-sub"><?php echo esc_html( ( $tiers[ $t->tier ]['name'] ?? '' ) . ' plan · ' . zxc_money( $price ) . '/month' ); ?></div></div></div>
      <?php if ( $balance >= $price && $price > 0 ) : ?>
        <form method="post" style="margin-top:14px"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="subscribe_wallet"><input type="hidden" name="tier" value="<?php echo esc_attr( $t->tier ); ?>"><input type="hidden" name="months" value="1"><button class="btn btn-glass btn-block" type="submit">Renew 1 month from wallet</button></form>
      <?php else : ?><p class="fine" style="margin-top:12px">Top up at least <?php echo esc_html( zxc_money( max( 0, $price - $balance ) ) ); ?> more to pay your next month from the wallet.</p><?php endif; ?>
    </section>
  </div>
</div>
<section class="card flush"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div><div class="card-title">Transactions</div><div class="card-sub">Top-ups, subscription payments and adjustments</div></div></div>
  <?php if ( $history ) : ?><div class="table-wrap"><table class="table"><thead><tr><th>Transaction</th><th class="hide-sm">Reference</th><th class="hide-sm">Date</th><th>Status</th><th class="r">Amount</th></tr></thead><tbody>
  <?php foreach ( $history as $h ) : ?><?php $ty = $types[ $h->type ] ?? array( 'wallet', '', ucfirst( $h->type ) ); ?><tr>
    <td><div class="row" style="gap:12px"><span class="ico sm <?php echo esc_attr( $h->amount < 0 ? 'amber' : $ty[1] ); ?>"><?php echo zxc_icon( $ty[0] ); // phpcs:ignore ?></span><div><b><?php echo esc_html( $ty[2] ); ?></b><small><?php echo esc_html( $h->note . ( $h->channel ? ' · ' . ucwords( str_replace( '_', ' ', $h->channel ) ) : '' ) ); ?></small><small class="mob"><?php echo esc_html( zxc_date( $h->created ) ); ?></small></div></div></td>
    <td class="mono fine hide-sm"><?php echo esc_html( $h->reference ); ?></td><td class="fine hide-sm"><?php echo esc_html( zxc_date( $h->created ) ); ?></td><td><?php echo zxc_status_badge( $h->status ); // phpcs:ignore ?></td>
    <td class="r"><b style="color:<?php echo $h->amount >= 0 ? 'var(--ok)' : 'var(--ink)'; ?>"><?php echo esc_html( ( $h->amount >= 0 ? '+' : '−' ) . zxc_money( abs( $h->amount ) ) ); ?></b></td>
  </tr><?php endforeach; ?></tbody></table></div>
  <?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'wallet', 'a-float' ); // phpcs:ignore ?></span><b>No transactions yet</b><span>Top up your wallet to pay for your plan in one tap.</span></div><?php endif; ?>
</section>
	<?php
	return zxc_dash_page( $t, 'wallet', 'Wallet', ob_get_clean(), array( 'sub' => 'Your balance for subscription payments.' ) );
}

function zxc_view_dash_wallet_demo( $t, $w ) {
	ob_start();
	?>
<form method="post" class="card stack center narrow" style="justify-items:center;padding:30px"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="wallet_demo"><input type="hidden" name="reference" value="<?php echo esc_attr( $w->reference ); ?>">
  <span class="badge badge-warn">Test payment</span><h1 style="font-size:34px"><?php echo esc_html( zxc_money( $w->amount ) ); ?></h1><p class="muted">Wallet top-up. The platform has not connected Flutterwave yet, so this payment is simulated.</p>
  <button class="btn btn-primary btn-lg btn-block" type="submit">Simulate successful top-up</button><a class="btn btn-ghost btn-block" href="<?php echo esc_url( zxc_dash_url( 'wallet' ) ); ?>">Cancel</a></form>
	<?php
	return zxc_dash_page( $t, 'wallet', 'Top up wallet', ob_get_clean() );
}

function zxc_view_dash_cwallets( $t, $rows, array $tot, $q ) {
	ob_start();
	?>
<section class="kpis a-rise">
  <?php
  echo zxc_kpi( 'Balances held', zxc_money( $tot['held'] ), 'owed to customers as Wi-Fi credit', 'wallet', '', true ); // phpcs:ignore
  echo zxc_kpi( 'Total loaded', zxc_money_short( $tot['deposits'] ), 'top-ups & credits', 'download', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Spent on plans', zxc_money_short( $tot['spent'] ), 'paid from wallets', 'wifi', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Wallets', (string) $tot['wallets'], 'customers with a wallet', 'users', 'amber' ); // phpcs:ignore
  ?>
</section>
<div class="split">
  <section class="card flush wide">
    <form class="filters" method="get" style="padding:18px 22px"><input class="input" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search phone or name"><button class="btn btn-glass" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Search</button></form>
    <?php if ( $rows ) : ?>
      <div class="table-wrap"><table class="table"><thead><tr><th>Customer</th><th class="hide-sm">Loaded</th><th class="hide-sm">Last activity</th><th class="r">Balance</th></tr></thead><tbody>
      <?php foreach ( $rows as $c ) : ?><tr>
        <td><div class="row" style="gap:10px"><span class="avatar"><?php echo esc_html( $c->name ? strtoupper( substr( $c->name, 0, 1 ) ) : substr( preg_replace( '/\D/', '', (string) $c->phone ), -2 ) ); ?></span><div><b><?php echo esc_html( $c->name ? $c->name : zxc_mask_phone( $c->phone ) ); ?></b><small><?php echo esc_html( $c->phone ); ?></small></div></div></td>
        <td class="hide-sm"><?php echo esc_html( zxc_money( $c->topped ) ); ?></td>
        <td class="fine hide-sm"><?php echo esc_html( $c->last_tx ? zxc_ago( $c->last_tx ) : 'Opened ' . zxc_ago( $c->created ) ); ?></td>
        <td class="r"><b style="color:<?php echo $c->balance > 0 ? 'var(--ok)' : 'var(--muted)'; ?>"><?php echo esc_html( zxc_money( $c->balance ) ); ?></b></td>
      </tr><?php endforeach; ?></tbody></table></div>
    <?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'wallet', 'a-float' ); // phpcs:ignore ?></span><b>No customer wallets yet</b><span>Customers open a wallet from the Wallet tab on your portal with their phone number and a PIN.</span></div><?php endif; ?>
  </section>
  <div class="side-col">
    <form method="post" class="card stack"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="cwallet_adjust">
      <div class="row"><span class="ico teal"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></span><div><div class="card-title">Load a wallet</div><div class="card-sub">Cash top-ups at your shop, bonuses or refunds</div></div></div>
      <label class="field">Customer phone<span class="input-group"><span class="addon">+234</span><input class="input" type="tel" name="phone" placeholder="803 123 4567" required></span></label>
      <label class="field">Amount (₦)<input class="input" type="number" name="amount" min="1" max="500000" required data-amount></label>
      <div class="chips" style="flex-wrap:wrap"><?php foreach ( array( 500, 1000, 2000, 5000 ) as $qa ) : ?><button type="button" class="chip" data-quick="<?php echo (int) $qa; ?>"><?php echo esc_html( zxc_money( $qa ) ); ?></button><?php endforeach; ?></div>
      <div class="seg" role="radiogroup" aria-label="Type"><label><input type="radio" name="type" value="credit" checked><span>Add credit</span></label><label><input type="radio" name="type" value="debit"><span>Remove</span></label></div>
      <label class="field">Note <em>optional</em><input class="input" name="note" maxlength="150" placeholder="e.g. Cash paid at counter"></label>
      <button class="btn btn-primary btn-block" type="submit">Save</button>
    </form>
    <section class="card"><div class="row"><span class="ico pink"><?php echo zxc_icon( 'globe' ); // phpcs:ignore ?></span><div class="grow"><div class="card-title">Share your wallet link</div><div class="card-sub">Customers top up here</div></div></div>
      <div class="copy-row" style="margin-top:12px"><code id="cwlink"><?php echo esc_html( zxc_portal_url( $t, 'wallet' ) ); ?></code><button type="button" class="btn btn-glass btn-sm" data-copy="#cwlink"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy</button></div>
    </section>
  </div>
</div>
	<?php
	return zxc_dash_page( $t, 'cwallets', 'Customer wallets', ob_get_clean(), array( 'sub' => 'Prepaid Wi-Fi balances your customers keep with you.' ) );
}
