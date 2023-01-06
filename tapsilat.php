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
            $this->title = "Kredi kartı ile ödeme";
            $this->method_title = "Kredi kartı ile ödeme";
            $this->method_description = "Ödemeleri kredi kartı/banka kartı kullanarak alın.";
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
                    "desc_tip" => "Token bilgisi"
                ),
                "3d" => array(
                    "title" => "3D secure aktif",
                    "label" => "3D secure aktif<br>",
                    "type" => "checkbox",
                    "desc_tip" => "3D secure ile ödemeye izin verecek misiniz?",
                    "default" => "yes"
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
                if ($_SERVER["REQUEST_METHOD"] == "POST") {
                    $request = new Request();
                    $request->Token = $settings["Token"];
                    $request->OrderId = $order->order_key;
                    if (isset($_GET["callback"])) {
                        $response = $request->order_details($request);
                        if (isset($response["order_payment_status"])) {
                            print_r($response);
                        }
                    } else {
                        $request->Amount = $order->order_total;
                        $request->Currency = "TRY";
                        $request->CardHolder = $_POST["cardholder"];
                        $request->CardNumber = str_replace(" ", "", $_POST["cardnumber"]);
                        $request->CardMonth = $_POST["cardmonth"];
                        $request->CardYear =  "20" . $_POST["cardyear"];
                        $request->CardCode = $_POST["cardcode"];
                        if ($settings["3d"] == "yes") {
                            $request->ThreeDPay = true;
                            $request->Callback = $order->get_checkout_payment_url(true) . "&callback=1";
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
                                    $order->add_order_note("Ödeme tamamlandı. Sipariş numarası: " . $response["reference_id"] . "");
                                    $order->payment_complete();
                                    $woocommerce->cart->empty_cart();
                                    wp_redirect($this->get_return_url());
                                    exit;
                                }
                            }
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
            $body["conservation_id"] = $request->OrderId;
            $body["locale"] = substr(get_locale(), 0, 2);
            if ($request->ThreeDPay == true) {
                $body["payment_success_url"] = $request->Callback;
                $body["payment_failure_url"] = $request->Callback;
            }
            $headers = array();
            $headers[] = "Content-Type: application/json; charset=utf-8";
            $headers[] = "Authorization: Bearer " . $request->Token;
            $ch = curl_init("https://acquiring.tapsilat.com/api/v1/order");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
        public static function order_details(Request $request) {
            $body = array();
            $body["conservation_id"] = $request->OrderId;
            $headers = array();
            $headers[] = "Content-Type: application/json; charset=utf-8";
            $headers[] = "Authorization: Bearer " . $request->Token;
            $ch = curl_init("https://acquiring.tapsilat.com/api/v1/order/payment-details");
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
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
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $output = curl_exec($ch);
            curl_close($ch);
            return json_decode($output, true);
        }
    }
}
