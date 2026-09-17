<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * B2C Hakikisha service.
 *
 * Looks up a customer's (masked) registered name by phone number,
 * so you can confirm the recipient before sending a B2C payment.
 * This API is synchronous and requires approval from Safaricom.
 */
class B2CHakikishaService extends AbstractService
{
    private string $phoneNumber = '';

    private string $shortCode = '';

    private string $requestId = '';

    private string $sentRequestId = '';

    /**
     * Set the Safaricom phone number to look up.
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
     * Set the organization shortcode.
     * Defaults to the configured business code when not set.
     *
     * @param string $shortCode The shortcode
     *
     * @return self
     */
    public function setShortCode(string $shortCode): self
    {
        $this->shortCode = $shortCode;

        return $this;
    }

    /**
     * Set a unique identifier for the next request.
     * When not set, a new UUID v4 is generated for every lookup.
     *
     * @param string $requestId The request ID
     *
     * @return self
     */
    public function setRequestId(string $requestId): self
    {
        $this->requestId = $requestId;

        return $this;
    }

    /**
     * Get the request ID sent with the last lookup.
     *
     * @return string The request ID
     */
    public function getRequestId(): string
    {
        return $this->sentRequestId;
    }

    /**
     * Look up the customer registered to the phone number.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \RuntimeException         If the API returns an HTTP error (e.g. unknown customer)
     */
    public function lookup(): self
    {
        $shortCode = $this->shortCode ?: $this->config->getBusinessCode();

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if (empty($shortCode)) {
            throw new \InvalidArgumentException('Short code is required');
        }

        $this->sentRequestId = $this->requestId ?: $this->generateUuid();

        $data = [
            'header' => [
                'requestID' => $this->sentRequestId,
                'timestamp' => (string)time(),
            ],
            'body' => [
                'msisdn' => $this->phoneNumber,
                'shortcode' => $shortCode,
            ],
        ];

        $this->response = $this->client->executeRequest($data, '/mpesa/b2c/hakikisha/v1/hakikisha');

        return $this;
    }

    /**
     * Whether the last lookup found the customer.
     *
     * @return bool
     */
    public function isFound(): bool
    {
        return isset($this->response->header->status)
            && '200' === (string)$this->response->header->status
            && isset($this->response->body->firstName);
    }

    /**
     * Get the customer's name parts from the last lookup.
     * Middle and last names are masked, e.g. "M******".
     *
     * @return array{firstName: string, middleName: string, lastName: string}|null
     *                                                                            Null if the customer was not found
     */
    public function getCustomer(): ?array
    {
        if (! $this->isFound()) {
            return null;
        }

        $body = $this->response->body;

        return [
            'firstName' => (string)($body->firstName ?? ''),
            'middleName' => (string)($body->middleName ?? ''),
            'lastName' => (string)($body->lastName ?? ''),
        ];
    }

    /**
     * Get the customer's name as a single string, e.g. "john M****** M******".
     *
     * @return string|null Null if the customer was not found
     */
    public function getCustomerName(): ?string
    {
        $customer = $this->getCustomer();

        return null === $customer ? null : implode(' ', array_filter($customer, 'strlen'));
    }

    /**
     * Get the error message from the last lookup, if any.
     *
     * @return string|null
     */
    public function getErrorMessage(): ?string
    {
        if (! $this->response || $this->isFound()) {
            return null;
        }

        return $this->response->body->message
            ?? $this->response->header->message
            ?? 'Customer lookup failed';
    }
}
