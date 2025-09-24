<?php

namespace Tapsilat\WooCommerce\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use Tapsilat\WooCommerce\Checkout\CheckoutProcessor;

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
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/payment-success',
            [
                [
                    'methods' => WP_REST_Server::ALLMETHODS,
                    'callback' => [$this, 'handle_payment_success'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/payment-failure',
            [
                [
                    'methods' => WP_REST_Server::ALLMETHODS,
                    'callback' => [$this, 'handle_payment_failure'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/payment-callback',
            [
                [
                    'methods' => WP_REST_Server::ALLMETHODS,
                    'callback' => [$this, 'handle_payment_callback'],
                    'permission_callback' => '__return_true',
                ],
            ]
        );
    }

    /**
     * Handle payment success callback
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_success(WP_REST_Request $request)
    {
        $reference_id = $request->get_param('reference_id');
        $conversation_id = $request->get_param('conversation_id');

        if (!$reference_id && !$conversation_id) {
            return new WP_Error(
                'missing_params',
                __('Missing reference_id or conversation_id parameter.', 'tapsilat-woocommerce'),
                ['status' => 400]
            );
        }

        // Find order by reference_id
        $order = $this->find_order_by_reference_id($reference_id);
        if (!$order) {
            return new WP_Error(
                'order_not_found',
                __('Order not found.', 'tapsilat-woocommerce'),
                ['status' => 404]
            );
        }

        // Verify payment status with Tapsilat
        $orderStatus = $this->checkoutProcessor->getOrderStatus($reference_id);
        if (!$orderStatus || !isset($orderStatus['status'])) {
            return new WP_Error(
                'verification_failed',
                __('Payment verification failed.', 'tapsilat-woocommerce'),
                ['status' => 400]
            );
        }

        if ($orderStatus['status'] === 'SUCCESS') {
            // Update order status to completed
            $order->update_status('processing');
            $order->add_order_note(__('Payment successful via Tapsilat webhook. Reference ID: ', 'tapsilat-woocommerce') . $reference_id);
            $order->payment_complete($reference_id);

            // Redirect to success page
            $redirect_url = $this->get_success_url($order);
        } else {
            // Payment not successful
            $order->update_status('failed');
            $order->add_order_note(__('Payment failed via Tapsilat webhook. Reference ID: ', 'tapsilat-woocommerce') . $reference_id);
            
            $redirect_url = $this->get_failure_url($order);
        }

        // Return redirect response
        return new WP_REST_Response([
            'success' => true,
            'redirect_url' => $redirect_url,
            'message' => __('Payment processed successfully.', 'tapsilat-woocommerce')
        ], 200);
    }

    /**
     * Handle payment failure callback
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_failure(WP_REST_Request $request)
    {
        $reference_id = $request->get_param('reference_id');
        $conversation_id = $request->get_param('conversation_id');

        if (!$reference_id && !$conversation_id) {
            return new WP_Error(
                'missing_params',
                __('Missing reference_id or conversation_id parameter.', 'tapsilat-woocommerce'),
                ['status' => 400]
            );
        }

        // Find order by reference_id
        $order = $this->find_order_by_reference_id($reference_id);
        if (!$order) {
            return new WP_Error(
                'order_not_found',
                __('Order not found.', 'tapsilat-woocommerce'),
                ['status' => 404]
            );
        }

        // Update order status to failed
        $order->update_status('failed');
        $order->add_order_note(__('Payment failed via Tapsilat webhook. Reference ID: ', 'tapsilat-woocommerce') . $reference_id);

        $redirect_url = $this->get_failure_url($order);

        return new WP_REST_Response([
            'success' => true,
            'redirect_url' => $redirect_url,
            'message' => __('Payment failure processed.', 'tapsilat-woocommerce')
        ], 200);
    }

    /**
     * Handle general payment callback (for webhook notifications)
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response|WP_Error
     */
    public function handle_payment_callback(WP_REST_Request $request)
    {
        $body = $request->get_body();
        $data = json_decode($body, true);

        if (!$data || !isset($data['reference_id'])) {
            return new WP_Error(
                'invalid_data',
                __('Invalid callback data.', 'tapsilat-woocommerce'),
                ['status' => 400]
            );
        }

        $reference_id = $data['reference_id'];
        $status = $data['status'] ?? null;

        // Find order by reference_id
        $order = $this->find_order_by_reference_id($reference_id);
        if (!$order) {
            return new WP_Error(
                'order_not_found',
                __('Order not found.', 'tapsilat-woocommerce'),
                ['status' => 404]
            );
        }

        // Process based on status
        if ($status === 'SUCCESS' || $status === 'COMPLETED') {
            $order->update_status('processing');
            $order->add_order_note(__('Payment completed via Tapsilat callback. Reference ID: ', 'tapsilat-woocommerce') . $reference_id);
            $order->payment_complete($reference_id);
        } elseif ($status === 'FAILED' || $status === 'CANCELLED') {
            $order->update_status('failed');
            $order->add_order_note(__('Payment failed via Tapsilat callback. Reference ID: ', 'tapsilat-woocommerce') . $reference_id);
        }

        return new WP_REST_Response([
            'success' => true,
            'message' => __('Callback processed successfully.', 'tapsilat-woocommerce')
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

        $orders = wc_get_orders([
            'meta_key' => '_tapsilat_reference_id',
            'meta_value' => $reference_id,
            'limit' => 1,
        ]);

        return !empty($orders) ? $orders[0] : false;
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
        return $order->get_cancel_order_url();
    }
}