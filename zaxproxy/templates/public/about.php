<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$brand  = zp_get( 'brand' );
$cur    = zp_get( 'currency_code' );
$types  = zp_enabled_types();
$prices = zp_public_prices();
$places = $prices ? max( wp_list_pluck( $prices, 'countries' ) ) : 0;
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-pabout">
			<div class="zp-anim">
				<span class="zp-eyebrow">About us</span>
				<h1>Reliable proxies without the runaround</h1>
				<p><?php echo esc_html( $brand ); ?> is a proxy service built for people and businesses who need clean, dependable IP addresses and want a simple way to get them. We give you private IPv4, IPv6, ISP and mobile proxies from locations around the world, with prices in <?php echo esc_html( $cur ); ?> and payment by local bank transfer.</p>
				<p>We started <?php echo esc_html( $brand ); ?> because buying good proxies was harder than it should be. Most services price in foreign currency, ask for cards that many people do not have, and leave you waiting for support. We wanted something you could open on your phone, pay for the way you already pay for things, and trust to simply work.</p>
				<div class="zp-row" style="margin-top:8px"><a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Create account</a><a class="zp-btn zp-btn--ghost" href="<?php echo esc_url( zp_route_url( 'contact' ) ); ?>">Talk to us</a></div>
			</div>
			<div class="zp-pabout__art zp-anim" style="--d:2"><div class="zp-phero__orb"><?php echo zp_illustration( 'globe' ); // phpcs:ignore ?></div></div>
		</div>
	</div>
</section>

<section class="zp-pstrip">
	<div class="zp-wrap">
		<div class="zp-reveal"><strong><?php echo count( $types ); ?></strong><span>Proxy networks in one account</span></div>
		<div class="zp-reveal" style="--d:1"><strong><?php echo $places ? (int) $places . '+' : 'Global'; ?></strong><span><?php echo $places ? 'Countries available' : 'Locations worldwide'; ?></span></div>
		<div class="zp-reveal" style="--d:2"><strong><?php echo esc_html( $cur ); ?></strong><span>Local pricing and bank transfer</span></div>
		<div class="zp-reveal" style="--d:3"><strong>24/7</strong><span>Automatic ordering and delivery</span></div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-grid2">
			<div class="zp-card zp-pbig zp-reveal"><span class="zp-type__ico"><?php echo zp_icon( 'zap', 22 ); // phpcs:ignore ?></span><h2>Our mission</h2><p>To make professional-grade proxies easy to buy and easy to use for everyone, from a single freelancer managing a few accounts to an agency running thousands of connections. Good tools should not be reserved for people with foreign cards and technical teams.</p></div>
			<div class="zp-card zp-pbig zp-reveal" style="--d:1"><span class="zp-type__ico"><?php echo zp_icon( 'eye', 22 ); // phpcs:ignore ?></span><h2>What we believe</h2><p>The internet works best when it is open and when people can protect their privacy. A proxy is a simple, legitimate tool for both. We also believe that tool has to be used responsibly, which is why we enforce clear rules about what our network may be used for.</p></div>
		</div>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">What we offer</span><h2>Four networks, one simple account</h2><p>Each network suits a different kind of work. You can use any of them from the same wallet.</p></div>
		<div class="zp-grid2">
			<?php
			$long = array(
				'ipv4'   => 'Dedicated datacenter IPv4 addresses. The all-round choice: fast, stable and accepted everywhere. Ideal for social media accounts, e-commerce and day-to-day automation.',
				'ipv6'   => 'IPv6 addresses sold in batches at the lowest price per IP. Ideal when you need large numbers of addresses for websites that support IPv6.',
				'isp'    => 'Static addresses that belong to real internet providers, so websites see an ordinary home connection. Ideal for accounts that need extra trust.',
				'mobile' => 'Addresses from 4G and LTE mobile carriers, with rotation on a timer or on demand. Ideal for apps and platforms that trust mobile users most.',
			);
			$i    = 0;
			foreach ( $types as $slug => $t ) :
				$i++;
				?>
				<div class="zp-card zp-pfeature zp-reveal" style="--d:<?php echo (int) ( $i % 2 ) + 1; ?>"><span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span><strong><?php echo esc_html( $t['label'] ); ?> proxies</strong><p><?php echo esc_html( $long[ $slug ] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Our values</span><h2>How we run <?php echo esc_html( $brand ); ?></h2></div>
		<div class="zp-grid3">
			<div class="zp-card zp-pfeature zp-reveal" style="--d:1"><span class="zp-type__ico"><?php echo zp_icon( 'wallet', 22 ); // phpcs:ignore ?></span><strong>Honest pricing</strong><p>The price you see before you pay is the price you pay. No hidden fees, no subscriptions you did not ask for, and failed orders are refunded to your wallet automatically.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:2"><span class="zp-type__ico"><?php echo zp_icon( 'zap', 22 ); // phpcs:ignore ?></span><strong>Fast delivery</strong><p>Orders are processed automatically at any hour. Most proxies are in your dashboard within minutes of paying.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:3"><span class="zp-type__ico"><?php echo zp_icon( 'ticket', 22 ); // phpcs:ignore ?></span><strong>Real support</strong><p>When something is not working you can open a ticket and a person on our team will help you, not a script.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:1"><span class="zp-type__ico"><?php echo zp_icon( 'lock', 22 ); // phpcs:ignore ?></span><strong>Your privacy</strong><p>We collect only what we need to run your account, we never sell your data, and we do not see or store your card details.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:2"><span class="zp-type__ico"><?php echo zp_icon( 'shield', 22 ); // phpcs:ignore ?></span><strong>Responsible use</strong><p>Our network is for lawful work only. We act on abuse reports and remove anyone who breaks our Acceptable Use Policy.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:3"><span class="zp-type__ico"><?php echo zp_icon( 'phone', 22 ); // phpcs:ignore ?></span><strong>Simple by design</strong><p>A clean dashboard that works like an app on your phone, so managing proxies takes seconds, not a manual.</p></div>
		</div>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-preseller zp-reveal">
			<div>
				<span class="zp-eyebrow">For entrepreneurs</span>
				<h2>Build your own brand on our network</h2>
				<p>Through our reseller program you can open your own proxy store, with your own name, prices and customers, while we handle the infrastructure and delivery. It is a simple way to start a real online business.</p>
			</div>
			<div class="zp-preseller__side"><a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( is_user_logged_in() ? zp_url( 'reseller' ) : zp_url( 'register' ) ); ?>">Learn about reselling <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a></div>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-pcta zp-reveal">
			<div><h2>Have a question about us?</h2><p>We are happy to talk before you buy.</p></div>
			<a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( zp_route_url( 'contact' ) ); ?>">Contact us <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
