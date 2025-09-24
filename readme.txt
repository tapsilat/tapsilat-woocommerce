=== Tapsilat WooCommerce Plugin ===
Contributors: wordpress@tapsilat.dev
Tags: woocommerce, tapsilat, payment, gateway, payments, commerce, blocks
Requires at least: 5.0
Tested up to: 6.0
Stable tag: 2025.09.24.1
Requires PHP: 7.4
License: AGPL
License URI: https://github.com/tapsilat/tapsilat-woocommerce/blob/main/LICENSE.md

Modern payment gateway plugin for WooCommerce that integrates with Tapsilat payment system.

== Description ==

Tapsilat WooCommerce Plugin provides a secure and modern payment gateway integration for your WooCommerce store. This plugin connects your store with Tapsilat's no-code, cloud-based or on-premise, end-to-end fintech platform - built for speed and scale around your unique business needs.

**Key Features:**

* Secure payment processing via Tapsilat
* Support for WooCommerce Blocks (Gutenberg checkout)
* Multiple payment form display modes (iframe, redirect, popup)
* Automatic order status management
* Webhook support for real-time payment updates
* Compatible with WooCommerce HPOS (High-Performance Order Storage)
* Multi-currency support (TRY, USD, EUR)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/tapsilat-woocommerce` directory
2. Activate the plugin through the WordPress admin panel
3. Go to WooCommerce Settings > Payments > Tapsilat
4. Configure your Tapsilat API credentials
5. Enable the payment method

== Configuration ==

**Required Settings:**
* Token: Your Tapsilat API token
* API Environment: Choose between Production or Custom
* Currency: Select your preferred currency (TRY, USD, EUR)

**Webhook URLs:**
Configure these URLs in your Tapsilat merchant panel:
* Success URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-success`
* Failure URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-failure`
* Callback URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-callback`

== Requirements ==

* WordPress 5.0 or higher
* WooCommerce 8.0 or higher
* PHP 7.4 or higher
* Tapsilat merchant account

== Frequently Asked Questions ==

= How to get API Key? =

You can get API Key from [Tapsilat](https://acquiring.tapsilat.com) website by creating a merchant account.

= Is this plugin compatible with WooCommerce Blocks? =

Yes, this plugin fully supports WooCommerce Blocks (Gutenberg checkout) for modern checkout experiences.

= What currencies are supported? =

The plugin supports TRY (Turkish Lira), USD (US Dollar), and EUR (Euro).

= How do webhooks work? =

Webhooks provide real-time updates about payment status. Configure the webhook URLs in your Tapsilat merchant panel to receive automatic notifications about successful, failed, or pending payments.

== Screenshots ==

1. Plugin settings page in WooCommerce admin
2. Tapsilat payment method selection on checkout
3. Payment form integration options

== Changelog ==

= 2025.09.24.1 =
* Updated version numbering system
* Secure payment processing via Tapsilat
* WooCommerce Blocks support
* Multiple payment form display modes
* Webhook integration
* HPOS compatibility

= 1.0 =
* Initial release
