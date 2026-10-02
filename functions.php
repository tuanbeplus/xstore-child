<?php
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 1001 );
function theme_enqueue_styles() {
	if (function_exists('etheme_child_styles')){
		etheme_child_styles();
	}
}

// Disable responsive image sizes (srcset)
add_filter( 'wp_calculate_image_srcset', '__return_false' );
function random_product_redirect() {
    if (isset($_GET['random-product'])) {
        global $wpdb;

        // Get all product IDs from the database
        $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'";
        $product_ids = $wpdb->get_col($query);

        // Ensure there are products to choose from
        if (!empty($product_ids)) {
            // Shuffle the array and get the first random product ID
            shuffle($product_ids);
            $random_id = $product_ids[0];

            // Redirect to the random product's permalink
            wp_redirect(get_permalink($random_id));
            exit;
        }
    }
}
add_action('template_redirect', 'random_product_redirect');



function register_shipped_order_status() {
    register_post_status('wc-shipped', array(
        'label'                     => _x('Shipped', 'Order status', 'woocommerce'),
        'public'                    => true,
        'show_in_admin_status_list'  => true,
        'show_in_admin_all_list'     => true,
        'exclude_from_search'        => false,
        'label_count'                => _n_noop('Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'woocommerce'),
    ));
}
add_action('init', 'register_shipped_order_status');


function add_shipped_to_order_statuses($order_statuses) {
    $order_statuses['wc-shipped'] = _x('Shipped', 'Order status', 'woocommerce');
    return $order_statuses;
}
add_filter('wc_order_statuses', 'add_shipped_to_order_statuses');


add_action( 'woocommerce_before_order_object_save', 'force_shipped_status_if_tracking', 999, 2 );
function force_shipped_status_if_tracking( $order, $data_store ) {
    // Ensure we're dealing with a valid order object.
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return;
    }
    
    // Get tracking meta from the order object.
    $tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
    error_log( "Force Hook - Order #{$order->get_id()} - Tracking meta: " . print_r( $tracking_items, true ) );
    
    // If tracking meta exists, force the order status to shipped.
    if ( ! empty( $tracking_items ) && $order->get_status() !== 'shipped' ) {
        $order->set_status( 'shipped' );
        // Optionally, add an order note to indicate the change.
        $order->add_order_note( __( 'Automatically marked shipped via tracking check', 'your-textdomain' ) );
        error_log( "Force Hook - Order #{$order->get_id()} forced to shipped status." );
    }
}


function change_product_related_products_heading() {
    return esc_html__( 'You might also like', 'xstore' );
}
add_filter('woocommerce_product_related_products_heading', 'change_product_related_products_heading');


function custom_acf_product_rating() {
    global $post;

    if (!function_exists('get_field')) {
        return;
    }

    $acf_rating = get_field('number_rating', $post->ID);
	$custom_rating = get_field('custom_rating', $post->ID);

    if (!$custom_rating) {
        return;
    }

    $full_stars = floor($acf_rating);
    $decimal_part = $acf_rating - $full_stars;
    $percent = $decimal_part * 100; 

    echo '<div class="custom-acf-rating">';
    echo '<div class="star-container">';

    for ($i = 0; $i < $full_stars; $i++) {
        echo '<span class="star full">★</span>';
    }

    if ($decimal_part > 0) {
        echo '<span class="star half" style="--percent:' . $percent . '%;">★</span>';
    }

    $empty_stars = 5 - $full_stars - ($decimal_part > 0 ? 1 : 0);
    for ($i = 0; $i < $empty_stars; $i++) {
        echo '<span class="star empty">☆</span>';
    }

    echo '</div>';
    echo '<span class="rating-count">'.esc_html($custom_rating).'</span>';
    echo '</div>';
}
add_action('woocommerce_before_add_to_cart_form', 'custom_acf_product_rating', 9);


function enqueue_child_theme_assets() {
    // Get the child theme directory URI
    $theme_uri = get_stylesheet_directory_uri();

    // Enqueue CSS
    wp_enqueue_style('child-style', $theme_uri . '/assets/css/style.css', array(), time(), 'all');

    // Enqueue JS
    wp_enqueue_script('child-script', $theme_uri . '/assets/js/script.js', array('jquery'), time(), true);
    wp_localize_script('child-script', 'order_dates_ajax', [
          'ajax_url' => admin_url('admin-ajax.php'),
      ]);
}
add_action('wp_enqueue_scripts', 'enqueue_child_theme_assets');


add_action('wp_ajax_get_order_dates', 'get_order_dates_ajax_handler');
add_action('wp_ajax_nopriv_get_order_dates', 'get_order_dates_ajax_handler');
function get_order_dates_ajax_handler() {
    $ordered_date = date('M jS');
    $order_ready_date_first = date('M jS', strtotime('+1 days'));
    $order_ready_date_last = date('M jS', strtotime('+2 days'));
    $delivered_date_first = date('M jS', strtotime('+10 days'));
    $delivered_date_last = date('M jS', strtotime('+12 days'));

    ob_start();
    ?>
    <div class="order-dates-container">
        <div class="order-date"><i class="et-icon et-shopping-cart"></i><?php echo esc_html($ordered_date); ?><span> Ordered</span></div>
        <div class="order-ready"><i class="et-icon et-delivery"></i><?php echo esc_html($order_ready_date_first); ?> - <?php echo esc_html($order_ready_date_last); ?><span> Order Ready</span></div>
        <div class="delivered-date"><i class="et-icon et-gift"></i><?php echo esc_html($delivered_date_first); ?> - <?php echo esc_html($delivered_date_last); ?><span> Delivered</span></div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success(['html' => $html]);
}


add_action('woocommerce_after_add_to_cart_form', function() {
    echo '<div id="ajax-order-dates"></div>';
});

function add_custom_aggregate_rating_schema() {
    if (!is_product()) {
        return;
    }

    global $product;

    if (!$product) {
        return;
    }

    $product_name = $product->get_name();
    $product_url = get_permalink($product->get_id());
    $product_image = wp_get_attachment_url($product->get_image_id());
    $product_description = wp_strip_all_tags($product->get_short_description());

    $rating_value = get_post_meta(get_the_ID(), 'number_rating', true); // Custom field for rating value
    $review_count = get_post_meta(get_the_ID(), 'count_rating', true); // Custom field for review count

    // Ensure both rating values are valid
    if (!empty($rating_value) && is_numeric($rating_value) && !empty($review_count) && is_numeric($review_count)) {
        ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Product",
          "name": "<?php echo esc_js($product_name); ?>",
          "url": "<?php echo esc_url($product_url); ?>",
          "image": "<?php echo esc_url($product_image); ?>",
          "description": "<?php echo esc_js($product_description); ?>",
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?php echo esc_js($rating_value); ?>",
            "reviewCount": "<?php echo esc_js($review_count); ?>",
			"bestRating": 5
          }
        }
        </script>
        <?php
    }
}

add_action('wp_head', 'add_custom_aggregate_rating_schema');


function custom_currency_switcher_shortcode() {
    if ( function_exists( 'wc_get_currency_switcher_markup' ) ) {
        $instance = [
            'symbol' => true,
            'flag'   => true,
        ];
        $args = [];
        return wc_get_currency_switcher_markup( $instance, $args );
    }
    return ''; // Return empty string if function doesn't exist
}
add_shortcode( 'currency_switcher', 'custom_currency_switcher_shortcode' );


function custom_currency_switcher() {
 ?>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
 
   <script>
        $(document).ready(function() {
            $('.wrapper-header-currency form select').select2({
                width: '115px',
                templateResult: formatCurrency,
                templateSelection: formatCurrency
            });

            function formatCurrency(state) {
                if (!state.id) {
                    return state.text;
                }
                return $('<span>' + state.text + '</span>');
            }
        });
    </script>
 <?php
}

add_action('wp_footer', 'custom_currency_switcher',998);

// add_action('wp_footer','get_data_currentcy',9999);

function get_data_currentcy() {
    if(!empty($_GET['test'])) {
        $currencies = get_option('woo_multi_currency_params');
        if(!empty($currencies)) {
            $country_cu = $currencies['currency'];
            $currency_symbols = array();
            $flag_symbols = array();
            $symbol_map = [
                "USD" => "$",   // United States Dollar
                "EUR" => "€",   // Euro
                "GBP" => "£",   // British Pound Sterling
                "JPY" => "¥",   // Japanese Yen
                "KRW" => "₩",   // South Korean Won
                "RUB" => "₽",   // Russian Ruble
                "INR" => "₹",   // Indian Rupee
                "TRY" => "₺",   // Turkish Lira
                "THB" => "฿",   // Thai Baht
                "VND" => "₫",   // Vietnamese Dong
                "AED" => "د.إ", // United Arab Emirates Dirham
                "CAD" => "C$",  // Canadian Dollar
                "AUD" => "A$",  // Australian Dollar
                "NZD" => "NZ$", // New Zealand Dollar
                "CHF" => "CHF", // Swiss Franc
                "DKK" => "kr",  // Danish Krone
                "HKD" => "HK$", // Hong Kong Dollar
                "NOK" => "NOK", // Norwegian Krone
                "SEK" => "SEK", // Swedish Krona
                "SGD" => "SGD", // Singapore Dollar
                "ZAR" => "R",   // South African Rand
                "PHP" => "₱",   // Philippine Peso
                "MXN" => "MX$", // Mexican Peso
                "BRL" => "R$",  // Brazilian Real
                "ARS" => "ARS$",// Argentine Peso
                "CLP" => "CLP$",// Chilean Peso
                "COP" => "COP$",// Colombian Peso
                "PEN" => "S/",  // Peruvian Sol
                "CZK" => "Kč",  // Czech Koruna
                "HUF" => "Ft",  // Hungarian Forint
                "PLN" => "zł",  // Polish Złoty
                "ILS" => "₪",   // Israeli Shekel
                "NGN" => "₦",   // Nigerian Naira
                "EGP" => "EGP", // Egyptian Pound
                "TZS" => "TSh", // Tanzanian Shilling
                "KES" => "KSh", // Kenyan Shilling
                "PKR" => "₨",   // Pakistani Rupee
                "BDT" => "৳",   // Bangladeshi Taka
                "HNL" => "L",   // Honduran Lempira
                "GYD" => "G$",  // Guyanese Dollar
                "BZD" => "BZ$", // Belize Dollar
                "JMD" => "J$",  // Jamaican Dollar
                "TTD" => "TT$", // Trinidad and Tobago Dollar
                "PAB" => "B/.", // Panamanian Balboa
                "GTQ" => "Q",   // Guatemalan Quetzal
                "XOF" => "CFA", // West African CFA Franc
                "XPF" => "F",   // CFP Franc
                "MAD" => "MAD", // Moroccan Dirham
                "GMD" => "D",   // Gambian Dalasi
                "MWK" => "MK",  // Malawian Kwacha
                "ZMW" => "ZMW", // Zambian Kwacha
                "UGX" => "UGX", // Ugandan Shilling
                "MZN" => "MZN", // Mozambican Metical
                "BWP" => "BWP", // Botswana Pula
                "LSL" => "LSL", // Lesotho Loti
                "SZL" => "SZL"  // Swazi Lilangeni
            ];

            $flag_map = [
                "USD" => "🇺🇸",   // United States Dollar
                "EUR" => "🇪🇺",   // Euro
                "GBP" => "🇬🇧",   // British Pound Sterling
                "JPY" => "🇯🇵",   // Japanese Yen
                "KRW" => "🇰🇷",   // South Korean Won
                "RUB" => "🇷🇺",   // Russian Ruble
                "INR" => "🇮🇳",   // Indian Rupee
                "TRY" => "🇹🇷",   // Turkish Lira
                "THB" => "🇹🇭",   // Thai Baht
                "VND" => "🇻🇳",   // Vietnamese Dong
                "AED" => "🇦🇪",   // United Arab Emirates Dirham
                "CAD" => "🇨🇦",   // Canadian Dollar
                "AUD" => "🇦🇺",   // Australian Dollar
                "NZD" => "🇳🇿",   // New Zealand Dollar
                "CHF" => "🇨🇭",   // Swiss Franc
                "DKK" => "🇩🇰",   // Danish Krone
                "HKD" => "🇭🇰",   // Hong Kong Dollar
                "NOK" => "🇳🇴",   // Norwegian Krone
                "SEK" => "🇸🇪",   // Swedish Krona
                "SGD" => "🇸🇬",   // Singapore Dollar
                "ZAR" => "🇿🇦",   // South African Rand
                "PHP" => "🇵🇭",   // Philippine Peso
                "MXN" => "🇲🇽",   // Mexican Peso
                "BRL" => "🇧🇷",   // Brazilian Real
                "ARS" => "🇦🇷",   // Argentine Peso
                "CLP" => "🇨🇱",   // Chilean Peso
                "COP" => "🇨🇴",   // Colombian Peso
                "PEN" => "🇵🇪",   // Peruvian Sol
                "CZK" => "🇨🇿",   // Czech Koruna
                "HUF" => "🇭🇺",   // Hungarian Forint
                "PLN" => "🇵🇱",   // Polish Złoty
                "ILS" => "🇮🇱",   // Israeli Shekel
                "NGN" => "🇳🇬",   // Nigerian Naira
                "EGP" => "🇪🇬",   // Egyptian Pound
                "TZS" => "🇹🇿",   // Tanzanian Shilling
                "KES" => "🇰🇪",   // Kenyan Shilling
                "PKR" => "🇵🇰",   // Pakistani Rupee
                "BDT" => "🇧🇩",   // Bangladeshi Taka
                "HNL" => "🇭🇳",   // Honduran Lempira
                "GYD" => "🇬🇾",   // Guyanese Dollar
                "BZD" => "🇧🇿",   // Belize Dollar
                "JMD" => "🇯🇲",   // Jamaican Dollar
                "TTD" => "🇹🇹",   // Trinidad and Tobago Dollar
                "PAB" => "🇵🇦",   // Panamanian Balboa
                "GTQ" => "🇬🇹",   // Guatemalan Quetzal
                "XOF" => "🇫🇷",   // West African CFA Franc (flag of France as a representation)
                "XPF" => "🇫🇷",   // CFP Franc (flag of France as a representation)
                "MAD" => "🇲🇦",   // Moroccan Dirham
                "GMD" => "🇬🇲",   // Gambian Dalasi
                "MWK" => "🇲🇼",   // Malawian Kwacha
                "ZMW" => "🇿🇲",   // Zambian Kwacha
                "UGX" => "🇺🇬",   // Ugandan Shilling
                "MZN" => "🇲🇿",   // Mozambican Metical
                "BWP" => "🇧🇼",   // Botswana Pula
                "LSL" => "🇱🇸",   // Lesotho Loti
                "SZL" => "🇸🇿"    // Swazi Lilangeni
            ];
            
            foreach($country_cu as $currency) {
                if(isset($symbol_map[$currency])) {
                    $currency_symbols[$symbol_map[$currency]] = $currency;
                }
            }

            foreach($country_cu as $currency) {
                if(isset($flag_map[$currency])) {
                    $flag_symbols[$currency] = $flag_map[$currency];
                }
            }

            // var_dump($flag_symbols);
            

            if(!empty($currency_symbols)) {
                ?>
                <div class="wrapper-header-currency">
                    <form>
                        <select id='currency-select' name="wmc-currency" aria-label="Select currency" onchange="this.form.submit()">
                            <?php
                                foreach($currency_symbols as $key => $value) {
                                    ?>
                                        <option value = "<?php echo $value?>">
                                            <?php echo $flag_symbols[$value].' '.$key.' '.$value?>
                                        </option>
                                    <?php
                                }
                            ?>
                        </seclect>    
                    </form>        

                </div>   
                <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
 
                <script>
                        jQuery(document).ready(function() {
                            jQuery('.wrapper-header-currency form select').select2({
                                width: '115px',
                                templateResult: formatCurrency,
                                templateSelection: formatCurrency
                            });

                            function formatCurrency(state) {
                                if (!state.id) {
                                    return state.text;
                                }
                                return $('<span>' + state.text + '</span>');
                            }
                        });
                    </script> 
                <?php
                
            }
            
        }
        
   
    }

}


function get_data_currency_shortcode() {
    
        $currencies = get_option('woo_multi_currency_params');
        if (!empty($currencies)) {
            $country_cu = $currencies['currency'];
            $currency_symbols = array();
            $flag_symbols = array();
            $symbol_map = [
                "USD" => "$",   // United States Dollar
                "EUR" => "€",   // Euro
                "GBP" => "£",   // British Pound Sterling
                "JPY" => "¥",   // Japanese Yen
                "KRW" => "₩",   // South Korean Won
                "RUB" => "₽",   // Russian Ruble
                "INR" => "₹",   // Indian Rupee
                "TRY" => "₺",   // Turkish Lira
                "THB" => "฿",   // Thai Baht
                "VND" => "₫",   // Vietnamese Dong
                "AED" => "د.إ", // United Arab Emirates Dirham
                "CAD" => "C$",  // Canadian Dollar
                "AUD" => "A$",  // Australian Dollar
                "NZD" => "NZ$", // New Zealand Dollar
                "CHF" => "CHF", // Swiss Franc
                "DKK" => "kr",  // Danish Krone
                "HKD" => "HK$", // Hong Kong Dollar
                "NOK" => "NOK", // Norwegian Krone
                "SEK" => "SEK", // Swedish Krona
                "SGD" => "SGD", // Singapore Dollar
                "ZAR" => "R",   // South African Rand
                "PHP" => "₱",   // Philippine Peso
                "MXN" => "MX$", // Mexican Peso
                "BRL" => "R$",  // Brazilian Real
                "ARS" => "ARS$",// Argentine Peso
                "CLP" => "CLP$",// Chilean Peso
                "COP" => "COP$",// Colombian Peso
                "PEN" => "S/",  // Peruvian Sol
                "CZK" => "Kč",  // Czech Koruna
                "HUF" => "Ft",  // Hungarian Forint
                "PLN" => "zł",  // Polish Złoty
                "ILS" => "₪",   // Israeli Shekel
                "NGN" => "₦",   // Nigerian Naira
                "EGP" => "EGP", // Egyptian Pound
                "TZS" => "TSh", // Tanzanian Shilling
                "KES" => "KSh", // Kenyan Shilling
                "PKR" => "₨",   // Pakistani Rupee
                "BDT" => "৳",   // Bangladeshi Taka
                "HNL" => "L",   // Honduran Lempira
                "GYD" => "G$",  // Guyanese Dollar
                "BZD" => "BZ$", // Belize Dollar
                "JMD" => "J$",  // Jamaican Dollar
                "TTD" => "TT$", // Trinidad and Tobago Dollar
                "PAB" => "B/.", // Panamanian Balboa
                "GTQ" => "Q",   // Guatemalan Quetzal
                "XOF" => "CFA", // West African CFA Franc
                "XPF" => "F",   // CFP Franc
                "MAD" => "MAD", // Moroccan Dirham
                "GMD" => "D",   // Gambian Dalasi
                "MWK" => "MK",  // Malawian Kwacha
                "ZMW" => "ZMW", // Zambian Kwacha
                "UGX" => "UGX", // Ugandan Shilling
                "MZN" => "MZN", // Mozambican Metical
                "BWP" => "BWP", // Botswana Pula
                "LSL" => "LSL", // Lesotho Loti
                "SZL" => "SZL"  // Swazi Lilangeni
            ];

            $flag_map = [
                "USD" => "🇺🇸",   // United States Dollar
                "EUR" => "🇪🇺",   // Euro
                "GBP" => "🇬🇧",   // British Pound Sterling
                "JPY" => "🇯🇵",   // Japanese Yen
                "KRW" => "🇰🇷",   // South Korean Won
                "RUB" => "🇷🇺",   // Russian Ruble
                "INR" => "🇮🇳",   // Indian Rupee
                "TRY" => "🇹🇷",   // Turkish Lira
                "THB" => "🇹🇭",   // Thai Baht
                "VND" => "🇻🇳",   // Vietnamese Dong
                "AED" => "🇦🇪",   // United Arab Emirates Dirham
                "CAD" => "🇨🇦",   // Canadian Dollar
                "AUD" => "🇦🇺",   // Australian Dollar
                "NZD" => "🇳🇿",   // New Zealand Dollar
                "CHF" => "🇨🇭",   // Swiss Franc
                "DKK" => "🇩🇰",   // Danish Krone
                "HKD" => "🇭🇰",   // Hong Kong Dollar
                "NOK" => "🇳🇴",   // Norwegian Krone
                "SEK" => "🇸🇪",   // Swedish Krona
                "SGD" => "🇸🇬",   // Singapore Dollar
                "ZAR" => "🇿🇦",   // South African Rand
                "PHP" => "🇵🇭",   // Philippine Peso
                "MXN" => "🇲🇽",   // Mexican Peso
                "BRL" => "🇧🇷",   // Brazilian Real
                "ARS" => "🇦🇷",   // Argentine Peso
                "CLP" => "🇨🇱",   // Chilean Peso
                "COP" => "🇨🇴",   // Colombian Peso
                "PEN" => "🇵🇪",   // Peruvian Sol
                "CZK" => "🇨🇿",   // Czech Koruna
                "HUF" => "🇭🇺",   // Hungarian Forint
                "PLN" => "🇵🇱",   // Polish Złoty
                "ILS" => "🇮🇱",   // Israeli Shekel
                "NGN" => "🇳🇬",   // Nigerian Naira
                "EGP" => "🇪🇬",   // Egyptian Pound
                "TZS" => "🇹🇿",   // Tanzanian Shilling
                "KES" => "🇰🇪",   // Kenyan Shilling
                "PKR" => "🇵🇰",   // Pakistani Rupee
                "BDT" => "🇧🇩",   // Bangladeshi Taka
                "HNL" => "🇭🇳",   // Honduran Lempira
                "GYD" => "🇬🇾",   // Guyanese Dollar
                "BZD" => "🇧🇿",   // Belize Dollar
                "JMD" => "🇯🇲",   // Jamaican Dollar
                "TTD" => "🇹🇹",   // Trinidad and Tobago Dollar
                "PAB" => "🇵🇦",   // Panamanian Balboa
                "GTQ" => "🇬🇹",   // Guatemalan Quetzal
                "XOF" => "🇫🇷",   // West African CFA Franc (flag of France as a representation)
                "XPF" => "🇫🇷",   // CFP Franc (flag of France as a representation)
                "MAD" => "🇲🇦",   // Moroccan Dirham
                "GMD" => "🇬🇲",   // Gambian Dalasi
                "MWK" => "🇲🇼",   // Malawian Kwacha
                "ZMW" => "🇿🇲",   // Zambian Kwacha
                "UGX" => "🇺🇬",   // Ugandan Shilling
                "MZN" => "🇲🇿",   // Mozambican Metical
                "BWP" => "🇧🇼",   // Botswana Pula
                "LSL" => "🇱🇸",   // Lesotho Loti
                "SZL" => "🇸🇿"    // Swazi Lilangeni
            ];

            foreach ($country_cu as $currency) {
                if (isset($symbol_map[$currency])) {
                    $currency_symbols[$symbol_map[$currency]] = $currency;
                }
            }

            foreach ($country_cu as $currency) {
                if (isset($flag_map[$currency])) {
                    $flag_symbols[$currency] = $flag_map[$currency];
                }
            }

            $output = '';
            if (!empty($currency_symbols)) {
                $output .= '<div class="wrapper-header-currency">';
                $output .= '<form>';
                $output .= '<select id="currency-select" name="wmc-currency" aria-label="Select currency" onchange="this.form.submit()">';
                
                foreach ($currency_symbols as $key => $value) {
                    $output .= '<option value="' . $value . '">' . $flag_symbols[$value] . ' ' . $key . ' ' . $value . '</option>';
                }
                $output .= '</select>';
                $output .= '</form>';
                $output .= '</div>';
                
                $output .= '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">';
                $output .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>';
                $output .= '<script>
                        jQuery(document).ready(function() {
                            jQuery(".wrapper-header-currency form select").select2({
                                width: "118px",
                                templateResult: formatCurrency,
                                templateSelection: formatCurrency
                            });

                            function formatCurrency(state) {
                                if (!state.id) {
                                    return state.text;
                                }
                                return $("<span>" + state.text + "</span>");
                            }
                        });
                        document.addEventListener("DOMContentLoaded", function () {
                            const activeCurrency = document.querySelector(".wmc-currency.wmc-active");
                            if(activeCurrency) {
                                const currency = activeCurrency.getAttribute("data-currency");
                                if (currency) {
                                // Lưu vào localStorage
                                    localStorage.setItem("selectedCurrency", currency);
                                    
                                }
                            }
                            const savedCurrency = localStorage.getItem("selectedCurrency");
                            if(savedCurrency) {
                                document.getElementById("currency-select").value = savedCurrency;
                            }
                        });
                    </script>';
            }
            return $output;
        }
    
}

add_shortcode('currency_selector', 'get_data_currency_shortcode');

// Disable native lazy loading

add_filter( 'wp_lazy_loading_enabled', '__return_false' );

add_shortcode('box_description_top_category', 'get_data_box_description_top_category');
function get_data_box_description_top_category() {
    ob_start();
    $list_category = get_field('list_categories_des','options');
    $term = get_queried_object();
    $term_id = $term->term_id;

    if ( ! empty( $list_category ) && $term_id ) {
        foreach ( $list_category as $ids_cat ) {
            if ( ! empty( $ids_cat['category'] ) && in_array( $term_id, $ids_cat['category'], true ) ) {
                $full_html  = $ids_cat['top_description'];
                $plain_text = wp_strip_all_tags( $full_html );
                $word_count = str_word_count( $plain_text );
                $preview    = wp_trim_words( $plain_text, 40, '…' );
                ?>
                
                <style>
                  .wrapper-des-top-category { max-width: 100%; margin: 0 auto; padding: 15px; }
                  @media only screen and (max-width: 768px) {
                    .desktop-first { display: none; }
                    .mobile-first  { display: block; }
                  }
                  @media only screen and (min-width: 769px) {
                    .desktop-first { display: block; }
                    .mobile-first  { display: none; }
                  }
                </style>
                
                <div class="wrapper-des-top-category">
                  <!-- Desktop: full text -->
                  <div class="desktop-first">
                    <?php echo $full_html; ?>
                  </div>
                
                  <!-- Mobile: truncated with toggle -->
                  <div class="mobile-first">
                    <div class="preview-text">
                      <?php echo $preview; ?>
                    </div>
                    <?php if ( $word_count > 40 ) : ?>
                      <a href="#" class="toggle-more" style="font-weight:bold;">Read More</a>
                      <div class="full-text" style="display:none;">
                        <?php echo $full_html; ?>
                        <a href="#" class="toggle-less" style="font-weight:bold;">Read Less</a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                  document.querySelectorAll('.wrapper-des-top-category .mobile-first').forEach(function(container) {
                    var preview = container.querySelector('.preview-text');
                    var moreBtn = container.querySelector('.toggle-more');
                    var fullDiv = container.querySelector('.full-text');
                    var lessBtn = container.querySelector('.toggle-less');

                    if ( moreBtn ) {
                      moreBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        preview.style.display = 'none';
                        moreBtn.style.display = 'none';
                        fullDiv.style.display = 'block';
                      });
                    }
                    if ( lessBtn ) {
                      lessBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        fullDiv.style.display = 'none';
                        preview.style.display = 'block';
                        moreBtn.style.display = 'inline';
                      });
                    }
                  });
                });
                </script>
                
                <?php
                break; // stop looping once matched
            }
        }
    }
    return ob_get_clean();
}


add_shortcode('box_description_bot_category', 'get_data_box_description_bot_category');
function get_data_box_description_bot_category() {
    ob_start();
    $list_category = get_field('list_categories_des','options');
    $term = get_queried_object();
    $term_id = $term->term_id;

    if ( ! empty( $list_category ) && $term_id ) {
        foreach ( $list_category as $ids_cat ) {
            if ( ! empty( $ids_cat['category'] ) && in_array( $term_id, $ids_cat['category'], true ) ) {
                ?>
                <div class="wrapper-des-bottom-category">
                    <?php echo $ids_cat['bottom_description']; ?>
                </div>
                <?php
                break;
            }
        }
    }
    return ob_get_clean();
}


add_filter('woocommerce_payments_google_pay_payment_request_args', function($args) {
    // Reformat total price display value (Google Pay UI)
    if (isset($args['total']['amount'])) {
        $args['total']['amount'] = number_format((float) $args['total']['amount'], 2, '.', '');
    }
    return $args;
});


if (function_exists('opcache_reset')) {
    opcache_reset();
}


// Include the project pack functions for custom CSS and other features.
require_once get_stylesheet_directory() . '/project-pack/functions.php';

/**
 * Hook to add tracking code to <head> from ACF Options page field (vf_header_scripts).
 */
add_action( 'wp_head', 'vf_header_tracking_scripts', 1 );
function vf_header_tracking_scripts() {
    if ( ! function_exists( 'get_field' ) ) {
        return;
    }

    $scripts = get_field( 'vf_header_scripts', 'option' );
    if ( ! empty( $scripts ) ) {
        echo  "\n" . $scripts . "\n";
    }
}

function vfs_custom_admin_bar_css() {
    ?>
    <style>
        #wpadminbar #wp-admin-bar-updraft_admin_node,
        #wpadminbar #wp-admin-bar-btn-wcabe-admin-bar,
        #wpadminbar #wp-admin-bar-brevo_push_admin_bar_button,
        #wpadminbar #wp-admin-bar-wpcode-admin-bar-info,
        #wpadminbar #wp-admin-bar-user-registration-menu,
        #wpadminbar #wp-admin-bar-villatheme,
        #wpadminbar #wp-admin-bar-stats,
        #wpadminbar #wp-admin-bar-et-top-bar-general-menu,
        #wpadminbar #wp-admin-bar-et-top-bar-theme-builders-menu,
        #wpadminbar #wp-admin-bar-weglot,
        #wpadminbar #wp-admin-bar-et-top-bar-xstore-sales-booster {
            display: none !important;
        }
    </style>
    <?php
}
add_action('admin_head', 'vfs_custom_admin_bar_css');
add_action('wp_head', 'vfs_custom_admin_bar_css');


/**
 * PPOM display logic: show/hide PPOM fields based on Customize dropdown.
 * Migrated from WPCode snippet.
 */
add_action('wp_footer', 'custom_ppom_display_logic');
function custom_ppom_display_logic() {
    if (is_product()) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.ppom-rendering-fields').hide();

            $('#customize').change(function() {
                if ($(this).val() === 'Customize') {
                    $('.ppom-rendering-fields').slideDown();
                } else {
                    $('.ppom-rendering-fields').slideUp();
                    $('#custom_name').val('');
                }
            });

            if ($('#customize').val() === 'Customize') {
                $('.ppom-rendering-fields').show();
            }
        });
        </script>
        <?php
    }
}

/**
 * Add "Size Guide" tab to WooCommerce single product page.
 * Shows children sizes for "kids" category (slug or ID 1839), adult sizes for all others.
 */
add_filter('woocommerce_product_tabs', 'vf_size_guide_tab');
function vf_size_guide_tab($tabs) {
    $tabs['size_guide'] = array(
        'title'    => __('Size Guide', 'flavor-flavor'),
        'priority' => 15,
        'callback' => 'vf_size_guide_tab_content',
    );
    return $tabs;
}

function vf_size_guide_tab_content() {
    global $product;

    $is_kids = false;

    if ($product) {
        // Check if product has category slug "kids" or category term ID 1839
        if (has_term('kids', 'product_cat', $product->get_id()) || has_term(1839, 'product_cat', $product->get_id())) {
            $is_kids = true;
        }
    }

    ?>
    <div class="vf-size-guide">
        <p class="vf-size-guide__intro">Find your perfect fit using the size chart below. Measurements are approximate and may vary slightly.</p>
        <div class="vf-size-guide__toggle">
            <button type="button" class="vf-unit-btn active" data-unit="metric">CM / KG</button>
            <button type="button" class="vf-unit-btn" data-unit="imperial">INCHES / LB</button>
        </div>

        <?php if ($is_kids) : ?>
            <!-- Children Size Guide — Metric -->
            <div class="vf-size-table" data-unit="metric">
                <table>
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Height</th>
                            <th>Weight</th>
                            <th>Recommended Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>16</td><td>90-105cm</td><td>25-30kg</td><td>3-5 Years</td></tr>
                        <tr><td>18</td><td>105-115cm</td><td>30-35kg</td><td>5-6 Years</td></tr>
                        <tr><td>20</td><td>115-125cm</td><td>35-40kg</td><td>6-7 Years</td></tr>
                        <tr><td>22</td><td>125-135cm</td><td>40-45kg</td><td>7-8 Years</td></tr>
                        <tr><td>24</td><td>135-145cm</td><td>45-50kg</td><td>9-10 Years</td></tr>
                        <tr><td>26</td><td>145-155cm</td><td>50-55kg</td><td>11-12 Years</td></tr>
                        <tr><td>28</td><td>155-165cm</td><td>55-60kg</td><td>13-15 Years</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Children Size Guide — Imperial -->
            <div class="vf-size-table" data-unit="imperial" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Height</th>
                            <th>Weight</th>
                            <th>Recommended Age</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>16</td><td>35.4-41.3″</td><td>55-66 lb</td><td>3-5 Years</td></tr>
                        <tr><td>18</td><td>41.3-45.3″</td><td>66-77 lb</td><td>5-6 Years</td></tr>
                        <tr><td>20</td><td>45.3-49.2″</td><td>77-88 lb</td><td>6-7 Years</td></tr>
                        <tr><td>22</td><td>49.2-53.1″</td><td>88-99 lb</td><td>7-8 Years</td></tr>
                        <tr><td>24</td><td>53.1-57.1″</td><td>99-110 lb</td><td>9-10 Years</td></tr>
                        <tr><td>26</td><td>57.1-61″</td><td>110-121 lb</td><td>11-12 Years</td></tr>
                        <tr><td>28</td><td>61-65″</td><td>121-132 lb</td><td>13-15 Years</td></tr>
                    </tbody>
                </table>
            </div>

        <?php else : ?>
            <!-- Adult Size Guide — Metric -->
            <div class="vf-size-table" data-unit="metric">
                <table>
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Length</th>
                            <th>Chest</th>
                            <th>Shoulders</th>
                            <th>Height</th>
                            <th>Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>S</td><td>71cm</td><td>102cm</td><td>46cm</td><td>158-175cm</td><td>50-65kg</td></tr>
                        <tr><td>M</td><td>73cm</td><td>106cm</td><td>47cm</td><td>165-180cm</td><td>60-70kg</td></tr>
                        <tr><td>L</td><td>75cm</td><td>110cm</td><td>48.5cm</td><td>168-185cm</td><td>70-80kg</td></tr>
                        <tr><td>XL</td><td>77cm</td><td>115cm</td><td>49.5cm</td><td>170-190cm</td><td>75-85kg</td></tr>
                        <tr><td>XXL</td><td>81cm</td><td>120cm</td><td>50.5cm</td><td>170-200cm</td><td>85-100kg</td></tr>
                        <tr><td>3XL</td><td>83cm</td><td>124cm</td><td>51.5cm</td><td>180-205cm</td><td>90-110kg</td></tr>
                        <tr><td>4XL</td><td>85cm</td><td>128cm</td><td>52cm</td><td>185-205cm</td><td>95-120kg</td></tr>
                    </tbody>
                </table>
            </div>

            <!-- Adult Size Guide — Imperial -->
            <div class="vf-size-table" data-unit="imperial" style="display:none;">
                <table>
                    <thead>
                        <tr>
                            <th>Size</th>
                            <th>Length</th>
                            <th>Chest</th>
                            <th>Shoulders</th>
                            <th>Height</th>
                            <th>Weight</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>S</td><td>28″</td><td>40.2″</td><td>18.1″</td><td>62.2-68.9″</td><td>110-143 lb</td></tr>
                        <tr><td>M</td><td>28.7″</td><td>41.7″</td><td>18.5″</td><td>65-70.9″</td><td>132-154 lb</td></tr>
                        <tr><td>L</td><td>29.5″</td><td>43.3″</td><td>19.1″</td><td>66.1-72.8″</td><td>154-176 lb</td></tr>
                        <tr><td>XL</td><td>30.3″</td><td>45.3″</td><td>19.5″</td><td>66.9-74.8″</td><td>165-187 lb</td></tr>
                        <tr><td>XXL</td><td>31.9″</td><td>47.2″</td><td>19.9″</td><td>66.9-78.7″</td><td>187-220 lb</td></tr>
                        <tr><td>3XL</td><td>32.7″</td><td>48.8″</td><td>20.3″</td><td>70.9-80.7″</td><td>198-243 lb</td></tr>
                        <tr><td>4XL</td><td>33.5″</td><td>50.4″</td><td>20.5″</td><td>72.8-80.7″</td><td>209-265 lb</td></tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

        <p class="vf-size-guide__link">Need more help? <a href="/sizing/" target="_blank">View Full Size Guide &rarr;</a></p>
    </div>

    <style>
        body .woocommerce-tabs.horizontal .wc-tabs .et-woocommerce-tab {
            font-weight: 600;
        }
        body .woocommerce-tabs {
            margin-top: 30px;
        }
        .vf-size-guide { margin-top: 10px; }
        .vf-size-guide__toggle { margin-bottom: 15px; display: flex; gap: 8px; }
        .vf-unit-btn {
            padding: 8px 18px;
            border: 1px solid #ddd;
            background: #f5f5f5 !important;
            cursor: pointer;
            font-size: 16px !important;
            letter-spacing: 0.5px;
            transition: all 0.2s ease;
        }
        .vf-unit-btn.active {
            background: #222 !important;
            color: #fff !important;
            border-color: #222;
        }
        .vf-size-guide table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        .vf-size-guide table th,
        .vf-size-guide table td {
            padding: 10px 14px !important;
            text-align: left;
            border: 1px solid #e0e0e0;
        }
        @media screen and (max-width: 768px) {
            .vf-size-guide table th,
            .vf-size-guide table td {
                padding: 8px 6px !important;
                font-size: 13px
            }
        }
        .vf-size-guide table th {
            background: #222;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            letter-spacing: 0.5px;
            color: #fff;
        }
        .vf-size-guide table td:first-child {
            font-weight: 600;
        }
        .vf-size-guide table tr:nth-child(even) td {
            background: #fafafa;
        }
        .vf-size-guide__intro {
            color: #444;
            font-size: 14px;
            margin-bottom: 12px;
        }
        .vf-size-guide__link {
            margin-top: 15px;
            font-size: 14px;
        }
        .vf-size-guide__link a {
            color: #222;
            font-weight: 600;
            text-decoration: underline;
        }
        .vf-size-guide__link a:hover {
            color: #000;
        }
    </style>

    <script>
        jQuery(document).ready(function($) {
            $('.vf-unit-btn').on('click', function() {
                var unit = $(this).data('unit');
                $('.vf-unit-btn').removeClass('active');
                $(this).addClass('active');
                $('.vf-size-table').hide();
                $('.vf-size-table[data-unit="' + unit + '"]').show();
            });
        });
    </script>
    <?php
}

/**
 * Unregister unused custom post types and hide WP default "Posts".
 * CPTs: testimonials, staticblocks, etheme_slides (XStore theme)
 * Also hides default "post" from admin menu, admin bar, and dashboard.
 */
add_action('init', 'vf_unregister_unused_post_types', 100);
function vf_unregister_unused_post_types() {
    // XStore theme CPTs
    $cpts_to_remove = array('testimonials', 'staticblocks', 'etheme_slides');

    foreach ($cpts_to_remove as $cpt) {
        if (post_type_exists($cpt)) {
            unregister_post_type($cpt);
        }
    }
}

// Remove "Posts" from admin sidebar menu
add_action('admin_menu', 'vf_remove_default_post_menu', 999);
function vf_remove_default_post_menu() {
    remove_menu_page('edit.php');
}

// Remove "+ New > Post" from admin bar
add_action('admin_bar_menu', 'vf_remove_post_from_admin_bar', 999);
function vf_remove_post_from_admin_bar($wp_admin_bar) {
    $wp_admin_bar->remove_node('new-post');
}

/**
 * Load Custom Elementor Widgets
 */
if ( file_exists( get_stylesheet_directory() . '/elementor/widgets-load.php' ) ) {
    require_once get_stylesheet_directory() . '/elementor/widgets-load.php';
}
