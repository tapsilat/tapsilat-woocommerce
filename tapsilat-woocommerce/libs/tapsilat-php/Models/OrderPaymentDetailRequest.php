<?php
namespace Tapsilat\Models;

class OrderPaymentDetailRequest
{
    public $reference_id;
    public $conversation_id;

    public function __construct(
        $reference_id,
        $conversation_id = null
    ) {
        $this->reference_id = $reference_id;
        $this->conversation_id = $conversation_id;
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
