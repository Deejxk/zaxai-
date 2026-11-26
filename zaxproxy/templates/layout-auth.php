<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var string $view */
$brand  = zp_get( 'brand' );
$flash  = zp_take_flash();
$titles = array( 'login' => 'Sign in', 'register' => 'Create account', 'forgot' => 'Reset password', 'reset' => 'New password', 'admin-login' => 'Admin sign in' );
$admin  = 'admin-login' === $view;
?>
<!doctype html>
<html lang="en">
<head>
<?php
// Sign in and sign up are pages people search for by name, so search engines may list them.
$zp_auth_desc = array(
	'login'    => 'Sign in to your ' . $brand . ' account to buy, renew and manage your private proxies.',
	'register' => 'Create a free ' . $brand . ' account and get private IPv4, IPv6, ISP and mobile proxies in minutes.',
);
ZP_Router::head( $titles[ $view ], isset( $zp_auth_desc[ $view ] ) && empty( $_GET ) && ! zp_reseller_ctx(), isset( $zp_auth_desc[ $view ] ) ? $zp_auth_desc[ $view ] : '' ); // phpcs:ignore
?>
</head>
<body class="zp-body zp-auth-body">
<?php ZP_Router::splash(); ?>
<div class="zp-auth">
	<section class="zp-auth__art" aria-hidden="true">
		<a class="zp-brand zp-brand--light" href="<?php echo esc_url( zp_reseller_ctx() ? ZP_Resellers::store_url( zp_reseller_ctx() ) : home_url( '/' ) ); ?>"><?php echo zp_logo( 34 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
		<div class="zp-auth__illus"><?php echo zp_illustration( 'network' ); // phpcs:ignore ?></div>
		<div class="zp-auth__pitch">
			<h2><?php echo $admin ? 'Control room' : 'Private proxies, ready in seconds.'; ?></h2>
			<p><?php echo $admin ? 'Manage customers, orders, deposits and pricing from one place.' : 'IPv4, IPv6, ISP and mobile IPs across the globe. Pay from your wallet, manage everything from one dashboard.'; ?></p>
		</div>
	</section>
	<section class="zp-auth__form">
		<div class="zp-auth__bar">
			<a class="zp-brand zp-auth__mlogo" href="<?php echo esc_url( zp_reseller_ctx() ? ZP_Resellers::store_url( zp_reseller_ctx() ) : home_url( '/' ) ); ?>"><?php echo zp_logo( 32 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
			<button class="zp-iconbtn zp-theme" id="zp-theme" type="button" title="Toggle dark / light mode" aria-label="Toggle dark / light mode">
				<span class="zp-theme__sun"><?php echo zp_icon( 'sun' ); // phpcs:ignore ?></span><span class="zp-theme__moon"><?php echo zp_icon( 'moon' ); // phpcs:ignore ?></span>
			</button>
		</div>
		<div class="zp-auth__card">
			<?php if ( $flash ) : ?>
				<div class="zp-alert zp-alert--<?php echo esc_attr( $flash['type'] ); ?>" role="status"><?php echo zp_icon( 'error' === $flash['type'] ? 'x' : 'check', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $flash['message'] ); ?></span></div>
			<?php endif; ?>

			<?php if ( 'login' === $view ) : ?>
				<h1>Welcome back</h1>
				<p class="zp-muted">Sign in to manage your proxies.</p>
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'login' ); ?>
					<label class="zp-field"><span>Email or username</span><div class="zp-input"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?><input type="text" name="login" autocomplete="username" required autofocus></div></label>
					<label class="zp-field"><span>Password</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="password" autocomplete="current-password" required><button type="button" class="zp-input__btn" data-zp-reveal aria-label="Show password"><?php echo zp_icon( 'eye', 18 ); // phpcs:ignore ?></button></div></label>
					<div class="zp-row zp-row--between">
						<label class="zp-check"><input type="checkbox" name="remember" value="1" checked><span>Keep me signed in</span></label>
						<a href="<?php echo esc_url( zp_url( 'forgot' ) ); ?>">Forgot password?</a>
					</div>
					<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit">Sign in</button>
				</form>
				<?php if ( zp_get( 'registration' ) ) : ?>
					<p class="zp-auth__alt">New here? <a href="<?php echo esc_url( zp_url( 'register' ) ); ?>">Create an account</a></p>
				<?php endif; ?>

			<?php elseif ( 'register' === $view ) : ?>
				<h1>Create your account</h1>
				<p class="zp-muted">It takes less than a minute.</p>
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'register' ); ?>
					<input type="text" name="website" class="zp-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					<label class="zp-field"><span>Full name</span><div class="zp-input"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?><input type="text" name="name" autocomplete="name" required autofocus></div></label>
					<label class="zp-field"><span>Email</span><div class="zp-input"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?><input type="email" name="email" autocomplete="email" required></div></label>
					<label class="zp-field"><span>Password</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="password" autocomplete="new-password" minlength="8" required><button type="button" class="zp-input__btn" data-zp-reveal aria-label="Show password"><?php echo zp_icon( 'eye', 18 ); // phpcs:ignore ?></button></div><small>At least 8 characters.</small></label>
					<label class="zp-check"><input type="checkbox" name="terms" value="1" required><span>I agree to use the service lawfully and accept the terms of service.</span></label>
					<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit">Create account</button>
				</form>
				<p class="zp-auth__alt">Already have an account? <a href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Sign in</a></p>

			<?php elseif ( 'forgot' === $view ) : ?>
				<h1>Forgot your password?</h1>
				<p class="zp-muted">Enter your email and we will send you a reset link.</p>
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'forgot' ); ?>
					<label class="zp-field"><span>Email</span><div class="zp-input"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?><input type="email" name="email" autocomplete="email" required autofocus></div></label>
					<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit">Send reset link</button>
				</form>
				<p class="zp-auth__alt"><a href="<?php echo esc_url( zp_url( 'login' ) ); ?>">Back to sign in</a></p>

			<?php elseif ( 'reset' === $view ) : ?>
				<h1>Choose a new password</h1>
				<p class="zp-muted">Make it at least 8 characters.</p>
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'reset' ); ?>
					<input type="hidden" name="key" value="<?php echo esc_attr( zp_query( 'key' ) ); ?>">
					<input type="hidden" name="login" value="<?php echo esc_attr( zp_query( 'login' ) ); ?>">
					<label class="zp-field"><span>New password</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="password" autocomplete="new-password" minlength="8" required autofocus><button type="button" class="zp-input__btn" data-zp-reveal aria-label="Show password"><?php echo zp_icon( 'eye', 18 ); // phpcs:ignore ?></button></div></label>
					<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit">Update password</button>
				</form>

			<?php else : ?>
				<h1>Admin sign in</h1>
				<p class="zp-muted">Restricted area. Administrators only.</p>
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'admin_login' ); ?>
					<label class="zp-field"><span>Email or username</span><div class="zp-input"><?php echo zp_icon( 'shield', 18 ); // phpcs:ignore ?><input type="text" name="login" autocomplete="username" required autofocus></div></label>
					<label class="zp-field"><span>Password</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="password" autocomplete="current-password" required><button type="button" class="zp-input__btn" data-zp-reveal aria-label="Show password"><?php echo zp_icon( 'eye', 18 ); // phpcs:ignore ?></button></div></label>
					<label class="zp-check"><input type="checkbox" name="remember" value="1"><span>Keep me signed in</span></label>
					<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit">Sign in to admin</button>
				</form>
			<?php endif; ?>
		</div>
	</section>
</div>
<?php ZP_Router::foot(); ?>
</body>
</html>
