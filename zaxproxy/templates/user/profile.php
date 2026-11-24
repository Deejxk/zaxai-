<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user */
?>
<div class="zp-grid2">
	<section class="zp-card zp-anim">
		<div class="zp-card__head"><h3>Profile</h3></div>
		<div class="zp-profile">
			<span class="zp-avatar zp-avatar--lg"><?php echo esc_html( strtoupper( mb_substr( $user->display_name, 0, 1 ) ) ); ?></span>
			<div><strong><?php echo esc_html( $user->display_name ); ?></strong><span>Member since <?php echo esc_html( zp_date( $user->user_registered, false ) ); ?></span></div>
		</div>
		<form method="post" class="zp-form">
			<?php zp_nonce_field( 'profile' ); ?>
			<label class="zp-field"><span>Full name</span><div class="zp-input"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?><input type="text" name="name" value="<?php echo esc_attr( $user->display_name ); ?>" required></div></label>
			<label class="zp-field"><span>Email</span><div class="zp-input"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?><input type="email" name="email" value="<?php echo esc_attr( $user->user_email ); ?>" required></div></label>
			<button class="zp-btn zp-btn--primary" type="submit">Save changes</button>
		</form>
	</section>

	<section class="zp-card zp-anim" style="--d:1">
		<div class="zp-card__head"><h3>Password</h3></div>
		<form method="post" class="zp-form">
			<?php zp_nonce_field( 'password' ); ?>
			<label class="zp-field"><span>Current password</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="current" autocomplete="current-password" required></div></label>
			<label class="zp-field"><span>New password</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="password" autocomplete="new-password" minlength="8" required><button type="button" class="zp-input__btn" data-zp-reveal aria-label="Show password"><?php echo zp_icon( 'eye', 18 ); // phpcs:ignore ?></button></div><small>At least 8 characters.</small></label>
			<button class="zp-btn zp-btn--primary" type="submit">Change password</button>
		</form>
	</section>
</div>

<section class="zp-card zp-anim" style="--d:2">
	<div class="zp-card__head"><h3>App &amp; appearance</h3></div>
	<div class="zp-prefs">
		<div><strong>Theme</strong><span>Switch between dark and light mode.</span></div>
		<button class="zp-btn zp-btn--soft" type="button" data-zp-theme-toggle><?php echo zp_icon( 'moon', 18 ); // phpcs:ignore ?>Toggle theme</button>
	</div>
	<div class="zp-prefs">
		<div><strong>Install the app</strong><span>Add <?php echo esc_html( zp_get( 'brand' ) ); ?> to your home screen on iPhone or Android for one-tap access.</span></div>
		<button class="zp-btn zp-btn--soft" type="button" data-zp-install><?php echo zp_icon( 'download', 18 ); // phpcs:ignore ?>Install</button>
	</div>
	<div class="zp-prefs">
		<div><strong>Sign out</strong><span>End your session on this device.</span></div>
		<a class="zp-btn zp-btn--ghost" href="<?php echo esc_url( add_query_arg( '_zpnonce', wp_create_nonce( 'zp_logout' ), zp_url( 'logout' ) ) ); ?>"><?php echo zp_icon( 'logout', 18 ); // phpcs:ignore ?>Sign out</a>
	</div>
</section>
