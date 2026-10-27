<?php
/**
 * XStore Child Theme functions and definitions
 *
 * All custom logic is modularized inside the /inc directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('THEME_VERSION', '2.1.86');
define('INC_DIR', get_stylesheet_directory() . '/inc/');

/**
 * 1. Helper functions & utilities
 */
require_once INC_DIR . 'helpers.php';

/**
 * 2. General theme hooks, enqueues, optimizations, admin tweaks
 */
require_once INC_DIR . 'hooks.php';

/**
 * 3. Custom shortcodes (currency switcher, category descriptions, etc.)
 */
require_once INC_DIR . 'shortcodes.php';

/**
 * 3b. Breadcrumbs (Elementor widget, [vf_breadcrumbs], single product heading)
 */
require_once INC_DIR . 'breadcrumbs.php';

/**
 * 4. WooCommerce customizations (order status, ratings, shipping estimates, size guide, etc.)
 */
require_once INC_DIR . 'woo.php';

/**
 * 5. Admin tools (Regenerate Variations tool)
 */
require_once INC_DIR . 'regenerate-variations.php';

/**
 * 6. Elementor Custom Widgets
 */
if ( file_exists( get_stylesheet_directory() . '/elementor/widgets-load.php' ) ) {
    require_once get_stylesheet_directory() . '/elementor/widgets-load.php';
}

add_action( 'wp_enqueue_scripts', 'vf_home_dequeue_css', 9999 );
add_action( 'wp_print_styles', 'vf_home_dequeue_css', 9999 );
add_action( 'wp_print_footer_scripts', 'vf_home_dequeue_css', 1 );

function vf_home_dequeue_css() {
    if ( ! is_front_page() ) {
        return;
    }

    $handles = array(
        'metorik-css',                      // metorik.css
        'sib-front-css',                    // mailin-front.css (Brevo)
        'wc-ppcp-mini-cart-buttons',        // ppcp-blocks-css-mini-cart-buttons.css
        'fluentform-elementor-widget',      // fluent-forms-elementor-widget.css
        'wmc-flags',                        // flags-64.min.css
        'woocommerce-multi-currency',       // woocommerce-multi-currency.min.css
        'wc-blocks-style',                  // wc-blocks.css
    );

    foreach ( $handles as $handle ) {
        wp_dequeue_style( $handle );
    }
}
