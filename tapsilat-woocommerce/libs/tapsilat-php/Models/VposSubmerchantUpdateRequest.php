<?php

namespace Tapsilat\Models;

class VposSubmerchantUpdateRequest extends Model
{
    public $external_reference_id;
    public $submerchant_id;
    public $terminal_no;
    public $mcc;
    public $tax_id;
    public $national_id;
    public $title;
    public $switch_id;
}
