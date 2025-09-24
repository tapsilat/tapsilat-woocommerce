<?php

namespace Tapsilat\WooCommerce\Blocks;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Class BlocksCheckoutMethod
 *
 * @extends AbstractPaymentMethodType
 */
class BlocksCheckoutMethod extends AbstractPaymentMethodType
{
    protected $name = 'tapsilat';

    public function __construct()
    {
        // Constructor'da hiçbir şey yapmaya gerek yok
    }

    public function initialize(): void
    {
        $this->settings = get_option('woocommerce_tapsilat_settings', []);
    }

    public function is_active(): bool
    {
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        $available = isset($payment_gateways['tapsilat']) && $payment_gateways['tapsilat']->is_available();
        
        // Debug: Log blocks availability
        error_log('Tapsilat Blocks: Checking availability. Gateway exists: ' . (isset($payment_gateways['tapsilat']) ? 'YES' : 'NO') . ', Available: ' . ($available ? 'YES' : 'NO'));
        
        return $available;
    }

    public function get_payment_method_script_handles(): array
    {
        $dependencies = [];
        $version = time();

        $path = plugin_dir_path(__FILE__) . '../../assets/js/blocks/tapsilat-blocks.asset.php';

        if (file_exists($path)) {
            $asset = require $path;
            $version = filemtime(plugin_dir_path(__FILE__) . '../../assets/js/blocks/tapsilat-blocks.js');
            $dependencies = is_array($asset['dependencies']) ? $asset['dependencies'] : [];
        }

        wp_register_script(
            'wc-tapsilat-blocks-integration',
            plugin_dir_url(__FILE__) . '../../assets/js/blocks/tapsilat-blocks.js',
            $dependencies,
            $version,
            true
        );

        return ['wc-tapsilat-blocks-integration'];
    }

    public function get_payment_method_data(): array
    {
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        $gateway = isset($payment_gateways[$this->name]) ? $payment_gateways[$this->name] : null;
        
        // Get icon URL
        $icon_url = $this->get_icon_url();
        
        return [
            'title' => $gateway ? $gateway->get_title() : __('Payment by Tapsilat', 'tapsilat-woocommerce'),
            'description' => $gateway ? $gateway->get_description() : __('Pay securely using your credit/debit card or alternative payment methods.', 'tapsilat-woocommerce'),
            'supports' => $gateway ? array_filter($gateway->supports, [$gateway, 'supports']) : [],
            'icon' => $icon_url, // Add icon URL to the data
        ];
    }

    /**
     * Get setting from the gateway.
     *
     * @param string $key Setting key.
     * @param mixed $default Default value.
     * @return mixed
     */
    public function get_setting($key, $default = '')
    {
        if (empty($this->settings)) {
            $this->initialize();
        }
        return isset($this->settings[$key]) ? $this->settings[$key] : $default;
    }

    /**
     * Get icon URL for the payment method
     *
     * @return string
     */
    private function get_icon_url(): string
    {
        // First check if there's a custom icon set in gateway settings
        $payment_gateways = WC()->payment_gateways->payment_gateways();
        $gateway = isset($payment_gateways[$this->name]) ? $payment_gateways[$this->name] : null;
        
        if ($gateway && !empty($gateway->icon)) {
            return $gateway->icon;
        }

        // Check if there's a logo setting from WordPress media library
        $logo_attachment_id = $this->get_setting('tapsilat_logo');
        if ($logo_attachment_id) {
            $logo_url = wp_get_attachment_url($logo_attachment_id);
            if ($logo_url) {
                return $logo_url;
            }
        }

        // Default to plugin's logo file
        $default_logo_path = plugin_dir_url(__FILE__) . '../../assets/logo.png';
        return $default_logo_path;
    }
}