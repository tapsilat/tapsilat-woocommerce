<?php
if (!defined("ABSPATH")) {
    exit;
}
$settings = get_option("woocommerce_tapsilat_settings");
?>
<?php if (isset($checkout) && !empty($checkout["error"])) { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li>Payment is faild, your card issuer has responded with this message: <br />
                    <pre><?php print($checkout["error"]); ?></pre>
                    Please re-try your payment.
                </li>
            </ul>
        </div>
    </section>
<?php } ?>
<?php if (isset($settings["Token"])) { ?>
    <hr />
    <form autocomplete="on" method="POST" id="cc_form" action="">
        <div class="row">
            <table id="cc_form_inputs_table">
                <tr>
                    <td>
                        Full name on card<br />
                        <input type="text" id="cc_name" name="cardholder" class="cc_input" placeholder="Full Name" />
                    </td>
                </tr>
                <tr>
                    <td>
                        Card Number <br />
                        <input type="text" id="cc_number" name="cardnumber" class="cc_input" placeholder="•••• •••• •••• ••••" minlength="16" maxlength="16" pattern="[0-9]{16}" required />
                    </td>
                </tr>
                <tr>
                    <td>
                        Card Expire Month and Year.<br />
                        <input type="text" size="2" id="cc_month" name="cardmonth" class="cc_input" placeholder="AA" minlength="2" maxlength="2" pattern="[0-9]{2}" required />
                        <input type="text" size="2" id="cc_year" name="cardyear" class="cc_input" placeholder="YY" minlength="2" maxlength="2" pattern="[0-9]{2}" required />
                    </td>
                </tr>
                <tr>
                    <td>
                        Security Code<br />
                        <input type="text" size="4" id="cc_cvc" name="cardcode" class="cc_input" placeholder="•••" minlength="3" maxlength="4" pattern="[0-9]{3,4}" required />
                    </td>
                </tr>
                <tr>
                    <td>
                        <button type="submit" id="cc_form_submit" class="btn btn-lg btn-primary">Pay Now</button>
                    </td>
                </tr>
            </table>
        </div>
    </form>
<?php } else { ?>
    <section>
        <div class="row">
            <ul class="woocommerce-error" id="errDiv">
                <li>Payment is not active</li>
            </ul>
        </div>
    </section>
<?php } ?>