<?php
/**
 * Uninstall script for Tapsilat WooCommerce plugin
 * 
 * This file is called when the plugin is deleted from the WordPress admin.
 * It removes all plugin options and cleans up the database.
 */

// Prevent direct access
defined("WP_UNINSTALL_PLUGIN") || exit;

// Remove plugin options
delete_option("woocommerce_tapsilat_settings");

// Remove any transient cache data
delete_transient('tapsilat_order_cache');

// Clear scheduled cron jobs
wp_clear_scheduled_hook('tapsilat_check_order_status');

// Remove custom cron schedules (they will be automatically removed when the plugin is deleted)

// Log uninstall for debugging purposes
error_log('Tapsilat WooCommerce: Plugin uninstalled and all settings cleaned up');

// Note: We don't remove order meta data or logs as those might be needed for accounting purposes
// Order data in wp_posts and wp_postmeta tables with payment_method = 'tapsilat' is preserved