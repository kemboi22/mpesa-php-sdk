<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Account Balance service.
 */
class AccountBalanceService extends AbstractService
{
    private string $initiator = "";

    private string $identifier_type = "4";

    private string $remarks = "";

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
     * Sets the identifier type for the account balance request.
     * Defaults to "4" (organization short code).
     *
     * @param string $identifier_type The type of identifier to associate with the request.
     *
     * @return $this
     */
    public function setIdentifierType(string $identifier_type): self
    {
        $this->identifier_type = $identifier_type;

        return $this;
    }

    /**
     * Sets any additional information to be associated with the transaction.
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
     * Sets the URL that receives the balance result.
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
     * Initiates an account balance request to the M-Pesa API.
     *
     * @param string|null $initiator          The username of the M-Pesa API operator.
     * @param string|null $initiator_password The password to authenticate the initiator.
     * @param string|null $partyA             The shortcode to receive the transaction.
     * @param string|null $identifier_type    The type of organization receiving the transaction.
     * @param string|null $remarks            Any additional information to be associated with the transaction.
     * @param string|null $queue_url          The URL to receive timeout notifications.
     * @param string|null $result_url         The URL to receive the response from the M-Pesa API.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function accountBalance(
        ?string $initiator = null,
        ?string $initiator_password = null,
        ?string $partyA = null,
        ?string $identifier_type = null,
        ?string $remarks = null,
        ?string $queue_url = null,
        ?string $result_url = null
    ): self {
        if ($initiator !== null) {
            $this->setInitiator($initiator);
        }
        if ($remarks !== null) {
            $this->setRemarks($remarks);
        }
        if ($partyA !== null) {
            $this->config->setBusinessCode($partyA);
        }
        if ($identifier_type !== null) {
            $this->setIdentifierType($identifier_type);
        }
        if ($queue_url !== null) {
            $this->config->setQueueTimeoutUrl($queue_url);
        }
        if ($result_url !== null) {
            $this->config->setResultUrl($result_url);
        }
        if ($initiator_password !== null) {
            $this->config->setSecurityCredential($initiator_password);
        }

        $this->validateParams();

        $requestData = [
            "Initiator" => $this->initiator,
            "SecurityCredential" => $this->config->getSecurityCredential(),
            "CommandID" => "AccountBalance",
            "PartyA" => $this->config->getBusinessCode(),
            "IdentifierType" => $this->identifier_type,
            "Remarks" => $this->remarks,
            "QueueTimeOutURL" => $this->config->getQueueTimeoutUrl(),
            "ResultURL" => $this->config->getResultUrl(),
        ];

        $this->response = $this->client->executeRequest($requestData, "/mpesa/accountbalance/v1/query");

        return $this;
    }

    /**
     * Validate required parameters before querying.
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

        if (empty($this->config->getQueueTimeoutUrl())) {
            throw new \InvalidArgumentException('Queue timeout URL is required');
        }

        if (empty($this->config->getResultUrl())) {
            throw new \InvalidArgumentException('Result URL is required');
        }
    }

    /**
     * Parse the balances out of an account balance result callback.
     *
     * The AccountBalance result parameter looks like
     * "Working Account|KES|700000.00|700000.00|0.00|0.00&Utility Account|KES|...".
     * Each account is "Name|Currency|Current|Available|Reserved|Uncleared".
     *
     * @param array|object|string $callback The decoded callback body, or its raw JSON
     *
     * @return array<string, array{currency: string, current: float, available: float, reserved: float, uncleared: float}>
     *               Balances keyed by account name; empty if the result has no balance
     */
    public static function parseBalances($callback): array
    {
        if (is_string($callback)) {
            $callback = json_decode($callback, true);
        } elseif (is_object($callback)) {
            $callback = json_decode(json_encode($callback), true);
        }

        $parameters = $callback['Result']['ResultParameters']['ResultParameter'] ?? [];

        // A single parameter may be sent as an object instead of a list
        if (isset($parameters['Key'])) {
            $parameters = [$parameters];
        }

        $raw = null;
        foreach ($parameters as $parameter) {
            if (($parameter['Key'] ?? null) === 'AccountBalance') {
                $raw = (string)($parameter['Value'] ?? '');
                break;
            }
        }

        if (empty($raw)) {
            return [];
        }

        $balances = [];
        foreach (explode('&', $raw) as $account) {
            $fields = explode('|', $account);
            if (count($fields) < 3) {
                continue;
            }

            $balances[trim($fields[0])] = [
                'currency' => $fields[1],
                'current' => (float)($fields[2] ?? 0),
                'available' => (float)($fields[3] ?? 0),
                'reserved' => (float)($fields[4] ?? 0),
                'uncleared' => (float)($fields[5] ?? 0),
            ];
        }

        return $balances;
    }
}
