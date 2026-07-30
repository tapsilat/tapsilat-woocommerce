<?php
namespace Tapsilat\Models;

class BillingAddressDTO
{
    public $address;
    public $billing_type;
    public $citizenship;
    public $city;
    public $contact_name;
    public $contact_phone;
    public $country;
    public $district;
    public $tax_office;
    public $title;
    public $vat_number;
    public $zip_code;
    public $neighbourhood;
    public $street1;
    public $street2;
    public $street3;

    public function __construct(
        $address = null,
        $billing_type = null,
        $citizenship = null,
        $city = null,
        $contact_name = null,
        $contact_phone = null,
        $country = null,
        $district = null,
        $tax_office = null,
        $title = null,
        $vat_number = null,
        $zip_code = null,
        $neighbourhood = null,
        $street1 = null,
        $street2 = null,
        $street3 = null
    ) {
        $this->address = $address;
        $this->billing_type = $billing_type;
        $this->citizenship = $citizenship;
        $this->city = $city;
        $this->contact_name = $contact_name;
        $this->contact_phone = $contact_phone;
        $this->country = $country;
        $this->district = $district;
        $this->tax_office = $tax_office;
        $this->title = $title;
        $this->vat_number = $vat_number;
        $this->zip_code = $zip_code;
        $this->neighbourhood = $neighbourhood;
        $this->street1 = $street1;
        $this->street2 = $street2;
        $this->street3 = $street3;
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                if (is_object($value) && method_exists($value, 'toArray')) {
                    $result[$key] = $value->toArray();
                } elseif (is_array($value)) {
                    $result[$key] = array_map(function ($item) {
                        return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
                    }, $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}
