<?php
/**
 * Documentation: how to set up a hotspot and connect billing.
 * Shown publicly at /docs and inside the dashboard at /dashboard/docs.
 *
 * @package ZaxConnect
 */

defined( 'ABSPATH' ) || exit;

/** Docs sections: id => [icon, title, html]. */
function zxc_docs_sections( $t = null ) {
	$name   = esc_html( zxc_setting( 'platform_name' ) );
	$dns    = esc_html( $t && $t->hotspot_dns ? $t->hotspot_dns : 'login.wifi' );
	$host   = esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) );
	$portal = $t ? esc_html( zxc_portal_url( $t ) ) : esc_html( home_url( '/h/your-business' ) );
	$wh     = $t ? esc_html( rest_url( 'zaxconnect/v1/flutterwave/' . $t->slug ) ) : esc_html( rest_url( 'zaxconnect/v1/flutterwave/your-business' ) );
	$trial  = (int) zxc_setting( 'trial_days' );
	$a      = function ( $page, $label ) {
		return '<a class="doc-link" href="' . esc_url( zxc_dash_url( $page ) ) . '">' . esc_html( $label ) . '</a>';
	};
	$tip    = function ( $html, $kind = 'info' ) {
		return '<div class="doc-tip ' . $kind . '">' . zxc_icon( 'warn' === $kind ? 'alert' : 'sparkles' ) . '<div>' . $html . '</div></div>';
	};

	return array(
		'overview'  => array(
			'book',
			'How ' . $name . ' works',
			'<p>' . $name . ' turns your internet connection into a Wi-Fi business. Customers join your Wi-Fi, see <b>your branded page</b>, pay with card, bank transfer or USSD, and get online straight away. You don’t need to be there.</p>
			<div class="topology"><div class="node"><b>Internet</b><small>MTN ODU, fibre or Starlink</small></div><div class="ln"></div><div class="node hl"><b>MikroTik router</b><small>Runs the hotspot</small></div><div class="ln"></div><div class="node"><b>Access points</b><small>Customers’ Wi-Fi</small></div><div class="ln"></div><div class="node"><b>' . $name . '</b><small>Portal, payments, vouchers</small></div></div>
			<ol class="steps-list">
				<li>A customer connects to your Wi-Fi. The MikroTik router shows them your portal.</li>
				<li>They choose a plan and pay. The money goes to <b>your own Flutterwave account</b>.</li>
				<li>' . $name . ' creates a voucher and the router picks it up within about 10 seconds.</li>
				<li>The customer is logged in automatically. The router enforces the time, speed and data limits.</li>
			</ol>
			<p>Your router only makes outgoing connections, so it works on MTN, Airtel, Glo or Starlink. You don’t need a public IP address or a VPS.</p>',
		),
		'need'      => array(
			'router',
			'What you need',
			'<div class="doc-grid">
				<div><b>Internet source</b><span>MTN 5G ODU, fibre or Starlink, with an Ethernet port.</span></div>
				<div><b>MikroTik router</b><span>Any model with Hotspot, e.g. hAP ax lite, hAP ac², hEX or RB951. RouterOS v6.45+ or v7.</span></div>
				<div><b>Access points</b><span>Optional, for bigger spaces. Ceiling or outdoor APs in bridge/AP mode.</span></div>
				<div><b>A computer</b><span>Windows or Mac with <b>WinBox</b> (free from mikrotik.com) to set up the router once.</span></div>
				<div><b>Flutterwave account</b><span>Free at flutterwave.com, so payments land in your bank account.</span></div>
				<div><b>Backup power</b><span>An inverter or UPS keeps the Wi-Fi up during outages.</span></div>
			</div>',
		),
		'account'   => array(
			'palette',
			'Step 1 · Set up your account and brand',
			'<p>When you sign up you get a <b>' . ( $trial ? $trial . '-day free trial' : 'free trial' ) . '</b> and your own customer portal:</p>
			<p><code class="doc-code">' . $portal . '</code></p>
			<ol class="steps-list">
				<li>Open ' . $a( 'branding', 'Portal & branding' ) . ' and add your logo, brand colour, a headline and your WhatsApp or phone number for customer support.</li>
				<li>Keep the <b>Hotspot DNS name</b> as <code>' . $dns . '</code> unless you know you need something else. It must match the router (Step 2).</li>
				<li>Click <b>View portal</b> to see what customers will see.</li>
			</ol>',
		),
		'router'    => array(
			'wifi',
			'Step 2 · Connect your MikroTik router',
			'<p>Do this once per location. Open ' . $a( 'locations', 'Locations & routers' ) . ' in your dashboard. It has your personal setup script.</p>
			<ol class="timeline doc-steps">
				<li><b>Wire it up</b><p>Plug your internet source into <b>ether1</b> on the MikroTik. Plug your access points into the other ports.</p></li>
				<li><b>Open WinBox</b><p>Connect your computer to the router, open WinBox, click the router under <b>Neighbors</b> and log in (user <code>admin</code>, blank password on a new router).</p></li>
				<li><b>Give the router internet</b><p>Go to <b>IP → DHCP Client</b>, click <b>+</b>, choose <b>ether1</b> and click OK. The status should become <b>bound</b>.</p></li>
				<li><b>Run Hotspot Setup</b><p>Go to <b>IP → Hotspot → Hotspot Setup</b>. Choose your LAN bridge (usually <code>bridge</code>), accept the suggested addresses, and when asked for the <b>DNS name</b> enter <code>' . $dns . '</code>. Skip creating a local user.</p></li>
				<li><b>Paste your setup script</b><p>In ' . $a( 'locations', 'Locations & routers' ) . ' click <b>Copy</b> under “Router setup script”. In WinBox open <b>New Terminal</b>, paste it and press Enter. You can run it again any time.</p></li>
				<li><b>Upload the login pages</b><p>Download <b>login.html</b> and <b>alogin.html</b> from the same page. In WinBox open <b>Files</b>, open the <code>hotspot</code> folder and drag both files in, replacing the old ones.</p></li>
				<li><b>Check it’s connected</b><p>Within a minute the location shows a green <b>Connected</b> badge in your dashboard. You’re live.</p></li>
			</ol>' .
			$tip( 'The script lets customers open <b>' . $host . '</b> and Flutterwave before they pay (the “walled garden”). If you move ' . $name . ' to a new domain, run the script again.' ),
		),
		'payments'  => array(
			'card',
			'Step 3 · Connect Flutterwave payments',
			'<p>Customer payments go <b>straight to your own Flutterwave account</b>. ' . $name . ' never holds your money.</p>
			<ol class="steps-list">
				<li>Sign in at <b>dashboard.flutterwave.com</b> and finish your business verification (KYC) so you can take live payments.</li>
				<li>Go to <b>Settings → API Keys</b>. Copy your <b>Public key</b> and <b>Secret key</b> into ' . $a( 'payments', 'Payment settings' ) . '.</li>
				<li>Go to <b>Settings → Webhooks</b> and paste this URL:<br><code class="doc-code">' . $wh . '</code></li>
				<li>Type a <b>secret hash</b> (any password-like phrase) in Flutterwave, then paste the same hash into Payment settings and save.</li>
			</ol>' .
			$tip( 'Until you add your keys, payments run in <b>test mode</b>: customers see a simulated payment page so you can try everything. Turn off “Allow simulated test payments” before you open for business.', 'warn' ),
		),
		'transfers' => array(
			'bank',
			'Accept bank transfers to your own account',
			'<p>Prefer to be paid straight into your personal or business bank account? Turn on <b>bank transfers</b> in ' . $a( 'payments', 'Payment settings' ) . ' and add your bank name, account number, account name and WhatsApp number.</p>
			<ol class="steps-list">
				<li>At checkout the customer picks <b>Transfer to your bank</b> and sees your account details and a payment reference.</li>
				<li>After paying, they tap <b>I’ve sent the money</b> and then <b>Send proof on WhatsApp</b>. This opens a chat with you, with the amount and reference already filled in.</li>
				<li>Check your bank app, then open ' . $a( 'transfers', 'Transfer approvals' ) . ' and tap <b>Approve</b> or <b>Decline</b>. When you approve, the customer gets a voucher and connects automatically.</li>
			</ol>' .
			$tip( 'Waiting transfers show as a badge in the menu and as an alert on your dashboard, so customers never wait long.' ),
		),
		'plans'     => array(
			'ticket',
			'Step 4 · Create your plans and prices',
			'<p>Open ' . $a( 'plans', 'Plans' ) . '. Each plan is something customers can buy, like “1 Hour · ₦200” or “Daily · ₦500”.</p>
			<div class="doc-grid">
				<div><b>Price (₦)</b><span>What the customer pays.</span></div>
				<div><b>Validity</b><span>How long the plan lasts once used, e.g. 1 hour, 1 day, 30 days.</span></div>
				<div><b>Data cap</b><span>Optional limit in MB. Leave empty for unlimited.</span></div>
				<div><b>Devices</b><span>How many phones or laptops can share one voucher.</span></div>
				<div><b>Speed</b><span>Optional download and upload limits in Mbps.</span></div>
				<div><b>Display order</b><span>Lower numbers show first on your portal.</span></div>
			</div>
			<p>Changes reach your routers automatically.</p>',
		),
		'test'      => array(
			'check',
			'Step 5 · Test it like a customer',
			'<ol class="steps-list">
				<li>On your phone, turn off mobile data and join your Wi-Fi.</li>
				<li>Your portal should open by itself. If it doesn’t, open any website, e.g. <code>neverssl.com</code>.</li>
				<li>Pick the cheapest plan and pay (or use the test payment page in test mode).</li>
				<li>You should be online within about 10 seconds. The purchase appears in ' . $a( 'sales', 'Sales' ) . ' and the device in ' . $a( 'online', 'Online now' ) . '.</li>
			</ol>',
		),
		'vouchers'  => array(
			'printer',
			'Selling vouchers offline',
			'<p>Not every customer pays online. In ' . $a( 'vouchers', 'Vouchers' ) . ' you can generate a batch of codes for any plan and <b>print branded cards</b> for your shop, attendants or resellers. Customers type the code on your portal to connect. You can see which cards are used and which are not.</p>',
		),
		'cwallets'  => array(
			'wallet',
			'Customer wallets',
			'<p>Customers can keep a Wi-Fi balance with you. They open the <b>Wallet</b> tab on your portal, sign in with their phone number and a 4-digit PIN, top up, and then buy plans in one tap without paying each time.</p>
			<ul class="steps-list">
				<li>Online top-ups go to your Flutterwave account, just like plan payments.</li>
				<li>If a customer pays you cash, add it to their wallet in ' . $a( 'cwallets', 'Customer wallets' ) . '.</li>
				<li>Share your wallet link with regular customers from the same page.</li>
			</ul>',
		),
		'billing'   => array(
			'star',
			'Your subscription and wallet',
			'<p>After the free trial, choose a plan in ' . $a( 'billing', 'Subscription' ) . '. You can pay by card or from ' . $a( 'wallet', 'My wallet' ) . ', which you can top up in advance so your hotspot never goes offline. Your customers’ payments are never deducted for this. They stay in your Flutterwave account.</p>',
		),
		'monitor'   => array(
			'chart',
			'Track your business',
			'<div class="doc-grid">
				<div><b>' . $a( '', 'Dashboard' ) . '</b><span>Today’s sales, who’s online and your router status.</span></div>
				<div><b>' . $a( 'analytics', 'Analytics' ) . '</b><span>Revenue trends, best plans, busiest hours and repeat customers.</span></div>
				<div><b>' . $a( 'sales', 'Sales' ) . '</b><span>Every payment with its reference and status.</span></div>
				<div><b>' . $a( 'customers', 'Customers' ) . '</b><span>Who buys, how often and how much they’ve spent.</span></div>
			</div>',
		),
		'app'       => array(
			'download',
			'Install the app on your phone',
			'<ul class="steps-list">
				<li><b>Android (Chrome):</b> tap <b>Install</b> on the banner, or open the browser menu ⋮ and choose <b>Install app</b>.</li>
				<li><b>iPhone (Safari):</b> tap the <b>Share</b> button, then <b>Add to Home Screen</b>.</li>
			</ul>
			<p>The app opens full screen from its own icon. Tap the refresh button at the top to reload the page you’re on.</p>',
		),
		'trouble'   => array(
			'help',
			'Troubleshooting',
			'<div class="doc-faq">
				<details><summary>The location says “Not connected”</summary><p>Check the router has internet (IP → DHCP Client shows <b>bound</b>, and Tools → Ping to google.com works). Then paste the setup script again. Check <b>System → Scheduler</b> has a ZaxConnect entry.</p></details>
				<details><summary>The portal doesn’t open when customers join</summary><p>Make sure Hotspot Setup was run on the bridge your access points use, and that <b>login.html</b> and <b>alogin.html</b> are in the <code>hotspot</code> folder. On the phone, try opening <code>neverssl.com</code>.</p></details>
				<details><summary>The payment page won’t load</summary><p>Paste the setup script again. It adds Flutterwave to the walled garden so customers can pay before they’re online.</p></details>
				<details><summary>A customer paid but isn’t online</summary><p>Find the payment in Sales. If it shows <b>success</b>, the voucher reaches the router within about 10 seconds. Ask the customer to refresh the portal. If Flutterwave shows the payment but Sales doesn’t, check your webhook URL and secret hash in Payment settings.</p></details>
				<details><summary>The DNS name is wrong</summary><p>The DNS name in Portal & branding must match IP → Hotspot → Server Profiles → DNS Name on the router. Change either one so they match, then paste the script again.</p></details>
			</div>
			<p style="margin-top:14px">Still stuck? Message us from ' . $a( 'support', 'Support' ) . ' and we’ll help you get live.</p>',
		),
	);
}

/** Shared docs body: table of contents + sections. */
function zxc_docs_body( $t = null ) {
	$sections = zxc_docs_sections( $t );
	ob_start();
	?>
<div class="docs">
  <nav class="docs-toc" aria-label="On this page">
    <div class="side-label">On this page</div>
    <?php foreach ( $sections as $id => $s ) : ?><a href="#<?php echo esc_attr( $id ); ?>"><?php echo zxc_icon( $s[0] ); // phpcs:ignore ?><span><?php echo esc_html( $s[1] ); ?></span></a><?php endforeach; ?>
  </nav>
  <div class="docs-main">
    <?php foreach ( $sections as $id => $s ) : ?>
      <section class="card doc-sec" id="<?php echo esc_attr( $id ); ?>">
        <div class="row" style="margin-bottom:14px"><span class="ico"><?php echo zxc_icon( $s[0] ); // phpcs:ignore ?></span><h2><?php echo esc_html( $s[1] ); ?></h2></div>
        <div class="doc-body"><?php echo $s[2]; // phpcs:ignore -- built from escaped parts above ?></div>
      </section>
    <?php endforeach; ?>
  </div>
</div>
	<?php
	return ob_get_clean();
}

/** Public documentation page (/docs). */
function zxc_view_page_docs() {
	$t = null;
	if ( is_user_logged_in() ) {
		$t = zxc_tenant_for_user( get_current_user_id() );
	}
	ob_start();
	echo zxc_page_hero( 'Documentation', 'Set up your hotspot and <span class="grad-text">start selling Wi-Fi</span>', 'Everything you need to connect your MikroTik router, take payments and run your hotspot business.' ); // phpcs:ignore
	echo '<section class="section tight"><div class="wrap">' . zxc_docs_body( $t ) . '</div></section>'; // phpcs:ignore
	return zxc_site_page( 'Documentation · ' . zxc_setting( 'platform_name' ), 'docs', ob_get_clean() . zxc_cta_band(), 'How to set up your hotspot, connect your MikroTik router and take payments.' );
}

/** Documentation inside the owner dashboard (/dashboard/docs). */
function zxc_view_dash_docs( $t, $welcome = false ) {
	ob_start();
	if ( $welcome ) :
		?>
<section class="card doc-welcome a-rise">
  <span class="ico lg"><?php echo zxc_icon( 'sparkles', 'a-float' ); // phpcs:ignore ?></span>
  <div class="grow"><h2>Welcome, your account is ready</h2><p>Start here. This guide explains how <?php echo esc_html( zxc_setting( 'platform_name' ) ); ?> works and walks you through connecting your router and payments. It takes about 30 minutes, and you can come back to it any time from <b>Documentation</b> in the menu.</p></div>
  <div class="row wrap-row" style="gap:8px"><a class="btn btn-primary" href="#router"><?php echo zxc_icon( 'wifi' ); // phpcs:ignore ?> Connect my router</a><a class="btn btn-glass" href="<?php echo esc_url( zxc_dash_url() ); ?>">Go to dashboard</a></div>
</section>
		<?php
	endif;
	echo zxc_docs_body( $t ); // phpcs:ignore
	return zxc_dash_page( $t, 'docs', 'Documentation', ob_get_clean(), array( 'sub' => 'How to set up your hotspot and connect billing.' ) );
}
