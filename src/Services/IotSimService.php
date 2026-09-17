<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * IoT SIM Management service.
 *
 * Manages Safaricom IoT SIM cards (activation, suspension, status, naming,
 * activation trends) and the messages sent to them.
 *
 * Set the account once with setVpnGroup() and setUsername(), then call the
 * operation methods. Each operation stores the response for getResponse().
 */
class IotSimService extends AbstractService
{
    public const OPERATION_SUSPEND = 'suspend';

    public const OPERATION_RESUME = 'resume';

    private string $vpnGroup = '';

    private string $username = '';

    /**
     * Set the customer account number, e.g. "1-225560081663_VPN".
     *
     * @param string $vpnGroup The account number
     *
     * @return self
     */
    public function setVpnGroup(string $vpnGroup): self
    {
        $this->vpnGroup = trim($vpnGroup);

        return $this;
    }

    /**
     * Set the username (email) of a user registered under the account.
     *
     * @param string $username The username
     *
     * @return self
     */
    public function setUsername(string $username): self
    {
        $this->username = trim($username);

        return $this;
    }

    // ---------------------------------------------------------------------
    // SIM operations
    // ---------------------------------------------------------------------

    /**
     * Fetch the SIMs in the account, a page at a time.
     *
     * @param int $startAtIndex Index to start from (0 for the first page)
     * @param int $pageSize     Number of SIMs to return
     *
     * @return self
     */
    public function getAllSims(int $startAtIndex = 0, int $pageSize = 10): self
    {
        $this->requireAccount(true);

        if ($startAtIndex < 0) {
            throw new \InvalidArgumentException('Start index cannot be negative');
        }

        if ($pageSize < 1) {
            throw new \InvalidArgumentException('Page size must be greater than 0');
        }

        return $this->send('/simportal/v1/allsims', [
            'vpnGroup' => [$this->vpnGroup],
            'startAtIndex' => (string)$startAtIndex,
            'pageSize' => (string)$pageSize,
            'username' => $this->username,
        ]);
    }

    /**
     * Get the SIMs from the last getAllSims() call.
     *
     * @return array<int, object> SIMs (msisdn, iccid, imsi, asset_name, life_cycle_status, ...)
     */
    public function getSims(): array
    {
        $sims = $this->response->body->Desc ?? [];

        return is_array($sims) ? $sims : [];
    }

    /**
     * Check a SIM's status on the network.
     *
     * @param string $msisdn The SIM's IoT subscriber number
     *
     * @return self
     */
    public function queryLifeCycleStatus(string $msisdn): self
    {
        return $this->sendSimRequest('/simportal/v1/queryLifeCycleStatus', $msisdn);
    }

    /**
     * Check a SIM's status together with its product (tariff) details.
     *
     * @param string $msisdn The SIM's IoT subscriber number
     *
     * @return self
     */
    public function queryCustomerInfo(string $msisdn): self
    {
        return $this->sendSimRequest('/simportal/v1/querycustomerinfo', $msisdn);
    }

    /**
     * Activate a SIM.
     *
     * @param string $msisdn The SIM's IoT subscriber number
     *
     * @return self
     */
    public function activateSim(string $msisdn): self
    {
        return $this->sendSimRequest('/simportal/v1/simactivation', $msisdn);
    }

    /**
     * Give a SIM (asset) a name.
     *
     * @param string $msisdn    The SIM's IoT subscriber number
     * @param string $assetName The name to assign, e.g. "Tracker001"
     *
     * @return self
     */
    public function renameAsset(string $msisdn, string $assetName): self
    {
        if ('' === trim($assetName)) {
            throw new \InvalidArgumentException('Asset name is required');
        }

        return $this->sendSimRequest('/simportal/v1/renameasset', $msisdn, [
            'assetName' => $assetName,
        ]);
    }

    /**
     * Suspend a SIM.
     *
     * @param string $msisdn    The SIM's IoT subscriber number
     * @param string $productId The ID of the SIM's tariff (offeringId from queryCustomerInfo())
     *
     * @return self
     */
    public function suspendSim(string $msisdn, string $productId): self
    {
        return $this->suspendOrResume($msisdn, $productId, self::OPERATION_SUSPEND);
    }

    /**
     * Resume (unsuspend) a SIM.
     *
     * @param string $msisdn    The SIM's IoT subscriber number
     * @param string $productId The ID of the SIM's tariff (offeringId from queryCustomerInfo())
     *
     * @return self
     */
    public function resumeSim(string $msisdn, string $productId): self
    {
        return $this->suspendOrResume($msisdn, $productId, self::OPERATION_RESUME);
    }

    /**
     * Get the daily trends of SIM status changes in the account.
     *
     * @param \DateTimeInterface|string $startDate A date, or a string like "20240221"
     * @param \DateTimeInterface|string $endDate   A date, or a string like "20240421"
     *
     * @return self
     */
    public function getActivationTrends($startDate, $endDate): self
    {
        $this->requireAccount(true);

        return $this->send('/simportal/v1/getactivationtrends', [
            'vpnGroup' => $this->vpnGroup,
            'startDate' => $this->formatDate($startDate, 'Ymd'),
            'stopDate' => $this->formatDate($endDate, 'Ymd'),
            'username' => $this->username,
        ]);
    }

    // ---------------------------------------------------------------------
    // Messaging
    // ---------------------------------------------------------------------

    /**
     * Send a message to a SIM.
     *
     * @param string $msisdn  The SIM's IoT subscriber number
     * @param string $message The message
     *
     * @return self
     */
    public function sendMessage(string $msisdn, string $message): self
    {
        $this->requireAccount(false);

        if ('' === $message) {
            throw new \InvalidArgumentException('Message is required');
        }

        return $this->send('/simportal/v1/sendsinglemessage', [
            'msisdn' => $this->requireMsisdn($msisdn),
            'message' => $message,
            'vpnGroup' => $this->vpnGroup,
        ]);
    }

    /**
     * Search the messages sent to a SIM.
     *
     * @param string $msisdn   The SIM's number ("254" is added if missing)
     * @param int    $pageNo   Page number, starting at 1
     * @param int    $pageSize Messages per page
     *
     * @return self
     */
    public function searchMessages(string $msisdn, int $pageNo = 1, int $pageSize = 50): self
    {
        return $this->send(
            '/simportal/v1/searchmessages' . $this->pageQuery($pageNo, $pageSize),
            ['searchValue' => $this->withCountryCode($msisdn)]
        );
    }

    /**
     * Fetch messages with a given processing status sent within a date range.
     *
     * @param \DateTimeInterface|string $startDate A date, or a string like "02-05-2024 08:39:11"
     * @param \DateTimeInterface|string $endDate   A date, or a string like "02-10-2024 00:00:00"
     * @param string                    $status    Processing status code, e.g. "1"
     * @param int                       $pageNo    Page number, starting at 1
     * @param int                       $pageSize  Messages per page
     *
     * @return self
     */
    public function filterMessages($startDate, $endDate, string $status, int $pageNo = 1, int $pageSize = 10): self
    {
        if ('' === $status) {
            throw new \InvalidArgumentException('Status is required');
        }

        return $this->send(
            '/simportal/v1/filtermessages' . $this->pageQuery($pageNo, $pageSize),
            [
                'startDate' => $this->formatDate($startDate, 'd-m-Y H:i:s'),
                'endDate' => $this->formatDate($endDate, 'd-m-Y H:i:s'),
                'status' => $status,
            ]
        );
    }

    /**
     * Fetch the messages sent to all SIMs in the account.
     *
     * @param int $pageNo   Page number, starting at 1
     * @param int $pageSize Messages per page
     *
     * @return self
     */
    public function getAllMessages(int $pageNo = 1, int $pageSize = 10): self
    {
        $this->requireAccount(false);

        return $this->send(
            '/simportal/v1/getallmessages' . $this->pageQuery($pageNo, $pageSize),
            [
                'vpnGroup' => $this->vpnGroup,
                'pageNo' => $pageNo,
                'pageSize' => $pageSize,
            ]
        );
    }

    /**
     * Get the messages from the last searchMessages(), filterMessages()
     * or getAllMessages() call.
     *
     * @return array<int, object> Messages (id, msisdn, message, processingStatus, date, ...)
     */
    public function getMessages(): array
    {
        $messages = $this->response->body->content ?? [];

        return is_array($messages) ? $messages : [];
    }

    /**
     * Delete a message.
     *
     * @param int|string $messageId The message's id (from getMessages())
     *
     * @return self
     */
    public function deleteMessage($messageId): self
    {
        if ('' === (string)$messageId) {
            throw new \InvalidArgumentException('Message ID is required');
        }

        return $this->send('/simportal/v1/deletemessage', [
            'id' => is_numeric($messageId) ? (int)$messageId : $messageId,
        ]);
    }

    /**
     * Delete all messages sent to a SIM.
     *
     * @param string $msisdn The SIM's number ("254" is added if missing)
     *
     * @return self
     */
    public function deleteMessageThread(string $msisdn): self
    {
        return $this->send('/simportal/v1/deleteMessageThread', [
            'msisdn' => $this->withCountryCode($msisdn),
        ]);
    }

    // ---------------------------------------------------------------------
    // Response helpers
    // ---------------------------------------------------------------------

    /**
     * Whether the last operation succeeded (header.responseCode 200).
     *
     * Some failures (e.g. a SIM that isn't in the account) also come back
     * with 200, so check getBody() for the operation's own result too.
     *
     * @return bool
     */
    public function isSuccessful(): bool
    {
        return isset($this->response->header->responseCode)
            && 200 === (int)$this->response->header->responseCode;
    }

    /**
     * Get the body of the last response.
     *
     * @return mixed
     */
    public function getBody()
    {
        return $this->response->body ?? null;
    }

    // ---------------------------------------------------------------------
    // Internals
    // ---------------------------------------------------------------------

    /**
     * Send a request for a single SIM (msisdn + vpnGroup + username).
     *
     * @param string               $endpoint The API endpoint
     * @param string               $msisdn   The SIM's IoT subscriber number
     * @param array<string, mixed> $extra    Additional fields
     *
     * @return self
     */
    private function sendSimRequest(string $endpoint, string $msisdn, array $extra = []): self
    {
        $this->requireAccount(true);

        return $this->send($endpoint, array_merge([
            'msisdn' => $this->requireMsisdn($msisdn),
            'vpnGroup' => $this->vpnGroup,
            'username' => $this->username,
        ], $extra));
    }

    /**
     * Suspend or resume a SIM.
     *
     * @param string $msisdn    The SIM's IoT subscriber number
     * @param string $productId The tariff ID
     * @param string $operation OPERATION_SUSPEND or OPERATION_RESUME
     *
     * @return self
     */
    private function suspendOrResume(string $msisdn, string $productId, string $operation): self
    {
        if ('' === trim($productId)) {
            throw new \InvalidArgumentException('Product ID is required');
        }

        return $this->sendSimRequest('/simportal/v1/suspend_unsuspend_sub', $msisdn, [
            'product' => trim($productId),
            'operation' => $operation,
        ]);
    }

    /**
     * Send a POST request and store the response.
     *
     * @param string               $endpoint The API endpoint
     * @param array<string, mixed> $data     The request payload
     *
     * @return self
     */
    private function send(string $endpoint, array $data): self
    {
        $this->response = $this->client->executeRequest($data, $endpoint);

        return $this;
    }

    /**
     * Ensure the account details needed by an operation are set.
     *
     * @param bool $needsUsername Whether the operation also needs the username
     *
     * @throws \InvalidArgumentException If a value is missing
     */
    private function requireAccount(bool $needsUsername): void
    {
        if ('' === $this->vpnGroup) {
            throw new \InvalidArgumentException('VPN group (account number) is required');
        }

        if ($needsUsername && '' === $this->username) {
            throw new \InvalidArgumentException('Username is required');
        }
    }

    /**
     * Ensure a SIM number was given.
     *
     * @param string $msisdn The SIM's number
     *
     * @return string The trimmed number
     *
     * @throws \InvalidArgumentException If the number is empty
     */
    private function requireMsisdn(string $msisdn): string
    {
        $msisdn = trim($msisdn);

        if ('' === $msisdn) {
            throw new \InvalidArgumentException('MSISDN is required');
        }

        return $msisdn;
    }

    /**
     * Prefix a SIM number with 254, as the messaging endpoints require.
     *
     * @param string $msisdn The SIM's number
     *
     * @return string The number starting with 254
     */
    private function withCountryCode(string $msisdn): string
    {
        $msisdn = ltrim($this->requireMsisdn($msisdn), '+');

        if (str_starts_with($msisdn, '254')) {
            return $msisdn;
        }

        return '254' . ltrim($msisdn, '0');
    }

    /**
     * Build the pagination query string.
     *
     * @param int $pageNo   Page number, starting at 1
     * @param int $pageSize Items per page
     *
     * @return string The query string, including "?"
     */
    private function pageQuery(int $pageNo, int $pageSize): string
    {
        if ($pageNo < 1) {
            throw new \InvalidArgumentException('Page number must be 1 or greater');
        }

        if ($pageSize < 1) {
            throw new \InvalidArgumentException('Page size must be greater than 0');
        }

        return '?' . http_build_query(['pageNo' => $pageNo, 'pageSize' => $pageSize]);
    }

    /**
     * Format a date for the API.
     *
     * @param \DateTimeInterface|string $date   The date
     * @param string                    $format The format for DateTimeInterface values
     *
     * @return string The formatted date
     *
     * @throws \InvalidArgumentException If the date is empty
     */
    private function formatDate($date, string $format): string
    {
        $value = $date instanceof \DateTimeInterface ? $date->format($format) : trim((string)$date);

        if ('' === $value) {
            throw new \InvalidArgumentException('Date is required');
        }

        return $value;
    }
}
