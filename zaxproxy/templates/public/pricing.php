<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$cur    = zp_get( 'currency_code' );
$types  = zp_enabled_types();
$prices = zp_public_prices();
$cta    = is_user_logged_in() ? 'buy' : 'register';
$perks  = array(
	'ipv4'   => array( 'Dedicated datacenter IP', 'HTTP(S) and SOCKS5 ports', 'Login or IP whitelist access', 'Extend any time' ),
	'ipv6'   => array( 'Sold in batches of 10 or more', 'Choose HTTP(S) or SOCKS5', 'Lowest cost per IP', 'Renew the whole batch' ),
	'isp'    => array( 'Static residential IP', 'Real provider networks', 'HTTP(S) and SOCKS5 ports', 'Extend any time' ),
	'mobile' => array( '4G / LTE carrier IP', 'Choose operator and rotation', 'Rotate on demand by link', 'HTTP(S) and SOCKS5 ports' ),
);
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-anim"><span class="zp-eyebrow">Pricing</span><h1>Pay only for what you rent</h1><p>No subscriptions and no setup fees. Prices are in <?php echo esc_html( $cur ); ?> and depend on the proxy type, country, period and quantity you choose.</p></div>
		<div class="zp-pplans">
			<?php
			$i = 0;
			foreach ( $types as $slug => $t ) :
				$i++;
				?>
				<div class="zp-card zp-pplan zp-anim" style="--d:<?php echo (int) $i; ?>">
					<span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $t['label'] ); ?></h3>
					<p><?php echo esc_html( $t['desc'] ); ?></p>
					<div class="zp-pplan__price">
						<?php if ( isset( $prices[ $slug ] ) ) : ?>
							<span>from</span><strong><?php echo esc_html( zp_money( $prices[ $slug ]['price'] ) ); ?></strong><span>per proxy / <?php echo (int) $prices[ $slug ]['days']; ?> days</span>
						<?php else : ?>
							<strong class="zp-pplan__live">Live pricing</strong><span>shown at checkout</span>
						<?php endif; ?>
					</div>
					<ul>
						<?php foreach ( $perks[ $slug ] as $perk ) : ?><li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?><?php echo esc_html( $perk ); ?></li><?php endforeach; ?>
						<?php if ( isset( $prices[ $slug ] ) ) : ?><li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?><?php echo (int) $prices[ $slug ]['countries']; ?> locations available</li><?php endif; ?>
					</ul>
					<a class="zp-btn zp-btn--primary zp-btn--block" href="<?php echo esc_url( zp_url( $cta, 'buy' === $cta ? array( 'type' => $slug ) : array() ) ); ?>">Buy <?php echo esc_html( $t['label'] ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="zp-muted zp-center" style="margin-top:22px">"From" prices are per proxy for a standard location. Your exact price is calculated live before you pay.</p>
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">How pricing works</span><h2>Four things decide your price</h2></div>
		<div class="zp-psteps zp-psteps--4">
			<div class="zp-pstep zp-reveal" style="--d:1"><b><?php echo zp_icon( 'layers', 20 ); // phpcs:ignore ?></b><strong>Proxy type</strong><p>IPv6 costs the least per IP. IPv4 is the everyday choice. ISP and mobile cost more because the addresses come from real providers and carriers.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:2"><b><?php echo zp_icon( 'globe', 20 ); // phpcs:ignore ?></b><strong>Country</strong><p>Some locations cost more than others. You see the price for each country as soon as you select it.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:3"><b><?php echo zp_icon( 'clock', 20 ); // phpcs:ignore ?></b><strong>Period</strong><p>Rent for 30, 60 or 90 days. You can extend before the proxy expires to keep the same IP.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:4"><b><?php echo zp_icon( 'plus', 20 ); // phpcs:ignore ?></b><strong>Quantity</strong><p>Buy one proxy or hundreds in a single order. The total updates as you change the number.</p></div>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-grid2">
			<div class="zp-card zp-pbig zp-reveal">
				<h2>Included with every proxy</h2>
				<ul class="zp-plist">
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>A private IP used only by you for the whole period</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Automatic delivery to your dashboard</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Username and password, or IP whitelist</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Copy and export in several formats</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Renewals from your dashboard</span></li>
					<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Ticket support</span></li>
				</ul>
			</div>
			<div class="zp-card zp-pbig zp-reveal" style="--d:1">
				<h2>How you pay</h2>
				<ul class="zp-plist">
					<li><?php echo zp_icon( 'wallet', 18 ); // phpcs:ignore ?><span><strong>Wallet:</strong> add funds once, then pay for orders and renewals from your balance.</span></li>
					<li><?php echo zp_icon( 'card', 18 ); // phpcs:ignore ?><span><strong>Bank transfer in <?php echo esc_html( $cur ); ?>:</strong> pay into our account and send your receipt on Telegram.</span></li>
					<li><?php echo zp_icon( 'refresh', 18 ); // phpcs:ignore ?><span><strong>Automatic refunds:</strong> if an order cannot be delivered, the full amount returns to your wallet.</span></li>
					<li><?php echo zp_icon( 'receipt', 18 ); // phpcs:ignore ?><span><strong>Full history:</strong> every top-up, purchase and refund is listed in your wallet.</span></li>
				</ul>
				<a class="zp-btn zp-btn--soft" href="<?php echo esc_url( zp_route_url( 'refund-policy' ) ); ?>">Read our refund policy</a>
			</div>
		</div>
	</div>
</section>

<section class="zp-psec" style="padding-top:0">
	<div class="zp-wrap">
		<div class="zp-preseller zp-reveal">
			<div><span class="zp-eyebrow">Buying to resell?</span><h2>Get the reseller price</h2><p>Resellers buy every proxy at a lower rate and sell under their own brand. Unlock a reseller account for <?php echo esc_html( zp_money( zp_get( 'reseller_fee', 45000 ) ) ); ?>.</p></div>
			<div class="zp-preseller__side"><a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( is_user_logged_in() ? zp_url( 'reseller' ) : zp_url( 'register' ) ); ?>">Become a reseller <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a></div>
		</div>
	</div>
</section>
