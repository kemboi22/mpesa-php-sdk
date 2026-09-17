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
     * Accepts "0712345678", "712345678", "+254 712 345 678", "254-712-345-678", ...
     * and returns digits with the country code, e.g. "254712345678".
     *
     * @param string $phone       The phone number
     * @param string $countryCode The country code
     *
     * @return string The cleaned phone number
     *
     * @throws \RuntimeException If the phone number is empty or invalid
     */
    public function cleanPhoneNumber(string $phone, string $countryCode = '254'): string
    {
        $phone = trim($phone);
        $digits = preg_replace('/\D/', '', $phone);

        if ('' === $digits) {
            throw new \RuntimeException('Phone number cannot be empty!');
        }

        if (str_starts_with($phone, '+')) {
            // Already in international format
            $number = $digits;
        } elseif (str_starts_with($digits, '0')) {
            // Replace leading 0 with country code
            $number = $countryCode . substr($digits, 1);
        } elseif (9 === strlen($digits)) {
            // Local number without the leading 0, e.g. 712345678
            $number = $countryCode . $digits;
        } else {
            $number = $digits;
        }

        if (strlen($number) < 10) {
            throw new \RuntimeException('Phone number is too short!');
        }

        if (strlen($number) > 15) {
            throw new \RuntimeException('Phone number is too long!');
        }

        return $number;
    }
}
