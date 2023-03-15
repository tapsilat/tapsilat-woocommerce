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
<?php } else if(isset($response["reference_id"])){ ?>
    <hr />
    <iframe src="<?php echo get_option("woocommerce_tapsilat_settings")["CheckoutURL"]; ?>/?reference_id=<?php echo $response["reference_id"]; ?>" width="100%" height="680"></iframe>
<?php } ?>