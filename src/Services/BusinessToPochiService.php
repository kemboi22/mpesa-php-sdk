<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Business to Pochi (B2Pochi) service.
 *
 * Pays from a B2C shortcode into a customer's business wallet
 * (Pochi la Biashara).
 */
class BusinessToPochiService extends AbstractService
{
    public const MIN_AMOUNT = 10;

    public const MAX_AMOUNT = 250000;

    private string $originatorConversationId = '';

    private string $initiatorName = '';

    private string $amount = '';

    private string $phoneNumber = '';

    private string $remarks = '';

    private string $occasion = '';

    /**
     * Sets a unique ID for this request, used to prevent double disbursement.
     * A UUID v4 is generated automatically when not set.
     *
     * @param string $id The originator conversation ID.
     *
     * @return $this
     */
    public function setOriginatorConversationId(string $id): self
    {
        $this->originatorConversationId = $id;

        return $this;
    }

    /**
     * Get the originator conversation ID sent with the last payment.
     * Use it to match the result callback or query the transaction status.
     *
     * @return string The originator conversation ID
     */
    public function getOriginatorConversationId(): string
    {
        return $this->originatorConversationId;
    }

    /**
     * Sets the username of the M-Pesa API operator.
     * The user needs the "ORG B2C API initiator" role.
     *
     * @param string $initiatorName The username of the M-Pesa API operator.
     *
     * @return $this
     */
    public function setInitiatorName(string $initiatorName): self
    {
        $this->initiatorName = $initiatorName;

        return $this;
    }

    /**
     * Sets the encrypted security credential (e.g. generated on the Daraja portal).
     *
     * @param string $credential The encrypted security credential.
     *
     * @return $this
     */
    public function setSecurityCredential(string $credential): self
    {
        $this->config->overrideSecurityCredential($credential);

        return $this;
    }

    /**
     * Sets the amount to pay (Ksh 10 to 250,000).
     *
     * @param int|string $amount The amount.
     *
     * @return $this
     */
    public function setAmount($amount): self
    {
        $this->amount = (string)$amount;

        return $this;
    }

    /**
     * Sets the B2C shortcode the money is sent from.
     * Defaults to the configured business code.
     *
     * @param string $partyA The B2C shortcode.
     *
     * @return $this
     */
    public function setPartyA(string $partyA): self
    {
        $this->config->setBusinessCode($partyA);

        return $this;
    }

    /**
     * Sets the phone number of the Pochi la Biashara wallet receiving the money.
     *
     * @param string $phoneNumber The recipient's phone number.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $this->cleanPhoneNumber($phoneNumber);

        return $this;
    }

    /**
     * Sets additional information for the transaction (2 to 100 characters).
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
     * Optional. Sets additional information for the transaction (1 to 100 characters).
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
     * Sets the URL that receives a notification if the request times out.
     *
     * @param string $url The queue timeout URL.
     *
     * @return $this
     */
    public function setQueueTimeoutUrl(string $url): self
    {
        $this->config->setQueueTimeoutUrl($url);

        return $this;
    }

    /**
     * Sets the URL that receives the transaction result.
     *
     * @param string $url The result URL.
     *
     * @return $this
     */
    public function setResultUrl(string $url): self
    {
        $this->config->setResultUrl($url);

        return $this;
    }

    /**
     * Validate required parameters before paying.
     *
     * @throws \InvalidArgumentException If required parameters are missing or invalid
     */
    private function validateParams(): void
    {
        if (empty($this->initiatorName)) {
            throw new \InvalidArgumentException('Initiator name is required');
        }

        if (empty($this->config->getSecurityCredential())) {
            throw new \InvalidArgumentException('Security credential is required');
        }

        if (empty($this->config->getBusinessCode())) {
            throw new \InvalidArgumentException('Business code (PartyA) is required');
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number (PartyB) is required');
        }

        if (! is_numeric($this->amount)) {
            throw new \InvalidArgumentException('Amount is required and must be a number');
        }

        if ($this->amount < self::MIN_AMOUNT || $this->amount > self::MAX_AMOUNT) {
            throw new \InvalidArgumentException(
                sprintf('Amount must be between %d and %d', self::MIN_AMOUNT, self::MAX_AMOUNT)
            );
        }

        $remarksLength = strlen($this->remarks);
        if ($remarksLength < 2 || $remarksLength > 100) {
            throw new \InvalidArgumentException('Remarks must be between 2 and 100 characters');
        }

        if (strlen($this->occasion) > 100) {
            throw new \InvalidArgumentException('Occasion cannot be longer than 100 characters');
        }

        if (empty($this->config->getQueueTimeoutUrl())) {
            throw new \InvalidArgumentException('Queue timeout URL is required');
        }

        if (empty($this->config->getResultUrl())) {
            throw new \InvalidArgumentException('Result URL is required');
        }
    }

    /**
     * Send a payment to the customer's Pochi la Biashara wallet.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing or invalid
     */
    public function pay(): self
    {
        $this->validateParams();

        if (empty($this->originatorConversationId)) {
            $this->originatorConversationId = $this->generateUuid();
        }

        // "Occassion" is spelled as the M-Pesa API expects it
        $requestData = [
            'OriginatorConversationID' => $this->originatorConversationId,
            'InitiatorName' => $this->initiatorName,
            'SecurityCredential' => $this->config->getSecurityCredential(),
            'CommandID' => 'BusinessPayToPochi',
            'Amount' => $this->amount,
            'PartyA' => $this->config->getBusinessCode(),
            'PartyB' => $this->phoneNumber,
            'Remarks' => $this->remarks,
            'QueueTimeOutURL' => $this->config->getQueueTimeoutUrl(),
            'ResultURL' => $this->config->getResultUrl(),
        ];

        if (! empty($this->occasion)) {
            $requestData['Occassion'] = $this->occasion;
        }

        $this->response = $this->client->executeRequest($requestData, '/mpesa/b2pochi/v1/paymentrequest');

        return $this;
    }
}
