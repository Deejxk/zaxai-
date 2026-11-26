<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Search engines: what they may read (robots.txt), the list of pages (sitemap.xml) and the tags in
 * each public page that tell Google what the site is, what it is called and how to show it.
 */
class ZP_SEO {

	public static function boot() {
		add_action( 'init', array( __CLASS__, 'early' ), 1 );
	}

	/** The address visitors use for this site (a reseller's own domain inside their store). */
	public static function origin() {
		$r = zp_reseller_ctx();
		if ( $r && ! empty( $r->domain ) ) {
			return 'https://' . preg_replace( '/[^a-z0-9.\-]/', '', strtolower( (string) $r->domain ) );
		}
		return untrailingslashit( home_url() );
	}

	private static function path() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home && 0 === strpos( $path, $home ) ) {
			$path = substr( $path, strlen( $home ) );
		}
		return '/' . ltrim( $path, '/' );
	}

	/** robots.txt and sitemap.xml are answered here, before anything else looks at the request. */
	public static function early() {
		if ( ! zp_get( 'landing' ) ) {
			return; // the owner runs a normal website next to the store and keeps its own files
		}
		$path = self::path();
		if ( '/robots.txt' === $path ) {
			self::send( 'text/plain', self::robots() );
		}
		if ( '/sitemap.xml' === $path || '/sitemap_index.xml' === $path || '/wp-sitemap.xml' === $path ) {
			self::send( 'application/xml', self::sitemap() );
		}
	}

	private static function send( $type, $body ) {
		status_header( 200 );
		header( 'Content-Type: ' . $type . '; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		header( 'X-Robots-Tag: noindex' ); // the file itself is not a search result
		echo $body; // phpcs:ignore
		exit;
	}

	public static function robots() {
		$home = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$base = '/' . ltrim( $home, '/' );
		$out  = "User-agent: *\nAllow: /\n";
		// Private areas and machine addresses: nothing there is a search result.
		// (The admin address is deliberately not listed: this file is public.)
		foreach ( array( 'zp-app/', 'zp-notify/', 'zp-webhook/', 'zp-sw/', 'zp-offline/' ) as $p ) {
			$out .= 'Disallow: ' . $base . $p . "\n";
		}
		foreach ( array_diff( ZP_Router::USER_PAGES, array( 'help' ) ) as $p ) {
			$out .= 'Disallow: ' . $base . ZP_USER_BASE . '/' . $p . "/\n";
		}
		return $out . "\nSitemap: " . self::origin() . "/sitemap.xml\n";
	}

	/** Public pages, most important first. */
	private static function pages() {
		$list = array(
			''        => array( '1.0', 'daily' ),
			'pricing' => array( '0.9', 'daily' ),
			'faq'     => array( '0.8', 'weekly' ),
			'about'   => array( '0.6', 'monthly' ),
			'contact' => array( '0.6', 'monthly' ),
		);
		foreach ( array( 'terms', 'privacy', 'refund-policy', 'acceptable-use', 'cookies' ) as $legal ) {
			$list[ $legal ] = array( '0.3', 'yearly' );
		}
		return $list;
	}

	public static function sitemap() {
		$origin = self::origin();
		$day    = gmdate( 'Y-m-d' );
		$xml    = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
		if ( zp_reseller_ctx() ) {
			$xml .= '<url><loc>' . esc_url( $origin . '/' ) . '</loc><lastmod>' . $day . '</lastmod></url>' . "\n";
		} else {
			foreach ( self::pages() as $slug => $meta ) {
				$loc  = '' === $slug ? home_url( '/' ) : zp_route_url( $slug );
				$xml .= '<url><loc>' . esc_url( $loc ) . '</loc><lastmod>' . $day . '</lastmod><changefreq>' . $meta[1] . '</changefreq><priority>' . $meta[0] . '</priority></url>' . "\n";
			}
			foreach ( array( 'login', 'register' ) as $p ) {
				$xml .= '<url><loc>' . esc_url( zp_url( $p ) ) . '</loc><changefreq>monthly</changefreq><priority>0.4</priority></url>' . "\n";
			}
		}
		return $xml . '</urlset>' . "\n";
	}

	/** A verification code pasted by the owner: the bare code, or the whole tag the search engine gives. */
	public static function clean_code( $raw ) {
		$raw = (string) $raw;
		if ( preg_match( '/content\s*=\s*["\']([^"\']+)["\']/i', $raw, $m ) ) {
			$raw = $m[1];
		}
		return substr( preg_replace( '/[^A-Za-z0-9_\-]/', '', $raw ), 0, 120 );
	}

	private static function json( $data ) {
		echo '<script type="application/ld+json">' . wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG ) . "</script>\n"; // phpcs:ignore
	}

	/** Tags for a page that search engines may list. Printed inside <head>. */
	public static function head( $full_title, $description ) {
		$brand  = (string) zp_get( 'brand' );
		$origin = self::origin();
		$path   = self::path();
		$url    = $origin . ( '/' === $path ? '/' : trailingslashit( $path ) );
		$image  = ZP_URL . 'assets/og.png?v=' . ZP_VERSION;
		$desc   = (string) $description;
		$own    = ! zp_reseller_ctx(); // the main site, not a reseller's store

		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		echo '<meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1">' . "\n";
		foreach ( array(
			'og:type'        => 'website',
			'og:site_name'   => $brand,
			'og:title'       => $full_title,
			'og:description' => $desc,
			'og:url'         => $url,
			'og:locale'      => 'en_NG',
		) as $k => $v ) {
			if ( '' !== $v ) {
				echo '<meta property="' . esc_attr( $k ) . '" content="' . esc_attr( $v ) . '">' . "\n";
			}
		}
		if ( $own ) {
			echo '<meta property="og:image" content="' . esc_url( $image ) . '">' . "\n" . '<meta property="og:image:width" content="1200">' . "\n" . '<meta property="og:image:height" content="630">' . "\n";
		}
		echo '<meta name="twitter:card" content="' . ( $own ? 'summary_large_image' : 'summary' ) . '">' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $full_title ) . '">' . "\n";
		if ( '' !== $desc ) {
			echo '<meta name="twitter:description" content="' . esc_attr( $desc ) . '">' . "\n";
		}
		if ( ! $own ) {
			return;
		}
		echo '<meta name="twitter:image" content="' . esc_url( $image ) . '">' . "\n";
		foreach ( array( 'google_verify' => 'google-site-verification', 'bing_verify' => 'msvalidate.01' ) as $key => $name ) {
			$code = self::clean_code( zp_get( $key ) );
			if ( '' !== $code ) {
				echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $code ) . '">' . "\n";
			}
		}

		// What the site is, in the form search engines read.
		$home = home_url( '/' );
		if ( '/' === $path ) {
			// The brand is one word, but people also type it as two: both are given as its names.
			$spaced = trim( preg_replace( '/(?<=[a-z])(?=[A-Z])/', ' ', $brand ) );
			$names  = array_values( array_unique( array_filter( array( $spaced !== $brand ? $spaced : '', (string) wp_parse_url( $home, PHP_URL_HOST ) ) ) ) );
			$org    = array(
				'@context'      => 'https://schema.org',
				'@type'         => 'Organization',
				'@id'           => $home . '#organization',
				'name'          => $brand,
				'alternateName' => $names,
				'url'           => $home,
				'logo'          => ZP_URL . 'assets/icons/icon-512.png',
				'description'   => $desc,
			);
			$mail = (string) zp_get( 'support_email' );
			if ( is_email( $mail ) ) {
				$org['contactPoint'] = array( '@type' => 'ContactPoint', 'contactType' => 'customer support', 'email' => $mail, 'availableLanguage' => 'English' );
			}
			$tg = trim( (string) zp_get( 'telegram' ) );
			if ( '' !== $tg ) {
				$org['sameAs'] = array( 0 === strpos( $tg, 'http' ) ? esc_url_raw( $tg ) : 'https://t.me/' . ltrim( $tg, '@' ) );
			}
			self::json( $org );
			self::json(
				array(
					'@context'      => 'https://schema.org',
					'@type'         => 'WebSite',
					'@id'           => $home . '#website',
					'name'          => $brand,
					'alternateName' => $names,
					'url'           => $home,
					'publisher'     => array( '@id' => $home . '#organization' ),
					'inLanguage'    => 'en',
				)
			);
		} else {
			$crumb = trim( str_replace( array( ' · ' . $brand, $brand . ' · ' ), '', $full_title ) );
			self::json(
				array(
					'@context'        => 'https://schema.org',
					'@type'           => 'BreadcrumbList',
					'itemListElement' => array(
						array( '@type' => 'ListItem', 'position' => 1, 'name' => $brand, 'item' => $home ),
						array( '@type' => 'ListItem', 'position' => 2, 'name' => $crumb, 'item' => $url ),
					),
				)
			);
		}
		if ( '/faq/' === trailingslashit( $path ) && function_exists( 'zp_faq_groups' ) ) {
			$items = array();
			foreach ( zp_faq_groups() as $group ) {
				foreach ( (array) $group as $q => $ans ) {
					if ( is_string( $q ) && is_string( $ans ) && count( $items ) < 40 ) {
						$items[] = array( '@type' => 'Question', 'name' => $q, 'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $ans ) ) );
					}
				}
			}
			if ( $items ) {
				self::json( array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $items ) );
			}
		}
	}
}
