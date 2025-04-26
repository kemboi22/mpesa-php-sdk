<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaConfig;
use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaInterface;

/**
 * Abstract base service class
 */
abstract class BaseService
{
    protected MpesaConfig $config;
    protected MpesaInterface $client;

    public function __construct(MpesaConfig $config, MpesaInterface $client)
    {
        $this->config = $config;
        $this->client = $client;
    }

    /**
     * Generate a timestamp in the required format
     *
     * @return string The timestamp
     */
    protected function generateTimestamp(): string
    {
        return date('YmdHis');
    }

    /**
     * Generate a password for secure API calls
     *
     * @return string The password
     */
    protected function generatePassword(): string
    {
        return base64_encode(
            $this->config->getBusinessCode() .
            $this->config->getPassKey() .
            $this->generateTimestamp()
        );
    }
}

/**
 * Business to Customer service
 */
class BusinessToCustomerService extends AbstractService
{
    // Implementation details would go here
}

/**
 * Account Balance service
 */
class AccountBalanceService extends AbstractService
{
    // Implementation details would go here
}

/**
 * Transaction Status service
 */
class TransactionStatusService extends AbstractService
{
    // Implementation details would go here
}

/**
 * Reversal service
 */
class ReversalService extends AbstractService
{
    // Implementation details would go here
}