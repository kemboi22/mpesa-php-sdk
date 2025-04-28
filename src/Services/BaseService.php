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

    /**
     * Clean a phone number for API calls
     *
     * @param string $phone The phone number
     * @param string $countryCode The country code
     * @return string|array|null The cleaned phone number, or an empty string/array if the phone number is invalid
     */
    function cleanPhoneNumber(string $phone, string $countryCode = '254'): array|string|null
    {
        if (empty($phone)) {
            return '';
        }
        if($phone < 9) {
            return '';
        }

        $phone = trim($phone);
        if (str_starts_with($phone, '+')) {
            return '' . preg_replace('/\D/', '', substr($phone, 1));
        }
        if(str_starts_with($phone, '0')) {
            return $countryCode . preg_replace('/\D/', '', substr($phone, 1));
        }
        return preg_replace('/\D/', '', $phone);
    }

}