<?php
/**
 * General Theme Actions and Filters
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Enqueue Parent and Child Theme Styles
 */
add_action( 'wp_enqueue_scripts', 'theme_enqueue_styles', 1001 );
function theme_enqueue_styles() {
    if ( function_exists( 'etheme_child_styles' ) ) {
        etheme_child_styles();
    }
}

/**
 * Enqueue Child Theme Custom Assets (main.css and main.js)
 */
add_action( 'wp_enqueue_scripts', 'enqueue_child_theme_assets' );
function enqueue_child_theme_assets() {
    // Enqueue CSS
    wp_enqueue_style(
        'main-style',
        get_stylesheet_directory_uri() . '/assets/css/main.css',
        array(),
        filemtime( get_stylesheet_directory() . '/assets/css/main.css' ),
        'all'
    );

    // Enqueue JS
    wp_enqueue_script(
        'main-script',
        get_stylesheet_directory_uri() . '/assets/js/main.js',
        array( 'jquery' ),
        filemtime( get_stylesheet_directory() . '/assets/js/main.js' ),
        true
    );

    wp_localize_script( 'main-script', 'order_dates_ajax', array(
        'ajax_url' => admin_url( 'admin-ajax.php' ),
    ) );
}

/**
 * Performance: Dequeue unnecessary scripts on front page
 */
add_action( 'wp_print_scripts', 'bt_remove_wp_enqueue_scripts', 999 );
function bt_remove_wp_enqueue_scripts() {
    if ( is_front_page() ) {
        $scripts = array(
            'wc-add-to-cart',
            'wc-address-autocomplete-common',
            'wc-address-autocomplete',
            'comment-reply',
            'et_swiper-slider',
            'jetpack-carousel',
        );

        foreach ( $scripts as $script ) {
            wp_dequeue_script( $script );
            wp_deregister_script( $script );
        }
    }
}

/**
 * Performance: Dequeue unnecessary styles on front page
 */
add_action( 'wp_print_styles', 'bt_remove_wp_enqueue_styles', 999 );
function bt_remove_wp_enqueue_styles() {
    if ( is_front_page() ) {
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

        foreach ( $styles as $style ) {
            wp_dequeue_style( $style );
        }
    }
}

/**
 * Disable responsive image sizes (srcset)
 */
add_filter( 'wp_calculate_image_srcset', '__return_false' );

/**
 * Disable native lazy loading
 */
add_filter( 'wp_lazy_loading_enabled', '__return_false' );

/**
 * Hook to add tracking code to <head> from ACF Options page field (vf_header_scripts)
 */
add_action( 'wp_head', 'vf_header_tracking_scripts', 1 );
function vf_header_tracking_scripts() {
    if ( ! function_exists( 'get_field' ) ) {
        return;
    }

    $scripts = get_field( 'vf_header_scripts', 'option' );
    if ( ! empty( $scripts ) ) {
        echo "\n" . $scripts . "\n";
    }
}

/**
 * Hide unwanted admin bar menus
 */
add_action( 'admin_head', 'vfs_custom_admin_bar_css' );
add_action( 'wp_head', 'vfs_custom_admin_bar_css' );
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

/**
 * Unregister unused custom post types (testimonials, staticblocks, etheme_slides)
 */
add_action( 'init', 'vf_unregister_unused_post_types', 100 );
function vf_unregister_unused_post_types() {
    $cpts_to_remove = array( 'testimonials', 'staticblocks', 'etheme_slides' );

    foreach ( $cpts_to_remove as $cpt ) {
        if ( post_type_exists( $cpt ) ) {
            unregister_post_type( $cpt );
        }
    }
}

/**
 * Remove "Posts" from admin sidebar menu
 */
add_action( 'admin_menu', 'vf_remove_default_post_menu', 999 );
function vf_remove_default_post_menu() {
    remove_menu_page( 'edit.php' );
}

/**
 * Remove "+ New > Post" from admin bar
 */
add_action( 'admin_bar_menu', 'vf_remove_post_from_admin_bar', 999 );
function vf_remove_post_from_admin_bar( $wp_admin_bar ) {
    $wp_admin_bar->remove_node( 'new-post' );
}
