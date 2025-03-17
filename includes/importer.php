<?php
// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

function amazon_woo_crawler_import($product_data)
{
    if (!class_exists('WC_Product_Simple') || empty($product_data['title']) || empty($product_data['sale_price']) || empty($product_data['sku'])) {
        return new WP_Error(
            'sku_exists',
            __('Missing required product data.', 'amazon-woo-crawler'),
        );
    }

    // Check if product with the same SKU already exists
    $existing_product_id = wc_get_product_id_by_sku($product_data['sku']);
    if ($existing_product_id) {
        return new WP_Error(
            'sku_exists',
            __('Product with this SKU already exists.', 'amazon-woo-crawler'),
            ['status' => 400, 'product_id' => $existing_product_id]
        );
    }

    $product = new WC_Product_Simple();
    $product->set_name($product_data['title']);
    $product->set_sku($product_data['sku']); // Set SKU
    $product->set_regular_price(floatval(preg_replace('/[^0-9.]/', '', $product_data['sale_price'])));

    // Set product description (if available)
    $description = $product_data['description'] ?? '';

    // Append Features to Description
    if (!empty($product_data['feature']) && is_array($product_data['feature'])) {
        $description .= "\n\n<strong>Features:</strong>\n<ul>";
        foreach ($product_data['feature'] as $feature) {
            $description .= "<li>" . esc_html($feature) . "</li>";
        }
        $description .= "</ul>";
    }

    // Append Additional Information
    if (!empty($product_data['information']) && is_array($product_data['information'])) {
        $description .= "\n\n<strong>Specifications:</strong>\n<ul>";
        foreach ($product_data['information'] as $info) {
            $description .= "<li><strong>" . esc_html($info['name']) . ":</strong> " . esc_html($info['value']) . "</li>";
        }
        $description .= "</ul>";
    }

    $product->set_description($description);

    // Set product categories
    if (!empty($product_data['categories']) && is_array($product_data['categories'])) {
        $category_ids = [];
        foreach ($product_data['categories'] as $category_name) {
            // Check if category exists, if not create it
            $category = get_term_by('name', $category_name, 'product_cat');

            if (!$category) {
                // Create category
                $category = wp_insert_term($category_name, 'product_cat');

                if (!is_wp_error($category) && isset($category['term_id'])) {
                    $category_id = $category['term_id'];
                } else {
                    continue; // Skip this category if it failed to create
                }
            } else {
                $category_id = $category->term_id; // WP_Term object, so use -> instead of []
            }

            $category_ids[] = $category_id;
        }

        if (!empty($category_ids)) {
            // Set categories for the product
            $product->set_category_ids($category_ids);
        }
    }


    // Set product images (if available)
    if (!empty($product_data['images']) && is_array($product_data['images'])) {
        $image_ids = [];
        foreach ($product_data['images'] as $image_url) {
            $image_id = amazon_woo_crawler_download_image($image_url);
            if ($image_id) {
                $image_ids[] = $image_id;
            }
        }

        if (!empty($image_ids)) {
            $product->set_image_id($image_ids[0]); // Set main image
            if (count($image_ids) > 1) {
                $product->set_gallery_image_ids(array_slice($image_ids, 1)); // Set gallery images
            }
        }
    }

    $product->save();

    // Add Reviews as Comments
    if (!empty($product_data['reviews']) && is_array($product_data['reviews'])) {
        foreach ($product_data['reviews'] as $review) {
            $comment_data = [
                'comment_post_ID' => $product->get_id(),
                'comment_author' => $review['name'] ?? 'Anonymous',
                'comment_author_email' => '', // Amazon reviews do not have an email
                'comment_content' => esc_html($review['comment']),
                'comment_approved' => 1,
                'comment_meta' => [
                    'rating' => isset($review['rating']) ? floatval($review['rating']) : 5.0,
                    'location' => $review['location'] ?? '',
                    'date' => $review['date'] ?? ''
                ]
            ];
            wp_insert_comment($comment_data);
        }
    }

    return [
        'status' => 'success',
        'message' => 'Product imported successfully',
        'product_id' => $product->get_id()
    ];
}


function amazon_woo_crawler_download_image($image_url)
{
    $upload_dir = wp_upload_dir();
    $image_data = file_get_contents($image_url);
    $filename = basename($image_url);

    if (wp_mkdir_p($upload_dir['path'])) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }

    file_put_contents($file, $image_data);
    $wp_filetype = wp_check_filetype($filename, null);
    $attachment = [
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name($filename),
        'post_content' => '',
        'post_status' => 'inherit'
    ];

    $attach_id = wp_insert_attachment($attachment, $file);
    require_once ABSPATH . 'wp-admin/includes/image.php';
    $attach_data = wp_generate_attachment_metadata($attach_id, $file);
    wp_update_attachment_metadata($attach_id, $attach_data);

    return $attach_id;
}
