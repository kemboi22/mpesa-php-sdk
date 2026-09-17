<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Mobile Number Validation (KYC) service.
 *
 * Checks whether a Safaricom number is registered under a given ID.
 * This API is synchronous, commercial (charged per call) and requires
 * onboarding by Safaricom.
 */
class MobileNumberValidationService extends AbstractService
{
    public const ID_NATIONAL = '01';

    public const ID_MILITARY = '02';

    public const ID_PASSPORT = '05';

    public const ID_TYPES = [self::ID_NATIONAL, self::ID_MILITARY, self::ID_PASSPORT];

    private string $requestRefId = '';

    private string $sentRequestRefId = '';

    private string $shortCode = '';

    private string $phoneNumber = '';

    private string $idType = '';

    private string $idNumber = '';

    /**
     * Set a unique identifier for the next request.
     * When not set, a new UUID v4 is generated for every validation.
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
     * Get the request reference ID sent with the last validation.
     *
     * @return string The request reference ID
     */
    public function getRequestRefId(): string
    {
        return $this->sentRequestRefId;
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
     * Set the Safaricom phone number to validate.
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
     * Set the ID type.
     *
     * @param string $idType 01 (National ID), 02 (Military ID) or 05 (Passport);
     *                       use the ID_* constants
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the ID type is not supported
     */
    public function setIdType(string $idType): self
    {
        if (! in_array($idType, self::ID_TYPES, true)) {
            throw new \InvalidArgumentException(
                'ID type must be one of: ' . implode(', ', self::ID_TYPES)
            );
        }

        $this->idType = $idType;

        return $this;
    }

    /**
     * Set the customer's ID number.
     *
     * @param string $idNumber The ID number
     *
     * @return self
     */
    public function setIdNumber(string $idNumber): self
    {
        $this->idNumber = trim($idNumber);

        return $this;
    }

    /**
     * Check whether the phone number is registered under the ID.
     * Each call is billed by Safaricom.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     * @throws \RuntimeException         If the API returns an HTTP error
     */
    public function validate(): self
    {
        $shortCode = $this->shortCode ?: $this->config->getBusinessCode();

        if (empty($shortCode)) {
            throw new \InvalidArgumentException('Short code is required');
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if (empty($this->idType)) {
            throw new \InvalidArgumentException('ID type is required');
        }

        if ('' === $this->idNumber) {
            throw new \InvalidArgumentException('ID number is required');
        }

        $this->sentRequestRefId = $this->requestRefId ?: $this->generateUuid();

        $data = [
            'requestRefID' => $this->sentRequestRefId,
            'shortCode' => $shortCode,
            'msisdn' => $this->phoneNumber,
            'idType' => $this->idType,
            'idNumber' => $this->idNumber,
        ];

        $this->response = $this->client->executeRequest($data, '/v1/KYC-validation/validateID');

        return $this;
    }

    /**
     * Whether the phone number matched the ID in the last validation.
     * responseCode 4000 = match, 4001 = no match.
     *
     * @return bool
     */
    public function isMatch(): bool
    {
        if (! $this->response) {
            return false;
        }

        $status = $this->response->status ?? null;

        return true === $status || 'true' === strtolower((string)$status);
    }
}
