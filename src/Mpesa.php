<?php

namespace Kemboielvis\MpesaSdkPhp;

use Kemboielvis\MpesaSdkPhp\Helpers\AccountBalance;
use Kemboielvis\MpesaSdkPhp\Helpers\BusinessToCustomer;
use Kemboielvis\MpesaSdkPhp\Helpers\CustomerToBusiness;
use Kemboielvis\MpesaSdkPhp\Helpers\Reversal;
use Kemboielvis\MpesaSdkPhp\Helpers\Stk;
use Kemboielvis\MpesaSdkPhp\Helpers\TransactionStatus;

class Mpesa
{

    public  string $consumer_key = "";
    public string $consumer_secret = "";
    public  string $business_code = "";
    public string $pass_key = "";
    public string $transaction_type = "";
    public string $token_url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    public string $phone_number = "";

    public string $amount = "";
    public string $call_back_url = "https://mydomain.com/path";
    public string $stk_push_url = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";
    public string $security_credential = "";

    public object $response;
    public string $queue_timeout_url = "";
    public string $result_url = "";

//    public function __construct($key = null, $secret = null)
//    {
//        $this->set_credentials();
//        return $this;
//
//    }

    public function setCredentials($consumer_key = null, $consumer_secret = null): static
    {
        if ($consumer_key != null) $this->consumerKey($consumer_key);
        if ($consumer_secret != null) $this->consumerSecret($consumer_secret);
        return $this;
    }


    public function timestamp(): string
    {
        return date("YmdHis");
    }
    public function password(): string
    {
        return base64_encode($this->business_code.$this->pass_key.$this->timestamp());
    }
    public function businessCode(string $business_code): static
    {
        $this->business_code = $business_code;
        return $this;
    }

    public  function amount(int $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function phoneNumber(string $phone): static
    {
//        if($phone[0] == "+") $phone = substr($phone, 1);
//        if($phone[0] == "0") $phone = substr($phone, 1);
//        if($phone[0] == "7") $phone = "254" . $phone;

        $this->phone_number = $phone;
        return $this;
    }
    public function consumerKey(string $consumer_key): static
    {
        $this->consumer_key = $consumer_key;
        return $this;
    }

    public function consumerSecret(string $consumer_secret): static
    {
        $this->consumer_secret = $consumer_secret;
        return $this;
    }

    public function passKey(string $pass_key): static
    {
        $this->pass_key = $pass_key;
        return $this;
    }

    public function response(){
        return $this->response;
    }
    public function securityCredential($initiator_password): static
    {
        $method = "aes-256-cbc";
        $password = "mypassword";
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $this->security_credential = base64_encode($iv.openssl_encrypt("$initiator_password + Certificate", $method, $password, 0, $iv));
        return $this;
    }

    public function resultUrl($result_url): static
    {
        $this->result_url = $result_url;
        return $this;
    }
    public function queueTimeoutUrl($timeout_url): static
    {
        $this->queue_timeout_url = $timeout_url;
        return $this;
    }

    public function authenticationToken(){
        $curl_transfer = curl_init();
        curl_setopt($curl_transfer, CURLOPT_URL, $this->token_url);
        $credentials = base64_encode($this->consumer_key.":".$this->consumer_secret);
        curl_setopt($curl_transfer, CURLOPT_HTTPHEADER, array('Authorization: Basic '.$credentials));
        curl_setopt($curl_transfer, CURLOPT_HEADER, false);
        curl_setopt($curl_transfer, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl_transfer);
        return json_decode($response)->access_token;
    }

    public function curls(array $data, $url){
        $curl_transfer = curl_init($url);
        curl_setopt($curl_transfer, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Authorization: Bearer ".$this->authenticationToken()]);

        curl_setopt($curl_transfer, CURLOPT_POST, true);
        curl_setopt($curl_transfer, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl_transfer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_transfer, CURLOPT_HEADER, false);
        $response = curl_exec($curl_transfer);
        curl_close($curl_transfer);
        return json_decode($response);
    }

    public function stk(): Stk
    {
        return new Stk([
            "consumer_key" => $this->consumer_key,
            "consumer_secret" => $this->consumer_secret,
            "business_code" => $this->business_code,
            "transaction_type" => $this->transaction_type,
            "amount" => $this->amount,
            "phone_number" => $this->phone_number,
            "call_back_url" => $this->call_back_url
        ]);
    }

    public function customerToBusiness(): CustomerToBusiness
    {
        return new CustomerToBusiness($this->consumer_key, $this->consumer_secret);
    }

    public function businessToCustomer(): BusinessToCustomer
    {
        return new BusinessToCustomer($this->consumer_key, $this->consumer_secret);
    }

    public function checkBalance(): AccountBalance
    {
        return new AccountBalance($this->consumer_key, $this->consumer_secret);
    }

    public function transactionStatus(): TransactionStatus
    {
        return new TransactionStatus($this->consumer_key, $this->consumer_secret);
    }

    public function reversal(): Reversal
    {
        return new Reversal($this->consumer_key, $this->consumer_secret);
    }

}