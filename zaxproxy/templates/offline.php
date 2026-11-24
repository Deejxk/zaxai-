<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<?php ZP_Router::head( 'Offline' ); ?>
</head>
<body class="zp-body">
<div class="zp-content" style="min-height:100dvh;justify-content:center;align-items:center">
	<div class="zp-empty">
		<div class="zp-empty__art"><?php echo zp_illustration( 'empty-proxies' ); // phpcs:ignore ?></div>
		<h3>You are offline</h3>
		<p>Check your connection and try again. Your proxies keep running in the meantime.</p>
		<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'dashboard' ) ); ?>">Try again</a>
	</div>
</div>
</body>
</html>
