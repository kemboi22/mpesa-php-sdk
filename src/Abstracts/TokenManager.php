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

    // Helper: check if path is a stream (e.g., php://)
    private function isStreamPath(string $path): bool
    {
        return (bool) preg_match('#^[a-zA-Z0-9_]+://#', $path);
    }

    // Helper: derive lock file path (use temp dir for streams to avoid invalid paths)
    private function getLockFilePath(): string
    {
        if ($this->isStreamPath($this->tokenCacheFile)) {
            $name = 'mpesa_cache_' . md5($this->tokenCacheFile) . '.lock';
            return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;
        }
        return $this->tokenCacheFile . '.lock';
    }

    // Acquire an exclusive lock, waiting up to timeoutMs. Returns the lock file handle.
    private function acquireLock(int $timeoutMs = 5000)
    {
        $lockPath = $this->getLockFilePath();
        $this->ensureCacheDirectory($lockPath);

        $fh = @fopen($lockPath, 'c');
        if ($fh === false) {
            throw new \RuntimeException('Unable to open lock file: ' . $lockPath);
        }

        $start = microtime(true);
        $sleepUs = 50000; // 50ms
        while (!@flock($fh, LOCK_EX | LOCK_NB)) {
            if (((microtime(true) - $start) * 1000) >= $timeoutMs) {
                fclose($fh);
                throw new \RuntimeException('Timed out acquiring token cache lock: ' . $lockPath);
            }
            usleep($sleepUs);
        }

        if ($this->debug) {
            error_log('[MpesaSDK] Acquired lock: ' . $lockPath);
        }
        return $fh;
    }

    // Release a previously acquired lock file handle.
    private function releaseLock($fh): void
    {
        if (is_resource($fh)) {
            @flock($fh, LOCK_UN);
            @fclose($fh);
        }
        if ($this->debug) {
            error_log('[MpesaSDK] Released lock: ' . $this->getLockFilePath());
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
        // Try to get cached token first (fast path, no locking). Atomic renames make this safe.
        $cachedToken = $this->getCachedToken();
        if ($cachedToken) {
            if ($this->debug) {
                error_log('[MpesaSDK] Using cached token from: ' . $this->tokenCacheFile);
            }
            return $cachedToken;
        }

        // Acquire a process-wide lock to prevent thundering herd. Re-check cache after lock.
        $lockHandle = $this->acquireLock(10000);
        try {
            $cachedToken = $this->getCachedToken();
            if ($cachedToken) {
                if ($this->debug) {
                    error_log('[MpesaSDK] Using cached token (post-lock) from: ' . $this->tokenCacheFile);
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
                curl_close($curl);
                throw new \RuntimeException("Token request failed: $error");
            }

            if ($this->debug) {
                error_log('[MpesaSDK] Token response: ' . $response);
            }

            $tokenData = json_decode($response);
            curl_close($curl);

            if (! isset($tokenData->access_token)) {
                throw new \RuntimeException('Failed to get access token from M-Pesa API');
            }

            // Get expiration time or default to 3600 seconds
            $expiresIn = isset($tokenData->expires_in) ? intval($tokenData->expires_in) : 3600;

            // Add a buffer to prevent edge cases
            $expiresIn = max(0, $expiresIn - 60);

            // Cache the token (atomic write)
            $this->cacheToken($tokenData->access_token, $expiresIn);
            if ($this->debug) {
                error_log('[MpesaSDK] Cached new token to: ' . $this->tokenCacheFile . ' (TTL: ' . $expiresIn . 's)');
            }

            return $tokenData->access_token;
        } finally {
            $this->releaseLock($lockHandle);
        }
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

        $raw = @file_get_contents($this->tokenCacheFile);
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

        // Prefer atomic write via temp file + rename on filesystem paths.
        if ($this->isStreamPath($this->tokenCacheFile)) {
            $result = @file_put_contents($this->tokenCacheFile, json_encode($cacheData), LOCK_EX);
            if ($result === false) {
                if ($this->debug) {
                    error_log('[MpesaSDK] Failed to write token cache (stream) file: ' . $this->tokenCacheFile);
                }
                throw new \RuntimeException('Failed to write token cache file: ' . $this->tokenCacheFile);
            }
        } else {
            $this->writeCacheAtomically($cacheData);
        }

        if ($this->debug) {
            error_log('[MpesaSDK] Wrote token cache file: ' . $this->tokenCacheFile);
        }
    }

    // Atomic cache writer using a temporary file + rename.
    private function writeCacheAtomically(array $cacheData): void
    {
        $dir = dirname($this->tokenCacheFile);
        $this->ensureCacheDirectory($this->tokenCacheFile);

        $tmp = @tempnam($dir, 'mpesa_cache_');
        if ($tmp === false) {
            throw new \RuntimeException('Failed to create temporary file in: ' . $dir);
        }

        $bytes = @file_put_contents($tmp, json_encode($cacheData), LOCK_EX);
        if ($bytes === false) {
            @unlink($tmp);
            throw new \RuntimeException('Failed to write temporary cache file: ' . $tmp);
        }

        @chmod($tmp, 0664);

        if (!@rename($tmp, $this->tokenCacheFile)) {
            @unlink($tmp);
            throw new \RuntimeException('Failed to move cache file into place: ' . $this->tokenCacheFile);
        }
    }

    /**
     * Clear the token cache.
     */
    public function clearCache(): void
    {
        if (file_exists($this->tokenCacheFile)) {
            @unlink($this->tokenCacheFile);
            if ($this->debug) {
                error_log('[MpesaSDK] Token cache cleared: ' . $this->tokenCacheFile);
            }
        }
        // Best-effort cleanup of lock file (not required for correctness)
        $lockPath = $this->getLockFilePath();
        if (file_exists($lockPath)) {
            @unlink($lockPath);
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
