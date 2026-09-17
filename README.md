# M-Pesa PHP SDK

A PHP SDK for Safaricom M-Pesa APIs with batteries included: STK Push, C2B, B2C, Reversals, Transaction Status, and more. Now with multi-process safe token caching (lock + atomic writes).

## Requirements
- PHP 8.0+
- ext-curl, ext-openssl (installed by default on most PHP builds)
- Composer for library installation

## Installation

```bash
composer require kemboielvis/mpesa-sdk-php
```

## Quick start

```php
<?php
require 'vendor/autoload.php';

use Kemboielvis\MpesaSdkPhp\Mpesa;

// Option A: via constructor
$mpesa = new Mpesa('YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET', 'sandbox'); // or 'live'

// Option B: via setCredentials (also allows specifying a custom token store file)
$mpesa = (new Mpesa())
    ->setCredentials('YOUR_CONSUMER_KEY', 'YOUR_CONSUMER_SECRET', 'sandbox', /* optional */ 'mpesa_api_cache.json');

// Optional: choose where to store the token cache file
// If only a filename is provided, it's stored under the system temp directory.
$mpesa->setStoreFile('mpesa_api_cache.json');

// Optional: enable debug logging (prints to PHP error_log)
$mpesa->setDebug(true);

// Example: STK Push
$response = $mpesa->setBusinessCode('YOUR_TILL_OR_SHORTCODE')
    ->setPassKey('YOUR_LNM_PASSKEY')
    ->stk()
    ->setTransactionType('CustomerPayBillOnline') // or 'CustomerBuyGoodsOnline'
    ->setAmount(100)
    ->setPhoneNumber('254712345678')
    ->setCallbackUrl('https://yourdomain.com/callback')
    ->setAccountReference('INV-12345')
    ->setTransactionDesc('Payment for invoice INV-12345')
    ->push()
    ->getResponse();

print_r($response);
```

## Multi-process safe token cache
The SDK caches the OAuth access token on disk to minimize network calls. The cache is safe for concurrent use by multiple PHP processes:
- A lock file prevents the "thundering herd" when the token needs refreshing.
- Cache writes are atomic (temp file + rename) to avoid partial or corrupt files.
- Malformed/expired cache is ignored and re-fetched safely by a single lock holder.

Details:
- Default cache name: `mpesa_api_cache.json`. If you pass only a filename, it is stored under the system temp directory. You can provide an absolute or relative path.
- Lock file location: same directory as the cache file with `.lock` suffix. For stream paths (e.g., `php://memory`), the lock is stored in the system temp directory.
- Methods:
  - `Mpesa::setStoreFile(string $path)` — sets the token cache file and refreshes the internal client.
  - `Mpesa::clearTokenCache()` — clears the current token cache.
  - `Mpesa::getResolvedStoreFilePath()` — returns the resolved absolute path the SDK uses for the cache.
  - `Mpesa::setDebug(bool $on)` — enable debug logging to troubleshoot token flow.

## Services and examples

- STK Push (Lipa Na M-Pesa)
```php
$resp = $mpesa->stk()
    ->setTransactionType('CustomerPayBillOnline')
    ->setAmount(100)
    ->setPhoneNumber('254712345678')
    ->setCallbackUrl('https://yourdomain.com/callback')
    ->setAccountReference('INV-12345')
    ->setTransactionDesc('Payment for invoice')
    ->push()
    ->getResponse();
```

- Query STK Push status
```php
$status = $mpesa->stk()
    ->query('CHECKOUT_REQUEST_ID')
    ->getResponse();
```

- Customer to Business (C2B) — Register URLs
```php
$resp = $mpesa->customerToBusiness()
    ->setResponseType('Completed')
    ->setConfirmationUrl('https://yourdomain.com/confirmation')
    ->setValidationUrl('https://yourdomain.com/validation')
    ->registerUrl()
    ->getResponse();
```

- C2B — Simulate payment
```php
$resp = $mpesa->customerToBusiness()
    ->setCommandId('CustomerPayBillOnline')
    ->setAmount(100)
    ->setPhoneNumber('254712345678')
    ->setBillRefNumber('INV-123') // for PayBill only
    ->simulate()
    ->getResponse();
```

- Business to Customer (B2C)
```php
$resp = $mpesa->businessToCustomer()
    ->setInitiatorName('YOUR_INITIATOR_NAME')
    ->setCommandId('SalaryPayment') // or BusinessPayment, PromotionPayment
    ->setAmount(1000)
    ->setPhoneNumber('254712345678')
    ->setRemarks('Salary payment')
    ->setOccasion('May 2023 salary')
    ->paymentRequest(
        'YOUR_INITIATOR_NAME',
        'YOUR_INITIATOR_PASSWORD',
        'SalaryPayment',
        1000,
        'YOUR_SHORTCODE',
        '254712345678',
        'Salary payment',
        'https://yourdomain.com/timeout',
        'https://yourdomain.com/result',
        'May 2023 salary'
    );
```

- B2C Hakikisha (check who owns a number before paying; requires Safaricom approval)
```php
try {
    $check = $mpesa->b2cHakikisha()
        ->setPhoneNumber('0722000000')
        ->setShortCode('123456') // defaults to setBusinessCode()
        ->lookup();

    if ($check->isFound()) {
        echo $check->getCustomerName(); // "john M****** M******"
        $check->getCustomer();          // ['firstName' => 'john', 'middleName' => 'M******', 'lastName' => 'M******']
    } else {
        echo $check->getErrorMessage();
    }
} catch (RuntimeException $e) {
    // HTTP errors, e.g. "API error (400): The customer does not exist."
}
```

- Mobile Number Validation (check a number is registered under an ID; commercial, billed per call)
```php
use Kemboielvis\MpesaSdkPhp\Services\MobileNumberValidationService as Kyc;

$kyc = $mpesa->mobileNumberValidation()
    ->setShortCode('776700')        // defaults to setBusinessCode()
    ->setPhoneNumber('0710860780')
    ->setIdType(Kyc::ID_NATIONAL)   // ID_NATIONAL (01), ID_MILITARY (02), ID_PASSPORT (05)
    ->setIdNumber('45435345')
    ->validate();

$kyc->isMatch();     // true when responseCode is 4000 ("Details match successfully")
$kyc->getResponse(); // responseRefID, responseCode, responseMessage, status
```

- Mobile Data Bundles (sell Safaricom data bundles in your app)
```php
use Kemboielvis\MpesaSdkPhp\Services\MobileDataBundlesService as Bundles;

// 1. Fetch the offers for a customer
$bundles = $mpesa->mobileDataBundles()->fetchOffers('0708374149');
foreach ($bundles->getOffers() as $offer) {
    echo $offer->offerName, ' - Ksh ', $offer->offerPrice, PHP_EOL; // "Weekly 2GB - Ksh 99"
}

// 2. Buy one (setOffer() copies offeringId, account, price, data amount and validity)
$purchase = $mpesa->mobileDataBundles()
    ->setPhoneNumber('0708374149')
    ->setOffer($bundles->getOffers()[0])
    ->setPaymentMode(Bundles::PAYMENT_MODE_AIRTIME) // or PAYMENT_MODE_MPESA
    // ->setTransactionId('...')                    // optional; generated if omitted
    ->purchase();

$purchase->isPurchaseSuccessful();
$transactionId = $purchase->getTransactionId();

// 3. Check the status later (M-Pesa purchases complete asynchronously)
$status = $mpesa->mobileDataBundles()->checkStatus($transactionId)->getResponse();
// responseId, responseDesc, responseStatus ("1000" = success), responseCreated
```

- Business to Pochi (pay a customer's Pochi la Biashara wallet)
```php
$pochi = $mpesa->businessToPochi()
    ->setInitiatorName('testapi')                   // needs "ORG B2C API initiator" role
    ->setSecurityCredential('ENCRYPTED_CREDENTIAL') // from the Daraja portal
    ->setPartyA('600992')                           // B2C shortcode; defaults to setBusinessCode()
    ->setPhoneNumber('0705912645')                  // Pochi wallet number
    ->setAmount(10)                                 // Ksh 10 - 250,000
    ->setRemarks('Supplier payment')                // 2 - 100 characters
    ->setOccasion('ChristmasPay')                   // optional
    ->setQueueTimeoutUrl('https://yourdomain.com/pochi/timeout')
    ->setResultUrl('https://yourdomain.com/pochi/result')
    // ->setOriginatorConversationId('...')         // optional; a UUID is generated if omitted
    ->pay();

$pochi->getResponse();
$id = $pochi->getOriginatorConversationId(); // store it to match the callback and avoid double payment
```

- Reversal
```php
$resp = $mpesa->reversal()
    ->setInitiator('YOUR_INITIATOR_NAME')
    ->setTransactionId('YOUR_TRANSACTION_ID')
    ->setReceiverIdentifierType('11') // 1=MSISDN, 2=Till, 4=Shortcode
    ->setRemarks('Refund')
    ->setOccasion('Customer refund')
    ->reverse(
        'YOUR_INITIATOR_NAME',
        'YOUR_INITIATOR_PASSWORD',
        'Refund',
        'YOUR_SHORTCODE',
        'YOUR_TRANSACTION_ID',
        '11',
        'https://yourdomain.com/timeout',
        'https://yourdomain.com/result',
        'Customer refund'
    );
```

- Business Pay Bill (pay a paybill from your business account)
```php
$bill = $mpesa->businessPayBill()
    ->setInitiator('API_Username')                  // needs "Org Business Pay Bill API initiator" role
    ->setSecurityCredential('ENCRYPTED_CREDENTIAL') // from the Daraja portal
    ->setPartyA('123456')                           // your shortcode; defaults to setBusinessCode()
    ->setPartyB('000000')                           // paybill to pay
    ->setAmount(239)
    ->setAccountReference('353353')                 // account number at the paybill, max 13 chars
    ->setRequester('254700000000')                  // optional: customer you are paying for
    ->setRemarks('OK')
    ->setOccasion('Rent')                           // optional
    ->setQueueTimeoutUrl('https://yourdomain.com/b2b/timeout')
    ->setResultUrl('https://yourdomain.com/b2b/result')
    ->pay();

$bill->getResponse(); // OriginatorConversationID, ConversationID, ResponseCode, ResponseDescription
```

- Business Buy Goods (pay a till / merchant store from your business account)
```php
$goods = $mpesa->businessBuyGoods()
    ->setInitiator('API_Username')
    ->setSecurityCredential('ENCRYPTED_CREDENTIAL')
    ->setPartyB('000000')           // till, store number or merchant HO
    ->setAmount(239)
    ->setAccountReference('353353') // max 13 chars
    ->setRequester('254700000000')  // optional
    ->setQueueTimeoutUrl('https://yourdomain.com/b2b/businessbuygoods/queue')
    ->setResultUrl('https://yourdomain.com/b2b/businessbuygoods/result')
    ->pay();
```
It takes the same setters as Business Pay Bill.

- B2C Account Top Up (load funds into a B2C shortcode)
```php
$topUp = $mpesa->b2cAccountTopUp()
    ->setInitiator('testapi')                       // needs "Org Business Pay to Bulk API initiator" role
    ->setSecurityCredential('ENCRYPTED_CREDENTIAL') // from the Daraja portal
    ->setPartyA('600979')                           // your shortcode; defaults to setBusinessCode()
    ->setPartyB('600000')                           // B2C shortcode to load
    ->setAmount(239)
    ->setAccountReference('353353')
    ->setRequester('254708374149')                  // optional
    ->setRemarks('Top up')
    ->setQueueTimeoutUrl('https://yourdomain.com/topup/timeout')
    ->setResultUrl('https://yourdomain.com/topup/result')
    ->topUp();

$topUp->getResponse(); // OriginatorConversationID, ConversationID, ResponseCode, ResponseDescription
```

- Account Balance
```php
$balance = $mpesa->setBusinessCode('600000')   // PartyA: your shortcode
    ->accountBalance()
    ->setInitiator('testapiuser')
    ->setSecurityCredential('ENCRYPTED_CREDENTIAL') // from the Daraja portal
    ->setIdentifierType('4')                        // optional, 4 = shortcode (default)
    ->setRemarks('Balance check')
    ->setQueueTimeoutUrl('https://yourdomain.com/timeout')
    ->setResultUrl('https://yourdomain.com/balance/result')
    ->accountBalance();

$balance->getResponse(); // OriginatorConversationID, ConversationID, ResponseCode, ResponseDescription
```

The actual balance arrives later on your `ResultURL`. Parse it with:
```php
use Kemboielvis\MpesaSdkPhp\Services\AccountBalanceService;

$balances = AccountBalanceService::parseBalances(file_get_contents('php://input'));
// ['Working Account' => ['currency' => 'KES', 'current' => 700000.0, 'available' => 700000.0,
//                        'reserved' => 0.0, 'uncleared' => 0.0], 'Utility Account' => [...], ...]
```

- Pull Transactions (recover C2B transactions from the last 48 hours)
```php
// One-time registration (1000 = registered, 1001 = already registered)
$mpesa->pullTransactions()
    ->setShortCode('600000')          // defaults to setBusinessCode()
    ->setNominatedNumber('0722000000') // number in the shortcode KYC details
    ->setCallbackUrl('https://yourdomain.com/pull/callback')
    ->register()
    ->getResponse();

// Query (1000 = transactions found, 1001 = none in the period)
$pull = $mpesa->pullTransactions()
    ->setShortCode('600000')
    ->setStartDate(new DateTime('-2 hours'))  // or '2020-08-04 08:36:00'
    ->setEndDate(new DateTime())
    ->setOffset(0)                             // row to start from, for paging
    ->query();

foreach ($pull->getTransactions() as $trx) {
    echo $trx->transactionId, ' ', $trx->amount, ' ', $trx->billreference, PHP_EOL;
}
```

- Tax Remittance (pay KRA)
```php
$tax = $mpesa->taxRemittance()
    ->setInitiator('TaxPayer')
    ->setSecurityCredential('ENCRYPTED_CREDENTIAL') // from the Daraja portal
    ->setPartyA('888880')                           // your shortcode; defaults to setBusinessCode()
    ->setAmount(239)
    ->setAccountReference('PRN1234XN')              // payment registration number from KRA
    ->setRemarks('VAT for March')
    ->setQueueTimeoutUrl('https://yourdomain.com/b2b/remittax/queue')
    ->setResultUrl('https://yourdomain.com/b2b/remittax/result')
    ->remit();

$tax->getResponse(); // OriginatorConversationID, ConversationID, ResponseCode, ResponseDescription
```

PartyB is fixed to KRA's shortcode `572572`. The final result (`Result.ResultCode`, `TransactionID`, ...) is posted to your `ResultURL`.

- B2B Express Checkout (USSD Push to Till)
```php
$b2b = $mpesa->b2bExpressCheckout()
    ->setPrimaryShortCode('000001')   // merchant till paying (debit party)
    ->setReceiverShortCode('000002')  // your paybill (defaults to setBusinessCode())
    ->setAmount(100)
    ->setPaymentRef('INV-123')        // shown to the merchant in the prompt
    ->setCallbackUrl('https://yourdomain.com/b2b/result')
    ->setPartnerName('Your Business') // your name as the merchant knows it
    // ->setRequestRefId('...')       // optional; a UUID is generated if omitted
    ->push();

$ack = $b2b->getResponse();          // e.g. { "code": "0", "status": "USSD Initiated Successfully" }
$requestId = $b2b->getRequestRefId(); // matches `requestId` in the callback
```

The callback posted to your `callbackUrl` has `resultCode` (`0` = success, `4001` = user cancelled), `resultDesc`, `requestId`, `amount`, and on success `transactionId` and `status`.

- Dynamic QR code
```php
$qr = $mpesa->dynamicQr()
    ->setMerchantName('TEST SUPERMARKET')
    ->setRefNo('INV-123')
    ->setAmount(100)
    ->setTrxCode('BG')   // BG=Buy Goods, WA=Agent withdraw, PB=Paybill, SM=Send Money, SB=Send to Business
    ->setCpi('373132')   // till/paybill/phone; defaults to setBusinessCode()
    ->setSize(300)       // optional, pixels (default 300)
    ->generate();

$qr->getResponse();           // ResponseCode, RequestID, ResponseDescription, QRCode
$qr->getQrCode();             // base64 PNG
echo '<img src="' . $qr->getQrCodeDataUri() . '">';
$qr->saveQrCode('/path/to/qr.png');
```

- Age on Network (when was a number registered? commercial, billed per call)
```php
$age = $mpesa->ageOnNetwork()->check('0722000000');

if ($age->isSuccessful()) {
    $age->getRegistrationDate();      // raw value, e.g. "2019-01-12" or a message
    $age->getRegistrationDateTime();  // DateTimeImmutable, or null if it is not a date
}
```

- IoT SIM Management (manage Safaricom IoT SIMs and their messages)
```php
$iot = $mpesa->iotSim()
    ->setVpnGroup('1-555162310488_VPN')           // your IoT account number
    ->setUsername('darajasandbox@safaricom.co.ke'); // user registered on the account

// SIM operations
$sims = $iot->getAllSims(0, 20)->getSims();        // start index, page size
$iot->queryLifeCycleStatus('0110100606')->getBody(); // desc, status, statusCode
$info = $iot->queryCustomerInfo('0110100606')->getBody(); // offeringName, offeringId, ...
$iot->activateSim('0110100606');
$iot->renameAsset('0110100606', 'Tracker001');
$iot->suspendSim('0110100606', $info->offeringId);
$iot->resumeSim('0110100606', $info->offeringId);
$iot->getActivationTrends(new DateTime('-30 days'), new DateTime()); // or '20240221', '20240421'

// Messaging
$iot->sendMessage('0110100606', 'Test');
$messages = $iot->searchMessages('0110100606')->getMessages(); // "254" is added for you
$iot->filterMessages('02-05-2024 08:39:11', new DateTime(), '1', 1, 10)->getMessages();
$iot->getAllMessages(1, 10)->getMessages();
$iot->deleteMessage($messages[0]->id);
$iot->deleteMessageThread('0110100606');

$iot->isSuccessful(); // header.responseCode === 200 for the last call
```

Some failures (e.g. a SIM that is not in your account) still return `responseCode` 200, so check `getBody()` as well.

## Error handling
Wrap service calls in try/catch:

```php
try {
    $resp = $mpesa->stk()->push()->getResponse();
} catch (\Throwable $e) {
    error_log('M-Pesa error: ' . $e->getMessage());
}
```

## Advanced configuration

- Token cache file
```php
$mpesa->setStoreFile('/var/run/mpesa/token.json');
$path = $mpesa->getResolvedStoreFilePath(); // inspect where it ends up
```

- Debug logs
```php
$mpesa->setDebug(true); // lock events, cache hits/misses, and token response metadata go to error_log
```

- Test-only: override base URL
For automated tests or proxies, you can override via the underlying config (not usually needed in apps):

```php
// $config is internal; shown for completeness in test setups only
// $config->setBaseUrl('http://127.0.0.1:8091');
```

## Testing
The repository ships with a few simple tests, including concurrency/tamper checks for the token cache.

- Smoke test: cache read path
```bash
php src/Tests/token_cache_smoke.php
```

- Concurrency test: verifies single network fetch with many parallel processes
```bash
# Start fake token server in a background shell
php -S 127.0.0.1:8091 src/Tests/fake_mpesa_server.php

# In another shell
php src/Tests/concurrency_test.php
```

- Tamper concurrency test: corrupts the cache mid-flight; ensures consistency and minimal re-fetch
```bash
# Start fake token server on a different port
php -S 127.0.0.1:8092 src/Tests/fake_mpesa_server.php

# In another shell
php src/Tests/tamper_concurrency_test.php
```

Notes:
- These tests use a local fake server and do not hit Safaricom endpoints.
- If you see permission issues for the cache path, choose a directory writable by your PHP processes (e.g., `/tmp` or a shared run directory) and use `Mpesa::setStoreFile()`.

## Troubleshooting
- Token cache not updating:
  - Ensure the process has write permission to the cache directory.
  - Check for SELinux/AppArmor restrictions if applicable.
  - Enable debug with `$mpesa->setDebug(true)` to see lock/cache logs in error_log.
- SSL errors on sandbox: ensure your environment has recent CA certificates; avoid disabling verification in production.

## License
MIT License. See `LICENSE` in this repository.

## Support
Open an issue with details (PHP version, OS, logs, and a minimal repro). Pull requests welcome.
