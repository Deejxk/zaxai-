<?php
/**
 * Customer-facing captive portal, branded per business.
 */
defined( 'ABSPATH' ) || exit;

function zxc_portal_nav( $t, $active = '', $back = '' ) {
	ob_start();
	?>
<header class="nav" data-nav>
  <div class="wrap nav-in">
    <?php if ( $back ) : ?><a href="<?php echo esc_url( $back ); ?>" class="icon-btn" aria-label="Back"><?php echo zxc_icon( 'back' ); // phpcs:ignore ?></a><?php endif; ?>
    <a href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" class="brand">
      <?php echo zxc_brand_mark( $t ); // phpcs:ignore ?>
      <span class="brand-text"><span class="brand-name"><?php echo esc_html( $t->name ); ?></span><span class="brand-sub">Powered by <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?></span></span>
    </a>
    <nav class="nav-links" aria-label="Portal">
      <?php foreach ( array( array( '', 'Home', 'home' ), array( 'plans', 'Plans', 'plans' ), array( 'wallet', 'Wallet', 'wallet' ), array( 'plan?tab=voucher', 'Voucher', 'voucher' ), array( 'plan', 'My plan', 'plan' ), array( 'help', 'Help', 'help' ) ) as $l ) : ?>
        <a href="<?php echo esc_url( zxc_portal_url( $t, $l[0] ) ); ?>" class="<?php echo $l[2] === $active ? 'on' : ''; ?>"><?php echo esc_html( $l[1] ); ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-actions">
      <?php echo zxc_switch_buttons( 'portal', $t ); // phpcs:ignore ?>
      <?php echo zxc_refresh_btn(); // phpcs:ignore ?>
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
    </div>
  </div>
</header>
	<?php
	return ob_get_clean();
}

function zxc_portal_foot( $t ) {
	$wa = $t->support_whatsapp ? 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', $t->support_whatsapp ) ) : '';
	ob_start();
	?>
<footer class="wrap pt-foot">
  <div class="links">
    <?php if ( $wa ) : ?><a href="<?php echo esc_url( $wa ); ?>"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a><?php endif; ?>
    <?php if ( $t->support_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $t->support_phone ) ); ?>"><?php echo zxc_icon( 'call' ); // phpcs:ignore ?> Call</a><?php endif; ?>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'help' ) ); ?>"><?php echo zxc_icon( 'help' ); // phpcs:ignore ?> Help</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'terms' ) ); ?>"><?php echo zxc_icon( 'file' ); // phpcs:ignore ?> Terms</a>
  </div>
  <div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?><span>Payments secured by Flutterwave · <?php echo esc_html( $t->name ); ?><?php echo $t->location ? ' · ' . esc_html( $t->location ) : ''; ?></span></div>
</footer>
	<?php
	return ob_get_clean();
}

function zxc_portal_page( $t, $title, $content, $active = 'home', $back = '' ) {
	$dock = zxc_dock(
		array(
			array( zxc_portal_url( $t ), 'home', 'Home', 'home' === $active ),
			array( zxc_portal_url( $t, 'wallet' ), 'wallet', 'Wallet', 'wallet' === $active ),
			array( zxc_portal_url( $t, 'plans' ), 'wifi', 'Buy', 'plans' === $active, true ),
			array( zxc_portal_url( $t, 'plan' ), 'clock', 'My plan', in_array( $active, array( 'plan', 'voucher' ), true ) ),
			array( zxc_portal_url( $t, 'help' ), 'help', 'Help', 'help' === $active ),
		),
		'Portal'
	);
	$body = zxc_portal_nav( $t, $active, $back ) . '<main class="wrap">' . $content . '</main>' . zxc_portal_foot( $t ) . $dock;
	return zxc_page( $title . ' · ' . $t->name, $body, array( 'body_class' => 'has-dock', 'style' => zxc_brand_style( $t ), 'robots' => 'noindex', 'app' => array( 'slug' => $t->slug, 'name' => $t->name, 'sub' => 'Fast Wi-Fi · Powered by ' . zxc_setting( 'platform_name' ) ) ) );
}

function zxc_hero_art() {
	return '<svg class="art" viewBox="0 0 300 190" aria-hidden="true">
    <g class="a-orbit"><circle cx="150" cy="95" r="82" fill="none" stroke="rgba(255,255,255,.1)" stroke-dasharray="2 7"/><circle cx="232" cy="95" r="4" fill="#22D3EE"/><circle cx="68" cy="95" r="3" fill="#C4B5FD"/></g>
    <g class="a-float">
      <rect x="110" y="98" width="80" height="36" rx="11" fill="rgba(255,255,255,.08)" stroke="rgba(255,255,255,.2)"/>
      <circle cx="128" cy="116" r="3" fill="#34D399"/><circle cx="140" cy="116" r="3" fill="#22D3EE"/><circle cx="152" cy="116" r="3" fill="rgba(255,255,255,.4)"/>
      <path d="M132 98V84M168 98V84" stroke="rgba(255,255,255,.4)" stroke-width="3" stroke-linecap="round"/>
      <path class="w1" d="M138 76a17 17 0 0 1 24 0" fill="none" stroke="#22D3EE" stroke-width="3.2" stroke-linecap="round"/>
      <path class="w2" d="M130 66a29 29 0 0 1 40 0" fill="none" stroke="var(--accent)" stroke-width="3.2" stroke-linecap="round"/>
      <path class="w3" d="M122 56a41 41 0 0 1 56 0" fill="none" stroke="#C4B5FD" stroke-width="3.2" stroke-linecap="round"/>
    </g></svg>';
}

function zxc_plan_cards( $t, $plans, $selected = null ) {
	ob_start();
	foreach ( $plans as $p ) {
		$href = zxc_portal_url( $t, 'buy/' . $p->id );
		$meta = ( $p->data_mb ? zxc_data_label( $p->data_mb ) : 'Unlimited data' ) . ' · ' . zxc_speed_label( $p->download_mbps );
		?>
<a class="plan <?php echo $p->popular ? 'popular' : ''; ?> <?php echo $selected && (int) $selected->id === (int) $p->id ? 'on' : ''; ?>" href="<?php echo esc_url( $href ); ?>" data-plan data-href="<?php echo esc_url( $href ); ?>" data-name="<?php echo esc_attr( $p->name ); ?>" data-price="<?php echo esc_attr( zxc_money( $p->price ) ); ?>" data-cat="<?php echo esc_attr( zxc_plan_category( $p ) ); ?>">
  <span class="ico"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span>
  <span class="plan-info"><span class="plan-name"><?php echo esc_html( $p->name ); ?><?php if ( $p->popular ) : ?><span class="badge badge-accent">Popular</span><?php endif; ?></span><span class="plan-meta"><?php echo esc_html( $p->description ? $p->description : $meta ); ?></span></span>
  <span class="plan-price"><b><?php echo esc_html( zxc_money( $p->price ) ); ?></b><small><?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></small></span>
  <span class="plan-extra">
    <span><?php echo zxc_icon( 'clock' ); // phpcs:ignore ?>Valid for <?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></span>
    <span><?php echo zxc_icon( 'data' ); // phpcs:ignore ?><?php echo esc_html( zxc_data_label( $p->data_mb ) ); ?></span>
    <span><?php echo zxc_icon( 'zap' ); // phpcs:ignore ?><?php echo esc_html( zxc_speed_label( $p->download_mbps ) ); ?></span>
    <span><?php echo zxc_icon( 'devices' ); // phpcs:ignore ?><?php echo (int) $p->devices; ?> device<?php echo $p->devices > 1 ? 's' : ''; ?></span>
    <span class="btn <?php echo $p->popular ? 'btn-primary' : 'btn-glass'; ?> btn-block">Buy now</span>
  </span>
</a>
		<?php
	}
	return ob_get_clean();
}

function zxc_plans_section( $t, $plans, $title = 'Choose a plan' ) {
	$popular = null;
	$cats    = array();
	foreach ( $plans as $p ) {
		if ( ! $popular && $p->popular ) {
			$popular = $p;
		}
		$cats[ zxc_plan_category( $p ) ] = true;
	}
	ob_start();
	?>
<section id="plans" class="stack" style="gap:16px;scroll-margin-top:90px">
  <div class="sec-row">
    <div><h2><?php echo esc_html( $title ); ?></h2><p>Your time starts at first login. Unused vouchers never expire.</p></div>
    <?php if ( count( $cats ) > 1 ) : ?>
      <div class="chips" role="group" aria-label="Filter plans"><button type="button" class="chip on" data-filter="all">All</button>
        <?php foreach ( array( 'hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly' ) as $k => $label ) : ?><?php if ( isset( $cats[ $k ] ) ) : ?><button type="button" class="chip" data-filter="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></button><?php endif; ?><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
  <?php if ( ! $plans ) : ?><div class="card empty"><span class="ico lg"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?></span><b>No plans yet</b><span>Please check back shortly.</span></div><?php endif; ?>
  <div class="plans"><?php echo zxc_plan_cards( $t, $plans, $popular ); // phpcs:ignore ?></div>
</section>
<div class="select-bar mob <?php echo $popular ? '' : 'hide'; ?>" data-select-bar>
  <div class="grow"><small>Selected plan</small><b data-sel-name><?php echo $popular ? esc_html( $popular->name . ' · ' . zxc_money( $popular->price ) ) : ''; ?></b></div>
  <a href="<?php echo esc_url( $popular ? zxc_portal_url( $t, 'buy/' . $popular->id ) : '#plans' ); ?>" class="btn btn-primary" data-sel-go>Continue <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a>
</div>
	<?php
	return ob_get_clean();
}

function zxc_view_portal_home( $t, array $a ) {
	ob_start();
	?>
<div class="pt">
  <?php echo zxc_alert( 'bad', $a['error'] ); // phpcs:ignore ?>
  <section class="pt-hero a-rise">
    <?php echo zxc_hero_art(); // phpcs:ignore ?>
    <span class="live-tag"><span class="live"></span>Hotspot online</span>
    <h1><?php echo esc_html( $t->tagline ? $t->tagline : 'Fast Wi-Fi. Pay small, browse big.' ); ?></h1>
    <p>Pick a plan, pay in seconds by card, transfer or USSD, and get online instantly.</p>
    <div class="hero-actions"><a href="<?php echo esc_url( zxc_portal_url( $t, 'plans' ) ); ?>" class="btn btn-primary btn-lg">See plans <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a><a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>" class="btn btn-lg" style="color:#fff;background:rgba(255,255,255,.08);border-color:rgba(255,255,255,.18)"><?php echo zxc_icon( 'ticket' ); // phpcs:ignore ?> I have a voucher</a></div>
    <?php if ( ! empty( $a['ctx']['mac'] ) ) : ?><div class="device-chip"><?php echo zxc_icon( 'phone' ); // phpcs:ignore ?> Your device <b><?php echo esc_html( $a['ctx']['mac'] ); ?></b></div><?php endif; ?>
  </section>
  <nav class="quick" aria-label="Quick actions">
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plans' ) ); ?>"><span class="ico"><?php echo zxc_icon( 'zap' ); // phpcs:ignore ?></span>Buy plan</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>"><span class="ico teal"><?php echo zxc_icon( 'ticket', 'a-float' ); // phpcs:ignore ?></span>Voucher</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) ); ?>"><span class="ico amber"><?php echo zxc_icon( 'clockAnim' ); // phpcs:ignore ?></span>My plan</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'wallet' ) ); ?>"><span class="ico pink"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?></span>Wallet</a>
  </nav>
  <?php echo zxc_plans_section( $t, $a['plans'] ); // phpcs:ignore ?>
  <section class="stack" style="gap:12px">
    <h2 style="font-size:19px">How it works</h2>
    <div class="glass list how-grid">
      <div class="list-row"><span class="num">1</span><span><b>Pick a plan</b><small>Hourly, daily, weekly or monthly</small></span></div>
      <div class="list-row"><span class="num">2</span><span><b>Pay securely</b><small>Card, bank transfer or USSD</small></span></div>
      <div class="list-row"><span class="num">3</span><span><b>You’re online</b><small>We connect your device automatically</small></span></div>
    </div>
  </section>
</div>
	<?php
	return zxc_portal_page( $t, 'Get online', ob_get_clean(), 'home' );
}

function zxc_view_portal_plans( $t, array $a ) {
	return zxc_portal_page( $t, 'Plans', '<div class="pt">' . zxc_plans_section( $t, $a['plans'], 'All plans' ) . '</div>', 'plans', zxc_portal_url( $t ) );
}

function zxc_view_portal_checkout( $t, array $a ) {
	$p    = $a['plan'];
	$wc   = zxc_customer_session( $t );
	$wbal = $wc ? zxc_customer_balance( $wc->id ) : null;
	ob_start();
	?>
<div class="pt">
  <div class="stepper"><span class="s done"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span>Plan<span class="ln on"></span><span class="s on">2</span><span style="color:var(--ink)">Pay</span><span class="ln"></span><span class="s">3</span>Connect</div>
  <div class="checkout">
    <section class="summary a-rise">
      <svg width="120" height="80" viewBox="0 0 120 80" aria-hidden="true" style="position:absolute;right:-6px;top:8px"><path class="w1" d="M48 58a17 17 0 0 1 24 0" fill="none" stroke="#22D3EE" stroke-width="3" stroke-linecap="round"/><path class="w2" d="M40 48a29 29 0 0 1 40 0" fill="none" stroke="var(--accent)" stroke-width="3" stroke-linecap="round"/><path class="w3" d="M32 38a41 41 0 0 1 56 0" fill="none" stroke="#C4B5FD" stroke-width="3" stroke-linecap="round"/></svg>
      <div class="k">You’re buying</div>
      <h2><?php echo esc_html( $p->name ); ?></h2>
      <div class="price"><?php echo esc_html( zxc_money( $p->price ) ); ?></div>
      <div class="spec-grid">
        <div><small>Validity</small><b><?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></b></div>
        <div><small>Data</small><b><?php echo esc_html( $p->data_mb ? str_replace( ' data', '', zxc_data_label( $p->data_mb ) ) : 'Unlimited' ); ?></b></div>
        <div><small>Speed</small><b><?php echo esc_html( zxc_speed_label( $p->download_mbps ) ); ?></b></div>
        <div><small>Devices</small><b><?php echo (int) $p->devices; ?></b></div>
      </div>
      <p style="margin-top:16px;font-size:12.5px;color:rgba(244,245,255,.6)">Validity starts at your first login. Unused vouchers never expire.</p>
    </section>
    <form method="post" class="card stack" action="<?php echo esc_url( zxc_portal_url( $t, 'buy/' . $p->id ) ); ?>">
      <div class="card-title" style="font-size:18px">Your details</div>
      <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
      <?php if ( ! empty( $a['closed'] ) ) : ?><?php echo zxc_alert( 'warn', 'Online payment is not available at this hotspot right now. Ask the attendant for a voucher card.' ); // phpcs:ignore ?><?php endif; ?>
      <label class="field">Phone number<span class="input-group"><span class="addon">+234</span><input class="input" type="tel" name="phone" inputmode="tel" placeholder="803 123 4567" value="<?php echo esc_attr( $a['values']['phone'] ?? '' ); ?>" required autocomplete="tel"></span></label>
      <label class="field">Email <em>optional, for your receipt</em><input class="input" type="email" name="email" placeholder="you@example.com" value="<?php echo esc_attr( $a['values']['email'] ?? '' ); ?>" autocomplete="email"></label>
      <?php
      $methods = array();
      if ( ! isset( $a['online'] ) || $a['online'] ) {
        $methods = array( array( 'card', 'card', 'Card', 'Visa, Mastercard, Verve' ), array( 'bank', 'bank', 'Instant bank transfer', 'Confirmed automatically' ), array( 'ussd', 'ussd', 'USSD', 'Dial a short code, no internet needed' ) );
      }
      if ( ! empty( $a['manual'] ) ) {
        $methods[] = array( 'manual', 'bank', 'Transfer to ' . $t->bank_name, 'Pay to our account and send proof on WhatsApp' );
      }
      $picked = $a['values']['method'] ?? ( count( $methods ) > 1 ? $methods[1][0] : ( $methods[0][0] ?? '' ) );
      ?>
      <?php if ( $methods ) : ?><div class="stack" style="gap:10px"><span class="field">Pay with</span>
        <?php foreach ( $methods as $m ) : ?>
          <label class="method<?php echo 'manual' === $m[0] ? ' manual' : ''; ?>"><input type="radio" name="method" value="<?php echo esc_attr( $m[0] ); ?>" <?php checked( $picked, $m[0] ); ?>><span class="ico sm<?php echo 'manual' === $m[0] ? ' teal' : ''; ?>"><?php echo zxc_icon( $m[1] ); // phpcs:ignore ?></span><span class="grow"><b style="display:block;font-size:14.5px;font-weight:600"><?php echo esc_html( $m[2] ); ?></b><small class="fine"><?php echo esc_html( $m[3] ); ?></small></span><span class="radio"></span></label>
        <?php endforeach; ?>
      </div><?php endif; ?>
      <button class="btn btn-primary btn-lg btn-block" type="submit" <?php disabled( ! empty( $a['closed'] ) ); ?>><?php echo zxc_icon( 'lock' ); // phpcs:ignore ?> Continue to pay <?php echo esc_html( zxc_money( $p->price ) ); ?></button>
      <a class="wallet-pay" href="<?php echo esc_url( zxc_portal_url( $t, 'wallet' ) . '?buy=' . $p->id ); ?>"><span class="ico sm pink"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?></span><span class="grow"><b>Pay from my Wi-Fi wallet</b><small><?php echo null !== $wbal ? 'Balance ' . esc_html( zxc_money( $wbal ) ) : 'Use your balance, or open a wallet'; ?></small></span><?php echo zxc_icon( 'chevron' ); // phpcs:ignore ?></a>
      <?php if ( ! empty( $a['demo'] ) && empty( $a['closed'] ) && ( ! isset( $a['online'] ) || $a['online'] ) ) : ?><?php echo zxc_alert( 'warn', 'Test mode: this hotspot has not connected Flutterwave yet, so payment is simulated.' ); // phpcs:ignore ?><?php else : ?><div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?><span>Secured by Flutterwave. Card details never touch this site.</span></div><?php endif; ?>
    </form>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'Checkout', ob_get_clean(), 'plans', zxc_portal_url( $t, 'plans' ) );
}

function zxc_view_portal_manual( $t, $o, $error = '' ) {
	$sent = 'submitted' === $o->channel;
	$wa   = zxc_manual_wa_link( $t, $o );
	ob_start();
	?>
<div class="pt narrow"<?php if ( $sent ) : ?> data-poll="<?php echo esc_url( rest_url( 'zaxconnect/v1/order/' . $o->reference ) ); ?>" data-go="<?php echo esc_url( zxc_portal_url( $t, 'pay/check/' . $o->reference ) ); ?>"<?php endif; ?>>
  <div class="stepper"><span class="s done"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span>Transfer<span class="ln <?php echo $sent ? 'on' : ''; ?>"></span><span class="s <?php echo $sent ? 'done' : 'on'; ?>"><?php echo $sent ? zxc_icon( 'check' ) : '2'; // phpcs:ignore ?></span><span style="color:var(--ink)">Send proof</span><span class="ln"></span><span class="s <?php echo $sent ? 'on' : ''; ?>">3</span>Connect</div>
  <?php if ( ! $sent ) : ?>
  <section class="bank-pay a-rise">
    <div class="bp-top"><span class="bp-k">Transfer exactly</span><b class="bp-amt"><?php echo esc_html( zxc_money( $o->amount ) ); ?></b><span class="bp-plan"><?php echo esc_html( $o->plan_name ); ?></span></div>
    <div class="bp-rows">
      <div class="bp-row"><span><small>Bank</small><b><?php echo esc_html( $t->bank_name ); ?></b></span></div>
      <div class="bp-row"><span><small>Account number</small><b class="mono" id="bp-acct"><?php echo esc_html( $t->bank_account ); ?></b></span><button type="button" class="bp-copy" data-copy="#bp-acct"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?>Copy</button></div>
      <div class="bp-row"><span><small>Account name</small><b><?php echo esc_html( $t->bank_holder ); ?></b></span></div>
      <div class="bp-row"><span><small>Amount</small><b id="bp-amt"><?php echo esc_html( (string) (int) $o->amount ); ?></b></span><button type="button" class="bp-copy" data-copy="#bp-amt"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?>Copy</button></div>
      <div class="bp-row"><span><small>Narration / description</small><b class="mono" id="bp-ref"><?php echo esc_html( $o->reference ); ?></b></span><button type="button" class="bp-copy" data-copy="#bp-ref"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?>Copy</button></div>
    </div>
  </section>
  <form method="post" class="card stack">
    <?php echo zxc_nonce_field( 'zxc_manual' ) . zxc_alert( 'bad', $error ); // phpcs:ignore ?>
    <div class="row"><span class="ico teal"><?php echo zxc_icon( 'user' ); // phpcs:ignore ?></span><div><div class="card-title">After you transfer</div><div class="card-sub">Tell us who paid so we can find your payment.</div></div></div>
    <label class="field">Name on the account you paid from<input class="input" name="payer_name" maxlength="120" placeholder="e.g. Tunde Bakare" required autocomplete="name"></label>
    <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?> I’ve sent the money</button>
    <p class="fine center">Use your bank app, USSD or POS. Your Wi-Fi starts as soon as <?php echo esc_html( $t->name ); ?> confirms the transfer.</p>
  </form>
  <?php else : ?>
  <section class="card stack center a-rise" style="justify-items:center;padding:28px 22px">
    <span class="bp-wait"><svg width="64" height="64" viewBox="0 0 50 50" aria-hidden="true"><circle cx="25" cy="25" r="20" fill="none" stroke="var(--accent-soft)" stroke-width="4"/><circle class="a-spin" cx="25" cy="25" r="20" fill="none" stroke="var(--accent)" stroke-width="4" stroke-linecap="round" stroke-dasharray="30 100"/></svg><?php echo zxc_icon( 'clock' ); // phpcs:ignore ?></span>
    <h1 style="font-size:24px">Waiting for confirmation</h1>
    <p class="muted">Send your proof of payment to <?php echo esc_html( $t->name ); ?> on WhatsApp. You’ll be connected automatically as soon as they approve it. Keep this page open.</p>
    <?php if ( $wa ) : ?><a class="btn btn-wa btn-lg btn-block" href="<?php echo esc_url( $wa ); ?>" target="_blank" rel="noopener"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?> Send proof on WhatsApp</a><?php endif; ?>
    <div class="glass receipt" style="width:100%"><div><span>Reference</span><b class="mono"><?php echo esc_html( $o->reference ); ?></b></div><div><span>Plan</span><b><?php echo esc_html( $o->plan_name ); ?></b></div><div><span>Amount</span><b><?php echo esc_html( zxc_money( $o->amount ) ); ?></b></div><div><span>Paid by</span><b><?php echo esc_html( $o->payer_name ); ?></b></div></div>
    <a class="btn btn-glass btn-block" href="<?php echo esc_url( zxc_portal_url( $t, 'pay/manual/' . $o->reference ) ); ?>"><?php echo zxc_icon( 'refresh' ); // phpcs:ignore ?> Check status</a>
    <p class="fine">Save your reference <b class="mono"><?php echo esc_html( $o->reference ); ?></b>. You can come back to this page any time.</p>
  </section>
  <?php endif; ?>
</div>
	<?php
	return zxc_portal_page( $t, $sent ? 'Waiting for confirmation' : 'Bank transfer', ob_get_clean(), 'plans' );
}

function zxc_view_portal_demo_pay( $t, $o ) {
	ob_start();
	?>
<div class="pt narrow"><form method="post" class="card stack center" style="justify-items:center;padding:30px">
  <span class="badge badge-warn">Test payment</span>
  <h1 style="font-size:38px"><?php echo esc_html( zxc_money( $o->amount ) ); ?></h1>
  <p class="muted"><?php echo esc_html( $o->plan_name ); ?> · Ref <?php echo esc_html( $o->reference ); ?></p>
  <button class="btn btn-primary btn-lg btn-block" name="result" value="success" type="submit">Simulate successful payment</button>
  <button class="btn btn-ghost btn-block" name="result" value="failed" type="submit">Simulate failed payment</button>
</form></div>
	<?php
	return zxc_portal_page( $t, 'Test payment', ob_get_clean(), 'plans' );
}

function zxc_view_portal_pending( $t, $o ) {
	ob_start();
	?>
<div class="pt narrow" data-poll="<?php echo esc_url( rest_url( 'zaxconnect/v1/order/' . $o->reference ) ); ?>" data-go="<?php echo esc_url( zxc_portal_url( $t, 'pay/check/' . $o->reference ) ); ?>">
  <div class="card stack center" style="justify-items:center;padding:32px">
    <svg width="60" height="60" viewBox="0 0 50 50" aria-hidden="true"><circle cx="25" cy="25" r="20" fill="none" stroke="var(--accent-soft)" stroke-width="5"/><circle class="a-spin" cx="25" cy="25" r="20" fill="none" stroke="var(--accent)" stroke-width="5" stroke-linecap="round" stroke-dasharray="30 100"/></svg>
    <h1 style="font-size:24px">Confirming your payment…</h1>
    <p class="muted">This usually takes a few seconds. Bank transfers can take a few minutes. Keep this page open.</p>
    <div class="glass receipt" style="width:100%"><div><span>Reference</span><b class="mono"><?php echo esc_html( $o->reference ); ?></b></div><div><span>Plan</span><b><?php echo esc_html( $o->plan_name ); ?></b></div><div><span>Amount</span><b><?php echo esc_html( zxc_money( $o->amount ) ); ?></b></div></div>
    <a class="btn btn-glass btn-block" href="<?php echo esc_url( zxc_portal_url( $t, 'pay/check/' . $o->reference ) ); ?>">Check again</a>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'Confirming payment', ob_get_clean(), 'plans' );
}

function zxc_view_portal_failed( $t, $o ) {
	ob_start();
	?>
<div class="pt narrow center">
  <span class="ico lg bad a-pop" style="margin:0 auto;width:80px;height:80px;border-radius:26px"><?php echo zxc_icon( 'x' ); // phpcs:ignore ?></span>
  <?php if ( 'declined' === $o->status ) : ?>
  <div><h1 style="font-size:26px">Payment not confirmed</h1><p class="muted" style="margin-top:8px"><?php echo esc_html( $t->name ); ?> could not confirm your transfer for <b><?php echo esc_html( $o->plan_name ); ?></b> (ref <?php echo esc_html( $o->reference ); ?>). If you did pay, please contact them.</p></div>
  <?php $wa = zxc_wa_number( $t ); if ( $wa ) : ?><a class="btn btn-wa btn-lg btn-block" href="<?php echo esc_url( 'https://wa.me/' . $wa . '?text=' . rawurlencode( 'Hello, my transfer ' . $o->reference . ' was declined. Can you help?' ) ); ?>" target="_blank" rel="noopener"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?> Chat on WhatsApp</a><?php endif; ?>
  <?php else : ?>
  <div><h1 style="font-size:26px">Payment not completed</h1><p class="muted" style="margin-top:8px">Your payment for <b><?php echo esc_html( $o->plan_name ); ?></b> did not go through. You have not been charged.</p></div>
  <?php endif; ?>
  <a href="<?php echo esc_url( zxc_portal_url( $t, 'buy/' . $o->plan_id ) ); ?>" class="btn btn-primary btn-lg btn-block">Try again</a>
  <a href="<?php echo esc_url( zxc_portal_url( $t, 'plans' ) ); ?>" class="btn btn-glass btn-block">Choose another plan</a>
</div>
	<?php
	return zxc_portal_page( $t, 'Payment failed', ob_get_clean(), 'plans' );
}

function zxc_view_portal_success( $t, array $a ) {
	$o    = $a['order'];
	$v    = $a['voucher'];
	$code = zxc_format_code( $v->code );
	ob_start();
	?>
<div class="pt narrow center"<?php echo $a['ready'] ? '' : ' data-poll="' . esc_url( rest_url( 'zaxconnect/v1/order/' . $o->reference ) ) . '" data-poll-ready data-go="' . esc_url( zxc_portal_url( $t, 'success/' . $o->reference ) ) . '"'; ?>>
  <div class="success-art">
    <span class="ring"></span><span class="ring r2"></span>
    <span class="conf" style="left:8px;top:0;background:#A78BFA"></span><span class="conf" style="right:6px;top:8px;background:#22D3EE;border-radius:50%;animation-delay:.5s"></span><span class="conf" style="left:34px;top:-6px;background:#FBBF24;animation-delay:.9s"></span><span class="conf" style="right:32px;top:-4px;background:#F472B6;animation-delay:.3s"></span>
    <span class="check a-pop"><svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="a-draw"><path d="m4.5 12.5 4.8 4.8L19.5 7"/></svg></span>
  </div>
  <div class="a-rise3"><h1 style="font-size:28px">Payment successful</h1><p class="muted" style="margin-top:6px">Your <b style="color:var(--ink)"><?php echo esc_html( $o->plan_name ); ?></b> is ready to use.</p></div>
  <section class="card code-box a-rise3">
    <span class="eyebrow" style="color:var(--muted)">Your voucher code</span>
    <div class="code" id="vcode"><?php echo esc_html( $code ); ?></div>
    <button type="button" class="btn btn-soft btn-sm" data-copy="<?php echo esc_attr( $code ); ?>"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy code</button>
  </section>
  <?php if ( $a['link'] && $a['ready'] ) : ?>
    <a href="<?php echo esc_url( $a['link'] ); ?>" class="btn btn-primary btn-lg btn-block" data-autoconnect><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?> Connect now</a>
    <p class="fine">Connecting you automatically in <b data-countdown>5s</b>…</p>
  <?php elseif ( $a['link'] ) : ?>
    <span class="btn btn-glass btn-lg btn-block" aria-live="polite"><svg width="20" height="20" viewBox="0 0 50 50" aria-hidden="true"><circle class="a-spin" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-dasharray="30 100"/></svg> Activating on the hotspot…</span>
    <p class="fine">This takes up to 15 seconds. You’ll be connected automatically.</p>
  <?php else : ?>
    <?php echo zxc_alert( 'info', 'Join the ' . $t->name . ' Wi-Fi, open any website and enter this code under “Voucher”.' ); // phpcs:ignore ?>
  <?php endif; ?>
  <div class="glass receipt"><div><span>Reference</span><b class="mono"><?php echo esc_html( $o->reference ); ?></b></div><div><span>Amount</span><b><?php echo esc_html( zxc_money( $o->amount ) ); ?></b></div><div><span>Paid with</span><b><?php echo esc_html( $o->channel ? ucwords( str_replace( '_', ' ', $o->channel ) ) : 'Online' ); ?></b></div></div>
  <p class="fine">Save this code. Reconnect with it any time until your plan ends.</p>
</div>
	<?php
	return zxc_portal_page( $t, 'Payment successful', ob_get_clean(), 'voucher' );
}

function zxc_view_portal_plan( $t, array $a ) {
	$v   = $a['v'];
	$tab = $a['tab'];
	$st  = $v ? zxc_voucher_state( $v ) : null;
	ob_start();
	?>
<div class="pt narrow">
  <?php if ( $a['online'] ) : ?><?php echo zxc_alert( 'ok', 'You’re connected! Enjoy your browsing.' ); // phpcs:ignore ?><?php endif; ?>
  <div class="seg" role="tablist">
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . ( $a['code'] ? '?code=' . $a['code'] : '' ) ); ?>" class="<?php echo 'status' === $tab ? 'on' : ''; ?>" role="tab">My plan</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>" class="<?php echo 'voucher' === $tab ? 'on' : ''; ?>" role="tab">Enter voucher</a>
  </div>
  <?php echo zxc_alert( 'bad', $a['error'] ); // phpcs:ignore ?>
  <?php if ( 'voucher' === $tab ) : ?>
    <form class="card stack center a-rise" style="justify-items:center;padding:28px" method="post" action="<?php echo esc_url( zxc_portal_url( $t, 'voucher' ) ); ?>" data-voucher-form data-api="<?php echo esc_url( rest_url( 'zaxconnect/v1/voucher/' . $t->slug ) ); ?>">
      <span class="ico lg"><?php echo zxc_icon( 'ticket', 'a-float' ); // phpcs:ignore ?></span>
      <h1 style="font-size:23px">Enter your voucher</h1>
      <p class="muted">Bought a voucher card? Type the 8-character code.</p>
      <label class="field" style="width:100%;text-align:left">Voucher code<input class="input input-code" name="code" placeholder="XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="9" required></label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Connect <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></button>
      <div class="form-msg" data-msg role="status"></div>
      <a href="<?php echo esc_url( zxc_portal_url( $t, 'recover' ) ); ?>" class="fine" style="text-decoration:underline;text-underline-offset:3px">Lost your code? Recover it</a>
    </form>
  <?php elseif ( $v ) : ?>
    <?php
    $labels = array( 'unused' => array( 'Not used yet', '' ), 'active' => array( 'Active', 'badge-ok' ), 'expired' => array( 'Expired', 'badge-bad' ), 'exhausted' => array( 'Data used up', 'badge-bad' ), 'disabled' => array( 'Disabled', 'badge-bad' ) );
    $lab    = $labels[ $st['state'] ] ?? array( $st['state'], '' );
    $dash   = (int) round( 339 * ( 1 - ( 'active' === $st['state'] ? $st['ratio'] : ( 'unused' === $st['state'] ? 1 : 0 ) ) ) );
    $link   = zxc_login_link( $a['ctx'], $v->code, zxc_portal_url( $t, 'connected' ) . '?code=' . $v->code );
    ?>
    <section class="card stack a-rise" style="justify-items:center;gap:16px">
      <div class="row between" style="width:100%"><span class="badge <?php echo esc_attr( $lab[1] ); ?>"><?php if ( 'active' === $st['state'] ) : ?><span class="live" style="width:6px;height:6px"></span><?php endif; ?><?php echo esc_html( $lab[0] ); ?></span><span class="mono fine" style="font-weight:600"><?php echo esc_html( zxc_format_code( $v->code ) ); ?></span></div>
      <div class="ring-meter">
        <svg width="210" height="210" viewBox="0 0 120 120" aria-hidden="true"><defs><linearGradient id="rg" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="var(--accent)"/><stop offset="1" stop-color="var(--accent-3)"/></linearGradient></defs><circle cx="60" cy="60" r="54" fill="none" stroke="var(--track)" stroke-width="8"/><circle class="fill" cx="60" cy="60" r="54" fill="none" stroke="url(#rg)" stroke-width="8" stroke-linecap="round" stroke-dasharray="339" stroke-dashoffset="<?php echo (int) $dash; ?>"/></svg>
        <div class="label"><small class="fine">Time left</small><b <?php echo 'active' === $st['state'] ? 'data-until="' . (int) $st['expires'] * 1000 . '"' : ''; ?>><?php echo esc_html( 'active' === $st['state'] ? zxc_remaining( $st['left'] ) : ( 'unused' === $st['state'] ? zxc_duration( $v->duration_min ) : '0m' ) ); ?></b><small class="fine">of <?php echo esc_html( zxc_duration( $v->duration_min ) ); ?></small></div>
      </div>
      <b style="font-family:var(--display);font-size:18px;font-weight:650"><?php echo esc_html( $v->plan_name ); ?></b>
    </section>
    <div class="mini-stats">
      <div class="card mini-stat"><span class="ico sm teal"><?php echo zxc_icon( 'download' ); // phpcs:ignore ?></span><small>Data used</small><b><?php echo esc_html( zxc_bytes( $st['used'] ) ); ?></b><div class="fine"><?php echo esc_html( $st['cap'] ? 'of ' . zxc_bytes( $st['cap'] ) : 'Unlimited plan' ); ?></div></div>
      <div class="card mini-stat"><span class="ico sm"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span><small>Speed</small><b><?php echo esc_html( zxc_speed_label( $v->download_mbps ) ); ?></b><div class="fine"><?php echo (int) $v->devices; ?> device<?php echo $v->devices > 1 ? 's' : ''; ?></div></div>
    </div>
    <?php if ( $st['cap'] ) : ?><div class="card"><div class="row between" style="font-size:13.5px"><span class="muted">Data used</span><b><?php echo (int) min( 100, round( $st['used'] / $st['cap'] * 100 ) ); ?>%</b></div><div class="bar warn" style="margin-top:10px"><span class="a-growx" style="width:<?php echo (int) min( 100, round( $st['used'] / $st['cap'] * 100 ) ); ?>%"></span></div></div><?php endif; ?>
    <?php if ( $st['expires'] ) : ?><div class="glass receipt"><div><span>Started</span><b><?php echo esc_html( zxc_date( $st['activated'] ) ); ?></b></div><div><span>Ends</span><b><?php echo esc_html( zxc_date( $st['expires'] ) ); ?></b></div></div><?php endif; ?>
    <?php if ( $link && in_array( $st['state'], array( 'unused', 'active' ), true ) && ! $a['online'] ) : ?><a href="<?php echo esc_url( $link ); ?>" class="btn btn-primary btn-lg btn-block"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?> Connect with this voucher</a><?php endif; ?>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plans' ) ); ?>" class="btn btn-glass btn-lg btn-block"><?php echo in_array( $st['state'], array( 'expired', 'exhausted' ), true ) ? 'Buy a new plan' : 'Buy another plan'; ?></a>
  <?php else : ?>
    <form class="card stack" method="get" action="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) ); ?>">
      <span class="ico lg amber"><?php echo zxc_icon( 'clockAnim' ); // phpcs:ignore ?></span>
      <h1 style="font-size:23px">Check your plan</h1>
      <p class="muted">Enter your voucher code to see time and data left.</p>
      <label class="field">Voucher code<input class="input input-code" name="code" placeholder="XXXX-XXXX" autocomplete="off" required></label>
      <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Check status</button>
    </form>
  <?php endif; ?>
</div>
	<?php
	return zxc_portal_page( $t, $a['online'] ? 'Connected' : 'My plan', ob_get_clean(), 'voucher' === $tab ? 'voucher' : 'plan' );
}

function zxc_view_portal_recover( $t, array $a ) {
	ob_start();
	?>
<div class="pt narrow">
  <form class="card stack" method="post" action="<?php echo esc_url( zxc_portal_url( $t, 'recover' ) ); ?>">
    <span class="ico lg"><?php echo zxc_icon( 'search', 'a-float' ); // phpcs:ignore ?></span>
    <h1 style="font-size:23px">Recover your voucher</h1>
    <p class="muted">Enter the phone number you paid with. We show vouchers bought on this device, or enter the payment reference from your receipt.</p>
    <label class="field">Phone number<input class="input" type="tel" name="phone" value="<?php echo esc_attr( $a['phone'] ?? '' ); ?>" placeholder="0803 123 4567" required></label>
    <label class="field">Payment reference <em>if using another device</em><input class="input mono" name="reference" value="<?php echo esc_attr( $a['ref'] ?? '' ); ?>" placeholder="ZX…" autocomplete="off"></label>
    <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Find my voucher</button>
  </form>
  <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
  <?php if ( ! empty( $a['results'] ) ) : ?>
    <div class="glass list"><?php foreach ( $a['results'] as $r ) : ?><div class="list-row"><span class="grow"><b class="mono"><?php echo esc_html( zxc_format_code( $r->code ) ); ?></b><small><?php echo esc_html( $r->plan_name . ' · ' . zxc_date( $r->paid_at ) ); ?></small></span><a class="btn btn-soft btn-sm" href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?code=' . $r->code ); ?>">View</a></div><?php endforeach; ?></div>
  <?php endif; ?>
</div>
	<?php
	return zxc_portal_page( $t, 'Recover voucher', ob_get_clean(), 'help' );
}

function zxc_view_portal_help( $t, array $a ) {
	$wa  = $t->support_whatsapp ? 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', $t->support_whatsapp ) ) : '';
	$v   = $a['values'] ?? array();
	$faq = array(
		array( 'How do I connect after paying?', 'You are connected automatically within about 15 seconds. If not, open My plan and tap “Connect with this voucher”.' ),
		array( 'When does my time start?', 'Your plan starts the first time you log in, not when you buy it.' ),
		array( 'I lost my voucher code', 'Use “Recover voucher” with the phone number you paid with.' ),
		array( 'Can I use my voucher on another phone?', 'Each plan allows a set number of devices, shown on the plan card. Log out on one device to use another.' ),
		array( 'I was charged but did not get a code', 'Bank transfers can take a few minutes. Check “Recover voucher”, or send us a message below with your phone number.' ),
	);
	ob_start();
	?>
<div class="pt">
  <section class="center a-rise" style="padding-top:6px"><span class="eyebrow">Help centre</span><h1 style="margin-top:10px;font-size:clamp(28px,5vw,40px)">How can we help?</h1><p class="muted" style="margin-top:8px">Answers to common questions, or reach <?php echo esc_html( $t->name ); ?> directly.</p></section>
  <div class="grid g4">
    <?php if ( $wa ) : ?><a class="card feature hover-lift" href="<?php echo esc_url( $wa ); ?>"><span class="ico teal"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?></span><h3>WhatsApp</h3><p><?php echo esc_html( $t->support_whatsapp ); ?></p></a><?php endif; ?>
    <?php if ( $t->support_phone ) : ?><a class="card feature hover-lift" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $t->support_phone ) ); ?>"><span class="ico amber"><?php echo zxc_icon( 'call' ); // phpcs:ignore ?></span><h3>Call us</h3><p><?php echo esc_html( $t->support_phone ); ?></p></a><?php endif; ?>
    <a class="card feature hover-lift" href="<?php echo esc_url( zxc_portal_url( $t, 'recover' ) ); ?>"><span class="ico"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?></span><h3>Recover voucher</h3><p>Find a code you bought</p></a>
    <a class="card feature hover-lift" href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) ); ?>"><span class="ico pink"><?php echo zxc_icon( 'clock' ); // phpcs:ignore ?></span><h3>Check my plan</h3><p>Time and data left</p></a>
  </div>
  <div class="split">
    <section class="wide stack" style="gap:10px"><h2 style="font-size:20px">Common questions</h2>
      <?php foreach ( $faq as $i => $q ) : ?><details class="faq"<?php echo 0 === $i ? ' open' : ''; ?>><summary><?php echo esc_html( $q[0] ); ?><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></summary><p><?php echo esc_html( $q[1] ); ?></p></details><?php endforeach; ?>
    </section>
    <form method="post" class="card side-col stack" action="<?php echo esc_url( zxc_portal_url( $t, 'help' ) ); ?>">
      <div class="card-title">Send a message</div>
      <?php if ( ! empty( $a['sent'] ) ) : ?>
        <div class="empty"><span class="ico lg ok a-pop"><?php echo zxc_icon( 'checkCirc' ); // phpcs:ignore ?></span><b>Message sent</b><span><?php echo esc_html( $t->name ); ?> will get back to you soon.</span></div>
      <?php else : ?>
        <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
        <label class="field">Your name<input class="input" name="name" value="<?php echo esc_attr( $v['name'] ?? '' ); ?>" required></label>
        <label class="field">Phone number<input class="input" type="tel" name="phone" value="<?php echo esc_attr( $v['phone'] ?? '' ); ?>" required></label>
        <label class="field">Topic<select class="input" name="topic"><option>Payment issue</option><option>Can’t connect</option><option>Voucher problem</option><option>Other</option></select></label>
        <label class="field">Message<textarea class="input" name="message" required><?php echo esc_textarea( $v['message'] ?? '' ); ?></textarea></label>
        <button class="btn btn-primary btn-block" type="submit"><?php echo zxc_icon( 'message' ); // phpcs:ignore ?> Send message</button>
      <?php endif; ?>
    </form>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'Help', ob_get_clean(), 'help' );
}

function zxc_view_portal_terms( $t ) {
	$n = $t->name;
	ob_start();
	?>
<div class="pt"><article class="card prose" style="max-width:860px;margin:0 auto;width:100%">
  <h2>Terms of use · <?php echo esc_html( $n ); ?></h2>
  <p>This Wi-Fi service is provided by <?php echo esc_html( $n ); ?>. By buying a plan or using a voucher you agree to these terms.</p>
  <h2>Plans and vouchers</h2>
  <p>Each plan gives internet access for the time, data and number of devices shown. Time starts at your first login and continues even if you disconnect. Unused vouchers do not expire. Vouchers cannot be exchanged for cash.</p>
  <h2>Fair and lawful use</h2>
  <p>Do not use the network for illegal activity, to attack other devices or to resell access. Speeds are “up to” and can vary with the number of users and the upstream internet connection.</p>
  <h2>Payments and refunds</h2>
  <p>Payments are processed securely by Flutterwave. If you were charged but did not receive a voucher, use “Recover voucher” or contact <?php echo esc_html( $n ); ?> through the Help page.</p>
  <h2>Privacy</h2>
  <p>We collect your phone number (and email if you give it), your device’s MAC address and usage to provide the service and support. Card details are never stored by this site.</p>
</article></div>
	<?php
	return zxc_portal_page( $t, 'Terms of use', ob_get_clean(), 'help' );
}

function zxc_view_portal_missing() {
	$body = zxc_site_nav() . '<main class="auth-form" style="min-height:70vh"><div class="card center stack" style="justify-items:center;max-width:440px;padding:34px"><span class="ico lg amber">' . zxc_icon( 'wifi' ) . '</span><h1 style="font-size:26px">Hotspot not found</h1><p class="muted">This hotspot page doesn’t exist or is temporarily offline.</p><a class="btn btn-primary" href="' . esc_url( home_url( '/' ) ) . '">Go to homepage</a></div></main>';
	return zxc_page( 'Hotspot not found', $body, array( 'robots' => 'noindex' ) );
}

/* ---------- Customer wallet ---------- */

function zxc_view_portal_wallet_login( $t, array $a ) {
	$buy = (int) zxc_get( 'buy', 10 );
	ob_start();
	?>
<div class="pt narrow">
  <section class="wallet-hero a-rise">
    <div class="wh-art" aria-hidden="true"><span class="coin c1">₦</span><span class="coin c2">₦</span><span class="wh-card"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?></span></div>
    <span class="eyebrow">Wi-Fi wallet</span>
    <h1>Load once, browse anytime</h1>
    <p>Keep a balance with <?php echo esc_html( $t->name ); ?> and buy plans in one tap — no card details every time.</p>
  </section>
  <form method="post" class="card stack a-rise2" action="<?php echo esc_url( zxc_portal_url( $t, 'wallet' ) . ( $buy ? '?buy=' . $buy : '' ) ); ?>">
    <input type="hidden" name="action" value="login">
    <div><div class="card-title" style="font-size:18px">Open or sign in to your wallet</div><div class="card-sub">New here? Choose a 4-digit PIN and your wallet is created instantly.</div></div>
    <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
    <label class="field">Phone number<span class="input-group"><span class="addon">+234</span><input class="input" type="tel" name="phone" inputmode="tel" placeholder="803 123 4567" value="<?php echo esc_attr( $a['values']['phone'] ?? '' ); ?>" required autocomplete="tel"></span></label>
    <label class="field">4-digit PIN<input class="input pin-input" type="password" name="pin" inputmode="numeric" pattern="\d{4}" maxlength="4" placeholder="••••" required autocomplete="current-password"></label>
    <label class="field">Your name <em>optional</em><input class="input" type="text" name="name" maxlength="120" placeholder="e.g. Tunde" value="<?php echo esc_attr( $a['values']['name'] ?? '' ); ?>" autocomplete="name"></label>
    <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'lock' ); // phpcs:ignore ?> Continue to wallet</button>
    <div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?><span>Your PIN is encrypted. Never share it with anyone, including staff.</span></div>
  </form>
  <div class="glass list how-grid" style="margin-top:4px">
    <div class="list-row"><span class="num">1</span><span><b>Top up</b><small>Card, transfer or USSD</small></span></div>
    <div class="list-row"><span class="num">2</span><span><b>Buy any plan</b><small>Paid instantly from balance</small></span></div>
    <div class="list-row"><span class="num">3</span><span><b>Get online</b><small>Connected automatically</small></span></div>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'Wallet', ob_get_clean(), 'wallet', zxc_portal_url( $t ) );
}

function zxc_view_portal_wallet( $t, array $a ) {
	$c       = $a['customer'];
	$bal     = (int) $a['balance'];
	$types   = array(
		'deposit'  => array( 'download', 'ok', 'Top-up' ),
		'credit'   => array( 'sparkles', 'ok', 'Bonus from ' . $t->name ),
		'purchase' => array( 'wifi', '', 'Plan purchase' ),
		'debit'    => array( 'receipt', 'amber', 'Adjustment' ),
	);
	$picked = null;
	foreach ( $a['plans'] as $p ) {
		if ( (int) $p->id === $a['buy'] ) {
			$picked = $p;
		}
	}
	ob_start();
	?>
<div class="pt">
  <?php if ( 'ok' === $a['topup'] ) : ?><?php echo zxc_alert( 'ok', 'Top-up successful. Your new balance is ' . zxc_money( $bal ) . '.' ); // phpcs:ignore ?><?php elseif ( 'pending' === $a['topup'] ) : ?><?php echo zxc_alert( 'warn', 'Your top-up is being confirmed. Bank transfers can take a few minutes — refresh this page shortly.' ); // phpcs:ignore ?><?php elseif ( 'failed' === $a['topup'] ) : ?><?php echo zxc_alert( 'bad', 'The top-up did not go through. You have not been charged.' ); // phpcs:ignore ?><?php endif; ?>
  <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
  <div class="wallet-grid">
    <div class="stack" style="gap:18px">
      <section class="bank-card a-rise" aria-label="Wallet balance">
        <div class="row between"><span class="row" style="gap:10px"><?php echo zxc_brand_mark( $t ); // phpcs:ignore ?><b style="font-family:var(--display);font-weight:600"><?php echo esc_html( $t->name ); ?> Wallet</b></span><span class="chip-ic" aria-hidden="true"></span></div>
        <div><small>Available balance</small><div class="bal" data-count="<?php echo (int) $bal; ?>"><?php echo esc_html( zxc_money( $bal ) ); ?></div></div>
        <div class="row between wrap-row" style="gap:10px"><span class="mono" style="letter-spacing:.14em;opacity:.85"><?php echo esc_html( zxc_mask_phone( $c->phone ) ); ?></span><span style="opacity:.85;font-size:13px"><?php echo esc_html( $c->name ? $c->name : 'Wi-Fi customer' ); ?></span></div>
      </section>
      <?php if ( $picked ) : ?>
        <form method="post" class="card stack confirm-buy a-rise2"><input type="hidden" name="action" value="buy"><input type="hidden" name="plan" value="<?php echo (int) $picked->id; ?>">
          <div class="row"><span class="ico"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span><div class="grow"><div class="card-title"><?php echo esc_html( $picked->name ); ?></div><div class="card-sub"><?php echo esc_html( zxc_duration( $picked->duration_min ) . ' · ' . zxc_data_label( $picked->data_mb ) ); ?></div></div><b class="price-tag"><?php echo esc_html( zxc_money( $picked->price ) ); ?></b></div>
          <?php if ( $bal >= $picked->price ) : ?><button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'zap' ); // phpcs:ignore ?> Pay <?php echo esc_html( zxc_money( $picked->price ) ); ?> from wallet</button>
          <?php else : ?><p class="fine">Top up at least <b><?php echo esc_html( zxc_money( $picked->price - $bal ) ); ?></b> to buy this plan.</p><?php endif; ?>
        </form>
      <?php endif; ?>
      <section class="card">
        <div class="card-head"><div><div class="card-title">Buy with your balance</div><div class="card-sub">Instant — no payment page</div></div></div>
        <div class="wplans">
          <?php foreach ( $a['plans'] as $p ) : ?>
            <form method="post" class="wplan <?php echo $bal >= $p->price ? '' : 'short'; ?>"><input type="hidden" name="action" value="buy"><input type="hidden" name="plan" value="<?php echo (int) $p->id; ?>">
              <span class="ico sm"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?></span>
              <span class="grow"><b><?php echo esc_html( $p->name ); ?></b><small><?php echo esc_html( zxc_duration( $p->duration_min ) . ' · ' . zxc_data_label( $p->data_mb ) ); ?></small></span>
              <b class="pp"><?php echo esc_html( zxc_money( $p->price ) ); ?></b>
              <button class="btn btn-sm <?php echo $bal >= $p->price ? 'btn-primary' : 'btn-ghost'; ?>" type="submit" <?php disabled( $bal < $p->price ); ?>><?php echo $bal >= $p->price ? 'Buy' : 'Low balance'; ?></button>
            </form>
          <?php endforeach; ?>
          <?php if ( ! $a['plans'] ) : ?><p class="fine">No plans available yet.</p><?php endif; ?>
        </div>
      </section>
    </div>
    <div class="stack" style="gap:18px">
      <form method="post" class="card stack a-rise2"><input type="hidden" name="action" value="topup">
        <div class="row"><span class="ico"><?php echo zxc_icon( 'plus' ); // phpcs:ignore ?></span><div><div class="card-title">Top up</div><div class="card-sub">Card, bank transfer or USSD</div></div></div>
        <label class="field">Amount (₦)<span class="input-group"><span class="addon">₦</span><input class="input" type="number" name="amount" min="100" max="200000" step="50" value="1000" required data-amount style="font-family:var(--display);font-size:20px;font-weight:700"></span></label>
        <div class="chips" style="flex-wrap:wrap"><?php foreach ( array( 200, 500, 1000, 2000, 5000 ) as $q ) : ?><button type="button" class="chip" data-quick="<?php echo (int) $q; ?>"><?php echo esc_html( zxc_money( $q ) ); ?></button><?php endforeach; ?></div>
        <button class="btn btn-primary btn-lg btn-block" type="submit" <?php disabled( ! $a['open'] ); ?>><?php echo zxc_icon( 'lock' ); // phpcs:ignore ?> Top up securely</button>
        <?php if ( ! $a['open'] ) : ?><?php echo zxc_alert( 'warn', 'Online top-up is not available here right now. Ask the attendant to load your wallet with cash.' ); // phpcs:ignore ?>
        <?php elseif ( $a['demo'] ) : ?><?php echo zxc_alert( 'warn', 'Test mode: this hotspot has not connected Flutterwave yet, so top-ups are simulated.' ); // phpcs:ignore ?>
        <?php else : ?><div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?><span>Secured by Flutterwave</span></div><?php endif; ?>
      </form>
      <section class="card">
        <div class="card-head"><div class="card-title">Activity</div><a class="btn btn-ghost btn-sm" href="<?php echo esc_url( zxc_portal_url( $t, 'wallet/logout' ) ); ?>"><?php echo zxc_icon( 'logout' ); // phpcs:ignore ?> Sign out</a></div>
        <?php if ( $a['history'] ) : ?><div class="tx-list"><?php foreach ( $a['history'] as $h ) : ?><?php $ty = $types[ $h->type ] ?? array( 'wallet', '', ucfirst( $h->type ) ); ?>
          <div class="tx"><span class="ico sm <?php echo esc_attr( 'failed' === $h->status ? 'bad' : ( $h->amount < 0 ? '' : $ty[1] ) ); ?>"><?php echo zxc_icon( 'failed' === $h->status ? 'x' : $ty[0] ); // phpcs:ignore ?></span><span class="grow"><b><?php echo esc_html( 'failed' === $h->status ? 'Top-up failed' : $ty[2] ); ?></b><small><?php echo esc_html( ( 'purchase' === $h->type ? strtok( $h->note, '·' ) . ' · ' : '' ) . zxc_date( $h->created ) ); ?></small></span><b class="amt <?php echo $h->amount >= 0 && 'success' === $h->status ? 'up' : ''; ?>"><?php echo esc_html( ( $h->amount >= 0 ? '+' : '−' ) . zxc_money( abs( $h->amount ) ) ); ?></b></div>
        <?php endforeach; ?></div>
        <?php else : ?><div class="empty" style="padding:18px"><span class="ico"><?php echo zxc_icon( 'wallet', 'a-float' ); // phpcs:ignore ?></span>No activity yet. Top up to get started.</div><?php endif; ?>
      </section>
    </div>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'My wallet', ob_get_clean(), 'wallet', zxc_portal_url( $t ) );
}

function zxc_view_portal_wallet_demo( $t, $w ) {
	ob_start();
	?>
<div class="pt narrow"><form method="post" class="card stack center" style="justify-items:center;padding:30px">
  <span class="badge badge-warn">Test payment</span>
  <h1 style="font-size:38px"><?php echo esc_html( zxc_money( $w->amount ) ); ?></h1>
  <p class="muted">Wallet top-up · Ref <?php echo esc_html( $w->reference ); ?></p>
  <button class="btn btn-primary btn-lg btn-block" name="result" value="success" type="submit">Simulate successful top-up</button>
  <button class="btn btn-ghost btn-block" name="result" value="failed" type="submit">Simulate failed top-up</button>
</form></div>
	<?php
	return zxc_portal_page( $t, 'Test top-up', ob_get_clean(), 'wallet' );
}
