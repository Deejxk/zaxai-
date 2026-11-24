<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$brand = zp_get( 'brand' );
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-pabout">
			<div class="zp-anim">
				<span class="zp-eyebrow">About us</span>
				<h1>Reliable proxies without the runaround</h1>
				<p><?php echo esc_html( $brand ); ?> makes it simple to rent private proxies. We built it for marketers, developers, researchers and businesses who need clean, dependable IP addresses and do not want to wrestle with complicated tools or foreign payment methods to get them.</p>
				<p>You fund a wallet in <?php echo esc_html( zp_get( 'currency_code' ) ); ?>, choose the network and location you need, and your proxies arrive in your dashboard. From there you can copy credentials, export lists, extend rentals and get help from our support team.</p>
				<p>We care about three things: honest pricing, fast delivery and responsible use. Our network is for legitimate work only, and we enforce our <a href="<?php echo esc_url( zp_route_url( 'acceptable-use' ) ); ?>">Acceptable Use Policy</a> to keep it clean for everyone.</p>
				<div class="zp-row" style="margin-top:8px"><a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Create account</a><a class="zp-btn zp-btn--ghost" href="<?php echo esc_url( zp_route_url( 'contact' ) ); ?>">Talk to us</a></div>
			</div>
			<div class="zp-pabout__art zp-anim" style="--d:2"><div class="zp-phero__orb"><?php echo zp_illustration( 'globe' ); // phpcs:ignore ?></div></div>
		</div>
		<div class="zp-grid3" style="margin-top:36px">
			<div class="zp-card zp-pfeature zp-reveal" style="--d:1"><span class="zp-type__ico"><?php echo zp_icon( 'wallet', 22 ); // phpcs:ignore ?></span><strong>Honest pricing</strong><p>The price you see before paying is the price you pay. No hidden fees.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:2"><span class="zp-type__ico"><?php echo zp_icon( 'zap', 22 ); // phpcs:ignore ?></span><strong>Fast delivery</strong><p>Orders are processed automatically, day and night.</p></div>
			<div class="zp-card zp-pfeature zp-reveal" style="--d:3"><span class="zp-type__ico"><?php echo zp_icon( 'ticket', 22 ); // phpcs:ignore ?></span><strong>Real support</strong><p>Open a ticket and a person on our team will help you.</p></div>
		</div>
	</div>
</section>
