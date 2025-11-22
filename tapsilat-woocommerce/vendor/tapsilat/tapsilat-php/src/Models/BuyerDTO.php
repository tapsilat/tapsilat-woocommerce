<?php
namespace Tapsilat\Models;

class BuyerDTO
{
    public $name;
    public $surname;
    public $birth_date;
    public $city;
    public $country;
    public $email;
    public $gsm_number;
    public $id;
    public $identity_number;
    public $ip;
    public $last_login_date;
    public $registration_address;
    public $registration_date;
    public $title;
    public $zip_code;

    public function __construct(
        $name,
        $surname,
        $birth_date = null,
        $city = null,
        $country = null,
        $email = null,
        $gsm_number = null,
        $id = null,
        $identity_number = null,
        $ip = null,
        $last_login_date = null,
        $registration_address = null,
        $registration_date = null,
        $title = null,
        $zip_code = null
    ) {
        $this->name = $name;
        $this->surname = $surname;
        $this->birth_date = $birth_date;
        $this->city = $city;
        $this->country = $country;
        $this->email = $email;
        $this->gsm_number = $gsm_number;
        $this->id = $id;
        $this->identity_number = $identity_number;
        $this->ip = $ip;
        $this->last_login_date = $last_login_date;
        $this->registration_address = $registration_address;
        $this->registration_date = $registration_date;
        $this->title = $title;
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
