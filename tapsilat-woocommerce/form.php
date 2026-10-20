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
// $checkoutUrl is set by WC_Gateway_Tapsilat::receipt()
?>
<?php if (empty($settings["Token"])) { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li><?php esc_html_e('Payment is not active', 'tapsilat-woocommerce'); ?></li>
            </ul>
        </div>
    </section>
<?php } else { ?>
    <hr />
    <?php if (!empty($checkoutUrl)) { ?>
        <iframe src="<?php echo esc_url($checkoutUrl); ?>" width="100%" height="680"></iframe>
    <?php } else { ?>
        <div class="woocommerce-error">
            <p><?php esc_html_e('Unable to load payment form. Please try again.', 'tapsilat-woocommerce'); ?></p>
        </div>
    <?php } ?>
<?php } ?>