# Amazon to WooCommerce Crawler

## Description

Amazon to WooCommerce Crawler is a WordPress plugin that scrapes product data from Amazon and imports it into WooCommerce. It helps store owners quickly populate their WooCommerce store with Amazon products, including images, descriptions, prices, and more.

## Features

- Scrape Amazon product details (title, price, description, images, etc.)
- Import scraped data directly into WooCommerce as products
- Customizable settings for importing and formatting data
- API support for advanced integration
- Proxy support for secure and anonymous scraping
- Admin panel for managing settings and imports

## Installation

1. Download the plugin files and upload them to the `/wp-content/plugins/amazon-woo-crawler/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to **Amazon Woo Crawler** settings in the WordPress admin panel.
4. Configure the necessary API keys and proxy settings (if applicable).

## Usage

1. Enter the Amazon product URL in the scraper input field.
2. Click the "Fetch Product" button to retrieve product details.
3. Review and modify the data if necessary.
4. Click "Import to WooCommerce" to add the product to your WooCommerce store.

## File Structure

```
├── README.md
├── amazon-woo-crawler.php  # Main plugin file
├── assets                   # Contains plugin assets (CSS, JS, images)
├── includes                 # Core functionalities
│   ├── admin-page.php       # Admin interface
│   ├── api.php              # API integrations
│   ├── helper-functions.php # Utility functions
│   ├── importer.php         # WooCommerce product importer
│   ├── scraper.php          # Amazon scraper logic
│   ├── settings.php         # Plugin settings handler
│   └── test.php             # Testing and debugging
├── samples                  # Example files for reference
│   ├── exam.html            # Sample HTML for testing scraping
│   └── proxy.txt            # Proxy list example
└── uninstall.php            # Cleanup script for plugin removal
```

## Requirements

- WordPress 5.0+
- WooCommerce 4.0+
- PHP 7.4+
- cURL enabled on the server

## Contributing

Feel free to fork this repository, submit pull requests, or report issues.

## License

This plugin is licensed under the MIT License.

## Disclaimer

This plugin is for educational and research purposes only. Scraping Amazon's data may violate their terms of service. Use responsibly and ensure compliance with Amazon's policies.
