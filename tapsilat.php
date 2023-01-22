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
                    $request = new Request();
                    $request->Token = $settings["Token"];
                    $request->OrderId = $order->order_key . "-" . $_GET["rnd"];
                    $response = $request->order_details($request);
                    if (isset($response["order_payment_status"])) {
                        $paymentstatus = $response["order_payment_status"];
                        if (isset($paymentstatus["is_error"]) && $paymentstatus["is_error"] == false) {
                            $order->update_status("processing");
                            $order->add_order_note("Process is comleted, Order ID: " . $response["order"]["reference_id"] . "");
                            $order->payment_complete();
                            $woocommerce->cart->empty_cart();
                            wp_redirect($this->get_return_url());
                            exit;
                        } elseif (isset($paymentstatus["message"])) {
                            $checkout = array("error" => $paymentstatus["message"]);
                        } else {
                            $checkout = array("error" => "Payment is not completed. Please try again.");
                        }
                    }
                } elseif ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $rnd = floor(microtime(true) * 1000);
                    $request = new Request();
                    $request->Token = $settings["Token"];
                    $request->OrderId = $order->order_key . "-" . $rnd;
                    $request->Amount = $order->order_total;
                    $request->Currency = $settings["Currency"];
                    $request->CardHolder = $_POST["cardholder"];
                    $request->CardNumber = str_replace(" ", "", $_POST["cardnumber"]);
                    $request->CardMonth = $_POST["cardmonth"];
                    $request->CardYear =  "20" . $_POST["cardyear"];
                    $request->CardCode = $_POST["cardcode"];
                    if ($settings["3d"] == "yes") {
                        $request->ThreeDPay = true;
                        $request->Callback = $order->get_checkout_payment_url(true) . "&rnd=" . $rnd;
                    } else {
                        $request->ThreeDPay = false;
                    }
                    $request->Installment = [1];
                    $response = $request->order($request);
                    if (isset($response["reference_id"])) {
                        $request->Reference = $response["reference_id"];
                        if ($settings["3d"] == "yes") {
                            $checkout = $request->checkout($request);
                            if (isset($checkout["form"]) && !empty($checkout["form"])) {
                                $dom = new DomDocument();
                                $dom->loadHTML(base64_decode($checkout["form"]));
                                $form = $dom->getElementsByTagName("form")->item(0);
                                print($dom->savehtml($form));
                                print("<script>document.payment.submit();</script>");
                                exit;
                            }
                        } else {
                            $checkout = $request->checkout($request);
                            if (isset($checkout["paid"]) && $checkout["paid"] == true) {
                                $order->update_status("processing");
                                $order->add_order_note("Process is comleted, Order ID: " . $response["reference_id"] . "");
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
    class Request {
        public $Token;
        public $OrderId;
        public $Reference;
        public $Callback;
        public $Amount;
        public $Currency;
        public $Installment;
        public $CardHolder;
        public $CardNumber;
        public $CardMonth;
        public $CardYear;
        public $CardCode;
        public $ThreeDPay;
        public static function order(Request $request) {
            $body = array();
            $body["amount"] = floatval($request->Amount);
            $body["currency"] = $request->Currency;
            $body["enabled_installments"] = $request->Installment;
            $body["conversation_id"] = $request->OrderId;
            $body["locale"] = substr(get_locale(), 0, 2);
            if ($request->ThreeDPay == true) {
                $body["payment_success_url"] = $request->Callback;
                $body["payment_failure_url"] = $request->Callback;
            }
            $headers = array();
            $headers[] = "Content-Type: application/json; charset=utf-8";
            $headers[] = "Authorization: Bearer " . $request->Token;
            $ch = curl_init("https://acquiring.tapsilat.com/api/v1/order/create");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
        public static function order_details(Request $request) {
            $body = array();
            $body["conversation_id"] = $request->OrderId;
            $headers = array();
            $headers[] = "Content-Type: application/json; charset=utf-8";
            $headers[] = "Authorization: Bearer " . $request->Token;
            $ch = curl_init("https://acquiring.tapsilat.com/api/v1/order/payment-details");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
        public static function checkout(Request $request) {
            $body = array();
            $body["holder_name"] = $request->CardHolder;
            $body["card_number"] = $request->CardNumber;
            $body["expiry_month"] = $request->CardMonth;
            $body["expiry_year"] = $request->CardYear;
            $body["cvv"] = $request->CardCode;
            $body["reference_id"] = $request->Reference;
            $body["three_d_pay"] = $request->ThreeDPay;
            $headers = array();
            $headers[] = "Content-Type: application/json; charset=utf-8";
            $headers[] = "Authorization: Bearer " . $request->Token;
            $ch = curl_init("https://checkout.tapsilat.com/api/v1/checkout");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body, JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
    }

    class TapsilatOrder{
        public $locale;
        public $currency;
        public $buyer;
        public $shipping_address;
        public $billing_address;
        public $basket_items;
        public $enabled_installments;
        public $amount;
        public $conservation_id;
        public $payment_failure_url;
        public $payment_success_url;
        public function __construct($locale, $currency, $buyer, $shipping_address, $billing_address, $basket_items, $enabled_installments, $amount, $conservation_id, $payment_failure_url, $payment_success_url) {
            $this->locale = $locale;
            $this->currency = $currency;
            $this->buyer = $buyer;
            $this->shipping_address = $shipping_address;
            $this->billing_address = $billing_address;
            $this->basket_items = $basket_items;
            $this->enabled_installments = $enabled_installments;
            $this->amount = $amount;
            $this->conservation_id = $conservation_id;
            $this->payment_failure_url = $payment_failure_url;
            $this->payment_success_url = $payment_success_url;
        }

        public function getArray() {
            return array(
                "locale" => $this->locale,
                "currency" => $this->currency,
                "buyer" => $this->buyer,
                "shipping_address" => $this->shipping_address,
                "billing_address" => $this->billing_address,
                "basket_items" => $this->basket_items,
                "enabled_installments" => $this->enabled_installments,
                "amount" => $this->amount,
                "conservation_id" => $this->conservation_id,
                "payment_failure_url" => $this->payment_failure_url,
                "payment_success_url" => $this->payment_success_url
            );
        }

        public function setBuyer($id, $name, $surname, $email, $identity_number, $gsm_number, $registration_date, $last_login_date, $registration_address, $city, $country, $zip_code, $ip) {
            $this->buyer = array(
                "id" => $id,
                "name" => $name,
                "surname" => $surname,
                "email" => $email,
                "identity_number" => $identity_number,
                "gsm_number" => $gsm_number,
                "registration_date" => $registration_date,
                "last_login_date" => $last_login_date,
                "registration_address" => $registration_address,
                "city" => $city,
                "country" => $country,
                "zip_code" => $zip_code,
                "ip" => $ip
            );
        }

        public function setShippingAddress($address, $zip_code, $contact_name, $city, $country, $tracking_code) {
            $this->shipping_address = array(
                "address" => $address,
                "zip_code" => $zip_code,
                "contact_name" => $contact_name,
                "city" => $city,
                "country" => $country,
                "tracking_code" => $tracking_code
            );
        }

   
        public function setBillingAddress($address, $zip_code, $contact_name, $city, $country) {
            $this->billing_address = array(
                "address" => $address,
                "zip_code" => $zip_code,
                "contact_name" => $contact_name,
                "city" => $city,
                "country" => $country
            );
        }

     
        public function addBasketItems($id, $price, $name, $category1, $category2, $item_type) {
            $this->basket_items[] = array(
                "id" => $id,
                "price" => $price,
                "name" => $name,
                "category1" => $category1,
                "category2" => $category2,
                "item_type" => $item_type
            );
        }

        public function emptyBasketItems() {
            $this->basket_items = array();
        }

        public function setLocale($locale) {
            $this->locale = $locale;
        }

        public function setCurrency($currency) {
            $this->currency = $currency;
        }

        public function setEnabledInstallments($enabled_installments) {
            $this->enabled_installments = $enabled_installments;
        }

        public function setAmount($amount) {
            $this->amount = $amount;
        }


        public function setConservationId($conservation_id) {
            $this->conservation_id = $conservation_id;
        }


        public function setPaymentFailureUrl($payment_failure_url) {
            $this->payment_failure_url = $payment_failure_url;
        }

        public function setPaymentSuccessUrl($payment_success_url) {
            $this->payment_success_url = $payment_success_url;
        }



    }
}
