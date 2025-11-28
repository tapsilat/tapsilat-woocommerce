<?php
namespace Tapsilat\Models;

class SubscriptionBillingDTO
{
    public $address;
    public $city;
    public $contact_name;
    public $country;
    public $vat_number;
    public $zip_code;

    public function __construct(
        $address = null,
        $city = null,
        $contact_name = null,
        $country = null,
        $vat_number = null,
        $zip_code = null
    ) {
        $this->address = $address;
        $this->city = $city;
        $this->contact_name = $contact_name;
        $this->country = $country;
        $this->vat_number = $vat_number;
        $this->zip_code = $zip_code;
    }

    public function toArray()
    {
        return array_filter([
            'address' => $this->address,
            'city' => $this->city,
            'contact_name' => $this->contact_name,
            'country' => $this->country,
            'vat_number' => $this->vat_number,
            'zip_code' => $this->zip_code,
        ], function($value) {
            return $value !== null;
        });
    }
}
