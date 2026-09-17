<?php

namespace Kemboielvis\MpesaSdkPhp\Services;

/**
 * B2C Account Top Up service.
 *
 * Loads funds from your MMF/Working account into a B2C shortcode's
 * utility account for disbursement. The initiator needs the
 * "Org Business Pay to Bulk API initiator" role.
 */
class B2CAccountTopUpService extends AbstractB2BPaymentService
{
    protected function getCommandId(): string
    {
        return 'BusinessPayToBulk';
    }

    /**
     * Load funds into the B2C shortcode via the M-Pesa API.
     *
     * @return $this
     *
     * @throws \InvalidArgumentException If required parameters are missing
     */
    public function topUp(): self
    {
        return $this->sendPayment('B2C account top up');
    }
}
