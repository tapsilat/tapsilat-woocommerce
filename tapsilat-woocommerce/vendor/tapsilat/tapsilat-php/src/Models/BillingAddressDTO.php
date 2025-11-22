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
        $zip_code = null
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
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
