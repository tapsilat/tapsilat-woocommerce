<?php
namespace Tapsilat;

use Tapsilat\Models\OrderCreateDTO;
use Tapsilat\Models\OrderResponse;
use Tapsilat\Models\RefundOrderDTO;
use Tapsilat\Models\OrderPaymentTermCreateDTO;
use Tapsilat\Models\OrderPaymentTermUpdateDTO;
use Tapsilat\Models\OrderTermRefundRequest;

class TapsilatAPI
{
    private $baseUrl;
    private $apiKey;
    private $timeout;

    public function __construct($apiKey = '', $timeout = 10, $baseUrl = 'https://acquiring.tapsilat.dev/api/v1')
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
        return new OrderResponse($response);
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
        ], function($value) {
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
        $endpoint = '/order/term';
        $params = ['term_reference_id' => $termReferenceId];
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function createOrderTerm(OrderPaymentTermCreateDTO $term)
    {
        $endpoint = '/order/term';
        $payload = $term->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function deleteOrderTerm($orderId, $termReferenceId)
    {
        $endpoint = '/order/term';
        $payload = ['order_id' => $orderId, 'term_reference_id' => $termReferenceId];
        return $this->makeRequest('DELETE', $endpoint, null, $payload);
    }

    public function updateOrderTerm(OrderPaymentTermUpdateDTO $term)
    {
        $endpoint = '/order/term';
        $payload = $term->toArray();
        return $this->makeRequest('PATCH', $endpoint, null, $payload);
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

    public function orderManualCallback($referenceId, $conversationId)
    {
        $endpoint = '/order/callback';
        $payload = ['reference_id' => $referenceId, 'conversation_id' => $conversationId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderRelatedUpdate($referenceId, $relatedReferenceId)
    {
        $endpoint = '/order/related';
        $payload = ['reference_id' => $referenceId, 'related_reference_id' => $relatedReferenceId];
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }
}
