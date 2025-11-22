<?php
namespace Tapsilat\Models;

class ShippingAddressDTO
{
    public $address;
    public $city;
    public $contact_name;
    public $country;
    public $shipping_date;
    public $tracking_code;
    public $zip_code;

    public function __construct(
        $address = null,
        $city = null,
        $contact_name = null,
        $country = null,
        $shipping_date = null,
        $tracking_code = null,
        $zip_code = null
    ) {
        $this->address = $address;
        $this->city = $city;
        $this->contact_name = $contact_name;
        $this->country = $country;
        $this->shipping_date = $shipping_date;
        $this->tracking_code = $tracking_code;
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
