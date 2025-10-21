<?php
// Tamper concurrency test: ensure that even if the cache file is manipulated concurrently,
// the system does not thrash and all workers end up with the correct token. We allow at most
// one additional network call due to tampering.

require __DIR__ . '/../../vendor/autoload.php';

function assert_true($cond, $msg) {
    if (!$cond) {
        fwrite(STDERR, "Assertion failed: $msg\n");
        exit(1);
    }
}

$testsDir = __DIR__;
$worker = $testsDir . '/token_worker.php';
$tamper = $testsDir . '/tamper_once.php';
$counterFile = $testsDir . '/fake_counter.txt';
$tmpDir = $testsDir . '/tmp';
@mkdir($tmpDir, 0775, true);
$cachePath = $tmpDir . '/concurrent_token.json';

// Cleanup from previous runs
if (file_exists($counterFile)) @unlink($counterFile);
if (file_exists($cachePath)) @unlink($cachePath);

$baseUrl = 'http://127.0.0.1:8092';
$N = 12;

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

function run_tamper(string $tamper, string $cachePath, int $delayMs): void {
    $cmd = sprintf('php %s %s %d', escapeshellarg($tamper), escapeshellarg($cachePath), $delayMs);
    $descriptors = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $proc = proc_open($cmd, $descriptors, $pipes);
    if (is_resource($proc)) {
        // Wait for tamper to finish
        fclose($pipes[0]); fclose($pipes[1]); fclose($pipes[2]);
        proc_close($proc);
    }
}

// Round 1: build initial cache with concurrent clients (no tamper)
$tokens1 = run_workers($worker, $baseUrl, $cachePath, $N);
$unique1 = array_values(array_unique($tokens1));
assert_true(count($unique1) === 1, 'Round 1: tokens must be identical');
assert_true($unique1[0] === 'FAKE_TOKEN_123', 'Round 1: token should equal FAKE_TOKEN_123');
$counter1 = file_exists($counterFile) ? (int)trim(file_get_contents($counterFile)) : -1;
assert_true($counter1 === 1, 'Round 1: expected exactly one network call');

// Round 2: start tamper shortly after firing workers; allow a single refresh at most
// Fire tamper asynchronously with small delay
$delayMs = 100; // adjust if needed
$tp = function() use ($tamper, $cachePath, $delayMs) { run_tamper($tamper, $cachePath, $delayMs); };
// Run tamper in background using pcntl_fork if available; else run before workers (still races)
$forked = false;
if (function_exists('pcntl_fork')) {
    $pid = pcntl_fork();
    if ($pid === 0) {
        // child
        $tp();
        exit(0);
    } elseif ($pid > 0) {
        $forked = true;
    }
}
if (!$forked) {
    // Fallback: start tamper and in parallel try to run workers quickly after
    // This still creates a race that should trigger invalidation handling
    $tp();
}

$tokens2 = run_workers($worker, $baseUrl, $cachePath, $N);
$unique2 = array_values(array_unique($tokens2));
assert_true(count($unique2) === 1, 'Round 2: tokens must be identical after tamper');
assert_true($unique2[0] === 'FAKE_TOKEN_123', 'Round 2: token should equal FAKE_TOKEN_123');

$counter2 = file_exists($counterFile) ? (int)trim(file_get_contents($counterFile)) : -1;
assert_true($counter2 <= 2, 'Round 2: expected at most one additional network call due to tamper');

// Give a brief moment for any late tamper to finish before Round 3
usleep(200 * 1000);

// Round 3: no tamper, ensure no additional calls beyond the allowed one
$tokens3 = run_workers($worker, $baseUrl, $cachePath, $N);
$unique3 = array_values(array_unique($tokens3));
assert_true(count($unique3) === 1, 'Round 3: tokens must be identical');
assert_true($unique3[0] === 'FAKE_TOKEN_123', 'Round 3: token should equal FAKE_TOKEN_123');
$counter3 = file_exists($counterFile) ? (int)trim(file_get_contents($counterFile)) : -1;
assert_true($counter3 <= 2, 'Round 3: total network calls should be <= 2');

echo "PASS: Tamper concurrency test succeeded (tokens consistent, minimal refresh)\n";

// Cleanup
@unlink($cachePath);
@unlink($counterFile);

