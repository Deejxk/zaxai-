<?php
/**
 * Legal documents for the public site. Returned as data so the wording is easy to edit.
 * These are general-purpose templates: have them reviewed for your business and jurisdiction.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$brand   = zp_get( 'brand' );
$site    = wp_parse_url( home_url(), PHP_URL_HOST );
$email   = is_email( zp_get( 'support_email' ) ) ? zp_get( 'support_email' ) : 'our support team';
$cur     = zp_get( 'currency_code' );
$updated = wp_date( 'F j, Y', filemtime( __FILE__ ) );

return array(
	'terms'          => array(
		'title'    => 'Terms of Service',
		'updated'  => $updated,
		'intro'    => "These terms govern your use of {$brand} ({$site}). By creating an account or buying a service you agree to them.",
		'sections' => array(
			'1. The service'                 => array( "{$brand} rents access to proxy servers (IPv4, IPv6, ISP and mobile) for a fixed rental period. Proxies are sourced from upstream network providers and delivered to your account dashboard." ),
			'2. Your account'                => array( 'You must provide accurate information, be at least 18 years old, and keep your login details secure. You are responsible for all activity carried out through your account and your proxies.' ),
			'3. Wallet and payments'         => array( "Services are paid for from a prepaid wallet held in {$cur}. Wallet funds are used only to buy and extend services on {$brand} and do not earn interest. Prices are shown before you confirm an order and may change for future orders at any time." ),
			'4. Delivery and rental periods' => array( 'Most orders are delivered within minutes; some may take longer depending on the upstream provider. The rental period starts when the proxy is issued and ends on the expiry date shown in your dashboard. Proxies that are not extended before expiry are released and cannot be recovered.' ),
			'5. Acceptable use'              => array( 'You agree to follow our Acceptable Use Policy. We may suspend or terminate any proxy or account that breaches it, without refund.' ),
			'6. Availability'                => array( 'We aim for uninterrupted service but do not guarantee that a proxy will work with every website or application, or that it will never be blocked by a third party. Maintenance or upstream issues may cause temporary downtime.' ),
			'7. Refunds'                     => array( 'Refunds are handled under our Refund Policy.' ),
			'8. Limitation of liability'     => array( "The service is provided \"as is\". To the fullest extent permitted by law, {$brand} is not liable for indirect or consequential losses, lost profits or lost data, and our total liability for any claim is limited to the amount you paid for the affected service." ),
			'9. Termination'                 => array( 'You may stop using the service at any time. We may suspend or close accounts that breach these terms, are used fraudulently, or put our network at risk.' ),
			'10. Changes to these terms'     => array( 'We may update these terms from time to time. The latest version is always published on this page, and continued use of the service means you accept it.' ),
			'11. Contact'                    => array( "Questions about these terms can be sent to {$email}." ),
		),
	),
	'privacy'        => array(
		'title'    => 'Privacy Policy',
		'updated'  => $updated,
		'intro'    => "This policy explains what personal data {$brand} collects, why we collect it, and the choices you have.",
		'sections' => array(
			'Information we collect' => array(
				array(
					'Account details: your name, email address and encrypted password.',
					'Order and wallet records: what you bought, amounts paid, payment references and transaction history.',
					'Support messages: tickets and contact-form messages you send us.',
					'Technical data: IP address, browser type and basic logs needed to keep the site secure.',
					'An IP address you choose to whitelist for proxy authorization.',
				),
			),
			'How we use it'          => array(
				array(
					'To create and manage your account and deliver the services you order.',
					'To process payments and keep accurate financial records.',
					'To answer support requests and send service messages such as password resets.',
					'To prevent fraud, abuse and breaches of our Acceptable Use Policy.',
					'To meet legal obligations.',
				),
			),
			'Sharing'                => array( 'We do not sell your personal data. We share it only with providers that help us run the service: payment processors (to take payments), our upstream proxy provider (order parameters such as country and quantity, and a whitelisted IP if you supply one), hosting and email providers, and authorities where the law requires it.' ),
			'Payments'               => array( 'Card and bank payments are handled by our payment processor. We never see or store your full card details.' ),
			'Retention'              => array( 'We keep account, order and transaction records for as long as your account is open and for the period required by tax and accounting rules afterwards. Support messages are kept while they are useful for helping you.' ),
			'Security'               => array( 'We use encrypted connections, hashed passwords and access controls to protect your data. No online service can be perfectly secure, so please use a strong, unique password.' ),
			'Your rights'            => array( "Under the Nigeria Data Protection Act and similar laws you can ask to access, correct or delete your personal data, or object to how we use it. Contact {$email} and we will respond within a reasonable time." ),
			'Cookies'                => array( 'We use a small number of essential cookies. See our Cookie Policy for details.' ),
			'Changes'                => array( 'We will post any changes to this policy on this page and update the date above.' ),
		),
	),
	'refund-policy'  => array(
		'title'    => 'Refund Policy',
		'updated'  => $updated,
		'intro'    => 'We want you to be happy with your proxies. This policy explains when you can get your money back.',
		'sections' => array(
			'Orders we cannot fulfil'  => array( 'If an order fails or cannot be delivered, the full amount is returned to your wallet automatically.' ),
			'Proxies that do not work' => array( 'If a proxy does not connect at all, tell us through a support ticket within 24 hours of delivery. We will replace it or refund the order to your wallet.' ),
			'What is not refundable'   => array(
				array(
					'Proxies that work but are blocked by a specific website or service.',
					'Proxies you no longer need, or ordered with the wrong type, country or period.',
					'Unused time on a rental after the first 24 hours.',
					'Services suspended for breaching our Acceptable Use Policy.',
				),
			),
			'Wallet balance'           => array( "Refunds are credited to your {$brand} wallet so you can use them straight away. Withdrawal of unused wallet funds to your original payment method can be requested through support and is reviewed case by case; payment-processor fees are not refundable." ),
			'How to ask'               => array( "Open a support ticket from your dashboard, or contact {$email}, with your order number and a short description of the problem." ),
		),
	),
	'acceptable-use' => array(
		'title'    => 'Acceptable Use Policy',
		'updated'  => $updated,
		'intro'    => "Our proxies are for lawful purposes only. This policy applies to everyone who uses {$brand}.",
		'sections' => array(
			'You must not use the service to' => array(
				array(
					'Break any law or regulation that applies to you or to the target system.',
					'Commit or attempt fraud, identity theft, carding, phishing or financial scams.',
					'Access accounts, systems or data without permission, including credential stuffing and brute-force attacks.',
					'Send spam or bulk unsolicited messages.',
					'Launch or take part in denial-of-service attacks, or scan and exploit networks.',
					'Distribute malware, ransomware or other harmful software.',
					'Access, store or share child sexual abuse material or any content that exploits minors.',
					'Harass, threaten or stalk any person.',
					'Infringe intellectual property rights.',
					'Resell or share access in a way that hides abusive activity.',
				),
			),
			'Enforcement'                     => array( 'We may suspend or terminate proxies and accounts that breach this policy, without notice or refund, and may cooperate with lawful requests from authorities.' ),
			'Reporting abuse'                 => array( "If you believe one of our IP addresses is being abused, contact {$email} with the IP address, time and details so we can investigate." ),
		),
	),
	'cookies'        => array(
		'title'    => 'Cookie Policy',
		'updated'  => $updated,
		'intro'    => "{$brand} uses only the cookies and browser storage needed to make the site work.",
		'sections' => array(
			'What we use'         => array(
				array(
					'Login cookies: keep you signed in to your dashboard.',
					'Security cookies: protect forms and your session.',
					'Message cookie: shows a confirmation or error after you submit a form, then expires within a minute.',
					'Theme preference: your dark or light mode choice is saved in your browser.',
					'Offline support: the installable app stores its design files on your device so it opens quickly.',
				),
			),
			'What we do not use'  => array( 'We do not use advertising cookies or sell browsing data to third parties.' ),
			'Third parties'       => array( 'If you pay by card or bank transfer, the payment processor sets its own cookies on its checkout page. Fonts are loaded from Google Fonts.' ),
			'Managing cookies'    => array( 'You can clear or block cookies in your browser settings. If you block essential cookies you will not be able to sign in.' ),
		),
	),
);
