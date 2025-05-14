# M-Pesa PHP SDK

A comprehensive PHP SDK for Safaricom's M-Pesa API, providing easy integration for various M-Pesa services including STK Push, C2B, B2C, reversals, and more.

## Installation

Install the SDK using Composer:

```bash
composer require kemboielvis/mpesa-sdk-php
```

Basic Usage
Initialization
Initialize the M-Pesa SDK with your consumer key and secret from the Safaricom Developer Portal:

```php
<?php
require 'vendor/autoload.php';

use Kemboielvis\MpesaSdkPhp\Mpesa;

// Method 1: Using setCredentials() with parameters
$mpesa = (new Mpesa())->setCredentials(
    'YOUR_CONSUMER_KEY',
    'YOUR_CONSUMER_SECRET',
    'sandbox' // or 'live' for production
);

// Method 2: Using fluent setters
$mpesa = (new Mpesa())
    ->setBusinessCode('YOUR_BUSINESS_CODE')
    ->setPassKey('YOUR_PASS_KEY')
    ->setCredentials(
        'YOUR_CONSUMER_KEY',
        'YOUR_CONSUMER_SECRET',
        'sandbox'
    );
```

Available Services

# 1. STK Push (M-Pesa Express)

Initiate an STK push payment request:

```php
$response = $mpesa->stk()
    ->setTransactionType('CustomerPayBillOnline') // or 'CustomerBuyGoodsOnline'
    ->setAmount(100) // Amount in KES
    ->setPhoneNumber('254712345678') // Customer phone number
    ->setCallbackUrl('https://yourdomain.com/callback')
    ->setAccountReference('INV-12345')
    ->setTransactionDesc('Payment for invoice')
    ->push()
    ->getResponse();

print_r($response);
```

Query STK Push Status

```php
$status = $mpesa->stk()
    ->query('CHECKOUT_REQUEST_ID')
    ->getResponse();
```

# 2. Customer to Business (C2B)

Register URLs

```php
$response = $mpesa->customerToBusiness()
    ->setResponseType('Completed')
    ->setConfirmationUrl('https://yourdomain.com/confirmation')
    ->setValidationUrl('https://yourdomain.com/validation')
    ->registerUrl()
    ->getResponse();
```

Simulate C2B Payment

```php
$response = $mpesa->customerToBusiness()
    ->setCommandId('CustomerPayBillOnline') // or 'CustomerBuyGoodsOnline'
    ->setAmount(100)
    ->setPhoneNumber('254712345678')
    ->setBillRefNumber('INV-123') // For paybill only
    ->simulate()
    ->getResponse();
```

# 3. Business to Customer (B2C)

Send money from business to customer:

```php
$response = $mpesa->businessToCustomer()
    ->setInitiatorName('YOUR_INITIATOR_NAME')
    ->setCommandId('SalaryPayment') // or 'BusinessPayment', 'PromotionPayment'
    ->setAmount(1000)
    ->setPhoneNumber('254712345678')
    ->setRemarks('Salary payment')
    ->setOccasion('May 2023 salary')
    ->paymentRequest(
        'YOUR_INITIATOR_NAME',
        'YOUR_INITIATOR_PASSWORD',
        'SalaryPayment',
        1000,
        'YOUR_BUSINESS_CODE',
        '254712345678',
        'Salary payment',
        'https://yourdomain.com/timeout',
        'https://yourdomain.com/result',
        'May 2023 salary'
    );

print_r($response);
```

# 4. Reversal Service

Reverse a transaction:

```php
$response = $mpesa->reversal()
    ->setInitiator('YOUR_INITIATOR_NAME')
    ->setTransactionId('YOUR_TRANSACTION_ID')
    ->setReceiverIdentifierType('11') // 1=MSISDN, 2=Till, 4=Shortcode
    ->setRemarks('Refund')
    ->setOccasion('Customer refund')
    ->reverse(
        'YOUR_INITIATOR_NAME',
        'YOUR_INITIATOR_PASSWORD',
        'Refund',
        'YOUR_BUSINESS_CODE',
        'YOUR_TRANSACTION_ID',
        '11', // Identifier type
        'https://yourdomain.com/timeout',
        'https://yourdomain.com/result',
        'Customer refund'
    );

print_r($response);
```

RuntimeException for API request failures

Exception for general errors

Always wrap your calls in try-catch blocks:

```php
try {
    $response = $mpesa->stk()->push()->getResponse();
} catch (\Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
```

# Testing

For sandbox testing, use the test credentials from the Safaricom Developer Portal and set the environment to 'sandbox'.

# License

This SDK is open-source software licensed under the MIT license.

# Support

For issues or feature requests, please open an issue on the GitHub repository.

This README provides comprehensive documentation covering:

1. Installation instructions
2. Basic usage examples for all services
3. Configuration options
4. Error handling guidance
5. Testing information
6. License and support details

The documentation follows a clear structure with code examples for each service and explains all the key parameters and methods available in your SDK.
