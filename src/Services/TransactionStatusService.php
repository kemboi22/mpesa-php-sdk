<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Transaction Status service.
 */
class TransactionStatusService extends AbstractService
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
     * @return $this
     */
    public function setInitiator(string $initiator): self
    {
        $this->initiator = $initiator;

        return $this;
    }

    /**
     * Sets the ID of the transaction to check its status.
     *
     * @param string $transaction_id The ID of the transaction to check its status.
     *
     * @return $this
     */
    public function setTransactionId(string $transaction_id): self
    {
        $this->transaction_id = $transaction_id;

        return $this;
    }

    /**
     * Sets the type of identifier used for the party.
     *
     * @param string $identifier_type The type of identifier used.
     *
     * @return $this
     */
    public function setIdentifierType(string $identifier_type): self
    {
        $this->identifier_type = $identifier_type;

        return $this;
    }

    /**
     * Sets additional information to be associated with the transaction.
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
     * Sets the occasion for the transaction.
     *
     * The occasion is a descriptive name given to the transaction.
     * This field provides additional context for the transaction.
     *
     * @param string $occasion The occasion for the transaction.
     *
     * @return $this
     */
    public function setOccasion(string $occasion): self
    {
        $this->occasion = $occasion;

        return $this;
    }

    /**
     * Checks the status of a transaction via the M-Pesa API.
     *
     * @param string|null $initiator          The username of the M-Pesa API operator.
     * @param string|null $initiator_password The password to authenticate the initiator.
     * @param string|null $remarks            Any additional information to be associated with the transaction.
     * @param string|null $partyA             The shortcode to receive the transaction.
     * @param string|null $transaction_id     The ID of the transaction to check its status.
     * @param string|null $identifier_type    The type of identifier used for the party.
     * @param string|null $queue_timeout_url  The URL for timeout notification if the request times out in the queue.
     * @param string|null $result_url         The URL to receive the response from the M-Pesa API.
     * @param string|null $occasion           Any additional information to be associated with the transaction.
     *
     * @return array The response from the M-Pesa API.
     */
    public function checkTransactionStatus(
        ?string $initiator = null,
        ?string $initiator_password = null,
        ?string $remarks = null,
        ?string $partyA = null,
        ?string $transaction_id = null,
        ?string $identifier_type = null,
        ?string $queue_timeout_url = null,
        ?string $result_url = null,
        ?string $occasion = null
    ): self {
        if ($initiator !== null) {
            $this->setInitiator($initiator);
        }
        if ($remarks !== null) {
            $this->setRemarks($remarks);
        }
        if ($partyA !== null) {
            $this->config->setBusinessCode($partyA);
        }
        if ($transaction_id !== null) {
            $this->setTransactionId($transaction_id);
        }
        if ($identifier_type !== null) {
            $this->setIdentifierType($identifier_type);
        }
        if ($queue_timeout_url !== null) {
            $this->config->setQueueTimeoutUrl($queue_timeout_url);
        }
        if ($result_url !== null) {
            $this->config->setResultUrl($result_url);
        }
        if ($occasion !== null) {
            $this->setOccasion($occasion);
        }
        if ($initiator_password !== null) {
            $this->config->setSecurityCredential($initiator_password);
        }

        $requestData = [
            "Initiator" => $this->initiator,
            "SecurityCredential" => $this->config->getSecurityCredential(),
            "CommandID" => "TransactionStatusQuery",
            "TransactionID" => $this->transaction_id,
            "PartyA" => $this->config->getBusinessCode(),
            "IdentifierType" => $this->identifier_type,
            "ResultURL" => $this->config->getResultUrl(),
            "QueueTimeOutURL" => $this->config->getQueueTimeoutUrl(),
            "Remarks" => $this->remarks,
            "Occassion" => $this->occasion,
        ];

        $this->response = $this->client->executeRequest($requestData, "/mpesa/transactionstatus/v1/query");
        return $this;
    }
}
