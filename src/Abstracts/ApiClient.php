<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * HTTP client for M-Pesa API
 */
class ApiClient implements MpesaInterface
{
    private MpesaConfig $config;
    private TokenManager $tokenManager;

    public function __construct(MpesaConfig $config)
    {
        $this->config = $config;
        $this->tokenManager = new TokenManager($config);
    }

    /**
     * Execute a request to the M-Pesa API
     *
     * @param array $data The request payload
     * @param string $endpoint The API endpoint
     * @return object The API response
     * @throws \RuntimeException If the request fails
     */
    public function executeRequest(array $data, string $endpoint): object
    {
        $token = $this->tokenManager->getToken();

        $curl = curl_init($this->config->getBaseUrl() . $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);

        if ($error) {
            throw new \RuntimeException("API request failed: $error");
        }

        // Handle unauthorized error
        if (401 == $httpCode) {
            $this->tokenManager->clearCache();
            return $this->retryRequest($data, $endpoint);
        }

        $responseData = json_decode($response);

        // Check for API errors
        if ($httpCode >= 400) {
            $errorMessage = $responseData->errorMessage ?? 'Unknown error occurred';

            throw new \RuntimeException("API error ($httpCode): $errorMessage");
        }

        return $responseData;
    }

    /**
     * Retry a request after token refresh
     *
     * @param array $data The request payload
     * @param string $endpoint The API endpoint
     * @return object The API response
     */
    private function retryRequest(array $data, string $endpoint): object
    {
        $token = $this->tokenManager->getToken();

        $curl = curl_init($this->config->getBaseUrl() . $endpoint);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        curl_close($curl);

        if ($error) {
            throw new \RuntimeException("API retry request failed: $error");
        }

        return json_decode($response);
    }
}