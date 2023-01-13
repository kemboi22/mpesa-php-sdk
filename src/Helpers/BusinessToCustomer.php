<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

class BusinessToCustomer extends \Kemboielvis\MpesaSdkPhp\Mpesa
{
    private string $initiator_name = "";
    private string $security_credential = "";
    private string $command_id = "";
    private string $remarks = "";
    private string $queue_timeout_url = "";
    private string $occasion = "";
    private string $result_url = "";


    public function initiator_name($initiator_name): static
    {
        $this->initiator_name = $initiator_name;
        return $this;
    }
    public function security_credential($initiator_password): static
    {
        $method = "aes-256-cbc";
        $password = "mypassword";
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $this->security_credential = base64_encode($iv.openssl_encrypt("$initiator_password + Certificate", $method, $password, 0, $iv));
        return $this;
    }

    public function command_id($command_id): static
    {
        $this->command_id = $command_id;
        return $this;
    }
    public function remarks($remarks): static
    {
        $this->remarks = $remarks;
        return $this;
    }
    public function queue_timeout_url($timeout_url): static
    {
        $this->queue_timeout_url = $timeout_url;
        return $this;
    }
    public function occasion($occasion): static
    {
        $this->occasion = $occasion;
        return $this;
    }
    public function result_url($result_url): static
    {
        $this->result_url = $result_url;
        return $this;
    }

    public function payment_request($initiator_name = null, $initiator_password = null, $command_id = null, $amount = null, $partyA = null, $phone_number = null, $remarks = null, $queue_timeout_url = null, $result_url = null, $occasion = null): static
    {
        if ($initiator_name != null) $this->initiator_name($initiator_name);
        if ($command_id != null) $this->command_id($command_id);
        if ($amount != null) $this->amount($amount);
        if ($partyA != null) $this->business_code($partyA);
        if ($phone_number != null) $this->phone_number($phone_number);
        if ($remarks != null) $this->remarks($remarks);
        if ($queue_timeout_url != null) $this->queue_timeout_url($queue_timeout_url);
        if ($result_url != null) $this->result_url($result_url);
        if ($occasion != null) $this->occasion($occasion);
        if ($initiator_password != null) $this->security_credential($initiator_password);
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
        $this->response = $this->curls($array_data, "https://sandbox.safaricom.co.ke/mpesa/b2c/v1/paymentrequest");
        return $this;
    }

}