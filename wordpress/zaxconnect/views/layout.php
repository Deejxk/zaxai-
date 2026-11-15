<?php
/**
 * Shared view helpers: page shell, icons, brand marks, theme toggle, mobile footer menu.
 * All helpers return HTML strings; dynamic values are escaped where they are printed.
 */
defined( 'ABSPATH' ) || exit;

function zxc_icons() {
	static $i = null;
	if ( null === $i ) {
		$i = array(
			'home'      => '<path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6h-6v6H4a1 1 0 0 1-1-1z"/>',
			'wifi'      => '<path d="M2.5 9a14 14 0 0 1 19 0"/><path d="M5.5 12.5a9.5 9.5 0 0 1 13 0"/><path d="M8.7 16a5 5 0 0 1 6.6 0"/><circle cx="12" cy="19.3" r="1.1" fill="currentColor"/>',
			'wifiAnim'  => '<path class="w3" d="M2.5 9a14 14 0 0 1 19 0"/><path class="w2" d="M5.5 12.5a9.5 9.5 0 0 1 13 0"/><path class="w1" d="M8.7 16a5 5 0 0 1 6.6 0"/><circle cx="12" cy="19.3" r="1.2" fill="currentColor"/>',
			'ticket'    => '<path d="M3 8a2 2 0 0 0 2-2h14a2 2 0 0 0 2 2v2a2 2 0 0 0 0 4v2a2 2 0 0 0-2 2H5a2 2 0 0 0-2-2v-2a2 2 0 0 0 0-4z"/><path d="M14 6v12" stroke-dasharray="2 2.5"/>',
			'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>',
			'clockAnim' => '<circle cx="12" cy="12" r="9"/><path class="w1" d="M12 7v5l3.2 2"/>',
			'help'      => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9.5a2.5 2.5 0 1 1 3.5 2.3c-.6.3-1 .9-1 1.6v.6M12 17h.01"/>',
			'bolt'      => '<path d="M13 3 5 13.5h6L10 21l8-10.5h-6z"/>',
			'data'      => '<ellipse cx="12" cy="6" rx="7" ry="3"/><path d="M5 6v6c0 1.7 3.1 3 7 3s7-1.3 7-3V6"/><path d="M5 12v6c0 1.7 3.1 3 7 3s7-1.3 7-3v-6"/>',
			'devices'   => '<rect x="3" y="5" width="13" height="10" rx="1.5"/><path d="M2 18h15"/><rect x="18" y="8" width="4" height="10" rx="1"/>',
			'phone'     => '<rect x="6" y="2.5" width="12" height="19" rx="3"/><path d="M11 18.5h2"/>',
			'call'      => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
			'whatsapp'  => '<path d="M4 20l1.3-4A8 8 0 1 1 8.4 19z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1a4 4 0 0 1-2-2l1-1-1-2z"/>',
			'headset'   => '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/><path d="M19 19c0 1.5-2 2.5-5 2.5"/>',
			'shield'    => '<path d="M12 3 4.5 6v5.5c0 4.6 3.2 8.3 7.5 9.5 4.3-1.2 7.5-4.9 7.5-9.5V6z"/><path d="m9 12 2 2 4-4"/>',
			'check'     => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
			'copy'      => '<rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/>',
			'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
			'back'      => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
			'chevron'   => '<path d="m9 6 6 6-6 6"/>',
			'chevDown'  => '<path d="m6 9 6 6 6-6"/>',
			'alert'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/>',
			'search'    => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.5 4.5"/>',
			'sun'       => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2.5v2M12 19.5v2M2.5 12h2M19.5 12h2M5.3 5.3l1.4 1.4M17.3 17.3l1.4 1.4M5.3 18.7l1.4-1.4M17.3 6.7l1.4-1.4"/>',
			'moon'      => '<path d="M20.5 14.2A8.5 8.5 0 0 1 9.8 3.5a8.5 8.5 0 1 0 10.7 10.7z"/>',
			'grid'      => '<rect x="4" y="4" width="7" height="7" rx="1.5"/><rect x="13" y="4" width="7" height="7" rx="1.5"/><rect x="4" y="13" width="7" height="7" rx="1.5"/><rect x="13" y="13" width="7" height="7" rx="1.5"/>',
			'card'      => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/>',
			'bank'      => '<path d="M3 10 12 4l9 6M5 10v8M19 10v8M9.5 10v8M14.5 10v8M3 20h18"/>',
			'ussd'      => '<rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9.5 7.5h.01M12 7.5h.01M14.5 7.5h.01M9.5 10.5h.01M12 10.5h.01M14.5 10.5h.01M9.5 13.5h.01M12 13.5h.01M14.5 13.5h.01M11 18h2"/>',
			'users'     => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6.5 6.5 0 0 1 3.5 6"/>',
			'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
			'router'    => '<rect x="3" y="13" width="18" height="7" rx="2"/><path d="M7 16.5h.01M11 16.5h.01M15 13V9"/><path class="w1" d="M9.5 7a4 4 0 0 1 5.5 0"/><path class="w2" d="M7.5 4.5a7 7 0 0 1 9.5 0"/>',
			'star'      => '<path d="M12 3l2.6 5.3 5.9.9-4.3 4.1 1 5.8L12 16.4 6.8 19.1l1-5.8L3.5 9.2l5.9-.9z"/>',
			'gear'      => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.8.3l-.1.1A2 2 0 1 1 4.3 17l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.6-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1A2 2 0 1 1 7 4.3l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1A2 2 0 1 1 19.7 7l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
			'logout'    => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/>',
			'plus'      => '<path d="M12 5v14M5 12h14"/>',
			'printer'   => '<path d="M7 9V3h10v6"/><rect x="3" y="9" width="18" height="8" rx="2"/><rect x="7" y="14" width="10" height="7"/>',
			'download'  => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
			'menu'      => '<path d="M4 7h16M4 12h16M4 17h16"/>',
			'more'      => '<circle cx="5" cy="12" r="1.3" fill="currentColor"/><circle cx="12" cy="12" r="1.3" fill="currentColor"/><circle cx="19" cy="12" r="1.3" fill="currentColor"/>',
			'trend'     => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
			'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
			'pin'       => '<path d="M12 21s-7-6.2-7-11.5a7 7 0 0 1 14 0C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
			'palette'   => '<path d="M12 3a9 9 0 0 0 0 18c1 0 1.7-.8 1.7-1.7 0-.5-.2-.9-.5-1.2-.3-.3-.5-.7-.5-1.2 0-.9.8-1.7 1.7-1.7H16a5 5 0 0 0 5-5c0-4-4-7.2-9-7.2z"/><circle cx="7.5" cy="11" r="1"/><circle cx="10" cy="7.5" r="1"/><circle cx="14.5" cy="7.5" r="1"/>',
			'tag'       => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.3"/>',
			'play'      => '<path d="M8 5.5v13l10.5-6.5z" fill="currentColor" stroke="none"/>',
			'up'        => '<path d="M12 19V5M5 12l7-7 7 7"/>',
			'external'  => '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
			'trash'     => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
			'login'     => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H4"/>',
			'building'  => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M9 7h.01M15 7h.01M9 11h.01M15 11h.01M9 15h.01M15 15h.01M10 21v-3h4v3"/>',
			'wallet'    => '<path d="M4 7a2 2 0 0 1 2-2h12v4"/><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M16 13.5h2"/>',
		);
	}
	return $i;
}

function zxc_icon( $name, $cls = '' ) {
	$icons = zxc_icons();
	return '<svg class="i ' . esc_attr( $cls ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $icons[ $name ] ?? '' ) . '</svg>';
}

function zxc_logo_mark() {
	static $n = 0;
	$id = 'zxg' . ( ++$n );
	return '<svg class="logo-mark" width="36" height="36" viewBox="0 0 48 48" aria-hidden="true"><defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#5B3DF5"/><stop offset="1" stop-color="#14C8E8"/></linearGradient></defs><rect width="48" height="48" rx="14" fill="url(#' . $id . ')"/><path d="M14 15.5h17.5L15.5 33H34" fill="none" stroke="#fff" stroke-width="4.2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="35" cy="14.5" r="3.2" fill="#fff"/></svg>';
}

function zxc_logo( $href = '' ) {
	$name = zxc_setting( 'platform_name' );
	$word = preg_match( '/^(.*?)(Connect)$/', $name, $m ) ? esc_html( $m[1] ) . '<b>' . esc_html( $m[2] ) . '</b>' : esc_html( $name );
	return '<a href="' . esc_url( $href ? $href : home_url( '/' ) ) . '" class="logo" aria-label="' . esc_attr( $name ) . ' home">' . zxc_logo_mark() . '<span class="logo-word">' . $word . '</span></a>';
}

/** A business's mark: uploaded logo or its initial on the brand colour. */
function zxc_brand_mark( $t, $extra = '' ) {
	if ( $t->logo_url ) {
		return '<span class="brand-mark" ' . $extra . '><img src="' . esc_url( $t->logo_url ) . '" alt=""></span>';
	}
	return '<span class="brand-mark" ' . $extra . '>' . esc_html( strtoupper( mb_substr( trim( $t->name ), 0, 1 ) ) ) . '</span>';
}

/** CSS variables that apply a business's brand colour. */
function zxc_brand_style( $t ) {
	$c = zxc_is_hex( $t->brand_color ) ? $t->brand_color : '#4F3BF0';
	return '--accent:' . $c . ';--accent-ink:' . zxc_accent_ink( $c ) . ';--accent-soft:color-mix(in srgb,' . $c . ' 14%,var(--surface));--accent-text:color-mix(in srgb,' . $c . ' 82%,var(--ink));';
}

function zxc_theme_toggle() {
	return '<button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="Toggle light and dark mode">' . zxc_icon( 'moon', 'moon' ) . zxc_icon( 'sun', 'sun' ) . '</button>';
}

/** Mobile footer menu. $items: [ [href, icon, label, active] ] */
function zxc_bottom_nav( array $items, $label = 'Main' ) {
	$h = '<nav class="bottom-nav" style="--n:' . count( $items ) . '" aria-label="' . esc_attr( $label ) . '">';
	foreach ( $items as $it ) {
		$active = ! empty( $it[3] );
		if ( 0 === strpos( $it[0], 'button:' ) ) {
			$h .= '<button type="button" ' . esc_attr( substr( $it[0], 7 ) ) . ' class="' . ( $active ? 'active' : '' ) . '"><span class="pill">' . zxc_icon( $it[1] ) . '</span><span>' . esc_html( $it[2] ) . '</span></button>';
		} else {
			$h .= '<a href="' . esc_url( $it[0] ) . '" class="' . ( $active ? 'active' : '' ) . '"' . ( $active ? ' aria-current="page"' : '' ) . '><span class="pill">' . zxc_icon( $it[1] ) . '</span><span>' . esc_html( $it[2] ) . '</span></a>';
		}
	}
	return $h . '</nav>';
}

function zxc_alert( $type, $text, $allow_html = false ) {
	if ( ! $text ) {
		return '';
	}
	$ic = 'ok' === $type ? 'check' : 'alert';
	return '<div class="alert alert-' . esc_attr( $type ) . '" role="' . ( 'bad' === $type ? 'alert' : 'status' ) . '">' . zxc_icon( $ic ) . '<div>' . ( $allow_html ? wp_kses( $text, array( 'a' => array( 'href' => array() ), 'b' => array(), 'code' => array() ) ) : esc_html( $text ) ) . '</div></div>';
}

function zxc_nonce_field( $action ) {
	return '<input type="hidden" name="_zxc" value="' . esc_attr( wp_create_nonce( $action ) ) . '">';
}

function zxc_asset( $path ) {
	return esc_url( ZXC_URL . 'assets/' . $path . '?v=' . ZXC_VERSION );
}

/** Full HTML document. $body is trusted HTML built by the views. */
function zxc_page( $title, $body, array $opts = array() ) {
	$opts    = wp_parse_args( $opts, array( 'body_class' => '', 'style' => '', 'description' => '', 'scripts' => array(), 'robots' => '' ) );
	$scripts = '';
	foreach ( $opts['scripts'] as $s ) {
		$scripts .= '<script src="' . zxc_asset( 'js/' . $s ) . '" defer></script>';
	}
	ob_start();
	?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0B1020">
<?php if ( $opts['description'] ) : ?><meta name="description" content="<?php echo esc_attr( $opts['description'] ); ?>"><?php endif; ?>
<?php if ( $opts['robots'] ) : ?><meta name="robots" content="<?php echo esc_attr( $opts['robots'] ); ?>"><?php endif; ?>
<title><?php echo esc_html( $title ); ?></title>
<script>(function(){try{var t=localStorage.getItem('zx-theme');if(t==='dark'||t==='light')document.documentElement.setAttribute('data-theme',t);}catch(e){}})();</script>
<link rel="icon" href="<?php echo zxc_asset( 'img/favicon.svg' ); // phpcs:ignore ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?php echo zxc_asset( 'css/app.css' ); // phpcs:ignore ?>">
</head>
<body class="<?php echo esc_attr( $opts['body_class'] ); ?>"<?php echo $opts['style'] ? ' style="' . esc_attr( $opts['style'] ) . '"' : ''; ?>>
<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<script src="<?php echo zxc_asset( 'js/ui.js' ); // phpcs:ignore ?>" defer></script>
<?php echo $scripts; // phpcs:ignore ?>
</body>
</html>
	<?php
	return ob_get_clean();
}
