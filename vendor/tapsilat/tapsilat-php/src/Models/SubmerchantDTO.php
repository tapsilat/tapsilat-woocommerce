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
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
