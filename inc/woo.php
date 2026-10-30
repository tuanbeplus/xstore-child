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
            $('#customize').on('change', function() {
                if ($(this).val() === 'Customize') {
                    $('.ppom-rendering-fields').stop(true, true).show();
                } else {
                    $('.ppom-rendering-fields').stop(true, true).hide();
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
 * Product thumbnails in the checkout order summary.
 */
add_filter( 'etheme_checkout_order_review_product_images', '__return_true' );

/**
 * Product thumbnails on the thank-you order table.
 *
 * @param string               $item_name
 * @param WC_Order_Item        $item
 * @param bool                 $is_visible
 * @return string
 */
function vf_thankyou_order_item_image( $item_name, $item, $is_visible ) {
    unset( $is_visible );

    if ( ! function_exists( 'is_order_received_page' ) || ! is_order_received_page() ) {
        return $item_name;
    }
    if ( ! $item instanceof WC_Order_Item_Product || false !== strpos( $item_name, '<img' ) ) {
        return $item_name;
    }

    $product = $item->get_product();
    if ( ! $product ) {
        return $item_name;
    }

    $image = $product->get_image( 'woocommerce_thumbnail', array( 'class' => 'vf-order-item-image' ) );

    return '<span class="vf-order-item">' . $image . '<span class="vf-order-item-name">' . $item_name . '</span></span>';
}
add_filter( 'woocommerce_order_item_name', 'vf_thankyou_order_item_image', 10, 3 );

/**
 * Show billing and shipping on the thank-you page.
 * WooCommerce hides them unless the logged-in user owns the order.
 *
 * @param bool     $show
 * @param WC_Order $order
 * @return bool
 */
function vf_show_thankyou_customer_details( $show, $order ) {
    unset( $order );
    if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
        return true;
    }
    return $show;
}
add_filter( 'woocommerce_order_details_show_customer_details', 'vf_show_thankyou_customer_details', 10, 2 );

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

/**
 * Get the total item quantity that qualifies for the Buy 3 Get 1 promotion.
 *
 * This intentionally counts cart quantities rather than distinct product lines.
 * The coupon itself remains managed independently by WooCommerce.
 *
 * @return int
 */
function vf_buy_3_get_1_cart_quantity() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return 0;
    }

    return max( 0, (int) WC()->cart->get_cart_contents_count() );
}

/**
 * Return the Buy 3 Get 1 promotion-progress component.
 *
 * @return string
 */
function vf_get_buy_3_get_1_progress_bar() {
    $quantity       = vf_buy_3_get_1_cart_quantity();
    $required_items = 3;
    $completed      = min( $quantity, $required_items );
    $progress       = ( $completed / $required_items ) * 100;
    $is_unlocked    = $quantity >= $required_items;

    if ( $is_unlocked ) {
        $message = __( 'You unlocked 1 FREE kit!', 'xstore-child' );
    } else {
        $remaining = $required_items - $quantity;
        $message   = sprintf(
            _n(
                'Add %d more kit to get 1 FREE',
                'Add %d more kits to get 1 FREE',
                $remaining,
                'xstore-child'
            ),
            $remaining
        );
    }

    ob_start();
    ?>
    <div class="vf-buy-3-progress<?php echo $is_unlocked ? ' is-unlocked' : ''; ?>" role="status" aria-live="polite">
        <p class="vf-buy-3-progress__message"><?php echo esc_html( $message ); ?></p>
        <p class="vf-buy-3-progress__coupon">
            <span><?php esc_html_e( 'Coupon:', 'xstore-child' ); ?></span>
            <strong>buytwogetthree</strong>
        </p>
        <div class="vf-buy-3-progress__track" role="progressbar" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( $required_items ); ?>" aria-valuenow="<?php echo esc_attr( $completed ); ?>" aria-label="<?php esc_attr_e( 'Buy 3 Get 1 Free progress', 'xstore-child' ); ?>">
            <span class="vf-buy-3-progress__indicator" style="width: <?php echo esc_attr( $progress ); ?>%;" aria-hidden="true"></span>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * Output the Buy 3 Get 1 promotion progress in the relevant cart summary.
 */
function vf_output_buy_3_get_1_progress_bar() {
    echo vf_get_buy_3_get_1_progress_bar(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'woocommerce_before_mini_cart', 'vf_output_buy_3_get_1_progress_bar', 5 );
add_action( 'woocommerce_before_cart_totals', 'vf_output_buy_3_get_1_progress_bar', 5 );
add_action( 'woocommerce_checkout_before_order_review', 'vf_output_buy_3_get_1_progress_bar', 5 );

/**
 * Output the XStore mini-cart subtotal after WooCommerce coupon discounts.
 *
 * XStore's default callback displays get_cart_subtotal(), which is calculated
 * before coupons. Cart contents total is WooCommerce's discounted product
 * amount and excludes shipping, so it matches the mini-cart subtotal label.
 *
 * @return void
 */
function vf_discounted_mini_cart_subtotal() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
        return;
    }

    $cart              = WC()->cart;
    $insurance_fees    = (float) $cart->get_fee_total();
    $regular_subtotal  = (float) $cart->get_subtotal() + $insurance_fees;
    $discounted_total  = (float) $cart->get_cart_contents_total() + $insurance_fees;
    $has_coupon_saving = $regular_subtotal > $discounted_total;
    $subtotal_html     = '<span class="big-coast">';

    if ( $has_coupon_saving ) {
        $subtotal_html .= '<del class="vf-mini-cart-original-price">' . wp_kses_post( wc_price( $regular_subtotal ) ) . '</del>';
    }

    $subtotal_html .= '<ins class="vf-mini-cart-discounted-price">' . wp_kses_post( wc_price( $discounted_total ) ) . '</ins>';
    $subtotal_html .= '</span>';

    echo '<span class="small-h">' . esc_html__( 'Subtotal:', 'xstore' ) . '</span> ' . $subtotal_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

/**
 * Replace XStore's pre-coupon mini-cart subtotal callback for every render,
 * including the theme's custom mini-cart AJAX response.
 *
 * @return void
 */
function vf_use_discounted_mini_cart_subtotal() {
    remove_action( 'woocommerce_widget_shopping_cart_total', 'woocommerce_widget_shopping_cart_subtotal', 10 );
    remove_action( 'woocommerce_widget_shopping_cart_total', 'etheme_woocommerce_widget_shopping_cart_subtotal', 10 );
    add_action( 'woocommerce_widget_shopping_cart_total', 'vf_discounted_mini_cart_subtotal', 10 );
}
add_action( 'woocommerce_before_mini_cart', 'vf_use_discounted_mini_cart_subtotal', 1 );

/**
 * Refresh the mini-cart promotion progress with WooCommerce cart fragments.
 *
 * @param array $fragments
 * @return array
 */
function vf_buy_3_get_1_progress_bar_fragment( $fragments ) {
    $fragments['.vf-buy-3-progress'] = vf_get_buy_3_get_1_progress_bar();
    return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'vf_buy_3_get_1_progress_bar_fragment' );

/**
 * Render XStore's mini-cart footer with the insurance control directly above
 * its subtotal. XStore exposes no hook inside this footer section.
 */
function vf_mini_cart_footer_with_insurance() {
    if ( ! WC()->cart || WC()->cart->is_empty() ) {
        return;
    }

    remove_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );

    if ( etheme_get_option( 'cart_content_view_cart_button_et-desktop', false ) ) {
        add_action( 'woocommerce_widget_shopping_cart_buttons', 'woocommerce_widget_shopping_cart_button_view_cart', 10 );
    }
    ?>
    <div class="product_list-popup-footer-wrapper">
        <div class="product_list-popup-footer-inner">
            <div class="cart-popup-footer">
                <a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="btn-view-cart wc-forward">
                    <?php esc_html_e( 'Shopping cart ', 'xstore' ); ?> (<?php echo esc_html( WC()->cart->get_cart_contents_count() ); ?>)
                </a>
                <?php if ( class_exists( 'Codenitive_Shipping_Insurance' ) ) : ?>
                    <div class="vf-mini-cart-insurance-slot" aria-live="polite"></div>
                <?php endif; ?>
                <div class="cart-widget-subtotal woocommerce-mini-cart__total total">
                    <?php do_action( 'woocommerce_widget_shopping_cart_total' ); ?>
                </div>
            </div>

            <?php do_action( 'woocommerce_widget_shopping_cart_before_buttons' ); ?>
            <p class="buttons mini-cart-buttons"><?php do_action( 'woocommerce_widget_shopping_cart_buttons' ); ?></p>
            <?php do_action( 'woocommerce_widget_shopping_cart_after_buttons' ); ?>
        </div>
    </div>
    <?php
}

/** Replace the parent footer so the insurance slot is located above subtotal. */
function vf_replace_mini_cart_footer() {
    remove_action( 'et_woocomerce_mini_cart_footer', 'et_woocomerce_mini_cart_footer', 10 );
    add_action( 'et_woocomerce_mini_cart_footer', 'vf_mini_cart_footer_with_insurance', 10 );
}
add_action( 'wp', 'vf_replace_mini_cart_footer', 1 );

/**
 * Show the customer thank-you URL, including the real order key, on the admin order screen.
 */
/**
 * XStore lazy-load replaces product-grid image src with a placeholder and
 * restores it from data-src only after its IntersectionObserver runs. On
 * mobile that observer often waits for the first touch, so the first four
 * Elementor product images are printed with their real file instead.
 */
function vf_eager_product_grid_images_enabled() {
	return is_front_page() || is_home() || is_shop() || is_product_taxonomy() || is_post_type_archive( 'product' );
}

function vf_restore_product_image_src( $img ) {
	if ( preg_match( '/\bdata-src=(["\'])([^"\']+)\1/i', $img, $source ) ) {
		$img = preg_replace( '/\bsrc=(["\'])[^"\']*\1/i', 'src="' . esc_url( $source[2] ) . '"', $img, 1 );
		$img = preg_replace( '/\sdata-src=(["\'])[^"\']*\1/i', '', $img );
	}

	if ( preg_match( '/\bdata-srcset=(["\'])([^"\']+)\1/i', $img, $source ) ) {
		if ( preg_match( '/\bsrcset=/i', $img ) ) {
			$img = preg_replace( '/\bsrcset=(["\'])[^"\']*\1/i', 'srcset="' . esc_attr( $source[2] ) . '"', $img, 1 );
		} else {
			$img = preg_replace( '/<img\b/i', '<img srcset="' . esc_attr( $source[2] ) . '"', $img, 1 );
		}
		$img = preg_replace( '/\sdata-srcset=(["\'])[^"\']*\1/i', '', $img );
	}

	$img = preg_replace( '/\bloading=(["\'])lazy\1/i', 'loading="eager"', $img );

	if ( preg_match( '/\bclass=(["\'])([^"\']*)\1/i', $img, $class ) ) {
		$classes = preg_replace( '/\b(lazyload|lazyload-lqip|lazyload-simple|et-lazyload-fadeIn)\b/', '', $class[2] );
		$classes = trim( preg_replace( '/\s+/', ' ', $classes . ' no-lazy' ) );
		$img     = preg_replace( '/\bclass=(["\'])[^"\']*\1/i', 'class="' . esc_attr( $classes ) . '"', $img, 1 );
	} else {
		$img = preg_replace( '/<img\b/i', '<img class="no-lazy"', $img, 1 );
	}

	return $img;
}

function vf_eager_product_grid_html( $html ) {
	static $remaining = 4;

	if ( $remaining < 1 || ! is_string( $html ) || false === strpos( $html, 'etheme-product-grid-image' ) ) {
		return $html;
	}

	return preg_replace_callback(
		'/(<div\b[^>]*\bclass=(["\'])[^"\']*\betheme-product-grid-image\b[^"\']*\2[^>]*>\s*<a\b[^>]*>\s*)(<img\b[^>]*>)/i',
		function ( $matches ) use ( &$remaining ) {
			if ( $remaining < 1 ) {
				return $matches[0];
			}

			$image = $matches[3];
			if ( ! preg_match( '/\bdata-src=/i', $image ) && ! preg_match( '/\blazyload\b/', $image ) && ! preg_match( '/\bloading=(["\'])lazy\1/i', $image ) ) {
				return $matches[0];
			}

			$remaining--;
			return $matches[1] . vf_restore_product_image_src( $image );
		},
		$html
	);
}

function vf_eager_elementor_product_grid( $content, $widget ) {
	if ( ! vf_eager_product_grid_images_enabled() || ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) ) {
		return $content;
	}

	if ( 'etheme_product_grid' !== $widget->get_name() ) {
		return $content;
	}

	return vf_eager_product_grid_html( $content );
}
add_filter( 'elementor/widget/render_content', 'vf_eager_elementor_product_grid', 20, 2 );

function vf_eager_product_grid_content( $content ) {
	if ( ! vf_eager_product_grid_images_enabled() ) {
		return $content;
	}

	return vf_eager_product_grid_html( $content );
}
add_filter( 'the_content', 'vf_eager_product_grid_content', 10001 );

add_action( 'woocommerce_admin_order_data_after_order_details', 'vf_admin_order_received_url' );
function vf_admin_order_received_url( $order ) {
    if ( ! $order instanceof WC_Order || ! current_user_can( 'edit_shop_orders' ) ) {
        return;
    }

    $url = $order->get_checkout_order_received_url();
    ?>
    <p class="form-field form-field-wide">
        <label><?php esc_html_e( 'Customer thank you page', 'xstore-child' ); ?></label>
        <a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $url ); ?></a>
    </p>
    <?php
}
