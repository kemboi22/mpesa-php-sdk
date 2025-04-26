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
     * Constructor
     *
     * @param string $baseUrl The base URL for the M-Pesa API
     * @param string $token_url The URL for obtaining an authentication token
     * @param string $consumer_key The consumer key for the M-Pesa API
     * @param string $consumer_secret The consumer secret for the M-Pesa API
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
     * Returns an authentication token from the M-Pesa API.
     *
     * Checks if there is a valid cached token before attempting to get a new one.
     * If a cached token is found, it is returned immediately.
     * If no valid cached token is found, a new token is obtained from the M-Pesa API
     * and the response is cached for future requests.
     *
     * @return string|null The authentication token, or null if no valid token could be obtained
     */
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
     * Retrieves a cached authentication token if it exists and is still valid.
     *
     * This method checks for the existence of a cached token file and ensures
     * the token within is not expired. If the file does not exist or the token
     * is expired, null is returned.
     *
     * @return string|null The cached token if valid, or null if no valid cached token is available.
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

    /**
     * Caches the authentication token with its expiration time.
     *
     * This method stores the provided token in a cache file along with its
     * expiration timestamp and creation time. The token is saved in a JSON
     * format for later retrieval.
     *
     * @param string $token The authentication token to be cached.
     * @param int $expires_in The number of seconds until the token expires.
     * @return void
     */
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
     * Executes a cURL request to the specified URL with the provided data.
     *
     * This method initializes a cURL session and sends a POST request to the
     * given URL, using the provided data as JSON payload. It includes an
     * authorization header with a bearer token. If the request is unauthorized,
     * it clears the token cache and retries the request once.
     *
     * @param array<int,mixed> $data The data to be sent in the POST request.
     * @param string $url The endpoint URL for the cURL request.
     * @return mixed The decoded JSON response from the server.
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

    /**
     * Clears the authentication token cache.
     *
     * If the token cache file exists, this method deletes it, effectively
     * invalidating the current authentication token. This is typically used
     * when the API returns an unauthorized response, indicating that the
     * current token is no longer valid.
     */
    private function clearTokenCache(): void
    {
        if (file_exists($this->token_cache_file)) {
            unlink($this->token_cache_file);
        }
    }


    /**
     * Handles an unauthorized response by getting a fresh token and retrying the request.
     *
     * If the API returns an unauthorized response, this method is called to handle it.
     * It clears the token cache, gets a fresh token using {@see authenticationToken},
     * and retries the original request.
     *
     * @param array $data The original request data.
     * @param string $url The original request URL.
     *
     * @return mixed The response from the retried request.
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
