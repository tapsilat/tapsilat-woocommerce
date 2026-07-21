<?php
namespace Tapsilat\Models;

class GetOrderPaymentsRequest
{
    public $conversation_id;
    public $order_reference_id;
    public $order_id;

    public function __construct($order_id, $conversation_id = null, $order_reference_id = null)
    {
        $this->conversation_id = $conversation_id;
        $this->order_reference_id = $order_reference_id;
        $this->order_id = $order_id;
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
