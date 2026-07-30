<?php
namespace Tapsilat\Models;

class OrgCreateBusinessRequest
{
    public $address;
    public $business_name;
    public $business_type;
    public $email;
    public $first_name;
    public $identity_number;
    public $last_name;
    public $phone;
    public $tax_number;
    public $tax_office;
    public $zip_code;

    public function __construct(
        $address,
        $business_name,
        $business_type,
        $email,
        $first_name,
        $identity_number,
        $last_name,
        $phone,
        $tax_number,
        $tax_office,
        $zip_code) {
        $this->address = $address;
        $this->business_name = $business_name;
        $this->business_type = $business_type;
        $this->email = $email;
        $this->first_name = $first_name;
        $this->identity_number = $identity_number;
        $this->last_name = $last_name;
        $this->phone = $phone;
        $this->tax_number = $tax_number;
        $this->tax_office = $tax_office;
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
