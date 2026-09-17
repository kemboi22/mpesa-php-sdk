<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * STK Push service.
 */
class StkService extends BaseService
{
    private string $transactionType = '';

    private string $amount = '';

    private string $phoneNumber = '';

    private string $callbackUrl = '';

    private string $accountReference = '';

    private string $transactionDesc = '';

    private string $partyB = '';

    private ?object $response = null;

    /**
     * Set the transaction type.
     *
     * @param string $type Transaction type (CustomerPayBillOnline|CustomerBuyGoodsOnline)
     *
     * @return self
     */
    public function setTransactionType(string $type): self
    {
        $this->transactionType = $type;

        return $this;
    }

    /**
     * Set the amount.
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
     * Set the phone number.
     *
     * @param string $phoneNumber The phone number
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $this->cleanPhoneNumber($phoneNumber);

        return $this;
    }

    /**
     * Set the account that receives the money.
     *
     * Defaults to the business code. For CustomerBuyGoodsOnline, set this to the
     * till number when it differs from the store number used as the business code.
     *
     * @param string $partyB The paybill or till number
     *
     * @return self
     */
    public function setPartyB(string $partyB): self
    {
        $this->partyB = $partyB;

        return $this;
    }

    /**
     * Set the callback URL.
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
     * Set the account reference.
     *
     * @param string $reference The account reference
     *
     * @return self
     */
    public function setAccountReference(string $reference): self
    {
        $this->accountReference = $reference;

        return $this;
    }

    /**
     * Set the transaction description.
     *
     * @param string $description The transaction description
     *
     * @return self
     */
    public function setTransactionDesc(string $description): self
    {
        $this->transactionDesc = $description;

        return $this;
    }

    /**
     * Validate required parameters before push.
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    private function validatePushParams(): void
    {
        if (empty($this->config->getBusinessCode())) {
            throw new \InvalidArgumentException('Business code is required');
        }

        if (empty($this->transactionType)) {
            throw new \InvalidArgumentException('Transaction type is required');
        }

        if (empty($this->amount)) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if (empty($this->callbackUrl)) {
            throw new \InvalidArgumentException('Callback URL is required');
        }
    }

    /**
     * Initiate an STK push request.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function push(): self
    {
        $this->validatePushParams();

        // The password is tied to the timestamp, so both must use the same value
        $timestamp = $this->generateTimestamp();

        $data = [
            'BusinessShortCode' => $this->config->getBusinessCode(),
            'Password' => $this->generatePassword($timestamp),
            'Timestamp' => $timestamp,
            'TransactionType' => $this->transactionType,
            'Amount' => $this->amount,
            'PartyA' => $this->phoneNumber,
            'PartyB' => $this->partyB ?: $this->config->getBusinessCode(),
            'PhoneNumber' => $this->phoneNumber,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $this->accountReference ?: 'Account',
            'TransactionDesc' => $this->transactionDesc ?: 'Transaction',
        ];

        $this->response = $this->client->executeRequest($data, '/mpesa/stkpush/v1/processrequest');

        return $this;
    }

    /**
     * Get the checkout request ID.
     *
     * @return string The checkout request ID
     *
     * @throws \RuntimeException If no response is available
     */
    public function getCheckoutRequestId(): string
    {
        if (! $this->response || ! isset($this->response->CheckoutRequestID)) {
            throw new \RuntimeException('No STK push response available');
        }

        return $this->response->CheckoutRequestID;
    }

    /**
     * Query the status of an STK push transaction.
     *
     * @param string|null $checkoutRequestId Optional checkout request ID
     *
     * @return self Read the result with getResponse()
     *
     * @throws \RuntimeException If no checkout request ID is available
     */
    public function query(?string $checkoutRequestId = null): self
    {
        $requestId = $checkoutRequestId ?? $this->getCheckoutRequestId();
        $timestamp = $this->generateTimestamp();

        $data = [
            'BusinessShortCode' => $this->config->getBusinessCode(),
            'Password' => $this->generatePassword($timestamp),
            'Timestamp' => $timestamp,
            'CheckoutRequestID' => $requestId,
        ];

        $this->response = $this->client->executeRequest($data, '/mpesa/stkpushquery/v1/query');

        return $this;
    }

    /**
     * Get the response.
     *
     * @return object|null The response
     */
    public function getResponse(): ?object
    {
        return $this->response;
    }
}
