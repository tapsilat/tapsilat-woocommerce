<?php

namespace Tapsilat\Models;

/**
 * Selects installment options for a card BIN.
 */
class CardInstallmentsRequest extends Model
{
    /** The card's first digits (at least 8). */
    public $bin;
    public $amount;
    /** Currency unit or name, for example "TRY". */
    public $currency;
    public $is_three_d;
}
