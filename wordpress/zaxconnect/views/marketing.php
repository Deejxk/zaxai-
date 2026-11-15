<?php
/**
 * Public SaaS website: home, product pages, legal pages, auth.
 */
defined( 'ABSPATH' ) || exit;

function zxc_swatches() {
	return array( '#5B5BF6' => 'Indigo', '#8B5CF6' => 'Violet', '#06B6D4' => 'Cyan', '#10B981' => 'Emerald', '#F43F5E' => 'Rose', '#F97316' => 'Orange', '#0F172A' => 'Midnight' );
}

function zxc_site_nav( $active = '' ) {
	$links = array( 'features' => 'Features', 'pricing' => 'Pricing', 'docs' => 'Docs', 'about' => 'About', 'contact' => 'Contact' );
	ob_start();
	?>
<header class="nav" data-nav>
  <div class="wrap nav-in">
    <?php echo zxc_logo(); // phpcs:ignore ?>
    <nav class="nav-links" aria-label="Primary">
      <?php foreach ( $links as $k => $label ) : ?>
        <a href="<?php echo esc_url( home_url( '/' . $k ) ); ?>" class="<?php echo $active === $k ? 'on' : ''; ?>"><?php echo esc_html( $label ); ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-actions">
      <?php echo zxc_switch_buttons( 'site' ); // phpcs:ignore ?>
      <?php echo zxc_refresh_btn(); // phpcs:ignore ?>
      <?php echo zxc_theme_toggle(); // phpcs:ignore ?>
      <?php if ( ! is_user_logged_in() ) : ?>
        <a href="<?php echo esc_url( home_url( '/login' ) ); ?>" class="btn btn-ghost desk">Sign in</a>
        <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="btn btn-primary desk">Start free trial</a>
      <?php endif; ?>
      <button type="button" class="icon-btn mob" data-navmenu aria-expanded="false" aria-label="Open menu"><?php echo zxc_icon( 'menu' ); // phpcs:ignore ?></button>
    </div>
  </div>
  <div class="nav-sheet" data-navsheet>
    <nav aria-label="Mobile">
      <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo 'home' === $active ? 'on' : ''; ?>"><?php echo zxc_icon( 'home' ); // phpcs:ignore ?>Home</a>
      <?php foreach ( $links as $k => $label ) : ?>
        <a href="<?php echo esc_url( home_url( '/' . $k ) ); ?>" class="<?php echo $active === $k ? 'on' : ''; ?>"><?php echo zxc_icon( array( 'features' => 'sparkles', 'pricing' => 'tag', 'docs' => 'book', 'about' => 'users', 'contact' => 'message' )[ $k ] ?? 'arrow' ); // phpcs:ignore ?><?php echo esc_html( $label ); ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-sheet-cta">
      <?php if ( is_user_logged_in() ) : ?>
        <a class="btn btn-primary btn-block" href="<?php echo esc_url( zxc_home_panel( wp_get_current_user() ) ); ?>"><?php echo zxc_icon( 'dashboard' ); // phpcs:ignore ?> Open dashboard</a>
      <?php else : ?>
        <a class="btn btn-glass btn-block" href="<?php echo esc_url( home_url( '/login' ) ); ?>">Sign in</a>
        <a class="btn btn-primary btn-block" href="<?php echo esc_url( home_url( '/signup' ) ); ?>">Start free trial</a>
      <?php endif; ?>
    </div>
  </div>
</header>
	<?php
	return ob_get_clean();
}

function zxc_site_footer() {
	$name = zxc_setting( 'platform_name' );
	ob_start();
	?>
<footer class="footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <?php echo zxc_logo(); // phpcs:ignore ?>
        <p class="muted" style="margin-top:14px;max-width:320px;font-size:14.5px">The complete platform for running a professional Wi-Fi hotspot business in Nigeria.</p>
        <div class="row" style="margin-top:18px;gap:8px">
          <?php if ( zxc_setting( 'support_whatsapp' ) ) : ?><a class="icon-btn" href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', zxc_setting( 'support_whatsapp' ) ) ) ); ?>" aria-label="WhatsApp"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?></a><?php endif; ?>
          <?php if ( zxc_setting( 'support_email' ) ) : ?><a class="icon-btn" href="mailto:<?php echo esc_attr( zxc_setting( 'support_email' ) ); ?>" aria-label="Email"><?php echo zxc_icon( 'mail' ); // phpcs:ignore ?></a><?php endif; ?>
          <a class="icon-btn" href="<?php echo esc_url( home_url( '/contact' ) ); ?>" aria-label="Contact"><?php echo zxc_icon( 'message' ); // phpcs:ignore ?></a>
        </div>
      </div>
      <div><h4>Product</h4><nav><a href="<?php echo esc_url( home_url( '/features' ) ); ?>">Features</a><a href="<?php echo esc_url( home_url( '/pricing' ) ); ?>">Pricing</a><a href="<?php echo esc_url( home_url( '/guide' ) ); ?>">Hardware &amp; setup</a><a href="<?php echo esc_url( home_url( '/faq' ) ); ?>">FAQ</a></nav></div>
      <div><h4>Company</h4><nav><a href="<?php echo esc_url( home_url( '/about' ) ); ?>">About</a><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>">Contact</a><a href="<?php echo esc_url( home_url( '/login' ) ); ?>">Owner sign in</a><a href="<?php echo esc_url( home_url( '/signup' ) ); ?>">Start free trial</a></nav></div>
      <div><h4>Legal</h4><nav><a href="<?php echo esc_url( home_url( '/terms' ) ); ?>">Terms of service</a><a href="<?php echo esc_url( home_url( '/privacy' ) ); ?>">Privacy policy</a><a href="<?php echo esc_url( home_url( '/refunds' ) ); ?>">Refund policy</a></nav></div>
    </div>
    <div class="footer-base"><span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $name ); ?>. All rights reserved.</span><span>Payments secured by Flutterwave</span></div>
  </div>
</footer>
	<?php
	return ob_get_clean();
}

function zxc_site_dock( $active ) {
	$user = wp_get_current_user();
	$last = is_user_logged_in() ? array( zxc_home_panel( $user ), 'dashboard', zxc_is_platform_admin( $user ) && ! zxc_tenant_for_user( $user->ID ) ? 'Admin' : 'Dashboard' ) : array( home_url( '/login' ), 'login', 'Sign in' );
	return zxc_dock(
		array(
			array( home_url( '/' ), 'home', 'Home', 'home' === $active ),
			array( home_url( '/features' ), 'sparkles', 'Features', 'features' === $active ),
			array( home_url( '/signup' ), 'zap', 'Start', false, true ),
			array( home_url( '/pricing' ), 'tag', 'Pricing', 'pricing' === $active ),
			array( $last[0], $last[1], $last[2], 'login' === $active ),
		)
	);
}

function zxc_site_page( $title, $active, $content, $desc = '' ) {
	return zxc_page(
		$title,
		zxc_site_nav( $active ) . '<main>' . $content . '</main>' . zxc_site_footer(),
		array( 'description' => $desc )
	);
}

function zxc_cta_band() {
	ob_start();
	?>
<section class="wrap" style="padding-bottom:96px">
  <div class="cta">
    <div style="max-width:560px"><h2>Ready to start selling Wi-Fi?</h2><p>Create your brand, connect your router and take your first payment today.</p></div>
    <div class="row wrap-row"><a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="btn btn-white btn-lg">Start your free trial <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a><a href="<?php echo esc_url( home_url( '/contact' ) ); ?>" class="btn btn-lg" style="color:#fff;border-color:rgba(255,255,255,.35)">Talk to us</a></div>
  </div>
</section>
	<?php
	return ob_get_clean();
}

function zxc_page_hero( $eyebrow, $title, $text ) {
	return '<section class="page-hero wrap a-rise"><span class="eyebrow">' . esc_html( $eyebrow ) . '</span><h1>' . wp_kses( $title, array( 'span' => array( 'class' => array() ) ) ) . '</h1><p>' . esc_html( $text ) . '</p></section>';
}

function zxc_features_list() {
	return array(
		array( 'palette', '', 'Your own branded portal', 'Your logo, colours and business name on the page customers see the moment they join your Wi-Fi.' ),
		array( 'card', 'teal', 'Flutterwave payments', 'Card, bank transfer and USSD. Money settles straight into your own Flutterwave account.' ),
		array( 'ticket', 'amber', 'Instant vouchers', 'Customers pay and connect automatically. Print voucher cards to sell offline too.' ),
		array( 'zap', 'blue', 'Time, speed & data control', 'Hourly to monthly plans, speed caps, data bundles and device limits — enforced by your router.' ),
		array( 'chart', 'pink', 'Live business dashboard', 'Revenue, sales, customers, online users and reports, from your phone or laptop.' ),
		array( 'router', '', 'One-click router setup', 'We generate the MikroTik configuration. Paste it once and your hotspot is live.' ),
		array( 'users', 'teal', 'Customer insights', 'See your repeat customers, what they buy and when your hotspot is busiest.' ),
		array( 'building', 'amber', 'Multiple locations', 'Run several hotspots from one account. Vouchers work at every location.' ),
		array( 'shield', 'blue', 'Secure by design', 'Server-side payment checks, signed router sync and no card data on your site.' ),
	);
}

function zxc_view_landing() {
	$name  = zxc_setting( 'platform_name' );
	$trial = (int) zxc_setting( 'trial_days' );
	ob_start();
	?>
<section class="hero">
  <div class="wrap hero-grid">
    <div class="hero-copy a-rise">
      <span class="pill"><b>NEW</b>Flutterwave payouts straight to your bank</span>
      <h1>Launch your own <span class="grad-text">Wi-Fi hotspot</span> business in minutes.</h1>
      <p class="lead"><?php echo esc_html( $name ); ?> gives you a beautiful branded captive portal, instant voucher billing and a live business dashboard. Connect your router, set your prices and start earning today.</p>
      <div class="hero-cta">
        <a href="<?php echo esc_url( home_url( '/signup' ) ); ?>" class="btn btn-primary btn-lg">Create your hotspot brand <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a>
        <a href="<?php echo esc_url( home_url( '/guide' ) ); ?>" class="btn btn-glass btn-lg"><?php echo zxc_icon( 'play' ); // phpcs:ignore ?> See how it works</a>
      </div>
      <div class="ticks"><span><?php echo zxc_icon( 'checkCirc' ); // phpcs:ignore ?><?php echo (int) $trial; ?>-day free trial</span><span><?php echo zxc_icon( 'checkCirc' ); // phpcs:ignore ?>No card required</span><span><?php echo zxc_icon( 'checkCirc' ); // phpcs:ignore ?>Works with MikroTik</span></div>
    </div>
    <div class="hero-art a-rise2" aria-hidden="true">
      <div class="device a-float" style="--d-accent:#8B5CF6">
        <div class="device-screen">
          <div class="row" style="padding:16px 16px 10px;position:relative"><span class="brand-mark" style="width:28px;height:28px;font-size:12px;border-radius:9px">L</span><span style="font-family:var(--display);font-weight:600;font-size:13px">Lekki Hub WiFi</span></div>
          <div style="margin:4px 12px 12px;padding:16px;border-radius:20px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);position:relative">
            <span class="live-tag" style="height:22px;font-size:9.5px;padding:0 8px"><span class="live" style="width:6px;height:6px"></span>Online</span>
            <div style="margin-top:10px;font-family:var(--display);font-weight:700;font-size:17px;line-height:1.15">Fast Wi-Fi.<br>Pay small, browse big.</div>
            <svg width="70" height="36" viewBox="0 0 80 40" style="position:absolute;right:12px;top:14px"><path class="w1" d="M28 30a17 17 0 0 1 24 0" fill="none" stroke="#22D3EE" stroke-width="3" stroke-linecap="round"/><path class="w2" d="M20 21a29 29 0 0 1 40 0" fill="none" stroke="#A78BFA" stroke-width="3" stroke-linecap="round"/><path class="w3" d="M12 12a41 41 0 0 1 56 0" fill="none" stroke="#818CF8" stroke-width="3" stroke-linecap="round"/></svg>
          </div>
          <div style="padding:0 12px;display:grid;gap:8px;position:relative">
            <div class="mini-plan"><span>Quick Hour</span><span>₦100</span></div>
            <div class="mini-plan on"><span>Daily Pass</span><span>₦350</span></div>
            <div class="mini-plan"><span>Weekly Pass</span><span>₦1,500</span></div>
            <div style="margin-top:4px;height:42px;border-radius:14px;background:linear-gradient(135deg,#6366F1,#8B5CF6);display:grid;place-items:center;font-weight:600;font-size:13px">Pay ₦350</div>
          </div>
          <div style="margin-top:auto;margin:auto 10px 10px;height:46px;border-radius:16px;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);display:grid;grid-template-columns:repeat(4,1fr);place-items:center;color:rgba(255,255,255,.55)">
            <?php foreach ( array( 'home', 'wifi', 'ticket', 'help' ) as $k => $ic ) : ?><span style="<?php echo 0 === $k ? 'color:#A5B4FC' : ''; ?>"><?php echo zxc_icon( $ic ); // phpcs:ignore ?></span><?php endforeach; ?>
          </div>
        </div>
      </div>
      <div class="float-card a-float2" style="left:0;top:70px;width:214px">
        <div class="row between"><span class="fine" style="font-weight:600">Revenue this week</span><span class="badge badge-ok">+18%</span></div>
        <div style="margin-top:14px;height:60px;display:flex;align-items:flex-end;gap:6px">
          <?php foreach ( array( 38, 60, 46, 76, 64, 100 ) as $k => $h ) : ?><span class="a-grow" style="flex:1;height:<?php echo (int) $h; ?>%;border-radius:6px 6px 2px 2px;background:<?php echo 5 === $k ? 'linear-gradient(180deg,var(--accent-2),var(--accent))' : 'color-mix(in srgb,var(--accent) 22%,transparent)'; ?>;animation-delay:<?php echo esc_attr( 0.1 * $k ); ?>s"></span><?php endforeach; ?>
        </div>
      </div>
      <div class="float-card a-float3 row" style="right:0;top:310px;width:236px;padding:14px">
        <span class="ico ok a-draw" style="border-radius:50%"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span>
        <div><b style="font-size:13.5px;font-weight:600">Payment received</b><div class="fine">Daily Pass · voucher sent</div></div>
      </div>
      <div class="float-card a-float2" style="right:24px;top:36px;width:64px;height:64px;padding:0;display:grid;place-items:center;color:var(--accent-text)"><?php echo zxc_icon( 'router' ); // phpcs:ignore ?></div>
    </div>
  </div>
</section>

<section class="wrap"><div class="glass logos"><span class="faint" style="font-family:var(--font);font-weight:500;font-size:13.5px">Built for</span><?php foreach ( array( array( 'building', 'Hostels' ), array( 'heart', 'Cafés' ), array( 'home', 'Estates' ), array( 'book', 'Campuses' ), array( 'star', 'Event centres' ), array( 'tag', 'Shops' ) ) as $l ) : ?><span><?php echo zxc_icon( $l[0] ); // phpcs:ignore ?><?php echo esc_html( $l[1] ); ?></span><?php endforeach; ?></div></section>

<section class="section">
  <div class="wrap">
    <div class="sec-head"><span class="eyebrow"><?php echo zxc_icon( 'sparkles' ); // phpcs:ignore ?> Everything included</span><h2>Run a professional hotspot business from one place</h2><p>Every tool you need to sell Wi-Fi, get paid and grow, without hiring a developer.</p></div>
    <div class="bento mt">
      <?php foreach ( array_slice( zxc_features_list(), 0, 6 ) as $i => $f ) : ?>
        <article class="card feature hover-lift <?php echo 0 === $i || 5 === $i ? 'c4' : ''; ?>"><span class="ico lg <?php echo esc_attr( $f[1] ); ?>"><?php echo zxc_icon( $f[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $f[2] ); ?></h3><p><?php echo esc_html( $f[3] ); ?></p></article>
      <?php endforeach; ?>
    </div>
    <div class="center" style="margin-top:28px"><a class="btn btn-glass" href="<?php echo esc_url( home_url( '/features' ) ); ?>">Explore all features <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></a></div>
  </div>
</section>

<section class="section tight">
  <div class="wrap">
    <div class="sec-head"><span class="eyebrow"><?php echo zxc_icon( 'layers' ); // phpcs:ignore ?> How it works</span><h2>From sign-up to first sale in four steps</h2></div>
    <div class="grid g4 mt">
      <?php foreach ( array( array( '01', 'palette', 'Create your brand', 'Sign up and add your business name, logo and colours.' ), array( '02', 'tag', 'Set your prices', 'Hourly, daily, weekly, monthly or data bundles. You decide.' ), array( '03', 'router', 'Connect your router', 'Plug a MikroTik into your internet and paste our setup script.' ), array( '04', 'trend', 'Start earning', 'Customers buy on your portal. Money goes to your account.' ) ) as $s ) : ?>
        <div class="card step hover-lift"><div class="row between"><span class="n">STEP <?php echo esc_html( $s[0] ); ?></span><span class="ico sm"><?php echo zxc_icon( $s[1] ); // phpcs:ignore ?></span></div><h3><?php echo esc_html( $s[2] ); ?></h3><p><?php echo esc_html( $s[3] ); ?></p></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section">
  <div class="wrap">
    <div class="sec-head"><span class="eyebrow"><?php echo zxc_icon( 'tag' ); // phpcs:ignore ?> Pricing</span><h2>Simple plans that grow with you</h2><p>Start free for <?php echo (int) $trial; ?> days. No card required.</p></div>
    <?php echo zxc_tiers_grid(); // phpcs:ignore ?>
  </div>
</section>

<section class="section tight">
  <div class="wrap" style="max-width:820px">
    <div class="sec-head"><span class="eyebrow"><?php echo zxc_icon( 'help' ); // phpcs:ignore ?> FAQ</span><h2>Questions, answered</h2></div>
    <div class="stack mt" style="gap:10px"><?php echo zxc_faq_items( 5 ); // phpcs:ignore ?></div>
    <div class="center" style="margin-top:24px"><a class="btn btn-glass" href="<?php echo esc_url( home_url( '/faq' ) ); ?>">See all questions</a></div>
  </div>
</section>
	<?php
	$content = ob_get_clean() . zxc_cta_band();
	return zxc_site_page( $name . ' · Launch your Wi-Fi hotspot business', 'home', $content, 'Branded captive portal, instant voucher billing and a live dashboard for Wi-Fi hotspot businesses.' );
}

function zxc_tiers_grid() {
	ob_start();
	?>
<div class="grid mt">
  <?php foreach ( zxc_tiers() as $k => $t ) : ?>
    <div class="tier <?php echo 'business' === $k ? 'best' : ''; ?> hover-lift">
      <div class="row between"><b style="font-family:var(--display);font-size:18px;font-weight:650"><?php echo esc_html( $t['name'] ); ?></b><?php if ( 'business' === $k ) : ?><span class="badge badge-accent">Most popular</span><?php endif; ?></div>
      <p class="muted" style="margin-top:6px;font-size:14px"><?php echo esc_html( $t['desc'] ); ?></p>
      <div class="tier-price"><b><?php echo esc_html( zxc_money( $t['price'] ) ); ?></b><span class="muted">/ month</span></div>
      <a href="<?php echo esc_url( home_url( '/signup?plan=' . $k ) ); ?>" class="btn <?php echo 'business' === $k ? 'btn-primary' : 'btn-glass'; ?> btn-block" style="margin-top:22px">Start free trial</a>
      <ul><?php foreach ( $t['features'] as $f ) : ?><li><?php echo zxc_icon( 'check' ); // phpcs:ignore ?><span><?php echo esc_html( $f ); ?></span></li><?php endforeach; ?></ul>
    </div>
  <?php endforeach; ?>
</div>
	<?php
	return ob_get_clean();
}

function zxc_faq_list() {
	return array(
		array( 'What equipment do I need?', 'An internet source (MTN ODU, fibre or Starlink), a MikroTik router with Hotspot (most hAP models) and, for bigger spaces, Wi-Fi access points. Our setup guide lists everything.' ),
		array( 'Who receives the payments?', 'Customer payments go straight into your own Flutterwave account. We never hold your money.' ),
		array( 'How fast are customers connected after paying?', 'Usually within 10–15 seconds. Your router checks in every 10 seconds and the customer is logged in automatically.' ),
		array( 'Do I need a public IP or a VPS?', 'No. Your router connects out to us over HTTPS, so it works behind MTN and other mobile networks with no public IP.' ),
		array( 'Can I sell vouchers offline?', 'Yes. Generate printable voucher cards from your dashboard and sell them through attendants or shops.' ),
		array( 'Can I use my own logo and colours?', 'Yes. Every plan includes your logo, brand colour, headline and support contacts on your customer portal.' ),
		array( 'What happens after the free trial?', 'Choose a plan and pay monthly from your dashboard. Vouchers you already sold keep working while you decide.' ),
		array( 'Can I run more than one location?', 'Yes. The Business plan includes up to 5 router locations and Enterprise is unlimited. One voucher works at all of your locations.' ),
		array( 'How do I get help?', 'Use the Support page in your dashboard, WhatsApp us, or send a message from our contact page.' ),
	);
}

function zxc_faq_items( $limit = 0 ) {
	$items = zxc_faq_list();
	if ( $limit ) {
		$items = array_slice( $items, 0, $limit );
	}
	$h = '';
	foreach ( $items as $i => $q ) {
		$h .= '<details class="faq"' . ( 0 === $i ? ' open' : '' ) . '><summary>' . esc_html( $q[0] ) . zxc_icon( 'plus' ) . '</summary><p>' . esc_html( $q[1] ) . '</p></details>';
	}
	return $h;
}

function zxc_view_page_features() {
	ob_start();
	echo zxc_page_hero( 'Features', 'Everything you need to run a <span class="grad-text">profitable hotspot</span>', 'From the first tap on your Wi-Fi to money in your bank — all in one sleek platform.' ); // phpcs:ignore
	?>
<section class="section tight"><div class="wrap"><div class="grid">
  <?php foreach ( zxc_features_list() as $f ) : ?>
    <article class="card feature hover-lift"><span class="ico lg <?php echo esc_attr( $f[1] ); ?>"><?php echo zxc_icon( $f[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $f[2] ); ?></h3><p><?php echo esc_html( $f[3] ); ?></p></article>
  <?php endforeach; ?>
</div></div></section>
<section class="section tight"><div class="wrap"><div class="grid g2">
  <div class="card" style="padding:30px"><span class="eyebrow">For your customers</span><h2 style="margin-top:12px;font-size:28px">A portal they’ll love using</h2>
    <ul class="spec-list" style="list-style:none;padding:0;margin:22px 0 0">
      <?php foreach ( array( 'Opens automatically when they join your Wi-Fi', 'Clear plans with price, time, speed and data', 'Pay by card, bank transfer or USSD', 'Automatic login after payment — no typing', 'Live countdown of time and data left', 'Lost-code recovery and one-tap support' ) as $x ) : ?><li><span class="ico sm ok"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span><span><?php echo esc_html( $x ); ?></span></li><?php endforeach; ?>
    </ul></div>
  <div class="card" style="padding:30px"><span class="eyebrow">For you</span><h2 style="margin-top:12px;font-size:28px">A dashboard that runs the business</h2>
    <ul class="spec-list" style="list-style:none;padding:0;margin:22px 0 0">
      <?php foreach ( array( 'Revenue, sales and online users in real time', 'Customer list with spend and repeat buyers', 'Reports by plan, payment channel and busy hours', 'Printable voucher cards for offline sales', 'Multiple locations and routers', 'Support inbox for customer messages' ) as $x ) : ?><li><span class="ico sm"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span><span><?php echo esc_html( $x ); ?></span></li><?php endforeach; ?>
    </ul></div>
</div></div></section>
	<?php
	return zxc_site_page( 'Features · ' . zxc_setting( 'platform_name' ), 'features', ob_get_clean() . zxc_cta_band() );
}

function zxc_view_page_pricing() {
	$tiers = zxc_tiers();
	$rows  = array(
		array( 'Router locations', '1', 'Up to 5', 'Unlimited' ),
		array( 'Vouchers per month', '200', 'Unlimited', 'Unlimited' ),
		array( 'Branded captive portal', 'yes', 'yes', 'yes' ),
		array( 'Flutterwave payments', 'yes', 'yes', 'yes' ),
		array( 'Printable voucher cards', 'yes', 'yes', 'yes' ),
		array( 'Customers & reports', 'yes', 'yes', 'yes' ),
		array( 'Sales export (CSV)', '—', 'yes', 'yes' ),
		array( 'Support', 'Email', 'WhatsApp', 'Priority' ),
	);
	ob_start();
	echo zxc_page_hero( 'Pricing', 'Simple, <span class="grad-text">transparent</span> pricing', 'Start free for ' . (int) zxc_setting( 'trial_days' ) . ' days. Upgrade when your hotspot grows.' ); // phpcs:ignore
	?>
<section class="wrap" style="padding-bottom:40px"><?php echo zxc_tiers_grid(); // phpcs:ignore ?></section>
<section class="section tight"><div class="wrap">
  <div class="card flush"><div class="table-wrap"><table class="table">
    <thead><tr><th>Compare plans</th><?php foreach ( $tiers as $t ) : ?><th><?php echo esc_html( $t['name'] ); ?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach ( $rows as $r ) : ?><tr><td><b><?php echo esc_html( $r[0] ); ?></b></td><?php for ( $i = 1; $i <= 3; $i++ ) : ?><td><?php echo 'yes' === $r[ $i ] ? '<span class="ico sm ok" style="width:28px;height:28px">' . zxc_icon( 'check' ) . '</span>' : esc_html( $r[ $i ] ); // phpcs:ignore ?></td><?php endfor; ?></tr><?php endforeach; ?></tbody>
  </table></div></div>
</div></section>
<section class="section tight"><div class="wrap" style="max-width:820px"><div class="stack" style="gap:10px"><?php echo zxc_faq_items( 4 ); // phpcs:ignore ?></div></div></section>
	<?php
	return zxc_site_page( 'Pricing · ' . zxc_setting( 'platform_name' ), 'pricing', ob_get_clean() . zxc_cta_band() );
}

function zxc_view_page_guide() {
	ob_start();
	echo zxc_page_hero( 'Hardware & setup', 'Go live in <span class="grad-text">under an hour</span>', 'Everything you need to buy and the exact steps to connect your hotspot.' ); // phpcs:ignore
	?>
<section class="section tight"><div class="wrap">
  <div class="card" style="padding:28px">
    <div class="card-title">How your network connects</div>
    <div class="topology"><div class="node"><b>Internet</b><small>MTN ODU, fibre or Starlink</small></div><div class="ln"></div><div class="node hl"><b>MikroTik router</b><small>Hotspot + walled garden</small></div><div class="ln"></div><div class="node"><b>Access points</b><small>Your customers’ Wi-Fi</small></div></div>
    <p class="muted">The MikroTik shows your branded portal to new devices, checks in with <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?> every 10 seconds for new vouchers, and enforces time, speed and data limits. No public IP or VPS needed.</p>
  </div>
</div></section>
<section class="section tight"><div class="wrap">
  <div class="sec-head"><span class="eyebrow">Shopping list</span><h2>What you need</h2></div>
  <div class="grid g4 mt">
    <?php foreach ( array( array( 'globe', 'Internet source', 'MTN 5G ODU, fibre or Starlink with an Ethernet port.' ), array( 'router', 'MikroTik router', 'Any model with Hotspot, e.g. hAP ax lite, hAP ac² or hEX.' ), array( 'wifi', 'Access points', 'Optional, for bigger spaces. Ceiling or outdoor APs.' ), array( 'cable', 'Cables & power', 'Cat6 cables, a PoE injector or switch, and backup power.' ) ) as $h ) : ?>
      <div class="card feature hover-lift"><span class="ico lg"><?php echo zxc_icon( $h[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $h[1] ); ?></h3><p><?php echo esc_html( $h[2] ); ?></p></div>
    <?php endforeach; ?>
  </div>
</div></section>
<section class="section tight"><div class="wrap" style="max-width:860px">
  <div class="sec-head"><span class="eyebrow">Step by step</span><h2>Setup in six steps</h2></div>
  <ol class="timeline mt">
    <li><b>Create your account</b><p>Sign up, add your brand and set your prices. It takes about five minutes.</p></li>
    <li><b>Wire your equipment</b><p>Connect the internet source to the MikroTik’s ether1 port and your access points to the other ports.</p></li>
    <li><b>Give the router internet</b><p>In WinBox, open IP → DHCP Client and add one on ether1.</p></li>
    <li><b>Run the Hotspot setup</b><p>IP → Hotspot → Hotspot Setup on your LAN bridge. Use the DNS name shown in your dashboard.</p></li>
    <li><b>Paste your setup script</b><p>Copy the script from Dashboard → Locations, open New Terminal in WinBox and paste it.</p></li>
    <li><b>Upload two small files</b><p>Download login.html and alogin.html from your dashboard and drag them into the router’s hotspot folder. You’re live!</p></li>
  </ol>
  <div class="center" style="margin-top:22px"><a class="btn btn-primary" href="<?php echo esc_url( home_url( '/docs' ) ); ?>"><?php echo zxc_icon( 'book' ); // phpcs:ignore ?> Read the full documentation</a></div>
</div></section>
	<?php
	return zxc_site_page( 'Hardware & setup guide · ' . zxc_setting( 'platform_name' ), 'guide', ob_get_clean() . zxc_cta_band() );
}

function zxc_view_page_about() {
	$name = zxc_setting( 'platform_name' );
	ob_start();
	echo zxc_page_hero( 'About us', 'Making Wi-Fi a <span class="grad-text">business anyone can run</span>', $name . ' helps entrepreneurs turn an internet connection into a reliable, professional source of income.' ); // phpcs:ignore
	?>
<section class="section tight"><div class="wrap"><div class="grid g2">
  <div class="card" style="padding:32px"><span class="ico lg"><?php echo zxc_icon( 'target' ); // phpcs:ignore ?></span><h2 style="margin-top:20px;font-size:26px">Our mission</h2><p class="muted" style="margin-top:12px;font-size:15.5px">Millions of people need affordable internet by the hour or by the day. We give hotspot owners the same polished tools as big ISPs — branded portals, instant payments and real-time insight — so they can serve their communities and grow.</p></div>
  <div class="card" style="padding:32px"><span class="ico lg teal"><?php echo zxc_icon( 'heart' ); // phpcs:ignore ?></span><h2 style="margin-top:20px;font-size:26px">What we believe</h2><p class="muted" style="margin-top:12px;font-size:15.5px">Your money should go straight to you. Your customers should get online in seconds. And running a hotspot shouldn’t require a developer. Every part of <?php echo esc_html( $name ); ?> is built around those three ideas.</p></div>
</div>
<div class="grid g4 mt">
  <?php foreach ( array( array( 'zap', 'Fast', 'Customers online within seconds of paying.' ), array( 'shield', 'Secure', 'Payments verified server-side, every time.' ), array( 'heart', 'Local', 'Built for Nigerian networks and payments.' ), array( 'sparkles', 'Simple', 'Set up in minutes, run from your phone.' ) ) as $v ) : ?>
    <div class="card feature hover-lift"><span class="ico"><?php echo zxc_icon( $v[0] ); // phpcs:ignore ?></span><h3><?php echo esc_html( $v[1] ); ?></h3><p><?php echo esc_html( $v[2] ); ?></p></div>
  <?php endforeach; ?>
</div></div></section>
	<?php
	return zxc_site_page( 'About · ' . $name, 'about', ob_get_clean() . zxc_cta_band() );
}

function zxc_view_contact( array $a ) {
	$v = $a['values'] ?? array();
	ob_start();
	echo zxc_page_hero( 'Contact', 'Let’s <span class="grad-text">talk</span>', 'Questions about pricing, hardware or setup? Send us a message and we’ll get back to you quickly.' ); // phpcs:ignore
	?>
<section class="section tight" style="padding-top:0"><div class="wrap"><div class="split">
  <form method="post" class="card wide stack" style="padding:28px">
    <?php echo zxc_nonce_field( 'zxc_contact' ); // phpcs:ignore ?>
    <?php if ( ! empty( $a['sent'] ) ) : ?>
      <div class="empty"><span class="ico lg ok a-pop"><?php echo zxc_icon( 'checkCirc' ); // phpcs:ignore ?></span><b>Message sent</b><span>Thank you! We’ll reply to your email soon.</span></div>
    <?php else : ?>
      <?php echo zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
      <div class="form-grid">
        <label class="field">Your name<input class="input" name="name" value="<?php echo esc_attr( $v['name'] ?? '' ); ?>" required autocomplete="name"></label>
        <label class="field">Email<input class="input" type="email" name="email" value="<?php echo esc_attr( $v['email'] ?? '' ); ?>" required autocomplete="email"></label>
        <label class="field">Phone <em>optional</em><input class="input" type="tel" name="phone" value="<?php echo esc_attr( $v['phone'] ?? '' ); ?>" autocomplete="tel"></label>
        <label class="field">Topic<select class="input" name="topic"><?php foreach ( array( 'General enquiry', 'Pricing & plans', 'Hardware & setup', 'Partnership', 'Support' ) as $o ) : ?><option <?php selected( $v['topic'] ?? '', $o ); ?>><?php echo esc_html( $o ); ?></option><?php endforeach; ?></select></label>
        <label class="field span-2">Message<textarea class="input" name="message" required minlength="10"><?php echo esc_textarea( $v['message'] ?? '' ); ?></textarea></label>
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="sr" aria-hidden="true">
      </div>
      <div class="actions"><button class="btn btn-primary btn-lg" type="submit"><?php echo zxc_icon( 'message' ); // phpcs:ignore ?> Send message</button></div>
    <?php endif; ?>
  </form>
  <div class="side-col">
    <div class="card"><div class="contact-list">
      <?php if ( zxc_setting( 'support_email' ) ) : ?><a href="mailto:<?php echo esc_attr( zxc_setting( 'support_email' ) ); ?>"><span class="ico"><?php echo zxc_icon( 'mail' ); // phpcs:ignore ?></span><span><b style="display:block;font-weight:600">Email</b><span class="fine"><?php echo esc_html( zxc_setting( 'support_email' ) ); ?></span></span></a><?php endif; ?>
      <?php if ( zxc_setting( 'support_whatsapp' ) ) : ?><a href="<?php echo esc_url( 'https://wa.me/' . preg_replace( '/^0/', '234', preg_replace( '/\D/', '', zxc_setting( 'support_whatsapp' ) ) ) ); ?>"><span class="ico teal"><?php echo zxc_icon( 'whatsapp' ); // phpcs:ignore ?></span><span><b style="display:block;font-weight:600">WhatsApp</b><span class="fine"><?php echo esc_html( zxc_setting( 'support_whatsapp' ) ); ?></span></span></a><?php endif; ?>
      <?php if ( zxc_setting( 'support_phone' ) ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', zxc_setting( 'support_phone' ) ) ); ?>"><span class="ico amber"><?php echo zxc_icon( 'call' ); // phpcs:ignore ?></span><span><b style="display:block;font-weight:600">Phone</b><span class="fine"><?php echo esc_html( zxc_setting( 'support_phone' ) ); ?></span></span></a><?php endif; ?>
      <div><span class="ico blue"><?php echo zxc_icon( 'pin' ); // phpcs:ignore ?></span><span><b style="display:block;font-weight:600">Office</b><span class="fine"><?php echo esc_html( zxc_setting( 'address' ) ); ?></span></span></div>
    </div></div>
    <div class="card"><div class="card-title">Already a customer?</div><p class="card-sub" style="margin-bottom:14px">Sign in and use the Support page in your dashboard for faster help.</p><a class="btn btn-glass btn-block" href="<?php echo esc_url( home_url( '/login' ) ); ?>">Sign in</a></div>
  </div>
</div></div></section>
	<?php
	return zxc_site_page( 'Contact · ' . zxc_setting( 'platform_name' ), 'contact', ob_get_clean() );
}

function zxc_view_page_faq() {
	ob_start();
	echo zxc_page_hero( 'Help centre', 'Frequently asked <span class="grad-text">questions</span>', 'Everything you need to know about running your hotspot with us.' ); // phpcs:ignore
	echo '<section class="section tight" style="padding-top:0"><div class="wrap" style="max-width:820px"><div class="stack" style="gap:10px">' . zxc_faq_items() . '</div><div class="card center" style="margin-top:28px;padding:28px"><div class="card-title">Still have questions?</div><p class="card-sub" style="margin:6px 0 16px">Our team is happy to help.</p><a class="btn btn-primary" href="' . esc_url( home_url( '/contact' ) ) . '">Contact us</a></div></div></section>'; // phpcs:ignore
	return zxc_site_page( 'FAQ · ' . zxc_setting( 'platform_name' ), 'faq', ob_get_clean() );
}

function zxc_legal_page( $title, $slug, array $sections ) {
	$name = zxc_setting( 'platform_name' );
	ob_start();
	echo zxc_page_hero( 'Legal', esc_html( $title ), 'Last updated ' . wp_date( 'j F Y', strtotime( '2026-11-15' ) ) . '.' ); // phpcs:ignore
	echo '<section class="section tight" style="padding-top:0"><div class="wrap" style="max-width:860px"><article class="card prose">';
	foreach ( $sections as $h => $paras ) {
		echo '<h2>' . esc_html( $h ) . '</h2>';
		foreach ( (array) $paras as $p ) {
			echo '<p>' . esc_html( str_replace( '{name}', $name, $p ) ) . '</p>';
		}
	}
	echo '<h2>Contact</h2><p>Questions about this policy? <a class="grad-text" href="' . esc_url( home_url( '/contact' ) ) . '">Contact us</a>.</p></article></div></section>';
	return zxc_site_page( $title . ' · ' . $name, $slug, ob_get_clean() );
}

function zxc_view_page_terms() {
	return zxc_legal_page(
		'Terms of service',
		'terms',
		array(
			'1. About these terms'     => array( 'These terms govern your use of {name}, a platform that lets Wi-Fi hotspot owners ("owners") sell internet access to their customers. By creating an account you agree to these terms.' ),
			'2. Your account'          => array( 'You must give accurate information, keep your password safe and are responsible for activity on your account. You must be at least 18 years old and legally able to run a business in your country.' ),
			'3. Subscriptions'         => array( 'Paid plans are billed monthly or for the period you choose, in advance. Free trials end automatically. If a subscription lapses, online sales pause but vouchers already sold continue to work.' ),
			'4. Payments to owners'    => array( 'Customer payments are processed by Flutterwave and paid into the owner\'s own Flutterwave account. {name} does not hold owners\' funds and is not a party to the sale between an owner and their customers.' ),
			'5. Owner responsibilities' => array( 'Owners are responsible for their internet service, hardware, pricing, customer service, refunds to their customers and compliance with local laws, including any licences required to resell internet access.' ),
			'6. Acceptable use'        => array( 'You may not use {name} for unlawful activity, to defraud customers, to interfere with the service or to resell the platform without permission.' ),
			'7. Availability'          => array( 'We work hard to keep the service available but cannot guarantee uninterrupted operation. We may update features from time to time.' ),
			'8. Liability'             => array( 'To the extent permitted by law, {name} is not liable for indirect losses or for losses caused by internet providers, hardware, payment processors or events outside our control.' ),
			'9. Ending your account'   => array( 'You can stop using {name} at any time. We may suspend accounts that break these terms or fail to pay.' ),
			'10. Changes'              => array( 'We may update these terms. We will notify owners of significant changes by email or in the dashboard.' ),
		)
	);
}

function zxc_view_page_privacy() {
	return zxc_legal_page(
		'Privacy policy',
		'privacy',
		array(
			'What we collect'   => array( 'Owner account details (name, email, business details), customer phone numbers and optional emails entered at checkout, voucher usage (time, data, device MAC address) and router check-in data.' ),
			'How we use it'     => array( 'To run the service: issue and activate vouchers, show owners their sales and customers, process subscriptions, prevent fraud and provide support.' ),
			'Payments'          => array( 'Card and bank details are entered on Flutterwave\'s secure checkout and are never stored by {name}.' ),
			'Sharing'           => array( 'Customer purchase details are visible to the owner of the hotspot where they bought. We share data with payment processors and hosting providers only as needed to run the service. We do not sell personal data.' ),
			'Retention'         => array( 'We keep records for as long as the owner\'s account is active and as required for accounting and legal purposes.' ),
			'Your rights'       => array( 'You can ask us to access, correct or delete your personal data, subject to legal record-keeping requirements, in line with the Nigeria Data Protection Act 2023.' ),
			'Security'          => array( 'We use encryption in transit, hashed passwords, server-side payment verification and access controls to protect data.' ),
		)
	);
}

function zxc_view_page_refunds() {
	return zxc_legal_page(
		'Refund policy',
		'refunds',
		array(
			'Subscriptions'           => array( 'If you are charged for a subscription in error, contact us within 7 days and we will refund the charge. Partially used subscription periods are not normally refunded.' ),
			'Wi-Fi plans bought by customers' => array( 'Wi-Fi plans are sold by each hotspot owner. Customers should contact the owner using the Help page on the hotspot portal. Owners can issue refunds through their Flutterwave dashboard.' ),
			'Failed payments'         => array( 'If a customer is charged but does not receive a voucher, the voucher can be recovered from the portal using their phone number, or the owner can find it under Sales.' ),
		)
	);
}

/* -------------------------------------------------------------------------
 * Auth pages
 * ---------------------------------------------------------------------- */

function zxc_auth_brand( $inner, $kicker, $title, $text ) {
	$name = zxc_setting( 'platform_name' );
	$word = preg_match( '/^(.*?)(Connect)$/', $name, $m ) ? esc_html( $m[1] ) . '<b>' . esc_html( $m[2] ) . '</b>' : esc_html( $name );
	return '<aside class="ax-brand"><div class="ax-mesh" aria-hidden="true"><i></i><i></i><i></i></div>
  <a class="ax-logo" href="' . esc_url( home_url( '/' ) ) . '">' . zxc_logo_mark() . '<span>' . $word . '</span></a>
  <div class="ax-copy"><span class="ax-kicker"><span class="live"></span>' . esc_html( $kicker ) . '</span><h2>' . wp_kses( $title, array( 'span' => array( 'class' => array() ), 'br' => array() ) ) . '</h2><p>' . esc_html( $text ) . '</p></div>
  ' . $inner . '
  <div class="ax-foot"><span>' . zxc_icon( 'shield' ) . ' Bank-grade security</span><span>' . zxc_icon( 'card' ) . ' Payments by Flutterwave</span></div>
</aside>';
}

function zxc_auth_mock() {
	ob_start();
	?>
<div class="ax-mock" aria-hidden="true">
  <div class="ax-mock-card">
    <div class="row between"><span class="ax-mk-l"><span class="live"></span>Today’s revenue</span><span class="ax-up"><?php echo zxc_icon( 'trend' ); // phpcs:ignore ?>+18%</span></div>
    <b class="ax-big">₦48,250</b>
    <svg class="ax-spark" viewBox="0 0 300 70" preserveAspectRatio="none"><defs><linearGradient id="axsp" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity=".45"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs><path d="M0 58 C30 52 45 40 70 44 S115 22 140 30 S190 12 215 20 S260 6 300 4 V70 H0Z" fill="url(#axsp)"/><path class="ax-line" d="M0 58 C30 52 45 40 70 44 S115 22 140 30 S190 12 215 20 S260 6 300 4" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"/></svg>
    <div class="ax-stats"><div><b>23</b><small>Online now</small></div><div><b>61</b><small>Sales</small></div><div><b>2/2</b><small>Routers up</small></div></div>
  </div>
  <div class="ax-toasts">
    <div class="ax-toast"><span class="ax-t-ic"><?php echo zxc_icon( 'check' ); // phpcs:ignore ?></span><span><b>Daily Pass · ₦350</b><small>Paid by transfer · just now</small></span></div>
    <div class="ax-toast"><span class="ax-t-ic"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?></span><span><b>New device online</b><small>Lekki Phase 1 · router A</small></span></div>
    <div class="ax-toast"><span class="ax-t-ic"><?php echo zxc_icon( 'wallet' ); // phpcs:ignore ?></span><span><b>Wallet top-up · ₦2,000</b><small>Card · 2 min ago</small></span></div>
  </div>
</div>
	<?php
	return ob_get_clean();
}

function zxc_auth_top( $extra = '' ) {
	return '<div class="ax-top"><a class="ax-back" href="' . esc_url( home_url( '/' ) ) . '">' . zxc_icon( 'back' ) . '<span>Back to website</span></a><div class="row" style="gap:8px">' . $extra . zxc_theme_toggle() . '</div></div>';
}

function zxc_auth_legal() {
	return '<div class="ax-legal">© ' . esc_html( gmdate( 'Y' ) . ' ' . zxc_setting( 'platform_name' ) ) . '<span>·</span><a href="' . esc_url( home_url( '/terms' ) ) . '">Terms</a><a href="' . esc_url( home_url( '/privacy' ) ) . '">Privacy</a><a href="' . esc_url( home_url( '/docs' ) ) . '">Help</a></div>';
}

function zxc_auth_shell( $title, $heading, $form, $art_title, $art_quote ) {
	ob_start();
	?>
<main class="ax">
  <?php echo zxc_auth_brand( zxc_auth_mock(), 'Hotspot billing platform', $art_title, $art_quote ); // phpcs:ignore ?>
  <section class="ax-main">
    <?php echo zxc_auth_top(); // phpcs:ignore ?>
    <div class="ax-body"><div class="ax-card a-rise"><?php echo $heading . $form; // phpcs:ignore ?></div></div>
    <?php echo zxc_auth_legal(); // phpcs:ignore ?>
  </section>
</main>
	<?php
	return zxc_page( $title, ob_get_clean(), array( 'robots' => 'noindex', 'aurora' => false, 'splash' => true ) );
}

function zxc_auth_input( $icon, $html ) {
	return '<span class="ax-inp">' . zxc_icon( $icon ) . $html . '</span>';
}

function zxc_view_login( array $a ) {
	ob_start();
	?>
<div class="ax-head">
  <span class="ax-badge"><?php echo zxc_logo_mark(); // phpcs:ignore ?></span>
  <h1>Welcome back</h1>
  <p>Sign in to manage your hotspot business.</p>
</div>
<form method="post" action="<?php echo esc_url( home_url( '/login' ) ); ?>" class="ax-form">
  <?php echo zxc_nonce_field( 'zxc_login' ) . zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
  <label class="field">Email address<?php echo zxc_auth_input( 'mail', '<input class="input" type="email" name="email" value="' . esc_attr( $a['email'] ?? '' ) . '" placeholder="you@business.com" autocomplete="email" required autofocus>' ); // phpcs:ignore ?></label>
  <label class="field"><span class="row between">Password <a class="ax-a" href="<?php echo esc_url( home_url( '/forgot' ) ); ?>">Forgot password?</a></span><?php echo zxc_auth_input( 'lock', '<input class="input" type="password" name="password" placeholder="Your password" autocomplete="current-password" required><button type="button" class="ax-eye" data-pw-toggle aria-label="Show password">' . zxc_icon( 'eye' ) . '</button>' ); // phpcs:ignore ?></label>
  <label class="check ax-remember"><input type="checkbox" name="remember" value="1" checked> Keep me signed in</label>
  <button class="btn btn-primary btn-lg btn-block ax-submit" type="submit">Sign in <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></button>
  <div class="ax-or"><span>New to <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?>?</span></div>
  <a class="btn btn-glass btn-lg btn-block" href="<?php echo esc_url( home_url( '/signup' ) ); ?>"><?php echo zxc_icon( 'sparkles' ); // phpcs:ignore ?> Create your hotspot, free for <?php echo (int) zxc_setting( 'trial_days' ); ?> days</a>
</form>
	<?php
	return zxc_auth_shell( 'Sign in · ' . zxc_setting( 'platform_name' ), '', ob_get_clean(), 'Your hotspot business, <span>beautifully managed.</span>', 'Customers pay, connect and keep coming back, while you watch every sale happen live from your phone.' );
}

function zxc_view_forgot( array $a ) {
	ob_start();
	?>
<div class="ax-head">
  <span class="ax-badge soft"><?php echo zxc_icon( 'lock' ); // phpcs:ignore ?></span>
  <h1>Reset your password</h1>
  <?php if ( ! empty( $a['sent'] ) ) : ?><p>If an account exists for that email, a reset link is on its way. Check your inbox and spam folder.</p><?php else : ?><p>Enter your account email and we’ll send you a secure reset link.</p><?php endif; ?>
</div>
<?php if ( ! empty( $a['sent'] ) ) : ?>
  <a class="btn btn-primary btn-lg btn-block" href="<?php echo esc_url( home_url( '/login' ) ); ?>"><?php echo zxc_icon( 'back' ); // phpcs:ignore ?> Back to sign in</a>
<?php else : ?>
  <form method="post" class="ax-form">
    <?php echo zxc_nonce_field( 'zxc_forgot' ); // phpcs:ignore ?>
    <label class="field">Email address<?php echo zxc_auth_input( 'mail', '<input class="input" type="email" name="email" placeholder="you@business.com" required autofocus>' ); // phpcs:ignore ?></label>
    <button class="btn btn-primary btn-lg btn-block ax-submit" type="submit">Send reset link <?php echo zxc_icon( 'arrow' ); // phpcs:ignore ?></button>
    <a class="ax-a center" href="<?php echo esc_url( home_url( '/login' ) ); ?>">Back to sign in</a>
  </form>
<?php endif; ?>
	<?php
	return zxc_auth_shell( 'Reset password · ' . zxc_setting( 'platform_name' ), '', ob_get_clean(), 'Locked out? <span>We’ve got you.</span>', 'Reset your password in a minute and get straight back to running your hotspot.' );
}

function zxc_view_admin_login( array $a ) {
	$name = zxc_setting( 'platform_name' );
	ob_start();
	?>
<main class="auth-form" style="min-height:100vh">
  <div class="auth-card a-rise">
    <div class="center" style="margin-bottom:26px"><?php echo zxc_logo(); // phpcs:ignore ?></div>
    <div class="card" style="padding:30px">
      <div class="row"><span class="ico"><?php echo zxc_icon( 'shield' ); // phpcs:ignore ?></span><div><h1 style="font-size:22px">Admin console</h1><p class="fine">Restricted to <?php echo esc_html( $name ); ?> staff</p></div></div>
      <form method="post" class="stack" style="margin-top:24px">
        <?php echo zxc_nonce_field( 'zxc_admin_login' ) . zxc_alert( 'bad', $a['error'] ?? '' ); // phpcs:ignore ?>
        <label class="field">Email or username<input class="input" name="email" value="<?php echo esc_attr( $a['email'] ?? '' ); ?>" autocomplete="username" required autofocus></label>
        <label class="field">Password<input class="input" type="password" name="password" autocomplete="current-password" required></label>
        <button class="btn btn-primary btn-lg btn-block" type="submit"><?php echo zxc_icon( 'lock' ); // phpcs:ignore ?> Sign in to admin</button>
      </form>
    </div>
    <div class="row between" style="margin-top:18px;font-size:13.5px"><a class="muted" href="<?php echo esc_url( home_url( '/' ) ); ?>">← Back to website</a><?php echo zxc_theme_toggle(); // phpcs:ignore ?></div>
  </div>
</main>
	<?php
	return zxc_page( 'Admin sign in · ' . $name, ob_get_clean(), array( 'robots' => 'noindex' ) );
}

function zxc_view_signup( array $a ) {
	$v     = $a['values'] ?? array();
	$val   = function ( $k, $d = '' ) use ( $v ) {
		return isset( $v[ $k ] ) && '' !== $v[ $k ] ? $v[ $k ] : $d;
	};
	$color = zxc_is_hex( $val( 'brand_color' ) ) ? $val( 'brand_color' ) : '#5B5BF6';
	$steps = array( array( 'Your account', 'Name, email, password' ), array( 'Brand', 'Name, colour, logo' ), array( 'Plans & prices', 'What you sell' ), array( 'Payments', 'Connect Flutterwave' ), array( 'Router', 'Your first location' ) );
	$pname = zxc_setting( 'platform_name' );
	ob_start();
	?>
<form class="ax ax-signup" method="post" action="<?php echo esc_url( home_url( '/signup' ) ); ?>" enctype="multipart/form-data" data-wizard data-start="<?php echo (int) ( $a['start'] ?? 0 ); ?>" novalidate>
  <?php echo zxc_nonce_field( 'zxc_signup' ); // phpcs:ignore ?>
  <input type="hidden" name="tier" value="<?php echo esc_attr( $a['tier'] ?? 'business' ); ?>">
  <?php ob_start(); ?>
    <nav class="wiz-steps ax-steps" aria-label="Sign-up steps"><?php foreach ( $steps as $i => $s ) : ?><button type="button" class="wiz-step" data-step-btn><span class="dot"><?php echo (int) $i + 1; ?></span><span><b><?php echo esc_html( $s[0] ); ?></b><small><?php echo esc_html( $s[1] ); ?></small></span></button><?php endforeach; ?></nav>
    <div class="ax-trial"><?php echo zxc_icon( 'sparkles' ); // phpcs:ignore ?><span><b><?php echo (int) zxc_setting( 'trial_days' ); ?>-day free trial</b><small>No card needed · cancel any time</small></span></div>
  <?php echo zxc_auth_brand( ob_get_clean(), 'Create your hotspot', 'Launch your Wi-Fi business <span>in five minutes.</span>', 'Set up your brand, prices and payments. We’ll guide you through connecting your router next.' ); // phpcs:ignore ?>
  <section class="ax-main">
    <?php echo zxc_auth_top( '<span class="ax-stepno">Step <span data-step-no>1</span> of 5</span>' ); // phpcs:ignore ?>
    <div class="ax-progress"><span data-progress style="width:20%"></span></div>
    <div class="ax-body ax-wide">
    <section class="ax-card wiz-body">
      <?php echo zxc_alert( 'bad', $a['error'] ?? '', true ); // phpcs:ignore ?>
      <div class="wiz-pane">
        <span class="ico lg"><?php echo zxc_icon( 'user', 'a-float' ); // phpcs:ignore ?></span>
        <div><h1>Create your account</h1><p class="muted" style="margin-top:8px">You’ll use this to sign in and manage your hotspot business.</p></div>
        <label class="field">Full name<?php echo zxc_auth_input( 'user', '<input class="input" name="owner_name" value="' . esc_attr( $val( 'owner_name' ) ) . '" placeholder="e.g. Ada Obi" autocomplete="name" required maxlength="60">' ); // phpcs:ignore ?></label>
        <label class="field">Email address<?php echo zxc_auth_input( 'mail', '<input class="input" type="email" name="email" value="' . esc_attr( $val( 'email' ) ) . '" placeholder="you@business.com" autocomplete="email" required>' ); // phpcs:ignore ?></label>
        <label class="field">Password <em>at least 8 characters</em><?php echo zxc_auth_input( 'lock', '<input class="input" type="password" name="password" minlength="8" placeholder="Create a password" autocomplete="new-password" required><button type="button" class="ax-eye" data-pw-toggle aria-label="Show password">' . zxc_icon( 'eye' ) . '</button>' ); // phpcs:ignore ?></label>
        <p class="fine">Already have an account? <a style="color:var(--accent-text);font-weight:600" href="<?php echo esc_url( home_url( '/login' ) ); ?>">Sign in</a></p>
      </div>
      <div class="wiz-pane">
        <div><h1>Create your hotspot brand</h1><p class="muted" style="margin-top:8px">This is what customers see when they join your Wi-Fi.</p></div>
        <label class="field">Business name<input class="input" name="business_name" value="<?php echo esc_attr( $val( 'business_name' ) ); ?>" placeholder="e.g. Lekki Hub WiFi" required maxlength="50"></label>
        <div class="field">Your portal address<div class="input-group"><span class="addon"><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) ); ?>/h/</span><span class="input" style="display:flex;align-items:center"><b data-slug>your-hotspot</b></span></div></div>
        <div class="field">Brand colour<div class="swatches" data-swatches="#brand_color">
          <?php foreach ( zxc_swatches() as $c => $n ) : ?><button type="button" class="swatch <?php echo strtolower( $c ) === strtolower( $color ) ? 'on' : ''; ?>" data-color="<?php echo esc_attr( $c ); ?>" style="background:<?php echo esc_attr( $c ); ?>;--sw:<?php echo esc_attr( $c ); ?>" aria-label="<?php echo esc_attr( $n ); ?>"></button><?php endforeach; ?>
          <input class="input mono" id="brand_color" name="brand_color" value="<?php echo esc_attr( $color ); ?>" pattern="#[0-9A-Fa-f]{6}" style="width:120px;height:42px" aria-label="Custom colour (hex)">
        </div></div>
        <div class="logo-drop">
          <span class="brand-mark" data-pv-initial style="width:56px;height:56px;border-radius:16px;font-size:24px;background:<?php echo esc_attr( $color ); ?>">Y</span>
          <div class="grow"><b style="font-size:14px;font-weight:600">Logo</b><div class="fine" id="logo-msg">Optional · PNG, JPG or WebP, under 500 KB</div></div>
          <label class="btn btn-glass btn-sm">Upload<input type="file" name="logo_file" accept="image/png,image/jpeg,image/webp" class="sr" data-logo-input="#logo_preview" data-logo-msg="#logo-msg"></label><input type="hidden" id="logo_preview">
        </div>
      </div>
      <div class="wiz-pane">
        <span class="ico lg teal"><?php echo zxc_icon( 'wifiAnim' ); // phpcs:ignore ?></span>
        <div><h1>Plans &amp; prices</h1><p class="muted" style="margin-top:8px">We’ll add six popular plans for you. Set your two main prices now and fine-tune later.</p></div>
        <div class="form-grid">
          <label class="field">Quick Hour price (₦)<input class="input" type="number" name="price_hour" min="0" value="<?php echo esc_attr( $val( 'price_hour', 100 ) ); ?>" required></label>
          <label class="field">Daily Pass price (₦)<input class="input" type="number" name="price_daily" min="0" value="<?php echo esc_attr( $val( 'price_daily', 350 ) ); ?>" required></label>
        </div>
        <div class="glass list"><?php foreach ( array( array( '3 Hours', '₦200' ), array( '2GB Bundle', '₦500' ), array( 'Weekly Pass', '₦1,500' ), array( 'Monthly Pass', '₦5,000' ) ) as $p ) : ?><div class="list-row"><span class="grow"><b><?php echo esc_html( $p[0] ); ?></b><small>Added automatically</small></span><b><?php echo esc_html( $p[1] ); ?></b></div><?php endforeach; ?></div>
      </div>
      <div class="wiz-pane">
        <span class="ico lg amber"><?php echo zxc_icon( 'card', 'a-float' ); // phpcs:ignore ?></span>
        <div><h1>Connect Flutterwave</h1><p class="muted" style="margin-top:8px">Customer payments go straight into your own Flutterwave account. Find your keys at dashboard.flutterwave.com → Settings → API Keys. You can skip this and add them later — until then, payments are simulated so you can test.</p></div>
        <label class="field">Public key<input class="input mono" name="flw_public" value="<?php echo esc_attr( $val( 'flw_public' ) ); ?>" placeholder="FLWPUBK-…" autocomplete="off"></label>
        <label class="field">Secret key<input class="input mono" type="password" name="flw_secret" value="<?php echo esc_attr( $val( 'flw_secret' ) ); ?>" placeholder="FLWSECK-…" autocomplete="off"></label>
        <label class="field">Webhook secret hash <em>optional</em><input class="input mono" name="flw_hash" value="<?php echo esc_attr( $val( 'flw_hash' ) ); ?>" autocomplete="off"></label>
      </div>
      <div class="wiz-pane">
        <span class="ico lg blue"><?php echo zxc_icon( 'router' ); // phpcs:ignore ?></span>
        <div><h1>Your first location</h1><p class="muted" style="margin-top:8px">We’ll generate a setup script for the MikroTik router at this location.</p></div>
        <label class="field">Location name<input class="input" name="location" value="<?php echo esc_attr( $val( 'location' ) ); ?>" placeholder="e.g. Lekki Phase 1 café" required maxlength="60"></label>
        <label class="check"><input type="checkbox" name="terms" value="1" required> I agree to the <a href="<?php echo esc_url( home_url( '/terms' ) ); ?>" style="color:var(--accent-text);font-weight:600" target="_blank" rel="noopener">terms of service</a></label>
      </div>
      <div class="wiz-foot"><button type="button" class="btn btn-ghost" data-back>Back</button><button type="button" class="btn btn-primary" data-next>Continue</button></div>
    </section>
    <aside class="wiz-preview" aria-label="Live preview">
      <span class="eyebrow">Live preview</span>
      <div class="device a-float" style="position:relative;left:auto;top:auto;margin:0;width:270px;height:540px">
        <div class="device-screen" data-preview style="--d-accent:<?php echo esc_attr( $color ); ?>">
          <div class="row" style="padding:16px 14px 10px;position:relative"><span class="brand-mark" data-pv-initial style="width:30px;height:30px;font-size:13px;border-radius:9px;background:<?php echo esc_attr( $color ); ?>">Y</span><span class="brand-text"><span class="brand-name" style="font-size:13.5px;color:#fff" data-pv-name>Your Hotspot</span><span class="brand-sub" style="font-size:9.5px;color:rgba(255,255,255,.55)">Powered by <?php echo esc_html( $pname ); ?></span></span></div>
          <div style="margin:4px 12px 12px;padding:16px;border-radius:20px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.08);position:relative">
            <span class="live-tag" style="height:22px;font-size:9.5px;padding:0 8px"><span class="live" style="width:6px;height:6px"></span>Online</span>
            <div style="margin-top:10px;max-width:160px;font-family:var(--display);font-size:17px;font-weight:700;line-height:1.15">Welcome to <span data-pv-name>Your Hotspot</span></div>
          </div>
          <div style="padding:0 12px;display:grid;gap:8px;position:relative">
            <div class="mini-plan"><span>Quick Hour</span><span>₦100</span></div>
            <div class="mini-plan on"><span>Daily Pass</span><span>₦350</span></div>
            <div class="mini-plan"><span>Weekly Pass</span><span>₦1,500</span></div>
            <div data-pv-btn style="margin-top:6px;height:44px;border-radius:14px;background:<?php echo esc_attr( $color ); ?>;color:#fff;font-weight:600;font-size:13px;display:grid;place-items:center">Pay ₦350</div>
          </div>
        </div>
      </div>
    </aside>
    </div>
    <?php echo zxc_auth_legal(); // phpcs:ignore ?>
  </section>
</form>
	<?php
	return zxc_page( 'Create your hotspot · ' . $pname, ob_get_clean(), array( 'scripts' => array( 'signup.js' ), 'robots' => 'noindex', 'aurora' => false, 'splash' => true ) );
}
