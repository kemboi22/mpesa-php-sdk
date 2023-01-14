<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

use Kemboielvis\MpesaSdkPhp\Mpesa;

class Stk extends Mpesa
{
    public function __construct($data)
    {
        $this->configure($data);
    }

    public function configure(array $data)
    {

        if (array_key_exists("business_code", $data)){
            if (array_key_exists("transaction_type", $data)){
                if (array_key_exists("amount", $data)){
                    if (array_key_exists("phone_number", $data)){
                        if (array_key_exists("call_back_url", $data)){
                            $this->consumer_key = $data["consumer_key"];
                            $this->consumer_secret = $data["consumer_secret"];
                            $this->business_code = $data["business_code"];
                            $this->transaction_type = $data["transaction_type"];
                            $this->amount = $data["amount"];
                            $this->phone_number = (int)$data["phone_number"];
                            $this->call_back_url = $data["call_back_url"];
                            return $this;

                        }else{
                            throw new \Exception("Call Back url is required");
                        }
                    }else{
                        throw new \Exception("A phone Number is required");
                    }
                }else{
                    throw new \Exception("An Amount is required");
                }
            }else{
                throw new \Exception("A transaction type is required");
            }
        }else{
            throw new \Exception("A Business Code is required");
        }


    }

    public function push(){

        $array_data = [
            "BusinessShortCode" => $this->business_code,
            "Password" => $this->password(),
            "Timestamp" => $this->timestamp(),
            "TransactionType" => $this->transaction_type,
            "Amount" => $this->amount,
            "PartyA" => $this->phone_number,
            "PartyB" => $this->business_code,
            "PhoneNumber" => $this->phone_number,
            "CallBackURL" => $this->call_back_url,
            "AccountReference" => "CompanyXLTD",
            "TransactionDesc" => "Payment of X"

        ];
        $this->response = $this->curls($array_data, $this->stk_push_url);
        return $this;
    }


    public function pass_key(string $pass_key): static
    {
        $this->pass_key = $pass_key;
        return $this;
    }
    public function transaction_type(string $transaction_type): static
    {
        $this->transaction_type = $transaction_type;
        return $this;
    }


    public function call_back_url($url): static
    {
        $this->call_back_url = $url;
        return $this;
    }
    public function checkout_id(){
        return $this->response->CheckoutRequestID;
    }


    public function Query()
    {
        $array_data = [
            "BusinessShortCode" => $this->business_code,
            "Password" => $this->password(),
            "Timestamp" => $this->timestamp(),
            "CheckoutRequestID" => $this->checkout_id()
        ];
        return $this->curls($array_data, "https://sandbox.safaricom.co.ke/mpesa/stkpushquery/v1/query");
    }

}