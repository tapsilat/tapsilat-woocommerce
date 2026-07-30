<?php
namespace Tapsilat\Models;

class OrgCreateUserRequest
{
    public $conversation_id;
    public $email;
    public $first_name;
    public $identity_number;
    public $is_mail_verified;
    public $last_name;
    public $phone;
    public $reference_id;

    public function __construct(
        $conversation_id,
        $email,
        $first_name,
        $identity_number,
        $is_mail_verified = false,
        $last_name,
        $phone,
        $reference_id) {
        $this->conversation_id = $conversation_id;
        $this->email = $email;
        $this->first_name = $first_name;
        $this->identity_number = $identity_number;
        $this->is_mail_verified = $is_mail_verified;
        $this->last_name = $last_name;
        $this->phone = $phone;
        $this->reference_id = $reference_id;
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
