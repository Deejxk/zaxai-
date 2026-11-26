<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** The store's own "page not found" screen. */
$store = function_exists( 'zp_domain_store' ) ? zp_domain_store() : null;
if ( $store ) {
	$GLOBALS['zp_reseller_ctx'] = $store;
}
$home = home_url( '/' );
?>
<!doctype html>
<html lang="en">
<head>
<?php ZP_Router::head( 'Page not found' ); ?>
<meta name="robots" content="noindex">
<style>
.zp-nf{min-height:100vh;min-height:100dvh;display:grid;place-items:center;padding:24px;text-align:center}
.zp-nf__box{max-width:440px;display:flex;flex-direction:column;align-items:center;gap:12px}
.zp-nf__code{font-size:84px;font-weight:800;letter-spacing:-.05em;line-height:1;background:var(--zp-grad);-webkit-background-clip:text;background-clip:text;color:transparent}
.zp-nf h1{font-size:24px;font-weight:800}
.zp-nf p{color:var(--zp-text-2);line-height:1.55}
.zp-nf__row{display:flex;gap:10px;flex-wrap:wrap;justify-content:center;margin-top:10px}
</style>
</head>
<body class="zp-body">
<main class="zp-nf">
	<div class="zp-nf__box">
		<a class="zp-brand" href="<?php echo esc_url( $home ); ?>" style="padding:0 0 10px"><?php echo zp_logo( 40 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
		<div class="zp-nf__code" aria-hidden="true">404</div>
		<h1>Page not found</h1>
		<p>The page you are looking for does not exist or has been moved.</p>
		<div class="zp-nf__row">
			<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( $home ); ?>">Go to home page</a>
			<a class="zp-btn zp-btn--ghost" href="<?php echo esc_url( zp_url( is_user_logged_in() ? 'dashboard' : 'login' ) ); ?>"><?php echo is_user_logged_in() ? 'Open dashboard' : 'Sign in'; ?></a>
		</div>
	</div>
</main>
</body>
</html>
