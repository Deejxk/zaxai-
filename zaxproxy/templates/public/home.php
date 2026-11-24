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
<section class="zp-phero">
	<div class="zp-phero__glow" aria-hidden="true"></div>
	<div class="zp-wrap zp-phero__in">
		<div class="zp-phero__text">
			<span class="zp-ptag zp-anim"><i></i>IPv4 · IPv6 · ISP · Mobile</span>
			<h1 class="zp-anim" style="--d:1">Private proxies, <em>ready in minutes.</em></h1>
			<p class="zp-anim" style="--d:2">Rent fast, dedicated IP addresses from locations around the world. Pay in <?php echo esc_html( $cur ); ?> by bank transfer, get your proxies delivered straight to your dashboard, and manage everything from your phone or computer.</p>
			<div class="zp-row zp-anim" style="--d:3">
				<a class="zp-btn zp-btn--primary zp-btn--lg" href="<?php echo esc_url( $cta ); ?>"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Get started</a>
				<a class="zp-btn zp-btn--ghost zp-btn--lg" href="<?php echo esc_url( zp_route_url( 'pricing' ) ); ?>">See pricing</a>
			</div>
			<ul class="zp-phero__ticks zp-anim" style="--d:4">
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>HTTP(S) &amp; SOCKS5</li>
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>Automatic delivery</li>
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>30, 60 and 90 day plans</li>
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>Installable app</li>
			</ul>
		</div>
		<div class="zp-phero__art zp-anim" style="--d:2"><div class="zp-phero__orb"><?php echo zp_illustration( 'network' ); // phpcs:ignore ?></div></div>
	</div>
</section>

<section class="zp-pstrip">
	<div class="zp-wrap">
		<div class="zp-reveal"><strong><?php echo count( $types ); ?></strong><span>Proxy networks: <?php echo esc_html( implode( ', ', wp_list_pluck( $types, 'label' ) ) ); ?></span></div>
		<div class="zp-reveal" style="--d:1"><strong><?php echo $places ? (int) $places . '+' : 'Global'; ?></strong><span><?php echo $places ? 'Countries to choose from' : 'Locations around the world'; ?></span></div>
		<div class="zp-reveal" style="--d:2"><strong>30 · 60 · 90</strong><span>Day plans, extend any time</span></div>
		<div class="zp-reveal" style="--d:3"><strong>24/7</strong><span>Automatic ordering and delivery</span></div>
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
					<span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
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
		<div class="zp-grid3">
			<?php foreach ( $uses as $n => $u ) : ?>
				<div class="zp-card zp-pfeature zp-reveal" style="--d:<?php echo (int) ( $n % 3 ) + 1; ?>"><span class="zp-type__ico"><?php echo zp_icon( $u[0], 22 ); // phpcs:ignore ?></span><strong><?php echo esc_html( $u[1] ); ?></strong><p><?php echo esc_html( $u[2] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Why <?php echo esc_html( $brand ); ?></span><h2>Built to stay out of your way</h2><p>Everything you need to buy, use and manage proxies, without the complicated parts.</p></div>
		<div class="zp-grid3">
			<?php foreach ( $features as $n => $f ) : ?>
				<div class="zp-card zp-pfeature zp-reveal" style="--d:<?php echo (int) ( $n % 3 ) + 1; ?>"><span class="zp-type__ico"><?php echo zp_icon( $f[0], 22 ); // phpcs:ignore ?></span><strong><?php echo esc_html( $f[1] ); ?></strong><p><?php echo esc_html( $f[2] ); ?></p></div>
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

<section class="zp-psec zp-psec--alt">
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

<section class="zp-psec">
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

<section class="zp-psec" style="padding-top:0">
	<div class="zp-wrap">
		<div class="zp-pcta zp-reveal">
			<div><h2>Ready when you are.</h2><p>Create a free account and buy your first proxy today. No subscription, no setup fee.</p></div>
			<a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( $cta ); ?>">Create account <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
