<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

class CustomerToBusiness extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    public string $confirmation_url = "";
    public string $validation_url = "";
    public string $response_type = "";
    private string $command_id = "";
    private string $bill_ref_number = "";

    /**
     * Registers a url for customer to business (C2B) on M-Pesa
     * @param string $confirmation_url The URL that receives the confirmation request from API upon payment completion
     * @param string $validation_url The URL that receives the validation request from API upon payment submission
     * @param string $response_type The type of response to be sent to the confirmation and validation URLs
     * @return static
     */
    public function registerUrl($confirmation_url = null, $validation_url = null, $response_type = null): static
    {
        if($response_type != null){ $this->responseType($response_type); }
        if($confirmation_url != null) { $this->confirmationUrl($confirmation_url) ; }
        if ($response_type != null) { $this->validationUrl($validation_url); }
        $array_data = [
            "ShortCode" => $this->business_code,
            "ResponseType" => $this->response_type ,
            "ConfirmationURL" => $this->confirmation_url,
            "ValidationURL" => $this->validation_url
        ];
        $this->response = $this->curls($array_data, "/mpesa/c2b/v1/registerurl");
        return $this;

    }

    /**
     * Set the confirmation URL to receive the confirmation request from API upon payment completion
     * @param string $confirmation_url The URL to receive the confirmation request from API upon payment completion
     * @return static
     */
    public function confirmationUrl($confirmation_url): static
    {
        $this->confirmation_url = $confirmation_url;
        return $this;
    }
    /**
     * Set the validation URL to receive the validation request from API upon payment submission
     * @param string $validation_url The URL to receive the validation request from API upon payment submission
     * @return static
     */
    public function validationUrl($validation_url): static
    {
        $this->validation_url = $validation_url;
        return $this;
    }

    /**
     * Set the type of response to be sent to the confirmation and validation URLs
     * @param string $response_type The type of response to be sent to the confirmation and validation URLs
     * @return static
     */
    public function responseType($response_type): static
    {
        $this->response_type = $response_type;
        return $this;
    }

    /**
     * CustomerToBusiness constructor.
     *
     * @param string $consumer_key The consumer key for the M-Pesa API.
     * @param string $consumer_secret The consumer secret for the M-Pesa API.
     * @param string $baseUrl The base URL for the M-Pesa API.
     */
    public function __construct($consumer_key, $consumer_secret, $baseUrl)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Simulate a C2B transaction
     * @param string $business_code [optional] The shortcode of the organization receiving the transaction.
     * @param string $command_id [optional] The unique identifier of the transaction type. Either CustomerPayBillOnline or CustomerBuyGoodsOnline
     * @param int $amount [optional] The amount to be transacted.
     * @param string $phone_number [optional] The phone number of the customer initiating the transaction.
     * @param string $bill_ref_number [optional] A unique identifier of the transaction.
     * @return static
     */
    public function simulate($business_code = "", $command_id = "", $amount = "", $phone_number = "", $bill_ref_number = ""): static
    {
        if ($business_code != "") $this->businessCode($business_code);
        if ($command_id != "") $this->commandId($command_id);
        if ($amount != "") $this->amount($amount);
        if ($phone_number != "") $this->phoneNumber($phone_number);
        if ($bill_ref_number != "") $this->billRefNumber($bill_ref_number);
        $array_data = [
            "ShortCode" =>  $this->business_code,
            "CommandID" => $this->command_id,
            "Amount" => $this->amount,
            "Msisdn" => $this->phone_number,
            "BillRefNumber" => $this->bill_ref_number,
        ];
        $this->response = $this->curls($array_data, "/mpesa/c2b/v1/simulate");
        return $this;
    }

    /**
     * Sets the command ID for the transaction.
     *
     * The command ID is a unique identifier of the transaction type. The following are some of the supported command IDs:
     * - CustomerPayBillOnline
     * - CustomerBuyGoodsOnline
     *
     * @param string $command_id The command ID.
     *
     * @return static
     */
    public function commandId($command_id): static
    {
        $this->command_id = $command_id;
        return $this;
    }

    /**
     * Sets the bill reference number for the transaction.
     *
     * The bill reference number is a unique identifier of the transaction.
     *
     * @param string $bill_ref_number The bill reference number.
     *
     * @return static
     */
    public function billRefNumber($bill_ref_number): static
    {
        $this->bill_ref_number = $bill_ref_number;
        return $this;
    }


}