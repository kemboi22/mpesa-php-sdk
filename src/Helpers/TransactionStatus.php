<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;



class TransactionStatus extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator = "";
    private string $transaction_id = "";
    private string $identifier_type = "";
    private string $remarks = "";
    private string $occasion = "";

    public function initiator($initiator): static
    {
        $this->initiator = $initiator;
        return $this;
    }

    public function transaction_id($transaction_id): static
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }
    public function identifier_type($identifier_type): static
    {
        $this->identifier_type = $identifier_type;
        return $this;
    }
    public function remarks($remarks): static
    {
        $this->remarks = $remarks;
        return $this;
    }
    public function occasion($occasion): static
    {
        $this->occasion = $occasion;
        return $this;
    }




    public function check_transaction_status($initiator = null, $initiator_password= null, $remarks = null, $partyA = null, $transaction_id = null, $identifier_type = null, $queue_timeout_url = null, $result_url = null, $occasion = null): static
    {
        if ($initiator != null) $this->initiator($initiator);
        if ($remarks != null) $this->remarks($remarks);
        if ($partyA != null) $this->business_code($partyA);
        if ($transaction_id != null) $this->transaction_id($transaction_id);
        if ($identifier_type != null) $this->identifier_type($identifier_type);
        if ($queue_timeout_url != null) $this->queue_timeout_url($queue_timeout_url);
        if ($result_url != null) $this->result_url($result_url);
        if ($occasion != null) $this->occasion($occasion);
        if ($initiator_password != null) $this->security_credential($initiator_password);

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
       $this->response = $this->curls($array_data, "https://sandbox.safaricom.co.ke/mpesa/transactionstatus/v1/query");
       return $this;
    }

}