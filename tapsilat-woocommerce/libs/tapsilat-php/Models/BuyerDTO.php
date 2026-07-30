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
    public $registration_date;
    public $title;
    public $zip_code;
    public $registration_address;
    public $income_type;
    public $education;
    public $occupation;

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
        $registration_date = null,
        $title = null,
        $zip_code = null,
        $registration_address = null,
        $income_type = null,
        $education = null,
        $occupation = null
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
        $this->registration_date = $registration_date;
        $this->title = $title;
        $this->zip_code = $zip_code;
        $this->registration_address = $registration_address;
        $this->income_type = $income_type;
        $this->education = $education;
        $this->occupation = $occupation;
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
