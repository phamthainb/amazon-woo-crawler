<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function amazon_woo_crawler_admin_page()
{
    ?>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <div class="wrap p-6">
        <h1 class="text-2xl font-bold mb-4">Amazon to WooCommerce Crawler</h1>

        <!-- Settings Form -->
        <button class="collapsible w-full bg-gray-200 px-4 py-2 text-left text-lg font-semibold">Settings</button>
        <div class="content p-4 border rounded bg-white" style="display: none;">
            <form id="amazon-crawler-settings" class="space-y-4">
                <label for="proxy_list" class="block text-sm font-medium text-gray-700">Proxy List (one per line):</label>
                <textarea placeholder="ip:port:user:pass, ip:port" id="proxy_list" name="proxy_list" rows="3"
                    class="w-full border rounded p-2"></textarea>
                <button type="submit" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Save Settings
                </button>
            </form>
        </div>

        <hr class="my-6">
        <!-- URL Input Form -->
        <div class="content p-4 border rounded bg-white">
            <form id="amazon-crawler-form" class="space-y-4">
                <label for="amazon_urls" class="block text-sm font-medium text-gray-700">Amazon Product URLs (one per
                    line):</label>
                <textarea id="amazon_urls" name="amazon_urls" rows="5"
                    class="w-full border rounded p-2">https://www.amazon.com/dp/B07WC1846W</textarea>
                <button id="start-scraping" type="submit"
                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Start
                    Scraping
                </button>
                <button id="clear-data" type="button" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Clear
                </button>
            </form>
        </div>

        <hr class="my-6">



        <!-- Results Section -->
        <button class="collapsible w-full bg-gray-200 px-4 py-2 text-left text-lg font-semibold">Scraped Products</button>
        <div class="content p-4 border rounded bg-white">
            <table class="w-full border-collapse border border-gray-200">
                <thead>
                    <tr class="bg-gray-100">
                        <th class="border px-4 py-2">#</th>
                        <th class="border px-4 py-2">SKU</th>
                        <th class="border px-4 py-2">Image</th>
                        <th class="border px-4 py-2">Title</th>
                        <th class="border px-4 py-2">Price</th>
                        <th class="border px-4 py-2 w-48">Actions</th>
                    </tr>
                </thead>
                <tbody id="scraped-products">
                    <tr>
                        <td colspan="6" class="border px-4 py-2 text-center">No results yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}