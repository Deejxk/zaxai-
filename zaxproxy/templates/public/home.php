<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$brand   = zp_get( 'brand' );
$cur     = zp_get( 'currency_code' );
$types   = zp_enabled_types();
$prices  = zp_public_prices();
$cta     = is_user_logged_in() ? zp_url( 'buy' ) : zp_url( 'register' );
$faqs    = array_slice( zp_faqs(), 0, 6, true );
$places  = $prices ? max( wp_list_pluck( $prices, 'countries' ) ) : 0;
$fee     = (float) zp_get( 'reseller_fee', 45000 );
$compare = array(
	'ipv4'   => array( 'Social media, e-commerce, everyday automation', 'Datacenter', 'Static', 'Very fast' ),
	'ipv6'   => array( 'Large volumes on IPv6-ready sites', 'Datacenter', 'Static', 'Very fast' ),
	'isp'    => array( 'Accounts that need a home-style IP', 'Real internet providers', 'Static', 'Fast' ),
	'mobile' => array( 'Apps and sites that trust mobile users most', '4G / LTE carriers', 'Rotating', 'Good' ),
);
$uses    = array(
	array( 'users', 'Social media management', 'Run and grow multiple accounts safely, each on its own dedicated IP.' ),
	array( 'cart', 'E-commerce and marketplaces', 'Manage seller accounts, check listings and compare prices by country.' ),
	array( 'search', 'Web scraping and research', 'Collect public data at scale without being slowed down or blocked.' ),
	array( 'chart', 'SEO and rank tracking', 'See real search results from the locations your customers are in.' ),
	array( 'eye', 'Ad verification', 'Check how your adverts and landing pages appear in other countries.' ),
	array( 'shield', 'Privacy and security', 'Keep your own IP address private while you work online.' ),
);
$features = array(
	array( 'zap', 'Delivered automatically', 'Pay from your wallet and your proxies appear in your dashboard, usually within minutes, day or night.' ),
	array( 'shield', 'Private and dedicated', 'Every IP is issued to one customer for the whole rental period. Nobody else uses it.' ),
	array( 'globe', 'Locations worldwide', 'Choose the exact country you need from a wide and growing list.' ),
	array( 'swap', 'HTTP(S) and SOCKS5', 'Both protocols on every IPv4, ISP and mobile proxy, on separate ports.' ),
	array( 'lock', 'Two ways to sign in', 'Use a username and password, or whitelist your own IP so no login is needed.' ),
	array( 'refresh', 'Extend any time', 'Keep the same IPs for as long as you like with one-tap renewals.' ),
	array( 'download', 'Export in any format', 'Copy or download lists as ip:port:user:pass, URL format and more.' ),
	array( 'wallet', 'Pay in ' . $cur, 'Fund your wallet by bank transfer. No dollar card or foreign payment needed.' ),
	array( 'ticket', 'Real support', 'Open a ticket from your dashboard and a person on our team replies.' ),
);
?>
<section class="zl-hero">
	<div class="zl-hero__bg" aria-hidden="true"><i></i><i></i><i></i></div>
	<div class="zp-wrap zl-hero__in">
		<div class="zl-hero__text">
			<span class="zl-pill zp-anim"><i></i><?php echo esc_html( implode( ' · ', wp_list_pluck( $types, 'label' ) ) ); ?></span>
			<h1 class="zp-anim" style="--d:1">Private proxies,<br><em>ready in minutes.</em></h1>
			<p class="zp-anim" style="--d:2"><?php echo esc_html( $brand ); ?> gives you fast, dedicated IP addresses from around the world. Pay in <?php echo esc_html( $cur ); ?>, get them delivered to your dashboard, and connect with one tap on Android and Windows.</p>
			<div class="zl-hero__cta zp-anim" style="--d:3">
				<a class="zp-btn zp-btn--primary zp-btn--lg" href="<?php echo esc_url( $cta ); ?>"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Get started</a>
				<a class="zp-btn zp-btn--ghost zp-btn--lg" href="<?php echo esc_url( zp_route_url( 'pricing' ) ); ?>">See pricing</a>
			</div>
			<ul class="zl-ticks zp-anim" style="--d:4">
				<li><?php echo zp_icon( 'check', 15 ); // phpcs:ignore ?>HTTP(S) &amp; SOCKS5</li>
				<li><?php echo zp_icon( 'check', 15 ); // phpcs:ignore ?>Automatic delivery</li>
				<li><?php echo zp_icon( 'check', 15 ); // phpcs:ignore ?>30, 60 and 90 days</li>
				<li><?php echo zp_icon( 'check', 15 ); // phpcs:ignore ?>One IP, one customer</li>
			</ul>
		</div>

		<div class="zl-stage zp-anim" style="--d:2" aria-hidden="true">
			<div class="zl-stage__ring zl-stage__ring--1"></div>
			<div class="zl-stage__ring zl-stage__ring--2"></div>
			<svg class="zl-globe" viewBox="0 0 240 240">
				<defs>
					<linearGradient id="zl-sea" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8b7bff"/><stop offset=".55" stop-color="#5b46e8"/><stop offset="1" stop-color="#2563eb"/></linearGradient>
					<clipPath id="zl-disc"><circle cx="120" cy="120" r="92"/></clipPath>
				</defs>
				<circle cx="120" cy="120" r="92" fill="url(#zl-sea)"/>
				<g clip-path="url(#zl-disc)">
					<g class="zl-globe__lands" fill="#fff" fill-opacity=".2">
						<path d="M-20 80c18-13 44-15 60-3s10 28-5 36-28 3-39 15-28 3-28-15 3-26 12-33Z"/><path d="M84 56c21-10 52-5 57 13s-13 23-29 28-15 26-33 21-18-26-13-39 8-18 18-23Z"/><path d="M182 100c16-8 39 0 42 16s-10 28-26 31-28-8-28-23 3-18 12-24Z"/><path d="M254 66c18-10 44-8 54 8s0 28-15 33-33-3-41-15-8-18 2-26Z"/><path d="M316 118c16-5 36 3 39 18s-13 23-28 23-23-10-21-23 3-15 10-18Z"/><path d="M396 80c18-13 44-15 60-3s10 28-5 36-28 3-39 15-28 3-28-15 3-26 12-33Z"/><path d="M500 56c21-10 52-5 57 13s-13 23-29 28-15 26-33 21-18-26-13-39 8-18 18-23Z"/>
					</g>
					<ellipse cx="120" cy="120" rx="36" ry="92" fill="none" stroke="#fff" stroke-opacity=".28"/><ellipse cx="120" cy="120" rx="70" ry="92" fill="none" stroke="#fff" stroke-opacity=".2"/>
					<path d="M28 120h184M38 80h164M38 160h164" stroke="#fff" stroke-opacity=".2"/>
					<ellipse cx="88" cy="78" rx="52" ry="34" fill="#fff" opacity=".12"/>
				</g>
				<g class="zl-ping zl-ping--1"><circle cx="78" cy="98" r="5" fill="#34d399"/><circle class="zl-ping__r" cx="78" cy="98" r="5" fill="none" stroke="#34d399" stroke-width="2"/></g>
				<g class="zl-ping zl-ping--2"><circle cx="160" cy="150" r="5" fill="#fbbf24"/><circle class="zl-ping__r" cx="160" cy="150" r="5" fill="none" stroke="#fbbf24" stroke-width="2"/></g>
				<g class="zl-ping zl-ping--3"><circle cx="150" cy="78" r="5" fill="#fff"/><circle class="zl-ping__r" cx="150" cy="78" r="5" fill="none" stroke="#fff" stroke-width="2"/></g>
				<path class="zl-arc" d="M78 98Q112 40 150 78" fill="none" stroke="#fff" stroke-width="1.600" stroke-dasharray="4 6" stroke-linecap="round"/>
				<path class="zl-arc" d="M150 78Q186 108 160 150" fill="none" stroke="#fff" stroke-width="1.600" stroke-dasharray="4 6" stroke-linecap="round"/>
			</svg>
			<div class="zl-badge"><?php echo zp_logo( 46 ); // phpcs:ignore ?></div>
			<div class="zl-float zl-float--a"><span class="zp-flag">🇺🇸</span><div><strong class="zp-mono">205.120.31.217</strong><small>IPv4 · Dallas, Texas</small></div><b><i></i>Connected</b></div>
			<div class="zl-float zl-float--b"><span class="zl-float__ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 22s7-6.500 7-12a7 7 0 1 0-14 0c0 5.500 7 12 7 12Z"/><circle cx="12" cy="10" r="2.600"/></svg></span><div><strong>GPS matched</strong><small>Same city as your proxy</small></div></div>
			<div class="zl-float zl-float--c"><span class="zl-float__ico zl-float__ico--zap"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?></span><div><strong>Delivered</strong><small>in minutes, day or night</small></div></div>
		</div>
	</div>

	<div class="zp-wrap">
		<div class="zl-nums">
			<div class="zp-reveal"><span class="zl-ico zl-ico--float"><?php echo zp_icon( 'layers', 20 ); // phpcs:ignore ?></span><div><strong><?php echo count( $types ); ?></strong><span>Proxy networks</span></div></div>
			<div class="zp-reveal" style="--d:1"><span class="zl-ico zl-ico--spin"><?php echo zp_icon( 'globe', 20 ); // phpcs:ignore ?></span><div><strong><?php echo $places ? (int) $places . '+' : 'Global'; ?></strong><span><?php echo $places ? 'Countries' : 'Locations'; ?></span></div></div>
			<div class="zp-reveal" style="--d:2"><span class="zl-ico zl-ico--tick"><?php echo zp_icon( 'clock', 20 ); // phpcs:ignore ?></span><div><strong>30 · 60 · 90</strong><span>Day plans</span></div></div>
			<div class="zp-reveal" style="--d:3"><span class="zl-ico zl-ico--flash"><?php echo zp_icon( 'zap', 20 ); // phpcs:ignore ?></span><div><strong>24/7</strong><span>Automatic delivery</span></div></div>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Proxy types</span><h2>The right IP for every job</h2><p>Four networks, one wallet. Choose the one that fits your project and switch whenever you need to.</p></div>
		<div class="zp-types">
			<?php
			$i = 0;
			foreach ( $types as $slug => $t ) :
				$i++;
				?>
				<a class="zp-type zp-reveal" style="--d:<?php echo (int) $i; ?>" href="<?php echo esc_url( is_user_logged_in() ? zp_url( 'buy', array( 'type' => $slug ) ) : zp_url( 'register' ) ); ?>">
					<span class="zp-type__ico zl-ico zl-ico--<?php echo esc_attr( array( 'float', 'spin', 'pulse', 'swing' )[ ( $i - 1 ) % 4 ] ); ?>"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
					<strong><?php echo esc_html( $t['label'] ); ?> proxies</strong>
					<span><?php echo esc_html( $t['desc'] ); ?></span>
					<?php if ( isset( $prices[ $slug ] ) ) : ?><b class="zp-type__price">from <?php echo esc_html( zp_money( $prices[ $slug ]['price'] ) ); ?><small> / <?php echo (int) $prices[ $slug ]['days']; ?> days</small></b><?php endif; ?>
					<em><?php echo zp_icon( 'arrow', 16 ); // phpcs:ignore ?></em>
				</a>
			<?php endforeach; ?>
		</div>

		<div class="zp-card zp-pcompare zp-reveal">
			<div class="zp-card__head"><h3>Which one should I choose?</h3></div>
			<div class="zp-tablewrap"><table class="zp-table">
				<thead><tr><th>Type</th><th>Best for</th><th>IP source</th><th>IP behaviour</th><th>Speed</th></tr></thead>
				<tbody>
				<?php foreach ( $types as $slug => $t ) : ?>
					<tr>
						<td data-label="Type"><strong><?php echo esc_html( $t['label'] ); ?></strong></td>
						<td data-label="Best for"><?php echo esc_html( $compare[ $slug ][0] ); ?></td>
						<td data-label="IP source"><?php echo esc_html( $compare[ $slug ][1] ); ?></td>
						<td data-label="IP behaviour"><?php echo esc_html( $compare[ $slug ][2] ); ?></td>
						<td data-label="Speed"><?php echo esc_html( $compare[ $slug ][3] ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		</div>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">What people use it for</span><h2>One tool, many jobs</h2><p>Whatever you do online, a clean dedicated IP makes it faster and more reliable.</p></div>
		<div class="zp-grid3 zl-grid">
			<?php foreach ( $uses as $n => $u ) : ?>
				<div class="zp-card zp-pfeature zp-reveal" style="--d:<?php echo (int) ( $n % 3 ) + 1; ?>"><span class="zp-type__ico zl-ico zl-ico--<?php echo esc_attr( array( 'float', 'pulse', 'swing', 'flash', 'tick', 'pulse' )[ $n % 6 ] ); ?>"><?php echo zp_icon( $u[0], 22 ); // phpcs:ignore ?></span><strong><?php echo esc_html( $u[1] ); ?></strong><p><?php echo esc_html( $u[2] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Why <?php echo esc_html( $brand ); ?></span><h2>Built to stay out of your way</h2><p>Everything you need to buy, use and manage proxies, without the complicated parts.</p></div>
		<div class="zp-grid3 zl-grid">
			<?php foreach ( $features as $n => $f ) : ?>
				<div class="zp-card zp-pfeature zp-reveal" style="--d:<?php echo (int) ( $n % 3 ) + 1; ?>"><span class="zp-type__ico zl-ico zl-ico--<?php echo esc_attr( array( 'flash', 'pulse', 'spin', 'swing', 'tick', 'spin', 'float', 'float', 'swing' )[ $n % 9 ] ); ?>"><?php echo zp_icon( $f[0], 22 ); // phpcs:ignore ?></span><strong><?php echo esc_html( $f[1] ); ?></strong><p><?php echo esc_html( $f[2] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">How it works</span><h2>From sign-up to connected in four steps</h2></div>
		<div class="zp-psteps zp-psteps--4">
			<div class="zp-pstep zp-reveal" style="--d:1"><b>1</b><strong>Create your account</strong><p>Sign up with your name and email. It takes less than a minute and it is free.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:2"><b>2</b><strong>Fund your wallet</strong><p>Transfer <?php echo esc_html( $cur ); ?> to our bank account and send your receipt on Telegram. We credit your wallet once it is confirmed.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:3"><b>3</b><strong>Choose your proxy</strong><p>Pick a type, a country, 30, 60 or 90 days and how many you need. You see the exact price before you pay.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:4"><b>4</b><strong>Copy and connect</strong><p>Your proxies appear in your dashboard with IP, port, username and password. Copy them into your browser or tool.</p></div>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zl-apps">
			<div class="zl-phone zp-reveal" aria-hidden="true">
				<div class="zl-phone__screen">
					<div class="zl-phone__top"><?php echo zp_logo( 20 ); // phpcs:ignore ?><span><?php echo esc_html( $brand ); ?> Connect</span></div>
					<div class="zl-power"><i></i><i></i><b><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.200" stroke-linecap="round"><path d="M12 3v9"/><path d="M6.600 6.800a7.600 7.600 0 1 0 10.800 0"/></svg></b></div>
					<strong class="zl-phone__state">Protected</strong>
					<small>Tap to disconnect</small>
					<div class="zl-phone__row"><span class="zp-flag">🇬🇧</span><div><strong>London, England</strong><small>United Kingdom · IPv4</small></div></div>
					<div class="zl-phone__row zl-phone__row--gps"><span class="zl-float__ico"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round"><path d="M12 22s7-6.500 7-12a7 7 0 1 0-14 0c0 5.500 7 12 7 12Z"/><circle cx="12" cy="10" r="2.600"/></svg></span><div><strong>GPS set to London</strong><small>Matches your proxy</small></div></div>
				</div>
			</div>
			<div class="zp-reveal" style="--d:1">
				<span class="zp-eyebrow"><?php echo esc_html( $brand ); ?> Connect</span>
				<h2>Connect with one tap. No settings to type.</h2>
				<p>Install the <?php echo esc_html( $brand ); ?> Connect app on Android or Windows, sign in, and every proxy you bought is already there. Tap the button and you are connected.</p>
				<div class="zl-applist">
					<div><span class="zl-ico zl-ico--pulse"><?php echo zp_icon( 'phone', 20 ); // phpcs:ignore ?></span><div><strong>Android and Windows apps</strong><span>Your proxies appear by themselves. Switch location with one tap.</span></div></div>
					<div><span class="zl-ico zl-ico--float"><svg class="zp-ico" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.800" stroke-linejoin="round"><path d="M12 22s7-6.500 7-12a7 7 0 1 0-14 0c0 5.500 7 12 7 12Z"/><circle cx="12" cy="10" r="2.600"/></svg></span><div><strong>GPS map built in</strong><span>Put your GPS in the same city as your proxy, or anywhere in the world.</span></div></div>
					<div><span class="zl-ico zl-ico--swing"><?php echo zp_icon( 'shield', 20 ); // phpcs:ignore ?></span><div><strong>iPhone too</strong><span>A step-by-step guide gets your iPhone or iPad connected in two minutes.</span></div></div>
				</div>
				<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( $cta ); ?>">Get the app <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
			</div>
		</div>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-psplit">
			<div class="zp-reveal">
				<span class="zp-eyebrow">Your dashboard</span>
				<h2>Everything in one place, on any device</h2>
				<p>Your dashboard shows every proxy you own, when it expires and how much is in your wallet. It works on your phone like an app: install it to your home screen and manage your proxies anywhere.</p>
				<ul class="zp-plist">
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span><strong>See all your proxies</strong> with country, ports, login details and time left.</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span><strong>Copy or export</strong> one proxy or your whole list in the format your tool needs.</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span><strong>Extend in one tap</strong> before a proxy expires, so you keep the same IP.</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span><strong>Track every naira</strong> with a full wallet and order history.</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span><strong>Dark and light mode</strong>, on phone, tablet and computer.</span></li>
				</ul>
				<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( $cta ); ?>">Open your dashboard</a>
			</div>
			<div class="zp-pmock zp-reveal" style="--d:2" aria-hidden="true">
				<div class="zp-pmock__bar"><i></i><i></i><i></i><span>My proxies</span></div>
				<div class="zp-pmock__row"><span class="zp-flag">🇺🇸</span><div><strong class="zp-mono">205.120.31.217</strong><small>IPv4 · United States</small></div><?php echo zp_badge( 'active' ); // phpcs:ignore ?></div>
				<div class="zp-pmock__row"><span class="zp-flag">🇬🇧</span><div><strong class="zp-mono">61.62.63.64</strong><small>Mobile · United Kingdom</small></div><?php echo zp_badge( 'active' ); // phpcs:ignore ?></div>
				<div class="zp-pmock__row"><span class="zp-flag">🇩🇪</span><div><strong class="zp-mono">185.81.112.210</strong><small>ISP · Germany</small></div><?php echo zp_badge( 'expiring' ); // phpcs:ignore ?></div>
				<div class="zp-pmock__foot"><span>Wallet</span><strong><?php echo esc_html( zp_money( 48500 ) ); ?></strong></div>
			</div>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-preseller zp-reveal">
			<div>
				<span class="zp-eyebrow">Reseller program</span>
				<h2>Start your own proxy business</h2>
				<p>Open a store under your own brand name, with your own link or your own <span class="zp-mono">.cloud</span> domain. You buy at the reseller price, set your own prices, and keep the difference. We deliver the proxies to your customers for you.</p>
				<ul class="zp-plist zp-plist--light">
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Your brand, your prices, your customers</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>A dashboard to track sales, customers and profit</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Customers pay you directly into your own bank account</span></li>
				</ul>
			</div>
			<div class="zp-preseller__side">
				<span>Unlock for</span><strong><?php echo esc_html( zp_money( $fee ) ); ?></strong>
				<a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( is_user_logged_in() ? zp_url( 'reseller' ) : zp_url( 'register' ) ); ?>">Become a reseller <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
			</div>
		</div>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap zp-pnarrow">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Questions</span><h2>Good to know</h2></div>
		<div class="zp-card zp-faq zp-reveal">
			<?php foreach ( $faqs as $q => $a ) : ?>
				<details><summary><?php echo esc_html( $q ); ?><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p><?php echo esc_html( $a ); ?></p></details>
			<?php endforeach; ?>
		</div>
		<p class="zp-center" style="margin-top:16px"><a href="<?php echo esc_url( zp_route_url( 'faq' ) ); ?>">Read all questions →</a></p>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-pcta zp-reveal">
			<div><h2>Ready when you are.</h2><p>Create a free account and buy your first proxy today. No subscription, no setup fee.</p></div>
			<a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( $cta ); ?>">Create account <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
