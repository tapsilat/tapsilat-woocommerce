<?php
namespace Tapsilat\Models;

class SubscriptionRedirectResponse
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function getUrl()
    {
        return isset($this->data['url']) ? $this->data['url'] : null;
    }

    public function toArray()
    {
        return $this->data;
    }
}
