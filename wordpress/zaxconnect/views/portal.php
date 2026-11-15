<?php
/**
 * Customer-facing captive portal, branded per business.
 */
defined( 'ABSPATH' ) || exit;

function zxc_portal_header( $t, $back = '' ) {
	ob_start();
	?>
<header class="topbar">
  <div class="container topbar-inner">
    <?php if ( $back ) : ?>
      <a href="<?php echo esc_url( $back ); ?>" class="icon-btn" aria-label="Back"><?php echo zxc_icon( 'back' ); // phpcs:ignore ?></a>
    <?php endif; ?>
    <a href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" class="brand">
      <?php echo zxc_brand_mark( $t ); // phpcs:ignore ?>
      <span class="brand-text"><span class="brand-name"><?php echo esc_html( $t->name ); ?></span><span class="brand-sub">Powered by <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?></span></span>
    </a>
    <nav class="topnav" aria-label="Portal">
      <a href="<?php echo esc_url( zxc_portal_url( $t ) . '#plans' ); ?>">Plans</a>
      <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>">Use voucher</a>
      <a href="<?php echo esc_url( zxc_portal_url( $t, 'recover' ) ); ?>">Lost code?</a>
    </nav>
    <div class="top-actions">
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
      <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) ); ?>" class="btn btn-dark desk-only">Check my plan</a>
    </div>
  </div>
</header>
	<?php
	return ob_get_clean();
}

function zxc_portal_footer( $t, $active = 'home' ) {
	$wa = $t->support_whatsapp ? 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', $t->support_whatsapp ) ) : '';
	ob_start();
	?>
<footer class="pt-foot container">
  <?php if ( $wa || $t->support_phone ) : ?>
    <div class="links">
      <span class="muted" style="align-self:center">Need help?</span>
      <?php if ( $wa ) : ?><a href="<?php echo esc_url( $wa ); ?>"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?> WhatsApp</a><?php endif; ?>
      <?php if ( $t->support_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $t->support_phone ) ); ?>"><?php echo zxc_icon( 'call' ); // phpcs:ignore ?> <?php echo esc_html( $t->support_phone ); ?></a><?php endif; ?>
    </div>
  <?php endif; ?>
  <div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?> Payments secured by Flutterwave · <?php echo esc_html( $t->name ); ?><?php echo $t->location ? ' · ' . esc_html( $t->location ) : ''; ?></div>
</footer>
	<?php
	$help = $wa ? $wa : ( $t->support_phone ? 'tel:' . preg_replace( '/[^0-9+]/', '', $t->support_phone ) : zxc_portal_url( $t, 'recover' ) );
	echo zxc_bottom_nav( // phpcs:ignore
		array(
			array( zxc_portal_url( $t ), 'home', 'Home', 'home' === $active ),
			array( zxc_portal_url( $t ) . '#plans', 'wifi', 'Plans', 'plans' === $active ),
			array( zxc_portal_url( $t, 'plan' ) . '?tab=voucher', 'ticket', 'Voucher', 'voucher' === $active ),
			array( zxc_portal_url( $t, 'plan' ), 'clock', 'My plan', 'plan' === $active ),
			array( $help, 'help', 'Help', 'help' === $active ),
		)
	);
	return ob_get_clean();
}

function zxc_portal_page( $t, $title, $content, $active = 'home', $back = '', $scripts = array() ) {
	$body = zxc_portal_header( $t, $back ) . '<main class="container">' . $content . '</main>' . zxc_portal_footer( $t, $active );
	return zxc_page( $title . ' · ' . $t->name, $body, array( 'body_class' => 'has-bottom-nav portal-body', 'style' => zxc_brand_style( $t ), 'scripts' => $scripts, 'robots' => 'noindex' ) );
}

function zxc_hero_art() {
	return '<svg class="art" viewBox="0 0 300 190" aria-hidden="true">
    <g class="a-orbit"><circle cx="150" cy="95" r="80" fill="none" stroke="rgba(255,255,255,.08)" stroke-dasharray="3 7"/><circle cx="230" cy="95" r="4" fill="#14C8E8"/><circle cx="70" cy="95" r="3" fill="#A79BFF"/></g>
    <g class="a-float">
      <rect x="112" y="98" width="76" height="34" rx="9" fill="#1C2246" stroke="rgba(255,255,255,.18)"/>
      <circle cx="128" cy="115" r="3" fill="#22D39A"/><circle cx="140" cy="115" r="3" fill="#14C8E8"/><circle cx="152" cy="115" r="3" fill="rgba(255,255,255,.35)"/>
      <path d="M132 98v-14M168 98v-14" stroke="rgba(255,255,255,.35)" stroke-width="3" stroke-linecap="round"/>
      <path class="w1" d="M138 78a17 17 0 0 1 24 0" fill="none" stroke="#14C8E8" stroke-width="3.2" stroke-linecap="round"/>
      <path class="w2" d="M130 68a29 29 0 0 1 40 0" fill="none" stroke="var(--accent)" stroke-width="3.2" stroke-linecap="round"/>
      <path class="w3" d="M122 58a41 41 0 0 1 56 0" fill="none" stroke="#A79BFF" stroke-width="3.2" stroke-linecap="round"/>
    </g></svg>';
}

function zxc_view_portal_home( $t, array $a ) {
	$plans   = $a['plans'];
	$popular = null;
	foreach ( $plans as $p ) {
		if ( $p->popular ) {
			$popular = $p;
			break;
		}
	}
	$cats = array();
	foreach ( $plans as $p ) {
		$cats[ zxc_plan_category( $p ) ] = true;
	}
	ob_start();
	?>
<div class="pt-main">
  <?php echo zxc_alert( 'bad', $a['error'] ); // phpcs:ignore ?>
  <section class="pt-hero a-rise">
    <?php echo zxc_hero_art(); // phpcs:ignore ?>
    <span class="live-tag"><span class="live-dot a-pulse"></span>Hotspot online</span>
    <h1><?php echo esc_html( $t->tagline ? $t->tagline : 'Fast Wi-Fi. Pay small, browse big.' ); ?></h1>
    <p>Pick a plan, pay in seconds with card, transfer or USSD, and get online instantly.</p>
    <div class="hero-cta">
      <a href="#plans" class="btn btn-primary btn-lg">See plans</a>
      <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>" class="btn btn-lg" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.18);color:#fff">I have a voucher</a>
    </div>
    <?php if ( ! empty( $a['ctx']['mac'] ) ) : ?>
      <div class="device-chip"><?php echo zxc_icon( 'phone' ); // phpcs:ignore ?> Your device <b><?php echo esc_html( $a['ctx']['mac'] ); ?></b></div>
    <?php endif; ?>
  </section>

  <nav class="quick" aria-label="Quick actions">
    <a href="#plans"><span class="ico-tile"><?php echo zxc_icon( 'bolt', 'a-bolt' ); // phpcs:ignore ?></span>Buy plan</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>"><span class="ico-tile teal"><?php echo zxc_icon( 'ticket', 'a-float' ); // phpcs:ignore ?></span>Voucher</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) ); ?>"><span class="ico-tile amber"><?php echo zxc_icon( 'clockAnim' ); // phpcs:ignore ?></span>My plan</a>
  </nav>

  <section id="plans" class="stack" style="gap:16px;scroll-margin-top:90px">
    <div class="sec-head">
      <div><h2>Choose a plan</h2><p>Time starts counting at your first login.</p></div>
      <?php if ( count( $cats ) > 1 ) : ?>
        <div class="chips" role="group" aria-label="Filter plans">
          <button type="button" class="chip on" data-filter="all">All</button>
          <?php foreach ( array( 'hourly' => 'Hourly', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly' ) as $k => $label ) : ?>
            <?php if ( isset( $cats[ $k ] ) ) : ?><button type="button" class="chip" data-filter="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></button><?php endif; ?>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php if ( ! $plans ) : ?>
      <div class="card empty"><span class="ico-tile"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?></span>No plans are available right now. Please check back shortly.</div>
    <?php endif; ?>
    <div class="plans">
      <?php foreach ( $plans as $p ) : ?>
        <?php
        $href = zxc_portal_url( $t, 'buy/' . $p->id );
        $meta = ( $p->data_mb ? zxc_data_label( $p->data_mb ) : 'Unlimited' ) . ' · ' . zxc_speed_label( $p->download_mbps ) . ' · ' . $p->devices . ' device' . ( $p->devices > 1 ? 's' : '' );
        ?>
        <a class="plan <?php echo $p->popular ? 'popular' : ''; ?> <?php echo $popular && $popular->id === $p->id ? 'on' : ''; ?>" href="<?php echo esc_url( $href ); ?>" data-plan data-href="<?php echo esc_url( $href ); ?>" data-name="<?php echo esc_attr( $p->name ); ?>" data-price="<?php echo esc_attr( zxc_money( $p->price ) ); ?>" data-cat="<?php echo esc_attr( zxc_plan_category( $p ) ); ?>">
          <span class="ico-tile"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span>
          <span class="plan-info">
            <span class="plan-name"><?php echo esc_html( $p->name ); ?><?php if ( $p->popular ) : ?> <span class="badge badge-accent">Popular</span><?php endif; ?></span>
            <span class="plan-meta"><?php echo esc_html( $p->description ? $p->description : $meta ); ?></span>
          </span>
          <span class="plan-price"><b><?php echo esc_html( zxc_money( $p->price ) ); ?></b><small><?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></small></span>
          <span class="card-extra">
            <span><?php echo zxc_icon( 'clock' ); // phpcs:ignore ?>Valid for <?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></span>
            <span><?php echo zxc_icon( 'data' ); // phpcs:ignore ?><?php echo esc_html( zxc_data_label( $p->data_mb ) ); ?></span>
            <span><?php echo zxc_icon( 'bolt' ); // phpcs:ignore ?><?php echo esc_html( zxc_speed_label( $p->download_mbps ) ); ?></span>
            <span><?php echo zxc_icon( 'devices' ); // phpcs:ignore ?><?php echo (int) $p->devices; ?> device<?php echo $p->devices > 1 ? 's' : ''; ?></span>
            <span class="btn <?php echo $p->popular ? 'btn-primary' : 'btn-soft'; ?> btn-block">Buy now</span>
          </span>
        </a>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="stack" style="gap:12px">
    <h2 style="font-size:18px">How it works</h2>
    <div class="list-card how">
      <div class="list-row"><span class="num-tile">1</span><span><b>Pick a plan</b><small>Hourly, daily, weekly or monthly</small></span></div>
      <div class="list-row"><span class="num-tile">2</span><span><b>Pay securely</b><small>Card, bank transfer or USSD</small></span></div>
      <div class="list-row"><span class="num-tile">3</span><span><b>You’re online</b><small>We connect your device automatically</small></span></div>
    </div>
  </section>
</div>
<?php if ( $popular ) : ?>
<div class="select-bar mob-only" data-select-bar>
  <div class="spacer" style="min-width:0"><small>Selected</small><b data-sel-name><?php echo esc_html( $popular->name . ' · ' . zxc_money( $popular->price ) ); ?></b></div>
  <a href="<?php echo esc_url( zxc_portal_url( $t, 'buy/' . $popular->id ) ); ?>" class="btn btn-primary" data-sel-go>Continue <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a>
</div>
<?php else : ?>
<div class="select-bar mob-only hide" data-select-bar>
  <div class="spacer" style="min-width:0"><small>Selected</small><b data-sel-name></b></div>
  <a href="#plans" class="btn btn-primary" data-sel-go>Continue <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a>
</div>
<?php endif; ?>
	<?php
	return zxc_portal_page( $t, 'Get online', ob_get_clean(), 'home' );
}

function zxc_view_portal_checkout( $t, array $a ) {
	$p = $a['plan'];
	ob_start();
	?>
<div class="pt-main">
  <div class="stepper"><span class="s done"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span>Plan<span class="ln on"></span><span class="s on">2</span><span style="color:var(--ink)">Pay</span><span class="ln"></span><span class="s">3</span>Connect</div>
  <div class="checkout-grid">
    <section class="summary a-rise">
      <svg width="120" height="80" viewBox="0 0 120 80" aria-hidden="true" style="position:absolute;right:-6px;top:6px"><path class="w1" d="M48 58a17 17 0 0 1 24 0" fill="none" stroke="#14C8E8" stroke-width="3" stroke-linecap="round"/><path class="w2" d="M40 48a29 29 0 0 1 40 0" fill="none" stroke="#7C6CFF" stroke-width="3" stroke-linecap="round"/><path class="w3" d="M32 38a41 41 0 0 1 56 0" fill="none" stroke="#A79BFF" stroke-width="3" stroke-linecap="round"/></svg>
      <div class="k">You’re buying</div>
      <h2><?php echo esc_html( $p->name ); ?></h2>
      <div class="price"><?php echo esc_html( zxc_money( $p->price ) ); ?></div>
      <div class="spec-grid">
        <div><small>Validity</small><b><?php echo esc_html( zxc_duration( $p->duration_min ) ); ?></b></div>
        <div><small>Data</small><b><?php echo esc_html( $p->data_mb ? str_replace( ' data', '', zxc_data_label( $p->data_mb ) ) : 'Unlimited' ); ?></b></div>
        <div><small>Speed</small><b><?php echo esc_html( zxc_speed_label( $p->download_mbps ) ); ?></b></div>
        <div><small>Devices</small><b><?php echo (int) $p->devices; ?></b></div>
      </div>
      <p style="margin-top:14px;font-size:12.5px;color:rgba(255,255,255,.6)">Validity starts at your first login. Unused vouchers never expire.</p>
    </section>

    <form method="post" class="card card-pad stack" style="gap:16px" action="<?php echo esc_url( zxc_portal_url( $t, 'buy/' . $p->id ) ); ?>">
      <h2 style="font-size:18px">Your details</h2>
      <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
      <?php if ( ! empty( $a['closed'] ) ) : ?>
        <?php echo zxc_alert( 'warn', 'Online payment is not available at this hotspot right now. Ask the attendant for a voucher card.' ); // phpcs:ignore ?>
      <?php endif; ?>
      <label class="field">Phone number
        <span class="input-group"><span class="input-addon">+234</span><input class="input" type="tel" name="phone" inputmode="tel" placeholder="803 123 4567" value="<?php echo esc_attr( $a['values']['phone'] ?? '' ); ?>" required autocomplete="tel"></span>
      </label>
      <label class="field">Email <em>optional, for your receipt</em><input class="input" type="email" name="email" placeholder="you@example.com" value="<?php echo esc_attr( $a['values']['email'] ?? '' ); ?>" autocomplete="email"></label>
      <div class="stack" style="gap:10px">
        <span class="field" style="margin:0">Pay with</span>
        <?php foreach ( array( array( 'card', 'Card', 'Visa, Mastercard, Verve' ), array( 'bank', 'Bank transfer', 'Pay from any Nigerian bank app' ), array( 'ussd', 'USSD', 'Dial a short code, no internet needed' ) ) as $i => $m ) : ?>
          <label class="method"><input type="radio" name="method" value="<?php echo esc_attr( $m[0] ); ?>" <?php checked( 1 === $i ); ?>>
            <span class="ico-tile" style="width:42px;height:42px;border-radius:13px"><?php echo zxc_icon( $m[0] ); // phpcs:ignore ?></span>
            <span class="spacer"><b style="display:block;font-size:14.5px"><?php echo esc_html( $m[1] ); ?></b><small class="fine"><?php echo esc_html( $m[2] ); ?></small></span>
            <span class="radio"></span></label>
        <?php endforeach; ?>
      </div>
      <button class="btn btn-primary btn-lg btn-block" type="submit" <?php disabled( ! empty( $a['closed'] ) ); ?>><?php echo zxc_icon( 'shield', 'a-bolt' ); // phpcs:ignore ?> Pay <?php echo esc_html( zxc_money( $p->price ) ); ?> securely</button>
      <?php if ( ! empty( $a['demo'] ) && empty( $a['closed'] ) ) : ?>
        <?php echo zxc_alert( 'warn', 'Test mode: this hotspot has not connected Flutterwave yet, so payment is simulated.' ); // phpcs:ignore ?>
      <?php else : ?>
        <div class="trust"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?> Secured by Flutterwave. Your card details never touch this site.</div>
      <?php endif; ?>
    </form>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'Checkout', ob_get_clean(), 'plans', zxc_portal_url( $t ) );
}

function zxc_view_portal_demo_pay( $t, $o ) {
	ob_start();
	?>
<div class="pt-main narrow">
  <form method="post" class="card card-pad stack center" style="justify-items:center">
    <span class="badge badge-warn">Test payment</span>
    <h1 style="font-size:34px"><?php echo esc_html( zxc_money( $o->amount ) ); ?></h1>
    <p class="muted"><?php echo esc_html( $o->plan_name ); ?> · Ref <?php echo esc_html( $o->reference ); ?></p>
    <button class="btn btn-primary btn-lg btn-block" name="result" value="success" type="submit">Simulate successful payment</button>
    <button class="btn btn-ghost btn-block" name="result" value="failed" type="submit">Simulate failed payment</button>
  </form>
</div>
	<?php
	return zxc_portal_page( $t, 'Test payment', ob_get_clean(), 'plans' );
}

function zxc_view_portal_pending( $t, $o ) {
	ob_start();
	?>
<div class="pt-main narrow" data-poll="<?php echo esc_url( rest_url( 'zaxconnect/v1/order/' . $o->reference ) ); ?>" data-go="<?php echo esc_url( zxc_portal_url( $t, 'pay/check/' . $o->reference ) ); ?>">
  <div class="card card-pad stack center" style="justify-items:center">
    <svg width="56" height="56" viewBox="0 0 50 50" aria-hidden="true"><circle cx="25" cy="25" r="20" fill="none" stroke="var(--accent-soft)" stroke-width="5"/><circle class="a-spin" style="transform-origin:center" cx="25" cy="25" r="20" fill="none" stroke="var(--accent)" stroke-width="5" stroke-linecap="round" stroke-dasharray="30 100"/></svg>
    <h1 style="font-size:24px">Confirming your payment…</h1>
    <p class="muted">This usually takes a few seconds. Bank transfers can take up to a few minutes. Keep this page open.</p>
    <div class="receipt">
      <div><span>Reference</span><b class="mono"><?php echo esc_html( $o->reference ); ?></b></div>
      <div><span>Plan</span><b><?php echo esc_html( $o->plan_name ); ?></b></div>
      <div><span>Amount</span><b><?php echo esc_html( zxc_money( $o->amount ) ); ?></b></div>
    </div>
    <a class="btn btn-soft btn-block" href="<?php echo esc_url( zxc_portal_url( $t, 'pay/check/' . $o->reference ) ); ?>">Check again</a>
  </div>
</div>
	<?php
	return zxc_portal_page( $t, 'Confirming payment', ob_get_clean(), 'plans' );
}

function zxc_view_portal_failed( $t, $o ) {
	ob_start();
	?>
<div class="pt-main narrow center">
  <div class="fail-art a-pop"><?php echo zxc_icon( 'alert' ); // phpcs:ignore ?></div>
  <div><h1 style="font-size:26px">Payment not completed</h1><p class="muted" style="margin-top:8px">Your payment for <b><?php echo esc_html( $o->plan_name ); ?></b> did not go through. You have not been charged.</p></div>
  <a href="<?php echo esc_url( zxc_portal_url( $t, 'buy/' . $o->plan_id ) ); ?>" class="btn btn-primary btn-lg btn-block">Try again</a>
  <a href="<?php echo esc_url( zxc_portal_url( $t ) ); ?>" class="btn btn-ghost btn-block">Choose another plan</a>
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
<div class="pt-main narrow center"<?php echo $a['ready'] ? '' : ' data-poll="' . esc_url( rest_url( 'zaxconnect/v1/order/' . $o->reference ) ) . '" data-poll-ready data-go="' . esc_url( zxc_portal_url( $t, 'success/' . $o->reference ) ) . '"'; ?>>
  <div class="success-art">
    <span class="ring"></span><span class="ring r2"></span>
    <span class="conf" style="left:6px;top:0;background:#7C6CFF"></span><span class="conf" style="right:4px;top:6px;background:#14C8E8;border-radius:50%;animation-delay:.5s"></span><span class="conf" style="left:30px;top:-6px;background:#FDB022;animation-delay:.9s"></span><span class="conf" style="right:28px;top:-4px;background:#F38EC5;animation-delay:.3s"></span>
    <span class="check a-pop"><svg width="44" height="44" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path class="a-draw" d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
  </div>
  <div class="a-rise3"><h1 style="font-size:26px">Payment successful</h1><p class="muted" style="margin-top:6px">Your <b style="color:var(--ink)"><?php echo esc_html( $o->plan_name ); ?></b> is ready to use.</p></div>
  <section class="code-box a-rise3">
    <div class="eyebrow-tag" style="color:var(--muted)">Your voucher code</div>
    <div class="code-value" id="vcode"><?php echo esc_html( $code ); ?></div>
    <button type="button" class="btn btn-soft btn-sm" data-copy="<?php echo esc_attr( $code ); ?>"><?php echo zxc_icon( 'copy' ); // phpcs:ignore ?> Copy code</button>
  </section>
  <?php if ( $a['link'] && $a['ready'] ) : ?>
    <a href="<?php echo esc_url( $a['link'] ); ?>" class="btn btn-primary btn-lg btn-block" data-autoconnect><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?> Connect now</a>
    <p class="muted" style="font-size:13px">Connecting you automatically in <b data-countdown>5s</b>…</p>
  <?php elseif ( $a['link'] ) : ?>
    <span class="btn btn-ghost btn-lg btn-block" aria-live="polite"><svg width="20" height="20" viewBox="0 0 50 50" aria-hidden="true"><circle class="a-spin" style="transform-origin:center" cx="25" cy="25" r="20" fill="none" stroke="currentColor" stroke-width="6" stroke-linecap="round" stroke-dasharray="30 100"/></svg> Activating on the hotspot…</span>
    <p class="muted" style="font-size:13px">This takes up to 15 seconds. You’ll be connected automatically.</p>
  <?php else : ?>
    <?php echo zxc_alert( 'info', 'Connect to the ' . $t->name . ' Wi-Fi, open any website and enter this code under “I have a voucher”.' ); // phpcs:ignore ?>
  <?php endif; ?>
  <div class="receipt">
    <div><span>Reference</span><b class="mono"><?php echo esc_html( $o->reference ); ?></b></div>
    <div><span>Amount</span><b><?php echo esc_html( zxc_money( $o->amount ) ); ?></b></div>
    <div><span>Paid with</span><b><?php echo esc_html( $o->channel ? ucwords( str_replace( '_', ' ', $o->channel ) ) : 'Online' ); ?></b></div>
  </div>
  <p class="fine">Save this code. You can reconnect with it any time until your plan expires.</p>
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
<div class="pt-main narrow">
  <?php if ( $a['online'] ) : ?>
    <?php echo zxc_alert( 'ok', 'You’re connected! Enjoy your browsing.' ); // phpcs:ignore ?>
  <?php endif; ?>
  <div class="seg" role="tablist">
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . ( $a['code'] ? '?code=' . $a['code'] : '' ) ); ?>" class="<?php echo 'status' === $tab ? 'on' : ''; ?>" role="tab">Active plan</a>
    <a href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?tab=voucher' ); ?>" class="<?php echo 'voucher' === $tab ? 'on' : ''; ?>" role="tab">Enter voucher</a>
  </div>
  <?php echo zxc_alert( 'bad', $a['error'] ); // phpcs:ignore ?>

  <?php if ( 'voucher' === $tab ) : ?>
    <form class="card card-pad stack center a-rise" style="justify-items:center" method="post" action="<?php echo esc_url( zxc_portal_url( $t, 'voucher' ) ); ?>" data-voucher-form data-api="<?php echo esc_url( rest_url( 'zaxconnect/v1/voucher/' . $t->slug ) ); ?>">
      <span class="ico-tile" style="width:64px;height:64px;border-radius:20px"><?php echo zxc_icon( 'ticket', 'a-float' ); // phpcs:ignore ?></span>
      <h1 style="font-size:22px">Enter your voucher</h1>
      <p class="muted">Bought a voucher card? Type the 8-character code.</p>
      <label class="field" style="width:100%;text-align:left">Voucher code<input class="input input-code" name="code" placeholder="XXXX-XXXX" autocomplete="off" autocapitalize="characters" spellcheck="false" maxlength="9" required></label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Connect <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></button>
      <div class="form-msg" data-msg role="status"></div>
      <a href="<?php echo esc_url( zxc_portal_url( $t, 'recover' ) ); ?>" class="fine" style="text-decoration:underline">Lost your code? Recover it</a>
    </form>
  <?php elseif ( $v ) : ?>
    <?php
    $labels = array( 'unused' => array( 'Not used yet', '' ), 'active' => array( 'Active', 'badge-ok' ), 'expired' => array( 'Expired', 'badge-bad' ), 'exhausted' => array( 'Data used up', 'badge-bad' ), 'disabled' => array( 'Disabled', 'badge-bad' ) );
    $lab    = $labels[ $st['state'] ] ?? array( $st['state'], '' );
    $dash   = (int) round( 339 * ( 1 - ( 'active' === $st['state'] ? $st['ratio'] : ( 'unused' === $st['state'] ? 1 : 0 ) ) ) );
    $link   = zxc_login_link( $a['ctx'], $v->code, zxc_portal_url( $t, 'connected' ) . '?code=' . $v->code );
    ?>
    <section class="card card-pad stack a-rise" style="justify-items:center;gap:16px">
      <div class="row" style="width:100%;justify-content:space-between"><span class="badge <?php echo esc_attr( $lab[1] ); ?>"><?php if ( 'active' === $st['state'] ) : ?><span class="live-dot a-pulse"></span><?php endif; ?><?php echo esc_html( $lab[0] ); ?></span><span class="mono fine" style="font-weight:700"><?php echo esc_html( zxc_format_code( $v->code ) ); ?></span></div>
      <div class="ring-meter">
        <svg width="200" height="200" viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="54" fill="none" stroke="var(--track)" stroke-width="9"/><circle class="fill" cx="60" cy="60" r="54" fill="none" stroke="var(--accent)" stroke-width="9" stroke-linecap="round" stroke-dasharray="339" stroke-dashoffset="<?php echo (int) $dash; ?>"/></svg>
        <div class="label"><small class="muted" style="font-size:12px">Time left</small><b <?php echo 'active' === $st['state'] ? 'data-until="' . (int) $st['expires'] * 1000 . '"' : ''; ?>><?php echo esc_html( 'active' === $st['state'] ? zxc_remaining( $st['left'] ) : ( 'unused' === $st['state'] ? zxc_duration( $v->duration_min ) : '0m' ) ); ?></b><small class="muted" style="font-size:12px">of <?php echo esc_html( zxc_duration( $v->duration_min ) ); ?></small></div>
      </div>
      <b style="font-size:17px"><?php echo esc_html( $v->plan_name ); ?></b>
    </section>
    <div class="mini-stats">
      <div class="mini-stat"><span class="ico-tile teal"><?php echo zxc_icon( 'download' ); // phpcs:ignore ?></span><small>Data used</small><b><?php echo esc_html( zxc_bytes( $st['used'] ) ); ?></b><div class="fine"><?php echo esc_html( $st['cap'] ? 'of ' . zxc_bytes( $st['cap'] ) : 'Unlimited plan' ); ?></div></div>
      <div class="mini-stat"><span class="ico-tile"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span><small>Speed</small><b><?php echo esc_html( zxc_speed_label( $v->download_mbps ) ); ?></b><div class="fine"><?php echo (int) $v->devices; ?> device<?php echo $v->devices > 1 ? 's' : ''; ?></div></div>
    </div>
    <?php if ( $st['cap'] ) : ?>
      <div class="card card-pad"><div class="row" style="justify-content:space-between;font-size:13.5px"><span class="muted">Data used</span><b><?php echo (int) min( 100, round( $st['used'] / $st['cap'] * 100 ) ); ?>%</b></div><div class="bar-track warn" style="margin-top:10px"><span class="a-growx" style="width:<?php echo (int) min( 100, round( $st['used'] / $st['cap'] * 100 ) ); ?>%"></span></div></div>
    <?php endif; ?>
    <?php if ( $st['expires'] ) : ?>
      <div class="receipt"><div><span>Started</span><b><?php echo esc_html( zxc_date( $st['activated'] ) ); ?></b></div><div><span>Expires</span><b><?php echo esc_html( zxc_date( $st['expires'] ) ); ?></b></div></div>
    <?php endif; ?>
    <?php if ( $link && in_array( $st['state'], array( 'unused', 'active' ), true ) && ! $a['online'] ) : ?>
      <a href="<?php echo esc_url( $link ); ?>" class="btn btn-primary btn-lg btn-block"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?> Connect with this voucher</a>
    <?php endif; ?>
    <a href="<?php echo esc_url( zxc_portal_url( $t ) . '#plans' ); ?>" class="btn btn-ghost btn-lg btn-block"><?php echo in_array( $st['state'], array( 'expired', 'exhausted' ), true ) ? 'Buy a new plan' : 'Top up / buy another plan'; ?></a>
  <?php else : ?>
    <form class="card card-pad stack" method="get" action="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) ); ?>">
      <span class="ico-tile amber" style="width:56px;height:56px;border-radius:18px"><?php echo zxc_icon( 'clockAnim' ); // phpcs:ignore ?></span>
      <h1 style="font-size:22px">Check your plan</h1>
      <p class="muted">Enter your voucher code to see time and data left.</p>
      <label class="field">Voucher code<input class="input input-code" name="code" placeholder="XXXX-XXXX" autocomplete="off" required></label>
      <button class="btn btn-dark btn-lg btn-block" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Check status</button>
    </form>
  <?php endif; ?>
</div>
	<?php
	return zxc_portal_page( $t, $a['online'] ? 'Connected' : 'My plan', ob_get_clean(), 'voucher' === $tab ? 'voucher' : 'plan' );
}

function zxc_view_portal_recover( $t, array $a ) {
	ob_start();
	?>
<div class="pt-main narrow">
  <form class="card card-pad stack" method="post" action="<?php echo esc_url( zxc_portal_url( $t, 'recover' ) ); ?>">
    <span class="ico-tile" style="width:56px;height:56px;border-radius:18px"><?php echo zxc_icon( 'search', 'a-float' ); // phpcs:ignore ?></span>
    <h1 style="font-size:22px">Recover your voucher</h1>
    <p class="muted">Enter the phone number you paid with. For your security we show vouchers bought from this device, or enter the payment reference from your receipt.</p>
    <label class="field">Phone number<input class="input" type="tel" name="phone" value="<?php echo esc_attr( $a['phone'] ?? '' ); ?>" placeholder="0803 123 4567" required></label>
    <label class="field">Payment reference <em>if using another device</em><input class="input mono" name="reference" value="<?php echo esc_attr( $a['ref'] ?? '' ); ?>" placeholder="ZX…" autocomplete="off"></label>
    <button class="btn btn-dark btn-lg btn-block" type="submit"><?php echo zxc_icon( 'search' ); // phpcs:ignore ?> Find my voucher</button>
  </form>
  <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
  <?php if ( ! empty( $a['results'] ) ) : ?>
    <div class="list-card">
      <?php foreach ( $a['results'] as $r ) : ?>
        <div class="list-row"><span class="spacer"><b class="mono"><?php echo esc_html( zxc_format_code( $r->code ) ); ?></b><small><?php echo esc_html( $r->plan_name . ' · ' . zxc_date( $r->paid_at ) ); ?></small></span><a class="btn btn-soft btn-sm" href="<?php echo esc_url( zxc_portal_url( $t, 'plan' ) . '?code=' . $r->code ); ?>">View</a></div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
	<?php
	return zxc_portal_page( $t, 'Recover voucher', ob_get_clean(), 'help' );
}

function zxc_view_portal_missing() {
	$body = zxc_site_header( true ) . '<main class="auth-wrap"><div class="auth-card center stack" style="justify-items:center"><span class="ico-tile amber" style="width:60px;height:60px;border-radius:18px">' . zxc_icon( 'wifi' ) . '</span><h1>Hotspot not found</h1><p class="muted">This hotspot page does not exist or is temporarily offline.</p><a class="btn btn-primary" href="' . esc_url( home_url( '/' ) ) . '">Go to homepage</a></div></main>';
	return zxc_page( 'Hotspot not found', $body, array( 'robots' => 'noindex' ) );
}
