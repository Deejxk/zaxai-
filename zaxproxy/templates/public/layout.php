<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $view */
$brand  = zp_get( 'brand' );
$flash  = zp_take_flash();
$legal  = include ZP_DIR . 'templates/public/legal.php';
$titles = array(
	'home'    => array( 'Private proxies in minutes', 'Buy private IPv4, IPv6, ISP and mobile proxies. Pay in ' . zp_get( 'currency_code' ) . ', get instant delivery and manage everything from one dashboard.' ),
	'pricing' => array( 'Pricing', 'Simple proxy pricing in ' . zp_get( 'currency_code' ) . '. Pay only for what you rent.' ),
	'faq'     => array( 'FAQ', 'Answers to common questions about ' . $brand . ' proxies, payments and delivery.' ),
	'about'   => array( 'About us', 'Who we are and why we built ' . $brand . '.' ),
	'contact' => array( 'Contact', 'Get in touch with the ' . $brand . ' team.' ),
);
$is_legal = isset( $legal[ $view ] );
$title    = $is_legal ? $legal[ $view ]['title'] : $titles[ $view ][0];
$desc     = $is_legal ? $legal[ $view ]['intro'] : $titles[ $view ][1];
$logged   = is_user_logged_in();
$menu     = array( '' => 'Home', 'pricing' => 'Pricing', 'faq' => 'FAQ', 'about' => 'About', 'contact' => 'Contact' );
$current  = array_search( $view, ZP_Router::PUBLIC_PAGES, true );
?>
<!doctype html>
<html lang="en">
<head>
<?php ZP_Router::head( $title, true, $desc ); ?>
<link rel="stylesheet" href="<?php echo esc_url( zp_asset( 'css/public.css' ) ); ?>">
</head>
<body class="zp-body zp-public">
<?php ZP_Router::splash(); ?>
<header class="zp-phead">
	<div class="zp-wrap zp-phead__in">
		<a class="zp-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo zp_logo( 32 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
		<nav class="zp-pnav" aria-label="Main">
			<?php foreach ( $menu as $slug => $label ) : ?>
				<a class="<?php echo $slug === $current ? 'is-active' : ''; ?>" href="<?php echo esc_url( '' === $slug ? home_url( '/' ) : zp_route_url( $slug ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<div class="zp-pnav__cta">
				<?php if ( $logged ) : ?>
					<a class="zp-btn zp-btn--primary zp-btn--block" href="<?php echo esc_url( zp_url( 'dashboard' ) ); ?>">Open dashboard</a>
				<?php else : ?>
					<a class="zp-btn zp-btn--ghost zp-btn--block" href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a>
					<a class="zp-btn zp-btn--primary zp-btn--block" href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Get started</a>
				<?php endif; ?>
			</div>
		</nav>
		<div class="zp-phead__actions">
			<button class="zp-iconbtn zp-theme" id="zp-theme" type="button" title="Toggle dark / light mode" aria-label="Toggle dark / light mode">
				<span class="zp-theme__sun"><?php echo zp_icon( 'sun' ); // phpcs:ignore ?></span><span class="zp-theme__moon"><?php echo zp_icon( 'moon' ); // phpcs:ignore ?></span>
			</button>
			<?php if ( $logged ) : ?>
				<a class="zp-btn zp-btn--primary zp-btn--sm zp-phead__desk" href="<?php echo esc_url( zp_url( 'dashboard' ) ); ?>">Dashboard</a>
			<?php else : ?>
				<a class="zp-btn zp-btn--ghost zp-btn--sm zp-phead__desk" href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a>
				<a class="zp-btn zp-btn--primary zp-btn--sm" href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Get started</a>
			<?php endif; ?>
			<button class="zp-iconbtn zp-pnav-toggle" id="zp-pnav-toggle" type="button" aria-label="Menu"><?php echo zp_icon( 'menu' ); // phpcs:ignore ?></button>
		</div>
	</div>
</header>

<main>
	<?php if ( $flash ) : ?>
		<div class="zp-wrap" style="padding-top:18px"><div class="zp-alert zp-alert--<?php echo esc_attr( $flash['type'] ); ?>" role="status"><?php echo zp_icon( 'error' === $flash['type'] ? 'x' : 'check', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $flash['message'] ); ?></span></div></div>
	<?php endif; ?>
	<?php
	if ( $is_legal ) {
		$doc = $legal[ $view ];
		include ZP_DIR . 'templates/public/legal-page.php';
	} else {
		include ZP_DIR . 'templates/public/' . $view . '.php';
	}
	?>
</main>

<footer class="zp-pfoot">
	<div class="zp-wrap zp-pfoot__grid">
		<div class="zp-pfoot__brand">
			<a class="zp-brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo zp_logo( 30 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
			<p>Private proxies for teams and individuals. Fast delivery, fair pricing, real support.</p>
		</div>
		<div><h4>Product</h4>
			<a href="<?php echo esc_url( zp_route_url( 'pricing' ) ); ?>">Pricing</a>
			<a href="<?php echo esc_url( zp_url( 'buy' ) ); ?>">Buy proxies</a>
			<a href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a>
			<a href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Create account</a>
		</div>
		<div><h4>Company</h4>
			<a href="<?php echo esc_url( zp_route_url( 'about' ) ); ?>">About us</a>
			<a href="<?php echo esc_url( zp_route_url( 'contact' ) ); ?>">Contact</a>
			<a href="<?php echo esc_url( zp_route_url( 'faq' ) ); ?>">FAQ</a>
		</div>
		<div><h4>Legal</h4>
			<?php foreach ( $legal as $slug => $doc_link ) : ?>
				<a href="<?php echo esc_url( zp_route_url( $slug ) ); ?>"><?php echo esc_html( $doc_link['title'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
	<div class="zp-wrap zp-pfoot__base"><span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $brand ); ?>. All rights reserved.</span><span>Use of the service is subject to our <a href="<?php echo esc_url( zp_route_url( 'acceptable-use' ) ); ?>">Acceptable Use Policy</a>.</span></div>
</footer>
<?php ZP_Router::foot(); ?>
</body>
</html>
