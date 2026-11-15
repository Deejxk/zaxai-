<?php
/**
 * Platform operator panel (front-end, for WordPress administrators).
 */
defined( 'ABSPATH' ) || exit;

function zxc_platform_page( $active, $title, $content, $sub = '' ) {
	$flash = zxc_take_flash();
	$nav   = array(
		''         => array( 'grid', 'Overview' ),
		'settings' => array( 'gear', 'Platform settings' ),
	);
	ob_start();
	?>
<div class="app">
  <aside class="side" data-side>
    <?php echo zxc_logo( home_url( '/zx-admin' ) ); // phpcs:ignore ?>
    <div class="tenant-switch"><span class="brand-mark" style="background:#4F3BF0;color:#fff"><?php echo zxc_icon( 'building' ); // phpcs:ignore ?></span><span style="min-width:0"><b>Platform admin</b><small><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?></small></span></div>
    <nav class="side-nav" aria-label="Platform">
      <?php foreach ( $nav as $k => $n ) : ?>
        <a href="<?php echo esc_url( home_url( '/zx-admin' . ( $k ? '/' . $k : '' ) ) ); ?>" class="<?php echo $active === $k ? 'active' : ''; ?>"><?php echo zxc_icon( $n[0] ); // phpcs:ignore ?><span><?php echo esc_html( $n[1] ); ?></span></a>
      <?php endforeach; ?>
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php echo zxc_icon( 'external' ); // phpcs:ignore ?><span>View website</span></a>
    </nav>
    <div class="side-foot" style="margin-top:auto">
      <?php if ( zxc_tenant_for_user( get_current_user_id() ) ) : ?><a href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?>My hotspot dashboard</a><?php endif; ?>
      <a href="<?php echo esc_url( admin_url() ); ?>"><?php echo zxc_icon( 'gear' ); // phpcs:ignore ?>WordPress admin</a>
      <a href="<?php echo esc_url( home_url( '/logout' ) ); ?>"><?php echo zxc_icon( 'logout' ); // phpcs:ignore ?>Sign out</a>
    </div>
  </aside>
  <div class="app-drawer-backdrop" data-backdrop></div>
  <div class="app-main">
    <header class="app-top">
      <button type="button" class="icon-btn app-menu-btn" data-menu aria-label="Open menu"><?php echo zxc_icon( 'menu' ); // phpcs:ignore ?></button>
      <div class="title"><h1><?php echo esc_html( $title ); ?></h1><?php if ( $sub ) : ?><div class="sub"><?php echo esc_html( $sub ); ?></div><?php endif; ?></div>
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
    </header>
    <div class="app-body">
      <?php echo $flash ? zxc_alert( $flash['type'], $flash['text'] ) : ''; // phpcs:ignore ?>
      <?php echo $content; // phpcs:ignore ?>
    </div>
  </div>
</div>
	<?php
	echo zxc_bottom_nav( // phpcs:ignore
		array(
			array( home_url( '/zx-admin' ), 'grid', 'Overview', '' === $active ),
			array( home_url( '/zx-admin/settings' ), 'gear', 'Settings', 'settings' === $active ),
			array( home_url( '/' ), 'external', 'Website' ),
			array( 'button:data-menu', 'more', 'More' ),
		),
		'Platform'
	);
	return zxc_page( $title . ' · Platform admin', ob_get_clean(), array( 'body_class' => 'has-bottom-nav', 'robots' => 'noindex' ) );
}

function zxc_view_platform_home( $tenants, $stats ) {
	$tiers = zxc_tiers();
	ob_start();
	?>
<section class="kpis a-rise">
  <?php
  echo zxc_kpi( 'Monthly recurring revenue', zxc_money( $stats['mrr'] ), (int) $stats['paying'] . ' paying businesses', 'trend', '', true ); // phpcs:ignore
  echo zxc_kpi( 'Businesses', (string) count( $tenants ), (int) $stats['trials'] . ' on free trial', 'building', 'blue' ); // phpcs:ignore
  echo zxc_kpi( 'Subscription revenue', zxc_money_short( $stats['subs_rev'] ), 'all time', 'star', 'amber' ); // phpcs:ignore
  echo zxc_kpi( 'Wi-Fi sales processed', zxc_money_short( $stats['gmv'] ), 'paid to owners', 'card', 'teal' ); // phpcs:ignore
  ?>
</section>
<section class="card" style="overflow:hidden">
  <div class="card-head card-pad" style="margin:0;padding-bottom:12px"><div><div class="card-title">Hotspot businesses</div><div class="card-sub">Everyone who signed up at <?php echo esc_html( home_url( '/signup' ) ); ?></div></div></div>
  <?php if ( $tenants ) : ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Business</th><th>Owner</th><th>Plan</th><th>Status</th><th>Router</th><th class="num">Sales</th><th></th></tr></thead>
      <tbody>
      <?php foreach ( $tenants as $t ) : ?>
        <?php $s = zxc_subscription( $t ); ?>
        <tr>
          <td><div class="row" style="gap:10px"><?php echo zxc_brand_mark( $t, 'style="width:34px;height:34px;font-size:14px;border-radius:10px;background:' . esc_attr( $t->brand_color ) . ';color:' . esc_attr( zxc_accent_ink( $t->brand_color ) ) . '"' ); // phpcs:ignore ?><div><b><?php echo esc_html( $t->name ); ?></b><small><a class="link" href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" target="_blank" rel="noopener">/h/<?php echo esc_html( $t->slug ); ?></a></small></div></div></td>
          <td><?php echo esc_html( $t->display_name ); ?><small><?php echo esc_html( $t->user_email ); ?></small></td>
          <td><?php echo esc_html( $tiers[ $t->tier ]['name'] ?? $t->tier ); ?><small><?php echo esc_html( isset( $s['ends'] ) ? 'until ' . zxc_date( $s['ends'] ) : '' ); ?></small></td>
          <td><span class="badge <?php echo esc_attr( array( 'active' => 'badge-ok', 'trial' => 'badge-soft', 'expired' => 'badge-warn', 'suspended' => 'badge-bad' )[ $s['state'] ] ); ?>"><?php echo esc_html( $s['label'] ); ?></span></td>
          <td class="fine"><?php echo esc_html( $t->router_seen ? zxc_ago( $t->router_seen ) : 'never' ); ?></td>
          <td class="num"><b><?php echo esc_html( zxc_money( $t->revenue ) ); ?></b></td>
          <td class="num"><div class="row" style="gap:6px;justify-content:flex-end">
            <form method="post"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="tenant_id" value="<?php echo (int) $t->id; ?>"><input type="hidden" name="action" value="extend"><button class="btn btn-ghost btn-sm" type="submit" title="Add 30 days">+30 days</button></form>
            <form method="post" data-confirm="<?php echo esc_attr( 'suspended' === $t->status ? 'Re-activate this business?' : 'Suspend this business? Its portal goes offline.' ); ?>"><?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="tenant_id" value="<?php echo (int) $t->id; ?>"><input type="hidden" name="action" value="<?php echo 'suspended' === $t->status ? 'activate' : 'suspend'; ?>"><button class="btn <?php echo 'suspended' === $t->status ? 'btn-soft' : 'btn-danger'; ?> btn-sm" type="submit"><?php echo 'suspended' === $t->status ? 'Activate' : 'Suspend'; ?></button></form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  <?php else : ?>
    <div class="empty"><span class="ico-tile"><?php echo zxc_icon( 'building' ); // phpcs:ignore ?></span><b style="color:var(--ink)">No businesses yet</b><span>Share <?php echo esc_html( home_url( '/signup' ) ); ?> with hotspot owners.</span></div>
  <?php endif; ?>
</section>
	<?php
	return zxc_platform_page( '', 'Overview', ob_get_clean(), 'Your hotspot SaaS at a glance.' );
}

function zxc_view_platform_settings() {
	$s    = zxc_settings();
	$mask = $s['flw_secret'] ? substr( $s['flw_secret'], 0, 10 ) . '••••••' . substr( $s['flw_secret'], -4 ) : '';
	ob_start();
	?>
<form method="post" class="stack" style="gap:20px">
  <?php echo zxc_nonce_field( 'zxc_platform' ); // phpcs:ignore ?><input type="hidden" name="action" value="settings">
  <section class="card card-pad form-grid">
    <div class="span-2"><div class="card-title">Brand</div><div class="card-sub">Shown on the marketing site, dashboards and customer portals (“Powered by …”).</div></div>
    <label class="field">Platform name<input class="input" name="platform_name" value="<?php echo esc_attr( $s['platform_name'] ); ?>" required></label>
    <label class="field">Support email<input class="input" type="email" name="support_email" value="<?php echo esc_attr( $s['support_email'] ); ?>"></label>
    <label class="check span-2"><input type="checkbox" name="takeover_home" value="1" <?php checked( $s['takeover_home'], '1' ); ?>> Use the <?php echo esc_html( $s['platform_name'] ); ?> landing page as the website homepage</label>
  </section>
  <section class="card card-pad form-grid">
    <div class="span-2"><div class="card-title">Subscription prices</div><div class="card-sub">What hotspot owners pay you each month (₦).</div></div>
    <label class="field">Starter<input class="input" type="number" min="0" name="price_starter" value="<?php echo esc_attr( $s['price_starter'] ); ?>"></label>
    <label class="field">Business<input class="input" type="number" min="0" name="price_business" value="<?php echo esc_attr( $s['price_business'] ); ?>"></label>
    <label class="field">Enterprise<input class="input" type="number" min="0" name="price_enterprise" value="<?php echo esc_attr( $s['price_enterprise'] ); ?>"></label>
    <label class="field">Free trial (days)<input class="input" type="number" min="1" name="trial_days" value="<?php echo esc_attr( $s['trial_days'] ); ?>"></label>
  </section>
  <section class="card card-pad form-grid">
    <div class="span-2"><div class="card-title">Your Flutterwave account</div><div class="card-sub">Collects owners’ subscription payments. Webhook URL: <code><?php echo esc_html( rest_url( 'zaxconnect/v1/flutterwave/platform' ) ); ?></code></div></div>
    <label class="field">Public key<input class="input mono" name="flw_public" value="<?php echo esc_attr( $s['flw_public'] ); ?>" placeholder="FLWPUBK-…"></label>
    <label class="field">Secret key <?php echo $mask ? '<em>saved: ' . esc_html( $mask ) . '</em>' : ''; ?><input class="input mono" type="password" name="flw_secret" placeholder="<?php echo $mask ? 'Leave blank to keep the saved key' : 'FLWSECK-…'; ?>" autocomplete="off"></label>
    <label class="field">Webhook secret hash<input class="input mono" name="flw_hash" value="<?php echo esc_attr( $s['flw_hash'] ); ?>"></label>
    <label class="check" style="align-self:end"><input type="checkbox" name="demo_billing" value="1" <?php checked( $s['demo_billing'], '1' ); ?>> Simulate subscription payments until keys are set</label>
  </section>
  <div class="form-actions"><button class="btn btn-primary btn-lg" type="submit">Save settings</button></div>
</form>
	<?php
	return zxc_platform_page( 'settings', 'Platform settings', ob_get_clean() );
}
