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
        $token = isset($this->settings['Token']) ? $this->settings['Token'] : '';
        $apiUrl = $this->getApiUrl();
        $this->tapsilatAPI = new TapsilatAPI($token, 10, $apiUrl);
    }

    private function getApiUrl()
    {
        $apiEnvironment = isset($this->settings['API']) ? $this->settings['API'] : 'production';
        if ($apiEnvironment === 'custom') {
            if (!empty($this->settings['custom_api_url'])) {
                $customUrl = rtrim($this->settings['custom_api_url'], '/');
                $customUrl = preg_replace('/\/api\/v1$/', '', $customUrl);
                return $customUrl . '/api/v1/';
            }
            return 'https://panel.tapsilat.dev/api/v1/';
        }
        return 'https://panel.tapsilat.com/api/v1/';
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
            return $this->tapsilatAPI->getOrderStatuses();
        } catch (Exception $e) {
            $this->debug_log('Get order statuses error: ' . $e->getMessage());
            return false;
        }
    }

    public function getBasketItemTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getBasketItemTypes();
        } catch (Exception $e) {
            $this->debug_log('Get basket item types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getErrorCodes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getErrorCodes();
        } catch (Exception $e) {
            $this->debug_log('Get error codes error: ' . $e->getMessage());
            return false;
        }
    }

    public function getPaymentTermStatuses()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getPaymentTermStatuses();
        } catch (Exception $e) {
            $this->debug_log('Get payment term statuses error: ' . $e->getMessage());
            return false;
        }
    }

    public function getProductTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getProductTypes();
        } catch (Exception $e) {
            $this->debug_log('Get product types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getShortcutTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getShortcutTypes();
        } catch (Exception $e) {
            $this->debug_log('Get shortcut types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionPaymentTypes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getTransactionPaymentTypes();
        } catch (Exception $e) {
            $this->debug_log('Get transaction payment types error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionPurposes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getTransactionPurposes();
        } catch (Exception $e) {
            $this->debug_log('Get transaction purposes error: ' . $e->getMessage());
            return false;
        }
    }

    public function getTransactionStatuses()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getTransactionStatuses();
        } catch (Exception $e) {
            $this->debug_log('Get transaction statuses error: ' . $e->getMessage());
            return false;
        }
    }
}
