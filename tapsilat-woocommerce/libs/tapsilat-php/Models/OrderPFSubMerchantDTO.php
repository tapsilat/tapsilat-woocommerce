<?php
namespace Tapsilat\Models;

class OrderPFSubMerchantDTO
{
    public $mcc;
    public $name;
    public $org_id;
    public $address;
    public $city;
    public $country;
    public $country_iso_code;
    public $id;
    public $national_id;
    public $postal_code;
    public $submerchant_nin;
    public $submerchant_url;
    public $switch_id;
    public $terminal_no;

    public function __construct(
        $mcc = null,
        $name = null,
        $org_id = null,
        $address = null,
        $city = null,
        $country = null,
        $country_iso_code = null,
        $id = null,
        $national_id = null,
        $postal_code = null,
        $submerchant_nin = null,
        $submerchant_url = null,
        $switch_id = null,
        $terminal_no = null
    ) {
        $this->mcc = $mcc;
        $this->name = $name;
        $this->org_id = $org_id;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
        $this->country_iso_code = $country_iso_code;
        $this->id = $id;
        $this->national_id = $national_id;
        $this->postal_code = $postal_code;
        $this->submerchant_nin = $submerchant_nin;
        $this->submerchant_url = $submerchant_url;
        $this->switch_id = $switch_id;
        $this->terminal_no = $terminal_no;
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
