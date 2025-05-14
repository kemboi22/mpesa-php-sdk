<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

use Kemboielvis\MpesaSdkPhp\Mpesa;

class Stk extends Mpesa {
    public function __construct($data) {
        parent::__construct();
        $this->configure($data);
    }

    private string $account_reference = '';

    private string $transaction_desc = '';

    private $baseUrl;

    /**
     * Configure the STK object with the required parameters.
     *
     * @param array $data The array should contain the following keys:
     *                    - business_code: The business code of the mpesa account
     *                    - transaction_type: The transaction type of the mpesa account
     *                    - amount: The amount to be transacted
     *                    - phone_number: The phone number to transact with
     *                    - call_back_url: The url to be used for the callback
     *                    - consumer_key: The consumer key for the mpesa account
     *                    - consumer_secret: The consumer secret for the mpesa account
     *                    - base_url: The base url for the mpesa api
     *
     * @return Stk
     *
     * @throws \Exception
     */
    public function configure(array $data): Stk {
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

    /**
     * Initiates an STK push request to the M-Pesa API.
     *
     * This function constructs the necessary data for the STK push request,
     * including business shortcode, password, timestamp, transaction type,
     * amount, party information, phone number, callback URL, account reference,
     * and transaction description. It then sends the request using the `curls`
     * method and stores the response.
     *
     * @return Stk Returns the current instance of the Stk class.
     */
    public function push(): Stk {
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

    /**
     * Sets the transaction type of the STK push request.
     *
     * Valid transaction types are:
     * - CustomerPayBillOnline
     * - CustomerBuyGoodsOnline
     *
     * @param string $transaction_type The transaction type.
     *
     * @return Stk Returns the current instance of the Stk class.
     */
    public function transactionType(string $transaction_type): Stk {
        $this->transaction_type = $transaction_type;

        return $this;
    }

    /**
     * Sets the account reference for the STK push request.
     *
     * This parameter is mandatory and should be a unique value for each
     * transaction. It is used by M-Pesa to identify the transaction and
     * provide a more detailed description of the transaction.
     *
     * @param string $account_reference The account reference.
     *
     * @return Stk Returns the current instance of the Stk class.
     */
    public function accountReference(string $account_reference): Stk {
        $this->account_reference = $account_reference;

        return $this;
    }

    /**
     * Sets the transaction description for the STK push request.
     *
     * This is a short description of the transaction.
     *
     * @param string $transaction_desc The transaction description.
     *
     * @return Stk Returns the current instance of the Stk class.
     */
    public function transactionDesc(string $transaction_desc): Stk {
        $this->transaction_desc = $transaction_desc;

        return $this;
    }

    /**
     * Sets the callback URL for the STK push request.
     *
     * This URL is called by M-Pesa after the transaction has been completed.
     *
     * @param string $url The URL to be called.
     *
     * @return Stk Returns the current instance of the Stk class.
     */
    public function callBackUrl(string $url): Stk {
        $this->call_back_url = $url;

        return $this;
    }

    /**
     * Get the Checkout Request ID from the STK push response.
     *
     * The Checkout Request ID is a unique identifier assigned by M-Pesa to the
     * transaction. It can be used to query the status of the transaction.
     *
     * @return string The Checkout Request ID.
     */
    public function checkoutId() {
        return $this->response->CheckoutRequestID;
    }

    /**
     * Queries the status of an STK push transaction from the M-Pesa API.
     *
     * This function constructs the necessary data for the STK push query request,
     * including business shortcode, password, timestamp, and the checkout request ID.
     * It then sends the request using the `curls` method and returns the response.
     *
     * @return mixed The response from the M-Pesa API containing the transaction status.
     */
    public function query() {
        $array_data = [
            'BusinessShortCode' => $this->business_code,
            'Password' => $this->password(),
            'Timestamp' => $this->timestamp(),
            'CheckoutRequestID' => $this->checkoutId(),
        ];

        return $this->curls($array_data, '/mpesa/stkpushquery/v1/query');
    }
}
