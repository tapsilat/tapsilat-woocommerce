<?php
/**
 * Add subscription meta fields to product edit page
 */
add_action('woocommerce_product_options_general_product_data', 'tapsilat_add_subscription_product_fields');
add_action('woocommerce_process_product_meta', 'tapsilat_save_subscription_product_fields');

function tapsilat_add_subscription_product_fields()
{
    global $post;

    $settings = get_option('woocommerce_tapsilat_settings', []);
    $subscriptions_enabled = isset($settings['enable_subscriptions']) && $settings['enable_subscriptions'] === 'yes';

    if (!$subscriptions_enabled) {
        return;
    }

    echo '<div class="options_group subscription_product_data">';

    // Is Subscription checkbox
    woocommerce_wp_checkbox(array(
        'id' => '_is_tapsilat_subscription',
        'label' => __('Tapsilat Subscription', 'tapsilat-woocommerce'),
        'description' => __('Enable this product as a subscription with recurring payments', 'tapsilat-woocommerce'),
        'desc_tip' => true,
    ));

    // Subscription Period
    woocommerce_wp_select(array(
        'id' => '_tapsilat_subscription_period',
        'label' => __('Billing Period (days)', 'tapsilat-woocommerce'),
        'description' => __('How often the customer will be charged', 'tapsilat-woocommerce'),
        'desc_tip' => true,
        'options' => array(
            '7' => __('Weekly (7 days)', 'tapsilat-woocommerce'),
            '15' => __('Bi-weekly (15 days)', 'tapsilat-woocommerce'),
            '30' => __('Monthly (30 days)', 'tapsilat-woocommerce'),
            '60' => __('Bi-monthly (60 days)', 'tapsilat-woocommerce'),
            '90' => __('Quarterly (90 days)', 'tapsilat-woocommerce'),
            '180' => __('Semi-annually (180 days)', 'tapsilat-woocommerce'),
            '365' => __('Annually (365 days)', 'tapsilat-woocommerce'),
        ),
        'value' => get_post_meta($post->ID, '_tapsilat_subscription_period', true) ?: '30',
        'wrapper_class' => 'show_if_tapsilat_subscription',
    ));

    // Subscription Cycle
    woocommerce_wp_text_input(array(
        'id' => '_tapsilat_subscription_cycle',
        'label' => __('Billing Cycles', 'tapsilat-woocommerce'),
        'description' => __('Number of billing cycles. Set to 0 for unlimited.', 'tapsilat-woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => array(
            'min' => '0',
            'step' => '1',
        ),
        'value' => get_post_meta($post->ID, '_tapsilat_subscription_cycle', true) ?: '12',
        'wrapper_class' => 'show_if_tapsilat_subscription',
    ));

    // Payment Day
    woocommerce_wp_text_input(array(
        'id' => '_tapsilat_subscription_payment_date',
        'label' => __('Payment Day of Month', 'tapsilat-woocommerce'),
        'description' => __('Day of the month when payment is charged (1-28)', 'tapsilat-woocommerce'),
        'desc_tip' => true,
        'type' => 'number',
        'custom_attributes' => array(
            'min' => '1',
            'max' => '28',
            'step' => '1',
        ),
        'value' => get_post_meta($post->ID, '_tapsilat_subscription_payment_date', true) ?: '1',
        'wrapper_class' => 'show_if_tapsilat_subscription',
    ));

    echo '</div>';

    // Add JavaScript to show/hide subscription fields
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#_is_tapsilat_subscription').change(function () {
                if ($(this).is(':checked')) {
                    $('.show_if_tapsilat_subscription').show();
                } else {
                    $('.show_if_tapsilat_subscription').hide();
                }
            }).change();
        });
    </script>
    <?php
}

function tapsilat_save_subscription_product_fields($post_id)
{
    $is_subscription = isset($_POST['_is_tapsilat_subscription']) ? 'yes' : 'no';
    update_post_meta($post_id, '_is_tapsilat_subscription', $is_subscription);

    if ($is_subscription === 'yes') {
        $period = isset($_POST['_tapsilat_subscription_period']) ? sanitize_text_field($_POST['_tapsilat_subscription_period']) : '30';
        $cycle = isset($_POST['_tapsilat_subscription_cycle']) ? absint($_POST['_tapsilat_subscription_cycle']) : 12;
        $payment_date = isset($_POST['_tapsilat_subscription_payment_date']) ? absint($_POST['_tapsilat_subscription_payment_date']) : 1;

        update_post_meta($post_id, '_tapsilat_subscription_period', $period);
        update_post_meta($post_id, '_tapsilat_subscription_cycle', $cycle);
        update_post_meta($post_id, '_tapsilat_subscription_payment_date', $payment_date);
    }
}
