<?php

namespace Tapsilat\WooCommerce\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Tapsilat\TapsilatAPI;
use Tapsilat\WebhookEvent;
use Tapsilat\WooCommerce\Checkout\CheckoutProcessor;

/**
 * Receives Tapsilat's payment callbacks and customers returning from the checkout.
 *
 * Nothing a request carries is trusted: a callback's Tapsilat-Signature is verified when a webhook
 * secret is configured, and the order status always comes from the Tapsilat API.
 */
class WebhookController extends WP_REST_Controller
{
    /**
     * Namespace.
     *
     * @var string
     */
    protected $namespace = 'tapsilat/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'webhook';

    private $checkoutProcessor;

    public function __construct()
    {
        $this->checkoutProcessor = new CheckoutProcessor();
    }

    /**
     * Register the routes for webhook.
     */
    public function register_routes()
    {
        foreach (['payment-success', 'payment-failure', 'payment-callback'] as $route) {
            register_rest_route(
                $this->namespace,
                '/' . $this->rest_base . '/' . $route,
                [
                    [
                        'methods' => WP_REST_Server::ALLMETHODS,
                        'callback' => [$this, 'handle_notification'],
                        'permission_callback' => '__return_true',
                    ],
                ]
            );
        }
    }

    /**
     * Handle payment success callback
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_success(WP_REST_Request $request)
    {
        return $this->handle_notification($request);
    }

    /**
     * Handle payment failure callback
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_failure(WP_REST_Request $request)
    {
        return $this->handle_notification($request);
    }

    /**
     * Handle general payment callback (for webhook notifications)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_callback(WP_REST_Request $request)
    {
        return $this->handle_notification($request);
    }

    /**
     * Handles a signed callback ({"type", "data", "token"}) posted by Tapsilat, or a request that
     * names the order with reference_id, or with order_id and key, such as a customer's browser.
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_notification(WP_REST_Request $request)
    {
        $body = $request->get_body();
        $payload = $body !== '' ? json_decode($body, true) : null;

        if (is_array($payload) && isset($payload['type']) && array_key_exists('data', $payload)) {
            return $this->handle_callback($request, $body, new WebhookEvent($payload));
        }

        return $this->handle_return($request);
    }

    /**
     * @param WP_REST_Request $request
     * @param string $body
     * @param WebhookEvent $event
     * @return WP_REST_Response|WP_Error
     */
    private function handle_callback(WP_REST_Request $request, $body, WebhookEvent $event)
    {
        $settings = get_option('woocommerce_tapsilat_settings', []);
        $secret = isset($settings['webhook_secret']) ? (string) $settings['webhook_secret'] : '';
        if ($secret !== '') {
            $signature = (string) $request->get_header(TapsilatAPI::WEBHOOK_SIGNATURE_HEADER);
            if (!TapsilatAPI::verifyWebhook($body, $signature, $secret)) {
                return new WP_Error(
                    'invalid_signature',
                    __('Invalid webhook signature.', 'tapsilat-woocommerce'),
                    ['status' => 401]
                );
            }
        }

        $reference_id = $event->getReferenceId();
        $order = $this->find_order_by_reference_id($reference_id);
        if (!$order) {
            $order = $this->find_order_by_key($request);
            $reference_id = null;
        }
        if (!$order) {
            // Not an order of this store, such as one created elsewhere with the same account.
            // Answer 2xx so that Tapsilat does not retry it.
            return new WP_REST_Response(['success' => true, 'ignored' => true], 200);
        }

        $state = $this->checkoutProcessor->syncOrderStatus(
            $order,
            __('Tapsilat callback', 'tapsilat-woocommerce'),
            true,
            $reference_id
        );
        if ($state === null) {
            // The status could not be checked; a non-2xx answer makes Tapsilat retry later.
            return new WP_Error(
                'status_unavailable',
                __('The payment status could not be checked.', 'tapsilat-woocommerce'),
                ['status' => 503]
            );
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Callback processed successfully.', 'tapsilat-woocommerce')
        ], 200);
    }

    /**
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    private function handle_return(WP_REST_Request $request)
    {
        $reference_id = sanitize_text_field((string) $request->get_param('reference_id'));
        $order = $this->find_order_by_key($request) ?: $this->find_order_by_reference_id($reference_id);

        if (!$order) {
            if (!$reference_id && !$request->get_param('order_id')) {
                return new WP_Error(
                    'missing_params',
                    __('Missing reference_id or order_id parameter.', 'tapsilat-woocommerce'),
                    ['status' => 400]
                );
            }
            return new WP_Error(
                'order_not_found',
                __('Order not found.', 'tapsilat-woocommerce'),
                ['status' => 404]
            );
        }

        $state = $this->checkoutProcessor->syncOrderStatus($order, __('customer returned', 'tapsilat-woocommerce'));
        $redirect_url = $state === 'failed' ? $this->get_failure_url($order) : $this->get_success_url($order);

        if ($request->get_method() === 'GET') {
            $response = new WP_REST_Response(null, 302);
            $response->header('Location', $redirect_url);
            return $response;
        }

        return new WP_REST_Response([
            'success' => true,
            'status' => $state,
            'redirect_url' => $redirect_url,
            'message' => __('Payment status updated.', 'tapsilat-woocommerce')
        ], 200);
    }

    /**
     * Find order by Tapsilat reference ID
     *
     * @param string $reference_id
     * @return \WC_Order|false
     */
    private function find_order_by_reference_id($reference_id)
    {
        if (!$reference_id) {
            return false;
        }

        // First check transient cache for better performance
        $cache_key = 'tapsilat_order_ref_' . md5($reference_id);
        $cached_order_id = get_transient($cache_key);

        if ($cached_order_id !== false) {
            $order = wc_get_order($cached_order_id);
            if ($order && $this->has_reference($order, $reference_id)) {
                return $order;
            }
            // If cached order doesn't match, delete the cache
            delete_transient($cache_key);
        }

        // The current attempt's reference, then those of earlier attempts.
        foreach (['_tapsilat_reference_id', '_tapsilat_attempt_reference_id'] as $meta_key) {
            // Use optimized query with specific parameters to minimize database load
            $orders = wc_get_orders([
                'meta_key' => $meta_key, // @codingStandardsIgnoreLine - Necessary for payment processing
                'meta_value' => $reference_id, // @codingStandardsIgnoreLine - Necessary for payment processing
                'limit' => 1,
                'return' => 'objects',
                // Cancelled too: WooCommerce cancels unpaid orders when held stock times out, and
                // the payment may still complete afterwards.
                'status' => ['pending', 'on-hold', 'processing', 'completed', 'failed', 'cancelled'],
                'orderby' => 'date',
                'order' => 'DESC'
            ]);

            if (!empty($orders)) {
                $order = $orders[0];
                // Cache the result for 5 minutes to improve performance
                set_transient($cache_key, $order->get_id(), 5 * MINUTE_IN_SECONDS);
                return $order;
            }
        }

        return false;
    }

    /**
     * @param \WC_Order $order
     * @param string $reference_id
     * @return bool
     */
    private function has_reference($order, $reference_id)
    {
        if ($order->get_meta('_tapsilat_reference_id') === $reference_id) {
            return true;
        }
        foreach ($order->get_meta('_tapsilat_attempt_reference_id', false) as $meta) {
            if ($meta->value === $reference_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the order named by the order_id and key parameters of the URLs the plugin gives Tapsilat.
     *
     * @param WP_REST_Request $request
     * @return \WC_Order|false
     */
    private function find_order_by_key(WP_REST_Request $request)
    {
        $order_id = absint($request->get_param('order_id'));
        $key = (string) $request->get_param('key');
        if (!$order_id || $key === '') {
            return false;
        }

        $order = wc_get_order($order_id);
        if (!$order || !hash_equals($order->get_order_key(), $key)) {
            return false;
        }

        return $order;
    }

    /**
     * Get success redirect URL
     *
     * @param \WC_Order $order
     * @return string
     */
    private function get_success_url($order)
    {
        $gateway = new \WC_Gateway_Tapsilat();
        return $gateway->get_return_url($order);
    }

    /**
     * Get failure redirect URL
     *
     * @param \WC_Order $order
     * @return string
     */
    private function get_failure_url($order)
    {
        return $order->get_checkout_payment_url();
    }
}
