<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$s       = zp_settings();
$types   = zp_types();
$type    = sanitize_key( zp_query( 'type', 'ipv4' ) );
$type    = isset( $types[ $type ] ) ? $type : 'ipv4';
$has_key = '' !== trim( (string) $s['api_key'] );
$opts    = $has_key ? ZP_Orders::options( $type ) : new WP_Error( 'zp_config', 'Add your API key in Settings to load live provider prices.' );
$country = strtoupper( preg_replace( '/[^A-Za-z]/', '', zp_query( 'country' ) ) );
$rows    = array();
$error   = is_wp_error( $opts ) ? $opts->get_error_message() : '';
$qty     = 'ipv6' === $type ? 10 : 1;
$pct     = (float) $s[ 'markup_' . $type ];

if ( ! $error && $opts['countries'] ) {
	$codes = wp_list_pluck( $opts['countries'], 'code' );
	if ( ! in_array( $country, $codes, true ) ) {
		$country = in_array( 'USA', $codes, true ) ? 'USA' : $codes[0];
	}
	$cache_key = 'zp_pricelist3_' . md5( $type . $country . $s['api_key'] );
	$costs     = isset( $_GET['refresh'] ) ? false : get_transient( $cache_key ); // phpcs:ignore
	if ( ! is_array( $costs ) ) {
		$costs = array();
		foreach ( $opts['days'] as $d ) {
			$params = array( 'proxyType' => $type, 'days' => $d, 'goal' => 'instagram', 'country' => $country, 'count' => $qty );
			if ( 'mobile' === $type && ! empty( $opts['tariffs'][ $country ][0] ) ) {
				$op                       = $opts['tariffs'][ $country ][0];
				$params['mobileOperator'] = $op['name'];
				$params['rotationTime']   = $op['rotation'] ? $op['rotation'][0] : 0;
			}
			$res = ZP_API::order_price( $params );
			if ( is_wp_error( $res ) ) {
				$error = $res->get_error_message();
				break;
			}
			$costs[ $d ] = (float) $res['amount'];
		}
		if ( ! $error ) {
			set_transient( $cache_key, $costs, 30 * MINUTE_IN_SECONDS );
		}
	}
	$rows = array_intersect_key( $costs, array_flip( zp_allowed_days() ) );
}
?>
<form method="post" class="zp-form">
	<?php zp_nonce_field( 'save_pricing' ); ?>
	<div class="zp-grid2">
		<section class="zp-card zp-anim">
			<div class="zp-card__head"><h3>Currency &amp; exchange rate</h3></div>
			<p class="zp-muted" style="margin-bottom:14px">The provider bills you in US dollars. Customers see prices and hold their wallet in your store currency.</p>
			<label class="zp-field"><span>Exchange rate: 1 USD equals</span><div class="zp-input zp-input--amount"><b><?php echo esc_html( $s['currency_symbol'] ); ?></b><input type="number" name="fx_rate" value="<?php echo esc_attr( $s['fx_rate'] ); ?>" min="0.01" step="0.01" required></div><small>Use the rate you actually pay when funding your provider account, so your markup stays true profit.</small></label>
			<div class="zp-fieldrow">
				<label class="zp-field"><span>Currency code</span><div class="zp-input"><input type="text" name="currency_code" value="<?php echo esc_attr( $s['currency_code'] ); ?>" maxlength="3" required></div></label>
				<label class="zp-field"><span>Symbol</span><div class="zp-input"><input type="text" name="currency_symbol" value="<?php echo esc_attr( $s['currency_symbol'] ); ?>" maxlength="4" required></div></label>
			</div>
		</section>
		<section class="zp-card zp-anim" style="--d:1">
			<div class="zp-card__head"><h3>Your markup</h3></div>
			<p class="zp-muted" style="margin-bottom:14px">Percentage added on top of the provider price for each proxy type.</p>
			<div class="zp-fieldrow" style="margin-top:0">
				<?php foreach ( $types as $slug => $t ) : ?>
					<label class="zp-field"><span><?php echo esc_html( $t['label'] ); ?> markup (%)</span><div class="zp-input"><input type="number" name="markup_<?php echo esc_attr( $slug ); ?>" value="<?php echo esc_attr( $s[ 'markup_' . $slug ] ); ?>" min="0" max="1000" step="0.1" required></div></label>
				<?php endforeach; ?>
			</div>
			<label class="zp-field" style="margin-top:14px"><span>Reseller markup (%), all proxy types</span><div class="zp-input"><?php echo zp_icon( 'users', 18 ); // phpcs:ignore ?><input type="number" name="reseller_markup" value="<?php echo esc_attr( $s['reseller_markup'] ); ?>" min="0" max="1000" step="0.1" required></div><small>The lower rate for accounts with a reseller store. It is what a reseller pays for their own orders and what their wallet is charged for each order in their store. Their customers pay this plus the reseller's own markup.</small></label>
			<div class="zp-fieldrow">
				<label class="zp-field"><span>Reseller activation fee (<?php echo esc_html( $s['currency_code'] ); ?>)</span><div class="zp-input"><input type="number" name="reseller_fee" value="<?php echo esc_attr( $s['reseller_fee'] ); ?>" min="0" step="1" required></div><small>One-time, includes the first 30 days.</small></label>
				<label class="zp-field"><span>Reseller monthly fee (<?php echo esc_html( $s['currency_code'] ); ?>)</span><div class="zp-input"><input type="number" name="reseller_monthly" value="<?php echo esc_attr( $s['reseller_monthly'] ); ?>" min="0" step="1" required></div><small>Auto-charged from the wallet every 30 days.</small></label>
			</div>
			<div class="zp-callout" style="margin-top:16px">Selling price = provider price (USD) × exchange rate × (1 + markup %), rounded up to the next whole <?php echo esc_html( $s['currency_code'] ); ?>.</div>
		</section>
	</div>
	<div class="zp-row" style="justify-content:flex-end"><button class="zp-btn zp-btn--primary zp-btn--lg" type="submit">Save pricing</button></div>
</form>

<section class="zp-card zp-anim" style="--d:2">
	<div class="zp-card__head"><h3>Live API pricing</h3>
		<a class="zp-btn zp-btn--soft zp-btn--sm" href="<?php echo esc_url( zp_admin_url( 'pricing', array( 'type' => $type, 'country' => $country, 'refresh' => 1 ) ) ); ?>"><?php echo zp_icon( 'refresh', 16 ); // phpcs:ignore ?>Refresh</a>
	</div>
	<form class="zp-toolbar" method="get" action="<?php echo esc_url( zp_admin_url( 'pricing' ) ); ?>" style="margin-bottom:14px">
		<?php if ( ! zp_pretty() ) : ?><input type="hidden" name="zp" value="<?php echo esc_attr( ZP_ADMIN_BASE ); ?>"><input type="hidden" name="zpp" value="pricing"><?php endif; ?>
		<div class="zp-pills zp-pills--sm">
			<?php foreach ( $types as $slug => $t ) : ?>
				<a class="zp-pill<?php echo $slug === $type ? ' is-active' : ''; ?>" href="<?php echo esc_url( zp_admin_url( 'pricing', array( 'type' => $slug ) ) ); ?>"><?php echo esc_html( $t['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
		<?php if ( ! is_wp_error( $opts ) ) : ?>
			<input type="hidden" name="type" value="<?php echo esc_attr( $type ); ?>">
			<select name="country" class="zp-select zp-select--sm" onchange="this.form.submit()" aria-label="Country">
				<?php foreach ( $opts['countries'] as $c ) : ?>
					<option value="<?php echo esc_attr( $c['code'] ); ?>" <?php selected( $country, $c['code'] ); ?>><?php echo esc_html( $c['flag'] . ' ' . $c['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		<?php endif; ?>
	</form>
	<?php if ( $error ) : ?>
		<div class="zp-alert zp-alert--error"><?php echo zp_icon( 'x', 18 ); // phpcs:ignore ?><span><?php echo esc_html( $error ); ?></span></div>
	<?php elseif ( $rows ) : ?>
		<div class="zp-tablewrap"><table class="zp-table">
			<thead><tr><th>Period</th><th>Quantity</th><th class="zp-right">API price (USD)</th><th class="zp-right">API price (<?php echo esc_html( $s['currency_code'] ); ?>)</th><th class="zp-right">Markup</th><th class="zp-right">Your price</th><th class="zp-right">Your profit</th><th class="zp-right">Reseller price</th><th class="zp-right">Profit from reseller</th></tr></thead>
			<tbody>
			<?php
			foreach ( $rows as $d => $cost ) :
				$local = $cost * zp_fx();
				$sell  = ZP_Orders::markup( $cost, $type );
				$whole = ZP_Orders::markup( $cost, $type, true );
				?>
				<tr>
					<td data-label="Period"><strong><?php echo (int) $d; ?> days</strong></td>
					<td data-label="Quantity"><?php echo (int) $qty; ?> × <?php echo esc_html( $types[ $type ]['label'] ); ?></td>
					<td data-label="API price (USD)" class="zp-right"><?php echo esc_html( zp_usd( $cost ) ); ?></td>
					<td data-label="API price (<?php echo esc_attr( $s['currency_code'] ); ?>)" class="zp-right"><?php echo esc_html( zp_money( round( $local, 2 ) ) ); ?></td>
					<td data-label="Markup" class="zp-right"><?php echo esc_html( rtrim( rtrim( number_format( $pct, 2 ), '0' ), '.' ) ); ?>%</td>
					<td data-label="Your price" class="zp-right"><strong><?php echo esc_html( zp_money( $sell ) ); ?></strong></td>
					<td data-label="Your profit" class="zp-right"><strong class="zp-pos"><?php echo esc_html( zp_money( round( $sell - $local, 2 ) ) ); ?></strong></td>
					<td data-label="Reseller price" class="zp-right"><strong><?php echo esc_html( zp_money( $whole ) ); ?></strong><span class="zp-sub"><?php echo esc_html( rtrim( rtrim( number_format( (float) $s['reseller_markup'], 2 ), '0' ), '.' ) ); ?>%</span></td>
					<td data-label="Profit from reseller" class="zp-right"><strong class="zp-pos"><?php echo esc_html( zp_money( round( $whole - $local, 2 ) ) ); ?></strong></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table></div>
		<p class="zp-muted" style="font-size:13px;margin-top:12px">Provider prices are fetched live from the proxy-ipv4.com API and cached for 30 minutes. Bulk orders may be discounted by the provider; customers are always quoted from the live price for their exact order.</p>
	<?php endif; ?>
</section>
