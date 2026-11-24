<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** Branded landing page of a reseller store: /s/{slug}/ */
$store  = zp_reseller_ctx();
$brand  = $store->brand;
$logged = is_user_logged_in();
$mine   = $logged && ( ZP_Resellers::is_customer_of( get_current_user_id(), $store->user_id ) || get_current_user_id() === (int) $store->user_id );
$cta    = $mine ? zp_route_url( ZP_USER_BASE, 'buy' ) : zp_url( 'register' );
$types  = zp_enabled_types();
$base   = zp_public_prices();
?>
<!doctype html>
<html lang="en">
<head>
<?php ZP_Router::head( 'Private proxies', true, 'Buy private IPv4, IPv6, ISP and mobile proxies from ' . $brand . '.' ); ?>
<link rel="stylesheet" href="<?php echo esc_url( zp_asset( 'css/public.css' ) ); ?>">
</head>
<body class="zp-body zp-public">
<?php ZP_Router::splash(); ?>
<header class="zp-phead">
	<div class="zp-wrap zp-phead__in">
		<a class="zp-brand" href="<?php echo esc_url( ZP_Resellers::store_url( $store ) ); ?>"><?php echo zp_logo( 32 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
		<div class="zp-phead__actions">
			<button class="zp-iconbtn zp-theme" id="zp-theme" type="button" title="Toggle dark / light mode" aria-label="Toggle dark / light mode">
				<span class="zp-theme__sun"><?php echo zp_icon( 'sun' ); // phpcs:ignore ?></span><span class="zp-theme__moon"><?php echo zp_icon( 'moon' ); // phpcs:ignore ?></span>
			</button>
			<?php if ( $mine ) : ?>
				<a class="zp-btn zp-btn--primary zp-btn--sm" style="display:inline-flex" href="<?php echo esc_url( zp_route_url( ZP_USER_BASE, 'dashboard' ) ); ?>">Dashboard</a>
			<?php else : ?>
				<a class="zp-btn zp-btn--ghost zp-btn--sm" href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a>
				<a class="zp-btn zp-btn--primary zp-btn--sm zp-phead__desk" href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Get started</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main>
	<section class="zp-phero">
		<div class="zp-phero__glow" aria-hidden="true"></div>
		<div class="zp-wrap zp-phero__in">
			<div class="zp-phero__text">
				<span class="zp-ptag zp-anim"><i></i>IPv4 · IPv6 · ISP · Mobile</span>
				<?php if ( $store->headline ) : ?>
					<h1 class="zp-anim" style="--d:1"><em><?php echo esc_html( $store->headline ); ?></em></h1>
				<?php else : ?>
					<h1 class="zp-anim" style="--d:1"><?php echo esc_html( $brand ); ?>: <em>private proxies, ready in minutes.</em></h1>
				<?php endif; ?>
				<p class="zp-anim" style="--d:2"><?php echo $store->tagline ? nl2br( esc_html( $store->tagline ) ) : 'Fast, dedicated IPs from locations around the world. Create an account, fund your wallet and get your proxies delivered to your dashboard.'; ?></p>
				<div class="zp-row zp-anim" style="--d:3">
					<a class="zp-btn zp-btn--primary zp-btn--lg" href="<?php echo esc_url( $cta ); ?>"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?><?php echo $mine ? 'Buy proxies' : 'Create account'; ?></a>
					<?php if ( ! $mine ) : ?><a class="zp-btn zp-btn--ghost zp-btn--lg" href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a><?php endif; ?>
				</div>
				<ul class="zp-phero__ticks zp-anim" style="--d:4">
					<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>HTTP(S) &amp; SOCKS5</li>
					<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>Automatic delivery</li>
					<li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?>30, 60 and 90 day plans</li>
				</ul>
			</div>
			<div class="zp-phero__art zp-anim" style="--d:2"><div class="zp-phero__orb"><?php echo zp_illustration( 'network' ); // phpcs:ignore ?></div></div>
		</div>
	</section>

	<section class="zp-psec zp-psec--alt">
		<div class="zp-wrap">
			<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">Proxy types</span><h2>Pick the network you need</h2></div>
			<div class="zp-types">
				<?php
				$i = 0;
				foreach ( $types as $slug => $t ) :
					$i++;
					?>
					<a class="zp-type zp-reveal" style="--d:<?php echo (int) $i; ?>" href="<?php echo esc_url( $cta ); ?>">
						<span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
						<strong><?php echo esc_html( $t['label'] ); ?> proxies</strong>
						<span><?php echo esc_html( $t['desc'] ); ?></span>
						<?php if ( isset( $base[ $slug ] ) ) : ?><b class="zp-type__price">from <?php echo esc_html( zp_money( ZP_Resellers::price( $base[ $slug ]['wholesale'], $store ) ) ); ?><small> / <?php echo (int) $base[ $slug ]['days']; ?> days</small></b><?php endif; ?>
						<em><?php echo zp_icon( 'arrow', 16 ); // phpcs:ignore ?></em>
					</a>
				<?php endforeach; ?>
			</div>
		</div>
	</section>

	<section class="zp-psec">
		<div class="zp-wrap">
			<div class="zp-phead2 zp-reveal"><span class="zp-eyebrow">How it works</span><h2>Three steps to your first proxy</h2></div>
			<div class="zp-psteps">
				<div class="zp-pstep zp-reveal" style="--d:1"><b>1</b><strong>Create your account</strong><p>Sign up with your email in under a minute.</p></div>
				<div class="zp-pstep zp-reveal" style="--d:2"><b>2</b><strong>Fund your wallet</strong><p>Pay by bank transfer and send your receipt.</p></div>
				<div class="zp-pstep zp-reveal" style="--d:3"><b>3</b><strong>Choose and connect</strong><p>Pick a type, country and period, then copy your credentials.</p></div>
			</div>
		</div>
	</section>
</main>

<footer class="zp-pfoot">
	<div class="zp-wrap zp-pfoot__base" style="margin-top:0;border-top:0;padding-top:0">
		<span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $brand ); ?>. All rights reserved.</span>
		<span><a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'privacy' ) ); ?>">Privacy Policy</a> · <a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'refund' ) ); ?>">Refund Policy</a> · <a href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a> · <a href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Create account</a></span>
	</div>
</footer>
<?php ZP_Router::foot(); ?>
</body>
</html>
