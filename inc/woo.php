<?php
/**
 * WooCommerce Customizations & Hooks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register custom "Shipped" order status
 */
function register_shipped_order_status() {
    register_post_status( 'wc-shipped', array(
        'label'                     => _x( 'Shipped', 'Order status', 'woocommerce' ),
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop( 'Shipped <span class="count">(%s)</span>', 'Shipped <span class="count">(%s)</span>', 'woocommerce' ),
    ) );
}
add_action( 'init', 'register_shipped_order_status' );

/**
 * Add "Shipped" to WooCommerce order status list
 */
function add_shipped_to_order_statuses( $order_statuses ) {
    $order_statuses['wc-shipped'] = _x( 'Shipped', 'Order status', 'woocommerce' );
    return $order_statuses;
}
add_filter( 'wc_order_statuses', 'add_shipped_to_order_statuses' );

/**
 * Automatically set order status to Shipped if tracking item is present
 */
function force_shipped_status_if_tracking( $order, $data_store ) {
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return;
    }
    
    $tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
    
    if ( ! empty( $tracking_items ) && $order->get_status() !== 'shipped' ) {
        $order->set_status( 'shipped' );
        $order->add_order_note( __( 'Automatically marked shipped via tracking check', 'xstore-child' ) );
    }
}
add_action( 'woocommerce_before_order_object_save', 'force_shipped_status_if_tracking', 999, 2 );

/**
 * Change related products heading
 */
function change_product_related_products_heading() {
    return esc_html__( 'You might also like', 'xstore' );
}
add_filter( 'woocommerce_product_related_products_heading', 'change_product_related_products_heading' );

/**
 * Custom ACF product rating star display before add to cart form
 */
function custom_acf_product_rating() {
    global $post;

    if ( ! function_exists( 'get_field' ) || ! $post ) {
        return;
    }

    $acf_rating    = get_field( 'number_rating', $post->ID );
    $custom_rating = get_field( 'custom_rating', $post->ID );

    if ( ! $custom_rating ) {
        return;
    }

    $full_stars   = floor( $acf_rating );
    $decimal_part = $acf_rating - $full_stars;
    $percent      = $decimal_part * 100;

    echo '<div class="custom-acf-rating">';
    echo '<div class="star-container">';

    for ( $i = 0; $i < $full_stars; $i++ ) {
        echo '<span class="star full">★</span>';
    }

    if ( $decimal_part > 0 ) {
        echo '<span class="star half" style="--percent:' . esc_attr( $percent ) . '%;">★</span>';
    }

    $empty_stars = 5 - $full_stars - ( $decimal_part > 0 ? 1 : 0 );
    for ( $i = 0; $i < $empty_stars; $i++ ) {
        echo '<span class="star empty">☆</span>';
    }

    echo '</div>';
    echo '<span class="rating-count">' . esc_html( $custom_rating ) . '</span>';
    echo '</div>';
}
add_action( 'woocommerce_before_add_to_cart_form', 'custom_acf_product_rating', 9 );

/**
 * Shipping estimates AJAX handler
 */
function get_order_dates_ajax_handler() {
    $ordered_date           = date( 'M jS' );
    $order_ready_date_first = date( 'M jS', strtotime( '+1 days' ) );
    $order_ready_date_last  = date( 'M jS', strtotime( '+2 days' ) );
    $delivered_date_first   = date( 'M jS', strtotime( '+10 days' ) );
    $delivered_date_last    = date( 'M jS', strtotime( '+12 days' ) );

    ob_start();
    ?>
    <h3>Shipping Estimates</h3>
    <div class="order-dates-container">
        <div class="order-date"><i class="et-icon et-shopping-cart"></i><?php echo esc_html( $ordered_date ); ?><span> Ordered</span></div>
        <div class="order-ready"><i class="et-icon et-delivery"></i><?php echo esc_html( $order_ready_date_first ); ?> - <?php echo esc_html( $order_ready_date_last ); ?><span> Order Ready</span></div>
        <div class="delivered-date"><i class="et-icon et-gift"></i><?php echo esc_html( $delivered_date_first ); ?> - <?php echo esc_html( $delivered_date_last ); ?><span> Delivered</span></div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_get_order_dates', 'get_order_dates_ajax_handler' );
add_action( 'wp_ajax_nopriv_get_order_dates', 'get_order_dates_ajax_handler' );

/**
 * Add placeholder for AJAX order dates below single product add-to-cart form
 */
add_action( 'woocommerce_after_add_to_cart_form', function() {
    echo '<div id="ajax-order-dates"></div>';
} );

/**
 * Add custom AggregateRating schema to head on single product pages
 */
function add_custom_aggregate_rating_schema() {
    if ( ! is_product() ) {
        return;
    }

    global $product;

    if ( ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    if ( ! is_a( $product, 'WC_Product' ) ) {
        return;
    }

    $product_name        = $product->get_name();
    $product_url         = get_permalink( $product->get_id() );
    $product_image       = wp_get_attachment_url( $product->get_image_id() );
    $product_description = wp_strip_all_tags( $product->get_short_description() );

    $rating_value = get_post_meta( get_the_ID(), 'number_rating', true );
    $review_count = get_post_meta( get_the_ID(), 'count_rating', true );

    if ( ! empty( $rating_value ) && is_numeric( $rating_value ) && ! empty( $review_count ) && is_numeric( $review_count ) ) {
        ?>
        <script type="application/ld+json">
        {
          "@context": "https://schema.org",
          "@type": "Product",
          "name": "<?php echo esc_js( $product_name ); ?>",
          "url": "<?php echo esc_url( $product_url ); ?>",
          "image": "<?php echo esc_url( $product_image ); ?>",
          "description": "<?php echo esc_js( $product_description ); ?>",
          "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "<?php echo esc_js( $rating_value ); ?>",
            "reviewCount": "<?php echo esc_js( $review_count ); ?>",
            "bestRating": 5
          }
        }
        </script>
        <?php
    }
}
add_action( 'wp_head', 'add_custom_aggregate_rating_schema' );

/**
 * Format total price display value for Google Pay payment request
 */
add_filter( 'woocommerce_payments_google_pay_payment_request_args', function( $args ) {
    if ( isset( $args['total']['amount'] ) ) {
        $args['total']['amount'] = number_format( (float) $args['total']['amount'], 2, '.', '' );
    }
    return $args;
} );

/**
 * Highlight only the coupon code text in cart totals.
 * Migrated from project-pack.
 */
function bt_highlight_coupon_code_label( $label, $coupon ) {
    if ( ! $coupon || ! is_object( $coupon ) || ! method_exists( $coupon, 'get_code' ) ) {
        return $label;
    }

    $coupon_code = $coupon->get_code();

    if ( '' === $coupon_code ) {
        return $label;
    }

    $prefix = esc_html__( 'Coupon:', 'woocommerce' ) . ' ';

    return $prefix . '<span style="color: var(--et_red-color);" class="bt-coupon-code">' . esc_html( $coupon_code ) . '</span>';
}
add_filter( 'woocommerce_cart_totals_coupon_label', 'bt_highlight_coupon_code_label', 10, 2 );

/**
 * Meta Pixel purchase tracking hook on thank you page.
 * Migrated from project-pack.
 */
function vf_custom_meta_pixel_purchase( $order_id ) {
    if ( ! $order_id ) {
        return;
    }
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }
    // Only track paid orders.
    if ( ! $order->is_paid() ) {
        return;
    }
    // Prevent duplicate tracking.
    if ( $order->get_meta( '_meta_pixel_purchase_tracked', true ) ) {
        return;
    }
    $value    = (float) $order->get_total();
    $currency = $order->get_currency();
    ?>
    <script>
        if (typeof fbq === 'function') {
            fbq('track', 'Purchase', {
                value: <?php echo wp_json_encode( $value ); ?>,
                currency: <?php echo wp_json_encode( $currency ); ?>
            });
        }
    </script>
    <?php
    $order->update_meta_data( '_meta_pixel_purchase_tracked', '1' );
    $order->save();
}
add_action( 'woocommerce_thankyou', 'vf_custom_meta_pixel_purchase', 10, 1 );

/**
 * PPOM display logic: show/hide PPOM fields based on Customize dropdown.
 */
function custom_ppom_display_logic() {
    if ( is_product() ) {
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
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
add_action( 'wp_footer', 'custom_ppom_display_logic' );

/**
 * Add "Size Guide" tab to WooCommerce single product page.
 * Shows children sizes for "kids" category (slug or ID 1839), adult sizes for all others.
 */
function vf_size_guide_tab( $tabs ) {
    $tabs['size_guide'] = array(
        'title'    => __( 'Size Guide', 'flavor-flavor' ),
        'priority' => 15,
        'callback' => 'vf_size_guide_tab_content',
    );
    return $tabs;
}
add_filter( 'woocommerce_product_tabs', 'vf_size_guide_tab' );

function vf_size_guide_tab_content() {
    global $product;

    if ( ! is_a( $product, 'WC_Product' ) ) {
        $product = wc_get_product( get_the_ID() );
    }

    $is_kids = false;

    if ( is_a( $product, 'WC_Product' ) ) {
        if ( has_term( 'kids', 'product_cat', $product->get_id() ) || has_term( 1839, 'product_cat', $product->get_id() ) ) {
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

        <?php if ( $is_kids ) : ?>
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
