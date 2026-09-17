<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * HTTP client for M-Pesa API.
 */
class ApiClient implements MpesaInterface, SupportsGetRequests
{
    private MpesaConfig $config;

    private TokenManager $tokenManager;

    public function __construct(MpesaConfig $config)
    {
        $this->config = $config;
        $this->tokenManager = new TokenManager($config);
    }

    /**
     * Execute a POST request to the M-Pesa API.
     *
     * @param array  $data     The request payload
     * @param string $endpoint The API endpoint
     *
     * @return object The API response
     *
     * @throws \RuntimeException If the request fails
     */
    public function executeRequest(array $data, string $endpoint): object
    {
        return $this->send('POST', $endpoint, $data);
    }

    /**
     * Execute a GET request to the M-Pesa API.
     *
     * @param string               $endpoint The API endpoint
     * @param array<string, mixed> $query    Query string parameters
     *
     * @return object The API response
     *
     * @throws \RuntimeException If the request fails
     */
    public function executeGetRequest(string $endpoint, array $query = []): object
    {
        if (! empty($query)) {
            $endpoint .= (str_contains($endpoint, '?') ? '&' : '?') . http_build_query($query);
        }

        return $this->send('GET', $endpoint);
    }

    /**
     * Send a request, refreshing the token and retrying once on 401.
     *
     * @param string     $method   HTTP method (GET or POST)
     * @param string     $endpoint The API endpoint
     * @param array|null $data     The JSON payload for POST requests
     * @param bool       $isRetry  Whether this is the retry after a token refresh
     *
     * @return object The API response
     *
     * @throws \RuntimeException If the request fails
     */
    private function send(string $method, string $endpoint, ?array $data = null, bool $isRetry = false): object
    {
        $token = $this->tokenManager->getToken();

        $curl = curl_init($this->config->getBaseUrl() . $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        if ('POST' === $method) {
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        } else {
            curl_setopt($curl, CURLOPT_HTTPGET, true);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error) {
            $prefix = $isRetry ? 'API retry request failed' : 'API request failed';

            throw new \RuntimeException("$prefix: $error");
        }

        // Handle unauthorized error
        if (401 == $httpCode && ! $isRetry) {
            $this->tokenManager->clearCache();

            return $this->send($method, $endpoint, $data, true);
        }

        $responseData = json_decode($response);

        if ($this->config->getDebug()) {
            error_log('[MpesaSDK] API response: ' . (string)$response);
        }

        // Check for API errors
        if ($httpCode >= 400) {
            // Most APIs use errorMessage; Hakikisha-style APIs nest it under body/header
            $errorMessage = $responseData->errorMessage
                ?? $responseData->body->message
                ?? $responseData->header->message
                ?? $responseData->header->customerMessage
                ?? 'Unknown error occurred';

            throw new \RuntimeException("API error ($httpCode): $errorMessage");
        }

        if (! is_object($responseData)) {
            throw new \RuntimeException('API returned an invalid response: ' . substr((string)$response, 0, 200));
        }

        return $responseData;
    }
}
