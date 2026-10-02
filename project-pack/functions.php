<?php
// Hook the function to enqueue the custom CSS file for the project pack
add_action('wp_enqueue_scripts', 'b_enqueue_project_pack_css');

/**
 * Enqueue the custom project-pack.css file for the theme.
 * Uses filemtime for cache busting.
 */
function b_enqueue_project_pack_css() {
    wp_enqueue_style(
        'b-project-pack-css',
        get_stylesheet_directory_uri() . '/project-pack/assets/project-pack.css',
        array(),
        filemtime(get_stylesheet_directory() . '/project-pack/assets/project-pack.css')
    );

}
//de enqueue scripts
add_action('wp_print_scripts','bt_remove_wp_enqueue_scripts',999);
function bt_remove_wp_enqueue_scripts(){
	$scripts = array();
	if(is_front_page()){
    $scripts = array(
      'wc-add-to-cart',
      'wc-address-autocomplete-common',
      'wc-address-autocomplete',
      'comment-reply',
      'et_swiper-slider',
      'jetpack-carousel',
  	);
  }
	foreach ($scripts as $script) {
		wp_dequeue_script($script);
		wp_deregister_script($script);
	}
}
//de enqueue styles
add_action('wp_print_styles','bt_remove_wp_enqueue_styles',999);
function bt_remove_wp_enqueue_styles(){
	$styles = array();
    if(is_front_page()){
        $styles = array(
            'woocommerce-smart-coupons-available-coupons-block',
            'woocommerce-smart-coupons-send-coupon-form-block',
            'woocommerce-smart-coupons-action-tab-frontend',
            'user-registration-general',
            'wc-address-autocomplete',
            'wcap_countdown_timer',
            'etheme-swatches-style',
            'brands-styles',
            'e-swiper',
            'jetpack-carousel-swiper-css',
            'jetpack-carousel',
            'jetpack_likes',
            'ppom-main',
        );
  	}
	foreach ($styles as $style) {
		wp_dequeue_style($style);
	}
}

/**
 * Highlight only the coupon code text in cart totals.
 *
 * @param string $label       Coupon label HTML.
 * @param object $coupon      WooCommerce coupon object.
 * @return string
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

add_action( 'woocommerce_thankyou', 'vf_custom_meta_pixel_purchase', 10, 1 );
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

// Regenerate Variations admin tool (migrated from WPCode).
require_once get_stylesheet_directory() . '/project-pack/regenerate-variations.php';