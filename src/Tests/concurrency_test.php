<?php
// Concurrency test: start fake server, spawn multiple workers with shared cache,
// verify only one network call is made and all tokens match.

require __DIR__ . '/../../vendor/autoload.php';

function assert_true($cond, $msg) {
    if (!$cond) {
        fwrite(STDERR, "Assertion failed: $msg\n");
        exit(1);
    }
}

$baseDir = dirname(__DIR__, 1);
$testsDir = __DIR__;
$worker = $testsDir . '/token_worker.php';
$counterFile = $testsDir . '/fake_counter.txt';
$tmpDir = $testsDir . '/tmp';
@mkdir($tmpDir, 0775, true);
$cachePath = $tmpDir . '/concurrent_token.json';

// Cleanup from previous runs
if (file_exists($counterFile)) @unlink($counterFile);
if (file_exists($cachePath)) @unlink($cachePath);

$baseUrl = 'http://127.0.0.1:8091';
$N = 10;

function run_workers(string $worker, string $baseUrl, string $cachePath, int $N): array {
    $procs = [];
    $pipesArr = [];
    for ($i = 0; $i < $N; $i++) {
        $cmd = sprintf('php %s %s %s', escapeshellarg($worker), escapeshellarg($baseUrl), escapeshellarg($cachePath));
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $proc = proc_open($cmd, $descriptors, $pipes);
        if (!is_resource($proc)) {
            fwrite(STDERR, "Failed to start worker $i\n");
            continue;
        }
        // Non-blocking read on stdout
        stream_set_blocking($pipes[1], true);
        $procs[] = $proc;
        $pipesArr[] = $pipes;
    }

    $tokens = [];
    foreach ($procs as $idx => $proc) {
        $pipes = $pipesArr[$idx];
        $out = stream_get_contents($pipes[1]);
        $err = stream_get_contents($pipes[2]);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $status = proc_close($proc);
        if ($status !== 0) {
            fwrite(STDERR, "Worker $idx exited with status $status. STDERR: $err\n");
        }
        $tokens[] = trim($out);
    }

    return $tokens;
}

// Round 1: all workers start with no cache; expect only one server hit
$tokens1 = run_workers($worker, $baseUrl, $cachePath, $N);
// All tokens should be the same and equal to FAKE_TOKEN_123
$unique1 = array_values(array_unique($tokens1));
assert_true(count($unique1) === 1, 'All tokens from round 1 should match');
assert_true($unique1[0] === 'FAKE_TOKEN_123', 'Token should equal FAKE_TOKEN_123');

// Counter should read 1
$counter = file_exists($counterFile) ? (int)trim(file_get_contents($counterFile)) : -1;
assert_true($counter === 1, 'Expected exactly one request to the token endpoint in round 1');

// Round 2: with cached token present, expect no additional server calls
$tokens2 = run_workers($worker, $baseUrl, $cachePath, $N);
$unique2 = array_values(array_unique($tokens2));
assert_true(count($unique2) === 1, 'All tokens from round 2 should match');
assert_true($unique2[0] === 'FAKE_TOKEN_123', 'Round 2 token should equal FAKE_TOKEN_123');

$counter2 = file_exists($counterFile) ? (int)trim(file_get_contents($counterFile)) : -1;
assert_true($counter2 === 1, 'Counter should remain 1 after round 2 (no new network calls)');

echo "PASS: Concurrency test succeeded (single fetch, all tokens match)\n";

// Cleanup
@unlink($cachePath);
@unlink($counterFile);

