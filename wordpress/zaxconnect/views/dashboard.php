<?php
/**
 * Hotspot owner dashboard (front-end, separate from wp-admin).
 */
defined( 'ABSPATH' ) || exit;

function zxc_dash_nav() {
	return array(
		''          => array( 'grid', 'Dashboard' ),
		'plans'     => array( 'wifi', 'Plans' ),
		'vouchers'  => array( 'ticket', 'Vouchers' ),
		'sales'     => array( 'card', 'Sales' ),
		'online'    => array( 'users', 'Online users' ),
		'locations' => array( 'router', 'Locations & routers' ),
		'branding'  => array( 'palette', 'Portal & branding' ),
		'payments'  => array( 'wallet', 'Payments' ),
		'billing'   => array( 'star', 'Subscription' ),
		'account'   => array( 'user', 'Account' ),
	);
}

function zxc_dash_url( $page = '' ) {
	return home_url( '/dashboard' . ( $page ? '/' . $page : '' ) );
}

/**
 * Wrap a dashboard page.
 *
 * @param object $t       tenant
 * @param string $active  nav key
 * @param string $title   page title
 * @param string $content page HTML
 * @param array  $o       sub, actions
 */
function zxc_dash_page( $t, $active, $title, $content, array $o = array() ) {
	global $wpdb;
	$sub    = zxc_subscription( $t );
	$online = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zxc_table( 'online' ) . ' WHERE tenant_id = %d AND seen_at >= %d', $t->id, time() - 90 ) );
	$flash  = zxc_take_flash();
	ob_start();
	?>
<div class="app">
  <aside class="side" data-side>
    <?php echo zxc_logo( zxc_dash_url() ); // phpcs:ignore ?>
    <a class="tenant-switch" href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" target="_blank" rel="noopener">
      <?php echo zxc_brand_mark( $t, 'style="background:' . esc_attr( $t->brand_color ) . ';color:' . esc_attr( zxc_accent_ink( $t->brand_color ) ) . '"' ); // phpcs:ignore ?>
      <span style="min-width:0;flex:1"><b><?php echo esc_html( $t->name ); ?></b><small><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) . '/h/' . $t->slug ); ?></small></span>
      <?php echo zxc_icon( 'external' ); // phpcs:ignore ?>
    </a>
    <nav class="side-nav" aria-label="Dashboard">
      <?php foreach ( zxc_dash_nav() as $key => $n ) : ?>
        <a href="<?php echo esc_url( zxc_dash_url( $key ) ); ?>" class="<?php echo $active === $key ? 'active' : ''; ?>"<?php echo $active === $key ? ' aria-current="page"' : ''; ?>><?php echo zxc_icon( $n[0] ); // phpcs:ignore ?><span><?php echo esc_html( $n[1] ); ?></span><?php if ( 'online' === $key && $online ) : ?><span class="count"><?php echo (int) $online; ?></span><?php endif; ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="plan-card">
      <div class="k"><?php echo esc_html( zxc_tiers()[ $t->tier ]['name'] ?? 'Business' ); ?> plan</div>
      <p><?php echo esc_html( 'trial' === $sub['state'] ? 'Free trial · ' . $sub['days'] . ' day' . ( 1 === $sub['days'] ? '' : 's' ) . ' left' : ( 'active' === $sub['state'] ? 'Renews in ' . $sub['days'] . ' days' : $sub['label'] ) ); ?></p>
      <div class="bar"><span class="a-growx" style="width:<?php echo (int) round( $sub['progress'] * 100 ); ?>%"></span></div>
      <a href="<?php echo esc_url( zxc_dash_url( 'billing' ) ); ?>" class="btn btn-white"><?php echo 'active' === $sub['state'] ? 'Manage plan' : 'Upgrade'; ?></a>
    </div>
    <div class="side-foot">
      <?php if ( current_user_can( 'manage_options' ) ) : ?><a href="<?php echo esc_url( home_url( '/zx-admin' ) ); ?>"><?php echo zxc_icon( 'building' ); // phpcs:ignore ?>Platform admin</a><?php endif; ?>
      <a href="<?php echo esc_url( home_url( '/logout' ) ); ?>"><?php echo zxc_icon( 'logout' ); // phpcs:ignore ?>Sign out</a>
    </div>
  </aside>
  <div class="app-drawer-backdrop" data-backdrop></div>
  <div class="app-main">
    <header class="app-top">
      <button type="button" class="icon-btn app-menu-btn" data-menu aria-label="Open menu"><?php echo zxc_icon( 'menu' ); // phpcs:ignore ?></button>
      <div class="title"><h1><?php echo esc_html( $title ); ?></h1><?php if ( ! empty( $o['sub'] ) ) : ?><div class="sub"><?php echo esc_html( $o['sub'] ); ?></div><?php endif; ?></div>
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
      <?php echo $o['actions'] ?? ''; // phpcs:ignore ?>
    </header>
    <div class="app-body">
      <?php if ( ! $sub['active'] ) : ?>
        <?php echo zxc_alert( 'warn', 'Your ' . strtolower( $sub['label'] ) . '. Customers can still use vouchers they already bought, but online sales are paused. <a href="' . esc_url( zxc_dash_url( 'billing' ) ) . '">Choose a plan</a> to continue.', true ); // phpcs:ignore ?>
      <?php endif; ?>
      <?php echo $flash ? zxc_alert( $flash['type'], $flash['text'] ) : ''; // phpcs:ignore ?>
      <?php echo $content; // phpcs:ignore ?>
    </div>
  </div>
</div>
	<?php
	echo zxc_bottom_nav( // phpcs:ignore
		array(
			array( zxc_dash_url(), 'home', 'Home', '' === $active ),
			array( zxc_dash_url( 'sales' ), 'card', 'Sales', 'sales' === $active ),
			array( zxc_dash_url( 'vouchers' ), 'ticket', 'Vouchers', 'vouchers' === $active ),
			array( zxc_dash_url( 'online' ), 'users', 'Online', 'online' === $active ),
			array( 'button:data-menu', 'more', 'More', ! in_array( $active, array( '', 'sales', 'vouchers', 'online' ), true ) ),
		),
		'Dashboard'
	);
	return zxc_page( $title . ' · ' . $t->name, ob_get_clean(), array( 'body_class' => 'has-bottom-nav', 'robots' => 'noindex' ) );
}

function zxc_kpi( $label, $value, $sub, $icon, $tone = '', $hero = false ) {
	return '<div class="kpi' . ( $hero ? ' hero' : '' ) . '"><div class="kpi-top"><span>' . esc_html( $label ) . '</span><span class="ico-tile ' . esc_attr( $tone ) . '">' . zxc_icon( $icon ) . '</span></div><div class="kpi-value">' . esc_html( $value ) . '</div><div class="kpi-sub">' . wp_kses( $sub, array( 'b' => array() ) ) . '</div></div>';
}

function zxc_status_badge( $status ) {
	$map = array( 'success' => array( 'Paid', 'badge-ok' ), 'pending' => array( 'Pending', 'badge-warn' ), 'failed' => array( 'Failed', 'badge-bad' ), 'unused' => array( 'Unused', '' ), 'active' => array( 'Active', 'badge-ok' ), 'expired' => array( 'Expired', 'badge-bad' ), 'disabled' => array( 'Disabled', 'badge-bad' ) );
	$b   = $map[ $status ] ?? array( $status, '' );
	return '<span class="badge ' . esc_attr( $b[1] ) . '">' . esc_html( $b[0] ) . '</span>';
}

function zxc_view_dash_home( $t, array $s ) {
	$user  = wp_get_current_user();
	$hour  = (int) wp_date( 'G', null, new DateTimeZone( 'Africa/Lagos' ) );
	$greet = $hour < 12 ? 'Good morning' : ( $hour < 17 ? 'Good afternoon' : 'Good evening' );
	$first = explode( ' ', trim( $user->display_name ) )[0];
	$delta = $s['yesterday']->total > 0 ? round( ( $s['today']->total - $s['yesterday']->total ) / $s['yesterday']->total * 100 ) : null;
	$max   = 0;
	foreach ( $s['series'] as $d ) {
		$max = max( $max, $d['total'] );
	}
	$max  = $max > 0 ? $max : 1000;
	$nice = $max;
	foreach ( array( 1, 2, 2.5, 5, 10 ) as $m ) {
		$p = pow( 10, floor( log10( $max ) ) );
		if ( $m * $p >= $max ) {
			$nice = $m * $p;
			break;
		}
	}
	$range_total = array_sum( array_column( $s['series'], 'total' ) );
	$router_ok   = false;
	foreach ( $s['routers'] as $r ) {
		$router_ok = $router_ok || $r->last_seen >= time() - 120;
	}
	$checks = array(
		array( zxc_gateway_ready( $t ), 'Connect Flutterwave', 'Receive card, transfer and USSD payments.', zxc_dash_url( 'payments' ) ),
		array( (bool) array_filter( $s['routers'], function ( $r ) { return $r->last_seen > 0; } ), 'Connect your MikroTik router', 'Paste the setup script into your router terminal.', zxc_dash_url( 'locations' ) ),
		array( '' !== $t->support_whatsapp || '' !== $t->support_phone || '' !== $t->logo_url, 'Personalise your portal', 'Add your logo and support contacts.', zxc_dash_url( 'branding' ) ),
	);
	$done = count( array_filter( array_column( $checks, 0 ) ) );
	ob_start();
	?>
<?php if ( $done < count( $checks ) ) : ?>
<section class="card card-pad a-rise">
  <div class="card-head"><div><div class="card-title">Finish setting up</div><div class="card-sub"><?php echo (int) $done; ?> of <?php echo count( $checks ); ?> done. You’re almost live.</div></div></div>
  <div class="checklist">
    <?php foreach ( $checks as $c ) : ?>
      <div class="item <?php echo $c[0] ? 'done' : ''; ?>"><span class="cdot"><?php echo $c[0] ? zxc_icon( 'check' ) : ''; // phpcs:ignore ?></span><div class="spacer"><b style="font-size:14.5px"><?php echo esc_html( $c[1] ); ?></b><p><?php echo esc_html( $c[2] ); ?></p></div><?php if ( ! $c[0] ) : ?><a class="btn btn-soft btn-sm" href="<?php echo esc_url( $c[3] ); ?>">Set up</a><?php endif; ?></div>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<section class="kpis a-rise">
  <?php
  echo zxc_kpi( 'Revenue today', zxc_money( $s['today']->total ), ( null !== $delta ? '<b>' . ( $delta >= 0 ? '+' : '' ) . $delta . '%</b> vs yesterday · ' : '' ) . (int) $s['today']->cnt . ' sales', 'trend', '', true ); // phpcs:ignore
  echo zxc_kpi( 'This month', zxc_money_short( $s['month']->total ), (int) $s['month']->cnt . ' sales', 'card', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Online now', (string) $s['online'], 'connected devices', 'wifi', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Active vouchers', (string) $s['active'], $s['unused'] . ' unused in stock', 'ticket', 'amber' ); // phpcs:ignore
  ?>
</section>

<div class="split">
  <section class="card card-pad wide">
    <div class="card-head">
      <div><div class="card-title">Revenue</div><div class="card-sub"><?php echo esc_html( zxc_money( $range_total ) . ' · last ' . $s['range'] . ' days' ); ?></div></div>
      <nav class="seg-sm" aria-label="Range"><?php foreach ( array( 7, 14, 30 ) as $r ) : ?><a href="<?php echo esc_url( zxc_dash_url() . '?range=' . $r ); ?>" class="<?php echo $s['range'] === $r ? 'on' : ''; ?>"><?php echo (int) $r; ?> days</a><?php endforeach; ?></nav>
    </div>
    <div class="chart" role="img" aria-label="Daily revenue chart">
      <div class="chart-y"><span><?php echo esc_html( zxc_money_short( $nice ) ); ?></span><span><?php echo esc_html( zxc_money_short( $nice / 2 ) ); ?></span><span>₦0</span></div>
      <div class="chart-bars">
        <?php foreach ( $s['series'] as $i => $d ) : ?>
          <div class="chart-col <?php echo count( $s['series'] ) - 1 === $i ? 'today' : ''; ?>" tabindex="0">
            <span class="a-grow" style="height:<?php echo esc_attr( max( $d['total'] ? 2 : 0, round( $d['total'] / $nice * 100, 1 ) ) ); ?>%;animation-delay:<?php echo esc_attr( $i * 0.03 ); ?>s"></span>
            <span class="tip"><?php echo esc_html( $d['label'] . ': ' . zxc_money( $d['total'] ) . ' · ' . $d['count'] . ' sale' . ( 1 === $d['count'] ? '' : 's' ) ); ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="chart-x"><span><?php echo esc_html( $s['series'][0]['label'] ); ?></span><span>Today</span></div>
    <details style="margin-top:12px;font-size:13px"><summary class="muted" style="cursor:pointer;font-weight:600">View as table</summary>
      <div class="table-wrap"><table class="table"><thead><tr><th>Day</th><th class="num">Sales</th><th class="num">Revenue</th></tr></thead><tbody>
      <?php foreach ( array_reverse( $s['series'] ) as $d ) : ?><tr><td><?php echo esc_html( $d['label'] ); ?></td><td class="num"><?php echo (int) $d['count']; ?></td><td class="num"><?php echo esc_html( zxc_money( $d['total'] ) ); ?></td></tr><?php endforeach; ?>
      </tbody></table></div>
    </details>
  </section>
  <div class="narrow-col">
    <section class="card card-pad">
      <div class="card-head"><div><div class="card-title">Top plans</div><div class="card-sub">Last 30 days</div></div></div>
      <?php if ( $s['top'] ) : ?>
        <div class="rank">
          <?php foreach ( $s['top'] as $p ) : ?>
            <div><div class="rank-row"><span><?php echo esc_html( $p->name ); ?></span><b><?php echo esc_html( zxc_money( $p->total ) ); ?></b></div><div class="bar-track"><span class="a-growx" style="width:<?php echo (int) max( 3, round( $p->total / $s['top'][0]->total * 100 ) ); ?>%"></span></div><div class="fine"><?php echo (int) $p->cnt; ?> sales</div></div>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <div class="empty"><span class="ico-tile"><?php echo zxc_icon( 'trend' ); // phpcs:ignore ?></span>No sales yet. Share your portal link to get started.</div>
      <?php endif; ?>
    </section>
    <section class="card card-pad">
      <div class="status-line"><span class="live-dot <?php echo $router_ok ? 'a-pulse' : ''; ?>" style="<?php echo $router_ok ? '' : 'background:var(--border-strong)'; ?>"></span><b><?php echo $router_ok ? 'Router connected' : 'Router not connected'; ?></b></div>
      <div class="kv" style="margin-top:14px">
        <?php foreach ( $s['routers'] as $r ) : ?>
          <div><span><?php echo esc_html( $r->name ); ?></span><b><?php echo esc_html( $r->last_seen ? 'seen ' . zxc_ago( $r->last_seen ) : 'waiting' ); ?></b></div>
        <?php endforeach; ?>
      </div>
      <a class="btn btn-ghost btn-sm btn-block" style="margin-top:14px" href="<?php echo esc_url( zxc_dash_url( 'locations' ) ); ?>">Manage locations</a>
    </section>
  </div>
</div>

<div class="split">
  <section class="card wide" style="overflow:hidden">
    <div class="card-head card-pad" style="margin:0;padding-bottom:12px"><div class="card-title">Recent sales</div><a class="link" href="<?php echo esc_url( zxc_dash_url( 'sales' ) ); ?>">View all</a></div>
    <?php echo zxc_sales_table( $s['recent'], true ); // phpcs:ignore ?>
  </section>
  <div class="narrow-col">
    <section class="card card-pad">
      <div class="card-title" style="margin-bottom:12px">Quick actions</div>
      <div class="action-list">
        <a href="<?php echo esc_url( zxc_dash_url( 'vouchers' ) ); ?>"><span class="ico-tile"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></span>Generate voucher cards<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
        <a href="<?php echo esc_url( zxc_dash_url( 'branding' ) ); ?>"><span class="ico-tile pink"><?php echo zxc_icon( 'palette' ); // phpcs:ignore ?></span>Customise your portal<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
        <a href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" target="_blank" rel="noopener"><span class="ico-tile teal"><?php echo zxc_icon( 'external' ); // phpcs:ignore ?></span>Open your portal<?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
      </div>
    </section>
  </div>
</div>
	<?php
	return zxc_dash_page(
		$t, '', 'Dashboard', ob_get_clean(),
		array(
			'sub'     => $greet . ', ' . $first . '. Here’s how ' . $t->name . ' is doing.',
			'actions' => '<a class="btn btn-primary desk-only" href="' . esc_url( zxc_dash_url( 'vouchers' ) ) . '">' . zxc_icon( 'plus' ) . 'New vouchers</a>',
		)
	);
}

function zxc_sales_table( $rows, $compact = false ) {
	if ( ! $rows ) {
		return '<div class="empty"><span class="ico-tile">' . zxc_icon( 'card' ) . '</span>No transactions yet.</div>';
	}
	ob_start();
	?>
<div class="table-wrap"><table class="table">
  <thead><tr><th>Customer</th><th>Plan</th><?php if ( ! $compact ) : ?><th>Voucher</th><th>Reference</th><?php endif; ?><th>Status</th><th class="num">Amount</th></tr></thead>
  <tbody>
  <?php foreach ( $rows as $r ) : ?>
    <tr>
      <td><b><?php echo esc_html( zxc_mask_phone( $r->phone ) ); ?></b><small><?php echo esc_html( zxc_ago( $r->created ) . ( $r->channel ? ' · ' . ucwords( str_replace( '_', ' ', $r->channel ) ) : '' ) ); ?></small></td>
      <td><?php echo esc_html( $r->plan_name ); ?></td>
      <?php if ( ! $compact ) : ?>
        <td class="mono"><?php echo esc_html( $r->voucher_code ? zxc_format_code( $r->voucher_code ) : '—' ); ?></td>
        <td class="mono fine"><?php echo esc_html( $r->reference ); ?></td>
      <?php endif; ?>
      <td><?php echo zxc_status_badge( $r->status ); // phpcs:ignore ?></td>
      <td class="num"><b><?php echo esc_html( zxc_money( $r->amount ) ); ?></b></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table></div>
	<?php
	return ob_get_clean();
}

function zxc_view_dash_plans( $t, $plans ) {
	ob_start();
	?>
<div class="plan-admin-grid">
  <?php foreach ( $plans as $p ) : ?>
    <article class="plan-admin <?php echo $p->active ? '' : 'off'; ?>">
      <div class="row" style="justify-content:space-between;align-items:flex-start">
        <div><b style="font-size:16.5px"><?php echo esc_html( $p->name ); ?></b><div class="fine"><?php echo esc_html( $p->description ); ?></div></div>
        <div class="row" style="gap:6px;flex-wrap:wrap;justify-content:flex-end"><?php if ( $p->popular ) : ?><span class="badge badge-soft">Popular</span><?php endif; ?><span class="badge <?php echo $p->active ? 'badge-ok' : ''; ?>"><?php echo $p->active ? 'Live' : 'Hidden'; ?></span></div>
      </div>
      <div class="price"><?php echo esc_html( zxc_money( $p->price ) ); ?></div>
      <div class="spec">
        <div><span>Validity</span><b><?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></b></div>
        <div><span>Data</span><b><?php echo esc_html( $p->data_mb ? str_replace( ' data', '', zxc_data_label( $p->data_mb ) ) : 'Unlimited' ); ?></b></div>
        <div><span>Speed</span><b><?php echo esc_html( (float) $p->download_mbps > 0 ? ( 0 + $p->download_mbps ) . '↓ / ' . ( (float) $p->upload_mbps > 0 ? 0 + $p->upload_mbps : '∞' ) . '↑ Mbps' : 'Unlimited' ); ?></b></div>
        <div><span>Devices</span><b><?php echo (int) $p->devices; ?></b></div>
        <div><span>Sold</span><b><?php echo (int) $p->sold; ?></b></div>
      </div>
      <div class="row">
        <a class="btn btn-soft btn-sm" href="<?php echo esc_url( zxc_dash_url( 'plans/' . $p->id ) ); ?>">Edit</a>
        <form method="post"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="plan_toggle"><input type="hidden" name="plan_id" value="<?php echo (int) $p->id; ?>"><button class="btn btn-ghost btn-sm" type="submit"><?php echo $p->active ? 'Hide' : 'Publish'; ?></button></form>
      </div>
    </article>
  <?php endforeach; ?>
</div>
	<?php
	return zxc_dash_page( $t, 'plans', 'Plans', ob_get_clean(), array( 'sub' => 'What customers can buy on your portal.', 'actions' => '<a class="btn btn-primary" href="' . esc_url( zxc_dash_url( 'plans/new' ) ) . '">' . zxc_icon( 'plus' ) . '<span class="desk-only">New plan</span></a>' ) );
}

function zxc_view_dash_plan_form( $t, $plan ) {
	$p      = $plan ? $plan : (object) array( 'id' => 0, 'name' => '', 'description' => '', 'price' => '', 'duration_min' => 60, 'data_mb' => 0, 'download_mbps' => 5, 'upload_mbps' => 2, 'devices' => 1, 'popular' => 0, 'active' => 1, 'sort_order' => 0 );
	$unit   = 0 === $p->duration_min % 1440 ? 'days' : ( 0 === $p->duration_min % 60 ? 'hours' : 'minutes' );
	$amount = 'days' === $unit ? $p->duration_min / 1440 : ( 'hours' === $unit ? $p->duration_min / 60 : $p->duration_min );
	ob_start();
	?>
<form method="post" class="card card-pad form-grid" action="<?php echo esc_url( zxc_dash_url( 'plans' ) ); ?>">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?>
  <input type="hidden" name="action" value="plan_save"><input type="hidden" name="plan_id" value="<?php echo (int) $p->id; ?>">
  <label class="field span-2">Plan name<input class="input" name="name" value="<?php echo esc_attr( $p->name ); ?>" placeholder="e.g. Daily Pass" required maxlength="80"></label>
  <label class="field span-2">Short description<input class="input" name="description" value="<?php echo esc_attr( $p->description ); ?>" placeholder="e.g. Unlimited access for a full day" maxlength="160"></label>
  <label class="field">Price (₦)<input class="input" type="number" name="price" min="0" value="<?php echo esc_attr( $p->price ); ?>" required></label>
  <div class="field">Validity <em>starts at first login</em>
    <div class="row" style="gap:8px"><input class="input" type="number" name="duration_value" min="1" value="<?php echo esc_attr( $amount ); ?>" required>
      <select class="input" name="duration_unit" style="max-width:140px"><?php foreach ( array( 'minutes', 'hours', 'days' ) as $u ) : ?><option <?php selected( $u, $unit ); ?>><?php echo esc_html( $u ); ?></option><?php endforeach; ?></select></div>
  </div>
  <label class="field">Data cap (MB) <em>0 = unlimited</em><input class="input" type="number" name="data_mb" min="0" value="<?php echo esc_attr( $p->data_mb ); ?>"></label>
  <label class="field">Devices per voucher<input class="input" type="number" name="devices" min="1" max="10" value="<?php echo esc_attr( $p->devices ); ?>"></label>
  <label class="field">Download speed (Mbps) <em>0 = unlimited</em><input class="input" type="number" step="0.5" name="download_mbps" min="0" value="<?php echo esc_attr( 0 + $p->download_mbps ); ?>"></label>
  <label class="field">Upload speed (Mbps) <em>0 = unlimited</em><input class="input" type="number" step="0.5" name="upload_mbps" min="0" value="<?php echo esc_attr( 0 + $p->upload_mbps ); ?>"></label>
  <label class="field">Display order<input class="input" type="number" name="sort_order" value="<?php echo esc_attr( $p->sort_order ); ?>"></label>
  <div class="field">Options
    <label class="check"><input type="checkbox" name="active" value="1" <?php checked( (int) $p->active, 1 ); ?>> Show on portal</label>
    <label class="check"><input type="checkbox" name="popular" value="1" <?php checked( (int) $p->popular, 1 ); ?>> Highlight as “Popular”</label>
  </div>
  <div class="span-2 form-actions"><a class="btn btn-ghost" href="<?php echo esc_url( zxc_dash_url( 'plans' ) ); ?>">Cancel</a><button class="btn btn-primary" type="submit"><?php echo $plan ? 'Save changes' : 'Create plan'; ?></button></div>
</form>
	<?php
	return zxc_dash_page( $t, 'plans', $plan ? 'Edit ' . $plan->name : 'New plan', ob_get_clean() );
}

function zxc_view_dash_vouchers( $t, array $a ) {
	$allowance = zxc_voucher_allowance( $t );
	ob_start();
	?>
<div class="split">
  <section class="card wide" style="overflow:hidden">
    <form class="filters card-pad" method="get" style="padding-bottom:14px">
      <select class="input" name="status" onchange="this.form.submit()">
        <?php foreach ( array( 'all' => 'All vouchers', 'unused' => 'Unused', 'active' => 'Active', 'expired' => 'Expired', 'disabled' => 'Disabled' ) as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $a['status'], $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
      </select>
      <input class="input" name="q" value="<?php echo esc_attr( $a['q'] ); ?>" placeholder="Code, phone or batch">
      <button class="btn btn-ghost" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?><span class="sr">Search</span></button>
    </form>
    <?php if ( $a['rows'] ) : ?>
      <div class="table-wrap"><table class="table">
        <thead><tr><th>Code</th><th>Plan</th><th>Status</th><th>Usage</th><th>Time left</th><th></th></tr></thead>
        <tbody>
        <?php foreach ( $a['rows'] as $v ) : ?>
          <tr>
            <td><b class="mono"><?php echo esc_html( zxc_format_code( $v->code ) ); ?></b><small><?php echo esc_html( 'online' === $v->source ? ( $v->phone ? zxc_mask_phone( $v->phone ) : 'Online sale' ) : 'Batch ' . $v->batch ); ?></small></td>
            <td><?php echo esc_html( $v->plan_name ); ?></td>
            <td><?php echo zxc_status_badge( $v->status ); // phpcs:ignore ?></td>
            <td><?php echo esc_html( zxc_bytes( $v->bytes_used ) ); ?><small><?php echo esc_html( $v->mac ? $v->mac : 'No device yet' ); ?></small></td>
            <td class="fine"><?php echo esc_html( $v->expires_at ? ( $v->expires_at > time() ? zxc_remaining( $v->expires_at - time() ) : 'Ended ' . zxc_date( $v->expires_at ) ) : 'Not started' ); ?></td>
            <td class="num"><form method="post"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="voucher_toggle"><input type="hidden" name="voucher_id" value="<?php echo (int) $v->id; ?>"><button class="btn btn-ghost btn-sm" type="submit"><?php echo 'disabled' === $v->status ? 'Enable' : 'Disable'; ?></button></form></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table></div>
    <?php else : ?>
      <div class="empty"><span class="ico-tile"><?php echo zxc_icon( 'ticket' ); // phpcs:ignore ?></span>No vouchers match.</div>
    <?php endif; ?>
  </section>
  <div class="narrow-col">
    <section class="card card-pad">
      <div class="card-title">Generate voucher cards</div>
      <p class="card-sub" style="margin-bottom:16px">Print cards to sell through attendants or shops.</p>
      <form method="post" class="stack">
        <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="vouchers_generate">
        <label class="field">Plan<select class="input" name="plan_id" required><?php foreach ( $a['plans'] as $p ) : ?><option value="<?php echo (int) $p->id; ?>"><?php echo esc_html( $p->name . ' · ' . zxc_money( $p->price ) ); ?></option><?php endforeach; ?></select></label>
        <label class="field">Quantity<input class="input" type="number" name="quantity" min="1" max="<?php echo (int) min( 500, $allowance ); ?>" value="<?php echo (int) min( 20, $allowance ); ?>" required></label>
        <?php if ( PHP_INT_MAX !== $allowance ) : ?><p class="fine"><?php echo (int) $allowance; ?> vouchers left this month on your plan.</p><?php endif; ?>
        <button class="btn btn-primary btn-block" type="submit" <?php disabled( $allowance < 1 ); ?>><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?> Generate</button>
      </form>
    </section>
    <section class="card card-pad">
      <div class="card-title" style="margin-bottom:12px">Recent batches</div>
      <?php if ( $a['batches'] ) : ?>
        <div class="action-list">
          <?php foreach ( $a['batches'] as $b ) : ?>
            <a href="<?php echo esc_url( zxc_dash_url( 'vouchers/print/' . rawurlencode( $b->batch ) ) ); ?>" target="_blank" rel="noopener"><span class="ico-tile"><?php echo zxc_icon( 'printer' ); // phpcs:ignore ?></span><span class="spacer" style="font-weight:600"><b style="display:block"><?php echo esc_html( $b->batch ); ?></b><small class="fine"><?php echo esc_html( $b->plan_name . ' · ' . $b->cnt . ' cards · ' . (int) $b->unused . ' unused' ); ?></small></span><?php echo zxc_icon( 'chevron', 'go' ); // phpcs:ignore ?></a>
          <?php endforeach; ?>
        </div>
      <?php else : ?>
        <div class="empty" style="padding:16px">No batches yet.</div>
      <?php endif; ?>
    </section>
  </div>
</div>
	<?php
	return zxc_dash_page( $t, 'vouchers', 'Vouchers', ob_get_clean(), array( 'sub' => 'Every voucher sold online or printed.' ) );
}

function zxc_view_dash_print( $t, $batch, $rows ) {
	ob_start();
	?>
<div class="print-bar"><b><?php echo count( $rows ); ?> voucher cards · <?php echo esc_html( $batch ); ?></b><button class="btn btn-primary" onclick="window.print()"><?php echo zxc_icon( 'printer' ); // phpcs:ignore ?> Print</button></div>
<div class="cards-sheet">
  <?php foreach ( $rows as $v ) : ?>
    <div class="vcard">
      <div class="vcard-top"><span class="brand"><?php echo zxc_brand_mark( $t ); // phpcs:ignore ?><b style="font-size:13px"><?php echo esc_html( $t->name ); ?></b></span><span class="vcard-price"><?php echo esc_html( zxc_money( $v->price ) ); ?></span></div>
      <div class="vcard-plan"><?php echo esc_html( $v->plan_name . ' · ' . zxc_duration( $v->duration_min ) . ( $v->data_mb ? ' · ' . zxc_data_label( $v->data_mb ) : '' ) ); ?></div>
      <div class="vcard-code"><?php echo esc_html( zxc_format_code( $v->code ) ); ?></div>
      <div class="vcard-help">Connect to <b><?php echo esc_html( $t->name ); ?></b> Wi-Fi → tap “Voucher” → enter code.</div>
    </div>
  <?php endforeach; ?>
</div>
	<?php
	return zxc_page( 'Voucher cards ' . $batch, ob_get_clean(), array( 'body_class' => 'print-body', 'style' => zxc_brand_style( $t ), 'robots' => 'noindex' ) );
}

function zxc_view_dash_sales( $t, array $a ) {
	ob_start();
	?>
<section class="kpis">
  <?php
  echo zxc_kpi( 'Successful sales', (string) $a['totals']->cnt, 'all time', 'check', 'teal' ); // phpcs:ignore
  echo zxc_kpi( 'Total revenue', zxc_money( $a['totals']->total ), 'all time', 'trend' ); // phpcs:ignore
  echo zxc_kpi( 'Average sale', zxc_money( $a['totals']->cnt ? round( $a['totals']->total / $a['totals']->cnt ) : 0 ), 'per transaction', 'card', 'amber' ); // phpcs:ignore
  ?>
</section>
<section class="card" style="overflow:hidden">
  <form class="filters card-pad" method="get" style="padding-bottom:14px">
    <select class="input" name="status" onchange="this.form.submit()">
      <?php foreach ( array( 'all' => 'All statuses', 'success' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed' ) as $k => $label ) : ?><option value="<?php echo esc_attr( $k ); ?>" <?php selected( $a['status'], $k ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
    </select>
    <input class="input" name="q" value="<?php echo esc_attr( $a['q'] ); ?>" placeholder="Search phone or reference">
    <button class="btn btn-ghost" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?><span class="sr">Search</span></button>
  </form>
  <?php echo zxc_sales_table( $a['rows'] ); // phpcs:ignore ?>
</section>
	<?php
	return zxc_dash_page( $t, 'sales', 'Sales', ob_get_clean(), array( 'actions' => '<a class="btn btn-ghost" href="' . esc_url( zxc_dash_url( 'sales/export' ) ) . '">' . zxc_icon( 'download' ) . '<span class="desk-only">Export CSV</span></a>' ) );
}

function zxc_view_dash_online( $t, $rows ) {
	ob_start();
	?>
<section class="card" style="overflow:hidden">
  <?php if ( $rows ) : ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Voucher</th><th>Plan</th><th>Device</th><th>Location</th><th class="num">Data used</th><th>Time left</th></tr></thead>
      <tbody>
      <?php foreach ( $rows as $r ) : ?>
        <tr>
          <td><b class="mono"><?php echo esc_html( zxc_format_code( $r->code ) ); ?></b><small>online <?php echo esc_html( zxc_ago( $r->first_seen ) ); ?></small></td>
          <td><?php echo esc_html( $r->plan_name ? $r->plan_name : '—' ); ?></td>
          <td class="mono fine"><?php echo esc_html( $r->mac ); ?></td>
          <td><?php echo esc_html( $r->router_name ); ?></td>
          <td class="num"><?php echo esc_html( zxc_bytes( $r->bytes ) ); ?></td>
          <td class="fine"><?php echo esc_html( $r->expires_at ? zxc_remaining( $r->expires_at - time() ) : '—' ); ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else : ?>
    <div class="empty"><span class="ico-tile blue"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span><b style="color:var(--ink)">Nobody is online right now</b><span>Connected customers appear here within seconds of logging in.</span></div>
  <?php endif; ?>
</section>
	<?php
	return zxc_dash_page( $t, 'online', 'Online users', ob_get_clean(), array( 'actions' => '<span class="badge badge-ok"><span class="live-dot a-pulse"></span>' . count( $rows ) . ' online</span>' ) );
}

function zxc_view_dash_locations( $t, $routers ) {
	ob_start();
	?>
<section class="card card-pad">
  <div class="card-title">How your network connects</div>
  <div class="topology" style="margin:16px 0">
    <div class="node"><b>MTN ODU</b><small>Internet source</small></div><div class="ln"></div>
    <div class="node brand-node"><b>MikroTik router</b><small>Hotspot + walled garden</small></div><div class="ln"></div>
    <div class="node"><b>Access points</b><small>Customers’ Wi-Fi</small></div>
  </div>
  <p class="muted" style="font-size:14px">The MikroTik shows your portal to new devices. Every 10 seconds it checks in with <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?>, picks up new vouchers and enforces time, speed and data limits. It works behind MTN’s network with no public IP needed.</p>
</section>

<?php foreach ( $routers as $r ) : ?>
  <?php $online = $r->last_seen >= time() - 120; ?>
  <section class="card card-pad stack" style="gap:18px">
    <div class="card-head" style="margin:0">
      <div class="row"><span class="ico-tile <?php echo $online ? 'ok' : 'amber'; ?>"><?php echo zxc_icon( 'router' ); // phpcs:ignore ?></span><div><div class="card-title"><?php echo esc_html( $r->name ); ?></div><div class="card-sub"><?php echo esc_html( $r->last_seen ? 'Last check-in ' . zxc_ago( $r->last_seen ) : 'Waiting for the router to check in' ); ?></div></div></div>
      <div class="row"><span class="badge <?php echo $online ? 'badge-ok' : 'badge-warn'; ?>"><?php if ( $online ) : ?><span class="live-dot a-pulse"></span><?php endif; ?><?php echo $online ? 'Connected' : 'Not connected'; ?></span>
        <?php if ( count( $routers ) > 1 ) : ?><form method="post" data-confirm="Remove this location? Its router stops receiving vouchers."><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="location_delete"><input type="hidden" name="router_id" value="<?php echo (int) $r->id; ?>"><button class="icon-btn" type="submit" aria-label="Remove location"><?php echo zxc_icon( 'trash' ); // phpcs:ignore ?></button></form><?php endif; ?></div>
    </div>
    <ol class="steps-list">
      <li>Connect the MTN ODU’s LAN cable to <b>ether1</b> of the MikroTik, and your access points to the other ports.</li>
      <li>In WinBox open <b>IP → DHCP Client</b> and add one on <b>ether1</b>.</li>
      <li>Open <b>IP → Hotspot → Hotspot Setup</b>, choose your LAN bridge, accept the defaults, and use <code><?php echo esc_html( $t->hotspot_dns ); ?></code> as the DNS name. Skip creating a local user.</li>
      <li>Open <b>New Terminal</b> and paste the script below.</li>
      <li>Download <a class="link" href="<?php echo esc_url( zxc_dash_url( 'locations/' . $r->id . '/login.html' ) ); ?>">login.html</a> and <a class="link" href="<?php echo esc_url( zxc_dash_url( 'locations/' . $r->id . '/alogin.html' ) ); ?>">alogin.html</a>, then drag both into the <code>hotspot</code> folder in <b>Files</b>.</li>
    </ol>
    <div>
      <div class="row" style="justify-content:space-between;margin-bottom:10px"><b style="font-size:14px">Router setup script</b><button type="button" class="btn btn-soft btn-sm" data-copy="#script-<?php echo (int) $r->id; ?>"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy script</button></div>
      <pre class="code-block" id="script-<?php echo (int) $r->id; ?>"><?php echo esc_html( zxc_router_script( $t, $r ) ); ?></pre>
    </div>
  </section>
<?php endforeach; ?>

<section class="card card-pad">
  <div class="card-title">Add another location</div>
  <p class="card-sub" style="margin-bottom:14px">Each location gets its own router script. Vouchers work at all your locations.</p>
  <?php if ( zxc_can_add_router( $t ) ) : ?>
    <form method="post" class="filters"><?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="location_add"><input class="input" name="name" placeholder="e.g. Ikeja branch" required maxlength="80"><button class="btn btn-primary" type="submit"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?> Add location</button></form>
  <?php else : ?>
    <?php echo zxc_alert( 'info', 'Your plan includes ' . count( $routers ) . ' location' . ( count( $routers ) > 1 ? 's' : '' ) . '. <a href="' . esc_url( zxc_dash_url( 'billing' ) ) . '">Upgrade</a> to add more.', true ); // phpcs:ignore ?>
  <?php endif; ?>
</section>
	<?php
	return zxc_dash_page( $t, 'locations', 'Locations & routers', ob_get_clean(), array( 'sub' => 'Connect a MikroTik router at each hotspot location.' ) );
}

function zxc_view_dash_branding( $t ) {
	ob_start();
	?>
<form method="post" enctype="multipart/form-data" class="split">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="branding">
  <section class="card card-pad form-grid wide" style="align-content:start">
    <label class="field">Business name<input class="input" name="name" value="<?php echo esc_attr( $t->name ); ?>" required maxlength="80"></label>
    <label class="field">Location <em>optional</em><input class="input" name="location" value="<?php echo esc_attr( $t->location ); ?>" placeholder="e.g. Lekki Phase 1, Lagos"></label>
    <label class="field span-2">Headline on your portal<input class="input" name="tagline" value="<?php echo esc_attr( $t->tagline ); ?>" maxlength="160"></label>
    <div class="field span-2">Brand colour
      <div class="swatches" data-swatches="#brand_color">
        <?php foreach ( zxc_swatches() as $c => $n ) : ?><button type="button" class="swatch <?php echo strtolower( $c ) === strtolower( $t->brand_color ) ? 'on' : ''; ?>" data-color="<?php echo esc_attr( $c ); ?>" style="background:<?php echo esc_attr( $c ); ?>;--sw:<?php echo esc_attr( $c ); ?>" aria-label="<?php echo esc_attr( $n ); ?>"></button><?php endforeach; ?>
        <input class="input mono" id="brand_color" name="brand_color" value="<?php echo esc_attr( $t->brand_color ); ?>" pattern="#[0-9A-Fa-f]{6}" style="width:120px;height:44px" aria-label="Custom colour (hex)">
      </div>
    </div>
    <div class="logo-drop span-2">
      <?php echo zxc_brand_mark( $t, 'style="width:56px;height:56px;border-radius:16px;font-size:24px"' ); // phpcs:ignore ?>
      <div class="spacer"><b style="font-size:14px">Logo</b><div class="fine" id="logo-msg">PNG, JPG or WebP, under 500 KB</div><?php if ( $t->logo_url ) : ?><label class="check" style="font-size:13px"><input type="checkbox" name="remove_logo" value="1"> Remove current logo</label><?php endif; ?></div>
      <label class="btn btn-ghost btn-sm">Upload<input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" class="sr" data-logo-input="#logo_preview" data-logo-msg="#logo-msg"></label><input type="hidden" id="logo_preview">
    </div>
    <label class="field">Support phone<input class="input" name="support_phone" value="<?php echo esc_attr( $t->support_phone ); ?>" placeholder="0803 123 4567"></label>
    <label class="field">WhatsApp number<input class="input" name="support_whatsapp" value="<?php echo esc_attr( $t->support_whatsapp ); ?>" placeholder="0803 123 4567"></label>
    <label class="field span-2">Hotspot DNS name <em>advanced: must match your MikroTik hotspot setup</em><input class="input mono" name="hotspot_dns" value="<?php echo esc_attr( $t->hotspot_dns ); ?>"></label>
    <div class="span-2 form-actions"><a class="btn btn-ghost" href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" target="_blank" rel="noopener"><?php echo zxc_icon( 'external' ); // phpcs:ignore ?> View portal</a><button class="btn btn-primary" type="submit">Save branding</button></div>
  </section>
  <div class="narrow-col">
    <section class="card card-pad">
      <div class="card-title">Your portal link</div>
      <p class="card-sub" style="margin-bottom:12px">Customers land here when they join your Wi-Fi. You can also share it.</p>
      <div class="code-value" style="font-size:13px;letter-spacing:0;padding:12px" id="portal-link"><?php echo esc_html( zxc_portal_url( $t ) ); ?></div>
      <button type="button" class="btn btn-soft btn-sm btn-block" style="margin-top:10px" data-copy="#portal-link"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy link</button>
    </section>
  </div>
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
  <section class="card card-pad stack wide">
    <div class="row"><span class="ico-tile amber"><?php echo zxc_icon( 'card', 'a-float' ); // phpcs:ignore ?></span><div><div class="card-title">Flutterwave</div><div class="card-sub">Customer payments go straight to your own Flutterwave account.</div></div>
      <span class="spacer"></span><span class="badge <?php echo zxc_gateway_ready( $t ) ? 'badge-ok' : 'badge-warn'; ?>"><?php echo zxc_gateway_ready( $t ) ? 'Connected' : 'Not connected'; ?></span></div>
    <label class="field">Public key<input class="input mono" name="flw_public" value="<?php echo esc_attr( $t->flw_public ); ?>" placeholder="FLWPUBK-…" autocomplete="off"></label>
    <label class="field">Secret key <?php echo $t->flw_secret ? '<em>saved: ' . esc_html( $mask( $t->flw_secret ) ) . '</em>' : ''; ?><input class="input mono" type="password" name="flw_secret" placeholder="<?php echo $t->flw_secret ? 'Leave blank to keep the saved key' : 'FLWSECK-…'; ?>" autocomplete="off"></label>
    <label class="field">Webhook secret hash <em>recommended</em><input class="input mono" name="flw_hash" value="<?php echo esc_attr( $t->flw_hash ); ?>" autocomplete="off"></label>
    <label class="check"><input type="checkbox" name="demo_payments" value="1" <?php checked( (int) $t->demo_payments, 1 ); ?>> Allow simulated test payments while Flutterwave is not connected</label>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Save payment settings</button></div>
  </section>
  <div class="narrow-col">
    <section class="card card-pad">
      <div class="card-title" style="margin-bottom:10px">Setup steps</div>
      <ol class="steps-list" style="font-size:14px">
        <li>Log in at <b>dashboard.flutterwave.com</b>.</li>
        <li>Go to <b>Settings → API Keys</b>, copy the public and secret keys here.</li>
        <li>Go to <b>Settings → Webhooks</b>, set the URL below, type any secret hash, and paste the same hash here.</li>
      </ol>
      <div class="code-value" style="font-size:12px;letter-spacing:0;padding:12px;margin-top:12px;word-break:break-all" id="wh"><?php echo esc_html( rest_url( 'zaxconnect/v1/flutterwave/' . $t->slug ) ); ?></div>
      <button type="button" class="btn btn-soft btn-sm btn-block" style="margin-top:10px" data-copy="#wh"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy webhook URL</button>
    </section>
  </div>
</form>
	<?php
	return zxc_dash_page( $t, 'payments', 'Payments', ob_get_clean() );
}

function zxc_view_dash_billing( $t, $history ) {
	$sub   = zxc_subscription( $t );
	$tiers = zxc_tiers();
	ob_start();
	?>
<section class="card card-pad row" style="flex-wrap:wrap;gap:16px">
  <span class="ico-tile" style="width:52px;height:52px;border-radius:16px"><?php echo zxc_icon( 'star', 'a-float' ); // phpcs:ignore ?></span>
  <div class="spacer"><div class="card-title"><?php echo esc_html( $sub['label'] ); ?></div><div class="card-sub"><?php echo esc_html( isset( $sub['ends'] ) ? ( 'trial' === $sub['state'] ? 'Trial ends ' : 'Paid until ' ) . zxc_date( $sub['ends'] ) . ' · ' . $sub['days'] . ' days left' : 'Choose a plan below to keep selling online.' ); ?></div></div>
  <span class="badge <?php echo $sub['active'] ? 'badge-ok' : 'badge-bad'; ?>"><?php echo esc_html( $tiers[ $t->tier ]['name'] ?? '' ); ?></span>
</section>
<div class="mk-grid" style="margin-top:0">
  <?php foreach ( $tiers as $k => $tier ) : ?>
    <form method="post" class="tier <?php echo $t->tier === $k ? 'best' : ''; ?>">
      <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="subscribe"><input type="hidden" name="tier" value="<?php echo esc_attr( $k ); ?>">
      <div class="row" style="justify-content:space-between"><b style="font-size:18px"><?php echo esc_html( $tier['name'] ); ?></b><?php if ( $t->tier === $k ) : ?><span class="badge badge-soft">Current</span><?php endif; ?></div>
      <p class="muted" style="margin-top:6px;font-size:14px"><?php echo esc_html( $tier['desc'] ); ?></p>
      <div class="tier-price"><b><?php echo esc_html( zxc_money( $tier['price'] ) ); ?></b><span class="muted">/ month</span></div>
      <label class="field" style="margin-top:16px">Pay for<select class="input" name="months"><option value="1">1 month</option><option value="3">3 months</option><option value="6">6 months</option><option value="12">12 months</option></select></label>
      <button class="btn <?php echo $t->tier === $k ? 'btn-primary' : 'btn-soft'; ?> btn-block" style="margin-top:14px" type="submit"><?php echo $t->tier === $k && 'active' === $sub['state'] ? 'Renew' : 'Choose ' . esc_html( $tier['name'] ); ?></button>
      <ul><?php foreach ( $tier['features'] as $f ) : ?><li><?php echo zxc_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $f ); ?></span></li><?php endforeach; ?></ul>
    </form>
  <?php endforeach; ?>
</div>
<?php if ( $history ) : ?>
<section class="card" style="overflow:hidden">
  <div class="card-head card-pad" style="margin:0;padding-bottom:12px"><div class="card-title">Billing history</div></div>
  <div class="table-wrap"><table class="table"><thead><tr><th>Date</th><th>Plan</th><th>Reference</th><th>Status</th><th class="num">Amount</th></tr></thead><tbody>
  <?php foreach ( $history as $h ) : ?><tr><td><?php echo esc_html( zxc_date( $h->created ) ); ?></td><td><?php echo esc_html( ( $tiers[ $h->tier ]['name'] ?? $h->tier ) . ' · ' . $h->months . ' mo' ); ?></td><td class="mono fine"><?php echo esc_html( $h->reference ); ?></td><td><?php echo zxc_status_badge( $h->status ); // phpcs:ignore ?></td><td class="num"><b><?php echo esc_html( zxc_money( $h->amount ) ); ?></b></td></tr><?php endforeach; ?>
  </tbody></table></div>
</section>
<?php endif; ?>
	<?php
	return zxc_dash_page( $t, 'billing', 'Subscription', ob_get_clean(), array( 'sub' => 'Your ' . zxc_setting( 'platform_name' ) . ' plan.' ) );
}

function zxc_view_dash_billing_demo( $t, $s ) {
	ob_start();
	?>
<form method="post" class="card card-pad stack center narrow" style="justify-items:center">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="subscribe_demo"><input type="hidden" name="reference" value="<?php echo esc_attr( $s->reference ); ?>">
  <span class="badge badge-warn">Test payment</span>
  <h1 style="font-size:32px"><?php echo esc_html( zxc_money( $s->amount ) ); ?></h1>
  <p class="muted">The platform has not connected Flutterwave yet, so this subscription payment is simulated.</p>
  <button class="btn btn-primary btn-lg btn-block" type="submit">Simulate successful payment</button>
  <a class="btn btn-ghost btn-block" href="<?php echo esc_url( zxc_dash_url( 'billing' ) ); ?>">Cancel</a>
</form>
	<?php
	return zxc_dash_page( $t, 'billing', 'Subscription payment', ob_get_clean() );
}

function zxc_view_dash_account( $t, $user ) {
	ob_start();
	?>
<form method="post" class="card card-pad form-grid">
  <?php echo zxc_nonce_field( 'zxc_dash' ); // phpcs:ignore ?><input type="hidden" name="action" value="account">
  <label class="field">Your name<input class="input" name="display_name" value="<?php echo esc_attr( $user->display_name ); ?>" required></label>
  <label class="field">Email address <em>contact support to change</em><input class="input" value="<?php echo esc_attr( $user->user_email ); ?>" disabled></label>
  <div class="span-2"><div class="card-title" style="margin-top:8px">Change password</div><p class="card-sub">Leave blank to keep your current password.</p></div>
  <label class="field">Current password<input class="input" type="password" name="current" autocomplete="current-password"></label>
  <span></span>
  <label class="field">New password<input class="input" type="password" name="password" minlength="8" autocomplete="new-password"></label>
  <label class="field">Confirm new password<input class="input" type="password" name="confirm" minlength="8" autocomplete="new-password"></label>
  <div class="span-2 form-actions"><button class="btn btn-primary" type="submit">Save account</button></div>
</form>
	<?php
	return zxc_dash_page( $t, 'account', 'Account', ob_get_clean() );
}
