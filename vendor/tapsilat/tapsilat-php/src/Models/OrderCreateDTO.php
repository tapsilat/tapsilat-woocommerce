<?php
namespace Tapsilat\Models;

class OrderCreateDTO
{
    public $amount;
    public $currency;
    public $locale;
    public $buyer;
    public $basket_items;
    public $billing_address;
    public $checkout_design;
    public $conversation_id;
    public $enabled_installments;
    public $external_reference_id;
    public $metadata;
    public $order_cards;
    public $paid_amount;
    public $partial_payment;
    public $payment_failure_url;
    public $payment_methods;
    public $payment_options;
    public $payment_success_url;
    public $payment_terms;
    public $pf_sub_merchant;
    public $shipping_address;
    public $sub_organization;
    public $submerchants;
    public $tax_amount;
    public $three_d_force;

    public function __construct(
        $amount,
        $currency,
        $locale,
        BuyerDTO $buyer,
        array $basket_items = null,
        BillingAddressDTO $billing_address = null,
        CheckoutDesignDTO $checkout_design = null,
        $conversation_id = null,
        array $enabled_installments = null,
        $external_reference_id = null,
        array $metadata = null,
        OrderCardDTO $order_cards = null,
        $paid_amount = null,
        $partial_payment = null,
        $payment_failure_url = null,
        $payment_methods = null,
        array $payment_options = null,
        $payment_success_url = null,
        array $payment_terms = null,
        $pf_sub_merchant = null,
        ShippingAddressDTO $shipping_address = null,
        SubOrganizationDTO $sub_organization = null,
        array $submerchants = null,
        $tax_amount = null,
        $three_d_force = null
    ) {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->locale = $locale;
        $this->buyer = $buyer;
        $this->basket_items = $basket_items;
        $this->billing_address = $billing_address;
        $this->checkout_design = $checkout_design;
        $this->conversation_id = $conversation_id;
        $this->enabled_installments = $enabled_installments;
        $this->external_reference_id = $external_reference_id;
        $this->metadata = $metadata;
        $this->order_cards = $order_cards;
        $this->paid_amount = $paid_amount;
        $this->partial_payment = $partial_payment;
        $this->payment_failure_url = $payment_failure_url;
        $this->payment_methods = $payment_methods;
        $this->payment_options = $payment_options;
        $this->payment_success_url = $payment_success_url;
        $this->payment_terms = $payment_terms;
        $this->pf_sub_merchant = $pf_sub_merchant;
        $this->shipping_address = $shipping_address;
        $this->sub_organization = $sub_organization;
        $this->submerchants = $submerchants;
        $this->tax_amount = $tax_amount;
        $this->three_d_force = $three_d_force;
    }

    public function toArray()
    {
        $result = [];
        foreach (get_object_vars($this) as $key => $value) {
            if ($value !== null) {
                if (is_object($value) && method_exists($value, 'toArray')) {
                    $result[$key] = $value->toArray();
                } elseif (is_array($value)) {
                    $result[$key] = array_map(function($item) {
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
