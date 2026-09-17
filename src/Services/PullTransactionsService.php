<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Pull Transactions service.
 *
 * Retrieves C2B transactions for a shortcode from the last 48 hours,
 * e.g. to recover callbacks that never reached your system.
 */
class PullTransactionsService extends AbstractService
{
    private const DATE_FORMAT = 'Y-m-d H:i:s';

    private string $shortCode = '';

    private string $nominatedNumber = '';

    private string $callbackUrl = '';

    private string $startDate = '';

    private string $endDate = '';

    private int $offset = 0;

    /**
     * Set the organization shortcode (paybill/till).
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
     * Set the Safaricom number nominated for the organization
     * (found under the shortcode KYC details on the M-Pesa portal).
     *
     * @param string $phoneNumber The nominated phone number
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setNominatedNumber(string $phoneNumber): self
    {
        $this->nominatedNumber = $this->cleanPhoneNumber($phoneNumber);

        return $this;
    }

    /**
     * Set the callback URL used when registering.
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
     * Set the start of the period to query.
     *
     * @param \DateTimeInterface|string $date A date, or a string like "2020-08-04 08:36:00"
     *
     * @return self
     */
    public function setStartDate($date): self
    {
        $this->startDate = $this->formatDate($date);

        return $this;
    }

    /**
     * Set the end of the period to query.
     *
     * @param \DateTimeInterface|string $date A date, or a string like "2020-08-16 10:10:00"
     *
     * @return self
     */
    public function setEndDate($date): self
    {
        $this->endDate = $this->formatDate($date);

        return $this;
    }

    /**
     * Set the row to start from (0 for the first page).
     *
     * @param int $offset The offset
     *
     * @return self
     */
    public function setOffset(int $offset): self
    {
        if ($offset < 0) {
            throw new \InvalidArgumentException('Offset cannot be negative');
        }

        $this->offset = $offset;

        return $this;
    }

    /**
     * Format a date for the API.
     *
     * @param \DateTimeInterface|string $date The date
     *
     * @return string The formatted date
     */
    private function formatDate($date): string
    {
        return $date instanceof \DateTimeInterface ? $date->format(self::DATE_FORMAT) : trim((string)$date);
    }

    /**
     * Get the shortcode to use, falling back to the configured business code.
     *
     * @return string The shortcode
     */
    private function resolveShortCode(): string
    {
        return $this->shortCode ?: $this->config->getBusinessCode();
    }

    /**
     * Register the shortcode for pulling transactions (one-time setup).
     * ResponseStatus 1000 = registered, 1001 = already registered.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function register(): self
    {
        $shortCode = $this->resolveShortCode();

        if (empty($shortCode)) {
            throw new \InvalidArgumentException('Short code is required');
        }

        if (empty($this->nominatedNumber)) {
            throw new \InvalidArgumentException('Nominated number is required');
        }

        if (empty($this->callbackUrl)) {
            throw new \InvalidArgumentException('Callback URL is required');
        }

        $data = [
            'ShortCode' => $shortCode,
            'RequestType' => 'Pull',
            'NominatedNumber' => $this->nominatedNumber,
            'CallBackURL' => $this->callbackUrl,
        ];

        $this->response = $this->client->executeRequest($data, '/pulltransactions/v1/register');

        return $this;
    }

    /**
     * Query C2B transactions for the configured period.
     * ResponseCode 1000 = transactions found, 1001 = none in the period.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function query(): self
    {
        $shortCode = $this->resolveShortCode();

        if (empty($shortCode)) {
            throw new \InvalidArgumentException('Short code is required');
        }

        if (empty($this->startDate)) {
            throw new \InvalidArgumentException('Start date is required');
        }

        if (empty($this->endDate)) {
            throw new \InvalidArgumentException('End date is required');
        }

        $data = [
            'ShortCode' => $shortCode,
            'StartDate' => $this->startDate,
            'EndDate' => $this->endDate,
            'OffSetValue' => (string)$this->offset,
        ];

        $this->response = $this->client->executeRequest($data, '/pulltransactions/v1/query');

        return $this;
    }

    /**
     * Get the transactions from the last query as a flat list.
     *
     * @return array<int, object> Transactions (transactionId, trxDate, msisdn, sender,
     *                            transactiontype, billreference, amount, organizationname)
     */
    public function getTransactions(): array
    {
        $rows = $this->response->Response ?? [];

        if (! is_array($rows)) {
            return [];
        }

        $transactions = [];
        // The API nests transactions one level deep: [[{...}, {...}]]
        array_walk_recursive($rows, function ($row) use (&$transactions) {
            if (is_object($row)) {
                $transactions[] = $row;
            }
        });

        return $transactions;
    }
}
