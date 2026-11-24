<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$email = zp_get( 'support_email' );
$tg    = zp_telegram_url();
?>
<section class="zp-ppage">
	<div class="zp-wrap">
		<div class="zp-phead2 zp-anim"><span class="zp-eyebrow">Contact</span><h1>We would love to hear from you</h1><p>Questions before you buy, help with an order, or interested in reselling? Choose the way that suits you and we will get back to you.</p></div>

		<div class="zp-grid3 zp-pcontacts">
			<?php if ( $tg ) : ?>
				<a class="zp-card zp-pfeature zp-anim" style="--d:1" href="<?php echo esc_url( $tg ); ?>" target="_blank" rel="noopener noreferrer"><span class="zp-type__ico"><?php echo zp_icon( 'send', 22 ); // phpcs:ignore ?></span><strong>Chat on Telegram</strong><p>The fastest way to reach us, and where you send payment receipts.</p><b>Open chat →</b></a>
			<?php endif; ?>
			<?php if ( is_email( $email ) ) : ?>
				<a class="zp-card zp-pfeature zp-anim" style="--d:2" href="mailto:<?php echo esc_attr( $email ); ?>"><span class="zp-type__ico"><?php echo zp_icon( 'mail', 22 ); // phpcs:ignore ?></span><strong>Email us</strong><p>For detailed questions, partnerships and anything that needs a written reply.</p><b><?php echo esc_html( $email ); ?></b></a>
			<?php endif; ?>
			<a class="zp-card zp-pfeature zp-anim" style="--d:3" href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>"><span class="zp-type__ico"><?php echo zp_icon( 'ticket', 22 ); // phpcs:ignore ?></span><strong>Open a support ticket</strong><p>Already a customer? Tickets go straight to our team with your account details attached.</p><b>Go to support →</b></a>
		</div>

		<div class="zp-grid2 zp-grid2--wide" style="margin-top:20px">
			<section class="zp-card zp-anim" style="--d:2">
				<div class="zp-card__head"><h3>Send us a message</h3></div>
				<form method="post" class="zp-form">
					<?php zp_nonce_field( 'contact' ); ?>
					<input type="text" name="website" class="zp-hp" tabindex="-1" autocomplete="off" aria-hidden="true">
					<div class="zp-fieldrow" style="margin-top:0">
						<label class="zp-field"><span>Your name</span><div class="zp-input"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?><input type="text" name="name" required autocomplete="name"></div></label>
						<label class="zp-field"><span>Email</span><div class="zp-input"><?php echo zp_icon( 'mail', 18 ); // phpcs:ignore ?><input type="email" name="email" required autocomplete="email"></div></label>
					</div>
					<label class="zp-field"><span>Message</span><textarea name="message" rows="6" class="zp-textarea" required placeholder="How can we help? If it is about an order, include your order number."></textarea></label>
					<button class="zp-btn zp-btn--primary zp-btn--lg" type="submit">Send message</button>
					<p class="zp-summary__note" style="margin:0">We reply by email, usually within one business day.</p>
				</form>
			</section>
			<section class="zp-card zp-anim" style="--d:3">
				<div class="zp-empty__art" style="margin:0 auto 8px"><?php echo zp_illustration( 'support' ); // phpcs:ignore ?></div>
				<div class="zp-card__head"><h3>Before you write</h3></div>
				<ul class="zp-list zp-list--link">
					<li><a href="<?php echo esc_url( zp_route_url( 'faq' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'help', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Read the FAQ</strong><span>Most questions are answered there</span></div></a></li>
					<li><a href="<?php echo esc_url( zp_route_url( 'pricing' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'wallet', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>See pricing</strong><span>Live prices for every proxy type</span></div></a></li>
					<li><a href="<?php echo esc_url( zp_route_url( 'refund-policy' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'refresh', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Refund policy</strong><span>When and how refunds work</span></div></a></li>
					<li><a href="<?php echo esc_url( zp_route_url( 'acceptable-use' ) ); ?>"><span class="zp-list__ico"><?php echo zp_icon( 'shield', 18 ); // phpcs:ignore ?></span><div class="zp-list__main"><strong>Report abuse</strong><span>Tell us about misuse of one of our IPs</span></div></a></li>
				</ul>
			</section>
		</div>
	</div>
</section>
