<?php

namespace Tapsilat\Models;

class OrgUserTokenCreateReq
{
    public $invalidate_old_tokens;
    public $email;
    /** Token lifetime in minutes. */
    public $expire;
    /** @deprecated Not part of the API; the server ignores it. */
    public $language;
    /** @deprecated Not part of the API; the server ignores it. */
    public $metadata;
    /** @deprecated Not part of the API; the server ignores it. */
    public $title;
    /** @deprecated Not part of the API; the server ignores it. */
    public $type;
    public $user_id;

    public function __construct(
        $email,
        $expire = null,
        $language = null,
        $metadata = null,
        $title = null,
        $type = null,
        $invalidate_old_tokens = null,
        $user_id = null
    ) {
        $this->user_id = $user_id;
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
