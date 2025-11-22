<?php
/**
 * Popup payment form template
 * 
 * Security: All outputs are properly escaped to prevent XSS
 */
if (!defined("ABSPATH")) {
    exit;
}

// Get settings with proper sanitization
$settings = get_option("woocommerce_tapsilat_settings", array());
?>
<?php if (empty($settings["Token"])) { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li><?php esc_html_e('Payment is not active', 'tapsilat-woocommerce'); ?></li>
            </ul>
        </div>
    </section>
<?php } else if(isset($response["reference_id"]) && !empty($response["reference_id"])){ ?>
    <style>
        .modal {
            display: block;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .open_payment_form {
            display: none;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
    <script>
        window.onload = function(){

            const modal = document.getElementById("payment_form");
            const btn = document.getElementById("open_payment_form");
            const iframe = document.getElementById("iframe_form");
            const span = document.getElementById("close");

            btn.onclick = function() {
                modal.style.display = "block";
                btn.style.display = "none";
                <?php 
                $checkoutProcessor = new \Tapsilat\WooCommerce\Checkout\CheckoutProcessor();
                $checkoutUrl = $checkoutProcessor->getCheckoutUrl(sanitize_text_field($response["reference_id"]));
                ?>
                iframe.src = "<?php echo esc_url($checkoutUrl); ?>";
            }

            span.onclick = function() {
                modal.style.display = "none";
                btn.style.display = "block";

            }

            window.onclick = function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            }
        }
    </script>
    <button id="open_payment_form" class="open_payment_form"><?php esc_html_e('Open Payment Form', 'tapsilat-woocommerce'); ?></button>

    <div id="payment_form" class="modal">

        <!-- Modal content -->
        <div class="modal-content">
            <span class="close" id="close">&times;</span>
            <iframe id="iframe_form" src="<?php echo esc_url($checkoutUrl); ?>" width="100%" height="680"></iframe>
        </div>

    </div>


    <hr />
<?php } ?>