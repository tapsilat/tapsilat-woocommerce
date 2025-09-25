<?php

namespace Tapsilat\WooCommerce\Checkout;

use Tapsilat\TapsilatAPI;
use Tapsilat\Models\BuyerDTO;
use Tapsilat\Models\OrderCreateDTO;
use WC_Order;
use WC_Countries;

class CheckoutProcessor
{
    private $tapsilatAPI;
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('woocommerce_tapsilat_settings', []);
        
        if (!empty($this->settings['Token'])) {
            // Determine API URL based on settings
            $apiUrl = $this->getApiUrl();
            $this->tapsilatAPI = new TapsilatAPI($this->settings['Token'], 10, $apiUrl);
        }
    }

    /**
     * Log debug information if debug mode is enabled
     * 
     * Security: Ensures no sensitive data is logged
     *
     * @param string $message The message to log (should not contain sensitive data)
     */
    private function debug_log($message)
    {
        // Only log if WP_DEBUG is enabled and WooCommerce logger is available
        if (defined('WP_DEBUG') && WP_DEBUG && function_exists('wc_get_logger')) {
            // Sanitize the message to prevent log injection
            $safe_message = sanitize_text_field($message);
            
            // Use WooCommerce logger
            $logger = wc_get_logger();
            $logger->info('Tapsilat: ' . $safe_message, array('source' => 'tapsilat-woocommerce'));
        }
    }    /**
     * Get API URL based on settings
     *
     * @return string
     */
    private function getApiUrl()
    {
        $apiEnvironment = isset($this->settings['API']) ? $this->settings['API'] : 'production';
        
        if ($apiEnvironment === 'custom') {
            // Use custom API URL if provided, otherwise fallback to dev environment
            if (!empty($this->settings['custom_api_url'])) {
                $customUrl = rtrim($this->settings['custom_api_url'], '/');
                // Remove /api/v1/accounting if already present to avoid duplication
                $customUrl = preg_replace('/\/api\/v1\/accounting$/', '', $customUrl);
                // Remove /api/v1 if already present to avoid duplication
                $customUrl = preg_replace('/\/api\/v1$/', '', $customUrl);
                return $customUrl . '/api/v1/';
            } else {
                // Default to dev environment when custom is selected but no URL provided
                return 'https://acquiring.tapsilat.com/api/v1/';
            }
        }
        
        // Production environment
        return 'https://acquiring.tapsilat.com/api/v1/';
    }

    /**
     * Get or create order using Tapsilat SDK
     * First checks if order with conversation_id exists, if not creates new one
     *
     * @param WC_Order $order
     * @return array|false
     */
    public function createOrder(WC_Order $order)
    {
        if (!$this->tapsilatAPI) {
            return false;
        }

        try {
            // First check if we already have an order with this conversation_id
            $conversationId = $order->get_id();
            $existingOrder = $this->getOrderByConversationId($conversationId);
            
            if ($existingOrder) {
                $this->debug_log('Found existing order for conversation_id: ' . $conversationId);
                return $existingOrder;
            }
            
            $this->debug_log('Creating new order for conversation_id: ' . $conversationId);
            $orderData = $order->get_data();
            
            // Create buyer DTO
            $buyer = new BuyerDTO(
                $orderData['billing']['first_name'],
                $orderData['billing']['last_name'],
                null, // birth_date
                $this->getCityName($orderData['billing']['country'], $orderData['billing']['state']),
                $this->getCountryName($orderData['billing']['country']),
                $orderData['billing']['email'],
                $orderData['billing']['phone']
            );

            // Create order DTO
            $orderCreateDTO = new OrderCreateDTO(
                (float) $order->get_total(),
                $this->settings['Currency'] ?? 'TRY',
                substr(get_locale(), 0, 2),
                $buyer
            );

            // Set conversation_id as WooCommerce order ID
            if (method_exists($orderCreateDTO, 'setConversationId')) {
                $orderCreateDTO->setConversationId($order->get_id());
                $this->debug_log('Setting conversation_id to Order ID: ' . $order->get_id());
            }

            // Add metadata with WooCommerce info and custom settings
            $metadata = [
                'woocommerce_order_id' => $order->get_id(),
                'woocommerce_order_key' => $order->get_order_key(),
                'payment_source' => 'woocommerce_api',
                'plugin_version' => '2025.09.24.1',
                'site_url' => get_site_url()
            ];

            // Add custom metadata from settings if available
            if (!empty($this->settings['custom_metadata'])) {
                $customMetadata = json_decode($this->settings['custom_metadata'], true);
                if (is_array($customMetadata)) {
                    $metadata = array_merge($metadata, $customMetadata);
                }
            }

            if (method_exists($orderCreateDTO, 'setMetadata')) {
                $orderCreateDTO->setMetadata($metadata);
                $this->debug_log('Setting metadata: ' . json_encode($metadata));
            }

            // Log the API URL we're sending to
            $this->debug_log('Sending order creation request to: ' . $this->getApiUrl());
            $this->debug_log('Order data - Amount: ' . $order->get_total() . ', Currency: ' . ($this->settings['Currency'] ?? 'TRY') . ', Order ID: ' . $order->get_id());

            // Create order via API
            $response = $this->tapsilatAPI->createOrder($orderCreateDTO);
            
            $this->debug_log('API Response received - Type: ' . gettype($response));
            if (is_object($response)) {
                $this->debug_log('Response class: ' . get_class($response));
            }
            
            if ($response && method_exists($response, 'getReferenceId') && $response->getReferenceId()) {
                $this->debug_log('Order created successfully - Reference ID: ' . $response->getReferenceId());
                
                // Store reference_id and metadata in order meta
                $order->update_meta_data('_tapsilat_reference_id', $response->getReferenceId());
                $order->update_meta_data('_tapsilat_order_id', $response->getOrderId());
                $order->update_meta_data('_tapsilat_conversation_id', $order->get_id());
                $order->update_meta_data('_tapsilat_metadata', $metadata);
                $order->save();
                
                // Return array format for backward compatibility
                return [
                    'reference_id' => $response->getReferenceId(),
                    'order_id' => $response->getOrderId(),
                    'checkout_url' => $response->getCheckoutUrl(),
                    'data' => $response->getData()
                ];
            } else {
                $this->debug_log('Order creation failed - Invalid response or missing reference ID');
            }

        } catch (\Exception $e) {
            $this->debug_log('Order Creation Error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Get checkout URL for order
     *
     * @param string $referenceId
     * @return string|false
     */
    public function getCheckoutUrl($referenceId)
    {
        if (!$this->tapsilatAPI) {
            return false;
        }

        try {
            // getCheckoutUrl returns the URL string directly (it handles OrderResponse internally)
            $checkoutUrl = $this->tapsilatAPI->getCheckoutUrl($referenceId);
            return $checkoutUrl;
        } catch (\Exception $e) {
            $this->debug_log('Checkout URL Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get existing order by conversation_id
     *
     * @param string $conversationId
     * @return array|false
     */
    private function getOrderByConversationId($conversationId)
    {
        if (!$this->tapsilatAPI) {
            return false;
        }

        try {
            // Check if we have this order stored in our cache first
            $cachedOrder = get_transient('tapsilat_order_' . $conversationId);
            if ($cachedOrder) {
                $this->debug_log('Using cached order for conversation_id: ' . $conversationId);
                return $cachedOrder;
            }

            // Try to get order from Tapsilat API using conversation_id
            // Note: This might need to be implemented in the Tapsilat SDK
            if (method_exists($this->tapsilatAPI, 'getOrderByConversationId')) {
                $response = $this->tapsilatAPI->getOrderByConversationId($conversationId);
                
                if ($response && method_exists($response, 'getReferenceId') && $response->getReferenceId()) {
                    $orderData = [
                        'reference_id' => $response->getReferenceId(),
                        'order_id' => $response->getOrderId(),
                        'checkout_url' => $response->getCheckoutUrl(),
                        'data' => $response->getData()
                    ];
                    
                    // Cache for 30 minutes
                    set_transient('tapsilat_order_' . $conversationId, $orderData, 30 * MINUTE_IN_SECONDS);
                    
                    return $orderData;
                }
            }

            return false;
        } catch (\Exception $e) {
            $this->debug_log('Error getting order by conversation_id: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get order status from Tapsilat
     *
     * @param string $referenceId
     * @return array|false
     */
    public function getOrderStatus($referenceId)
    {
        if (!$this->tapsilatAPI) {
            return false;
        }

        try {
            return $this->tapsilatAPI->getOrderStatus($referenceId);
        } catch (\Exception $e) {
            $this->debug_log('Order Status Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Cancel order in Tapsilat
     *
     * @param string $referenceId
     * @return bool
     */
    public function cancelOrder($referenceId)
    {
        if (!$this->tapsilatAPI) {
            return false;
        }

        try {
            $this->tapsilatAPI->cancelOrder($referenceId);
            return true;
        } catch (\Exception $e) {
            $this->debug_log('Order Cancel Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get country name
     *
     * @param string $countryCode
     * @return string
     */
    private function getCountryName($countryCode)
    {
        $countries = new WC_Countries();
        $countryNames = $countries->get_countries();
        return $countryNames[$countryCode] ?? $countryCode;
    }

    /**
     * Get city/state name
     *
     * @param string $countryCode
     * @param string $stateCode
     * @return string
     */
    private function getCityName($countryCode, $stateCode)
    {
        $countries = new WC_Countries();
        $states = $countries->get_states($countryCode);
        
        if (is_array($states) && isset($states[$stateCode])) {
            return $states[$stateCode];
        }
        
        return $stateCode;
    }
}