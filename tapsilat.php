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
            $this->title = "Credit Card/Debit Card or alternative payment methods";
            $this->method_title = "Credit Card/Debit Card or alternative payment methods";
            $this->method_description = "Pay with Card/Debit Card or alternative payment methods";
            $this->init_form_fields();
            $this->init_settings();
            add_action("woocommerce_receipt_" . $this->id, array($this, "receipt"));
            add_action("woocommerce_thankyou_" . $this->id, array($this, "receipt"));
            add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));
        }
        public function init_form_fields() {
            $this->form_fields = array(
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
                )
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
                            $order->update_status("processing");
                            $order->add_order_note("Payment successful, Order ID: " . $response["order"]["reference_id"]);
                            $order->payment_complete();
                            $woocommerce->cart->empty_cart();
                            wp_redirect($this->get_return_url());
                            exit;
                        } elseif (isset($paymentstatus["message"])) {
                            $checkout = array("error" => $paymentstatus["message"]);
                        } else {
                            $checkout = array("error" => "Payment failed. Please try again.");
                        }
                    } else {
                        if (isset($response["error"])) {
                            $checkout = $response;
                        } else {
                            $checkout = array("error" => $response);
                        }
                    }
                } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
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
                        "name" => $data["billing"]["first_name"],
                        "surname" => $data["billing"]["last_name"],
                        "email" => $data["billing"]["email"],
                        "gsm_number" => $data["billing"]["phone"],
                        "ip" => $data["customer_ip_address"]
                    );
                    $request->Billing = array(
                        "contact_name" => $data["billing"]["company"],
                        "address" => trim($data["billing"]["address_1"] . " " . $data["billing"]["address_2"]  . " " . $data["billing"]["city"]),
                        "country" => $countries->get_countries()[$data["billing"]["country"]],
                        "city" => $countries->get_states($data["billing"]["country"])[$data["billing"]["state"]],
                        "zip_code" => $data["billing"]["postcode"]
                    );
                    $request->Shipping = array(
                        "contact_name" => $data["shipping"]["company"],
                        "address" => trim($data["shipping"]["address_1"] . " " . $data["shipping"]["address_2"]  . " " . $data["shipping"]["city"]),
                        "country" => $countries->get_countries()[$data["shipping"]["country"]],
                        "city" => $countries->get_states($data["shipping"]["country"])[$data["shipping"]["state"]],
                        "zip_code" => $data["shipping"]["postcode"]
                    );
                    $basket = array();
                    foreach ($order->get_items() as $item) {
                        $product = $item->get_product();
                        $basket[] = array(
                            "name" => $product->get_name(),
                            "price" => floatval($product->get_price())
                        );
                    }
                    $request->Basket = $basket;
                    if ($settings["3d"] == "yes") {
                        $request->Callback = $order->get_checkout_payment_url(true) . "&rnd=" . $rnd;
                    }
                    $request->Locale = substr(get_locale(), 0, 2);
                    $response = $request->create();
                    if (isset($response["reference_id"])) {
                        $request = new Checkout();
                        $request->Token = $settings["Token"];
                        $request->Reference = $response["reference_id"];
                        $request->CardHolder = $_POST["cardholder"];
                        $request->CardNumber = $_POST["cardnumber"];
                        $request->CardMonth = $_POST["cardmonth"];
                        $request->CardYear =  "20" . $_POST["cardyear"];
                        $request->CardCode = $_POST["cardcode"];
                        if ($settings["3d"] == "yes") {
                            $request->ThreeDPay = true;
                        } else {
                            $request->ThreeDPay = false;
                        }
                        $checkout = $request->transaction();
                        if ($settings["3d"] == "yes") {
                            if (isset($checkout["form"]) && !empty($checkout["form"])) {
                                $dom = new DomDocument();
                                $dom->loadHTML(base64_decode($checkout["form"]));
                                $form = $dom->getElementsByTagName("form")->item(0);
                                print($dom->savehtml($form));
                                print("<script>document.payment.submit();</script>");
                                exit;
                            }
                        } else {
                            if (isset($checkout["paid"]) && $checkout["paid"] == true) {
                                $order->update_status("processing");
                                $order->add_order_note("Payment successful, Order ID: " . $response["reference_id"]);
                                $order->payment_complete();
                                $woocommerce->cart->empty_cart();
                                wp_redirect($this->get_return_url());
                                exit;
                            }
                        }
                    } else {
                        if (isset($response["error"])) {
                            $checkout = $response;
                        } else {
                            $checkout = array("error" => $response);
                        }
                    }
                }
                include plugin_dir_path(__FILE__) . "form.php";
            }
        }
    }
    class Checkout {
        public $Token;
        public $Reference;
        public $CardHolder;
        public $CardNumber;
        public $CardMonth;
        public $CardYear;
        public $CardCode;
        public $ThreeDPay;
        public function transaction() {
            $body = array();
            $body["reference_id"] = $this->Reference;
            $body["holder_name"] = $this->CardHolder;
            $body["card_number"] = $this->CardNumber;
            $body["expiry_month"] = $this->CardMonth;
            $body["expiry_year"] = $this->CardYear;
            $body["cvv"] = $this->CardCode;
            $body["three_d_pay"] = $this->ThreeDPay;
            $ch = curl_init("https://checkout.tapsilat.com/api/v1/checkout");
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
        public $Callback;
        public $Locale;
        public function create() {
            $body = array();
            $body["conversation_id"] = $this->Conversation;
            $body["amount"] = floatval($this->Amount);
            $body["currency"] = $this->Currency;
            $body["enabled_installments"] = $this->Installment;
            $body["buyer"] = $this->Buyer;
            $body["billing_address"] = $this->Billing;
            $body["shipping_address"] = $this->Shipping;
            $body["basket_items"] = $this->Basket;
            $body["payment_success_url"] = $this->Callback;
            $body["payment_failure_url"] = $this->Callback;
            $body["locale"] = $this->Locale;
            $ch = curl_init("https://acquiring.tapsilat.com/api/v1/order/create");
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
            $ch = curl_init("https://acquiring.tapsilat.com/api/v1/order/payment-details");
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
