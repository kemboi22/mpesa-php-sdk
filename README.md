# mpesa-php-sdk

This is an SDK for Safaricom Mpesa API

Basic Usage:

use composer to install the sdk and import autoload file in vendor 

```
composer require kemboielvis/mpesa-sdk-php
```

1. Initialize mpesa sdk with consumer key and consumer secret from https://developer.safaricom.co.ke/
  

```php
    //USING ARROW FUNCTION
    <?php
    require ('vendor/autoload.php');
    use Kemboielvis\MpesaSdkPhp\Mpesa;
    $credentials = new Mpesa();
    // replace consumerkey with consumer key from daraja portal
    // Replace consumer secret with consumer secret from daraja portal
    // in the env it either "live" or "develoment
    $mpesa = $credentials->consumerKey("CONSUMER_KEY")->consumerSecret("CONSUMER_SECRET")->env("live")->setCredentials();
 ```
 ```php
    // PASSING KEY AS PARAMETERS IN SET CREDENTIALS
    $credentials = new Mpesa();
    $mpesa = $credentials->setCredentials("CONSUMER_KEY", "CONSUMER_SECRET", "live");
 ```
 
 2. Sending an STK push
 ```
 BusinessShortCode => This is organizations shortcode (Paybill or Buygoods - A 5 to 7 digit account number)
 used to identify an organization and receive the transaction.
 
 TransactionType => This is the transaction type that is used to identify the transaction when sending the request to M-Pesa. 
 The transaction type for M-Pesa Express is "CustomerPayBillOnline" OR "CustomerBuyGoodsOnline"
 
 Amount => Money that customer pays
 
 Phone Number => The phone number sending money.
 
 CallBackURL => A CallBack URL is a valid secure URL that is used to receive notifications from M-Pesa API.
 It is the endpoint to which the results will be sent by M-Pesa API.
 
 AccountReference => This is a parameter that is defined by your system as an identifier of the transaction
 
 Transaction Desc => This is any additional information/comment that can be sent along with the request from your system. 
 Maximum of 13 Characters.
 ```
 
 ```php
     $stk = $mpesa->stk()
     ->businessCode("BUSINESS_CODE")
     ->amount(AMOUNT)
     ->phoneNumber("PHONE_NUMBER")
     ->callBackUrl("CALL_BACK_URL")
     ->transactionType("CustomerPayBillOnline")
     ->accountReference("ACCOUNT-REFERENCE")
     ->transactionDesc("TRANSACTION_DESC")
     ->passKey("PASS_KEY");
    // Get response in and store it after sending a push
    $response = $stk->push()->response();
    // Query STK Push and check its status
    $transactionQuery = $push->query();
 ```
 
  3.Register confirmation and Validation url
  
  ```
  Validation Url => This is the URL that receives the validation request from API upon payment submission.
  
  Confirmation URL => This is the URL that receives the confirmation request from API upon payment completion.
  
  Response Type => Completed OR Canceled
  ```
  ```php
    $registerUrl = $mpesa->customerToBusiness()
      ->responseType("Completed")
      ->validationUrl("https://mydomain.com/confirmation")
      ->confirmationUrl("https://mydomain.com/confirmation")
      ->businessCode("600984")->registerUrl();
    // Get the response
    $response = $registerUrl->response();
  ```
  
  4. Customer to business 
  
  ```
  CommandID => This is a unique identifier of the transaction type: Either CustomerPayBillOnline or CustomerBuyGoodsOnline
  
  Phone Number => Phone number initiating customer to business transaction
  
  BillRefNumber =>  This is used on CustomerPayBillOnline option only. This is where a customer is expected to enter a unique bill identifier, e.g an Account Number. 
  
  Business code => This is the Short Code receiving the amount being transacted.
  
  ```
  ```php
    $c2b = $mpesa->customerToBusiness();
    $simulate = $c2b->businessCode("600988")->commandId("CustomerBuyGoodsOnline")->amount("10")->phoneNumber("PHONE_NUMBER")->simulate();
    // You can add 
    ->billRefNumber("BILL REF_NUMBER") //  For pay bills only
    
   
    // Get the response
    $response = $simulate->response();   
   
  ```
  
  5. Business to Customer 
  
    Transfer money from business to a customer
    InitiatorName => The username of the M-Pesa B2C account API operator. 
    NOTE: the access channel for this   operator must be API and the account must be in active status.
    
    SecurityCredential => Pass the initiator password
    
    CommandID => SalaryPayment, BusinessPayment, PromotionPayment
    
    Phone Number => This is the customer mobile number  to receive the amount. 
    The number should have the country code (254) without the plus sign.
    
    Remarks => This is the customer mobile number  to receive the amount. 
    The number should have the country code (254) without the plus sign.
    
    QueueTimeOutURL => This is the URL to be specified in your request that will be used by API Proxy to send notification 
    incase the payment request is timed out while awaiting processing in the queue. 
    
    Result URL => This is the URL to be specified in your request that will be used by M-Pesa to send notification
    upon processing of the payment request.
    
    Occasion => Any additional information to be associated with the transaction. (Sentence of upto 100 characters)
    
  ```php
  
      $b2c = $mpesa->businessToCustomer();
      $paymentRequest = $b2c
      ->initiatorName("INITIATOR_NAME")
      ->securityCredential("INTIATOR_PASSWORD")
      ->commandId("SalaryPayment")
      ->amount(AMOUNT) //int
      ->businessCode("600584")
      ->phoneNumber("PHONE_NUMBER")->remarks("Test")
      ->queueTimeoutUrl("https://mydomain.com/b2c/queue")
      ->resultUrl("https://mydomain.com/b2c/queue")
      ->occasion("Test")->paymentRequest();
    $response = $paymentRequest->response();
    print_r($response->response());
  
  ```
