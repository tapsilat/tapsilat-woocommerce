<?php
namespace Tapsilat\Models;

class OrgUserTokenCreateReq
{
    public $invalidate_old_tokens;
    public $email;
    public $expire;
    public $language;
    public $metadata;
    public $title;
    public $type;

    public function __construct($email, $expire = null, $language = null, $metadata = null, $title = null, $type = null, $invalidate_old_tokens = null)
    {
        $this->invalidate_old_tokens = $invalidate_old_tokens;
        $this->email = $email;
        $this->expire = $expire;
        $this->language = $language;
        $this->metadata = $metadata;
        $this->title = $title;
        $this->type = $type;
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
