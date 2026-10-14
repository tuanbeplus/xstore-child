<?php
namespace VintageFootballElementorWidgets\Widgets\VFBreadcrumbs;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Background;
use Elementor\Icons_Manager;
use Elementor\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Widget_VFBreadcrumbs extends Widget_Base {

    public function get_name() {
        return 'vf-breadcrumbs';
    }

    public function get_title() {
        return __( 'VF Breadcrumbs', 'xstore-child' );
    }

    public function get_icon() {
        return 'eicon-breadcrumbs';
    }

    public function get_categories() {
        return [ 'vintage-football' ];
    }

    public function get_keywords() {
        return [ 'breadcrumb', 'breadcrumbs', 'navigation', 'back', 'history', 'vintage', 'football', 'woocommerce', 'shop' ];
    }

    public function get_style_depends() {
        return [ 'vf-breadcrumbs-style' ];
    }

    protected function register_controls() {

        // =========================================================================
        // CONTENT SECTION: RETURN BUTTON
        // =========================================================================
        $this->start_controls_section(
            'section_return_button',
            [
                'label' => __( 'Return Button', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_return_button',
            [
                'label'        => __( 'Show Return Button', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'return_button_text',
            [
                'label'       => __( 'Button Text', 'xstore-child' ),
                'type'        => Controls_Manager::TEXT,
                'default'     => __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' ),
                'placeholder' => __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' ),
                'condition'   => [
                    'show_return_button' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'show_return_icon',
            [
                'label'        => __( 'Show Chevron Icon', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'condition'    => [
                    'show_return_button' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // CONTENT SECTION: BREADCRUMBS TRAIL
        // =========================================================================
        $this->start_controls_section(
            'section_breadcrumbs',
            [
                'label' => __( 'Breadcrumbs Trail', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'show_home',
            [
                'label'        => __( 'Show Home', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'home_label',
            [
                'label'     => __( 'Home Label', 'xstore-child' ),
                'type'      => Controls_Manager::TEXT,
                'default'   => __( 'HOME', 'xstore-child' ),
                'condition' => [
                    'show_home' => 'yes',
                ],
            ]
        );

        $this->add_control(
            'include_shop',
            [
                'label'        => __( 'Include Shop Link', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'no',
                'description'  => __( 'Include WooCommerce shop link before product categories.', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'show_current',
            [
                'label'        => __( 'Show Current Item', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            ]
        );

        $this->add_control(
            'delimiter_text',
            [
                'label'   => __( 'Delimiter', 'xstore-child' ),
                'type'    => Controls_Manager::TEXT,
                'default' => '>',
            ]
        );

        $this->add_control(
            'text_transform',
            [
                'label'   => __( 'Text Transform', 'xstore-child' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'uppercase',
                'options' => [
                    'uppercase'  => __( 'Uppercase', 'xstore-child' ),
                    'capitalize' => __( 'Capitalize', 'xstore-child' ),
                    'none'       => __( 'Normal (None)', 'xstore-child' ),
                ],
            ]
        );

        $this->add_control(
            'enable_schema',
            [
                'label'        => __( 'Enable Schema.org (JSON-LD)', 'xstore-child' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => __( 'Yes', 'xstore-child' ),
                'label_off'    => __( 'No', 'xstore-child' ),
                'return_value' => 'yes',
                'default'      => 'yes',
                'description'  => __( 'Output BreadcrumbList structured data for search engine SEO.', 'xstore-child' ),
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE SECTION: GENERAL CONTAINER
        // =========================================================================
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __( 'Container', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_responsive_control(
            'container_padding',
            [
                'label'      => __( 'Padding', 'xstore-child' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => [ 'px', 'em', '%' ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-breadcrumbs-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'container_gap',
            [
                'label'      => __( 'Gap Between Left & Right', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px', 'em' ],
                'range'      => [
                    'px' => [ 'min' => 0, 'max' => 60 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-breadcrumbs-container' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE SECTION: RETURN BUTTON
        // =========================================================================
        $this->start_controls_section(
            'section_style_return_btn',
            [
                'label'     => __( 'Return Button', 'xstore-child' ),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'show_return_button' => 'yes',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'return_btn_typography',
                'selector' => '{{WRAPPER}} .vf-bc-return-btn',
            ]
        );

        $this->start_controls_tabs( 'tabs_return_btn' );

        $this->start_controls_tab(
            'tab_return_btn_normal',
            [
                'label' => __( 'Normal', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'return_btn_color',
            [
                'label'     => __( 'Text Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-btn' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'return_btn_icon_color',
            [
                'label'     => __( 'Icon Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-icon svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_return_btn_hover',
            [
                'label' => __( 'Hover', 'xstore-child' ),
            ]
        );

        $this->add_control(
            'return_btn_hover_color',
            [
                'label'     => __( 'Text Hover Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-btn:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'return_btn_hover_icon_color',
            [
                'label'     => __( 'Icon Hover Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-return-btn:hover .vf-bc-return-icon svg' => 'fill: {{VALUE}}; color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_responsive_control(
            'return_btn_icon_size',
            [
                'label'      => __( 'Icon Size', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 8, 'max' => 24 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-bc-return-icon svg' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ],
                'separator'  => 'before',
            ]
        );

        $this->add_responsive_control(
            'return_btn_icon_gap',
            [
                'label'      => __( 'Icon Gap', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 2, 'max' => 20 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-bc-return-btn' => 'gap: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();

        // =========================================================================
        // STYLE SECTION: BREADCRUMBS
        // =========================================================================
        $this->start_controls_section(
            'section_style_breadcrumbs',
            [
                'label' => __( 'Breadcrumbs', 'xstore-child' ),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'breadcrumbs_typography',
                'selector' => '{{WRAPPER}} .vf-bc-list',
            ]
        );

        $this->add_control(
            'link_color',
            [
                'label'     => __( 'Link Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-link' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'link_hover_color',
            [
                'label'     => __( 'Link Hover Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-link:hover' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'current_color',
            [
                'label'     => __( 'Current Item Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-item.is-current .vf-bc-current-title' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'delimiter_color',
            [
                'label'     => __( 'Delimiter Color', 'xstore-child' ),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .vf-bc-delimiter' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_responsive_control(
            'delimiter_spacing',
            [
                'label'      => __( 'Delimiter Spacing', 'xstore-child' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => [ 'px' ],
                'range'      => [
                    'px' => [ 'min' => 2, 'max' => 24 ],
                ],
                'selectors'  => [
                    '{{WRAPPER}} .vf-bc-delimiter' => 'margin-left: {{SIZE}}{{UNIT}}; margin-right: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Build the breadcrumbs trail array based on current queried object
     *
     * @param array $settings
     * @return array
     */
    public function get_breadcrumbs_trail( $settings ) {
        $trail        = [];
        $show_home    = ( 'yes' === $settings['show_home'] );
        $home_label   = ! empty( $settings['home_label'] ) ? $settings['home_label'] : __( 'HOME', 'xstore-child' );
        $include_shop = ( 'yes' === $settings['include_shop'] );
        $show_current = ( 'yes' === $settings['show_current'] );
        $is_uppercase = ( 'uppercase' === $settings['text_transform'] );

        $format_title = function( $title ) use ( $is_uppercase ) {
            $clean = wp_strip_all_tags( $title );
            return $is_uppercase ? mb_strtoupper( $clean, 'UTF-8' ) : $clean;
        };

        // Add Home item
        if ( $show_home ) {
            $trail[] = [
                'title'      => $format_title( $home_label ),
                'url'        => home_url( '/' ),
                'is_current' => false,
            ];
        }

        $is_editor = class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

        // ---------------------------------------------------------------------
        // 1. Single Product
        // ---------------------------------------------------------------------
        if ( function_exists( 'is_product' ) && is_product() ) {
            $product_id = get_the_ID();

            // Optional Shop page link
            if ( $include_shop && function_exists( 'wc_get_page_id' ) ) {
                $shop_id = wc_get_page_id( 'shop' );
                if ( $shop_id && $shop_id > 0 ) {
                    $trail[] = [
                        'title'      => $format_title( get_the_title( $shop_id ) ),
                        'url'        => get_permalink( $shop_id ),
                        'is_current' => false,
                    ];
                }
            }

            // Consistent category resolution:
            // 1. Yoast SEO primary term
            // 2. Rank Math primary term
            // 3. Deepest product_cat term
            $term = null;
            if ( class_exists( 'WPSEO_Primary_Term' ) ) {
                $wpseo_primary_term = new \WPSEO_Primary_Term( 'product_cat', $product_id );
                $primary_id         = $wpseo_primary_term->get_primary_term();
                if ( $primary_id ) {
                    $term = get_term( $primary_id, 'product_cat' );
                }
            }

            if ( ( ! $term || is_wp_error( $term ) ) && metadata_exists( 'post', $product_id, 'rank_math_primary_product_cat' ) ) {
                $rm_primary_id = get_post_meta( $product_id, 'rank_math_primary_product_cat', true );
                if ( $rm_primary_id ) {
                    $term = get_term( (int) $rm_primary_id, 'product_cat' );
                }
            }

            if ( ! $term || is_wp_error( $term ) ) {
                $terms = get_the_terms( $product_id, 'product_cat' );
                if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
                    $deepest_term = null;
                    $max_depth    = -1;
                    foreach ( $terms as $t ) {
                        $ancestors = get_ancestors( $t->term_id, 'product_cat', 'taxonomy' );
                        $depth     = count( $ancestors );
                        if ( $depth > $max_depth ) {
                            $max_depth    = $depth;
                            $deepest_term = $t;
                        }
                    }
                    $term = $deepest_term;
                }
            }

            // Append category ancestors + category itself
            if ( $term && ! is_wp_error( $term ) ) {
                $ancestors = get_ancestors( $term->term_id, 'product_cat', 'taxonomy' );
                if ( ! empty( $ancestors ) ) {
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, 'product_cat' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                            $trail[] = [
                                'title'      => $format_title( $ancestor->name ),
                                'url'        => get_term_link( $ancestor ),
                                'is_current' => false,
                            ];
                        }
                    }
                }

                $trail[] = [
                    'title'      => $format_title( $term->name ),
                    'url'        => get_term_link( $term ),
                    'is_current' => false,
                ];
            }

            // Current product title
            if ( $show_current ) {
                $trail[] = [
                    'title'      => $format_title( get_the_title( $product_id ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }

            return $trail;
        }

        // ---------------------------------------------------------------------
        // 2. Product Category Archive
        // ---------------------------------------------------------------------
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            $current_term = get_queried_object();

            if ( $include_shop && function_exists( 'wc_get_page_id' ) ) {
                $shop_id = wc_get_page_id( 'shop' );
                if ( $shop_id && $shop_id > 0 ) {
                    $trail[] = [
                        'title'      => $format_title( get_the_title( $shop_id ) ),
                        'url'        => get_permalink( $shop_id ),
                        'is_current' => false,
                    ];
                }
            }

            if ( $current_term && ! is_wp_error( $current_term ) ) {
                $ancestors = get_ancestors( $current_term->term_id, 'product_cat', 'taxonomy' );
                if ( ! empty( $ancestors ) ) {
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, 'product_cat' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                            $trail[] = [
                                'title'      => $format_title( $ancestor->name ),
                                'url'        => get_term_link( $ancestor ),
                                'is_current' => false,
                            ];
                        }
                    }
                }

                if ( $show_current ) {
                    $trail[] = [
                        'title'      => $format_title( $current_term->name ),
                        'url'        => '',
                        'is_current' => true,
                    ];
                }
            }

            return $trail;
        }

        // ---------------------------------------------------------------------
        // 3. WooCommerce Shop Page
        // ---------------------------------------------------------------------
        if ( function_exists( 'is_shop' ) && is_shop() ) {
            if ( $show_current && function_exists( 'woocommerce_page_title' ) ) {
                $trail[] = [
                    'title'      => $format_title( woocommerce_page_title( false ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }
            return $trail;
        }

        // ---------------------------------------------------------------------
        // 4. Product Tag or Other Custom Product Taxonomy
        // ---------------------------------------------------------------------
        if ( ( function_exists( 'is_product_tag' ) && is_product_tag() ) || is_tax() ) {
            $current_term = get_queried_object();
            if ( $current_term && ! is_wp_error( $current_term ) ) {
                $taxonomy = $current_term->taxonomy;
                if ( is_taxonomy_hierarchical( $taxonomy ) ) {
                    $ancestors = get_ancestors( $current_term->term_id, $taxonomy, 'taxonomy' );
                    if ( ! empty( $ancestors ) ) {
                        $ancestors = array_reverse( $ancestors );
                        foreach ( $ancestors as $ancestor_id ) {
                            $ancestor = get_term( $ancestor_id, $taxonomy );
                            if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                                $trail[] = [
                                    'title'      => $format_title( $ancestor->name ),
                                    'url'        => get_term_link( $ancestor ),
                                    'is_current' => false,
                                ];
                            }
                        }
                    }
                }

                if ( $show_current ) {
                    $trail[] = [
                        'title'      => $format_title( $current_term->name ),
                        'url'        => '',
                        'is_current' => true,
                    ];
                }
            }
            return $trail;
        }

        // ---------------------------------------------------------------------
        // 5. Standard WordPress Hierarchical Page
        // ---------------------------------------------------------------------
        if ( is_page() ) {
            $page_id   = get_the_ID();
            $ancestors = get_post_ancestors( $page_id );

            if ( ! empty( $ancestors ) ) {
                $ancestors = array_reverse( $ancestors );
                foreach ( $ancestors as $ancestor_id ) {
                    $trail[] = [
                        'title'      => $format_title( get_the_title( $ancestor_id ) ),
                        'url'        => get_permalink( $ancestor_id ),
                        'is_current' => false,
                    ];
                }
            }

            if ( $show_current ) {
                $trail[] = [
                    'title'      => $format_title( get_the_title( $page_id ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }

            return $trail;
        }

        // ---------------------------------------------------------------------
        // 6. Standard Single Post (Blog or CPT)
        // ---------------------------------------------------------------------
        if ( is_single() ) {
            $post_id    = get_the_ID();
            $categories = get_the_category( $post_id );

            if ( ! empty( $categories ) ) {
                $cat       = $categories[0];
                $ancestors = get_ancestors( $cat->term_id, 'category', 'taxonomy' );
                if ( ! empty( $ancestors ) ) {
                    $ancestors = array_reverse( $ancestors );
                    foreach ( $ancestors as $ancestor_id ) {
                        $ancestor = get_term( $ancestor_id, 'category' );
                        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                            $trail[] = [
                                'title'      => $format_title( $ancestor->name ),
                                'url'        => get_term_link( $ancestor ),
                                'is_current' => false,
                            ];
                        }
                    }
                }

                $trail[] = [
                    'title'      => $format_title( $cat->name ),
                    'url'        => get_term_link( $cat ),
                    'is_current' => false,
                ];
            }

            if ( $show_current ) {
                $trail[] = [
                    'title'      => $format_title( get_the_title( $post_id ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }

            return $trail;
        }

        // ---------------------------------------------------------------------
        // 7. Standard Category / Tag Archive
        // ---------------------------------------------------------------------
        if ( is_category() || is_tag() ) {
            $current_term = get_queried_object();
            if ( $current_term && ! is_wp_error( $current_term ) ) {
                if ( is_category() ) {
                    $ancestors = get_ancestors( $current_term->term_id, 'category', 'taxonomy' );
                    if ( ! empty( $ancestors ) ) {
                        $ancestors = array_reverse( $ancestors );
                        foreach ( $ancestors as $ancestor_id ) {
                            $ancestor = get_term( $ancestor_id, 'category' );
                            if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                                $trail[] = [
                                    'title'      => $format_title( $ancestor->name ),
                                    'url'        => get_term_link( $ancestor ),
                                    'is_current' => false,
                                ];
                            }
                        }
                    }
                }

                if ( $show_current ) {
                    $trail[] = [
                        'title'      => $format_title( $current_term->name ),
                        'url'        => '',
                        'is_current' => true,
                    ];
                }
            }
            return $trail;
        }

        // ---------------------------------------------------------------------
        // 8. Blog Posts Index
        // ---------------------------------------------------------------------
        if ( is_home() ) {
            $page_for_posts = get_option( 'page_for_posts' );
            $blog_title     = $page_for_posts ? get_the_title( $page_for_posts ) : __( 'BLOG', 'xstore-child' );
            if ( $show_current ) {
                $trail[] = [
                    'title'      => $format_title( $blog_title ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }
            return $trail;
        }

        // ---------------------------------------------------------------------
        // 9. Search Results
        // ---------------------------------------------------------------------
        if ( is_search() ) {
            if ( $show_current ) {
                $trail[] = [
                    'title'      => $format_title( sprintf( __( 'Search: "%s"', 'xstore-child' ), get_search_query() ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }
            return $trail;
        }

        // ---------------------------------------------------------------------
        // 10. 404 Page
        // ---------------------------------------------------------------------
        if ( is_404() ) {
            if ( $show_current ) {
                $trail[] = [
                    'title'      => $format_title( __( '404 NOT FOUND', 'xstore-child' ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }
            return $trail;
        }

        // ---------------------------------------------------------------------
        // 11. Elementor Editor Sample Preview (Live Preview in Elementor Canvas)
        // ---------------------------------------------------------------------
        if ( $is_editor || count( $trail ) <= 1 ) {
            $sample_trail = [];
            if ( $show_home ) {
                $sample_trail[] = [
                    'title'      => $format_title( $home_label ),
                    'url'        => home_url( '/' ),
                    'is_current' => false,
                ];
            }
            if ( $include_shop ) {
                $sample_trail[] = [
                    'title'      => $format_title( __( 'SHOP', 'xstore-child' ) ),
                    'url'        => '#',
                    'is_current' => false,
                ];
            }
            $sample_trail[] = [
                'title'      => $format_title( __( 'CLUBS', 'xstore-child' ) ),
                'url'        => '#',
                'is_current' => false,
            ];
            if ( $show_current ) {
                $sample_trail[] = [
                    'title'      => $format_title( __( 'BAYERN', 'xstore-child' ) ),
                    'url'        => '',
                    'is_current' => true,
                ];
            }
            return $sample_trail;
        }

        return $trail;
    }

    protected function render() {
        $settings           = $this->get_settings_for_display();
        $show_return_button = ( 'yes' === $settings['show_return_button'] );
        $return_text        = ! empty( $settings['return_button_text'] ) ? $settings['return_button_text'] : __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' );
        $show_return_icon   = ( 'yes' === $settings['show_return_icon'] );
        $delimiter          = ! empty( $settings['delimiter_text'] ) ? $settings['delimiter_text'] : '>';

        $trail = $this->get_breadcrumbs_trail( $settings );

        if ( empty( $trail ) && ! $show_return_button ) {
            return;
        }

        // Schema.org structured data (JSON-LD)
        if ( 'yes' === $settings['enable_schema'] && ! empty( $trail ) ) {
            $schema_elements = [];
            $pos = 1;
            foreach ( $trail as $t_item ) {
                $schema_elements[] = [
                    '@type'    => 'ListItem',
                    'position' => $pos,
                    'name'     => $t_item['title'],
                    'item'     => ! empty( $t_item['url'] ) ? esc_url( $t_item['url'] ) : esc_url( home_url( add_query_arg( null, null ) ) ),
                ];
                $pos++;
            }
            $schema_data = [
                '@context'        => 'https://schema.org',
                '@type'           => 'BreadcrumbList',
                'itemListElement' => $schema_elements,
            ];
            echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
        }
        ?>

        <div class="vf-breadcrumbs-container">
            <?php if ( ! empty( $trail ) ) : ?>
                <div class="vf-breadcrumbs-left">
                    <nav class="vf-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'xstore-child' ); ?>">
                        <ol class="vf-bc-list" itemscope itemtype="https://schema.org/BreadcrumbList">
                            <?php
                            $total_items = count( $trail );
                            $i = 0;
                            foreach ( $trail as $crumb ) :
                                $i++;
                                $is_last = ( $i === $total_items ) || ! empty( $crumb['is_current'] );
                                ?>
                                <li class="vf-bc-item <?php echo $is_last ? 'is-current' : ''; ?>" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                                    <?php if ( ! $is_last && ! empty( $crumb['url'] ) ) : ?>
                                        <a href="<?php echo esc_url( $crumb['url'] ); ?>" class="vf-bc-link" itemprop="item">
                                            <span itemprop="name"><?php echo esc_html( $crumb['title'] ); ?></span>
                                        </a>
                                    <?php else : ?>
                                        <span class="vf-bc-current-title" itemprop="name" aria-current="page"><?php echo esc_html( $crumb['title'] ); ?></span>
                                    <?php endif; ?>
                                    <meta itemprop="position" content="<?php echo (int) $i; ?>" />
                                </li>

                                <?php if ( ! $is_last ) : ?>
                                    <li class="vf-bc-delimiter" aria-hidden="true">
                                        <?php echo esc_html( $delimiter ); ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ol>
                    </nav>
                </div>
            <?php endif; ?>

            <?php if ( $show_return_button ) : ?>
                <div class="vf-breadcrumbs-right">
                    <a href="javascript:history.go(-1)" class="vf-bc-return-btn" role="button" aria-label="<?php echo esc_attr( $return_text ); ?>" onclick="if(window.history.length > 1){window.history.go(-1);}else{window.location.href='<?php echo esc_url( home_url( '/' ) ); ?>';} return false;">
                        <?php if ( $show_return_icon ) : ?>
                            <span class="vf-bc-return-icon" aria-hidden="true">
                                <svg fill="currentColor" viewBox="-4.5 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg">
                                    <path d="M1.6 17.262a1.03 1.03 0 0 1-.728-1.757l6.073-6.073L.872 3.36a1.03 1.03 0 0 1 1.455-1.455l6.8 6.8a1.03 1.03 0 0 1 0 1.456l-6.8 6.8a1.025 1.025 0 0 1-.727.302z"/>
                                </svg>
                            </span>
                        <?php endif; ?>
                        <span class="vf-bc-return-text"><?php echo esc_html( $return_text ); ?></span>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <?php
    }

    protected function content_template() {
        // Server-side rendered in PHP
    }
}

