<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$types  = zp_enabled_types();
$prices = zp_public_prices();
$cta    = is_user_logged_in() ? zp_url( 'buy' ) : zp_url( 'register' );
$faqs   = array_slice( zp_faqs(), 0, 5, true );
?>
<section class="zp-phero">
	<div class="zp-phero__glow" aria-hidden="true"></div>
	<div class="zp-wrap zp-phero__in">
		<div class="zp-phero__text">
			<span class="zp-ptag zp-anim"><i></i>IPv4 · IPv6 · ISP · Mobile</span>
			<h1 class="zp-anim" style="--d:1">Private proxies, <em>ready in minutes.</em></h1>
			<p class="zp-anim" style="--d:2">Rent fast, dedicated IPs from locations around the world. Pay in <?php echo esc_html( zp_get( 'currency_code' ) ); ?>, get your proxies delivered to your dashboard and manage them from any device.</p>
			<div class="zp-row zp-anim" style="--d:3">
				<a class="zp-btn zp-btn--primary zp-btn--lg" href="<?php echo esc_url( $cta ); ?>"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Get started</a>
				<a class="zp-btn zp-btn--ghost zp-btn--lg" href="<?php echo esc_url( zp_route_url( 'pricing' ) ); ?>">See pricing</a>
			</div>
			<ul class="zp-phero__ticks zp-anim" style="--d:4">
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>HTTP(S) &amp; SOCKS5</li>
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>Automatic delivery</li>
				<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>Installable app</li>
			</ul>
		</div>
		<div class="zp-phero__art zp-anim" style="--d:2"><div class="zp-phero__orb"><?php echo zp_illustration( 'network' ); // phpcs:ignore ?></div></div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Proxy types</span><h2>The right IP for every job</h2><p>Four networks, one wallet. Switch between them whenever your project needs it.</p></div>
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
	</div>
</section>

<section class="zp-psec zp-psec--alt">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Why <?php echo esc_html( zp_get( 'brand' ) ); ?></span><h2>Built to stay out of your way</h2></div>
		<div class="zp-grid3">
			<?php
			$features = array(
				array( 'zap', 'Delivered automatically', 'Pay from your wallet and your proxies appear in your dashboard, usually within minutes.' ),
				array( 'shield', 'Private and dedicated', 'Every IP is issued to one customer for the whole rental period.' ),
				array( 'globe', 'Global locations', 'Pick the country you need from a wide and growing list of locations.' ),
				array( 'refresh', 'Extend any time', 'Keep the same IPs for as long as you need with one-tap renewals.' ),
				array( 'download', 'Export in any format', 'Copy or download lists as ip:port:user:pass, URL format and more.' ),
				array( 'phone', 'Works like an app', 'Install it on iPhone or Android and manage proxies from your home screen.' ),
			);
			foreach ( $features as $n => $f ) :
				?>
				<div class="zp-card zp-pfeature zp-reveal" style="--d:<?php echo (int) ( $n % 3 ) + 1; ?>"><span class="zp-type__ico"><?php echo zp_icon( $f[0], 22 ); // phpcs:ignore ?></span><strong><?php echo esc_html( $f[1] ); ?></strong><p><?php echo esc_html( $f[2] ); ?></p></div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="zp-psec">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">How it works</span><h2>Three steps to your first proxy</h2></div>
		<div class="zp-psteps">
			<div class="zp-pstep zp-reveal" style="--d:1"><b>1</b><strong>Create your account</strong><p>Sign up with your email. It takes less than a minute.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:2"><b>2</b><strong>Fund your wallet</strong><p>Top up in <?php echo esc_html( zp_get( 'currency_code' ) ); ?> using the payment method that suits you.</p></div>
			<div class="zp-pstep zp-reveal" style="--d:3"><b>3</b><strong>Choose and connect</strong><p>Pick a type, country and period. Copy the credentials and you are online.</p></div>
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
			<div><h2>Ready when you are.</h2><p>Create a free account and buy your first proxy today.</p></div>
			<a class="zp-btn zp-btn--light zp-btn--lg" href="<?php echo esc_url( $cta ); ?>">Create account <?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></a>
		</div>
	</div>
</section>
