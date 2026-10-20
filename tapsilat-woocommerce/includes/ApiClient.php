<?php

namespace Tapsilat\WooCommerce;

use Tapsilat\TapsilatAPI;

/**
 * Builds the Tapsilat API client from the gateway settings.
 */
class ApiClient
{
    const PRODUCTION_URL = 'https://panel.tapsilat.com/api/v1';
    const DEVELOPMENT_URL = 'https://panel.tapsilat.dev/api/v1';

    /**
     * @param array|null $settings The gateway settings; read from the options when null
     * @return TapsilatAPI|null Null when no API token is configured
     */
    public static function make($settings = null)
    {
        $settings = is_array($settings) ? $settings : get_option('woocommerce_tapsilat_settings', []);
        if (empty($settings['Token'])) {
            return null;
        }

        return new TapsilatAPI($settings['Token'], 10, self::baseUrl($settings));
    }

    /**
     * The API base URL, ending in /api/v1, for the configured environment.
     *
     * @param array $settings
     * @return string
     */
    public static function baseUrl($settings)
    {
        $environment = isset($settings['API']) ? $settings['API'] : 'production';
        if ($environment !== 'custom') {
            return self::PRODUCTION_URL;
        }
        if (empty($settings['custom_api_url'])) {
            return self::DEVELOPMENT_URL;
        }

        $url = rtrim($settings['custom_api_url'], '/');
        // Accept the URL with or without the /api/v1 (or /api/v1/accounting) suffix.
        $url = preg_replace('#/api/v1(/accounting)?$#', '', $url);

        return $url . '/api/v1';
    }
}
