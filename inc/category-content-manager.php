<?php
/**
 * Temporary Product Category Content Manager.
 *
 * Exports product-category context for a top-description audit and imports
 * approved top descriptions without overwriting existing category content.
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
        __( 'Category Content Audit', 'xstore-child' ),
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
    );
}

/**
 * Download the requested category audit JSON file.
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
    $backup_file = $directory . 'before-import-' . gmdate( 'Y-m-d-His' ) . '.json';
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
 * Render the temporary category-content manager screen.
 */
function vf_category_content_manager_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }
    ?>
    <div class="wrap" style="max-width: 1000px;">
        <h1><?php esc_html_e( 'Category Content Audit & Import', 'xstore-child' ); ?></h1>
        <p><?php esc_html_e( 'Temporary tool: export category context, preview descriptions locally, and apply updates. Existing descriptions are protected and never overwritten.', 'xstore-child' ); ?></p>

        <div id="vf-status-box" style="display:none; margin: 15px 0;"></div>

        <hr style="margin: 25px 0;">
        <h2><?php esc_html_e( '1. Export Category Audit JSON', 'xstore-child' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="vf_category_content_export">
            <?php wp_nonce_field( 'vf_category_content_export' ); ?>
            <p>
                <label for="vf-category-content-scope"><strong><?php esc_html_e( 'Export scope:', 'xstore-child' ); ?></strong></label>
                <select id="vf-category-content-scope" name="scope">
                    <option value="missing_top"><?php esc_html_e( 'Categories missing Top Description', 'xstore-child' ); ?></option>
                    <option value="all"><?php esc_html_e( 'All Product Categories (159)', 'xstore-child' ); ?></option>
                    <option value="has_top"><?php esc_html_e( 'Categories with an existing Top Description', 'xstore-child' ); ?></option>
                </select>
            </p>
            <?php submit_button( __( 'Download JSON Export', 'xstore-child' ), 'secondary', 'submit', false ); ?>
        </form>

        <hr style="margin: 25px 0;">
        <h2><?php esc_html_e( '2. Load & Preview JSON File', 'xstore-child' ); ?></h2>
        <p><?php esc_html_e( 'Choose the JSON import file below. It will be loaded and displayed in your browser instantly without reloading the page.', 'xstore-child' ); ?></p>

        <div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
            <p>
                <label for="vf-json-file"><strong><?php esc_html_e( 'Select JSON File:', 'xstore-child' ); ?></strong></label><br>
                <input type="file" id="vf-json-file" accept=".json,application/json" style="margin-top: 6px;">
            </p>
            <p>
                <button type="button" id="vf-btn-load" class="button button-primary">
                    <?php esc_html_e( 'Load & Preview Content', 'xstore-child' ); ?>
                </button>
            </p>
        </div>

        <div id="vf-preview-section" style="display:none; margin-top: 25px;">
            <hr style="margin: 25px 0;">
            <h2><?php esc_html_e( '3. Preview & Apply Updates', 'xstore-child' ); ?></h2>
            <p id="vf-preview-summary" style="font-weight: 600; font-size: 15px; color: #1d2327;"></p>

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

            <p>
                <button type="button" id="vf-btn-apply" class="button button-primary button-hero" style="background:#008a20; border-color:#00731a;">
                    <?php esc_html_e( 'Apply Top Description Import', 'xstore-child' ); ?>
                </button>
                <span id="vf-apply-spinner" class="spinner" style="float: none; margin-left: 10px;"></span>
            </p>
        </div>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var stagedCategories = [];
        var nonce = '<?php echo esc_js( wp_create_nonce( 'vf_category_content_import_nonce' ) ); ?>';

        function showNotice(type, text) {
            var cssClass = (type === 'error') ? 'notice-error' : 'notice-success';
            $('#vf-status-box')
                .removeClass('notice-error notice-success')
                .addClass('notice ' + cssClass + ' is-dismissible')
                .html('<p>' + text + '</p>')
                .show();
            $('html, body').animate({ scrollTop: $('#vf-status-box').offset().top - 40 }, 200);
        }

        // File selection / Load button
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

                    stagedCategories = items;

                    // Render Preview Table
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

        // Also trigger load automatically when a file is selected
        $('#vf-json-file').on('change', function() {
            if (this.files && this.files.length) {
                $('#vf-btn-load').trigger('click');
            }
        });

        // Apply Button Click
        $('#vf-btn-apply').on('click', function() {
            if (!stagedCategories.length) {
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
                    nonce: nonce,
                    categories: JSON.stringify(stagedCategories)
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
