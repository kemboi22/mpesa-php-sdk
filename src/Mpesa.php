<?php

namespace Kemboielvis\MpesaSdkPhp;

use Kemboielvis\MpesaSdkPhp\Helpers\Stk;

class Mpesa
{
    public string $consumer_key = "Kuu9J6dcwaSnZjr3iyHKIwXvFCXkkt8y";
    public string $consumer_secret = "ljjSvwG5uvvTccdG";
    public  string $business_code = "";
    public string $pass_key = "";
    public string $type_of_transaction = "";
    public string $token_url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
    public string $phone_number = "";
    public string $onine_payment = "";
    public string $amount = "";
    public string $call_back_url = "https://mydomain.com/path";
    public string $stk_push_url = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";

    public string $partyB = "";
    public function timestamp(): string
    {
        return date("Ymdhis");
    }
    public function password(){
        return base64_encode($this->business_code.$this->pass_key.$this->timestamp());
    }

    public function authentication_token(){
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
        $curl_transfer = curl_init();
        curl_setopt($curl_transfer, CURLOPT_URL, $url);
        curl_setopt($curl_transfer, CURLOPT_HTTPHEADER, array('Content-Type:application/json', 'Authorization:Bearer '.$this->authentication_token()));
        $json_string = json_encode($data);
        curl_setopt($curl_transfer, CURLOPT_POST, 1);
        curl_setopt($curl_transfer, CURLOPT_POSTFIELDS, $json_string);
        curl_setopt($curl_transfer, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_transfer, CURLOPT_HEADER, false);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($curl_transfer);
        curl_close($curl_transfer);
        return $response;
    }

    public function stk($data){
        return new Stk($data);
    }

}