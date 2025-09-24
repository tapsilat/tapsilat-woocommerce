<?php
namespace Tapsilat\Models;

class OrderCardDTO
{
    public $card_id;
    public $card_sequence;

    public function __construct($card_id, $card_sequence)
    {
        $this->card_id = $card_id;
        $this->card_sequence = $card_sequence;
    }

    public function toArray()
    {
        return [
            'card_id' => $this->card_id,
            'card_sequence' => $this->card_sequence
        ];
    }
}
