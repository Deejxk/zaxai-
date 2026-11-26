<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * "Connect your proxy" guides: an animated walkthrough plus written steps for each device.
 * The box and button names are the real ones used by ZaxProxy Connect (Windows and Android) and Potatso (iPhone); the screens themselves are drawn.
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
		'intro' => 'Use the ZaxProxy Connect app for Windows 10 and 11. Sign in once and your proxies are ready: no settings to change.',
		'need_title' => 'What you need',
		'need_note'  => 'The same email and password you use on this website. You do not need to copy any proxy details.',
		'need'  => array( 'Email or username' => 'Your ZaxProxy login', 'Password' => 'Your ZaxProxy password' ),
		'slides' => array(
			array( 'Download', 'On this page, click Download next to "ZaxProxy Connect for Windows". A zip file is saved to your computer.', array( array( 'ZaxProxy Connect for Windows', 'Download', 1 ), array( 'ZaxProxy-Connect-Windows.zip', 'Saved', 0 ) ), '' ),
			array( 'Install', 'Open the zip file and double-click ZaxProxy-Connect-Setup. Click Install, then Finish.', array( array( 'ZaxProxy-Connect-Setup', 'Open', 1 ), array( 'ZaxProxy Connect Setup', '', 0 ) ), 'Install' ),
			array( 'ZaxProxy Connect', 'The app opens in its own window. Type your ZaxProxy email and password and click Sign in.', array( array( 'Email or username', 'you@example.com', 1 ), array( 'Password', $ex['pass'], 1 ), array( 'Connect automatically after signing in', 'On', 0 ) ), 'Sign in' ),
			array( 'Your locations', 'All your active proxies are listed under "Your locations". Click the one you want to use.', array( array( 'United States · ' . $ex['ip'], 'Selected', 1 ), array( 'United Kingdom', 'Select', 0 ), array( 'Germany', 'Select', 0 ) ), '' ),
			array( 'ZaxProxy Connect', 'Click the big round power button. When it turns green and says "Protected", your PC is using the proxy.', array( array( 'Status', 'Protected', 1 ), array( 'Connected to', 'United States', 0 ), array( 'IP address', $ex['ip'], 0 ) ), '' ),
			array( 'Check it works', 'Search "what is my IP" in your browser. It should show your proxy\'s IP address and country, not your own.', array( array( 'Your IP address', $ex['ip'], 1 ), array( 'Status', 'Connected through your proxy', 0 ) ), '' ),
		),
		'steps' => array(
			'Click <strong>Download</strong> above to get <strong>ZaxProxy Connect for Windows</strong>. It is saved as a zip file.',
			'Open the zip file and double-click <strong>ZaxProxy-Connect-Setup</strong>. Click <strong>Install</strong>, then <strong>Finish</strong>.',
			'ZaxProxy Connect opens in its own window. Type your ZaxProxy <strong>email</strong> and <strong>password</strong> and click <strong>Sign in</strong>. Use the eye icon to check your password.',
			'With <strong>Connect automatically after signing in</strong> ticked, the app connects you straight away. Otherwise click a proxy under <strong>Your locations</strong>, then click the round <strong>power button</strong>.',
			'When the button turns green and says <strong>Protected</strong>, your PC is using the proxy.',
			'Search <strong>what is my IP</strong>. If you see the proxy\'s IP and country, you are connected.',
		),
		'notes' => array(
			'To change location, click another proxy in the list while connected. The app switches to it.',
			'To stop using the proxy, click the power button again. Closing the window also disconnects, so keep it open (you can minimise it) while you browse.',
			'<strong>GPS map:</strong> click <strong>GPS map</strong> in the app to choose any state, city or country, or switch on <strong>Match my GPS to the connected proxy</strong>. A ZaxProxy browser window (Chrome or Edge) opens by itself, and every website in that window sees the place you chose. There is nothing to install.',
			'Windows may show a blue <strong>Windows protected your PC</strong> box the first time, because the program is new. Click <strong>More info</strong>, then <strong>Run anyway</strong>.',
			'<strong>Without the app:</strong> open Settings, Network &amp; internet, Proxy, click <strong>Set up</strong> next to <strong>Use a proxy server</strong>, and type the IP address and the <strong>HTTP(S) port</strong> from My proxies. Your browser then asks for the proxy username and password.',
		),
	),
	'android' => array(
		'name'  => 'Android',
		'icon'  => 'phone',
		'kind'  => 'phone',
		'intro' => 'Use the ZaxProxy Connect app. It works on Wi-Fi and on mobile data, for every app on the phone.',
		'need_title' => 'What you need',
		'need_note'  => 'The same email and password you use on this website. You do not need to copy any proxy details.',
		'need'  => array( 'Email or username' => 'Your ZaxProxy login', 'Password' => 'Your ZaxProxy password' ),
		'slides' => array(
			array( 'Download', 'On this page, tap Download next to "ZaxProxy Connect". The app file is saved to your phone.', array( array( 'ZaxProxy Connect', 'Download', 1 ), array( 'ZaxProxy-Connect.apk', 'Saved', 0 ) ), '' ),
			array( 'Install', 'Open the downloaded file. If Android asks, allow your browser to install apps, then tap Install and Open.', array( array( 'Allow from this source', 'On', 1 ), array( 'ZaxProxy Connect', '', 0 ) ), 'Install' ),
			array( 'ZaxProxy Connect', 'Type your ZaxProxy email and password and tap Sign in. Tap the eye to check your password.', array( array( 'Email or username', 'you@example.com', 1 ), array( 'Password', $ex['pass'], 1 ) ), 'Sign in' ),
			array( 'Your locations', 'All your active proxies are listed under "Your locations". Tap the one you want to use.', array( array( 'United States · ' . $ex['ip'], 'Selected', 1 ), array( 'United Kingdom', '', 0 ), array( 'Germany', '', 0 ) ), '' ),
			array( 'ZaxProxy Connect', 'Tap the big round power button at the top.', array( array( 'Status', 'Not connected', 0 ), array( 'Selected location', 'United States', 1 ) ), 'Tap to connect' ),
			array( 'Connection request', 'The first time, Android asks if ZaxProxy Connect can set up a VPN connection. Tap OK. This is how the app sends your phone through the proxy.', array( array( 'ZaxProxy Connect wants to set up a VPN connection', '', 1 ) ), 'OK' ),
			array( 'Check it works', 'The button turns green and says "Protected", and a key icon appears at the top of the screen. Search "what is my IP": it should show the proxy\'s IP and country.', array( array( 'Status', 'Protected', 1 ), array( 'Your IP is now', $ex['ip'], 1 ) ), '' ),
		),
		'steps' => array(
			'Tap <strong>Download</strong> above to get the <strong>ZaxProxy Connect</strong> app.',
			'Open the downloaded file. If Android asks, <strong>allow</strong> your browser to install apps, then tap <strong>Install</strong> and <strong>Open</strong>.',
			'Type your ZaxProxy <strong>email</strong> and <strong>password</strong> and tap <strong>Sign in</strong>. Tap the eye icon to check your password.',
			'Under <strong>Your locations</strong>, tap the proxy you want to use. Then tap the round <strong>power button</strong>.',
			'The first time, Android asks to set up a VPN connection. Tap <strong>OK</strong>.',
			'The button turns green and says <strong>Protected</strong>, and a <strong>key icon</strong> shows at the top of the screen. Search <strong>what is my IP</strong> to confirm the proxy\'s IP and country.',
		),
		'notes' => array(
			'To change location, tap another proxy in the list while connected. The app switches to it.',
			'To stop using the proxy, open ZaxProxy Connect and tap the power button again.',
			'<strong>GPS map:</strong> tap <strong>Open map</strong> in the app to move this phone\'s GPS to any state, city or country, or switch on <strong>Match my GPS to the connected proxy</strong>. The first time, the app shows a one-minute phone setup (Developer options, <strong>Select mock location app</strong>).',
			'Android asks you to allow the install because the app comes from our website and not from the Play Store.',
			'<strong>Without the app (Wi-Fi only):</strong> open Settings, Wi-Fi, hold your network, choose Modify, set Proxy to Manual, then type the IP address and the HTTP(S) port from My proxies.',
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
							<div><strong>ZaxProxy Connect for Windows</strong><span>Download, install and sign in with this account. Your proxies appear in the app and connect with one click.</span><?php if ( ! zp_get( 'app_public' ) ) : ?><em>Only administrators can see this until it is switched on in Settings.</em><?php endif; ?></div>
							<a class="zp-btn zp-btn--primary zp-btn--sm" href="<?php echo esc_url( ZP_App::download_url( 'windows' ) ); ?>" target="_blank" rel="noopener"><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?>Download</a>
						</div>
					<?php endif; ?>
					<?php if ( 'android' === $key && ZP_App::offered() ) : ?>
						<div class="zp-appcard">
							<span class="zp-appcard__ico"><?php echo zp_logo( 38 ); // phpcs:ignore ?></span>
							<div><strong>ZaxProxy Connect for Android</strong><span>Download, install and sign in with this account. Your proxies appear in the app and connect with one tap.</span><?php if ( ! zp_get( 'app_public' ) ) : ?><em>Only administrators can see this until it is switched on in Settings.</em><?php endif; ?></div>
							<a class="zp-btn zp-btn--primary zp-btn--sm" href="<?php echo esc_url( ZP_App::download_url() ); ?>" target="_blank" rel="noopener"><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?>Download</a>
						</div>
					<?php endif; ?>
					<?php if ( ! empty( $g['app'] ) ) : ?>
						<div class="zp-setup__app"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'download', 18 ); // phpcs:ignore ?></span><div><strong>App to use: <?php echo esc_html( $g['app'][0] ); ?></strong><span><?php echo esc_html( $g['app'][1] ); ?></span></div></div>
					<?php endif; ?>
					<div class="zp-setup__need">
						<strong><?php echo esc_html( isset( $g['need_title'] ) ? $g['need_title'] : 'What you need from My proxies' ); ?></strong>
						<dl><?php foreach ( $g['need'] as $label => $val ) : ?><div><dt><?php echo esc_html( $label ); ?></dt><dd class="zp-mono"><?php echo esc_html( $val ); ?></dd></div><?php endforeach; ?></dl>
						<small><?php echo esc_html( isset( $g['need_note'] ) ? $g['need_note'] : 'These are examples. Use the details shown on your own proxy.' ); ?></small>
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
		<details><summary>It asks for a username and password again and again<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>This happens when you set a proxy by hand and the username or password was typed wrongly. Copy them from My proxies with the copy button instead of typing, and make sure there is no space at the start or the end.</p></details>
		<details><summary>The internet stops working after I connect<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Check three things: the port matches the type (HTTP(S) port for Windows and Wi-Fi settings, SOCKS5 port for Potatso), the proxy has not expired, and the IP address has no missing digit. Then disconnect and connect again.</p></details>
		<details><summary>"What is my IP" still shows my own location<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>The proxy is not switched on yet. On a phone, look for the key or VPN icon at the top of the screen. In ZaxProxy Connect the button must be green and say "Protected". Close and reopen your browser and check again.</p></details>
		<details><summary>Can I use the same proxy on my phone and my computer?<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Yes. Sign in to ZaxProxy Connect on each device and choose the same proxy. For accounts, it is safer to use each account from one device and one proxy only.</p></details>
	</div>
</section>
