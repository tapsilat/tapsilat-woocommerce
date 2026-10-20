<?php

namespace Tapsilat\Models;

class MarketplaceContractFilter extends Model
{
    /** Currency UUID or unit such as "TRY". */
    public $currency_id;
    public $seller_type;
    /** Defaults to "create". */
    public $operation;
    /** List of VPOS IDs. */
    public $vpos_ids;
}
