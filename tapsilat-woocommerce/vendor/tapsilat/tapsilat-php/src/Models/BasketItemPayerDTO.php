<?php
namespace Tapsilat\Models;

class BasketItemPayerDTO
{
    public $address;
    public $reference_id;
    public $tax_office;
    public $title;
    public $type;
    public $vat;

    public function __construct(
        $address = null,
        $reference_id = null,
        $tax_office = null,
        $title = null,
        $type = null,
        $vat = null
    ) {
        $this->address = $address;
        $this->reference_id = $reference_id;
        $this->tax_office = $tax_office;
        $this->title = $title;
        $this->type = $type;
        $this->vat = $vat;
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                $result[$key] = $value;
            }
        }
        return $result;
    }
}
