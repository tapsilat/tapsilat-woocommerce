<?php
namespace Tapsilat\Models;

class SubOrganizationDTO
{
    public $acquirer;
    public $address;
    public $contact_first_name;
    public $contact_last_name;
    public $currency;
    public $email;
    public $gsm_number;
    public $iban;
    public $identity_number;
    public $legal_company_title;
    public $organization_name;
    public $sub_merchant_external_id;
    public $sub_merchant_key;
    public $sub_merchant_type;
    public $tax_number;
    public $tax_office;

    public function __construct(
        $acquirer = null,
        $address = null,
        $contact_first_name = null,
        $contact_last_name = null,
        $currency = null,
        $email = null,
        $gsm_number = null,
        $iban = null,
        $identity_number = null,
        $legal_company_title = null,
        $organization_name = null,
        $sub_merchant_external_id = null,
        $sub_merchant_key = null,
        $sub_merchant_type = null,
        $tax_number = null,
        $tax_office = null
    ) {
        $this->acquirer = $acquirer;
        $this->address = $address;
        $this->contact_first_name = $contact_first_name;
        $this->contact_last_name = $contact_last_name;
        $this->currency = $currency;
        $this->email = $email;
        $this->gsm_number = $gsm_number;
        $this->iban = $iban;
        $this->identity_number = $identity_number;
        $this->legal_company_title = $legal_company_title;
        $this->organization_name = $organization_name;
        $this->sub_merchant_external_id = $sub_merchant_external_id;
        $this->sub_merchant_key = $sub_merchant_key;
        $this->sub_merchant_type = $sub_merchant_type;
        $this->tax_number = $tax_number;
        $this->tax_office = $tax_office;
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
