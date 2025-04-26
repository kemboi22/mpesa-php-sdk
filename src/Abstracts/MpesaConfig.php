<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * Configuration class for M-Pesa SDK
 */
class MpesaConfig
{
    private string $consumerKey;
    private string $consumerSecret;
    private string $environment;
    private string $baseUrl;
    private string $businessCode;
    private ?string $passKey;

    public function __construct(
        string  $consumerKey,
        string  $consumerSecret,
        string  $environment = 'sandbox',
        ?string $businessCode = null,
        ?string $passKey = null
    )
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->environment = strtolower($environment);
        $this->businessCode = $businessCode ?? '';
        $this->passKey = $passKey ?? '';
        $this->baseUrl = ('live' === $this->environment)
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
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
}