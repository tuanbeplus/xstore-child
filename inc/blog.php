<?php
/**
 * Blog helpers: assets, reading time, related posts, related products.
 *
 * Used by single.php (post type "post").
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Load blog assets on blog views only.
 */
function vf_blog_enqueue_assets() {
    $is_single = is_singular( 'post' );
    $is_blog   = is_home();

    if ( ! $is_single && ! $is_blog ) {
        return;
    }

    $version = defined( 'THEME_VERSION' ) ? THEME_VERSION : null;

    wp_enqueue_style(
        'vf-blog-style',
        get_stylesheet_directory_uri() . ( $is_single ? '/assets/css/blog-single.css' : '/assets/css/blog-archive.css' ),
        array(),
        $version,
        'all'
    );

    // Both templates render the theme breadcrumbs, so their CSS must be
    // queued in the head (the trail itself is built after wp_head).
    if ( function_exists( 'vf_breadcrumbs_register_style' ) ) {
        vf_breadcrumbs_register_style();
        wp_enqueue_style( 'vf-breadcrumbs-style' );
    }

    if ( ! $is_single ) {
        wp_enqueue_script(
            'vf-blog-archive',
            get_stylesheet_directory_uri() . '/assets/js/blog-archive.js',
            array(),
            $version,
            true
        );
        return;
    }

    // Product cards inside "Related Kits" need the shop card styles.
    if ( ! function_exists( 'wc_get_products' ) ) {
        return;
    }

    wp_enqueue_style( 'woocommerce-general' );
    wp_enqueue_style( 'woocommerce-smallscreen' );

    // XStore registers these handles globally (see etheme_config_css_files()).
    // Marking them in $etheme_styles stops loop-start.php from printing a
    // duplicate inline <link> when the product loop renders further down.
    global $etheme_styles;
    if ( ! is_array( $etheme_styles ) ) {
        $etheme_styles = array();
    }

    wp_enqueue_style( 'etheme-woocommerce-archive' );
    $etheme_styles[] = 'woocommerce-archive';

    $product_view = function_exists( 'etheme_get_option' ) ? etheme_get_option( 'product_view', 'disable' ) : 'disable';
    if ( $product_view && ! in_array( $product_view, array( 'disable', 'custom' ), true ) ) {
        wp_enqueue_style( 'etheme-product-view-' . $product_view );
        $etheme_styles[] = 'product-view-' . $product_view;
    }

    // "Related Kits" reuses the Elementor product-search widget card design.
    if ( ! wp_style_is( 'vf-product-search-filters-style', 'registered' ) ) {
        wp_register_style(
            'vf-product-search-filters-style',
            get_stylesheet_directory_uri() . '/elementor/widgets/vf-product-search-filters/widget.css',
            array(),
            $version
        );
    }
    wp_enqueue_style( 'vf-product-search-filters-style' );
}
add_action( 'wp_enqueue_scripts', 'vf_blog_enqueue_assets', 60 );

/**
 * Estimated reading time in minutes.
 *
 * @param int|null $post_id Post ID.
 * @return int
 */
function vf_blog_reading_time( $post_id = null ) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $content = (string) get_post_field( 'post_content', $post_id );
    $words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );

    return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Primary category of a post (Rank Math primary first, then the first term).
 *
 * @param int|null $post_id Post ID.
 * @return WP_Term|null
 */
function vf_blog_primary_category( $post_id = null ) {
    $post_id = $post_id ? $post_id : get_the_ID();

    $primary = (int) get_post_meta( $post_id, 'rank_math_primary_category', true );
    if ( $primary ) {
        $term = get_term( $primary, 'category' );
        if ( $term && ! is_wp_error( $term ) ) {
            return $term;
        }
    }

    $categories = get_the_category( $post_id );

    return ! empty( $categories ) ? $categories[0] : null;
}

/**
 * Related posts from the same categories.
 *
 * @param int|null $post_id Post ID.
 * @param int      $limit   Number of posts.
 * @return WP_Post[]
 */
function vf_blog_related_posts( $post_id = null, $limit = 3 ) {
    $post_id    = $post_id ? $post_id : get_the_ID();
    $categories = wp_get_post_categories( $post_id );

    $args = array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'post__not_in'        => array( $post_id ),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    );

    if ( ! empty( $categories ) ) {
        $args['category__in'] = $categories;
    }

    $posts = get_posts( $args );

    if ( empty( $posts ) ) {
        unset( $args['category__in'] );
        $posts = get_posts( $args );
    }

    return $posts;
}

/**
 * Product IDs related to a post.
 *
 * Priority: ACF relationship field, manual meta list, post tags matched to
 * product category slugs, best-selling in-stock products, then newest.
 *
 * @param int|null $post_id Post ID.
 * @param int      $limit   Number of products.
 * @return int[]
 */
function vf_blog_related_product_ids( $post_id = null, $limit = 4 ) {
    $post_id = $post_id ? $post_id : get_the_ID();
    $limit   = max( 1, (int) $limit );

    if ( ! function_exists( 'wc_get_products' ) ) {
        return array();
    }

    // 1. ACF "Related Products" (post_object, return_format: id, multiple on).
    if ( function_exists( 'get_field' ) ) {
        foreach ( array( 'vf_post_related_products', 'related_products', 'blog_related_products' ) as $field ) {
            $value = get_field( $field, $post_id );
            if ( empty( $value ) ) {
                continue;
            }

            $ids = array();
            foreach ( (array) $value as $item ) {
                if ( is_object( $item ) ) {
                    $ids[] = (int) ( $item->ID ?? 0 );
                } elseif ( is_array( $item ) ) {
                    $ids[] = (int) ( $item['ID'] ?? $item['id'] ?? 0 );
                } else {
                    $ids[] = (int) $item;
                }
            }

            $ids = array_values( array_unique( array_filter( $ids ) ) );

            if ( $ids ) {
                // Honour the editor's full selection and its order.
                return $ids;
            }
        }
    }

    // 2. Manual meta list of IDs.
    $meta = (string) get_post_meta( $post_id, '_vf_related_products', true );
    if ( '' !== $meta ) {
        $ids = array_values( array_filter( array_map( 'absint', explode( ',', $meta ) ) ) );
        if ( $ids ) {
            return array_slice( $ids, 0, $limit );
        }
    }

    // 3. Post tags matched against product category slugs.
    $tags = wp_get_post_tags( $post_id, array( 'fields' => 'slugs' ) );
    if ( ! empty( $tags ) ) {
        $products = wc_get_products( array(
            'status'       => 'publish',
            'limit'        => $limit,
            'return'       => 'ids',
            'stock_status' => 'instock',
            'tax_query'    => array(
                array(
                    'taxonomy' => 'product_cat',
                    'field'    => 'slug',
                    'terms'    => $tags,
                ),
            ),
        ) );

        if ( ! empty( $products ) ) {
            return array_map( 'absint', $products );
        }
    }

    // 4. Fallback: most popular — best-selling in-stock products.
    $popular = new WP_Query( array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => $limit,
        'fields'              => 'ids',
        'no_found_rows'       => true,
        'ignore_sticky_posts' => true,
        'meta_key'            => 'total_sales',
        'orderby'             => 'meta_value_num',
        'order'               => 'DESC',
        'meta_query'          => array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '=',
            ),
        ),
        'tax_query'           => array(
            array(
                'taxonomy' => 'product_visibility',
                'field'    => 'name',
                'terms'    => 'exclude-from-catalog',
                'operator' => 'NOT IN',
            ),
        ),
    ) );

    $popular_ids = array_map( 'absint', (array) $popular->posts );

    if ( ! empty( $popular_ids ) ) {
        return $popular_ids;
    }

    // 5. Last resort: newest in-stock products.
    $products = wc_get_products( array(
        'status'       => 'publish',
        'limit'        => $limit,
        'return'       => 'ids',
        'stock_status' => 'instock',
        'orderby'      => 'date',
        'order'        => 'DESC',
    ) );

    return array_map( 'absint', (array) $products );
}

/**
 * Render the theme's native product loop for a list of product IDs.
 *
 * Uses WooCommerce's own loop wrapper plus XStore's content-product template,
 * so markup and CSS match the shop / category product loop exactly.
 *
 * @param int[] $product_ids Product IDs.
 */
function vf_blog_render_product_loop( $product_ids ) {
    if ( empty( $product_ids ) || ! function_exists( 'wc_get_template_part' ) ) {
        return;
    }

    $query = new WP_Query( array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => count( $product_ids ),
        'post__in'            => $product_ids,
        'orderby'             => 'post__in',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ) );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return;
    }

    $columns = 4;

    wc_set_loop_prop( 'columns', $columns );
    wc_set_loop_prop( 'total', $query->post_count );
    wc_set_loop_prop( 'per_page', $query->post_count );
    wc_set_loop_prop( 'current_page', 1 );
    wc_set_loop_prop( 'is_paginated', false );

    global $woocommerce_loop;
    $woocommerce_loop = array_merge( (array) $woocommerce_loop, array(
        'columns'      => $columns,
        'loop'         => 0,
        'is_shortcode' => true,
        'doing_ajax'   => false,
    ) );

    // Same DOM wrapper the Elementor product-search widget renders, so its
    // stylesheet styles these cards identically.
    echo '<div class="elementor-widget-vf-product-search-filters vf-blog-products"><div class="vf-ps"><div class="vf-ps-results">';

    woocommerce_product_loop_start();

    while ( $query->have_posts() ) {
        $query->the_post();
        do_action( 'woocommerce_shop_loop' );
        wc_get_template_part( 'content', 'product' );
    }

    woocommerce_product_loop_end();

    echo '</div></div></div>';

    wp_reset_postdata();
}

/* -------------------------------------------------------------------------
 * Comments
 * ---------------------------------------------------------------------- */

/**
 * Turn off comments and trackbacks on blog posts only.
 *
 * Product reviews use a different post type and stay untouched.
 */
function vf_blog_disable_post_comments() {
    remove_post_type_support( 'post', 'comments' );
    remove_post_type_support( 'post', 'trackbacks' );
}
add_action( 'init', 'vf_blog_disable_post_comments' );

/**
 * Close comments/pings on existing posts.
 *
 * @param bool $open    Current state.
 * @param int  $post_id Post ID.
 * @return bool
 */
function vf_blog_close_post_comments( $open, $post_id ) {
    $post = get_post( $post_id );

    if ( $post && 'post' === $post->post_type ) {
        return false;
    }

    return $open;
}
add_filter( 'comments_open', 'vf_blog_close_post_comments', 20, 2 );
add_filter( 'pings_open', 'vf_blog_close_post_comments', 20, 2 );

/**
 * Never print stored comments for blog posts (handles legacy spam).
 *
 * @param array $comments Comments list.
 * @param int   $post_id  Post ID.
 * @return array
 */
function vf_blog_hide_post_comments( $comments, $post_id ) {
    $post = get_post( $post_id );

    if ( $post && 'post' === $post->post_type ) {
        return array();
    }

    return $comments;
}
add_filter( 'comments_array', 'vf_blog_hide_post_comments', 20, 2 );

/* -------------------------------------------------------------------------
 * Blog archive helpers
 * ---------------------------------------------------------------------- */

/**
 * URL of the blog index (posts page).
 *
 * @return string
 */
function vf_blog_index_url() {
    $page_for_posts = (int) get_option( 'page_for_posts' );

    if ( $page_for_posts ) {
        return get_permalink( $page_for_posts );
    }

    return home_url( '/blog/' );
}

/**
 * Blog categories used by the archive filter chips.
 *
 * @return WP_Term[]
 */
function vf_blog_filter_categories() {
    $terms = get_terms( array(
        'taxonomy'   => 'category',
        'hide_empty' => true,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ) );

    return is_wp_error( $terms ) ? array() : $terms;
}

/**
 * Render the category chips as links.
 *
 * "All" points at the posts page; the rest use ?cat=<id> so pagination and the
 * blog template are preserved.
 */
function vf_blog_render_category_chips() {
    $terms   = vf_blog_filter_categories();
    $current = (int) get_query_var( 'cat' );

    if ( empty( $terms ) ) {
        return;
    }

    echo '<div class="vf-filters-wrap">';
    echo '<h2 class="vf-filters__title">' . esc_html__( 'Categories', 'xstore-child' ) . '</h2>';
    echo '<div class="vf-filters" role="group" aria-label="' . esc_attr__( 'Filter posts by topic', 'xstore-child' ) . '">';

    printf(
        '<a class="vf-chip%s" href="%s"%s>%s</a>',
        $current ? '' : ' is-active',
        esc_url( vf_blog_index_url() ),
        $current ? '' : ' aria-current="page"',
        esc_html__( 'All', 'xstore-child' )
    );

    foreach ( $terms as $term ) {
        // The default "Uncategorized" term is noise on a curated blog.
        if ( 'uncategorized' === $term->slug ) {
            continue;
        }

        printf(
            '<a class="vf-chip%s" href="%s"%s>%s</a>',
            ( $current === (int) $term->term_id ) ? ' is-active' : '',
            esc_url( add_query_arg( 'cat', (int) $term->term_id, vf_blog_index_url() ) ),
            ( $current === (int) $term->term_id ) ? ' aria-current="page"' : '',
            esc_html( $term->name )
        );
    }

    echo '</div>';
    echo '</div>';
}

/**
 * Blog pagination markup.
 */
function vf_blog_render_pagination() {
    global $wp_query;

    $total   = (int) $wp_query->max_num_pages;
    $current = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

    if ( $total < 2 ) {
        return;
    }

    $links = paginate_links( array(
        'total'     => $total,
        'current'   => $current,
        'type'      => 'array',
        'mid_size'  => 2,
        'end_size'  => 1,
        'prev_text' => __( 'Prev', 'xstore-child' ),
        'next_text' => __( 'Next', 'xstore-child' ),
        'add_args'  => array_filter( array(
            'cat' => (int) get_query_var( 'cat' ),
        ) ),
    ) );

    if ( empty( $links ) ) {
        return;
    }

    echo '<nav class="vf-pagination" aria-label="' . esc_attr__( 'Blog pagination', 'xstore-child' ) . '">';

    foreach ( $links as $link ) {
        // paginate_links already sets aria-current on the current page.
        echo str_replace( 'page-numbers current', 'page-numbers is-current', $link ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    echo '</nav>';
}

/* -------------------------------------------------------------------------
 * Blog index grid size (.vf-post-grid)
 * ---------------------------------------------------------------------- */

/**
 * Cards per page in the blog index grid.
 *
 * Change the 5 in $per_page to control how many posts .vf-post-grid shows.
 * The value is applied to the main query, so pagination and the AJAX
 * pagination always follow it.
 *
 * @return int
 */
function vf_blog_posts_per_page() {
    $per_page = 8;

    return max( 1, (int) apply_filters( 'vf_blog_posts_per_page', $per_page ) );
}

/**
 * Apply the grid page size to the blog index main query.
 *
 * @param WP_Query $query Query object.
 */
function vf_blog_set_posts_per_page( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() || $query->is_feed() ) {
        return;
    }

    $query->set( 'posts_per_page', vf_blog_posts_per_page() );
}
add_action( 'pre_get_posts', 'vf_blog_set_posts_per_page' );

/**
 * Recent posts for the blog sidebar.
 *
 * @param int $limit Number of posts.
 * @return WP_Post[]
 */
function vf_blog_recent_posts( $limit = 5 ) {
    return get_posts( array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => max( 1, (int) $limit ),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ) );
}

/* -------------------------------------------------------------------------
 * Image fallback
 * ---------------------------------------------------------------------- */

/**
 * Placeholder image used when a post has no featured image.
 *
 * @param array $attr Optional <img> attributes.
 * @return string
 */
function vf_blog_placeholder_image( $attr = array() ) {
    $attr = wp_parse_args(
        $attr,
        array(
            'class'    => '',
            'loading'  => 'lazy',
            'alt'      => '',
            'width'    => 1200,
            'height'   => 800,
        )
    );

    return sprintf(
        '<img src="%1$s" width="%2$d" height="%3$d" alt="%4$s" class="%5$s" loading="%6$s" decoding="async" data-no-lazy="1" />',
        esc_url( get_stylesheet_directory_uri() . '/assets/img/blog-placeholder.svg' ),
        (int) $attr['width'],
        (int) $attr['height'],
        esc_attr( $attr['alt'] ),
        esc_attr( $attr['class'] ),
        esc_attr( $attr['loading'] )
    );
}

/**
 * Featured image with a placeholder fallback.
 *
 * Outputs a plain <img> with the requested size instead of
 * get_the_post_thumbnail(), because the theme swaps the src for a 10x10
 * lazy-load placeholder and the card would paint blurry until JS runs.
 *
 * @param int    $post_id Post ID.
 * @param string $size    Image size.
 * @param array  $attr    Extra <img> attributes: class, loading, alt, fetchpriority, sizes.
 * @return string
 */
function vf_blog_post_thumbnail( $post_id, $size = 'medium_large', $attr = array() ) {
    $attr = wp_parse_args(
        $attr,
        array(
            'class'         => '',
            'loading'       => 'lazy',
            'fetchpriority' => '',
            'sizes'         => '',
        )
    );

    $alt_override = array_key_exists( 'alt', $attr ) ? $attr['alt'] : null;
    $image_id     = (int) get_post_thumbnail_id( $post_id );

    if ( $image_id ) {
        $src = wp_get_attachment_image_src( $image_id, $size );

        if ( $src ) {
            $alt = null !== $alt_override ? $alt_override : get_post_meta( $image_id, '_wp_attachment_image_alt', true );

            if ( '' === trim( (string) $alt ) ) {
                $alt = get_the_title( $post_id );
            }

            $html  = sprintf(
                '<img src="%1$s" width="%2$d" height="%3$d" alt="%4$s"',
                esc_url( $src[0] ),
                (int) $src[1],
                (int) $src[2],
                esc_attr( $alt )
            );
            $html .= $attr['class'] ? ' class="' . esc_attr( $attr['class'] ) . '"' : '';

            // Responsive candidates are built manually because the global
            // srcset filter is disabled (see inc/hooks.php).
            $srcset = vf_image_srcset( $image_id, 2048 );

            if ( $srcset ) {
                $html .= ' srcset="' . esc_attr( $srcset ) . '"';
                $html .= ' sizes="' . esc_attr( $attr['sizes'] ? $attr['sizes'] : '100vw' ) . '"';
            }

            $html .= ' loading="' . esc_attr( $attr['loading'] ) . '" decoding="async" data-no-lazy="1"';

            if ( $attr['fetchpriority'] ) {
                $html .= ' fetchpriority="' . esc_attr( $attr['fetchpriority'] ) . '"';
            }

            return $html . ' />';
        }
    }

    return vf_blog_placeholder_image( $attr );
}

/* -------------------------------------------------------------------------
 * Featured posts (ACF "vf_featured_post" true/false)
 * ---------------------------------------------------------------------- */

/**
 * IDs of posts flagged as featured, newest first.
 *
 * @return int[]
 */
function vf_blog_featured_post_ids() {
    static $ids = null;

    if ( null !== $ids ) {
        return $ids;
    }

    $ids = get_posts( array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'posts_per_page'      => -1,
        'fields'              => 'ids',
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'meta_query'          => array(
            array(
                'key'     => 'vf_featured_post',
                'value'   => '1',
                'compare' => '=',
            ),
        ),
    ) );

    $ids = array_map( 'absint', (array) $ids );

    return $ids;
}

/**
 * Featured posts, in the same order as vf_blog_featured_post_ids().
 *
 * @return WP_Post[]
 */
function vf_blog_featured_posts() {
    $ids = vf_blog_featured_post_ids();

    if ( empty( $ids ) ) {
        return array();
    }

    return get_posts( array(
        'post_type'           => 'post',
        'post_status'         => 'publish',
        'post__in'            => $ids,
        'orderby'             => 'post__in',
        'posts_per_page'      => count( $ids ),
        'ignore_sticky_posts' => true,
        'no_found_rows'       => true,
    ) );
}

/* -------------------------------------------------------------------------
 * Top stories (overlay cards above the featured list)
 * ---------------------------------------------------------------------- */

/**
 * Posts for the top-stories overlay section: featured posts first, then the
 * newest posts that are not already in the list.
 *
 * @param WP_Post[] $featured Featured posts, newest first.
 * @param int       $limit    Number of cards.
 * @return WP_Post[]
 */
function vf_blog_top_story_posts( $featured = array(), $limit = 3 ) {
    $limit = max( 1, (int) $limit );
    $posts = array_slice( array_values( (array) $featured ), 0, $limit );

    if ( count( $posts ) < $limit ) {
        $exclude = wp_list_pluck( $posts, 'ID' );

        $fill = get_posts( array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit - count( $posts ),
            'post__not_in'        => $exclude ? $exclude : array( 0 ),
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ) );

        $posts = array_merge( $posts, $fill );
    }

    return $posts;
}

/**
 * Overlay card used by the top-stories section.
 *
 * The whole card is one link; the category is a label, not a nested link.
 *
 * @param WP_Post|int $post    Post object or ID.
 * @param bool        $is_lead Whether this is the large lead card.
 * @return string
 */
function vf_blog_render_overlay_card( $post, $is_lead = false ) {
    $post = get_post( $post );

    if ( ! $post ) {
        return '';
    }

    $cat       = vf_blog_primary_category( $post->ID );
    $minutes   = vf_blog_reading_time( $post->ID );
    $permalink = get_permalink( $post );
    $tag       = $is_lead ? 'h2' : 'h3';
    $attr      = array(
        'class'   => 'vf-overlay-card__img',
        'loading' => $is_lead ? 'eager' : 'lazy',
        'sizes'   => $is_lead
            ? '(max-width: 767px) calc(100vw - 32px), (max-width: 1024px) calc(100vw - 48px), 580px'
            : '(max-width: 480px) calc(100vw - 32px), (max-width: 767px) calc(50vw - 24px), (max-width: 1024px) calc(50vw - 32px), 340px',
    );

    if ( $is_lead ) {
        $attr['fetchpriority'] = 'high';
    }

    $html  = '<article class="vf-overlay-card' . ( $is_lead ? ' vf-overlay-card--lead' : '' ) . '">';
    $html .= '<a class="vf-overlay-card__link" href="' . esc_url( $permalink ) . '">';
    $html .= '<span class="vf-overlay-card__media">' . vf_blog_post_thumbnail( $post->ID, 'medium_large', $attr ) . '</span>';
    $html .= '<span class="vf-overlay-card__body">';

    if ( $cat ) {
        $html .= '<span class="vf-overlay-card__cat">' . esc_html( $cat->name ) . '</span>';
    }

    $html .= '<' . $tag . ' class="vf-overlay-card__title">' . esc_html( get_the_title( $post ) ) . '</' . $tag . '>';
    $html .= '<span class="vf-overlay-card__meta">';
    $html .= '<time datetime="' . esc_attr( get_the_date( DATE_W3C, $post ) ) . '">' . esc_html( get_the_date( 'j F, Y', $post ) ) . '</time>';
    $html .= '<span aria-hidden="true">&middot;</span>';
    $html .= '<span>' . esc_html( sprintf( _n( '%s min read', '%s min read', $minutes, 'xstore-child' ), number_format_i18n( $minutes ) ) ) . '</span>';
    $html .= '</span>';

    $html .= '</span>';
    $html .= '</a>';
    $html .= '</article>';

    return $html;
}

/**
 * Posts shown in the top-stories overlay section.
 *
 * Featured posts come first; when there are none (or too few) the newest
 * posts fill the remaining slots.
 *
 * @param int $limit Number of cards.
 * @return WP_Post[]
 */
function vf_blog_top_stories( $limit = 3 ) {
    static $cache = array();

    $limit = max( 1, (int) $limit );

    if ( isset( $cache[ $limit ] ) ) {
        return $cache[ $limit ];
    }

    $featured = vf_blog_featured_posts();

    if ( empty( $featured ) ) {
        $featured = get_posts( array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => $limit,
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,
        ) );
    }

    $cache[ $limit ] = vf_blog_top_story_posts( $featured, $limit );

    return $cache[ $limit ];
}

/**
 * Preload the lead top-story image on the blog index.
 *
 * The overlay card is the LCP element on /blog/, so the browser should start
 * fetching it before the markup is parsed.
 */
function vf_blog_preload_top_story_image() {
    if ( ! is_home() ) {
        return;
    }

    $posts = vf_blog_top_stories( 3 );

    if ( empty( $posts[0] ) ) {
        return;
    }

    $image_id = (int) get_post_thumbnail_id( $posts[0]->ID );

    if ( ! $image_id ) {
        return;
    }

    $src = wp_get_attachment_image_src( $image_id, 'medium_large' );

    if ( empty( $src[0] ) ) {
        return;
    }

    $srcset = vf_image_srcset( $image_id, 2048 );
    $sizes  = '(max-width: 767px) calc(100vw - 32px), (max-width: 1024px) calc(100vw - 48px), 580px';

    echo '<link rel="preload" as="image" href="' . esc_url( $src[0] ) . '"';

    if ( $srcset ) {
        echo ' imagesrcset="' . esc_attr( $srcset ) . '" imagesizes="' . esc_attr( $sizes ) . '"';
    }

    echo ' fetchpriority="high" />' . "\n";
}
add_action( 'wp_head', 'vf_blog_preload_top_story_image', 2 );
