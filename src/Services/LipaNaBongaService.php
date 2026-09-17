<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Lipa na Bonga service.
 *
 * Lets customers pay your paybill/till with Safaricom Bonga points.
 * The customer confirms with their M-Pesa PIN; the payment result is sent
 * to your registered C2B URLs (see CustomerToBusinessService::registerUrl()).
 */
class LipaNaBongaService extends AbstractService
{
    /**
     * Ksh value of one Bonga point.
     */
    public const DEFAULT_CONVERSION_RATE = 0.2;

    private string $phoneNumber = '';

    private ?float $amount = null;

    private ?int $points = null;

    private float $conversionRate = self::DEFAULT_CONVERSION_RATE;

    private string $shortCode = '';

    private string $accountNumber = '';

    /**
     * Get the Ksh value of a number of points.
     *
     * @param int $points The Bonga points
     *
     * @return self
     */
    public function calculatePoints(int $points): self
    {
        if ($points < 1) {
            throw new \InvalidArgumentException('Points must be greater than 0');
        }

        $this->response = $this->client->executeRequest(
            ['points' => (string)$points],
            '/v1/lipa/na/bonga/calculate-points'
        );

        return $this;
    }

    /**
     * Set the phone number whose points will be redeemed.
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
     * Set the Ksh amount to pay. If points are not set, they are
     * calculated from the amount and conversion rate.
     *
     * @param int|float|string $amount The amount
     *
     * @return self
     */
    public function setAmount($amount): self
    {
        if (! is_numeric($amount) || $amount <= 0) {
            throw new \InvalidArgumentException('Amount must be a number greater than 0');
        }

        $this->amount = (float)$amount;

        return $this;
    }

    /**
     * Set the Bonga points to deduct. If the amount is not set, it is
     * calculated from the points and conversion rate.
     *
     * @param int $points The points
     *
     * @return self
     */
    public function setPoints(int $points): self
    {
        if ($points < 1) {
            throw new \InvalidArgumentException('Points must be greater than 0');
        }

        $this->points = $points;

        return $this;
    }

    /**
     * Set the Ksh value of one point (default 0.2).
     *
     * @param float $rate The conversion rate
     *
     * @return self
     */
    public function setConversionRate(float $rate): self
    {
        if ($rate <= 0) {
            throw new \InvalidArgumentException('Conversion rate must be greater than 0');
        }

        $this->conversionRate = $rate;

        return $this;
    }

    /**
     * Set the paybill/till receiving the payment.
     * Defaults to the configured business code.
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
     * Set the account number the payment is for.
     *
     * @param string $accountNumber The account number
     *
     * @return self
     */
    public function setAccountNumber(string $accountNumber): self
    {
        $this->accountNumber = $accountNumber;

        return $this;
    }

    /**
     * Redeem the customer's points as payment to the shortcode.
     * The customer gets a prompt to confirm with their M-Pesa PIN.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function redeem(): self
    {
        $shortCode = $this->shortCode ?: $this->config->getBusinessCode();

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        if (null === $this->amount && null === $this->points) {
            throw new \InvalidArgumentException('Amount or points is required');
        }

        if (empty($shortCode)) {
            throw new \InvalidArgumentException('Short code is required');
        }

        if ('' === $this->accountNumber) {
            throw new \InvalidArgumentException('Account number is required');
        }

        // Fill in whichever of amount/points is missing; points round up so the amount is covered
        $points = $this->points ?? (int)ceil(round($this->amount / $this->conversionRate, 6));
        $amount = $this->amount ?? round($this->points * $this->conversionRate, 2);

        $data = [
            'msisdn' => $this->phoneNumber,
            'amount' => $this->toNumber($amount),
            'bongaPoints' => $points,
            'conversionRate' => $this->conversionRate,
            'shortCode' => $shortCode,
            'accountNumber' => $this->accountNumber,
        ];

        $this->response = $this->client->executeRequest($data, '/v1/lipa/na/bonga/redeem-paybill');

        return $this;
    }

    /**
     * Whether the last request succeeded (header.responseCode 200).
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return isset($this->response->header->responseCode)
            && 200 === (int)$this->response->header->responseCode;
    }

    /**
     * Get the message meant for the customer from the last request.
     *
     * @return string|null
     */
    public function getCustomerMessage(): ?string
    {
        return $this->response->header->customerMessage ?? null;
    }

    /**
     * Get the Ksh amount from the last calculatePoints() call.
     *
     * @return float|null
     */
    public function getCalculatedAmount(): ?float
    {
        if (! isset($this->response->body->amount)) {
            return null;
        }

        return (float)$this->response->body->amount;
    }

    /**
     * Send whole amounts as integers, like the API examples.
     *
     * @param float $value The value
     *
     * @return int|float
     */
    private function toNumber(float $value)
    {
        return floor($value) === $value ? (int)$value : $value;
    }
}
