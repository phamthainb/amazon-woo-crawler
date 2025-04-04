<?php
// Exit if accessed directly

// Scrape product data from Amazon
// - URL: https://www.amazon.com/dp/B07H8Q3JH9
// - SKU: B07H8Q3JH9
// - Tags, categories
// - Title: productTitle
// - Sale price
// - Images, videos
// - Product information {name, value}[]
// - Product description
// - Rate score
// - Ratings [{photo, name, date, rate, comment}]

if (!defined('ABSPATH')) {
    exit;
}

function getProxy($accessToken)
{
    $currentProxyUrl = "http://proxy.shoplike.vn/Api/getCurrentProxy?access_token=" . urlencode($accessToken);
    $newProxyUrl = "http://proxy.shoplike.vn/Api/getNewProxy?access_token=" . urlencode($accessToken);

    $currentProxy = fetchUrl($currentProxyUrl);
    $currentProxyJson = json_decode($currentProxy, true);

    if ($currentProxyJson['status'] === "error") {
        $newProxyRes = fetchUrl($newProxyUrl);
        //    $newProxyResJson = json_decode($newProxyRes, true);
        return $newProxyRes;
    }

    return $currentProxy;
}

function fetchUrl($url)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ($httpCode === 200) ? $response : false;
}

function amazon_woo_crawler_scrape($url, $proxy_url = '', $proxy_username = '', $proxy_password = '')
{
    require_once(ABSPATH . WPINC . '/class-wp-http.php');

    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return new WP_Error(
            'invalid_url',
            'Invalid Amazon product URL',
            ['url' => $url]
        );
    }

    // Extract SKU from URL
    preg_match('/\/dp\/([A-Z0-9]+)/', $url, $matches);
    $sku = $matches[1] ?? '';

    // HTTP request with proxy settings
    $args = [
        'timeout' => 30,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'en-US,en;q=0.9'
        ],
        'sslverify' => false
    ];

    // Proxy configuration
    // if (!empty($proxy_url)) {
    //     $args['proxy'] = $proxy_url;

    //     if (!empty($proxy_username) && !empty($proxy_password)) {
    //         $args['headers']['Proxy-Authorization'] = 'Basic ' . base64_encode("$proxy_username:$proxy_password");
    //     }
    // }

    // migrate with rotate proxy
    // curl --location -g 'http://proxy.shoplike.vn/Api/getCurrentProxy?access_token=xxx'
    // if not getCurrentProxy then curl --location -g 'http://proxy.shoplike.vn/Api/getNewProxy?access_token=xxx'

    $proxyJson = getProxy('xx');

    if ($proxyJson === false) {
        die("Failed to get proxy data.");
    }

    $proxyData = json_decode($proxyJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        die("Invalid JSON response.");
    }

    if (isset($proxyData['data']['proxy'])) {
        $args['proxy'] = $proxyData['data']['proxy'];
    }

    // Fetch product page
    $response = wp_remote_get($url, $args);
    // $response = wp_remote_get("ifconfig.me", $args);
    // echo wp_remote_retrieve_body($response);
    // die();

    if (is_wp_error($response)) {
        return new WP_Error(
            'scrape_failed',
            'Failed to fetch product data',
            [
                'error' => $response->get_error_message(),
                'url' => $url,
                'proxy' => $proxy_url
            ]
        );
    }

    $html = wp_remote_retrieve_body($response);

    // Check for Amazon blocking
    if (stripos($html, 'To discuss automated access to Amazon data') !== false) {
        return new WP_Error(
            'access_blocked',
            'Amazon is blocking automated access',
            [
                'url' => $url,
                'debug' => $html
            ]
        );
    }

    // Load HTML
    // Replace the deprecated mb_convert_encoding() with a more modern method
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);

    // Enhanced extraction functions
    $extractText = function ($query) use ($xpath) {
        $node = $xpath->query($query)->item(0);
        return $node ? trim($node->textContent) : '';
    };

    $extractList = function ($query) use ($xpath) {
        $list = [];
        foreach ($xpath->query($query) as $node) {
            $list[] = trim($node->textContent);
        }
        return $list;
    };

    $sale_price = $extractText("//span[contains(@class, 'a-price-whole')]")
        . $extractText("//span[contains(@class, 'a-price-fraction')]") ?? '00';



    // Product information table
    $product_information = [];
    foreach ($xpath->query("//table[contains(@class, 'prodDetTable')]//tr") as $tr) {
        $nameNode = $tr->getElementsByTagName('th')->item(0);
        $valueNode = $tr->getElementsByTagName('td')->item(0);

        // Check if both name and value nodes exist and have specific classes
        if (
            $nameNode && $valueNode &&
            (strpos($nameNode->getAttribute('class'), 'prodDetSectionEntry') !== false) &&
            (strpos($valueNode->getAttribute('class'), 'prodDetAttrValue') !== false)
        ) {

            $product_information[] = [
                'name' => trim($nameNode->textContent),
                'value' => trim($valueNode->textContent),
            ];
        }
    }

    // Images
    // Find the line containing "colorImages": { "initial":
    $images = [];
    $colorImages = preg_match(
        '/\'colorImages\':\s*(\{.*?\})\s*,\s*\'colorToAsin\'/s',
        $html,
        $matches
    );

    if ($colorImages) {
        $jsonString = $matches[1];
        $jsonString = str_replace("'", '"', $jsonString);
        $jsonString = preg_replace('/\s+/', '', $jsonString);
        $jsonString = preg_replace('/,\}/', '}', $jsonString);
        $jsonData = json_decode($jsonString, true);

        if ($jsonData && is_array($jsonData)) {
            foreach ($jsonData["initial"] as $image) {

                if (isset($image['hiRes']) && !empty($image['hiRes'])) {
                    $images[] = $image['hiRes']; // High resolution image
                } elseif (isset($image['large']) && !empty($image['large'])) {
                    $images[] = $image['large']; // Large image fallback
                }
            }
        }
    }

    // Reviews
    $reviews = [];
    $reviewsNodes = $xpath->query("//li[@data-hook='review']");

    foreach ($reviewsNodes as $review) {
        $nameNode = $xpath->query(".//span[contains(@class, 'a-profile-name')]", $review);
        $photoNode = $xpath->query(".//div[@class='a-profile-avatar']//img/@src", $review);
        $dateNode = $xpath->query(".//span[contains(@data-hook, 'review-date')]", $review);
        $rateNode = $xpath->query(".//span[contains(@class, 'a-icon-alt')]", $review);
        $commentNode = $xpath->query(".//div[contains(@class, 'reviewText') or contains(@class, 'review-text')]", $review);

        // Extracting values
        $name = $nameNode->length > 0 ? trim($nameNode->item(0)->textContent) : "";
        $photo = $photoNode->length > 0 ? trim($photoNode->item(0)->textContent) : "";
        $comment = $commentNode->length > 0 ? trim($commentNode->item(0)->textContent) : "";

        // Extract Rating as a Number
        $rating = "";
        if ($rateNode->length > 0) {
            preg_match('/(\d+(\.\d+)?)/', $rateNode->item(0)->textContent, $matches);
            $rating = $matches[1] ?? ""; // Extracts numeric rating
        }

        // Extract Location & Date
        $location = "";
        $date = "";
        if ($dateNode->length > 0) {
            $dateText = trim($dateNode->item(0)->textContent);
            if (preg_match('/Reviewed in (.*?) on (.+)/', $dateText, $matches)) {
                $location = trim($matches[1]); // Extracts location
                $date = trim($matches[2]); // Extracts date
            }
        }

        // Store review data
        $reviews[] = [
            'name' => $name,
            'photo' => $photo,
            'rating' => $rating,
            'location' => $location,
            'date' => $date,
            'comment' => $comment,
        ];
    }


    // Detailed extraction
    $product_data = [
        'sku' => $sku,
        'url' => $url,
        'title' => $extractText("//span[@id='productTitle']"),
        'sale_price' => $sale_price,
        'categories' => $extractList("//div[@id='wayfinding-breadcrumbs_feature_div']//a"),
        'description' => $extractText("//div[@id='productDescription']"),
        'feature' => $extractList("//*[@id='feature-bullets']//span"),
        'information' => $product_information,
        'images' => $images,
        'reviews' => $reviews,
        'ratings' => 5,

        // Debug information
        'debug' => [
            'proxy_url' => $proxyData,
        ]
    ];

    return $product_data;
}

