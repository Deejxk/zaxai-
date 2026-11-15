<?php
/**
 * Public SaaS pages: landing page, login, sign-up wizard.
 */
defined( 'ABSPATH' ) || exit;

function zxc_swatches() {
	return array( '#F04438' => 'Red', '#4F3BF0' => 'Violet', '#0E9384' => 'Teal', '#175CD3' => 'Blue', '#DC6803' => 'Orange', '#0B1020' => 'Ink' );
}

function zxc_site_header( $minimal = false ) {
	ob_start();
	?>
<header class="topbar">
  <div class="container topbar-inner">
    <?php echo zxc_logo(); // phpcs:ignore ?>
    <?php if ( $minimal ) : ?>
      <span class="spacer"></span>
    <?php else : ?>
      <nav class="topnav" aria-label="Primary">
        <a href="<?php echo esc_url( home_url( '/#features' ) ); ?>">Features</a>
        <a href="<?php echo esc_url( home_url( '/#how' ) ); ?>">How it works</a>
        <a href="<?php echo esc_url( home_url( '/#pricing' ) ); ?>">Pricing</a>
        <a href="<?php echo esc_url( home_url( '/#faq' ) ); ?>">FAQ</a>
      </nav>
    <?php endif; ?>
    <div class="top-actions">
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
      <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="btn btn-ghost desk-only">Log in</a>
      <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="btn btn-primary"><span class="desk-only">Start free trial</span><span class="mob-only">Get started</span></a>
    </div>
  </div>
</header>
	<?php
	return ob_get_clean();
}

function zxc_view_landing() {
	$name   = zxc_setting( 'platform_name' );
	$trial  = (int) zxc_setting( 'trial_days' );
	$tiers  = zxc_tiers();
	$feats  = array(
		array( 'star', '', 'Your own branded portal', 'Your logo, colours and business name on the page customers see when they join your Wi-Fi.' ),
		array( 'card', 'teal', 'Flutterwave payments', 'Card, bank transfer and USSD. Money settles straight into your own Flutterwave account.' ),
		array( 'ticket', 'amber', 'Automatic vouchers', 'Customers pay and get connected instantly. Print voucher cards to sell offline too.' ),
		array( 'bolt', 'blue', 'Time, speed and data limits', 'Hourly, daily or monthly plans with speed caps, data bundles and device limits.' ),
		array( 'trend', 'pink', 'Live business dashboard', 'Revenue, sales, online users and top plans, from your phone or laptop.' ),
		array( 'router', '', 'One-click router setup', 'We generate your MikroTik configuration. Paste it into your router and you’re live.' ),
	);
	$steps  = array(
		array( '01', 'Create your brand', 'Sign up, add your business name, logo and colours.' ),
		array( '02', 'Set your prices', 'Hourly, daily, weekly, monthly or data bundles. You decide.' ),
		array( '03', 'Connect your router', 'Plug a MikroTik into your internet source and paste our setup script.' ),
		array( '04', 'Start earning', 'Customers buy access on your portal. Payments go to your account.' ),
	);
	$faqs   = array(
		array( 'Do I need special equipment?', 'You need a MikroTik router between your internet source (MTN ODU, fibre or Starlink) and your Wi-Fi access points. Most hAP models work.' ),
		array( 'Who receives the payments?', 'Customer payments go straight to your own Flutterwave account. We never hold your money.' ),
		array( 'Can I use my own logo and colours?', 'Yes. Every plan includes your logo, brand colour and business name on your customer portal.' ),
		array( 'Can I sell vouchers offline?', 'Yes. Generate printable voucher cards from your dashboard and sell them through attendants or shops.' ),
		array( 'What happens after the free trial?', 'Choose a plan and pay monthly from your dashboard. Vouchers you already sold keep working.' ),
	);
	ob_start();
	echo zxc_site_header(); // phpcs:ignore
	?>
<main>
  <section class="mk-hero" id="top">
    <div class="container mk-hero-inner">
      <div class="mk-hero-copy a-rise">
        <span class="mk-badge"><span>NEW</span>Flutterwave payouts straight to your bank</span>
        <h1>Launch your own <em>Wi-Fi hotspot</em> business in minutes.</h1>
        <p class="mk-lead"><?php echo esc_html( $name ); ?> gives you a branded captive portal, automatic voucher billing and a business dashboard. Connect your router, set your prices and start selling Wi-Fi today.</p>
        <div class="mk-cta">
          <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="btn btn-primary btn-lg">Create your hotspot brand <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a>
          <a href="#how" class="btn btn-ghost btn-lg"><?php echo zxc_icon( 'play' ); // phpcs:ignore ?> See how it works</a>
        </div>
        <div class="mk-checks"><span><?php echo zxc_icon( 'check' ); // phpcs:ignore ?><?php echo (int) $trial; ?>-day free trial</span><span><?php echo zxc_icon( 'check' ); // phpcs:ignore ?>No card required</span><span><?php echo zxc_icon( 'check' ); // phpcs:ignore ?>Works with MikroTik</span></div>
      </div>
      <div class="mk-visual a-rise2" aria-hidden="true">
        <div class="mk-phone a-float">
          <div class="mk-screen" style="--accent:#F04438;--accent-ink:#fff">
            <div class="phone-bar"><span class="brand-mark" style="width:26px;height:26px;font-size:12px;border-radius:8px">L</span><b style="font-size:13px">Lekki Hub WiFi</b></div>
            <div style="margin:12px;padding:14px;border-radius:18px;background:#0B1020;color:#fff">
              <span class="live-tag" style="font-size:9.5px;padding:4px 8px"><span class="live-dot a-pulse" style="width:6px;height:6px"></span>Online</span>
              <div style="margin-top:8px;font-weight:800;font-size:16px;line-height:1.2">Fast Wi-Fi. Pay small, browse big.</div>
              <svg width="80" height="40" viewBox="0 0 80 40"><path class="w1" d="M28 30a17 17 0 0 1 24 0" fill="none" stroke="#14C8E8" stroke-width="3" stroke-linecap="round"/><path class="w2" d="M20 21a29 29 0 0 1 40 0" fill="none" stroke="#7C6CFF" stroke-width="3" stroke-linecap="round"/><path class="w3" d="M12 12a41 41 0 0 1 56 0" fill="none" stroke="#A79BFF" stroke-width="3" stroke-linecap="round"/></svg>
            </div>
            <div style="padding:0 12px;display:grid;gap:8px">
              <div class="mini-plan"><span>Quick Hour</span><span>₦100</span></div>
              <div class="mini-plan on"><span>Daily Pass</span><span>₦350</span></div>
              <div class="mini-plan"><span>Weekly Pass</span><span>₦1,500</span></div>
              <div style="margin-top:4px;height:40px;border-radius:12px;background:#F04438;color:#fff;font-weight:700;font-size:12.5px;display:grid;place-items:center">Pay ₦350</div>
            </div>
          </div>
        </div>
        <div class="mk-float a-float2" style="left:0;top:70px;width:210px">
          <div class="fine" style="font-weight:600">Revenue this week</div>
          <div style="margin-top:12px;height:58px;display:flex;align-items:flex-end;gap:6px">
            <?php foreach ( array( 40, 62, 48, 78, 66, 100 ) as $i => $h ) : ?>
              <span class="a-grow" style="flex:1;height:<?php echo (int) $h; ?>%;border-radius:4px 4px 0 0;background:<?php echo 5 === $i ? 'var(--primary)' : 'var(--primary-soft)'; ?>;animation-delay:<?php echo esc_attr( 0.1 * $i ); ?>s"></span>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="mk-float a-float3 row" style="right:0;top:300px;width:230px;padding:14px">
          <span class="ico-tile ok" style="border-radius:50%;width:40px;height:40px"><svg class="i" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round"><path class="a-draw" d="m5 12.5 4.5 4.5L19 7.5"/></svg></span>
          <div><b style="font-size:13px">Payment received</b><div class="fine">Daily Pass · voucher sent</div></div>
        </div>
        <div class="mk-float a-float2" style="right:26px;top:40px;width:64px;height:64px;padding:0;display:grid;place-items:center;color:var(--primary-ink)"><?php echo zxc_icon( 'router' ); // phpcs:ignore ?></div>
      </div>
    </div>
  </section>

  <section class="mk-strip"><div class="container"><span>Built for</span><b>Cafés</b><b>Hostels</b><b>Estates</b><b>Campuses</b><b>Event centres</b><b>Shops</b></div></section>

  <section class="mk-section" id="features">
    <div class="container">
      <div class="mk-head"><div class="eyebrow-tag">Everything included</div><h2>Run a professional hotspot business from one dashboard</h2></div>
      <div class="mk-grid">
        <?php foreach ( $feats as $f ) : ?>
          <article class="feature"><span class="ico-tile <?php echo esc_attr( $f[1] ); ?>"><?php echo zxc_icon( $f[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $f[2] ); ?></h3><p><?php echo esc_html( $f[3] ); ?></p></article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="mk-dark mk-section" id="how">
    <div class="container">
      <div style="max-width:640px"><div class="eyebrow-tag">How it works</div><h2 style="margin-top:12px;font-size:clamp(30px,4vw,42px)">From sign-up to first sale in four steps</h2></div>
      <div class="mk-grid" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr))">
        <?php foreach ( $steps as $s ) : ?>
          <div class="step-card"><div class="n"><?php echo esc_html( $s[0] ); ?></div><h3><?php echo esc_html( $s[1] ); ?></h3><p><?php echo esc_html( $s[2] ); ?></p></div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="mk-section" id="pricing">
    <div class="container">
      <div class="mk-head"><div class="eyebrow-tag">Pricing</div><h2>Simple plans that grow with you</h2><p>Start free for <?php echo (int) $trial; ?> days. Upgrade when you’re ready.</p></div>
      <div class="mk-grid">
        <?php foreach ( $tiers as $k => $t ) : ?>
          <div class="tier <?php echo 'business' === $k ? 'best' : ''; ?>">
            <div class="row" style="justify-content:space-between"><b style="font-size:18px"><?php echo esc_html( $t['name'] ); ?></b><?php if ( 'business' === $k ) : ?><span class="badge" style="background:var(--primary);color:#fff">Most popular</span><?php endif; ?></div>
            <p class="muted" style="margin-top:6px;font-size:14px"><?php echo esc_html( $t['desc'] ); ?></p>
            <div class="tier-price"><b><?php echo esc_html( zxc_money( $t['price'] ) ); ?></b><span class="muted">/ month</span></div>
            <a href="<?php echo esc_url( home_url( '/signup?plan=' . $k ) ); ?>" class="btn <?php echo 'business' === $k ? 'btn-primary' : 'btn-soft'; ?> btn-block" style="margin-top:22px">Start free trial</a>
            <ul><?php foreach ( $t['features'] as $f ) : ?><li><?php echo zxc_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $f ); ?></span></li><?php endforeach; ?></ul>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="container" id="faq" style="max-width:820px;padding-bottom:92px">
    <h2 class="center" style="font-size:34px">Questions, answered</h2>
    <div class="stack" style="margin-top:30px;gap:10px">
      <?php foreach ( $faqs as $i => $q ) : ?>
        <details class="faq" <?php echo 0 === $i ? 'open' : ''; ?>><summary><?php echo esc_html( $q[0] ); ?><?php echo zxc_icon( 'chevDown' ); // phpcs:ignore ?></summary><p><?php echo esc_html( $q[1] ); ?></p></details>
      <?php endforeach; ?>
    </div>
  </section>

  <section class="container" style="padding-bottom:92px">
    <div class="mk-cta-band">
      <svg width="420" height="420" viewBox="0 0 420 420" aria-hidden="true" style="position:absolute;right:-100px;top:-120px;opacity:.25"><circle class="w1" cx="210" cy="210" r="80" fill="none" stroke="#fff" stroke-width="2"/><circle class="w2" cx="210" cy="210" r="130" fill="none" stroke="#fff" stroke-width="2"/><circle class="w3" cx="210" cy="210" r="180" fill="none" stroke="#fff" stroke-width="2"/></svg>
      <div style="position:relative;max-width:560px"><h2>Ready to start selling Wi-Fi?</h2><p>Create your brand, connect your router and take your first payment today.</p></div>
      <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="btn btn-white btn-lg" style="position:relative">Start your free trial</a>
    </div>
  </section>
</main>
<footer class="site-foot"><div class="container"><span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $name ); ?>. All rights reserved.</span>
  <nav><a href="<?php echo esc_url( home_url( '/login' ) ); ?>">Owner login</a><?php if ( zxc_setting( 'support_email' ) ) : ?><a href="mailto:<?php echo esc_attr( zxc_setting( 'support_email' ) ); ?>">Contact</a><?php endif; ?></nav></div></footer>
	<?php
	echo zxc_bottom_nav( // phpcs:ignore
		array(
			array( '#top', 'home', 'Home', true ),
			array( '#features', 'star', 'Features' ),
			array( '#pricing', 'tag', 'Pricing' ),
			array( home_url( '/login' ), 'login', 'Log in' ),
		)
	);
	return zxc_page(
		$name . ' · Launch your Wi-Fi hotspot business',
		ob_get_clean(),
		array( 'body_class' => 'has-bottom-nav', 'description' => 'Branded captive portal, automatic voucher billing and a business dashboard for Wi-Fi hotspot owners.' )
	);
}

function zxc_view_login( array $a ) {
	ob_start();
	echo zxc_site_header( true ); // phpcs:ignore
	?>
<main class="auth-wrap">
  <div class="auth-card a-rise">
    <span class="ico-tile" style="width:52px;height:52px;border-radius:16px"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span>
    <h1 style="margin-top:18px">Welcome back</h1>
    <p class="muted" style="margin-top:6px">Log in to manage your hotspot business.</p>
    <form method="post" action="<?php echo esc_url( home_url( '/login' ) ); ?>" class="stack">
      <?php echo zxc_nonce_field( 'zxc_login' ) . zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
      <label class="field">Email address<input class="input" type="email" name="email" value="<?php echo esc_attr( $a['email'] ?? '' ); ?>" autocomplete="email" required autofocus></label>
      <label class="field">Password<input class="input" type="password" name="password" autocomplete="current-password" required></label>
      <button class="btn btn-primary btn-lg btn-block" type="submit">Log in</button>
      <div class="row" style="justify-content:space-between;font-size:14px">
        <a class="link" href="<?php echo esc_url( wp_lostpassword_url( home_url( '/login' ) ) ); ?>">Forgot password?</a>
        <a class="link" href="<?php echo esc_url( home_url( '/signup' ) ); ?>">Create a hotspot</a>
      </div>
    </form>
  </div>
</main>
	<?php
	return zxc_page( 'Log in · ' . zxc_setting( 'platform_name' ), ob_get_clean(), array( 'robots' => 'noindex' ) );
}

function zxc_view_signup( array $a ) {
	$v     = $a['values'] ?? array();
	$val   = function ( $k, $d = '' ) use ( $v ) {
		return isset( $v[ $k ] ) && '' !== $v[ $k ] ? $v[ $k ] : $d;
	};
	$color = zxc_is_hex( $val( 'brand_color' ) ) ? $val( 'brand_color' ) : '#F04438';
	$steps = array( array( 'Your account', 'Name, email, password' ), array( 'Brand', 'Name, colour, logo' ), array( 'Plans & prices', 'What you sell' ), array( 'Payments', 'Connect Flutterwave' ), array( 'Router', 'Your first location' ) );
	$pname = zxc_setting( 'platform_name' );
	ob_start();
	?>
<header class="topbar">
  <div class="container topbar-inner">
    <?php echo zxc_logo(); // phpcs:ignore ?>
    <div class="top-actions"><span class="muted desk-only" style="font-size:13.5px">Step <span data-step-no>1</span> of 5</span><?php echo zxc_theme_toggle(); // phpcs:ignore ?></div>
  </div>
  <div class="progress"><span data-progress style="width:20%"></span></div>
</header>
<main class="container">
  <form class="wizard" method="post" action="<?php echo esc_url( home_url( '/signup' ) ); ?>" enctype="multipart/form-data" data-wizard data-start="<?php echo (int) ( $a['start'] ?? 0 ); ?>" novalidate>
    <?php echo zxc_nonce_field( 'zxc_signup' ); // phpcs:ignore ?>
    <input type="hidden" name="tier" value="<?php echo esc_attr( $a['tier'] ?? 'business' ); ?>">
    <nav class="wiz-steps" aria-label="Sign-up steps">
      <?php foreach ( $steps as $i => $s ) : ?>
        <button type="button" class="wiz-step" data-step-btn><span class="dot"><?php echo (int) $i + 1; ?></span><span><b><?php echo esc_html( $s[0] ); ?></b><small><?php echo esc_html( $s[1] ); ?></small></span></button>
      <?php endforeach; ?>
    </nav>

    <section class="wiz-body">
      <?php echo zxc_alert( 'bad', $a['error'] ?? '', true ); // phpcs:ignore ?>
      <div class="wiz-pane">
        <span class="ico-tile" style="width:60px;height:60px;border-radius:18px"><?php echo zxc_icon( 'user', 'a-float' ); // phpcs:ignore ?></span>
        <div><h1>Create your account</h1><p class="muted" style="margin-top:8px">You’ll use this to log in and manage your hotspot business.</p></div>
        <label class="field">Full name<input class="input" name="owner_name" value="<?php echo esc_attr( $val( 'owner_name' ) ); ?>" autocomplete="name" required maxlength="60"></label>
        <label class="field">Email address<input class="input" type="email" name="email" value="<?php echo esc_attr( $val( 'email' ) ); ?>" autocomplete="email" required></label>
        <label class="field">Password <em>at least 8 characters</em><input class="input" type="password" name="password" minlength="8" autocomplete="new-password" required></label>
      </div>

      <div class="wiz-pane">
        <div><h1>Create your hotspot brand</h1><p class="muted" style="margin-top:8px">This is what customers see when they connect to your Wi-Fi.</p></div>
        <label class="field">Business name<input class="input" name="business_name" value="<?php echo esc_attr( $val( 'business_name' ) ); ?>" placeholder="e.g. Lekki Hub WiFi" required maxlength="50"></label>
        <div class="field">Your portal address
          <div class="input-group"><span class="input-addon"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>/h/</span><span class="input" style="display:flex;align-items:center"><b data-slug>your-hotspot</b></span></div>
        </div>
        <div class="field">Brand colour
          <div class="swatches" data-swatches="#brand_color">
            <?php foreach ( zxc_swatches() as $c => $n ) : ?>
              <button type="button" class="swatch <?php echo strtolower( $c ) === strtolower( $color ) ? 'on' : ''; ?>" data-color="<?php echo esc_attr( $c ); ?>" style="background:<?php echo esc_attr( $c ); ?>;--sw:<?php echo esc_attr( $c ); ?>" aria-label="<?php echo esc_attr( $n ); ?>"></button>
            <?php endforeach; ?>
            <input class="input mono" id="brand_color" name="brand_color" value="<?php echo esc_attr( $color ); ?>" pattern="#[0-9A-Fa-f]{6}" style="width:120px;height:44px" aria-label="Custom colour (hex)">
          </div>
        </div>
        <div class="logo-drop">
          <span class="brand-mark" data-pv-initial style="width:56px;height:56px;border-radius:16px;font-size:24px;background:<?php echo esc_attr( $color ); ?>">Y</span>
          <div class="spacer"><b style="font-size:14px">Logo</b><div class="fine" id="logo-msg">Optional · PNG, JPG or WebP, under 500 KB</div></div>
          <label class="btn btn-ghost btn-sm">Upload<input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" class="sr" data-logo-input="#logo_preview" data-logo-msg="#logo-msg"></label>
          <input type="hidden" id="logo_preview">
        </div>
      </div>

      <div class="wiz-pane">
        <span class="ico-tile teal" style="width:60px;height:60px;border-radius:18px"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span>
        <div><h1>Plans &amp; prices</h1><p class="muted" style="margin-top:8px">We’ll add six popular plans for you. Set your two main prices now and fine-tune everything later.</p></div>
        <div class="form-grid">
          <label class="field">Quick Hour price (₦)<input class="input" type="number" name="price_hour" min="0" value="<?php echo esc_attr( $val( 'price_hour', 100 ) ); ?>" required></label>
          <label class="field">Daily Pass price (₦)<input class="input" type="number" name="price_daily" min="0" value="<?php echo esc_attr( $val( 'price_daily', 350 ) ); ?>" required></label>
        </div>
        <div class="list-card">
          <?php foreach ( array( array( '3 Hours', '₦200' ), array( '2GB Bundle', '₦500' ), array( 'Weekly Pass', '₦1,500' ), array( 'Monthly Pass', '₦5,000' ) ) as $p ) : ?>
            <div class="list-row"><span class="spacer"><b><?php echo esc_html( $p[0] ); ?></b><small>Added automatically</small></span><b><?php echo esc_html( $p[1] ); ?></b></div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="wiz-pane">
        <span class="ico-tile amber" style="width:60px;height:60px;border-radius:18px"><?php echo zxc_icon( 'card', 'a-float' ); // phpcs:ignore ?></span>
        <div><h1>Connect Flutterwave</h1><p class="muted" style="margin-top:8px">Customer payments go straight into your own Flutterwave account. Find your keys at dashboard.flutterwave.com → Settings → API Keys. You can skip this and add them later. Until then, payments are simulated so you can test.</p></div>
        <label class="field">Public key<input class="input mono" name="flw_public" value="<?php echo esc_attr( $val( 'flw_public' ) ); ?>" placeholder="FLWPUBK-…" autocomplete="off"></label>
        <label class="field">Secret key<input class="input mono" type="password" name="flw_secret" value="<?php echo esc_attr( $val( 'flw_secret' ) ); ?>" placeholder="FLWSECK-…" autocomplete="off"></label>
        <label class="field">Webhook secret hash <em>optional</em><input class="input mono" name="flw_hash" value="<?php echo esc_attr( $val( 'flw_hash' ) ); ?>" autocomplete="off"></label>
      </div>

      <div class="wiz-pane">
        <span class="ico-tile blue" style="width:60px;height:60px;border-radius:18px"><?php echo zxc_icon( 'router' ); // phpcs:ignore ?></span>
        <div><h1>Your first location</h1><p class="muted" style="margin-top:8px">We’ll generate a setup script for the MikroTik router at this location. Paste it into the router terminal and your portal goes live.</p></div>
        <label class="field">Location name<input class="input" name="location" value="<?php echo esc_attr( $val( 'location' ) ); ?>" placeholder="e.g. Lekki Phase 1 café" required maxlength="60"></label>
        <label class="check"><input type="checkbox" name="terms" value="1" required> I agree to the terms of service</label>
      </div>

      <div class="wiz-foot">
        <button type="button" class="btn btn-ghost" data-back>Back</button>
        <button type="button" class="btn btn-primary" data-next>Continue</button>
      </div>
    </section>

    <aside class="wiz-preview" aria-label="Live preview">
      <span class="eyebrow-tag" style="color:var(--muted)">Live preview</span>
      <div class="phone a-float">
        <div class="phone-screen" data-preview style="--accent:<?php echo esc_attr( $color ); ?>;--accent-ink:#fff">
          <div class="phone-bar"><span class="brand-mark" data-pv-initial style="width:28px;height:28px;border-radius:9px;font-size:13px;background:<?php echo esc_attr( $color ); ?>">Y</span>
            <span class="brand-text"><span class="brand-name" style="font-size:13.5px" data-pv-name>Your Hotspot</span><span class="brand-sub" style="font-size:9.5px">Powered by <?php echo esc_html( $pname ); ?></span></span></div>
          <div style="margin:12px;padding:16px;border-radius:20px;background:#0B1020;color:#fff;position:relative;overflow:hidden">
            <svg width="90" height="60" viewBox="0 0 90 60" style="position:absolute;right:6px;top:8px;color:var(--accent)"><path class="w1" d="M33 44a17 17 0 0 1 24 0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round"/><path class="w2" d="M25 34a29 29 0 0 1 40 0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".75"/><path class="w3" d="M17 24a41 41 0 0 1 56 0" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" opacity=".5"/></svg>
            <span class="live-tag" style="font-size:9.5px;padding:4px 8px"><span class="live-dot a-pulse" style="width:6px;height:6px"></span>Online</span>
            <div style="margin-top:10px;max-width:150px;font-size:17px;font-weight:800;line-height:1.2">Welcome to <span data-pv-name>Your Hotspot</span></div>
          </div>
          <div style="padding:0 12px;display:grid;gap:8px">
            <div class="mini-plan"><span>Quick Hour</span><span>₦100</span></div>
            <div class="mini-plan on"><span>Daily Pass</span><span>₦350</span></div>
            <div class="mini-plan"><span>Weekly Pass</span><span>₦1,500</span></div>
            <div style="margin-top:6px;height:44px;border-radius:14px;background:var(--accent);color:#fff;font-weight:700;font-size:13px;display:grid;place-items:center">Pay ₦350</div>
          </div>
        </div>
      </div>
    </aside>
  </form>
</main>
	<?php
	return zxc_page( 'Create your hotspot · ' . $pname, ob_get_clean(), array( 'scripts' => array( 'signup.js' ), 'robots' => 'noindex' ) );
}
