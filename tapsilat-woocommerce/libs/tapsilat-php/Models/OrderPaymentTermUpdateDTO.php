<?php
namespace Tapsilat\Models;

class OrderPaymentTermUpdateDTO
{
    public $term_reference_id;
    public $amount;
    public $due_date;
    public $paid_date;
    public $required;
    public $status;
    public $term_sequence;

    public function __construct(
        $term_reference_id,
        $amount = null,
        $due_date = null,
        $paid_date = null,
        $required = null,
        $status = null,
        $term_sequence = null
    ) {
        $this->term_reference_id = $term_reference_id;
        $this->amount = $amount;
        $this->due_date = $due_date;
        $this->paid_date = $paid_date;
        $this->required = $required;
        $this->status = $status;
        $this->term_sequence = $term_sequence;
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
