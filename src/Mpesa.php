<?php

namespace Kemboielvis\MpesaSdkPhp;

use Kemboielvis\MpesaSdkPhp\Helpers\AccountBalance;
use Kemboielvis\MpesaSdkPhp\Helpers\BusinessToCustomer;
use Kemboielvis\MpesaSdkPhp\Helpers\CustomerToBusiness;
use Kemboielvis\MpesaSdkPhp\Helpers\Reversal;
use Kemboielvis\MpesaSdkPhp\Helpers\Stk;
use Kemboielvis\MpesaSdkPhp\Helpers\TransactionStatus;
use Kemboielvis\MpesaSdkPhp\Http\ApiClient;

class Mpesa
{
    protected string $consumer_key = '';
    protected string $consumer_secret = '';
    protected string $business_code = '';
    protected string $pass_key = '';
    protected string $transaction_type = '';
    private string $token_url = '/oauth/v1/generate?grant_type=client_credentials';
    protected string $phone_number = '';

    protected string $amount = '';
    protected string $call_back_url = '';

    protected string $security_credential = '';

    protected object $response;
    protected string $queue_timeout_url = '';
    protected string $result_url = '';

    private string $baseUrl = '';

    private string $env = '';
    private ApiClient $apiClient;

    public function __construct(?string $key = null, ?string $secret = null, ?string $env = null)
    {
        $this->setCredentials($key, $secret, $env);
        $this->apiClient = new ApiClient($this->baseUrl, $this->token_url, $key, $secret);
    }

    public function setCredentials(?string $consumer_key = null, ?string $consumer_secret = null, ?string $env = null): Mpesa
    {
        if (null != $consumer_key) {
            $this->consumerKey($consumer_key);
        }
        if (null != $consumer_secret) {
            $this->consumerSecret($consumer_secret);
        }
        if (null != $env) {
            $this->env($env);
        }
        $this->baseUrl = ('live' == strtolower($this->env)) ? 'https://api.safaricom.co.ke' : 'https://sandbox.safaricom.co.ke';

        $this->apiClient = new ApiClient($this->baseUrl, $this->token_url, $this->consumer_key, $this->consumer_secret);

        return $this;
    }

    public function timestamp(): string
    {
        return date('YmdHis');
    }

    public function password(): string
    {
        return base64_encode($this->business_code . $this->pass_key . $this->timestamp());
    }

    public function businessCode(string $business_code): static
    {
        $this->business_code = $business_code;

        return $this;
    }

    public function env(string $env): Mpesa
    {
        $this->env = $env;

        return $this;
    }

    public function amount(int $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function phoneNumber(string $phone): static
    {
        $this->phone_number = $phone;

        return $this;
    }

    public function consumerKey(string $consumer_key): static
    {
        $this->consumer_key = $consumer_key;

        return $this;
    }

    public function consumerSecret(string $consumer_secret): static
    {
        $this->consumer_secret = $consumer_secret;

        return $this;
    }

    public function passKey(string $pass_key): static
    {
        $this->pass_key = $pass_key;

        return $this;
    }

    public function response(): object
    {
        return $this->response;
    }

    public function securityCredential(string $initiator_password): Mpesa
    {
        $method = 'aes-256-cbc';
        $password = 'mypassword';
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $this->security_credential = base64_encode($iv . openssl_encrypt("{$initiator_password} + Certificate", $method, $password, 0, $iv));

        return $this;
    }

    public function resultUrl(string $result_url): Mpesa
    {
        $this->result_url = $result_url;

        return $this;
    }

    public function queueTimeoutUrl(string $timeout_url): Mpesa
    {
        $this->queue_timeout_url = $timeout_url;

        return $this;
    }

    /**
     * @param array<int,mixed> $data
     */
    public function curls(array $data, string $url)
    {
        return $this->apiClient->curls($data, $url);
    }

    public function stk(): Stk
    {
        return new Stk([
            'consumer_key' => $this->consumer_key,
            'consumer_secret' => $this->consumer_secret,
            'base_url' => $this->baseUrl,
            'business_code' => $this->business_code,
            'transaction_type' => $this->transaction_type,
            'amount' => $this->amount,
            'phone_number' => $this->phone_number,
            'call_back_url' => $this->call_back_url,
        ]);
    }

    public function customerToBusiness(): CustomerToBusiness
    {
        return new CustomerToBusiness($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    public function businessToCustomer(): BusinessToCustomer
    {
        return new BusinessToCustomer($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    public function checkBalance(): AccountBalance
    {
        return new AccountBalance($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    public function transactionStatus(): TransactionStatus
    {
        return new TransactionStatus($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }

    public function reversal(): Reversal
    {
        return new Reversal($this->consumer_key, $this->consumer_secret, $this->baseUrl);
    }
}
