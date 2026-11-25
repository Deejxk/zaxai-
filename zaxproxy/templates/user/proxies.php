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
?>
<?php if ( $pending ) : ?>
	<div class="zp-alert zp-alert--info zp-anim"><span class="zp-spinner"></span><span><?php echo esc_html( sprintf( '%d order%s being set up. New proxies appear here automatically, usually within a few minutes.', $pending, 1 === $pending ? ' is' : 's are' ) ); ?></span><a class="zp-btn zp-btn--sm zp-btn--soft" href="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">Refresh</a></div>
<?php endif; ?>

<form class="zp-toolbar zp-anim" method="get" action="<?php echo esc_url( zp_url( 'proxies' ) ); ?>">
	<?php if ( ! zp_pretty() ) : ?><input type="hidden" name="zp" value="<?php echo esc_attr( ZP_USER_BASE ); ?>"><input type="hidden" name="zpp" value="proxies"><?php endif; ?>
	<div class="zp-input zp-input--search"><?php echo zp_icon( 'search', 18 ); // phpcs:ignore ?><input type="search" name="q" value="<?php echo esc_attr( $f_q ); ?>" placeholder="Search IP, label or country"></div>
	<select name="type" class="zp-select" onchange="this.form.submit()">
		<option value="">All types</option>
		<?php foreach ( zp_types() as $slug => $t ) : ?>
			<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $f_type, $slug ); ?>><?php echo esc_html( $t['label'] ); ?></option>
		<?php endforeach; ?>
	</select>
	<select name="state" class="zp-select" onchange="this.form.submit()">
		<option value="">Any status</option>
		<option value="active" <?php selected( $f_state, 'active' ); ?>>Active</option>
		<option value="expired" <?php selected( $f_state, 'expired' ); ?>>Expired</option>
	</select>
	<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'buy' ) ); ?>"><?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?><span>Buy more</span></a>
</form>

<?php if ( ! $rows ) : ?>
	<div class="zp-card zp-anim" style="--d:1">
		<?php echo zp_empty( 'empty-proxies', $f_q || $f_type || $f_state ? 'No proxies match your filters' : 'No proxies yet', $f_q || $f_type || $f_state ? 'Try clearing the search or filters.' : 'Buy a proxy and it will be delivered here with its credentials.', zp_url( 'buy' ), 'Buy proxies' ); // phpcs:ignore ?>
	</div>
	<?php
	return;
endif;
?>

<a class="zp-howto zp-anim" href="<?php echo esc_url( zp_url( 'help' ) ); ?>#connect"><span class="zp-stat__ico zp-tone-violet"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?></span><div><strong>How to connect your proxy</strong><span>Step-by-step guides for Windows, Android and iPhone</span></div><b><?php echo zp_icon( 'arrow', 16 ); // phpcs:ignore ?></b></a>

<div class="zp-bulk zp-anim" style="--d:1" id="zp-bulk">
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
		?>
		<article class="zp-proxy zp-anim" style="--d:<?php echo (int) min( $i + 1, 10 ); ?>" data-id="<?php echo (int) $p->id; ?>" data-type="<?php echo esc_attr( $p->proxy_type ); ?>"
			data-ip="<?php echo esc_attr( $p->ip ); ?>" data-http="<?php echo esc_attr( $p->https_port ); ?>" data-socks="<?php echo esc_attr( $p->socks5_port ); ?>"
			data-login="<?php echo esc_attr( $p->login ); ?>" data-pass="<?php echo esc_attr( $p->password ); ?>">
			<header>
				<label class="zp-check"><input type="checkbox" class="zp-proxy__check" value="<?php echo (int) $p->id; ?>" <?php disabled( $state, 'expired' ); ?> aria-label="Select proxy"><span></span></label>
				<span class="zp-flag"><?php echo esc_html( zp_flag( $p->country ) ); ?></span>
				<div class="zp-proxy__id">
					<strong class="zp-mono"><?php echo esc_html( $p->ip ); ?></strong>
					<span><?php echo esc_html( zp_type_label( $p->proxy_type ) . ' · ' . zp_country_name( $p->country ) . ( $p->mobile_operator ? ' · ' . $p->mobile_operator : '' ) ); ?></span>
				</div>
				<?php echo zp_badge( $state ); // phpcs:ignore ?>
			</header>
			<dl class="zp-proxy__grid">
				<?php if ( $p->https_port ) : ?><div><dt>HTTP(S) port</dt><dd class="zp-mono"><?php echo esc_html( $p->https_port ); ?></dd></div><?php endif; ?>
				<?php if ( $p->socks5_port ) : ?><div><dt>SOCKS5 port</dt><dd class="zp-mono"><?php echo esc_html( $p->socks5_port ); ?></dd></div><?php endif; ?>
				<?php if ( $p->login ) : ?>
					<div><dt>Username</dt><dd class="zp-mono"><?php echo esc_html( $p->login ); ?></dd></div>
					<div><dt>Password</dt><dd class="zp-mono zp-secret" data-secret="<?php echo esc_attr( $p->password ); ?>">••••••••</dd></div>
				<?php else : ?>
					<div><dt>Access</dt><dd>IP whitelist</dd></div>
				<?php endif; ?>
				<div><dt>Expires</dt><dd><?php echo esc_html( zp_date( $p->date_end ) ); ?></dd></div>
				<div><dt>Time left</dt><dd class="zp-left zp-left--<?php echo esc_attr( $state ); ?>"><?php echo esc_html( zp_time_left( $p->date_end ) ); ?></dd></div>
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
