<?php

namespace Tapsilat\Models;

/**
 * Changes the seller or payout of a completed marketplace item. Every field is required.
 */
class SubmerchantPaymentItemUpdate extends Model
{
    public $locale;
    public $conversation_id;
    public $payment_transaction_id;
    public $sub_merchant_key;
    /** Decimal string greater than zero, for example "60.25". */
    public $sub_merchant_price;
    public $acquirer;
}
