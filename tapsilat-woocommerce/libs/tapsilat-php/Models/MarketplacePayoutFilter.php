<?php

namespace Tapsilat\Models;

class MarketplacePayoutFilter extends Model
{
    public $order_id;
    public $submerchant_id;
    public $vpos_id;
    public $provider;
    public $status;
    public $release_status;
    public $approval_mode;
    /** Currency unit such as "TRY". */
    public $currency;
    public $eligibility;
    public $search;
    public $min_amount;
    public $max_amount;
    /** YYYY-MM-DD. */
    public $start_date;
    /** YYYY-MM-DD. */
    public $end_date;
    public $page;
    public $per_page;
}
