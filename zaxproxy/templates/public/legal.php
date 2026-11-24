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
$r_fee   = zp_money( zp_get( 'reseller_fee', 45000 ) );
$r_month = zp_money( zp_get( 'reseller_monthly', 15000 ) );
$r_dom   = zp_money( zp_get( 'reseller_domain_fee', 12000 ) );

return array(
	'terms'          => array(
		'title'    => 'Terms and Conditions',
		'updated'  => $updated,
		'intro'    => "These Terms and Conditions are the agreement between you and {$brand} ({$site}). They explain what we provide, what we expect from you, and what happens when things go wrong. By creating an account, adding funds or buying a service you agree to them. If you do not agree, please do not use the service.",
		'sections' => array(
			'1. Definitions'                       => array(
				array(
					"\"{$brand}\", \"we\", \"us\" and \"our\" mean the operator of {$site}.",
					'"You" and "customer" mean the person or business that holds an account.',
					'"Service" means the proxy servers, dashboard, wallet, reseller tools and support we provide.',
					'"Proxy" means an IP address and port we rent to you for a fixed period.',
					'"Wallet" means the prepaid balance on your account.',
					'"Reseller" means a customer who has unlocked a reseller account and runs a store on our platform.',
				),
			),
			'2. Who can use the service'           => array(
				'You must be at least 18 years old and legally able to enter into a contract. If you use the service for a business, you confirm that you have authority to bind that business.',
				'You must give accurate information when you register and keep it up to date. One person or business may not open multiple accounts to get around limits, suspensions or these terms.',
			),
			'3. Your account and security'         => array(
				'You are responsible for keeping your password and proxy credentials confidential, and for everything done through your account and your proxies, whether or not you authorised it.',
				'Tell us straight away if you believe someone else has accessed your account. We may ask you to verify your identity before making account changes.',
			),
			'4. The service we provide'            => array(
				"{$brand} rents access to IPv4, IPv6, ISP and mobile proxies for periods of 30, 60 or 90 days. Proxies are sourced from upstream network providers and delivered to your dashboard.",
				'The available locations, proxy types, periods and prices may change at any time. A change never affects a proxy you have already paid for during its current rental period.',
			),
			'5. Wallet and payments'               => array(
				"All services are paid for from your wallet, which is held in {$cur}. You fund your wallet by bank transfer or any other method shown on the Wallet page.",
				'For bank transfers, your wallet is credited only after we have confirmed that the money has arrived. Submitting a false or duplicate payment claim is a serious breach of these terms and may lead to account closure.',
				"Wallet funds can only be used to buy and extend services on {$brand}. They are not a bank deposit, do not earn interest and cannot be transferred to another account.",
				'Prices are shown before you confirm each order. By confirming, you authorise us to deduct the price from your wallet.',
			),
			'6. Delivery and rental periods'       => array(
				'Most orders are delivered within minutes. Some take longer because of the upstream provider; such an order shows as "Processing" until the proxies appear in your dashboard.',
				'The rental period starts when the proxy is issued, not when you first use it, and ends on the expiry date shown in your dashboard.',
				'You may extend a proxy at any time before it expires. A proxy that is not extended is released at expiry and cannot be recovered; we cannot guarantee that you will get the same IP address again.',
			),
			'7. Acceptable use'                    => array(
				'You must follow our Acceptable Use Policy at all times. It forms part of these terms. We may suspend or terminate any proxy or account that breaches it, without notice and without refund.',
			),
			'8. Reseller accounts'                 => array(
				"A reseller account is unlocked by paying a one-time activation fee (currently {$r_fee}), which includes the first 30 days. After that a monthly fee (currently {$r_month}) is deducted from the reseller's wallet automatically every 30 days.",
				'If the monthly fee cannot be deducted, the reseller account is paused: the store goes offline, its customers cannot buy, and the reseller is charged regular prices until the fee is paid.',
				"When a reseller's customer places an order, the reseller price of that order is deducted from the reseller's wallet. If the wallet cannot cover it, the order is refused.",
				"An optional branded .cloud domain costs {$r_dom}. We register and connect it; if the chosen name is not available the fee is refunded.",
				"Resellers are independent businesses. They are responsible for their own customers, including collecting payment, crediting wallets, answering support requests and making sure their customers follow our Acceptable Use Policy. A reseller's customers have no contract with {$brand}.",
				'We may suspend a reseller store that is used in breach of these terms. Activation, monthly and domain fees are not refundable once paid, except where stated.',
			),
			'9. Availability and performance'      => array(
				'We work to keep the service running without interruption, but we do not guarantee that it will always be available, or that a proxy will work with every website, application or service.',
				'Third parties may block or limit particular IP addresses or whole networks. This is outside our control and is not a fault in the service.',
				'We may carry out maintenance or make changes that briefly interrupt the service. We will try to do this at quiet times.',
			),
			'10. Refunds'                          => array(
				'Refunds are handled under our Refund Policy, which forms part of these terms.',
			),
			'11. Suspension and termination'       => array(
				'You may stop using the service at any time. Proxies you have paid for remain active until they expire.',
				'We may suspend or close an account, with or without notice, if it breaches these terms, is used for fraud or abuse, puts our network or other customers at risk, or if we are required to by law.',
				'If we close an account because of a breach, active services end immediately and no refund is due. If we close an account for any other reason, we will refund the unused wallet balance.',
			),
			'12. Intellectual property'            => array(
				"The {$brand} name, logo, website, dashboard and software belong to us or our licensors. You may use them only to access the service. You may not copy, resell or reverse engineer them, except as allowed under the reseller program.",
			),
			'13. Disclaimers'                      => array(
				'The service is provided "as is" and "as available". To the fullest extent the law allows, we give no warranties, whether express or implied, including warranties of fitness for a particular purpose.',
				'You are responsible for how you use the service and for making sure your use is lawful where you are and where your traffic is sent.',
			),
			'14. Limitation of liability'          => array(
				"To the fullest extent permitted by law, {$brand} is not liable for indirect, incidental or consequential losses, or for lost profits, revenue, data, accounts or business opportunities, arising from the use of or inability to use the service.",
				'Our total liability for any claim is limited to the amount you paid us for the affected service in the 30 days before the claim arose.',
				'Nothing in these terms limits liability that cannot be limited by law.',
			),
			'15. Indemnity'                        => array(
				"You agree to compensate {$brand} for any loss, claim or cost (including reasonable legal fees) that results from your breach of these terms or your misuse of the service, including misuse by anyone using your account or proxies.",
			),
			'16. Changes to these terms'           => array(
				'We may update these terms from time to time. The latest version is always on this page with the date it was last updated. If a change is significant we will tell you by email or in your dashboard. Continuing to use the service after a change means you accept the new terms.',
			),
			'17. Governing law'                    => array(
				'These terms are governed by the laws of the Federal Republic of Nigeria. We will always try to settle a complaint with you directly first. If that is not possible, the courts of Nigeria have jurisdiction.',
			),
			'18. Contact'                          => array( "Questions about these terms can be sent to {$email}." ),
		),
	),
	'privacy'        => array(
		'title'    => 'Privacy Policy',
		'updated'  => $updated,
		'intro'    => "Your privacy matters to us. This policy explains what personal data {$brand} collects, why we collect it, who we share it with, how long we keep it, and the rights you have. It applies to {$site} and to our dashboard and app.",
		'sections' => array(
			'1. Who is responsible for your data' => array(
				"{$brand} is the data controller for the personal data described in this policy. You can reach us about any privacy matter at {$email}.",
			),
			'2. Information we collect'           => array(
				array(
					'Account details: your name, email address and an encrypted (hashed) password.',
					'Order records: the proxies you buy and extend, the country, period, quantity and intended use you select, and the price paid.',
					'Wallet records: top-ups, payment references, the name on the account you paid from, and your transaction history.',
					'Support messages: tickets, contact-form messages and anything you send us on Telegram or by email.',
					'Technical data: your IP address, browser and device type, and basic server logs needed to run and secure the site.',
					'An IP address you choose to whitelist for proxy authorisation.',
					'Reseller details: if you open a store, your brand name, store link, domain, bank details and Telegram username that you ask us to show to your customers.',
				),
			),
			'3. Information we do not collect'    => array(
				'We do not see or store your bank card numbers. We do not ask for identity documents unless we are investigating fraud or are required to by law.',
				'We do not inspect, record or sell the content of the traffic you send through your proxies. Our upstream providers may keep limited technical logs (such as connection times and data volumes) to operate their networks and handle abuse reports.',
			),
			'4. How we use your information'      => array(
				array(
					'To create and manage your account and deliver the services you order.',
					'To confirm payments, credit your wallet and keep accurate financial records.',
					'To answer support requests and send service messages such as password resets and order updates.',
					'To detect and prevent fraud, abuse and breaches of our Acceptable Use Policy.',
					'To improve the service, for example by understanding which locations are most in demand.',
					'To meet our legal and tax obligations.',
				),
			),
			'5. Our legal basis'                  => array(
				'We process your data because it is necessary to perform our contract with you, because we have a legitimate interest in running a secure and reliable service, because the law requires it, or, where we ask for it, because you have given consent.',
			),
			'6. Who we share it with'             => array(
				'We do not sell your personal data. We share it only where needed to run the service:',
				array(
					'Our upstream proxy provider: the order details (type, country, quantity, period, intended use) and a whitelisted IP if you supply one. They do not receive your name or email.',
					'Payment providers: to process and confirm payments.',
					'Hosting, email and messaging providers: to run the website and deliver messages.',
					'A reseller: if you registered through a reseller store, that reseller can see your name, email, wallet balance, orders and support tickets in order to serve you.',
					'Authorities: where we are legally required to, or to protect our rights, our customers or the public.',
				),
			),
			'7. International transfers'          => array(
				'Some of our providers are located outside Nigeria. Where your data is transferred abroad we take reasonable steps to make sure it remains protected.',
			),
			'8. How long we keep it'              => array(
				'We keep account, order and transaction records for as long as your account is open and for the period required by tax and accounting rules afterwards. Support messages are kept while they are useful for helping you. Server logs are kept for a short period for security.',
				'If you ask us to delete your account, we will remove or anonymise your personal data, except records we must keep by law.',
			),
			'9. Security'                         => array(
				'We use encrypted connections (HTTPS), hashed passwords, access controls and protection against automated attacks. No online service can be completely secure, so please use a strong, unique password and keep your proxy credentials private.',
			),
			'10. Your rights'                     => array(
				'Under the Nigeria Data Protection Act and similar laws you have the right to:',
				array(
					'Ask for a copy of the personal data we hold about you.',
					'Ask us to correct data that is wrong or incomplete.',
					'Ask us to delete your data, where we have no legal reason to keep it.',
					'Object to or restrict certain uses of your data.',
					'Withdraw consent where we rely on it.',
					'Complain to the Nigeria Data Protection Commission if you are unhappy with how we handle your data.',
				),
				"To use any of these rights, contact {$email}. We will respond within a reasonable time and may need to confirm your identity first.",
			),
			'11. Cookies'                         => array( 'We use a small number of essential cookies. See our Cookie Policy for details.' ),
			'12. Children'                        => array( 'The service is not intended for anyone under 18, and we do not knowingly collect data from children.' ),
			'13. Changes to this policy'          => array( 'We will post any changes on this page and update the date above. If a change is significant we will let you know by email or in your dashboard.' ),
		),
	),
	'refund-policy'  => array(
		'title'    => 'Refund Policy',
		'updated'  => $updated,
		'intro'    => 'We want you to be happy with every proxy you buy. This policy explains when you can get your money back, when you cannot, and how to ask. Please read it before you place an order.',
		'sections' => array(
			'1. Orders we cannot deliver'           => array(
				'If an order fails or the provider cannot supply it, the full amount returns to your wallet automatically, usually at once. You do not need to contact us.',
			),
			'2. Proxies that do not work'           => array(
				'If a proxy does not connect at all, open a support ticket within 24 hours of delivery and include the proxy IP or order number. We will test it and either replace it or refund that proxy to your wallet.',
				'Please check your setup first: the IP, port, username and password must be entered exactly as shown in your dashboard, and the protocol (HTTP or SOCKS5) must match the port.',
			),
			'3. When a refund is not available'     => array(
				array(
					'The proxy works, but a particular website, app or service blocks or limits it.',
					'You no longer need the proxy, or you ordered the wrong type, country, quantity or period.',
					'More than 24 hours have passed since delivery.',
					'The proxy was suspended because of a breach of our Acceptable Use Policy.',
					'Unused time on a rental, including when you stop using a proxy early.',
					'You did not extend a proxy before it expired and lost the IP address.',
				),
			),
			'4. Renewals'                           => array(
				'A renewal is a new purchase of time on a proxy you already have. Renewals are refundable only if the renewal could not be applied, in which case the amount returns to your wallet automatically.',
			),
			'5. Wallet top-ups'                     => array(
				"Refunds for services are credited to your {$brand} wallet so you can use them immediately.",
				'If you want unused wallet funds returned to your bank account, ask through a support ticket. We review each request individually. Approved withdrawals are paid to the account the money came from, less any bank or payment-processor charges, and may take several working days.',
				'Bonus credit, promotional credit and amounts already spent cannot be withdrawn.',
			),
			'6. Reseller fees'                      => array(
				"The reseller activation fee ({$r_fee}) and monthly fee ({$r_month}) are not refundable once paid, because the reseller price and store are available to you immediately.",
				"The branded domain fee ({$r_dom}) is refunded in full if we are unable to register the name you chose. Once a domain has been registered for you it cannot be refunded.",
			),
			'7. Customers of reseller stores'       => array(
				"If you bought through a reseller's store, your payment was made to that reseller. Please contact the reseller for any refund. Orders that fail are still returned to your store wallet automatically.",
			),
			'8. Chargebacks and payment disputes'   => array(
				'Please contact us before disputing a payment with your bank: we can usually resolve the problem faster. Accounts with an open dispute may be suspended until it is settled.',
			),
			'9. How to request a refund'            => array(
				"Open a support ticket from your dashboard, or contact {$email}, with your order number, the proxy IP if relevant, and a short description of the problem. We aim to respond within one business day.",
			),
		),
	),
	'acceptable-use' => array(
		'title'    => 'Acceptable Use Policy',
		'updated'  => $updated,
		'intro'    => "Our proxies are for lawful purposes only. This policy applies to everyone who uses {$brand}, including resellers and their customers. It protects our network, our customers and the wider internet.",
		'sections' => array(
			'1. You must not use the service to' => array(
				array(
					'Break any law or regulation that applies to you or to the system you are connecting to.',
					'Commit or attempt fraud, identity theft, carding, phishing, money laundering or financial scams.',
					'Access accounts, systems or data without permission, including credential stuffing, brute-force attacks and account takeover.',
					'Send spam or bulk unsolicited messages by email, SMS, social media or any other channel.',
					'Launch or take part in denial-of-service attacks, or scan and exploit networks and devices.',
					'Create, store or distribute malware, ransomware or other harmful software.',
					'Access, store or share child sexual abuse material or any content that exploits minors. We report this to the authorities.',
					'Harass, threaten, stalk or defame any person.',
					'Promote terrorism, violence or hatred against any group.',
					'Infringe copyright, trademarks or other intellectual property rights.',
					'Manipulate elections, spread coordinated disinformation or impersonate another person or organisation.',
					'Resell or share access in a way that hides abusive activity.',
				),
			),
			'2. Fair use of the network'         => array(
				'Do not use the service in a way that harms its performance for other customers, attempts to bypass technical limits, or interferes with our systems or those of our providers.',
			),
			'3. Your responsibility'             => array(
				'You are responsible for all traffic sent through your proxies, including traffic sent by anyone you give your credentials to. Many websites have their own terms of use; it is your responsibility to follow them.',
				'Resellers are responsible for making sure their customers follow this policy.',
			),
			'4. Enforcement'                     => array(
				'If we believe this policy has been broken we may, without notice, suspend or terminate the proxies or account involved, withhold refunds, and preserve and share relevant information with law enforcement where the law allows or requires it.',
			),
			'5. Reporting abuse'                 => array(
				"If you believe one of our IP addresses is being misused, contact {$email} with the IP address, the date and time (with time zone), and a description of what happened. We investigate every report.",
			),
		),
	),
	'cookies'        => array(
		'title'    => 'Cookie Policy',
		'updated'  => $updated,
		'intro'    => "{$brand} uses only the cookies and browser storage needed to make the site and app work. This page explains what they are and how to control them.",
		'sections' => array(
			'1. What cookies are'      => array( 'Cookies are small text files a website stores in your browser. Similar technologies, such as local storage, keep small pieces of information on your device.' ),
			'2. What we use'           => array(
				array(
					'Login cookies: keep you signed in to your dashboard.',
					'Security cookies: protect forms and your session against misuse.',
					'Message cookie: shows a confirmation or error after you submit a form, then expires within a minute.',
					'Theme preference: your dark or light mode choice is saved in your browser.',
					'App storage: the installable app keeps its design files and recent pages on your device so it opens and switches screens instantly.',
				),
			),
			'3. What we do not use'    => array( 'We do not use advertising or tracking cookies, and we do not sell browsing data to anyone.' ),
			'4. Third parties'         => array( 'If you pay through a payment processor, it sets its own cookies on its checkout page. Fonts are loaded from Google Fonts. Telegram applies its own policy when you open a chat with us.' ),
			'5. Managing cookies'      => array( 'You can clear or block cookies in your browser settings. If you block essential cookies you will not be able to sign in. Signing out removes your login cookies.' ),
		),
	),
);
