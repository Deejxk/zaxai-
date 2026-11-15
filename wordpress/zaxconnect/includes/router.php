<?php
/**
 * MikroTik integration for shared hosting (no RADIUS needed).
 *
 * Every router runs a small script every 10 seconds that POSTs its token, its last
 * acknowledged voucher id and the list of online users to the sync endpoint. The
 * response is plain data lines (never code) that the router script interprets:
 *   P,<profile>,<rate-limit>,<shared-users>   create/update a hotspot user profile (one per plan)
 *   A,<code>,<profile>,<uptime>,<bytes>       add a hotspot user for a voucher
 *   R,<code>                                  remove a voucher's user and kick its session
 *   H,<hash>                                  remember the current plan-profile version
 *   K,<voucher id>                            acknowledge vouchers up to this id
 * Works behind CGNAT because the router starts every connection.
 */
defined( 'ABSPATH' ) || exit;

const ZXC_SYNC_BUDGET = 3600; // RouterOS v6 fetch output=user is limited to ~4 KB

function zxc_payment_hosts() {
	return array( 'checkout.flutterwave.com', 'checkout-v3-ui-prod.f4b-flutterwave.com', 'api.flutterwave.com', 'flutterwave.com', 'www.flutterwave.com', 'fonts.googleapis.com', 'fonts.gstatic.com' );
}

function zxc_ros_time( $min ) {
	$min = max( 1, (int) $min );
	$d   = intdiv( $min, 1440 );
	$h   = intdiv( $min % 1440, 60 );
	$m   = $min % 60;
	return ( $d ? $d . 'd' : '' ) . ( $h ? $h . 'h' : '' ) . ( $m ? $m . 'm' : '' );
}

function zxc_ros_rate( $plan ) {
	$k = function ( $mbps ) {
		return (float) $mbps > 0 ? round( (float) $mbps * 1000 ) . 'k' : '0';
	};
	return $k( $plan->upload_mbps ) . '/' . $k( $plan->download_mbps ); // rx/tx = client upload/download
}

function zxc_profiles_hash( $plans ) {
	$s = '';
	foreach ( $plans as $p ) {
		$s .= $p->id . ':' . zxc_ros_rate( $p ) . ':' . (int) $p->devices . ';';
	}
	return substr( md5( $s ), 0, 10 );
}

/** Escape a value for a double-quoted RouterOS string typed in the terminal. */
function zxc_ros_str( $v ) {
	return '"' . str_replace( array( '\\', '"', '$', '?' ), array( '\\\\', '\\"', '\\$', '\\?' ), (string) $v ) . '"';
}

function zxc_router_script( $t, $router ) {
	$host     = wp_parse_url( home_url(), PHP_URL_HOST );
	$sync_url = rest_url( 'zaxconnect/v1/sync' );
	$platform = zxc_setting( 'platform_name' );
	$lines    = array(
		"# {$platform} · {$t->name} · {$router->name}",
		'# MikroTik RouterOS v6.45+ / v7. Run AFTER "IP > Hotspot > Hotspot Setup". Safe to run again.',
		'',
		'# 1. Hotspot login uses local users that ZaxConnect creates for each voucher',
		'/ip hotspot profile set [find] use-radius=no login-by=http-pap,cookie,mac-cookie dns-name=' . zxc_ros_str( $t->hotspot_dns ? $t->hotspot_dns : 'login.wifi' ) . ' http-cookie-lifetime=1d',
		'',
		'# 2. Walled garden: pages customers can open BEFORE paying',
		'/ip hotspot walled-garden remove [find comment~"ZaxConnect"]',
		'/ip hotspot walled-garden add dst-host=' . zxc_ros_str( $host ) . ' comment="ZaxConnect portal"',
		'/ip hotspot walled-garden add dst-host=' . zxc_ros_str( '*.' . $host ) . ' comment="ZaxConnect portal"',
		'/ip hotspot walled-garden add dst-host="*flutterwave*" comment="ZaxConnect payments"',
		'/ip hotspot walled-garden ip remove [find comment~"ZaxConnect"]',
		'/ip hotspot walled-garden ip add action=accept dst-host=' . zxc_ros_str( $host ) . ' comment="ZaxConnect portal"',
	);
	foreach ( zxc_payment_hosts() as $h ) {
		$lines[] = '/ip hotspot walled-garden ip add action=accept dst-host=' . zxc_ros_str( $h ) . ' comment="ZaxConnect payments"';
	}
	$lines[] = '';
	$lines[] = '# 3. Sync script: fetches new vouchers every 10 seconds';
	$lines[] = '/system script remove [find name="zaxconnect-sync"]';
	$lines[] = '/system script add name="zaxconnect-sync" policy=ftp,read,write,policy,test source={';
	$lines[] = ':global zxAck';
	$lines[] = ':global zxProf';
	$lines[] = ':if ([:typeof $zxAck] = "nothing") do={ :set zxAck 0 }';
	$lines[] = ':if ([:typeof $zxProf] = "nothing") do={ :set zxProf "x" }';
	$lines[] = ':local rep ""';
	$lines[] = ':foreach a in=[/ip hotspot active find] do={';
	$lines[] = '  :local u [/ip hotspot active get $a user]';
	$lines[] = '  :local m [/ip hotspot active get $a mac-address]';
	$lines[] = '  :local b 0';
	$lines[] = '  :local uid [/ip hotspot user find name=$u]';
	$lines[] = '  :if ([:len $uid] > 0) do={ :set b ([/ip hotspot user get $uid bytes-in] + [/ip hotspot user get $uid bytes-out]) }';
	$lines[] = '  :if ([:len $rep] < 3000) do={ :set rep ($rep . $u . "~" . $m . "~" . $b . "!") }';
	$lines[] = '}';
	$lines[] = ':do {';
	$lines[] = '  :local r [/tool fetch url=' . zxc_ros_str( $sync_url ) . ' http-method=post http-data=("t=' . $router->token . '&a=" . $zxAck . "&p=" . $zxProf . "&o=" . $rep) output=user as-value]';
	$lines[] = '  :if (($r->"status") = "finished") do={';
	$lines[] = '    :local d ($r->"data")';
	$lines[] = '    :local n [:len $d]';
	$lines[] = '    :local pos 0';
	$lines[] = '    :while ($pos < $n) do={';
	$lines[] = '      :local e [:find $d "\n" $pos]';
	$lines[] = '      :if ([:typeof $e] = "nil") do={ :set e $n }';
	$lines[] = '      :local line [:pick $d $pos $e]';
	$lines[] = '      :set pos ($e + 1)';
	$lines[] = '      :if ([:len $line] > 2) do={';
	$lines[] = '        :local f [:toarray $line]';
	$lines[] = '        :local k [:tostr ($f->0)]';
	$lines[] = '        :local v [:tostr ($f->1)]';
	$lines[] = '        :if ($k = "P") do={';
	$lines[] = '          :if ([:len [/ip hotspot user profile find name=$v]] = 0) do={ /ip hotspot user profile add name=$v }';
	$lines[] = '          /ip hotspot user profile set [find name=$v] rate-limit=[:tostr ($f->2)] shared-users=[:tonum ($f->3)]';
	$lines[] = '        }';
	$lines[] = '        :if ($k = "A") do={';
	$lines[] = '          :if ([:len [/ip hotspot user find name=$v]] = 0) do={ /ip hotspot user add name=$v password=$v profile=[:tostr ($f->2)] limit-uptime=[:totime ($f->3)] limit-bytes-total=[:tonum ($f->4)] comment="zaxconnect" }';
	$lines[] = '        }';
	$lines[] = '        :if ($k = "R") do={';
	$lines[] = '          /ip hotspot active remove [find user=$v]';
	$lines[] = '          /ip hotspot user remove [find name=$v]';
	$lines[] = '        }';
	$lines[] = '        :if ($k = "K") do={ :set zxAck [:tonum $v] }';
	$lines[] = '        :if ($k = "H") do={ :set zxProf $v }';
	$lines[] = '      }';
	$lines[] = '    }';
	$lines[] = '  }';
	$lines[] = '} on-error={ :log warning "ZaxConnect sync failed" }';
	$lines[] = '}';
	$lines[] = '/system scheduler remove [find name="zaxconnect-sync"]';
	$lines[] = '/system scheduler add name="zaxconnect-sync" interval=10s on-event="zaxconnect-sync" policy=ftp,read,write,policy,test';
	$lines[] = '/system script run zaxconnect-sync';
	$lines[] = '';
	$lines[] = '# 4. Done. Upload login.html and alogin.html to the "hotspot" folder (WinBox > Files).';
	return implode( "\n", $lines );
}

function zxc_login_html( $t ) {
	$portal = zxc_portal_url( $t );
	$name   = esc_html( $t->name );
	$p      = esc_url( $portal );
	$js     = wp_json_encode( $portal );
	return <<<HTML
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="-1">
<title>{$name}</title>
<noscript><meta http-equiv="refresh" content="0; url={$p}/?mac=$(mac-esc)&amp;link-login-only=$(link-login-only-esc)&amp;dst=$(link-orig-esc)"></noscript>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0B1020;color:#fff;font-family:system-ui,sans-serif;text-align:center}.s{width:38px;height:38px;border-radius:50%;border:3px solid rgba(255,255,255,.18);border-top-color:#A79BFF;animation:r 1s linear infinite;margin:0 auto 14px}@keyframes r{to{transform:rotate(360deg)}}a{color:#C7BFFF}</style>
</head>
<body>
<div><div class="s"></div>Opening {$name}…<br><small><a id="go" href="{$p}">Tap here if nothing happens</a></small></div>
<script>
(function () {
  var p = { mac: "$(mac)", ip: "$(ip)", "link-login-only": "$(link-login-only)", dst: "$(link-orig)", error: "$(error)" };
  var qs = [];
  for (var k in p) if (p[k]) qs.push(encodeURIComponent(k) + "=" + encodeURIComponent(p[k]));
  var url = {$js} + "/?" + qs.join("&");
  document.getElementById("go").href = url;
  window.location.replace(url);
})();
</script>
</body>
</html>

HTML;
}

function zxc_alogin_html( $t ) {
	$name = esc_html( $t->name );
	$js   = wp_json_encode( zxc_portal_url( $t, 'connected' ) . '?code=' );
	$p    = esc_url( zxc_portal_url( $t, 'connected' ) );
	return <<<HTML
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Connected · {$name}</title>
<style>body{margin:0;min-height:100vh;display:grid;place-items:center;background:#0B1020;color:#fff;font-family:system-ui,sans-serif;text-align:center}a{color:#C7BFFF}</style>
</head>
<body>
<div>You're online!<br><small><a id="go" href="{$p}">Continue</a></small></div>
<script>
(function () {
  var url = {$js} + encodeURIComponent("$(username)");
  document.getElementById("go").href = url;
  setTimeout(function () { window.location.replace(url); }, 800);
})();
</script>
</body>
</html>

HTML;
}

/**
 * Handle one router check-in and return the response body (data lines).
 *
 * @param string $token  router token
 * @param int    $ack    highest voucher id the router has applied
 * @param string $prof   profile hash the router last applied
 * @param string $report online users: code~mac~bytes!code~mac~bytes!
 */
function zxc_router_sync( $token, $ack, $prof, $report ) {
	global $wpdb;
	$router = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . zxc_table( 'routers' ) . ' WHERE token = %s', $token ) );
	if ( ! $router ) {
		return null;
	}
	$now = time();
	$tid = (int) $router->tenant_id;
	$ack = max( 0, (int) $ack );
	$wpdb->update( zxc_table( 'routers' ), array( 'last_seen' => $now, 'ack_id' => max( (int) $router->ack_id, $ack ) ), array( 'id' => $router->id ) );
	zxc_expire_vouchers( $tid );

	$out    = array();
	$remove = array();

	// 1. Online users: activate vouchers on first login, record usage, kick ended ones.
	foreach ( array_filter( explode( '!', (string) $report ) ) as $entry ) {
		$parts = explode( '~', $entry );
		$code  = zxc_normalize_code( $parts[0] ?? '' );
		$mac   = strtoupper( substr( preg_replace( '/[^0-9A-Fa-f:]/', '', $parts[1] ?? '' ), 0, 17 ) );
		$bytes = max( 0, (int) ( $parts[2] ?? 0 ) );
		if ( ! $code ) {
			continue;
		}
		$v = zxc_voucher_by_code( $code, $tid );
		if ( ! $v ) {
			continue;
		}
		if ( 'unused' === $v->status ) {
			$wpdb->update(
				zxc_table( 'vouchers' ),
				array( 'status' => 'active', 'activated_at' => $now, 'expires_at' => $now + (int) $v->duration_min * 60, 'mac' => $mac ),
				array( 'id' => $v->id )
			);
			$v->status     = 'active';
			$v->expires_at = $now + (int) $v->duration_min * 60;
		}
		$wpdb->update( zxc_table( 'vouchers' ), array( 'bytes_used' => max( (int) $v->bytes_used, $bytes ), 'last_seen' => $now ), array( 'id' => $v->id ) );
		$wpdb->query(
			$wpdb->prepare(
				'INSERT INTO ' . zxc_table( 'online' ) . ' (tenant_id, router_id, code, mac, bytes, first_seen, seen_at) VALUES (%d, %d, %s, %s, %d, %d, %d)
				ON DUPLICATE KEY UPDATE bytes = VALUES(bytes), seen_at = VALUES(seen_at)',
				$tid, $router->id, $code, $mac, $bytes, $now, $now
			)
		);
		if ( in_array( $v->status, array( 'expired', 'disabled' ), true ) || ( $v->expires_at && $v->expires_at <= $now ) ) {
			$remove[ $code ] = true;
		}
	}

	// 2. Plan profiles (only when plans changed).
	$plans = zxc_plans( $tid );
	$hash  = zxc_profiles_hash( $plans );
	if ( $hash !== $prof ) {
		foreach ( $plans as $p ) {
			$out[] = 'P,zx-p' . $p->id . ',' . zxc_ros_rate( $p ) . ',' . max( 1, (int) $p->devices );
		}
		$out[] = 'H,' . $hash;
	}

	// 3. Removals: vouchers that ended in the last 15 minutes, plus online ones that must go.
	$ended = $wpdb->get_col( $wpdb->prepare( 'SELECT code FROM ' . zxc_table( 'vouchers' ) . " WHERE tenant_id = %d AND status IN ('expired','disabled') AND ended_at >= %d ORDER BY ended_at DESC LIMIT 40", $tid, $now - 900 ) );
	foreach ( $ended as $c ) {
		$remove[ $c ] = true;
	}
	foreach ( array_keys( $remove ) as $c ) {
		$out[] = 'R,' . $c;
	}

	// 4. Re-enabled vouchers (ended_at holds -timestamp of re-enabling) are pushed again for 15 minutes.
	$size  = strlen( implode( "\n", $out ) ) + 40;
	$readd = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT v.code, v.plan_id, v.expires_at, p.duration_min, p.data_mb FROM ' . zxc_table( 'vouchers' ) . ' v JOIN ' . zxc_table( 'plans' ) . " p ON p.id = v.plan_id
			WHERE v.tenant_id = %d AND v.status IN ('unused','active') AND v.ended_at < 0 AND v.ended_at <= %d LIMIT 20",
			$tid, -( $now - 900 )
		)
	);
	foreach ( $readd as $r ) {
		if ( isset( $remove[ $r->code ] ) ) {
			continue;
		}
		$minutes = $r->expires_at ? max( 1, (int) ceil( ( $r->expires_at - $now ) / 60 ) ) : (int) $r->duration_min;
		$line    = 'A,' . $r->code . ',zx-p' . $r->plan_id . ',' . zxc_ros_time( $minutes ) . ',' . ( (int) $r->data_mb * 1048576 );
		if ( $size + strlen( $line ) + 1 <= ZXC_SYNC_BUDGET ) {
			$out[] = $line;
			$size += strlen( $line ) + 1;
		}
	}

	// 5. New vouchers after the router's acknowledged id, within the size budget.
	$last_id = $ack;
	$rows    = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT v.id, v.code, v.plan_id, v.status, v.expires_at, p.duration_min, p.data_mb FROM ' . zxc_table( 'vouchers' ) . ' v
			JOIN ' . zxc_table( 'plans' ) . " p ON p.id = v.plan_id WHERE v.tenant_id = %d AND v.id > %d ORDER BY v.id LIMIT 300",
			$tid, $ack
		)
	);
	foreach ( $rows as $r ) {
		if ( in_array( $r->status, array( 'unused', 'active' ), true ) ) {
			$minutes = $r->expires_at ? max( 1, (int) ceil( ( $r->expires_at - $now ) / 60 ) ) : (int) $r->duration_min;
			$line    = 'A,' . $r->code . ',zx-p' . $r->plan_id . ',' . zxc_ros_time( $minutes ) . ',' . ( (int) $r->data_mb * 1048576 );
			if ( $size + strlen( $line ) + 1 > ZXC_SYNC_BUDGET ) {
				break;
			}
			$out[] = $line;
			$size += strlen( $line ) + 1;
		}
		$last_id = (int) $r->id;
	}
	$out[] = 'K,' . $last_id;
	return implode( "\n", $out ) . "\n";
}

/** Has any of the tenant's routers received this voucher yet? */
function zxc_voucher_on_router( $v ) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . zxc_table( 'routers' ) . ' WHERE tenant_id = %d AND ack_id >= %d', $v->tenant_id, $v->id ) );
}

/** Is any router of the tenant checking in? */
function zxc_router_online( $tenant_id ) {
	global $wpdb;
	return (bool) $wpdb->get_var( $wpdb->prepare( 'SELECT 1 FROM ' . zxc_table( 'routers' ) . ' WHERE tenant_id = %d AND last_seen >= %d', $tenant_id, time() - 120 ) );
}
