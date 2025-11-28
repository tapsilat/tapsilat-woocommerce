<?php
namespace Tapsilat\Models;

class SubscriptionCreateResponse
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getReferenceId()
    {
        return isset($this->data['reference_id']) ? $this->data['reference_id'] : null;
    }

    public function getOrderReferenceId()
    {
        return isset($this->data['order_reference_id']) ? $this->data['order_reference_id'] : null;
    }

    public function getCode()
    {
        return isset($this->data['code']) ? $this->data['code'] : null;
    }

    public function getMessage()
    {
        return isset($this->data['message']) ? $this->data['message'] : null;
    }

    public function toArray()
    {
        return $this->data;
    }
}
