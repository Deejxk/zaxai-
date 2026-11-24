<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user @var float $balance */
$types  = zp_enabled_types();
$active = sanitize_key( zp_query( 'type' ) );
if ( ! isset( $types[ $active ] ) ) {
	$active = (string) key( $types );
}
if ( ! $types ) {
	echo zp_empty( 'empty-proxies', 'Store is being set up', 'Proxy plans are not available yet. Please check back soon.' ); // phpcs:ignore
	return;
}
?>
<form method="post" class="zp-buy" id="zp-buy" data-type="<?php echo esc_attr( $active ); ?>" novalidate>
	<?php zp_nonce_field( 'buy' ); ?>
	<input type="hidden" name="type" value="<?php echo esc_attr( $active ); ?>">

	<div class="zp-buy__main">
		<section class="zp-card zp-anim">
			<div class="zp-step"><b>1</b><h3>Proxy type</h3></div>
			<div class="zp-typepick">
				<?php foreach ( $types as $slug => $t ) : ?>
					<button type="button" class="zp-typepick__item<?php echo $slug === $active ? ' is-active' : ''; ?>" data-type="<?php echo esc_attr( $slug ); ?>">
						<span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
						<strong><?php echo esc_html( $t['label'] ); ?></strong>
						<span><?php echo esc_html( $t['desc'] ); ?></span>
					</button>
				<?php endforeach; ?>
			</div>
		</section>

		<section class="zp-card zp-anim" style="--d:1">
			<div class="zp-step"><b>2</b><h3>Location</h3></div>
			<div class="zp-input zp-input--search"><?php echo zp_icon( 'search', 18 ); // phpcs:ignore ?><input type="search" id="zp-country-search" placeholder="Search countries" autocomplete="off"></div>
			<div class="zp-countries" id="zp-countries"><div class="zp-skeleton-grid"><i></i><i></i><i></i><i></i><i></i><i></i></div></div>
			<input type="hidden" name="country" value="">
			<div class="zp-fieldrow" id="zp-mobile-fields" hidden>
				<label class="zp-field"><span>Mobile operator</span><select name="operator" class="zp-select"></select></label>
				<label class="zp-field"><span>IP rotation</span><select name="rotation" class="zp-select"></select></label>
			</div>
		</section>

		<section class="zp-card zp-anim" style="--d:2">
			<div class="zp-step"><b>3</b><h3>Plan</h3></div>
			<label class="zp-field"><span>Rental period</span></label>
			<div class="zp-pills" id="zp-days"></div>
			<input type="hidden" name="days" value="">
			<div class="zp-fieldrow">
				<label class="zp-field"><span>Quantity</span>
					<div class="zp-qty"><button type="button" data-step="-1" aria-label="Decrease">−</button><input type="number" name="qty" value="1" min="1" max="1000" inputmode="numeric"><button type="button" data-step="1" aria-label="Increase">+</button></div>
					<small id="zp-qty-hint"></small>
				</label>
				<label class="zp-field" id="zp-protocol-field" hidden><span>Protocol</span>
					<select name="protocol" class="zp-select"><option value="SOCKS5">SOCKS5</option><option value="HTTPS">HTTP(S)</option></select>
				</label>
			</div>
		</section>

		<section class="zp-card zp-anim" style="--d:3">
			<div class="zp-step"><b>4</b><h3>Usage &amp; access</h3></div>
			<div class="zp-fieldrow">
				<label class="zp-field"><span>What will you use it for?</span>
					<select name="goal" class="zp-select">
						<?php foreach ( zp_goals() as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</label>
				<label class="zp-field" id="zp-goal-text" hidden><span>Describe your use case</span><div class="zp-input"><input type="text" name="goal_text" maxlength="80" placeholder="e.g. price monitoring"></div></label>
			</div>
			<label class="zp-field"><span>Authorization</span></label>
			<div class="zp-seg">
				<label><input type="radio" name="auth_type" value="login" checked><span><?php echo zp_icon( 'lock', 16 ); // phpcs:ignore ?>Username &amp; password</span></label>
				<label><input type="radio" name="auth_type" value="ip"><span><?php echo zp_icon( 'shield', 16 ); // phpcs:ignore ?>IP whitelist</span></label>
			</div>
			<label class="zp-field" id="zp-authip" hidden><span>Your IPv4 address to whitelist</span><div class="zp-input"><input type="text" name="auth_ip" placeholder="203.0.113.10" inputmode="decimal"></div></label>
		</section>
	</div>

	<aside class="zp-buy__side">
		<div class="zp-card zp-summary zp-anim" style="--d:2">
			<h3>Order summary</h3>
			<dl>
				<div><dt>Type</dt><dd id="zp-sum-type"><?php echo esc_html( $types[ $active ]['label'] ); ?></dd></div>
				<div><dt>Location</dt><dd id="zp-sum-country">—</dd></div>
				<div><dt>Period</dt><dd id="zp-sum-days">—</dd></div>
				<div><dt>Quantity</dt><dd id="zp-sum-qty">1</dd></div>
				<div><dt>Price per proxy</dt><dd id="zp-sum-unit">—</dd></div>
			</dl>
			<div class="zp-summary__total"><span>Total</span><strong id="zp-sum-total">—</strong></div>
			<p class="zp-summary__note" id="zp-sum-note">Choose a location and period to see your price.</p>
			<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit" id="zp-buy-btn" disabled><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Pay from wallet</button>
			<div class="zp-summary__bal"><?php echo zp_icon( 'wallet', 16 ); // phpcs:ignore ?>Wallet: <strong><?php echo esc_html( zp_money( $balance ) ); ?></strong><a href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">Add funds</a></div>
		</div>
		<ul class="zp-assure zp-anim" style="--d:3">
			<li><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Delivered to your dashboard automatically</li>
			<li><?php echo zp_icon( 'shield', 18 ); // phpcs:ignore ?>Private IPs, HTTP(S) and SOCKS5</li>
			<li><?php echo zp_icon( 'refresh', 18 ); // phpcs:ignore ?>Extend any time before expiry</li>
		</ul>
	</aside>

	<div class="zp-buybar">
		<div><span>Total</span><strong id="zp-bar-total">—</strong></div>
		<button class="zp-btn zp-btn--primary" type="submit" id="zp-bar-btn" disabled>Pay from wallet</button>
	</div>
</form>
