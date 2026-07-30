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
    public $name;
    public $surname;
    public $identity_number;
    public $email;
    public $phone;

    public function __construct(
        $address = null,
        $reference_id = null,
        $tax_office = null,
        $title = null,
        $type = null,
        $vat = null,
        $name = null,
        $surname = null,
        $identity_number = null,
        $email = null,
        $phone = null
    ) {
        $this->address = $address;
        $this->reference_id = $reference_id;
        $this->tax_office = $tax_office;
        $this->title = $title;
        $this->type = $type;
        $this->vat = $vat;
        $this->name = $name;
        $this->surname = $surname;
        $this->identity_number = $identity_number;
        $this->email = $email;
        $this->phone = $phone;
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
