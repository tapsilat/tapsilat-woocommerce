<?php
namespace Tapsilat\Models;

class SubscriptionUserDTO
{
    public $id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $identity_number;
    public $address;
    public $city;
    public $country;
    public $zip_code;

    public function __construct(
        $id = null,
        $first_name = null,
        $last_name = null,
        $email = null,
        $phone = null,
        $identity_number = null,
        $address = null,
        $city = null,
        $country = null,
        $zip_code = null
    ) {
        $this->id = $id;
        $this->first_name = $first_name;
        $this->last_name = $last_name;
        $this->email = $email;
        $this->phone = $phone;
        $this->identity_number = $identity_number;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
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
