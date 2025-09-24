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
    public $pay_button_color;
    public $redirect_url;
    public $right_background_color;
    public $text_color;

    public function __construct(
        $input_background_color = null,
        $input_text_color = null,
        $label_text_color = null,
        $left_background_color = null,
        $logo = null,
        $order_detail_html = null,
        $pay_button_color = null,
        $redirect_url = null,
        $right_background_color = null,
        $text_color = null
    ) {
        $this->input_background_color = $input_background_color;
        $this->input_text_color = $input_text_color;
        $this->label_text_color = $label_text_color;
        $this->left_background_color = $left_background_color;
        $this->logo = $logo;
        $this->order_detail_html = $order_detail_html;
        $this->pay_button_color = $pay_button_color;
        $this->redirect_url = $redirect_url;
        $this->right_background_color = $right_background_color;
        $this->text_color = $text_color;
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
