<?php
namespace Tapsilat\Models;

class SubscriptionRedirectRequest
{
    public $subscription_id;

    public function __construct($subscription_id = null)
    {
        $this->subscription_id = $subscription_id;
    }

    public function toArray()
    {
        return array_filter([
            'subscription_id' => $this->subscription_id,
        ], function($value) {
            return $value !== null;
        });
    }
}
