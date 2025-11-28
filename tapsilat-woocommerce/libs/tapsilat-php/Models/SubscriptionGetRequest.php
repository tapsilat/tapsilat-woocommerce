<?php
namespace Tapsilat\Models;

class SubscriptionGetRequest
{
    public $reference_id;
    public $external_reference_id;

    public function __construct($reference_id = null, $external_reference_id = null)
    {
        $this->reference_id = $reference_id;
        $this->external_reference_id = $external_reference_id;
    }

    public function toArray()
    {
        return array_filter([
            'reference_id' => $this->reference_id,
            'external_reference_id' => $this->external_reference_id,
        ], function($value) {
            return $value !== null;
        });
    }
}
