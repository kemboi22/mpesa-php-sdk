<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Base class for other services (simplified implementations).
 */
abstract class AbstractService extends BaseService
{
    protected ?object $response = null;

    /**
     * Get the response.
     *
     * @return object|null The response
     */
    public function getResponse(): ?object
    {
        return $this->response;
    }
}
