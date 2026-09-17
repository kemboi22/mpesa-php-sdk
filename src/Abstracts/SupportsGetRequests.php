<?php

namespace Kemboielvis\MpesaSdkPhp\Abstracts;

/**
 * Implemented by API clients that can send GET requests.
 *
 * Kept separate from MpesaInterface so existing implementations of that
 * interface keep working.
 */
interface SupportsGetRequests
{
    /**
     * Execute a GET request.
     *
     * @param string               $endpoint The API endpoint
     * @param array<string, mixed> $query    Query string parameters
     *
     * @return mixed The API response
     */
    public function executeGetRequest(string $endpoint, array $query = []): mixed;
}
