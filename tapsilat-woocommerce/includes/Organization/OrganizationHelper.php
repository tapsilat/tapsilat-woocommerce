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
        $this->tapsilatAPI = \Tapsilat\WooCommerce\ApiClient::make(is_array($this->settings) ? $this->settings : []);
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
            return $this->tapsilatAPI->createOrganizationBusiness($businessData);
        } catch (Exception $e) {
            $this->debug_log('Create business error: ' . $e->getMessage());
            return false;
        }
    }

    public function getCurrencies()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationCurrencies();
        } catch (Exception $e) {
            $this->debug_log('Get currencies error: ' . $e->getMessage());
            return false;
        }
    }

    public function getCurrencyPresets()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationCurrencyPresets();
        } catch (Exception $e) {
            $this->debug_log('Get currency presets error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @deprecated The panel has no /organization/limit/user route, so this always fails.
     */
    public function getLimitUser($userId)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationLimitUser(new \Tapsilat\Models\GetUserLimitRequest($userId));
        } catch (Exception $e) {
            $this->debug_log('Get limit user error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @deprecated The panel has no /organization/limit/user route, so this always fails.
     */
    public function setLimitUser($limitData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->setOrganizationLimitUser($limitData);
        } catch (Exception $e) {
            $this->debug_log('Set limit user error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * @deprecated The panel has no /organization/limits route.
     */
    public function getLimits()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationLimits();
        } catch (Exception $e) {
            $this->debug_log('Get limits error: ' . $e->getMessage());
            return false;
        }
    }

    public function getVposList()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->listVpos();
        } catch (Exception $e) {
            $this->debug_log('Get vpos list error: ' . $e->getMessage());
            return false;
        }
    }

    public function getMetadata($name)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationMeta($name);
        } catch (Exception $e) {
            $this->debug_log('Get metadata error: ' . $e->getMessage());
            return false;
        }
    }

    public function getScopes()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationScopes();
        } catch (Exception $e) {
            $this->debug_log('Get scopes error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuborganizationList()
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationSuborganizations();
        } catch (Exception $e) {
            $this->debug_log('Get suborganization list error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuborganizationDetails($id)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationSuborganizationDetails($id);
        } catch (Exception $e) {
            $this->debug_log('Get suborganization details error: ' . $e->getMessage());
            return false;
        }
    }

    public function getSuborgSubmerchants($id)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->getOrganizationSuborganizationSubmerchants($id);
        } catch (Exception $e) {
            $this->debug_log('Get suborg submerchants error: ' . $e->getMessage());
            return false;
        }
    }

    public function createUser($userData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->createOrganizationUser($userData);
        } catch (Exception $e) {
            $this->debug_log('Create user error: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyUser($verifyData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->verifyOrganizationUser($verifyData);
        } catch (Exception $e) {
            $this->debug_log('Verify user error: ' . $e->getMessage());
            return false;
        }
    }

    public function verifyUserMobile($verifyData)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->verifyOrganizationUserMobile($verifyData);
        } catch (Exception $e) {
            $this->debug_log('Verify user mobile error: ' . $e->getMessage());
            return false;
        }
    }

    public function createUserToken($tokenReq)
    {
        if (!$this->tapsilatAPI) return false;
        try {
            return $this->tapsilatAPI->createOrganizationUserToken($tokenReq);
        } catch (Exception $e) {
            $this->debug_log('Create user token error: ' . $e->getMessage());
            return false;
        }
    }
}
