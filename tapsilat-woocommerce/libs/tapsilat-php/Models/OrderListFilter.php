<?php

namespace Tapsilat\Models;

/**
 * Selects orders for TapsilatAPI::getOrderListWithFilter().
 */
class OrderListFilter extends Model
{
    public $page;
    /** At most 100. */
    public $per_page;
    public $start_date;
    public $end_date;
    public $organization_id;
    public $conversation_id;
    public $related_reference_id;
    public $buyer_id;
    /** Numeric order status. */
    public $status;
}
