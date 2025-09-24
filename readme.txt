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
* Automatic order status management with configurable cron job
* Webhook support for real-time payment updates
* Compatible with WooCommerce HPOS (High-Performance Order Storage)
* 3D Secure authentication support
* Multi-currency support (TRY, USD, EUR)
* Customizable payment form design and branding
* Advanced order status monitoring and automatic updates
* Custom logo support for payment method
* Flexible API environment configuration (Production/Custom)

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

**Advanced Configuration Options:**
* 3D Secure: Enable/disable 3D Secure authentication for enhanced security
* Payment Form Display: Choose between iframe (embedded), redirect, or popup modes
* Order Status Check Frequency: Configure automatic cron job interval (5, 10, 15, or 30 minutes)
* Design Customization: Customize colors for input fields, labels, buttons, and panels
* Custom Logo: Upload custom logos for both payment method and checkout page
* Custom Metadata: Add additional data to be sent with orders (JSON format)

**Webhook URLs:**
Configure these URLs in your Tapsilat merchant panel:
* Success URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-success`
* Failure URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-failure`
* Callback URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-callback`

**Automatic Order Status Updates:**
The plugin includes an intelligent cron job system that automatically monitors and updates order statuses:
* Configurable Intervals: Set automatic checks every 5, 10, 15, or 30 minutes
* Smart Processing: Only checks orders that need status updates to optimize performance
* Recent Order Skip: Avoids checking recently modified orders to prevent conflicts

**Supported Status Mappings:**
* SUCCESS/COMPLETED → Processing (Payment completed, order processed)
* FAILED/CANCELLED/EXPIRED → Failed (Order marked as failed)
* PENDING/WAITING → On Hold (Order waiting for payment)

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

1. WordPress Plugin Management Page - View and manage the Tapsilat WooCommerce plugin from the WordPress admin panel
2. WooCommerce Payment Providers - Tapsilat payment gateway listed among WooCommerce payment methods
3. Tapsilat Settings Management Page - Comprehensive configuration options for API credentials, payment settings, and customization
4. Checkout Page with Tapsilat Payment Selection - Customer-facing checkout page showing Tapsilat as a payment option
5. Payment Processing Page - Secure payment form with card details and 3D Secure authentication

== Changelog ==

= 2025.09.24.1 =
* Advanced cron job system for automatic order status monitoring
* Configurable check intervals (5, 10, 15, 30 minutes)
* Comprehensive design customization options
* Custom logo support for payment methods
* 3D Secure authentication toggle
* Smart order status mapping and updates
* System status monitoring dashboard
* Performance optimizations for large order volumes
* Enhanced admin interface with dynamic field visibility
* Better error handling and logging
* Improved API environment configuration
* Token visibility toggle for security
* Webhook URL display for easy configuration

= 1.0 =
* Initial release
