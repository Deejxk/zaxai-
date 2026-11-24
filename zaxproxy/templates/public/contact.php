<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$email = zp_get( 'support_email' );
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-anim"><span class="zp-eyebrow">Contact</span><h1>We would love to hear from you</h1><p>Questions before you buy, or need help with an order? Send us a message.</p></div>
		<div class="zp-grid2 zp-grid2--wide">
			<section class="zp-card zp-anim" style="--d:1">
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'contact' ); ?>
					<input type="text" name="website" class="zp-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					<div class="zp-fieldrow" style="margin-top:0">
						<label class="zp-field"><span>Your name</span><div class="zp-input"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?><input type="text" name="name" required autocomplete="name"></div></label>
						<label class="zp-field"><span>Email</span><div class="zp-input"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?><input type="email" name="email" required autocomplete="email"></div></label>
					</div>
					<label class="zp-field"><span>Message</span><textarea name="message" rows="6" class="zp-textarea" required placeholder="How can we help?"></textarea></label>
					<button class="zp-btn zp-btn--primary zp-btn--lg" type="submit">Send message</button>
				</form>
			</section>
			<section class="zp-card zp-anim" style="--d:2">
				<div class="zp-empty__art" style="margin:0 auto 8px"><?php echo zp_illustration( 'support' ); // phpcs:ignore ?></div>
				<ul class="zp-list">
					<?php if ( is_email( $email ) ) : ?>
						<li><span class="zp-list__ico"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Email</strong><span><a href="mailto:<?php echo esc_attr( $email ); ?>"><?php echo esc_html( $email ); ?></a></span></div></li>
					<?php endif; ?>
					<li><span class="zp-list__ico"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Already a customer?</strong><span><a href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>">Open a support ticket</a> for the fastest reply.</span></div></li>
					<li><span class="zp-list__ico"><?php echo zp_icon( 'help', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Quick answers</strong><span><a href="<?php echo esc_url( zp_route_url( 'faq' ) ); ?>">Read the FAQ</a></span></div></li>
				</ul>
			</section>
		</div>
	</div>
</section>
