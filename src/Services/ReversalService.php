<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Reversal service.
 */
class ReversalService extends AbstractService {
    private string $initiator = "";

    private string $transaction_id = "";

    private string $receiver_identifier_type = "";

    private string $remarks = "";

    private string $occasion = "";

    /**
     * Sets the username of the M-Pesa API operator.
     *
     * @param string $initiator The username of the M-Pesa API operator.
     *
     * @return $this
     */
    public function setInitiator(string $initiator): self {
        $this->initiator = $initiator;

        return $this;
    }

    /**
     * Sets the ID of the transaction to be reversed.
     *
     * @param string $transaction_id The ID of the transaction to be reversed.
     *
     * @return $this
     */
    public function setTransactionId(string $transaction_id): self {
        $this->transaction_id = $transaction_id;

        return $this;
    }

    /**
     * Sets the type of identifier used for the receiver.
     *
     * Possible values are:
     * - MSISDN: Mobile phone number
     * - TillNumber: Till number
     * - ShortCode: Short code
     *
     * @param string $receiver_identifier_type The type of identifier used for the receiver.
     *
     * @return $this
     */
    public function setReceiverIdentifierType(string $receiver_identifier_type): self {
        $this->receiver_identifier_type = $receiver_identifier_type;

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
    public function setRemarks(string $remarks): self {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Sets the occasion for the transaction.
     *
     * The occasion is a descriptive name given to the transaction.
     * Examples of occasions include "Reversal", "Refund", etc.
     * This field provides additional context for the transaction.
     *
     * @param string $occasion The occasion for the transaction.
     *
     * @return $this
     */
    public function setOccasion(string $occasion): self {
        $this->occasion = $occasion;

        return $this;
    }

    /**
     * Reverses a transaction.
     *
     * @param string|null $initiator                The username of the M-Pesa API operator.
     * @param string|null $initiator_password       The password to authenticate the initiator.
     * @param string|null $remarks                  Any additional information to be associated with the transaction.
     * @param string|null $receiver_party           The shortcode or MSISDN of the receiver.
     * @param string|null $transaction_id           The ID of the transaction to be reversed.
     * @param string|null $receiver_identifier_type The type of identifier used for the receiver.
     * @param string|null $queue_timeout_url        The URL for timeout notification if the request times out in the queue.
     * @param string|null $result_url               The URL to receive the response from the M-Pesa API.
     * @param string|null $occasion                 Any additional information to be associated with the transaction.
     *
     * @return array The response from the M-Pesa API.
     */
    public function reverse(
        ?string $initiator = null,
        ?string $initiator_password = null,
        ?string $remarks = null,
        ?string $receiver_party = null,
        ?string $transaction_id = null,
        ?string $receiver_identifier_type = null,
        ?string $queue_timeout_url = null,
        ?string $result_url = null,
        ?string $occasion = null
    ): array {
        if ($initiator !== null) {
            $this->setInitiator($initiator);
        }
        if ($remarks !== null) {
            $this->setRemarks($remarks);
        }
        if ($receiver_party !== null) {
            $this->config->setBusinessCode($receiver_party);
        }
        if ($transaction_id !== null) {
            $this->setTransactionId($transaction_id);
        }
        if ($receiver_identifier_type !== null) {
            $this->setReceiverIdentifierType($receiver_identifier_type);
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
            "CommandID" => "TransactionReversal",
            "TransactionID" => $this->transaction_id,
            "ReceiverParty" => $this->config->getBusinessCode(),
            "ReceiverIdentifierType" => $this->receiver_identifier_type,
            "ResultURL" => $this->config->getResultUrl(),
            "QueueTimeOutURL" => $this->config->getQueueTimeoutUrl(),
            "Remarks" => $this->remarks,
            "Occassion" => $this->occasion,
        ];

        return $this->client->executeRequest($requestData, "/mpesa/reversal/v1/request");
    }
}
