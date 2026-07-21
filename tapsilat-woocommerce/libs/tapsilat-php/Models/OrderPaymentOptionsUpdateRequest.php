<?php
namespace Tapsilat\Models;

class OrderPaymentOptionsUpdateRequest
{
    public $payment_options;
    public $reference_id;

    public function __construct($reference_id, array $payment_options = [])
    {
        $this->reference_id = $reference_id;
        $this->payment_options = $payment_options;
    }

    public function toArray()
    {
        return [
            'payment_options' => $this->payment_options,
            'reference_id' => $this->reference_id,
        ];
    }
}
