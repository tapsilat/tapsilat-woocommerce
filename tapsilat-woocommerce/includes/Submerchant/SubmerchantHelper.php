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
        $this->tapsilatAPI = \Tapsilat\WooCommerce\ApiClient::make(is_array($this->settings) ? $this->settings : []);
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
            return $this->tapsilatAPI->listSubmerchants();
        } catch (Exception $e) {
            $this->debug_log('List submerchants error: ' . $e->getMessage());
            return false;
        }
    }
}
