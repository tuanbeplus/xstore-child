<?php
/**
 * VF Product Categories Filters - AJAX Filter & Pagination Handler
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper to get term continent
 */
function vf_get_category_continent( $term_id ) {
    $val = '';
    if ( function_exists( 'get_field' ) ) {
        $val = get_field( 'prod_cat_continent', 'product_cat_' . $term_id );
    }
    if ( empty( $val ) ) {
        $val = get_term_meta( $term_id, 'prod_cat_continent', true );
    }
    if ( empty( $val ) ) {
        $val = get_term_meta( $term_id, 'continent', true );
    }
    return is_string( $val ) ? trim( strtolower( $val ) ) : '';
}

/**
 * Helper to get term league
 */
function vf_get_category_league( $term_id ) {
    $val = '';
    if ( function_exists( 'get_field' ) ) {
        $val = get_field( 'prod_cat_league', 'product_cat_' . $term_id );
    }
    if ( empty( $val ) ) {
        $val = get_term_meta( $term_id, 'prod_cat_league', true );
    }
    if ( empty( $val ) ) {
        $val = get_term_meta( $term_id, 'league', true );
    }
    return is_string( $val ) ? trim( strtolower( $val ) ) : '';
}

/**
 * Render single category card HTML
 */
function vf_render_single_category_card( $cat_data, $settings = [] ) {
    $count_num = (int) $cat_data['count'];
    $show_count = ! isset( $settings['show_product_count'] ) || 'yes' === $settings['show_product_count'];
    $singular_format = ! empty( $settings['count_text_singular'] ) ? $settings['count_text_singular'] : '{count} product';
    $plural_format   = ! empty( $settings['count_text_plural'] ) ? $settings['count_text_plural'] : '{count} products';
    $open_new_tab    = ! empty( $settings['open_in_new_tab'] ) && 'yes' === $settings['open_in_new_tab'];
    $target_attr     = $open_new_tab ? ' target="_blank" rel="noopener"' : '';

    if ( 1 === $count_num ) {
        $count_string = str_replace( '{count}', $count_num, $singular_format );
    } else {
        $count_string = str_replace( '{count}', $count_num, $plural_format );
    }

    ob_start();
    ?>
    <a
        href="<?php echo esc_url( $cat_data['link'] ); ?>"
        class="vf-cat-card"
        data-id="<?php echo esc_attr( $cat_data['id'] ); ?>"
        data-name="<?php echo esc_attr( $cat_data['name'] ); ?>"
        data-count="<?php echo esc_attr( $count_num ); ?>"
        data-continent="<?php echo esc_attr( $cat_data['continent'] ); ?>"
        data-league="<?php echo esc_attr( $cat_data['league'] ); ?>"
        <?php echo $target_attr; ?>>

        <div class="vf-card-image-wrap <?php echo empty( $cat_data['image_url'] ) ? 'vf-card-image-wrap--placeholder' : ''; ?>">
            <?php if ( ! empty( $cat_data['image_url'] ) ) : ?>
                <img
                    src="<?php echo esc_url( $cat_data['image_url'] ); ?>"
                    alt="<?php echo esc_attr( $cat_data['name'] ); ?>"
                    class="vf-card-image"
                    loading="lazy"
                />
            <?php else : ?>
                <svg class="vf-placeholder-icon" viewBox="0 0 24 24">
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-9c.83 0 1.5-.67 1.5-1.5S7.83 8 7 8s-1.5.67-1.5 1.5S6.17 11 7 11zm10 0c.83 0 1.5-.67 1.5-1.5S17.83 8 17 8s-1.5.67-1.5 1.5S16.17 11 17 11zm10 0c.83 0 1.5-.67 1.5-1.5S17.83 8 17 8s-1.5.67-1.5 1.5.67 1.5 1.5 1.5z"></path>
                </svg>
            <?php endif; ?>
        </div>

        <div class="vf-card-overlay"></div>

        <div class="vf-card-content">
            <h3 class="vf-card-title"><?php echo esc_html( $cat_data['name'] ); ?></h3>
            <?php if ( $show_count ) : ?>
                <span class="vf-card-count"><?php echo esc_html( $count_string ); ?></span>
            <?php endif; ?>
        </div>

    </a>
    <?php
    return ob_get_clean();
}

/**
 * Prevent WordPress canonical redirect from redirecting /clubs/page/2/ back to /clubs/
 */
add_filter( 'redirect_canonical', 'vf_disable_canonical_redirect_for_paged', 10, 2 );
function vf_disable_canonical_redirect_for_paged( $redirect_url, $requested_url ) {
    if ( preg_match( '#/page/([0-9]+)/?#', $requested_url ) ) {
        return false;
    }
    return $redirect_url;
}

/**
 * Register rewrite rules so /slug/page/2/ is recognized by WordPress.
 */
add_action( 'init', 'vf_register_pagination_rewrites' );
function vf_register_pagination_rewrites() {
    add_rewrite_rule(
        '^(clubs|national-teams|legend|legends)/page/([0-9]+)/?$',
        'index.php?pagename=$matches[1]&paged=$matches[2]',
        'top'
    );
    add_rewrite_rule(
        '^([^/]+)/page/([0-9]+)/?$',
        'index.php?pagename=$matches[1]&paged=$matches[2]',
        'bottom'
    );
}

/**
 * Ensure requests matching /page/N/ are parsed properly even before rewrite rules are flushed.
 */
add_filter( 'request', 'vf_parse_paged_request', 1 );
function vf_parse_paged_request( $query_vars ) {
    if ( isset( $query_vars['pagename'] ) && preg_match( '#^(.*?)/page/([0-9]+)$#', $query_vars['pagename'], $matches ) ) {
        $query_vars['pagename'] = $matches[1];
        $query_vars['paged']    = (int) $matches[2];
        $query_vars['page']     = (int) $matches[2];
    }
    return $query_vars;
}

/**
 * Helper to construct pagination URLs (e.g. /clubs/page/2/)
 */
function vf_get_page_link_url( $page_num, $base_url = '' ) {
    if ( empty( $base_url ) ) {
        if ( ! empty( $_SERVER['HTTP_REFERER'] ) && wp_doing_ajax() ) {
            $base_url = $_SERVER['HTTP_REFERER'];
        } elseif ( ! empty( $_SERVER['REQUEST_URI'] ) ) {
            $base_url = home_url( $_SERVER['REQUEST_URI'] );
        } else {
            $base_url = home_url( '/' );
        }
    }

    $parts = parse_url( $base_url );
    $path  = isset( $parts['path'] ) ? $parts['path'] : '/';
    $path  = preg_replace( '#/page/\d+/?#', '', $path );
    $path  = rtrim( $path, '/' );

    if ( $page_num > 1 ) {
        $path .= '/page/' . $page_num . '/';
    } else {
        $path .= '/';
    }

    $scheme = ! empty( $parts['scheme'] ) ? $parts['scheme'] : ( is_ssl() ? 'https' : 'http' );
    $host   = ! empty( $parts['host'] ) ? $parts['host'] : ( ! empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : '' );

    if ( $host ) {
        $url = $scheme . '://' . $host . $path;
    } else {
        $url = home_url( $path );
    }

    if ( ! empty( $parts['query'] ) ) {
        $url .= '?' . $parts['query'];
    }

    return $url;
}

/**
 * Render pagination nav HTML with proper URLs
 */
function vf_render_pagination_nav( $current_page, $total_pages, $base_url = '' ) {
    if ( $total_pages <= 1 ) {
        return '';
    }

    ob_start();
    ?>
    <nav class="vf-pagination" aria-label="<?php esc_attr_e( 'Categories Pagination', 'xstore-child' ); ?>">
        <?php if ( $current_page > 1 ) : ?>
            <a href="<?php echo esc_url( vf_get_page_link_url( $current_page - 1, $base_url ) ); ?>" class="vf-page-btn vf-page-btn--prev" data-page="<?php echo esc_attr( $current_page - 1 ); ?>" aria-label="<?php esc_attr_e( 'Previous page', 'xstore-child' ); ?>">
                &larr; <?php esc_html_e( 'Prev', 'xstore-child' ); ?>
            </a>
        <?php endif; ?>

        <div class="vf-page-numbers">
            <?php
            // Calculate pagination window
            $start_page = max( 1, $current_page - 2 );
            $end_page   = min( $total_pages, $current_page + 2 );

            if ( $start_page > 1 ) {
                printf(
                    '<a href="%s" class="vf-page-btn" data-page="1">1</a>',
                    esc_url( vf_get_page_link_url( 1, $base_url ) )
                );
                if ( $start_page > 2 ) {
                    echo '<span class="vf-page-dots">&hellip;</span>';
                }
            }

            for ( $i = $start_page; $i <= $end_page; $i++ ) {
                $is_active = ( $i === $current_page );
                printf(
                    '<a href="%s" class="vf-page-btn %s" data-page="%d" %s>%d</a>',
                    esc_url( vf_get_page_link_url( $i, $base_url ) ),
                    $is_active ? 'is-active' : '',
                    $i,
                    $is_active ? 'aria-current="page"' : '',
                    $i
                );
            }

            if ( $end_page < $total_pages ) {
                if ( $end_page < $total_pages - 1 ) {
                    echo '<span class="vf-page-dots">&hellip;</span>';
                }
                printf(
                    '<a href="%s" class="vf-page-btn" data-page="%d">%d</a>',
                    esc_url( vf_get_page_link_url( $total_pages, $base_url ) ),
                    $total_pages,
                    $total_pages
                );
            }
            ?>
        </div>

        <?php if ( $current_page < $total_pages ) : ?>
            <a href="<?php echo esc_url( vf_get_page_link_url( $current_page + 1, $base_url ) ); ?>" class="vf-page-btn vf-page-btn--next" data-page="<?php echo esc_attr( $current_page + 1 ); ?>" aria-label="<?php esc_attr_e( 'Next page', 'xstore-child' ); ?>">
                <?php esc_html_e( 'Next', 'xstore-child' ); ?> &rarr;
            </a>
        <?php endif; ?>
    </nav>
    <?php
    return ob_get_clean();
}

/**
 * Handle AJAX filtering of categories
 */
add_action( 'wp_ajax_vf_filter_product_categories', 'vf_ajax_filter_product_categories' );
add_action( 'wp_ajax_nopriv_vf_filter_product_categories', 'vf_ajax_filter_product_categories' );

function vf_ajax_filter_product_categories() {
    check_ajax_referer( 'vf_filter_nonce', 'nonce' );

    $parent_id   = ! empty( $_POST['parent_id'] ) ? absint( $_POST['parent_id'] ) : 0;
    $continent   = ! empty( $_POST['continent'] ) ? sanitize_text_field( strtolower( wp_unslash( $_POST['continent'] ) ) ) : 'all';
    $league      = ! empty( $_POST['league'] ) ? sanitize_text_field( strtolower( wp_unslash( $_POST['league'] ) ) ) : 'all';
    $search      = ! empty( $_POST['search'] ) ? sanitize_text_field( wp_unslash( $_POST['search'] ) ) : '';
    $sort        = ! empty( $_POST['sort'] ) ? sanitize_text_field( wp_unslash( $_POST['sort'] ) ) : 'name_asc';
    $page        = ! empty( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
    $base_url    = ! empty( $_POST['base_url'] ) ? esc_url_raw( wp_unslash( $_POST['base_url'] ) ) : '';
    $per_page    = 15; // 15 categories per page as requested

    if ( 0 === $parent_id ) {
        wp_send_json_error( [ 'message' => 'Invalid parent category.' ] );
    }

    // Query all child categories of this parent
    $terms = get_terms( [
        'taxonomy'   => 'product_cat',
        'child_of'   => $parent_id,
        'hide_empty' => false,
    ] );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        wp_send_json_success( [
            'html'            => '',
            'pagination_html' => '',
            'total'           => 0,
            'shown_count'     => 0,
            'current_page'    => 1,
            'total_pages'     => 0,
            'count_text'      => __( 'Showing 0 categories', 'xstore-child' ),
        ] );
    }

    $normalized_search = $search ? remove_accents( strtolower( $search ) ) : '';

    $filtered_data = [];

    foreach ( $terms as $term ) {
        $term_continent = vf_get_category_continent( $term->term_id );
        $term_league    = vf_get_category_league( $term->term_id );

        // 1. Continent Filter
        if ( 'all' !== $continent && $term_continent !== $continent ) {
            continue;
        }

        // 2. League Filter
        if ( 'all' !== $league && $term_league !== $league ) {
            continue;
        }

        // 3. Search Filter (Accent-insensitive)
        if ( ! empty( $normalized_search ) ) {
            $term_name_clean = remove_accents( strtolower( $term->name ) );
            if ( false === strpos( $term_name_clean, $normalized_search ) ) {
                continue;
            }
        }

        // Thumbnail
        $thumb_id  = get_term_meta( $term->term_id, 'thumbnail_id', true );
        $image_url = $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '';

        // Link
        $term_link = get_term_link( $term );
        if ( is_wp_error( $term_link ) ) {
            $term_link = '#';
        }

        $filtered_data[] = [
            'id'        => $term->term_id,
            'name'      => $term->name,
            'slug'      => $term->slug,
            'count'     => (int) $term->count,
            'link'      => $term_link,
            'image_url' => $image_url,
            'continent' => $term_continent,
            'league'    => $term_league,
        ];
    }

    // 4. Sort
    usort( $filtered_data, function( $a, $b ) use ( $sort ) {
        switch ( $sort ) {
            case 'name_desc':
                return strcasecmp( $b['name'], $a['name'] );
            case 'count_desc':
                if ( $b['count'] === $a['count'] ) {
                    return strcasecmp( $a['name'], $b['name'] );
                }
                return $b['count'] - $a['count'];
            case 'count_asc':
                if ( $a['count'] === $b['count'] ) {
                    return strcasecmp( $a['name'], $b['name'] );
                }
                return $a['count'] - $b['count'];
            case 'name_asc':
            default:
                return strcasecmp( $a['name'], $b['name'] );
        }
    } );

    // 5. Pagination Calculation
    $total_items = count( $filtered_data );
    $total_pages = max( 1, (int) ceil( $total_items / $per_page ) );
    $page        = min( $total_pages, $page );
    $offset      = ( $page - 1 ) * $per_page;
    $paged_data  = array_slice( $filtered_data, $offset, $per_page );
    $shown_count = count( $paged_data );

    // 6. Build HTML Cards
    $cards_html = '';
    if ( ! empty( $paged_data ) ) {
        foreach ( $paged_data as $cat_item ) {
            $cards_html .= vf_render_single_category_card( $cat_item );
        }
    }

    // 7. Build Pagination HTML
    $pagination_html = vf_render_pagination_nav( $page, $total_pages, $base_url );

    // 8. Build Count Text
    if ( $total_items === 0 ) {
        $count_text = __( 'Showing 0 categories', 'xstore-child' );
    } elseif ( $total_items <= $per_page ) {
        $count_text = sprintf(
            /* translators: %d: total categories */
            __( 'Showing %d of %d categories', 'xstore-child' ),
            $total_items,
            $total_items
        );
    } else {
        $start_num = $offset + 1;
        $end_num   = $offset + $shown_count;
        $count_text = sprintf(
            /* translators: 1: start count, 2: end count, 3: total categories */
            __( 'Showing %1$d–%2$d of %3$d categories', 'xstore-child' ),
            $start_num,
            $end_num,
            $total_items
        );
    }

    wp_send_json_success( [
        'html'            => $cards_html,
        'pagination_html' => $pagination_html,
        'total'           => $total_items,
        'shown_count'     => $shown_count,
        'current_page'    => $page,
        'total_pages'     => $total_pages,
        'count_text'      => $count_text,
    ] );
}
