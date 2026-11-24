<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="zp-ppage">
	<div class="zp-wrap zp-pnarrow">
		<div class="zp-phead2 zp-anim"><span class="zp-eyebrow">FAQ</span><h1>Frequently asked questions</h1><p>Everything you need to know before you buy.</p></div>
		<div class="zp-card zp-faq zp-anim" style="--d:1">
			<?php foreach ( zp_faqs() as $q => $a ) : ?>
				<details><summary><?php echo esc_html( $q ); ?><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p><?php echo esc_html( $a ); ?></p></details>
			<?php endforeach; ?>
		</div>
		<div class="zp-cta zp-anim" style="--d:2;margin-top:20px"><div><strong>Did not find your answer?</strong><span>Our team is happy to help.</span></div><a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_route_url( 'contact' ) ); ?>">Contact us</a></div>
	</div>
</section>
