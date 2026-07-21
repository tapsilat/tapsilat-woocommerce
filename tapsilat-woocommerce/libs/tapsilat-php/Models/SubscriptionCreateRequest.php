<?php
namespace Tapsilat\Models;

class SubscriptionCreateRequest
{
    public $amount;
    public $currency;
    public $title;
    public $period;
    public $cycle;
    public $payment_date;
    public $external_reference_id;
    public $success_url;
    public $failure_url;
    public $card_id;
    public $billing;
    public $user;

    public function __construct(
        $amount = null,
        ?SubscriptionBillingDTO $billing = null,
        $card_id = null,
        $currency = null,
        $cycle = null,
        $external_reference_id = null,
        $failure_url = null,
        $payment_date = null,
        $period = null,
        $success_url = null,
        $title = null,
        ?SubscriptionUserDTO $user = null
    ) {
        $this->amount = $amount;
        $this->billing = $billing;
        $this->card_id = $card_id;
        $this->currency = $currency;
        $this->cycle = $cycle;
        $this->external_reference_id = $external_reference_id;
        $this->failure_url = $failure_url;
        $this->payment_date = $payment_date;
        $this->period = $period;
        $this->success_url = $success_url;
        $this->title = $title;
        $this->user = $user;
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
