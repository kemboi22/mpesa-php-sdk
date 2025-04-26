<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;
    /**
     * Represents an account balance request to the M-Pesa API.
     *
     * This class extends the M-Pesa class and provides functionality to check
     * the balance of an account using the M-Pesa API. It allows setting various
     * parameters required for the request such as initiator, identifier type,
     * and remarks.
     */
class AccountBalance extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator = "";
    private string $identifier_type = "";
    private string $remarks = "";

    /**
     * The username of the M-Pesa API operator.
     *
     * @param string $initiator
     * @return static
     */
    public function initiator($initiator): static
    {
        $this->initiator = $initiator;
        return $this;
    }
    /**
     * Sets the identifier type for the account balance request.
     *
     * @param string $identifier_type The type of identifier to associate with the request.
     * @return static
     */
    public function identifierType($identifier_type): static
    {
        $this->identifier_type = $identifier_type;
        return $this;
    }
    /**
     * This is any additional information to be associated with the transaction.
     * Maximum of 100 characters.
     *
     * @param string $remarks
     * @return static
     */
    public function remarks($remarks): static
    {
        $this->remarks = $remarks;
        return $this;
    }
    /**
     * Class constructor
     *
     * @param string $consumer_key
     * @param string $consumer_secret
     * @param string $baseUrl
     */
    public function __construct($consumer_key, $consumer_secret, $baseUrl)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->baseUrl = $baseUrl;
    }

/**
 * Initiates an account balance request to the M-Pesa API.
 *
 * @param string|null $initiator The username of the M-Pesa API operator.
 * @param string|null $initiator_password The password to authenticate the initiator.
 * @param string|null $partyA The shortcode to receive the transaction.
 * @param string|null $identifier_type The type of organization receiving the transaction.
 * @param string|null $remarks Any additional information to be associated with the transaction.
 * @param string|null $queue_url The URL to receive timeout notifications.
 * @param string|null $result_url The URL to receive the response from the M-Pesa API.
 *
 * @return static
 */
    public function accountBalance($initiator = null, $initiator_password = null, $partyA = null, $identifier_type = null, $remarks = null, $queue_url = null, $result_url = null): static
    {
        if ($initiator != null) $this->initiator($initiator);
        if ($remarks != null) $this->remarks($remarks);
        if ($partyA != null) $this->businessCode($partyA);
        if ($identifier_type != null) $this->identifierType($identifier_type);
        if ($queue_url != null) $this->queueTimeoutUrl($queue_url);
        if ($result_url != null) $this->resultUrl($result_url);
        if ($initiator_password != null) $this->securityCredential($initiator_password);
        $array_data = [
            "Initiator" => $this->initiator,
            "SecurityCredential" => $this->security_credential,
            "CommandID" => "AccountBalance",
            "PartyA" => $this->business_code,
            "IdentifierType" => $this->identifier_type,
            "Remarks" => $this->remarks,
            "QueueTimeOutURL" => $this->queue_timeout_url,
            "ResultURL" => $this->result_url,
        ];
        $this->response = $this->curls($array_data, "/mpesa/accountbalance/v1/query");
        return $this;
    }

}