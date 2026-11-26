<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Front controller. Serves the standalone user panel (/panel), the standalone
 * admin panel (/zax-admin), the landing page, PWA files and webhooks without
 * touching the active theme or wp-admin.
 */
class ZP_Router {

	/** Set for a reseller's store pages: they open straight away, with no launch animation. */
	public static $no_splash = false;

	const USER_PAGES  = array( 'dashboard', 'buy', 'proxies', 'orders', 'wallet', 'tickets', 'reseller', 'profile', 'help' );
	const GUEST_PAGES = array( 'login', 'register', 'forgot', 'reset' );
	const PUBLIC_PAGES = array(
		''               => 'home',
		'pricing'        => 'pricing',
		'faq'            => 'faq',
		'about'          => 'about',
		'contact'        => 'contact',
		'terms'          => 'terms',
		'privacy'        => 'privacy',
		'refund-policy'  => 'refund-policy',
		'acceptable-use' => 'acceptable-use',
		'cookies'        => 'cookies',
	);
	const ADMIN_PAGES = array( 'dashboard', 'pricing', 'orders', 'proxies', 'users', 'resellers', 'deposits', 'transactions', 'tickets', 'settings' );

	public static function dispatch() {
		if ( 'cli' === PHP_SAPI || is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ) {
			return;
		}
		list( $base, $page, $sub ) = self::parse();

		$ds = zp_domain_store();
		if ( $ds ) {
			// A reseller's own domain: the store lives at the root and nothing of the platform shows.
			if ( in_array( $base, array( 'zp-manifest', 'zp-sw', 'zp-offline', 'zp-notify', ZP_USER_BASE ), true ) ) {
				$GLOBALS['zp_reseller_ctx'] = $ds;
			} elseif ( '' === $base || in_array( $base, self::GUEST_PAGES, true ) || 'privacy' === $base || 'refund' === $base ) {
				self::store( $ds->slug, $base );
				self::store_offline( $ds );
			} else {
				zp_redirect( home_url( '/' ) );
			}
		}

		switch ( $base ) {
			case 'zp-manifest':
				self::manifest();
				break;
			case 'zp-sw':
				self::service_worker();
				break;
			case 'zp-notify':
				ZP_Notify::endpoint();
				break;
			case 'zp-app':
				ZP_App::endpoint( $page );
				break;
			case 'zp-offline':
				self::no_cache();
				include ZP_DIR . 'templates/offline.php';
				exit;
			case 'zp-webhook':
				if ( 'paystack' === $page ) {
					ZP_Gateways::paystack_webhook();
				}
				status_header( 404 );
				exit;
			case ZP_USER_BASE:
				self::user( $page );
				break;
			case 's':
				self::store( $page, $sub );
				break;
			case ZP_ADMIN_BASE:
				self::admin( $page );
				break;
			default:
				if ( zp_get( 'landing' ) && isset( self::PUBLIC_PAGES[ $base ] ) && '' === $page ) { // phpcs:ignore
					self::no_cache();
					if ( 'contact' === $base && 'POST' === self::method() ) {
						self::contact();
					}
					$view = self::PUBLIC_PAGES[ $base ];
					include ZP_DIR . 'templates/public/layout.php';
					exit;
				}
				break;
		}
	}

	private static function method() {
		return isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( sanitize_key( $_SERVER['REQUEST_METHOD'] ) ) : 'GET';
	}

	/** @return array{0:string,1:string} [base, page] */
	private static function parse() {
		if ( isset( $_GET['zp'] ) ) { // phpcs:ignore
			return array( sanitize_key( $_GET['zp'] ), sanitize_key( isset( $_GET['zpp'] ) ? $_GET['zpp'] : '' ), '' ); // phpcs:ignore
		}
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}
		$path = trim( preg_replace( '#^index\.php#', '', trim( $path, '/' ) ), '/' );
		$bits = explode( '/', $path );
		$base = strtolower( $bits[0] );
		if ( ! preg_match( '/^[a-z0-9.\-]*$/', $base ) ) {
			return array( '-', '', '' );
		}
		return array( $base, isset( $bits[1] ) ? sanitize_title( $bits[1] ) : '', isset( $bits[2] ) ? sanitize_key( $bits[2] ) : '' );
	}

	public static function no_cache() {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'X-LiteSpeed-Cache-Control: no-cache' );
		header( 'X-Frame-Options: SAMEORIGIN' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		status_header( 200 );
	}

	private static function contact() {
		$back = zp_route_url( 'contact' );
		if ( zp_post( 'zp_do' ) !== 'contact' || ! wp_verify_nonce( zp_post( '_zpnonce' ), 'zp_contact' ) || ! empty( $_POST['website'] ) ) { // phpcs:ignore
			zp_flash( 'Something went wrong. Please try again.', 'error' );
			zp_redirect( $back );
		}
		$name    = zp_post( 'name' );
		$email   = sanitize_email( zp_post( 'email' ) );
		$message = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : ''; // phpcs:ignore
		if ( mb_strlen( $name ) < 2 || ! is_email( $email ) || mb_strlen( $message ) < 10 ) {
			zp_flash( 'Please fill in your name, a valid email and a message.', 'error' );
			zp_redirect( $back );
		}
		if ( zp_throttled( 'contact', 5, HOUR_IN_SECONDS ) ) {
			zp_flash( 'You have sent several messages already. Please try again later.', 'error' );
			zp_redirect( $back );
		}
		$to = is_email( zp_get( 'support_email' ) ) ? zp_get( 'support_email' ) : get_option( 'admin_email' );
		wp_mail( $to, '[' . zp_get( 'brand' ) . '] Contact form: ' . $name, $message . "\n\nFrom: {$name} <{$email}>", array( 'Reply-To: ' . $email ) );
		zp_flash( 'Thanks, your message is on its way. We will reply by email.' );
		zp_redirect( $back );
	}

	/* ---------- User panel ---------- */

	private static function user( $page ) {
		self::no_cache();
		$page = $page ? $page : 'dashboard';

		// Customers of a reseller get the reseller's brand and payment details throughout the panel.
		if ( is_user_logged_in() ) {
			$store = ZP_Resellers::of_user( get_current_user_id() );
			if ( $store ) {
				$GLOBALS['zp_reseller_ctx'] = $store;
			}
		}

		$ds = zp_domain_store();
		if ( $ds && is_user_logged_in() && ! ZP_Resellers::is_customer_of( get_current_user_id(), $ds->user_id ) ) {
			wp_logout();
			zp_redirect( zp_url( 'login' ) );
		}

		if ( 'logout' === $page ) {
			$to = zp_url( 'login' );
			if ( is_user_logged_in() && wp_verify_nonce( zp_query( '_zpnonce' ), 'zp_logout' ) ) {
				wp_logout();
			}
			zp_redirect( $to );
		}

		if ( in_array( $page, self::GUEST_PAGES, true ) ) {
			if ( is_user_logged_in() ) {
				zp_redirect( zp_url( 'dashboard' ) );
			}
			if ( 'POST' === self::method() ) {
				ZP_User_Actions::handle_guest( $page );
			}
			$view = $page;
			include ZP_DIR . 'templates/layout-auth.php';
			exit;
		}

		if ( ! is_user_logged_in() ) {
			if ( 'ajax' === $page ) {
				wp_send_json( array( 'ok' => false, 'error' => 'Your session expired. Please sign in again.' ), 401 );
			}
			zp_redirect( zp_url( 'login' ) );
		}

		if ( 'ajax' === $page ) {
			ZP_User_Actions::ajax();
		}
		if ( 'export' === $page ) {
			ZP_User_Actions::export();
		}
		if ( ! in_array( $page, self::USER_PAGES, true ) || ( 'reseller' === $page && zp_reseller_ctx() ) ) {
			zp_redirect( zp_url( 'dashboard' ) );
		}
		if ( 'POST' === self::method() ) {
			ZP_User_Actions::handle( $page );
		}
		if ( 'wallet' === $page && zp_query( 'zp_verify' ) ) {
			$res = ZP_Gateways::paystack_verify( zp_query( 'zp_verify' ) );
			zp_flash( is_wp_error( $res ) ? 'We could not confirm this payment yet. If you were charged, contact support with your reference.' : 'Payment received. Your wallet has been credited.', is_wp_error( $res ) ? 'error' : 'success' );
			zp_redirect( zp_url( 'wallet' ) );
		}

		$user = wp_get_current_user();
		ZP_Orders::maybe_sync_user( $user->ID );
		$view    = $page;
		$balance = ZP_Wallet::balance( $user->ID );
		include ZP_DIR . 'templates/layout-user.php';
		exit;
	}

	/* ---------- Reseller stores: /s/{slug}/ ---------- */

	private static function store( $slug, $page ) {
		$store = ZP_Resellers::by_slug( $slug );
		if ( ! ZP_Resellers::is_live( $store ) ) {
			return; // unknown, suspended or unpaid store: let WordPress answer with its normal 404
		}
		self::no_cache();
		$GLOBALS['zp_reseller_ctx'] = $store;
		self::$no_splash            = true;

		if ( '' === $page ) {
			include ZP_DIR . 'templates/store.php';
			exit;
		}
		if ( 'privacy' === $page || 'refund' === $page ) {
			$doc = $page;
			include ZP_DIR . 'templates/store-policy.php';
			exit;
		}
		if ( ! in_array( $page, self::GUEST_PAGES, true ) ) {
			zp_redirect( ZP_Resellers::store_url( $store ) );
		}
		if ( is_user_logged_in() ) {
			zp_redirect( zp_route_url( ZP_USER_BASE, 'dashboard' ) );
		}
		if ( 'POST' === self::method() ) {
			ZP_User_Actions::handle_guest( $page );
		}
		$view = $page;
		include ZP_DIR . 'templates/layout-auth.php';
		exit;
	}

	/** Shown on a store's own domain while the store is paused, so visitors do not land on an unrelated page. */
	private static function store_offline( $store ) {
		self::no_cache();
		status_header( 503 );
		$GLOBALS['zp_reseller_ctx'] = $store;
		echo '<!doctype html><html lang="en"><head>';
		self::head( 'Temporarily unavailable' );
		echo '</head><body class="zp-body"><div class="zp-content" style="min-height:100dvh;justify-content:center;align-items:center"><div class="zp-empty"><div class="zp-empty__art">' . zp_illustration( 'empty-proxies' ) . '</div><h3>' . esc_html( $store->brand ) . ' is temporarily unavailable</h3><p>Please check back soon or contact the store owner.</p></div></div></body></html>'; // phpcs:ignore
		exit;
	}

	/* ---------- Admin panel ---------- */

	private static function admin( $page ) {
		self::no_cache();
		$page = $page ? $page : 'dashboard';

		if ( 'login' === $page ) {
			if ( current_user_can( 'manage_options' ) ) {
				zp_redirect( zp_admin_url() );
			}
			if ( 'POST' === self::method() ) {
				ZP_Admin_Actions::login();
			}
			$view = 'admin-login';
			include ZP_DIR . 'templates/layout-auth.php';
			exit;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			zp_redirect( zp_admin_url( 'login' ) );
		}
		if ( ! in_array( $page, self::ADMIN_PAGES, true ) ) {
			zp_redirect( zp_admin_url() );
		}
		if ( 'POST' === self::method() ) {
			ZP_Admin_Actions::handle( $page );
		}
		$user = wp_get_current_user();
		$view = $page;
		include ZP_DIR . 'templates/layout-admin.php';
		exit;
	}

	/* ---------- PWA ---------- */

	private static function manifest() {
		$brand = zp_get( 'brand' );
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		status_header( 200 );
		echo wp_json_encode(
			array(
				'id'               => '/?zaxproxy-app',
				'name'             => $brand,
				'short_name'       => $brand,
				'description'      => 'Buy and manage private proxies.',
				'start_url'        => zp_url( 'dashboard' ) . ( zp_pretty() ? '?' : '&' ) . 'source=pwa',
				'scope'            => home_url( '/' ),
				'display'          => 'standalone',
				'orientation'      => 'portrait-primary',
				'background_color' => '#0b0d14',
				'theme_color'      => '#0b0d14',
				'categories'       => array( 'business', 'utilities' ),
				'icons'            => array(
					array( 'src' => ZP_URL . 'assets/icons/icon-192.png?v=' . ZP_VERSION, 'sizes' => '192x192', 'type' => 'image/png', 'purpose' => 'any' ),
					array( 'src' => ZP_URL . 'assets/icons/icon-512.png?v=' . ZP_VERSION, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any' ),
					array( 'src' => ZP_URL . 'assets/icons/maskable-512.png?v=' . ZP_VERSION, 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable' ),
				),
				'shortcuts'        => array(
					array( 'name' => 'Buy proxies', 'url' => zp_url( 'buy' ) ),
					array( 'name' => 'My proxies', 'url' => zp_url( 'proxies' ) ),
					array( 'name' => 'Wallet', 'url' => zp_url( 'wallet' ) ),
				),
			)
		);
		exit;
	}

	private static function service_worker() {
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache' );
		status_header( 200 );
		$js = file_get_contents( ZP_DIR . 'assets/js/sw.js' ); // phpcs:ignore
		echo str_replace( // phpcs:ignore
			array( '__ZP_VERSION__', '__ZP_OFFLINE__', '__ZP_ASSETS__' ),
			array( ZP_VERSION, esc_url_raw( zp_route_url( 'zp-offline' ) ), esc_url_raw( ZP_URL . 'assets/' ) ),
			$js
		);
		exit;
	}

	/** Shared <head> tags for every standalone page. */
	public static function head( $title, $index = false, $description = '' ) {
		$brand = zp_get( 'brand' );
		?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, viewport-fit=cover">
<script>(function(){var d=document,stop=function(e){e.preventDefault()};['gesturestart','gesturechange','gestureend'].forEach(function(n){d.addEventListener(n,stop,{passive:false})});d.addEventListener('touchmove',function(e){if(e.touches&&e.touches.length>1)e.preventDefault()},{passive:false});var last=0;d.addEventListener('touchend',function(e){var now=Date.now(),t=e.target;if(now-last<320&&!(t&&t.closest&&t.closest('a,button,input,textarea,select,label,summary,[role=button],[data-zp-copy],.zp-country,.zp-typepick__item')))e.preventDefault();last=now},{passive:false});d.addEventListener('wheel',function(e){if(e.ctrlKey)e.preventDefault()},{passive:false})})();</script>
<?php $full_title = false !== stripos( $title, $brand ) ? $title : $title . ' · ' . $brand; // a title that already names the brand is used as it is ?>
<title><?php echo esc_html( $full_title ); ?></title>
<?php if ( $description ) : ?><meta name="description" content="<?php echo esc_attr( $description ); ?>"><?php endif; ?>
<?php
if ( $index ) {
	ZP_SEO::head( $full_title, $description );
} else {
	echo '<meta name="robots" content="noindex">' . "\n";
}
?>
<meta name="theme-color" content="#0b0d14" id="zp-theme-color">
<script>try{if(localStorage.getItem('zp-hidebal')==='1')document.documentElement.classList.add('zp-hidebal')}catch(e){}</script>
<?php if ( ZP_Shield::alias_on() ) : ?><script>window.zpPlain=function(){try{if(document.cookie.indexOf('zp_plain=')<0){document.cookie='zp_plain=1; path=/; max-age=86400; samesite=lax';location.reload();}}catch(e){}};</script><?php endif; ?>
<link rel="manifest" href="<?php echo esc_url( zp_route_url( 'zp-manifest' ) ); ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $brand ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( ZP_URL . 'assets/icons/apple-touch-icon.png?v=' . ZP_VERSION ); ?>">
<?php if ( ! zp_reseller_ctx() ) : ?><link rel="icon" type="image/svg+xml" href="<?php echo esc_url( ZP_URL . 'assets/icons/favicon.svg?v=' . ZP_VERSION ); ?>">
<link rel="shortcut icon" href="<?php echo esc_url( ZP_URL . 'assets/icons/favicon.ico?v=' . ZP_VERSION ); ?>">
<link rel="icon" type="image/png" sizes="32x32" href="<?php echo esc_url( ZP_URL . 'assets/icons/favicon-32.png?v=' . ZP_VERSION ); ?>"><?php endif; ?>
<link rel="icon" type="image/png" sizes="192x192" href="<?php echo esc_url( ZP_URL . 'assets/icons/icon-192.png?v=' . ZP_VERSION ); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Sora:wght@700;800&family=Source+Serif+4:opsz,wght@8..60,700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<script>(function(){try{var t=localStorage.getItem('zp-theme')||(matchMedia('(prefers-color-scheme: light)').matches?'light':'dark');document.documentElement.setAttribute('data-theme',t);}catch(e){document.documentElement.setAttribute('data-theme','dark');}})();</script>
<?php if ( ! empty( $GLOBALS['zp_reseller_ctx'] ) ) { self::$no_splash = true; } ?>
<?php if ( self::$no_splash ) : ?><script>try{sessionStorage.setItem('zp-splash','1')}catch(e){}</script><?php endif; ?>
<?php if ( ! self::$no_splash ) : ?><script>(function(){try{if(!sessionStorage.getItem('zp-splash')){sessionStorage.setItem('zp-splash','1');document.documentElement.className+=' zp-splashing';setTimeout(function(){document.documentElement.className=document.documentElement.className.replace(' zp-splashing','');},5700);}}catch(e){}})();</script><?php endif; ?>
<link rel="stylesheet" href="<?php echo esc_url( zp_asset( 'css/app.css' ) ); ?>"<?php echo ZP_Shield::alias_on() ? ' onerror="window.zpPlain&&zpPlain()"' : ''; ?>>
<?php
$zp_color = ZP_Resellers::color( zp_reseller_ctx() );
if ( $zp_color ) : // a store's own brand colour replaces the platform palette everywhere on its pages
	?>
<?php $zp_dark = zp_mix( $zp_color, '#000000', 0.14 ); // gradient end: a little darker, so white text stays readable across the button ?>
<style>html[data-theme]{--zp-brand:<?php echo esc_html( $zp_color ); ?>;--zp-brand-2:<?php echo esc_html( $zp_dark ); ?>;--zp-grad:linear-gradient(135deg,<?php echo esc_html( $zp_color ); ?> 0%,<?php echo esc_html( $zp_dark ); ?> 100%);--zp-soft:color-mix(in srgb,<?php echo esc_html( $zp_color ); ?> 16%,transparent)}html[data-theme="dark"]{--zp-soft-ink:color-mix(in srgb,<?php echo esc_html( $zp_color ); ?> 45%,#fff)}html[data-theme="light"]{--zp-soft-ink:color-mix(in srgb,<?php echo esc_html( $zp_color ); ?> 72%,#000)}.zp-btn--primary,.zp-hero,.zp-balance,.zp-storelink,.zp-phero__orb,.zp-pcta,.zp-preseller,.zp-auth__art{box-shadow:0 20px 44px -26px <?php echo esc_html( $zp_color ); ?>!important}.zp-wordmark b,.zp-phero h1 em{background:none;-webkit-background-clip:border-box;background-clip:border-box;color:var(--zp-soft-ink);-webkit-text-fill-color:currentColor}.zp-brand--light .zp-wordmark b{color:#fff}.zp-splash__name .zp-wordmark b{color:color-mix(in srgb,<?php echo esc_html( $zp_color ); ?> 45%,#fff)}.zp-splash__orb--a{background:radial-gradient(circle,<?php echo esc_html( $zp_color ); ?> 0%,transparent 68%)}.zp-splash__orb--b{background:radial-gradient(circle,<?php echo esc_html( $zp_dark ); ?> 0%,transparent 68%)}.zp-phero__glow{background:radial-gradient(520px 320px at 18% 20%,color-mix(in srgb,<?php echo esc_html( $zp_color ); ?> 30%,transparent),transparent 65%)}</style>
<?php endif; ?>
		<?php
	}

	/**
	 * Launch splash: a 5 second animated intro shown once per app session
	 * (first page opened in a tab or after launching the installed app).
	 */
	public static function splash() {
		if ( self::$no_splash ) {
			return;
		}
		$brand = zp_get( 'brand' );
		?>
<div class="zp-splash" aria-hidden="true">
	<div class="zp-splash__orb zp-splash__orb--a"></div>
	<div class="zp-splash__orb zp-splash__orb--b"></div>
	<div class="zp-splash__grid"></div>
	<div class="zp-splash__core">
		<div class="zp-splash__logo">
			<span class="zp-splash__ring"></span><span class="zp-splash__ring zp-splash__ring--2"></span><span class="zp-splash__ring zp-splash__ring--3"></span>
			<span class="zp-splash__halo"></span>
			<?php echo zp_logo( 104 ); // phpcs:ignore ?>
		</div>
		<div class="zp-splash__name"><?php echo zp_wordmark(); // phpcs:ignore ?></div>
		<div class="zp-splash__tag">Private proxies, ready in minutes</div>
		<div class="zp-splash__bar"><i></i></div>
		<div class="zp-splash__status"><span>Securing connection</span><span>Loading locations</span><span>Almost ready</span></div>
	</div>
	<div class="zp-splash__foot"><span><?php echo zp_icon( 'shield', 14 ); // phpcs:ignore ?>Private</span><span><?php echo zp_icon( 'zap', 14 ); // phpcs:ignore ?>Fast</span><span><?php echo zp_icon( 'globe', 14 ); // phpcs:ignore ?>Global</span></div>
</div>
		<?php
	}

	public static function foot( $ajax = false, $admin = false ) {
		$cfg = array( 'cur' => zp_get( 'currency_symbol', '₦' ), 'sw' => zp_route_url( 'zp-sw' ), 'scope' => wp_parse_url( home_url( '/' ), PHP_URL_PATH ) ?: '/' );
		if ( is_user_logged_in() ) {
			// In-app navigation: which URLs can be swapped in place without a reload.
			$cfg['uid']   = get_current_user_id();
			$cfg['base']  = $admin ? zp_admin_url() : zp_route_url( ZP_USER_BASE );
			$cfg['pages'] = $admin ? self::ADMIN_PAGES : self::USER_PAGES;
			$cfg['home']  = 'dashboard';
			$cfg['notify'] = array( 'url' => zp_route_url( 'zp-notify' ), 'nonce' => wp_create_nonce( 'zp_notify' ), 'key' => ZP_Notify::public_key() );
		}
		if ( $ajax ) {
			$cfg['ajax']  = zp_url( 'ajax' );
			$cfg['nonce'] = wp_create_nonce( 'zp_ajax' );
		}
		echo '<div class="zp-toasts" id="zp-toasts" aria-live="polite"></div>';
		echo '<script>window.ZP=' . wp_json_encode( $cfg ) . ';</script>';
		echo '<script src="' . esc_url( zp_asset( 'js/app.js' ) ) . '" defer></script>'; // phpcs:ignore
	}
}
