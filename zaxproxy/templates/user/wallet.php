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
							<?php if ( zp_get( 'bank_account_number' ) ) : ?>
								<div class="zp-bank">
									<div class="zp-bank__head"><?php echo zp_icon( 'card', 18 ); // phpcs:ignore ?><strong>Pay into this account</strong></div>
									<dl>
										<div><dt>Bank</dt><dd><?php echo esc_html( zp_get( 'bank_name' ) ); ?></dd></div>
										<div><dt>Account name</dt><dd><?php echo esc_html( zp_get( 'bank_account_name' ) ); ?></dd></div>
										<div class="zp-bank__num"><dt>Account number</dt><dd><span class="zp-mono"><?php echo esc_html( zp_get( 'bank_account_number' ) ); ?></span><button type="button" class="zp-btn zp-btn--soft zp-btn--sm" data-zp-copy="<?php echo esc_attr( zp_get( 'bank_account_number' ) ); ?>" data-zp-copied="Account number copied"><?php echo zp_icon( 'copy', 16 ); // phpcs:ignore ?>Copy</button></dd></div>
										<div><dt>Amount to send</dt><dd><strong id="zp-bank-amount">—</strong><button type="button" class="zp-btn zp-btn--soft zp-btn--sm" id="zp-copy-amount"><?php echo zp_icon( 'copy', 16 ); // phpcs:ignore ?>Copy</button></dd></div>
									</dl>
								</div>
							<?php else : ?>
								<div class="zp-callout">Bank details are not available yet. Please contact support to make a deposit.</div>
							<?php endif; ?>
							<?php if ( zp_get( 'manual_instructions' ) ) : ?><p class="zp-summary__note" style="margin:0"><?php echo nl2br( esc_html( zp_get( 'manual_instructions' ) ) ); ?></p><?php endif; ?>
							<label class="zp-field"><span>Name on the account you are paying from</span><div class="zp-input"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?><input type="text" name="note" maxlength="120" placeholder="e.g. Ada Okafor"></div></label>
						</div>
					<?php endif; ?>
					<button class="zp-btn zp-btn--primary zp-btn--block zp-btn--lg" type="submit" id="zp-deposit-btn" data-manual="I have made the transfer" data-default="Add funds"><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?><span>Add funds</span></button>
			</form>
		<?php endif; ?>
	</section>
</div>

<?php if ( $deposits ) : ?>
<section class="zp-card zp-anim" style="--d:2" id="zp-receipts">
	<div class="zp-card__head"><h3>Your deposits</h3></div>
	<?php
	$just = zp_query( 'sent' );
	foreach ( $deposits as $d ) :
		if ( 'pending' !== $d->status || $d->reference !== $just ) {
			continue;
		}
		$tg = zp_telegram_url( zp_receipt_message( $d, $user ) );
		?>
		<div class="zp-receipt">
			<div class="zp-receipt__art"><?php echo zp_illustration( 'support' ); // phpcs:ignore ?></div>
			<div class="zp-receipt__text">
				<strong>One last step: send us your receipt</strong>
				<span>We have recorded your <?php echo esc_html( zp_money( $d->amount ) ); ?> deposit (ref <b class="zp-mono"><?php echo esc_html( $d->reference ); ?></b>). <?php echo $tg ? 'Chat with us on Telegram and attach your payment receipt so we can approve it.' : 'We will credit your wallet as soon as the payment is confirmed.'; ?></span>
			</div>
			<?php if ( $tg ) : ?><a class="zp-btn zp-btn--telegram zp-btn--lg" href="<?php echo esc_attr( $tg ); // built from a sanitised handle + rawurlencode; esc_url would strip the line breaks ?>" target="_blank" rel="noopener noreferrer"><?php echo zp_icon( 'send', 18 ); // phpcs:ignore ?>Send receipt on Telegram</a><?php endif; ?>
		</div>
	<?php endforeach; ?>
	<ul class="zp-list">
		<?php
		foreach ( $deposits as $d ) :
			$tg = 'pending' === $d->status ? zp_telegram_url( zp_receipt_message( $d, $user ) ) : '';
			?>
			<li>
				<span class="zp-list__ico"><?php echo zp_icon( 'receipt', 18 ); // phpcs:ignore ?></span>
				<div class="zp-list__main"><strong><?php echo esc_html( zp_money( $d->amount ) . ' · ' . ZP_Wallet::gateway_label( $d->gateway ) ); ?></strong><span><?php echo esc_html( 'Ref ' . $d->reference . ' · ' . zp_date( $d->created_at ) . ( 'rejected' === $d->status && $d->admin_note ? ' · ' . $d->admin_note : '' ) ); ?></span></div>
				<div class="zp-list__side zp-list__side--row">
					<?php if ( $tg ) : ?><a class="zp-btn zp-btn--telegram zp-btn--sm" href="<?php echo esc_attr( $tg ); // built from a sanitised handle + rawurlencode; esc_url would strip the line breaks ?>" target="_blank" rel="noopener noreferrer"><?php echo zp_icon( 'send', 16 ); // phpcs:ignore ?><span>Send receipt</span></a><?php endif; ?>
					<?php echo zp_badge( $d->status ); // phpcs:ignore ?>
				</div>
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
