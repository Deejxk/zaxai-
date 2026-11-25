<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * "Connect your proxy" guides: an animated walkthrough plus written steps for each device.
 * Screens are simplified drawings; the real app can look a little different.
 */
$ex    = array( 'ip' => '205.120.31.217', 'http' => '53062', 'socks' => '53063', 'user' => 'zx9c9748af', 'pass' => '••••••••' );
$mine  = zp_url( 'proxies' );
$copy  = array(
	'screen' => 'My proxies',
	'cap'    => 'Open My proxies and copy these four details. You will type them in the next steps.',
);
// Each slide: screen title, caption, rows [ label, value, highlighted? ], optional button label.
$guides = array(
	'windows' => array(
		'name'  => 'Windows',
		'icon'  => 'server',
		'kind'  => 'pc',
		'intro' => 'Works on Windows 10 and 11. No app needed.',
		'need'  => array( 'IP address' => $ex['ip'], 'HTTP(S) port' => $ex['http'], 'Username' => $ex['user'], 'Password' => $ex['pass'] ),
		'slides' => array(
			array( $copy['screen'], $copy['cap'], array( array( 'IP address', $ex['ip'], 1 ), array( 'HTTP(S) port', $ex['http'], 1 ), array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), '' ),
			array( 'Settings', 'Click the Start button, open Settings, then choose Network & internet.', array( array( 'System', '', 0 ), array( 'Bluetooth & devices', '', 0 ), array( 'Network & internet', '›', 1 ), array( 'Personalisation', '', 0 ) ), '' ),
			array( 'Network & internet', 'Scroll down and click Proxy.', array( array( 'Wi-Fi', '', 0 ), array( 'VPN', '', 0 ), array( 'Proxy', '›', 1 ), array( 'Dial-up', '', 0 ) ), '' ),
			array( 'Proxy', 'Under "Manual proxy setup" click Set up, then switch on "Use a proxy server".', array( array( 'Automatically detect settings', 'Off', 0 ), array( 'Use a proxy server', 'On', 1 ) ), 'Set up' ),
			array( 'Edit proxy server', 'Type the IP address and the HTTP(S) port from My proxies, then click Save.', array( array( 'Proxy IP address', $ex['ip'], 1 ), array( 'Port', $ex['http'], 1 ) ), 'Save' ),
			array( 'Sign in', 'Open your browser. A small box asks for a username and password: type the ones from My proxies.', array( array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), 'Sign in' ),
			array( 'Check it works', 'Search "what is my IP" in your browser. It should show your proxy\'s IP address and country, not your own.', array( array( 'Your IP address', $ex['ip'], 1 ), array( 'Status', 'Connected through your proxy', 0 ) ), '' ),
		),
		'steps' => array(
			'Open <strong>My proxies</strong> in your dashboard and copy the <strong>IP address</strong>, <strong>HTTP(S) port</strong>, <strong>username</strong> and <strong>password</strong>.',
			'On your computer click <strong>Start</strong>, open <strong>Settings</strong>, then <strong>Network &amp; internet</strong>, then <strong>Proxy</strong>.',
			'Under <strong>Manual proxy setup</strong> click <strong>Set up</strong> and switch on <strong>Use a proxy server</strong>.',
			'Type the <strong>IP address</strong> and the <strong>HTTP(S) port</strong>, then click <strong>Save</strong>.',
			'Open your browser. When it asks, type the proxy <strong>username</strong> and <strong>password</strong>.',
			'Search <strong>what is my IP</strong>. If you see the proxy\'s IP and country, you are connected.',
		),
		'notes' => array(
			'Windows settings use the <strong>HTTP(S) port</strong>, not the SOCKS5 port.',
			'To stop using the proxy, go back to the same page and switch <strong>Use a proxy server</strong> off.',
			'Want the proxy in one browser only? Use the proxy settings of that browser or an extension instead.',
		),
	),
	'android' => array(
		'name'  => 'Android',
		'icon'  => 'phone',
		'kind'  => 'phone',
		'intro' => 'Use the free Tun2Socks app. It works on Wi-Fi and on mobile data, for every app on the phone.',
		'app'   => array( 'Tun2Socks', 'Search for "Tun2Socks" in the Google Play Store and install it.' ),
		'need'  => array( 'IP address' => $ex['ip'], 'SOCKS5 port' => $ex['socks'], 'Username' => $ex['user'], 'Password' => $ex['pass'] ),
		'slides' => array(
			array( $copy['screen'], $copy['cap'], array( array( 'IP address', $ex['ip'], 1 ), array( 'SOCKS5 port', $ex['socks'], 1 ), array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), '' ),
			array( 'Play Store', 'Open the Google Play Store, search for Tun2Socks and tap Install.', array( array( 'Search', 'Tun2Socks', 1 ), array( 'Tun2Socks', 'Install', 1 ) ), '' ),
			array( 'Tun2Socks', 'Open the app. Choose SOCKS5 and type the IP address and the SOCKS5 port.', array( array( 'Type', 'SOCKS5', 0 ), array( 'Server', $ex['ip'], 1 ), array( 'Port', $ex['socks'], 1 ) ), '' ),
			array( 'Tun2Socks', 'Type your proxy username and password, then save.', array( array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), 'Save' ),
			array( 'Tun2Socks', 'Tap Start (or Connect). Android asks to allow a connection: tap OK.', array( array( 'Connection request', 'Tun2Socks wants to set up a connection', 0 ) ), 'OK' ),
			array( 'Connected', 'A small key icon appears at the top of your screen. Your whole phone now uses the proxy.', array( array( 'Status', 'Connected', 1 ), array( 'Server', $ex['ip'], 0 ) ), '' ),
			array( 'Check it works', 'Open your browser and search "what is my IP". It should show the proxy\'s IP and country.', array( array( 'Your IP address', $ex['ip'], 1 ) ), '' ),
		),
		'steps' => array(
			'Open <strong>My proxies</strong> and copy the <strong>IP address</strong>, <strong>SOCKS5 port</strong>, <strong>username</strong> and <strong>password</strong>.',
			'Open the <strong>Google Play Store</strong>, search for <strong>Tun2Socks</strong> and install it.',
			'Open the app. Choose <strong>SOCKS5</strong>, then type the <strong>IP address</strong> as the server and the <strong>SOCKS5 port</strong> as the port.',
			'Type the proxy <strong>username</strong> and <strong>password</strong> and save.',
			'Tap <strong>Start</strong> (or <strong>Connect</strong>). When Android asks to allow the connection, tap <strong>OK</strong>.',
			'A <strong>key icon</strong> shows at the top of the screen. Search <strong>what is my IP</strong> to confirm the proxy\'s IP and country.',
		),
		'notes' => array(
			'The buttons in the app can be named a little differently from version to version, but the four details you type are always the same.',
			'To stop using the proxy, open Tun2Socks and tap <strong>Stop</strong> (or <strong>Disconnect</strong>).',
			'<strong>Without an app (Wi-Fi only):</strong> open Settings, Wi-Fi, hold your network, choose Modify, set Proxy to Manual, then type the IP address and the HTTP(S) port.',
		),
	),
	'ios' => array(
		'name'  => 'iPhone / iPad',
		'icon'  => 'phone',
		'kind'  => 'phone',
		'intro' => 'Use the Potatso app (many people call it the "Potato" app). It works on Wi-Fi and on mobile data.',
		'app'   => array( 'Potatso', 'Search for "Potatso" in the App Store and install it.' ),
		'need'  => array( 'IP address' => $ex['ip'], 'SOCKS5 port' => $ex['socks'], 'Username' => $ex['user'], 'Password' => $ex['pass'] ),
		'slides' => array(
			array( $copy['screen'], $copy['cap'], array( array( 'IP address', $ex['ip'], 1 ), array( 'SOCKS5 port', $ex['socks'], 1 ), array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), '' ),
			array( 'App Store', 'Open the App Store, search for Potatso and tap Get.', array( array( 'Search', 'Potatso', 1 ), array( 'Potatso', 'Get', 1 ) ), '' ),
			array( 'Potatso', 'Open the app and tap the + button to add a proxy. Choose SOCKS5 as the type.', array( array( 'Add proxy', '+', 1 ), array( 'Type', 'SOCKS5', 1 ) ), '' ),
			array( 'Add proxy', 'Type the IP address as the host and the SOCKS5 port as the port.', array( array( 'Host', $ex['ip'], 1 ), array( 'Port', $ex['socks'], 1 ) ), '' ),
			array( 'Add proxy', 'Type your proxy username and password, then tap Save.', array( array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), 'Save' ),
			array( 'Potatso', 'Select your proxy and tap the Start button. iPhone asks to add a VPN setting: tap Allow.', array( array( '"Potatso" would like to add VPN configurations', '', 0 ) ), 'Allow' ),
			array( 'Check it works', 'A VPN icon shows at the top. Open Safari and search "what is my IP" to see the proxy\'s IP and country.', array( array( 'Your IP address', $ex['ip'], 1 ), array( 'Status', 'Connected', 0 ) ), '' ),
		),
		'steps' => array(
			'Open <strong>My proxies</strong> and copy the <strong>IP address</strong>, <strong>SOCKS5 port</strong>, <strong>username</strong> and <strong>password</strong>.',
			'Open the <strong>App Store</strong>, search for <strong>Potatso</strong> and install it.',
			'Open the app and tap <strong>+</strong> to add a proxy. Choose <strong>SOCKS5</strong> as the type.',
			'Type the <strong>IP address</strong> as the host, the <strong>SOCKS5 port</strong> as the port, then your <strong>username</strong> and <strong>password</strong>. Tap <strong>Save</strong>.',
			'Select the proxy you just added and tap <strong>Start</strong>. When your iPhone asks to add a VPN setting, tap <strong>Allow</strong> and enter your passcode.',
			'A <strong>VPN icon</strong> shows at the top of the screen. Search <strong>what is my IP</strong> in Safari to confirm the proxy\'s IP and country.',
		),
		'notes' => array(
			'The buttons in the app can be named a little differently from version to version, but the four details you type are always the same.',
			'To stop using the proxy, open Potatso and tap the same button again to disconnect.',
			'<strong>Without an app (Wi-Fi only):</strong> open Settings, Wi-Fi, tap the (i) next to your network, Configure Proxy, Manual. Type the IP address and the HTTP(S) port, switch on Authentication and type your username and password.',
		),
	),
);
$first = true;
?>
<section class="zp-card zp-anim zp-setup" style="--d:2" id="connect" data-zp-setup>
	<div class="zp-card__head"><h3><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?><?php echo ! empty( $zp_setup_only ) ? 'How to connect your proxy' : 'Connect your proxy'; ?></h3><a href="<?php echo esc_url( $mine ); ?>">My proxies</a></div>
	<p class="zp-setup__lead"><?php echo ! empty( $zp_setup_only ) ? 'After you buy, this is how you put the proxy on your device. Choose your device, watch the short walkthrough or follow the written steps.' : 'Choose your device. Watch the short walkthrough, or follow the written steps below it. It takes about two minutes.'; ?></p>
	<div class="zp-seg zp-setup__tabs" role="tablist">
		<?php foreach ( $guides as $key => $g ) : ?>
			<button type="button" role="tab" class="<?php echo $first ? 'is-active' : ''; ?>" data-setup="<?php echo esc_attr( $key ); ?>"><?php echo zp_icon( $g['icon'], 16 ); // phpcs:ignore ?><?php echo esc_html( $g['name'] ); ?></button>
			<?php
			$first = false;
		endforeach;
		?>
	</div>

	<?php
	$first = true;
	foreach ( $guides as $key => $g ) :
		$total = count( $g['slides'] );
		?>
		<div class="zp-setup__pane" data-setup-pane="<?php echo esc_attr( $key ); ?>"<?php echo $first ? '' : ' hidden'; ?>>
			<p class="zp-setup__intro"><?php echo esc_html( $g['intro'] ); ?></p>
			<div class="zp-setup__grid">
				<div class="zp-vid zp-vid--<?php echo esc_attr( $g['kind'] ); ?>" data-zp-vid>
					<div class="zp-vid__stage">
						<span class="zp-vid__badge"><i></i>Walkthrough</span>
						<?php foreach ( $g['slides'] as $i => $s ) : ?>
							<div class="zp-vid__slide<?php echo 0 === $i ? ' is-on' : ''; ?>">
								<div class="zp-vid__device">
									<div class="zp-vid__bar"><i></i><b><?php echo esc_html( $s[0] ); ?></b></div>
									<div class="zp-vid__rows">
										<?php foreach ( $s[2] as $n => $row ) : ?>
											<div class="zp-vid__row<?php echo $row[2] ? ' is-hot' : ''; ?>" style="--n:<?php echo (int) $n; ?>"><span><?php echo esc_html( $row[0] ); ?></span><?php if ( '' !== $row[1] ) : ?><strong><?php echo esc_html( $row[1] ); ?></strong><?php endif; ?></div>
										<?php endforeach; ?>
										<?php if ( $s[3] ) : ?><div class="zp-vid__btn" style="--n:<?php echo count( $s[2] ); ?>"><?php echo esc_html( $s[3] ); ?><i></i></div><?php endif; ?>
									</div>
								</div>
							</div>
						<?php endforeach; ?>
					</div>
					<div class="zp-vid__caps">
						<?php foreach ( $g['slides'] as $i => $s ) : ?>
							<p class="<?php echo 0 === $i ? 'is-on' : ''; ?>"><b>Step <?php echo (int) $i + 1; ?> of <?php echo (int) $total; ?></b><?php echo esc_html( $s[1] ); ?></p>
						<?php endforeach; ?>
					</div>
					<div class="zp-vid__progress"><?php for ( $i = 0; $i < $total; $i++ ) : ?><i><b></b></i><?php endfor; ?></div>
					<div class="zp-vid__ctrl">
						<button type="button" data-vid="prev" aria-label="Previous step"><?php echo zp_icon( 'back', 18 ); // phpcs:ignore ?></button>
						<button type="button" class="zp-vid__play" data-vid="play" aria-label="Play or pause"><span class="is-play">▶ Play</span><span class="is-pause">❚❚ Pause</span></button>
						<button type="button" data-vid="next" aria-label="Next step"><?php echo zp_icon( 'arrow', 18 ); // phpcs:ignore ?></button>
					</div>
				</div>

				<div class="zp-setup__text">
					<?php if ( ! empty( $g['app'] ) ) : ?>
						<div class="zp-setup__app"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'download', 18 ); // phpcs:ignore ?></span><div><strong>App to use: <?php echo esc_html( $g['app'][0] ); ?></strong><span><?php echo esc_html( $g['app'][1] ); ?></span></div></div>
					<?php endif; ?>
					<div class="zp-setup__need">
						<strong>What you need from My proxies</strong>
						<dl><?php foreach ( $g['need'] as $label => $val ) : ?><div><dt><?php echo esc_html( $label ); ?></dt><dd class="zp-mono"><?php echo esc_html( $val ); ?></dd></div><?php endforeach; ?></dl>
						<small>These are examples. Use the details shown on your own proxy.</small>
					</div>
					<ol class="zp-setup__steps">
						<?php foreach ( $g['steps'] as $i => $step ) : ?>
							<li><b><?php echo (int) $i + 1; ?></b><span><?php echo wp_kses( $step, array( 'strong' => array() ) ); ?></span></li>
						<?php endforeach; ?>
					</ol>
					<ul class="zp-setup__notes">
						<?php foreach ( $g['notes'] as $note ) : ?><li><?php echo wp_kses( $note, array( 'strong' => array() ) ); ?></li><?php endforeach; ?>
					</ul>
				</div>
			</div>
		</div>
		<?php
		$first = false;
	endforeach;
	?>
</section>

<?php
if ( ! empty( $zp_setup_only ) ) {
	return; // the buy page shows only the connection guide
}
?>
<section class="zp-card zp-anim" style="--d:3">
	<div class="zp-card__head"><h3>Using a proxy for your accounts</h3></div>
	<p class="zp-setup__lead">Setting up a social media, shopping or any other account on a proxy? Follow these simple rules and your accounts stay safe.</p>
	<div class="zp-todo zp-rules">
		<div class="zp-todo__item"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'user', 18 ); // phpcs:ignore ?></span><div><strong>One proxy for one account</strong><span>Do not log several accounts of the same app into one proxy.</span></div></div>
		<div class="zp-todo__item"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'check', 18 ); // phpcs:ignore ?></span><div><strong>Connect first, then open the app</strong><span>Turn the proxy on, check your IP, and only then open the account.</span></div></div>
		<div class="zp-todo__item"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'globe', 18 ); // phpcs:ignore ?></span><div><strong>Keep the same country</strong><span>Pick the country the account belongs to and stay on it.</span></div></div>
		<div class="zp-todo__item"><span class="zp-stat__ico zp-tone-amber"><?php echo zp_icon( 'refresh', 18 ); // phpcs:ignore ?></span><div><strong>Renew before it expires</strong><span>Renewing keeps the same IP, so the account never sees a change.</span></div></div>
	</div>
	<div class="zp-faq" style="margin-top:14px">
		<details><summary>It asks for a username and password again and again<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>The username or password was typed wrongly. Copy them from My proxies with the copy button instead of typing, and make sure there is no space at the start or the end.</p></details>
		<details><summary>The internet stops working after I connect<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Check three things: the port matches the type (HTTP(S) port for Windows and Wi-Fi settings, SOCKS5 port for Tun2Socks and Potatso), the proxy has not expired, and the IP address has no missing digit. Then disconnect and connect again.</p></details>
		<details><summary>"What is my IP" still shows my own location<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>The proxy is not switched on yet. On a phone, look for the key or VPN icon at the top of the screen. On Windows, check that "Use a proxy server" is on. Close and reopen your browser and check again.</p></details>
		<details><summary>Can I use the same proxy on my phone and my computer?<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Yes. Type the same details on each device. For accounts, it is safer to use each account from one device and one proxy only.</p></details>
	</div>
</section>
