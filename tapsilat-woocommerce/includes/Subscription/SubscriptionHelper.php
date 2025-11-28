<?php
namespace Tapsilat\WooCommerce\Subscription;

use Tapsilat\TapsilatAPI;
use Tapsilat\Models\SubscriptionCreateRequest;
use Tapsilat\Models\SubscriptionGetRequest;
use Tapsilat\Models\SubscriptionCancelRequest;
use Tapsilat\Models\SubscriptionBillingDTO;
use Tapsilat\Models\SubscriptionUserDTO;

/**
 * Subscription Helper Class
 * Handles all subscription-related operations for WooCommerce
 */
class SubscriptionHelper
{

    private $api_client;
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('woocommerce_tapsilat_settings', []);
        $this->init_api_client();
    }

    /**
     * Initialize Tapsilat API client
     */
    private function init_api_client()
    {
        $token = isset($this->settings['Token']) ? $this->settings['Token'] : '';

        if (empty($token)) {
            return;
        }

        // Get API URL based on environment
        $api_env = isset($this->settings['API']) ? $this->settings['API'] : 'production';
        $base_url = 'https://panel.tapsilat.com/api/v1';

        if ($api_env === 'custom') {
            $custom_url = isset($this->settings['custom_api_url']) ? $this->settings['custom_api_url'] : '';
            if (!empty($custom_url)) {
                $base_url = $custom_url;
            } else {
                $base_url = 'https://panel.tapsilat.dev/api/v1';
            }
        }

        $this->api_client = new TapsilatAPI($token, 10, $base_url);
    }

    /**
     * Check if subscriptions are enabled
     */
    public function is_subscription_enabled()
    {
        return isset($this->settings['enable_subscriptions']) && $this->settings['enable_subscriptions'] === 'yes';
    }

    /**
     * Create a subscription
     * 
     * @param WC_Order $order WooCommerce order object
     * @param array $subscription_data Subscription data
     * @return array|WP_Error Subscription response or error
     */
    public function create_subscription($order, $subscription_data = [])
    {
        if (!$this->api_client) {
            return new \WP_Error('no_api_client', __('Tapsilat API client not initialized', 'tapsilat-woocommerce'));
        }

        try {
            // Prepare billing data
            $billing = new SubscriptionBillingDTO(
                $order->get_billing_address_1() . ' ' . $order->get_billing_address_2(),
                $order->get_billing_city(),
                $order->get_billing_first_name() . ' ' . $order->get_billing_last_name(),
                $order->get_billing_country(),
                '', // vat_number - can be added as custom field
                $order->get_billing_postcode()
            );

            // Prepare user data
            $user = new SubscriptionUserDTO(
                $order->get_customer_id() ?: 'guest_' . $order->get_id(),
                $order->get_billing_first_name(),
                $order->get_billing_last_name(),
                $order->get_billing_email(),
                $order->get_billing_phone(),
                '', // identity_number - can be added as custom field
                $order->get_billing_address_1(),
                $order->get_billing_city(),
                $order->get_billing_country(),
                $order->get_billing_postcode()
            );

            // Get subscription settings
            $period = isset($subscription_data['period']) ? $subscription_data['period'] :
                (isset($this->settings['subscription_period']) ? $this->settings['subscription_period'] : 30);
            $cycle = isset($subscription_data['cycle']) ? $subscription_data['cycle'] :
                (isset($this->settings['subscription_cycle']) ? $this->settings['subscription_cycle'] : 12);
            $payment_date = isset($subscription_data['payment_date']) ? $subscription_data['payment_date'] : 1;

            // Prepare subscription request
            $subscription = new SubscriptionCreateRequest(
                $order->get_total(),
                $order->get_currency(),
                'Subscription for Order #' . $order->get_id(),
                $period,
                $cycle,
                $payment_date,
                'wc_order_' . $order->get_id(),
                $order->get_checkout_order_received_url(),
                $order->get_cancel_order_url(),
                isset($subscription_data['card_id']) ? $subscription_data['card_id'] : null,
                $billing,
                $user
            );

            // Create subscription via API
            $response = $this->api_client->createSubscription($subscription);

            // Save subscription data to order meta
            $order->update_meta_data('_tapsilat_subscription_id', $response->getReferenceId());
            $order->update_meta_data('_tapsilat_subscription_order_id', $response->getOrderReferenceId());
            $order->update_meta_data('_tapsilat_subscription_period', $period);
            $order->update_meta_data('_tapsilat_subscription_cycle', $cycle);
            $order->update_meta_data('_is_subscription', 'yes');
            $order->save();

            return [
                'success' => true,
                'subscription_id' => $response->getReferenceId(),
                'order_reference_id' => $response->getOrderReferenceId()
            ];

        } catch (\Exception $e) {
            return new \WP_Error('subscription_creation_failed', $e->getMessage());
        }
    }

    /**
     * Get subscription details
     * 
     * @param string $reference_id Subscription reference ID
     * @param string $external_reference_id External reference ID
     * @return array|WP_Error Subscription details or error
     */
    public function get_subscription($reference_id = null, $external_reference_id = null)
    {
        if (!$this->api_client) {
            return new \WP_Error('no_api_client', __('Tapsilat API client not initialized', 'tapsilat-woocommerce'));
        }

        try {
            $request = new SubscriptionGetRequest($reference_id, $external_reference_id);
            $subscription = $this->api_client->getSubscription($request);

            return [
                'success' => true,
                'subscription' => $subscription
            ];

        } catch (\Exception $e) {
            return new \WP_Error('subscription_get_failed', $e->getMessage());
        }
    }

    /**
     * Cancel a subscription
     * 
     * @param string $reference_id Subscription reference ID
     * @param string $external_reference_id External reference ID
     * @return array|WP_Error Success status or error
     */
    public function cancel_subscription($reference_id = null, $external_reference_id = null)
    {
        if (!$this->api_client) {
            return new \WP_Error('no_api_client', __('Tapsilat API client not initialized', 'tapsilat-woocommerce'));
        }

        try {
            $request = new SubscriptionCancelRequest($reference_id, $external_reference_id);
            $this->api_client->cancelSubscription($request);

            // Update order meta if we have external reference
            if ($external_reference_id && strpos($external_reference_id, 'wc_order_') === 0) {
                $order_id = str_replace('wc_order_', '', $external_reference_id);
                $order = wc_get_order($order_id);
                if ($order) {
                    $order->update_meta_data('_tapsilat_subscription_status', 'cancelled');
                    $order->add_order_note(__('Subscription cancelled via Tapsilat', 'tapsilat-woocommerce'));
                    $order->save();
                }
            }

            return [
                'success' => true,
                'message' => __('Subscription cancelled successfully', 'tapsilat-woocommerce')
            ];

        } catch (\Exception $e) {
            return new \WP_Error('subscription_cancel_failed', $e->getMessage());
        }
    }

    /**
     * Redirect subscription (e.g. for updating payment method)
     * 
     * @param string $subscription_id Subscription ID
     * @return array|WP_Error Redirect URL or error
     */
    public function redirect_subscription($subscription_id)
    {
        if (!$this->api_client) {
            return new \WP_Error('no_api_client', __('Tapsilat API client not initialized', 'tapsilat-woocommerce'));
        }

        try {
            $request = new \Tapsilat\Models\SubscriptionRedirectRequest($subscription_id);
            $response = $this->api_client->redirectSubscription($request);

            return [
                'success' => true,
                'url' => $response->getUrl()
            ];

        } catch (\Exception $e) {
            return new \WP_Error('subscription_redirect_failed', $e->getMessage());
        }
    }

    /**
     * List all subscriptions
     * 
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array|WP_Error Subscriptions list or error
     */
    public function list_subscriptions($page = 1, $per_page = 10)
    {
        if (!$this->api_client) {
            return new \WP_Error('no_api_client', __('Tapsilat API client not initialized', 'tapsilat-woocommerce'));
        }

        try {
            $subscriptions = $this->api_client->listSubscriptions($page, $per_page);

            return [
                'success' => true,
                'subscriptions' => $subscriptions
            ];

        } catch (\Exception $e) {
            return new \WP_Error('subscription_list_failed', $e->getMessage());
        }
    }

    /**
     * Check if an order is a subscription
     * 
     * @param WC_Order $order WooCommerce order object
     * @return bool True if subscription, false otherwise
     */
    public function is_subscription_order($order)
    {
        return $order->get_meta('_is_subscription') === 'yes';
    }

    /**
     * Get subscription meta from order
     * 
     * @param WC_Order $order WooCommerce order object
     * @return array Subscription metadata
     */
    public function get_order_subscription_meta($order)
    {
        return [
            'subscription_id' => $order->get_meta('_tapsilat_subscription_id'),
            'order_reference_id' => $order->get_meta('_tapsilat_subscription_order_id'),
            'period' => $order->get_meta('_tapsilat_subscription_period'),
            'cycle' => $order->get_meta('_tapsilat_subscription_cycle'),
            'status' => $order->get_meta('_tapsilat_subscription_status') ?: 'active'
        ];
    }
}
