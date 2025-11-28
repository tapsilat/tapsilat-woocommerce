<?php
namespace Tapsilat\Models;

class MetadataDTO
{
    public $key;
    public $value;

    public function __construct($key, $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function toArray()
    {
        return [
            'key' => $this->key,
            'value' => $this->value
        ];
    }
}
