<?php
namespace Tapsilat\Models;

class SplitOrderItemPaymentRequest
{
    public $amount;
    public $order_id;
    public $order_item_payment_id;

    public function __construct($order_id, $order_item_payment_id, $amount)
    {
        $this->order_id = $order_id;
        $this->order_item_payment_id = $order_item_payment_id;
        $this->amount = $amount;
    }

    public function toArray()
    {
        return [
            'amount' => $this->amount,
            'order_id' => $this->order_id,
            'order_item_payment_id' => $this->order_item_payment_id,
        ];
    }
}
