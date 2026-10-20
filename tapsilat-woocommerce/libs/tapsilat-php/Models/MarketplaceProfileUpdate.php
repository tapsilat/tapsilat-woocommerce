<?php

namespace Tapsilat\Models;

/**
 * Replaces a marketplace seller profile at revision. Preserve the fields you are not changing.
 */
class MarketplaceProfileUpdate extends Model
{
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
    /** The seller's revision, exactly as received. */
    public $revision;
}
