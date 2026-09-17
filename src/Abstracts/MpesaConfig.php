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

    private string $certificate = '';

    private int $timeout = 60;

    private int $connectTimeout = 10;

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
        $this->store_file = $store_file ?? $this->getEncryptedFileName();
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

    // Allow overriding base URL (useful for tests or custom gateways)
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
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
     * Get the encrypted security credential sent to M-Pesa.
     *
     * @return string
     */
    public function getSecurityCredential(): string
    {
        return $this->security_credential;
    }

    /**
     * Set M-Pesa's public key certificate, used to encrypt initiator passwords.
     * Download the sandbox or production certificate from the Daraja portal.
     *
     * @param string $certificate Path to the .cer file, or its PEM contents
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the certificate cannot be read
     */
    public function setCertificate(string $certificate): self
    {
        $certificate = trim($certificate);

        if ('' === $certificate) {
            throw new \InvalidArgumentException('Certificate cannot be empty');
        }

        if (! str_contains($certificate, '-----BEGIN') && is_file($certificate)) {
            $contents = @file_get_contents($certificate);
            if (false === $contents) {
                throw new \InvalidArgumentException("Unable to read certificate file: $certificate");
            }
            $certificate = trim($contents);
        }

        // DER (binary) certificates are converted to PEM
        if (! str_contains($certificate, '-----BEGIN')) {
            $certificate = "-----BEGIN CERTIFICATE-----\n"
                . chunk_split(base64_encode($certificate), 64, "\n")
                . "-----END CERTIFICATE-----\n";
        }

        if (false === openssl_pkey_get_public($certificate)) {
            throw new \InvalidArgumentException('Invalid certificate: no public key could be read');
        }

        $this->certificate = $certificate;

        return $this;
    }

    /**
     * Get M-Pesa's public key certificate (PEM), if set.
     *
     * @return string
     */
    public function getCertificate(): string
    {
        return $this->certificate;
    }

    /**
     * Encrypt the initiator password with M-Pesa's public key certificate
     * (RSA, PKCS #1 v1.5) and store it as the security credential.
     *
     * The certificate is only needed when no credential is set yet: if an
     * already encrypted credential was provided (constructor or
     * overrideSecurityCredential()) and there is no certificate, that
     * credential is kept and the password is not used.
     *
     * @param string      $initiator_password The initiator's plain-text password.
     * @param string|null $certificate        Optional certificate path or PEM; defaults to setCertificate()
     *
     * @return self
     *
     * @throws \InvalidArgumentException If there is neither a certificate nor an existing credential
     * @throws \RuntimeException         If encryption fails
     */
    public function setSecurityCredential(string $initiator_password, ?string $certificate = null): self
    {
        if (null !== $certificate) {
            $this->setCertificate($certificate);
        }

        if ('' === $this->certificate) {
            if ('' !== $this->security_credential) {
                // Use the pre-encrypted credential (e.g. generated on the Daraja portal)
                return $this;
            }

            throw new \InvalidArgumentException(
                'An M-Pesa certificate is required to encrypt the initiator password. '
                . 'Call setCertificate() with the certificate from the Daraja portal, '
                . 'or pass an already encrypted credential to overrideSecurityCredential().'
            );
        }

        $publicKey = openssl_pkey_get_public($this->certificate);

        if (false === $publicKey || ! openssl_public_encrypt($initiator_password, $encrypted, $publicKey, OPENSSL_PKCS1_PADDING)) {
            throw new \RuntimeException('Failed to encrypt the initiator password');
        }

        $this->security_credential = base64_encode($encrypted);

        return $this;
    }

    /**
     * Override the security credential.
     *
     * This method allows you to override the security credential with a custom value.
     * The security credential is used to authenticate the M-Pesa API.
     *
     * @param string $credential The security credential to be used.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the security credential is empty.
     */
    public function overrideSecurityCredential(string $credential): self
    {
        if (empty($credential)) {
            throw new \InvalidArgumentException('Security credential cannot be empty');
        }
        $this->security_credential = $credential;
        return $this;
    }


    /**
     * Get the default token cache file name for these credentials.
     *
     * The name is a one-way hash, so the credentials cannot be recovered from it.
     *
     * @return string The file name
     */
    public function getEncryptedFileName(): string
    {
        return 'mpesa_token_' . hash('sha256', $this->getConsumerKey() . ':' . $this->getConsumerSecret()) . '.json';
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

    /**
     * Get the maximum time in seconds an API request may take.
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * Set the maximum time in seconds an API request may take.
     */
    public function setTimeout(int $seconds): self
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException('Timeout must be at least 1 second');
        }

        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Get the maximum time in seconds to wait for a connection.
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * Set the maximum time in seconds to wait for a connection.
     */
    public function setConnectTimeout(int $seconds): self
    {
        if ($seconds < 1) {
            throw new \InvalidArgumentException('Connect timeout must be at least 1 second');
        }

        $this->connectTimeout = $seconds;
        return $this;
    }
}
