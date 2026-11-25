<?php
/**
 * Hardening: keeps the software underneath out of sight and its own login and admin screens
 * closed to the public.
 *
 * - The built-in login page and dashboard answer "not found" unless a staff member is already
 *   signed in through the store's own admin panel.
 * - XML-RPC, the public REST API, feeds, author pages, sitemaps and any other built-in page
 *   answer with the store's own "not found" page.
 * - Asset links use a neutral /zx/ address (when the web server supports it) so page source
 *   does not show plugin or upload folder names.
 * - Security headers are sent with every page.
 *
 * Emergency switch: add  define( 'ZP_ALLOW_WP_LOGIN', true );  to wp-config.php to reopen the
 * built-in login page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ZP_Shield {

	const MARK = 'ZaxProxy';

	/** Runs as soon as the plugin file is loaded, before anything is printed. */
	public static function early() {
		if ( 'cli' === PHP_SAPI || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			return;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? strtolower( basename( (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : ''; // phpcs:ignore
		if ( in_array( $script, array( 'xmlrpc.php', 'wp-trackback.php', 'wp-links-opml.php', 'wp-mail.php', 'wp-signup.php', 'wp-activate.php', 'wp-comments-post.php' ), true ) ) {
			self::gone();
		}
		if ( ! headers_sent() ) {
			header_remove( 'X-Powered-By' );
			header( 'X-Content-Type-Options: nosniff' );
			header( 'X-Frame-Options: SAMEORIGIN' );
			header( 'Referrer-Policy: strict-origin-when-cross-origin' );
			header( 'Permissions-Policy: geolocation=(), camera=(), microphone=(), payment=()' );
			if ( is_ssl() ) {
				header( 'Strict-Transport-Security: max-age=15552000' );
			}
		}
		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
			define( 'DISALLOW_FILE_EDIT', true ); // no editing of code from the dashboard
		}

		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'x_redirect_by', '__return_false' );
		add_filter( 'the_generator', '__return_empty_string' );
		add_filter( 'wp_sitemaps_enabled', '__return_false' );
		add_filter(
			'wp_headers',
			function ( $headers ) {
				unset( $headers['X-Pingback'], $headers['X-Powered-By'] );
				return $headers;
			}
		);
		foreach ( array( 'wp_generator', 'rsd_link', 'wlwmanifest_link', 'rest_output_link_wp_head', 'wp_shortlink_wp_head', 'wp_oembed_add_discovery_links', 'feed_links', 'feed_links_extra', 'print_emoji_detection_script' ) as $fn ) {
			remove_action( 'wp_head', $fn );
			remove_action( 'wp_head', $fn, 2 );
			remove_action( 'wp_head', $fn, 3 );
			remove_action( 'wp_head', $fn, 7 );
		}
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'template_redirect', 'wp_shortlink_header', 11 );

		add_action( 'init', array( __CLASS__, 'guard' ), 0 );
		add_action( 'init', array( __CLASS__, 'setup' ), 6 );
		add_action( 'login_init', array( __CLASS__, 'login' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'fallback' ), 0 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots' ), 99 );
		add_filter(
			'login_url',
			function () {
				return zp_admin_url();
			},
			99
		);
		add_filter(
			'logout_redirect',
			function () {
				return zp_admin_url();
			},
			99
		);
		add_filter(
			'rest_authentication_errors',
			function ( $result ) {
				if ( ! self::is_staff() ) {
					return new WP_Error( 'not_found', 'Not found', array( 'status' => 404 ) );
				}
				return $result;
			},
			99
		);
		// One generic answer for failed sign-ins on any remaining path: never say which part was wrong.
		add_filter(
			'login_errors',
			function () {
				return 'Those details are not correct.';
			}
		);
	}

	private static function is_staff() {
		return is_user_logged_in() && current_user_can( 'manage_options' );
	}

	private static function path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home && '/' !== $home && 0 === strpos( $path, $home ) ) {
			$path = '/' . substr( $path, strlen( $home ) );
		}
		return strtolower( $path );
	}

	/** Closed doors: the dashboard and the REST API for everyone who is not signed-in staff. */
	public static function guard() {
		if ( wp_doing_cron() || ( defined( 'WP_INSTALLING' ) && WP_INSTALLING ) ) {
			return;
		}
		$script = isset( $_SERVER['SCRIPT_NAME'] ) ? strtolower( basename( (string) wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) ) : ''; // phpcs:ignore
		if ( is_admin() && ! in_array( $script, array( 'admin-ajax.php', 'admin-post.php' ), true ) && ! is_user_logged_in() ) {
			self::not_found();
		}
		$path = self::path();
		$rest = '/' . trim( rest_get_url_prefix(), '/' );
		if ( ( 0 === strpos( $path, $rest . '/' ) || $path === $rest || isset( $_GET['rest_route'] ) ) && ! self::is_staff() ) { // phpcs:ignore
			self::not_found();
		}
		if ( isset( $_GET['author'] ) || isset( $_GET['feed'] ) ) { // phpcs:ignore -- no user or feed listings
			self::not_found();
		}
	}

	/** The built-in login page is not public. Staff sign in through the store's own admin panel. */
	public static function login() {
		if ( defined( 'ZP_ALLOW_WP_LOGIN' ) && ZP_ALLOW_WP_LOGIN ) {
			return;
		}
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( $_REQUEST['action'] ) : ''; // phpcs:ignore
		if ( 'logout' === $action || self::is_staff() || isset( $_REQUEST['interim-login'] ) ) { // phpcs:ignore
			return;
		}
		self::not_found();
	}

	/** Anything the store itself did not answer gets the store's own "not found" page. */
	public static function fallback() {
		if ( is_robots() || ( function_exists( 'is_favicon' ) && is_favicon() ) ) {
			return;
		}
		if ( ! zp_get( 'landing' ) ) {
			return; // the owner is running a normal website next to the store
		}
		self::not_found();
	}

	public static function robots() {
		return "User-agent: *\nDisallow: /" . ZP_USER_BASE . "/\nAllow: /\n";
	}

	public static function not_found() {
		if ( ! headers_sent() ) {
			status_header( 404 );
			nocache_headers();
		}
		include ZP_DIR . 'templates/404.php';
		exit;
	}

	/** A bare 404 for requests that should not even look like part of a website. */
	private static function gone() {
		if ( ! headers_sent() ) {
			http_response_code( 404 );
			header( 'Content-Type: text/plain; charset=utf-8' );
		}
		echo 'Not found';
		exit;
	}

	/* ---------- Neutral asset addresses ---------- */

	public static function alias_on() {
		return 'on' === get_option( 'zp_alias' );
	}

	/** Public address of an uploaded file (store logos) without the upload folder name. */
	public static function media_url( $url ) {
		if ( ! self::alias_on() || ! $url ) {
			return $url;
		}
		$up   = wp_get_upload_dir();
		$base = set_url_scheme( $up['baseurl'] );
		$url  = set_url_scheme( $url );
		return 0 === strpos( $url, $base ) ? home_url( '/zx/media' . substr( $url, strlen( $base ) ) ) : $url;
	}

	private static function rules() {
		$plugin  = trim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( ZP_DIR ) ), '/' );
		$up      = wp_get_upload_dir();
		$uploads = trim( str_replace( wp_normalize_path( ABSPATH ), '', wp_normalize_path( $up['basedir'] ) ), '/' );
		if ( ! preg_match( '#^[A-Za-z0-9_\-/.]+$#', $plugin ) || ! preg_match( '#^[A-Za-z0-9_\-/.]+$#', $uploads ) ) {
			return '';
		}
		$base = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$base = $base ? $base : '/';
		return implode(
			"\n",
			array(
				'# BEGIN ' . self::MARK,
				'<IfModule mod_rewrite.c>',
				'RewriteEngine On',
				'RewriteBase ' . $base,
				'RewriteRule ^zx/assets/(.*)$ ' . $plugin . '/assets/$1 [L]',
				'RewriteRule ^zx/media/(.*)$ ' . $uploads . '/$1 [L]',
				'RewriteRule ^(readme\.html|license\.txt|wp-config-sample\.php|xmlrpc\.php|wp-trackback\.php|wp-links-opml\.php|wp-mail\.php|wp-signup\.php|wp-activate\.php)$ - [R=404,L,NC]',
				'RewriteRule ^' . preg_quote( $plugin, '#' ) . '/(includes|templates)/ - [R=404,L,NC]',
				'RewriteRule ^' . preg_quote( $uploads, '#' ) . '/.*\.(php|phtml|phar)$ - [R=404,L,NC]',
				'RewriteCond %{HTTP_COOKIE} !wordpress_logged_in_ [NC]',
				'RewriteCond %{REQUEST_URI} !admin-ajax\.php [NC]',
				'RewriteRule ^(wp-admin|wp-includes)(/|$) - [R=404,L,NC]',
				'</IfModule>',
				'# END ' . self::MARK,
				'',
			)
		);
	}

	private static function strip( $text ) {
		return (string) preg_replace( '/# BEGIN ' . self::MARK . '\b.*?# END ' . self::MARK . '\s*/s', '', (string) $text );
	}

	/**
	 * Once per version: install the web-server rules, then prove with a real request that the
	 * neutral asset address works. If it does not, the rules are removed again and normal links stay.
	 */
	public static function setup() {
		if ( get_option( 'zp_shield_ver' ) === ZP_VERSION || wp_doing_ajax() || wp_doing_cron() ) {
			return;
		}
		update_option( 'zp_shield_ver', ZP_VERSION, false ); // set first: the check below calls this site again
		$file  = ABSPATH . '.htaccess';
		$rules = self::rules();
		$state = 'off';
		if ( $rules && file_exists( $file ) && is_readable( $file ) && wp_is_writable( $file ) ) {
			$old   = (string) file_get_contents( $file ); // phpcs:ignore
			$clean = self::strip( $old );
			if ( ! file_exists( $file . '.zp-backup' ) ) {
				@file_put_contents( $file . '.zp-backup', $clean ); // phpcs:ignore
			}
			if ( false !== @file_put_contents( $file, $rules . $clean, LOCK_EX ) ) { // phpcs:ignore
				$res  = wp_remote_get( home_url( '/zx/assets/css/app.css?check=' . time() ), array( 'timeout' => 10, 'sslverify' => false, 'redirection' => 0 ) );
				$ok   = ! is_wp_error( $res ) && 200 === (int) wp_remote_retrieve_response_code( $res ) && false !== strpos( (string) wp_remote_retrieve_body( $res ), '--zp-brand' );
				$home = wp_remote_get( home_url( '/?check=' . time() ), array( 'timeout' => 10, 'sslverify' => false, 'redirection' => 0 ) );
				$up   = ! is_wp_error( $home ) && (int) wp_remote_retrieve_response_code( $home ) < 500;
				if ( $ok && $up ) {
					$state = 'on';
				} else {
					@file_put_contents( $file, $clean, LOCK_EX ); // phpcs:ignore -- put everything back exactly as it was
				}
			}
		}
		update_option( 'zp_alias', $state, true );
	}

	/** Remove the web-server rules (plugin switched off). */
	public static function teardown() {
		$file = ABSPATH . '.htaccess';
		if ( file_exists( $file ) && wp_is_writable( $file ) ) {
			@file_put_contents( $file, self::strip( (string) file_get_contents( $file ) ), LOCK_EX ); // phpcs:ignore
		}
		delete_option( 'zp_alias' );
		delete_option( 'zp_shield_ver' );
	}
}
