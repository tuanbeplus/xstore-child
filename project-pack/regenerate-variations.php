<?php
/**
 * Remove and Add No Customize Variations for Kids
 *
 * Deletes all existing variations for products in the 'kids' category
 * and creates new variations with numeric sizes (16–28) and
 * 'No customize'/'Customize' options.
 *
 * Migrated from WPCode snippet (Author: Sang Huynh, v2.1)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Admin menu
add_action('admin_menu', function () {
    add_menu_page(
        'Regenerate Variations',
        'Regenerate Variations',
        'manage_woocommerce',
        'regenerate-variations',
        'regenerate_variations_admin_page',
        'dashicons-update',
        56
    );
});

// Admin page UI
function regenerate_variations_admin_page() {
    ?>
    <div class="wrap">
        <h1>Regenerate Variations for Kids Products</h1>
        <p>Click the button below to delete all existing variations and create new ones for products in the 'kids' category. Progress will be shown below.</p>
        <button id="run-regenerate-all" class="button button-primary">Run for Kids Products</button>
        <div id="progress-container" style="margin-top: 20px;">
            <div id="progress-bar" style="width: 0%; height: 20px; background-color: #4CAF50; color: white; text-align: center; line-height: 20px;">0%</div>
        </div>
        <div id="status-message" style="margin-top: 10px;"></div>
        <div id="log-link" style="margin-top: 10px;"></div>
    </div>

    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('#run-regenerate-all').on('click', function() {
                if (!confirm('Are you sure you want to regenerate variations for all products in the kids category? This will delete all existing variations and cannot be undone.')) {
                    return;
                }

                $(this).prop('disabled', true).text('Processing...');
                $('#status-message').text('Fetching kids products...');
                $('#progress-bar').css('width', '0%').text('0%');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_all_variable_products',
                    },
                    success: function(response) {
                        if (response.success && response.data.products.length > 0) {
                            const products = response.data.products;
                            const total = products.length;
                            let current = 0;

                            $('#status-message').text(`Found ${total} variable products in kids category. Starting process...`);

                            function processNextProduct() {
                                if (current >= total) {
                                    $('#run-regenerate-all').prop('disabled', false).text('Run for Kids Products');
                                    $('#status-message').text('Process completed! Check the log for details.');
                                    $('#progress-bar').css('width', '100%').text('100%');
                                    $('#log-link').html('<a href="<?php echo esc_url(wp_upload_dir()['baseurl'] . '/variation_adjust_log.txt'); ?>" target="_blank">View Log</a>');
                                    return;
                                }

                                const productId = products[current];
                                $('#status-message').text(`Processing product ID ${productId} (${current + 1}/${total})...`);

                                $.ajax({
                                    url: ajaxurl,
                                    type: 'POST',
                                    data: {
                                        action: 'run_regenerate_variations',
                                        product_id: productId,
                                        nonce: '<?php echo wp_create_nonce("regenerate_variations_nonce"); ?>',
                                    },
                                    success: function(response) {
                                        if (response.success) {
                                            current++;
                                            const progress = Math.round((current / total) * 100);
                                            $('#progress-bar').css('width', progress + '%').text(progress + '%');
                                            processNextProduct();
                                        } else {
                                            $('#status-message').text(`Error processing product ID ${productId}: ${response.data.message}`);
                                            $('#run-regenerate-all').prop('disabled', false).text('Run for Kids Products');
                                        }
                                    },
                                    error: function() {
                                        $('#status-message').text(`AJAX error while processing product ID ${productId}.`);
                                        $('#run-regenerate-all').prop('disabled', false).text('Run for Kids Products');
                                    }
                                });
                            }

                            processNextProduct();
                        } else {
                            $('#status-message').text('No variable products found in kids category.');
                            $('#run-regenerate-all').prop('disabled', false).text('Run for Kids Products');
                        }
                    },
                    error: function() {
                        $('#status-message').text('Error fetching kids products.');
                        $('#run-regenerate-all').prop('disabled', false).text('Run for Kids Products');
                    }
                });
            });
        });
    </script>
    <style>
        #progress-container {
            width: 100%;
            background-color: #f1f1f1;
            border: 1px solid #ccc;
        }
        #progress-bar {
            transition: width 0.3s ease;
        }
    </style>
    <?php
}

// AJAX: Get all variable products in kids category
add_action('wp_ajax_get_all_variable_products', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    $args = [
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => [
            [
                'taxonomy' => 'product_cat',
                'field' => 'slug',
                'terms' => 'kids',
            ],
        ],
        'meta_query' => [
            [
                'key' => '_product_attributes',
                'compare' => 'EXISTS',
            ],
        ],
    ];

    $query = new WP_Query($args);
    $products = $query->posts;
    $variable_products = [];

    foreach ($products as $product_id) {
        $product = wc_get_product($product_id);
        if ($product && $product->is_type('variable')) {
            $variable_products[] = $product_id;
        }
    }

    wp_send_json_success(['products' => $variable_products]);
});

// AJAX: Regenerate variations for a single product
add_action('wp_ajax_run_regenerate_variations', function () {
    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(['message' => 'Permission denied.']);
    }

    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'regenerate_variations_nonce')) {
        wp_send_json_error(['message' => 'Invalid nonce.']);
    }

    $product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
    if (!$product_id) {
        wp_send_json_error(['message' => 'Invalid product ID.']);
    }

    remove_and_add_no_customize($product_id);
    wp_send_json_success(['message' => "Processed product ID $product_id"]);
});

function remove_and_add_no_customize($product_id) {
    $upload_dir = wp_upload_dir();
    if (!is_writable($upload_dir['basedir'])) {
        error_log("Error: Upload directory is not writable for Product ID $product_id.");
        return;
    }

    $log_path = $upload_dir['basedir'] . '/variation_adjust_log.txt';
    $log = @fopen($log_path, 'a');
    if ($log === false) {
        error_log("Error: Cannot write to log file for Product ID $product_id.");
        return;
    }

    $product = wc_get_product($product_id);
    if (!$product || !$product->is_type('variable')) {
        fwrite($log, "Product ID $product_id is not a variable product.\n");
        fclose($log);
        return;
    }

    if (!has_term('kids', 'product_cat', $product_id)) {
        fwrite($log, "Product ID $product_id is not in kids category. Skipping.\n");
        fclose($log);
        return;
    }

    fwrite($log, "=== Start: " . date('Y-m-d H:i:s') . " for Product ID $product_id ===\n");

    $product_attributes = get_post_meta($product_id, '_product_attributes', true);
    if (!$product_attributes || !is_array($product_attributes)) {
        fwrite($log, "Product ID $product_id has no attributes.\n");
        fclose($log);
        return;
    }

    // Register size attribute if missing
    if (!isset($product_attributes['size'])) {
        $product_attributes['size'] = [
            'name' => 'size',
            'value' => '16 | 18 | 20 | 22 | 24 | 26 | 28',
            'position' => 0,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 0,
        ];
        update_post_meta($product_id, '_product_attributes', $product_attributes);
        fwrite($log, "   Registered size attribute.\n");
    }

    // Register customize attribute if missing
    if (!isset($product_attributes['customize'])) {
        $product_attributes['customize'] = [
            'name' => 'customize',
            'value' => 'No customize | Customize',
            'position' => 1,
            'is_visible' => 1,
            'is_variation' => 1,
            'is_taxonomy' => 0,
        ];
        update_post_meta($product_id, '_product_attributes', $product_attributes);
        fwrite($log, "   Registered customize attribute.\n");
    }

    // Get price from first variation with non-zero regular_price
    $regular_price = '0.00';
    $sale_price = '0.00';
    $variations = $product->get_children();
    foreach ($variations as $variation_id) {
        $variation = wc_get_product($variation_id);
        if ($variation) {
            $price = $variation->get_regular_price();
            if (!empty($price) && floatval($price) > 0) {
                $regular_price = $price;
                $sale_price = $variation->get_sale_price() ?: $regular_price;
                fwrite($log, "   Found first non-zero price: Regular = $regular_price, Sale = $sale_price from Variation ID $variation_id\n");
                break;
            }
        }
    }
    if ($regular_price == '0.00') {
        fwrite($log, "   No non-zero price found, defaulting to 0.00\n");
    }

    // Delete ALL existing variations
    $removed = 0;
    foreach ($variations as $variation_id) {
        $variation = wc_get_product($variation_id);
        if ($variation) {
            $attributes = $variation->get_variation_attributes();
            $size_value = isset($attributes['attribute_size']) ? $attributes['attribute_size'] : '';
            $customize_value = isset($attributes['attribute_customize']) ? $attributes['attribute_customize'] : '';
            wp_delete_post($variation_id, true);
            fwrite($log, "   Removed variation ID $variation_id with Size = $size_value, Customize = $customize_value\n");
            $removed++;
        }
    }
    fwrite($log, "   Removed $removed variations.\n");

    // Numeric sizes for kids category
    $sizes = ['16', '18', '20', '22', '24', '26', '28'];
    fwrite($log, "   Using numeric sizes for kids category: " . implode(', ', $sizes) . "\n");

    // Create new variations with No customize and Customize
    $custom_values = ['No customize', 'Customize'];
    $created = 0;
    foreach ($sizes as $size) {
        foreach ($custom_values as $custom_value) {
            $variation = new WC_Product_Variation();
            $variation->set_parent_id($product_id);
            $variation->set_attributes([
                'attribute_size' => $size,
                'attribute_customize' => $custom_value,
            ]);
            $variation->set_regular_price($regular_price);
            $variation->set_sale_price($sale_price);
            $variation->set_manage_stock(false);
            $variation->set_stock_status('instock');
            $variation_id = $variation->save();

            fwrite($log, "   Added: Size = $size, Customize = $custom_value, Regular Price = $regular_price, Sale Price = $sale_price, Variation ID: $variation_id\n");
            $created++;
        }
    }

    if ($created > 0) {
        $product->save();
        WC_Product_Variable::sync($product_id);
        wc_delete_product_transients($product_id);
        fwrite($log, "Added $created variations.\n\n");
    } else {
        fwrite($log, "No variations added.\n\n");
    }

    // Update attribute values
    $product_attributes['size']['value'] = implode(' | ', $sizes);
    $product_attributes['customize']['value'] = implode(' | ', $custom_values);
    update_post_meta($product_id, '_product_attributes', $product_attributes);
    fwrite($log, "   Updated attributes - Size: " . $product_attributes['size']['value'] . ", Customize: " . $product_attributes['customize']['value'] . "\n");

    fclose($log);
}
