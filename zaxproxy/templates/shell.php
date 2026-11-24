<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $shell @var string $view @var WP_User $user */
$brand   = zp_get( 'brand' );
$url     = $shell['url'];
$flash   = zp_take_flash();
$initial = strtoupper( mb_substr( $user->display_name ? $user->display_name : $user->user_login, 0, 1 ) );
$home    = $shell['admin'] ? zp_admin_url() : zp_url( 'dashboard' );
$logout  = add_query_arg( '_zpnonce', wp_create_nonce( 'zp_logout' ), zp_url( 'logout' ) );
?>
<!doctype html>
<html lang="en">
<head>
<?php ZP_Router::head( $shell['title'] ); ?>
</head>
<body class="zp-body<?php echo $shell['admin'] ? ' zp-is-admin' : ''; ?>">
<?php ZP_Router::splash(); ?>
<div class="zp-shell">
	<aside class="zp-side">
		<a class="zp-brand" href="<?php echo esc_url( $home ); ?>"><?php echo zp_logo(); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?><?php echo $shell['admin'] ? '<em>Admin</em>' : ''; ?></a>
		<nav class="zp-nav" aria-label="Main">
			<?php foreach ( $shell['nav'] as $slug => $item ) : ?>
				<a class="zp-nav__item<?php echo $slug === $view ? ' is-active' : ''; ?>" href="<?php echo esc_url( $url( 'dashboard' === $slug && $shell['admin'] ? '' : $slug ) ); ?>">
					<?php echo zp_icon( $item[1] ); // phpcs:ignore ?><span><?php echo esc_html( $item[0] ); ?></span>
					<?php if ( ! empty( $item[2] ) ) : ?><b class="zp-count"><?php echo (int) $item[2]; ?></b><?php endif; ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<div class="zp-side__foot">
			<?php if ( $shell['admin'] ) : ?>
				<a class="zp-nav__item" href="<?php echo esc_url( zp_url( 'dashboard' ) ); ?>"><?php echo zp_icon( 'arrow' ); // phpcs:ignore ?><span>Open user panel</span></a>
			<?php else : ?>
				<div class="zp-sidecard">
					<div class="zp-sidecard__art"><?php echo zp_illustration( 'support' ); // phpcs:ignore ?></div>
					<strong>Need a hand?</strong>
					<p>Our team answers tickets around the clock.</p>
					<a class="zp-btn zp-btn--soft zp-btn--sm" href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>">Contact support</a>
				</div>
			<?php endif; ?>
		</div>
	</aside>

	<div class="zp-main">
		<header class="zp-top">
			<a class="zp-top__logo" href="<?php echo esc_url( $home ); ?>" aria-label="<?php echo esc_attr( $brand ); ?>"><?php echo zp_logo( 32 ); // phpcs:ignore ?></a>
			<h1 class="zp-top__title"><?php echo esc_html( $shell['title'] ); ?></h1>
			<div class="zp-top__actions">
				<?php echo $shell['chip']; // phpcs:ignore ?>
				<?php if ( current_user_can( 'manage_options' ) ) : ?>
					<a class="zp-iconbtn zp-switch" href="<?php echo esc_url( $shell['admin'] ? zp_url( 'dashboard' ) : zp_admin_url() ); ?>" title="<?php echo $shell['admin'] ? 'Switch to user panel' : 'Switch to admin panel'; ?>" aria-label="<?php echo $shell['admin'] ? 'Switch to user panel' : 'Switch to admin panel'; ?>"><?php echo zp_icon( $shell['admin'] ? 'user' : 'shield' ); // phpcs:ignore ?></a>
				<?php endif; ?>
				<button class="zp-iconbtn" id="zp-install" type="button" hidden title="Install app" aria-label="Install app"><?php echo zp_icon( 'download' ); // phpcs:ignore ?></button>
				<button class="zp-iconbtn zp-theme" id="zp-theme" type="button" title="Toggle dark / light mode" aria-label="Toggle dark / light mode">
					<span class="zp-theme__sun"><?php echo zp_icon( 'sun' ); // phpcs:ignore ?></span><span class="zp-theme__moon"><?php echo zp_icon( 'moon' ); // phpcs:ignore ?></span>
				</button>
				<div class="zp-menu">
					<button class="zp-avatar" type="button" data-zp-menu aria-haspopup="true" aria-label="Account menu"><?php echo esc_html( $initial ); ?></button>
					<div class="zp-menu__panel" hidden>
						<div class="zp-menu__head"><strong><?php echo esc_html( $user->display_name ); ?></strong><span><?php echo esc_html( $user->user_email ); ?></span></div>
						<a href="<?php echo esc_url( zp_url( 'profile' ) ); ?>"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?>Account</a>
						<?php if ( current_user_can( 'manage_options' ) && ! $shell['admin'] ) : ?>
							<a href="<?php echo esc_url( zp_admin_url() ); ?>"><?php echo zp_icon( 'shield', 18 ); // phpcs:ignore ?>Admin panel</a>
						<?php endif; ?>
						<a href="<?php echo esc_url( $logout ); ?>"><?php echo zp_icon( 'logout', 18 ); // phpcs:ignore ?>Sign out</a>
					</div>
				</div>
			</div>
		</header>

		<main class="zp-content" id="zp-content">
			<?php if ( $flash ) : ?>
				<div class="zp-alert zp-alert--<?php echo esc_attr( $flash['type'] ); ?>" role="status"><?php echo zp_icon( 'error' === $flash['type'] ? 'x' : 'check', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $flash['message'] ); ?></span></div>
			<?php endif; ?>
			<?php include $shell['template']; ?>
		</main>
	</div>
</div>

<nav class="zp-tabbar" aria-label="Quick navigation">
	<?php
	$i = 0;
	foreach ( $shell['tabs'] as $slug => $item ) :
		$is_fab = $slug === $shell['fab'];
		?>
		<a class="zp-tab<?php echo $slug === $view ? ' is-active' : ''; ?><?php echo $is_fab ? ' zp-tab--fab' : ''; ?>" href="<?php echo esc_url( $url( 'dashboard' === $slug && $shell['admin'] ? '' : $slug ) ); ?>">
			<span class="zp-tab__ico"><?php echo zp_icon( $item[1], 22 ); // phpcs:ignore ?></span><span class="zp-tab__label"><?php echo esc_html( $item[0] ); ?></span>
		</a>
	<?php endforeach; ?>
	<button class="zp-tab<?php echo isset( $shell['tabs'][ $view ] ) ? '' : ' is-active'; ?>" type="button" data-zp-sheet="zp-more">
		<span class="zp-tab__ico"><?php echo zp_icon( 'menu', 22 ); // phpcs:ignore ?></span><span class="zp-tab__label">More</span>
	</button>
</nav>

<div class="zp-sheet" id="zp-more" hidden>
	<div class="zp-sheet__backdrop" data-zp-close></div>
	<div class="zp-sheet__panel" role="dialog" aria-label="Menu">
		<div class="zp-sheet__grip"></div>
		<div class="zp-sheet__grid">
			<?php foreach ( $shell['nav'] as $slug => $item ) : ?>
				<a class="<?php echo $slug === $view ? 'is-active' : ''; ?>" href="<?php echo esc_url( $url( 'dashboard' === $slug && $shell['admin'] ? '' : $slug ) ); ?>">
					<span><?php echo zp_icon( $item[1], 22 ); // phpcs:ignore ?></span><?php echo esc_html( $item[0] ); ?>
				</a>
			<?php endforeach; ?>
			<a href="<?php echo esc_url( $logout ); ?>"><span><?php echo zp_icon( 'logout', 22 ); // phpcs:ignore ?></span>Sign out</a>
		</div>
	</div>
</div>

<div class="zp-sheet zp-sheet--center" id="zp-ios-install" hidden>
	<div class="zp-sheet__backdrop" data-zp-close></div>
	<div class="zp-sheet__panel" role="dialog" aria-label="Install app">
		<div class="zp-sheet__grip"></div>
		<h3>Install <?php echo esc_html( $brand ); ?></h3>
		<ol class="zp-steps">
			<li>Tap the <strong>Share</strong> button in Safari's toolbar.</li>
			<li>Choose <strong>Add to Home Screen</strong>.</li>
			<li>Tap <strong>Add</strong>. The app appears on your home screen.</li>
		</ol>
		<button class="zp-btn zp-btn--primary zp-btn--block" type="button" data-zp-close>Got it</button>
	</div>
</div>
<?php ZP_Router::foot( ! $shell['admin'], $shell['admin'] ); ?>
</body>
</html>
