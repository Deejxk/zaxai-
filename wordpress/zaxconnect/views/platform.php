<?php
/**
 * ZaxConnect admin console (/zx-admin) — its own login and screens, separate from wp-admin.
 */
defined( 'ABSPATH' ) || exit;

function zxc_admin_url( $page = '' ) {
	return home_url( '/zx-admin' . ( $page ? '/' . $page : '' ) );
}

function zxc_admin_page( $active, $title, $content, array $o = array() ) {
	$unread = zxc_unread_count( null );
	$user   = wp_get_current_user();
	$groups = array(
		'Platform' => array( '' => array( 'dashboard', 'Overview' ), 'businesses' => array( 'building', 'Businesses' ), 'subscriptions' => array( 'receipt', 'Subscriptions' ), 'inbox' => array( 'inbox', 'Inbox' ) ),
		'Manage'   => array( 'team' => array( 'users', 'Team' ), 'settings' => array( 'gear', 'Settings' ), 'account' => array( 'user', 'My account' ) ),
	);
	ob_start();
	?>
<div class="app">
  <aside class="side" data-side>
    <?php echo zxc_logo( zxc_admin_url() ); // phpcs:ignore ?>
    <div class="tenant"><span class="brand-mark"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?></span><span class="grow"><b>Admin console</b><small><?php echo esc_html( $user->display_name ); ?></small></span></div>
    <?php foreach ( $groups as $g => $items ) : ?>
      <div><div class="side-label"><?php echo esc_html( $g ); ?></div><nav class="side-nav" style="margin-top:6px" aria-label="<?php echo esc_attr( $g ); ?>">
        <?php foreach ( $items as $k => $n ) : ?><a href="<?php echo esc_url( zxc_admin_url( $k ) ); ?>" class="<?php echo $active === $k ? 'on' : ''; ?>"<?php echo $active === $k ? ' aria-current="page"' : ''; ?>><?php echo zxc_icon( $n[0] ); // phpcs:ignore ?><span><?php echo esc_html( $n[1] ); ?></span><?php if ( 'inbox' === $k && $unread ) : ?><span class="count accent"><?php echo (int) $unread; ?></span><?php endif; ?></a><?php endforeach; ?>
      </nav></div>
    <?php endforeach; ?>
    <div class="side-foot side-nav" style="margin-top:auto">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo zxc_icon( 'globe' ); // phpcs:ignore ?><span>View website</span></a>
      <?php if ( zxc_tenant_for_user( $user->ID ) ) : ?><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?><span>My hotspot dashboard</span></a><?php endif; ?>
      <a href="<?php echo esc_url( zxc_admin_url( 'logout' ) ); ?>"><?php echo zxc_icon( 'logout' ); // phpcs:ignore ?><span>Sign out</span></a>
    </div>
  </aside>
  <div class="backdrop" data-backdrop></div>
  <div class="main">
    <header class="topbar">
      <button type="button" class="icon-btn menu-btn" data-menu aria-label="Open menu"><?php echo zxc_icon( 'menu' ); // phpcs:ignore ?></button>
      <div class="title"><h1><?php echo esc_html( $title ); ?></h1><?php if ( ! empty( $o['sub'] ) ) : ?><div class="sub"><?php echo esc_html( $o['sub'] ); ?></div><?php endif; ?></div>
      <?php echo zxc_switch_buttons( 'admin' ); // phpcs:ignore ?>
      <a class="icon-btn" href="<?php echo esc_url( zxc_admin_url( 'inbox' ) ); ?>" aria-label="Inbox" data-tip="Inbox"><?php echo zxc_icon( 'bell', $unread ? 'a-bell' : '' ); // phpcs:ignore ?><?php if ( $unread ) : ?><span class="dot"></span><?php endif; ?></a>
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
      <?php echo $o['actions'] ?? ''; // phpcs:ignore ?>
    </header>
    <div class="content"><?php echo $content; // phpcs:ignore ?></div>
  </div>
</div>
	<?php
	echo zxc_toast( zxc_take_flash() ); // phpcs:ignore
	echo zxc_dock( // phpcs:ignore
		array(
			array( zxc_admin_url(), 'dashboard', 'Overview', '' === $active ),
			array( zxc_admin_url( 'businesses' ), 'building', 'Businesses', 'businesses' === $active ),
			array( zxc_admin_url( 'inbox' ), 'inbox', 'Inbox', 'inbox' === $active, true ),
			array( zxc_admin_url( 'settings' ), 'gear', 'Settings', 'settings' === $active ),
			array( 'button:data-menu', 'menu', 'More', in_array( $active, array( 'subscriptions', 'team', 'account' ), true ) ),
		),
		'Admin'
	);
	return zxc_page( $title . ' · Admin · ' . zxc_setting( 'platform_name' ), ob_get_clean(), array( 'body_class' => 'has-dock', 'robots' => 'noindex' ) );
}

function zxc_sub_badge( $t ) {
	$s   = zxc_subscription( $t );
	$cls = array( 'active' => 'badge-ok', 'trial' => 'badge-soft', 'expired' => 'badge-warn', 'suspended' => 'badge-bad' );
	return '<span class="badge ' . esc_attr( $cls[ $s['state'] ] ?? '' ) . '">' . esc_html( $s['label'] ) . '</span>';
}

function zxc_tenants_table( $tenants ) {
	if ( ! $tenants ) {
		return '<div class="empty"><span class="ico lg">' . zxc_icon( 'building' ) . '</span><b>No businesses yet</b><span>Share ' . esc_html( home_url( '/signup' ) ) . ' with hotspot owners.</span></div>';
	}
	$tiers = zxc_tiers();
	ob_start();
	?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Business</th><th>Owner</th><th>Plan</th><th>Status</th><th>Router</th><th class="r">Sales</th><th></th></tr></thead>
  <tbody><?php foreach ( $tenants as $t ) : ?><tr>
    <td><a class="row" style="gap:10px" href="<?php echo esc_url( zxc_admin_url( 'businesses/' . $t->id ) ); ?>"><span style="<?php echo esc_attr( zxc_brand_style( $t ) ); ?>"><?php echo zxc_brand_mark( $t ); // phpcs:ignore ?></span><span><b><?php echo esc_html( $t->name ); ?></b><small>/h/<?php echo esc_html( $t->slug ); ?></small></span></a></td>
    <td><?php echo esc_html( $t->display_name ); ?><small><?php echo esc_html( $t->user_email ); ?></small></td>
    <td><?php echo esc_html( $tiers[ $t->tier ]['name'] ?? $t->tier ); ?></td>
    <td><?php echo zxc_sub_badge( $t ); // phpcs:ignore ?></td>
    <td><span class="row" style="gap:8px"><span class="live <?php echo $t->router_seen >= time() - 120 ? '' : 'off'; ?>"></span><span class="fine"><?php echo esc_html( $t->router_seen ? zxc_ago( $t->router_seen ) : 'never' ); ?></span></span></td>
    <td class="r"><b><?php echo esc_html( zxc_money( $t->revenue ) ); ?></b><small><?php echo (int) $t->sales; ?> sales</small></td>
    <td class="r"><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_admin_url( 'businesses/' . $t->id ) ); ?>">Manage</a></td>
  </tr><?php endforeach; ?></tbody>
</table></div>
	<?php
	return ob_get_clean();
}

function zxc_view_admin_overview( array $s ) {
	$series = array_map( function ( $w ) { return array( 'label' => $w['label'], 'total' => $w['count'] ); }, $s['signups'] );
	ob_start();
	?>
<section class="kpis a-rise">
  <?php
  echo zxc_kpi( 'Monthly recurring revenue', zxc_money( $s['mrr'] ), '<b>' . (int) $s['paying'] . '</b> paying businesses', 'trend', '', true ); // phpcs:ignore
  echo zxc_kpi( 'Businesses', (string) count( $s['tenants'] ), (int) $s['trials'] . ' on trial · ' . (int) $s['expired'] . ' lapsed', 'building', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Routers online', (string) $s['online_routers'], 'checked in last 2 min', 'router', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Wi-Fi sales processed', zxc_money_short( $s['gmv'] ), 'paid to owners, all time', 'wallet', 'amber' ); // phpcs:ignore
  ?>
</section>
<div class="split">
  <section class="card wide"><div class="card-head"><div><div class="card-title">New businesses</div><div class="card-sub">Sign-ups per week, last 12 weeks</div></div></div><?php echo zxc_bar_chart( $series, false ); // phpcs:ignore ?></section>
  <div class="side-col">
    <section class="card"><div class="card-head"><div><div class="card-title">Subscription revenue</div><div class="card-sub">All time</div></div><span class="ico sm"><?php echo zxc_icon( 'star' ); // phpcs:ignore ?></span></div><div class="kpi-val" style="margin-top:0"><?php echo esc_html( zxc_money( $s['subs_rev'] ) ); ?></div>
      <?php if ( $s['recent_subs'] ) : ?><div class="kv" style="margin-top:16px"><?php foreach ( $s['recent_subs'] as $r ) : ?><div><span><?php echo esc_html( $r->tenant_name ); ?></span><b><?php echo esc_html( zxc_money( $r->amount ) ); ?></b></div><?php endforeach; ?></div><?php endif; ?></section>
    <section class="card"><div class="card-title" style="margin-bottom:12px">Shortcuts</div><div class="links-list">
      <a href="<?php echo esc_url( zxc_admin_url( 'inbox' ) ); ?>"><span class="ico sm"><?php echo zxc_icon( 'inbox' ); // phpcs:ignore ?></span>Inbox<?php if ( $s['unread'] ) : ?><span class="badge badge-accent" style="margin-left:auto"><?php echo (int) $s['unread']; ?> new</span><?php else : ?><?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?><?php endif; ?></a>
      <a href="<?php echo esc_url( zxc_admin_url( 'settings' ) ); ?>"><span class="ico sm teal"><?php echo zxc_icon( 'tag' ); // phpcs:ignore ?></span>Prices &amp; payments<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>"><span class="ico sm amber"><?php echo zxc_icon( 'userPlus' ); // phpcs:ignore ?></span>Owner sign-up page<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
    </div></section>
  </div>
</div>
<section class="card flush"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div class="card-title">Latest businesses</div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_admin_url( 'businesses' ) ); ?>">View all</a></div><?php echo zxc_tenants_table( array_slice( $s['tenants'], 0, 8 ) ); // phpcs:ignore ?></section>
	<?php
	return zxc_admin_page( '', 'Overview', ob_get_clean(), array( 'sub' => 'Your hotspot SaaS at a glance.' ) );
}

function zxc_view_admin_businesses( $tenants, $q ) {
	ob_start();
	?>
<section class="card flush">
  <form class="filters" method="get" style="padding:18px 22px"><input class="input" name="q" value="<?php echo esc_attr( $q ); ?>" placeholder="Search name, address or owner email"><button class="btn btn-glass" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Search</button></form>
  <?php echo zxc_tenants_table( $tenants ); // phpcs:ignore ?>
</section>
	<?php
	return zxc_admin_page( 'businesses', 'Businesses', ob_get_clean(), array( 'sub' => count( $tenants ) . ' hotspot businesses on the platform.' ) );
}

function zxc_view_admin_business( $t, $owner, array $stats, $subs ) {
	$sub   = zxc_subscription( $t );
	$tiers = zxc_tiers();
	$form  = function ( $action, $label, $cls = 'btn-glass', $confirm = '' ) use ( $t ) {
		return '<form method="post"' . ( $confirm ? ' data-confirm="' . esc_attr( $confirm ) . '"' : '' ) . '>' . zxc_nonce_field( 'zxc_platform' ) . '<input type="hidden" name="action" value="' . esc_attr( $action ) . '"><input type="hidden" name="tenant_id" value="' . (int) $t->id . '"><button class="btn ' . esc_attr( $cls ) . ' btn-sm" type="submit">' . esc_html( $label ) . '</button></form>';
	};
	ob_start();
	?>
<section class="card row wrap-row" style="gap:16px;<?php echo esc_attr( zxc_brand_style( $t ) ); ?>">
  <?php echo zxc_brand_mark( $t, 'style="width:56px;height:56px;border-radius:16px;font-size:22px"' ); // phpcs:ignore ?>
  <div class="grow"><div class="card-title" style="font-size:20px"><?php echo esc_html( $t->name ); ?></div><div class="card-sub"><?php echo esc_html( ( $owner ? $owner->display_name . ' · ' . $owner->user_email : 'No owner' ) . ' · joined ' . zxc_date( $t->created ) ); ?></div></div>
  <?php echo zxc_sub_badge( $t ); // phpcs:ignore ?>
  <a class="btn btn-glass btn-sm" href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>"><?php echo zxc_icon( 'globe' ); // phpcs:ignore ?> View portal</a>
</section>
<section class="kpis">
  <?php
  echo zxc_kpi( 'Revenue this month', zxc_money( $stats['month']->total ), (int) $stats['month']->cnt . ' sales', 'wallet', '', true ); // phpcs:ignore
  echo zxc_kpi( 'Today', zxc_money( $stats['today']->total ), (int) $stats['today']->cnt . ' sales', 'trend', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Online now', (string) $stats['online'], 'connected devices', 'zap', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Active vouchers', (string) $stats['active'], $stats['unused'] . ' unused', 'ticket', 'amber' ); // phpcs:ignore
  ?>
</section>
<div class="split">
  <section class="card wide"><div class="card-head"><div><div class="card-title">Revenue</div><div class="card-sub">Last 14 days</div></div></div><?php echo zxc_bar_chart( array_map( function ( $d ) { return array( 'label' => $d['label'], 'total' => $d['total'], 'count' => $d['count'] ); }, $stats['series'] ) ); // phpcs:ignore ?></section>
  <div class="side-col">
    <section class="card stack"><div class="card-title">Manage</div>
      <div class="kv"><div><span>Plan</span><b><?php echo esc_html( $tiers[ $t->tier ]['name'] ?? $t->tier ); ?></b></div><div><span>Status</span><b><?php echo esc_html( $sub['label'] ); ?></b></div><?php if ( isset( $sub['ends'] ) ) : ?><div><span>Until</span><b><?php echo esc_html( zxc_date( $sub['ends'] ) ); ?></b></div><?php endif; ?><div><span>Flutterwave</span><b><?php echo zxc_gateway_ready( $t ) ? 'Connected' : 'Not connected'; ?></b></div></div>
      <form method="post" class="row" style="gap:8px"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="tier"><input type="hidden" name="tenant_id" value="<?php echo (int) $t->id; ?>"><select class="input" name="tier" style="height:40px"><?php foreach ( $tiers as $k => $x ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $t->tier, $k ); ?>><?php echo esc_html( $x['name'] ); ?></option><?php endforeach; ?></select><button class="btn btn-glass btn-sm" type="submit">Change plan</button></form>
      <div class="row wrap-row" style="gap:8px"><?php echo $form( 'extend', '+30 days', 'btn-soft' ); // phpcs:ignore ?><?php echo 'suspended' === $t->status ? $form( 'activate', 'Re-activate', 'btn-soft' ) : $form( 'suspend', 'Suspend', 'btn-danger', 'Suspend this business? Its portal goes offline.' ); // phpcs:ignore ?></div>
    </section>
    <section class="card stack"><div class="row between"><div class="card-title">Wallet</div><b style="font-family:var(--display);font-size:20px"><?php echo esc_html( zxc_money( zxc_wallet_balance( $t->id ) ) ); ?></b></div>
      <form method="post" class="stack" style="gap:10px"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="wallet_credit"><input type="hidden" name="tenant_id" value="<?php echo (int) $t->id; ?>">
        <div class="row" style="gap:8px"><input class="input" type="number" name="amount" placeholder="Amount (− to debit)" required style="height:40px"><button class="btn btn-soft btn-sm" type="submit">Apply</button></div>
        <input class="input" name="note" placeholder="Note (optional)" style="height:40px"></form></section>
    <section class="card"><div class="card-title" style="margin-bottom:12px">Routers</div><div class="kv"><?php foreach ( $stats['routers'] as $r ) : ?><div><span class="row" style="gap:8px"><span class="live <?php echo $r->last_seen >= time() - 120 ? '' : 'off'; ?>"></span><?php echo esc_html( $r->name ); ?></span><b><?php echo esc_html( $r->last_seen ? zxc_ago( $r->last_seen ) : 'never' ); ?></b></div><?php endforeach; ?></div></section>
  </div>
</div>
<div class="split">
  <section class="card flush wide"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div class="card-title">Recent sales</div></div><?php echo zxc_sales_table( $stats['recent'], true ); // phpcs:ignore ?></section>
  <section class="card flush side-col"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div class="card-title">Subscription payments</div></div>
    <?php if ( $subs ) : ?><div class="table-wrap"><table class="table"><tbody><?php foreach ( $subs as $x ) : ?><tr><td><?php echo esc_html( ( $tiers[ $x->tier ]['name'] ?? $x->tier ) . ' · ' . $x->months . ' mo' ); ?><small><?php echo esc_html( zxc_date( $x->created ) ); ?></small></td><td><?php echo zxc_status_badge( $x->status ); // phpcs:ignore ?></td><td class="r"><b><?php echo esc_html( zxc_money( $x->amount ) ); ?></b></td></tr><?php endforeach; ?></tbody></table></div>
    <?php else : ?><div class="empty" style="padding:24px">No payments yet.</div><?php endif; ?></section>
</div>
	<?php
	return zxc_admin_page( 'businesses', $t->name, ob_get_clean(), array( 'sub' => 'Business details', 'actions' => '<a class="btn btn-ghost desk" href="' . esc_url( zxc_admin_url( 'businesses' ) ) . '">' . zxc_icon( 'back' ) . 'All businesses</a>' ) );
}

function zxc_view_admin_subscriptions( $rows ) {
	$tiers = zxc_tiers();
	$total = 0;
	foreach ( $rows as $r ) {
		$total += 'success' === $r->status ? (int) $r->amount : 0;
	}
	ob_start();
	?>
<section class="kpis"><?php echo zxc_kpi( 'Collected', zxc_money( $total ), 'from listed payments', 'wallet', '', true ) . zxc_kpi( 'Payments', (string) count( $rows ), 'latest 300', 'receipt', 'teal' ); // phpcs:ignore ?></section>
<section class="card flush">
  <?php if ( $rows ) : ?><div class="table-wrap"><table class="table"><thead><tr><th>Business</th><th>Plan</th><th>Reference</th><th>Date</th><th>Status</th><th class="r">Amount</th></tr></thead><tbody>
  <?php foreach ( $rows as $r ) : ?><tr><td><a href="<?php echo esc_url( zxc_admin_url( 'businesses/' . $r->tenant_id ) ); ?>"><b><?php echo esc_html( $r->tenant_name ); ?></b></a><small>/h/<?php echo esc_html( $r->slug ); ?></small></td><td><?php echo esc_html( ( $tiers[ $r->tier ]['name'] ?? $r->tier ) . ' · ' . $r->months . ' mo' ); ?></td><td class="mono fine"><?php echo esc_html( $r->reference ); ?></td><td class="fine"><?php echo esc_html( zxc_date( $r->created ) ); ?></td><td><?php echo zxc_status_badge( $r->status ); // phpcs:ignore ?></td><td class="r"><b><?php echo esc_html( zxc_money( $r->amount ) ); ?></b></td></tr><?php endforeach; ?>
  </tbody></table></div><?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'receipt' ); // phpcs:ignore ?></span><b>No subscription payments yet</b></div><?php endif; ?>
</section>
	<?php
	return zxc_admin_page( 'subscriptions', 'Subscriptions', ob_get_clean(), array( 'sub' => 'Owners’ subscription payments to the platform.' ) );
}

function zxc_view_admin_inbox( $msgs ) {
	$types = array( 'visitor' => array( 'Website', 'badge-soft' ), 'owner' => array( 'Hotspot owner', 'badge-ok' ) );
	ob_start();
	?>
<section class="card">
  <?php if ( $msgs ) : ?><div><?php foreach ( $msgs as $m ) : ?><div class="msg <?php echo $m->is_read ? '' : 'unread'; ?>"><span class="avatar"><?php echo esc_html( strtoupper( mb_substr( $m->name, 0, 1 ) ) ); ?></span><div class="grow">
    <div class="row between wrap-row" style="gap:6px"><b class="subj" style="font-weight:600"><?php echo esc_html( $m->subject ); ?></b><span class="row" style="gap:8px"><span class="badge <?php echo esc_attr( $types[ $m->from_type ][1] ?? '' ); ?>"><?php echo esc_html( $types[ $m->from_type ][0] ?? $m->from_type ); ?></span><span class="fine"><?php echo esc_html( zxc_ago( $m->created ) ); ?></span></span></div>
    <div class="fine" style="margin-top:2px"><?php echo esc_html( $m->name . ( $m->email ? ' · ' . $m->email : '' ) . ( $m->phone ? ' · ' . $m->phone : '' ) ); ?></div>
    <p style="margin-top:8px;font-size:14px;white-space:pre-line;color:var(--ink-2)"><?php echo esc_html( $m->body ); ?></p>
    <div class="row" style="margin-top:10px;gap:8px"><?php if ( $m->email ) : ?><a class="btn btn-soft btn-sm" href="<?php echo esc_url( 'mailto:' . $m->email . '?subject=' . rawurlencode( 'Re: ' . $m->subject ) ); ?>"><?php echo zxc_icon( 'mail' ); // phpcs:ignore ?> Reply</a><?php endif; ?><?php if ( $m->phone ) : ?><a class="btn btn-soft btn-sm" href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', $m->phone ) ) ); ?>"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a><?php endif; ?></div>
  </div></div><?php endforeach; ?></div>
  <?php else : ?><div class="empty"><span class="ico lg"><?php echo zxc_icon( 'inbox' ); // phpcs:ignore ?></span><b>Inbox zero</b><span>Messages from your contact page and hotspot owners appear here.</span></div><?php endif; ?>
</section>
	<?php
	return zxc_admin_page( 'inbox', 'Inbox', ob_get_clean(), array( 'sub' => 'Contact-form messages and owner support requests.' ) );
}

function zxc_view_admin_team( $team ) {
	ob_start();
	?>
<div class="split">
  <section class="card flush wide"><div class="card-head" style="padding:20px 22px 0;margin-bottom:10px"><div class="card-title">Admin team</div></div>
    <div class="table-wrap"><table class="table"><thead><tr><th>Name</th><th>Role</th><th>Joined</th><th></th></tr></thead><tbody>
    <?php foreach ( $team as $u ) : ?><?php $is_wp_admin = user_can( $u, 'manage_options' ); ?><tr><td><div class="row" style="gap:10px"><span class="avatar"><?php echo esc_html( strtoupper( mb_substr( $u->display_name, 0, 1 ) ) ); ?></span><div><b><?php echo esc_html( $u->display_name ); ?></b><small><?php echo esc_html( $u->user_email ); ?></small></div></div></td><td><span class="badge <?php echo $is_wp_admin ? 'badge-accent' : 'badge-soft'; ?>"><?php echo $is_wp_admin ? 'Owner' : 'Admin'; ?></span></td><td class="fine"><?php echo esc_html( zxc_date( strtotime( $u->user_registered ) ) ); ?></td><td class="r"><?php if ( ! $is_wp_admin && get_current_user_id() !== $u->ID ) : ?><form method="post" data-confirm="Remove admin access for this person?"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="team_remove"><input type="hidden" name="user_id" value="<?php echo (int) $u->ID; ?>"><button class="btn btn-danger btn-sm" type="submit">Remove</button></form><?php endif; ?></td></tr><?php endforeach; ?>
    </tbody></table></div></section>
  <form method="post" class="card side-col stack"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="team_add">
    <div class="row"><span class="ico"><?php echo zxc_icon( 'userPlus' ); // phpcs:ignore ?></span><div><div class="card-title">Add an admin</div><div class="card-sub">They sign in at /zx-admin/login and never see WordPress.</div></div></div>
    <label class="field">Full name<input class="input" name="name" required></label>
    <label class="field">Email<input class="input" type="email" name="email" required></label>
    <label class="field">Temporary password <em>10+ characters</em><input class="input" type="password" name="password" minlength="10" required autocomplete="new-password"></label>
    <button class="btn btn-primary btn-block" type="submit"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?> Add admin</button>
  </form>
</div>
	<?php
	return zxc_admin_page( 'team', 'Team', ob_get_clean(), array( 'sub' => 'People who can use this admin console.' ) );
}

function zxc_view_admin_settings() {
	$s    = zxc_settings();
	$mask = $s['flw_secret'] ? substr( $s['flw_secret'], 0, 10 ) . '••••••' . substr( $s['flw_secret'], -4 ) : '';
	ob_start();
	?>
<form method="post" class="stack" style="gap:20px"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="settings">
  <section class="card form-grid"><div class="span-2 row"><span class="ico"><?php echo zxc_icon( 'sparkles' ); // phpcs:ignore ?></span><div><div class="card-title">Brand &amp; contact</div><div class="card-sub">Shown on the website, dashboards and portals (“Powered by …”).</div></div></div>
    <label class="field">Platform name<input class="input" name="platform_name" value="<?php echo esc_attr( $s['platform_name'] ); ?>" required></label>
    <label class="field">Support email<input class="input" type="email" name="support_email" value="<?php echo esc_attr( $s['support_email'] ); ?>"></label>
    <label class="field">Support phone<input class="input" name="support_phone" value="<?php echo esc_attr( $s['support_phone'] ); ?>"></label>
    <label class="field">WhatsApp number<input class="input" name="support_whatsapp" value="<?php echo esc_attr( $s['support_whatsapp'] ); ?>"></label>
    <label class="field span-2">Office address<input class="input" name="address" value="<?php echo esc_attr( $s['address'] ); ?>"></label>
    <label class="check span-2"><input type="checkbox" name="takeover_home" value="1" <?php checked( $s['takeover_home'], '1' ); ?>> Use the <?php echo esc_html( $s['platform_name'] ); ?> landing page as the website homepage</label>
  </section>
  <section class="card form-grid"><div class="span-2 row"><span class="ico teal"><?php echo zxc_icon( 'tag' ); // phpcs:ignore ?></span><div><div class="card-title">Subscription prices</div><div class="card-sub">What hotspot owners pay you each month (₦).</div></div></div>
    <label class="field">Starter<input class="input" type="number" min="0" name="price_starter" value="<?php echo esc_attr( $s['price_starter'] ); ?>"></label>
    <label class="field">Business<input class="input" type="number" min="0" name="price_business" value="<?php echo esc_attr( $s['price_business'] ); ?>"></label>
    <label class="field">Enterprise<input class="input" type="number" min="0" name="price_enterprise" value="<?php echo esc_attr( $s['price_enterprise'] ); ?>"></label>
    <label class="field">Free trial (days)<input class="input" type="number" min="1" name="trial_days" value="<?php echo esc_attr( $s['trial_days'] ); ?>"></label>
  </section>
  <section class="card form-grid"><div class="span-2 row"><span class="ico amber"><?php echo zxc_icon( 'card' ); // phpcs:ignore ?></span><div><div class="card-title">Your Flutterwave account</div><div class="card-sub">Collects owners’ subscription payments. Webhook URL: <code><?php echo esc_html( rest_url( 'zaxconnect/v1/flutterwave/platform' ) ); ?></code></div></div></div>
    <label class="field">Public key<input class="input mono" name="flw_public" value="<?php echo esc_attr( $s['flw_public'] ); ?>" placeholder="FLWPUBK-…"></label>
    <label class="field">Secret key <?php echo $mask ? '<em>saved: ' . esc_html( $mask ) . '</em>' : ''; ?><input class="input mono" type="password" name="flw_secret" placeholder="<?php echo $mask ? 'Leave blank to keep the saved key' : 'FLWSECK-…'; ?>" autocomplete="off"></label>
    <label class="field">Webhook secret hash<input class="input mono" name="flw_hash" value="<?php echo esc_attr( $s['flw_hash'] ); ?>"></label>
    <label class="check" style="align-self:end"><input type="checkbox" name="demo_billing" value="1" <?php checked( $s['demo_billing'], '1' ); ?>> Simulate subscription payments until keys are set</label>
  </section>
  <div class="actions"><button class="btn btn-primary btn-lg" type="submit">Save settings</button></div>
</form>
	<?php
	return zxc_admin_page( 'settings', 'Settings', ob_get_clean(), array( 'sub' => 'Brand, prices and payments for the whole platform.' ) );
}

function zxc_view_admin_account( $user ) {
	ob_start();
	?>
<form method="post" class="card form-grid"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="account">
  <div class="span-2 row"><span class="avatar" style="width:52px;height:52px;font-size:20px;border-radius:16px"><?php echo esc_html( strtoupper( mb_substr( $user->display_name, 0, 1 ) ) ); ?></span><div><div class="card-title"><?php echo esc_html( $user->display_name ); ?></div><div class="card-sub"><?php echo esc_html( $user->user_email ); ?></div></div></div>
  <label class="field">Display name<input class="input" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required></label><span></span>
  <div class="span-2"><hr class="divider"><div class="card-title" style="margin-top:12px">Change password</div><p class="card-sub">Leave blank to keep your current password.</p></div>
  <label class="field">Current password<input class="input" type="password" name="current" autocomplete="current-password"></label><span></span>
  <label class="field">New password <em>10+ characters</em><input class="input" type="password" name="password" minlength="10" autocomplete="new-password"></label>
  <label class="field">Confirm new password<input class="input" type="password" name="confirm" minlength="10" autocomplete="new-password"></label>
  <div class="span-2 actions"><button class="btn btn-primary" type="submit">Save account</button></div>
</form>
	<?php
	return zxc_admin_page( 'account', 'My account', ob_get_clean() );
}
