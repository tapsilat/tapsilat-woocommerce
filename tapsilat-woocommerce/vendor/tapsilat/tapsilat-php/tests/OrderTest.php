<?php
namespace Tapsilat\Tests;

use PHPUnit\Framework\TestCase;
use Tapsilat\TapsilatAPI;
use Tapsilat\APIException;
use Tapsilat\Models\BuyerDTO;
use Tapsilat\Models\OrderCreateDTO;
use Tapsilat\Models\BasketItemDTO;
use Tapsilat\Models\BasketItemPayerDTO;
use Tapsilat\Models\BillingAddressDTO;
use Tapsilat\Models\CheckoutDesignDTO;
use Tapsilat\Models\MetadataDTO;
use Tapsilat\Models\OrderCardDTO;
use Tapsilat\Models\PaymentTermDTO;
use Tapsilat\Models\OrderPFSubMerchantDTO;
use Tapsilat\Models\OrderResponse;
use Tapsilat\Models\RefundOrderDTO;
use Tapsilat\Models\ShippingAddressDTO;
use Tapsilat\Models\SubmerchantDTO;
use Tapsilat\Models\SubOrganizationDTO;

class OrderTest extends TestCase
{
    public function testOrderToArray()
    {
        $buyer = new BuyerDTO("John", "Doe", null, null, null, "test@example.com");
        $order = new OrderCreateDTO(
            100,
            "TRY",
            "tr",
            $buyer
        );
        $jsonData = $order->toArray();
        $this->assertEquals(100, $jsonData["amount"]);
        $this->assertEquals("TRY", $jsonData["currency"]);
        $this->assertEquals("tr", $jsonData["locale"]);
        $this->assertEquals("John", $jsonData["buyer"]["name"]);
        $this->assertEquals("Doe", $jsonData["buyer"]["surname"]);
        $this->assertEquals("test@example.com", $jsonData["buyer"]["email"]);
    }

    public function testBasketItemPayerDTOToArray()
    {
        $payer = new BasketItemPayerDTO("uskudar", "123456789", null, null, "PERSONAL");
        $payerArray = $payer->toArray();
        $this->assertEquals("uskudar", $payerArray["address"]);
        $this->assertEquals("PERSONAL", $payerArray["type"]);
        $this->assertEquals("123456789", $payerArray["reference_id"]);
        $this->assertArrayNotHasKey("tax_office", $payerArray);
    }

    public function testBasketItemDTOToArray()
    {
        $payerData = new BasketItemPayerDTO("uskudar", null, null, null, "BUSINESS");
        $item = new BasketItemDTO(
            null, null, null, null, null, null,
            "BI101", "PHYSICAL", "Binocular", null, $payerData, 19.99, 1
        );
        $itemArray = $item->toArray();
        $this->assertEquals("BI101", $itemArray["id"]);
        $this->assertEquals("Binocular", $itemArray["name"]);
        $this->assertEquals(19.99, $itemArray["price"]);
        $this->assertEquals(1, $itemArray["quantity"]);
        $this->assertEquals("PHYSICAL", $itemArray["item_type"]);
        $this->assertEquals("uskudar", $itemArray["payer"]["address"]);
        $this->assertEquals("BUSINESS", $itemArray["payer"]["type"]);
        $this->assertArrayNotHasKey("category1", $itemArray);
    }

    public function testBillingAddressDTOToArray()
    {
        $billing = new BillingAddressDTO("uskudar", null, null, "Istanbul", "Jane Doe", null, "TR");
        $billingArray = $billing->toArray();
        $this->assertEquals("uskudar", $billingArray["address"]);
        $this->assertEquals("Istanbul", $billingArray["city"]);
        $this->assertEquals("TR", $billingArray["country"]);
        $this->assertEquals("Jane Doe", $billingArray["contact_name"]);
        $this->assertArrayNotHasKey("zip_code", $billingArray);
    }

    public function testCheckoutDesignDTOToArray()
    {
        $design = new CheckoutDesignDTO(null, null, null, null, "http://example.com/logo.png", null, "#FF0000");
        $designArray = $design->toArray();
        $this->assertEquals("#FF0000", $designArray["pay_button_color"]);
        $this->assertEquals("http://example.com/logo.png", $designArray["logo"]);
        $this->assertArrayNotHasKey("input_background_color", $designArray);
    }

    public function testMetadataDTOToArray()
    {
        $meta = new MetadataDTO("key", "value");
        $metaArray = $meta->toArray();
        $this->assertEquals("key", $metaArray["key"]);
        $this->assertEquals("value", $metaArray["value"]);
    }

    public function testOrderCardDTOToArray()
    {
        $card = new OrderCardDTO("123456789", 1);
        $cardArray = $card->toArray();
        $this->assertEquals("123456789", $cardArray["card_id"]);
        $this->assertEquals(1, $cardArray["card_sequence"]);
    }

    public function testPaymentTermDTOToArray()
    {
        $term = new PaymentTermDTO(50.0, null, "2025-10-21T23:59:59Z", null, null, "PENDING", null, 1);
        $termArray = $term->toArray();
        $this->assertEquals(50.0, $termArray["amount"]);
        $this->assertEquals("2025-10-21T23:59:59Z", $termArray["due_date"]);
        $this->assertEquals("PENDING", $termArray["status"]);
        $this->assertEquals(1, $termArray["term_sequence"]);
        $this->assertArrayNotHasKey("data", $termArray);
    }

    public function testOrderPFSubMerchantDTOToArray()
    {
        $pfSub = new OrderPFSubMerchantDTO(null, null, null, null, "123456789", "1234", "John Doe");
        $pfSubArray = $pfSub->toArray();
        $this->assertEquals("123456789", $pfSubArray["id"]);
        $this->assertEquals("John Doe", $pfSubArray["name"]);
        $this->assertEquals("1234", $pfSubArray["mcc"]);
        $this->assertArrayNotHasKey("address", $pfSubArray);
    }

    public function testShippingAddressDTOToArray()
    {
        $shipping = new ShippingAddressDTO("uskudar", "Istanbul", "Jane Doe", "Turkey");
        $shippingArray = $shipping->toArray();
        $this->assertEquals("uskudar", $shippingArray["address"]);
        $this->assertEquals("Istanbul", $shippingArray["city"]);
        $this->assertEquals("Turkey", $shippingArray["country"]);
        $this->assertEquals("Jane Doe", $shippingArray["contact_name"]);
    }

    public function testSubOrganizationDTOToArray()
    {
        $subOrg = new SubOrganizationDTO(
            null, null, null, null, null, null, null, null, null,
            "ACME Inc.", "ACME Inc.", null, "sub merchant key"
        );
        $subOrgArray = $subOrg->toArray();
        $this->assertEquals("ACME Inc.", $subOrgArray["organization_name"]);
        $this->assertEquals("sub merchant key", $subOrgArray["sub_merchant_key"]);
        $this->assertEquals("ACME Inc.", $subOrgArray["legal_company_title"]);
        $this->assertArrayNotHasKey("acquirer", $subOrgArray);
    }

    public function testSubmerchantDTOToArray()
    {
        $submerchant = new SubmerchantDTO(20.49, "merchant reference id", "BI101");
        $submerchantArray = $submerchant->toArray();
        $this->assertEquals(20.49, $submerchantArray["amount"]);
        $this->assertEquals("merchant reference id", $submerchantArray["merchant_reference_id"]);
        $this->assertEquals("BI101", $submerchantArray["order_basket_item_id"]);
    }

    public function testRefundOrderDTOToArray()
    {
        $dto = new RefundOrderDTO(50.0, "ref123", "item001");
        $dtoArray = $dto->toArray();
        $this->assertEquals(50.0, $dtoArray["amount"]);
        $this->assertEquals("ref123", $dtoArray["reference_id"]);
        $this->assertEquals("item001", $dtoArray["order_item_id"]);

        $dtoFull = new RefundOrderDTO(100.0, "ref456", "item002", "payment002");
        $dtoFullArray = $dtoFull->toArray();
        $this->assertEquals(100.0, $dtoFullArray["amount"]);
        $this->assertEquals("ref456", $dtoFullArray["reference_id"]);
        $this->assertEquals("item002", $dtoFullArray["order_item_id"]);
        $this->assertEquals("payment002", $dtoFullArray["order_item_payment_id"]);
    }

    public function testCreateOrderSuccess()
    {
        $expectedApiJsonResponse = [
            'order_id' => 'mock-03d03353-78bc-4432-9da6-1433ecd7fbbb',
            'reference_id' => 'mock-03d03353-9b5b-4289-b231-ffbe50f8a79d',
        ];

        // Create a mock of TapsilatAPI
        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        // Set expectation for makeRequest method
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/create', null, $this->anything())
            ->willReturn($expectedApiJsonResponse);

        $buyer = new BuyerDTO('John', 'Doe', null, null, null, 'test@example.com');
        $orderPayloadDto = new OrderCreateDTO(
            100,
            'TRY',
            'tr',
            $buyer
        );

        $orderResponseObj = $apiMock->createOrder($orderPayloadDto);

        $this->assertInstanceOf(OrderResponse::class, $orderResponseObj);
        $this->assertEquals($expectedApiJsonResponse['order_id'], $orderResponseObj->getOrderId());
        $this->assertEquals($expectedApiJsonResponse['reference_id'], $orderResponseObj->getReferenceId());
    }

    public function testCreateOrderWithBasketItems()
    {
        $expectedApiJsonResponse = [
            'order_id' => 'order_basket',
            'reference_id' => 'ref_basket',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/create', null, $this->anything())
            ->willReturn($expectedApiJsonResponse);

        $buyerData = new BuyerDTO('Test', 'User');
        $basketItemPayer = new BasketItemPayerDTO(null, 'payer_ref0_item1');
        $basketItem1 = new BasketItemDTO(
            null, null, null, null, null, null,
            'B001', null, 'Item 1', null, $basketItemPayer, 10.0, 1
        );
        $basketItem2 = new BasketItemDTO(
            null, null, null, null, null, null,
            'B002', null, 'Item 2', null,
            new BasketItemPayerDTO(null, 'payer_ref1_item2'),
            20.49, 2
        );

        $orderPayloadDto = new OrderCreateDTO(
            50.98,
            'TRY',
            'tr',
            $buyerData,
            [$basketItem1, $basketItem2]
        );

        $apiResponse = $apiMock->createOrder($orderPayloadDto);

        $this->assertEquals($expectedApiJsonResponse['order_id'], $apiResponse->getOrderId());
    }

    public function testGetOrderSuccess()
    {
        $referenceId = 'mock-03d03353-9b5b-4289-b231-ffbe50f8a79d';
        $expectedApiJsonResponse = [
            'checkout_url' => 'https://checkout.test.dev?reference_id=mock-03d03353-d2be-4094-b5f6-7b7a8473534e',
            'status' => 8,
            'reference_id' => $referenceId,
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}")
            ->willReturn($expectedApiJsonResponse);

        $result = $apiMock->getOrder($referenceId);

        $this->assertInstanceOf(OrderResponse::class, $result);
        $this->assertEquals($expectedApiJsonResponse['checkout_url'], $result->getCheckoutUrl());
        $this->assertEquals($expectedApiJsonResponse['status'], $result->getData()['status']);
    }

    public function testGetOrderFailure()
    {
        $referenceId = 'mock-failed-reference-id';
        $apiErrorContent = ['code' => 101160, 'error' => 'ORDER_ORDER_DETAIL_ORDER_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}")
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_ORDER_DETAIL_ORDER_NOT_FOUND');

        $apiMock->getOrder($referenceId);
    }

    public function testGetOrderByConversationIdSuccess()
    {
        $conversationId = 'mock-conversation-id';
        $expectedApiJsonResponse = [
            'checkout_url' => 'https://checkout.test.dev?reference_id=mock-03d03353-d2be-4094-b5f6-7b7a8473534e',
            'status' => 8,
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/conversation/{$conversationId}")
            ->willReturn($expectedApiJsonResponse);

        $result = $apiMock->getOrderByConversationId($conversationId);

        $this->assertInstanceOf(OrderResponse::class, $result);
        $this->assertEquals($expectedApiJsonResponse['checkout_url'], $result->getCheckoutUrl());
    }

    public function testGetOrderByConversationIdFailure()
    {
        $conversationId = 'mock-conversation-id';
        $apiErrorContent = ['code' => 101160, 'error' => 'ORDER_ORDER_DETAIL_ORDER_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/conversation/{$conversationId}")
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_ORDER_DETAIL_ORDER_NOT_FOUND');

        $apiMock->getOrderByConversationId($conversationId);
    }

    public function testGetOrderList()
    {
        $page = 1;
        $perPage = 3;
        $expectedApiJsonResponse = [
            'page' => 1,
            'per_page' => 3,
            'rows' => [[], [], []],
            'total' => 24,
            'total_page' => 8,
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedParams = ['page' => $page, 'per_page' => $perPage];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', '/order/list', $expectedParams)
            ->willReturn($expectedApiJsonResponse);

        $result = $apiMock->getOrderList($page, $perPage);

        $this->assertEquals($expectedApiJsonResponse, $result);
    }

    public function testGetOrderSubmerchants()
    {
        $page = 1;
        $perPage = 2;
        $expectedApiJsonResponse = [
            'page' => 1,
            'per_page' => 2,
            'row' => [[], []],
            'total' => 10,
            'total_pages' => 5,
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedParams = ['page' => $page, 'per_page' => $perPage];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', '/order/submerchants', $expectedParams)
            ->willReturn($expectedApiJsonResponse);

        $result = $apiMock->getOrderSubmerchants($page, $perPage);

        $this->assertEquals($expectedApiJsonResponse, $result);
    }

    public function testGetCheckoutUrlSuccess()
    {
        $referenceId = 'mock-ref-for-checkout';
        $expectedCheckoutUrl = 'https://checkout.test.dev?reference_id=mock-checkout-url-generated';
        $getOrderApiJsonResponse = [
            'checkout_url' => $expectedCheckoutUrl,
            'status' => 'Waiting for payment',
            'reference_id' => $referenceId,
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}")
            ->willReturn($getOrderApiJsonResponse);

        $checkoutUrlResult = $apiMock->getCheckoutUrl($referenceId);

        $this->assertEquals($expectedCheckoutUrl, $checkoutUrlResult);
    }

    public function testCancelOrderNotFound()
    {
        $referenceId = 'mock-reference-id';
        $apiErrorContent = [
            'code' => 101550,
            'error' => 'ORDER_CANCEL_ORDER_GET_ORDER_NOT_FOUND',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/cancel', null, $expectedPayload)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_CANCEL_ORDER_GET_ORDER_NOT_FOUND');

        $apiMock->cancelOrder($referenceId);
    }

    public function testCancelOrderSuccess()
    {
        $referenceId = 'mock-reference-id';
        $expectedApiJsonResponse = [
            'is_success' => true,
            'error' => 'ORDER_CANCEL_SUCCESS',
            'status' => '101645',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/cancel', null, $expectedPayload)
            ->willReturn($expectedApiJsonResponse);

        $apiResponse = $apiMock->cancelOrder($referenceId);

        $this->assertEquals($expectedApiJsonResponse, $apiResponse);
    }

    public function testRefundOrderSuccess()
    {
        $expectedApiJsonResponse = ['is_success' => true, 'error' => 'REFUND_SUCCESSFUL'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $refundPayloadDto = new RefundOrderDTO(50.0, 'mock-reference-id');
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/refund', null, $this->anything())
            ->willReturn($expectedApiJsonResponse);

        $apiResponse = $apiMock->refundOrder($refundPayloadDto);

        $this->assertEquals($expectedApiJsonResponse, $apiResponse);
    }

    public function testRefundOrderFailure()
    {
        $apiErrorContent = ['code' => 201010, 'error' => 'REFUND_VALIDATION_ERROR'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $refundPayloadDto = new RefundOrderDTO(0, 'order_ref_invalid');
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/refund', null, $this->anything())
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('REFUND_VALIDATION_ERROR');

        $apiMock->refundOrder($refundPayloadDto);
    }

    public function testRefundAllOrderSuccess()
    {
        $referenceId = 'order_ref_xyz';
        $expectedApiJsonResponse = ['is_success' => true, 'error' => 'REFUND_ALL_SUCCESSFUL'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/refund-all', null, $expectedPayload)
            ->willReturn($expectedApiJsonResponse);

        $apiResponse = $apiMock->refundAllOrder($referenceId);

        $this->assertEquals($expectedApiJsonResponse, $apiResponse);
    }

    public function testRefundAllOrderFailure()
    {
        $referenceId = 'order_ref_nonexistent';
        $apiErrorContent = ['code' => 201020, 'error' => 'ORDER_NOT_FOUND_FOR_REFUND_ALL'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/refund-all', null, $expectedPayload)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_NOT_FOUND_FOR_REFUND_ALL');

        $apiMock->refundAllOrder($referenceId);
    }

    public function testGetOrderPaymentDetailsSuccessWithRefId()
    {
        $referenceId = 'mock-reference-id';
        $expectedResponse = ['id' => 'mock-payment-details-id'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}/payment-details")
            ->willReturn($expectedResponse);

        $result = $apiMock->getOrderPaymentDetails($referenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetOrderPaymentDetailsSuccessWithConvId()
    {
        $referenceId = 'mock-reference-id';
        $conversationId = 'mock-conversation-id';
        $expectedResponse = ['id' => 'mock-payment-details-id-conv'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = [
            'conversation_id' => $conversationId,
            'reference_id' => $referenceId,
        ];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/payment-details', null, $expectedPayload)
            ->willReturn($expectedResponse);

        $result = $apiMock->getOrderPaymentDetails($referenceId, $conversationId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetOrderPaymentDetailsNotFound()
    {
        $referenceId = 'mock-reference-id';
        $apiErrorContent = [
            'code' => 101230,
            'error' => 'ORDER_ORDER_PAYMENT_DETAIL_ORDER_DETAIL_NOT_FOUND',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}/payment-details")
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_ORDER_PAYMENT_DETAIL_ORDER_DETAIL_NOT_FOUND');

        $apiMock->getOrderPaymentDetails($referenceId);
    }

    public function testGetOrderStatusSuccess()
    {
        $referenceId = 'mock-reference-id';
        $expectedResponse = ['status' => 'Refunded'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}/status")
            ->willReturn($expectedResponse);

        $result = $apiMock->getOrderStatus($referenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetOrderStatusNotFound()
    {
        $referenceId = 'mock-reference-id';
        $apiErrorContent = ['code' => 100810, 'error' => 'ORDER_GET_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}/status")
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_GET_NOT_FOUND');

        $apiMock->getOrderStatus($referenceId);
    }

    public function testGetOrderTransactionsSuccess()
    {
        $referenceId = 'mock-reference-id';
        $expectedResponse = [['id' => 'mock-transaction-1']];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}/transactions")
            ->willReturn($expectedResponse);

        $result = $apiMock->getOrderTransactions($referenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetOrderTransactionsNotFound()
    {
        $referenceId = 'mock-reference-id';
        $apiErrorContent = [
            'code' => 101260,
            'error' => 'ORDER_GET_ORDER_TXS_GET_ORDER_NOT_FOUND',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', "/order/{$referenceId}/transactions")
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_GET_ORDER_TXS_GET_ORDER_NOT_FOUND');

        $apiMock->getOrderTransactions($referenceId);
    }

    public function testGetOrderTermSuccess()
    {
        $termReferenceId = 'mock-term-ref-id';
        $expectedResponse = [
            'term_sequence' => 1,
            'amount' => 100,
            'status' => 'PENDING',
            'due_date' => ['seconds' => 1760486400],
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedParams = ['term_reference_id' => $termReferenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', '/order/term', $expectedParams)
            ->willReturn($expectedResponse);

        $result = $apiMock->getOrderTerm($termReferenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testGetOrderTermFailure()
    {
        $termReferenceId = 'mock-none-term-ref-id';
        $apiErrorContent = ['code' => 313010, 'error' => 'ORDER_GET_PAYMENT_TERM_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedParams = ['term_reference_id' => $termReferenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('GET', '/order/term', $expectedParams)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_GET_PAYMENT_TERM_NOT_FOUND');

        $apiMock->getOrderTerm($termReferenceId);
    }

    public function testCreateOrderTermSuccess()
    {
        $payloadDto = new \Tapsilat\Models\OrderPaymentTermCreateDTO(
            'order123',
            'term-ref-create',
            200,
            '2025-10-10 00:00:00',
            2,
            false,
            'active'
        );
        $expectedResponse = ['message' => 'ORDER_ADD_PAYMENT_TERM_SUCCESS', 'code' => 156050];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/term', null, $this->anything())
            ->willReturn($expectedResponse);

        $result = $apiMock->createOrderTerm($payloadDto);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testCreateOrderTermFailureExceedsOrderAmount()
    {
        $payloadDto = new \Tapsilat\Models\OrderPaymentTermCreateDTO(
            'order123',
            'term-ref-create',
            600,
            '2025-10-10 00:00:00',
            2,
            false,
            'PENDING'
        );
        $apiErrorContent = [
            'code' => 156025,
            'error' => 'ORDER_ADD_PAYMENT_TERM_AMOUNT_EXCEEDS_ORDER_AMOUNT',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/term', null, $this->anything())
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_ADD_PAYMENT_TERM_AMOUNT_EXCEEDS_ORDER_AMOUNT');

        $apiMock->createOrderTerm($payloadDto);
    }

    public function testCreateOrderTermFailureStatusInvalid()
    {
        $payloadDto = new \Tapsilat\Models\OrderPaymentTermCreateDTO(
            'order123',
            'term-ref-create',
            600,
            '2025-10-10 00:00:00',
            2,
            false,
            'PENDİNG'
        );
        $apiErrorContent = ['code' => 140141, 'error' => 'TERM_STATUS_INVALID'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/term', null, $this->anything())
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('TERM_STATUS_INVALID');

        $apiMock->createOrderTerm($payloadDto);
    }

    public function testDeleteOrderTermSuccess()
    {
        $orderId = 'mock-order-id';
        $termReferenceId = 'mock-none-term-id';
        $expectedResponse = ['code' => 156090, 'message' => 'ORDER_REMOVE_PAYMENT_TERM_SUCCESS'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['order_id' => $orderId, 'term_reference_id' => $termReferenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('DELETE', '/order/term', null, $expectedPayload)
            ->willReturn($expectedResponse);

        $result = $apiMock->deleteOrderTerm($orderId, $termReferenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testDeleteOrderTermFailure()
    {
        $orderId = 'mock-order-id';
        $termReferenceId = 'mock-none-term-id';
        $apiErrorContent = ['code' => 156070, 'error' => 'ORDER_REMOVE_PAYMENT_TERM_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['order_id' => $orderId, 'term_reference_id' => $termReferenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('DELETE', '/order/term', null, $expectedPayload)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_REMOVE_PAYMENT_TERM_NOT_FOUND');

        $apiMock->deleteOrderTerm($orderId, $termReferenceId);
    }

    public function testUpdateOrderTermSuccess()
    {
        $payloadDto = new \Tapsilat\Models\OrderPaymentTermUpdateDTO(
            'term-to-update',
            60,
            null,
            null,
            null,
            'PENDING'
        );
        $expectedResponse = ['message' => 'ORDER_UPDATE_PAYMENT_TERM_SUCCESS', 'code' => 156130];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('PATCH', '/order/term', null, $this->anything())
            ->willReturn($expectedResponse);

        $result = $apiMock->updateOrderTerm($payloadDto);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testUpdateOrderTermNotFound()
    {
        $payloadDto = new \Tapsilat\Models\OrderPaymentTermUpdateDTO(
            'mock-term-id',
            120
        );
        $apiErrorContent = ['code' => 156110, 'error' => 'ORDER_UPDATE_PAYMENT_TERM_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('PATCH', '/order/term', null, $this->anything())
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_UPDATE_PAYMENT_TERM_NOT_FOUND');

        $apiMock->updateOrderTerm($payloadDto);
    }

    public function testOrderTerminateOrderNotFound()
    {
        $referenceId = 'mock-reference-id';
        $apiErrorContent = ['code' => 338000, 'error' => 'ORDER_TERMINATE_ORDER_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/terminate', null, $expectedPayload)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_TERMINATE_ORDER_NOT_FOUND');

        $apiMock->orderTerminate($referenceId);
    }

    public function testOrderTerminateOrderSuccess()
    {
        $referenceId = 'mock-reference-id';
        $expectedResponse = ['message' => 'ORDER_TERMINATE_ORDER_SUCCESS', 'code' => 338100];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/terminate', null, $expectedPayload)
            ->willReturn($expectedResponse);

        $result = $apiMock->orderTerminate($referenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testOrderCallbackFailed()
    {
        $referenceId = 'mock-reference-id';
        $conversationId = 'mock-conversation-id';
        $apiErrorContent = ['code' => 12000, 'error' => 'ACTION_FAILED'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId, 'conversation_id' => $conversationId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/callback', null, $expectedPayload)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ACTION_FAILED');

        $apiMock->orderManualCallback($referenceId, $conversationId);
    }

    public function testOrderCallbackOrderSuccess()
    {
        $referenceId = 'mock-reference-id';
        $conversationId = 'mock-conversation-id';
        $expectedResponse = ['message' => 'ORDER_CALLBACK_SUCCESS', 'code' => 12100];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId, 'conversation_id' => $conversationId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/callback', null, $expectedPayload)
            ->willReturn($expectedResponse);

        $result = $apiMock->orderManualCallback($referenceId, $conversationId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testOrderRelatedUpdateNotFound()
    {
        $referenceId = 'mock-reference-id';
        $relatedReferenceId = 'mock-related-reference-id';
        $apiErrorContent = ['code' => 12000, 'error' => 'ORDER_NOT_FOUND'];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId, 'related_reference_id' => $relatedReferenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/related', null, $expectedPayload)
            ->willThrowException(new APIException(400, $apiErrorContent['code'], $apiErrorContent['error']));

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('ORDER_NOT_FOUND');

        $apiMock->orderRelatedUpdate($referenceId, $relatedReferenceId);
    }

    public function testOrderRelatedUpdateSuccess()
    {
        $referenceId = 'mock-reference-id';
        $relatedReferenceId = 'mock-related-reference-id';
        $expectedResponse = ['message' => 'ORDER_RELATED_UPDATE_SUCCESS', 'code' => 12100];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $expectedPayload = ['reference_id' => $referenceId, 'related_reference_id' => $relatedReferenceId];
        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/related', null, $expectedPayload)
            ->willReturn($expectedResponse);

        $result = $apiMock->orderRelatedUpdate($referenceId, $relatedReferenceId);

        $this->assertEquals($expectedResponse, $result);
    }

    public function testCreateOrderWithGsmValidation()
    {
        $expectedApiJsonResponse = [
            'order_id' => 'mock-order-with-gsm',
            'reference_id' => 'mock-ref-with-gsm',
        ];

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        $apiMock->expects($this->once())
            ->method('makeRequest')
            ->with('POST', '/order/create', null, $this->anything())
            ->willReturn($expectedApiJsonResponse);

        $buyer = new BuyerDTO('John', 'Doe', null, null, null, 'test@example.com', '+90 555 123-45-67');
        $orderPayloadDto = new OrderCreateDTO(100, 'TRY', 'tr', $buyer);

        $orderResponseObj = $apiMock->createOrder($orderPayloadDto);

        $this->assertInstanceOf(OrderResponse::class, $orderResponseObj);
        $this->assertEquals($expectedApiJsonResponse['order_id'], $orderResponseObj->getOrderId());
    }

    public function testCreateOrderWithInvalidGsmRaisesException()
    {
        $buyer = new BuyerDTO('John', 'Doe', null, null, null, 'test@example.com', 'invalid-phone');
        $orderPayloadDto = new OrderCreateDTO(100, 'TRY', 'tr', $buyer);

        $apiMock = $this->getMockBuilder(TapsilatAPI::class)
            ->onlyMethods(['makeRequest'])
            ->getMock();

        // The validation should happen before the API call, so no API call should be made
        $apiMock->expects($this->never())
            ->method('makeRequest');

        $this->expectException(APIException::class);
        $this->expectExceptionMessage('Invalid phone number format');

        $apiMock->createOrder($orderPayloadDto);
    }
}
