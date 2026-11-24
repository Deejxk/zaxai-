<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$s       = zp_settings();
$has_key = '' !== trim( (string) $s['api_key'] );
$server  = isset( $_SERVER['SERVER_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_ADDR'] ) ) : '';
$toggle  = function ( $name, $label, $hint ) use ( $s ) {
	echo '<label class="zp-prefs"><div><strong>' . esc_html( $label ) . '</strong><span>' . esc_html( $hint ) . '</span></div><span class="zp-check"><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $s[ $name ] ), true, false ) . '></span></label>';
};
?>
<form method="post" class="zp-form" id="zp-settings">
	<?php zp_nonce_field( 'save_settings' ); ?>
	<div class="zp-grid2">
		<section class="zp-card zp-anim">
			<div class="zp-card__head"><h3>Provider connection</h3><?php echo zp_badge( $has_key ? 'active' : 'pending' ); // phpcs:ignore ?></div>
			<label class="zp-field"><span>proxy-ipv4.com API key</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="api_key" autocomplete="off" placeholder="<?php echo $has_key ? 'Saved. Enter a new key to replace it.' : 'Paste your API key'; ?>"></div>
				<small>Find it in your proxy-ipv4.com account under API. If the provider restricts API access by IP, allow this server<?php echo $server ? ': ' . esc_html( $server ) : ''; ?>.</small></label>
			<label class="zp-field"><span>Proxy types on sale</span></label>
			<div class="zp-row">
				<?php foreach ( zp_types() as $slug => $t ) : ?>
					<label class="zp-check"><input type="checkbox" name="types[]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, (array) $s['types'], true ) ); ?>><span><?php echo esc_html( $t['label'] ); ?></span></label>
				<?php endforeach; ?>
			</div>
			<label class="zp-field" style="margin-top:16px"><span>Rental periods on sale (days)</span><div class="zp-input"><input type="text" name="allowed_days" value="<?php echo esc_attr( $s['allowed_days'] ); ?>" placeholder="30,60,90"></div><small>Comma separated. Only these periods are offered for purchases and renewals. Leave empty to offer every period the provider has.</small></label>
			<button class="zp-btn zp-btn--soft" type="submit" name="zp_then" value="test" style="margin-top:16px"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Save &amp; test connection</button>
		</section>

		<section class="zp-card zp-anim" style="--d:1">
			<div class="zp-card__head"><h3>Pricing</h3></div>
			<p class="zp-muted" style="margin-bottom:14px">Exchange rate: 1 USD = <?php echo esc_html( zp_money( zp_fx() ) ); ?>. Set your rate and markup per proxy type, and compare with live provider prices, on the Pricing page.</p>
			<a class="zp-btn zp-btn--soft" href="<?php echo esc_url( zp_admin_url( 'pricing' ) ); ?>"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Open pricing</a>
		</section>
	</div>

	<div class="zp-grid2">
		<section class="zp-card zp-anim" style="--d:2">
			<div class="zp-card__head"><h3>Bank transfer deposits</h3></div>
			<div class="zp-fieldrow" style="margin-top:0">
				<label class="zp-field"><span>Minimum top-up (<?php echo esc_html( $s['currency_code'] ); ?>)</span><div class="zp-input"><input type="number" name="min_deposit" value="<?php echo esc_attr( $s['min_deposit'] ); ?>" min="0.5" step="0.5"></div></label>
				<label class="zp-field"><span>Maximum top-up (<?php echo esc_html( $s['currency_code'] ); ?>)</span><div class="zp-input"><input type="number" name="max_deposit" value="<?php echo esc_attr( $s['max_deposit'] ); ?>" min="1" step="1"></div></label>
			</div>
			<?php $toggle( 'manual_enabled', 'Bank transfer', 'Customers pay into your bank account, send the receipt on Telegram, and you approve it under Deposits.' ); ?>
			<div class="zp-fieldrow">
				<label class="zp-field"><span>Bank name</span><div class="zp-input"><input type="text" name="bank_name" value="<?php echo esc_attr( $s['bank_name'] ); ?>" maxlength="80" placeholder="e.g. Access Bank"></div></label>
				<label class="zp-field"><span>Account number</span><div class="zp-input"><input type="text" name="bank_account_number" value="<?php echo esc_attr( $s['bank_account_number'] ); ?>" maxlength="30" inputmode="numeric" placeholder="0123456789"></div></label>
			</div>
			<label class="zp-field"><span>Account name</span><div class="zp-input"><input type="text" name="bank_account_name" value="<?php echo esc_attr( $s['bank_account_name'] ); ?>" maxlength="120" placeholder="Name shown to customers"></div></label>
			<label class="zp-field"><span>Telegram username for receipts</span><div class="zp-input"><?php echo zp_icon( 'send', 18 ); // phpcs:ignore ?><input type="text" name="telegram" value="<?php echo esc_attr( $s['telegram'] ); ?>" maxlength="80" placeholder="@yourusername or https://t.me/yourusername"></div><small>After a transfer, customers get a button that opens a Telegram chat with you, with the amount and deposit reference already typed in.</small></label>
			<label class="zp-field"><span>Method name</span><div class="zp-input"><input type="text" name="manual_title" value="<?php echo esc_attr( $s['manual_title'] ); ?>"></div></label>
			<label class="zp-field"><span>Extra note shown under the bank details</span><textarea name="manual_instructions" rows="3" class="zp-textarea"><?php echo esc_textarea( $s['manual_instructions'] ); ?></textarea></label>
		</section>

		<section class="zp-card zp-anim" style="--d:3">
			<div class="zp-card__head"><h3>Paystack (card / bank)</h3><?php echo zp_badge( $s['paystack_enabled'] && $s['paystack_secret'] ? 'active' : 'closed' ); // phpcs:ignore ?></div>
			<?php $toggle( 'paystack_enabled', 'Enable Paystack', 'Instant, automatic wallet top-ups.' ); ?>
			<label class="zp-field"><span>Secret key</span><div class="zp-input"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><input type="password" name="paystack_secret" autocomplete="off" placeholder="<?php echo $s['paystack_secret'] ? 'Saved. Enter a new key to replace it.' : 'sk_live_…'; ?>"></div></label>
			<p class="zp-muted" style="font-size:13.5px">Customers are charged in <?php echo esc_html( $s['currency_code'] ); ?>, the same amount that is credited to their wallet.</p>
			<label class="zp-field"><span>Webhook URL (add in Paystack dashboard)</span><div class="zp-input"><input type="text" readonly value="<?php echo esc_attr( zp_route_url( 'zp-webhook', 'paystack' ) ); ?>" onclick="this.select()"></div></label>
		</section>
	</div>

	<section class="zp-card zp-anim" style="--d:4">
		<div class="zp-card__head"><h3>General</h3></div>
		<div class="zp-fieldrow" style="margin-top:0">
			<label class="zp-field"><span>Brand name</span><div class="zp-input"><input type="text" name="brand" value="<?php echo esc_attr( $s['brand'] ); ?>" maxlength="40" required></div></label>
			<label class="zp-field"><span>Support email (ticket alerts, contact form)</span><div class="zp-input"><input type="email" name="support_email" value="<?php echo esc_attr( $s['support_email'] ); ?>"></div></label>
		</div>
		<?php $toggle( 'registration', 'Allow new sign-ups', 'Turn off to close registration.' ); ?>
		<?php $toggle( 'landing', 'Public website', 'Serve the ZaxProxy home, pricing, FAQ, contact and legal pages instead of the WordPress theme.' ); ?>
	</section>

	<div class="zp-savebar"><button class="zp-btn zp-btn--primary zp-btn--lg" type="submit">Save settings</button></div>
</form>
