<?php

namespace Tapsilat\Models;

class VposSubmerchantCreateRequest extends Model
{
    public $external_reference_id;
    public $submerchant_id;
    public $terminal_no;
    public $vpos_id;
    public $mcc;
    public $tax_id;
    public $national_id;
    public $title;
    public $switch_id;
    public $city;
    public $country;
    public $country_isocode;
    public $postal_code;
    public $address;
    public $submerchant_url;
    public $submerchant_nin;
}
