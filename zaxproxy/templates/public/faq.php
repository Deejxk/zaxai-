<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$groups = zp_faq_groups();
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-anim"><span class="zp-eyebrow">FAQ</span><h1>Frequently asked questions</h1><p>Everything you need to know about our proxies, payments, delivery and the reseller program.</p></div>
		<div class="zp-pfaq">
			<aside class="zp-plegal__nav zp-anim">
				<strong>Topics</strong>
				<?php foreach ( $groups as $name => $items ) : ?>
					<a href="#faq-<?php echo esc_attr( sanitize_title( $name ) ); ?>"><?php echo esc_html( $name ); ?></a>
				<?php endforeach; ?>
			</aside>
			<div>
				<?php
				$i = 0;
				foreach ( $groups as $name => $items ) :
					$i++;
					?>
					<h2 class="zp-pfaq__title zp-reveal" id="faq-<?php echo esc_attr( sanitize_title( $name ) ); ?>"><?php echo esc_html( $name ); ?></h2>
					<div class="zp-card zp-faq zp-reveal">
						<?php foreach ( $items as $q => $a ) : ?>
							<details><summary><?php echo esc_html( $q ); ?><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p><?php echo esc_html( $a ); ?></p></details>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
				<div class="zp-cta zp-reveal" style="margin-top:24px"><div><strong>Did not find your answer?</strong><span>Our team is happy to help.</span></div><a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_route_url( 'contact' ) ); ?>">Contact us</a></div>
			</div>
		</div>
	</div>
</section>
