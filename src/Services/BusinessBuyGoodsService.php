<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Business Buy Goods service.
 *
 * Pays a till number, merchant store or merchant HO from your business
 * account, for yourself or on behalf of a customer. Money moves to the
 * recipient's merchant account.
 */
class BusinessBuyGoodsService extends AbstractB2BPaymentService
{
    protected bool $requiresAccountReference = true;

    protected function getCommandId(): string
    {
        return 'BusinessBuyGoods';
    }

    /**
     * Pay the merchant via the M-Pesa API.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing or invalid
     */
    public function pay(): self
    {
        return $this->sendPayment('Goods payment');
    }
}
