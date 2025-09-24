<?php
namespace Tapsilat\Models;

class OrderResponse
{
    private $data;

    public function __construct($data)
    {
        $this->data = is_array($data) ? $data : [];
    }

    public function getReferenceId()
    {
        return isset($this->data['reference_id']) ? $this->data['reference_id'] : null;
    }

    public function getCheckoutUrl()
    {
        return isset($this->data['checkout_url']) ? $this->data['checkout_url'] : null;
    }

    public function getOrderId()
    {
        return isset($this->data['order_id']) ? $this->data['order_id'] : null;
    }

    public function getData()
    {
        return $this->data;
    }
}
