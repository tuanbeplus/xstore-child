<?php
/**
 * Product search + catalog filters.
 *
 * The Elementor widget draws the toolbar and sidebar. Product cards stay in
 * Elementor's Archive Products widget, which reads the main query. These
 * hooks narrow that query, then the widget script swaps the archive markup
 * without a full navigation.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Query keys the sidebar is allowed to set.
 *
 * @return string[]
 */
function vf_ps_query_keys() {
    return array( 'q', 'continents', 'leagues', 'clubs', 'nations', 'legends', 'decades', 'more', 'category', 'min_price', 'max_price', 'orderby' );
}

/**
 * @param string $key
 * @return string[]
 */
function vf_ps_request_list( $key ) {
    $raw = null;
    if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) {
        $raw = wp_unslash( $_GET[ $key ] );
    } elseif ( isset( $_GET[ 'vf_' . $key ] ) && '' !== $_GET[ 'vf_' . $key ] ) {
        $raw = wp_unslash( $_GET[ 'vf_' . $key ] );
    }
    if ( null === $raw || '' === $raw ) {
        return array();
    }

    $parts = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
    $clean = array();

    foreach ( $parts as $part ) {
        $part = sanitize_title( $part );
        if ( '' !== $part ) {
            $clean[] = $part;
        }
    }

    return array_values( array_unique( $clean ) );
}

/**
 * @param string $key
 * @return int[]
 */
function vf_ps_request_ids( $key ) {
    $ids = array();
    foreach ( vf_ps_request_list( $key ) as $part ) {
        $id = absint( $part );
        if ( $id ) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/**
 * First non-empty request list among the given keys.
 *
 * @param string[] $keys
 * @return string[]
 */
function vf_ps_request_first( $keys ) {
    foreach ( $keys as $key ) {
        $values = vf_ps_request_list( $key );
        if ( $values ) {
            return $values;
        }
    }
    return array();
}

/**
 * @param string[] $keys
 * @return int[]
 */
function vf_ps_request_ids_first( $keys ) {
    $ids = array();
    foreach ( vf_ps_request_first( $keys ) as $part ) {
        $id = absint( $part );
        if ( $id ) {
            $ids[] = $id;
        }
    }
    return $ids;
}

/**
 * Decade starts shown in the sidebar.
 *
 * @return array<int,string>
 */
function vf_ps_decade_options() {
    $options = array();
    for ( $year = 1960; $year <= 2020; $year += 10 ) {
        $options[ $year ] = sprintf(
            /* translators: %d: decade start, such as 1960 */
            __( '%d kits', 'xstore-child' ),
            $year
        );
    }
    return $options;
}

/**
 * Four-digit years covered by the selected decades.
 *
 * @param string[] $decades
 * @return string[]
 */
function vf_ps_decade_years( $decades ) {
    $years = array();
    foreach ( $decades as $decade ) {
        $start = absint( $decade );
        if ( $start < 1960 || $start > 2020 || 0 !== ( $start % 10 ) ) {
            continue;
        }
        for ( $year = $start; $year <= $start + 9; $year++ ) {
            $years[] = (string) $year;
        }
    }
    return array_values( array_unique( $years ) );
}

/**
 * Current storefront currency symbol.
 *
 * @return string
 */
function vf_ps_currency_symbol() {
    if ( ! function_exists( 'get_woocommerce_currency_symbol' ) ) {
        return '';
    }
    $currency = '';
    if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
        $currency = WOOMULTI_CURRENCY_Data::get_ins()->get_current_currency();
    } elseif ( class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
        $currency = WOOMULTI_CURRENCY_F_Data::get_ins()->get_current_currency();
    }
    return $currency ? get_woocommerce_currency_symbol( $currency ) : get_woocommerce_currency_symbol();
}

/**
 * Legend filter values are category slugs. Numeric leftovers are converted.
 *
 * @param string[] $parts
 * @return string[]
 */
function vf_ps_legend_slugs( $parts ) {
    $slugs = array();
    foreach ( $parts as $part ) {
        if ( ctype_digit( (string) $part ) ) {
            $term = get_term( (int) $part, 'product_cat' );
            if ( $term && ! is_wp_error( $term ) ) {
                $slugs[] = $term->slug;
            }
            continue;
        }
        $slugs[] = $part;
    }

    return array_values( array_unique( $slugs ) );
}

/**
 * @return array
 */
function vf_ps_active_filters() {
    if ( isset( $GLOBALS['vf_ps_filters'] ) && is_array( $GLOBALS['vf_ps_filters'] ) ) {
        return $GLOBALS['vf_ps_filters'];
    }

    $min = isset( $_GET['min_price'] ) ? (float) wp_unslash( $_GET['min_price'] ) : 0;
    $max = isset( $_GET['max_price'] ) ? (float) wp_unslash( $_GET['max_price'] ) : 0;
    $keyword = '';
    if ( isset( $_GET['q'] ) ) {
        $keyword = sanitize_text_field( wp_unslash( $_GET['q'] ) );
    } elseif ( isset( $_GET['vf_q'] ) ) {
        $keyword = sanitize_text_field( wp_unslash( $_GET['vf_q'] ) );
    } elseif ( isset( $_GET['s'] ) ) {
        $keyword = sanitize_text_field( wp_unslash( $_GET['s'] ) );
    }

    $context_cat = 0;
    if ( isset( $_GET['category'] ) ) {
        $context_cat = absint( $_GET['category'] );
    } elseif ( isset( $_GET['vf_cat'] ) ) {
        $context_cat = absint( $_GET['vf_cat'] );
    }

    return array(
        'q'           => $keyword,
        'continent'   => vf_ps_request_first( array( 'continents', 'continent' ) ),
        'league'      => vf_ps_request_first( array( 'leagues', 'league' ) ),
        'club'        => vf_ps_request_ids_first( array( 'clubs', 'club' ) ),
        'nation'      => vf_ps_request_first( array( 'nations', 'nation' ) ),
        'legend'      => vf_ps_legend_slugs( vf_ps_request_first( array( 'legends', 'legend' ) ) ),
        'decade'      => vf_ps_request_first( array( 'decades', 'decade' ) ),
        'more'        => vf_ps_request_first( array( 'more' ) ),
        'min'         => $min > 0 ? $min : 0,
        'max'         => $max > 0 ? $max : 0,
        'orderby'     => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '',
        'context_cat' => $context_cat,
    );
}

/**
 * @param string $slug
 * @return string
 */
function vf_ps_continent_label( $slug ) {
    $labels = array(
        'europe'        => __( 'Europe', 'xstore-child' ),
        'south_america' => __( 'South America', 'xstore-child' ),
        'north_america' => __( 'North America', 'xstore-child' ),
        'asia'          => __( 'Asia', 'xstore-child' ),
        'africa'        => __( 'Africa', 'xstore-child' ),
        'oceania'       => __( 'Oceania', 'xstore-child' ),
    );

    return isset( $labels[ $slug ] ) ? $labels[ $slug ] : ucwords( str_replace( array( '_', '-' ), ' ', $slug ) );
}

/**
 * @param string $slug
 * @return string
 */
function vf_ps_league_label( $slug ) {
    $labels = array(
        'premier_league'             => __( 'Premier League', 'xstore-child' ),
        'la_liga'                    => __( 'La Liga', 'xstore-child' ),
        'serie_a'                    => __( 'Serie A', 'xstore-child' ),
        'bundesliga'                 => __( 'Bundesliga', 'xstore-child' ),
        'ligue_1'                    => __( 'Ligue 1', 'xstore-child' ),
        'primeira_liga'              => __( 'Primeira Liga', 'xstore-child' ),
        'eredivisie'                 => __( 'Eredivisie', 'xstore-child' ),
        'scottish_premiership'       => __( 'Scottish Premiership', 'xstore-child' ),
        'brasileirao_serie_a'        => __( 'Brasileirão Série A', 'xstore-child' ),
        'argentina_primera_division' => __( 'Argentine Primera División', 'xstore-child' ),
        'liga_mx'                    => __( 'Liga MX', 'xstore-child' ),
        'chile_primera_division'     => __( 'Chile Primera División', 'xstore-child' ),
        'paraguay_primera_division'  => __( 'Paraguay Primera División', 'xstore-child' ),
    );

    return isset( $labels[ $slug ] ) ? $labels[ $slug ] : ucwords( str_replace( array( '_', '-' ), ' ', $slug ) );
}

/**
 * @param int $term_id
 * @param string $key
 * @return string
 */
function vf_ps_term_meta_value( $term_id, $key ) {
    $value = '';
    if ( function_exists( 'get_field' ) ) {
        $value = get_field( $key, 'product_cat_' . $term_id );
    }
    if ( empty( $value ) ) {
        $value = get_term_meta( $term_id, $key, true );
    }
    return is_string( $value ) ? sanitize_title( $value ) : '';
}

/**
 * @param float $amount Base-currency amount.
 * @return float
 */
function vf_ps_to_current_price( $amount ) {
    if ( function_exists( 'wmc_get_price' ) ) {
        return (float) wmc_get_price( $amount );
    }
    return (float) $amount;
}

/**
 * @param float $amount Current-currency amount.
 * @return float
 */
function vf_ps_to_base_price( $amount ) {
    if ( function_exists( 'wmc_revert_price' ) ) {
        return (float) wmc_revert_price( $amount );
    }
    return (float) $amount;
}

/**
 * @return float
 */
function vf_ps_catalog_max_price() {
    $cached = get_transient( 'vf_ps_max_price' );
    if ( false !== $cached ) {
        return (float) $cached;
    }

    global $wpdb;
    $max = (float) $wpdb->get_var(
        "SELECT MAX( CAST( meta_value AS DECIMAL(12,2) ) )
        FROM {$wpdb->postmeta}
        WHERE meta_key = '_price' AND meta_value <> ''"
    );
    if ( $max < 1 ) {
        $max = 500;
    }

    set_transient( 'vf_ps_max_price', $max, 12 * HOUR_IN_SECONDS );
    return $max;
}

/**
 * Category IDs whose continent/league meta matches the request.
 *
 * @param string[] $continents
 * @param string[] $leagues
 * @return int[]|null Null when neither filter is set.
 */
function vf_ps_meta_category_ids( $continents, $leagues ) {
    if ( empty( $continents ) && empty( $leagues ) ) {
        return null;
    }

    $terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'fields'     => 'ids',
        )
    );

    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return array( 0 );
    }

    $matched = array();
    foreach ( $terms as $term_id ) {
        $term_id   = (int) $term_id;
        $continent = vf_ps_term_meta_value( $term_id, 'prod_cat_continent' );
        $league    = vf_ps_term_meta_value( $term_id, 'prod_cat_league' );

        if ( $continents && ! in_array( $continent, $continents, true ) ) {
            continue;
        }
        if ( $leagues && ! in_array( $league, $leagues, true ) ) {
            continue;
        }
        $matched[] = $term_id;
    }

    return $matched ? $matched : array( 0 );
}

/**
 * @param WP_Query $query
 * @return bool
 */
function vf_ps_is_catalog_query( $query ) {
    if ( $query->get( 'vf_ps_apply' ) ) {
        return true;
    }
    if ( is_admin() || ! $query->is_main_query() ) {
        return false;
    }

    $post_type = $query->get( 'post_type' );
    $types     = array_filter( (array) $post_type );
    $search_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';

    if ( $query->is_search() && ( in_array( 'product', $types, true ) || in_array( 'only_product', $types, true ) || in_array( $search_type, array( 'product', 'only_product' ), true ) ) ) {
        return true;
    }

    if ( $query->is_post_type_archive( 'product' ) ) {
        return true;
    }

    $taxonomies = get_object_taxonomies( 'product' );
    if ( $taxonomies && $query->is_tax( $taxonomies ) ) {
        return true;
    }

    return false;
}

/**
 * Narrow a product query with the sidebar request.
 *
 * @param WP_Query $query
 */
function vf_ps_apply_filters_to_query( $query ) {
    $filters = vf_ps_active_filters();

    $post_type = $query->get( 'post_type' );
    if ( 'only_product' === $post_type && ! post_type_exists( 'only_product' ) ) {
        $query->set( 'post_type', 'product' );
    }

    if ( $query->get( 'vf_ps_apply' ) || isset( $_GET['q'] ) || isset( $_GET['vf_q'] ) || isset( $_GET['s'] ) ) {
        $query->set( 's', $filters['q'] );
    }

    if ( ! empty( $filters['context_cat'] ) ) {
        $tax_query   = $query->get( 'tax_query' );
        $tax_query   = is_array( $tax_query ) ? $tax_query : array();
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => array( (int) $filters['context_cat'] ),
            'operator' => 'IN',
        );
        $query->set( 'tax_query', $tax_query );
    }

    $tax_query = $query->get( 'tax_query' );
    if ( ! is_array( $tax_query ) ) {
        $tax_query = array();
    }

    $cat_ids = vf_ps_meta_category_ids( $filters['continent'], $filters['league'] );
    if ( $filters['club'] ) {
        $cat_ids = null === $cat_ids ? $filters['club'] : array_values( array_intersect( $cat_ids, $filters['club'] ) );
        if ( empty( $cat_ids ) ) {
            $cat_ids = array( 0 );
        }
    }

    if ( null !== $cat_ids ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'term_id',
            'terms'    => array_map( 'absint', $cat_ids ),
            'operator' => 'IN',
        );
    }

    if ( ! empty( $filters['nation'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $filters['nation'],
            'operator' => 'IN',
        );
    }

    if ( $filters['legend'] ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $filters['legend'],
            'operator' => 'IN',
        );
    }

    if ( ! empty( $filters['more'] ) ) {
        $tax_query[] = array(
            'taxonomy' => 'product_cat',
            'field'    => 'slug',
            'terms'    => $filters['more'],
            'operator' => 'IN',
        );
    }

    if ( count( $tax_query ) > 1 ) {
        $tax_query['relation'] = 'AND';
    }
    if ( $tax_query ) {
        $query->set( 'tax_query', $tax_query );
    }

    $meta_query = $query->get( 'meta_query' );
    if ( ! is_array( $meta_query ) ) {
        $meta_query = array();
    }

    if ( $filters['min'] || $filters['max'] ) {
        $min = $filters['min'] ? vf_ps_to_base_price( $filters['min'] ) : 0;
        $max = $filters['max'] ? vf_ps_to_base_price( $filters['max'] ) : vf_ps_catalog_max_price();
        $meta_query[] = array(
            'key'     => '_price',
            'value'   => array( $min, $max ),
            'compare' => 'BETWEEN',
            'type'    => 'DECIMAL(12,2)',
        );
    }

    if ( $meta_query ) {
        $query->set( 'meta_query', $meta_query );
    }

    switch ( $filters['orderby'] ) {
        case 'price':
            $query->set( 'meta_key', '_price' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'ASC' );
            break;
        case 'price-desc':
            $query->set( 'meta_key', '_price' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'DESC' );
            break;
        case 'title':
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'ASC' );
            break;
        case 'title-desc':
            $query->set( 'orderby', 'title' );
            $query->set( 'order', 'DESC' );
            break;
        case 'date-asc':
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'ASC' );
            break;
        case 'date':
            $query->set( 'orderby', 'date' );
            $query->set( 'order', 'DESC' );
            break;
        case 'popularity':
        default:
            $query->set( 'meta_key', 'total_sales' );
            $query->set( 'orderby', 'meta_value_num' );
            $query->set( 'order', 'DESC' );
            break;
    }
}

/**
 * @param WP_Query $query
 */
function vf_ps_pre_get_posts( $query ) {
    if ( ! vf_ps_is_catalog_query( $query ) ) {
        return;
    }
    vf_ps_apply_filters_to_query( $query );
}
add_action( 'pre_get_posts', 'vf_ps_pre_get_posts', 20 );

/**
 * Match a decade by the years written in the product title.
 *
 * @param string   $where
 * @param WP_Query $query
 * @return string
 */
function vf_ps_decade_where( $where, $query ) {
    if ( ! vf_ps_is_catalog_query( $query ) ) {
        return $where;
    }

    $filters = vf_ps_active_filters();
    $years   = vf_ps_decade_years( isset( $filters['decade'] ) ? $filters['decade'] : array() );
    if ( ! $years ) {
        return $where;
    }

    global $wpdb;
    $likes = array();
    foreach ( $years as $year ) {
        $likes[] = $wpdb->prepare( "{$wpdb->posts}.post_title LIKE %s", '%' . $wpdb->esc_like( $year ) . '%' );
    }
    $where .= ' AND (' . implode( ' OR ', $likes ) . ')';

    return $where;
}
add_filter( 'posts_where', 'vf_ps_decade_where', 20, 2 );

/**
 * Filters for the widget's own product loop.
 *
 * @param array $source
 * @return array
 */
function vf_ps_filters_from_source( $source ) {
    $list = function( $key ) use ( $source ) {
        if ( empty( $source[ $key ] ) ) {
            return array();
        }
        $raw   = is_array( $source[ $key ] ) ? $source[ $key ] : explode( ',', (string) $source[ $key ] );
        $clean = array();
        foreach ( $raw as $part ) {
            $part = sanitize_title( wp_unslash( $part ) );
            if ( '' !== $part ) {
                $clean[] = $part;
            }
        }
        return array_values( array_unique( $clean ) );
    };
    $ids = function( $key ) use ( $list ) {
        $out = array();
        foreach ( $list( $key ) as $part ) {
            $id = absint( $part );
            if ( $id ) {
                $out[] = $id;
            }
        }
        return $out;
    };

    $min = isset( $source['min_price'] ) ? (float) wp_unslash( $source['min_price'] ) : 0;
    $max = isset( $source['max_price'] ) ? (float) wp_unslash( $source['max_price'] ) : 0;

    return array(
        'q'           => isset( $source['q'] ) ? sanitize_text_field( wp_unslash( $source['q'] ) ) : '',
        'continent'   => $list( 'continent' ),
        'league'      => $list( 'league' ),
        'club'        => $ids( 'club' ),
        'nation'      => $list( 'nation' ),
        'legend'      => vf_ps_legend_slugs( $list( 'legend' ) ),
        'decade'      => $list( 'decade' ),
        'more'        => $list( 'more' ),
        'min'         => $min > 0 ? $min : 0,
        'max'         => $max > 0 ? $max : 0,
        'orderby'     => isset( $source['orderby'] ) ? sanitize_text_field( wp_unslash( $source['orderby'] ) ) : 'popularity',
        'context_cat' => isset( $source['context_cat'] ) ? absint( $source['context_cat'] ) : 0,
    );
}

/**
 * Run the filtered product query.
 *
 * @param array $filters
 * @param int   $paged
 * @return WP_Query
 */
function vf_ps_query_products( $filters, $paged, $per_page = 12 ) {
    $per_page = min( 48, max( 1, (int) $per_page ) );

    $GLOBALS['vf_ps_filters'] = $filters;
    $query = new WP_Query(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            's'              => $filters['q'],
            'paged'          => max( 1, (int) $paged ),
            'posts_per_page' => $per_page,
            'vf_ps_apply'    => 1,
        )
    );
    unset( $GLOBALS['vf_ps_filters'] );

    return $query;
}

/**
 * Render WooCommerce product cards and pagination.
 *
 * @param array $filters
 * @param int   $paged
 * @param array $layout
 * @return int Found posts.
 */
function vf_ps_render_loop( $filters, $paged, $layout = array() ) {
    $layout = wp_parse_args(
        $layout,
        array(
            'per_page' => 12,
            'columns'  => 3,
            'paginate' => true,
            'empty'    => __( 'No products found matching your search.', 'xstore-child' ),
        )
    );

    if ( ! function_exists( 'wc_get_template_part' ) ) {
        vf_ps_render_empty( __( 'WooCommerce is required to list products.', 'xstore-child' ) );
        return 0;
    }

    $query = vf_ps_query_products( $filters, $paged, $layout['per_page'] );
    $found = (int) $query->found_posts;

    echo '<div class="vf-ps-loop woocommerce">';

    if ( function_exists( 'wc_set_loop_prop' ) ) {
        wc_set_loop_prop( 'columns', max( 1, (int) $layout['columns'] ) );
        wc_set_loop_prop( 'name', 'vf_ps' );
        wc_set_loop_prop( 'is_shortcode', true );
        wc_set_loop_prop( 'total', $found );
        wc_set_loop_prop( 'total_pages', (int) $query->max_num_pages );
        wc_set_loop_prop( 'per_page', (int) $query->get( 'posts_per_page' ) );
        wc_set_loop_prop( 'current_page', max( 1, (int) $paged ) );
    }

    if ( $query->have_posts() ) {
        woocommerce_product_loop_start();
        while ( $query->have_posts() ) {
            $query->the_post();
            wc_get_template_part( 'content', 'product' );
        }
        woocommerce_product_loop_end();
        if ( ! empty( $layout['paginate'] ) ) {
            vf_ps_render_pagination( max( 1, (int) $paged ), (int) $query->max_num_pages );
        }
    } else {
        vf_ps_render_empty( $layout['empty'] );
    }

    wp_reset_postdata();
    echo '</div>';

    return $found;
}

/**
 * @param string $message
 */
function vf_ps_render_empty( $message ) {
    if ( '' === $message || 'No products found.' === $message ) {
        $message = __( 'No products found matching your search.', 'xstore-child' );
    }
    echo '<div class="vf-ps-empty">';
    echo '<svg class="vf-ps-empty-icon" viewBox="0 0 24 24" aria-hidden="true">';
    echo '<circle cx="11" cy="11" r="8"></circle>';
    echo '<line x1="21" y1="21" x2="16.65" y2="16.65"></line>';
    echo '<line x1="8" y1="11" x2="14" y2="11"></line>';
    echo '</svg>';
    echo '<h4 class="vf-ps-empty-title">' . esc_html__( 'No Products Found', 'xstore-child' ) . '</h4>';
    echo '<p class="vf-ps-empty-text">' . esc_html( $message ) . '</p>';
    echo '<button type="button" class="vf-ps-empty-reset">' . esc_html__( 'Reset Filters', 'xstore-child' ) . '</button>';
    echo '</div>';
}

/**
 * @param int $current
 * @param int $total
 */
function vf_ps_render_pagination( $current, $total ) {
    if ( $total <= 1 ) {
        return;
    }

    $pages = array();
    if ( $total <= 6 ) {
        for ( $i = 1; $i <= $total; $i++ ) {
            $pages[] = $i;
        }
    } else {
        $left  = max( 2, $current - 1 );
        $right = min( $total - 1, $current + 1 );
        if ( $current <= 3 ) {
            $left  = 2;
            $right = 3;
        } elseif ( $current >= $total - 2 ) {
            $left  = $total - 2;
            $right = $total - 1;
        }

        $pages[] = 1;
        if ( $left > 2 ) {
            $pages[] = 'dots';
        }
        for ( $i = $left; $i <= $right; $i++ ) {
            $pages[] = $i;
        }
        if ( $right < $total - 1 ) {
            $pages[] = 'dots';
        }
        $pages[] = $total;
    }

    echo '<nav class="vf-ps-pagination" aria-label="' . esc_attr__( 'Products pagination', 'xstore-child' ) . '">';
    if ( $current > 1 ) {
        echo '<button type="button" class="vf-ps-page vf-ps-page-arrow" data-page="' . esc_attr( $current - 1 ) . '" aria-label="' . esc_attr__( 'Previous page', 'xstore-child' ) . '">&lsaquo;</button>';
    }
    foreach ( $pages as $page ) {
        if ( 'dots' === $page ) {
            echo '<span class="vf-ps-page-dots" aria-hidden="true">&hellip;</span>';
            continue;
        }
        if ( (int) $page === $current ) {
            echo '<span class="vf-ps-page is-current" aria-current="page">' . esc_html( (string) $page ) . '</span>';
        } else {
            echo '<button type="button" class="vf-ps-page" data-page="' . esc_attr( $page ) . '">' . esc_html( (string) $page ) . '</button>';
        }
    }
    if ( $current < $total ) {
        echo '<button type="button" class="vf-ps-page vf-ps-page-arrow" data-page="' . esc_attr( $current + 1 ) . '" aria-label="' . esc_attr__( 'Next page', 'xstore-child' ) . '">&rsaquo;</button>';
    }
    echo '</nav>';
}

/**
 * AJAX product loop for the filter sidebar.
 */
function vf_ps_ajax_results() {
    check_ajax_referer( 'vf_ps_nonce', 'nonce' );

    $filters = vf_ps_filters_from_source( wp_unslash( $_POST ) );
    $paged   = isset( $_POST['page'] ) ? max( 1, absint( $_POST['page'] ) ) : 1;
    $layout  = array(
        'per_page' => isset( $_POST['per_page'] ) ? absint( $_POST['per_page'] ) : 12,
        'columns'  => isset( $_POST['columns'] ) ? absint( $_POST['columns'] ) : 3,
        'paginate' => ! isset( $_POST['paginate'] ) || 'yes' === sanitize_text_field( wp_unslash( $_POST['paginate'] ) ),
            'empty'    => isset( $_POST['empty'] ) ? sanitize_text_field( wp_unslash( $_POST['empty'] ) ) : __( 'No products found matching your search.', 'xstore-child' ),
    );

    ob_start();
    $found = vf_ps_render_loop( $filters, $paged, $layout );
    $html  = ob_get_clean();

    wp_send_json_success(
        array(
            'html'  => $html,
            'found' => $found,
        )
    );
}
add_action( 'wp_ajax_vf_product_results', 'vf_ps_ajax_results' );
add_action( 'wp_ajax_nopriv_vf_product_results', 'vf_ps_ajax_results' );

/**
 * @return array{clubs:WP_Term[], nations:WP_Term[], legends:WP_Term[], continents:array, leagues:array, more:WP_Term[]}
 */
function vf_ps_filter_choices() {
    $clubs_parent = get_term_by( 'slug', 'clubs', 'product_cat' );
    $clubs        = array();
    if ( $clubs_parent && ! is_wp_error( $clubs_parent ) ) {
        $clubs = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'child_of'   => (int) $clubs_parent->term_id,
                'hide_empty' => true,
                'orderby'    => 'count',
                'order'      => 'DESC',
            )
        );
        if ( is_wp_error( $clubs ) ) {
            $clubs = array();
        }
    }

    $nations_parent = get_term_by( 'slug', 'national-teams', 'product_cat' );
    $nations        = array();
    if ( $nations_parent && ! is_wp_error( $nations_parent ) ) {
        $nations = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'child_of'   => (int) $nations_parent->term_id,
                'hide_empty' => true,
                'orderby'    => 'count',
                'order'      => 'DESC',
            )
        );
        if ( is_wp_error( $nations ) ) {
            $nations = array();
        }
    }

    $legends_parent = get_term( 1943, 'product_cat' );
    if ( ! $legends_parent || is_wp_error( $legends_parent ) ) {
        $legends_parent = get_term_by( 'slug', 'legends', 'product_cat' );
    }
    $legends = array();
    if ( $legends_parent && ! is_wp_error( $legends_parent ) ) {
        $legends = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'child_of'   => (int) $legends_parent->term_id,
                'hide_empty' => true,
                'orderby'    => 'count',
                'order'      => 'DESC',
            )
        );
        if ( is_wp_error( $legends ) ) {
            $legends = array();
        }
    }

    $continents = array();
    $leagues    = array();
    $legend_root = ( $legends_parent && ! is_wp_error( $legends_parent ) ) ? (int) $legends_parent->term_id : 1943;
    $catalog_terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        )
    );
    if ( is_wp_error( $catalog_terms ) ) {
        $catalog_terms = array();
    }

    foreach ( $catalog_terms as $club ) {
        if ( (int) $club->term_id === $legend_root ) {
            continue;
        }
        $club_ancestors = array_map( 'intval', get_ancestors( $club->term_id, 'product_cat', 'taxonomy' ) );
        if ( in_array( $legend_root, $club_ancestors, true ) || 'legends' === $club->slug ) {
            continue;
        }

        $continent = vf_ps_term_meta_value( $club->term_id, 'prod_cat_continent' );
        $league    = vf_ps_term_meta_value( $club->term_id, 'prod_cat_league' );
        if ( $continent ) {
            if ( ! isset( $continents[ $continent ] ) ) {
                $continents[ $continent ] = 0;
            }
            $continents[ $continent ] += (int) $club->count;
        }
        if ( $league ) {
            if ( ! isset( $leagues[ $league ] ) ) {
                $leagues[ $league ] = array(
                    'count'      => 0,
                    'continents' => array(),
                );
            }
            $leagues[ $league ]['count'] += (int) $club->count;
            if ( $continent ) {
                $leagues[ $league ]['continents'][ $continent ] = true;
            }
        }
    }

    arsort( $continents, SORT_NUMERIC );
    uasort(
        $leagues,
        static function ( $a, $b ) {
            return (int) $b['count'] <=> (int) $a['count'];
        }
    );

    $more = vf_ps_more_categories();

    return compact( 'clubs', 'nations', 'legends', 'continents', 'leagues', 'more' );
}

/**
 * Leaf categories outside clubs, national teams, legends, and All Products.
 *
 * @return WP_Term[]
 */
function vf_ps_more_categories() {
    $exclude = array( 1498 );
    foreach ( array( 'clubs', 'national-teams', 'legends' ) as $slug ) {
        $root = get_term_by( 'slug', $slug, 'product_cat' );
        if ( $root && ! is_wp_error( $root ) ) {
            $exclude[] = (int) $root->term_id;
        }
    }
    $exclude[] = 1943;
    foreach ( $exclude as $root_id ) {
        $children = get_term_children( (int) $root_id, 'product_cat' );
        if ( ! is_wp_error( $children ) ) {
            $exclude = array_merge( $exclude, array_map( 'intval', $children ) );
        }
    }
    $exclude = array_values( array_unique( array_map( 'intval', $exclude ) ) );

    $terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
            'exclude'    => $exclude,
        )
    );
    if ( is_wp_error( $terms ) ) {
        return array();
    }

    $leaves = array();
    foreach ( $terms as $term ) {
        $children = get_terms(
            array(
                'taxonomy'   => 'product_cat',
                'parent'     => (int) $term->term_id,
                'hide_empty' => false,
                'fields'     => 'ids',
                'number'     => 1,
            )
        );
        if ( empty( $children ) || is_wp_error( $children ) ) {
            $leaves[] = $term;
        }
    }

    usort(
        $leaves,
        static function ( $a, $b ) {
            return (int) $b->count <=> (int) $a->count;
        }
    );

    return $leaves;
}

/**
 * @param string $name
 * @param string $value
 * @param string $label
 * @param int    $count
 * @param bool   $checked
 * @param array  $attrs
 */
function vf_ps_render_checkbox( $name, $value, $label, $count, $checked, $attrs = array() ) {
    $attr_html = '';
    foreach ( $attrs as $key => $attr_value ) {
        $attr_html .= ' ' . esc_attr( $key ) . '="' . esc_attr( $attr_value ) . '"';
    }
    ?>
    <label class="vf-ps-check">
        <input type="checkbox" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" data-label="<?php echo esc_attr( $label ); ?>" <?php checked( $checked ); ?> <?php echo $attr_html; ?> />
        <span class="vf-ps-check-box" aria-hidden="true"></span>
        <span class="vf-ps-check-label"><?php echo esc_html( $label ); ?></span>
        <span class="vf-ps-check-count"><?php echo esc_html( (string) (int) $count ); ?></span>
    </label>
    <?php
}

