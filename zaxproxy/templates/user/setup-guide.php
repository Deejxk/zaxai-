<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * "Connect your proxy" guides: an animated walkthrough plus written steps for each device.
 * The box and button names are the real ones used by Windows, Tun2Socks and Potatso; the screens themselves are drawn.
 * Drop a real screen recording at assets/guides/{windows|android|ios}.mp4 and it is shown instead of the drawing.
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
			array( 'Proxy', 'Find "Manual proxy setup". Next to "Use a proxy server" click the Set up button.', array( array( 'Automatically detect settings', 'On', 0 ), array( 'Manual proxy setup', '', 0 ), array( 'Use a proxy server', 'Off', 1 ) ), 'Set up' ),
			array( 'Edit proxy server', 'Switch "Use a proxy server" on. Type the IP address in "Proxy IP address" and the HTTP(S) port in "Port", then click Save.', array( array( 'Use a proxy server', 'On', 1 ), array( 'Proxy IP address', $ex['ip'], 1 ), array( 'Port', $ex['http'], 1 ) ), 'Save' ),
			array( 'Sign in', 'Open your browser. A small box asks for a username and password: type the ones from My proxies.', array( array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), 'Sign in' ),
			array( 'Check it works', 'Search "what is my IP" in your browser. It should show your proxy\'s IP address and country, not your own.', array( array( 'Your IP address', $ex['ip'], 1 ), array( 'Status', 'Connected through your proxy', 0 ) ), '' ),
		),
		'steps' => array(
			'Open <strong>My proxies</strong> in your dashboard and copy the <strong>IP address</strong>, <strong>HTTP(S) port</strong>, <strong>username</strong> and <strong>password</strong>.',
			'On your computer click <strong>Start</strong>, open <strong>Settings</strong>, then <strong>Network &amp; internet</strong>, then <strong>Proxy</strong>.',
			'Under <strong>Manual proxy setup</strong>, next to <strong>Use a proxy server</strong>, click <strong>Set up</strong>.',
			'Switch <strong>Use a proxy server</strong> on. Type the IP address in <strong>Proxy IP address</strong> and the HTTP(S) port in <strong>Port</strong>, then click <strong>Save</strong>.',
			'Open your browser. When it asks, type the proxy <strong>username</strong> and <strong>password</strong>.',
			'Search <strong>what is my IP</strong>. If you see the proxy\'s IP and country, you are connected.',
		),
		'notes' => array(
			'Windows settings use the <strong>HTTP(S) port</strong>, not the SOCKS5 port.',
			'To stop using the proxy, go back to the same page and switch <strong>Use a proxy server</strong> off.',
			'<strong>Windows 10:</strong> the same page is in Settings, Network &amp; Internet, Proxy. Switch on <strong>Use a proxy server</strong>, type the IP in <strong>Address</strong> and the port in <strong>Port</strong>, then click <strong>Save</strong>.',
			'Want the proxy in one browser only? Use the proxy settings of that browser or an extension instead.',
		),
	),
	'android' => array(
		'name'  => 'Android',
		'icon'  => 'phone',
		'kind'  => 'phone',
		'intro' => 'Use the Tun2Socks app. It works on Wi-Fi and on mobile data, for every app on the phone.',
		'app'   => array( 'Tun2Socks', 'Search for "Tun2Socks" in the Google Play Store and install it.' ),
		'need'  => array( 'IP address' => $ex['ip'], 'SOCKS5 port' => $ex['socks'], 'Username' => $ex['user'], 'Password' => $ex['pass'] ),
		'slides' => array(
			array( $copy['screen'], $copy['cap'], array( array( 'IP address', $ex['ip'], 1 ), array( 'SOCKS5 port', $ex['socks'], 1 ), array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), '' ),
			array( 'Play Store', 'Open the Google Play Store, search for Tun2Socks and tap Install. Then open the app.', array( array( 'Search', 'Tun2Socks', 1 ), array( 'Tun2Socks', 'Install', 1 ) ), '' ),
			array( 'Tun2Socks', 'In the box called "Socks host" type your proxy IP address. In "Port" type the SOCKS5 port.', array( array( 'Socks host', $ex['ip'], 1 ), array( 'Port', $ex['socks'], 1 ), array( 'Username', '', 0 ), array( 'Password', '', 0 ) ), '' ),
			array( 'Tun2Socks', 'Now type your proxy username and password in the next two boxes.', array( array( 'Socks host', $ex['ip'], 0 ), array( 'Port', $ex['socks'], 0 ), array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), '' ),
			array( 'Tun2Socks', 'Tap the "Start Connect" button at the bottom.', array( array( 'Socks host', $ex['ip'], 0 ), array( 'Port', $ex['socks'], 0 ) ), 'Start Connect' ),
			array( 'Connection request', 'Android asks if Tun2Socks can set up a VPN connection. Tap OK. This is how the app sends your phone through the proxy.', array( array( 'Tun2Socks wants to set up a VPN connection', '', 1 ) ), 'OK' ),
			array( 'Check it works', 'A key or VPN icon appears at the top of the screen. Open your browser and search "what is my IP": it should show the proxy\'s IP and country.', array( array( 'Your IP address', $ex['ip'], 1 ), array( 'Status', 'Connected', 0 ) ), '' ),
		),
		'steps' => array(
			'Open <strong>My proxies</strong> and copy the <strong>IP address</strong>, <strong>SOCKS5 port</strong>, <strong>username</strong> and <strong>password</strong>.',
			'Open the <strong>Google Play Store</strong>, search for <strong>Tun2Socks</strong>, install it and open it.',
			'In <strong>Socks host</strong> type the <strong>IP address</strong>. In <strong>Port</strong> type the <strong>SOCKS5 port</strong>.',
			'In <strong>Username</strong> and <strong>Password</strong> type your proxy username and password.',
			'Tap <strong>Start Connect</strong>. When Android asks to set up a VPN connection, tap <strong>OK</strong>.',
			'A <strong>key or VPN icon</strong> shows at the top of the screen. Search <strong>what is my IP</strong> to confirm the proxy\'s IP and country.',
		),
		'notes' => array(
			'If the app shows a <strong>DNS</strong> box, you can leave it as it is, or type <strong>8.8.8.8</strong>.',
			'Use the <strong>SOCKS5 port</strong> here, not the HTTP(S) port.',
			'To stop using the proxy, open Tun2Socks and tap the same button again to disconnect.',
			'<strong>Without an app (Wi-Fi only):</strong> open Settings, Wi-Fi, hold your network, choose Modify, set Proxy to Manual, then type the IP address and the HTTP(S) port.',
		),
	),
	'ios' => array(
		'name'  => 'iPhone / iPad',
		'icon'  => 'phone',
		'kind'  => 'phone',
		'intro' => 'Use the Potatso app (many people call it the "Potato" app). It works on Wi-Fi and on mobile data.',
		'app'   => array( 'Potatso', 'Search for "Potatso" in the App Store and install it. "Potatso Lite" works the same way.' ),
		'need'  => array( 'IP address' => $ex['ip'], 'SOCKS5 port' => $ex['socks'], 'Username' => $ex['user'], 'Password' => $ex['pass'] ),
		'slides' => array(
			array( $copy['screen'], $copy['cap'], array( array( 'IP address', $ex['ip'], 1 ), array( 'SOCKS5 port', $ex['socks'], 1 ), array( 'Username', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), '' ),
			array( 'App Store', 'Open the App Store, search for Potatso and tap Get. Then open the app.', array( array( 'Search', 'Potatso', 1 ), array( 'Potatso', 'Get', 1 ) ), '' ),
			array( 'Potatso', 'On the first screen tap "Add a Proxy". Then, under "Manual Input", tap "Add".', array( array( 'Add a Proxy', '+', 1 ), array( 'Manual Input', 'Add', 1 ) ), '' ),
			array( 'Proxy', 'Tap "Type" and choose SOCKS5. In "Host" type your proxy IP address. In "Port" type the SOCKS5 port.', array( array( 'Type', 'SOCKS5', 1 ), array( 'Host', $ex['ip'], 1 ), array( 'Port', $ex['socks'], 1 ) ), '' ),
			array( 'Proxy', 'Set "Authentication" to Password. Type your proxy username in "User" and your password in "Password". Tap Save.', array( array( 'Authentication', 'Password', 1 ), array( 'User', $ex['user'], 1 ), array( 'Password', $ex['pass'], 1 ) ), 'Save' ),
			array( 'Potatso', 'Back on the main screen, tap the Play button to connect. iPhone asks to add VPN configurations: tap Allow and enter your passcode.', array( array( '"Potatso" Would Like to Add VPN Configurations', '', 1 ) ), 'Allow' ),
			array( 'Check it works', 'A VPN icon shows at the top of the screen. Open Safari and search "what is my IP": it should show the proxy\'s IP and country.', array( array( 'Your IP address', $ex['ip'], 1 ), array( 'Status', 'Connected', 0 ) ), '' ),
		),
		'steps' => array(
			'Open <strong>My proxies</strong> and copy the <strong>IP address</strong>, <strong>SOCKS5 port</strong>, <strong>username</strong> and <strong>password</strong>.',
			'Open the <strong>App Store</strong>, search for <strong>Potatso</strong>, install it and open it.',
			'Tap <strong>Add a Proxy</strong>. Under <strong>Manual Input</strong> tap <strong>Add</strong>.',
			'Tap <strong>Type</strong> and choose <strong>SOCKS5</strong>. In <strong>Host</strong> type the IP address and in <strong>Port</strong> type the SOCKS5 port.',
			'Set <strong>Authentication</strong> to <strong>Password</strong>. Type your username in <strong>User</strong> and your password in <strong>Password</strong>, then tap <strong>Save</strong>.',
			'Tap the <strong>Play</strong> button to connect. When your iPhone asks to add VPN configurations, tap <strong>Allow</strong> and enter your passcode.',
			'A <strong>VPN icon</strong> shows at the top of the screen. Search <strong>what is my IP</strong> in Safari to confirm the proxy\'s IP and country.',
		),
		'notes' => array(
			'Use the <strong>SOCKS5 port</strong> here, not the HTTP(S) port.',
			'To stop using the proxy, open Potatso and tap the same button again to disconnect.',
			'<strong>Without an app (Wi-Fi only):</strong> open Settings, Wi-Fi, tap the (i) next to your network, Configure Proxy, Manual. Type the IP address and the HTTP(S) port, switch on Authentication and type your username and password.',
		),
	),
);
// A real screen recording replaces the drawn walkthrough when one has been uploaded for that device.
foreach ( $guides as $key => $g ) {
	$guides[ $key ]['video'] = is_readable( ZP_DIR . 'assets/guides/' . $key . '.mp4' ) ? ZP_URL . 'assets/guides/' . $key . '.mp4?v=' . ZP_VERSION : '';
	$guides[ $key ]['poster'] = is_readable( ZP_DIR . 'assets/guides/' . $key . '.jpg' ) ? ZP_URL . 'assets/guides/' . $key . '.jpg?v=' . ZP_VERSION : '';
}
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
				<?php if ( $g['video'] ) : ?>
				<div class="zp-realvid">
					<video controls playsinline preload="metadata"<?php echo $g['poster'] ? ' poster="' . esc_url( $g['poster'] ) . '"' : ''; ?> src="<?php echo esc_url( $g['video'] ); ?>"></video>
					<p><?php echo zp_icon( 'eye', 16 ); // phpcs:ignore ?>Real screen recording of the setup. Tap play and follow along.</p>
				</div>
				<?php else : ?>
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
				<?php endif; ?>

				<div class="zp-setup__text">
					<?php if ( 'windows' === $key && ZP_App::offered( 'windows' ) ) : ?>
						<div class="zp-appcard">
							<span class="zp-appcard__ico"><?php echo zp_logo( 38 ); // phpcs:ignore ?></span>
							<div><strong>Easiest way: ZaxProxy Connect for Windows</strong><span>Download it, open it, sign in with this account and click Connect on any of your proxies. No settings to change and no password to type.</span><?php if ( ! zp_get( 'app_public' ) ) : ?><em>Only administrators can see this until it is switched on in Settings.</em><?php endif; ?></div>
							<a class="zp-btn zp-btn--primary zp-btn--sm" href="<?php echo esc_url( ZP_App::download_url( 'windows' ) ); ?>" target="_blank" rel="noopener"><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?>Download</a>
						</div>
						<p class="zp-appcard__note">Windows may show a blue "Windows protected your PC" box the first time, because the program is new. Click <strong>More info</strong>, then <strong>Run anyway</strong>. ZaxProxy Connect then sits near the clock: right-click its icon to disconnect or quit. You can still set the proxy by hand with the steps below.</p>
					<?php endif; ?>
					<?php if ( 'android' === $key && ZP_App::offered() ) : ?>
						<div class="zp-appcard">
							<span class="zp-appcard__ico"><?php echo zp_logo( 38 ); // phpcs:ignore ?></span>
							<div><strong>Easiest way: the ZaxProxy Connect app</strong><span>Install it, sign in with this account and tap Connect on any of your proxies. Nothing to type.</span><?php if ( ! zp_get( 'app_public' ) ) : ?><em>Only administrators can see this until it is switched on in Settings.</em><?php endif; ?></div>
							<a class="zp-btn zp-btn--primary zp-btn--sm" href="<?php echo esc_url( ZP_App::download_url() ); ?>" target="_blank" rel="noopener"><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?>Download</a>
						</div>
						<p class="zp-appcard__note">Android will ask you to allow installing the app, because it comes from our website and not from the Play Store. If you prefer, you can still use Tun2Socks with the steps below.</p>
					<?php endif; ?>
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
