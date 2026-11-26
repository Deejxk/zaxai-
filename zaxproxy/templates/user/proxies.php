<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user */
$f_type  = sanitize_key( zp_query( 'type' ) );
$f_state = sanitize_key( zp_query( 'state' ) );
$f_q     = zp_query( 'q' );
$rows    = ZP_Orders::user_proxies( $user->ID, array( 'type' => $f_type, 'state' => in_array( $f_state, array( 'active', 'expired' ), true ) ? $f_state : '', 'q' => $f_q ) );
global $wpdb;
$pending    = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . zp_table( 'orders' ) . " WHERE user_id = %d AND kind = 'purchase' AND status IN ('processing','review')", $user->ID ) );
$export_url = add_query_arg( '_zpnonce', wp_create_nonce( 'zp_export' ), zp_url( 'export' ) );

// The whole collection, whatever the filters say: it feeds the header and the filter counts.
$tot = array( 'all' => 0, 'active' => 0, 'expiring' => 0, 'expired' => 0 );
$places = array();
$next   = 0;
foreach ( ( $f_type || $f_state || $f_q ) ? ZP_Orders::user_proxies( $user->ID ) : $rows as $one ) {
	$st = ZP_Orders::proxy_state( $one );
	$tot['all']++;
	$tot[ $st ]++;
	if ( 'expired' !== $st ) {
		$places[ $one->country ] = 1;
		$e = strtotime( $one->date_end . ' UTC' );
		$next = $next ? min( $next, $e ) : $e;
	}
}
$live     = $tot['active'] + $tot['expiring'];
$filtered = $f_q || $f_type || $f_state;
$chip     = function ( $state ) use ( $f_type, $f_q ) {
	return esc_url( add_query_arg( array_filter( array( 'state' => $state, 'type' => $f_type, 'q' => $f_q ) ), zp_url( 'proxies' ) ) );
};
$geo_budget = 4; // new place look-ups per page view; known ones are free
?>
<section class="zp-phero zp-anim">
	<div class="zp-phero__main">
		<span class="zp-rhero__status<?php echo $live ? '' : ' is-off'; ?>"><i></i><?php echo $live ? esc_html( $live . ( 1 === $live ? ' proxy online' : ' proxies online' ) ) : 'No active proxies'; ?></span>
		<h2>My proxies</h2>
		<p><?php echo $live ? 'Every proxy you own, with its details ready to copy. Connect with one tap in ZaxProxy Connect.' : 'Your proxies are delivered here the moment an order is ready.'; ?></p>
		<div class="zp-phero__stats">
			<div><strong data-zp-count="<?php echo (int) $live; ?>"><?php echo (int) $live; ?></strong><span>Active</span></div>
			<div><strong data-zp-count="<?php echo (int) $tot['expiring']; ?>"><?php echo (int) $tot['expiring']; ?></strong><span>Expiring soon</span></div>
			<div><strong data-zp-count="<?php echo count( $places ); ?>"><?php echo count( $places ); ?></strong><span><?php echo 1 === count( $places ) ? 'Country' : 'Countries'; ?></span></div>
			<div><strong><?php echo $next ? esc_html( zp_time_left( gmdate( 'Y-m-d H:i:s', $next ) ) ) : '—'; ?></strong><span>Next expiry</span></div>
		</div>
	</div>
	<div class="zp-phero__art" aria-hidden="true">
		<svg viewBox="0 0 300 220">
			<defs>
				<radialGradient id="zpp-glow" cx="50%" cy="50%" r="50%"><stop offset="0" stop-color="#fff" stop-opacity=".34"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></radialGradient>
				<linearGradient id="zpp-shield" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#fff"/><stop offset="1" stop-color="#dcd8ff"/></linearGradient>
			</defs>
			<circle cx="150" cy="110" r="104" fill="url(#zpp-glow)"/>
			<g class="zpp-orbits" fill="none" stroke="#fff" stroke-opacity=".34">
				<ellipse cx="150" cy="110" rx="128" ry="46"/>
				<ellipse cx="150" cy="110" rx="128" ry="46" transform="rotate(60 150 110)"/>
				<ellipse cx="150" cy="110" rx="128" ry="46" transform="rotate(120 150 110)"/>
			</g>
			<g class="zpp-links" stroke="#fff" stroke-opacity=".5" stroke-width="1.500" stroke-dasharray="3 6">
				<path d="M150 110 44 62"/><path d="M150 110 262 58"/><path d="M150 110 52 172"/><path d="M150 110 250 170"/>
			</g>
			<g class="zpp-node zpp-n1"><circle cx="44" cy="62" r="17" fill="#fff" fill-opacity=".16" stroke="#fff" stroke-opacity=".6"/><text x="44" y="68" text-anchor="middle" font-size="16">🇺🇸</text></g>
			<g class="zpp-node zpp-n2"><circle cx="262" cy="58" r="17" fill="#fff" fill-opacity=".16" stroke="#fff" stroke-opacity=".6"/><text x="262" y="64" text-anchor="middle" font-size="16">🇬🇧</text></g>
			<g class="zpp-node zpp-n3"><circle cx="52" cy="172" r="17" fill="#fff" fill-opacity=".16" stroke="#fff" stroke-opacity=".6"/><text x="52" y="178" text-anchor="middle" font-size="16">🇩🇪</text></g>
			<g class="zpp-node zpp-n4"><circle cx="250" cy="170" r="17" fill="#fff" fill-opacity=".16" stroke="#fff" stroke-opacity=".6"/><text x="250" y="176" text-anchor="middle" font-size="16">🇨🇦</text></g>
			<circle class="zpp-pulse zpp-p1" r="4" fill="#fff"/><circle class="zpp-pulse zpp-p2" r="4" fill="#fff"/><circle class="zpp-pulse zpp-p3" r="4" fill="#fff"/><circle class="zpp-pulse zpp-p4" r="4" fill="#fff"/>
			<g class="zpp-hub">
				<circle cx="150" cy="110" r="46" fill="#fff" fill-opacity=".14"/>
				<path d="M150 70 120 81v22.500c0 19.500 12.800 33.500 30 39.700 17.200-6.200 30-20.200 30-39.700V81L150 70Z" fill="url(#zpp-shield)"/>
				<path d="M161 90.500h-17.300l-2.200 16.400c2.700-2.500 6.100-3.600 9.900-3.600 7 0 12 4.700 12 11.100 0 6.600-5.500 11.600-12.900 11.600-5.100 0-9.100-2-11.600-5.200" fill="none" stroke="#4f46e5" stroke-width="6.400" stroke-linecap="round" stroke-linejoin="round"/>
			</g>
		</svg>
	</div>
</section>
<?php if ( $pending ) : ?>
	<div class="zp-alert zp-alert--info zp-anim"><span class="zp-spinner"></span><span><?php echo esc_html( sprintf( '%d order%s being set up. New proxies appear here automatically, usually within a few minutes.', $pending, 1 === $pending ? ' is' : 's are' ) ); ?></span><a class="zp-btn zp-btn--sm zp-btn--soft" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">Refresh</a></div>
<?php endif; ?>

<form class="zp-ptools zp-anim" style="--d:1" method="get" action="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">
	<?php if ( ! zp_pretty() ) : ?><input type="hidden" name="zp" value="<?php echo esc_attr( ZP_USER_BASE ); ?>"><input type="hidden" name="zpp" value="proxies"><?php endif; ?>
	<?php if ( $f_state ) : ?><input type="hidden" name="state" value="<?php echo esc_attr( $f_state ); ?>"><?php endif; ?>
	<div class="zp-input zp-input--search"><?php echo zp_icon( 'search', 18 ); // phpcs:ignore ?><input type="search" name="q" value="<?php echo esc_attr( $f_q ); ?>" placeholder="Search IP, label or country"></div>
	<select name="type" class="zp-select" onchange="this.form.submit()" aria-label="Proxy type">
		<option value="">All types</option>
		<?php foreach ( zp_types() as $slug => $t ) : ?>
			<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f_type, $slug ); ?>><?php echo esc_html( $t['label'] ); ?></option>
		<?php endforeach; ?>
	</select>
	<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?><span>Buy more</span></a>
	<nav class="zp-pchips" aria-label="Status">
		<a class="<?php echo '' === $f_state ? 'is-on' : ''; ?>" href="<?php echo $chip( '' ); // phpcs:ignore ?>">All<b><?php echo (int) $tot['all']; ?></b></a>
		<a class="<?php echo 'active' === $f_state ? 'is-on' : ''; ?>" href="<?php echo $chip( 'active' ); // phpcs:ignore ?>"><i class="is-ok"></i>Active<b><?php echo (int) $live; ?></b></a>
		<a class="<?php echo 'expired' === $f_state ? 'is-on' : ''; ?>" href="<?php echo $chip( 'expired' ); // phpcs:ignore ?>"><i class="is-bad"></i>Expired<b><?php echo (int) $tot['expired']; ?></b></a>
	</nav>
</form>

<?php if ( ! $rows ) : ?>
	<div class="zp-card zp-anim" style="--d:2">
		<?php echo zp_empty( 'empty-proxies', $f_q || $f_type || $f_state ? 'No proxies match your filters' : 'No proxies yet', $f_q || $f_type || $f_state ? 'Try clearing the search or filters.' : 'Buy a proxy and it will be delivered here with its credentials.', zp_url( 'buy' ), 'Buy proxies' ); // phpcs:ignore ?>
	</div>
	<?php
	return;
endif;
?>

<div class="zp-plinks zp-anim" style="--d:2">
	<?php if ( ZP_App::offered() ) : ?>
	<a class="zp-plink" href="<?php echo esc_url( ZP_App::download_url() ); ?>" target="_blank" rel="noopener"><span class="zp-stat__ico zp-tone-green"><?php echo zp_icon( 'phone', 18 ); // phpcs:ignore ?></span><div><strong>Android app</strong><span>Connect with one tap</span></div><b><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?></b></a>
	<?php endif; ?>
	<?php if ( ZP_App::offered( 'windows' ) ) : ?>
	<a class="zp-plink" href="<?php echo esc_url( ZP_App::download_url( 'windows' ) ); ?>" target="_blank" rel="noopener"><span class="zp-stat__ico zp-tone-cyan"><?php echo zp_icon( 'server', 18 ); // phpcs:ignore ?></span><div><strong>Windows app</strong><span>Connect with one click</span></div><b><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?></b></a>
	<?php endif; ?>
	<a class="zp-plink" href="<?php echo esc_url( zp_url( 'help' ) ); ?>#connect"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?></span><div><strong>How to connect</strong><span>Windows, Android and iPhone</span></div><b><?php echo zp_icon( 'arrow', 16 ); // phpcs:ignore ?></b></a>
</div>

<div class="zp-bulk zp-anim" style="--d:3" id="zp-bulk">
	<label class="zp-check"><input type="checkbox" id="zp-check-all"><span><b id="zp-sel-count">0</b> selected</span></label>
	<div class="zp-bulk__actions">
		<select class="zp-select zp-select--sm" id="zp-export-format" aria-label="Export format">
			<option value="ip:port:user:pass">ip:port:user:pass</option>
			<option value="user:pass@ip:port">user:pass@ip:port</option>
			<option value="url">protocol://user:pass@ip:port</option>
			<option value="ip:port">ip:port</option>
		</select>
		<select class="zp-select zp-select--sm" id="zp-export-proto" aria-label="Protocol"><option value="http">HTTP(S)</option><option value="socks5">SOCKS5</option></select>
		<button type="button" class="zp-btn zp-btn--soft zp-btn--sm" id="zp-copy-sel"><?php echo zp_icon( 'copy', 16 ); // phpcs:ignore ?>Copy</button>
		<a class="zp-btn zp-btn--soft zp-btn--sm" id="zp-export" data-base="<?php echo esc_url( $export_url ); ?>" href="<?php echo esc_url( $export_url ); ?>"><?php echo zp_icon( 'download', 16 ); // phpcs:ignore ?>Export</a>
		<button type="button" class="zp-btn zp-btn--primary zp-btn--sm" id="zp-extend-open" disabled><?php echo zp_icon( 'refresh', 16 ); // phpcs:ignore ?>Extend</button>
	</div>
</div>

<div class="zp-proxies">
	<?php
	$i = 0;
	foreach ( $rows as $p ) :
		$state = ZP_Orders::proxy_state( $p );
		$i++;
		$geo   = ZP_App::geo( $p->ip, $geo_budget );
		$where = implode( ', ', array_unique( array_filter( array( $geo[0], $geo[1], zp_country_name( $p->country ) ) ) ) );
		// How much of the paid period is left, for the bar.
		$t_end   = $p->date_end ? strtotime( $p->date_end . ' UTC' ) : 0;
		$t_start = ! empty( $p->date_start ) ? strtotime( $p->date_start . ' UTC' ) : 0;
		$span    = $t_start && $t_end > $t_start ? $t_end - $t_start : 30 * DAY_IN_SECONDS;
		$pct     = 'expired' === $state ? 0 : (int) max( 4, min( 100, round( ( $t_end - time() ) / $span * 100 ) ) );
		?>
		<article class="zp-proxy zp-proxy--<?php echo esc_attr( $state ); ?> zp-anim" style="--d:<?php echo (int) min( $i + 3, 12 ); ?>" data-id="<?php echo (int) $p->id; ?>" data-type="<?php echo esc_attr( $p->proxy_type ); ?>"
			data-ip="<?php echo esc_attr( $p->ip ); ?>" data-http="<?php echo esc_attr( $p->https_port ); ?>" data-socks="<?php echo esc_attr( $p->socks5_port ); ?>"
			data-login="<?php echo esc_attr( $p->login ); ?>" data-pass="<?php echo esc_attr( $p->password ); ?>">
			<header>
				<label class="zp-check"><input type="checkbox" class="zp-proxy__check" value="<?php echo (int) $p->id; ?>" <?php disabled( $state, 'expired' ); ?> aria-label="Select proxy"><span></span></label>
				<span class="zp-flag zp-pflag"><?php echo esc_html( zp_flag( $p->country ) ); ?><i></i></span>
				<div class="zp-proxy__id">
					<strong class="zp-mono"><?php echo esc_html( $p->ip ); ?></strong>
					<span><?php echo esc_html( $where ); ?></span>
				</div>
				<?php echo zp_badge( $state ); // phpcs:ignore ?>
			</header>
			<div class="zp-ptags">
				<span><?php echo esc_html( zp_type_label( $p->proxy_type ) ); ?></span>
				<?php if ( $p->mobile_operator ) : ?><span><?php echo esc_html( $p->mobile_operator ); ?></span><?php endif; ?>
				<?php if ( $p->note ) : ?><span class="is-label"><?php echo esc_html( $p->note ); ?></span><?php endif; ?>
			</div>
			<div class="zp-plife">
				<div class="zp-plife__top"><span>Time left</span><strong class="zp-left zp-left--<?php echo esc_attr( $state ); ?>"><?php echo esc_html( zp_time_left( $p->date_end ) ); ?></strong></div>
				<div class="zp-plife__bar"><i style="--w:<?php echo (int) $pct; ?>%"></i></div>
				<div class="zp-plife__end"><?php echo 'expired' === $state ? 'Expired ' : 'Expires '; ?><?php echo esc_html( zp_date( $p->date_end ) ); ?></div>
			</div>
			<dl class="zp-proxy__grid">
				<?php if ( $p->https_port ) : ?><div><dt>HTTP(S) port</dt><dd class="zp-mono"><?php echo esc_html( $p->https_port ); ?></dd><button type="button" class="zp-pcopy" data-zp-copy="<?php echo esc_attr( $p->ip . ':' . $p->https_port ); ?>" data-zp-copied="IP and HTTP port copied" title="Copy IP and port"><?php echo zp_icon( 'copy', 14 ); // phpcs:ignore ?></button></div><?php endif; ?>
				<?php if ( $p->socks5_port ) : ?><div><dt>SOCKS5 port</dt><dd class="zp-mono"><?php echo esc_html( $p->socks5_port ); ?></dd><button type="button" class="zp-pcopy" data-zp-copy="<?php echo esc_attr( $p->ip . ':' . $p->socks5_port ); ?>" data-zp-copied="IP and SOCKS5 port copied" title="Copy IP and port"><?php echo zp_icon( 'copy', 14 ); // phpcs:ignore ?></button></div><?php endif; ?>
				<?php if ( $p->login ) : ?>
					<div><dt>Username</dt><dd class="zp-mono"><?php echo esc_html( $p->login ); ?></dd><button type="button" class="zp-pcopy" data-zp-copy="<?php echo esc_attr( $p->login ); ?>" data-zp-copied="Username copied" title="Copy username"><?php echo zp_icon( 'copy', 14 ); // phpcs:ignore ?></button></div>
					<div><dt>Password</dt><dd class="zp-mono zp-secret" data-secret="<?php echo esc_attr( $p->password ); ?>">••••••••</dd><button type="button" class="zp-pcopy" data-zp-copy="<?php echo esc_attr( $p->password ); ?>" data-zp-copied="Password copied" title="Copy password"><?php echo zp_icon( 'copy', 14 ); // phpcs:ignore ?></button></div>
				<?php else : ?>
					<div><dt>Access</dt><dd>IP whitelist</dd></div>
				<?php endif; ?>
			</dl>
			<footer>
				<form method="post" class="zp-note">
					<?php zp_nonce_field( 'note' ); ?>
					<input type="hidden" name="proxy_id" value="<?php echo (int) $p->id; ?>">
					<input type="text" name="note" value="<?php echo esc_attr( $p->note ); ?>" maxlength="190" placeholder="Add a label…" aria-label="Label">
				</form>
				<div class="zp-proxy__btns">
					<?php if ( $p->reboot_link && 'expired' !== $state ) : ?>
						<a class="zp-iconbtn zp-iconbtn--sm" href="<?php echo esc_url( $p->reboot_link ); ?>" target="_blank" rel="noopener noreferrer" title="Rotate IP"><?php echo zp_icon( 'swap', 16 ); // phpcs:ignore ?></a>
					<?php endif; ?>
					<?php if ( $p->login ) : ?><button type="button" class="zp-iconbtn zp-iconbtn--sm" data-zp-toggle-secret title="Show password"><?php echo zp_icon( 'eye', 16 ); // phpcs:ignore ?></button><?php endif; ?>
					<button type="button" class="zp-iconbtn zp-iconbtn--sm" data-zp-copy-proxy title="Copy proxy"><?php echo zp_icon( 'copy', 16 ); // phpcs:ignore ?></button>
					<?php if ( 'expired' !== $state ) : ?><button type="button" class="zp-btn zp-btn--soft zp-btn--sm" data-zp-extend-one><?php echo zp_icon( 'refresh', 16 ); // phpcs:ignore ?>Extend</button><?php endif; ?>
				</div>
			</footer>
		</article>
	<?php endforeach; ?>
</div>

<div class="zp-sheet zp-sheet--center" id="zp-extend" hidden>
	<div class="zp-sheet__backdrop" data-zp-close></div>
	<form method="post" class="zp-sheet__panel" role="dialog" aria-label="Extend proxies">
		<div class="zp-sheet__grip"></div>
		<?php zp_nonce_field( 'extend' ); ?>
		<div id="zp-extend-ids"></div>
		<h3>Extend <span id="zp-extend-count">1</span> proxy</h3>
		<p class="zp-muted" id="zp-extend-hint">Choose how long to extend for.</p>
		<div class="zp-pills" id="zp-extend-days"></div>
		<input type="hidden" name="days" value="">
		<div class="zp-summary__total"><span>Total</span><strong id="zp-extend-total">—</strong></div>
		<p class="zp-summary__note" id="zp-extend-note"></p>
		<div class="zp-row">
			<button type="button" class="zp-btn zp-btn--ghost" data-zp-close>Cancel</button>
			<button type="submit" class="zp-btn zp-btn--primary zp-grow" id="zp-extend-btn" disabled>Pay from wallet</button>
		</div>
	</form>
</div>
