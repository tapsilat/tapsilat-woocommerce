<?php
namespace Tapsilat\Models;

class UpdateCallbackURLRequest
{
    public $callback_url;
    public $cancel_callback_url;
    public $fail_callback_url;
    public $refund_callback_url;

    public function __construct(
        $callback_url = null,
        $cancel_callback_url = null,
        $fail_callback_url = null,
        $refund_callback_url = null
    ) {
        $this->callback_url = $callback_url;
        $this->cancel_callback_url = $cancel_callback_url;
        $this->fail_callback_url = $fail_callback_url;
        $this->refund_callback_url = $refund_callback_url;
    }

    public function toArray()
    {
        $data = array_filter([
            'callback_url' => $this->callback_url,
            'cancel_callback_url' => $this->cancel_callback_url,
            'fail_callback_url' => $this->fail_callback_url,
            'refund_callback_url' => $this->refund_callback_url,
        ], function ($value) {
            return $value !== null;
        });
        
        // Handle nested DTOs if any
        foreach ($data as $key => $val) {
            if (is_object($val) && method_exists($val, 'toArray')) {
                $data[$key] = $val->toArray();
            }
        }

        return $data;
    }
}
