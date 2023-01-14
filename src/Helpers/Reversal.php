<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

class Reversal extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator = "";
    private string $transaction_id = "";
    private string $receiver_identifier_type = "";
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
    public function identifier_type($receiver_identifier_type): static
    {
        $this->receiver_identifier_type = $receiver_identifier_type;
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
    public function __construct($consumer_key, $consumer_secret)
    {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
    }

    public function reverse($initiator = null, $initiator_password= null, $remarks = null, $receiver_party = null, $transaction_id = null, $receiver_identifier_type = null, $queue_timeout_url = null, $result_url = null, $occasion = null): static
    {
        if ($initiator != null) $this->initiator($initiator);
        if ($receiver_party != null) $this->business_code($receiver_party);
        if ($receiver_identifier_type != null) $this->identifier_type($receiver_identifier_type);
        if ($transaction_id != null) $this->transaction_id($transaction_id);
        if ($queue_timeout_url != null) $this->queue_timeout_url($queue_timeout_url);
        if ($result_url != null) $this->result_url($result_url);
        if ($occasion != null) $this->occasion($occasion);
        if ($initiator_password != null) $this->security_credential($initiator_password);
        if ($remarks != null) $this->remarks($remarks);

        $array_data = [
            "Initiator" => $this->initiator,
            "SecurityCredential" => $this->security_credential,
            "CommandID" => "TransactionReversal",
            "TransactionID" => $this->transaction_id,
            "ReceiverParty" => $this->business_code,
            "ReceiverIdentifierType" => $this->receiver_identifier_type,
            "ResultURL" => $this->result_url,
            "QueueTimeOutURL" => $this->queue_timeout_url,
            "Remarks" => $this->remarks,
            "Occassion" => $this->occasion,
        ];
        $this->response = $this->curls($array_data, "https://sandbox.safaricom.co.ke/mpesa/reversal/v1/request");
        return $this;
    }

}