<?php
namespace Tapsilat\Models;

class OrderPaymentTermCreateRequest
{
    public $order_id;
    public $term_reference_id;
    public $amount;
    public $due_date;
    public $term_sequence;
    public $required;
    public $status;
    public $data;
    public $paid_date;

    public function __construct(
        $order_id,
        $term_reference_id,
        $amount,
        $due_date,
        $term_sequence,
        $required,
        $status,
        $data = null,
        $paid_date = null
    ) {
        $this->order_id = $order_id;
        $this->term_reference_id = $term_reference_id;
        $this->amount = $amount;
        $this->due_date = $due_date;
        $this->term_sequence = $term_sequence;
        $this->required = $required;
        $this->status = $status;
        $this->data = $data;
        $this->paid_date = $paid_date;
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
