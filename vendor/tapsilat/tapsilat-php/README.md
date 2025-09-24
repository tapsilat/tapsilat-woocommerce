# Tapsilat Client SDK for PHP

[![CI](https://github.com/tapsilat/tapsilat-php/workflows/CI/badge.svg)](https://github.com/tapsilat/tapsilat-php/actions?query=workflow%3ACI)
[![Packagist](https://img.shields.io/packagist/v/tapsilat/tapsilat-php.svg)](https://packagist.org/packages/tapsilat/tapsilat-php)
[![Packagist Downloads](https://img.shields.io/packagist/dt/tapsilat/tapsilat-php.svg)](https://packagist.org/packages/tapsilat/tapsilat-php)

Create orders and retrieve secure checkout URLs.

Requires PHP 7.4+

## Installation

You can install the package via Composer:

```bash
composer require tapsilat/tapsilat-php
```

or

```bash
git clone https://github.com/tapsilat/tapsilat-php.git
cd tapsilat-php
composer install
```

## Usage

### Environment Setup
Create a `.env` file:
```env
TAPSILAT_API_KEY=your_api_key_here
```

### TapsilatAPI Initialization
```php
<?php

require_once 'vendor/autoload.php';

use Tapsilat\TapsilatAPI;

$apiKey = $_ENV['TAPSILAT_API_KEY'];
$client = new TapsilatAPI($apiKey);
```

### Validators
The SDK includes built-in validators for common data types:

#### GSM Number Validation
```php
use Tapsilat\Validators;

// Valid formats
$validGsm = Validators::validateGsmNumber("+905551234567");  // International with +
$validGsm = Validators::validateGsmNumber("00905551234567"); // International with 00
$validGsm = Validators::validateGsmNumber("05551234567");    // National format
$validGsm = Validators::validateGsmNumber("5551234567");     // Local format

// Automatically cleans formatting
$cleanGsm = Validators::validateGsmNumber("+90 555 123-45-67"); // Returns: "+905551234567"

// Throws APIException for invalid formats
try {
    Validators::validateGsmNumber("invalid-phone");
} catch (APIException $e) {
    echo "Error: " . $e->error;
}
```

#### Installments Validation
```php
use Tapsilat\Validators;

// Valid installment strings
$installments = Validators::validateInstallments("1,2,3,6");     // Returns: [1, 2, 3, 6]
$installments = Validators::validateInstallments("1, 2, 3, 6"); // Handles spaces
$installments = Validators::validateInstallments("");           // Returns: [1] (default)

// Throws APIException for invalid values
try {
    Validators::validateInstallments("1,15,abc"); // 15 > 12, abc is not a number
} catch (APIException $e) {
    echo "Error: " . $e->error;
}
```

### Order Create Process
```php
use Tapsilat\Models\BuyerDTO;
use Tapsilat\Models\OrderCreateDTO;

// GSM number will be automatically validated in createOrder
$buyer = new BuyerDTO(
    "John",
    "Doe",
    null, // birth_date
    null, // city
    null, // country
    "test@example.com", // email
    "+90 555 123-45-67" // Will be cleaned automatically
);
$order = new OrderCreateDTO(100, "TRY", "tr", $buyer);

$orderResponse = $client->createOrder($order);
```

### Get Order Details
```php
$referenceId = "mock-uuid-reference-id";
$orderDetails = $client->getOrder($referenceId);
```

### Get Order Details by Conversation ID
```php
$conversationId = "mock-uuid-conversation-id";
$orderDetails = $client->getOrderByConversationId($conversationId);
```

### Get Order List
```php
$orderList = $client->getOrderList($page = 1, $perPage = 5);
```

### Get Order Submerchants
```php
$orderList = $client->getOrderSubmerchants($page = 1, $perPage = 5);
```

### Get Checkout URL
```php
$referenceId = "mock-uuid-reference-id";
$checkoutUrl = $client->getCheckoutUrl($referenceId);
```

### Order Cancel Process
```php
$referenceId = "mock-uuid-reference-id";
$client->cancelOrder($referenceId);
```

### Order Refund Process
```php
use Tapsilat\Models\RefundOrderDTO;

$refundData = new RefundOrderDTO(100, "mock-uuid-reference-id");
$client->refundOrder($refundData);
```

### Order Refund All Process
```php
$referenceId = "mock-uuid-reference-id";
$client->refundAllOrder($referenceId);
```

### Get Order Payment Details
```php
$referenceId = "mock-uuid-reference-id";
$client->getOrderPaymentDetails($referenceId);

// You can get with conversation_id too
$conversationId = "mock-uuid-conversation-id";
$client->getOrderPaymentDetails($referenceId, $conversationId);
```

### Get Order Status
```php
$referenceId = "mock-uuid-reference-id";
$client->getOrderStatus($referenceId);
```

### Get Order Transactions
```php
$referenceId = "mock-uuid-reference-id";
$client->getOrderTransactions($referenceId);
```

### Get Order Term
```php
$termReferenceId = "mock-uuid-term-reference-id";
$client->getOrderTerm($termReferenceId);
```

### Create Order Term
```php
use Tapsilat\Models\OrderPaymentTermCreateDTO;

$orderId = "mock-order-id";
$terms = [
    new OrderPaymentTermCreateDTO(
        $orderId,
        "TERM-123000456",
        5000,
        "2025-10-10 00:00",
        1,
        true,
        "PENDING"
    ),
    new OrderPaymentTermCreateDTO(
        $orderId,
        "TERM-123000457",
        5000,
        "2025-11-10 00:00",
        2,
        true,
        "PENDING"
    )
];

foreach ($terms as $term) {
    $client->createOrderTerm($term);
}
```

### Delete Order Term
```php
$orderId = "mock-uuid-order-id";
$termReferenceId = "TERM-123000456";
$client->deleteOrderTerm($orderId, $termReferenceId);
```

### Update Order Term
```php
use Tapsilat\Models\OrderPaymentTermUpdateDTO;

$term = new OrderPaymentTermUpdateDTO(
    "TERM-123000457",
    null, // amount
    "2025-12-10 00:00", // due_date
    null, // paid_date
    true  // required
);
$client->updateOrderTerm($term);
```

### Refund Order Term
```php
use Tapsilat\Models\OrderTermRefundRequest;

$term = new OrderTermRefundRequest("term-id", 100);
$client->refundOrderTerm($term);
```

### Terminate Order Term
```php
$referenceId = "mock-uuid-reference-id";
$client->orderTerminate($referenceId);
```

### Manual Callback for Order
```php
$referenceId = "mock-uuid-reference-id";
$conversationId = "mock-uuid-conversation-id";
$client->orderManualCallback($referenceId, $conversationId);
```

### Order Related Reference Update
```php
$referenceId = "mock-uuid-reference-id";
$relatedReferenceId = "mock-uuid-related-reference-id";
$client->orderRelatedUpdate($referenceId, $relatedReferenceId);
```

## Requirements

- PHP >= 7.4

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Testing

Run the tests with PHPUnit:

```bash
composer test
```

## CI/CD

This package uses GitHub Actions for continuous integration and deployment:

- **CI**: Runs on every push to main/develop and pull requests
  - Tests against PHP 7.4, 8.0, 8.1, 8.2, and 8.3
  - Validates composer.json
  - Runs PHPUnit tests
  - Checks code style with PHP_CodeSniffer

- **Release & Publish**: Automatically triggered when a new tag is pushed
  - Creates a GitHub release (if it doesn't exist)
  - Validates and tests the package
  - Publishes to Packagist (if configured)
  - Verifies package availability on Packagist

- **Packagist Webhook**: Automatically notifies Packagist on every push to main
  - Triggers on pushes to main branch and merged PRs
  - Keeps Packagist updated with latest changes
  - Works alongside GitHub webhook for immediate updates

- **Dependabot**: Automatically updates dependencies
  - Weekly updates for Composer packages
  - Weekly updates for GitHub Actions
  - Creates pull requests for review

To create a new release:

```bash
git tag v1.0.0
git push origin v1.0.0
```

This will automatically:
1. Create a GitHub release
2. Publish the package to Packagist
3. Make it available via `composer require tapsilat/tapsilat-php`

## Docker Development

This package includes Docker support for easy development setup.

### Quick Start

```bash
# Build the Docker image
./docker-dev.sh build

# Start the development container
./docker-dev.sh start

# Open a shell in the container
./docker-dev.sh shell

# Run tests
./docker-dev.sh test
```

### Available Commands

- `./docker-dev.sh build` - Build the Docker image
- `./docker-dev.sh start` - Start the development container
- `./docker-dev.sh stop` - Stop the development container
- `./docker-dev.sh restart` - Restart the development container
- `./docker-dev.sh shell` - Open a shell in the development container
- `./docker-dev.sh test` - Run tests in the container
- `./docker-dev.sh install` - Install dependencies
- `./docker-dev.sh clean` - Clean up containers and images
- `./docker-dev.sh help` - Show help message

### Manual Docker Commands

```bash
# Build and start with docker-compose
docker-compose up -d tapsilat-dev

# Run tests
docker-compose run --rm tapsilat-test

# Execute commands in the container
docker-compose exec tapsilat-dev composer install
docker-compose exec tapsilat-dev composer test
docker-compose exec tapsilat-dev php example.php
```
