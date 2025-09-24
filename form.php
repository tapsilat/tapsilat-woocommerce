<?php
if (!defined("ABSPATH")) {
    exit;
}
$settings = get_option("woocommerce_tapsilat_settings");
?>
<?php if (!isset($settings["Token"])) { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li>Payment is not active</li>
            </ul>
        </div>
    </section>
<?php } else if(isset($response["reference_id"])){ 
    // Get checkout URL from Tapsilat SDK
    $checkoutProcessor = new \Tapsilat\WooCommerce\Checkout\CheckoutProcessor();
    $checkoutUrl = $checkoutProcessor->getCheckoutUrl($response["reference_id"]);
    ?>
    <hr />
    <?php if ($checkoutUrl) { ?>
        <iframe src="<?php echo esc_url($checkoutUrl); ?>" width="100%" height="680"></iframe>
    <?php } else { ?>
        <div class="woocommerce-error">
            <p><?php _e('Unable to load payment form. Please try again.', 'tapsilat-woocommerce'); ?></p>
        </div>
    <?php } ?>
<?php } ?>