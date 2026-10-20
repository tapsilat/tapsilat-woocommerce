<?php

namespace Tapsilat\Models;

/**
 * Creates a marketplace seller. Without vpos_id or provider_accounts only the seller is created.
 */
class MarketplaceSubmerchantCreateRequest extends Model
{
    /** MarketplaceAccountTarget list; an empty list creates only the seller. Never combine with vpos_id. */
    public $provider_accounts;
    public $locale;
    public $conversation_id;
    public $name;
    public $email;
    public $gsm_number;
    public $address;
    public $iban;
    public $tax_office;
    public $legal_company_title;
    /** Currency UUID or unit such as "TRY". */
    public $currency_id;
    public $sub_merchant_external_id;
    public $identity_number;
    public $sub_merchant_type;
    public $tax_number;
    public $labels;
    public $status;
    public $system_time;
    public $contact_name;
    public $contact_surname;
    /** "auto" or "manual". */
    public $approval_mode;
    public $release_policy_id;
    public $idempotency_key;
    /** Targets only this account, using its configured environment. */
    public $vpos_id;
}
