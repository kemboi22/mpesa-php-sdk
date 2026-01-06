<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Customer to Business service.
 */
class CustomerToBusinessService extends BaseService
{
    private string $confirmationUrl = '';

    private string $validationUrl = '';

    private string $responseType = '';

    private string $commandId = '';

    private string $billRefNumber = '';

    private ?object $response = null;

    private string $amount;

    private string $phoneNumber;

    /**
     * Set the confirmation URL.
     *
     * @param string $url The confirmation URL
     *
     * @return self
     */
    public function setConfirmationUrl(string $url): self
    {
        $this->confirmationUrl = $url;

        return $this;
    }

    /**
     * Set the validation URL.
     *
     * @param string $url The validation URL
     *
     * @return self
     */
    public function setValidationUrl(string $url): self
    {
        $this->validationUrl = $url;

        return $this;
    }

    /**
     * Set the response type.
     *
     * @param string $type The response type
     *
     * @return self
     */
    public function setResponseType(string $type): self
    {
        $this->responseType = $type;

        return $this;
    }

    /**
     * Set the command ID.
     *
     * @param string $commandId The command ID
     *
     * @return self
     */
    public function setCommandId(string $commandId): self
    {
        $this->commandId = $commandId;

        return $this;
    }

    /**
     * Set the bill reference number.
     *
     * @param string $refNumber The bill reference number
     *
     * @return self
     */
    public function setBillRefNumber(string $refNumber): self
    {
        $this->billRefNumber = $refNumber;

        return $this;
    }

    /**
     * Register C2B URLs.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function registerUrl(): self
    {
        if (empty($this->config->getBusinessCode())) {
            throw new \InvalidArgumentException('Business code is required');
        }

        if (empty($this->confirmationUrl)) {
            throw new \InvalidArgumentException('Confirmation URL is required');
        }

        if (empty($this->validationUrl)) {
            throw new \InvalidArgumentException('Validation URL is required');
        }

        $data = [
            'ShortCode' => $this->config->getBusinessCode(),
            'ResponseType' => $this->responseType,
            'ConfirmationURL' => $this->confirmationUrl,
            'ValidationURL' => $this->validationUrl,
        ];

        $this->response = $this->client->executeRequest($data, '/mpesa/c2b/v2/registerurl');

        return $this;
    }

    /**
     * Simulate a C2B transaction.
     *
     * @param string|null $phoneNumber Optional phone number
     * @param string|null $amount      Optional amount
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function simulate(?string $phoneNumber = null, ?string $amount = null): self
    {
        if ($phoneNumber !== null) {
            $this->setPhoneNumber($phoneNumber);
        }

        if ($amount !== null) {
            $this->setAmount($amount);
        }

        if (empty($this->config->getBusinessCode())) {
            throw new \InvalidArgumentException('Business code is required');
        }

        if (empty($this->commandId)) {
            throw new \InvalidArgumentException('Command ID is required');
        }

        if (empty($this->amount)) {
            throw new \InvalidArgumentException('Amount is required');
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        $data = [
            'ShortCode' => $this->config->getBusinessCode(),
            'CommandID' => $this->commandId,
            'Amount' => $this->amount,
            'Msisdn' => $this->phoneNumber,
            'BillRefNumber' => $this->billRefNumber,
        ];

        $this->response = $this->client->executeRequest($data, '/mpesa/c2b/v1/simulate');

        return $this;
    }

    /**
     * Set the phone number.
     *
     * @param string $phoneNumber The phone number
     *
     * @return self
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Set the amount.
     *
     * @param int|string $amount The amount
     *
     * @return self
     */
    public function setAmount(int|string $amount): self
    {
        $this->amount = (string)$amount;

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
