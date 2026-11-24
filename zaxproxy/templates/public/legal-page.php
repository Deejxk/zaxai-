<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $doc @var array $legal @var string $view */
?>
<section class="zp-ppage">
	<div class="zp-wrap zp-plegal">
		<aside class="zp-plegal__nav zp-anim">
			<strong>Legal</strong>
			<?php foreach ( $legal as $slug => $d ) : ?>
				<a class="<?php echo $slug === $view ? 'is-active' : ''; ?>" href="<?php echo esc_url( zp_route_url( $slug ) ); ?>"><?php echo esc_html( $d['title'] ); ?></a>
			<?php endforeach; ?>
		</aside>
		<article class="zp-card zp-plegal__doc zp-anim" style="--d:1">
			<h1><?php echo esc_html( $doc['title'] ); ?></h1>
			<p class="zp-muted">Last updated: <?php echo esc_html( $doc['updated'] ); ?></p>
			<p><?php echo esc_html( $doc['intro'] ); ?></p>
			<?php foreach ( $doc['sections'] as $heading => $paras ) : ?>
				<h2><?php echo esc_html( $heading ); ?></h2>
				<?php foreach ( (array) $paras as $para ) : ?>
					<?php if ( is_array( $para ) ) : ?>
						<ul><?php foreach ( $para as $li ) : ?><li><?php echo esc_html( $li ); ?></li><?php endforeach; ?></ul>
					<?php else : ?>
						<p><?php echo esc_html( $para ); ?></p>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endforeach; ?>
		</article>
	</div>
</section>
