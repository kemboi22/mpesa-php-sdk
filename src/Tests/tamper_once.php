<?php
// Tamper script: after a small delay, write invalid content to the shared cache file once.
// Usage: php tamper_once.php <cachePath> [delay_ms]

$cachePath = $argv[1] ?? (__DIR__ . '/tmp/concurrent_token.json');
$delayMs = isset($argv[2]) ? (int)$argv[2] : 150;

usleep($delayMs * 1000);

$dir = dirname($cachePath);
if (!is_dir($dir)) {
    @mkdir($dir, 0775, true);
}

// Corrupt the cache with invalid JSON
file_put_contents($cachePath, "}{garbage", LOCK_EX);

