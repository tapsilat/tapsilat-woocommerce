<?php
/*
Plugin Name: Tapsilat Payment Gateway for WooCommerce
Plugin URI: https://github.com/tapsilat/tapsilat-woocommerce
Description: Tapsilat payment gateway integration for WooCommerce
Version: 2025.11.28.1
Author: Tapsilat
Author URI: https://tapsilat.com
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires Plugins: woocommerce 
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

define('TAPSILAT_WOOCOMMERCE_VERSION', '2025.11.28.1');

// Custom Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'Tapsilat\\';
    $base_dir = plugin_dir_path(__FILE__) . 'libs/tapsilat-php/';

    // Check if the class uses the Tapsilat namespace
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    // Handle WooCommerce namespace separately
    if (strpos($class, 'Tapsilat\\WooCommerce\\') === 0) {
        $prefix = 'Tapsilat\\WooCommerce\\';
        $base_dir = plugin_dir_path(__FILE__) . 'includes/';
    }

    // Get the relative class name
    $relative_class = substr($class, strlen($prefix));

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    // If the file exists, require it
    if (file_exists($file)) {
        require $file;
    }
});

// Include our classes
require_once plugin_dir_path(__FILE__) . 'includes/Checkout/CheckoutProcessor.php';
require_once plugin_dir_path(__FILE__) . 'includes/Api/WebhookController.php';
require_once plugin_dir_path(__FILE__) . 'includes/Subscription/SubscriptionHelper.php';
require_once plugin_dir_path(__FILE__) . 'includes/Subscription/RenewalProcessor.php';
require_once plugin_dir_path(__FILE__) . 'includes/Subscription/ProductMeta.php';
require_once plugin_dir_path(__FILE__) . 'includes/Organization/OrganizationHelper.php';
require_once plugin_dir_path(__FILE__) . 'includes/System/SystemHelper.php';
require_once plugin_dir_path(__FILE__) . 'includes/Submerchant/SubmerchantHelper.php';

// Initialize the plugin after all plugins are loaded
add_action("plugins_loaded", "tapsilat_init_gateway", 11);
add_filter("woocommerce_payment_gateways", "tapsilat_add_gateway");

// Additional hook to ensure WooCommerce compatibility
add_action('woocommerce_loaded', 'tapsilat_woocommerce_loaded');

// Declare compatibility with WooCommerce features
add_action('before_woocommerce_init', function () {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
});

// Initialize Blocks support
add_action('woocommerce_blocks_loaded', function () {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/Blocks/BlocksCheckoutMethod.php';

    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function (Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry) {
            $payment_method_registry->register(new \Tapsilat\WooCommerce\Blocks\BlocksCheckoutMethod());
        }
    );
});

// Initialize REST API endpoints
add_action('rest_api_init', function () {
    $webhook_controller = new \Tapsilat\WooCommerce\Api\WebhookController();
    $webhook_controller->register_routes();
});

// Customers return here from the Tapsilat checkout (WC()->api_request_url('tapsilat_return'))
add_action('woocommerce_api_tapsilat_return', 'tapsilat_handle_return');

// Initialize cron job for order status checks
add_action('init', 'tapsilat_setup_cron');
add_action('tapsilat_check_order_status', 'tapsilat_check_pending_orders');
add_action('tapsilat_check_subscription_renewals', 'tapsilat_process_subscription_renewals');

// Plugin activation/deactivation hooks
register_activation_hook(__FILE__, 'tapsilat_activate_cron');
register_deactivation_hook(__FILE__, 'tapsilat_deactivate_cron');

function tapsilat_setup_cron()
{
    // Add custom cron schedule
    add_filter('cron_schedules', 'tapsilat_add_cron_schedule');

    // Get current interval setting
    $settings = get_option('woocommerce_tapsilat_settings', []);
    $interval = isset($settings['cron_interval']) ? (int) $settings['cron_interval'] : 5;
    if (!in_array($interval, [5, 10, 15, 30], true)) {
        $interval = 5;
    }
    $scheduleKey = "tapsilat_{$interval}_minutes";

    // This runs on every request, so reschedule only when the interval changed. Clearing and
    // scheduling every time made the next run always due, so the check ran on every request.
    if (wp_get_schedule('tapsilat_check_order_status') !== $scheduleKey) {
        wp_clear_scheduled_hook('tapsilat_check_order_status');
        wp_schedule_event(time() + $interval * MINUTE_IN_SECONDS, $scheduleKey, 'tapsilat_check_order_status');
    }

    // Schedule subscription renewals (check every hour)
    if (!wp_next_scheduled('tapsilat_check_subscription_renewals')) {
        wp_schedule_event(time() + HOUR_IN_SECONDS, 'hourly', 'tapsilat_check_subscription_renewals');
    }
}

function tapsilat_activate_cron()
{
    // Setup will be called by init hook
    tapsilat_setup_cron();
}

function tapsilat_deactivate_cron()
{
    wp_clear_scheduled_hook('tapsilat_check_order_status');
    wp_clear_scheduled_hook('tapsilat_check_subscription_renewals');
}

function tapsilat_add_cron_schedule($schedules)
{
    // Add all possible intervals
    $intervals = [5, 10, 15, 30];

    foreach ($intervals as $minutes) {
        $key = "tapsilat_{$minutes}_minutes";
        $schedules[$key] = array(
            'interval' => $minutes * 60,
            // translators: %d is the number of minutes for the cron schedule interval
            'display' => sprintf(__('Every %d Minutes (Tapsilat)', 'tapsilat-woocommerce'), $minutes)
        );
    }

    return $schedules;
}

function tapsilat_check_pending_orders()
{
    // Recent Tapsilat orders that may still change: unpaid ones, and failed or cancelled ones whose
    // payment may have completed late.
    $orders = wc_get_orders(array(
        'status' => array('pending', 'on-hold', 'failed', 'cancelled'),
        'payment_method' => 'tapsilat',
        'date_created' => '>' . (time() - 7 * DAY_IN_SECONDS),
        'limit' => 50, // Limit to avoid performance issues
        'orderby' => 'date',
        'order' => 'DESC'
    ));

    if (empty($orders)) {
        return;
    }

    $checkoutProcessor = new \Tapsilat\WooCommerce\Checkout\CheckoutProcessor();

    foreach ($orders as $order) {
        if (empty($order->get_meta('_tapsilat_reference_id'))) {
            continue;
        }

        // Skip if order was updated recently (within last 2 minutes)
        $lastModified = $order->get_date_modified();
        if ($lastModified && $lastModified->getTimestamp() > (time() - 120)) {
            continue;
        }

        // Marks paid orders paid and pending orders whose payment failed or expired failed.
        $checkoutProcessor->syncOrderStatus($order, __('cron check', 'tapsilat-woocommerce'));

        // Small delay to avoid overwhelming the API
        usleep(200000); // 0.2 second delay
    }
}

/**
 * Handles a customer returning from the Tapsilat checkout: checks the payment status with Tapsilat
 * and sends the customer, out of the checkout iframe if there is one, to the order received page or
 * back to the payment page.
 */
function tapsilat_handle_return()
{
    $order_id = isset($_GET['order_id']) ? absint(wp_unslash($_GET['order_id'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    $key = isset($_GET['key']) ? sanitize_text_field(wp_unslash($_GET['key'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

    $order = $order_id ? wc_get_order($order_id) : false;
    if (!$order || $key === '' || !hash_equals($order->get_order_key(), $key)) {
        wp_safe_redirect(wc_get_page_permalink('shop'));
        exit;
    }

    $checkoutProcessor = new \Tapsilat\WooCommerce\Checkout\CheckoutProcessor();
    $state = $checkoutProcessor->syncOrderStatus($order, __('customer returned', 'tapsilat-woocommerce'));

    if ($state === 'failed') {
        if (WC()->session) {
            wc_add_notice(__('Your payment could not be completed. Please try again.', 'tapsilat-woocommerce'), 'error');
        }
        $target = $order->get_checkout_payment_url();
    } else {
        if ($state === 'paid' && WC()->cart) {
            WC()->cart->empty_cart();
        }
        $target = $order->get_checkout_order_received_url();
    }

    // The checkout may be in an iframe on the payment page, so leave it from the top window.
    nocache_headers();
    header('Content-Type: text/html; charset=' . get_option('blog_charset'));
    echo '<!DOCTYPE html><html><head><meta charset="' . esc_attr(get_option('blog_charset')) . '">'
        . '<meta name="robots" content="noindex"><title>' . esc_html(get_bloginfo('name')) . '</title></head><body>'
        . '<script>window.top.location.replace(' . wp_json_encode(esc_url_raw($target)) . ');</script>'
        . '<noscript><a href="' . esc_url($target) . '" target="_top">' . esc_html__('Continue', 'tapsilat-woocommerce') . '</a></noscript>'
        . '</body></html>';
    exit;
}

function tapsilat_add_gateway($gateways)
{
    // Add gateway class to the list
    $gateways[] = 'WC_Gateway_Tapsilat';

    return $gateways;
}

function tapsilat_woocommerce_loaded()
{
    // Additional initialization when WooCommerce is fully loaded
    // No need to re-register gateway as it's already done via the filter
}

function tapsilat_init_gateway()
{

    // Prevent double loading
    if (class_exists('WC_Gateway_Tapsilat')) {
        return;
    }

    class WC_Gateway_Tapsilat extends WC_Payment_Gateway
    {

        private $checkoutProcessor;

        function __construct()
        {
            $this->id = "tapsilat";
            $this->icon = null;
            $this->has_fields = true;
            $this->method_title = "Tapsilat";
            $this->method_description = "Pay with Card/Debit Card or alternative payment methods";
            $this->supports = array('products', 'refunds');

            // Initialize form fields and settings
            $this->init_form_fields();
            $this->init_settings();

            // Load settings values
            $this->title = $this->get_option("title");
            $this->description = $this->get_option("description");
            $this->enabled = $this->get_option("enabled");

            // Set default values if empty
            if (empty($this->title)) {
                $this->title = __('Credit Card/Debit Card or alternative payment methods', 'tapsilat-woocommerce');
            }
            if (empty($this->description)) {
                $this->description = __('Pay securely using your credit/debit card or alternative payment methods.', 'tapsilat-woocommerce');
            }

            // Set icon
            $this->set_icon();

            // Initialize checkout processor
            $this->checkoutProcessor = new \Tapsilat\WooCommerce\Checkout\CheckoutProcessor();

            // Handle file upload with proper security checks
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'handle_logo_upload'));

            // Legacy file upload handling (will be deprecated)
            if (
                isset($_POST["woocommerce_tapsilat_enabled"]) &&
                isset($_POST['_wpnonce']) &&
                wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')
            ) {
                if (
                    isset($_FILES["woocommerce_tapsilat_logo"]) &&
                    !empty($_FILES["woocommerce_tapsilat_logo"]["name"]) &&
                    isset($_FILES["woocommerce_tapsilat_logo"]["error"]) &&
                    $_FILES["woocommerce_tapsilat_logo"]["error"] === UPLOAD_ERR_OK
                ) {

                    // Validate file type
                    $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
                    $file_type = wp_check_filetype(sanitize_file_name($_FILES["woocommerce_tapsilat_logo"]["name"]));

                    if (
                        in_array($file_type['type'], $allowed_types) &&
                        isset($_FILES["woocommerce_tapsilat_logo"]["tmp_name"])
                    ) {
                        $tmp_name = sanitize_text_field($_FILES["woocommerce_tapsilat_logo"]["tmp_name"]);
                        $upload = wp_upload_bits(
                            sanitize_file_name($_FILES["woocommerce_tapsilat_logo"]["name"]),
                            null,
                            file_get_contents($tmp_name)
                        );

                        // if upload is successful
                        if (!$upload["error"]) {
                            // get file url
                            $url = $upload["url"];
                            // get file path
                            $path = $upload["file"];
                            // get file type
                            $type = wp_check_filetype($path);
                            // set attachment data
                            $attachment = array(
                                "guid" => $url,
                                "post_mime_type" => $type["type"],
                                "post_title" => preg_replace("/\.[^.]+$/", "", basename($path)),
                                "post_content" => "",
                                "post_status" => "inherit"
                            );
                            // insert attachment
                            $attach_id = wp_insert_attachment($attachment, $path);
                            // set attachment id to tapsilat
                            $this->settings["tapsilat_logo"] = $attach_id;
                            // save tapsilat
                            update_option("woocommerce_tapsilat_settings", $this->settings);
                        }
                    }
                }
            }
            add_action("woocommerce_receipt_" . $this->id, array($this, "receipt"));
            add_action("woocommerce_thankyou_" . $this->id, array($this, "thankyou"));
            add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));

            // Add admin scripts for dynamic field visibility
            add_action('admin_enqueue_scripts', array($this, 'admin_scripts'));
        }

        /**
         * Process admin options and reschedule cron if interval changed
         */
        public function process_admin_options()
        {
            // Check user permissions
            if (!current_user_can('manage_woocommerce')) {
                wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'tapsilat-woocommerce'));
            }

            // Verify nonce for security (inherited from WooCommerce settings page)
            if (
                !isset($_POST['_wpnonce']) ||
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')
            ) {
                wp_die(esc_html__('Security check failed. Please try again.', 'tapsilat-woocommerce'));
            }

            $oldSettings = $this->settings;

            // Sanitize settings before saving (with nonce already verified)
            if (isset($_POST['woocommerce_tapsilat_Token'])) {
                $_POST['woocommerce_tapsilat_Token'] = sanitize_text_field(wp_unslash($_POST['woocommerce_tapsilat_Token']));
            }

            if (isset($_POST['woocommerce_tapsilat_title'])) {
                $_POST['woocommerce_tapsilat_title'] = sanitize_text_field(wp_unslash($_POST['woocommerce_tapsilat_title']));
            }

            if (isset($_POST['woocommerce_tapsilat_description'])) {
                $_POST['woocommerce_tapsilat_description'] = sanitize_textarea_field(wp_unslash($_POST['woocommerce_tapsilat_description']));
            }

            if (isset($_POST['woocommerce_tapsilat_webhook_secret'])) {
                $_POST['woocommerce_tapsilat_webhook_secret'] = sanitize_text_field(wp_unslash($_POST['woocommerce_tapsilat_webhook_secret']));
            }

            if (isset($_POST['woocommerce_tapsilat_custom_api_url'])) {
                $_POST['woocommerce_tapsilat_custom_api_url'] = esc_url_raw(wp_unslash($_POST['woocommerce_tapsilat_custom_api_url']));
            }

            $result = parent::process_admin_options();

            // Check if cron interval changed
            $newSettings = $this->get_option('cron_interval');
            $oldInterval = isset($oldSettings['cron_interval']) ? $oldSettings['cron_interval'] : '5';

            if ($newSettings !== $oldInterval) {
                // Reschedule cron job with new interval
                tapsilat_setup_cron();
            }

            return $result;
        }

        /**
         * Handle logo upload with proper security checks
         */
        public function handle_logo_upload()
        {
            // Check user permissions first
            if (!current_user_can('manage_woocommerce')) {
                wp_die(esc_html__('You do not have sufficient permissions to perform this action.', 'tapsilat-woocommerce'));
            }

            // Verify nonce for security
            if (
                !isset($_POST['_wpnonce']) ||
                !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'woocommerce-settings')
            ) {
                return;
            }

            // Check if file was uploaded
            if (
                !isset($_FILES["woocommerce_tapsilat_logo"]) ||
                empty($_FILES["woocommerce_tapsilat_logo"]["name"]) ||
                !isset($_FILES["woocommerce_tapsilat_logo"]["error"]) ||
                $_FILES["woocommerce_tapsilat_logo"]["error"] !== UPLOAD_ERR_OK
            ) {
                return;
            }

            // Validate file type
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
            $file_name = sanitize_file_name($_FILES["woocommerce_tapsilat_logo"]["name"]);
            $file_type = wp_check_filetype($file_name);

            if (
                !in_array($file_type['type'], $allowed_types) ||
                !isset($_FILES["woocommerce_tapsilat_logo"]["tmp_name"])
            ) {
                return;
            }

            // Handle file upload
            $tmp_name = sanitize_text_field($_FILES["woocommerce_tapsilat_logo"]["tmp_name"]);
            $upload = wp_upload_bits(
                $file_name,
                null,
                file_get_contents($tmp_name)
            );

            if (!$upload["error"]) {
                // Create attachment
                $attachment = array(
                    "guid" => $upload["url"],
                    "post_mime_type" => $file_type["type"],
                    "post_title" => preg_replace("/\.[^.]+$/", "", basename($upload["file"])),
                    "post_content" => "",
                    "post_status" => "inherit"
                );

                $attach_id = wp_insert_attachment($attachment, $upload["file"]);

                // Update setting
                $settings = get_option("woocommerce_tapsilat_settings", array());
                $settings["tapsilat_logo"] = $attach_id;
                update_option("woocommerce_tapsilat_settings", $settings);
            }
        }

        public function init_form_fields()
        {
            // Get current logo for preview
            $current_logo = $this->get_current_logo_preview();

            $this->form_fields = array(
                // === BASIC SETTINGS ===
                "basic_settings" => array(
                    "title" => "Basic Settings",
                    "type" => "title",
                    "description" => "Configure the main settings for Tapsilat payment gateway."
                ),
                "enabled" => array(
                    "title" => "Enable/Disable",
                    "label" => "Enable Tapsilat Payment Gateway",
                    "type" => "checkbox",
                    "description" => "Enable Tapsilat to accept payments on your store.",
                    "default" => "yes"
                ),
                "title" => array(
                    "title" => "Title",
                    "type" => "text",
                    "description" => "The title customers will see during checkout.",
                    "default" => "Credit Card/Debit Card or alternative payment methods",
                    "desc_tip" => true
                ),
                "description" => array(
                    "title" => "Description",
                    "type" => "textarea",
                    "description" => "Payment method description shown to customers.",
                    "default" => "Pay securely using your credit/debit card or alternative payment methods.",
                    "desc_tip" => true
                ),
                "tapsilat_logo" => array(
                    "title" => "Payment Method Logo",
                    "type" => "file",
                    "description" => "Upload a custom logo for the payment method. Recommended size: 48x32px (PNG format)" . $current_logo,
                    "desc_tip" => false
                ),

                // === API CREDENTIALS ===
                "api_credentials" => array(
                    "title" => "API Credentials",
                    "type" => "title",
                    "description" => "Get these credentials from your Tapsilat merchant panel."
                ),
                "Token" => array(
                    "title" => "API Token",
                    "type" => "password",
                    "description" => "Your Tapsilat API token. You can find this in your Tapsilat merchant dashboard.",
                    "desc_tip" => true,
                    "custom_attributes" => array(
                        "autocomplete" => "current-password"
                    )
                ),
                "API" => array(
                    "title" => "API Environment",
                    "type" => "select",
                    "description" => "Select production for live payments or custom for development/testing.",
                    "default" => "production",
                    "options" => array(
                        "production" => "Production (panel.tapsilat.com/api/v1)",
                        "custom" => "Custom/Development Environment",
                    ),
                    "desc_tip" => true,
                    "class" => "api-environment-select"
                ),
                "custom_api_url" => array(
                    "title" => "Custom API URL",
                    "type" => "text",
                    "description" => "Enter your custom Tapsilat API URL. Leave empty to use default dev environment (panel.tapsilat.dev/api/v1).",
                    "default" => "https://panel.tapsilat.dev/api/v1",
                    "desc_tip" => true,
                    "class" => "custom-api-url-field",
                    "placeholder" => "https://panel.tapsilat.dev/api/v1"
                ),
                "webhook_secret" => array(
                    "title" => "Webhook Secret",
                    "type" => "password",
                    "description" => "The webhook secret from the callback settings of your Tapsilat panel. Callbacks whose Tapsilat-Signature header does not match it are rejected. Payment statuses are always confirmed with the Tapsilat API.",
                    "desc_tip" => true,
                    "custom_attributes" => array(
                        "autocomplete" => "new-password"
                    )
                ),

                // === PAYMENT SETTINGS ===
                "payment_settings" => array(
                    "title" => "Payment Settings",
                    "type" => "title",
                    "description" => "Configure payment options and security settings."
                ),
                "Currency" => array(
                    "title" => "Currency",
                    "type" => "select",
                    "description" => "The currency for transactions. Make sure this matches your store currency.",
                    "default" => "TRY",
                    "options" => array(
                        "TRY" => "₺ Turkish Lira (TRY)",
                        "USD" => "$ US Dollar (USD)",
                        "EUR" => "€ Euro (EUR)",
                    ),
                    "desc_tip" => true
                ),
                "3d" => array(
                    "title" => "3D Secure",
                    "label" => "Enable 3D Secure authentication",
                    "type" => "checkbox",
                    "description" => "Enable 3D Secure for additional security. Recommended for live payments.",
                    "default" => "yes"
                ),
                "payment_form_view_mode" => array(
                    "title" => "Payment Form Display",
                    "type" => "select",
                    "description" => "How the payment form should be displayed to customers.",
                    "default" => "iframe",
                    "options" => array(
                        "iframe" => "Embedded (Iframe) - Recommended",
                        "redirect" => "Redirect to Tapsilat",
                        "popup" => "Popup Window",
                    ),
                    "desc_tip" => true
                ),

                // === DESIGN CUSTOMIZATION ===
                "design_settings" => array(
                    "title" => "Design Customization",
                    "type" => "title",
                    "description" => "Customize the appearance of the payment form to match your brand."
                ),
                "input_background_color" => array(
                    "title" => "Input Background Color",
                    "type" => "color",
                    "description" => "Background color for input fields in the payment form.",
                    "default" => "#ffffff",
                    "desc_tip" => true
                ),
                "input_text_color" => array(
                    "title" => "Input Text Color",
                    "type" => "color",
                    "description" => "Text color for input fields in the payment form.",
                    "default" => "#000000",
                    "desc_tip" => true
                ),
                "label_text_color" => array(
                    "title" => "Label Text Color",
                    "type" => "color",
                    "description" => "Color for field labels in the payment form.",
                    "default" => "#000000",
                    "desc_tip" => true
                ),
                "left_background_color" => array(
                    "title" => "Left Panel Background",
                    "type" => "color",
                    "description" => "Background color for the left panel of the payment form.",
                    "default" => "#ffffff",
                    "desc_tip" => true
                ),
                "right_background_color" => array(
                    "title" => "Right Panel Background",
                    "type" => "color",
                    "description" => "Background color for the right panel of the payment form.",
                    "default" => "#ffffff",
                    "desc_tip" => true
                ),
                "pay_button_color" => array(
                    "title" => "Pay Button Color",
                    "type" => "color",
                    "description" => "Background color for the payment button.",
                    "default" => "#1e40af",
                    "desc_tip" => true
                ),
                "text_color" => array(
                    "title" => "General Text Color",
                    "type" => "color",
                    "description" => "General text color for the payment form.",
                    "default" => "#000000",
                    "desc_tip" => true
                ),

                // === ADVANCED SETTINGS ===
                "advanced_settings" => array(
                    "title" => "Advanced Settings",
                    "type" => "title",
                    "description" => "Advanced configuration options."
                ),
                "logo" => array(
                    "title" => "Checkout Page Logo",
                    "type" => "file",
                    "description" => "Logo to display on the Tapsilat checkout page (separate from payment method logo).",
                    "desc_tip" => true
                ),
                "order_detail_html" => array(
                    "title" => "Order Detail HTML",
                    "type" => "textarea",
                    "description" => "Custom HTML to display in the order details section of the payment form.",
                    "desc_tip" => true
                ),
                "redirect_url" => array(
                    "title" => "Custom Redirect URL",
                    "type" => "text",
                    "description" => "Custom URL to redirect customers after payment (optional).",
                    "desc_tip" => true
                ),
                "custom_metadata" => array(
                    "title" => "Custom Metadata",
                    "type" => "textarea",
                    "description" => "Additional metadata to send with orders (JSON format). Example: {\"store_id\":\"123\",\"branch\":\"main\"}",
                    "desc_tip" => true,
                    "placeholder" => '{"store_id":"123","branch":"main"}'
                ),
                "cron_interval" => array(
                    "title" => "Order Status Check Frequency",
                    "type" => "select",
                    "description" => "How often the system should check and update order statuses automatically.",
                    "default" => "5",
                    "options" => array(
                        "5" => "Every 5 minutes (Recommended)",
                        "10" => "Every 10 minutes",
                        "15" => "Every 15 minutes",
                        "30" => "Every 30 minutes"
                    ),
                    "desc_tip" => true
                ),

                // === SUBSCRIPTION SETTINGS ===
                "subscription_settings" => array(
                    "title" => "Subscription Settings",
                    "type" => "title",
                    "description" => "Configure subscription and recurring payment options. Supports both regular product sales and subscription-based products."
                ),
                "enable_subscriptions" => array(
                    "title" => "Enable Subscriptions",
                    "label" => "Enable subscription and recurring payment support",
                    "type" => "checkbox",
                    "description" => "Allow customers to purchase subscriptions with automatic recurring payments. Works alongside regular product sales.",
                    "default" => "no",
                    "desc_tip" => true
                ),
                "subscription_period" => array(
                    "title" => "Default Subscription Period",
                    "type" => "select",
                    "description" => "Default billing period for subscriptions (in days). Can be overridden per product.",
                    "default" => "30",
                    "options" => array(
                        "7" => "Weekly (7 days)",
                        "15" => "Bi-weekly (15 days)",
                        "30" => "Monthly (30 days)",
                        "60" => "Bi-monthly (60 days)",
                        "90" => "Quarterly (90 days)",
                        "180" => "Semi-annually (180 days)",
                        "365" => "Annually (365 days)"
                    ),
                    "desc_tip" => true,
                    "class" => "subscription-field"
                ),
                "subscription_cycle" => array(
                    "title" => "Default Subscription Cycle",
                    "type" => "number",
                    "description" => "Number of billing cycles before subscription ends. Set to 0 for unlimited.",
                    "default" => "12",
                    "custom_attributes" => array(
                        "min" => "0",
                        "step" => "1"
                    ),
                    "desc_tip" => true,
                    "class" => "subscription-field"
                ),
                "subscription_payment_date" => array(
                    "title" => "Payment Day of Month",
                    "type" => "number",
                    "description" => "Day of the month when recurring payments should be charged (1-28).",
                    "default" => "1",
                    "custom_attributes" => array(
                        "min" => "1",
                        "max" => "28",
                        "step" => "1"
                    ),
                    "desc_tip" => true,
                    "class" => "subscription-field"
                ),
                "subscription_auto_renewal" => array(
                    "title" => "Auto Renewal",
                    "label" => "Automatically renew subscriptions",
                    "type" => "checkbox",
                    "description" => "Automatically process renewal payments when due. Disable for manual renewal only.",
                    "default" => "yes",
                    "class" => "subscription-field"
                ),

                // === WEBHOOK CONFIGURATION ===

                "webhook_urls" => array(
                    "title" => "Webhook Configuration",
                    "type" => "title",
                    "description" => "
                        <div style='background: #f9f9f9; padding: 15px; border-left: 4px solid #1e40af; margin: 10px 0;'>
                        <strong>🔗 Payment callbacks</strong><br><br>
                        Every order the plugin creates tells Tapsilat where to send its payment callbacks and where to return the customer, so no setup is needed for them.<br><br>
                        To also receive your organization's callbacks, enter this URL as the callback, fail, cancel and refund callback URLs in the callback settings of your Tapsilat panel:<br>
                        <code>" . esc_html(rest_url('tapsilat/v1/webhook/payment-callback')) . "</code><br><br>
                        <em>💡 Copy the webhook secret from the same page into the Webhook Secret field above so that callbacks are verified.</em>
                        </div>
                    ",
                ),

                // === SYSTEM STATUS ===
                "system_status" => array(
                    "title" => "System Status",
                    "type" => "title",
                    "description" => $this->get_system_status_info()
                ),
            );
        }

        /**
         * Check if gateway is available
         */
        public function is_available()
        {
            // Check if enabled
            if ('yes' !== $this->enabled) {
                return false;
            }

            // Check if required settings are configured
            if (empty($this->get_option('Token'))) {
                return false;
            }

            return parent::is_available();
        }

        /**
         * Admin scripts for dynamic form fields
         */
        public function admin_scripts($hook)
        {
            // Only load on WooCommerce settings pages
            if (strpos($hook, 'woocommerce_page_wc-settings') === false) {
                return;
            }

            // Check if we're on the payments tab and Tapsilat section using WordPress built-in functions
            $current_screen = get_current_screen();
            if (!$current_screen || $current_screen->id !== 'woocommerce_page_wc-settings') {
                return;
            }

            // Use WordPress built-in functions to safely check current page context
            if (!is_admin()) {
                return;
            }

            // Check if we're on the correct WooCommerce settings page and section
            global $woocommerce_settings;
            $current_tab = isset($woocommerce_settings['current_tab']) ? $woocommerce_settings['current_tab'] :
                (function_exists('wc_get_current_admin_url') ? basename(wp_parse_url(wc_get_current_admin_url(), PHP_URL_PATH)) : '');

            // Alternative method: check using WordPress admin page detection
            if (function_exists('get_admin_page_title')) {
                $page_title = get_admin_page_title();
                if (strpos($page_title, 'WooCommerce') === false) {
                    return;
                }
            }

            // Final check using WordPress request URI parsing (safer than direct $_GET access)
            $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
            if (strpos($request_uri, 'tab=checkout') === false || strpos($request_uri, 'section=tapsilat') === false) {
                return;
            }



            ?>
            <script type="text/javascript">
                jQuery(function ($) {
                    'use strict';

                    // Function to toggle custom URL fields
                    function toggleCustomFields() {
                        var apiEnv = $('#woocommerce_tapsilat_API').val();
                        var customApiRow = $('#woocommerce_tapsilat_custom_api_url').closest('tr');
                        var customApiField = $('#woocommerce_tapsilat_custom_api_url');

                        if (apiEnv === 'custom') {
                            customApiRow.show();
                            customApiField.prop('disabled', false).removeClass('disabled');
                        } else {
                            customApiRow.hide();
                            customApiField.prop('disabled', true).addClass('disabled');
                        }
                    }

                    // Hide the custom API URL field initially
                    $('#woocommerce_tapsilat_custom_api_url').closest('tr').hide();

                    // Initial toggle based on current value
                    toggleCustomFields();

                    // Toggle on change
                    $('#woocommerce_tapsilat_API').on('change', toggleCustomFields);

                    // Add token visibility toggle functionality
                    function initTokenToggle() {
                        var tokenField = $('#woocommerce_tapsilat_Token');

                        if (tokenField.length === 0) {
                            // Try again after a delay if field not found
                            setTimeout(initTokenToggle, 500);
                            return;
                        }

                        // Check if button already exists
                        if ($('#tapsilat-token-toggle').length > 0) {
                            return;
                        }

                        console.log('Tapsilat: Adding token toggle button');

                        // Wrap token field in a container for better control
                        var wrapper = tokenField.wrap('<div style="position: relative; display: inline-block; width: 100%;"></div>').parent();

                        // Create toggle button
                        var toggleButton = $('<button>', {
                            'type': 'button',
                            'id': 'tapsilat-token-toggle',
                            'class': 'button button-secondary',
                            'style': 'position: absolute; right: 5px; top: 50%; transform: translateY(-50%); font-size: 11px; padding: 2px 6px; height: 24px; z-index: 10;',
                            'title': 'Toggle token visibility',
                            'html': '👁️'
                        });

                        // Adjust input padding to make room for button
                        tokenField.css('padding-right', '40px');

                        // Add button to wrapper
                        wrapper.append(toggleButton);

                        console.log('Tapsilat: Token toggle button added');
                    }

                    // Token visibility toggle functionality
                    $(document).on('click', '#tapsilat-token-toggle', function (e) {
                        e.preventDefault();
                        e.stopPropagation();

                        var button = $(this);
                        var tokenField = $('#woocommerce_tapsilat_Token');

                        if (tokenField.length === 0) {
                            console.warn('Tapsilat: Token field not found for toggle');
                            return;
                        }

                        var currentType = tokenField.prop('type');
                        console.log('Tapsilat: Current token field type:', currentType);

                        if (currentType === 'password') {
                            // Show token
                            tokenField.prop('type', 'text');
                            button.html('🙈').css({
                                'background-color': '#dc3545',
                                'color': 'white',
                                'border-color': '#dc3545'
                            }).attr('title', 'Hide token');
                            console.log('Tapsilat: Token shown');
                        } else {
                            // Hide token
                            tokenField.prop('type', 'password');
                            button.html('👁️').css({
                                'background-color': '',
                                'color': '',
                                'border-color': ''
                            }).attr('title', 'Show token');
                            console.log('Tapsilat: Token hidden');
                        }
                    });

                    // Initialize token toggle with multiple attempts
                    initTokenToggle();

                    // Also try when DOM is fully loaded
                    $(window).on('load', function () {
                        setTimeout(initTokenToggle, 100);
                    });
                });
            </script>
            <?php
        }

        /**
         * Payment form on checkout page
         */
        public function payment_fields()
        {
            if ($this->description) {
                echo '<p>' . wp_kses_post($this->description) . '</p>';
            }
        }

        public function process_payment($orderid)
        {
            $order = wc_get_order($orderid);
            $settings = get_option("woocommerce_tapsilat_settings", array());

            // In redirect mode, send the customer straight to the Tapsilat checkout.
            if (isset($settings["payment_form_view_mode"]) && $settings["payment_form_view_mode"] === "redirect") {
                $checkout = $this->prepare_checkout($order);
                if (!empty($checkout['paid'])) {
                    return array("result" => "success", "redirect" => $this->get_return_url($order));
                }
                if (empty($checkout['url'])) {
                    wc_add_notice(__('Payment system error. Please try again.', 'tapsilat-woocommerce'), 'error');
                    return array("result" => "failure");
                }
                return array("result" => "success", "redirect" => $checkout['url']);
            }

            return array(
                "result" => "success",
                "redirect" => $order->get_checkout_payment_url(true),
            );
        }

        /**
         * Process refund
         *
         * @param int $order_id
         * @param float|null $amount
         * @param string $reason
         * @return bool|WP_Error
         */
        public function process_refund($order_id, $amount = null, $reason = '')
        {
            $order = wc_get_order($order_id);

            if (!$order) {
                return new WP_Error('invalid_order', __('Invalid order ID', 'tapsilat-woocommerce'));
            }

            $referenceId = $order->get_meta('_tapsilat_reference_id');

            if (!$referenceId) {
                return new WP_Error('missing_reference', __('Tapsilat reference ID not found', 'tapsilat-woocommerce'));
            }

            if (!$this->checkoutProcessor->refundOrder($referenceId, $amount)) {
                return new WP_Error('refund_failed', __('Refund failed in Tapsilat', 'tapsilat-woocommerce'));
            }

            $order->add_order_note(sprintf(__('Refunded %s via Tapsilat', 'tapsilat-woocommerce'), wc_price($amount)));
            return true;
        }
        function receipt($orderid)
        {
            $order = wc_get_order($orderid);
            if (!$order || !$order->needs_payment()) {
                return;
            }

            $checkout = $this->prepare_checkout($order);
            if (!empty($checkout['paid'])) {
                $this->leave_page($this->get_return_url($order));
                return;
            }
            if (empty($checkout['url'])) {
                echo '<ul class="woocommerce-error" role="alert"><li>' . esc_html__('Payment system error. Please try again.', 'tapsilat-woocommerce') . '</li></ul>';
                return;
            }

            $settings = get_option("woocommerce_tapsilat_settings", array());
            $mode = isset($settings["payment_form_view_mode"]) ? $settings["payment_form_view_mode"] : "iframe";
            $checkoutUrl = $checkout['url'];

            // Handle different display modes
            if ($mode == "redirect") {
                $this->leave_page($checkoutUrl);
            } elseif ($mode == "popup") {
                include plugin_dir_path(__FILE__) . "popup.php";
            } else {
                include plugin_dir_path(__FILE__) . "form.php";
            }
        }

        /**
         * A customer can reach the order received page before the payment callback arrives, so
         * check the payment status there.
         */
        public function thankyou($orderid)
        {
            $order = wc_get_order($orderid);
            if ($order && $order->needs_payment() && $order->get_meta('_tapsilat_reference_id')) {
                $this->checkoutProcessor->syncOrderStatus($order, __('order received page', 'tapsilat-woocommerce'), false);
            }
        }

        /**
         * The Tapsilat checkout for the order: that of its Tapsilat order while it is unpaid and
         * still matches the order, or else that of a new Tapsilat order.
         *
         * @param WC_Order $order
         * @return array ['paid' => true], ['url' => string], or [] when no checkout is available
         */
        private function prepare_checkout($order)
        {
            $referenceId = $order->get_meta('_tapsilat_reference_id');
            if ($referenceId) {
                $state = $this->checkoutProcessor->syncOrderStatus($order, __('payment page', 'tapsilat-woocommerce'), false);
                if ($state === 'paid') {
                    return array('paid' => true);
                }
                // Reuse the checkout unless that payment failed, was cancelled, expired or the
                // order changed since; also when the status could not be checked, rather than
                // starting a second payment for the same order.
                if (($state === 'pending' || $state === null) && $this->checkoutProcessor->isCurrentAttempt($order)) {
                    $checkoutUrl = $this->checkoutProcessor->getCheckoutUrl($referenceId);
                    if ($checkoutUrl) {
                        return array('url' => $checkoutUrl);
                    }
                }
            }

            $response = $this->checkoutProcessor->createOrder($order);
            if (!$response || empty($response['reference_id'])) {
                return array();
            }

            $checkoutUrl = !empty($response['checkout_url'])
                ? $response['checkout_url']
                : $this->checkoutProcessor->getCheckoutUrl($response['reference_id']);

            return $checkoutUrl ? array('url' => $checkoutUrl) : array();
        }

        /**
         * Sends the customer to $url, also when the page has already started to render.
         */
        private function leave_page($url)
        {
            if (!headers_sent()) {
                wp_redirect($url);
                exit;
            }

            echo '<script>window.location.replace(' . wp_json_encode(esc_url_raw($url)) . ');</script>';
            echo '<p><a href="' . esc_url($url) . '">' . esc_html__('Continue', 'tapsilat-woocommerce') . '</a></p>';
        }

        /**
         * Get current logo preview HTML
         */
        private function get_current_logo_preview()
        {
            $logo_attachment_id = $this->get_option('tapsilat_logo');
            $preview_html = '';

            if ($logo_attachment_id) {
                $logo_url = wp_get_attachment_url($logo_attachment_id);
                if ($logo_url) {
                    $preview_html = '<br><div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                        <strong>Current Logo:</strong><br>
                        <img src="' . esc_url($logo_url) . '" style="max-width: 100px; max-height: 60px; margin-top: 5px; border: 1px solid #ccc;" alt="Current Logo">
                    </div>';
                }
            } else {
                $default_logo = plugin_dir_url(__FILE__) . 'assets/logo.png';
                $preview_html = '<br><div style="margin-top: 10px; padding: 10px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                    <strong>Default Logo (will be used if no custom logo uploaded):</strong><br>
                    <img src="' . esc_url($default_logo) . '" style="max-width: 100px; max-height: 60px; margin-top: 5px; border: 1px solid #ccc;" alt="Default Logo">
                </div>';
            }

            return $preview_html;
        }

        /**
         * Get system status information
         */
        private function get_system_status_info()
        {
            $nextCron = wp_next_scheduled('tapsilat_check_order_status');
            $cronInterval = $this->get_option('cron_interval', '5');

            $cronStatus = $nextCron ?
                'Active (Next run: ' . wp_date('Y-m-d H:i:s', $nextCron) . ')' :
                'Not scheduled';

            $pendingOrders = wc_get_orders(array(
                'status' => array('pending', 'on-hold'),
                'payment_method' => 'tapsilat',
                'limit' => 1,
                'return' => 'ids'
            ));

            $pendingCount = count($pendingOrders);

            // Get interval display text
            $intervalText = $cronInterval . ' minute' . ($cronInterval > 1 ? 's' : '');

            return "
                <div style='background: #f0f8ff; padding: 15px; border-left: 4px solid #0073aa; margin: 10px 0;'>
                <strong>📊 System Status:</strong><br><br>
                <strong>Cron Job Status:</strong> $cronStatus<br>
                <strong>Check Interval:</strong> Every $intervalText<br>
                <strong>Pending Orders:</strong> $pendingCount orders waiting for status update<br><br>
                <em>ℹ️ Order statuses are automatically checked based on your selected interval. Change the interval above and save to reschedule.</em>
                </div>
            ";
        }

        /**
         * Set payment method icon
         */
        private function set_icon()
        {
            // Check if there's a custom logo from WordPress media library
            $logo_attachment_id = $this->get_option('tapsilat_logo');
            if ($logo_attachment_id) {
                $logo_url = wp_get_attachment_url($logo_attachment_id);
                if ($logo_url) {
                    $this->icon = $logo_url;
                    return;
                }
            }

            // Default to plugin's logo file
            $this->icon = plugin_dir_url(__FILE__) . 'assets/logo.png';
        }
    }
}
