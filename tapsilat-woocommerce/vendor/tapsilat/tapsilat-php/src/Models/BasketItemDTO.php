<?php
namespace Tapsilat\Models;

class BasketItemDTO
{
    public $category1;
    public $category2;
    public $commission_amount;
    public $coupon;
    public $coupon_discount;
    public $data;
    public $id;
    public $item_type;
    public $name;
    public $paid_amount;
    public $payer;
    public $price;
    public $quantity;
    public $quantity_float;
    public $quantity_unit;
    public $sub_merchant_key;
    public $sub_merchant_price;

    public function __construct(
        $category1 = null,
        $category2 = null,
        $commission_amount = null,
        $coupon = null,
        $coupon_discount = null,
        $data = null,
        $id = null,
        $item_type = null,
        $name = null,
        $paid_amount = null,
        BasketItemPayerDTO $payer = null,
        $price = null,
        $quantity = null,
        $quantity_float = null,
        $quantity_unit = null,
        $sub_merchant_key = null,
        $sub_merchant_price = null
    ) {
        $this->category1 = $category1;
        $this->category2 = $category2;
        $this->commission_amount = $commission_amount;
        $this->coupon = $coupon;
        $this->coupon_discount = $coupon_discount;
        $this->data = $data;
        $this->id = $id;
        $this->item_type = $item_type;
        $this->name = $name;
        $this->paid_amount = $paid_amount;
        $this->payer = $payer;
        $this->price = $price;
        $this->quantity = $quantity;
        $this->quantity_float = $quantity_float;
        $this->quantity_unit = $quantity_unit;
        $this->sub_merchant_key = $sub_merchant_key;
        $this->sub_merchant_price = $sub_merchant_price;
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                if ($value instanceof BasketItemPayerDTO) {
                    $result[$key] = $value->toArray();
                } else {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
    }
}
