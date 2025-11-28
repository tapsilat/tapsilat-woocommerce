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
        $currency = null,
        $title = null,
        $period = null,
        $cycle = null,
        $payment_date = null,
        $external_reference_id = null,
        $success_url = null,
        $failure_url = null,
        $card_id = null,
        $billing = null,
        $user = null
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->title = $title;
        $this->period = $period;
        $this->cycle = $cycle;
        $this->payment_date = $payment_date;
        $this->external_reference_id = $external_reference_id;
        $this->success_url = $success_url;
        $this->failure_url = $failure_url;
        $this->card_id = $card_id;
        $this->billing = $billing;
        $this->user = $user;
    }

    public function toArray()
    {
        $data = array_filter([
            'amount' => $this->amount,
            'currency' => $this->currency,
            'title' => $this->title,
            'period' => $this->period,
            'cycle' => $this->cycle,
            'payment_date' => $this->payment_date,
            'external_reference_id' => $this->external_reference_id,
            'success_url' => $this->success_url,
            'failure_url' => $this->failure_url,
            'card_id' => $this->card_id,
        ], function($value) {
            return $value !== null;
        });

        if ($this->billing instanceof SubscriptionBillingDTO) {
            $data['billing'] = $this->billing->toArray();
        }

        if ($this->user instanceof SubscriptionUserDTO) {
            $data['user'] = $this->user->toArray();
        }

        return $data;
    }
}
