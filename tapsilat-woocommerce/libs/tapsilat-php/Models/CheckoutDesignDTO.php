<?php
namespace Tapsilat\Models;

class CheckoutDesignDTO
{
    public $input_background_color;
    public $input_text_color;
    public $label_text_color;
    public $left_background_color;
    public $logo;
    public $order_detail_html;
    public $right_background_color;
    public $text_color;
    public $pay_button_color;
    public $redirect_url;

    public function __construct(
        $input_background_color = null,
        $input_text_color = null,
        $label_text_color = null,
        $left_background_color = null,
        $logo = null,
        $order_detail_html = null,
        $right_background_color = null,
        $text_color = null,
        $pay_button_color = null,
        $redirect_url = null
    ) {
        $this->input_background_color = $input_background_color;
        $this->input_text_color = $input_text_color;
        $this->label_text_color = $label_text_color;
        $this->left_background_color = $left_background_color;
        $this->logo = $logo;
        $this->order_detail_html = $order_detail_html;
        $this->right_background_color = $right_background_color;
        $this->text_color = $text_color;
        $this->pay_button_color = $pay_button_color;
        $this->redirect_url = $redirect_url;
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
