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
        return array_filter([
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'identity_number' => $this->identity_number,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'zip_code' => $this->zip_code,
        ], function($value) {
            return $value !== null;
        });
    }
}
