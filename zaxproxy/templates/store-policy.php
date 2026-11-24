<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** A reseller store's privacy or refund policy. @var string $doc */
$store = zp_reseller_ctx();
$brand = $store->brand;
$title = 'refund' === $doc ? 'Refund Policy' : 'Privacy Policy';
$text  = ZP_Resellers::policy( $store, $doc );
$home  = ZP_Resellers::store_url( $store );
?>
<!doctype html>
<html lang="en">
<head>
<?php ZP_Router::head( $title, true ); ?>
<link rel="stylesheet" href="<?php echo esc_url( zp_asset( 'css/public.css' ) ); ?>">
</head>
<body class="zp-body zp-public">
<header class="zp-phead">
	<div class="zp-wrap zp-phead__in">
		<a class="zp-brand" href="<?php echo esc_url( $home ); ?>"><?php echo zp_logo( 32 ); // phpcs:ignore ?><?php echo zp_wordmark(); // phpcs:ignore ?></a>
		<div class="zp-phead__actions">
			<button class="zp-iconbtn zp-theme" id="zp-theme" type="button" title="Toggle dark / light mode" aria-label="Toggle dark / light mode">
				<span class="zp-theme__sun"><?php echo zp_icon( 'sun' ); // phpcs:ignore ?></span><span class="zp-theme__moon"><?php echo zp_icon( 'moon' ); // phpcs:ignore ?></span>
			</button>
			<a class="zp-btn zp-btn--ghost zp-btn--sm" href="<?php echo esc_url( $home ); ?>">Back to store</a>
		</div>
	</div>
</header>
<main>
	<section class="zp-ppage">
		<div class="zp-wrap zp-pnarrow">
			<article class="zp-card zp-plegal__doc zp-anim">
				<h1><?php echo esc_html( $title ); ?></h1>
				<p class="zp-muted"><?php echo esc_html( $brand ); ?> · Last updated <?php echo esc_html( zp_date( $store->updated_at, false ) ); ?></p>
				<?php
				foreach ( preg_split( '/\n\s*\n/', $text ) as $block ) :
					$lines = array_values( array_filter( array_map( 'trim', explode( "\n", $block ) ), 'strlen' ) );
					if ( ! $lines ) {
						continue;
					}
					// A short first line followed by text reads as a heading.
					if ( count( $lines ) > 1 && mb_strlen( $lines[0] ) <= 60 && ! preg_match( '/[.!?:]$/', $lines[0] ) ) {
						echo '<h2>' . esc_html( array_shift( $lines ) ) . '</h2>';
					}
					echo '<p>' . implode( '<br>', array_map( 'esc_html', $lines ) ) . '</p>'; // phpcs:ignore
				endforeach;
				?>
			</article>
		</div>
	</section>
</main>
<footer class="zp-pfoot">
	<div class="zp-wrap zp-pfoot__base" style="margin-top:0;border-top:0;padding-top:0">
		<span>© <?php echo esc_html( gmdate( 'Y' ) . ' ' . $brand ); ?>. All rights reserved.</span>
		<span><a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'privacy' ) ); ?>">Privacy Policy</a> · <a href="<?php echo esc_url( ZP_Resellers::store_url( $store, 'refund' ) ); ?>">Refund Policy</a></span>
	</div>
</footer>
<?php ZP_Router::foot(); ?>
</body>
</html>
