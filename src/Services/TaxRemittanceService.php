<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Tax Remittance service.
 *
 * Remits tax to the Kenya Revenue Authority (KRA) against a
 * payment registration number (PRN) issued by KRA.
 */
class TaxRemittanceService extends AbstractService
{
    /**
     * KRA shortcode; the only credit party allowed for this API.
     */
    public const KRA_SHORTCODE = '572572';

    private string $initiator = '';

    private string $amount = '';

    private string $partyB = self::KRA_SHORTCODE;

    private string $accountReference = '';

    private string $remarks = '';

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
     * Sets the tax amount to remit.
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
     * Sets the credit party. Only the KRA shortcode (572572) is allowed,
     * which is the default.
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
     * Sets the payment registration number (PRN) issued by KRA.
     *
     * @param string $prn The PRN.
     *
     * @return $this
     */
    public function setAccountReference(string $prn): self
    {
        $this->accountReference = $prn;

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
     * Validate required parameters before remitting.
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    private function validateParams(): void
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

        if (empty($this->amount)) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if (empty($this->accountReference)) {
            throw new \InvalidArgumentException('Account reference (KRA PRN) is required');
        }

        if (empty($this->config->getQueueTimeoutUrl())) {
            throw new \InvalidArgumentException('Queue timeout URL is required');
        }

        if (empty($this->config->getResultUrl())) {
            throw new \InvalidArgumentException('Result URL is required');
        }
    }

    /**
     * Remit tax to KRA via the M-Pesa API.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function remit(): self
    {
        $this->validateParams();

        // "RecieverIdentifierType" is spelled as the M-Pesa API expects it
        $requestData = [
            'Initiator' => $this->initiator,
            'SecurityCredential' => $this->config->getSecurityCredential(),
            'CommandID' => 'PayTaxToKRA',
            'SenderIdentifierType' => '4',
            'RecieverIdentifierType' => '4',
            'Amount' => $this->amount,
            'PartyA' => $this->config->getBusinessCode(),
            'PartyB' => $this->partyB,
            'AccountReference' => $this->accountReference,
            'Remarks' => $this->remarks ?: 'Tax remittance',
            'QueueTimeOutURL' => $this->config->getQueueTimeoutUrl(),
            'ResultURL' => $this->config->getResultUrl(),
        ];

        $this->response = $this->client->executeRequest($requestData, '/mpesa/b2b/v1/remittax');

        return $this;
    }
}
