<?php
// Worker script: obtains a token using TokenManager and prints it.

require __DIR__ . '/../../vendor/autoload.php';

use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaConfig;
use Kemboielvis\MpesaSdkPhp\Abstracts\TokenManager;

$baseUrl = $argv[1] ?? 'http://127.0.0.1:8091';
$cachePath = $argv[2] ?? (__DIR__ . '/tmp/concurrent_token.json');

$config = new MpesaConfig('dummy', 'dummy', 'sandbox');
$config->setBaseUrl($baseUrl)
       ->setStoreFile($cachePath)
       ->setDebug(false);

$tm = new TokenManager($config);
$token = $tm->getToken();
// Print only the token on stdout
fwrite(STDOUT, $token . "\n");

