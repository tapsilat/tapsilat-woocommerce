<?php

namespace Tapsilat\Concerns;

use Tapsilat\Models\SubmerchantPaymentAction;
use Tapsilat\Models\SubmerchantPaymentItemUpdate;
use Tapsilat\Models\SubmerchantPayoutEventRequest;

/**
 * Submerchant settlement approval, payment item changes and payout events.
 */
trait SubmerchantSettlementMethods
{
    /**
     * Approves settlement of an item payment to its submerchant.
     */
    public function approveSubmerchantPayment(SubmerchantPaymentAction $request)
    {
        return $this->makeRequest('POST', '/submerchants/payment/approve', null, $request->toArray());
    }

    /**
     * Withdraws settlement approval of an item payment.
     */
    public function disapproveSubmerchantPayment(SubmerchantPaymentAction $request)
    {
        return $this->makeRequest('POST', '/submerchants/payment/disapprove', null, $request->toArray());
    }

    /**
     * Changes the seller or payout of a completed marketplace item.
     */
    public function updateSubmerchantPaymentItem(SubmerchantPaymentItemUpdate $request)
    {
        return $this->makeRequest('PUT', '/submerchants/payment/item', null, $request->toArray());
    }

    /**
     * Records an item business event; the server answers 202 Accepted. Time gates, approval
     * and settlement remain separate.
     */
    public function recordSubmerchantPayoutEvent(SubmerchantPayoutEventRequest $request)
    {
        return $this->makeRequest('POST', '/submerchant-payout-events', null, $request->toArray());
    }
}
