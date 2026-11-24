<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$tid = (int) zp_query( 'id' );
if ( $tid ) :
	$t = ZP_Tickets::get( $tid );
	if ( ! $t ) {
		echo zp_empty( 'empty-tickets', 'Ticket not found', '', zp_admin_url( 'tickets' ), 'Back to tickets' ); // phpcs:ignore
		return;
	}
	$owner = get_userdata( $t->user_id );
	?>
	<a class="zp-back" href="<?php echo esc_url( zp_admin_url( 'tickets' ) ); ?>">← All tickets</a>
	<section class="zp-card zp-anim">
		<div class="zp-card__head"><h3>#<?php echo (int) $t->id; ?> · <?php echo esc_html( $t->subject ); ?></h3><?php echo zp_badge( $t->status ); // phpcs:ignore ?></div>
		<p class="zp-muted" style="margin-bottom:16px">From <?php echo $owner ? '<a href="' . esc_url( zp_admin_url( 'users', array( 'id' => $owner->ID ) ) ) . '">' . esc_html( $owner->user_email ) . '</a>' : 'deleted user'; ?></p>
		<div class="zp-thread">
			<?php foreach ( ZP_Tickets::messages( $t->id ) as $m ) : ?>
				<div class="zp-msg<?php echo $m->is_admin ? ' zp-msg--staff' : ' zp-msg--me'; ?>"><div class="zp-msg__bubble"><?php echo nl2br( esc_html( $m->message ) ); ?></div><span><?php echo $m->is_admin ? 'Support' : 'Customer'; ?> · <?php echo esc_html( zp_date( $m->created_at ) ); ?></span></div>
			<?php endforeach; ?>
		</div>
		<form method="post" class="zp-form zp-reply">
			<?php zp_nonce_field( 'ticket_reply' ); ?>
			<input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>">
			<textarea name="message" rows="4" class="zp-textarea" placeholder="Reply to the customer…" required></textarea>
			<div class="zp-row zp-row--between">
				<button class="zp-btn zp-btn--ghost zp-btn--sm" type="submit" form="zp-close-ticket">Close ticket</button>
				<button class="zp-btn zp-btn--primary" type="submit">Send reply</button>
			</div>
		</form>
		<form method="post" id="zp-close-ticket"><?php zp_nonce_field( 'ticket_close' ); ?><input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>"></form>
	</section>
	<?php
	return;
endif;
$rows = $wpdb->get_results( 'SELECT t.*, u.user_email FROM ' . zp_table( 'tickets' ) . " t LEFT JOIN {$wpdb->users} u ON u.ID = t.user_id ORDER BY (t.status = 'open') DESC, t.updated_at DESC LIMIT 200" );
?>
<section class="zp-card zp-anim">
	<?php if ( ! $rows ) : ?>
		<?php echo zp_empty( 'empty-tickets', 'Inbox zero', 'Customer tickets will show up here.' ); // phpcs:ignore ?>
	<?php else : ?>
		<ul class="zp-list zp-list--link">
			<?php foreach ( $rows as $t ) : ?>
				<li><a href="<?php echo esc_url( zp_admin_url( 'tickets', array( 'id' => $t->id ) ) ); ?>">
					<span class="zp-list__ico"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span>
					<div class="zp-list__main"><strong><?php echo esc_html( $t->subject ); ?></strong><span>#<?php echo (int) $t->id; ?> · <?php echo esc_html( $t->user_email ); ?> · <?php echo esc_html( zp_date( $t->updated_at ) ); ?></span></div>
					<div class="zp-list__side"><?php echo zp_badge( $t->status ); // phpcs:ignore ?></div>
				</a></li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
