<?php
/**
 * Iframe payment form template
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
<?php } else if(isset($response["reference_id"]) && !empty($response["reference_id"])){ 
    // Get checkout URL from Tapsilat SDK with proper sanitization
    $checkoutProcessor = new \Tapsilat\WooCommerce\Checkout\CheckoutProcessor();
    $checkoutUrl = $checkoutProcessor->getCheckoutUrl(sanitize_text_field($response["reference_id"]));
    ?>
    <hr />
    <?php if ($checkoutUrl) { ?>
        <iframe src="<?php echo esc_url($checkoutUrl); ?>" width="100%" height="680"></iframe>
    <?php } else { ?>
        <div class="woocommerce-error">
            <p><?php esc_html_e('Unable to load payment form. Please try again.', 'tapsilat-woocommerce'); ?></p>
        </div>
    <?php } ?>
<?php } ?>