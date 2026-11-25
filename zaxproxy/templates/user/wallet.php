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
<?php
global $wpdb;
$sym   = zp_get( 'currency_symbol', '₦' );
$tt    = zp_table( 'transactions' );
$since = gmdate( 'Y-m-d 00:00:00', time() - 29 * DAY_IN_SECONDS );
$flow  = $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(CASE WHEN amount > 0 THEN amount ELSE 0 END),0) money_in, COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END),0) money_out FROM $tt WHERE user_id = %d AND created_at >= %s", $user->ID, $since ) ); // phpcs:ignore
$life  = $wpdb->get_row( $wpdb->prepare( "SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END),0) funded, COALESCE(SUM(CASE WHEN amount < 0 THEN -amount ELSE 0 END),0) spent FROM $tt WHERE user_id = %d", $user->ID ) ); // phpcs:ignore
$pend  = $wpdb->get_row( $wpdb->prepare( 'SELECT COUNT(*) n, COALESCE(SUM(amount),0) s FROM ' . zp_table( 'deposits' ) . " WHERE user_id = %d AND status = 'pending'", $user->ID ) );
$lastd = $wpdb->get_row( $wpdb->prepare( "SELECT amount, created_at FROM $tt WHERE user_id = %d AND type = 'deposit' ORDER BY id DESC LIMIT 1", $user->ID ) ); // phpcs:ignore
// Balance at the end of each of the last 30 days.
$start = $wpdb->get_var( $wpdb->prepare( "SELECT balance_after FROM $tt WHERE user_id = %d AND created_at < %s ORDER BY id DESC LIMIT 1", $user->ID, $since ) ); // phpcs:ignore
$ends  = array();
$moves = array();
foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) d, balance_after b FROM $tt WHERE user_id = %d AND created_at >= %s ORDER BY id ASC", $user->ID, $since ) ) as $r ) { // phpcs:ignore
	$ends[ $r->d ]  = (float) $r->b;
	$moves[ $r->d ] = isset( $moves[ $r->d ] ) ? $moves[ $r->d ] + 1 : 1;
}
$hist  = array();
$run   = null === $start ? 0.0 : (float) $start;
for ( $i = 29; $i >= 0; $i-- ) {
	$d = gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS );
	if ( isset( $ends[ $d ] ) ) {
		$run = $ends[ $d ];
	}
	$n          = isset( $moves[ $d ] ) ? $moves[ $d ] : 0;
	$hist[ $d ] = array( max( 0, $run ), $n ? $n . ( 1 === $n ? ' transaction' : ' transactions' ) : 'No activity' );
}
$holder = $user->display_name ? $user->display_name : $user->user_login;
?>
<div class="zp-walletgrid">
	<div class="zp-walletcol">
		<section class="zp-wcard zp-anim">
			<div class="zp-wcard__top"><span class="zp-wcard__brand"><?php echo zp_logo( 30 ); // phpcs:ignore ?><?php echo esc_html( zp_get( 'brand' ) ); ?></span><span class="zp-wcard__chip"><?php echo esc_html( zp_get( 'currency_code' ) ); ?> wallet</span></div>
			<span class="zp-wcard__label">Available balance</span>
			<strong class="zp-wcard__amount" data-zp-count="<?php echo esc_attr( $balance ); ?>" data-prefix="<?php echo esc_attr( $sym ); ?>" data-decimals="2"><?php echo esc_html( zp_money( $balance ) ); ?></strong>
			<div class="zp-wcard__foot">
				<div><span>Account holder</span><strong><?php echo esc_html( $holder ); ?></strong></div>
				<div><span>Wallet ID</span><strong class="zp-mono">ZX-<?php echo esc_html( str_pad( (string) $user->ID, 6, '0', STR_PAD_LEFT ) ); ?></strong></div>
			</div>
			<div class="zp-wcard__actions">
				<a class="zp-btn zp-btn--light" href="#zp-deposit"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?>Add funds</a>
				<a class="zp-btn zp-btn--glass" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><?php echo zp_icon( 'cart', 18 ); // phpcs:ignore ?>Buy proxies</a>
			</div>
		</section>
		<section class="zp-wstats">
			<div class="zp-wstat zp-anim" style="--d:1"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></span><span>Money in · 30 days</span><strong class="zp-pos"><?php echo esc_html( zp_money( $flow->money_in ) ); ?></strong></div>
			<div class="zp-wstat zp-anim" style="--d:2"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'cart', 18 ); // phpcs:ignore ?></span><span>Money out · 30 days</span><strong><?php echo esc_html( zp_money( $flow->money_out ) ); ?></strong></div>
			<div class="zp-wstat zp-anim" style="--d:3"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'clock', 18 ); // phpcs:ignore ?></span><span>Awaiting confirmation</span><strong><?php echo esc_html( zp_money( $pend->s ) ); ?></strong><small><?php echo (int) $pend->n; ?> <?php echo 1 === (int) $pend->n ? 'deposit' : 'deposits'; ?></small></div>
			<div class="zp-wstat zp-anim" style="--d:4"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'receipt', 18 ); // phpcs:ignore ?></span><span>Last top-up</span><strong><?php echo $lastd ? esc_html( zp_money( $lastd->amount ) ) : '—'; ?></strong><small><?php echo $lastd ? esc_html( zp_date( $lastd->created_at, false ) ) : 'None yet'; ?></small></div>
		</section>
		<section class="zp-card zp-anim zp-wflow" style="--d:5">
			<div class="zp-card__head"><h3>All time</h3></div>
			<div class="zp-meters__row"><span>Total funded</span><strong><?php echo esc_html( zp_money( $life->funded ) ); ?></strong></div>
			<div class="zp-meter"><i style="--w:100%"></i></div>
			<div class="zp-meters__row" style="margin-top:14px"><span>Total spent</span><strong><?php echo esc_html( zp_money( $life->spent ) ); ?></strong></div>
			<div class="zp-meter zp-meter--amber"><i style="--w:<?php echo (int) ( $life->funded > 0 ? min( 100, round( $life->spent / $life->funded * 100 ) ) : ( $life->spent > 0 ? 100 : 0 ) ); ?>%"></i></div>
		</section>
	</div>

	<section class="zp-card zp-anim zp-fund" style="--d:1">
		<div class="zp-card__head"><h3>Add funds</h3><span class="zp-fund__safe"><?php echo zp_icon( 'shield', 14 ); // phpcs:ignore ?>Secure top-up</span></div>
		<ol class="zp-fund__steps"><li><b>1</b>Enter amount</li><li><b>2</b>Make transfer</li><li><b>3</b>Send receipt</li></ol>
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

<section class="zp-card zp-anim" style="--d:2">
	<div class="zp-card__head"><h3>Balance history</h3><span class="zp-sub">Last 30 days</span></div>
	<?php echo zp_area_chart( $hist, 'bal' ); // phpcs:ignore ?>
	<p class="zp-chart__hint">Tap a day to see your balance at the end of that day.</p>
</section>

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
	<div class="zp-card__head"><h3>Transactions</h3><div class="zp-seg" data-zp-txfilter><button type="button" class="is-active" data-dir="all">All</button><button type="button" data-dir="in">Money in</button><button type="button" data-dir="out">Money out</button></div></div>
	<?php if ( ! $tx ) : ?>
		<?php echo zp_empty( 'empty-orders', 'No transactions yet', 'Top-ups, purchases and refunds will be listed here.' ); // phpcs:ignore ?>
	<?php else : ?>
		<ul class="zp-list" id="zp-txlist">
			<?php foreach ( $tx as $t ) : ?>
				<li data-dir="<?php echo $t->amount >= 0 ? 'in' : 'out'; ?>">
					<span class="zp-list__ico <?php echo $t->amount >= 0 ? 'zp-tone-green' : ''; ?>"><?php echo zp_icon( isset( $tx_icons[ $t->type ] ) ? $tx_icons[ $t->type ] : 'swap', 18 ); // phpcs:ignore ?></span>
					<div class="zp-list__main"><strong><?php echo esc_html( $t->description ? $t->description : ucfirst( $t->type ) ); ?></strong><span><?php echo esc_html( zp_date( $t->created_at ) ); ?></span></div>
					<div class="zp-list__side"><strong class="<?php echo $t->amount >= 0 ? 'zp-pos' : 'zp-neg'; ?>"><?php echo esc_html( ( $t->amount >= 0 ? '+' : '' ) . zp_money( $t->amount ) ); ?></strong><span>Balance <?php echo esc_html( zp_money( $t->balance_after ) ); ?></span></div>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php echo zp_paginate( $tx_total, $per, $pg ); // phpcs:ignore ?>
	<?php endif; ?>
</section>
