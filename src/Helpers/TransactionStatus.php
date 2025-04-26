<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;



class TransactionStatus extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator = "";
    private string $transaction_id = "";
    private string $identifier_type = "";
    private string $remarks = "";
    private string $occasion = "";


    /**
     * Sets the username of the M-Pesa API operator.
     *
     * @param string $initiator The username of the M-Pesa API operator.
     *
     * @return static
     */
    public function initiator($initiator): static
    {
        $this->initiator = $initiator;
        return $this;
    }

    /**
     * Sets the ID of the transaction to check its status.
     *
     * @param string $transaction_id The ID of the transaction to check its status.
     *
     * @return static
     */
    public function transactionId($transaction_id): static
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }
    public function identifierType($identifier_type): static
    {
        $this->identifier_type = $identifier_type;
        return $this;
    }
    /**
     * Additional information to be associated with the transaction.
     * Maximum of 100 characters.
     *
     * @param string $remarks The remarks for the transaction.
     *
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
     * This field provides additional context for the transaction.
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
     * Constructs a new instance of the TransactionStatus class.
     *
     * @param string $consumer_key The consumer key for the M-Pesa API.
     * @param string $consumer_secret The consumer secret for the M-Pesa API.
     * @param string $baseUrl The base URL of the M-Pesa API.
     */
    public function __construct($consumer_key, $consumer_secret, $baseUrl)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->baseUrl = $baseUrl;
    }

    /**
     * Checks the status of a transaction via the M-Pesa API.
     *
     * This function sends a request to check the status of a transaction using the
     * provided parameters. If any parameters are not provided, default values are used.
     *
     * @param string|null $initiator The username of the M-Pesa API operator.
     * @param string|null $initiator_password The password to authenticate the initiator.
     * @param string|null $remarks Any additional information to be associated with the transaction.
     * @param string|null $partyA The shortcode to receive the transaction.
     * @param string|null $transaction_id The ID of the transaction to check its status.
     * @param string|null $identifier_type The type of identifier used for the party.
     * @param string|null $queue_timeout_url The URL for timeout notification if the request times out in the queue.
     * @param string|null $result_url The URL to receive the response from the M-Pesa API.
     * @param string|null $occasion Any additional information to be associated with the transaction.
     *
     * @return static
     */
    public function checkTransactionStatus($initiator = null, $initiator_password= null, $remarks = null, $partyA = null, $transaction_id = null, $identifier_type = null, $queue_timeout_url = null, $result_url = null, $occasion = null): static
    {
        if ($initiator != null) $this->initiator($initiator);
        if ($remarks != null) $this->remarks($remarks);
        if ($partyA != null) $this->businessCode($partyA);
        if ($transaction_id != null) $this->transactionId($transaction_id);
        if ($identifier_type != null) $this->identifierType($identifier_type);
        if ($queue_timeout_url != null) $this->queueTimeoutUrl($queue_timeout_url);
        if ($result_url != null) $this->resultUrl($result_url);
        if ($occasion != null) $this->occasion($occasion);
        if ($initiator_password != null) $this->securityCredential($initiator_password);

       $array_data = [
           "Initiator" => $this->initiator,
            "SecurityCredential" => $this->security_credential,
            "CommandID" => "TransactionStatusQuery",
            "TransactionID" => $this->transaction_id,
            "PartyA" => $this->business_code,
            "IdentifierType" => $this->identifier_type,
            "ResultURL" => $this->result_url,
            "QueueTimeOutURL" => $this->queue_timeout_url,
            "Remarks" => $this->remarks,
            "Occassion" => $this->occasion,
       ];
       $this->response = $this->curls($array_data, "/mpesa/transactionstatus/v1/query");
       return $this;
    }

}