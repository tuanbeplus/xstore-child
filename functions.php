<?php
/**
 * XStore Child Theme functions and definitions
 *
 * All custom logic is modularized inside the /inc directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Reset OPcache if available during development/updates
if ( function_exists( 'opcache_reset' ) ) {
    opcache_reset();
}

$inc_dir = get_stylesheet_directory() . '/inc/';

/**
 * 1. Helper functions & utilities
 */
require_once $inc_dir . 'helpers.php';

/**
 * 2. General theme hooks, enqueues, optimizations, admin tweaks
 */
require_once $inc_dir . 'hooks.php';

/**
 * 3. Custom shortcodes (currency switcher, category descriptions, etc.)
 */
require_once $inc_dir . 'shortcodes.php';

/**
 * 4. WooCommerce customizations (order status, ratings, shipping estimates, size guide, etc.)
 */
require_once $inc_dir . 'woo.php';

/**
 * 5. Admin tools (Regenerate Variations tool)
 */
require_once $inc_dir . 'regenerate-variations.php';

/**
 * 6. Elementor Custom Widgets
 */
if ( file_exists( get_stylesheet_directory() . '/elementor/widgets-load.php' ) ) {
    require_once get_stylesheet_directory() . '/elementor/widgets-load.php';
}
