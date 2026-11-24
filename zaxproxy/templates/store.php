<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * A reseller's storefront: /s/{slug}/ or the store's own domain.
 * Five designs share this markup; the design is a class on <body> (see assets/css/store.css).
 */
$store   = zp_reseller_ctx();
$brand   = $store->brand;
$themes  = ZP_Resellers::themes();
$theme   = ZP_Resellers::theme( $store );
$logged  = is_user_logged_in();
$owner   = $logged && get_current_user_id() === (int) $store->user_id;
$mine    = $logged && ( $owner || ZP_Resellers::is_customer_of( get_current_user_id(), $store->user_id ) );
$preview = sanitize_key( zp_query( 'theme' ) );
if ( $owner && isset( $themes[ $preview ] ) ) {
	$theme = $preview; // the owner can try a design before saving it
} else {
	$preview = '';
}
$color   = ZP_Resellers::custom_color( $store );
$home    = ZP_Resellers::store_url( $store );
$login   = zp_url( 'login' );
$signup  = zp_url( 'register' );
$panel   = zp_route_url( ZP_USER_BASE, 'dashboard' );
$cta     = $mine ? zp_route_url( ZP_USER_BASE, 'buy' ) : $signup;
$cta_txt = $mine ? 'Buy proxies' : 'Create account';
$types   = zp_enabled_types();
$base    = zp_public_prices();
$cur     = zp_get( 'currency_code' );
$emoji   = array( 'ipv4' => '🖥', 'ipv6' => '🧬', 'isp' => '🏠', 'mobile' => '📱' );
$perks   = array(
	'ipv4'   => array( 'HTTP(S) and SOCKS5', 'Login or IP whitelist', 'One owner per IP' ),
	'ipv6'   => array( 'Batches of 10 or more', 'Lowest cost per IP', 'Choose your protocol' ),
	'isp'    => array( 'Real provider networks', 'High trust score', 'Extend any time' ),
	'mobile' => array( 'Choose your operator', 'Rotate on demand', 'HTTP(S) and SOCKS5' ),
);
$from    = array(); // store price per proxy, by type
$places  = 0;
foreach ( $base as $slug => $row ) {
	if ( isset( $row['wholesale'] ) ) {
		$from[ $slug ] = array( 'price' => zp_money( ZP_Resellers::price( $row['wholesale'], $store ) ), 'days' => (int) $row['days'] );
		$places        = max( $places, (int) $row['countries'] );
	}
}
$first    = $from ? (string) key( $from ) : '';
$headline = trim( (string) $store->headline );
$lead     = trim( (string) $store->tagline );
$std_lead = array(
	'aurora'  => 'Fast, dedicated IPs from locations around the world. Create an account, fund your wallet and your proxies land in your dashboard.',
	'minimal' => 'Choose a location and a plan, pay from your wallet, and connect. Dedicated IPv4, IPv6, ISP and mobile proxies with instant delivery.',
	'emerald' => 'Dedicated proxies for social media, e-commerce, research and automation. Fund your wallet in ' . $cur . ' and start in minutes.',
	'sunset'  => 'Pick a country, choose 30, 60 or 90 days and you are online. Friendly prices, fast setup, and a team that answers.',
	'carbon'  => 'Dedicated IPv4, IPv6, ISP and mobile endpoints. HTTP(S) and SOCKS5, username or IP auth, exported in the format your tools expect.',
);
$std_head = array(
	'aurora'  => 'Private proxies,<br><span class="zs-hl">ready in minutes.</span>',
	'minimal' => 'Clean IPs.<br>Simple pricing.<br><span class="zs-hl">No surprises.</span>',
	'emerald' => 'Your gateway to a <span class="zs-hl">faster, private</span> internet.',
	'sunset'  => 'Proxies made easy for everyone.',
	'carbon'  => 'Proxies built for <span class="zs-hl">people who ship.</span>',
);
$h1       = '' !== $headline ? esc_html( $headline ) : $std_head[ $theme ];
$lead     = '' !== $lead ? nl2br( esc_html( $lead ) ) : esc_html( $std_lead[ $theme ] );
$mark     = $store->logo_url ? '<img src="' . esc_url( set_url_scheme( $store->logo_url ) ) . '" alt="">' : '<i>' . esc_html( strtoupper( mb_substr( $brand, 0, 1 ) ) ) . '</i>';
$svg      = function ( $d ) {
	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $d . '</svg>';
};
$buttons  = '<div class="zs-cta-row"><a class="zs-btn zs-btn--primary zs-btn--lg" href="' . esc_url( $cta ) . '">' . esc_html( $cta_txt ) . '</a><a class="zs-btn zs-btn--ghost zs-btn--lg" href="#plans">View plans</a></div>';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo esc_html( $brand . ' · Private proxies' ); ?></title>
<meta name="description" content="<?php echo esc_attr( 'Buy private IPv4, IPv6, ISP and mobile proxies from ' . $brand . '. Pay in ' . $cur . ' and get instant delivery.' ); ?>">
<link rel="manifest" href="<?php echo esc_url( zp_route_url( 'zp-manifest' ) ); ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $brand ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo esc_url( zp_asset( 'css/store.css' ) ); ?>">
<?php if ( $color ) : // the store's own brand colour replaces the design's accent ?>
<style>body.zs{--accent:<?php echo esc_html( $color ); ?>;--accent2:color-mix(in srgb,<?php echo esc_html( $color ); ?> 62%,#fff);--ink:#fff;--grad:linear-gradient(135deg,<?php echo esc_html( $color ); ?>,color-mix(in srgb,<?php echo esc_html( $color ); ?> 62%,#fff))}body.zs--minimal .zs-btn--primary,body.zs--minimal .zs-seg3 span.on,body.zs--minimal .zs-step b,body.zs--minimal .zs-plan .badge,body.zs--minimal .zs-tabbar a.fab span{background:<?php echo esc_html( $color ); ?>;border-color:<?php echo esc_html( $color ); ?>}</style>
<?php endif; ?>
</head>
<body class="zs zs--<?php echo esc_attr( $theme ); ?>" id="top">
<header class="zs-head">
	<div class="zs-wrap">
		<a class="zs-brand" href="<?php echo esc_url( $home ); ?>"><?php echo $mark; // phpcs:ignore ?><?php echo esc_html( $brand ); ?></a>
		<nav class="zs-nav" aria-label="Main"><a href="#plans">Plans</a><a href="#how">How it works</a><a href="#faq">FAQ</a></nav>
		<div class="zs-actions">
			<?php if ( $mine ) : ?>
				<a class="zs-btn zs-btn--primary zs-btn--sm" href="<?php echo esc_url( $panel ); ?>">Dashboard</a>
			<?php else : ?>
				<a class="zs-btn zs-btn--ghost zs-btn--sm" href="<?php echo esc_url( $login ); ?>">Sign in</a>
				<a class="zs-btn zs-btn--primary zs-btn--sm" href="<?php echo esc_url( $signup ); ?>">Get started</a>
			<?php endif; ?>
		</div>
	</div>
</header>

<main>
<?php if ( 'aurora' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap">
		<span class="zs-tag zs-rise"><b></b>IPv4 · IPv6 · ISP · Mobile</span>
		<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
		<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
		<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
		<div class="zs-ticks zs-rise" style="--d:4"><span>HTTP(S) &amp; SOCKS5</span><span>Automatic delivery</span><span>30, 60, 90 day plans</span></div>
		<div class="zs-console zs-rise" style="--d:5" aria-hidden="true">
			<div class="zs-pcard"><small>IPv4 · United States</small><strong class="zs-mono">205.120.31.217</strong><span class="zs-pill">● Active · 29d left</span></div>
			<div class="zs-pcard"><small>Mobile · United Kingdom</small><strong class="zs-mono">61.62.63.64</strong><span class="zs-pill">● Active · 58d left</span></div>
			<div class="zs-pcard"><small>ISP · Germany</small><strong class="zs-mono">185.81.112.210</strong><span class="zs-pill">● Active · 88d left</span></div>
		</div>
	</div></section>

<?php elseif ( 'minimal' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b>Trusted private proxies</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
			<div class="zs-ticks zs-rise" style="--d:4"><span>HTTP(S) &amp; SOCKS5</span><span>One owner per IP</span></div>
		</div>
		<div class="zs-order zs-rise" style="--d:2" aria-hidden="true">
			<h3>Build your order</h3>
			<label>Proxy type</label><div class="zs-field"><?php echo esc_html( $first ? zp_type_label( $first ) . ' · Dedicated' : 'IPv4 · Dedicated' ); ?><span>⌄</span></div>
			<label>Location</label><div class="zs-field">🇺🇸 United States<span>⌄</span></div>
			<label>Period</label><div class="zs-seg3"><span class="on">30 days</span><span>60 days</span><span>90 days</span></div>
			<div class="zs-total"><span>From</span><strong><?php echo esc_html( $first ? $from[ $first ]['price'] : 'Live price' ); ?></strong></div>
			<a class="zs-btn zs-btn--primary" style="width:100%" href="<?php echo esc_url( $cta ); ?>">Continue</a>
		</div>
	</div></section>

<?php elseif ( 'emerald' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b><?php echo $places ? 'Online in ' . (int) $places . '+ locations' : 'Online around the world'; ?></span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
			<div class="zs-ticks zs-rise" style="--d:4"><span>Instant delivery</span><span>Extend any time</span><span>Real support</span></div>
		</div>
		<div class="zs-orbit zs-rise" style="--d:2" aria-hidden="true">
			<div class="zs-core"><span><b><?php echo count( $types ); ?></b>proxy<br>networks</span></div>
			<div class="zs-chip">🇺🇸 IPv4 · USA</div><div class="zs-chip">📱 Mobile 4G</div><div class="zs-chip">🏠 ISP · Germany</div><div class="zs-chip">⚡ SOCKS5</div>
		</div>
	</div></section>

<?php elseif ( 'sunset' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap"><div class="zs-panel">
		<div>
			<span class="zs-tag zs-rise"><b></b>New here? Welcome 👋</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
		</div>
		<div class="zs-stack zs-rise" style="--d:2" aria-hidden="true">
			<?php
			$n = 0;
			foreach ( $types as $slug => $t ) :
				if ( ++$n > 3 ) {
					break;
				}
				?>
				<div><span><?php echo esc_html( $t['label'] ); ?> proxy<small><?php echo isset( $from[ $slug ] ) ? (int) $from[ $slug ]['days'] . ' days' : 'Live pricing'; ?></small></span><em><?php echo esc_html( isset( $from[ $slug ] ) ? $from[ $slug ]['price'] : '→' ); ?></em></div>
			<?php endforeach; ?>
		</div>
	</div></div></section>

<?php else : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b>status: all systems online</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
			<div class="zs-ticks zs-rise" style="--d:4"><span>ip:port:user:pass</span><span>SOCKS5</span><span>Rotation on demand</span></div>
		</div>
		<div class="zs-term zs-rise" style="--d:2" aria-hidden="true"><header><i></i><i></i><i></i><span>proxies.txt</span></header><pre><i># 3 proxies · United States · 30 days</i>
<b>205.120.31.217</b>:53062:zx9c9748af:••••••••
<b>205.120.31.218</b>:53064:zx3dda908d:••••••••
<b>205.120.31.219</b>:53066:zxf80dbd86:••••••••

<i>$ curl -x socks5://205.120.31.217:53063 ifconfig.me</i>
<b>205.120.31.217</b>  <i># ✓ connected</i></pre></div>
	</div></section>
<?php endif; ?>

	<section class="zs-strip"><div class="zs-wrap">
		<div><strong><?php echo $places ? (int) $places . '+' : 'Global'; ?></strong><span>Locations to choose from</span></div>
		<div><strong><?php echo count( $types ); ?></strong><span>Proxy networks</span></div>
		<div><strong>30·60·90</strong><span>Day plans, extend any time</span></div>
		<div><strong>24/7</strong><span>Automatic delivery</span></div>
	</div></section>

	<section class="zs-sec" id="plans"><div class="zs-wrap">
		<div class="zs-title"><span>Plans</span><h2>Pick the network you need</h2><p>Prices in <?php echo esc_html( $cur ); ?>. Pay only for what you rent: 30, 60 or 90 days.</p></div>
		<div class="zs-plans">
			<?php
			$n = 0;
			foreach ( $types as $slug => $t ) :
				$pop = 0 === $n++;
				?>
				<div class="zs-plan<?php echo $pop ? ' pop' : ''; ?>">
					<?php echo $pop ? '<span class="badge">Most popular</span>' : ''; ?>
					<span class="ico"><?php echo esc_html( $emoji[ $slug ] ); ?></span>
					<h3><?php echo esc_html( $t['label'] ); ?></h3>
					<p><?php echo esc_html( $t['desc'] ); ?></p>
					<div class="zs-price">
						<?php if ( isset( $from[ $slug ] ) ) : ?>
							<small>from</small><strong><?php echo esc_html( $from[ $slug ]['price'] ); ?></strong><small>per proxy / <?php echo (int) $from[ $slug ]['days']; ?> days</small>
						<?php else : ?>
							<small>price</small><strong style="font-size:22px">Shown at checkout</strong><small>calculated live</small>
						<?php endif; ?>
					</div>
					<ul><?php foreach ( $perks[ $slug ] as $perk ) : ?><li><?php echo esc_html( $perk ); ?></li><?php endforeach; ?></ul>
					<a class="zs-btn <?php echo $pop ? 'zs-btn--primary' : 'zs-btn--ghost'; ?>" href="<?php echo esc_url( $mine ? zp_route_url( ZP_USER_BASE, 'buy', array( 'type' => $slug ) ) : $signup ); ?>">Buy <?php echo esc_html( $t['label'] ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
	</div></section>

	<section class="zs-sec zs-sec--alt" id="how"><div class="zs-wrap">
		<div class="zs-title"><span>How it works</span><h2>Three steps to your first proxy</h2></div>
		<div class="zs-steps">
			<div class="zs-step"><b>1</b><h3>Create your account</h3><p>Sign up with your name and email in under a minute.</p></div>
			<div class="zs-step"><b>2</b><h3>Fund your wallet</h3><p>Pay by bank transfer<?php echo $store->telegram ? ' and send your receipt on Telegram' : ''; ?>. Your wallet is credited once it is confirmed.</p></div>
			<div class="zs-step"><b>3</b><h3>Choose and connect</h3><p>Pick a type, country and period, then copy your proxy details into your browser or tool.</p></div>
		</div>
	</div></section>

	<section class="zs-sec" id="faq"><div class="zs-wrap">
		<div class="zs-title"><span>FAQ</span><h2>Good to know</h2></div>
		<div class="zs-faq">
			<?php foreach ( array_slice( zp_faqs(), 0, 5, true ) as $q => $a ) : ?>
				<details><summary><?php echo esc_html( $q ); ?></summary><p><?php echo esc_html( $a ); ?></p></details>
			<?php endforeach; ?>
		</div>
	</div></section>

	<section class="zs-cta"><div class="zs-wrap"><div>
		<div><h2>Ready when you are.</h2><p>Create a free account and buy your first proxy today.</p></div>
		<a class="zs-btn zs-btn--lg" href="<?php echo esc_url( $cta ); ?>"><?php echo esc_html( $cta_txt ); ?> →</a>
	</div></div></section>
</main>

<footer class="zs-foot"><div class="zs-wrap">
	<span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $brand ); ?>. All rights reserved.</span>
	<span><a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'privacy' ) ); ?>">Privacy Policy</a> · <a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'refund' ) ); ?>">Refund Policy</a> · <a href="<?php echo esc_url( $mine ? $panel : $login ); ?>"><?php echo $mine ? 'Dashboard' : 'Sign in'; ?></a></span>
</div></footer>

<nav class="zs-tabbar" aria-label="Quick navigation">
	<a class="on" href="#top"><?php echo $svg( '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h5v-6h4v6h5V9.5"/>' ); // phpcs:ignore ?>Home</a>
	<a href="#plans"><?php echo $svg( '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>' ); // phpcs:ignore ?>Plans</a>
	<a class="fab" href="<?php echo esc_url( $cta ); ?>"><span><?php echo $svg( '<path d="M12 5v14M5 12h14"/>' ); // phpcs:ignore ?></span>Buy</a>
	<a href="#faq"><?php echo $svg( '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.3a2.6 2.6 0 0 1 5 .9c0 1.8-2.5 2.2-2.5 4"/>' ); // phpcs:ignore ?>FAQ</a>
	<a href="<?php echo esc_url( $mine ? $panel : $login ); ?>"><?php echo $svg( '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>' ); // phpcs:ignore ?><?php echo $mine ? 'Account' : 'Sign in'; ?></a>
</nav>
<?php if ( $preview ) : ?>
	<div class="zs-preview">Preview: <?php echo esc_html( $themes[ $preview ]['name'] ); ?> design<a href="<?php echo esc_url( zp_route_url( ZP_USER_BASE, 'reseller', array( 'tab' => 'settings' ) ) ); ?>">Back to settings</a></div>
<?php endif; ?>
<script>
(function(){var tabs=[].slice.call(document.querySelectorAll('.zs-tabbar a[href^="#"]'));if(!('IntersectionObserver' in window)||!tabs.length)return;
var map={};tabs.forEach(function(a){map[a.getAttribute('href').slice(1)]=a;});
var io=new IntersectionObserver(function(es){es.forEach(function(e){if(e.isIntersecting&&map[e.target.id]){tabs.forEach(function(t){t.classList.remove('on');});map[e.target.id].classList.add('on');}});},{rootMargin:'-45% 0px -50% 0px'});
['top','plans','faq'].forEach(function(id){var el=id==='top'?document.querySelector('.zs-hero'):document.getElementById(id);if(el){if(id==='top')el.id=el.id||'top-hero';map[el.id]=map[id];io.observe(el);}});})();
</script>
</body>
</html>
