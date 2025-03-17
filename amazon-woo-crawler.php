<?php
/**
 * Plugin Name: Amazon to WooCommerce Crawler
 * Plugin URI: https://github.com/phamthainb/amazon-woo-crawler
 * Description: A WordPress plugin to scrape Amazon product data and import it into WooCommerce.
 * Version: 1.0
 * Author: phamthainb
 * Author URI: https://github.com/phamthainb
 * License: GPL2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AMAZON_WOO_CRAWLER_DIR', plugin_dir_path(__FILE__));
define('AMAZON_WOO_CRAWLER_URL', plugin_dir_url(__FILE__));

// Include required files
require_once AMAZON_WOO_CRAWLER_DIR . 'includes/admin-page.php';
require_once AMAZON_WOO_CRAWLER_DIR . 'includes/scraper.php';
require_once AMAZON_WOO_CRAWLER_DIR . 'includes/importer.php';
require_once AMAZON_WOO_CRAWLER_DIR . 'includes/api.php';
require_once AMAZON_WOO_CRAWLER_DIR . 'includes/test.php';

// Activation hook
function amazon_woo_crawler_activate()
{
    // Add necessary options on activation
    add_option('amazon_woo_crawler_settings', []);
}
register_activation_hook(__FILE__, 'amazon_woo_crawler_activate');

// Deactivation hook
function amazon_woo_crawler_deactivate()
{
    // Clean up options if necessary
    delete_option('amazon_woo_crawler_settings');
}
register_deactivation_hook(__FILE__, 'amazon_woo_crawler_deactivate');

// Admin menu setup
function amazon_woo_crawler_admin_menu()
{
    add_menu_page(
        'Amazon Crawler',
        'Amazon Crawler',
        'manage_options',
        'amazon-woo-crawler',
        'amazon_woo_crawler_admin_page',
        'dashicons-cart'
    );
}
add_action('admin_menu', 'amazon_woo_crawler_admin_menu');

