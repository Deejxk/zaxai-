<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$faqs = zp_faqs();
?>
<section class="zp-helphero zp-anim">
	<div><h2>How can we help?</h2><p>Step-by-step guides to connect your proxy on Windows, Android and iPhone, plus quick answers.</p><a class="zp-btn zp-btn--light zp-btn--sm" href="#connect">Connect my proxy</a></div>
	<div class="zp-helphero__art"><?php echo zp_illustration( 'support' ); // phpcs:ignore ?></div>
</section>

<?php include ZP_DIR . 'templates/user/setup-guide.php'; ?>

<div class="zp-grid3">
	<div class="zp-card zp-guide zp-anim" style="--d:1"><span class="zp-type__ico"><?php echo zp_icon( 'globe', 22 ); // phpcs:ignore ?></span><strong>Browser setup</strong><p>Open your browser or extension proxy settings, enter the IP and HTTP(S) port, then sign in with the proxy username and password.</p></div>
	<div class="zp-card zp-guide zp-anim" style="--d:2"><span class="zp-type__ico"><?php echo zp_icon( 'server', 22 ); // phpcs:ignore ?></span><strong>Apps &amp; bots</strong><p>Export your list in <span class="zp-mono">ip:port:user:pass</span> or URL format from My proxies and paste it into your tool.</p></div>
	<div class="zp-card zp-guide zp-anim" style="--d:3"><span class="zp-type__ico"><?php echo zp_icon( 'phone', 22 ); // phpcs:ignore ?></span><strong>Mobile rotation</strong><p>Mobile proxies rotate on a timer, or on demand with the rotate button on each proxy card.</p></div>
</div>

<section class="zp-card zp-anim" style="--d:4">
	<div class="zp-card__head"><h3>Frequently asked questions</h3></div>
	<div class="zp-faq">
		<?php foreach ( $faqs as $q => $a ) : ?>
			<details><summary><?php echo esc_html( $q ); ?><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p><?php echo esc_html( $a ); ?></p></details>
		<?php endforeach; ?>
	</div>
</section>

<section class="zp-cta zp-anim" style="--d:5">
	<div><strong>Still stuck?</strong><span>Open a ticket and our team will get back to you.</span></div>
	<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?>Contact support</a>
</section>
