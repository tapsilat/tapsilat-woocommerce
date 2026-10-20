<?php

namespace Tapsilat\Models;

/**
 * Creates a VPOS. An update replaces the whole configuration (fields left out are stored as
 * empty or false), so read the VPOS first and send every field back.
 */
class VposCreateRequest extends Model
{
    public $name;
    public $bank_name;
    public $env_mode;
    public $merchant;
    public $merchant_code;
    public $merchant_key;
    public $auth_key;
    public $terminal;
    public $company;
    public $username;
    public $password;
    public $store_key;
    public $api_key;
    public $api_secret;
    public $guid;
    public $pid;
    public $client_id;
    public $client_code;
    public $type;
    public $block_date;
    public $priority;
    public $main;
    public $force_three_d;
    public $marketplace;
    public $pf;
    public $payment_mode;
    public $prefix;
    public $acquirer_id;
    public $card_schemes;
    /** Currency UUIDs or units such as "TRY". */
    public $currencies;
    public $failover;
    public $pf_id;
    public $redirect_uri;
    public $tls;
    public $tls_key;
    public $tls_cert;
    public $root_ca;
    public $main_installment;
    public $main_without_installment;
}
