<?php
namespace VintageFootballElementorWidgets;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ElementorWidgets
 *
 * Custom Elementor Widgets Loader for Vintage Football
 */
class ElementorWidgets {

    private static $_instance = null;

    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function widgets_list() {
        return [
            'vf-product-categories-filters',
            'vf-product-search-filters',
            'vf-hero-slider',
            'vf-breadcrumbs',
        ];
    }

    public function widget_styles() {
        wp_register_style(
            'vf-cat-filters-style',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-product-categories-filters/widget.css',
            [],
            THEME_VERSION
        );

        wp_register_style(
            'vf-hero-slider-style',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-hero-slider/widget.css',
            [],
            THEME_VERSION
        );

        wp_register_style(
            'vf-product-search-filters-style',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-product-search-filters/widget.css',
            [],
            THEME_VERSION
        );

        wp_register_style(
            'vf-breadcrumbs-style',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-breadcrumbs/widget.css',
            [],
            THEME_VERSION
        );
    }

    public function widget_scripts() {
        wp_register_script(
            'vf-cat-filters-script',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-product-categories-filters/widget.js',
            [ 'jquery' ],
            THEME_VERSION,
            true
        );

        wp_localize_script(
            'vf-cat-filters-script',
            'vf_ajax_object',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'vf_filter_nonce' ),
            ]
        );

        wp_register_script(
            'vf-product-search-filters-script',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-product-search-filters/widget.js',
            [ 'jquery' ],
            THEME_VERSION,
            true
        );

        wp_localize_script(
            'vf-product-search-filters-script',
            'vf_ps',
            [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'vf_ps_nonce' ),
            ]
        );

        wp_register_script(
            'vf-hero-slider-script',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-hero-slider/widget.js',
            [ 'jquery' ],
            THEME_VERSION,
            true
        );
    }

    private function include_widgets_files() {
        foreach ( $this->widgets_list() as $widget ) {
            $widget_path = get_stylesheet_directory() . '/elementor/widgets/' . $widget . '/widget.php';
            if ( file_exists( $widget_path ) ) {
                require_once $widget_path;
            }
        }
    }

    public function register_categories( $elements_manager ) {
        $elements_manager->add_category(
            'vintage-football',
            [
                'title' => esc_html__( 'Vintage Football', 'xstore-child' ),
                'icon'  => 'fa fa-futbol-o',
            ]
        );
    }

    public function register_widgets( $widgets_manager = null ) {
        $this->include_widgets_files();

        if ( is_null( $widgets_manager ) && class_exists( '\Elementor\Plugin' ) ) {
            $widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
        }

        if ( ! $widgets_manager ) {
            return;
        }

        // Register VF Product Categories Filters
        if ( class_exists( 'VintageFootballElementorWidgets\Widgets\VFProductCategoriesFilters\Widget_VFProductCategoriesFilters' ) ) {
            $widget_instance = new Widgets\VFProductCategoriesFilters\Widget_VFProductCategoriesFilters();
            if ( method_exists( $widgets_manager, 'register' ) ) {
                $widgets_manager->register( $widget_instance );
            } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                $widgets_manager->register_widget_type( $widget_instance );
            }
        }

        if ( class_exists( 'VintageFootballElementorWidgets\Widgets\VFProductSearchFilters\Widget_VFProductSearchFilters' ) ) {
            $search_instance = new Widgets\VFProductSearchFilters\Widget_VFProductSearchFilters();
            if ( method_exists( $widgets_manager, 'register' ) ) {
                $widgets_manager->register( $search_instance );
            } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                $widgets_manager->register_widget_type( $search_instance );
            }
        }

        // Register VF Hero Slider
        if ( class_exists( 'VintageFootballElementorWidgets\Widgets\VFHeroSlider\Widget_VFHeroSlider' ) ) {
            $slider_instance = new Widgets\VFHeroSlider\Widget_VFHeroSlider();
            if ( method_exists( $widgets_manager, 'register' ) ) {
                $widgets_manager->register( $slider_instance );
            } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                $widgets_manager->register_widget_type( $slider_instance );
            }
        }

        // Register VF Breadcrumbs
        if ( class_exists( 'VintageFootballElementorWidgets\Widgets\VFBreadcrumbs\Widget_VFBreadcrumbs' ) ) {
            $bc_instance = new Widgets\VFBreadcrumbs\Widget_VFBreadcrumbs();
            if ( method_exists( $widgets_manager, 'register' ) ) {
                $widgets_manager->register( $bc_instance );
            } elseif ( method_exists( $widgets_manager, 'register_widget_type' ) ) {
                $widgets_manager->register_widget_type( $bc_instance );
            }
        }
    }

    public function __construct() {
        add_action( 'elementor/frontend/after_register_styles', [ $this, 'widget_styles' ] );
        add_action( 'elementor/frontend/after_register_scripts', [ $this, 'widget_scripts' ] );
        add_action( 'elementor/elements/categories_registered', [ $this, 'register_categories' ] );

        // Support both modern (Elementor 3.5+) and legacy widget registration hooks
        add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );
    }
}

// Include AJAX filter handler
require_once get_stylesheet_directory() . '/elementor/ajax-filter.php';
require_once get_stylesheet_directory() . '/elementor/product-search.php';

ElementorWidgets::instance();
