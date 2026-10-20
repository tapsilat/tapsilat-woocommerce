<?php

namespace Tapsilat\WooCommerce\System;

use Tapsilat\TapsilatAPI;
use Exception;

class SystemHelper
{
    private $tapsilatAPI;
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('woocommerce_tapsilat_settings');
        $this->init_api_client();
    }

    private function init_api_client()
    {
        $this->tapsilatAPI = \Tapsilat\WooCommerce\ApiClient::make(is_array($this->settings) ? $this->settings : []);
    }

    private function debug_log($message)
    {
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('wc_get_logger')) {
            $logger = wc_get_logger();
            $logger->debug($message, array('source' => 'tapsilat-system'));
        }
    }

    public function getOrderStatuses()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemOrderStatuses();
        } catch (Exception $e) {
            $this->debug_log('Get order statuses error: ' . $e->getMessage());
            return false;
        }
    }

    public function getBasketItemTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemBasketItemTypes();
        } catch (Exception $e) {
            $this->debug_log('Get basket item types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getErrorCodes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemErrorCodes();
        } catch (Exception $e) {
            $this->debug_log('Get error codes error: ' . $e->getMessage());
            return false;
        }
    }

    public function getPaymentTermStatuses()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemPaymentTermStatuses();
        } catch (Exception $e) {
            $this->debug_log('Get payment term statuses error: ' . $e->getMessage());
            return false;
        }
    }

    public function getProductTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemProductTypes();
        } catch (Exception $e) {
            $this->debug_log('Get product types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getShortcutTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemShortcutTypes();
        } catch (Exception $e) {
            $this->debug_log('Get shortcut types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionPaymentTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemTransactionPaymentTypes();
        } catch (Exception $e) {
            $this->debug_log('Get transaction payment types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionPurposes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemTransactionPurposes();
        } catch (Exception $e) {
            $this->debug_log('Get transaction purposes error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionStatuses()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSystemTransactionStatuses();
        } catch (Exception $e) {
            $this->debug_log('Get transaction statuses error: ' . $e->getMessage());
            return false;
        }
    }
}
