<?php

// Manual STK push test against the sandbox.
// Usage:
//   MPESA_CONSUMER_KEY=... MPESA_CONSUMER_SECRET=... MPESA_PHONE=2547XXXXXXXX \
//   MPESA_CALLBACK_URL=https://your-domain/callback php tests.php

require "../../vendor/autoload.php";
use Kemboielvis\MpesaSdkPhp\Mpesa;

$env = function (string $name, ?string $default = null): string {
    $value = getenv($name);
    if (false === $value || '' === $value) {
        if (null === $default) {
            fwrite(STDERR, "Missing environment variable: $name\n");
            exit(1);
        }
        return $default;
    }
    return $value;
};

$mpesa = new Mpesa($env('MPESA_CONSUMER_KEY'), $env('MPESA_CONSUMER_SECRET'), 'sandbox');
$mpesa->setDebug(true);

// Sandbox test shortcode and pass key published on the Daraja portal
$stk = $mpesa->setBusinessCode($env('MPESA_SHORTCODE', '174379'))
    ->setPassKey($env('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'))
    ->stk()->setAmount('1')
    ->setPhoneNumber($env('MPESA_PHONE'))
    ->setCallBackUrl($env('MPESA_CALLBACK_URL'))
    ->setTransactionType("CustomerPayBillOnline")
    ->setAccountReference("Test")
    ->setTransactionDesc("Test Push Mpesa");

print_r($stk->push()->getResponse());
