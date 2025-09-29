<?php

require "../../vendor/autoload.php";
use Kemboielvis\MpesaSdkPhp\Mpesa;

$mpesa = new Mpesa(
    'rHZXmBkGz6Ne30cA923bp9G0rSAK41hsDVCq65x522WkVqCF',
    'QC7BEvNXH9FfMATpduK1fTh1836XisZ9qG7cIZ15S9cDGzIsBMc2YAkAKsEr7wjo',
    'sandbox'
);
$mpesa->setDebug(true);
$mpesa = $mpesa->setCredentials(
    'rHZXmBkGz6Ne30cA923bp9G0rSAK41hsDVCq65x522WkVqCF',
    'QC7BEvNXH9FfMATpduK1fTh1836XisZ9qG7cIZ15S9cDGzIsBMc2YAkAKsEr7wjo',
    'sandbox',
    'mpesa_api_cache_token.json'
);
//$mpesa->passKey('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
//$mpesa->phoneNumber('254111844429');

$stk = $mpesa->setBusinessCode('174379')
    ->setPassKey('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919')
    ->stk()->setAmount('1')
    ->setPhoneNumber("254111844429")
    ->setCallBackUrl("https://f8e3-197-248-144-75.ngrok-free.app/callback")
    ->setTransactionType("CustomerPayBillOnline")
    ->setAccountReference("This is a test account reference")
    ->setTransactionDesc("Test Push Mpesa");

print_r($stk->push()->getResponse());
