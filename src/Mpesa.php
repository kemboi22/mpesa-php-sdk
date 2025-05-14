<?php

namespace Kemboielvis\MpesaSdkPhp;

use Kemboielvis\MpesaSdkPhp\Abstracts\ApiClient;
use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaConfig;
use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaInterface;
use Kemboielvis\MpesaSdkPhp\Services\AccountBalanceService;
use Kemboielvis\MpesaSdkPhp\Services\BusinessToCustomerService;
use Kemboielvis\MpesaSdkPhp\Services\CustomerToBusinessService;
use Kemboielvis\MpesaSdkPhp\Services\ReversalService;
use Kemboielvis\MpesaSdkPhp\Services\StkService;
use Kemboielvis\MpesaSdkPhp\Services\TransactionStatusService;

/**
 * Main M-Pesa SDK class.
 */
class Mpesa {
    private MpesaConfig $config;

    private MpesaInterface $client;

    /**
     * Create a new Mpesa instance.
     *
     * @param string|null $consumerKey    The consumer key
     * @param string|null $consumerSecret The consumer secret
     * @param string      $environment    The environment (live or sandbox)
     */
    public function __construct(
        string $consumerKey = null,
        string $consumerSecret = null,
        string $environment = 'sandbox'
    ) {
        $this->config = new MpesaConfig($consumerKey, $consumerSecret, $environment);
        $this->client = new ApiClient($this->config);
    }

    /**
     * Set the credentials for the M-Pesa API.
     *
     * @param string $consumerKey    The consumer key
     * @param string $consumerSecret The consumer secret
     * @param string $environment    The environment (live or sandbox)
     *
     * @return self
     */
    public function setCredentials(string $consumerKey, string $consumerSecret, string $environment = 'sandbox'): self {
        $this->config = new MpesaConfig($consumerKey, $consumerSecret, $environment);
        $this->client = new ApiClient($this->config);

        return $this;
    }

    /**
     * Set the business code.
     *
     * @param string $businessCode The business code
     *
     * @return self
     */
    public function setBusinessCode(string $businessCode): self {
        $this->config->setBusinessCode($businessCode);

        return $this;
    }

    /**
     * Set the pass key.
     *
     * @param string $passKey The pass key
     *
     * @return self
     */
    public function setPassKey(string $passKey): self {
        $this->config->setPassKey($passKey);

        return $this;
    }

    /**
     * Get STK push service.
     *
     * @return StkService
     */
    public function stk(): StkService {
        return new StkService($this->config, $this->client);
    }

    /**
     * Get C2B service.
     *
     * @return CustomerToBusinessService
     */
    public function customerToBusiness(): CustomerToBusinessService {
        return new CustomerToBusinessService($this->config, $this->client);
    }

    /**
     * Get B2C service.
     *
     * @return BusinessToCustomerService
     */
    public function businessToCustomer(): BusinessToCustomerService {
        return new BusinessToCustomerService($this->config, $this->client);
    }

    /**
     * Get account balance service.
     *
     * @return AccountBalanceService
     */
    public function accountBalance(): AccountBalanceService {
        return new AccountBalanceService($this->config, $this->client);
    }

    /**
     * Get transaction status service.
     *
     * @return TransactionStatusService
     */
    public function transactionStatus(): TransactionStatusService {
        return new TransactionStatusService($this->config, $this->client);
    }

    /**
     * Get reversal service.
     *
     * @return ReversalService
     */
    public function reversal(): ReversalService {
        return new ReversalService($this->config, $this->client);
    }
}
