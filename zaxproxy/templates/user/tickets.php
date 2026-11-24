<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var WP_User $user */
$tid = (int) zp_query( 'id' );
if ( $tid ) :
	$t = ZP_Tickets::get( $tid );
	if ( ! $t || (int) $t->user_id !== $user->ID ) {
		echo zp_empty( 'empty-tickets', 'Ticket not found', 'It may have been removed.', zp_url( 'tickets' ), 'Back to support' ); // phpcs:ignore
		return;
	}
	$messages = ZP_Tickets::messages( $t->id );
	?>
	<a class="zp-back" href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>">← All tickets</a>
	<section class="zp-card zp-anim">
		<div class="zp-card__head"><h3>#<?php echo (int) $t->id; ?> · <?php echo esc_html( $t->subject ); ?></h3><?php echo zp_badge( $t->status ); // phpcs:ignore ?></div>
		<div class="zp-thread">
			<?php foreach ( $messages as $m ) : ?>
				<div class="zp-msg<?php echo $m->is_admin ? ' zp-msg--staff' : ' zp-msg--me'; ?>">
					<div class="zp-msg__bubble"><?php echo nl2br( esc_html( $m->message ) ); ?></div>
					<span><?php echo $m->is_admin ? 'Support' : 'You'; ?> · <?php echo esc_html( zp_date( $m->created_at ) ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php if ( 'closed' !== $t->status ) : ?>
			<form method="post" class="zp-form zp-reply">
				<?php zp_nonce_field( 'ticket_reply' ); ?>
				<input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>">
				<textarea name="message" rows="3" class="zp-textarea" placeholder="Write a reply…" required></textarea>
				<div class="zp-row zp-row--between">
					<button class="zp-btn zp-btn--ghost zp-btn--sm" type="submit" form="zp-close-ticket">Close ticket</button>
					<button class="zp-btn zp-btn--primary" type="submit">Send reply</button>
				</div>
			</form>
			<form method="post" id="zp-close-ticket"><?php zp_nonce_field( 'ticket_close' ); ?><input type="hidden" name="ticket_id" value="<?php echo (int) $t->id; ?>"></form>
		<?php else : ?>
			<p class="zp-muted zp-center">This ticket is closed. Open a new one if you still need help.</p>
		<?php endif; ?>
	</section>
	<?php
	return;
endif;

$tickets = ZP_Tickets::for_user( $user->ID );
?>
<div class="zp-grid2 zp-grid2--wide">
	<section class="zp-card zp-anim">
		<div class="zp-card__head"><h3>Your tickets</h3></div>
		<?php if ( ! $tickets ) : ?>
			<?php echo zp_empty( 'empty-tickets', 'No tickets yet', 'Questions about an order or a proxy? Send us a message and we will help.' ); // phpcs:ignore ?>
		<?php else : ?>
			<ul class="zp-list zp-list--link">
				<?php foreach ( $tickets as $t ) : ?>
					<li><a href="<?php echo esc_url( zp_url( 'tickets', array( 'id' => $t->id ) ) ); ?>">
						<span class="zp-list__ico"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?></span>
						<div class="zp-list__main"><strong><?php echo esc_html( $t->subject ); ?></strong><span>#<?php echo (int) $t->id; ?> · Updated <?php echo esc_html( zp_date( $t->updated_at ) ); ?></span></div>
						<div class="zp-list__side"><?php echo zp_badge( $t->status ); // phpcs:ignore ?></div>
					</a></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</section>

	<section class="zp-card zp-anim" style="--d:1">
		<div class="zp-card__head"><h3>Open a ticket</h3></div>
		<form method="post" class="zp-form">
			<?php zp_nonce_field( 'ticket_new' ); ?>
			<label class="zp-field"><span>Subject</span><div class="zp-input"><input type="text" name="subject" maxlength="190" required placeholder="e.g. Proxy not connecting"></div></label>
			<label class="zp-field"><span>Message</span><textarea name="message" rows="5" class="zp-textarea" required placeholder="Tell us what happened. Include the proxy IP or order number if you have it."></textarea></label>
			<button class="zp-btn zp-btn--primary zp-btn--block" type="submit">Send to support</button>
		</form>
	</section>
</div>
