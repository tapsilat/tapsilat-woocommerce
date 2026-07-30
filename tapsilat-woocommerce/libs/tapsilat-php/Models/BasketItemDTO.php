<?php
namespace Tapsilat\Models;

class BasketItemDTO
{
    public $status;
    public $item_payments;
    public $paidable_amount;
    public $refunded_amount;
    public $refundable_amount;
    public $category1;
    public $category2;
    public $commission_amount;
    public $coupon;
    public $coupon_discount;
    public $data;
    public $id;
    public $item_type;
    public $mcc;
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
        $coupon = null,
        $coupon_discount = null,
        $data = null,
        $id = null,
        $item_type = null,
        $name = null,
        $paid_amount = null,
        $price = null,
        $commission_amount = null,
        $mcc = null,
        ?BasketItemPayerDTO $payer = null,
        $quantity = null,
        $quantity_float = null,
        $quantity_unit = null,
        $sub_merchant_key = null,
        $sub_merchant_price = null, $status = null, $item_payments = null, $paidable_amount = null, $refunded_amount = null, $refundable_amount = null
    ) {
        $this->status = $status;
        $this->item_payments = $item_payments;
        $this->paidable_amount = $paidable_amount;
        $this->refunded_amount = $refunded_amount;
        $this->refundable_amount = $refundable_amount;
        $this->category1 = $category1;
        $this->category2 = $category2;
        $this->coupon = $coupon;
        $this->coupon_discount = $coupon_discount;
        $this->data = $data;
        $this->id = $id;
        $this->item_type = $item_type;
        $this->name = $name;
        $this->paid_amount = $paid_amount;
        $this->price = $price;
        $this->commission_amount = $commission_amount;
        $this->mcc = $mcc;
        $this->payer = $payer;
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
