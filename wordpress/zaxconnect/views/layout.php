<?php
/**
 * Shared view helpers: page shell, icon set, brand marks, glass dock, panel switchers.
 * Helpers return HTML strings; dynamic values are escaped where printed.
 */
defined( 'ABSPATH' ) || exit;

function zxc_icons() {
	static $i = null;
	if ( null !== $i ) {
		return $i;
	}
	$i = array(
		'home'      => '<path d="M3 10.2 12 3l9 7.2V20a1 1 0 0 1-1 1h-5.5v-6h-5v6H4a1 1 0 0 1-1-1z"/>',
		'dashboard' => '<rect x="3" y="3" width="7.5" height="9" rx="2"/><rect x="13.5" y="3" width="7.5" height="5" rx="2"/><rect x="13.5" y="11" width="7.5" height="10" rx="2"/><rect x="3" y="15" width="7.5" height="6" rx="2"/>',
		'wifi'      => '<path d="M2 8.8a15 15 0 0 1 20 0"/><path d="M5 12.3a10 10 0 0 1 14 0"/><path d="M8.5 15.8a5 5 0 0 1 7 0"/><circle cx="12" cy="19.3" r="1" fill="currentColor"/>',
		'wifiAnim'  => '<path class="w3" d="M2 8.8a15 15 0 0 1 20 0"/><path class="w2" d="M5 12.3a10 10 0 0 1 14 0"/><path class="w1" d="M8.5 15.8a5 5 0 0 1 7 0"/><circle cx="12" cy="19.3" r="1.1" fill="currentColor"/>',
		'ticket'    => '<path d="M3 9a3 3 0 0 0 0 6v2a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-2a3 3 0 0 0 0-6V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2z"/><path d="M13 5v2M13 11v2M13 17v2"/>',
		'clock'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'clockAnim' => '<circle cx="12" cy="12" r="9"/><path class="w1" d="M12 7v5l3 2"/>',
		'help'      => '<circle cx="12" cy="12" r="9"/><path d="M9.2 9.3a2.9 2.9 0 0 1 5.6 1c0 1.9-2.8 2.4-2.8 4"/><path d="M12 17.2h.01"/>',
		'zap'       => '<path d="M13 2 4.5 13.5H12L11 22l8.5-11.5H12z"/>',
		'data'      => '<ellipse cx="12" cy="5.5" rx="8" ry="3"/><path d="M4 5.5v6.5c0 1.7 3.6 3 8 3s8-1.3 8-3V5.5"/><path d="M4 12v6.5c0 1.7 3.6 3 8 3s8-1.3 8-3V12"/>',
		'devices'   => '<rect x="2.5" y="4" width="14" height="10" rx="1.5"/><path d="M6 18h7M9.5 14v4"/><rect x="18" y="8" width="4" height="12" rx="1"/>',
		'phone'     => '<rect x="6" y="2" width="12" height="20" rx="3"/><path d="M11 18h2"/>',
		'call'      => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2z"/>',
		'whatsapp'  => '<path d="M3 21l1.6-4.8A9 9 0 1 1 8 19.6z"/><path d="M9 9.6c0 2.8 2.4 5.2 5.2 5.2l1.2-1.5-2.1-1-1 .9a3.6 3.6 0 0 1-2-2l.9-1-1-2.1z"/>',
		'mail'      => '<rect x="2.5" y="4.5" width="19" height="15" rx="2.5"/><path d="m3 7 9 6 9-6"/>',
		'pin'       => '<path d="M12 21.5s-7.5-6.4-7.5-12a7.5 7.5 0 0 1 15 0c0 5.6-7.5 12-7.5 12z"/><circle cx="12" cy="9.5" r="2.6"/>',
		'shield'    => '<path d="M12 2.5 4 5.5v6c0 5 3.4 8.9 8 10 4.6-1.1 8-5 8-10v-6z"/><path d="m8.8 12 2.2 2.2 4.4-4.4"/>',
		'lock'      => '<rect x="4" y="10.5" width="16" height="11" rx="2.5"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
		'check'     => '<path d="m4.5 12.5 4.8 4.8L19.5 7"/>',
		'checkCirc' => '<circle cx="12" cy="12" r="9"/><path d="m8.2 12.2 2.6 2.6 5-5.2"/>',
		'x'         => '<path d="M18 6 6 18M6 6l12 12"/>',
		'plus'      => '<path d="M12 5v14M5 12h14"/>',
		'copy'      => '<rect x="8" y="8" width="13" height="13" rx="2.5"/><path d="M16 8V5.5A2.5 2.5 0 0 0 13.5 3h-8A2.5 2.5 0 0 0 3 5.5v8A2.5 2.5 0 0 0 5.5 16H8"/>',
		'arrow'     => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'back'      => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
		'chevron'   => '<path d="m9 6 6 6-6 6"/>',
		'chevDown'  => '<path d="m6 9 6 6 6-6"/>',
		'external'  => '<path d="M14 4h6v6M20 4l-9 9"/><path d="M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
		'switch'    => '<path d="M16 3h5v5M21 3l-7 7"/><path d="M8 21H3v-5M3 21l7-7"/>',
		'alert'     => '<circle cx="12" cy="12" r="9"/><path d="M12 7.5v5.5M12 16.5v.01"/>',
		'search'    => '<circle cx="11" cy="11" r="7"/><path d="m20.5 20.5-4.5-4.5"/>',
		'sun'       => '<circle cx="12" cy="12" r="4.2"/><path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
		'moon'      => '<path d="M20.6 14.3A8.6 8.6 0 0 1 9.7 3.4a8.6 8.6 0 1 0 10.9 10.9z"/>',
		'card'      => '<rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6.5 15h4"/>',
		'bank'      => '<path d="M3 10 12 4l9 6"/><path d="M5 10v8M19 10v8M9.5 10v8M14.5 10v8M3 20.5h18"/>',
		'ussd'      => '<rect x="5.5" y="2.5" width="13" height="19" rx="2.5"/><path d="M9 7h.01M12 7h.01M15 7h.01M9 10.5h.01M12 10.5h.01M15 10.5h.01M9 14h.01M12 14h.01M15 14h.01M10.5 18h3"/>',
		'users'     => '<circle cx="9" cy="8" r="3.6"/><path d="M2.5 20.5a6.5 6.5 0 0 1 13 0"/><path d="M16 4.6a3.6 3.6 0 0 1 0 6.8M18.5 14.2a6.5 6.5 0 0 1 3 6.3"/>',
		'user'      => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
		'userPlus'  => '<circle cx="9.5" cy="8" r="4"/><path d="M2 21a7.5 7.5 0 0 1 15 0M19 8v6M16 11h6"/>',
		'router'    => '<rect x="2.5" y="13" width="19" height="7.5" rx="2.5"/><path d="M6.5 16.8h.01M10 16.8h.01M16.5 13V9.5"/><path class="w1" d="M13.8 7.4a4 4 0 0 1 5.4 0"/><path class="w2" d="M11.8 4.8a7 7 0 0 1 9.4 0"/>',
		'star'      => '<path d="m12 2.8 2.8 5.8 6.3.9-4.6 4.4 1.1 6.3L12 17.2 6.4 20.2l1.1-6.3L2.9 9.5l6.3-.9z"/>',
		'sparkles'  => '<path d="M12 3l1.8 4.7L18.5 9.5l-4.7 1.8L12 16l-1.8-4.7L5.5 9.5l4.7-1.8z"/><path d="M19 15l.8 2 2 .8-2 .8-.8 2-.8-2-2-.8 2-.8z"/>',
		'gear'      => '<path d="M12.2 2h-.4a2 2 0 0 0-2 2v.2a2 2 0 0 1-1 1.7l-.4.3a2 2 0 0 1-2 0l-.2-.1a2 2 0 0 0-2.7.7l-.2.4a2 2 0 0 0 .7 2.7l.2.1a2 2 0 0 1 1 1.7v.5a2 2 0 0 1-1 1.7l-.2.1a2 2 0 0 0-.7 2.7l.2.4a2 2 0 0 0 2.7.7l.2-.1a2 2 0 0 1 2 0l.4.3a2 2 0 0 1 1 1.7v.2a2 2 0 0 0 2 2h.4a2 2 0 0 0 2-2v-.2a2 2 0 0 1 1-1.7l.4-.3a2 2 0 0 1 2 0l.2.1a2 2 0 0 0 2.7-.7l.2-.4a2 2 0 0 0-.7-2.7l-.2-.1a2 2 0 0 1-1-1.7v-.5a2 2 0 0 1 1-1.7l.2-.1a2 2 0 0 0 .7-2.7l-.2-.4a2 2 0 0 0-2.7-.7l-.2.1a2 2 0 0 1-2 0l-.4-.3a2 2 0 0 1-1-1.7V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
		'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
		'login'     => '<path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/>',
		'printer'   => '<path d="M6 9V2.5h12V9"/><rect x="2.5" y="9" width="19" height="8.5" rx="2"/><rect x="6" y="14" width="12" height="7.5" rx="1"/>',
		'download'  => '<path d="M12 3.5v12M7 10.5l5 5 5-5M4 20.5h16"/>',
		'menu'      => '<path d="M4 6.5h16M4 12h16M4 17.5h10"/>',
		'more'      => '<circle cx="5" cy="12" r="1.4" fill="currentColor"/><circle cx="12" cy="12" r="1.4" fill="currentColor"/><circle cx="19" cy="12" r="1.4" fill="currentColor"/>',
		'trend'     => '<path d="m3 17 6-6 4 4 8-8"/><path d="M15 7h6v6"/>',
		'chart'     => '<path d="M3 3v18h18"/><rect x="7" y="12" width="3" height="5" rx="1"/><rect x="12" y="8" width="3" height="9" rx="1"/><rect x="17" y="5" width="3" height="12" rx="1"/>',
		'bell'      => '<path d="M6 8.5a6 6 0 0 1 12 0c0 6.5 2.5 8.5 2.5 8.5h-17S6 15 6 8.5"/><path d="M10.2 20.5a2 2 0 0 0 3.6 0"/>',
		'inbox'     => '<path d="M3 13h5l1.5 3h5L16 13h5"/><path d="M5.5 5h13L21 13v5.5a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V13z"/>',
		'message'   => '<path d="M21 15a2 2 0 0 1-2 2H7.5L3 21V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
		'palette'   => '<path d="M12 2.5a9.5 9.5 0 0 0 0 19c1.1 0 1.8-.8 1.8-1.8 0-.5-.2-.9-.5-1.3-.3-.3-.5-.7-.5-1.2 0-1 .8-1.8 1.8-1.8H17a4.5 4.5 0 0 0 4.5-4.5c0-4.6-4.3-8.4-9.5-8.4z"/><circle cx="7.5" cy="11" r="1.2"/><circle cx="10.5" cy="7" r="1.2"/><circle cx="15" cy="7.5" r="1.2"/>',
		'tag'       => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2.5 12.5V2.5h10l8.1 8.1a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.4"/>',
		'wallet'    => '<path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-3"/><path d="M21 9.5h-5a2.5 2.5 0 0 0 0 5h5z"/>',
		'building'  => '<rect x="4" y="2.5" width="16" height="19" rx="2"/><path d="M9 7h.01M15 7h.01M9 11h.01M15 11h.01M9 15h.01M15 15h.01M10 21.5V18h4v3.5"/>',
		'globe'     => '<circle cx="12" cy="12" r="9.5"/><path d="M2.5 12h19M12 2.5a14.5 14.5 0 0 1 0 19M12 2.5a14.5 14.5 0 0 0 0 19"/>',
		'book'      => '<path d="M4 19.5V5a2.5 2.5 0 0 1 2.5-2.5H20v17H6.5A2.5 2.5 0 0 0 4 22"/><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>',
		'file'      => '<path d="M14 2.5H6a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8.5z"/><path d="M14 2.5v6h6M8 13h8M8 17h5"/>',
		'receipt'   => '<path d="M5 2.5v19l2.3-1.5 2.4 1.5 2.3-1.5 2.3 1.5 2.4-1.5 2.3 1.5v-19l-2.3 1.5-2.4-1.5-2.3 1.5-2.3-1.5-2.4 1.5z"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/>',
		'trash'     => '<path d="M3.5 6.5h17M9 6.5V4h6v2.5M6 6.5l1 14h10l1-14M10 11v6M14 11v6"/>',
		'play'      => '<path d="M7 4.5v15l12.5-7.5z" fill="currentColor" stroke="none"/>',
		'heart'     => '<path d="M20.4 4.6a5.4 5.4 0 0 0-7.7 0L12 5.3l-.7-.7a5.4 5.4 0 0 0-7.7 7.7l8.4 8.4 8.4-8.4a5.4 5.4 0 0 0 0-7.7z"/>',
		'target'    => '<circle cx="12" cy="12" r="9.5"/><circle cx="12" cy="12" r="5.5"/><circle cx="12" cy="12" r="1.5"/>',
		'cpu'       => '<rect x="5" y="5" width="14" height="14" rx="2"/><rect x="9" y="9" width="6" height="6" rx="1"/><path d="M9 2v3M15 2v3M9 19v3M15 19v3M2 9h3M2 15h3M19 9h3M19 15h3"/>',
		'cable'     => '<path d="M4 9a2 2 0 0 1-2-2V5h6v2a2 2 0 0 1-2 2zM3 5V3M7 5V3M5 9v3a4 4 0 0 0 4 4h6a4 4 0 0 0 4-4V9M19 9a2 2 0 0 1-2-2V5h6v2a2 2 0 0 1-2 2zM18 5V3M22 5V3"/>',
		'refresh'   => '<path d="M21 12a9 9 0 1 1-2.6-6.4L21 8"/><path d="M21 3v5h-5"/>',
		'eye'       => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
		'filter'    => '<path d="M3 4.5h18l-7 8.5v6l-4 2v-8z"/>',
		'percent'   => '<path d="M19 5 5 19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
		'layers'    => '<path d="m12 2.5 9.5 5L12 12.5 2.5 7.5z"/><path d="m2.5 12 9.5 5 9.5-5M2.5 16.5l9.5 5 9.5-5"/>',
	);
	return $i;
}

function zxc_icon( $name, $cls = '' ) {
	$icons = zxc_icons();
	return '<svg class="i ' . esc_attr( $cls ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ( $icons[ $name ] ?? '' ) . '</svg>';
}

function zxc_logo_mark() {
	static $n = 0;
	$id = 'zxg' . ( ++$n );
	return '<svg class="logo-mark" viewBox="0 0 48 48" aria-hidden="true"><defs><linearGradient id="' . $id . '" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF4FD8"/><stop offset=".5" stop-color="#A855F7"/><stop offset="1" stop-color="#22D3EE"/></linearGradient></defs><rect width="48" height="48" rx="14" fill="url(#' . $id . ')"/><rect x="3" y="3" width="42" height="42" rx="11.5" fill="#1A1C4A"/><path d="M15 15.5h17.5L15.5 32.5H33" fill="none" stroke="url(#' . $id . ')" stroke-width="4.6" stroke-linecap="round" stroke-linejoin="round"/><circle cx="35.5" cy="13.5" r="2.8" fill="#22D3EE"/></svg>';
}

function zxc_logo( $href = '' ) {
	$name = zxc_setting( 'platform_name' );
	$word = preg_match( '/^(.*?)(Connect)$/', $name, $m ) ? esc_html( $m[1] ) . '<b>' . esc_html( $m[2] ) . '</b>' : esc_html( $name );
	return '<a href="' . esc_url( $href ? $href : home_url( '/' ) ) . '" class="logo" aria-label="' . esc_attr( $name ) . ' home">' . zxc_logo_mark() . '<span class="logo-word">' . $word . '</span></a>';
}

function zxc_brand_mark( $t, $extra = '' ) {
	if ( $t->logo_url ) {
		return '<span class="brand-mark" ' . $extra . '><img src="' . esc_url( $t->logo_url ) . '" alt=""></span>';
	}
	return '<span class="brand-mark" ' . $extra . '>' . esc_html( strtoupper( mb_substr( trim( $t->name ), 0, 1 ) ) ) . '</span>';
}

/** CSS variables that apply a business's brand colour. */
function zxc_brand_style( $t ) {
	$c = zxc_is_hex( $t->brand_color ) ? $t->brand_color : '#5B5BF6';
	return '--accent:' . $c . ';--accent-2:color-mix(in oklab,' . $c . ' 72%,#1e1b4b);--accent-ink:' . zxc_accent_ink( $c ) . ';--accent-soft:color-mix(in srgb,' . $c . ' 15%,transparent);--accent-text:color-mix(in oklab,' . $c . ' 78%,var(--ink));--au1:color-mix(in srgb,' . $c . ' 34%,transparent);--d-accent:' . $c . ';';
}

function zxc_theme_toggle() {
	return '<button type="button" class="icon-btn theme-toggle" data-theme-toggle aria-label="Switch light or dark mode" data-tip="Light / dark">' . zxc_icon( 'moon', 'moon' ) . zxc_icon( 'sun', 'sun' ) . '</button>';
}

/**
 * One-tap switch between public pages and the panels (same tab).
 *
 * @param string $context 'site' | 'portal' | 'dashboard' | 'admin'
 * @param object $tenant  current business (portal/dashboard)
 */
function zxc_switch_buttons( $context, $tenant = null ) {
	if ( ! is_user_logged_in() ) {
		return '';
	}
	$user  = wp_get_current_user();
	$own   = zxc_tenant_for_user( $user->ID );
	$admin = zxc_is_platform_admin( $user );
	$out   = '';
	$btn   = function ( $href, $icon, $label ) {
		return '<a class="icon-btn" href="' . esc_url( $href ) . '" aria-label="' . esc_attr( $label ) . '" data-tip="' . esc_attr( $label ) . '">' . zxc_icon( $icon ) . '</a>';
	};
	if ( 'site' === $context ) {
		if ( $admin ) {
			$out .= $btn( home_url( '/zx-admin' ), 'dashboard', 'Admin panel' );
		}
		if ( $own ) {
			$out .= $btn( home_url( '/dashboard' ), 'wifi', 'My hotspot dashboard' );
		}
	} elseif ( 'portal' === $context ) {
		if ( $own && $tenant && (int) $own->id === (int) $tenant->id ) {
			$out .= $btn( home_url( '/dashboard' ), 'dashboard', 'Owner dashboard' );
		} elseif ( $admin && $tenant ) {
			$out .= $btn( home_url( '/zx-admin/businesses/' . $tenant->id ), 'dashboard', 'Admin panel' );
		}
	} elseif ( 'dashboard' === $context ) {
		if ( $tenant ) {
			$out .= $btn( zxc_portal_url( $tenant ), 'globe', 'View my portal' );
		}
		if ( $admin ) {
			$out .= $btn( home_url( '/zx-admin' ), 'shield', 'Admin panel' );
		}
	} elseif ( 'admin' === $context ) {
		$out .= $btn( home_url( '/' ), 'globe', 'View website' );
		if ( $own ) {
			$out .= $btn( home_url( '/dashboard' ), 'wifi', 'My hotspot dashboard' );
		}
	}
	return $out;
}

/**
 * Glass mobile dock. $items: [ href|'button:attr', icon, label, active, fab ]
 */
function zxc_dock( array $items, $label = 'Main' ) {
	$h = '<nav class="dock" style="--n:' . count( $items ) . '" aria-label="' . esc_attr( $label ) . '">';
	foreach ( $items as $it ) {
		$cls = trim( ( ! empty( $it[3] ) ? 'on ' : '' ) . ( ! empty( $it[4] ) ? 'fab' : '' ) );
		$in  = '<span class="di">' . zxc_icon( $it[1] ) . '</span><span>' . esc_html( $it[2] ) . '</span>';
		if ( 0 === strpos( $it[0], 'button:' ) ) {
			$h .= '<button type="button" ' . esc_attr( substr( $it[0], 7 ) ) . ' class="' . esc_attr( $cls ) . '">' . $in . '</button>';
		} else {
			$h .= '<a href="' . esc_url( $it[0] ) . '" class="' . esc_attr( $cls ) . '"' . ( ! empty( $it[3] ) ? ' aria-current="page"' : '' ) . '>' . $in . '</a>';
		}
	}
	return $h . '</nav>';
}

function zxc_alert( $type, $text, $allow_html = false ) {
	if ( ! $text ) {
		return '';
	}
	$ic = 'ok' === $type ? 'checkCirc' : 'alert';
	return '<div class="alert alert-' . esc_attr( $type ) . '" role="' . ( 'bad' === $type ? 'alert' : 'status' ) . '">' . zxc_icon( $ic ) . '<div>' . ( $allow_html ? wp_kses( $text, array( 'a' => array( 'href' => array() ), 'b' => array(), 'code' => array() ) ) : esc_html( $text ) ) . '</div></div>';
}

function zxc_toast( $flash ) {
	if ( ! $flash ) {
		return '';
	}
	$ic = 'ok' === $flash['type'] ? 'checkCirc' : 'alert';
	return '<div class="toast ' . esc_attr( $flash['type'] ) . '" role="status" data-toast>' . zxc_icon( $ic ) . '<span>' . esc_html( $flash['text'] ) . '</span></div>';
}

function zxc_nonce_field( $action ) {
	return '<input type="hidden" name="_zxc" value="' . esc_attr( wp_create_nonce( $action ) ) . '">';
}

function zxc_asset( $path ) {
	return esc_url( ZXC_URL . 'assets/' . $path . '?v=' . ZXC_VERSION );
}

function zxc_page( $title, $body, array $opts = array() ) {
	$opts    = wp_parse_args( $opts, array( 'body_class' => '', 'style' => '', 'description' => '', 'scripts' => array(), 'robots' => '', 'aurora' => true, 'app' => null, 'splash' => null ) );
	$app     = is_array( $opts['app'] ) ? $opts['app'] : array();
	$app     = wp_parse_args( $app, array( 'slug' => '', 'name' => zxc_setting( 'platform_name' ), 'sub' => 'Hotspot billing, made simple' ) );
	$splash  = null === $opts['splash'] ? $opts['aurora'] : $opts['splash'];
	$icon    = ZXC_URL . 'assets/img/icon-192.png';
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
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
<meta name="theme-color" content="#171A45">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $app['name'] ); ?>">
<meta name="format-detection" content="telephone=no">
<link rel="manifest" href="<?php echo esc_url( home_url( '/manifest.webmanifest' ) . ( $app['slug'] ? '?t=' . rawurlencode( $app['slug'] ) : '' ) ); ?>">
<link rel="apple-touch-icon" href="<?php echo esc_url( ZXC_URL . 'assets/img/apple-touch-icon.png' ); ?>">
<?php if ( $opts['description'] ) : ?><meta name="description" content="<?php echo esc_attr( $opts['description'] ); ?>"><?php endif; ?>
<?php if ( $opts['robots'] ) : ?><meta name="robots" content="<?php echo esc_attr( $opts['robots'] ); ?>"><?php endif; ?>
<title><?php echo esc_html( $title ); ?></title>
<script>(function(){var d=document.documentElement;try{var t=localStorage.getItem('zx-theme');if(t==='dark'||t==='light')d.setAttribute('data-theme',t);if(localStorage.getItem('zx-hidebal')==='1')d.classList.add('zx-hidebal');}catch(e){}<?php if ( $splash ) : ?>try{if(!sessionStorage.getItem('zx-splash'))d.classList.add('zx-splashing');}catch(e){d.classList.add('zx-splashing');}<?php endif; ?>window.ZX_ICON=<?php echo wp_json_encode( $icon ); ?>;})();</script>
<?php if ( $splash ) : ?><style><?php echo zxc_splash_css(); // phpcs:ignore ?></style><?php endif; ?>
<link rel="icon" href="<?php echo zxc_asset( 'img/favicon.svg' ); // phpcs:ignore ?>" type="image/svg+xml">
<link rel="preload" href="<?php echo esc_url( ZXC_URL . 'assets/fonts/inter.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
<link rel="stylesheet" href="<?php echo zxc_asset( 'css/app.css' ); // phpcs:ignore ?>">
</head>
<body class="<?php echo esc_attr( $opts['body_class'] ); ?>"<?php echo $opts['style'] ? ' style="' . esc_attr( $opts['style'] ) . '"' : ''; ?>>
<?php if ( $splash ) : ?><?php echo zxc_splash( $app['name'], $app['sub'] ); // phpcs:ignore ?><?php endif; ?>
<?php if ( $opts['aurora'] ) : ?><div class="aurora" aria-hidden="true"><i></i><i></i><i></i></div><?php endif; ?>
<?php echo $body; // phpcs:ignore WordPress.Security.EscapeOutput ?>
<script src="<?php echo zxc_asset( 'js/ui.js' ); // phpcs:ignore ?>" defer></script>
<?php echo $scripts; // phpcs:ignore ?>
</body>
</html>
	<?php
	return ob_get_clean();
}

/* Shared small components */

function zxc_kpi( $label, $value, $sub, $icon, $tone = '', $hero = false ) {
	return '<div class="kpi' . ( $hero ? ' is-hero' : '' ) . '"><div class="kpi-top"><span>' . esc_html( $label ) . '</span><span class="ico ' . esc_attr( $tone ) . '">' . zxc_icon( $icon ) . '</span></div><div class="kpi-val">' . esc_html( $value ) . '</div><div class="kpi-sub">' . wp_kses( $sub, array( 'b' => array() ) ) . '</div></div>';
}

function zxc_status_badge( $status ) {
	$map = array(
		'success'   => array( 'Paid', 'badge-ok' ),
		'pending'   => array( 'Pending', 'badge-warn' ),
		'failed'    => array( 'Failed', 'badge-bad' ),
		'unused'    => array( 'Unused', '' ),
		'active'    => array( 'Active', 'badge-ok' ),
		'expired'   => array( 'Expired', 'badge-bad' ),
		'disabled'  => array( 'Disabled', 'badge-bad' ),
		'trial'     => array( 'Free trial', 'badge-soft' ),
		'suspended' => array( 'Suspended', 'badge-bad' ),
	);
	$b   = $map[ $status ] ?? array( ucfirst( (string) $status ), '' );
	return '<span class="badge ' . esc_attr( $b[1] ) . '">' . esc_html( $b[0] ) . '</span>';
}

/** Bar chart of [label,total,count] rows. */
function zxc_bar_chart( array $series, $money = true ) {
	$max = 0;
	foreach ( $series as $d ) {
		$max = max( $max, $d['total'] );
	}
	$max  = $max > 0 ? $max : ( $money ? 1000 : 10 );
	$nice = $max;
	$p    = pow( 10, floor( log10( $max ) ) );
	foreach ( array( 1, 2, 2.5, 5, 10 ) as $m ) {
		if ( $m * $p >= $max ) {
			$nice = $m * $p;
			break;
		}
	}
	$fmt = function ( $v ) use ( $money ) {
		return $money ? zxc_money_short( $v ) : (string) round( $v );
	};
	$h   = '<div class="chart" role="img" aria-label="Bar chart"><div class="chart-y"><span>' . esc_html( $fmt( $nice ) ) . '</span><span>' . esc_html( $fmt( $nice / 2 ) ) . '</span><span>' . ( $money ? '₦0' : '0' ) . '</span></div><div class="chart-bars">';
	$n   = count( $series );
	foreach ( $series as $i => $d ) {
		$pct = $d['total'] > 0 ? max( 2, round( $d['total'] / $nice * 100, 1 ) ) : 0;
		$tip = $d['label'] . ': ' . ( $money ? zxc_money( $d['total'] ) : $d['total'] ) . ( isset( $d['count'] ) && $money ? ' · ' . $d['count'] . ' sale' . ( 1 === $d['count'] ? '' : 's' ) : '' );
		$h  .= '<div class="chart-col' . ( $n - 1 === $i ? ' now' : '' ) . '" tabindex="0"><span class="a-grow" style="height:' . esc_attr( $pct ) . '%;animation-delay:' . esc_attr( $i * 0.03 ) . 's"></span><span class="tip">' . esc_html( $tip ) . '</span></div>';
	}
	return $h . '</div></div><div class="chart-x"><span>' . esc_html( $series[0]['label'] ?? '' ) . '</span><span>' . esc_html( $series[ $n - 1 ]['label'] ?? '' ) . '</span></div>';
}

/** Animated 5-second launch screen, shown once per app session. */
function zxc_splash( $name, $sub ) {
	$word = preg_match( '/^(.*?)(Connect)$/', $name, $m ) ? esc_html( $m[1] ) . '<b>' . esc_html( $m[2] ) . '</b>' : esc_html( $name );
	$letters = '';
	return '<div id="zx-splash" aria-hidden="true">
  <div class="sp-glow g1"></div><div class="sp-glow g2"></div><div class="sp-grid"></div>
  <div class="sp-center">
    <div class="sp-orbit"><span class="sp-ring"></span><span class="sp-ring r2"></span><i class="sp-dot d1"></i><i class="sp-dot d2"></i><i class="sp-dot d3"></i>
      <svg class="sp-mark" viewBox="0 0 48 48"><defs><linearGradient id="zxsp" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#FF4FD8"/><stop offset=".5" stop-color="#A855F7"/><stop offset="1" stop-color="#22D3EE"/></linearGradient></defs>
        <rect class="sp-box" x="2" y="2" width="44" height="44" rx="13" fill="#1A1C4A" stroke="url(#zxsp)" stroke-width="2.6"/>
        <path class="sp-z" d="M15 15.5h17.5L15.5 32.5H33" fill="none" stroke="url(#zxsp)" stroke-width="4.6" stroke-linecap="round" stroke-linejoin="round"/>
        <circle class="sp-c" cx="35.5" cy="13.5" r="2.8" fill="#22D3EE"/>
        <g class="sp-wave" fill="none" stroke="#8EF3FF" stroke-width="1.6" stroke-linecap="round"><path d="M38 6.5a7 7 0 0 1 4 4"/><path d="M39 2.5a11 11 0 0 1 7 7"/></g>
      </svg>
    </div>
    <div class="sp-word">' . $word . '</div>
    <div class="sp-sub">' . esc_html( $sub ) . '</div>
  </div>
  <div class="sp-foot"><div class="sp-bar"><span></span></div><div class="sp-load">Starting secure session<span class="sp-dots"><i>.</i><i>.</i><i>.</i></span></div></div>
</div>
<script>(function(){var d=document.documentElement;if(!d.classList.contains("zx-splashing")){var e=document.getElementById("zx-splash");if(e)e.parentNode.removeChild(e);return;}try{sessionStorage.setItem("zx-splash","1");}catch(x){}setTimeout(function(){d.classList.add("zx-splash-out");},4550);setTimeout(function(){d.classList.remove("zx-splashing","zx-splash-out");var e=document.getElementById("zx-splash");if(e)e.parentNode.removeChild(e);},5100);})();</script>';
}

function zxc_splash_css() {
	return '#zx-splash{display:none}
html.zx-splashing{overflow:hidden}
html.zx-splashing #zx-splash{display:flex;position:fixed;inset:0;z-index:2147483000;flex-direction:column;align-items:center;justify-content:center;overflow:hidden;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,sans-serif;background:radial-gradient(900px 600px at 15% 0%,#3A2484 0%,transparent 60%),radial-gradient(800px 600px at 100% 100%,#0F4A63 0%,transparent 60%),linear-gradient(165deg,#15183F,#1B1F52 50%,#152A4C);transition:opacity .55s ease,transform .55s ease,filter .55s ease}
html.zx-splash-out #zx-splash{opacity:0;transform:scale(1.06);filter:blur(6px)}
.sp-grid{position:absolute;inset:0;background-image:linear-gradient(rgba(168,176,255,.07) 1px,transparent 1px),linear-gradient(90deg,rgba(168,176,255,.07) 1px,transparent 1px);background-size:44px 44px;mask-image:radial-gradient(circle at 50% 45%,#000 0,transparent 65%);-webkit-mask-image:radial-gradient(circle at 50% 45%,#000 0,transparent 65%);animation:spFade 1.2s ease both}
.sp-glow{position:absolute;width:60vmax;height:60vmax;border-radius:50%;filter:blur(60px);opacity:.55}
.sp-glow.g1{background:radial-gradient(circle,rgba(255,79,216,.45),transparent 60%);left:-25vmax;top:-25vmax;animation:spDrift 5s ease-in-out both}
.sp-glow.g2{background:radial-gradient(circle,rgba(34,211,238,.35),transparent 60%);right:-25vmax;bottom:-25vmax;animation:spDrift 5s ease-in-out reverse both}
.sp-center{position:relative;display:flex;flex-direction:column;align-items:center;text-align:center;padding:0 24px}
.sp-orbit{position:relative;width:180px;height:180px;display:grid;place-items:center}
.sp-ring{position:absolute;inset:18px;border-radius:50%;border:1.5px dashed rgba(142,243,255,.4);animation:spSpin 9s linear infinite,spPop .9s cubic-bezier(.2,1.3,.4,1) .2s both}
.sp-ring.r2{inset:0;border:1px solid rgba(255,255,255,.08);border-top-color:rgba(255,79,216,.8);border-right-color:rgba(34,211,238,.6);animation:spSpin 2.4s linear infinite,spPop .9s cubic-bezier(.2,1.3,.4,1) .35s both}
.sp-dot{position:absolute;width:8px;height:8px;border-radius:50%;background:#22D3EE;box-shadow:0 0 12px #22D3EE;top:50%;left:50%;margin:-4px;animation:spOrb 3.2s linear infinite,spFade .6s ease 1s both}
.sp-dot.d2{background:#FF4FD8;box-shadow:0 0 12px #FF4FD8;animation-delay:-1.07s,1.1s}.sp-dot.d3{background:#A855F7;box-shadow:0 0 12px #A855F7;width:6px;height:6px;animation-delay:-2.13s,1.2s}
.sp-mark{position:relative;width:96px;height:96px;filter:drop-shadow(0 0 18px rgba(217,70,239,.55)) drop-shadow(0 0 28px rgba(34,211,238,.35));animation:spPop .9s cubic-bezier(.2,1.4,.4,1) .1s both,spPulse 2.2s ease-in-out 1.6s infinite}
.sp-box{stroke-dasharray:180;stroke-dashoffset:180;animation:spDraw 1.1s cubic-bezier(.6,0,.2,1) .25s forwards}
.sp-z{stroke-dasharray:60;stroke-dashoffset:60;animation:spDraw .9s cubic-bezier(.6,0,.2,1) 1s forwards}
.sp-c{transform-origin:35.5px 13.5px;transform:scale(0);animation:spDotIn .5s cubic-bezier(.2,1.6,.4,1) 1.75s forwards}
.sp-wave path{opacity:0;animation:spWave 1.6s ease-in-out 2s infinite}.sp-wave path+path{animation-delay:2.25s}
.sp-word{margin-top:26px;font-family:Sora,Inter,sans-serif;font-weight:800;font-size:clamp(30px,8vw,44px);letter-spacing:-.045em;line-height:1;animation:spRise .8s cubic-bezier(.2,.8,.2,1) 1.9s both}
.sp-word b{background:linear-gradient(100deg,#FF4FD8,#A855F7 45%,#22D3EE);-webkit-background-clip:text;background-clip:text;color:transparent}
.sp-sub{margin-top:12px;font-size:14px;font-weight:500;letter-spacing:.02em;color:rgba(218,221,247,.75);animation:spRise .8s cubic-bezier(.2,.8,.2,1) 2.3s both}
.sp-foot{position:absolute;left:0;right:0;bottom:calc(48px + env(safe-area-inset-bottom));display:flex;flex-direction:column;align-items:center;gap:12px;animation:spFade .6s ease 2.6s both}
.sp-bar{width:min(220px,60vw);height:4px;border-radius:99px;background:rgba(168,176,255,.15);overflow:hidden}
.sp-bar span{display:block;height:100%;width:100%;border-radius:inherit;background:linear-gradient(90deg,#FF4FD8,#A855F7,#22D3EE);box-shadow:0 0 12px rgba(217,70,239,.8);transform-origin:left;transform:scaleX(0);animation:spLoad 4.3s cubic-bezier(.4,.1,.2,1) .3s forwards}
.sp-load{font-size:12px;font-weight:600;letter-spacing:.08em;text-transform:uppercase;color:rgba(169,174,218,.8)}
.sp-dots i{font-style:normal;animation:spBlink 1.2s infinite}.sp-dots i:nth-child(2){animation-delay:.2s}.sp-dots i:nth-child(3){animation-delay:.4s}
@keyframes spDraw{to{stroke-dashoffset:0}}
@keyframes spDotIn{to{transform:scale(1)}}
@keyframes spPop{0%{transform:scale(.4);opacity:0}60%{transform:scale(1.06);opacity:1}100%{transform:scale(1);opacity:1}}
@keyframes spPulse{0%,100%{filter:drop-shadow(0 0 18px rgba(217,70,239,.55)) drop-shadow(0 0 28px rgba(34,211,238,.35))}50%{filter:drop-shadow(0 0 28px rgba(217,70,239,.85)) drop-shadow(0 0 44px rgba(34,211,238,.6))}}
@keyframes spSpin{to{rotate:360deg}}
@keyframes spOrb{from{transform:rotate(0) translateX(90px)}to{transform:rotate(360deg) translateX(90px)}}
@keyframes spRise{from{opacity:0;transform:translateY(16px);filter:blur(6px)}to{opacity:1;transform:none;filter:none}}
@keyframes spFade{from{opacity:0}to{opacity:1}}
@keyframes spDrift{from{transform:scale(.8)}to{transform:scale(1.1) translate(4vmax,4vmax)}}
@keyframes spLoad{0%{transform:scaleX(0)}40%{transform:scaleX(.45)}70%{transform:scaleX(.72)}100%{transform:scaleX(1)}}
@keyframes spWave{0%,100%{opacity:0}50%{opacity:1}}
@keyframes spBlink{0%,100%{opacity:.2}50%{opacity:1}}';
}
