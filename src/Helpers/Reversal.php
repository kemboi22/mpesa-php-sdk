<?php

namespace Kemboielvis\MpesaSdkPhp\Helpers;

class Reversal extends \Kemboielvis\MpesaSdkPhp\Mpesa {
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
     * @return static
     */
    public function initiator($initiator): static {
        $this->initiator = $initiator;

        return $this;
    }

    /**
     * The ID of the transaction to be reversed.
     *
     * @param string $transaction_id The ID of the transaction to be reversed.
     *
     * @return static
     */
    public function transactionId($transaction_id): static {
        $this->transaction_id = $transaction_id;

        return $this;
    }

    /**
     * The type of identifier used for the receiver.
     *
     * Possible values are:
     * - MSISDN: Mobile phone number
     * - TillNumber: Till number
     * - ShortCode: Short code
     *
     * @param string $receiver_identifier_type The type of identifier used for the receiver.
     *
     * @return static
     */
    public function identifierType($receiver_identifier_type): static {
        $this->receiver_identifier_type = $receiver_identifier_type;

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
    public function remarks($remarks): static {
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
     * @return static
     */
    public function occasion($occasion): static {
        $this->occasion = $occasion;

        return $this;
    }

    /**
     * Constructs a new instance of the Reversal class.
     *
     * @param string $consumer_key    The consumer key for the M-Pesa API.
     * @param string $consumer_secret The consumer secret for the M-Pesa API.
     * @param string $baseUrl         The base URL of the M-Pesa API.
     */
    public function __construct($consumer_key, $consumer_secret, $baseUrl) {
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->baseUrl = $baseUrl;
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
     *                                              Possible values are:
     *                                              - MSISDN: Mobile phone number
     *                                              - TillNumber: Till number
     *                                              - ShortCode: Short code
     * @param string|null $queue_timeout_url        The URL to be specified in your request that will be used by API Proxy to send notification
     *                                              incase the payment request is timed out while awaiting processing in the queue.
     * @param string|null $result_url               The URL to be specified in your request that will be used by M-Pesa to send notification
     *                                              upon processing of the payment request.
     * @param string|null $occasion                 Any additional information to be associated with the transaction.
     *
     * @return static
     */
    public function reverse($initiator = null, $initiator_password = null, $remarks = null, $receiver_party = null, $transaction_id = null, $receiver_identifier_type = null, $queue_timeout_url = null, $result_url = null, $occasion = null): static {
        if ($initiator != null) {
            $this->initiator($initiator);
        }
        if ($receiver_party != null) {
            $this->businessCode($receiver_party);
        }
        if ($receiver_identifier_type != null) {
            $this->identifierType($receiver_identifier_type);
        }
        if ($transaction_id != null) {
            $this->transactionId($transaction_id);
        }
        if ($queue_timeout_url != null) {
            $this->queueTimeoutUrl($queue_timeout_url);
        }
        if ($result_url != null) {
            $this->resultUrl($result_url);
        }
        if ($occasion != null) {
            $this->occasion($occasion);
        }
        if ($initiator_password != null) {
            $this->securityCredential($initiator_password);
        }
        if ($remarks != null) {
            $this->remarks($remarks);
        }

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
        $this->response = $this->curls($array_data, "/mpesa/reversal/v1/request");

        return $this;
    }
}
