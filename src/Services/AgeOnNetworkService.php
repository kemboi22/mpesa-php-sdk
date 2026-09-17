<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Age on Network service.
 *
 * Returns the date a Safaricom number was registered on the network.
 * This is a commercial API (billed per successful call) that requires
 * onboarding by Safaricom.
 */
class AgeOnNetworkService extends AbstractService
{
    public const DEFAULT_ENDPOINT = '/registration/v1/checkATI';

    private string $phoneNumber = '';

    private string $endpoint = self::DEFAULT_ENDPOINT;

    /**
     * Set the phone number to check.
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
     * Override the API path. Safaricom's docs list both
     * "/registration/v1/checkATI" and "/registration/lookup/v1/checkATI".
     *
     * @param string $endpoint The API path
     *
     * @return self
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = '/' . ltrim($endpoint, '/');

        return $this;
    }

    /**
     * Look up when the phone number was registered. Each call is billed.
     *
     * @param string|null $phoneNumber Optional phone number (otherwise use setPhoneNumber())
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the phone number is missing
     * @throws \RuntimeException         If the API returns an HTTP error
     */
    public function check(?string $phoneNumber = null): self
    {
        if (null !== $phoneNumber) {
            $this->setPhoneNumber($phoneNumber);
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        $this->response = $this->client->executeRequest(
            ['customerNumber' => $this->phoneNumber],
            $this->endpoint
        );

        return $this;
    }

    /**
     * Whether the last check succeeded (responseCode 200).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return isset($this->response->responseCode)
            && 200 === (int)$this->response->responseCode;
    }

    /**
     * Get the registration date exactly as the API returned it.
     * This may be a date or a message saying the number is over a year old.
     *
     * @return string|null
     */
    public function getRegistrationDate(): ?string
    {
        if (! isset($this->response->msisdnRegistrationDate)) {
            return null;
        }

        return (string)$this->response->msisdnRegistrationDate;
    }

    /**
     * Get the registration date as a date object, when the API returned a date.
     * Accepts both "dd-mm-yyyy" and "yyyy-mm-dd".
     *
     * @return \DateTimeImmutable|null Null if there is no date or it is a message
     */
    public function getRegistrationDateTime(): ?\DateTimeImmutable
    {
        $value = trim((string)$this->getRegistrationDate());

        foreach (['!d-m-Y', '!Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if (false !== $date && $date->format(substr($format, 1)) === $value) {
                return $date;
            }
        }

        return null;
    }
}
