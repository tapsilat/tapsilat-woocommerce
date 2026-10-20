<?php

namespace Tapsilat\Models;

class SubscriptionRedirectResponse implements \JsonSerializable
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return $this->data;
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
