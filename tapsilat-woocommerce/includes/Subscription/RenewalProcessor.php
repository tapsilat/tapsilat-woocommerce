<?php
/**
 * Process subscription renewals
 * Called by cron job hourly
 */
function tapsilat_process_subscription_renewals()
{
    $subscription_helper = new \Tapsilat\WooCommerce\Subscription\SubscriptionHelper();

    // Check if subscriptions are enabled
    if (!$subscription_helper->is_subscription_enabled()) {
        return;
    }

    // Get all subscription orders
    $subscription_orders = wc_get_orders(array(
        'limit' => -1,
        'meta_key' => '_is_subscription',
        'meta_value' => 'yes',
        'status' => array('processing', 'completed'),
    ));

    foreach ($subscription_orders as $order) {
        $subscription_meta = $subscription_helper->get_order_subscription_meta($order);

        // Skip if subscription is cancelled
        if ($subscription_meta['status'] === 'cancelled') {
            continue;
        }

        // Get subscription details from Tapsilat
        $subscription_id = $subscription_meta['subscription_id'];
        if (empty($subscription_id)) {
            continue;
        }

        $result = $subscription_helper->get_subscription($subscription_id);

        if (is_wp_error($result)) {
            $order->add_order_note(
                sprintf(
                    __('Subscription renewal check failed: %s', 'tapsilat-woocommerce'),
                    $result->get_error_message()
                )
            );
            continue;
        }

        // Check subscription status and update order accordingly
        if (isset($result['subscription'])) {
            $subscription = $result['subscription'];
            $status = $subscription->getStatus();

            // Update order meta with current status
            $order->update_meta_data('_tapsilat_subscription_status', strtolower($status));
            $order->save();

            // Handle different statuses
            switch (strtoupper($status)) {
                case 'CANCELLED':
                case 'EXPIRED':
                    $order->add_order_note(__('Subscription has been cancelled or expired.', 'tapsilat-woocommerce'));
                    break;

                case 'ACTIVE':
                    // Subscription is active, no action needed
                    break;
            }
        }
    }
}
