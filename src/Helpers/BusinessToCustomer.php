<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

class BusinessToCustomer extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator_name = "";
    private string $command_id = "";
    private string $remarks = "";
    private string $occasion = "";


    /**
     * Sets the username of the M-Pesa API operator.
     *
     * @param string $initiator_name The username of the M-Pesa API operator.
     *
     * @return static
     */
    public function initiatorName($initiator_name): static
    {
        $this->initiator_name = $initiator_name;
        return $this;
    }


    /**
     * Sets the command ID.
     *
     * The command ID is a short name that identifies the type of transaction.
     * The following are some of the supported command IDs:
     * - SalaryPayment
     * - BusinessPayment
     * - PromotionPayment
     * - CheckBalance
     * - BuyGoods
     * - DisburseFunds
     * - PayBill
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
     * Sets the remarks for the transaction.
     *
     * Remarks are additional information or comments associated
     * with the transaction. This information is typically used
     * for record-keeping or auditing purposes.
     *
     * @param string $remarks The remarks for the transaction.
     * @return static
     */
    public function remarks($remarks): static
    {
        $this->remarks = $remarks;
        return $this;
    }

    /**
     * Sets the occasion for the transaction.
     *
     * The occasion is a descriptive name given to the transaction.
     * Examples of occasions include "Salary Payment", "Business Payment",
     * "Promotion Payment", etc.
     *
     * @param string $occasion The occasion for the transaction.
     *
     * @return static
     */
    public function occasion($occasion): static
    {
        $this->occasion = $occasion;
        return $this;
    }

    /**
     * Creates a new instance of the BusinessToCustomer class.
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
     * Sends a business to customer payment request to the M-Pesa API.
     *
     * The following parameters are required: `initiator_name`, `initiator_password`,
     * `command_id`, `amount`, `partyA`, `phone_number`, `remarks`, `queue_timeout_url`,
     * `result_url`, and `occasion`.
     *
     * If any of these parameters are not provided, they will be set to their default
     * values.
     *
     * @param string|null $initiator_name The username of the M-Pesa B2C account API operator.
     * @param string|null $initiator_password The password to authenticate the initiator.
     * @param string|null $command_id The type of transaction: Either "SalaryPayment", "BusinessPayment", or "PromotionPayment".
     * @param int|null $amount The amount of money to be sent.
     * @param string|null $partyA The shortcode to receive the transaction.
     * @param string|null $phone_number The customer mobile number to receive the amount.
     * @param string|null $remarks Any additional information to be associated with the transaction.
     * @param string|null $queue_timeout_url The URL to be specified in your request that will be used by API Proxy to send notification
     * incase the payment request is timed out while awaiting processing in the queue.
     * @param string|null $result_url The URL to be specified in your request that will be used by M-Pesa to send notification
     * upon processing of the payment request.
     * @param string|null $occasion Any additional information to be associated with the transaction.
     *
     * @return static
     */
    public function paymentRequest($initiator_name = null, $initiator_password = null, $command_id = null, $amount = null, $partyA = null, $phone_number = null, $remarks = null, $queue_timeout_url = null, $result_url = null, $occasion = null): static
    {
        if ($initiator_name != null) $this->initiatorName($initiator_name);
        if ($command_id != null) $this->commandId($command_id);
        if ($amount != null) $this->amount($amount);
        if ($partyA != null) $this->businessCode($partyA);
        if ($phone_number != null) $this->phoneNumber($phone_number);
        if ($remarks != null) $this->remarks($remarks);
        if ($queue_timeout_url != null) $this->queueTimeoutUrl($queue_timeout_url);
        if ($result_url != null) $this->resultUrl($result_url);
        if ($occasion != null) $this->occasion($occasion);
        if ($initiator_password != null) $this->securityCredential($initiator_password);
        $array_data = [
            "InitiatorName" => $this->initiator_name,
            "SecurityCredential" => $this->security_credential,
            "CommandID" => $this->command_id,
            "Amount" => (int)$this->amount,
            "PartyA" => $this->business_code,
            "PartyB" => $this->phone_number,
            "Remarks" => $this->remarks,
            "QueueTimeOutURL" => $this->queue_timeout_url,
            "ResultURL" => $this->result_url,
            "Occassion" => $this->occasion,
        ];
        $this->response = $this->curls($array_data, "/mpesa/b2c/v1/paymentrequest");
        return $this;
    }

}