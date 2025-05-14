<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Account Balance service.
 */
class AccountBalanceService extends AbstractService
{
    private string $initiator = "";

    private string $identifier_type = "";

    private string $remarks = "";

    /**
     * Sets the username of the M-Pesa API operator.
     *
     * @param string $initiator The username of the M-Pesa API operator.
     *
     * @return $this
     */
    public function setInitiator(string $initiator): self
    {
        $this->initiator = $initiator;

        return $this;
    }

    /**
     * Sets the identifier type for the account balance request.
     *
     * @param string $identifier_type The type of identifier to associate with the request.
     *
     * @return $this
     */
    public function setIdentifierType(string $identifier_type): self
    {
        $this->identifier_type = $identifier_type;

        return $this;
    }

    /**
     * Sets any additional information to be associated with the transaction.
     * Maximum of 100 characters.
     *
     * @param string $remarks The remarks for the transaction.
     *
     * @return $this
     */
    public function setRemarks(string $remarks): self
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Initiates an account balance request to the M-Pesa API.
     *
     * @param string|null $initiator          The username of the M-Pesa API operator.
     * @param string|null $initiator_password The password to authenticate the initiator.
     * @param string|null $partyA             The shortcode to receive the transaction.
     * @param string|null $identifier_type    The type of organization receiving the transaction.
     * @param string|null $remarks            Any additional information to be associated with the transaction.
     * @param string|null $queue_url          The URL to receive timeout notifications.
     * @param string|null $result_url         The URL to receive the response from the M-Pesa API.
     *
     * @return array The response from the M-Pesa API.
     */
    public function accountBalance(
        ?string $initiator = null,
        ?string $initiator_password = null,
        ?string $partyA = null,
        ?string $identifier_type = null,
        ?string $remarks = null,
        ?string $queue_url = null,
        ?string $result_url = null
    ): array {
        if ($initiator !== null) {
            $this->setInitiator($initiator);
        }
        if ($remarks !== null) {
            $this->setRemarks($remarks);
        }
        if ($partyA !== null) {
            $this->config->setBusinessCode($partyA);
        }
        if ($identifier_type !== null) {
            $this->setIdentifierType($identifier_type);
        }
        if ($queue_url !== null) {
            $this->config->setQueueTimeoutUrl($queue_url);
        }
        if ($result_url !== null) {
            $this->config->setResultUrl($result_url);
        }
        if ($initiator_password !== null) {
            $this->config->setSecurityCredential($initiator_password);
        }

        $requestData = [
            "Initiator" => $this->initiator,
            "SecurityCredential" => $this->config->getSecurityCredential(),
            "CommandID" => "AccountBalance",
            "PartyA" => $this->config->getBusinessCode(),
            "IdentifierType" => $this->identifier_type,
            "Remarks" => $this->remarks,
            "QueueTimeOutURL" => $this->config->getQueueTimeoutUrl(),
            "ResultURL" => $this->config->getResultUrl(),
        ];

        return $this->client->executeRequest($requestData, "/mpesa/accountbalance/v1/query");
    }
}
