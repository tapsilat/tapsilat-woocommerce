<?php
namespace Tapsilat\Models;

class RemoveBasketItemRequest
{
    public $order_reference_id;
    public $basket_item_id;

    public function __construct(
        $order_reference_id,
        $basket_item_id) {
        $this->order_reference_id = $order_reference_id;
        $this->basket_item_id = $basket_item_id;
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
