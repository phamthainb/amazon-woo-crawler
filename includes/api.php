<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Register REST API routes
function amazon_woo_crawler_register_api()
{
    register_rest_route('amazon-crawler/v1', '/scrape/', array(
        'methods' => 'GET',
        'callback' => 'amazon_woo_crawler_api_scrape',
        'permission_callback' => '__return_true', // Make it public or restrict it as needed
    ));

    register_rest_route('amazon-crawler/v1', '/import/', array(
        'methods' => 'POST',
        'callback' => 'amazon_woo_crawler_api_import',
        'permission_callback' => function () {
            return current_user_can('edit_posts'); // Restrict to admins
        },
    ));

    // test only
    register_rest_route('amazon-crawler/v1', '/scrape_test/', array(
        'methods' => 'GET',
        'callback' => 'amazon_woo_crawler_api_scrape_test',
        'permission_callback' => '__return_true', // Make it public or restrict it as needed
    ));
}
add_action('rest_api_init', 'amazon_woo_crawler_register_api');

// API handler: Scrape Amazon product data
function amazon_woo_crawler_api_scrape(WP_REST_Request $request)
{
    $url = $request->get_param('url');
    // Retrieve proxy settings
    $proxy_url = $request->get_param('proxy_url');
    $proxy_username = $request->get_param('proxy_username');
    $proxy_password = $request->get_param('proxy_password');


    if (empty($url)) {
        return new WP_Error('missing_url', 'Amazon product URL is required.', ['status' => 400]);
    }

    require_once plugin_dir_path(__FILE__) . 'scraper.php';

    $product_data = amazon_woo_crawler_scrape($url, $proxy_url, $proxy_username, $proxy_password);

    if (!$product_data) {
        return new WP_Error('scrape_failed', 'Failed to scrape product data.', ['status' => 500]);
    }

    return rest_ensure_response($product_data);
}

// API handler: Import scraped product into WooCommerce
function amazon_woo_crawler_api_import(WP_REST_Request $request)
{
    $product_data = $request->get_json_params();

    if (empty($product_data) || !isset($product_data['title'])) {
        return new WP_Error('invalid_data', 'Invalid product data.', ['status' => 400]);
    }

    require_once plugin_dir_path(__FILE__) . 'importer.php';

    return amazon_woo_crawler_import($product_data);

}





// API handler: Scrape Amazon product data
function amazon_woo_crawler_api_scrape_test(WP_REST_Request $request)
{
    $url = $request->get_param('url');
    // Retrieve proxy settings
    $proxy_url = $request->get_param('proxy_url');
    $proxy_username = $request->get_param('proxy_username');
    $proxy_password = $request->get_param('proxy_password');


    if (empty($url)) {
        return new WP_Error('missing_url', 'Amazon product URL is required.', ['status' => 400]);
    }

    require_once plugin_dir_path(__FILE__) . 'test.php';

    $product_data = amazon_woo_crawler_scrape_test($url, $proxy_url, $proxy_username, $proxy_password);

    if (!$product_data) {
        return new WP_Error('scrape_failed', 'Failed to scrape product data.', ['status' => 500]);
    }

    return rest_ensure_response($product_data);
}
