<?php

namespace Tapsilat\WooCommerce\Organization;

use Tapsilat\TapsilatAPI;
use Exception;

class OrganizationHelper
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
            $logger->debug($message, array('source' => 'tapsilat-organization'));
        }
    }

    public function getSettings()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationSettings();
        } catch (Exception $e) {
            $this->debug_log('Get organization settings error: ' . $e->getMessage());
            return false;
        }
    }

    public function getCallback()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationCallback();
        } catch (Exception $e) {
            $this->debug_log('Get organization callback error: ' . $e->getMessage());
            return false;
        }
    }

    public function updateCallback($callbackData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->updateOrganizationCallback($callbackData);
        } catch (Exception $e) {
            $this->debug_log('Update organization callback error: ' . $e->getMessage());
            return false;
        }
    }

    public function createBusiness($businessData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->createBusiness($businessData);
        } catch (Exception $e) {
            $this->debug_log('Create business error: ' . $e->getMessage());
            return false;
        }
    }

    public function getCurrencies()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getCurrencies();
        } catch (Exception $e) {
            $this->debug_log('Get currencies error: ' . $e->getMessage());
            return false;
        }
    }

    public function getCurrencyPresets()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getCurrencyPresets();
        } catch (Exception $e) {
            $this->debug_log('Get currency presets error: ' . $e->getMessage());
            return false;
        }
    }

    public function getLimitUser($userId)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getLimitUser($userId);
        } catch (Exception $e) {
            $this->debug_log('Get limit user error: ' . $e->getMessage());
            return false;
        }
    }

    public function setLimitUser($limitData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->setLimitUser($limitData);
        } catch (Exception $e) {
            $this->debug_log('Set limit user error: ' . $e->getMessage());
            return false;
        }
    }

    public function getLimits()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getLimits();
        } catch (Exception $e) {
            $this->debug_log('Get limits error: ' . $e->getMessage());
            return false;
        }
    }

    public function getVposList()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getVposList();
        } catch (Exception $e) {
            $this->debug_log('Get vpos list error: ' . $e->getMessage());
            return false;
        }
    }

    public function getMetadata()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getMetadata();
        } catch (Exception $e) {
            $this->debug_log('Get metadata error: ' . $e->getMessage());
            return false;
        }
    }

    public function getScopes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getScopes();
        } catch (Exception $e) {
            $this->debug_log('Get scopes error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuborganizationList()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSuborganizationList();
        } catch (Exception $e) {
            $this->debug_log('Get suborganization list error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuborganizationDetails($id)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSuborganizationDetails($id);
        } catch (Exception $e) {
            $this->debug_log('Get suborganization details error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuborgSubmerchants($id)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getSuborgSubmerchants($id);
        } catch (Exception $e) {
            $this->debug_log('Get suborg submerchants error: ' . $e->getMessage());
            return false;
        }
    }

    public function createUser($userData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->createUser($userData);
        } catch (Exception $e) {
            $this->debug_log('Create user error: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyUser($verifyData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->verifyUser($verifyData);
        } catch (Exception $e) {
            $this->debug_log('Verify user error: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyUserMobile($verifyData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->verifyUserMobile($verifyData);
        } catch (Exception $e) {
            $this->debug_log('Verify user mobile error: ' . $e->getMessage());
            return false;
        }
    }

    public function createUserToken($tokenReq)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->createOrgUserToken($tokenReq);
        } catch (Exception $e) {
            $this->debug_log('Create user token error: ' . $e->getMessage());
            return false;
        }
    }
}
