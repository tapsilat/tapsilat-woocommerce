<?php
namespace Tapsilat;

use Tapsilat\Models\OrderCreateRequest;
use Tapsilat\Models\OrderResponse;
use Tapsilat\Models\RefundOrderRequest;
use Tapsilat\Models\CancelOrderRequest;
use Tapsilat\Models\RefundAllOrderRequest;
use Tapsilat\Models\OrderPaymentDetailRequest;
use Tapsilat\Models\OrderPaymentTermCreateRequest;
use Tapsilat\Models\OrderPaymentTermUpdateRequest;
use Tapsilat\Models\OrderPaymentTermDeleteRequest;
use Tapsilat\Models\OrderTermRefundRequest;
use Tapsilat\Models\TerminateRequest;
use Tapsilat\Models\OrderManualCallbackRequest;
use Tapsilat\Models\OrderRelatedReferenceRequest;
use Tapsilat\Models\AddBasketItemRequest;
use Tapsilat\Models\RemoveBasketItemRequest;
use Tapsilat\Models\UpdateBasketItemRequest;
use Tapsilat\Models\UpdateCallbackURLRequest;
use Tapsilat\Models\OrgCreateBusinessRequest;
use Tapsilat\Models\GetUserLimitRequest;
use Tapsilat\Models\SetLimitUserRequest;
use Tapsilat\Models\GetVposRequest;
use Tapsilat\Models\OrgCreateUserRequest;
use Tapsilat\Models\OrgUserVerifyRequest;
use Tapsilat\Models\OrgUserMobileVerifyRequest;
use Tapsilat\Models\SubscriptionCreateRequest;
use Tapsilat\Models\SubscriptionGetRequest;
use Tapsilat\Models\SubscriptionCancelRequest;
use Tapsilat\Models\SubscriptionRedirectRequest;
use Tapsilat\Models\SubscriptionCreateResponse;
use Tapsilat\Models\SubscriptionDetailResponse;
use Tapsilat\Models\SubscriptionRedirectResponse;
use Tapsilat\Models\OrderAccountingRequest;
use Tapsilat\Models\OrderPostAuthRequest;
use Tapsilat\Models\OrderPaymentOptionsUpdateRequest;
use Tapsilat\Models\SplitOrderItemPaymentRequest;
use Tapsilat\Models\GetOrderPaymentsRequest;
use Tapsilat\Models\OrderOIPDTO;
use Tapsilat\Models\RefundOrderDTO;
use Tapsilat\Models\SubmerchantCreateDTO;
use Tapsilat\Models\SubmerchantUpdateDTO;
use Tapsilat\Models\OrgUserTokenCreateReq;


class TapsilatAPI
{
    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $debug = false;

    public function __construct($apiKey = '', $timeout = 10, $baseUrl = 'https://panel.tapsilat.dev/api/v1')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->apiKey = $apiKey;
        $this->timeout = $timeout;
    }

    public function setDebug($debug)
    {
        $this->debug = (bool)$debug;
    }

    private function getHeaders()
    {
        $headers = ['Accept' => 'application/json'];
        if (!empty($this->apiKey)) {
            $headers['Authorization'] = 'Bearer ' . $this->apiKey;
        }
        return $headers;
    }

    protected function makeRequest($method, $endpoint, $params = null, $jsonPayload = null, $isRaw = false)
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

        if ($this->debug) {
            echo "Request: {$method} {$url}\n";
            echo "Headers: " . implode(", ", $curlHeaders) . "\n";
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if (is_resource($ch)) {
            curl_close($ch);
        }

        if ($error) {
            throw new APIException(0, -1, $error);
        }

        if ($httpCode >= 400) {
            $errorData = json_decode($response, true);
            $apiCode = isset($errorData['code']) ? $errorData['code'] : -1;
            $errorMsg = isset($errorData['error']) ? $errorData['error'] : 'HTTP error';
            throw new APIException($httpCode, $apiCode, $errorMsg);
        }

        return $isRaw ? $response : ($response ? json_decode($response, true) : []);
    }

    public function createOrder(OrderCreateRequest $order)
    {
        $endpoint = '/order/create';

        // Validate GSM number if provided
        if ($order->buyer && $order->buyer->gsm_number) {
            $order->buyer->gsm_number = Validators::validateGsmNumber($order->buyer->gsm_number);
        }

        // Validate installments if provided
        if (isset($order->enabled_installments) && $order->enabled_installments) {
            $order->enabled_installments = Validators::validateInstallments($order->enabled_installments);
        }

        $payload = $order->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        
        return new OrderResponse($response);
    }

    public function orderAccounting(OrderAccountingRequest $request)
    {
        $endpoint = '/order/accounting';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderPostAuth(OrderPostAuthRequest $request)
    {
        $endpoint = '/order/postauth';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getSystemOrderStatuses()
    {
        $endpoint = '/system/order-statuses';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemBasketItemTypes()
    {
        $endpoint = '/system/basket-item-types';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemErrorCodes()
    {
        $endpoint = '/system/error-codes';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemPaymentTermStatuses()
    {
        $endpoint = '/system/payment-term-statuses';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemProductTypes()
    {
        $endpoint = '/system/product-types';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemShortcutTypes()
    {
        $endpoint = '/system/shortcut-types';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemTransactionPaymentTypes()
    {
        $endpoint = '/system/transaction-payment-types';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemTransactionPurposes()
    {
        $endpoint = '/system/transaction-purposes';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getSystemTransactionStatuses()
    {
        $endpoint = '/system/transaction-statuses';
        return $this->makeRequest('GET', $endpoint);
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

    
    
    // --- Aliases for Python SDK Compatibility ---
    public function relatedUpdate(string $id, array $payload)
    {
        return $this->orderRelatedUpdate($id, $payload);
    }

    public function terminateOrder(string $id)
    {
        return $this->orderTerminate($id);
    }

    public function manualCallback(string $id)
    {
        return $this->orderManualCallback($id);
    }


    public function getOrderPayments(GetOrderPaymentsRequest $request)
    {
        $endpoint = "/order/payments";
        $payload = $request->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
    }

    public function getOrderPdf(string $id)
    {
        $endpoint = "/order/{$id}/export/pdf";
        return $this->makeRequest("GET", $endpoint, null, null, true);
    }

    public function getOrderExcel(string $id)
    {
        $endpoint = "/order/{$id}/export/excel";
        return $this->makeRequest("GET", $endpoint, null, null, true);
    }

    public function createOrderRefundRequest(RefundOrderRequest $dto)
    {
        $endpoint = "/order/refund-request";
        $payload = $dto->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
    }

    public function addOrderOip(OrderOIPDTO $dto)
    {
        $endpoint = "/order/oip";
        $payload = $dto->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
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

    public function getOrders($page = '1', $perPage = '10', $buyerId = '', $status = null)
    {
        $endpoint = '/order/list';
        $params = ['page' => $page, 'per_page' => $perPage];
        if (!empty($buyerId)) {
            $params['buyer_id'] = $buyerId;
        }
        if ($status !== null) {
            $params['status'] = $status;
        }
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function getCheckoutUrl($referenceId)
    {
        $response = $this->getOrder($referenceId);
        return $response->getCheckoutUrl();
    }

    public function cancelOrder(CancelOrderRequest $request)
    {
        $endpoint = '/order/cancel';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function refundOrder(RefundOrderRequest $refundData)
    {
        $endpoint = '/order/refund';
        $payload = $refundData->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function refundAllOrder(RefundAllOrderRequest $request)
    {
        $endpoint = '/order/refund-all';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrderPaymentDetails(OrderPaymentDetailRequest $request)
    {
        $endpoint = '/order/payment-details';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrderPaymentDetailsById($referenceId)
    {
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
        $endpoint = "/order/term";
        $params = ["term_reference_id" => $termReferenceId];
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function createOrderTerm(OrderPaymentTermCreateRequest $term)
    {
        $endpoint = '/order/term';
        $payload = $term->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function deleteOrderTerm(OrderPaymentTermDeleteRequest $request)
    {
        $endpoint = '/order/term';
        $payload = $request->toArray();
        return $this->makeRequest('DELETE', $endpoint, null, $payload);
    }

    public function updateOrderTerm(OrderPaymentTermUpdateRequest $term)
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



    public function orderTerminate(TerminateRequest $request)
    {
        $endpoint = '/order/terminate';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderManualCallback(OrderManualCallbackRequest $request)
    {
        $endpoint = '/order/callback';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderRelatedUpdate(OrderRelatedReferenceRequest $request)
    {
        $endpoint = '/order/releated';
        $payload = $request->toArray();
        return $this->makeRequest('PATCH', $endpoint, null, $payload);
    }

    public function addBasketItem(AddBasketItemRequest $request)
    {
        $endpoint = '/order/basket-item';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function removeBasketItem(RemoveBasketItemRequest $request)
    {
        $endpoint = '/order/basket-item';
        $payload = $request->toArray();
        return $this->makeRequest('DELETE', $endpoint, null, $payload);
    }

    public function updateBasketItem(UpdateBasketItemRequest $request)
    {
        $endpoint = '/order/basket-item';
        $payload = $request->toArray();
        return $this->makeRequest('PATCH', $endpoint, null, $payload);
    }

    public function updatePaymentOptions(OrderPaymentOptionsUpdateRequest $request)
    {
        $endpoint = '/order/payment-options';
        $payload = $request->toArray();
        return $this->makeRequest('PATCH', $endpoint, null, $payload);
    }

    public function splitOrderItemPayment(SplitOrderItemPaymentRequest $request)
    {
        $endpoint = '/order/split';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function orderCallback($id)
    {
        $endpoint = "/orders/{$id}/callback";
        return $this->makeRequest('GET', $endpoint);
    }

    public function orderVposQuery($id)
    {
        $endpoint = "/orders/{$id}/vpos-query";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationSettings()
    {
        $endpoint = '/organization/settings';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationCallback()
    {
        $endpoint = '/organization/callback';
        return $this->makeRequest('GET', $endpoint);
    }

    public function updateOrganizationCallback(UpdateCallbackURLRequest $request)
    {
        $endpoint = '/organization/callback';
        $payload = $request->toArray();
        return $this->makeRequest('PATCH', $endpoint, null, $payload);
    }

    public function createOrganizationBusiness(OrgCreateBusinessRequest $request)
    {
        $endpoint = '/organization/business/create';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrganizationCurrencies()
    {
        $endpoint = '/organization/currencies';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationLimitUser(GetUserLimitRequest $request)
    {
        $endpoint = '/organization/limit/user';
        $payload = $request->toArray();
        return $this->makeRequest('GET', $endpoint, null, $payload);
    }

    public function setOrganizationLimitUser(SetLimitUserRequest $request)
    {
        $endpoint = '/organization/limit/user';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrganizationLimits()
    {
        $endpoint = '/organization/limits';
        return $this->makeRequest('GET', $endpoint);
    }

    public function listOrganizationVpos(GetVposRequest $request)
    {
        $endpoint = '/organization/list-vpos';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function getOrganizationMeta($name)
    {
        $endpoint = "/organization/metas/{$name}";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationScopes()
    {
        $endpoint = '/organization/scopes';
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationSuborganizations($page = 1, $perPage = 10)
    {
        $endpoint = '/organization/suborganizations';
        $params = ['page' => $page, 'per_page' => $perPage];
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function getOrganizationSuborganizationDetails(string $id)
    {
        $endpoint = "/organization/suborganizations/{$id}";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationSuborganizationSubmerchants(string $id)
    {
        $endpoint = "/organization/suborganizations/{$id}/submerchant";
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationCurrencyPresets()
    {
        $endpoint = '/organization/currency-presets';
        return $this->makeRequest('GET', $endpoint);
    }

    
    public function createOrganizationUserToken(OrgUserTokenCreateReq $request)
    {
        $endpoint = "/organization/user/token";
        $payload = $request->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
    }

    public function createOrganizationUser(OrgCreateUserRequest $request)
    {
        $endpoint = '/organization/user/create';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function verifyOrganizationUser(OrgUserVerifyRequest $request)
    {
        $endpoint = '/organization/user/verify';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function verifyOrganizationUserMobile(OrgUserMobileVerifyRequest $request)
    {
        $endpoint = '/organization/user/verify-mobile';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    
    public function createSubmerchant(SubmerchantCreateDTO $request)
    {
        $endpoint = "/submerchants";
        $payload = $request->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
    }

    public function getSubmerchant(string $id)
    {
        $endpoint = "/submerchants/{$id}";
        return $this->makeRequest("GET", $endpoint);
    }

    public function getSuborganizationBySubmerchant(string $id)
    {
        $endpoint = "/submerchants/{$id}/suborganization";
        return $this->makeRequest("GET", $endpoint);
    }

    public function updateSubmerchant(string $id, SubmerchantUpdateDTO $request)
    {
        $endpoint = "/submerchants/{$id}";
        $payload = $request->toArray();
        return $this->makeRequest("PATCH", $endpoint, null, $payload);
    }

    public function deleteSubmerchant(string $id)
    {
        $endpoint = "/submerchants/{$id}";
        return $this->makeRequest("DELETE", $endpoint);
    }

    public function listSubmerchants(int $page = 1, int $perPage = 10)
    {
        $endpoint = "/submerchants";
        $params = ["page" => $page, "per_page" => $perPage];
        return $this->makeRequest("GET", $endpoint, $params);
    }

    // Subscription Methods

    public function getSubscription(SubscriptionGetRequest $request)
    {
        $endpoint = '/subscription';
        $payload = $request->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        return new SubscriptionDetailResponse($response);
    }

    public function cancelSubscription(SubscriptionCancelRequest $request)
    {
        $endpoint = '/subscription/cancel';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    public function createSubscription(SubscriptionCreateRequest $subscription)
    {
        $endpoint = '/subscription/create';
        $payload = $subscription->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        return new SubscriptionCreateResponse($response);
    }

    public function listSubscriptions($page = 1, $perPage = 10)
    {
        $endpoint = '/subscription/list';
        $params = ['page' => $page, 'per_page' => $perPage];
        return $this->makeRequest('GET', $endpoint, $params);
    }

    public function redirectSubscription(SubscriptionRedirectRequest $request)
    {
        $endpoint = '/subscription/redirect';
        $payload = $request->toArray();
        $response = $this->makeRequest('POST', $endpoint, null, $payload);
        return new SubscriptionRedirectResponse($response);
    }




    public static function verifyWebhook($payload, $signature, $secret)
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return 'sha256=' . $expectedSignature === $signature;
    }
}
