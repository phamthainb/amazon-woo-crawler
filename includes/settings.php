<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Add settings menu
function amazon_woo_crawler_settings_menu() {
    add_options_page(
        'Amazon Crawler Settings',
        'Amazon Crawler Settings',
        'manage_options',
        'amazon-woo-crawler-settings',
        'amazon_woo_crawler_settings_page'
    );
}
add_action('admin_menu', 'amazon_woo_crawler_settings_menu');

// Settings page content
function amazon_woo_crawler_settings_page() {
    ?>
    <div class="wrap">
        <h1>Amazon Crawler Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('amazon_woo_crawler_options');
            do_settings_sections('amazon-woo-crawler-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register settings
function amazon_woo_crawler_register_settings() {
    register_setting('amazon_woo_crawler_options', 'amazon_woo_crawler_api_key');
}
add_action('admin_init', 'amazon_woo_crawler_register_settings');
