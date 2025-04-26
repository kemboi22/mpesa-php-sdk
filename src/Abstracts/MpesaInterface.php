<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * Interface for all M-Pesa API operations
 */
interface MpesaInterface
{
    /**
     * Execute an API request
     *
     * @param array $data The request payload
     * @param string $endpoint The API endpoint
     * @return mixed The API response
     */
    public function executeRequest(array $data, string $endpoint);
}

