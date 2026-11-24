<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$types  = zp_enabled_types();
$prices = zp_public_prices();
$cta    = is_user_logged_in() ? 'buy' : 'register';
$perks  = array(
	'ipv4'   => array( 'Dedicated datacenter IP', 'HTTP(S) and SOCKS5 ports', 'Login or IP whitelist access', 'Extend any time' ),
	'ipv6'   => array( 'Sold in batches of 10+', 'Choose HTTP(S) or SOCKS5', 'Lowest cost per IP', 'Renew the whole batch' ),
	'isp'    => array( 'Static residential IP', 'Real provider networks', 'HTTP(S) and SOCKS5 ports', 'Extend any time' ),
	'mobile' => array( '4G / LTE carrier IP', 'Choose operator and rotation', 'Rotate on demand by link', 'HTTP(S) and SOCKS5 ports' ),
);
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-anim"><span class="zp-eyebrow">Pricing</span><h1>Pay only for what you rent</h1><p>No subscriptions and no setup fees. Prices are in <?php echo esc_html( zp_get( 'currency_code' ) ); ?> and depend on type, country, period and quantity.</p></div>
		<div class="zp-pplans">
			<?php
			$i = 0;
			foreach ( $types as $slug => $t ) :
				$i++;
				?>
				<div class="zp-card zp-pplan zp-anim" style="--d:<?php echo (int) $i; ?>">
					<span class="zp-type__ico"><?php echo zp_icon( $t['icon'], 22 ); // phpcs:ignore ?></span>
					<h3><?php echo esc_html( $t['label'] ); ?></h3>
					<p><?php echo esc_html( $t['desc'] ); ?></p>
					<div class="zp-pplan__price">
						<?php if ( isset( $prices[ $slug ] ) ) : ?>
							<span>from</span><strong><?php echo esc_html( zp_money( $prices[ $slug ]['price'] ) ); ?></strong><span>per proxy / <?php echo (int) $prices[ $slug ]['days']; ?> days</span>
						<?php else : ?>
							<strong class="zp-pplan__live">Live pricing</strong><span>shown at checkout</span>
						<?php endif; ?>
					</div>
					<ul>
						<?php foreach ( $perks[ $slug ] as $perk ) : ?><li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?><?php echo esc_html( $perk ); ?></li><?php endforeach; ?>
						<?php if ( isset( $prices[ $slug ] ) ) : ?><li><?php echo zp_icon( 'check', 16 ); // phpcs:ignore ?><?php echo (int) $prices[ $slug ]['countries']; ?> locations available</li><?php endif; ?>
					</ul>
					<a class="zp-btn zp-btn--primary zp-btn--block" href="<?php echo esc_url( zp_url( $cta, 'buy' === $cta ? array( 'type' => $slug ) : array() ) ); ?>">Buy <?php echo esc_html( $t['label'] ); ?></a>
				</div>
			<?php endforeach; ?>
		</div>
		<p class="zp-muted zp-center" style="margin-top:22px">"From" prices are per proxy for a standard location. Your exact price is calculated live before you pay.</p>
	</div>
</section>
