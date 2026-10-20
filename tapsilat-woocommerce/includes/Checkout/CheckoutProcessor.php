<?php

namespace Tapsilat\WooCommerce\Checkout;

use Tapsilat\OrderStatus;
use Tapsilat\TapsilatAPI;
use Tapsilat\Models\BasketItemDTO;
use Tapsilat\Models\BuyerDTO;
use Tapsilat\Models\MetadataDTO;
use Tapsilat\Models\OrderCreateDTO;
use Tapsilat\WooCommerce\ApiClient;
use WC_Order;
use WC_Countries;

class CheckoutProcessor
{
    /** @var TapsilatAPI|null */
    private $tapsilatAPI;
    private $settings;

    public function __construct()
    {
        $this->settings = get_option('woocommerce_tapsilat_settings', []);
        $this->tapsilatAPI = ApiClient::make($this->settings);
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
    }

    /**
     * The URL Tapsilat posts signed payment callbacks for this order to.
     *
     * @param WC_Order $order
     * @return string
     */
    public static function callbackUrl(WC_Order $order)
    {
        return add_query_arg(
            ['order_id' => $order->get_id(), 'key' => $order->get_order_key()],
            rest_url('tapsilat/v1/webhook/payment-callback')
        );
    }

    /**
     * The URL the Tapsilat checkout sends the customer back to after paying or giving up.
     *
     * @param WC_Order $order
     * @return string
     */
    public static function returnUrl(WC_Order $order)
    {
        return add_query_arg(
            ['order_id' => $order->get_id(), 'key' => $order->get_order_key()],
            WC()->api_request_url('tapsilat_return')
        );
    }

    /**
     * Creates a Tapsilat order for the WooCommerce order, or a subscription when the order contains
     * a Tapsilat subscription product, and stores its reference on the order.
     *
     * Every call is a new payment attempt with its own conversation ID, so it is also how a
     * failed or expired attempt is retried.
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
            if ($this->isSubscriptionOrder($order)) {
                $this->debug_log('Order contains subscription products, creating subscription');
                return $this->createSubscriptionOrder($order);
            }

            // Conversation IDs are unique across Tapsilat, so a bare order number would collide
            // with other stores and with earlier attempts for this order.
            $conversationId = 'wc-' . $order->get_id() . '-' . strtolower(wp_generate_password(8, false));
            $currency = $order->get_currency() ?: ($this->settings['Currency'] ?? 'TRY');
            $amount = $this->formatAmount($order->get_total());

            // Create buyer DTO using WooCommerce 3.0+ getters (HPOS compatible)
            $buyer = new BuyerDTO(
                $order->get_billing_first_name(),
                $order->get_billing_last_name(),
                null, // birth_date
                $this->getCityName($order->get_billing_country(), $order->get_billing_state()),
                $this->getCountryName($order->get_billing_country()),
                $order->get_billing_email(),
                $this->cleanPhone($order->get_billing_phone())
            );

            $orderCreateDTO = new OrderCreateDTO($amount, $currency, substr(get_locale(), 0, 2), $buyer);
            $orderCreateDTO->basket_items = $this->buildBasketItemsFromOrder($order, $amount);
            $orderCreateDTO->conversation_id = $conversationId;

            $metadata = $this->buildMetadata($order);
            $orderCreateDTO->metadata = array_map(function ($key) use ($metadata) {
                return new MetadataDTO((string) $key, $metadata[$key]);
            }, array_keys($metadata));

            // Tapsilat posts signed payment callbacks to the payment URLs, and the checkout's
            // "return to store" links lead to the redirect URLs.
            $orderCreateDTO->payment_success_url = self::callbackUrl($order);
            $orderCreateDTO->payment_failure_url = self::callbackUrl($order);
            $orderCreateDTO->redirect_success_url = self::returnUrl($order);
            $orderCreateDTO->redirect_failure_url = self::returnUrl($order);

            $this->debug_log('Creating Tapsilat order ' . $conversationId . ' - Amount: ' . $amount . ' ' . $currency);

            $response = $this->tapsilatAPI->createOrder($orderCreateDTO);
            if (!$response || !$response->getReferenceId()) {
                $this->debug_log('Order creation failed - Invalid response or missing reference ID');
                return false;
            }

            $this->debug_log('Order created successfully - Reference ID: ' . $response->getReferenceId());

            $order->update_meta_data('_tapsilat_reference_id', $response->getReferenceId());
            // Every attempt's reference, so that a late payment of an earlier attempt is still found.
            $order->add_meta_data('_tapsilat_attempt_reference_id', $response->getReferenceId());
            $order->update_meta_data('_tapsilat_order_id', $response->getOrderId());
            $order->update_meta_data('_tapsilat_conversation_id', $conversationId);
            $order->update_meta_data('_tapsilat_amount', $amount);
            $order->update_meta_data('_tapsilat_currency', $currency);
            $order->update_meta_data('_tapsilat_metadata', $metadata);
            $order->save();

            return [
                'reference_id' => $response->getReferenceId(),
                'order_id' => $response->getOrderId(),
                'checkout_url' => $response->getCheckoutUrl(),
                'data' => $response->getData()
            ];
        } catch (\Exception $e) {
            $this->debug_log('Order Creation Error: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Whether the order's Tapsilat order still matches its total and currency, so that its
     * checkout can be reused. Orders created before these were recorded are assumed to match.
     *
     * @param WC_Order $order
     * @return bool
     */
    public function isCurrentAttempt(WC_Order $order)
    {
        $amount = $order->get_meta('_tapsilat_amount');
        $currency = $order->get_meta('_tapsilat_currency');

        if ($amount !== '' && $this->formatAmount($amount) !== $this->formatAmount($order->get_total())) {
            return false;
        }

        return $currency === '' || $currency === $order->get_currency();
    }

    /**
     * Looks up the status of the order's Tapsilat order and moves the WooCommerce order to match:
     * a paid order is completed, and a pending one whose payment failed, was cancelled or expired
     * is marked failed. Callbacks and return URLs only trigger this lookup; nothing they carry is
     * trusted.
     *
     * @param WC_Order $order
     * @param string $source Where the check came from, for the order note
     * @param bool $markFailed Whether to mark a pending order failed when its payment failed
     * @param string|null $referenceId One of the order's payment attempts; the current one by default
     * @return string|null 'paid', 'failed', 'refunded' or 'pending', or null when the status is unknown
     */
    public function syncOrderStatus(WC_Order $order, $source, $markFailed = true, $referenceId = null)
    {
        $current = $order->get_meta('_tapsilat_reference_id');
        $referenceId = $referenceId ?: $current;
        if (!$referenceId) {
            return null;
        }
        $isCurrent = $referenceId === $current;

        $result = $this->getOrderStatus($referenceId);
        if (!is_array($result) || !isset($result['status'])) {
            return null;
        }
        $status = $result['status'];

        if (OrderStatus::isPaid($status)) {
            $payable = apply_filters(
                'woocommerce_valid_order_statuses_for_payment_complete',
                ['on-hold', 'pending', 'failed', 'cancelled'],
                $order
            );
            if (in_array($order->get_status(), $payable, true)) {
                if (!$isCurrent) {
                    // An earlier attempt was paid; refunds must go to it.
                    $order->update_meta_data('_tapsilat_reference_id', $referenceId);
                    $order->save();
                }
                $order->add_order_note(sprintf(
                    /* translators: 1: Tapsilat reference ID, 2: where the payment was confirmed */
                    __('Tapsilat payment confirmed. Reference ID: %1$s (%2$s)', 'tapsilat-woocommerce'),
                    $referenceId,
                    $source
                ));
                $order->payment_complete($referenceId);
            }
            return 'paid';
        }

        if (OrderStatus::isFailed($status)) {
            // A failed earlier attempt says nothing about the current one.
            if ($markFailed && $isCurrent && in_array($order->get_status(), ['pending', 'on-hold'], true)) {
                $order->update_status('failed', sprintf(
                    /* translators: 1: Tapsilat order status, 2: Tapsilat reference ID, 3: where the status was checked */
                    __('Tapsilat payment status: %1$s. Reference ID: %2$s (%3$s)', 'tapsilat-woocommerce'),
                    OrderStatus::name($status) ?: $status,
                    $referenceId,
                    $source
                ));
            }
            return 'failed';
        }

        if (OrderStatus::isRefunded($status)) {
            return 'refunded';
        }

        return 'pending';
    }

    /**
     * Check if order contains subscription products
     *
     * @param WC_Order $order
     * @return bool
     */
    private function isSubscriptionOrder(WC_Order $order)
    {
        // Check if subscriptions are enabled
        if (!isset($this->settings['enable_subscriptions']) || $this->settings['enable_subscriptions'] !== 'yes') {
            return false;
        }

        // Check order items for subscription products
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $isSubscription = get_post_meta($product->get_id(), '_is_tapsilat_subscription', true);
                if ($isSubscription === 'yes') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Create subscription order using SubscriptionHelper
     *
     * @param WC_Order $order
     * @return array|false
     */
    private function createSubscriptionOrder(WC_Order $order)
    {
        // Get subscription helper
        $subscriptionHelper = new \Tapsilat\WooCommerce\Subscription\SubscriptionHelper();

        // Get subscription data from first subscription product
        $subscriptionData = $this->getSubscriptionDataFromOrder($order);

        if (!$subscriptionData) {
            $this->debug_log('Failed to get subscription data from order');
            return false;
        }

        // Create subscription
        $result = $subscriptionHelper->create_subscription($order, $subscriptionData);

        if (is_wp_error($result)) {
            $this->debug_log('Subscription creation failed: ' . $result->get_error_message());
            return false;
        }

        if ($result && isset($result['success']) && $result['success']) {
            $this->debug_log('Subscription created successfully - ID: ' . $result['subscription_id']);

            // Get checkout URL for the subscription order
            if (!empty($result['order_reference_id'])) {
                // Callbacks, the return URL and the status checks find the order by this reference.
                $order->update_meta_data('_tapsilat_reference_id', $result['order_reference_id']);
                $order->add_meta_data('_tapsilat_attempt_reference_id', $result['order_reference_id']);
                $order->update_meta_data('_tapsilat_amount', $this->formatAmount($order->get_total()));
                $order->update_meta_data('_tapsilat_currency', $order->get_currency());
                $order->save();

                $checkoutUrl = $this->getCheckoutUrl($result['order_reference_id']);

                return [
                    'reference_id' => $result['order_reference_id'],
                    'subscription_id' => $result['subscription_id'],
                    'checkout_url' => $checkoutUrl,
                    'is_subscription' => true
                ];
            }
        }

        return false;
    }

    /**
     * Get subscription data from order items
     *
     * @param WC_Order $order
     * @return array|false
     */
    private function getSubscriptionDataFromOrder(WC_Order $order)
    {
        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            if ($product) {
                $isSubscription = get_post_meta($product->get_id(), '_is_tapsilat_subscription', true);

                if ($isSubscription === 'yes') {
                    return [
                        'period' => get_post_meta($product->get_id(), '_tapsilat_subscription_period', true) ?: 30,
                        'cycle' => get_post_meta($product->get_id(), '_tapsilat_subscription_cycle', true) ?: 12,
                        'payment_date' => get_post_meta($product->get_id(), '_tapsilat_subscription_payment_date', true) ?: 1,
                    ];
                }
            }
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
            return $checkoutUrl ?: false;
        } catch (\Exception $e) {
            $this->debug_log('Checkout URL Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * The metadata sent with the order: the WooCommerce order and the custom metadata setting.
     * Tapsilat stores metadata as string key/value pairs.
     *
     * @param WC_Order $order
     * @return array
     */
    private function buildMetadata(WC_Order $order)
    {
        $metadata = [
            'woocommerce_order_id' => $order->get_id(),
            'woocommerce_order_number' => $order->get_order_number(),
            'woocommerce_order_key' => $order->get_order_key(),
            'payment_source' => 'woocommerce_api',
            'plugin_version' => defined('TAPSILAT_WOOCOMMERCE_VERSION') ? TAPSILAT_WOOCOMMERCE_VERSION : '',
            'site_url' => get_site_url()
        ];

        // Add custom metadata from settings if available
        if (!empty($this->settings['custom_metadata'])) {
            $customMetadata = json_decode($this->settings['custom_metadata'], true);
            if (is_array($customMetadata)) {
                $metadata = array_merge($metadata, $customMetadata);
            }
        }

        return array_map(function ($value) {
            if (is_bool($value)) {
                return $value ? 'true' : 'false';
            }
            return is_scalar($value) || $value === null ? (string) $value : wp_json_encode($value);
        }, $metadata);
    }

    /**
     * The order's lines as basket items. Tapsilat requires their prices, which are line totals,
     * to add up to the order amount, so taxes, shipping and fees are included; when they still
     * do not add up, or a line is negative, the order is sent as a single item.
     *
     * @param WC_Order $order
     * @param float $amount The order amount sent to Tapsilat
     * @return BasketItemDTO[]
     */
    private function buildBasketItemsFromOrder(WC_Order $order, $amount)
    {
        $basketItems = [];
        foreach ($order->get_items() as $itemId => $item) {
            $product = $item->get_product();
            $basketItems[] = $this->basketItem(
                (string) $itemId,
                $item->get_name(),
                (float) $item->get_total() + (float) $item->get_total_tax(),
                max(1, (int) $item->get_quantity()),
                $product && $product->is_virtual() ? 'VIRTUAL' : 'PHYSICAL'
            );
        }
        foreach ($order->get_items('shipping') as $itemId => $item) {
            $basketItems[] = $this->basketItem(
                'shipping-' . $itemId,
                $item->get_name(),
                (float) $item->get_total() + (float) $item->get_total_tax(),
                1,
                'VIRTUAL',
                'Shipping'
            );
        }
        foreach ($order->get_items('fee') as $itemId => $item) {
            $basketItems[] = $this->basketItem(
                'fee-' . $itemId,
                $item->get_name(),
                (float) $item->get_total() + (float) $item->get_total_tax(),
                1,
                'VIRTUAL',
                'Fee'
            );
        }

        $sum = 0;
        $valid = !empty($basketItems);
        foreach ($basketItems as $basketItem) {
            $sum += (int) round($basketItem->price * 100);
            if ($basketItem->price < 0) {
                $valid = false;
            }
        }

        if (!$valid || $sum !== (int) round($amount * 100)) {
            return [$this->basketItem(
                'order-' . $order->get_id(),
                /* translators: %s: order number */
                sprintf(__('Order #%s', 'tapsilat-woocommerce'), $order->get_order_number()),
                $amount,
                1,
                'PHYSICAL'
            )];
        }

        return $basketItems;
    }

    private function basketItem($id, $name, $price, $quantity, $itemType, $category = 'Other')
    {
        return new BasketItemDTO(
            $category, // category1
            null, // category2
            null, // coupon
            null, // coupon_discount
            null, // data
            $id, // id
            $itemType, // item_type
            $name, // name
            null, // paid_amount
            $this->formatAmount($price), // price
            null, // commission_amount
            null, // mcc
            null, // payer
            $quantity // quantity
        );
    }

    /**
     * @param string|float $amount
     * @return float
     */
    private function formatAmount($amount)
    {
        return round((float) $amount, 2);
    }

    /**
     * The phone number as digits with an optional leading "+", or null when it is too short to
     * be one, so that an unusual format does not make the SDK reject the order.
     *
     * @param string $phone
     * @return string|null
     */
    private function cleanPhone($phone)
    {
        $phone = trim((string) $phone);
        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) < 5) {
            return null;
        }

        return (strpos($phone, '+') === 0 ? '+' : '') . $digits;
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
            $response = $this->tapsilatAPI->cancelOrder(new \Tapsilat\Models\CancelOrderDTO($referenceId));
            return $this->isSuccessful($response, 'Order Cancel');
        } catch (\Exception $e) {
            $this->debug_log('Order Cancel Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Refund order in Tapsilat
     *
     * @param string $referenceId
     * @param float|string $amount
     * @return bool
     */
    public function refundOrder($referenceId, $amount)
    {
        if (!$this->tapsilatAPI) {
            return false;
        }

        try {
            // WooCommerce passes the amount as a decimal string; Tapsilat expects a number.
            $refundDTO = new \Tapsilat\Models\RefundOrderDTO($this->formatAmount($amount), $referenceId);
            $response = $this->tapsilatAPI->refundOrder($refundDTO);
            return $this->isSuccessful($response, 'Order Refund');
        } catch (\Exception $e) {
            $this->debug_log('Order Refund Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tapsilat answers some refused operations with 200 and is_success false.
     */
    private function isSuccessful($response, $operation)
    {
        if (is_array($response) && array_key_exists('is_success', $response) && !$response['is_success']) {
            $this->debug_log($operation . ' Error: ' . ($response['message'] ?? 'is_success is false'));
            return false;
        }

        return true;
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

    public function getOrder($referenceId) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrder($referenceId); } catch (\Exception $e) { $this->debug_log('getOrder error: ' . $e->getMessage()); return false; }
    }
    public function getOrderList($page = 1, $perPage = 10, $startDate = '', $endDate = '', $organizationId = '', $relatedReferenceId = '') {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrderList($page, $perPage, $startDate, $endDate, $organizationId, $relatedReferenceId); } catch (\Exception $e) { $this->debug_log('getOrderList error: ' . $e->getMessage()); return false; }
    }
    public function getOrderSubmerchants($page = 1, $perPage = 10) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrderSubmerchants($page, $perPage); } catch (\Exception $e) { $this->debug_log('getOrderSubmerchants error: ' . $e->getMessage()); return false; }
    }
    public function refundAllOrder(\Tapsilat\Models\RefundAllOrderRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->refundAllOrder($request); } catch (\Exception $e) { $this->debug_log('refundAllOrder error: ' . $e->getMessage()); return false; }
    }
    public function getOrderPaymentDetails(\Tapsilat\Models\OrderPaymentDetailRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrderPaymentDetails($request); } catch (\Exception $e) { $this->debug_log('getOrderPaymentDetails error: ' . $e->getMessage()); return false; }
    }
    public function getOrderPaymentDetailsById($referenceId) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrderPaymentDetailsById($referenceId); } catch (\Exception $e) { $this->debug_log('getOrderPaymentDetailsById error: ' . $e->getMessage()); return false; }
    }
    public function orderAccounting(\Tapsilat\Models\OrderAccountingRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->orderAccounting($request); } catch (\Exception $e) { $this->debug_log('orderAccounting error: ' . $e->getMessage()); return false; }
    }
    public function orderPostAuth(\Tapsilat\Models\OrderPostAuthRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->orderPostAuth($request); } catch (\Exception $e) { $this->debug_log('orderPostAuth error: ' . $e->getMessage()); return false; }
    }
    public function terminateOrder(string $id) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->terminateOrder($id); } catch (\Exception $e) { $this->debug_log('terminateOrder error: ' . $e->getMessage()); return false; }
    }
    public function updatePaymentOptions(\Tapsilat\Models\OrderPaymentOptionsUpdateRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->updatePaymentOptions($request); } catch (\Exception $e) { $this->debug_log('updatePaymentOptions error: ' . $e->getMessage()); return false; }
    }
    public function splitOrderItemPayment(\Tapsilat\Models\SplitOrderItemPaymentRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->splitOrderItemPayment($request); } catch (\Exception $e) { $this->debug_log('splitOrderItemPayment error: ' . $e->getMessage()); return false; }
    }
    public function createOrderTerm(\Tapsilat\Models\OrderPaymentTermCreateRequest $term) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->createOrderTerm($term); } catch (\Exception $e) { $this->debug_log('createOrderTerm error: ' . $e->getMessage()); return false; }
    }
    public function deleteOrderTerm(\Tapsilat\Models\OrderPaymentTermDeleteRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->deleteOrderTerm($request); } catch (\Exception $e) { $this->debug_log('deleteOrderTerm error: ' . $e->getMessage()); return false; }
    }
    public function updateOrderTerm(\Tapsilat\Models\OrderPaymentTermUpdateRequest $term) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->updateOrderTerm($term); } catch (\Exception $e) { $this->debug_log('updateOrderTerm error: ' . $e->getMessage()); return false; }
    }
    public function getOrderTerm($termReferenceId) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrderTerm($termReferenceId); } catch (\Exception $e) { $this->debug_log('getOrderTerm error: ' . $e->getMessage()); return false; }
    }
    public function refundOrderTerm(\Tapsilat\Models\OrderTermRefundRequest $term) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->refundOrderTerm($term); } catch (\Exception $e) { $this->debug_log('refundOrderTerm error: ' . $e->getMessage()); return false; }
    }
    public function addBasketItem(\Tapsilat\Models\AddBasketItemRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->addBasketItem($request); } catch (\Exception $e) { $this->debug_log('addBasketItem error: ' . $e->getMessage()); return false; }
    }
    public function removeBasketItem(\Tapsilat\Models\RemoveBasketItemRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->removeBasketItem($request); } catch (\Exception $e) { $this->debug_log('removeBasketItem error: ' . $e->getMessage()); return false; }
    }
    public function updateBasketItem(\Tapsilat\Models\UpdateBasketItemRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->updateBasketItem($request); } catch (\Exception $e) { $this->debug_log('updateBasketItem error: ' . $e->getMessage()); return false; }
    }
    public function getOrderPayments(\Tapsilat\Models\GetOrderPaymentsRequest $request) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->getOrderPayments($request); } catch (\Exception $e) { $this->debug_log('getOrderPayments error: ' . $e->getMessage()); return false; }
    }
    public function orderCallback($id) {
        if (!$this->tapsilatAPI) return false;
        try { return $this->tapsilatAPI->orderCallback($id); } catch (\Exception $e) { $this->debug_log('orderCallback error: ' . $e->getMessage()); return false; }
    }
}
