<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user @var float $balance */
global $wpdb;
$me   = ZP_Resellers::get( $user->ID );
$host = wp_parse_url( home_url(), PHP_URL_HOST );

/* ---------- Not a reseller yet: pitch + sign-up ---------- */
if ( ! $me || ! $me->paid_until ) :
	$fee      = (float) zp_get( 'reseller_fee', 45000 );
	$monthly  = (float) zp_get( 'reseller_monthly', 15000 );
	$r_pct    = (float) zp_get( 'reseller_markup', 150 );
	$prices   = zp_public_prices();
	$pct      = function ( $n ) {
		return rtrim( rtrim( number_format( (float) $n, 1 ), '0' ), '.' ) . '%';
	};
	// How much cheaper the reseller rate is than the regular price, per proxy type.
	$saving   = array();
	foreach ( zp_enabled_types() as $slug => $t ) {
		$retail          = 1 + (float) zp_get( 'markup_' . $slug, 0 ) / 100;
		$whole           = 1 + $r_pct / 100;
		$saving[ $slug ] = array( 'off' => max( 0, ( 1 - $whole / $retail ) * 100 ), 'gain' => max( 0, ( $retail / $whole - 1 ) * 100 ) );
	}
	$best     = $saving ? max( wp_list_pluck( $saving, 'gain' ) ) : 0;
	$sample   = null;
	foreach ( $prices as $slug => $row ) {
		if ( isset( $row['wholesale'] ) && $row['price'] > $row['wholesale'] ) {
			$sample = array( 'type' => zp_type_label( $slug ), 'days' => $row['days'], 'retail' => $row['price'], 'whole' => $row['wholesale'], 'gain' => $row['price'] - $row['wholesale'] );
			break;
		}
	}
	$short    = max( 0, $fee - $balance );
	?>
	<section class="zp-hero zp-anim">
		<div class="zp-hero__text">
			<span class="zp-eyebrow"><?php echo zp_icon( 'lock', 14 ); // phpcs:ignore ?> Reseller program · locked</span>
			<h2>Run your own proxy business under your own brand.</h2>
			<p>Buy at the reseller price, sell at your price, keep the difference. Unlock your reseller account for <?php echo esc_html( zp_money( $fee ) ); ?> and get your own store, your own customers and your own dashboard.</p>
			<div class="zp-row"><a class="zp-btn zp-btn--light" href="#zp-rs-unlock"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Unlock for <?php echo esc_html( zp_money( $fee ) ); ?></a></div>
		</div>
		<div class="zp-hero__art"><?php echo zp_illustration( 'network' ); // phpcs:ignore ?></div>
	</section>

	<section class="zp-stats">
		<div class="zp-stat zp-anim" style="--d:1"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'lock' ); // phpcs:ignore ?></span><span class="zp-stat__label">One-time activation</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( $fee ) ); ?></strong><span class="zp-sub">Includes your first 30 days</span></div>
		<div class="zp-stat zp-anim" style="--d:2"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'refresh' ); // phpcs:ignore ?></span><span class="zp-stat__label">Monthly, after 30 days</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( $monthly ) ); ?></strong><span class="zp-sub">Taken from your wallet automatically</span></div>
		<div class="zp-stat zp-anim" style="--d:3"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'chart' ); // phpcs:ignore ?></span><span class="zp-stat__label">Your buying discount</span><strong class="zp-stat__value"><?php echo esc_html( 'up to ' . $pct( $saving ? max( wp_list_pluck( $saving, 'off' ) ) : 0 ) ); ?></strong><span class="zp-sub">Below the regular price</span></div>
		<div class="zp-stat zp-anim" style="--d:4"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'zap' ); // phpcs:ignore ?></span><span class="zp-stat__label">Your gain per sale</span><strong class="zp-stat__value zp-pos"><?php echo esc_html( $pct( $best ) . '+' ); ?></strong><span class="zp-sub">When you sell at the regular price</span></div>
	</section>

	<div class="zp-grid2">
		<section class="zp-card zp-anim" style="--d:5">
			<div class="zp-card__head"><h3>How the reseller account works</h3></div>
			<ol class="zp-rsteps">
				<li><b>1</b><div><strong>Unlock your account</strong><span>Pay <?php echo esc_html( zp_money( $fee ) ); ?> once from your wallet. Your reseller dashboard and store open immediately.</span></div></li>
				<li><b>2</b><div><strong>Get your own store link</strong><span>A page like <span class="zp-mono"><?php echo esc_html( $host ); ?>/s/yourbrand</span> with your brand name. Your customers register, pay and buy there.</span></div></li>
				<li><b>3</b><div><strong>Customers pay you directly</strong><span>They transfer to your bank account and send you the receipt. You approve it and their wallet is credited.</span></div></li>
				<li><b>4</b><div><strong>We deliver, your wallet pays the reseller price</strong><span>Each customer order takes the reseller price from your ZaxProxy wallet. Keep it funded or their orders cannot go through.</span></div></li>
				<li><b>5</b><div><strong>You keep the difference</strong><span>You choose your own markup. Everything above the reseller price is your profit, shown on your dashboard.</span></div></li>
			</ol>
		</section>

		<section class="zp-card zp-anim" style="--d:6">
			<div class="zp-card__head"><h3>What you gain</h3></div>
			<div class="zp-tablewrap"><table class="zp-table zp-table--keep">
				<thead><tr><th>Proxy type</th><th class="zp-right">You buy for</th><th class="zp-right">Gain at regular price</th></tr></thead>
				<tbody>
				<?php foreach ( zp_enabled_types() as $slug => $t ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $t['label'] ); ?></strong></td>
						<td class="zp-right"><?php echo esc_html( $pct( $saving[ $slug ]['off'] ) ); ?> less</td>
						<td class="zp-right"><strong class="zp-pos"><?php echo esc_html( $pct( $saving[ $slug ]['gain'] ) ); ?></strong></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<?php if ( $sample ) : ?>
				<div class="zp-callout" style="margin-top:14px">
					<strong>Example: 1 <?php echo esc_html( $sample['type'] ); ?> proxy, <?php echo (int) $sample['days']; ?> days</strong><br>
					Regular customers pay <?php echo esc_html( zp_money( $sample['retail'] ) ); ?>. You pay <?php echo esc_html( zp_money( $sample['whole'] ) ); ?>.<br>
					Sell it at the regular price and you keep <strong class="zp-pos"><?php echo esc_html( zp_money( $sample['gain'] ) ); ?></strong>.
					Sell 10 and you keep <?php echo esc_html( zp_money( $sample['gain'] * 10 ) ); ?>; 50, <?php echo esc_html( zp_money( $sample['gain'] * 50 ) ); ?>; 100, <?php echo esc_html( zp_money( $sample['gain'] * 100 ) ); ?>.
				</div>
			<?php endif; ?>
			<ul class="zp-assure" style="padding:0;margin-top:14px">
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>You also buy your own proxies at the reseller price.</li>
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>You set your selling price: charge more or less than the regular price.</li>
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>Track customers, sales and profit in your dashboard.</li>
			</ul>
		</section>
	</div>

	<section class="zp-card zp-anim" style="--d:7">
		<div class="zp-card__head"><h3>Fees and rules</h3></div>
		<ul class="zp-rules">
			<li><strong>Activation:</strong> <?php echo esc_html( zp_money( $fee ) ); ?>, paid once from your wallet. It covers your first 30 days.</li>
			<li><strong>Monthly fee:</strong> <?php echo esc_html( zp_money( $monthly ) ); ?> is taken from your wallet automatically every 30 days to keep the account active.</li>
			<li><strong>If the monthly fee cannot be paid:</strong> your store pauses, your customers cannot buy, and you go back to regular prices. Top up and renew, and everything is restored. Your customers and their proxies are kept.</li>
			<li><strong>Your wallet funds every sale:</strong> customers pay you, and the reseller price of each order comes out of your wallet. An empty wallet means their orders are refused.</li>
			<li><strong>You handle your customers:</strong> their payments, wallet credits and support tickets come to you.</li>
			<li><strong>Fees are not refundable</strong> once the account is unlocked or renewed.</li>
		</ul>
	</section>

	<section class="zp-card zp-anim" style="--d:8" id="zp-rs-unlock">
		<div class="zp-card__head"><h3><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?> Unlock your reseller account</h3><?php echo zp_badge( 'locked' ); // phpcs:ignore ?></div>
		<?php if ( $short > 0 ) : ?>
			<div class="zp-alert zp-alert--info" style="margin-bottom:16px"><?php echo zp_icon( 'wallet', 18 ); // phpcs:ignore ?><span>Your wallet has <?php echo esc_html( zp_money( $balance ) ); ?>. Add <?php echo esc_html( zp_money( $short ) ); ?> more to unlock.</span><a class="zp-btn zp-btn--sm zp-btn--primary" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">Add funds</a></div>
		<?php endif; ?>
		<form method="post" class="zp-form" onsubmit="return confirm('Pay <?php echo esc_js( zp_money( $fee ) ); ?> from your wallet to unlock your reseller account?')">
			<?php zp_nonce_field( 'reseller_save' ); ?>
			<div class="zp-fieldrow" style="margin-top:0">
				<label class="zp-field"><span>Brand name</span><div class="zp-input"><?php echo zp_icon( 'shield', 18 ); // phpcs:ignore ?><input type="text" name="brand" value="<?php echo esc_attr( $me ? $me->brand : '' ); ?>" maxlength="40" required placeholder="e.g. SwiftProxy" id="zp-rs-brand"></div></label>
				<label class="zp-field"><span>Store link</span><div class="zp-input"><b class="zp-prefix"><?php echo esc_html( $host ); ?>/s/</b><input type="text" name="slug" value="<?php echo esc_attr( $me ? $me->slug : '' ); ?>" maxlength="30" required pattern="[a-z0-9][a-z0-9\-]{1,28}[a-z0-9]" placeholder="swiftproxy" id="zp-rs-slug" autocapitalize="none"></div><small>Lowercase letters, numbers and dashes.</small></label>
			</div>
			<label class="zp-field"><span>Your markup on the reseller price (%)</span><div class="zp-input"><input type="number" name="markup" value="<?php echo esc_attr( $me ? (float) $me->markup : round( $best, 1 ) ); ?>" min="0" max="500" step="0.1" required></div><small><?php echo esc_html( $pct( $best ) ); ?> matches the regular price. Set it higher to earn more, or lower to undercut. You can change it any time.</small></label>
			<button class="zp-btn zp-btn--primary zp-btn--lg" type="submit"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Pay <?php echo esc_html( zp_money( $fee ) ); ?> and unlock</button>
			<p class="zp-summary__note" style="margin:0">Charged from your wallet now. From day 31, <?php echo esc_html( zp_money( $monthly ) ); ?> per month is charged automatically.</p>
		</form>
	</section>
	<?php
	return;
endif;

/* ---------- Reseller dashboard ---------- */
$tab     = sanitize_key( zp_query( 'tab', 'overview' ) );
$tabs    = array( 'overview' => 'Overview', 'customers' => 'Customers', 'deposits' => 'Deposits', 'orders' => 'Orders', 'tickets' => 'Tickets', 'settings' => 'Store settings' );
$tab     = isset( $tabs[ $tab ] ) ? $tab : 'overview';
$stats   = ZP_Resellers::stats( $user->ID );
$store_url = ZP_Resellers::store_url( $me ); // not $url: the shell layout owns that name
$ids     = ZP_Resellers::customer_ids( $user->ID );
$blocked = get_user_meta( $user->ID, 'zp_reseller_blocked', true );
$counts  = array( 'deposits' => $stats['pending'], 'tickets' => $stats['tickets'] );
$tab_url = function ( $t, $args = array() ) {
	return zp_url( 'reseller', array_merge( array( 'tab' => $t ), $args ) );
};
?>
<?php
$me      = ZP_Resellers::bill( $me ); // renew now if the month has just ended
$monthly = (float) zp_get( 'reseller_monthly', 15000 );
$paid_ts = strtotime( $me->paid_until . ' UTC' );
?>
<?php if ( 'expired' === $me->status ) : ?>
	<div class="zp-alert zp-alert--error"><?php echo zp_icon( 'lock', 18 ); // phpcs:ignore ?><span>Your reseller account is paused: the monthly fee of <?php echo esc_html( zp_money( $monthly ) ); ?> could not be taken from your wallet. Your store is offline and you are on regular prices until you renew.</span>
		<form method="post"><?php zp_nonce_field( 'reseller_renew' ); ?><button class="zp-btn zp-btn--sm zp-btn--primary" type="submit">Renew for <?php echo esc_html( zp_money( $monthly ) ); ?></button></form></div>
<?php elseif ( 'suspended' === $me->status ) : ?>
	<div class="zp-alert zp-alert--error"><?php echo zp_icon( 'x', 18 ); // phpcs:ignore ?><span>Your store is suspended. Customers cannot sign in or buy. Please contact support.</span></div>
<?php elseif ( is_array( $blocked ) && strtotime( $blocked['time'] . ' UTC' ) > time() - 3 * DAY_IN_SECONDS && $balance < (float) $blocked['amount'] ) : ?>
	<div class="zp-alert zp-alert--error"><?php echo zp_icon( 'wallet', 18 ); // phpcs:ignore ?><span>A customer order of <?php echo esc_html( zp_money( $blocked['amount'] ) ); ?> could not go through on <?php echo esc_html( zp_date( $blocked['time'] ) ); ?> because your wallet was too low.</span><a class="zp-btn zp-btn--sm zp-btn--primary" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">Top up</a></div>
<?php elseif ( $balance <= 0 ) : ?>
	<div class="zp-alert zp-alert--info"><?php echo zp_icon( 'wallet', 18 ); // phpcs:ignore ?><span>Your wallet is empty. Top up so your customers' orders can be fulfilled.</span><a class="zp-btn zp-btn--sm zp-btn--primary" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">Top up</a></div>
<?php endif; ?>

<nav class="zp-subtabs zp-anim" aria-label="Reseller sections">
	<?php foreach ( $tabs as $slug => $label ) : ?>
		<a class="zp-pill<?php echo $slug === $tab ? ' is-active' : ''; ?>" href="<?php echo esc_url( $tab_url( $slug ) ); ?>"><?php echo esc_html( $label ); ?><?php echo ! empty( $counts[ $slug ] ) ? '<b>' . (int) $counts[ $slug ] . '</b>' : ''; ?></a>
	<?php endforeach; ?>
</nav>

<?php if ( 'overview' === $tab ) : ?>
	<section class="zp-storelink zp-anim" style="--d:1">
		<div>
			<span class="zp-eyebrow">Your store</span>
			<h2><?php echo esc_html( $me->brand ); ?></h2>
			<a class="zp-mono" href="<?php echo esc_url( $store_url ); ?>" target="_blank" rel="noopener"><?php echo esc_html( preg_replace( '#^https?://#', '', untrailingslashit( $store_url ) ) ); ?></a>
		</div>
		<div class="zp-row">
			<button type="button" class="zp-btn zp-btn--light" data-zp-copy="<?php echo esc_attr( $store_url ); ?>" data-zp-copied="Store link copied"><?php echo zp_icon( 'copy', 18 ); // phpcs:ignore ?>Copy link</button>
			<a class="zp-btn zp-btn--glass" href="<?php echo esc_url( $store_url ); ?>" target="_blank" rel="noopener"><?php echo zp_icon( 'eye', 18 ); // phpcs:ignore ?>View store</a>
		</div>
	</section>

	<div class="zp-subinfo zp-anim" style="--d:2">
		<?php echo zp_icon( 'active' === $me->status ? 'check' : 'lock', 18 ); // phpcs:ignore ?>
		<span><?php echo 'active' === $me->status ? esc_html( sprintf( 'Reseller account active until %s (%s left). Then %s is charged from your wallet automatically each month.', zp_date( $me->paid_until, false ), zp_time_left( $me->paid_until ), zp_money( $monthly ) ) ) : 'Reseller account not active.'; ?></span>
		<?php if ( 'active' === $me->status && $paid_ts - time() < 5 * DAY_IN_SECONDS && $balance < $monthly ) : ?><a class="zp-btn zp-btn--sm zp-btn--soft" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">Top up for renewal</a><?php endif; ?>
	</div>

	<section class="zp-stats">
		<div class="zp-stat zp-anim" style="--d:2"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'wallet' ); // phpcs:ignore ?></span><span class="zp-stat__label">Your wallet</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( $balance ) ); ?></strong><span class="zp-sub">Pays for customer orders</span></div>
		<div class="zp-stat zp-anim" style="--d:3"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'users' ); // phpcs:ignore ?></span><span class="zp-stat__label">Customers</span><strong class="zp-stat__value"><?php echo (int) $stats['customers']; ?></strong><span class="zp-sub"><?php echo (int) $stats['orders']; ?> <?php echo 1 === (int) $stats['orders'] ? 'order' : 'orders'; ?></span></div>
		<div class="zp-stat zp-anim" style="--d:4"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'chart' ); // phpcs:ignore ?></span><span class="zp-stat__label">Sales</span><strong class="zp-stat__value"><?php echo esc_html( zp_money( $stats['sales'] ) ); ?></strong><span class="zp-sub">Cost <?php echo esc_html( zp_money( $stats['cost'] ) ); ?></span></div>
		<div class="zp-stat zp-anim" style="--d:5"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'zap' ); // phpcs:ignore ?></span><span class="zp-stat__label">Your profit</span><strong class="zp-stat__value zp-pos"><?php echo esc_html( zp_money( $stats['profit'] ) ); ?></strong><span class="zp-sub">Markup <?php echo esc_html( rtrim( rtrim( number_format( (float) $me->markup, 2 ), '0' ), '.' ) ); ?>%</span></div>
	</section>

	<?php
	$days = array();
	for ( $i = 29; $i >= 0; $i-- ) {
		$days[ gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS ) ] = 0;
	}
	foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT DATE(created_at) d, SUM(amount) s FROM ' . zp_table( 'orders' ) . " WHERE reseller_id = %d AND refunded = 0 AND status IN ('completed','processing','review') AND created_at >= %s GROUP BY DATE(created_at)", $user->ID, gmdate( 'Y-m-d 00:00:00', time() - 29 * DAY_IN_SECONDS ) ) ) as $r ) {
		if ( isset( $days[ $r->d ] ) ) {
			$days[ $r->d ] = (float) $r->s;
		}
	}
	$max     = max( 1, max( $days ) );
	$latest  = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'orders' ) . ' WHERE reseller_id = %d ORDER BY id DESC LIMIT 5', $user->ID ) );
	$newest  = $ids ? get_users( array( 'include' => $ids, 'orderby' => 'registered', 'order' => 'DESC', 'number' => 5 ) ) : array();
	?>
	<div class="zp-grid2 zp-grid2--wide">
		<section class="zp-card zp-anim" style="--d:6">
			<div class="zp-card__head"><h3>Sales, last 30 days</h3><a href="<?php echo esc_url( $tab_url( 'orders' ) ); ?>">All orders</a></div>
			<div class="zp-bars">
				<?php foreach ( $days as $d => $v ) : ?>
					<div class="zp-bars__col" title="<?php echo esc_attr( $d . ': ' . zp_money( $v ) ); ?>"><i style="height:<?php echo esc_attr( max( 3, round( $v / $max * 100 ) ) ); ?>%"></i><span><?php echo 0 === ( (int) gmdate( 'j', strtotime( $d ) ) % 5 ) ? esc_html( gmdate( 'j', strtotime( $d ) ) ) : '&nbsp;'; ?></span></div>
				<?php endforeach; ?>
			</div>
		</section>
		<section class="zp-card zp-anim" style="--d:7">
			<div class="zp-card__head"><h3>Newest customers</h3><a href="<?php echo esc_url( $tab_url( 'customers' ) ); ?>">View all</a></div>
			<?php if ( ! $newest ) : ?>
				<p class="zp-muted">No customers yet. Share your store link to get your first sign-up.</p>
			<?php else : ?>
				<ul class="zp-list">
					<?php foreach ( $newest as $c ) : ?>
						<li><span class="zp-avatar"><?php echo esc_html( strtoupper( mb_substr( $c->display_name, 0, 1 ) ) ); ?></span><div class="zp-list__main"><strong><?php echo esc_html( $c->display_name ); ?></strong><span><?php echo esc_html( $c->user_email ); ?></span></div><div class="zp-list__side"><strong><?php echo esc_html( zp_money( ZP_Wallet::balance( $c->ID ) ) ); ?></strong><span><?php echo esc_html( zp_date( $c->user_registered, false ) ); ?></span></div></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	</div>

	<?php if ( $latest ) : ?>
	<section class="zp-card zp-anim" style="--d:8">
		<div class="zp-card__head"><h3>Latest orders</h3><a href="<?php echo esc_url( $tab_url( 'orders' ) ); ?>">View all</a></div>
		<ul class="zp-list">
			<?php
			foreach ( $latest as $o ) :
				$c    = get_userdata( $o->user_id );
				$live = ! $o->refunded && in_array( $o->status, array( 'completed', 'processing', 'review' ), true );
				?>
				<li><span class="zp-flag"><?php echo esc_html( zp_flag( $o->country ) ); ?></span><div class="zp-list__main"><strong><?php echo esc_html( $o->qty . ' × ' . zp_type_label( $o->proxy_type ) . ' · ' . $o->days . ' days' ); ?></strong><span><?php echo esc_html( ( $c ? $c->user_email : '—' ) . ' · ' . zp_date( $o->created_at ) ); ?></span></div><div class="zp-list__side"><strong><?php echo esc_html( zp_money( $o->amount ) ); ?></strong><span class="<?php echo $live ? 'zp-pos' : ''; ?>"><?php echo $live ? esc_html( '+' . zp_money( $o->amount - $o->reseller_cost ) . ' profit' ) : esc_html( ucfirst( $o->status ) ); ?></span></div></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php endif; ?>

	<div class="zp-grid2">
		<section class="zp-card zp-anim" style="--d:9">
			<div class="zp-card__head"><h3>How your store works</h3></div>
			<ul class="zp-assure" style="padding:0">
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>Customers register and sign in from your store link, under your brand.</li>
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>They pay you directly using the bank details in Store settings.</li>
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>You approve the payment under Deposits and their wallet is credited.</li>
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>When they buy, your reseller price is taken from your wallet. The rest is your profit.</li>
				<li><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?>Your own orders are also charged at the reseller price.</li>
			</ul>
		</section>
		<section class="zp-card zp-anim" style="--d:10">
			<div class="zp-card__head"><h3>Needs attention</h3></div>
			<ul class="zp-list zp-list--link">
				<li><a href="<?php echo esc_url( $tab_url( 'deposits' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'card', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Deposits to approve</strong><span>Customers waiting for wallet credit</span></div><div class="zp-list__side"><strong><?php echo (int) $stats['pending']; ?></strong></div></a></li>
				<li><a href="<?php echo esc_url( $tab_url( 'tickets' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Open tickets</strong><span>Customers waiting for a reply</span></div><div class="zp-list__side"><strong><?php echo (int) $stats['tickets']; ?></strong></div></a></li>
				<?php if ( ! $me->bank_account_number ) : ?>
					<li><a href="<?php echo esc_url( $tab_url( 'settings' ) ); ?>"><span class="zp-list__ico zp-tone-amber"><?php echo zp_icon( 'settings', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Add your bank details</strong><span>Customers cannot pay you until you do</span></div></a></li>
				<?php endif; ?>
			</ul>
		</section>
	</div>

<?php elseif ( 'customers' === $tab ) : ?>
	<section class="zp-card zp-anim" style="--d:1">
		<div class="zp-card__head"><h3>Your customers</h3></div>
		<?php if ( ! $ids ) : ?>
			<?php echo zp_empty( 'empty-tickets', 'No customers yet', 'Share your store link. Everyone who registers there becomes your customer.' ); // phpcs:ignore ?>
		<?php else : ?>
			<div class="zp-tablewrap"><table class="zp-table">
				<thead><tr><th>Customer</th><th>Joined</th><th class="zp-right">Wallet</th><th class="zp-right">Active proxies</th><th>Adjust wallet</th></tr></thead>
				<tbody>
				<?php
				foreach ( get_users( array( 'include' => $ids, 'orderby' => 'registered', 'order' => 'DESC', 'number' => 200 ) ) as $c ) :
					?>
					<tr>
						<td data-label="Customer"><strong><?php echo esc_html( $c->display_name ); ?></strong><span class="zp-sub"><?php echo esc_html( $c->user_email ); ?></span></td>
						<td data-label="Joined"><?php echo esc_html( zp_date( $c->user_registered, false ) ); ?></td>
						<td data-label="Wallet" class="zp-right"><strong><?php echo esc_html( zp_money( ZP_Wallet::balance( $c->ID ) ) ); ?></strong></td>
						<td data-label="Active proxies" class="zp-right"><?php echo (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'proxies' ) . ' WHERE user_id = %d AND date_end > %s', $c->ID, zp_now() ) ); ?></td>
						<td data-label="Adjust wallet"><form method="post" class="zp-inline">
							<?php zp_nonce_field( 'reseller_adjust' ); ?><input type="hidden" name="tab" value="customers"><input type="hidden" name="customer_id" value="<?php echo (int) $c->ID; ?>">
							<input type="number" name="amount" placeholder="Amount" min="1" step="1" required style="width:104px">
							<button class="zp-btn zp-btn--soft zp-btn--sm" type="submit" name="direction" value="credit">Credit</button>
							<button class="zp-btn zp-btn--ghost zp-btn--sm" type="submit" name="direction" value="debit">Debit</button>
						</form></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		<?php endif; ?>
	</section>

<?php elseif ( 'deposits' === $tab ) : ?>
	<?php $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'deposits' ) . " WHERE reseller_id = %d ORDER BY (status = 'pending') DESC, id DESC LIMIT 100", $user->ID ) ); ?>
	<section class="zp-card zp-anim" style="--d:1">
		<div class="zp-card__head"><h3>Customer deposits</h3></div>
		<?php if ( ! $rows ) : ?>
			<?php echo zp_empty( 'empty-orders', 'No deposits yet', 'When a customer says they have paid you, it appears here for approval.' ); // phpcs:ignore ?>
		<?php else : ?>
			<p class="zp-muted" style="margin-bottom:12px">Approve only after you have confirmed the money in your own bank account.</p>
			<div class="zp-tablewrap"><table class="zp-table">
				<thead><tr><th>Deposit</th><th>Customer</th><th>Sender name</th><th class="zp-right">Amount</th><th>Status</th><th>Actions</th></tr></thead>
				<tbody>
				<?php
				foreach ( $rows as $d ) :
					$c = get_userdata( $d->user_id );
					?>
					<tr>
						<td data-label="Deposit"><strong class="zp-mono"><?php echo esc_html( $d->reference ); ?></strong><span class="zp-sub"><?php echo esc_html( zp_date( $d->created_at ) ); ?></span></td>
						<td data-label="Customer"><?php echo esc_html( $c ? $c->user_email : '—' ); ?></td>
						<td data-label="Sender name"><?php echo esc_html( $d->note ); ?></td>
						<td data-label="Amount" class="zp-right"><strong><?php echo esc_html( zp_money( $d->amount ) ); ?></strong></td>
						<td data-label="Status"><?php echo zp_badge( $d->status ); // phpcs:ignore ?></td>
						<td data-label="Actions"><?php if ( 'pending' === $d->status ) : ?><div class="zp-inline">
							<form method="post" onsubmit="return confirm('Confirm you received this payment and credit the customer wallet?')"><?php zp_nonce_field( 'reseller_deposit_approve' ); ?><input type="hidden" name="tab" value="deposits"><input type="hidden" name="id" value="<?php echo (int) $d->id; ?>"><button class="zp-btn zp-btn--primary zp-btn--sm" type="submit">Approve</button></form>
							<form method="post"><?php zp_nonce_field( 'reseller_deposit_reject' ); ?><input type="hidden" name="tab" value="deposits"><input type="hidden" name="id" value="<?php echo (int) $d->id; ?>"><button class="zp-btn zp-btn--danger zp-btn--sm" type="submit">Reject</button></form>
						</div><?php endif; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		<?php endif; ?>
	</section>

<?php elseif ( 'orders' === $tab ) : ?>
	<?php $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . zp_table( 'orders' ) . ' WHERE reseller_id = %d ORDER BY id DESC LIMIT 100', $user->ID ) ); ?>
	<section class="zp-card zp-anim" style="--d:1">
		<div class="zp-card__head"><h3>Customer orders</h3></div>
		<?php if ( ! $rows ) : ?>
			<?php echo zp_empty( 'empty-orders', 'No orders yet', 'Orders placed in your store are listed here with your profit on each one.' ); // phpcs:ignore ?>
		<?php else : ?>
			<div class="zp-tablewrap"><table class="zp-table">
				<thead><tr><th>Order</th><th>Customer</th><th>Details</th><th class="zp-right">Customer paid</th><th class="zp-right">Your cost</th><th class="zp-right">Profit</th><th>Status</th></tr></thead>
				<tbody>
				<?php
				foreach ( $rows as $o ) :
					$c    = get_userdata( $o->user_id );
					$live = ! $o->refunded && in_array( $o->status, array( 'completed', 'processing', 'review' ), true );
					?>
					<tr>
						<td data-label="Order"><strong>#<?php echo (int) $o->id; ?></strong><span class="zp-sub"><?php echo esc_html( zp_date( $o->created_at ) ); ?></span></td>
						<td data-label="Customer"><?php echo esc_html( $c ? $c->user_email : '—' ); ?></td>
						<td data-label="Details"><?php echo esc_html( ( 'extend' === $o->kind ? 'Renew ' : '' ) . $o->qty . ' × ' . zp_type_label( $o->proxy_type ) . ' · ' . zp_country_name( $o->country ) . ' · ' . $o->days . ' days' ); ?></td>
						<td data-label="Customer paid" class="zp-right"><strong><?php echo esc_html( zp_money( $o->amount ) ); ?></strong></td>
						<td data-label="Your cost" class="zp-right"><?php echo esc_html( zp_money( $o->reseller_cost ) ); ?></td>
						<td data-label="Profit" class="zp-right"><strong class="<?php echo $live ? 'zp-pos' : ''; ?>"><?php echo $live ? esc_html( zp_money( $o->amount - $o->reseller_cost ) ) : '—'; ?></strong></td>
						<td data-label="Status"><?php echo zp_badge( $o->status ); // phpcs:ignore ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
		<?php endif; ?>
	</section>

<?php elseif ( 'tickets' === $tab ) : ?>
	<?php
	$tid = (int) zp_query( 'id' );
	$t   = $tid ? ZP_Tickets::get( $tid ) : null;
	if ( $t && (int) $t->reseller_id === $user->ID ) :
		$c = get_userdata( $t->user_id );
		?>
		<a class="zp-back" href="<?php echo esc_url( $tab_url( 'tickets' ) ); ?>">← All tickets</a>
		<section class="zp-card zp-anim zp-is-admin">
			<div class="zp-card__head"><h3>#<?php echo (int) $t->id; ?> · <?php echo esc_html( $t->subject ); ?></h3><?php echo zp_badge( $t->status ); // phpcs:ignore ?></div>
			<p class="zp-muted" style="margin-bottom:16px">From <?php echo esc_html( $c ? $c->user_email : 'deleted user' ); ?></p>
			<div class="zp-thread">
				<?php foreach ( ZP_Tickets::messages( $t->id ) as $m ) : ?>
					<div class="zp-msg<?php echo $m->is_admin ? ' zp-msg--staff' : ' zp-msg--me'; ?>"><div class="zp-msg__bubble"><?php echo nl2br( esc_html( $m->message ) ); ?></div><span><?php echo $m->is_admin ? 'You' : 'Customer'; ?> · <?php echo esc_html( zp_date( $m->created_at ) ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<form method="post" class="zp-form zp-reply">
				<?php zp_nonce_field( 'reseller_ticket_reply' ); ?><input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>">
				<textarea name="message" rows="3" class="zp-textarea" placeholder="Reply to your customer…" required></textarea>
				<div class="zp-row zp-row--between">
					<button class="zp-btn zp-btn--ghost zp-btn--sm" type="submit" form="zp-rs-close">Close ticket</button>
					<button class="zp-btn zp-btn--primary" type="submit">Send reply</button>
				</div>
			</form>
			<form method="post" id="zp-rs-close"><?php zp_nonce_field( 'reseller_ticket_close' ); ?><input type="hidden" name="tab" value="tickets"><input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>"></form>
		</section>
	<?php else : ?>
		<?php $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT t.*, u.user_email FROM ' . zp_table( 'tickets' ) . " t LEFT JOIN {$wpdb->users} u ON u.ID = t.user_id WHERE t.reseller_id = %d ORDER BY (t.status = 'open') DESC, t.updated_at DESC LIMIT 100", $user->ID ) ); ?>
		<section class="zp-card zp-anim" style="--d:1">
			<div class="zp-card__head"><h3>Customer tickets</h3></div>
			<?php if ( ! $rows ) : ?>
				<?php echo zp_empty( 'empty-tickets', 'No tickets', 'Support requests from your customers come to you here.' ); // phpcs:ignore ?>
			<?php else : ?>
				<ul class="zp-list zp-list--link">
					<?php foreach ( $rows as $r ) : ?>
						<li><a href="<?php echo esc_url( $tab_url( 'tickets', array( 'id' => $r->id ) ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong><?php echo esc_html( $r->subject ); ?></strong><span>#<?php echo (int) $r->id; ?> · <?php echo esc_html( $r->user_email ); ?> · <?php echo esc_html( zp_date( $r->updated_at ) ); ?></span></div><div class="zp-list__side"><?php echo zp_badge( $r->status ); // phpcs:ignore ?></div></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</section>
	<?php endif; ?>

<?php else : ?>
	<form method="post" class="zp-form">
		<?php zp_nonce_field( 'reseller_save' ); ?><input type="hidden" name="tab" value="settings">
		<div class="zp-grid2">
			<section class="zp-card zp-anim" style="--d:1">
				<div class="zp-card__head"><h3>Brand &amp; pricing</h3></div>
				<label class="zp-field"><span>Brand name</span><div class="zp-input"><?php echo zp_icon( 'shield', 18 ); // phpcs:ignore ?><input type="text" name="brand" value="<?php echo esc_attr( $me->brand ); ?>" maxlength="40" required></div></label>
				<label class="zp-field"><span>Store link</span><div class="zp-input"><b class="zp-prefix"><?php echo esc_html( $host ); ?>/s/</b><input type="text" name="slug" value="<?php echo esc_attr( $me->slug ); ?>" maxlength="30" required pattern="[a-z0-9][a-z0-9\-]{1,28}[a-z0-9]" autocapitalize="none"></div><small>Changing this breaks links you have already shared.</small></label>
				<label class="zp-field"><span>Your markup (%)</span><div class="zp-input"><input type="number" name="markup" value="<?php echo esc_attr( (float) $me->markup ); ?>" min="0" max="500" step="0.1" required></div><small>Customer price = your reseller price + your markup.</small></label>
			</section>
			<section class="zp-card zp-anim" style="--d:2">
				<div class="zp-card__head"><h3>How customers pay you</h3></div>
				<div class="zp-fieldrow" style="margin-top:0">
					<label class="zp-field"><span>Bank name</span><div class="zp-input"><input type="text" name="bank_name" value="<?php echo esc_attr( $me->bank_name ); ?>" maxlength="80"></div></label>
					<label class="zp-field"><span>Account number</span><div class="zp-input"><input type="text" name="bank_account_number" value="<?php echo esc_attr( $me->bank_account_number ); ?>" maxlength="30" inputmode="numeric"></div></label>
				</div>
				<label class="zp-field"><span>Account name</span><div class="zp-input"><input type="text" name="bank_account_name" value="<?php echo esc_attr( $me->bank_account_name ); ?>" maxlength="120"></div></label>
				<label class="zp-field"><span>Telegram username for receipts</span><div class="zp-input"><?php echo zp_icon( 'send', 18 ); // phpcs:ignore ?><input type="text" name="telegram" value="<?php echo esc_attr( $me->telegram ); ?>" maxlength="80" placeholder="@yourusername"></div><small>Customers get a button to send you their payment receipt.</small></label>
			</section>
		</div>
		<div class="zp-savebar"><button class="zp-btn zp-btn--primary zp-btn--lg" type="submit">Save store settings</button></div>
	</form>
<?php endif; ?>
