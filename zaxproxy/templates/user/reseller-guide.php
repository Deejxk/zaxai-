<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Reseller documentation. Shown as the "Documentation" tab of the reseller dashboard and
 * readable before unlocking, so people know exactly what they are buying.
 *
 * @var WP_User $user @var object|null $me
 */
$brand      = zp_get( 'brand' );
$cur        = zp_get( 'currency_code' );
$host       = wp_parse_url( home_url(), PHP_URL_HOST );
$fee        = (float) zp_get( 'reseller_fee', 45000 );
$monthly    = (float) zp_get( 'reseller_monthly', 15000 );
$domain_fee = (float) zp_get( 'reseller_domain_fee', 12000 );
$r_pct      = (float) zp_get( 'reseller_markup', 150 );
$unlocked   = $me && $me->paid_until;
$pct        = function ( $n ) {
	return rtrim( rtrim( number_format( (float) $n, 1 ), '0' ), '.' ) . '%';
};

// A worked example from live prices when available, otherwise round numbers.
$ex     = array( 'type' => 'IPv4', 'days' => 30, 'retail' => 30000.0, 'whole' => 25000.0 );
$prices = zp_public_prices();
foreach ( $prices as $slug => $row ) {
	if ( isset( $row['wholesale'] ) && $row['price'] > $row['wholesale'] ) {
		$ex = array( 'type' => zp_type_label( $slug ), 'days' => (int) $row['days'], 'retail' => (float) $row['price'], 'whole' => (float) $row['wholesale'] );
		break;
	}
}
$match  = $ex['whole'] > 0 ? ( $ex['retail'] / $ex['whole'] - 1 ) * 100 : 20; // markup that equals the regular price
$sell   = function ( $markup ) use ( $ex ) {
	return (float) ceil( $ex['whole'] * ( 1 + $markup / 100 ) );
};
$rows   = array( 10, round( $match, 1 ), 30, 50 );
sort( $rows );
$toc    = array(
	'overview'  => 'What the reseller program is',
	'fees'      => 'Fees and what they cover',
	'pricing'   => 'How pricing and profit work',
	'setup'     => 'Setting up your store',
	'design'    => 'Designing your store',
	'payments'  => 'Getting paid by customers',
	'wallet'    => 'Your wallet and customer orders',
	'dashboard' => 'Your dashboard, tab by tab',
	'domain'    => 'Your own .cloud domain',
	'renewal'   => 'Monthly renewal and pausing',
	'customers' => 'What your customers see',
	'rules'     => 'Your responsibilities',
	'tips'      => 'Tips for growing your store',
	'problems'  => 'Common questions and problems',
);
?>
<div class="zp-doc">
	<aside class="zp-doc__toc zp-anim">
		<strong>On this page</strong>
		<?php
		$n = 0;
		foreach ( $toc as $id => $label ) :
			$n++;
			?>
			<a href="#doc-<?php echo esc_attr( $id ); ?>"><span><?php echo (int) $n; ?></span><?php echo esc_html( $label ); ?></a>
		<?php endforeach; ?>
	</aside>

	<div class="zp-doc__body">
		<section class="zp-card zp-doc__hero zp-anim">
			<span class="zp-eyebrow">Reseller documentation</span>
			<h2>Everything you need to run your own proxy store</h2>
			<p>This guide explains the reseller program from start to finish: what it costs, how you earn, how to set up your store, how customers pay you, and what each part of your dashboard does. Read it once and you will know exactly how your business works.</p>
			<?php if ( ! $unlocked ) : ?>
				<div class="zp-row"><a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'reseller' ) ); ?>#zp-rs-unlock"><?php echo zp_icon( 'zap', 18 ); // phpcs:ignore ?>Unlock for <?php echo esc_html( zp_money( $fee ) ); ?></a><a class="zp-btn zp-btn--ghost" href="<?php echo esc_url( zp_url( 'reseller' ) ); ?>">Back to overview</a></div>
			<?php endif; ?>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-overview">
			<h3><b>1</b>What the reseller program is</h3>
			<p>As a reseller you run your own proxy shop on top of <?php echo esc_html( $brand ); ?>. You get a store with your own brand name, your own link, your own prices and your own customers. We supply and deliver the proxies; you look after your customers and keep the profit.</p>
			<p>In short:</p>
			<ul>
				<li><strong>You buy cheaper.</strong> Resellers pay the reseller price, which is lower than the regular price.</li>
				<li><strong>You sell at your own price.</strong> You add your own markup on top of the reseller price.</li>
				<li><strong>Customers pay you directly.</strong> The money goes into your own bank account.</li>
				<li><strong>We deliver automatically.</strong> When your customer buys, the proxy is delivered to their dashboard without you doing anything.</li>
				<li><strong>You keep the difference.</strong> Everything above the reseller price is yours.</li>
			</ul>
			<div class="zp-callout">You do not need technical knowledge, servers or stock. Your store, your customers' dashboard, ordering and delivery are all provided.</div>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-fees">
			<h3><b>2</b>Fees and what they cover</h3>
			<div class="zp-tablewrap"><table class="zp-table zp-table--keep">
				<thead><tr><th>Fee</th><th class="zp-right">Amount</th><th>When</th></tr></thead>
				<tbody>
					<tr><td><strong>Activation</strong></td><td class="zp-right"><?php echo esc_html( zp_money( $fee ) ); ?></td><td>Once, when you unlock. Includes your first 30 days.</td></tr>
					<tr><td><strong>Monthly fee</strong></td><td class="zp-right"><?php echo esc_html( zp_money( $monthly ) ); ?></td><td>Every 30 days after that, automatically.</td></tr>
					<tr><td><strong>Branded domain</strong></td><td class="zp-right"><?php echo esc_html( zp_money( $domain_fee ) ); ?></td><td>Optional. Only if you want your own .cloud address.</td></tr>
				</tbody>
			</table></div>
			<ul>
				<li>All fees are paid from your <?php echo esc_html( $brand ); ?> wallet, so top up your wallet first.</li>
				<li>The activation fee opens your dashboard and store immediately.</li>
				<li>The monthly fee keeps your store online and keeps you on the reseller price.</li>
				<li>Activation and monthly fees are not refundable once paid. The domain fee is refunded if the name you chose is not available.</li>
			</ul>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-pricing">
			<h3><b>3</b>How pricing and profit work</h3>
			<p>There are three prices to understand:</p>
			<ol>
				<li><strong>Regular price</strong>: what a normal <?php echo esc_html( $brand ); ?> customer pays.</li>
				<li><strong>Reseller price</strong>: what you pay. It is lower than the regular price.</li>
				<li><strong>Your selling price</strong>: the reseller price plus your markup. This is what your customers pay.</li>
			</ol>
			<div class="zp-formula"><span>Your selling price</span><b>=</b><span>Reseller price</span><b>+</b><span>Your markup %</span></div>
			<div class="zp-formula"><span>Your profit</span><b>=</b><span>Your selling price</span><b>−</b><span>Reseller price</span></div>
			<p><strong>Example: 1 <?php echo esc_html( $ex['type'] ); ?> proxy for <?php echo (int) $ex['days']; ?> days.</strong> The regular price is <?php echo esc_html( zp_money( $ex['retail'] ) ); ?> and your reseller price is <?php echo esc_html( zp_money( $ex['whole'] ) ); ?>. The table shows what happens with different markups:</p>
			<div class="zp-tablewrap"><table class="zp-table zp-table--keep">
				<thead><tr><th>Your markup</th><th class="zp-right">Customer pays</th><th class="zp-right">Your profit</th><th>Compared with regular price</th></tr></thead>
				<tbody>
				<?php
				foreach ( $rows as $m ) :
					$s    = $sell( $m );
					$diff = $s - $ex['retail'];
					?>
					<tr>
						<td><strong><?php echo esc_html( $pct( $m ) ); ?></strong></td>
						<td class="zp-right"><?php echo esc_html( zp_money( $s ) ); ?></td>
						<td class="zp-right"><strong class="zp-pos"><?php echo esc_html( zp_money( $s - $ex['whole'] ) ); ?></strong></td>
						<td><?php echo abs( $diff ) < 1 ? 'Same as regular price' : esc_html( zp_money( abs( $diff ) ) . ( $diff < 0 ? ' cheaper' : ' more expensive' ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table></div>
			<ul>
				<li>A markup of about <strong><?php echo esc_html( $pct( $match ) ); ?></strong> makes your price the same as the regular price.</li>
				<li>A lower markup makes you cheaper than the regular price, which helps you win customers.</li>
				<li>A higher markup earns more per sale. It is your choice, and you can change it any time in Store settings.</li>
				<li>Your markup applies to every proxy type, country and period. Prices are rounded up to the next whole <?php echo esc_html( $cur ); ?>.</li>
				<li>You also buy your <strong>own</strong> proxies at the reseller price.</li>
			</ul>
			<div class="zp-callout"><strong>Monthly profit, roughly:</strong> multiply your profit per proxy by the number you sell. With the regular-price markup above, 10 sales earn about <?php echo esc_html( zp_money( ( $ex['retail'] - $ex['whole'] ) * 10 ) ); ?>, 50 sales about <?php echo esc_html( zp_money( ( $ex['retail'] - $ex['whole'] ) * 50 ) ); ?>, and 100 sales about <?php echo esc_html( zp_money( ( $ex['retail'] - $ex['whole'] ) * 100 ) ); ?>, before your <?php echo esc_html( zp_money( $monthly ) ); ?> monthly fee.</div>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-setup">
			<h3><b>4</b>Setting up your store</h3>
			<ol>
				<li><strong>Fund your wallet.</strong> Go to Wallet and add at least <?php echo esc_html( zp_money( $fee ) ); ?> (plus <?php echo esc_html( zp_money( $domain_fee ) ); ?> if you want a domain).</li>
				<li><strong>Open the Reseller page</strong> and scroll to "Unlock your reseller account".</li>
				<li><strong>Choose your brand name.</strong> This is the name your customers will see everywhere.</li>
				<li><strong>Choose your store link.</strong> Your store opens at <span class="zp-mono"><?php echo esc_html( $host ); ?>/s/your-link</span>. Use lowercase letters, numbers and dashes.</li>
				<li><strong>Set your markup.</strong> You can change it later.</li>
				<li><strong>Press "Pay and unlock".</strong> Your dashboard and store are live straight away.</li>
				<li><strong>Add your bank details and Telegram</strong> in Store settings so customers can pay you. Do this before sharing your link.</li>
			</ol>
			<div class="zp-callout">Changing your store link later breaks links you have already shared, so choose it carefully.</div>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-design">
			<h3><b>5</b>Designing your store</h3>
			<p>In <strong>Store settings → Store design</strong> you make the store your own:</p>
			<ul>
				<li><strong>Brand colour.</strong> Use the colour picker or one of the suggested colours. It changes the buttons and highlights on your store, your sign-in pages and your customers' dashboard.</li>
				<li><strong>Logo.</strong> Upload a square PNG, JPG or WebP under 1 MB. Without a logo, we show the first letter of your brand in your colour.</li>
				<li><strong>Headline and intro text.</strong> Write your own welcome message for the top of your store. Leave them empty to use the standard wording.</li>
				<li><strong>Privacy policy and refund policy.</strong> Write your own, or leave them empty and a standard policy in your brand's name is shown. Both are linked at the bottom of your store.</li>
			</ul>
			<p>Use "Preview store" to see your changes as your customers will.</p>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-payments">
			<h3><b>6</b>Getting paid by customers</h3>
			<p>Your customers do not pay <?php echo esc_html( $brand ); ?>. They pay <strong>you</strong>, into the bank account you enter in Store settings.</p>
			<ol>
				<li>Your customer opens Wallet in their dashboard, enters an amount and sees <strong>your</strong> bank name, account name and account number.</li>
				<li>They make the transfer and press "I have made the transfer".</li>
				<li>They tap "Send receipt on Telegram", which opens a chat with you with the amount and reference already typed in.</li>
				<li>The deposit appears in your dashboard under <strong>Deposits</strong>, marked Pending.</li>
				<li>You check your bank account. When the money has arrived, press <strong>Approve</strong> and their wallet is credited. If it has not, press <strong>Reject</strong>.</li>
			</ol>
			<div class="zp-callout zp-callout--warn"><strong>Always confirm the money in your own bank app before you approve.</strong> A receipt can be faked. Once you approve, the customer can spend that credit, and each purchase is paid from your wallet.</div>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-wallet">
			<h3><b>7</b>Your wallet and customer orders</h3>
			<p>This is the most important part to understand. Your customer's wallet holds credit that <strong>you</strong> gave them. Your own <?php echo esc_html( $brand ); ?> wallet holds real money that pays us.</p>
			<p>When a customer buys a proxy:</p>
			<ol>
				<li>Your selling price is taken from the <strong>customer's</strong> wallet.</li>
				<li>The reseller price is taken from <strong>your</strong> wallet.</li>
				<li>The proxy is delivered to the customer.</li>
				<li>The difference shows as profit in your dashboard. You already hold that money, because the customer paid you directly.</li>
			</ol>
			<ul>
				<li><strong>If your wallet cannot cover the reseller price, the order is refused.</strong> The customer sees "Ordering is temporarily unavailable" and is not charged.</li>
				<li>You then see a notice at the top of your dashboard telling you which order was blocked and how much was needed.</li>
				<li>If an order fails after payment, both wallets are refunded automatically: the customer gets their price back and you get the reseller price back.</li>
				<li>Renewals work the same way as purchases.</li>
			</ul>
			<div class="zp-callout">A simple rule: when you approve a customer's deposit, top up your own wallet with the reseller-price part of it. Then their orders always go through.</div>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-dashboard">
			<h3><b>8</b>Your dashboard, tab by tab</h3>
			<dl class="zp-doc__dl">
				<dt><?php echo zp_icon( 'chart', 18 ); // phpcs:ignore ?>Overview</dt>
				<dd>Your store link with a Copy button, your wallet balance, number of customers, total sales, your cost and your profit. Below that are a 30-day sales chart, your newest customers, your latest orders, and anything that needs your attention.</dd>
				<dt><?php echo zp_icon( 'users', 18 ); // phpcs:ignore ?>Customers</dt>
				<dd>Everyone who registered through your store, with their wallet balance and active proxies. Use Credit or Debit to adjust a customer's wallet by hand, for example for a cash payment or a correction.</dd>
				<dt><?php echo zp_icon( 'card', 18 ); // phpcs:ignore ?>Deposits</dt>
				<dd>Payments your customers say they have made. Approve to credit their wallet, or Reject. A number on the tab shows how many are waiting.</dd>
				<dt><?php echo zp_icon( 'receipt', 18 ); // phpcs:ignore ?>Orders</dt>
				<dd>Every order placed in your store: what the customer paid, what it cost you, and your profit on that order.</dd>
				<dt><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?>Tickets</dt>
				<dd>Support requests from your customers come to you, not to us. Open a ticket to read it, reply or close it. You get an email when a new ticket arrives.</dd>
				<dt><?php echo zp_icon( 'settings', 18 ); // phpcs:ignore ?>Store settings</dt>
				<dd>Your brand name, store link, markup, bank details, Telegram, store design, policies and branded domain.</dd>
				<dt><?php echo zp_icon( 'help', 18 ); // phpcs:ignore ?>Documentation</dt>
				<dd>This guide.</dd>
			</dl>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-domain">
			<h3><b>9</b>Your own .cloud domain</h3>
			<p>For <?php echo esc_html( zp_money( $domain_fee ) ); ?> you can replace <span class="zp-mono"><?php echo esc_html( $host ); ?>/s/your-link</span> with your own address, such as <span class="zp-mono">yourbrand.cloud</span>.</p>
			<ol>
				<li>Enter the name you want when unlocking, or later under Store settings → Branded domain.</li>
				<li>The fee is taken from your wallet and the request shows as Pending.</li>
				<li>We register the domain and connect it to your store, usually within 24 hours.</li>
				<li>When it shows as Active, your store, sign-up, sign-in and your customers' dashboard all run on your own domain.</li>
			</ol>
			<ul>
				<li>The domain always ends in <span class="zp-mono">.cloud</span>.</li>
				<li>If the name is already taken, the request is rejected and your <?php echo esc_html( zp_money( $domain_fee ) ); ?> is refunded. You can then try another name.</li>
				<li>Your original store link keeps working alongside the domain.</li>
				<li>Only your own customers can sign in on your domain.</li>
			</ul>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-renewal">
			<h3><b>10</b>Monthly renewal and pausing</h3>
			<ul>
				<li>Your dashboard shows the date your reseller account is paid until.</li>
				<li>On that date, <?php echo esc_html( zp_money( $monthly ) ); ?> is taken from your wallet automatically and the account runs for another 30 days.</li>
				<li>You see a reminder to top up when the date is close and your wallet is too low.</li>
			</ul>
			<p><strong>If your wallet cannot cover the monthly fee, your account is paused:</strong></p>
			<ul>
				<li>Your store goes offline and your customers cannot buy.</li>
				<li>You are charged regular prices for your own orders.</li>
				<li>Your customers, their wallets and their existing proxies are kept. Proxies they already bought keep working until they expire.</li>
			</ul>
			<p>To restore everything, top up your wallet and press <strong>Renew</strong> in your dashboard. The system also retries by itself once funds arrive.</p>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-customers">
			<h3><b>11</b>What your customers see</h3>
			<ul>
				<li>Your store page with your brand, logo, colour, headline and prices.</li>
				<li>Sign-up and sign-in pages in your brand.</li>
				<li>A full dashboard in your brand: buy proxies, my proxies, orders, wallet, support and account.</li>
				<li>Your bank details and your Telegram when they add funds.</li>
				<li>Your privacy and refund policies.</li>
				<li>Prices that already include your markup. They never see the reseller price or your profit.</li>
			</ul>
			<p>Customers of your store cannot open reseller stores of their own.</p>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-rules">
			<h3><b>12</b>Your responsibilities</h3>
			<ul>
				<li><strong>Your customers are yours.</strong> You collect their payments, credit their wallets, answer their tickets and handle their refund requests.</li>
				<li><strong>Keep your wallet funded</strong> so their orders are never refused.</li>
				<li><strong>Follow the Acceptable Use Policy</strong> and make sure your customers do. Proxies must not be used for fraud, spam, attacks or anything illegal.</li>
				<li><strong>Be honest in your store.</strong> Do not promise things the service does not offer.</li>
				<li>We may suspend a store that is used against the rules. A suspended store cannot sell.</li>
			</ul>
			<p>The full rules are in the <a href="<?php echo esc_url( zp_route_url( 'terms' ) ); ?>" target="_blank" rel="noopener">Terms and Conditions</a>, the <a href="<?php echo esc_url( zp_route_url( 'acceptable-use' ) ); ?>" target="_blank" rel="noopener">Acceptable Use Policy</a> and the <a href="<?php echo esc_url( zp_route_url( 'refund-policy' ) ); ?>" target="_blank" rel="noopener">Refund Policy</a>.</p>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-tips">
			<h3><b>13</b>Tips for growing your store</h3>
			<ul>
				<li><strong>Share your link everywhere:</strong> WhatsApp status, Telegram groups, Instagram bio, Twitter and Facebook.</li>
				<li><strong>Start slightly cheaper</strong> than the regular price to win your first customers, then adjust.</li>
				<li><strong>Approve deposits quickly.</strong> Fast wallet credit is the main reason customers come back.</li>
				<li><strong>Answer tickets the same day.</strong> Good support beats a low price.</li>
				<li><strong>Explain the proxy types</strong> to customers who are unsure: IPv4 for everyday use, ISP for accounts that need trust, mobile for apps, IPv6 for bulk.</li>
				<li><strong>Get a branded domain</strong> once you have regular customers. It looks more professional and is easier to remember.</li>
				<li><strong>Watch your Overview</strong> each day for pending deposits, open tickets and your wallet balance.</li>
			</ul>
		</section>

		<section class="zp-card zp-doc__sec zp-anim" id="doc-problems">
			<h3><b>14</b>Common questions and problems</h3>
			<div class="zp-faq">
				<details><summary>My customer says they cannot buy<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Check three things: their wallet has enough credit, your own wallet has enough to cover the reseller price, and your reseller account is active (not paused). The notice at the top of your dashboard tells you if an order was blocked by a low wallet.</p></details>
				<details><summary>My store link shows "not found"<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Your account is paused or suspended, or you changed your store link. Check your dashboard for a renewal notice and confirm the link under Store settings.</p></details>
				<details><summary>A customer paid but I see no deposit<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>They must press "I have made the transfer" in their Wallet. If they paid you another way, credit their wallet by hand from the Customers tab.</p></details>
				<details><summary>I approved a deposit by mistake<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Go to Customers and Debit the same amount from that customer's wallet, as long as they have not spent it yet.</p></details>
				<details><summary>Can I change my markup for one customer only?<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>No, your markup applies to your whole store. To give one customer a discount, credit the difference to their wallet from the Customers tab.</p></details>
				<details><summary>A customer's proxy is not working<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Ask them to check the IP, port, username and password exactly as shown, and that the protocol matches the port. If it still fails within 24 hours of delivery, open a ticket with us from Support and include the proxy IP.</p></details>
				<details><summary>How do I get help myself?<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Open a ticket from the Support page in your own dashboard. Your tickets come to the <?php echo esc_html( $brand ); ?> team.</p></details>
				<details><summary>Can I cancel?<?php echo zp_icon( 'plus', 18 ); // phpcs:ignore ?></summary><p>Yes. Simply stop funding the monthly fee and your account pauses at the end of the paid period. Fees already paid are not refunded.</p></details>
			</div>
		</section>

		<section class="zp-cta zp-anim">
			<div><strong>Still have a question?</strong><span>Our team is happy to explain anything about reselling.</span></div>
			<a class="zp-btn zp-btn--primary" href="<?php echo esc_url( zp_url( 'tickets' ) ); ?>"><?php echo zp_icon( 'ticket', 18 ); // phpcs:ignore ?>Ask support</a>
		</section>
	</div>
</div>
