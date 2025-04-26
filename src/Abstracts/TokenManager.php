<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * Auth token manager for M-Pesa API
 */
class TokenManager
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $baseUrl;
    private string $tokenUrl = '/oauth/v1/generate?grant_type=client_credentials';
    private string $tokenCacheFile;

    public function __construct(MpesaConfig $config)
    {
        $this->consumerKey = $config->getConsumerKey();
        $this->consumerSecret = $config->getConsumerSecret();
        $this->baseUrl = $config->getBaseUrl();
        $this->tokenCacheFile = sys_get_temp_dir() . '/mpesa_api_token_cache.json';
    }

    /**
     * Get a valid authentication token
     *
     * @return string The authentication token
     * @throws \RuntimeException If token retrieval fails
     */
    public function getToken(): string
    {
        // Try to get cached token first
        $cachedToken = $this->getCachedToken();
        if ($cachedToken) {
            return $cachedToken;
        }

        // Get a new token
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $this->baseUrl . $this->tokenUrl);
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        curl_setopt($curl, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($curl);
        $error = curl_error($curl);

        if ($error) {
            throw new \RuntimeException("Token request failed: $error");
        }

        $tokenData = json_decode($response);

        if (!isset($tokenData->access_token)) {
            throw new \RuntimeException('Failed to get access token from M-Pesa API');
        }

        // Get expiration time or default to 3600 seconds
        $expiresIn = isset($tokenData->expires_in) ? intval($tokenData->expires_in) : 3600;

        // Add a buffer to prevent edge cases
        $expiresIn = max(0, $expiresIn - 60);

        // Cache the token
        $this->cacheToken($tokenData->access_token, $expiresIn);

        return $tokenData->access_token;
    }

    /**
     * Get cached token if valid
     *
     * @return string|null The cached token or null if expired/invalid
     */
    private function getCachedToken(): ?string
    {
        if (!file_exists($this->tokenCacheFile)) {
            return null;
        }

        $cacheData = json_decode(file_get_contents($this->tokenCacheFile), true);

        // Check if token is expired
        if (time() > $cacheData['expires_at']) {
            return null;
        }

        return $cacheData['token'];
    }

    /**
     * Cache a token
     *
     * @param string $token The token to cache
     * @param int $expiresIn Seconds until expiration
     */
    private function cacheToken(string $token, int $expiresIn): void
    {
        $cacheData = [
            'token' => $token,
            'expires_at' => time() + $expiresIn,
            'created_at' => time(),
        ];

        file_put_contents($this->tokenCacheFile, json_encode($cacheData));
    }

    /**
     * Clear the token cache
     */
    public function clearCache(): void
    {
        if (file_exists($this->tokenCacheFile)) {
            unlink($this->tokenCacheFile);
        }
    }
}