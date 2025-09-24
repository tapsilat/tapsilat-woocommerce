<?php

namespace Tapsilat\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use Automattic\WooCommerce\Utilities\FeaturesUtil;

/**
 * Class BlocksSupport
 * 
 * Handles WooCommerce Blocks (Gutenberg Checkout) integration for Tapsilat
 */
class BlocksSupport
{
    /**
     * Initialize blocks support
     */
    public static function init()
    {
        add_action('woocommerce_blocks_loaded', [self::class, 'woocommerce_blocks_support']);
        add_action('before_woocommerce_init', [self::class, 'declare_compatibility']);
    }

    /**
     * Register Tapsilat with WooCommerce Blocks
     */
    public static function woocommerce_blocks_support()
    {
        if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
            return;
        }

        add_action(
            'woocommerce_blocks_payment_method_type_registration',
            function (PaymentMethodRegistry $payment_method_registry) {
                // Register Tapsilat Blocks payment method
                $payment_method_registry->register(new BlocksCheckoutMethod());
            }
        );
    }

    /**
     * Declare compatibility with WooCommerce features
     */
    public static function declare_compatibility()
    {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            FeaturesUtil::declare_compatibility(
                'cart_checkout_blocks',
                __FILE__,
                true
            );
        }
    }
}