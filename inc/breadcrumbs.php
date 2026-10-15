<?php
/**
 * VF Breadcrumbs — shared trail, markup, shortcode, and single product heading.
 *
 * Used by the Elementor widget, the [vf_breadcrumbs] shortcode, and the
 * single product page heading hook.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Default breadcrumb settings. Values match the Elementor widget controls.
 *
 * @return array
 */
function vf_get_breadcrumbs_default_settings() {
    return array(
        'show_return_button' => 'yes',
        'return_button_text' => __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' ),
        'show_return_icon'   => 'yes',
        'show_home'          => 'yes',
        'home_label'         => __( 'HOME', 'xstore-child' ),
        'include_shop'       => 'no',
        'show_current'       => 'yes',
        'text_transform'     => 'uppercase',
        'enable_schema'      => 'yes',
        'preview_fallback'   => 'no',
        'context'            => '',
    );
}

/**
 * Register breadcrumb widget stylesheet (once per request).
 */
function vf_breadcrumbs_register_style() {
    if ( wp_style_is( 'vf-breadcrumbs-style', 'registered' ) ) {
        return;
    }

    wp_register_style(
        'vf-breadcrumbs-style',
        get_stylesheet_directory_uri() . '/elementor/widgets/vf-breadcrumbs/widget.css',
        array(),
        defined( 'THEME_VERSION' ) ? THEME_VERSION : null
    );
}

/**
 * Cache key for the breadcrumb trail (per request).
 *
 * @param array $settings
 * @return string
 */
function vf_breadcrumbs_trail_cache_key( $settings ) {
    $parts = array(
        'show_home'        => $settings['show_home'],
        'include_shop'     => $settings['include_shop'],
        'show_current'     => $settings['show_current'],
        'text_transform'   => $settings['text_transform'],
        'preview_fallback' => $settings['preview_fallback'],
    );

    if ( is_singular() ) {
        $parts['singular_id'] = get_queried_object_id();
    } elseif ( is_category() || is_tag() || is_tax() ) {
        $term = get_queried_object();
        $parts['term_id'] = ( $term && isset( $term->term_id ) ) ? (int) $term->term_id : 0;
        $parts['taxonomy'] = ( $term && isset( $term->taxonomy ) ) ? $term->taxonomy : '';
    } elseif ( is_search() ) {
        $parts['search'] = get_search_query( false );
    } elseif ( is_home() ) {
        $parts['blog_home'] = 1;
    } elseif ( is_404() ) {
        $parts['not_found'] = 1;
    }

    return md5( wp_json_encode( $parts ) );
}

/**
 * Primary product category for breadcrumbs (Yoast, Rank Math, or deepest term).
 *
 * @param int $product_id
 * @return WP_Term|null
 */
function vf_breadcrumbs_get_primary_product_cat( $product_id ) {
    $term = null;

    if ( class_exists( 'WPSEO_Primary_Term' ) ) {
        $wpseo_primary = new \WPSEO_Primary_Term( 'product_cat', $product_id );
        $primary_id    = $wpseo_primary->get_primary_term();
        if ( $primary_id ) {
            $term = get_term( (int) $primary_id, 'product_cat' );
        }
    }

    if ( ! $term || is_wp_error( $term ) ) {
        $rm_primary_id = (int) get_post_meta( $product_id, 'rank_math_primary_product_cat', true );
        if ( $rm_primary_id > 0 ) {
            $term = get_term( $rm_primary_id, 'product_cat' );
        }
    }

    if ( ! $term || is_wp_error( $term ) ) {
        $terms = get_the_terms( $product_id, 'product_cat' );
        if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
            $deepest_term = null;
            $max_depth    = -1;
            foreach ( $terms as $t ) {
                $depth = count( get_ancestors( $t->term_id, 'product_cat', 'taxonomy' ) );
                if ( $depth > $max_depth ) {
                    $max_depth    = $depth;
                    $deepest_term = $t;
                }
            }
            $term = $deepest_term;
        }
    }

    return ( $term && ! is_wp_error( $term ) ) ? $term : null;
}

/**
 * Append ancestor terms and optional current term to the trail.
 *
 * @param array         $trail
 * @param WP_Term       $term
 * @param string        $taxonomy
 * @param callable      $format_title
 * @param bool          $include_term
 * @param bool          $term_is_current
 */
function vf_breadcrumbs_append_term_chain( array &$trail, $term, $taxonomy, $format_title, $include_term = true, $term_is_current = false ) {
    if ( ! $term || is_wp_error( $term ) ) {
        return;
    }

    $ancestors = get_ancestors( $term->term_id, $taxonomy, 'taxonomy' );
    if ( ! empty( $ancestors ) ) {
        foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
            $ancestor = get_term( (int) $ancestor_id, $taxonomy );
            if ( $ancestor && ! is_wp_error( $ancestor ) ) {
                $trail[] = array(
                    'title'      => $format_title( $ancestor->name ),
                    'url'        => get_term_link( $ancestor ),
                    'is_current' => false,
                );
            }
        }
    }

    if ( $include_term ) {
        $trail[] = array(
            'title'      => $format_title( $term->name ),
            'url'        => $term_is_current ? '' : get_term_link( $term ),
            'is_current' => $term_is_current,
        );
    }
}

/**
 * Whether the current view should load breadcrumb CSS in the head.
 *
 * @return bool
 */
function vf_breadcrumbs_page_needs_styles() {
    if ( function_exists( 'is_product' ) && is_product() ) {
        return true;
    }

    if ( ! is_singular() ) {
        return false;
    }

    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return false;
    }

    $content = get_post_field( 'post_content', $post_id );
    if ( is_string( $content ) && '' !== $content && has_shortcode( $content, 'vf_breadcrumbs' ) ) {
        return true;
    }

    if ( 'builder' !== get_post_meta( $post_id, '_elementor_edit_mode', true ) ) {
        return false;
    }

    $elementor_data = get_post_meta( $post_id, '_elementor_data', true );
    return is_string( $elementor_data ) && false !== strpos( $elementor_data, 'vf-breadcrumbs' );
}

/**
 * Build the breadcrumbs trail for the current request (cached per request).
 *
 * @param array $settings
 * @return array
 */
function vf_get_breadcrumbs_trail( $settings ) {
    static $cache = array();

    $settings = wp_parse_args( $settings, vf_get_breadcrumbs_default_settings() );
    $cache_key = vf_breadcrumbs_trail_cache_key( $settings );

    if ( isset( $cache[ $cache_key ] ) ) {
        return $cache[ $cache_key ];
    }

    $cache[ $cache_key ] = vf_build_breadcrumbs_trail( $settings );

    return $cache[ $cache_key ];
}

/**
 * Build the breadcrumbs trail (uncached implementation).
 *
 * @param array $settings
 * @return array
 */
function vf_build_breadcrumbs_trail( $settings ) {
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

    $preview   = ( 'yes' === $settings['preview_fallback'] );
    $is_editor = $preview && class_exists( '\Elementor\Plugin' ) && isset( \Elementor\Plugin::$instance->editor ) && \Elementor\Plugin::$instance->editor->is_edit_mode();

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

        $term = vf_breadcrumbs_get_primary_product_cat( $product_id );
        if ( $term ) {
            vf_breadcrumbs_append_term_chain( $trail, $term, 'product_cat', $format_title, true, false );
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
            vf_breadcrumbs_append_term_chain( $trail, $current_term, 'product_cat', $format_title, true, $show_current );
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
        if ( $current_term && ! is_wp_error( $current_term ) && is_taxonomy_hierarchical( $current_term->taxonomy ) ) {
            vf_breadcrumbs_append_term_chain( $trail, $current_term, $current_term->taxonomy, $format_title, true, $show_current );
        } elseif ( $current_term && ! is_wp_error( $current_term ) && $show_current ) {
            $trail[] = array(
                'title'      => $format_title( $current_term->name ),
                'url'        => '',
                'is_current' => true,
            );
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
                vf_breadcrumbs_append_term_chain( $trail, $current_term, 'category', $format_title, true, $show_current );
            } elseif ( $show_current ) {
                $trail[] = array(
                    'title'      => $format_title( $current_term->name ),
                    'url'        => '',
                    'is_current' => true,
                );
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
    if ( $preview && ( $is_editor || count( $trail ) <= 1 ) ) {
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

/**
 * Enqueue breadcrumb CSS when this view uses breadcrumbs (shortcode, heading, or widget).
 */
function vf_breadcrumbs_ensure_styles() {
    vf_breadcrumbs_register_style();

    if ( ! wp_style_is( 'vf-breadcrumbs-style', 'enqueued' ) ) {
        wp_enqueue_style( 'vf-breadcrumbs-style' );
    }
}

/**
 * Register and enqueue breadcrumb CSS in the head when needed.
 */
function vf_breadcrumbs_register_assets() {
    if ( ! vf_breadcrumbs_page_needs_styles() ) {
        return;
    }

    vf_breadcrumbs_register_style();
    wp_enqueue_style( 'vf-breadcrumbs-style' );
}
add_action( 'wp_enqueue_scripts', 'vf_breadcrumbs_register_assets', 20 );

/**
 * Render the breadcrumb bar.
 *
 * @param array $settings
 * @param bool  $echo
 * @return string
 */
function vf_render_breadcrumbs( $settings = array(), $echo = true ) {
    $settings = apply_filters( 'vf_breadcrumbs_settings', wp_parse_args( $settings, vf_get_breadcrumbs_default_settings() ) );

    vf_breadcrumbs_ensure_styles();

    $show_return_button = ( 'yes' === $settings['show_return_button'] );
    $return_text        = ! empty( $settings['return_button_text'] ) ? $settings['return_button_text'] : __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' );
    $show_return_icon   = ( 'yes' === $settings['show_return_icon'] );
    $trail              = vf_get_breadcrumbs_trail( $settings );
    $context_class      = ( 'heading' === $settings['context'] ) ? ' is-heading' : '';

    if ( empty( $trail ) && ! $show_return_button ) {
        return '';
    }

    ob_start();

    $use_json_ld = ( 'yes' === $settings['enable_schema'] && ! empty( $trail ) );

    if ( $use_json_ld ) {
        $schema_elements = array();
        $pos             = 1;
        $current_page_url = esc_url( home_url( '/' ) );
        if ( is_singular() ) {
            $current_page_url = esc_url( get_permalink() );
        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term ) {
                $term_link = get_term_link( $term );
                if ( ! is_wp_error( $term_link ) ) {
                    $current_page_url = esc_url( $term_link );
                }
            }
        } elseif ( is_search() ) {
            $current_page_url = esc_url( get_search_link() );
        }

        foreach ( $trail as $t_item ) {
            $schema_elements[] = array(
                '@type'    => 'ListItem',
                'position' => $pos,
                'name'     => $t_item['title'],
                'item'     => ! empty( $t_item['url'] ) ? esc_url( $t_item['url'] ) : $current_page_url,
            );
            $pos++;
        }
        $schema_data = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $schema_elements,
        );
        echo '<script type="application/ld+json">' . wp_json_encode( $schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
    }
    ?>
    <div class="vf-breadcrumbs-container<?php echo esc_attr( $context_class ); ?>">
        <?php if ( ! empty( $trail ) ) : ?>
            <div class="vf-breadcrumbs-left">
                <nav class="vf-breadcrumbs" aria-label="<?php esc_attr_e( 'Breadcrumb', 'xstore-child' ); ?>">
                    <ol class="vf-bc-list">
                        <?php
                        $total_items = count( $trail );
                        $i           = 0;
                        foreach ( $trail as $crumb ) :
                            $i++;
                            $is_last = ( $i === $total_items ) || ! empty( $crumb['is_current'] );
                            ?>
                                <li class="vf-bc-item <?php echo $is_last ? 'is-current' : ''; ?>">
                                    <?php if ( ! $is_last && ! empty( $crumb['url'] ) ) : ?>
                                        <a href="<?php echo esc_url( $crumb['url'] ); ?>" class="vf-bc-link">
                                            <?php echo esc_html( $crumb['title'] ); ?>
                                        </a>
                                    <?php else : ?>
                                        <span class="vf-bc-current-title" aria-current="page"><?php echo esc_html( $crumb['title'] ); ?></span>
                                    <?php endif; ?>
                                </li>
                            <?php if ( ! $is_last ) : ?>
                                <li class="vf-bc-delimiter" aria-hidden="true">
                                    <svg fill="currentColor" viewBox="-4.5 0 19 19" xmlns="http://www.w3.org/2000/svg" class="cf-icon-svg"><path d="M1.6 17.262a1.03 1.03 0 0 1-.728-1.757l6.073-6.073L.872 3.36a1.03 1.03 0 0 1 1.455-1.455l6.8 6.8a1.03 1.03 0 0 1 0 1.456l-6.8 6.8a1.025 1.025 0 0 1-.727.302z"/></svg>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ol>
                </nav>
            </div>
        <?php endif; ?>

        <?php if ( $show_return_button ) : ?>
            <div class="vf-breadcrumbs-right">
                <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="vf-bc-return-btn" role="button" aria-label="<?php echo esc_attr( $return_text ); ?>" onclick="if(window.history.length > 1){window.history.back();}else{window.location.href='<?php echo esc_url( home_url( '/' ) ); ?>';} return false;">
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

    $html = ob_get_clean();

    if ( $echo ) {
        echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        return '';
    }

    return $html;
}

/**
 * Shortcode: [vf_breadcrumbs]
 *
 * Attributes:
 * show_return, return_text, show_return_icon, show_home, home_label,
 * include_shop, show_current, text_transform, enable_schema
 *
 * @param array $atts
 * @return string
 */
function vf_breadcrumbs_shortcode( $atts = array() ) {
    $atts = shortcode_atts(
        array(
            'show_return'      => 'yes',
            'return_text'      => __( 'RETURN TO PREVIOUS PAGE', 'xstore-child' ),
            'show_return_icon' => 'yes',
            'show_home'        => 'yes',
            'home_label'       => __( 'HOME', 'xstore-child' ),
            'include_shop'     => 'no',
            'show_current'     => 'yes',
            'text_transform'   => 'uppercase',
            'enable_schema'    => 'yes',
        ),
        $atts,
        'vf_breadcrumbs'
    );

    $settings = array(
        'show_return_button' => $atts['show_return'],
        'return_button_text' => $atts['return_text'],
        'show_return_icon'   => $atts['show_return_icon'],
        'show_home'          => $atts['show_home'],
        'home_label'         => $atts['home_label'],
        'include_shop'       => $atts['include_shop'],
        'show_current'       => $atts['show_current'],
        'text_transform'     => $atts['text_transform'],
        'enable_schema'      => $atts['enable_schema'],
        'preview_fallback'   => 'no',
    );

    return vf_render_breadcrumbs( $settings, false );
}
add_shortcode( 'vf_breadcrumbs', 'vf_breadcrumbs_shortcode' );

/**
 * Drop native WooCommerce crumbs on single products so the heading shows VF breadcrumbs only.
 *
 * @param array $crumbs
 * @return array
 */
function vf_clear_single_product_breadcrumb( $crumbs ) {
    if ( function_exists( 'is_product' ) && is_product() && ! wp_doing_ajax() ) {
        return array();
    }
    return $crumbs;
}

/**
 * Hide XStore's own "back" control on single products. VF breadcrumbs include the return link.
 *
 * @param mixed $show
 * @return mixed
 */
function vf_hide_native_product_return( $show ) {
    if ( function_exists( 'is_product' ) && is_product() && ! wp_doing_ajax() ) {
        return false;
    }
    return $show;
}

/**
 * Print VF breadcrumbs inside the single product page heading.
 */
function vf_single_product_heading_breadcrumbs() {
    static $done = false;
    if ( $done ) {
        return;
    }
    $done = true;

    $wrap = ! get_query_var( 'et_breadcrumbs', false );

    if ( $wrap ) {
        echo '<div class="page-heading vf-breadcrumbs-heading"><div class="container"><div class="row"><div class="col-md-12">';
    }

    $settings                 = vf_get_breadcrumbs_default_settings();
    $settings['context']      = 'heading';
    $settings['show_current'] = 'no';
    vf_render_breadcrumbs( $settings );

    if ( $wrap ) {
        echo '</div></div></div></div>';
    }
}

/**
 * Attach breadcrumbs to the single product page heading after XStore sets its query vars.
 */
function vf_setup_single_product_breadcrumbs() {
    if ( ! function_exists( 'is_product' ) || ! is_product() || wp_doing_ajax() ) {
        return;
    }

    if ( ! apply_filters( 'vf_show_single_product_breadcrumbs', true ) ) {
        return;
    }

    if ( get_query_var( 'et_breadcrumbs', false ) ) {
        add_action( 'etheme_before_breadcrumbs', 'vf_single_product_heading_breadcrumbs', 5 );
        add_filter( 'woocommerce_get_breadcrumb', 'vf_clear_single_product_breadcrumb', 99 );
        add_filter( 'return_to_previous', 'vf_hide_native_product_return' );
    } else {
        add_action( 'woocommerce_before_main_content', 'vf_single_product_heading_breadcrumbs', 15 );
    }

    remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
    add_action( 'woocommerce_single_product_summary', 'vf_single_product_title', 5 );
}

/**
 * Product title in the summary (XStore may hide it when the name is in theme breadcrumbs).
 */
function vf_single_product_title() {
    if ( ! function_exists( 'is_product' ) || ! is_product() ) {
        return;
    }

    echo '<h1 class="product_title entry-title">' . esc_html( get_the_title() ) . '</h1>';
}

add_action( 'wp', 'vf_setup_single_product_breadcrumbs', 20 );
