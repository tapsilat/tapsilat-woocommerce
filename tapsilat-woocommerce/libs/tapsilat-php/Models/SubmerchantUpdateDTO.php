<?php
namespace Tapsilat\Models;

class SubmerchantUpdateDTO
{
    public $currency_id;
    public $status;
    public $tax_number;
    public $sub_merchant_key;
    public $locale;
    public $legal_company_title;
    public $system_time;
    public $organization_id;
    public $conversation_id;
    public $sub_merchant_external_id;
    public $address;
    public $city;
    public $country;
    public $email;
    public $gsm_number;
    public $iban;
    public $identity_number;
    public $name;
    public $contact_name;
    public $contact_surname;
    public $sub_merchant_type;
    public $tax_office;
    public $zip_code;
    public $district;
    public $iban_name;

    public function __construct(
        $address = null,
        $city = null,
        $country = null,
        $email = null,
        $gsm_number = null,
        $iban = null,
        $identity_number = null,
        $name = null,
        $contact_name = null,
        $contact_surname = null,
        $sub_merchant_type = null,
        $tax_office = null,
        $zip_code = null,
        $district = null,
        $iban_name = null, $currency_id = null, $status = null, $tax_number = null, $sub_merchant_key = null, $locale = null, $legal_company_title = null, $system_time = null, $organization_id = null, $conversation_id = null, $sub_merchant_external_id = null
    ) {
        $this->currency_id = $currency_id;
        $this->status = $status;
        $this->tax_number = $tax_number;
        $this->sub_merchant_key = $sub_merchant_key;
        $this->locale = $locale;
        $this->legal_company_title = $legal_company_title;
        $this->system_time = $system_time;
        $this->organization_id = $organization_id;
        $this->conversation_id = $conversation_id;
        $this->sub_merchant_external_id = $sub_merchant_external_id;
        $this->address = $address;
        $this->city = $city;
        $this->country = $country;
        $this->email = $email;
        $this->gsm_number = $gsm_number;
        $this->iban = $iban;
        $this->identity_number = $identity_number;
        $this->name = $name;
        $this->contact_name = $contact_name;
        $this->contact_surname = $contact_surname;
        $this->sub_merchant_type = $sub_merchant_type;
        $this->tax_office = $tax_office;
        $this->zip_code = $zip_code;
        $this->district = $district;
        $this->iban_name = $iban_name;
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
