<?php
/**
 * Temporary Product Category Content Manager.
 *
 * Exports product-category context for a top-description audit, imports
 * approved top descriptions, and manages Category FAQs via ACF Repeater.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'VF_CATEGORY_CONTENT_MANAGER_SLUG', 'vf-category-content-manager' );

/**
 * Register the temporary WooCommerce submenu.
 */
function vf_category_content_manager_menu() {
    add_submenu_page(
        'woocommerce',
        __( 'Category Content & FAQs Audit', 'xstore-child' ),
        __( 'Category Content Audit', 'xstore-child' ),
        'manage_woocommerce',
        VF_CATEGORY_CONTENT_MANAGER_SLUG,
        'vf_category_content_manager_page'
    );
}
add_action( 'admin_menu', 'vf_category_content_manager_menu', 99 );

/**
 * Get a product category's saved top description.
 *
 * @param WP_Term $term Category term.
 * @return string
 */
function vf_category_content_manager_top_description( $term ) {
    return ( $term instanceof WP_Term ) ? (string) $term->description : '';
}

/**
 * Get a product category's ACF FAQs.
 *
 * @param int $term_id Category term ID.
 * @return array Array of array( 'question' => ..., 'answer' => ... ).
 */
function vf_category_content_manager_get_faqs( $term_id ) {
    $faqs = array();

    if ( function_exists( 'get_field' ) ) {
        $acf_faqs = get_field( 'category_faqs', 'product_cat_' . $term_id );
        if ( ! empty( $acf_faqs ) && is_array( $acf_faqs ) ) {
            foreach ( $acf_faqs as $row ) {
                $q = ! empty( $row['question'] ) ? trim( $row['question'] ) : '';
                $a = ! empty( $row['answer'] ) ? trim( $row['answer'] ) : '';
                if ( $q && $a ) {
                    $faqs[] = array(
                        'question' => $q,
                        'answer'   => $a,
                    );
                }
            }
        }
    }

    if ( empty( $faqs ) ) {
        $count = get_term_meta( $term_id, 'category_faqs', true );
        if ( is_numeric( $count ) && (int) $count > 0 ) {
            for ( $i = 0; $i < (int) $count; $i++ ) {
                $q = get_term_meta( $term_id, "category_faqs_{$i}_question", true );
                $a = get_term_meta( $term_id, "category_faqs_{$i}_answer", true );
                if ( ! empty( $q ) && ! empty( $a ) ) {
                    $faqs[] = array(
                        'question' => trim( $q ),
                        'answer'   => trim( $a ),
                    );
                }
            }
        }
    }

    return $faqs;
}

/**
 * Build export data for one product category.
 *
 * @param WP_Term $term Category term.
 * @return array<string, mixed>
 */
function vf_category_content_manager_export_record( $term ) {
    $ancestor_ids = array_reverse( get_ancestors( $term->term_id, 'product_cat', 'taxonomy' ) );
    $ancestors    = array();

    foreach ( $ancestor_ids as $ancestor_id ) {
        $ancestor = get_term( $ancestor_id, 'product_cat' );
        if ( $ancestor && ! is_wp_error( $ancestor ) ) {
            $ancestors[] = array(
                'term_id' => (int) $ancestor->term_id,
                'name'    => $ancestor->name,
                'slug'    => $ancestor->slug,
            );
        }
    }

    $parent      = $term->parent ? get_term( $term->parent, 'product_cat' ) : null;
    $term_link   = get_term_link( $term );
    $thumbnail   = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
    $image_url   = $thumbnail ? wp_get_attachment_image_url( $thumbnail, 'full' ) : '';
    $top_content = vf_category_content_manager_top_description( $term );
    $bottom      = (string) get_term_meta( $term->term_id, '_et_second_description', true );
    $faqs        = vf_category_content_manager_get_faqs( $term->term_id );

    return array(
        'term_id'                 => (int) $term->term_id,
        'name'                    => $term->name,
        'slug'                    => $term->slug,
        'url'                     => is_wp_error( $term_link ) ? '' : $term_link,
        'parent_id'               => (int) $term->parent,
        'parent_name'             => ( $parent && ! is_wp_error( $parent ) ) ? $parent->name : '',
        'ancestors'               => $ancestors,
        'depth'                   => count( $ancestors ),
        'product_count'           => (int) $term->count,
        'thumbnail_url'           => $image_url ? $image_url : '',
        'continent'               => (string) get_term_meta( $term->term_id, 'prod_cat_continent', true ),
        'league'                  => (string) get_term_meta( $term->term_id, 'prod_cat_league', true ),
        'top_description_html'    => $top_content,
        'bottom_description_html' => $bottom,
        'has_top_description'     => '' !== trim( wp_strip_all_tags( $top_content ) ),
        'has_bottom_description'  => '' !== trim( wp_strip_all_tags( $bottom ) ),
        'faq_count'               => count( $faqs ),
        'faqs'                    => $faqs,
    );
}

/**
 * Download category audit JSON file (Top Descriptions).
 */
function vf_category_content_manager_export() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to export category content.', 'xstore-child' ) );
    }

    check_admin_referer( 'vf_category_content_export' );

    $scope = isset( $_POST['scope'] ) ? sanitize_key( wp_unslash( $_POST['scope'] ) ) : 'missing_top';
    $valid_scopes = array( 'all', 'missing_top', 'has_top' );
    if ( ! in_array( $scope, $valid_scopes, true ) ) {
        $scope = 'missing_top';
    }

    $terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if ( is_wp_error( $terms ) ) {
        wp_die( esc_html( $terms->get_error_message() ) );
    }

    $categories = array();
    foreach ( $terms as $term ) {
        $record  = vf_category_content_manager_export_record( $term );
        $has_top = ! empty( $record['has_top_description'] );

        if ( ( 'missing_top' === $scope && $has_top ) || ( 'has_top' === $scope && ! $has_top ) ) {
            continue;
        }

        $categories[] = $record;
    }

    $payload = array(
        'export_version' => 1,
        'generated_at'   => current_time( 'c' ),
        'site_url'       => home_url( '/' ),
        'scope'          => $scope,
        'category_count' => count( $categories ),
        'categories'     => $categories,
    );

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=vf-product-category-audit-' . gmdate( 'Y-m-d-His' ) . '.json' );
    echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}
add_action( 'admin_post_vf_category_content_export', 'vf_category_content_manager_export' );

/**
 * Download category FAQs export JSON file.
 */
function vf_category_faqs_export() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to export category FAQs.', 'xstore-child' ) );
    }

    check_admin_referer( 'vf_category_faqs_export_nonce' );

    $terms = get_terms(
        array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => false,
            'orderby'    => 'name',
            'order'      => 'ASC',
        )
    );

    if ( is_wp_error( $terms ) ) {
        wp_die( esc_html( $terms->get_error_message() ) );
    }

    $categories = array();
    foreach ( $terms as $term ) {
        $faqs = vf_category_content_manager_get_faqs( $term->term_id );
        $categories[] = array(
            'term_id'       => (int) $term->term_id,
            'name'          => $term->name,
            'slug'          => $term->slug,
            'url'           => is_wp_error( get_term_link( $term ) ) ? '' : get_term_link( $term ),
            'product_count' => (int) $term->count,
            'faq_count'     => count( $faqs ),
            'faqs'          => $faqs,
        );
    }

    $payload = array(
        'export_version' => 1,
        'export_type'    => 'category_faqs',
        'generated_at'   => current_time( 'c' ),
        'site_url'       => home_url( '/' ),
        'category_count' => count( $categories ),
        'categories'     => $categories,
    );

    nocache_headers();
    header( 'Content-Type: application/json; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=vf-category-faqs-export-' . gmdate( 'Y-m-d-His' ) . '.json' );
    echo wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    exit;
}
add_action( 'admin_post_vf_category_faqs_export', 'vf_category_faqs_export' );

/**
 * AJAX handler: apply imported top descriptions with auto-backup.
 */
function vf_ajax_apply_category_content() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'xstore-child' ) ) );
    }

    check_ajax_referer( 'vf_category_content_import_nonce', 'nonce' );

    $raw_categories = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : '';
    $categories = is_array( $raw_categories ) ? $raw_categories : json_decode( $raw_categories, true );

    if ( ! is_array( $categories ) || empty( $categories ) ) {
        wp_send_json_error( array( 'message' => __( 'No valid category data received.', 'xstore-child' ) ) );
    }

    // Step 1: Create backup of all categories to be touched
    $backup_rows = array();
    foreach ( $categories as $item ) {
        $term_id = isset( $item['term_id'] ) ? absint( $item['term_id'] ) : 0;
        if ( ! $term_id ) {
            continue;
        }
        $term = get_term( $term_id, 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) {
            $backup_rows[] = array(
                'term_id'              => (int) $term->term_id,
                'slug'                 => $term->slug,
                'top_description_html' => (string) $term->description,
            );
        }
    }

    $uploads   = wp_upload_dir();
    $directory = trailingslashit( $uploads['basedir'] ) . 'vf-category-content-backups/';
    wp_mkdir_p( $directory );
    $backup_file = $directory . 'top-desc-before-import-' . gmdate( 'Y-m-d-His' ) . '.json';
    @file_put_contents(
        $backup_file,
        wp_json_encode(
            array(
                'backup_version' => 1,
                'created_at'     => current_time( 'c' ),
                'categories'     => $backup_rows,
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        )
    );

    // Step 2: Apply top descriptions (only if currently empty)
    $updated = 0;
    $skipped = 0;
    $details = array();

    foreach ( $categories as $item ) {
        $term_id = isset( $item['term_id'] ) ? absint( $item['term_id'] ) : 0;
        $slug    = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
        $content = isset( $item['top_description_html'] ) ? wp_kses_post( $item['top_description_html'] ) : '';

        if ( ! $term_id || '' === trim( wp_strip_all_tags( $content ) ) ) {
            $skipped++;
            continue;
        }

        $term = get_term( $term_id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) || ( '' !== $slug && $slug !== $term->slug ) ) {
            $skipped++;
            $details[] = "Term ID {$term_id}: invalid or slug mismatch (skipped)";
            continue;
        }

        // Safety: never overwrite existing top description
        if ( '' !== trim( wp_strip_all_tags( (string) $term->description ) ) ) {
            $skipped++;
            $details[] = "{$term->name}: already has top description (skipped)";
            continue;
        }

        $result = wp_update_term( $term_id, 'product_cat', array( 'description' => $content ) );
        if ( ! is_wp_error( $result ) ) {
            $updated++;
            clean_term_cache( $term_id, 'product_cat' );
            $details[] = "{$term->name}: updated";
        } else {
            $skipped++;
            $details[] = "{$term->name}: update failed - " . $result->get_error_message();
        }
    }

    wp_send_json_success(
        array(
            'updated' => $updated,
            'skipped' => $skipped,
            'backup'  => basename( $backup_file ),
            'details' => $details,
        )
    );
}
add_action( 'wp_ajax_vf_apply_category_content', 'vf_ajax_apply_category_content' );

/**
 * AJAX handler: apply imported category FAQs into ACF Repeater.
 */
function vf_ajax_apply_category_faqs() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Permission denied.', 'xstore-child' ) ) );
    }

    check_ajax_referer( 'vf_category_faqs_nonce', 'nonce' );

    $raw_categories = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : '';
    $categories = is_array( $raw_categories ) ? $raw_categories : json_decode( $raw_categories, true );

    if ( ! is_array( $categories ) || empty( $categories ) ) {
        wp_send_json_error( array( 'message' => __( 'No valid category FAQs data received.', 'xstore-child' ) ) );
    }

    // Step 1: Create backup of all existing FAQs
    $backup_rows = array();
    foreach ( $categories as $item ) {
        $term_id = isset( $item['term_id'] ) ? absint( $item['term_id'] ) : 0;
        if ( ! $term_id ) {
            continue;
        }
        $term = get_term( $term_id, 'product_cat' );
        if ( $term && ! is_wp_error( $term ) ) {
            $backup_rows[] = array(
                'term_id' => (int) $term->term_id,
                'slug'    => $term->slug,
                'name'    => $term->name,
                'faqs'    => vf_category_content_manager_get_faqs( $term->term_id ),
            );
        }
    }

    $uploads   = wp_upload_dir();
    $directory = trailingslashit( $uploads['basedir'] ) . 'vf-category-content-backups/';
    wp_mkdir_p( $directory );
    $backup_file = $directory . 'faqs-before-import-' . gmdate( 'Y-m-d-His' ) . '.json';
    @file_put_contents(
        $backup_file,
        wp_json_encode(
            array(
                'backup_version' => 1,
                'created_at'     => current_time( 'c' ),
                'categories'     => $backup_rows,
            ),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        )
    );

    // Step 2: Apply FAQs to each category
    $updated = 0;
    $skipped = 0;
    $details = array();

    // ACF Field Keys from group_6afa5fc01eeb9
    $repeater_key = 'field_6afa5fc238c1a'; // category_faqs
    $q_key        = 'field_6afa603d38c1b'; // question
    $a_key        = 'field_6afa607f38c1c'; // answer

    foreach ( $categories as $item ) {
        $term_id  = isset( $item['term_id'] ) ? absint( $item['term_id'] ) : 0;
        $slug     = isset( $item['slug'] ) ? sanitize_title( $item['slug'] ) : '';
        $raw_faqs = isset( $item['faqs'] ) && is_array( $item['faqs'] ) ? $item['faqs'] : array();

        if ( ! $term_id || empty( $raw_faqs ) ) {
            $skipped++;
            continue;
        }

        $term = get_term( $term_id, 'product_cat' );
        if ( ! $term || is_wp_error( $term ) || ( '' !== $slug && $slug !== $term->slug ) ) {
            $skipped++;
            $details[] = "Term ID {$term_id}: invalid or slug mismatch (skipped)";
            continue;
        }

        $formatted_faqs = array();
        foreach ( $raw_faqs as $faq_item ) {
            $q = ! empty( $faq_item['question'] ) ? trim( sanitize_text_field( $faq_item['question'] ) ) : '';
            $a = ! empty( $faq_item['answer'] ) ? trim( wp_kses_post( $faq_item['answer'] ) ) : '';
            if ( $q && $a ) {
                $formatted_faqs[] = array(
                    'question' => $q,
                    'answer'   => $a,
                );
            }
        }

        if ( empty( $formatted_faqs ) ) {
            $skipped++;
            $details[] = "{$term->name}: no valid Q&A pairs (skipped)";
            continue;
        }

        // Save via ACF update_field if available
        $saved = false;
        if ( function_exists( 'update_field' ) ) {
            $saved = update_field( $repeater_key, $formatted_faqs, 'product_cat_' . $term_id );
            if ( ! $saved ) {
                $saved = update_field( 'category_faqs', $formatted_faqs, 'product_cat_' . $term_id );
            }
        }

        // Also ensure direct term meta is saved accurately according to ACF repeater format
        update_term_meta( $term_id, 'category_faqs', count( $formatted_faqs ) );
        update_term_meta( $term_id, '_category_faqs', $repeater_key );

        foreach ( $formatted_faqs as $i => $faq ) {
            update_term_meta( $term_id, "category_faqs_{$i}_question", $faq['question'] );
            update_term_meta( $term_id, "_category_faqs_{$i}_question", $q_key );
            update_term_meta( $term_id, "category_faqs_{$i}_answer", $faq['answer'] );
            update_term_meta( $term_id, "_category_faqs_{$i}_answer", $a_key );
        }

        clean_term_cache( $term_id, 'product_cat' );
        $updated++;
        $details[] = "{$term->name}: updated " . count( $formatted_faqs ) . ' FAQs';
    }

    wp_send_json_success(
        array(
            'updated' => $updated,
            'skipped' => $skipped,
            'backup'  => basename( $backup_file ),
            'details' => $details,
        )
    );
}
add_action( 'wp_ajax_vf_apply_category_faqs', 'vf_ajax_apply_category_faqs' );

/**
 * Render the category content & FAQs management screen.
 */
function vf_category_content_manager_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    ?>
    <div class="wrap" style="max-width: 1080px;">
        <h1><?php esc_html_e( 'Category Content & FAQs Management', 'xstore-child' ); ?></h1>
        <p><?php esc_html_e( 'Temporary tool to audit category intros, import top descriptions, and populate Category FAQs into the ACF Repeater field.', 'xstore-child' ); ?></p>

        <!-- Navigation Tabs -->
        <h2 class="nav-tab-wrapper" style="margin-bottom: 25px;">
            <a href="#tab-faqs" class="nav-tab nav-tab-active" id="vf-nav-faqs">
                <?php esc_html_e( '1. Category FAQs (ACF Repeater)', 'xstore-child' ); ?>
            </a>
            <a href="#tab-top-desc" class="nav-tab" id="vf-nav-top-desc">
                <?php esc_html_e( '2. Category Top Descriptions', 'xstore-child' ); ?>
            </a>
        </h2>

        <div id="vf-status-box" style="display:none; margin: 15px 0;"></div>

        <!-- =========================================================================
             TAB 1: CATEGORY FAQS (ACF REPEATER)
             ========================================================================= -->
        <div id="vf-tab-content-faqs" class="vf-tab-pane">
            <h2><?php esc_html_e( 'Manage Category FAQs (ACF Repeater: category_faqs)', 'xstore-child' ); ?></h2>
            <p><?php esc_html_e( 'Preview and import structured Q&A pairs directly into the category_faqs ACF repeater for each category. Use the [vf_category_faq] shortcode in your Elementor template to render the accordion.', 'xstore-child' ); ?></p>

            <div style="display: flex; gap: 20px; margin-bottom: 25px; flex-wrap: wrap;">
                <!-- Export FAQs -->
                <div style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php esc_html_e( 'Export Category FAQs', 'xstore-child' ); ?></h3>
                    <p style="color:#666; font-size:13px;"><?php esc_html_e( 'Download a JSON file containing all existing FAQs configured in ACF across all categories.', 'xstore-child' ); ?></p>
                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                        <input type="hidden" name="action" value="vf_category_faqs_export">
                        <?php wp_nonce_field( 'vf_category_faqs_export_nonce' ); ?>
                        <?php submit_button( __( 'Export All FAQs JSON', 'xstore-child' ), 'secondary', 'submit', false ); ?>
                    </form>
                </div>

                <!-- Load & Preview FAQs -->
                <div style="flex: 1.5; min-width: 360px; background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                    <h3><?php esc_html_e( 'Load & Preview FAQs JSON', 'xstore-child' ); ?></h3>
                    <p style="color:#666; font-size:13px;"><?php esc_html_e( 'Select the JSON file (e.g. vf-category-faqs-import-2026-11-16.json) to preview questions and answers before saving.', 'xstore-child' ); ?></p>
                    <p>
                        <input type="file" id="vf-json-faq-file" accept=".json,application/json" style="margin-top: 4px;">
                    </p>
                    <p>
                        <button type="button" id="vf-btn-load-faqs" class="button button-primary">
                            <?php esc_html_e( 'Load & Preview FAQs', 'xstore-child' ); ?>
                        </button>
                    </p>
                </div>
            </div>

            <!-- Preview FAQs Table Section -->
            <div id="vf-faq-preview-section" style="display:none;">
                <hr style="margin: 25px 0;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <div>
                        <h3 style="margin:0 0 4px;"><?php esc_html_e( 'Preview FAQs to be Applied', 'xstore-child' ); ?></h3>
                        <p id="vf-faq-preview-summary" style="margin:0; font-weight:600; color:#1d2327;"></p>
                    </div>
                    <div>
                        <button type="button" id="vf-btn-apply-faqs" class="button button-primary button-hero" style="background:#008a20; border-color:#00731a;">
                            <?php esc_html_e( 'Update All FAQs into ACF', 'xstore-child' ); ?>
                        </button>
                        <span id="vf-faq-spinner" class="spinner" style="float: none; margin-left: 8px;"></span>
                    </div>
                </div>

                <div style="max-height: 480px; overflow-y: auto; border: 1px solid #ccd0d4; background: #fff; margin-bottom: 20px;">
                    <table class="widefat striped" id="vf-faq-preview-table">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th style="width: 60px;">ID</th>
                                <th style="width: 140px;">Slug</th>
                                <th style="width: 180px;">Category</th>
                                <th style="width: 70px;">FAQs</th>
                                <th>Questions &amp; Answers Preview</th>
                            </tr>
                        </thead>
                        <tbody id="vf-faq-preview-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- =========================================================================
             TAB 2: CATEGORY TOP DESCRIPTIONS
             ========================================================================= -->
        <div id="vf-tab-content-top-desc" class="vf-tab-pane" style="display:none;">
            <h2><?php esc_html_e( 'Category Top Descriptions (Native Description)', 'xstore-child' ); ?></h2>
            <p><?php esc_html_e( 'Audit and import top descriptions into WooCommerce native product_cat.description. Existing descriptions are protected and never overwritten.', 'xstore-child' ); ?></p>

            <div style="background:#fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 25px;">
                <h3><?php esc_html_e( 'Export Top Descriptions Audit JSON', 'xstore-child' ); ?></h3>
                <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                    <input type="hidden" name="action" value="vf_category_content_export">
                    <?php wp_nonce_field( 'vf_category_content_export' ); ?>
                    <p>
                        <label for="vf-category-content-scope"><strong><?php esc_html_e( 'Scope:', 'xstore-child' ); ?></strong></label>
                        <select id="vf-category-content-scope" name="scope">
                            <option value="missing_top"><?php esc_html_e( 'Categories missing Top Description', 'xstore-child' ); ?></option>
                            <option value="all"><?php esc_html_e( 'All Product Categories (159)', 'xstore-child' ); ?></option>
                            <option value="has_top"><?php esc_html_e( 'Categories with an existing Top Description', 'xstore-child' ); ?></option>
                        </select>
                    </p>
                    <?php submit_button( __( 'Download Top Descriptions Export', 'xstore-child' ), 'secondary', 'submit', false ); ?>
                </form>
            </div>

            <div style="background:#fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
                <h3><?php esc_html_e( 'Load & Preview Top Descriptions JSON', 'xstore-child' ); ?></h3>
                <p>
                    <input type="file" id="vf-json-file" accept=".json,application/json">
                </p>
                <p>
                    <button type="button" id="vf-btn-load" class="button button-primary">
                        <?php esc_html_e( 'Load & Preview Content', 'xstore-child' ); ?>
                    </button>
                </p>
            </div>

            <div id="vf-preview-section" style="display:none; margin-top: 25px;">
                <hr style="margin: 25px 0;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <div>
                        <h3 style="margin:0;"><?php esc_html_e( 'Preview Top Descriptions', 'xstore-child' ); ?></h3>
                        <p id="vf-preview-summary" style="margin:0; font-weight: 600; color: #1d2327;"></p>
                    </div>
                    <div>
                        <button type="button" id="vf-btn-apply" class="button button-primary button-hero" style="background:#008a20; border-color:#00731a;">
                            <?php esc_html_e( 'Apply Top Descriptions', 'xstore-child' ); ?>
                        </button>
                        <span id="vf-apply-spinner" class="spinner" style="float: none; margin-left: 8px;"></span>
                    </div>
                </div>

                <div style="max-height: 440px; overflow-y: auto; border: 1px solid #ccd0d4; background: #fff; margin-bottom: 20px;">
                    <table class="widefat striped" id="vf-preview-table">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th style="width: 80px;">ID</th>
                                <th style="width: 180px;">Slug</th>
                                <th>Top Description Preview</th>
                            </tr>
                        </thead>
                        <tbody id="vf-preview-tbody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var stagedTopDesc = [];
        var stagedFaqs = [];
        var topDescNonce = '<?php echo esc_js( wp_create_nonce( 'vf_category_content_import_nonce' ) ); ?>';
        var faqsNonce = '<?php echo esc_js( wp_create_nonce( 'vf_category_faqs_nonce' ) ); ?>';

        function showNotice(type, text) {
            var cssClass = (type === 'error') ? 'notice-error' : 'notice-success';
            $('#vf-status-box')
                .removeClass('notice-error notice-success')
                .addClass('notice ' + cssClass + ' is-dismissible')
                .html('<p>' + text + '</p>')
                .show();
            $('html, body').animate({ scrollTop: $('#vf-status-box').offset().top - 40 }, 200);
        }

        // Tab Switching
        $('.nav-tab-wrapper a').on('click', function(e) {
            e.preventDefault();
            $('.nav-tab-wrapper a').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');
            $('.vf-tab-pane').hide();

            if ($(this).attr('id') === 'vf-nav-faqs') {
                $('#vf-tab-content-faqs').show();
            } else {
                $('#vf-tab-content-top-desc').show();
            }
        });

        // ==========================================
        // FAQS TAB HANDLERS
        // ==========================================
        $('#vf-btn-load-faqs').on('click', function() {
            var fileInput = document.getElementById('vf-json-faq-file');
            var file = fileInput.files && fileInput.files[0];

            if (!file) {
                alert('<?php echo esc_js( __( 'Please select a JSON file first.', 'xstore-child' ) ); ?>');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var parsed = JSON.parse(e.target.result);
                    var items = parsed.categories || parsed;

                    if (!Array.isArray(items) || !items.length) {
                        showNotice('error', '<?php echo esc_js( __( 'No categories found in the JSON file.', 'xstore-child' ) ); ?>');
                        return;
                    }

                    stagedFaqs = items;
                    var $tbody = $('#vf-faq-preview-tbody').empty();
                    var totalFaqs = 0;
                    var catCount = 0;

                    items.forEach(function(cat, index) {
                        var faqs = cat.faqs || [];
                        if (faqs.length > 0) {
                            catCount++;
                            totalFaqs += faqs.length;
                        }

                        var faqPreviewHtml = '';
                        faqs.forEach(function(f, fi) {
                            var qText = f.question || '';
                            var aText = (f.answer || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                            if (aText.length > 100) aText = aText.substring(0, 100) + '…';

                            faqPreviewHtml += '<div style="margin-bottom:6px; padding-bottom:6px; border-bottom:1px solid #f0f0f0;">' +
                                '<strong style="color:#0073aa;">Q' + (fi + 1) + ': ' + $('<div>').text(qText).html() + '</strong><br>' +
                                '<span style="color:#555; font-size:12px;">' + $('<div>').text(aText).html() + '</span>' +
                                '</div>';
                        });

                        var rowHtml = '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td><code>' + (cat.term_id || '-') + '</code></td>' +
                            '<td><strong>' + (cat.slug || '-') + '</strong></td>' +
                            '<td>' + (cat.name ? $('<div>').text(cat.name).html() : '-') + '</td>' +
                            '<td><span class="badge" style="background:#0073aa;color:#fff;padding:2px 8px;border-radius:10px;font-weight:600;">' + faqs.length + '</span></td>' +
                            '<td>' + (faqPreviewHtml || '<em style="color:#888;">(No FAQs)</em>') + '</td>' +
                        '</tr>';
                        $tbody.append(rowHtml);
                    });

                    $('#vf-faq-preview-summary').text(catCount + ' categories with ' + totalFaqs + ' FAQs ready to apply.');
                    $('#vf-faq-preview-section').show();
                    $('#vf-status-box').hide();

                    $('html, body').animate({ scrollTop: $('#vf-faq-preview-section').offset().top - 20 }, 300);

                } catch (err) {
                    showNotice('error', '<?php echo esc_js( __( 'Failed to parse JSON file: ', 'xstore-child' ) ); ?>' + err.message);
                }
            };

            reader.readAsText(file);
        });

        $('#vf-json-faq-file').on('change', function() {
            if (this.files && this.files.length) {
                $('#vf-btn-load-faqs').trigger('click');
            }
        });

        $('#vf-btn-apply-faqs').on('click', function() {
            if (!stagedFaqs.length) {
                alert('<?php echo esc_js( __( 'No FAQs to apply.', 'xstore-child' ) ); ?>');
                return;
            }

            if (!confirm('<?php echo esc_js( __( 'Apply these FAQs to the ACF category_faqs repeater for all listed categories? A JSON backup will be created automatically.', 'xstore-child' ) ); ?>')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Applying FAQs...', 'xstore-child' ) ); ?>');
            $('#vf-faq-spinner').addClass('is-active');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'vf_apply_category_faqs',
                    nonce: faqsNonce,
                    categories: JSON.stringify(stagedFaqs)
                },
                success: function(res) {
                    $('#vf-faq-spinner').removeClass('is-active');

                    if (res.success && res.data) {
                        var d = res.data;
                        var msg = '<strong>' + '<?php echo esc_js( __( 'Success!', 'xstore-child' ) ); ?>' + '</strong> ' +
                            d.updated + ' <?php echo esc_js( __( 'categories updated with FAQs in ACF.', 'xstore-child' ) ); ?> ' +
                            d.skipped + ' <?php echo esc_js( __( 'skipped.', 'xstore-child' ) ); ?> ' +
                            '<?php echo esc_js( __( 'Backup saved: ', 'xstore-child' ) ); ?>' + (d.backup || '');

                        showNotice('success', msg);
                        $btn.text('<?php echo esc_js( __( 'FAQs Updated Successfully ✓', 'xstore-child' ) ); ?>');
                    } else {
                        var errMsg = (res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'Unknown error occurred.', 'xstore-child' ) ); ?>';
                        showNotice('error', errMsg);
                        $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Update All FAQs into ACF', 'xstore-child' ) ); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#vf-faq-spinner').removeClass('is-active');
                    showNotice('error', 'AJAX error: ' + (error || status));
                    $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Update All FAQs into ACF', 'xstore-child' ) ); ?>');
                }
            });
        });

        // ==========================================
        // TOP DESCRIPTIONS TAB HANDLERS
        // ==========================================
        $('#vf-btn-load').on('click', function() {
            var fileInput = document.getElementById('vf-json-file');
            var file = fileInput.files && fileInput.files[0];

            if (!file) {
                alert('<?php echo esc_js( __( 'Please select a JSON file first.', 'xstore-child' ) ); ?>');
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                try {
                    var parsed = JSON.parse(e.target.result);
                    var items = parsed.categories || parsed;

                    if (!Array.isArray(items) || !items.length) {
                        showNotice('error', '<?php echo esc_js( __( 'No categories found in the JSON file.', 'xstore-child' ) ); ?>');
                        return;
                    }

                    stagedTopDesc = items;
                    var $tbody = $('#vf-preview-tbody').empty();
                    var count = 0;

                    items.forEach(function(cat, index) {
                        var text = (cat.top_description_html || '').replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
                        var shortText = text.length > 140 ? text.substring(0, 140) + '…' : text;

                        if (shortText.length > 0) {
                            count++;
                        }

                        var rowHtml = '<tr>' +
                            '<td>' + (index + 1) + '</td>' +
                            '<td><code>' + (cat.term_id || '-') + '</code></td>' +
                            '<td><strong>' + (cat.slug || '-') + '</strong></td>' +
                            '<td>' + (shortText || '<em style="color:#888;">(Empty)</em>') + '</td>' +
                        '</tr>';
                        $tbody.append(rowHtml);
                    });

                    $('#vf-preview-summary').text(count + ' <?php echo esc_js( __( 'categories ready for Top Description update.', 'xstore-child' ) ); ?>');
                    $('#vf-preview-section').show();
                    $('#vf-status-box').hide();

                    $('html, body').animate({ scrollTop: $('#vf-preview-section').offset().top - 20 }, 300);

                } catch (err) {
                    showNotice('error', '<?php echo esc_js( __( 'Failed to parse JSON file: ', 'xstore-child' ) ); ?>' + err.message);
                }
            };

            reader.readAsText(file);
        });

        $('#vf-json-file').on('change', function() {
            if (this.files && this.files.length) {
                $('#vf-btn-load').trigger('click');
            }
        });

        $('#vf-btn-apply').on('click', function() {
            if (!stagedTopDesc.length) {
                alert('<?php echo esc_js( __( 'No categories to apply.', 'xstore-child' ) ); ?>');
                return;
            }

            if (!confirm('<?php echo esc_js( __( 'Apply Top Descriptions to these categories? Existing descriptions will be safely skipped.', 'xstore-child' ) ); ?>')) {
                return;
            }

            var $btn = $(this);
            $btn.prop('disabled', true).text('<?php echo esc_js( __( 'Applying updates...', 'xstore-child' ) ); ?>');
            $('#vf-apply-spinner').addClass('is-active');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'vf_apply_category_content',
                    nonce: topDescNonce,
                    categories: JSON.stringify(stagedTopDesc)
                },
                success: function(res) {
                    $('#vf-apply-spinner').removeClass('is-active');

                    if (res.success && res.data) {
                        var d = res.data;
                        var msg = '<strong>' + '<?php echo esc_js( __( 'Success!', 'xstore-child' ) ); ?>' + '</strong> ' +
                            d.updated + ' <?php echo esc_js( __( 'category descriptions updated.', 'xstore-child' ) ); ?> ' +
                            d.skipped + ' <?php echo esc_js( __( 'skipped.', 'xstore-child' ) ); ?> ' +
                            '<?php echo esc_js( __( 'Backup saved: ', 'xstore-child' ) ); ?>' + (d.backup || '');

                        showNotice('success', msg);
                        $btn.text('<?php echo esc_js( __( 'Applied Successfully ✓', 'xstore-child' ) ); ?>');
                    } else {
                        var errMsg = (res.data && res.data.message) ? res.data.message : '<?php echo esc_js( __( 'Unknown error occurred.', 'xstore-child' ) ); ?>';
                        showNotice('error', errMsg);
                        $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Apply Top Description Import', 'xstore-child' ) ); ?>');
                    }
                },
                error: function(xhr, status, error) {
                    $('#vf-apply-spinner').removeClass('is-active');
                    showNotice('error', 'AJAX error: ' + (error || status));
                    $btn.prop('disabled', false).text('<?php echo esc_js( __( 'Apply Top Description Import', 'xstore-child' ) ); ?>');
                }
            });
        });
    });
    </script>
    <?php
}
