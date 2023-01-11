<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

use Kemboielvis\MpesaSdkPhp\Mpesa;

class Stk extends Mpesa
{
    public function __construct(array $data)
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
                            $this->business_code = $data["business_code"];
                            $this->type_of_transaction = $data["transaction_type"];
                            $this->amount = $data["amount"];
                            $this->phone_number = (int)$data["phone_number"];
                            $this->call_back_url = $data["call_back_url"];

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
            "TransactionType" => $this->type_of_transaction,
            "Amount" => $this->amount,
            "PartyA" => $this->phone_number,
            "PartyB" => $this->business_code,
            "PhoneNumber" => $this->phone_number,
            "CallBackURL" => $this->call_back_url,
            "AccountReference" => "CompanyXLTD",
            "TransactionDesc" => "Payment of X"

        ];
        return $this->curls($array_data, $this->stk_push_url);
    }

}