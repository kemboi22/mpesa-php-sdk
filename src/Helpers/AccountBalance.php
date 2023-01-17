<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

class AccountBalance extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator = "";
    private string $identifier_type = "";
    private string $remarks = "";

    public function initiator($initiator): static
    {
        $this->initiator = $initiator;
        return $this;
    }
    public function identifierType($identifier_type): static
    {
        $this->identifier_type = $identifier_type;
        return $this;
    }
    public function remarks($remarks): static
    {
        $this->remarks = $remarks;
        return $this;
    }
    public function __construct($consumer_key, $consumer_secret, $baseUrl)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->baseUrl = $baseUrl;
    }

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