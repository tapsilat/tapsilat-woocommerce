<?php
namespace Tapsilat\Models;

class RefundOrderDTO
{
    public $amount;
    public $reference_id;
    public $order_item_id;
    public $order_item_payment_id;

    public function __construct(
        $amount,
        $reference_id,
        $order_item_id = null,
        $order_item_payment_id = null
    ) {
        $this->amount = $amount;
        $this->reference_id = $reference_id;
        $this->order_item_id = $order_item_id;
        $this->order_item_payment_id = $order_item_payment_id;
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
