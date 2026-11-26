<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user @var float $balance */
global $wpdb;
$stats   = ZP_Orders::user_stats( $user->ID );
$proxies = array_slice( ZP_Orders::user_proxies( $user->ID, array( 'state' => 'active' ) ), 0, 5 );
$orders  = ZP_Orders::user_orders( $user->ID, 5 );
$first   = explode( ' ', trim( $user->display_name ) )[0];
$hour    = (int) wp_date( 'G' );
$greet   = $hour < 12 ? 'Good morning' : ( $hour < 18 ? 'Good afternoon' : 'Good evening' );
$sym     = zp_get( 'currency_symbol', '₦' );
$now     = zp_now();
$pt      = zp_table( 'proxies' );
$paidq   = "refunded = 0 AND status IN ('completed','processing','review')";

// Spending per day, last 60 days: [ amount, orders ].
$day = array();
for ( $i = 59; $i >= 0; $i-- ) {
	$day[ gmdate( 'Y-m-d', time() - $i * DAY_IN_SECONDS ) ] = array( 0.0, 0 );
}
foreach ( $wpdb->get_results( $wpdb->prepare( 'SELECT DATE(created_at) d, SUM(amount) s, COUNT(*) n FROM ' . zp_table( 'orders' ) . " WHERE user_id = %d AND $paidq AND created_at >= %s GROUP BY DATE(created_at)", $user->ID, gmdate( 'Y-m-d 00:00:00', time() - 59 * DAY_IN_SECONDS ) ) ) as $r ) { // phpcs:ignore
	if ( isset( $day[ $r->d ] ) ) {
		$day[ $r->d ] = array( (float) $r->s, (int) $r->n );
	}
}
$sum    = function ( $rows ) {
	$t = array( 0.0, 0 );
	foreach ( $rows as $v ) {
		$t[0] += $v[0];
		$t[1] += $v[1];
	}
	return $t;
};
$d30    = array_slice( $day, 30, 30, true );
$d7     = array_slice( $day, 53, 7, true );
$cur30  = $sum( $d30 );
$prev30 = $sum( array_slice( $day, 0, 30, true ) );
$cur7   = $sum( $d7 );
$prev7  = $sum( array_slice( $day, 46, 7, true ) );
$tipped = function ( $rows ) {
	$out = array();
	foreach ( $rows as $d => $v ) {
		$out[ $d ] = array( $v[0], $v[1] . ( 1 === $v[1] ? ' order' : ' orders' ) );
	}
	return $out;
};
$amounts = function ( $rows ) {
	$o = array();
	foreach ( $rows as $v ) {
		$o[] = $v[0];
	}
	return $o;
};

// The proxies you hold right now.
$by_type = $wpdb->get_results( $wpdb->prepare( "SELECT proxy_type t, COUNT(*) n FROM $pt WHERE user_id = %d AND date_end > %s GROUP BY proxy_type ORDER BY n DESC", $user->ID, $now ) ); // phpcs:ignore
$by_ctry = $wpdb->get_results( $wpdb->prepare( "SELECT country c, COUNT(*) n FROM $pt WHERE user_id = %d AND date_end > %s GROUP BY country ORDER BY n DESC LIMIT 5", $user->ID, $now ) ); // phpcs:ignore
$in14    = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $pt WHERE user_id = %d AND date_end > %s AND date_end < %s", $user->ID, $now, gmdate( 'Y-m-d H:i:s', time() + 14 * DAY_IN_SECONDS ) ) ); // phpcs:ignore
$next    = $wpdb->get_var( $wpdb->prepare( "SELECT MIN(date_end) FROM $pt WHERE user_id = %d AND date_end > %s", $user->ID, $now ) ); // phpcs:ignore
$active  = (int) $stats['active'];
$soon    = (int) $stats['expiring'];
$mid     = max( 0, $in14 - $soon );
$later   = max( 0, $active - $in14 );
$segs    = array();
foreach ( $by_type as $r ) {
	$segs[] = array( zp_type_label( $r->t ), (int) $r->n, 'proxies', (int) $r->n );
}
$avg     = $stats['orders'] > 0 ? $stats['spent'] / max( 1, $stats['orders'] ) : 0;
$pct     = function ( $n ) use ( $active ) {
	return $active > 0 ? (int) round( $n / $active * 100 ) : 0;
};
?>
<section class="zp-rhero zp-dhero zp-anim">
	<div class="zp-rhero__main">
		<span class="zp-rhero__status<?php echo $active ? '' : ' is-off'; ?>"><i></i><?php echo $active ? esc_html( $active . ( 1 === $active ? ' proxy online' : ' proxies online' ) ) : 'No active proxies yet'; ?></span>
		<h2><?php echo esc_html( $greet . ', ' . $first ); ?></h2>
		<p><?php echo $active ? 'Here is how your proxies and spending look today.' : 'Pick a location, choose a plan and your private proxies are delivered here in minutes.'; ?></p>
	</div>
	<div class="zp-rhero__actions">
		<a class="zp-btn zp-btn--light" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><?php echo zp_icon( 'cart', 18 ); // phpcs:ignore ?>Buy proxies</a>
		<a class="zp-btn zp-btn--glass" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?>Add funds</a>
	</div>
	<div class="zp-here" data-zp-here>
		<div class="zp-here__item"><span class="zp-here__ico"><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?></span><div><span>Your current IP</span><strong class="zp-mono" data-here="ip">Checking…</strong></div></div>
		<div class="zp-here__item"><span class="zp-here__ico"><?php echo zp_icon( 'server', 18 ); // phpcs:ignore ?></span><div><span>IP location (state, country)</span><strong data-here="place">Checking…</strong><small data-here="isp"></small></div></div>
		<div class="zp-here__item"><span class="zp-here__ico"><?php echo zp_icon( 'phone', 18 ); // phpcs:ignore ?></span><div><span>Device GPS location</span><strong data-here="gps">Not shown yet</strong><small class="zp-mono" data-here="gpsxy"></small><small data-here="gpsnote">Tap Show to see where this device really is.</small></div><button type="button" class="zp-here__btn" data-here="gpsbtn">Show</button></div>
	</div>
	<div class="zp-rhero__today">
		<div><span>Wallet balance</span><strong data-zp-count="<?php echo esc_attr( $balance ); ?>" data-prefix="<?php echo esc_attr( $sym ); ?>"><?php echo esc_html( zp_money( $balance ) ); ?></strong></div>
		<div><span>Active proxies</span><strong data-zp-count="<?php echo (int) $active; ?>"><?php echo (int) $active; ?></strong></div>
		<div><span>Expiring in 3 days</span><strong data-zp-count="<?php echo (int) $soon; ?>"><?php echo (int) $soon; ?></strong></div>
		<div><span>Next expiry</span><strong><?php echo $next ? esc_html( zp_time_left( $next ) ) : '—'; ?></strong></div>
	</div>
</section>

<?php if ( $soon ) : ?>
	<div class="zp-alert zp-alert--info"><?php echo zp_icon( 'clock', 18 ); // phpcs:ignore ?><span><?php echo esc_html( sprintf( '%d %s within 3 days. Renew now to keep the same IP.', $soon, 1 === $soon ? 'proxy expires' : 'proxies expire' ) ); ?></span><a class="zp-btn zp-btn--sm zp-btn--primary" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">Renew</a></div>
<?php endif; ?>

<section class="zp-kpis">
	<a class="zp-kpi zp-anim" style="--d:1" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">
		<div class="zp-kpi__top"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'wallet' ); // phpcs:ignore ?></span></div>
		<span class="zp-kpi__label">Wallet balance</span><strong class="zp-kpi__value" data-zp-count="<?php echo esc_attr( $balance ); ?>" data-prefix="<?php echo esc_attr( $sym ); ?>"><?php echo esc_html( zp_money( $balance ) ); ?></strong><span class="zp-kpi__sub">Tap to add funds</span>
	</a>
	<a class="zp-kpi zp-anim" style="--d:2" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">
		<div class="zp-kpi__top"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'globe' ); // phpcs:ignore ?></span><?php if ( $soon ) : ?><b class="zp-trend zp-trend--down"><?php echo (int) $soon; ?> expiring</b><?php endif; ?></div>
		<span class="zp-kpi__label">Active proxies</span><strong class="zp-kpi__value" data-zp-count="<?php echo (int) $active; ?>"><?php echo (int) $active; ?></strong><span class="zp-kpi__sub"><?php echo count( $by_ctry ); ?> <?php echo 1 === count( $by_ctry ) ? 'location' : 'locations'; ?> · <?php echo count( $by_type ); ?> <?php echo 1 === count( $by_type ) ? 'network' : 'networks'; ?></span>
	</a>
	<a class="zp-kpi zp-anim" style="--d:3" href="<?php echo esc_url( zp_url( 'orders' ) ); ?>">
		<div class="zp-kpi__top"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'chart' ); // phpcs:ignore ?></span><?php echo zp_trend( zp_delta( $cur30[0], $prev30[0] ) ); // phpcs:ignore ?></div>
		<span class="zp-kpi__label">Spent · 30 days</span><strong class="zp-kpi__value" data-zp-count="<?php echo esc_attr( $cur30[0] ); ?>" data-prefix="<?php echo esc_attr( $sym ); ?>"><?php echo esc_html( zp_money( $cur30[0] ) ); ?></strong><span class="zp-kpi__sub">vs previous 30 days</span>
		<?php echo zp_spark( $amounts( $d30 ) ); // phpcs:ignore ?>
	</a>
	<a class="zp-kpi zp-anim" style="--d:4" href="<?php echo esc_url( zp_url( 'orders' ) ); ?>">
		<div class="zp-kpi__top"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'receipt' ); // phpcs:ignore ?></span></div>
		<span class="zp-kpi__label">Total spent</span><strong class="zp-kpi__value" data-zp-count="<?php echo esc_attr( $stats['spent'] ); ?>" data-prefix="<?php echo esc_attr( $sym ); ?>"><?php echo esc_html( zp_money( $stats['spent'] ) ); ?></strong><span class="zp-kpi__sub"><?php echo (int) $stats['orders']; ?> <?php echo 1 === (int) $stats['orders'] ? 'order' : 'orders'; ?> all time</span>
	</a>
</section>

<div class="zp-grid2 zp-grid2--wide">
	<section class="zp-card zp-anim zp-salescard" style="--d:5" data-zp-ranges>
		<div class="zp-card__head"><h3>Spending overview</h3><div class="zp-seg" role="tablist"><button type="button" data-range="7">7 days</button><button type="button" class="is-active" data-range="30">30 days</button></div></div>
		<?php foreach ( array( '7' => array( $cur7, $prev7, 7 ), '30' => array( $cur30, $prev30, 30 ) ) as $key => $pair ) : ?>
			<div class="zp-salesum" data-range="<?php echo esc_attr( $key ); ?>"<?php echo '30' === (string) $key ? '' : ' hidden'; ?>>
				<div><span>Spent</span><strong><?php echo esc_html( zp_money( $pair[0][0] ) ); ?></strong><?php echo zp_trend( zp_delta( $pair[0][0], $pair[1][0] ), false ); // phpcs:ignore ?></div>
				<div><span>Orders</span><strong><?php echo (int) $pair[0][1]; ?></strong><?php echo zp_trend( zp_delta( $pair[0][1], $pair[1][1] ), false ); // phpcs:ignore ?></div>
				<div><span>Daily average</span><strong><?php echo esc_html( zp_money( round( $pair[0][0] / $pair[2] ) ) ); ?></strong><b class="zp-trend">per day</b></div>
			</div>
		<?php endforeach; ?>
		<div data-range="30"><?php echo zp_area_chart( $tipped( $d30 ), '30' ); // phpcs:ignore ?></div>
		<div data-range="7" hidden><?php echo zp_area_chart( $tipped( $d7 ), '7' ); // phpcs:ignore ?></div>
		<p class="zp-chart__hint">Tap a day to see what you spent and how many orders you placed.</p>
	</section>

	<section class="zp-card zp-anim" style="--d:6">
		<div class="zp-card__head"><h3>Your proxies</h3><a href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">Manage</a></div>
		<?php if ( ! $active ) : ?>
			<?php echo zp_donut( array(), '0', 'active' ); // phpcs:ignore ?>
			<p class="zp-muted" style="margin-top:12px">You have no active proxies. Your networks and locations will be broken down here once you buy.</p>
		<?php else : ?>
			<?php echo zp_donut( $segs, (string) $active, 'active' ); // phpcs:ignore ?>
		<?php endif; ?>
		<div class="zp-meters">
			<div class="zp-meters__row" style="margin:0"><span>Time left on your proxies</span></div>
			<div class="zp-stack3" aria-hidden="true"><i class="is-bad" style="--w:<?php echo (int) $pct( $soon ); ?>%"></i><i class="is-warn" style="--w:<?php echo (int) $pct( $mid ); ?>%"></i><i class="is-ok" style="--w:<?php echo (int) $pct( $later ); ?>%"></i></div>
			<ul class="zp-keys">
				<li><i class="is-bad"></i>Under 3 days<strong><?php echo (int) $soon; ?></strong></li>
				<li><i class="is-warn"></i>3 to 14 days<strong><?php echo (int) $mid; ?></strong></li>
				<li><i class="is-ok"></i>Over 14 days<strong><?php echo (int) $later; ?></strong></li>
			</ul>
		</div>
	</section>
</div>

<section class="zp-card zp-anim" style="--d:7">
	<div class="zp-card__head"><h3>Quick actions</h3></div>
	<div class="zp-todo">
		<a class="zp-todo__item" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'cart', 18 ); // phpcs:ignore ?></span><div><strong>Buy proxies</strong><span>Choose a network and location</span></div><b>→</b></a>
		<a class="zp-todo__item<?php echo $soon ? ' is-hot' : ''; ?>" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'refresh', 18 ); // phpcs:ignore ?></span><div><strong>Renew proxies</strong><span>Keep the same IP for longer</span></div><b><?php echo $soon ? (int) $soon : '→'; ?></b></a>
		<a class="zp-todo__item" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'wallet', 18 ); // phpcs:ignore ?></span><div><strong>Add funds</strong><span>Top up by bank transfer</span></div><b>→</b></a>
		<a class="zp-todo__item" href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span><div><strong>Get support</strong><span>We answer around the clock</span></div><b>→</b></a>
	</div>
</section>

<div class="zp-grid2">
	<section class="zp-card zp-anim" style="--d:8">
		<div class="zp-card__head"><h3>Top locations</h3><span class="zp-sub">Active proxies</span></div>
		<?php if ( ! $by_ctry ) : ?>
			<p class="zp-muted">The countries your proxies are in will be listed here.</p>
		<?php else : ?>
			<ul class="zp-list zp-rank">
				<?php foreach ( $by_ctry as $r ) : ?>
					<li><span class="zp-flag"><?php echo esc_html( zp_flag( $r->c ) ); ?></span><div class="zp-list__main"><strong><?php echo esc_html( zp_country_name( $r->c ) ); ?></strong><div class="zp-meter"><i style="--w:<?php echo (int) max( 4, round( $r->n / max( 1, (int) $by_ctry[0]->n ) * 100 ) ); ?>%"></i></div></div><div class="zp-list__side"><strong><?php echo (int) $r->n; ?></strong><span><?php echo (int) $pct( (int) $r->n ); ?>%</span></div></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<div class="zp-meters__pair" style="margin-top:16px"><div><span>Average order</span><strong><?php echo esc_html( zp_money( round( $avg ) ) ); ?></strong></div><div><span>Orders · 30 days</span><strong><?php echo (int) $cur30[1]; ?></strong></div></div>
	</section>

	<section class="zp-card zp-anim" style="--d:9">
		<div class="zp-card__head"><h3>Active proxies</h3><a href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">View all</a></div>
		<?php if ( ! $proxies ) : ?>
			<?php echo zp_empty( 'empty-proxies', 'No proxies yet', 'Your purchased proxies will show up here with credentials and expiry dates.', zp_url( 'buy' ), 'Buy your first proxy' ); // phpcs:ignore ?>
		<?php else : ?>
			<ul class="zp-list">
				<?php
				$geo_budget = 3; // new look-ups per page view; known ones are free
				foreach ( $proxies as $p ) :
					$geo   = ZP_App::geo( $p->ip, $geo_budget );
					$where = implode( ', ', array_unique( array_filter( array( $geo[0], $geo[1], zp_country_name( $p->country ) ) ) ) );
					?>
					<li>
						<span class="zp-flag"><?php echo esc_html( zp_flag( $p->country ) ); ?></span>
						<div class="zp-list__main"><strong class="zp-mono"><?php echo esc_html( $p->ip ); ?></strong><span><?php echo esc_html( zp_type_label( $p->proxy_type ) . ' · ' . $where ); ?></span></div>
						<div class="zp-list__side"><?php echo zp_badge( ZP_Orders::proxy_state( $p ) ); // phpcs:ignore ?><span><?php echo esc_html( zp_time_left( $p->date_end ) ); ?></span></div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>
</div>

<section class="zp-section zp-anim" style="--d:10">
	<div class="zp-section__head"><h3>Browse by type</h3><a href="<?php echo esc_url( zp_url( 'buy' ) ); ?>">See all <?php echo zp_icon( 'arrow', 16 ); // phpcs:ignore ?></a></div>
	<div class="zp-types">
		<?php foreach ( zp_enabled_types() as $slug => $t ) : ?>
			<a class="zp-type" href="<?php echo esc_url( zp_url( 'buy', array( 'type' => $slug ) ) ); ?>">
				<span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
				<strong><?php echo esc_html( $t['label'] ); ?> proxies</strong>
				<span><?php echo esc_html( $t['desc'] ); ?></span>
				<em><?php echo zp_icon( 'arrow', 16 ); // phpcs:ignore ?></em>
			</a>
		<?php endforeach; ?>
	</div>
</section>

<section class="zp-card zp-anim" style="--d:11">
	<div class="zp-card__head"><h3>Recent orders</h3><a href="<?php echo esc_url( zp_url( 'orders' ) ); ?>">View all</a></div>
	<?php if ( ! $orders ) : ?>
		<?php echo zp_empty( 'empty-orders', 'Nothing ordered yet', 'Every purchase and renewal is recorded here.' ); // phpcs:ignore ?>
	<?php else : ?>
		<ul class="zp-list">
			<?php foreach ( $orders as $o ) : ?>
				<li>
					<span class="zp-list__ico"><?php echo zp_icon( 'extend' === $o->kind ? 'refresh' : 'cart', 18 ); // phpcs:ignore ?></span>
					<div class="zp-list__main"><strong><?php echo esc_html( ( 'extend' === $o->kind ? 'Renewal · ' : '' ) . $o->qty . ' × ' . zp_type_label( $o->proxy_type ) ); ?></strong><span><?php echo esc_html( zp_country_name( $o->country ) . ' · ' . $o->days . ' days · ' . zp_date( $o->created_at, false ) ); ?></span></div>
					<div class="zp-list__side"><strong><?php echo esc_html( zp_money( $o->amount ) ); ?></strong><?php echo zp_badge( $o->status ); // phpcs:ignore ?></div>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
