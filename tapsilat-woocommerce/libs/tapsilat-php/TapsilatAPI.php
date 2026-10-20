<?php

namespace Tapsilat;

use Tapsilat\Models\OrderChargeRequest;
use Tapsilat\Models\CreateOrganizationCurrencyPayload;
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
use Tapsilat\Models\OrderListFilter;

class TapsilatAPI
{
    use Concerns\CardMethods;
    use Concerns\SubmerchantSettlementMethods;
    use Concerns\VposMethods;
    use Concerns\MarketplaceMethods;

    /** The header that carries the signature of every callback Tapsilat sends */
    public const WEBHOOK_SIGNATURE_HEADER = 'Tapsilat-Signature';

    /** How many seconds a callback's signature timestamp may differ from the current time */
    public const WEBHOOK_TOLERANCE = 300;

    private $baseUrl;
    private $apiKey;
    private $timeout;
    private $debug = false;
    private $currencyIdsByUnit = null;

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

    /**
     * Sends a request and returns the decoded JSON body ([] for an empty body). $endpoint is a path
     * relative to the base URL, or an absolute URL such as one from versionedBaseUrl(). An error
     * status, or a body that is not JSON, throws APIException.
     */
    protected function makeRequest($method, $endpoint, $params = null, $jsonPayload = null, $isRaw = false)
    {
        $url = $this->buildUrl($endpoint, $params);
        $headers = $this->getHeaders();
        $body = null;
        if ($jsonPayload !== null) {
            $headers['Content-Type'] = 'application/json';
            $body = $jsonPayload === [] ? '{}' : json_encode($jsonPayload);
        } elseif (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $body = '';
        }

        if ($this->debug) {
            echo "Request: {$method} {$url}\n";
        }

        list($status, $contentType, $raw) = $this->sendHttp($method, $url, $headers, $body);

        if ($status >= 400) {
            throw APIException::fromResponse($status, $raw);
        }
        if ($isRaw) {
            return $raw;
        }
        // A deployment serving the panel UI answers unknown routes with its HTML page and status 200
        if (stripos($contentType, 'text/html') !== false || strpos(ltrim($raw), '<') === 0) {
            throw APIException::unexpectedResponse($status, $contentType, $raw);
        }
        if (trim($raw) === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            throw new APIException($status, -1, 'invalid JSON response: ' . json_last_error_msg(), null, null, $raw);
        }
        return $decoded;
    }

    /**
     * Performs the HTTP request and returns [status code, content type, body].
     */
    protected function sendHttp($method, $url, array $headers, $body)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        $curlHeaders = [];
        foreach ($headers as $key => $value) {
            $curlHeaders[] = $key . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        $error = curl_error($ch);

        if (is_resource($ch)) {
            curl_close($ch);
        }

        if ($response === false || $error) {
            throw new APIException(0, -1, $error ?: 'request failed');
        }

        return [$status, $contentType, (string)$response];
    }

    private function buildUrl($endpoint, $params = null)
    {
        $url = preg_match('#^https?://#i', $endpoint) ? $endpoint : $this->baseUrl . '/' . ltrim($endpoint, '/');
        if (is_array($params) && $params) {
            $query = self::buildQuery($params);
            if ($query !== '') {
                $url .= (strpos($url, '?') === false ? '?' : '&') . $query;
            }
        }
        return $url;
    }

    /**
     * Encodes query parameters, skipping null and empty values. Booleans become "true" or
     * "false", and a list value repeats its key.
     */
    public static function buildQuery(array $params)
    {
        $parts = [];
        foreach ($params as $key => $value) {
            foreach (is_array($value) ? $value : [$value] as $item) {
                if ($item === null || $item === '') {
                    continue;
                }
                if (is_bool($item)) {
                    $item = $item ? 'true' : 'false';
                }
                $parts[] = rawurlencode($key) . '=' . rawurlencode((string)$item);
            }
        }
        return implode('&', $parts);
    }

    /**
     * Returns the base URL for another API version: a trailing /api/vN is replaced with
     * /api/{version}, otherwise /api/{version} is appended.
     */
    public function versionedBaseUrl($version)
    {
        if (preg_match('#^(.*)/api/v\d+$#', $this->baseUrl, $matches)) {
            return $matches[1] . '/api/' . $version;
        }
        return $this->baseUrl . '/api/' . $version;
    }

    /**
     * Returns the absolute API v2 URL for $path.
     */
    protected function v2Url($path)
    {
        return $this->versionedBaseUrl('v2') . '/' . ltrim($path, '/');
    }

    /**
     * Formats a path with sprintf, escaping each argument as a single path segment.
     */
    protected function path($format, ...$ids)
    {
        return vsprintf($format, array_map(function ($id) {
            return rawurlencode((string)$id);
        }, $ids));
    }

    /**
     * Returns the organization currency UUID for $reference. A UUID is returned in lowercase; a
     * unit such as "TRY" is looked up in getOrganizationCurrencies(), whose result is cached.
     * Most endpoints accept units directly, so this is only needed where a UUID is required.
     */
    public function resolveCurrencyId($reference)
    {
        $ref = trim((string)$reference);
        if ($ref === '') {
            throw new APIException(0, 0, 'currency_id is required');
        }
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $ref)) {
            return strtolower($ref);
        }
        if ($this->currencyIdsByUnit === null) {
            $response = $this->getOrganizationCurrencies();
            $map = [];
            $currencies = isset($response['currencies']) && is_array($response['currencies'])
                ? $response['currencies']
                : [];
            foreach ($currencies as $currency) {
                if (!empty($currency['currency_unit']) && !empty($currency['id'])) {
                    $map[strtoupper(trim($currency['currency_unit']))] = $currency['id'];
                }
            }
            $this->currencyIdsByUnit = $map;
        }
        $unit = strtoupper($ref);
        if (!isset($this->currencyIdsByUnit[$unit])) {
            throw new APIException(0, 0, sprintf(
                'unknown currency reference "%s"; use a currency UUID or organization currency unit',
                $reference
            ));
        }
        return $this->currencyIdsByUnit[$unit];
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
            $installments = $order->enabled_installments;
            $order->enabled_installments = Validators::validateInstallments(
                is_array($installments) ? implode(',', $installments) : $installments
            );
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
        $endpoint = $this->path("/order/%s", $referenceId);
        $response = $this->makeRequest('GET', $endpoint);
        return new OrderResponse($response);
    }

    public function getOrderByConversationId($conversationId)
    {
        $endpoint = $this->path("/order/conversation/%s", $conversationId);
        $response = $this->makeRequest('GET', $endpoint);
        return new OrderResponse($response);
    }



    // --- Aliases for Python SDK Compatibility ---
    /**
     * Links an order to a related reference ID; $payload holds related_reference_id.
     */
    public function relatedUpdate(string $id, array $payload)
    {
        $related = isset($payload['related_reference_id']) ? $payload['related_reference_id'] : null;
        return $this->orderRelatedUpdate(new OrderRelatedReferenceRequest($id, $related));
    }

    public function terminateOrder(string $id)
    {
        return $this->orderTerminate(new TerminateRequest($id));
    }

    public function manualCallback(string $id, $conversationId = null)
    {
        return $this->orderManualCallback(new OrderManualCallbackRequest($id, $conversationId));
    }


    public function getOrderPayments(GetOrderPaymentsRequest $request)
    {
        $endpoint = "/order/payments";
        $payload = $request->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
    }

    public function getOrderPdf(string $id)
    {
        $endpoint = $this->path("/order/%s/export/pdf", $id);
        return $this->makeRequest("GET", $endpoint, null, null, true);
    }

    public function getOrderExcel(string $id)
    {
        $endpoint = $this->path("/order/%s/export/excel", $id);
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

    public function getOrderList(
        $page = 1,
        $perPage = 10,
        $startDate = '',
        $endDate = '',
        $organizationId = '',
        $relatedReferenceId = ''
    ) {
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

    /**
     * Lists orders with every filter the order list endpoint accepts.
     */
    public function getOrderListWithFilter(OrderListFilter $filter)
    {
        return $this->makeRequest('GET', '/order/list', $filter->toArray());
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

    public function getOrderPaymentDetailsByConversationId($conversationId)
    {
        return $this->makeRequest('POST', '/order/payment-details', null, ['conversation_id' => $conversationId]);
    }

    public function getOrderPaymentDetailsById($referenceId)
    {
        $endpoint = $this->path("/order/%s/payment-details", $referenceId);
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrderStatus($referenceId)
    {
        $endpoint = $this->path("/order/%s/status", $referenceId);
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Returns the order's transactions; the list is empty when there are none.
     */
    public function getOrderTransactions($referenceId)
    {
        $endpoint = $this->path("/order/%s/transactions", $referenceId);
        $transactions = $this->makeRequest('GET', $endpoint);
        return $transactions === null ? [] : $transactions;
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
        $endpoint = $this->path("/orders/%s/callback", $id);
        return $this->makeRequest('GET', $endpoint);
    }

    public function orderVposQuery($id)
    {
        $endpoint = $this->path("/orders/%s/vpos-query", $id);
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

    /**
     * @deprecated The API has no organization user limit route, so this call always fails.
     */
    public function getOrganizationLimitUser(GetUserLimitRequest $request)
    {
        $endpoint = '/organization/limit/user';
        $payload = $request->toArray();
        return $this->makeRequest('GET', $endpoint, null, $payload);
    }

    /**
     * @deprecated The API has no organization user limit route, so this call always fails.
     */
    public function setOrganizationLimitUser(SetLimitUserRequest $request)
    {
        $endpoint = '/organization/limit/user';
        $payload = $request->toArray();
        return $this->makeRequest('POST', $endpoint, null, $payload);
    }

    /**
     * @deprecated The API has no /organization/limits route, so this call always fails.
     *             Use getOrganizationLimitsById().
     */
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
        $endpoint = $this->path("/organization/metas/%s", $name);
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
        $endpoint = $this->path("/organization/suborganizations/%s", $id);
        return $this->makeRequest('GET', $endpoint);
    }

    public function getOrganizationSuborganizationSubmerchants(string $id)
    {
        $endpoint = $this->path("/organization/suborganizations/%s/submerchant", $id);
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
        $endpoint = $this->path("/submerchants/%s", $id);
        return $this->makeRequest("GET", $endpoint);
    }

    public function getSuborganizationBySubmerchant(string $id)
    {
        $endpoint = $this->path("/submerchants/%s/suborganization", $id);
        return $this->makeRequest("GET", $endpoint);
    }

    public function updateSubmerchant(string $id, SubmerchantUpdateDTO $request)
    {
        $endpoint = $this->path("/submerchants/%s", $id);
        $payload = $request->toArray();
        return $this->makeRequest("PATCH", $endpoint, null, $payload);
    }

    public function deleteSubmerchant(string $id)
    {
        $endpoint = $this->path("/submerchants/%s", $id);
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




    /**
     * Verifies the Tapsilat-Signature header of a callback against the organization's webhook secret.
     *
     * Tapsilat sends "t=<unix timestamp>,v1=<hex HMAC-SHA256 of '<timestamp>.<raw body>'>". The
     * timestamp must be within $tolerance seconds of $now (time() by default; 0 disables the
     * check) so that a captured callback cannot be replayed. $payload must be the raw request
     * body, before any JSON decoding. The comparison takes constant time.
     *
     * A bare hex HMAC-SHA256 of the payload, with or without a "sha256=" prefix, is still accepted
     * for signatures made the way earlier versions of this SDK documented.
     *
     * @param string $payload The raw request body
     * @param string $signature The Tapsilat-Signature header
     * @param string $secret The webhook secret from the panel's callback settings
     * @param int $tolerance Seconds; 0 disables the timestamp check
     * @param int|null $now Unix time to compare the timestamp with
     * @return bool
     */
    public static function verifyWebhook(
        $payload,
        $signature,
        $secret,
        $tolerance = self::WEBHOOK_TOLERANCE,
        $now = null
    ) {
        $signature = trim((string)$signature);
        $secret = (string)$secret;
        $payload = (string)$payload;
        if ($signature === '' || $secret === '') {
            return false;
        }

        if (strpos($signature, 'sha256=') === 0 || strpos($signature, '=') === false) {
            if (strpos($signature, 'sha256=') === 0) {
                $signature = substr($signature, strlen('sha256='));
            }
            return hash_equals(hash_hmac('sha256', $payload, $secret), strtolower($signature));
        }

        $timestamp = null;
        $signatures = [];
        foreach (explode(',', $signature) as $part) {
            $pair = explode('=', trim($part), 2);
            if (count($pair) !== 2) {
                continue;
            }
            if ($pair[0] === 't') {
                $timestamp = $pair[1];
            } elseif ($pair[0] === 'v1') {
                $signatures[] = strtolower($pair[1]);
            }
        }
        if ($timestamp === null || !ctype_digit($timestamp) || !$signatures) {
            return false;
        }
        if ($tolerance > 0 && abs(($now === null ? time() : (int)$now) - (int)$timestamp) > $tolerance) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        foreach ($signatures as $candidate) {
            if (hash_equals($expected, $candidate)) {
                return true;
            }
        }
        return false;
    }

    public function chargeOrder(OrderChargeRequest $request)
    {
        $endpoint = "/order/charge";
        $payload = $request->toArray();
        return $this->makeRequest("POST", $endpoint, null, $payload);
    }

    /**
     * @deprecated The API has no /orders/payments route, so this call always fails.
     *             Use getOrderPayments() with an order identifier.
     */
    public function getAllOrdersPayments()
    {
        $endpoint = "/orders/payments";
        return $this->makeRequest("GET", $endpoint);
    }

    public function createOrganizationCurrency(CreateOrganizationCurrencyPayload $payload)
    {
        $endpoint = "/organization/currencies";
        $payloadArray = $payload->toArray();
        $response = $this->makeRequest("POST", $endpoint, null, $payloadArray);
        $this->currencyIdsByUnit = null;
        return $response;
    }

    public function getOrganizationPartners()
    {
        $endpoint = "/organization/partners";
        return $this->makeRequest("GET", $endpoint);
    }

    public function getOrganizationLimitsById(string $id, array $params = [])
    {
        $endpoint = $this->path('/organization/%s/limits', $id);
        return $this->makeRequest("GET", $endpoint, $params ?: null);
    }

    /**
     * Checks that the API is reachable; the server answers with the text "OK".
     */
    public function healthCheck()
    {
        $body = $this->makeRequest('GET', '/health', null, null, true);
        return ['status' => trim((string)$body)];
    }

    public function getSystemConfig()
    {
        $endpoint = "/system/config";
        return $this->makeRequest("GET", $endpoint);
    }
}
