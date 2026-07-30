<?php
namespace Tapsilat\Models;

class OrderTermRefundRequest
{
    public $term_id;
    public $amount;
    public $reference_id;
    public $term_payment_id;

    public function __construct(
        $term_id,
        $amount,
        $reference_id = null,
        $term_payment_id = null
    ) {
        $this->term_id = $term_id;
        $this->amount = $amount;
        $this->reference_id = $reference_id;
        $this->term_payment_id = $term_payment_id;
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
