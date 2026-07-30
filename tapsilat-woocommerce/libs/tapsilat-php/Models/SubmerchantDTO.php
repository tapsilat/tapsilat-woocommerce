<?php
namespace Tapsilat\Models;

class SubmerchantDTO
{
    public $amount;
    public $merchant_reference_id;
    public $order_basket_item_id;

    public function __construct(
        $amount = null,
        $merchant_reference_id = null,
        $order_basket_item_id = null
    ) {
        $this->amount = $amount;
        $this->merchant_reference_id = $merchant_reference_id;
        $this->order_basket_item_id = $order_basket_item_id;
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
