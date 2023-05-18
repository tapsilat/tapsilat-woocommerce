<?php
/*
Plugin Name: Tapsilat
Description: Woocommerce Plugin of Tapsilat
Version: 1.0
Author: Tapsilat
Author URI: https://tapsilat.com
License: GNU
*/

add_action("plugins_loaded", "init", 0);
add_filter("woocommerce_payment_gateways", "register");

function register($gateways) {
    $gateways[] = "tapsilat";
    return $gateways;
}
function init() {
    if (!class_exists("WC_Payment_Gateway")) {
        return;
    }
    class tapsilat extends WC_Payment_Gateway {
        function __construct() {
            $this->id = "tapsilat";
            $this->icon = null;
            $this->has_fields = true;
            $this->title = $this->get_option("title");
            $this->method_title = "tapsilat";
            $this->method_description = "Pay with Card/Debit Card or alternative payment methods";
            $this->init_form_fields();
            if (isset($_POST["woocommerce_tapsilat_enabled"])) {
                if ($_FILES["woocommerce_tapsilat_logo"]["name"] != ""){
                    $upload = wp_upload_bits($_FILES["woocommerce_tapsilat_logo"]["name"], null, file_get_contents($_FILES["woocommerce_tapsilat_logo"]["tmp_name"]));
                    // if upload is successful
                    if (!$upload["error"]) {
                        // get file url
                        $url = $upload["url"];
                        // get file path
                        $path = $upload["file"];
                        // get file type
                        $type = wp_check_filetype($path);
                        // set attachment data
                        $attachment = array(
                            "guid" => $url,
                            "post_mime_type" => $type["type"],
                            "post_title" => preg_replace("/\.[^.]+$/", "", basename($path)),
                            "post_content" => "",
                            "post_status" => "inherit"
                        );
                        // insert attachment
                        $attach_id = wp_insert_attachment($attachment, $path);
                        // set attachment id to tapsilat
                        $this->settings["tapsilat_logo"] = $attach_id;
                        // save tapsilat
                        update_option("woocommerce_tapsilat_settings", $this->settings);
                    }
                }
            }
            $this->init_settings();
            add_action("woocommerce_receipt_" . $this->id, array($this, "receipt"));
            add_action("woocommerce_thankyou_" . $this->id, array($this, "receipt"));
            add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));
        }
        public function init_form_fields() {
            $this->form_fields = array(
                "enabled" => array(
                    "title" => "Enable/Disable",
                    "label" => "Enable Tapsilat",
                    "type" => "checkbox",
                    "desc_tip" => "Enable Tapsilat",
                    "default" => "yes"
                ),
                "title" => array(
                    "title" => "Title",
                    "type" => "text",
                    "desc_tip" => "Title of the payment method",
                    "default" => "Credit Card/Debit Card or alternative payment methods"
                ),
                "Token" => array(
                    "title" => "Token",
                    "type" => "text",
                    "desc_tip" => "Get this from Tapsilat",
                ),
                "3d" => array(
                    "title" => "3D Secure active",
                    "label" => "3D Secure active<br>",
                    "type" => "checkbox",
                    "desc_tip" => "Use 3D Secure",
                    "default" => "yes"
                ),
                "Currency" => array(
                    "title" => "Currency",
                    "type" => "select",
                    "desc_tip" => "Select Currency",
                    "default" => "TRY",
                    "options" => array(
                        "TRY" => "₺ (TRY)",
                        "USD" => "$ (USD)",
                        "EUR" => "€ (EUR)",
                    )
                ),
                "API" => array(
                    "title" => "API",
                    "type" => "select",
                    "desc_tip" => "Select API",
                    "default" => "https://api.tapsilat.com",
                    "options" => array(
                        "https://api.tapsilat.com" => "Production",
                        "https://acquiring.tapsilat.dev" => "Development",
                    )
                ),
                "CheckoutURL" => array(
                    "title" => "Checkout URL",
                    "type" => "select",
                    "desc_tip" => "Select Checkout URL",
                    "default" => "https://checkout.tapsilat.com",
                    "options" => array(
                        "https://checkout.tapsilat.com" => "Production",
                        "https://checkout.tapsilat.dev" => "Development",
                    )
                ),
                "payment_form_view_mode" => array(
                    "title" => "Payment Form View Mode",
                    "type" => "select",
                    "desc_tip" => "Select View Mode",
                    "default" => "iframe",
                    "options" => array(
                        "iframe" => "Iframe",
                        "redirect" => "Redirect",
                        "popup" => "Popup",
                    )
                ),
                "input_background_color" => array(
                    "title" => "Input Background Color",
                    "type" => "color",
                    "desc_tip" => "Input Background Color",
                    "default" => "#ffffff"
                ),
                "input_text_color" => array(
                    "title" => "Input Text Color",
                    "type" => "color",
                    "desc_tip" => "Input Text Color",
                    "default" => "#000000"
                ),
                "label_text_color" => array(
                    "title" => "Label Text Color",
                    "type" => "color",
                    "desc_tip" => "Label Text Color",
                    "default" => "#000000"
                ),
                "left_background_color" => array(
                    "title" => "Left Background Color",
                    "type" => "color",
                    "desc_tip" => "Left Background Color",
                    "default" => "#ffffff"
                ),
                "right_background_color" => array(
                    "title" => "Right Background Color",
                    "type" => "color",
                    "desc_tip" => "Right Background Color",
                    "default" => "#ffffff"
                ),
                "pay_button_color" => array(
                    "title" => "Pay Button Color",
                    "type" => "color",
                    "desc_tip" => "Pay Button Color",
                    "default" => "#ffffff"
                ),
                "text_color" => array(
                    "title" => "Text Color",
                    "type" => "color",
                    "desc_tip" => "Text Color",
                    "default" => "#000000"
                ),
                "logo" => array(
                    "title" => "Logo",
                    "type" => "file",
                    "desc_tip" => "Logo",
                    "default" => ""
                ),
                "order_detail_html" => array(
                    "title" => "Order Detail HTML",
                    "type" => "textarea",
                    "desc_tip" => "Order Detail HTML",
                    "default" => ""
                ),
                "redirect_url" => array(
                    "title" => "Redirect URL",
                    "type" => "text",
                    "desc_tip" => "Redirect URL",
                    "default" => ""
                ),
            );
        }
        public function process_payment($orderid) {
            $order = wc_get_order($orderid);
            return array(
                "result" => "success",
                "redirect" => $order->get_checkout_payment_url(true),
            );
        }
        function receipt($orderid) {
            global $woocommerce;
            $order = wc_get_order($orderid);
            if ($order->get_status() == "pending") {
                $settings = get_option("woocommerce_tapsilat_settings");
                if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET["rnd"])) {
                    $rnd = $_GET["rnd"];
                    $request = new Order();
                    $request->Token = $settings["Token"];
                    $request->Conversation = $order->order_key . "-" . $rnd;
                    $request->Locale = substr(get_locale(), 0, 2);
                    $response = $request->details();
                    if (isset($response["order_payment_status"])) {
                        $paymentstatus = $response["order_payment_status"];
                        if (isset($paymentstatus["is_error"]) && $paymentstatus["is_error"] == false) {
                            $paymentDetails = $paymentstatus["payment_details"];
                            if (isset($paymentDetails["paid_amount"])){
                                if ($paymentDetails["paid_amount"] == $order["total"]){
                                    $order->update_status("processing");
                                    $order->add_order_note("Payment successful, Order ID: " . $response["order"]["reference_id"]);
                                    $order->payment_complete();
                                    $woocommerce->cart->empty_cart();
                                    wp_redirect($this->get_return_url());
                                    exit;
                                }else{
                                    $order->update_status("failed");
                                    $order->add_order_note("Payment failed, Order ID: " . $response["order"]["reference_id"]);
                                    $woocommerce->cart->empty_cart();
                                    wp_redirect($this->get_return_url());
                                    exit;
                                }
                            }else{
                                $order->update_status("processing");
                                $order->add_order_note("Payment successful, Order ID: " . $response["order"]["reference_id"]);
                                $order->payment_complete();
                                $woocommerce->cart->empty_cart();
                                wp_redirect($this->get_return_url());
                                exit;
                            }
                        }
                    }
                } else {
                    $data = $order->get_data();
                    $rnd = floor(microtime(true) * 1000);
                    $countries = new WC_Countries();
                    $request = new Order();
                    $request->Token = $settings["Token"];
                    $request->Conversation = $order->order_key . "-" . $rnd;
                    $request->Amount = $order->order_total;
                    $request->Currency = $settings["Currency"];
                    $request->Installment = [1];
                    $request->Buyer = array(
                        "id" => strval($data["customer_id"]),
                        "identity_number" => "11111111111",
                        "name" => $data["billing"]["first_name"],
                        "surname" => $data["billing"]["last_name"],
                        "email" => $data["billing"]["email"],
                        "gsm_number" => $data["billing"]["phone"],
                        "registration_address" =>  trim($data["billing"]["address_1"] . " " . $data["billing"]["address_2"]  . " " . $data["billing"]["city"]),
                        "country" => $countries->get_countries()[$data["billing"]["country"]],
                        "city" => $countries->get_states($data["billing"]["country"])[$data["billing"]["state"]],
                        "zip_code" => $data["billing"]["postcode"],
                        "ip" => $data["customer_ip_address"],
                    );
                    $request->Billing = array(
                        "contact_name" => $data["billing"]["company"],
                        "address" => trim($data["billing"]["address_1"] . " " . $data["billing"]["address_2"]  . " " . $data["billing"]["city"]),
                        "country" => $countries->get_countries()[$data["billing"]["country"]],
                        "city" => $countries->get_states($data["billing"]["country"])[$data["billing"]["state"]],
                        "zip_code" => $data["billing"]["postcode"]
                    );
                    @$country = $countries->get_countries($data["shipping"]["country"])[$data["shipping"]["country"]];
                    if (!$country){
                        $country = $countries->get_countries()[$data["billing"]["country"]];
                    }
                    @$city = $countries->get_states($data["shipping"]["country"])[$data["shipping"]["state"]];
                    if (!$city){
                        $city = $countries->get_states($data["billing"]["country"])[$data["billing"]["state"]];
                    }
                    $request->Shipping = array(
                        "contact_name" => $data["shipping"]["company"],
                        "address" => trim($data["shipping"]["address_1"] . " " . $data["shipping"]["address_2"]  . " " . $data["shipping"]["city"]),
                        "country" => $country,
                        "city" => $city,
                        "zip_code" => $data["shipping"]["postcode"]
                    );
                    $request->Design = array(
                        "redirect_url" => $order->get_checkout_payment_url(true) . "&rnd=" . $rnd,
                        "input_background_color" => $settings["input_background_color"],
                        "input_text_color" => $settings["input_text_color"],
                        "label_text_color" => $settings["label_text_color"],
                        "left_background_color" => $settings["left_background_color"],
                        "right_background_color" => $settings["right_background_color"],
                        "pay_button_color" => $settings["pay_button_color"],
                        "text_color" => $settings["text_color"],
                        "logo" => wp_get_attachment_url($settings["tapsilat_logo"]),
                        "order_detail_html" => $settings["order_detail_html"],
                    );
                    $basket = array();
                    foreach ($order->get_items() as $item) {
                        $product = $item->get_product();
                        $category_ids = $product->get_category_ids();
                        $categories = [];
                        foreach ($category_ids as $category_id) {
                            $category_term = get_term_by('id', $category_id, 'product_cat');
                            if ($category_term) {
                                $categories[] = $category_term->name;
                            }
                        }
                        $basket[] = array(
                            "id" => strval($product->get_id()),
                            "name" => $product->get_name(),
                            "price" => floatval($product->get_price() * $item->get_quantity()),
                            "category1" => $categories[0],
                        );
                    }
                    $request->Basket = $basket;
                    $request->Locale = substr(get_locale(), 0, 2);

                    $response = $request->create();
                }
                if ($settings["payment_form_view_mode"]=="redirect") {
                    $payment_url = get_option("woocommerce_tapsilat_settings")["CheckoutURL"]."/?reference_id=".$response["reference_id"];
                    wp_redirect($payment_url);
                    exit;
                }elseif ($settings["payment_form_view_mode"]=="popup") {
                    include plugin_dir_path(__FILE__) . "popup.php";
                }else{
                    include plugin_dir_path(__FILE__) . "form.php";
                }
            }
        }
    }
    class Order {
        public $Token;
        public $Conversation;
        public $Amount;
        public $Currency;
        public $Installment;
        public $Buyer;
        public $Billing;
        public $Shipping;
        public $Basket;
        public $Design;
        public $Locale;
        public function create() {
            $settings = get_option("woocommerce_tapsilat_settings");
            $body = array();
            $body["conversation_id"] = $this->Conversation;
            $body["amount"] = floatval($this->Amount);
            $body["currency"] = $this->Currency;
            $body["enabled_installments"] = $this->Installment;
            $body["buyer"] = $this->Buyer;
            $body["billing_address"] = $this->Billing;
            $body["shipping_address"] = $this->Shipping;
            $body["basket_items"] = $this->Basket;
            $body["checkout_design"] = $this->Design;
            $body["locale"] = $this->Locale;

            $ch = curl_init($settings["API"]."/api/v1/order/create");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8", "Authorization: Bearer " . $this->Token));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
        public function details() {
            $body = array();
            $body["conversation_id"] = $this->Conversation;
            $ch = curl_init(get_option("woocommerce_tapsilat_settings")["API"]."/api/v1/order/payment-details");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json; charset=utf-8", "Authorization: Bearer " . $this->Token));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
    }
}
