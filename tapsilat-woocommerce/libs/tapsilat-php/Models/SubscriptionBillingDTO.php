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
