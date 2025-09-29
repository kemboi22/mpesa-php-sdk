<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * Configuration class for M-Pesa SDK.
 */
class MpesaConfig
{
    private string $consumerKey;

    private string $consumerSecret;

    private string $environment;

    private string $baseUrl;

    private string $businessCode;

    private ?string $passKey;

    private string $security_credential;

    private string $queue_timeout_url;

    private string $result_url;

    private string $store_file;

    private bool $debug = false;

    public function __construct(
        string  $consumerKey,
        string  $consumerSecret,
        string  $environment = 'sandbox',
        ?string $businessCode = null,
        ?string $passKey = null,
        ?string $security_credential = null,
        ?string $queue_timeout_url = null,
        ?string $result_url = null,
        ?string $store_file = null,
    ) {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->environment = strtolower($environment);
        $this->businessCode = $businessCode ?? '';
        $this->passKey = $passKey ?? '';
        $this->baseUrl = ('live' === $this->environment)
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
        $this->security_credential = $security_credential ?? '';
        $this->queue_timeout_url = $queue_timeout_url ?? '';
        $this->result_url = $result_url ?? '';
        $this->store_file = $store_file ?? 'mpesa_api_cache.json';
    }

    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    public function getConsumerSecret(): string
    {
        return $this->consumerSecret;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getBusinessCode(): string
    {
        return $this->businessCode;
    }

    public function setBusinessCode(string $businessCode): self
    {
        $this->businessCode = $businessCode;

        return $this;
    }

    public function getPassKey(): string
    {
        return $this->passKey;
    }

    public function setPassKey(string $passKey): self
    {
        $this->passKey = $passKey;

        return $this;
    }

    /**
     * Generate and set the security credential using AES-256-CBC encryption.
     *
     * This method encrypts the initiator password with a predetermined
     * password and a random initialization vector (IV) using the AES-256-CBC
     * encryption algorithm. The result is then base64 encoded and stored as
     * the security credential.
     *
     * @return string
     */
    public function getSecurityCredential(): string
    {
        return $this->security_credential;
    }

    /**
     * Set the security credential by storing the provided initiator password.
     *
     * @param string $initiator_password The initiator password to be stored as the security credential.
     *
     * @return self
     */
    public function setSecurityCredential(string $initiator_password): self
    {
        $initiator_password1 = $initiator_password;

        $method = 'aes-256-cbc';
        $password = 'mypassword';
        $ivlen = openssl_cipher_iv_length($method);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $this->security_credential = base64_encode($iv . openssl_encrypt("{$initiator_password1} + Certificate", $method, $password, 0, $iv));

        return $this;
    }

    /**
     * Set the queue timeout URL.
     *
     * The queue timeout URL is the URL that will be used by the API to send a
     * notification in case the request times out while awaiting processing in
     * the queue.
     *
     * @param string $queue_timeout_url The URL that will be used by the API to
     *                                  send a notification in case the request
     *                                  times out while awaiting processing in
     *                                  the queue.
     *
     * @return self
     */
    public function setQueueTimeoutUrl(string $queue_timeout_url): self
    {
        $this->queue_timeout_url = $queue_timeout_url;

        return $this;
    }

    /**
     * Get the queue timeout URL.
     *
     * @return string The URL that will be used by the API to send a notification
     *                in case the request times out while awaiting processing in the queue.
     */
    public function getQueueTimeoutUrl(): string
    {
        return $this->queue_timeout_url;
    }

    /**
     * Set the result URL for the API request.
     *
     * @param string $result_url The URL to receive the response from the M-Pesa API.
     *
     * @return self
     */
    public function setResultUrl(string $result_url): self
    {
        $this->result_url = $result_url;

        return $this;
    }

    /**
     * Gets the result URL for the API request.
     *
     * @return string The result URL
     */
    public function getResultUrl(): string
    {
        return $this->result_url;
    }



    /**
     * Get the file path to store the transaction results.
     *
     * The file path is used to store the results of the transaction in a
     * file. The results are stored in JSON format.
     *
     * @return string The file path to store the transaction results.
     */
    public function getStoreFile(): string
    {
        return $this->store_file;
    }

    /**
     * Set the file path to store the transaction results.
     *
     * @param string $store_file The file path to store the transaction results.
     *
     * @return self
     */
    public function setStoreFile(string $store_file): self
    {
        $this->store_file = $store_file;
        return $this;
    }

    public function getDebug(): bool
    {
        return $this->debug;
    }

    public function setDebug(bool $debug): self
    {
        $this->debug = $debug;
        return $this;
    }
}
