<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * Business Pay Bill service.
 *
 * Pays a paybill (or paybill store) from your business account,
 * for yourself or on behalf of a customer. The initiator needs the
 * "Org Business Pay Bill API initiator" role.
 */
class BusinessPayBillService extends AbstractB2BPaymentService
{
    protected bool $requiresAccountReference = true;

    protected function getCommandId(): string
    {
        return 'BusinessPayBill';
    }

    /**
     * Pay the bill via the M-Pesa API.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing or invalid
     */
    public function pay(): self
    {
        return $this->sendPayment('Bill payment');
    }
}
