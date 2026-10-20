<?php

namespace Tapsilat\Models;

/**
 * Requests settlement approval or disapproval for an item payment. Every field is required.
 */
class SubmerchantPaymentAction extends Model
{
    public $locale;
    public $conversation_id;
    public $payment_transaction_id;
    public $acquirer;
}
