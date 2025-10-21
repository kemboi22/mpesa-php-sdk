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
