<?php
if (!defined("ABSPATH")) {
    exit;
}
$settings = get_option("woocommerce_tapsilat_settings");
?>
<?php if (isset($response) && !empty($response["error"])) { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li>Payment failed, your card issuer has responded with this message: <br />
                    <pre><?php print($response["error"]); ?></pre>
                    Please re-try your payment.
                </li>
            </ul>
        </div>
    </section>
<?php } ?>
<?php if (!isset($settings["Token"])) { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li>Payment is not active</li>
            </ul>
        </div>
    </section>
<?php } ?>