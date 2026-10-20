<?php

namespace Tapsilat\Models;

class CheckoutDesignDTO
{
    public $placeholder_color;
    public $input_background_color;
    public $input_text_color;
    public $label_text_color;
    public $left_background_color;
    public $logo;
    /** @deprecated Checkout no longer renders custom order detail HTML; the value is ignored. */
    public $order_detail_html;
    public $right_background_color;
    public $text_color;
    public $pay_button_color;
    /** @deprecated Ignored by checkout; use the order's redirect_success_url and redirect_failure_url. */
    public $redirect_url;
    public $pay_button_text_color;
    public $page_background_color;
    public $border_color;
    public $input_border_color;
    public $accent_color;
    public $text_muted_color;
    public $surface_muted_color;
    public $dark_mode_enabled;
    /** Dark-mode colors keyed by the light-mode field name, for example ["text_color" => "#ffffff"]. */
    public $dark_colors;

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
        $redirect_url = null,
        $placeholder_color = null,
        $pay_button_text_color = null,
        $page_background_color = null,
        $border_color = null,
        $input_border_color = null,
        $accent_color = null,
        $text_muted_color = null,
        $surface_muted_color = null,
        $dark_mode_enabled = null,
        $dark_colors = null
    ) {
        $this->pay_button_text_color = $pay_button_text_color;
        $this->page_background_color = $page_background_color;
        $this->border_color = $border_color;
        $this->input_border_color = $input_border_color;
        $this->accent_color = $accent_color;
        $this->text_muted_color = $text_muted_color;
        $this->surface_muted_color = $surface_muted_color;
        $this->dark_mode_enabled = $dark_mode_enabled;
        $this->dark_colors = $dark_colors;
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
        $this->placeholder_color = $placeholder_color;
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
