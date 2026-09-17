<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * B2B Express Checkout (USSD Push to Till) service.
 *
 * Lets a vendor send an M-Pesa USSD prompt to a merchant's till so the
 * merchant can pay from their till into the vendor's paybill.
 */
class B2BExpressCheckoutService extends AbstractService
{
    private string $primaryShortCode = '';

    private string $receiverShortCode = '';

    private string $amount = '';

    private string $paymentRef = '';

    private string $callbackUrl = '';

    private string $partnerName = '';

    private string $requestRefId = '';

    /**
     * Set the debit party: the merchant's till number paying the vendor.
     *
     * @param string $shortCode The merchant till/short code
     *
     * @return self
     */
    public function setPrimaryShortCode(string $shortCode): self
    {
        $this->primaryShortCode = $shortCode;

        return $this;
    }

    /**
     * Set the credit party: the vendor paybill receiving the payment.
     * Defaults to the configured business code when not set.
     *
     * @param string $shortCode The vendor paybill short code
     *
     * @return self
     */
    public function setReceiverShortCode(string $shortCode): self
    {
        $this->receiverShortCode = $shortCode;

        return $this;
    }

    /**
     * Set the amount to be paid to the vendor.
     *
     * @param int|string $amount The amount
     *
     * @return self
     */
    public function setAmount($amount): self
    {
        $this->amount = (string)$amount;

        return $this;
    }

    /**
     * Set the payment reference shown to the merchant in the prompt.
     *
     * @param string $paymentRef The payment reference
     *
     * @return self
     */
    public function setPaymentRef(string $paymentRef): self
    {
        $this->paymentRef = $paymentRef;

        return $this;
    }

    /**
     * Set the URL that receives the transaction result callback.
     *
     * @param string $url The callback URL
     *
     * @return self
     */
    public function setCallbackUrl(string $url): self
    {
        $this->callbackUrl = $url;

        return $this;
    }

    /**
     * Set the vendor's friendly name as known by the merchant.
     *
     * @param string $partnerName The partner name
     *
     * @return self
     */
    public function setPartnerName(string $partnerName): self
    {
        $this->partnerName = $partnerName;

        return $this;
    }

    /**
     * Set a unique identifier for this request.
     * A UUID v4 is generated automatically when not set.
     *
     * @param string $requestRefId The request reference ID
     *
     * @return self
     */
    public function setRequestRefId(string $requestRefId): self
    {
        $this->requestRefId = $requestRefId;

        return $this;
    }

    /**
     * Get the request reference ID sent with the last push.
     * Use it to match the callback (requestId) to this request.
     *
     * @return string The request reference ID
     */
    public function getRequestRefId(): string
    {
        return $this->requestRefId;
    }

    /**
     * Validate required parameters before push.
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    private function validatePushParams(): void
    {
        if (empty($this->primaryShortCode)) {
            throw new \InvalidArgumentException('Primary short code is required');
        }

        if (empty($this->receiverShortCode)) {
            throw new \InvalidArgumentException('Receiver short code is required');
        }

        if (empty($this->amount)) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if (empty($this->paymentRef)) {
            throw new \InvalidArgumentException('Payment reference is required');
        }

        if (empty($this->callbackUrl)) {
            throw new \InvalidArgumentException('Callback URL is required');
        }

        if (empty($this->partnerName)) {
            throw new \InvalidArgumentException('Partner name is required');
        }
    }

    /**
     * Initiate a B2B Express Checkout USSD push to the merchant's till.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function push(): self
    {
        if (empty($this->receiverShortCode)) {
            $this->receiverShortCode = $this->config->getBusinessCode();
        }

        if (empty($this->requestRefId)) {
            $this->requestRefId = $this->generateUuid();
        }

        $this->validatePushParams();

        $data = [
            'primaryShortCode' => $this->primaryShortCode,
            'receiverShortCode' => $this->receiverShortCode,
            'amount' => $this->amount,
            'paymentRef' => $this->paymentRef,
            'callbackUrl' => $this->callbackUrl,
            'partnerName' => $this->partnerName,
            'RequestRefID' => $this->requestRefId,
        ];

        $this->response = $this->client->executeRequest($data, '/v1/ussdpush/get-msisdn');

        return $this;
    }
}
