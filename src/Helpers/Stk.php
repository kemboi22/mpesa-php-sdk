<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

use Kemboielvis\MpesaSdkPhp\Mpesa;

class Stk extends Mpesa
{
    public function __construct($data)
    {
        $this->configure($data);
    }
    private string $account_reference = '';

    private string $transaction_desc = '';

    private $baseUrl;

    /**
     * @param array<int,mixed> $data
     */
    public function configure(array $data): Stk
    {
        if (array_key_exists('business_code', $data)) {
            if (array_key_exists('transaction_type', $data)) {
                if (array_key_exists('amount', $data)) {
                    if (array_key_exists('phone_number', $data)) {
                        if (array_key_exists('call_back_url', $data)) {
                            $this->consumer_key = $data['consumer_key'];
                            $this->consumer_secret = $data['consumer_secret'];
                            $this->business_code = $data['business_code'];
                            $this->transaction_type = $data['transaction_type'];
                            $this->amount = $data['amount'];
                            $this->phone_number = (int) $data['phone_number'];
                            $this->call_back_url = $data['call_back_url'];
                            $this->baseUrl = $data['base_url'];

                            return $this;
                        }
                        throw new \Exception('Call Back url is required');
                    } else {
                        throw new \Exception('A phone Number is required');
                    }
                } else {
                    throw new \Exception('An Amount is required');
                }
            } else {
                throw new \Exception('A transaction type is required');
            }
        } else {
            throw new \Exception('A Business Code is required');
        }
    }

    public function push(): Stk
    {
        $array_data = [
            'BusinessShortCode' => $this->business_code,
            'Password' => $this->password(),
            'Timestamp' => $this->timestamp(),
            'TransactionType' => $this->transaction_type,
            'Amount' => $this->amount,
            'PartyA' => $this->phone_number,
            'PartyB' => $this->business_code,
            'PhoneNumber' => $this->phone_number,
            'CallBackURL' => $this->call_back_url,
            'AccountReference' => $this->account_reference,
            'TransactionDesc' => $this->transaction_desc,

        ];
        $this->response = $this->curls($array_data, '/mpesa/stkpush/v1/processrequest');

        return $this;
    }

    public function transactionType(string $transaction_type): Stk
    {
        $this->transaction_type = $transaction_type;

        return $this;
    }

    public function accountReference(string $account_reference): Stk
    {
        $this->account_reference = $account_reference;

        return $this;
    }

    public function transactionDesc(string $transaction_desc): Stk
    {
        $this->transaction_desc = $transaction_desc;

        return $this;
    }

    public function callBackUrl(string $url): Stk
    {
        $this->call_back_url = $url;

        return $this;
    }

    public function checkoutId()
    {
        return $this->response->CheckoutRequestID;
    }

    public function query()
    {
        $array_data = [
            'BusinessShortCode' => $this->business_code,
            'Password' => $this->password(),
            'Timestamp' => $this->timestamp(),
            'CheckoutRequestID' => $this->checkoutId(),
        ];

        return $this->curls($array_data, '/mpesa/stkpushquery/v1/query');
    }
}
