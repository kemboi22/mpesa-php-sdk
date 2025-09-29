<?php
// Simple router for PHP built-in server to simulate M-Pesa token endpoint.
// It increments a counter file on each request and returns a fixed token JSON.

$counterFile = __DIR__ . '/fake_counter.txt';

function inc_counter($file)
{
    $dir = dirname($file);
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    $fh = @fopen($file, 'c+');
    if ($fh === false) {
        // Best effort: fallback create
        $fh = @fopen($file, 'w+');
        if ($fh === false) {
            return;
        }
    }
    if (@flock($fh, LOCK_EX)) {
        $count = 0;
        $raw = stream_get_contents($fh);
        if ($raw !== false && strlen($raw) > 0) {
            $count = (int)trim($raw);
        }
        $count++;
        ftruncate($fh, 0);
        rewind($fh);
        fwrite($fh, (string)$count);
        fflush($fh);
        @flock($fh, LOCK_UN);
    }
    fclose($fh);
}

// Only increment for the token endpoint path to be precise
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/oauth/v1/generate') === 0) {
    inc_counter($counterFile);
}

http_response_code(200);
header('Content-Type: application/json');
echo json_encode([
    'access_token' => 'FAKE_TOKEN_123',
    'expires_in' => 120,
]);

