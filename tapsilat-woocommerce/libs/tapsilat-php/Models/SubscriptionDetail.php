<?php
namespace Tapsilat\Models;

class SubscriptionDetail
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getAmount()
    {
        return isset($this->data['amount']) ? $this->data['amount'] : null;
    }

    public function getCurrency()
    {
        return isset($this->data['currency']) ? $this->data['currency'] : null;
    }

    public function getDueDate()
    {
        return isset($this->data['due_date']) ? $this->data['due_date'] : null;
    }

    public function getExternalReferenceId()
    {
        return isset($this->data['external_reference_id']) ? $this->data['external_reference_id'] : null;
    }

    public function getIsActive()
    {
        return isset($this->data['is_active']) ? $this->data['is_active'] : null;
    }

    public function getOrders()
    {
        return isset($this->data['orders']) ? $this->data['orders'] : null;
    }

    public function getPaymentDate()
    {
        return isset($this->data['payment_date']) ? $this->data['payment_date'] : null;
    }

    public function getPaymentStatus()
    {
        return isset($this->data['payment_status']) ? $this->data['payment_status'] : null;
    }

    public function getPeriod()
    {
        return isset($this->data['period']) ? $this->data['period'] : null;
    }

    public function getTitle()
    {
        return isset($this->data['title']) ? $this->data['title'] : null;
    }

    public function toArray()
    {
        return $this->data;
    }
}
