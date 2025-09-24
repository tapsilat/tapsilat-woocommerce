# Tapsilat WooCommerce Plugin

Modern payment gateway plugin for WooCommerce that integrates with Tapsilat payment system.

**Version:** 2025.09.24.1

## Features

- Secure payment processing via Tapsilat
- Support for WooCommerce Blocks (Gutenberg checkout)
- Multiple payment form display modes (iframe, redirect, popup)
- Automatic order status management
- Webhook support for real-time payment updates
- Compatible with WooCommerce HPOS (High-Performance Order Storage)

## Installation

1. Upload the plugin files to `/wp-content/plugins/tapsilat-woocommerce` directory
2. Activate the plugin through the WordPress admin panel
3. Go to WooCommerce Settings > Payments > Tapsilat
4. Configure your Tapsilat API credentials
5. Enable the payment method

## Configuration

### Required Settings

- **Token**: Your Tapsilat API token
- **API Environment**: Choose between Production or Custom
- **Currency**: Select your preferred currency (TRY, USD, EUR)

### How to get API Key?

You can get API Key from [Tapsilat](https://acquiring.tapsilat.com) website.

### Webhook URLs

Configure these URLs in your Tapsilat merchant panel:

- Success URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-success`
- Failure URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-failure`
- Callback URL: `https://yoursite.com/wp-json/tapsilat/v1/webhook/payment-callback`

## Requirements

- WordPress 5.0 or higher
- WooCommerce 8.0 or higher
- PHP 7.4 or higher
- Tapsilat merchant account

## Support

For technical support, please contact Tapsilat support team.
