<?php
/**
 * Base-free WooCommerce permalinks.
 *
 * Replaces the active "Remove CPT base" and "Remove Taxonomy URL" plugins for
 * products and product categories. It is deliberately opt-in: keep it disabled
 * until both plugins have been deactivated and the collision audit is clean.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VF_BASE_FREE_PERMALINKS_OPTION', 'vf_base_free_permalinks_enabled' );

function vf_base_free_permalinks_enabled() {
    return (bool) get_option( VF_BASE_FREE_PERMALINKS_OPTION, false );
}

function vf_base_free_permalinks_plugin_active( $plugin_file ) {
    $active_plugins = (array) get_option( 'active_plugins', array() );
    return in_array( $plugin_file, $active_plugins, true );
}

/** Match plugin folders too, so a non-standard plugin folder name is still safe. */
function vf_base_free_permalinks_plugin_name_active( $needle ) {
    foreach ( (array) get_option( 'active_plugins', array() ) as $plugin_file ) {
        if ( false !== strpos( strtolower( $plugin_file ), strtolower( $needle ) ) ) {
            return true;
        }
    }
    return false;
}

function vf_base_free_permalinks_dependencies_active() {
    return array_filter(
        array(
            'Remove CPT base'      => vf_base_free_permalinks_plugin_active( 'remove-cpt-base/remove-cpt-base.php' ) || vf_base_free_permalinks_plugin_name_active( 'remove-cpt-base' ),
            'Remove Taxonomy URL'  => vf_base_free_permalinks_plugin_active( 'remove-taxonomy-url/remove-taxonomy-url.php' ) || vf_base_free_permalinks_plugin_name_active( 'remove-taxonomy-url' ),
        )
    );
}

/** Return the full, hierarchical product category path. */
function vf_base_free_product_cat_path( $term ) {
    $term = get_term( $term, 'product_cat' );
    if ( ! $term || is_wp_error( $term ) ) {
        return '';
    }

    $ancestors = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
    $slugs     = array();

    foreach ( $ancestors as $ancestor_id ) {
        $ancestor = get_term( $ancestor_id, 'product_cat' );
        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
            $slugs[] = $ancestor->slug;
        }
    }

    $slugs[] = $term->slug;
    return implode( '/', $slugs );
}

/** Find a category only when every segment matches its real parent. */
function vf_base_free_find_product_cat( $path ) {
    $slugs  = array_values( array_filter( explode( '/', trim( rawurldecode( $path ), '/' ) ) ) );
    $parent = 0;
    $term   = null;

    if ( empty( $slugs ) ) {
        return null;
    }

    foreach ( $slugs as $slug ) {
        $terms = get_terms( array(
            'taxonomy'   => 'product_cat',
            'slug'       => sanitize_title( $slug ),
            'parent'     => $parent,
            'hide_empty' => false,
            'number'     => 1,
        ) );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
            return null;
        }

        $term   = $terms[0];
        $parent = (int) $term->term_id;
    }

    return $term;
}

/** Remove singular/attachment query vars that would fight the resolved route. */
function vf_base_free_reset_query_vars( $query_vars ) {
    foreach ( array( 'attachment', 'attachment_id', 'pagename', 'page', 'p', 'page_id', 'name', 'product', 'error', 'preview' ) as $key ) {
        unset( $query_vars[ $key ] );
    }

    return $query_vars;
}

/**
 * Rewrite query vars at the earliest reliable WordPress filter. This is needed
 * for a category whose slug is also an attachment slug: core's attachment rule
 * otherwise wins before parse_request/pre_get_posts are able to normalize it.
 */
function vf_base_free_category_request_vars( $query_vars ) {
    if ( ! vf_base_free_permalinks_enabled() || is_admin() ) {
        return $query_vars;
    }

    $request = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );

    // Core sets WP::$request before this filter, but fall back to the resolved
    // attachment slug in case a caching/rewrite layer runs in a different order.
    if ( '' === $request && ! empty( $query_vars['attachment'] ) ) {
        $request = (string) $query_vars['attachment'];
    }

    if ( '' === $request || 0 === strpos( $request, 'product-category/' ) || 0 === strpos( $request, 'product/' ) ) {
        return $query_vars;
    }

    $path        = $request;
    $page_number = 0;
    if ( preg_match( '#^(.+)/page/([1-9][0-9]*)$#', $path, $matches ) ) {
        $path        = $matches[1];
        $page_number = (int) $matches[2];
    }

    // Pages retain priority; categories retain priority over attachments.
    if ( get_page_by_path( $path, OBJECT, 'page' ) ) {
        return $query_vars;
    }

    $term = vf_base_free_find_product_cat( $path );
    if ( ! $term ) {
        return $query_vars;
    }

    // Keep unrelated query vars (orderby, price filters, pagination, etc.).
    $query_vars = vf_base_free_reset_query_vars( $query_vars );
    $query_vars['product_cat'] = $term->slug;

    if ( $page_number ) {
        $query_vars['paged'] = $page_number;
    }

    return $query_vars;
}
add_filter( 'request', 'vf_base_free_category_request_vars', 0 );

/** Generate base-free links only once this module is explicitly enabled. */
function vf_base_free_product_permalink( $permalink, $post ) {
    if ( ! vf_base_free_permalinks_enabled() || 'product' !== $post->post_type ) {
        return $permalink;
    }
    return home_url( user_trailingslashit( $post->post_name ) );
}
add_filter( 'post_type_link', 'vf_base_free_product_permalink', 20, 2 );

function vf_base_free_product_cat_link( $termlink, $term, $taxonomy ) {
    if ( ! vf_base_free_permalinks_enabled() || 'product_cat' !== $taxonomy ) {
        return $termlink;
    }

    $path = vf_base_free_product_cat_path( $term );
    return $path ? home_url( user_trailingslashit( $path ) ) : $termlink;
}
add_filter( 'term_link', 'vf_base_free_product_cat_link', 20, 3 );

/**
 * Resolve short URLs before WordPress can mistake them for attachment URLs.
 * Categories have priority over a product; products only occupy one segment.
 */
function vf_base_free_resolve_request( $wp ) {
    if ( ! vf_base_free_permalinks_enabled() || is_admin() || wp_doing_ajax() ) {
        return;
    }

    $path = trim( (string) $wp->request, '/' );
    if ( '' === $path || 0 === strpos( $path, 'wp-' ) || in_array( $path, array( 'feed', 'robots.txt', 'llms.txt' ), true ) ) {
        return;
    }

    // WordPress represents category pagination as path/page/N.
    $page_number = 0;
    if ( preg_match( '#^(.+)/page/([1-9][0-9]*)$#', $path, $matches ) ) {
        $path        = $matches[1];
        $page_number = (int) $matches[2];
    }

    // Never steal a real Page. Attachments intentionally do not get this
    // protection: product categories must win against attachment slug clashes.
    if ( get_page_by_path( $path, OBJECT, 'page' ) ) {
        return;
    }

    $term = vf_base_free_find_product_cat( $path );
    if ( $term ) {
        $wp->query_vars = vf_base_free_reset_query_vars( $wp->query_vars );
        $wp->query_vars['product_cat'] = $term->slug;

        if ( $page_number ) {
            $wp->query_vars['paged'] = $page_number;
        }
        return;
    }

    // A product uses a single slug, preventing it from swallowing pages/routes.
    if ( $page_number || false !== strpos( $path, '/' ) ) {
        return;
    }

    $product = get_page_by_path( $path, OBJECT, 'product' );
    if ( $product && 'publish' === $product->post_status ) {
        $wp->query_vars = vf_base_free_reset_query_vars( $wp->query_vars );
        $wp->query_vars['post_type'] = 'product';
        $wp->query_vars['name']      = $product->post_name;
    }
}
add_action( 'parse_request', 'vf_base_free_resolve_request', 1 );

/**
 * Force base-free category requests into a taxonomy query before the main
 * query can resolve a same-slug media attachment (for example /kids/).
 */
function vf_base_free_override_attachment_collision( $query ) {
    if ( ! vf_base_free_permalinks_enabled() || is_admin() || ! $query->is_main_query() ) {
        return;
    }

    $request = trim( (string) ( $GLOBALS['wp']->request ?? '' ), '/' );
    if ( '' === $request ) {
        $request = (string) $query->get( 'attachment' );
    }
    if ( '' === $request || 0 === strpos( $request, 'product-category/' ) || 0 === strpos( $request, 'product/' ) ) {
        return;
    }

    if ( preg_match( '#^(.+)/page/([1-9][0-9]*)$#', $request, $matches ) ) {
        $request = $matches[1];
    }

    $term = vf_base_free_find_product_cat( $request );
    if ( ! $term ) {
        return;
    }

    // Clear attachment/singular vars, then query the intended taxonomy.
    foreach ( array( 'attachment', 'attachment_id', 'name', 'p', 'page_id', 'post_type' ) as $key ) {
        $query->set( $key, '' );
    }
    $query->set( 'product_cat', $term->slug );
    $query->is_attachment = false;
    $query->is_singular   = false;
    $query->is_tax        = true;
    $query->is_archive    = true;
}
add_action( 'pre_get_posts', 'vf_base_free_override_attachment_collision', 0 );

/** 301 WooCommerce's default base URLs to the one canonical short URL. */
function vf_base_free_redirect_default_urls() {
    if ( ! vf_base_free_permalinks_enabled() || is_admin() || wp_doing_ajax() || 'GET' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
        return;
    }

    global $wp;
    $request = trim( (string) $wp->request, '/' );

    if ( is_singular( 'product' ) && 0 === strpos( $request, 'product/' ) ) {
        wp_safe_redirect( get_permalink( get_queried_object_id() ), 301 );
        exit;
    }

    if ( is_tax( 'product_cat' ) && 0 === strpos( $request, 'product-category/' ) ) {
        $target = get_term_link( get_queried_object() );
        if ( ! is_wp_error( $target ) ) {
            wp_safe_redirect( $target, 301 );
            exit;
        }
    }
}
add_action( 'template_redirect', 'vf_base_free_redirect_default_urls', 0 );

/**
 * Rank Math builds sitemap rows directly from database permalinks. Replace
 * product/category locations with the filtered canonical URLs used on-site.
 */
function vf_base_free_rank_math_sitemap_entry( $url, $type, $object = null ) {
    if ( ! vf_base_free_permalinks_enabled() || ! is_array( $url ) ) {
        return $url;
    }

    // Rank Math may pass a WP object, a DB row, or only an ID depending on
    // sitemap type/version. Hydrate it through core before checking its type.
    if ( 'post' === $type ) {
        $post_id = is_object( $object ) ? (int) ( $object->ID ?? $object->id ?? 0 ) : (int) $object;
        $post    = $post_id ? get_post( $post_id ) : null;
        if ( $post && 'product' === $post->post_type ) {
            $url['loc'] = get_permalink( $post );
        }
    }

    if ( 'term' === $type ) {
        $term_id = is_object( $object ) ? (int) ( $object->term_id ?? $object->object_id ?? $object->id ?? 0 ) : (int) $object;
        $term    = $term_id ? get_term( $term_id ) : null;
        if ( $term && ! is_wp_error( $term ) && 'product_cat' === $term->taxonomy ) {
            $term_link = get_term_link( $term );
            if ( ! is_wp_error( $term_link ) ) {
                $url['loc'] = $term_link;
            }
        }
    }

    return $url;
}
add_filter( 'rank_math/sitemap/entry', 'vf_base_free_rank_math_sitemap_entry', 20, 3 );

/** Rank Math's product sitemap has a dedicated URL filter in some versions. */
function vf_base_free_rank_math_product_sitemap_url( $url, $post ) {
    if ( vf_base_free_permalinks_enabled() && $post && 'product' === get_post_type( $post ) ) {
        return get_permalink( $post );
    }
    return $url;
}
add_filter( 'rank_math/sitemap/xml_post_url', 'vf_base_free_rank_math_product_sitemap_url', 20, 2 );

/**
 * Rank Math serves sitemaps from a cached copy (database transient or file),
 * so freshly filtered URLs only appear after that cache is gone. While this
 * module is enabled we bypass the cache entirely: 146 URLs is cheap to build
 * and it keeps the sitemap consistent with the on-site canonical URLs.
 */
function vf_base_free_disable_sitemap_cache( $enabled ) {
    return vf_base_free_permalinks_enabled() ? false : $enabled;
}
add_filter( 'rank_math/sitemap/enable_caching', 'vf_base_free_disable_sitemap_cache' );

/** Clear Rank Math's sitemap caches (DB transients + generated XML files). */
function vf_base_free_clear_rank_math_sitemap_cache() {
    global $wpdb;

    $wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_rank_math_sitemap_%' OR option_name LIKE '_transient_timeout_rank_math_sitemap_%'" );

    if ( class_exists( '\RankMath\Sitemap\Cache' ) && method_exists( '\RankMath\Sitemap\Cache', 'invalidate_storage' ) ) {
        try {
            \RankMath\Sitemap\Cache::invalidate_storage();
        } catch ( \Throwable $e ) {
            // Cache API differs between Rank Math versions; file cleanup below covers it.
        }
    }

    $patterns = array(
        WP_CONTENT_DIR . '/uploads/rank-math-sitemap-cache/*.xml',
        WP_CONTENT_DIR . '/uploads/rank-math-sitemap-cache/*/*.xml',
        WP_CONTENT_DIR . '/cache/rank-math-sitemap/*.xml',
        WP_CONTENT_DIR . '/cache/rank-math/*.xml',
    );

    foreach ( $patterns as $pattern ) {
        foreach ( (array) glob( $pattern ) as $file ) {
            if ( is_file( $file ) ) {
                @unlink( $file );
            }
        }
    }
}

/** Admin screen: status, audit and explicit enable/disable control. */
function vf_base_free_permalinks_menu() {
    add_submenu_page( 'woocommerce', 'Base-free Permalinks', 'Base-free Permalinks', 'manage_options', 'vf-base-free-permalinks', 'vf_base_free_permalinks_page' );
}
add_action( 'admin_menu', 'vf_base_free_permalinks_menu', 99 );

function vf_base_free_permalinks_collision_count() {
    global $wpdb;
    return (int) $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->posts} x ON x.post_name = p.post_name AND x.ID != p.ID
         WHERE p.post_type = 'product' AND p.post_status = 'publish'
         AND x.post_status IN ('publish', 'inherit')
         AND x.post_type IN ('page', 'post', 'attachment', 'product')"
    );
}

function vf_base_free_permalinks_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    if ( isset( $_POST['vf_base_free_save'] ) ) {
        check_admin_referer( 'vf_base_free_save' );
        $enabled = ! empty( $_POST['enabled'] );
        if ( $enabled && vf_base_free_permalinks_dependencies_active() ) {
            add_settings_error( 'vf_base_free', 'plugins-active', 'Deactivate Remove CPT base and Remove Taxonomy URL before enabling this replacement.', 'error' );
        } else {
            update_option( VF_BASE_FREE_PERMALINKS_OPTION, $enabled ? 1 : 0 );
            flush_rewrite_rules();
            vf_base_free_clear_rank_math_sitemap_cache();
            add_settings_error( 'vf_base_free', 'saved', $enabled ? 'Base-free permalink replacement enabled. Rewrite rules flushed once.' : 'Replacement disabled. Rewrite rules flushed once.', 'updated' );
        }
    }
    $active_plugins = vf_base_free_permalinks_dependencies_active();
    $enabled        = vf_base_free_permalinks_enabled();
    $collisions     = vf_base_free_permalinks_collision_count();
    ?>
    <div class="wrap"><h1>Base-free WooCommerce Permalinks</h1><?php settings_errors( 'vf_base_free' ); ?>
    <p>Replacement for <strong>Remove CPT base</strong> (Products only) and <strong>Remove Taxonomy URL</strong> (Product categories only). Product tags are intentionally not replaced.</p>
    <table class="widefat striped" style="max-width:760px"><tbody>
    <tr><th>Module</th><td><strong style="color:<?php echo $enabled ? '#008a20' : '#b32d2e'; ?>"><?php echo $enabled ? 'Enabled' : 'Standby'; ?></strong></td></tr>
    <tr><th>Remove-base plugins</th><td><?php echo $active_plugins ? esc_html( implode( ', ', array_keys( $active_plugins ) ) . ' active — keep this module in Standby.' ) : 'Both are inactive.'; ?></td></tr>
    <tr><th>Product slug collisions</th><td><?php echo esc_html( $collisions ); ?> possible product/page/post/attachment collisions. Review before enabling.</td></tr>
    </tbody></table>
    <h2>Switch-over order</h2><ol><li>Keep this module in Standby and deactivate the two plugins.</li><li>Clear WP Rocket/Cloudflare cache.</li><li>Enable this module below; it flushes rules once.</li><li>Test products, category parents/children, pagination and old base URLs.</li></ol>
    <form method="post"><?php wp_nonce_field( 'vf_base_free_save' ); ?><label><input type="checkbox" name="enabled" value="1" <?php checked( $enabled ); ?>> Enable theme replacement</label><p><button class="button button-primary" name="vf_base_free_save" value="1">Save setting</button></p></form></div>
    <?php
}
