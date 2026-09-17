<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaConfig;
use Kemboielvis\MpesaSdkPhp\Abstracts\MpesaInterface;

/**
 * Abstract base service class.
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
     * Generate a timestamp in the required format.
     *
     * @return string The timestamp
     */
    protected function generateTimestamp(): string
    {
        return date('YmdHis');
    }

    /**
     * Generate a password for secure API calls.
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
     * Generate a random UUID v4.
     *
     * @return string The UUID
     */
    protected function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    /**
     * Clean a phone number for API calls.
     *
     * @param string $phone       The phone number
     * @param string $countryCode The country code
     *
     * @return string The cleaned phone number, or an empty string/array if the phone number is invalid
     *
     * @throws \Exception
     */
    public function cleanPhoneNumber(string $phone, string $countryCode = '254'): string
    {
        if (empty($phone)) {
            throw new \RuntimeException('Phone number cannot be null!');
        }

        $phone = trim($phone);

        // Check if phone length is too short
        if (strlen($phone) < 9) {
            throw new \RuntimeException('Phone number is too short!');
        }

        if (str_starts_with($phone, '+')) {
            // Remove the '+' and keep digits
            return preg_replace('/\D/', '', substr($phone, 1));
        }

        if (str_starts_with($phone, '0')) {
            // Replace leading 0 with country code
            return $countryCode . preg_replace('/\D/', '', substr($phone, 1));
        }

        // Otherwise, just clean to digits
        return preg_replace('/\D/', '', $phone);
    }
}
