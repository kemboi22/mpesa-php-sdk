<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

use Kemboielvis\MpesaSdkPhp\Abstracts\SupportsGetRequests;

/**
 * Mobile Data Bundles (Dynamic Offers) service.
 *
 * Lets customers browse and buy Safaricom data bundles inside your app:
 * fetch the offers for a number, purchase one, then check its status.
 */
class MobileDataBundlesService extends AbstractService
{
    public const PAYMENT_MODE_AIRTIME = 'airtime';

    public const PAYMENT_MODE_MPESA = 'm-pesa';

    /**
     * serviceAccountId for dynamic offers when checking status.
     */
    public const DYNAMIC_OFFERS_SERVICE_ACCOUNT_ID = '0';

    private string $phoneNumber = '';

    private string $offeringId = '';

    private string $accountId = '';

    private string $price = '';

    private string $resourceAmount = '';

    private string $validity = '';

    private string $transactionId = '';

    private string $sentTransactionId = '';

    private string $paymentMode = '';

    /**
     * Set the customer's phone number.
     *
     * @param string $phoneNumber The phone number
     *
     * @return self
     *
     * @throws \Exception
     */
    public function setPhoneNumber(string $phoneNumber): self
    {
        $this->phoneNumber = $this->cleanPhoneNumber($phoneNumber);

        return $this;
    }

    /**
     * Set the product's unique number (offeringId from fetchOffers()).
     *
     * @param int|string $offeringId The offering ID
     *
     * @return self
     */
    public function setOfferingId($offeringId): self
    {
        $this->offeringId = (string)$offeringId;

        return $this;
    }

    /**
     * Set the resource account ID (resourceAccId from fetchOffers()).
     *
     * @param int|string $accountId The account ID
     *
     * @return self
     */
    public function setAccountId($accountId): self
    {
        $this->accountId = (string)$accountId;

        return $this;
    }

    /**
     * Set the product price (offerPrice from fetchOffers()).
     *
     * @param int|string $price The price
     *
     * @return self
     */
    public function setPrice($price): self
    {
        $this->price = (string)$price;

        return $this;
    }

    /**
     * Set the amount of data awarded in MB (resourceValue from fetchOffers()).
     *
     * @param int|string $resourceAmount The resource amount
     *
     * @return self
     */
    public function setResourceAmount($resourceAmount): self
    {
        $this->resourceAmount = (string)$resourceAmount;

        return $this;
    }

    /**
     * Set the product validity (offerValidity from fetchOffers()).
     *
     * @param int|string $validity The validity in days/hours
     *
     * @return self
     */
    public function setValidity($validity): self
    {
        $this->validity = (string)$validity;

        return $this;
    }

    /**
     * Fill the purchase details from an offer returned by fetchOffers().
     *
     * @param object|array $offer An offer (or child offer)
     *
     * @return self
     */
    public function setOffer($offer): self
    {
        $offer = (object)$offer;

        if (isset($offer->offeringId)) {
            $this->setOfferingId($offer->offeringId);
        }
        if (isset($offer->resourceAccId)) {
            $this->setAccountId($offer->resourceAccId);
        }
        if (isset($offer->offerPrice)) {
            $this->setPrice($offer->offerPrice);
        }
        if (isset($offer->resourceValue)) {
            $this->setResourceAmount($offer->resourceValue);
        }
        if (isset($offer->offerValidity)) {
            $this->setValidity($offer->offerValidity);
        }

        return $this;
    }

    /**
     * Set the transaction ID for the next purchase; used later to check its status.
     * When not set, a new numeric ID is generated for every purchase.
     *
     * @param int|string $transactionId The transaction ID
     *
     * @return self
     */
    public function setTransactionId($transactionId): self
    {
        $this->transactionId = (string)$transactionId;

        return $this;
    }

    /**
     * Get the transaction ID sent with the last purchase.
     *
     * @return string The transaction ID
     */
    public function getTransactionId(): string
    {
        return $this->sentTransactionId;
    }

    /**
     * Set how the customer pays.
     *
     * @param string $paymentMode PAYMENT_MODE_AIRTIME or PAYMENT_MODE_MPESA
     *
     * @return self
     */
    public function setPaymentMode(string $paymentMode): self
    {
        $this->paymentMode = $paymentMode;

        return $this;
    }

    /**
     * Get a client that can send GET requests.
     *
     * @return SupportsGetRequests
     *
     * @throws \RuntimeException If the configured client cannot send GET requests
     */
    private function getClient(): SupportsGetRequests
    {
        if (! $this->client instanceof SupportsGetRequests) {
            throw new \RuntimeException(
                'Mobile Data Bundles needs an API client that implements ' . SupportsGetRequests::class
            );
        }

        return $this->client;
    }

    /**
     * Generate a numeric transaction ID.
     *
     * @return string The transaction ID
     */
    private function generateTransactionId(): string
    {
        return date('YmdHis') . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    /**
     * Fetch the data offers available to the customer.
     *
     * @param string|null $phoneNumber Optional phone number (otherwise use setPhoneNumber())
     *
     * @return self
     *
     * @throws \InvalidArgumentException If the phone number is missing
     */
    public function fetchOffers(?string $phoneNumber = null): self
    {
        if (null !== $phoneNumber) {
            $this->setPhoneNumber($phoneNumber);
        }

        if (empty($this->phoneNumber)) {
            throw new \InvalidArgumentException('Phone number is required');
        }

        $this->response = $this->getClient()->executeGetRequest(
            '/v1/dynamic-offers/fetch',
            ['msisdn' => $this->phoneNumber]
        );

        return $this;
    }

    /**
     * Get the offers from the last fetchOffers() call.
     *
     * @return array<int, object> Offers (offerName, offeringId, offerPrice, resourceValue,
     *                            offerValidity, resourceAccId, childOffers, ...)
     */
    public function getOffers(): array
    {
        $offers = $this->response->lineItem->characteristicsValue ?? [];

        return is_array($offers) ? $offers : [];
    }

    /**
     * Purchase a data bundle for the customer.
     *
     * @return self
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function purchase(): self
    {
        $required = [
            'Phone number' => $this->phoneNumber,
            'Offering ID' => $this->offeringId,
            'Account ID' => $this->accountId,
            'Price' => $this->price,
            'Resource amount' => $this->resourceAmount,
            'Validity' => $this->validity,
            'Payment mode' => $this->paymentMode,
        ];
        foreach ($required as $name => $value) {
            if ('' === $value) {
                throw new \InvalidArgumentException("$name is required");
            }
        }

        $this->sentTransactionId = $this->transactionId ?: $this->generateTransactionId();

        $data = [
            'offeringId' => $this->offeringId,
            'accountId' => $this->accountId,
            'price' => $this->price,
            'resourceAmount' => $this->resourceAmount,
            'validity' => $this->validity,
            'msisdn' => $this->phoneNumber,
            'transactionId' => $this->sentTransactionId,
            'paymentMode' => $this->paymentMode,
        ];

        $this->response = $this->client->executeRequest($data, '/v1/dynamic-offers/facebook-bundle/purchase');

        return $this;
    }

    /**
     * Whether the last purchase succeeded (header.responseCode 200).
     *
     * @return bool
     */
    public function isPurchaseSuccessful(): bool
    {
        return isset($this->response->header->responseCode)
            && 200 === (int)$this->response->header->responseCode;
    }

    /**
     * Check the status of a purchase.
     *
     * @param string|null $transactionId    Defaults to the ID of the last purchase
     * @param string      $serviceAccountId Use "0" for dynamic offers
     *
     * @return self
     *
     * @throws \InvalidArgumentException If no transaction ID is available
     */
    public function checkStatus(
        ?string $transactionId = null,
        string $serviceAccountId = self::DYNAMIC_OFFERS_SERVICE_ACCOUNT_ID
    ): self {
        $id = $transactionId ?? $this->sentTransactionId;

        if (empty($id)) {
            throw new \InvalidArgumentException('Transaction ID is required');
        }

        $this->response = $this->getClient()->executeGetRequest(
            '/v2/bundles/get/status',
            ['id' => $id, 'serviceAccountId' => $serviceAccountId]
        );

        return $this;
    }
}
