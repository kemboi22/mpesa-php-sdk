<?php
// Simple smoke test for TokenManager cache read path without network calls.

require __DIR__ . '/../../vendor/autoload.php';

use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaConfig;
use Kemboielvis\MpesaSdkPhp\Abstracts\TokenManager;

function assertTrue($cond, $msg) {
    if (!$cond) {
        fwrite(STDERR, "Assertion failed: $msg\n");
        exit(1);
    }
}

// Configure with a specific cache file name to avoid clobbering other tests
$config = new MpesaConfig(
    'dummy-key',
    'dummy-secret',
    'sandbox',
    null,
    null,
    null,
    null,
    null,
    'token_cache_smoke.json'
);

$tm = new TokenManager($config);
$cacheFile = $tm->getCacheFilePath();

// Seed a valid token into the cache file
$token = 'TEST_TOKEN_' . bin2hex(random_bytes(4));
$data = [
    'token' => $token,
    'expires_at' => time() + 300, // valid for 5 minutes
    'created_at' => time(),
];
file_put_contents($cacheFile, json_encode($data));

// getToken should return the cached token without network
$result = $tm->getToken();
assertTrue($result === $token, 'Expected cached token to be returned');

echo "PASS: Cached token returned successfully\n";

// Cleanup
$tm->clearCache();
if (file_exists($cacheFile)) {
    @unlink($cacheFile);
}

