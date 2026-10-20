<?php

namespace Tapsilat\Models;

/**
 * Selects the installment table for an amount.
 */
class InstallmentTableRequest extends Model
{
    public $amount;
    /** Currency unit or name, for example "TRY". */
    public $currency;
    public $is_three_d;
}
