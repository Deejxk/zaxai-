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
$ic      = array(
	'ipv4'    => '<rect x="3" y="4" width="18" height="12" rx="2"/><path d="M8 20h8M12 16v4"/>',
	'ipv6'    => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
	'isp'     => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.500"/><path d="M9 20v-5h6v5"/>',
	'mobile'  => '<rect x="7" y="2.500" width="10" height="19" rx="2.500"/><path d="M11 18h2"/>',
	'shield'  => '<path d="M12 3 5 6v5.500c0 4.400 2.900 8 7 9.500 4.100-1.500 7-5.100 7-9.500V6z"/><path d="m9 12 2.200 2.200L15 10.300"/>',
	'bolt'    => '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
	'swap'    => '<path d="M4 8h14l-3-3M20 16H6l3 3"/>',
	'key'     => '<circle cx="8" cy="15" r="4"/><path d="m11 12 9-9M16 7l3 3M14 9l2 2"/>',
	'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
	'chat'    => '<path d="M4 5h16v11H9l-5 4z"/>',
	'globe'   => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.800 3 2.800 15 0 18M12 3c-2.800 3-2.800 15 0 18"/>',
	'cart'    => '<path d="M4 7h16l-1.500 11a2 2 0 0 1-2 1.700h-9a2 2 0 0 1-2-1.700z"/><path d="M8.500 7a3.500 3.500 0 0 1 7 0"/>',
	'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
	'users'   => '<circle cx="9" cy="8" r="3.500"/><path d="M2.500 20a6.500 6.500 0 0 1 13 0"/><path d="M16 4.800a3.500 3.500 0 0 1 0 6.400M18 14.500a6.500 6.500 0 0 1 3.500 5.500"/>',
	'code'    => '<path d="m8 8-4 4 4 4M16 8l4 4-4 4M13.500 5l-3 14"/>',
	'eye'     => '<path d="M2 12s3.600-7 10-7 10 7 10 7-3.600 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
	'wallet'  => '<rect x="3" y="6" width="18" height="13" rx="2.500"/><path d="M3 10.500h18M16 15h2"/>',
	'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
	'plug'    => '<path d="M9 3v5M15 3v5M6 8h12v3a6 6 0 0 1-12 0zM12 17v4"/>',
	'file'    => '<path d="M6 3h8l4 4v14H6z"/><path d="M14 3v4h4M9 13h6M9 17h6"/>',
	'check'   => '<path d="m5 12.500 4.500 4.500L19 7.500"/>',
);
$perks   = array(
	'ipv4'   => array( 'HTTP(S) and SOCKS5', 'Login or IP whitelist', 'One owner per IP', 'Works on every website' ),
	'ipv6'   => array( 'Batches of 10 or more', 'Lowest cost per IP', 'Choose your protocol', 'For IPv6-ready sites' ),
	'isp'    => array( 'Real provider networks', 'High trust score', 'Static, long sessions', 'Extend any time' ),
	'mobile' => array( 'Choose your operator', 'Rotate on demand', 'HTTP(S) and SOCKS5', 'Real 4G / LTE IPs' ),
);
$best    = array(
	'ipv4'   => 'Everyday use, social media, shopping',
	'ipv6'   => 'Bulk tasks on IPv6-ready sites',
	'isp'    => 'Accounts that need high trust',
	'mobile' => 'Social apps and strict platforms',
);
$source  = array( 'ipv4' => 'Data centre', 'ipv6' => 'Data centre', 'isp' => 'Internet provider', 'mobile' => 'Mobile operator' );
$sold    = array( 'ipv4' => 'Per IP', 'ipv6' => 'Batches of 10+', 'isp' => 'Per IP', 'mobile' => 'Per IP' );
$tg      = zp_telegram_url();
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
	'royal'   => 'Private, dedicated proxies with a first-class service. Choose your location, pay in ' . $cur . ' and connect in minutes.',
	'ocean'   => 'One simple dashboard for all your proxies. Fund your wallet, pick a country and your IPs are ready to use.',
	'nebula'  => 'Fast private IPs across the globe. IPv4, IPv6, ISP and mobile, delivered automatically to your dashboard.',
	'steel'   => 'Dedicated IPs, clear prices, no nonsense. Buy for 30, 60 or 90 days and renew whenever you like.',
	'blossom' => 'Private proxies for your social media, shops and everyday browsing. Simple to buy, simple to connect.',
);
$std_head = array(
	'aurora'  => 'Private proxies,<br><span class="zs-hl">ready in minutes.</span>',
	'minimal' => 'Clean IPs.<br>Simple pricing.<br><span class="zs-hl">No surprises.</span>',
	'emerald' => 'Your gateway to a <span class="zs-hl">faster, private</span> internet.',
	'sunset'  => 'Proxies made easy for everyone.',
	'carbon'  => 'Proxies built for <span class="zs-hl">people who ship.</span>',
	'royal'   => 'Premium proxies, <span class="zs-hl">privately yours.</span>',
	'ocean'   => 'All your proxies in <span class="zs-hl">one calm place.</span>',
	'nebula'  => 'Go anywhere online. <span class="zs-hl">Stay private.</span>',
	'steel'   => 'Reliable proxies.<br><span class="zs-hl">Straight prices.</span>',
	'blossom' => 'Browse, post and sell <span class="zs-hl">with confidence.</span>',
);
$h1       = '' !== $headline ? esc_html( $headline ) : $std_head[ $theme ];
$lead     = '' !== $lead ? nl2br( esc_html( $lead ) ) : esc_html( $std_lead[ $theme ] );
$mark     = $store->logo_url ? '<img src="' . esc_url( ZP_Shield::media_url( $store->logo_url ) ) . '" alt="">' : '<i>' . esc_html( strtoupper( mb_substr( $brand, 0, 1 ) ) ) . '</i>';
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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@600;700;800&family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;700&family=Archivo:wght@500;600;800&family=DM+Serif+Display&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?php echo esc_url( zp_asset( 'css/store.css' ) ); ?>"<?php echo ZP_Shield::alias_on() ? " onerror=\"if(document.cookie.indexOf('zp_plain=')<0){document.cookie='zp_plain=1; path=/; max-age=86400; samesite=lax';location.reload()}\"" : ''; ?>>
<?php
$pal = ZP_Resellers::store_palette( $store, $theme );
if ( $pal ) : // the store's own brand colour replaces the design's accent, with text colours chosen to stay readable
	?>
<style>body.zs{--accent:<?php echo esc_html( $pal['accent'] ); ?>;--accent2:<?php echo esc_html( $pal['end'] ); ?>;--accent-t:<?php echo esc_html( $pal['text'] ); ?>;--ink:<?php echo esc_html( $pal['ink'] ); ?>;--grad:linear-gradient(135deg,<?php echo esc_html( $pal['accent'] ); ?>,<?php echo esc_html( $pal['end'] ); ?>)}
body.zs .zs-hl{background:none;-webkit-background-clip:border-box;background-clip:border-box;color:<?php echo esc_html( $pal['text'] ); ?>;-webkit-text-fill-color:currentColor}
body.zs .zs-btn--primary,body.zs .zs-plan .badge,body.zs .zs-step b,body.zs .zs-brand i,body.zs .zs-tabbar a.fab span,body.zs .zs-seg3 span.on,body.zs .zs-core,body.zs .zs-phone__ring,body.zs .zs-spec__head,body.zs .zs-dash__chart i:last-child{background:var(--grad);border-color:transparent;color:<?php echo esc_html( $pal['ink'] ); ?>}
body.zs .zs-cta .zs-wrap>div,body.zs--sunset .zs-panel,body.zs--sunset .zs-panel p.lead,body.zs--sunset .zs-panel .zs-tag{color:<?php echo esc_html( $pal['ink'] ); ?>}
body.zs--sunset .zs-panel .zs-btn--ghost{color:<?php echo esc_html( $pal['ink'] ); ?>;border-color:<?php echo esc_html( $pal['ink'] ); ?>}
body.zs--sunset .zs-panel .zs-tag{background:transparent;border-color:currentColor}
body.zs--sunset .zs-panel .zs-tag b{background:<?php echo esc_html( $pal['ink'] ); ?>}
body.zs--sunset .zs-panel .zs-btn--primary{background:<?php echo esc_html( '#ffffff' === $pal['ink'] ? '#ffffff' : '#14161d' ); ?>;color:<?php echo esc_html( '#ffffff' === $pal['ink'] ? '#14161d' : '#ffffff' ); ?>}
body.zs--carbon .zs-strip strong{color:<?php echo esc_html( $pal['text'] ); ?>}</style>
<?php endif; ?>
</head>
<body class="zs zs--<?php echo esc_attr( $theme ); ?>" id="top">
<header class="zs-head">
	<div class="zs-wrap">
		<a class="zs-brand" href="<?php echo esc_url( $home ); ?>"><?php echo $mark; // phpcs:ignore ?><?php echo esc_html( $brand ); ?></a>
		<nav class="zs-nav" aria-label="Main"><a href="#plans">Plans</a><a href="#compare">Compare</a><a href="#features">Features</a><a href="#how">How it works</a><a href="#faq">FAQ</a></nav>
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
			<label>Location</label><div class="zs-field">United States<span>⌄</span></div>
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
			<div class="zs-chip">IPv4 · USA</div><div class="zs-chip">Mobile 4G</div><div class="zs-chip">ISP · Germany</div><div class="zs-chip">SOCKS5</div>
		</div>
	</div></section>

<?php elseif ( 'sunset' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap"><div class="zs-panel">
		<div>
			<span class="zs-tag zs-rise"><b></b>New here? Welcome</span>
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

<?php elseif ( 'royal' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b>Private access · Since day one</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
			<div class="zs-ticks zs-rise" style="--d:4"><span>One owner per IP</span><span>HTTP(S) &amp; SOCKS5</span><span>Extend any time</span></div>
		</div>
		<div class="zs-vip zs-rise" style="--d:2" aria-hidden="true">
			<div class="zs-vip__top"><span><?php echo esc_html( $brand ); ?></span><em>Private pass</em></div>
			<div class="zs-vip__chip"></div>
			<strong class="zs-mono">205.120.31.217</strong>
			<div class="zs-vip__row"><div><small>Network</small><?php echo esc_html( $first ? zp_type_label( $first ) : 'IPv4' ); ?></div><div><small>Period</small>30 days</div><div><small>From</small><?php echo esc_html( $first ? $from[ $first ]['price'] : 'Live price' ); ?></div></div>
		</div>
	</div></section>

<?php elseif ( 'ocean' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b>Simple proxy dashboard</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
			<div class="zs-ticks zs-rise" style="--d:4"><span>Instant delivery</span><span>Pay in <?php echo esc_html( $cur ); ?></span><span>Real support</span></div>
		</div>
		<div class="zs-dash zs-rise" style="--d:2" aria-hidden="true">
			<div class="zs-dash__bar"><i></i><i></i><i></i><span>My proxies</span></div>
			<div class="zs-dash__tiles"><div><small>Active</small><strong>12</strong></div><div><small>Locations</small><strong><?php echo $places ? (int) $places . '+' : '16+'; ?></strong></div><div><small>From</small><strong><?php echo esc_html( $first ? $from[ $first ]['price'] : 'Live price' ); ?></strong></div></div>
			<div class="zs-dash__chart"><i style="--h:38%"></i><i style="--h:62%"></i><i style="--h:48%"></i><i style="--h:78%"></i><i style="--h:56%"></i><i style="--h:92%"></i><i style="--h:70%"></i></div>
			<div class="zs-dash__line"><span class="zs-mono">205.120.31.217</span><em>Active</em></div>
			<div class="zs-dash__line"><span class="zs-mono">185.81.112.210</span><em>Active</em></div>
		</div>
	</div></section>

<?php elseif ( 'nebula' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap">
		<span class="zs-tag zs-rise"><b></b>IPv4 · IPv6 · ISP · Mobile</span>
		<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
		<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
		<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
		<div class="zs-glass zs-rise" style="--d:4" aria-hidden="true">
			<div><small>United States · <?php echo esc_html( $first ? zp_type_label( $first ) : 'IPv4' ); ?></small><strong class="zs-mono">205.120.31.217</strong><em>Online</em></div>
			<div><small>United Kingdom · Mobile</small><strong class="zs-mono">61.62.63.64</strong><em>Online</em></div>
			<div><small>Germany · ISP</small><strong class="zs-mono">185.81.112.210</strong><em>Online</em></div>
		</div>
	</div></section>

<?php elseif ( 'steel' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b>Dedicated proxies</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
		</div>
		<div class="zs-spec zs-rise" style="--d:2" aria-hidden="true">
			<div class="zs-spec__head">What you get</div>
			<dl>
				<div><dt>Networks</dt><dd><?php echo count( $types ); ?></dd></div>
				<div><dt>Locations</dt><dd><?php echo $places ? (int) $places . '+' : 'Global'; ?></dd></div>
				<div><dt>Protocols</dt><dd>HTTP(S) · SOCKS5</dd></div>
				<div><dt>Periods</dt><dd>30 · 60 · 90 days</dd></div>
				<div><dt>Owner per IP</dt><dd>1</dd></div>
				<div class="is-total"><dt>Price from</dt><dd><?php echo esc_html( $first ? $from[ $first ]['price'] : 'Live price' ); ?></dd></div>
			</dl>
		</div>
	</div></section>

<?php elseif ( 'blossom' === $theme ) : ?>
	<section class="zs-hero"><div class="zs-wrap zs-split">
		<div>
			<span class="zs-tag zs-rise"><b></b>Friendly private proxies</span>
			<h1 class="zs-rise" style="--d:1"><?php echo $h1; // phpcs:ignore ?></h1>
			<p class="lead zs-rise" style="--d:2"><?php echo $lead; // phpcs:ignore ?></p>
			<div class="zs-rise" style="--d:3"><?php echo $buttons; // phpcs:ignore ?></div>
			<div class="zs-ticks zs-rise" style="--d:4"><span>Works on phone and computer</span><span>Step-by-step guides</span></div>
		</div>
		<div class="zs-phone zs-rise" style="--d:2" aria-hidden="true">
			<div class="zs-phone__notch"></div>
			<div class="zs-phone__ring"><span>ON</span></div>
			<strong>Connected</strong>
			<small>Your connection is private</small>
			<div class="zs-phone__card"><span>Location</span><b>United States</b></div>
			<div class="zs-phone__card"><span>IP address</span><b class="zs-mono">205.120.31.217</b></div>
		</div>
	</div></section>

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
		<div><i><?php echo $svg( $ic['globe'] ); // phpcs:ignore ?></i><strong><?php echo $places ? (int) $places . '+' : 'Global'; ?></strong><span>Locations to choose from</span></div>
		<div><i><?php echo $svg( $ic['swap'] ); // phpcs:ignore ?></i><strong><?php echo count( $types ); ?></strong><span>Proxy networks</span></div>
		<div><i><?php echo $svg( $ic['clock'] ); // phpcs:ignore ?></i><strong>30·60·90</strong><span>Day plans, extend any time</span></div>
		<div><i><?php echo $svg( $ic['bolt'] ); // phpcs:ignore ?></i><strong>24/7</strong><span>Automatic delivery</span></div>
	</div></section>

	<section class="zs-sec" id="plans"><div class="zs-wrap">
		<div class="zs-title"><span>Plans</span><h2>Pick the network you need</h2><p>Every proxy is private to you for the whole rental. Prices are in <?php echo esc_html( $cur ); ?> per proxy, and you choose 30, 60 or 90 days at checkout.</p></div>
		<div class="zs-plans">
			<?php
			$n = 0;
			foreach ( $types as $slug => $t ) :
				$pop = 0 === $n++;
				?>
				<div class="zs-plan<?php echo $pop ? ' pop' : ''; ?>">
					<?php echo $pop ? '<span class="badge">Popular</span>' : ''; ?>
					<span class="ico"><?php echo $svg( $ic[ $slug ] ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $t['label'] ); ?></h3>
					<p><?php echo esc_html( $t['desc'] ); ?></p>
					<div class="zs-price">
						<?php if ( isset( $from[ $slug ] ) ) : ?>
							<small>from</small><strong><?php echo esc_html( $from[ $slug ]['price'] ); ?></strong><small>per proxy / <?php echo (int) $from[ $slug ]['days']; ?> days<?php echo 'mobile' === $slug ? ' · varies by country' : ''; ?></small>
						<?php else : ?>
							<small>price</small><strong class="zs-price__live">At checkout</strong><small>calculated live</small>
						<?php endif; ?>
					</div>
					<div class="zs-best"><small>Best for</small><?php echo esc_html( $best[ $slug ] ); ?></div>
					<ul><?php foreach ( $perks[ $slug ] as $perk ) : ?><li><?php echo esc_html( $perk ); ?></li><?php endforeach; ?></ul>
					<a class="zs-btn <?php echo $pop ? 'zs-btn--primary' : 'zs-btn--ghost'; ?>" href="<?php echo esc_url( $mine ? zp_route_url( ZP_USER_BASE, 'buy', array( 'type' => $slug ) ) : $signup ); ?>">Buy <?php echo esc_html( $t['label'] ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="zs-note"><?php echo $svg( $ic['shield'] ); // phpcs:ignore ?>The price you see at checkout is the price you pay. No setup fee and no automatic renewal: you extend only when you want to.</p>
	</div></section>

	<section class="zs-sec zs-sec--alt" id="compare"><div class="zs-wrap">
		<div class="zs-title"><span>Compare</span><h2>Which proxy is right for you?</h2><p>Not sure where to start? Here is how the networks differ.</p></div>
		<div class="zs-table"><table>
			<thead><tr><th>Network</th><th>Best for</th><th>IP comes from</th><th>Sold</th><th>Protocols</th><th>From</th></tr></thead>
			<tbody>
			<?php foreach ( $types as $slug => $t ) : ?>
				<tr>
					<th><span class="ico"><?php echo $svg( $ic[ $slug ] ); // phpcs:ignore ?></span><?php echo esc_html( $t['label'] ); ?></th>
					<td><?php echo esc_html( $best[ $slug ] ); ?></td>
					<td><?php echo esc_html( $source[ $slug ] ); ?></td>
					<td><?php echo esc_html( $sold[ $slug ] ); ?></td>
					<td><?php echo 'ipv6' === $slug ? 'HTTP(S) or SOCKS5' : 'HTTP(S) + SOCKS5'; ?></td>
					<td><strong><?php echo esc_html( isset( $from[ $slug ] ) ? $from[ $slug ]['price'] : 'At checkout' ); ?></strong></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<p class="zs-swipe">Swipe the table sideways to see more →</p>
	</div></section>

	<section class="zs-sec" id="features"><div class="zs-wrap">
		<div class="zs-title"><span>Why <?php echo esc_html( $brand ); ?></span><h2>Everything you need, nothing you don't</h2><p>Simple tools that make buying and managing proxies easy, on your phone or your computer.</p></div>
		<div class="zs-grid zs-grid--3">
			<div class="zs-card"><i><?php echo $svg( $ic['shield'] ); // phpcs:ignore ?></i><h3>Private to you</h3><p>Your proxy is rented to you alone for the whole period. Nobody else shares it.</p></div>
			<div class="zs-card"><i><?php echo $svg( $ic['bolt'] ); // phpcs:ignore ?></i><h3>Fast delivery</h3><p>Orders are set up automatically and appear in your dashboard, usually within minutes.</p></div>
			<div class="zs-card"><i><?php echo $svg( $ic['swap'] ); // phpcs:ignore ?></i><h3>HTTP(S) and SOCKS5</h3><p>Use the protocol your browser, phone or tool expects. Both are supported.</p></div>
			<div class="zs-card"><i><?php echo $svg( $ic['key'] ); // phpcs:ignore ?></i><h3>Two ways to log in</h3><p>Connect with a username and password, or whitelist your own IP address.</p></div>
			<div class="zs-card"><i><?php echo $svg( $ic['clock'] ); // phpcs:ignore ?></i><h3>Extend any time</h3><p>Keep the same IP for as long as you need. Renew from your dashboard before it expires.</p></div>
			<div class="zs-card"><i><?php echo $svg( $ic['file'] ); // phpcs:ignore ?></i><h3>Copy or export</h3><p>Copy one proxy with a tap, or download your whole list in the format your tool needs.</p></div>
		</div>
	</div></section>

	<section class="zs-sec zs-sec--alt" id="uses"><div class="zs-wrap">
		<div class="zs-title"><span>Use cases</span><h2>What people use our proxies for</h2></div>
		<div class="zs-grid zs-grid--uses">
			<div class="zs-use"><i><?php echo $svg( $ic['users'] ); // phpcs:ignore ?></i><h3>Social media</h3><p>Manage several accounts, each on its own clean IP.</p></div>
			<div class="zs-use"><i><?php echo $svg( $ic['cart'] ); // phpcs:ignore ?></i><h3>E-commerce</h3><p>Run stores and check prices in other countries.</p></div>
			<div class="zs-use"><i><?php echo $svg( $ic['search'] ); // phpcs:ignore ?></i><h3>SEO and research</h3><p>See search results exactly as locals see them.</p></div>
			<div class="zs-use"><i><?php echo $svg( $ic['code'] ); // phpcs:ignore ?></i><h3>Automation</h3><p>Connect bots, scripts and tools with stable IPs.</p></div>
			<div class="zs-use"><i><?php echo $svg( $ic['eye'] ); // phpcs:ignore ?></i><h3>Ad checking</h3><p>Confirm your adverts show correctly in each region.</p></div>
			<div class="zs-use"><i><?php echo $svg( $ic['globe'] ); // phpcs:ignore ?></i><h3>Private browsing</h3><p>Browse from another location and keep your own IP private.</p></div>
		</div>
	</div></section>

	<section class="zs-sec" id="how"><div class="zs-wrap">
		<div class="zs-title"><span>How it works</span><h2>From sign-up to connected in four steps</h2><p>No technical knowledge needed. If you can copy and paste, you can use a proxy.</p></div>
		<div class="zs-steps">
			<div class="zs-step"><b>1</b><h3>Create your account</h3><p>Sign up with your name and email. It takes less than a minute and it is free.</p></div>
			<div class="zs-step"><b>2</b><h3>Fund your wallet</h3><p>Pay in <?php echo esc_html( $cur ); ?> by bank transfer<?php echo $tg ? ', then send your receipt on Telegram' : ''; ?>. Your wallet is credited once payment is confirmed.</p></div>
			<div class="zs-step"><b>3</b><h3>Choose your proxy</h3><p>Pick a network, a country and 30, 60 or 90 days. You see the total before you pay.</p></div>
			<div class="zs-step"><b>4</b><h3>Copy and connect</h3><p>Your proxy details appear in your dashboard. Paste them into your browser, phone or tool.</p></div>
		</div>
	</div></section>

	<section class="zs-sec zs-sec--alt" id="pay"><div class="zs-wrap zs-pay">
		<div>
			<div class="zs-title"><span>Payment</span><h2>Pay in <?php echo esc_html( $cur ); ?>, the easy way</h2><p>Top up your wallet once and use the balance for any order or renewal.</p></div>
			<ul class="zs-checks">
				<li><?php echo $svg( $ic['check'] ); // phpcs:ignore ?><span><strong>Bank transfer.</strong> Our account details are shown in your wallet with a copy button.</span></li>
				<li><?php echo $svg( $ic['check'] ); // phpcs:ignore ?><span><strong>Wallet balance.</strong> Unused money stays in your wallet for your next order.</span></li>
				<li><?php echo $svg( $ic['check'] ); // phpcs:ignore ?><span><strong>Clear records.</strong> Every deposit and purchase is listed in your transaction history.</span></li>
				<li><?php echo $svg( $ic['check'] ); // phpcs:ignore ?><span><strong>Fair refunds.</strong> If an order cannot be delivered, the money goes straight back to your wallet.</span></li>
			</ul>
		</div>
		<div class="zs-help">
			<i><?php echo $svg( $ic['chat'] ); // phpcs:ignore ?></i>
			<h3>Need help choosing?</h3>
			<p>Tell us what you want to use the proxy for and we will point you to the right plan. Real people answer.</p>
			<?php if ( $tg ) : ?>
				<a class="zs-btn zs-btn--primary" href="<?php echo esc_attr( $tg ); ?>" target="_blank" rel="noopener">Chat on Telegram</a>
			<?php endif; ?>
			<a class="zs-btn zs-btn--ghost" href="<?php echo esc_url( $mine ? zp_route_url( ZP_USER_BASE, 'tickets' ) : $signup ); ?>"><?php echo $mine ? 'Open a support ticket' : 'Create a free account'; ?></a>
		</div>
	</div></section>

	<section class="zs-sec" id="faq"><div class="zs-wrap">
		<div class="zs-title"><span>FAQ</span><h2>Questions, answered</h2><p>The things customers ask us most often.</p></div>
		<div class="zs-faq">
			<?php foreach ( array_slice( zp_faqs(), 0, 8, true ) as $q => $a ) : ?>
				<details><summary><?php echo esc_html( $q ); ?></summary><p><?php echo esc_html( $a ); ?></p></details>
			<?php endforeach; ?>
		</div>
	</div></section>

	<section class="zs-cta"><div class="zs-wrap"><div>
		<div><h2>Ready when you are.</h2><p>Create a free account, fund your wallet and get your first proxy today.</p></div>
		<a class="zs-btn zs-btn--lg" href="<?php echo esc_url( $cta ); ?>"><?php echo esc_html( $cta_txt ); ?> →</a>
	</div></div></section>
</main>

<footer class="zs-foot"><div class="zs-wrap">
	<div class="zs-foot__grid">
		<div class="zs-foot__brand">
			<a class="zs-brand" href="<?php echo esc_url( $home ); ?>"><?php echo $mark; // phpcs:ignore ?><?php echo esc_html( $brand ); ?></a>
			<p>Private IPv4, IPv6, ISP and mobile proxies. Pay in <?php echo esc_html( $cur ); ?> and manage everything from one simple dashboard.</p>
		</div>
		<div><h4>Shop</h4><a href="#plans">Plans and prices</a><a href="#compare">Compare networks</a><a href="#uses">Use cases</a></div>
		<div><h4>Help</h4><a href="#how">How it works</a><a href="#faq">FAQ</a><?php if ( $tg ) : ?><a href="<?php echo esc_attr( $tg ); ?>" target="_blank" rel="noopener">Telegram support</a><?php endif; ?></div>
		<div><h4>Account</h4><a href="<?php echo esc_url( $mine ? $panel : $login ); ?>"><?php echo $mine ? 'Dashboard' : 'Sign in'; ?></a><?php if ( ! $mine ) : ?><a href="<?php echo esc_url( $signup ); ?>">Create account</a><?php endif; ?><a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'privacy' ) ); ?>">Privacy Policy</a><a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'refund' ) ); ?>">Refund Policy</a></div>
	</div>
	<div class="zs-foot__base"><span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $brand ); ?>. All rights reserved.</span><span>Proxies must be used lawfully and in line with our policies.</span></div>
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
