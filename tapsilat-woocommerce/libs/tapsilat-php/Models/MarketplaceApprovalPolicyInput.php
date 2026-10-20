<?php

namespace Tapsilat\Models;

/**
 * Creates or replaces an approval rule.
 */
class MarketplaceApprovalPolicyInput extends Model
{
    public $name;
    public $priority;
    public $enabled;
    public $provider;
    public $min_amount;
    public $max_amount;
    /** Currency UUID or unit such as "TRY"; the SDK resolves a unit to its UUID. */
    public $currency_id;
    public $submerchant_ids;
    public $labels_any;
    /** "auto" or "manual". */
    public $mode;
}
