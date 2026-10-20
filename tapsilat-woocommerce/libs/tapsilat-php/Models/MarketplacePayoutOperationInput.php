<?php

namespace Tapsilat\Models;

/**
 * Changes a payout's allocation or withdraws its approval.
 */
class MarketplacePayoutOperationInput extends Model
{
    public $kind;
    public $idempotency_key;
    /** The payout's revision, exactly as received. */
    public $revision;
    public $seller_reference;
    public $net_amount;
}
