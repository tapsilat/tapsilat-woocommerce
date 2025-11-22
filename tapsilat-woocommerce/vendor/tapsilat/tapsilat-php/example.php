<?php

require_once __DIR__ . '/vendor/autoload.php';

use Tapsilat\TapsilatAPI;
use Tapsilat\APIException;
use Tapsilat\Models\BuyerDTO;
use Tapsilat\Models\OrderCreateDTO;
use Tapsilat\Models\BasketItemDTO;
use Tapsilat\Models\BasketItemPayerDTO;
use Tapsilat\Models\BillingAddressDTO;
use Tapsilat\Models\CheckoutDesignDTO;
use Tapsilat\Models\ShippingAddressDTO;
use Tapsilat\Validators;

/**
 * Load environment variables from .env file
 */
function loadEnv($path = __DIR__ . '/.env')
{
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}

/**
 * Get API client from environment variable
 */
function getApiClient()
{
    // Load .env file
    loadEnv();

    $apiKey = $_ENV['API_KEY'] ?? $_ENV['TAPSILAT_API_KEY'] ?? '';
    if (empty($apiKey)) {
        echo "Error: API_KEY or TAPSILAT_API_KEY is not set in .env file.\n";
        return null;
    }

    echo "Using API Key from .env file...\n";
    return new TapsilatAPI($apiKey);
}

/**
 * Process order creation with error handling
 */
function processOrderCreation($client, $orderPayload, $scenarioName)
{
    echo str_repeat("#", 16) . "\n";
    echo $scenarioName . "\n";

    if (!$client) {
        echo "Client is not initialized.\n";
        return;
    }

    try {
        $response = $client->createOrder($orderPayload);
        echo "Order created successfully!\n";
        echo "Reference ID: " . $response->getReferenceId() . "\n";

        // Get checkout URL using reference_id (like Python version)
        $checkoutUrl = $client->getCheckoutUrl($response->getReferenceId());
        echo "Checkout URL: " . $checkoutUrl . "\n";
    } catch (APIException $e) {
        echo "API Error: " . $e->error . "\n";
        echo "Status Code: " . $e->statusCode . "\n";
        echo "Code: " . $e->code . "\n";
    } catch (Exception $e) {
        echo "Unexpected error: " . $e->getMessage() . "\n";
    }
}

/**
 * Scenario 1: Basic Order
 */
function runScenario1BasicOrder($client)
{
    $buyer = new BuyerDTO("John", "Doe", null, null, null, "test@example.com");
    $orderPayload = new OrderCreateDTO(100.00, "TRY", "tr", $buyer);
    processOrderCreation($client, $orderPayload, "Scenario 1: Basic Order");
}

/**
 * Scenario 2: Order with Basket Items
 */
function runScenario2OrderWithBasketItems($client)
{
    $buyer = new BuyerDTO("John", "Doe", null, null, null, "test@example.com");

    $basketItemPayer = new BasketItemPayerDTO(
        "Test Address",  // address
        "payer_ref0_item1", // reference_id
        "Test Tax Office", // tax_office
        "Test Company", // title
        "PERSONAL", // type
        "12345678901", // vat
    );

    $basketItem1 = new BasketItemDTO(
        "Electronics", // category1
        "Phones", // category2
        5.0, // commission_amount
        "DISCOUNT10", // coupon
        10.0, // coupon_discount
        "Item data", // data
        "item_1", // id
        "PHYSICAL", // item_type
        "Test Product 1", // name
        0, // paid_amount
        $basketItemPayer, // payer
        50.0, // price
        1 // quantity
    );

    $basketItem2 = new BasketItemDTO(
        "Electronics", // category1
        "Accessories", // category2
        2.5, // commission_amount
        null, // coupon
        0.0, // coupon_discount
        "Item data 2", // data
        "item_2", // id
        "PHYSICAL", // item_type
        "Test Product 2", // name
        0, // paid_amount
        $basketItemPayer, // payer
        50.0, // price
        1 // quantity
    );

    $orderPayload = new OrderCreateDTO(
        100.00, // amount
        "TRY", // currency
        "tr", // locale
        $buyer, // buyer
        [$basketItem1, $basketItem2] // basket_items
    );

    processOrderCreation($client, $orderPayload, "Scenario 2: Order with Basket Items");
}

/**
 * Scenario 3: Order with Addresses
 */
function runScenario3OrderWithAddresses($client)
{
    $buyer = new BuyerDTO("John", "Doe", null, null, null, "test@example.com");

    $billingAddress = new BillingAddressDTO(
        "Test Billing Address", // address
        "PERSONAL", // billing_type
        "TC", // citizenship
        "Istanbul", // city
        "John Doe", // contact_name
        "+905551234567", // contact_phone
        "Turkey", // country
        "Besiktas", // district
        "Istanbul Tax Office", // tax_office
        "John Doe", // title
        "12345678901", // vat_number
        "34000" // zip_code
    );

    $shippingAddress = new ShippingAddressDTO(
        "Test Shipping Address", // address
        "Istanbul", // city
        "John Doe", // contact_name
        "Turkey", // country
        "2025-12-31", // shipping_date
        "TRACK123", // tracking_code
        "34000" // zip_code
    );

    $orderPayload = new OrderCreateDTO(
        100.00, // amount
        "TRY", // currency
        "tr", // locale
        $buyer, // buyer
        null, // basket_items
        $billingAddress, // billing_address
        null, // checkout_design
        null, // conversation_id
        null, // enabled_installments
        null, // external_reference_id
        null, // metadata
        null, // order_cards
        null, // paid_amount
        null, // partial_payment
        null, // payment_failure_url
        null, // payment_methods
        null, // payment_options
        null, // payment_success_url
        null, // payment_terms
        null, // pf_sub_merchant
        $shippingAddress // shipping_address
    );

    processOrderCreation($client, $orderPayload, "Scenario 3: Order with Addresses");
}

/**
 * Scenario 4: Installments and Payment Methods
 */
function runScenario4InstallmentsAndPaymentMethods($client)
{
    $buyer = new BuyerDTO("John", "Doe", null, null, null, "test@example.com");

    $orderPayload = new OrderCreateDTO(
        1200.00, // amount
        "TRY", // currency
        "tr", // locale
        $buyer, // buyer
        null, // basket_items
        null, // billing_address
        null, // checkout_design
        null, // conversation_id
        [1, 2, 3, 6], // enabled_installments
        "EXT_REF_123", // external_reference_id
        null, // metadata
        null, // order_cards
        null, // paid_amount
        false, // partial_payment
        "https://example.com/payment-failure", // payment_failure_url
        true, // payment_methods - boolean value instead of string
        null, // payment_options
        "https://example.com/payment-success" // payment_success_url
    );

    processOrderCreation($client, $orderPayload, "Scenario 4: Installments and Payment Methods");
}

/**
 * Scenario 5: Detailed Checkout Design
 */
function runScenario5DetailedCheckoutDesign($client)
{
    $buyer = new BuyerDTO("John", "Doe", null, null, null, "test@example.com");

    $checkoutDesign = new CheckoutDesignDTO(
        "#FFFFFF", // input_background_color
        "#000000", // input_text_color
        "#333333", // label_text_color
        "#F5F5F5", // left_background_color
        "https://example.com/logo.png", // logo
        "<p>Custom order details HTML content</p>", // order_detail_html
        "#007BFF", // pay_button_color
        "https://example.com/custom-redirect", // redirect_url
        "#FFFFFF", // right_background_color
        "#000000" // text_color
    );

    $orderPayload = new OrderCreateDTO(
        250.00, // amount
        "TRY", // currency
        "tr", // locale
        $buyer, // buyer
        null, // basket_items
        null, // billing_address
        $checkoutDesign // checkout_design
    );

    processOrderCreation($client, $orderPayload, "Scenario 5: Detailed Checkout Design");
}

/**
 * Scenario 6: Validation Demo
 */
function runScenario6ValidationDemo($client)
{
    echo str_repeat("#", 16) . "\n";
    echo "Scenario 6: Validation Demo\n";

    // GSM Number Validation Demo
    echo "GSM Number Validation:\n";
    try {
        $cleanGsm = Validators::validateGsmNumber("+90 555 123-45-67");
        echo "Cleaned GSM: $cleanGsm\n";

        $validGsm = Validators::validateGsmNumber("05551234567");
        echo "Valid GSM: $validGsm\n";
    } catch (APIException $e) {
        echo "GSM Validation Error: " . $e->error . "\n";
    }

    // Installments Validation Demo
    echo "\nInstallments Validation:\n";
    try {
        $installments = Validators::validateInstallments("1,2,3,6");
        echo "Valid installments: " . json_encode($installments) . "\n";

        $installmentsWithSpaces = Validators::validateInstallments("1, 2, 3, 6");
        echo "Installments with spaces: " . json_encode($installmentsWithSpaces) . "\n";

        $defaultInstallments = Validators::validateInstallments("");
        echo "Default installments: " . json_encode($defaultInstallments) . "\n";
    } catch (APIException $e) {
        echo "Installments Validation Error: " . $e->error . "\n";
    }
}

/**
 * Scenario 7: Validation Errors
 */
function runScenario7ValidationErrors($client)
{
    echo str_repeat("#", 16) . "\n";
    echo "Scenario 7: Validation Errors\n";

    // Invalid GSM Number
    echo "Testing invalid GSM number:\n";
    try {
        Validators::validateGsmNumber("invalid-phone");
    } catch (APIException $e) {
        echo "Expected GSM Error: " . $e->error . "\n";
    }

    // Invalid Installments
    echo "\nTesting invalid installments:\n";
    try {
        Validators::validateInstallments("1,15,abc");
    } catch (APIException $e) {
        echo "Expected Installments Error: " . $e->error . "\n";
    }

    // Too short phone number
    echo "\nTesting too short phone number:\n";
    try {
        Validators::validateGsmNumber("+90123");
    } catch (APIException $e) {
        echo "Expected Short Phone Error: " . $e->error . "\n";
    }
}

// Main execution
if (php_sapi_name() === 'cli') {
    echo "=== Tapsilat PHP SDK Usage Examples ===\n\n";

    $apiClient = getApiClient();

    if ($apiClient) {
        runScenario1BasicOrder($apiClient);
        runScenario2OrderWithBasketItems($apiClient);
        runScenario3OrderWithAddresses($apiClient);
        runScenario4InstallmentsAndPaymentMethods($apiClient);
        runScenario5DetailedCheckoutDesign($apiClient);
        runScenario6ValidationDemo($apiClient);
        runScenario7ValidationErrors($apiClient);
    }

    echo "\n=== Examples completed ===\n";
}
