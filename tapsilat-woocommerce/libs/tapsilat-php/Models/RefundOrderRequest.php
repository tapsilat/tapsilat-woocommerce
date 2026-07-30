<?php
namespace Tapsilat\Models;

class RefundOrderRequest
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
