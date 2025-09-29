<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * Auth token manager for M-Pesa API.
 */
class TokenManager
{
    private string $consumerKey;

    private string $consumerSecret;

    private string $baseUrl;

    private string $tokenUrl = '/oauth/v1/generate?grant_type=client_credentials';

    private string $tokenCacheFile;

    private bool $debug = false;

    public function __construct(MpesaConfig $config)
    {
        $this->consumerKey = $config->getConsumerKey();
        $this->consumerSecret = $config->getConsumerSecret();
        $this->baseUrl = $config->getBaseUrl();
        $this->debug = method_exists($config, 'getDebug') ? (bool) $config->getDebug() : false;

        // Resolve the token cache path from config and ensure directory exists
        $this->tokenCacheFile = $this->resolveCachePath($config->getStoreFile());
        $this->ensureCacheDirectory($this->tokenCacheFile);

        if ($this->debug) {
            error_log('[MpesaSDK] Token cache file set to: ' . $this->tokenCacheFile);
        }
    }

    /**
     * Resolve the cache path from the given path.
     */
    private function resolveCachePath(string $path): string
    {
        $path = trim($path);

        // If empty, default to system temp directory with default filename
        if ($path === '') {
            return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'mpesa_api_cache.json';
        }

        // If path looks like a stream or protocol, use as-is (e.g., php://, file://)
        if (preg_match('#^[a-zA-Z0-9_]+://#', $path)) {
            return $path;
        }

        // If absolute path, use as-is
        if ($path[0] === DIRECTORY_SEPARATOR) {
            return $path;
        }

        // If no directory component given (e.g., "token.json" or "./token.json"), store in temp
        $dir = dirname($path);
        if ($dir === '.' || $dir === '') {
            return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($path);
        }

        // Otherwise treat as relative to current working directory
        return rtrim(getcwd(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
    }

    private function ensureCacheDirectory(string $filePath): void
    {
        $dir = dirname($filePath);
        if (! is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    /**
     * Get a valid authentication token.
     *
     * @return string The authentication token
     *
     * @throws \RuntimeException If token retrieval fails
     */
    public function getToken(): string
    {
        // Try to get cached token first
        $cachedToken = $this->getCachedToken();
        if ($cachedToken) {
            if ($this->debug) {
                error_log('[MpesaSDK] Using cached token from: ' . $this->tokenCacheFile);
            }
            return $cachedToken;
        }

        // Get a new token
        if ($this->debug) {
            error_log('[MpesaSDK] Requesting new token from: ' . $this->baseUrl . $this->tokenUrl);
        }
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
            if ($this->debug) {
                error_log('[MpesaSDK] Token request error: ' . $error);
            }
            throw new \RuntimeException("Token request failed: $error");
        }

        if ($this->debug) {
            error_log('[MpesaSDK] Token response: ' . $response);
        }

        $tokenData = json_decode($response);

        if (! isset($tokenData->access_token)) {
            throw new \RuntimeException('Failed to get access token from M-Pesa API');
        }

        // Get expiration time or default to 3600 seconds
        $expiresIn = isset($tokenData->expires_in) ? intval($tokenData->expires_in) : 3600;

        // Add a buffer to prevent edge cases
        $expiresIn = max(0, $expiresIn - 60);

        // Cache the token
        $this->cacheToken($tokenData->access_token, $expiresIn);
        if ($this->debug) {
            error_log('[MpesaSDK] Cached new token to: ' . $this->tokenCacheFile . ' (TTL: ' . $expiresIn . 's)');
        }

        return $tokenData->access_token;
    }

    /**
     * Get cached token if valid.
     */
    private function getCachedToken(): ?string
    {
        if (! file_exists($this->tokenCacheFile)) {
            if ($this->debug) {
                error_log('[MpesaSDK] No token cache file found at: ' . $this->tokenCacheFile);
            }
            return null;
        }

        $raw = file_get_contents($this->tokenCacheFile);
        if ($raw === false) {
            if ($this->debug) {
                error_log('[MpesaSDK] Failed to read token cache file: ' . $this->tokenCacheFile);
            }
            return null;
        }

        $cacheData = json_decode($raw, true);
        if (! is_array($cacheData) || ! isset($cacheData['token'], $cacheData['expires_at'])) {
            if ($this->debug) {
                error_log('[MpesaSDK] Invalid token cache file format at: ' . $this->tokenCacheFile);
            }
            return null;
        }

        // Check if token is expired
        if (time() > $cacheData['expires_at']) {
            if ($this->debug) {
                error_log('[MpesaSDK] Cached token expired at: ' . date('c', $cacheData['expires_at']));
            }
            return null;
        }

        if ($this->debug) {
            error_log('[MpesaSDK] Cached token is valid until: ' . date('c', $cacheData['expires_at']));
        }
        return $cacheData['token'];
    }

    /**
     * Cache a token.
     */
    private function cacheToken(string $token, int $expiresIn): void
    {
        $cacheData = [
            'token' => $token,
            'expires_at' => time() + $expiresIn,
            'created_at' => time(),
        ];

        $result = @file_put_contents($this->tokenCacheFile, json_encode($cacheData));
        if ($result === false) {
            if ($this->debug) {
                error_log('[MpesaSDK] Failed to write token cache file: ' . $this->tokenCacheFile);
            }
            throw new \RuntimeException('Failed to write token cache file: ' . $this->tokenCacheFile);
        }
        if ($this->debug) {
            error_log('[MpesaSDK] Wrote token cache file: ' . $this->tokenCacheFile);
        }
    }

    /**
     * Clear the token cache.
     */
    public function clearCache(): void
    {
        if (file_exists($this->tokenCacheFile)) {
            unlink($this->tokenCacheFile);
            if ($this->debug) {
                error_log('[MpesaSDK] Token cache cleared: ' . $this->tokenCacheFile);
            }
        }
    }

    /**
     * Get the resolved token cache file path.
     */
    public function getCacheFilePath(): string
    {
        return $this->tokenCacheFile;
    }
}
