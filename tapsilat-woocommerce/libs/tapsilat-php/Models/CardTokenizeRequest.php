<?php

namespace Tapsilat\Models;

/**
 * Tokenizes a card for later charges.
 */
class CardTokenizeRequest extends Model
{
    public $card_number;
    public $holder_name;
    public $expiry_month;
    /** Two or four digits. */
    public $expiry_year;
    public $cvv;
    public $name;
    public $default;
    /** 1 = 3D secure, 2 = half secure, 3 = non-secure. */
    public $mode;
    public $user_id;
    public $user_email;
    public $redirect_success_url;
    public $redirect_failure_url;
}
