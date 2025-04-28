<?php
require ('../../vendor/autoload.php');
use Kemboielvis\MpesaSdkPhp\Mpesa;

$mpesa = new Mpesa('rHZXmBkGz6Ne30cA923bp9G0rSAK41hsDVCq65x522WkVqCF',
    'QC7BEvNXH9FfMATpduK1fTh1836XisZ9qG7cIZ15S9cDGzIsBMc2YAkAKsEr7wjo',
    'sandbox');
$mpesa = $mpesa->setCredentials('rHZXmBkGz6Ne30cA923bp9G0rSAK41hsDVCq65x522WkVqCF',
    'QC7BEvNXH9FfMATpduK1fTh1836XisZ9qG7cIZ15S9cDGzIsBMc2YAkAKsEr7wjo',
    'sandbox');
//$mpesa->passKey('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919');
//$mpesa->phoneNumber('254111844429');

$stk = $mpesa->stk()
    ->businessCode("174379")
    ->amount('1')
    ->phoneNumber("254111844429")
    ->callBackUrl("CALL_BACK_URL")
    ->transactionType("CustomerPayBillOnline")
    ->accountReference("This is a test account reference")
    ->transactionDesc("Test Push Mpesa")
    ->passKey("bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919");

print_r($stk->push()->response());