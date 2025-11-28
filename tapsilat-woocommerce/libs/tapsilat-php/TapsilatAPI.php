<?php
namespace Tapsilat;

use Tapsilat\Models\OrderCreateDTO;
use Tapsilat\Models\OrderResponse;
use Tapsilat\Models\RefundOrderDTO;
use Tapsilat\Models\OrderPaymentTermCreateDTO;
use Tapsilat\Models\OrderPaymentTermUpdateDTO;
use Tapsilat\Models\OrderTermRefundRequest;
use Tapsilat\Models\SubscriptionCreateRequest;
use Tapsilat\Models\SubscriptionGetRequest;
use Tapsilat\Models\SubscriptionCancelRequest;
use Tapsilat\Models\SubscriptionRedirectRequest;
use Tapsilat\Models\SubscriptionCreateResponse;
use Tapsilat\Models\SubscriptionDetail;
use Tapsilat\Models\SubscriptionRedirectResponse;

class TapsilatAPI
{
    private $baseUrl;
    private $apiKey;
    private $timeout;

    public function __construct($apiKey = '', $timeout = 10, $baseUrl = 'https://panel.tapsilat.dev/api/v1')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    private function getHeaders()
    {
        $headers = ['Accept' => 'application/json'];
        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        return $headers;
    }

    protected function makeRequest($method, $endpoint, $params = null, $jsonPayload = null)
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $headers = $this->getHeaders();

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = $key . ': ' . $value;
        }

        if ($jsonPayload !== null) {
            $curlHeaders[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsonPayload));
        }

        if ($params !== null && $method === 'GET') {
            $url .= '?' . http_build_query($params);
            curl_setopt($ch, CURLOPT_URL, $url);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new APIException(0, -1, $error);
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $apiCode = isset($errorData['code']) ? $errorData['code'] : -1;
            $errorMsg = isset($errorData['error']) ? $errorData['error'] : 'HTTP error';
            throw new APIException($httpCode, $apiCode, $errorMsg);
        }

        return $response ? json_decode($response, true) : [];
    }

    public function createOrder(OrderCreateDTO $order)
    {
        $endpoint = '/order/create';

        // GSM number validation
        if ($order->buyer && $order->buyer->gsm_number) {
            $order->buyer->gsm_number = Validators::validateGsmNumber($order->buyer->gsm_number);
        }

        // Installments validation
        if (isset($order->enabled_installments)) {
            if (is_array($order->enabled_installments)) {
                $installmentsStr = implode(',', $order->enabled_installments);
            } else {
                $installmentsStr = str_replace(['[', ']', ' '], '', $order->enabled_installments);
            }
            $order->enabled_installments = Validators::validateInstallments($installmentsStr);
        }

        $payload = $order->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        $orderResponse = new OrderResponse($response);

        // Auto-fetch checkout URL if order creation was successful
        if (!empty($orderResponse->getReferenceId())) {
            try {
                $checkoutUrl = $this->getCheckoutUrl($orderResponse->getReferenceId());
                if (!empty($checkoutUrl)) {
                    $response['checkout_url'] = $checkoutUrl;
                    $orderResponse = new OrderResponse($response);
                }
            } catch (\Exception $e) {
                // Silently fail - don't break order creation if checkout URL fetch fails
            }
        }

        return $orderResponse;
    }

    public function getOrder($referenceId)
    {
        $endpoint = "/order/{$referenceId}";
        $response = $this->makeRequest('GET', $endpoint);
        return new OrderResponse($response);
    }

    public function getOrderByConversationId($conversationId)
    {
        $endpoint = "/order/conversation/{$conversationId}";
        $response = $this->makeRequest('GET', $endpoint);
        return new OrderResponse($response);
    }

    public function getOrderList($page = 1, $perPage = 10, $startDate = '', $endDate = '', $organizationId = '', $relatedReferenceId = '')
    {
        $endpoint = '/order/list';
        $params = array_filter([
            'page' => $page,
            'per_page' => $perPage,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'organization_id' => $organizationId,
            'related_reference_id' => $relatedReferenceId
        ], function ($value) {
            return $value !== '' && $value !== null;
        });
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function getOrderSubmerchants($page = 1, $perPage = 10)
    {
        $endpoint = '/order/submerchants';
        $params = ['page' => $page, 'per_page' => $perPage];
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function getOrders($page = '1', $perPage = '10', $buyerId = '')
    {
        $endpoint = '/order/list';
        $params = ['page' => $page, 'per_page' => $perPage];
        if (!empty($buyerId)) {
            $params['buyer_id'] = $buyerId;
        }
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function getCheckoutUrl($referenceId)
    {
        $response = $this->getOrder($referenceId);
        return $response->getCheckoutUrl();
    }

    public function cancelOrder($referenceId)
    {
        $endpoint = '/order/cancel';
        $payload = ['reference_id' => $referenceId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function refundOrder(RefundOrderDTO $refundData)
    {
        $endpoint = '/order/refund';
        $payload = $refundData->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function refundAllOrder($referenceId)
    {
        $endpoint = '/order/refund-all';
        $payload = ['reference_id' => $referenceId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrderPaymentDetails($referenceId, $conversationId = '')
    {
        if (!empty($conversationId)) {
            $endpoint = '/order/payment-details';
            $payload = ['conversation_id' => $conversationId, 'reference_id' => $referenceId];
            return $this->makeRequest('POST', $endpoint, null, $payload);
        }
        $endpoint = "/order/{$referenceId}/payment-details";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrderStatus($referenceId)
    {
        $endpoint = "/order/{$referenceId}/status";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrderTransactions($referenceId)
    {
        $endpoint = "/order/{$referenceId}/transactions";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrderTerm($termReferenceId)
    {
        $endpoint = "/order/term/{$termReferenceId}";
        return $this->makeRequest('GET', $endpoint);
    }

    public function createOrderTerm(OrderPaymentTermCreateDTO $term)
    {
        $endpoint = '/order/term/create';
        $payload = $term->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function deleteOrderTerm($orderId, $termReferenceId)
    {
        $endpoint = '/order/term/delete';
        $payload = ['order_id' => $orderId, 'term_reference_id' => $termReferenceId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function updateOrderTerm(OrderPaymentTermUpdateDTO $term)
    {
        $endpoint = '/order/term/update';
        $payload = $term->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function refundOrderTerm(OrderTermRefundRequest $term)
    {
        $endpoint = '/order/term/refund';
        $payload = $term->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderTerminate($referenceId)
    {
        $endpoint = '/order/terminate';
        $payload = ['reference_id' => $referenceId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderManualCallback($referenceId, $conversationId = '')
    {
        $endpoint = '/order/manual-callback';
        $payload = ['reference_id' => $referenceId];
        if (!empty($conversationId)) {
            $payload['conversation_id'] = $conversationId;
        }
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderRelatedUpdate($referenceId, $relatedReferenceId)
    {
        $endpoint = '/order/related-update';
        $payload = ['reference_id' => $referenceId, 'related_reference_id' => $relatedReferenceId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrganizationSettings()
    {
        $endpoint = '/organization/settings';
        return $this->makeRequest('GET', $endpoint);
    }

    // Subscription Methods

    public function createSubscription(SubscriptionCreateRequest $subscription)
    {
        $endpoint = '/subscription/create';
        $payload = $subscription->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        return new SubscriptionCreateResponse($response);
    }

    public function getSubscription(SubscriptionGetRequest $request)
    {
        $endpoint = '/subscription';
        $payload = $request->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        return new SubscriptionDetail($response);
    }

    public function listSubscriptions($page = 1, $perPage = 10)
    {
        $endpoint = '/subscription/list';
        $params = ['page' => $page, 'per_page' => $perPage];
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function cancelSubscription(SubscriptionCancelRequest $request)
    {
        $endpoint = '/subscription/cancel';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function redirectSubscription(SubscriptionRedirectRequest $request)
    {
        $endpoint = '/subscription/redirect';
        $payload = $request->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        return new SubscriptionRedirectResponse($response);
    }
}
