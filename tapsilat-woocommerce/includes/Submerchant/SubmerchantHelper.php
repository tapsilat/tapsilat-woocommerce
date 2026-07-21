<?php

namespace Tapsilat\WooCommerce\Submerchant;

use Tapsilat\TapsilatAPI;
use Exception;

class SubmerchantHelper
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
            $logger->debug($message, array('source' => 'tapsilat-submerchant'));
        }
    }

    public function createSubmerchant($dto)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->createSubmerchant($dto);
        } catch (Exception $e) {
            $this->debug_log('Create submerchant error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSubmerchant($id)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSubmerchant($id);
        } catch (Exception $e) {
            $this->debug_log('Get submerchant error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateSubmerchant($id, $dto)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->updateSubmerchant($id, $dto);
        } catch (Exception $e) {
            $this->debug_log('Update submerchant error: ' . $e->getMessage());
            return false;
        }
    }

    public function deleteSubmerchant($id)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->deleteSubmerchant($id);
        } catch (Exception $e) {
            $this->debug_log('Delete submerchant error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSubmerchantsList()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSubmerchantsList();
        } catch (Exception $e) {
            $this->debug_log('List submerchants error: ' . $e->getMessage());
            return false;
        }
    }
}
