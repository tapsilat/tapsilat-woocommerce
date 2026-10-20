<?php

namespace Tapsilat\Models;

/**
 * Records an item business event for marketplace payouts.
 */
class SubmerchantPayoutEventRequest extends Model
{
    public $idempotency_key;
    public $order_reference;
    public $item_id;
    public $event_type;
    /** RFC3339 timestamp; the server uses the current time when it is omitted. */
    public $occurred_at;
    /** Free-form event data as an associative array. */
    public $payload;
}
