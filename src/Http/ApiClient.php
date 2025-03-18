<?php

namespace Kemboielvis\MpesaSdkPhp\Http;

class ApiClient
{
    private $baseUrl;
    private $token_url;
    private $consumer_key;
    private $consumer_secret;
    private $token_cache_file;

    /**
     * @param mixed $baseUrl
     * @param mixed $token_url
     * @param mixed $consumer_key
     * @param mixed $consumer_secret
     */
    public function __construct(string $baseUrl, string $token_url, string $consumer_key, string $consumer_secret)
    {
        $this->baseUrl = $baseUrl;
        $this->token_url = $token_url;
        $this->consumer_key = $consumer_key;
        $this->consumer_secret = $consumer_secret;
        $this->token_cache_file = sys_get_temp_dir() . '/api_token_cache.json';
    }

    /**
     * @return <missing>|null
     * */
    public function authenticationToken()
    {
        // Check if we have a cached token
        $cached_token = $this->getCachedToken();
        if ($cached_token) {
            return $cached_token;
        }

        // No valid cached token, get a new one
        $curl_transfer = curl_init();
        curl_setopt($curl_transfer, CURLOPT_URL, $this->baseUrl . $this->token_url);
        $credentials = base64_encode($this->consumer_key . ':' . $this->consumer_secret);
        curl_setopt($curl_transfer, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
        curl_setopt($curl_transfer, CURLOPT_HEADER, false);
        curl_setopt($curl_transfer, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_transfer, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($curl_transfer);
        $token_data = json_decode($response);

        if (isset($token_data->access_token)) {
            // Get expiration time from response, or default to 3600 seconds
            $expires_in = isset($token_data->expires_in) ? intval($token_data->expires_in) : 3600;

            // Apply a 60-second buffer to prevent edge cases
            $expires_in = max(0, $expires_in - 60);

            // Cache the token with the correct expiration
            $this->cacheToken($token_data->access_token, $expires_in);

            return $token_data->access_token;
        }

        return null;
    }

    /**
     * @return null|<missing>
     */
    private function getCachedToken()
    {
        if (!file_exists($this->token_cache_file)) {
            return null;
        }

        $cache_data = json_decode(file_get_contents($this->token_cache_file), true);

        // Check if token is expired
        if (time() > $cache_data['expires_at']) {
            return null;
        }

        return $cache_data['token'];
    }

    private function cacheToken(string $token, $expires_in): void
    {
        $cache_data = [
            'token' => $token,
            'expires_at' => time() + $expires_in,
            'created_at' => time(),
        ];

        file_put_contents($this->token_cache_file, json_encode($cache_data));
    }

    /**
     * @return mixed@param array<int,mixed> $data
     */
    public function curls(array $data, string $url)
    {
        $token = $this->authenticationToken();
        $curl_transfer = curl_init($this->baseUrl . $url);
        curl_setopt($curl_transfer, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($curl_transfer, CURLOPT_POST, true);
        curl_setopt($curl_transfer, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl_transfer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_transfer, CURLOPT_HEADER, false);
        $response = curl_exec($curl_transfer);
        $http_code = curl_getinfo($curl_transfer, CURLINFO_HTTP_CODE);
        curl_close($curl_transfer);

        // If unauthorized, clear cache and retry once
        if (401 == $http_code) {
            $this->clearTokenCache();

            return $this->handleUnauthorized($data, $url);
        }

        return json_decode($response);
    }

    private function clearTokenCache(): void
    {
        if (file_exists($this->token_cache_file)) {
            unlink($this->token_cache_file);
        }
    }

    /**
     * @param array<int,mixed> $data
     */
    private function handleUnauthorized(array $data, string $url)
    {
        // Get a fresh token
        $token = $this->authenticationToken();

        // Retry the request
        $curl_transfer = curl_init($this->baseUrl . $url);
        curl_setopt($curl_transfer, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token,
        ]);
        curl_setopt($curl_transfer, CURLOPT_POST, true);
        curl_setopt($curl_transfer, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl_transfer, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_transfer, CURLOPT_HEADER, false);
        $response = curl_exec($curl_transfer);
        curl_close($curl_transfer);

        return json_decode($response);
    }
}
