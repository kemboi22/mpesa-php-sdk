<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Base class for payments sent through the B2B payment request API
 * (/mpesa/b2b/v1/paymentrequest): Business Pay Bill, Business Buy Goods and
 * B2C Account Top Up.
 *
 * Money moves from your MMF/Working account to the recipient's utility
 * (or merchant) account.
 */
abstract class AbstractB2BPaymentService extends AbstractService
{
    public const MAX_ACCOUNT_REFERENCE_LENGTH = 13;

    /**
     * Whether this payment type needs an account reference.
     */
    protected bool $requiresAccountReference = false;

    protected string $initiator = '';

    protected string $amount = '';

    protected string $partyB = '';

    protected string $accountReference = '';

    protected string $requester = '';

    protected string $remarks = '';

    protected string $occasion = '';

    /**
     * The CommandID sent to M-Pesa for this payment type.
     *
     * @return string
     */
    abstract protected function getCommandId(): string;

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
     * Sets the amount to send.
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
     * Sets the shortcode the money is deducted from.
     * Defaults to the configured business code.
     *
     * @param string $partyA The sender shortcode.
     *
     * @return $this
     */
    public function setPartyA(string $partyA): self
    {
        $this->config->setBusinessCode($partyA);

        return $this;
    }

    /**
     * Sets the shortcode the money is moved to.
     *
     * @param string $partyB The receiver shortcode.
     *
     * @return $this
     */
    public function setPartyB(string $partyB): self
    {
        $this->partyB = $partyB;

        return $this;
    }

    /**
     * Sets the account reference for the transaction (up to 13 characters).
     *
     * @param string $reference The account reference.
     *
     * @return $this
     */
    public function setAccountReference(string $reference): self
    {
        $this->accountReference = $reference;

        return $this;
    }

    /**
     * Optional. Sets the mobile number of the consumer on whose behalf you are paying.
     *
     * @param string $phoneNumber The requester's phone number.
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setRequester(string $phoneNumber): self
    {
        $this->requester = $this->cleanPhoneNumber($phoneNumber);

        return $this;
    }

    /**
     * Sets additional information for the transaction.
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
     * Optional. Sets additional information for the transaction.
     * Maximum of 100 characters.
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
     * Validate required parameters before sending.
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    protected function validateParams(): void
    {
        if (empty($this->initiator)) {
            throw new \InvalidArgumentException('Initiator is required');
        }

        if (empty($this->config->getSecurityCredential())) {
            throw new \InvalidArgumentException('Security credential is required');
        }

        if (empty($this->config->getBusinessCode())) {
            throw new \InvalidArgumentException('Business code (PartyA) is required');
        }

        if (empty($this->partyB)) {
            throw new \InvalidArgumentException('Receiver shortcode (PartyB) is required');
        }

        if (empty($this->amount)) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if ($this->requiresAccountReference && '' === $this->accountReference) {
            throw new \InvalidArgumentException('Account reference is required');
        }

        if (strlen($this->accountReference) > self::MAX_ACCOUNT_REFERENCE_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Account reference cannot be longer than %d characters', self::MAX_ACCOUNT_REFERENCE_LENGTH)
            );
        }

        if (strlen($this->remarks) > 100) {
            throw new \InvalidArgumentException('Remarks cannot be longer than 100 characters');
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
     * Validate and send the payment request.
     *
     * @param string $defaultRemarks Remarks to send when none are set
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    protected function sendPayment(string $defaultRemarks): self
    {
        $this->validateParams();

        // "RecieverIdentifierType" and "Occassion" are spelled as the M-Pesa API expects them
        $requestData = [
            'Initiator' => $this->initiator,
            'SecurityCredential' => $this->config->getSecurityCredential(),
            'CommandID' => $this->getCommandId(),
            'SenderIdentifierType' => '4',
            'RecieverIdentifierType' => '4',
            'Amount' => $this->amount,
            'PartyA' => $this->config->getBusinessCode(),
            'PartyB' => $this->partyB,
            'AccountReference' => $this->accountReference,
            'Remarks' => $this->remarks ?: $defaultRemarks,
            'QueueTimeOutURL' => $this->config->getQueueTimeoutUrl(),
            'ResultURL' => $this->config->getResultUrl(),
        ];

        if (! empty($this->requester)) {
            $requestData['Requester'] = $this->requester;
        }

        if (! empty($this->occasion)) {
            $requestData['Occassion'] = $this->occasion;
        }

        $this->response = $this->client->executeRequest($requestData, '/mpesa/b2b/v1/paymentrequest');

        return $this;
    }
}
