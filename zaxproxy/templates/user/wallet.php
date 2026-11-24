<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user @var float $balance */
$gateways = ZP_Gateways::available();
$per      = 12;
$pg       = zp_page_num();
$tx       = ZP_Wallet::transactions( $user->ID, $per, ( $pg - 1 ) * $per );
$tx_total = ZP_Wallet::count_transactions( $user->ID );
$deposits = array_filter(
	ZP_Wallet::user_deposits( $user->ID, 5 ),
	function ( $d ) {
		return 'manual' === $d->gateway;
	}
);
$min      = (float) zp_get( 'min_deposit' );
$presets  = array_values( array_filter( array( 2000, 5000, 10000, 20000, 50000 ), function ( $v ) use ( $min ) { return $v >= $min; } ) );
$tx_icons = array( 'deposit' => 'plus', 'purchase' => 'cart', 'extend' => 'refresh', 'refund' => 'swap', 'adjustment' => 'settings' );
?>
<div class="zp-walletgrid">
	<section class="zp-balance zp-anim">
		<div class="zp-balance__art"><?php echo zp_illustration( 'wallet' ); // phpcs:ignore ?></div>
		<span>Available balance</span>
		<strong data-zp-count="<?php echo esc_attr( $balance ); ?>" data-prefix="<?php echo esc_attr( zp_get( 'currency_symbol' ) ); ?>" data-decimals="2"><?php echo esc_html( zp_money( $balance ) ); ?></strong>
		<p>Funds are used for purchases and renewals. Balances are held in <?php echo esc_html( zp_get( 'currency_code' ) ); ?>.</p>
	</section>

	<section class="zp-card zp-anim" style="--d:1">
		<div class="zp-card__head"><h3>Add funds</h3></div>
		<?php if ( ! $gateways ) : ?>
			<p class="zp-muted">Top-ups are not available right now. Please contact support.</p>
		<?php else : ?>
			<form method="post" class="zp-form" id="zp-deposit">
				<?php zp_nonce_field( 'deposit' ); ?>
				<label class="zp-field"><span>Amount (<?php echo esc_html( zp_get( 'currency_code' ) ); ?>)</span>
					<div class="zp-input zp-input--amount"><b><?php echo esc_html( zp_get( 'currency_symbol' ) ); ?></b><input type="number" name="amount" min="<?php echo esc_attr( $min ); ?>" max="<?php echo esc_attr( zp_get( 'max_deposit' ) ); ?>" step="1" value="<?php echo esc_attr( $presets ? $presets[ min( 1, count( $presets ) - 1 ) ] : $min ); ?>" required inputmode="decimal"></div>
				</label>
				<div class="zp-pills zp-pills--sm">
					<?php foreach ( $presets as $v ) : ?>
						<button type="button" data-amount="<?php echo (int) $v; ?>"><?php echo esc_html( zp_money( $v ) ); ?></button>
					<?php endforeach; ?>
				</div>
				<label class="zp-field"><span>Payment method</span></label>
				<div class="zp-methods">
					<?php
					$first = true;
					foreach ( $gateways as $slug => $g ) :
						?>
						<label class="zp-method">
							<input type="radio" name="gateway" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $first ); ?>>
							<span class="zp-method__ico"><?php echo zp_icon( 'paystack' === $slug ? 'card' : 'receipt' ); // phpcs:ignore ?></span>
							<span class="zp-method__text"><strong><?php echo esc_html( $g['label'] ); ?></strong><em><?php echo esc_html( $g['desc'] ); ?></em></span>
							<span class="zp-method__tick"><?php echo zp_icon( 'check', 14 ); // phpcs:ignore ?></span>
						</label>
						<?php
						$first = false;
					endforeach;
					?>
				</div>
				<?php if ( isset( $gateways['manual'] ) ) : ?>
					<div class="zp-manual" id="zp-manual" <?php echo 'manual' === key( $gateways ) ? '' : 'hidden'; ?>>
						<div class="zp-callout"><?php echo nl2br( esc_html( zp_get( 'manual_instructions' ) ) ); ?></div>
						<label class="zp-field"><span>Payment reference / transaction ID</span><textarea name="note" rows="2" class="zp-textarea" placeholder="Paste the transfer reference or transaction hash"></textarea></label>
					</div>
				<?php endif; ?>
				<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?>Add funds</button>
			</form>
		<?php endif; ?>
	</section>
</div>

<?php if ( $deposits ) : ?>
<section class="zp-card zp-anim" style="--d:2">
	<div class="zp-card__head"><h3>Payment confirmations</h3></div>
	<ul class="zp-list">
		<?php foreach ( $deposits as $d ) : ?>
			<li>
				<span class="zp-list__ico"><?php echo zp_icon( 'receipt', 18 ); // phpcs:ignore ?></span>
				<div class="zp-list__main"><strong><?php echo esc_html( zp_money( $d->amount ) . ' · ' . ZP_Wallet::gateway_label( $d->gateway ) ); ?></strong><span><?php echo esc_html( zp_date( $d->created_at ) . ( 'rejected' === $d->status && $d->admin_note ? ' · ' . $d->admin_note : '' ) ); ?></span></div>
				<div class="zp-list__side"><?php echo zp_badge( $d->status ); // phpcs:ignore ?></div>
			</li>
		<?php endforeach; ?>
	</ul>
</section>
<?php endif; ?>

<section class="zp-card zp-anim" style="--d:3">
	<div class="zp-card__head"><h3>Transactions</h3></div>
	<?php if ( ! $tx ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No transactions yet', 'Top-ups, purchases and refunds will be listed here.' ); // phpcs:ignore ?>
	<?php else : ?>
		<ul class="zp-list">
			<?php foreach ( $tx as $t ) : ?>
				<li>
					<span class="zp-list__ico <?php echo $t->amount >= 0 ? 'zp-tone-green' : ''; ?>"><?php echo zp_icon( isset( $tx_icons[ $t->type ] ) ? $tx_icons[ $t->type ] : 'swap', 18 ); // phpcs:ignore ?></span>
					<div class="zp-list__main"><strong><?php echo esc_html( $t->description ? $t->description : ucfirst( $t->type ) ); ?></strong><span><?php echo esc_html( zp_date( $t->created_at ) ); ?></span></div>
					<div class="zp-list__side"><strong class="<?php echo $t->amount >= 0 ? 'zp-pos' : 'zp-neg'; ?>"><?php echo esc_html( ( $t->amount >= 0 ? '+' : '' ) . zp_money( $t->amount ) ); ?></strong><span>Balance <?php echo esc_html( zp_money( $t->balance_after ) ); ?></span></div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php echo zp_paginate( $tx_total, $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</section>
