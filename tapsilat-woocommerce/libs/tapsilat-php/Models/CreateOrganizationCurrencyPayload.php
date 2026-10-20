<?php

namespace Tapsilat\Models;

/**
 * @category API Models
 * @summary Organization currency creation payload
 */
class CreateOrganizationCurrencyPayload
{
    /**
     * @var string
     */
    public $currency_code;

    public function __construct($currency_code)
    {
        $this->currency_code = $currency_code;
    }

    public function toArray()
    {
        return [
            'currency_code' => $this->currency_code
        ];
    }
}
