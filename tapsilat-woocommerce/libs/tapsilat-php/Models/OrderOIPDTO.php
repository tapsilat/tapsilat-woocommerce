<?php
namespace Tapsilat\Models;

class OrderOIPDTO
{
    public $paid_date;
    public $wallet_id;
    public $amount;
    public $type;
    public $basket_item_id;
    public $order_id;
    public $order_item_id;

    public function __construct($amount, $type, $basket_item_id = null, $order_id = null, $order_item_id = null, $paid_date = null, $wallet_id = null)
    {
        $this->paid_date = $paid_date;
        $this->wallet_id = $wallet_id;
        $this->amount = $amount;
        $this->type = $type;
        $this->basket_item_id = $basket_item_id;
        $this->order_id = $order_id;
        $this->order_item_id = $order_item_id;
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
