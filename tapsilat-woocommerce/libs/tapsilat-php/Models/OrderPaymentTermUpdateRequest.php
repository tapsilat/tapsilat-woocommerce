<?php
namespace Tapsilat\Models;

class OrderPaymentTermUpdateRequest
{
    public $term_reference_id;
    public $amount;
    public $due_date;
    public $paid_date;
    public $required;
    public $status;
    public $term_sequence;

    public function __construct(
        $amount,
        $due_date,
        $required,
        $status,
        $term_reference_id,
        $term_sequence,
        $paid_date = null
    ) {
        $this->amount = $amount;
        $this->due_date = $due_date;
        $this->paid_date = $paid_date;
        $this->required = $required;
        $this->status = $status;
        $this->term_reference_id = $term_reference_id;
        $this->term_sequence = $term_sequence;
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
