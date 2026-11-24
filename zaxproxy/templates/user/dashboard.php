<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user @var float $balance */
$stats   = ZP_Orders::user_stats( $user->ID );
$proxies = array_slice( ZP_Orders::user_proxies( $user->ID, array( 'state' => 'active' ) ), 0, 5 );
$orders  = ZP_Orders::user_orders( $user->ID, 5 );
$first   = explode( ' ', trim( $user->display_name ) )[0];
$hour    = (int) wp_date( 'G' );
$greet   = $hour < 12 ? 'Good morning' : ( $hour < 18 ? 'Good afternoon' : 'Good evening' );
?>
<section class="zp-hero zp-anim">
	<div class="zp-hero__text">
		<span class="zp-eyebrow"><?php echo esc_html( $greet ); ?></span>
		<h2><?php echo esc_html( $first ); ?>, your network is <?php echo $stats['active'] ? 'online' : 'ready to launch'; ?>.</h2>
		<p><?php echo $stats['active'] ? esc_html( sprintf( '%d active %s working for you right now.', $stats['active'], 1 === $stats['active'] ? 'proxy is' : 'proxies are' ) ) : 'Pick a location, choose a plan and get private proxies delivered to this dashboard in moments.'; ?></p>
		<div class="zp-row">
			<a class="zp-btn zp-btn--light" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><?php echo zp_icon( 'cart', 18 ); // phpcs:ignore ?>Buy proxies</a>
			<a class="zp-btn zp-btn--glass" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?>Add funds</a>
		</div>
	</div>
	<div class="zp-hero__art"><?php echo zp_illustration( 'globe' ); // phpcs:ignore ?></div>
</section>

<section class="zp-stats">
	<a class="zp-stat zp-anim" style="--d:1" href="<?php echo esc_url( zp_url( 'wallet' ) ); ?>">
		<span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'wallet' ); // phpcs:ignore ?></span>
		<span class="zp-stat__label">Wallet balance</span>
		<strong class="zp-stat__value" data-zp-count="<?php echo esc_attr( $balance ); ?>" data-prefix="<?php echo esc_attr( zp_get( 'currency_symbol' ) ); ?>" data-decimals="2"><?php echo esc_html( zp_money( $balance ) ); ?></strong>
	</a>
	<a class="zp-stat zp-anim" style="--d:2" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">
		<span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'globe' ); // phpcs:ignore ?></span>
		<span class="zp-stat__label">Active proxies</span>
		<strong class="zp-stat__value" data-zp-count="<?php echo (int) $stats['active']; ?>"><?php echo (int) $stats['active']; ?></strong>
	</a>
	<a class="zp-stat zp-anim" style="--d:3" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">
		<span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'clock' ); // phpcs:ignore ?></span>
		<span class="zp-stat__label">Expiring in 3 days</span>
		<strong class="zp-stat__value" data-zp-count="<?php echo (int) $stats['expiring']; ?>"><?php echo (int) $stats['expiring']; ?></strong>
	</a>
	<a class="zp-stat zp-anim" style="--d:4" href="<?php echo esc_url( zp_url( 'orders' ) ); ?>">
		<span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'chart' ); // phpcs:ignore ?></span>
		<span class="zp-stat__label">Total spent</span>
		<strong class="zp-stat__value" data-zp-count="<?php echo esc_attr( $stats['spent'] ); ?>" data-prefix="<?php echo esc_attr( zp_get( 'currency_symbol' ) ); ?>" data-decimals="2"><?php echo esc_html( zp_money( $stats['spent'] ) ); ?></strong>
	</a>
</section>

<section class="zp-section zp-anim" style="--d:5">
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

<div class="zp-grid2">
	<section class="zp-card zp-anim" style="--d:6">
		<div class="zp-card__head"><h3>Active proxies</h3><a href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">View all</a></div>
		<?php if ( ! $proxies ) : ?>
			<?php echo zp_empty( 'empty-proxies', 'No proxies yet', 'Your purchased proxies will show up here with credentials and expiry dates.', zp_url( 'buy' ), 'Buy your first proxy' ); // phpcs:ignore ?>
		<?php else : ?>
			<ul class="zp-list">
				<?php foreach ( $proxies as $p ) : ?>
					<li>
						<span class="zp-flag"><?php echo esc_html( zp_flag( $p->country ) ); ?></span>
						<div class="zp-list__main"><strong class="zp-mono"><?php echo esc_html( $p->ip ); ?></strong><span><?php echo esc_html( zp_type_label( $p->proxy_type ) . ' · ' . zp_country_name( $p->country ) ); ?></span></div>
						<div class="zp-list__side"><?php echo zp_badge( ZP_Orders::proxy_state( $p ) ); // phpcs:ignore ?><span><?php echo esc_html( zp_time_left( $p->date_end ) ); ?></span></div>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="zp-card zp-anim" style="--d:7">
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
</div>
