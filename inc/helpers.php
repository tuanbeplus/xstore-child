<?php
/**
 * Helper and utility functions
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Redirect ?random-product requests to a randomly selected published product.
 */
function random_product_redirect() {
    if ( isset( $_GET['random-product'] ) ) {
        global $wpdb;

        $query = "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product' AND post_status = 'publish'";
        $product_ids = $wpdb->get_col( $query );

        if ( ! empty( $product_ids ) ) {
            shuffle( $product_ids );
            $random_id = $product_ids[0];

            wp_redirect( get_permalink( $random_id ) );
            exit;
        }
    }
}
add_action( 'template_redirect', 'random_product_redirect' );

/**
 * Currency data debugging / helper output function (via ?test=1).
 */
function get_data_currentcy() {
    if ( ! empty( $_GET['test'] ) ) {
        $currencies = get_option( 'woo_multi_currency_params' );
        if ( ! empty( $currencies ) ) {
            $country_cu = $currencies['currency'];
            $currency_symbols = array();
            $flag_symbols = array();
            $symbol_map = [
                "USD" => "$",   "EUR" => "€",   "GBP" => "£",   "JPY" => "¥",
                "KRW" => "₩",   "RUB" => "₽",   "INR" => "₹",   "TRY" => "₺",
                "THB" => "฿",   "VND" => "₫",   "AED" => "د.إ", "CAD" => "C$",
                "AUD" => "A$",  "NZD" => "NZ$", "CHF" => "CHF", "DKK" => "kr",
                "HKD" => "HK$", "NOK" => "NOK", "SEK" => "SEK", "SGD" => "SGD",
                "ZAR" => "R",   "PHP" => "₱",   "MXN" => "MX$", "BRL" => "R$",
                "ARS" => "ARS$","CLP" => "CLP$","COP" => "COP$","PEN" => "S/",
                "CZK" => "Kč",  "HUF" => "Ft",  "PLN" => "zł",  "ILS" => "₪",
                "NGN" => "₦",   "EGP" => "EGP", "TZS" => "TSh", "KES" => "KSh",
                "PKR" => "₨",   "BDT" => "৳",   "HNL" => "L",   "GYD" => "G$",
                "BZD" => "BZ$", "JMD" => "J$",  "TTD" => "TT$", "PAB" => "B/.",
                "GTQ" => "Q",   "XOF" => "CFA", "XPF" => "F",   "MAD" => "MAD",
                "GMD" => "D",   "MWK" => "MK",  "ZMW" => "ZMW", "UGX" => "UGX",
                "MZN" => "MZN", "BWP" => "BWP", "LSL" => "LSL", "SZL" => "SZL"
            ];

            $flag_map = [
                "USD" => "🇺🇸", "EUR" => "🇪🇺", "GBP" => "🇬🇧", "JPY" => "🇯🇵",
                "KRW" => "🇰🇷", "RUB" => "🇷🇺", "INR" => "🇮🇳", "TRY" => "🇹🇷",
                "THB" => "🇹🇭", "VND" => "🇻🇳", "AED" => "🇦🇪", "CAD" => "🇨🇦",
                "AUD" => "🇦🇺", "NZD" => "🇳🇿", "CHF" => "🇨🇭", "DKK" => "🇩🇰",
                "HKD" => "🇭🇰", "NOK" => "🇳🇴", "SEK" => "🇸🇪", "SGD" => "🇸🇬",
                "ZAR" => "🇿🇦", "PHP" => "🇵🇭", "MXN" => "🇲🇽", "BRL" => "🇧🇷",
                "ARS" => "🇦🇷", "CLP" => "🇨🇱", "COP" => "🇨🇴", "PEN" => "🇵🇪",
                "CZK" => "🇨🇿", "HUF" => "🇭🇺", "PLN" => "🇵🇱", "ILS" => "🇮🇱",
                "NGN" => "🇳🇬", "EGP" => "🇪🇬", "TZS" => "🇹🇿", "KES" => "🇰🇪",
                "PKR" => "🇵🇰", "BDT" => "🇧🇩", "HNL" => "🇭🇳", "GYD" => "🇬🇾",
                "BZD" => "🇧🇿", "JMD" => "🇯🇲", "TTD" => "🇹🇹", "PAB" => "🇵🇦",
                "GTQ" => "🇬🇹", "XOF" => "🇫🇷", "XPF" => "🇫🇷", "MAD" => "🇲🇦",
                "GMD" => "🇬🇲", "MWK" => "🇲🇼", "ZMW" => "🇿🇲", "UGX" => "🇺🇬",
                "MZN" => "🇲🇿", "BWP" => "🇧🇼", "LSL" => "🇱🇸", "SZL" => "🇸🇿"
            ];

            foreach ( $country_cu as $currency ) {
                if ( isset( $symbol_map[ $currency ] ) ) {
                    $currency_symbols[ $symbol_map[ $currency ] ] = $currency;
                }
                if ( isset( $flag_map[ $currency ] ) ) {
                    $flag_symbols[ $currency ] = $flag_map[ $currency ];
                }
            }

            if ( ! empty( $currency_symbols ) ) {
                ?>
                <div class="wrapper-header-currency">
                    <form>
                        <select id='currency-select' name="wmc-currency" aria-label="Select currency" onchange="this.form.submit()">
                            <?php foreach ( $currency_symbols as $key => $value ) : ?>
                                <option value="<?php echo esc_attr( $value ); ?>">
                                    <?php echo esc_html( $flag_symbols[ $value ] . ' ' . $key . ' ' . $value ); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </div>
                <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">
                <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
                <script>
                    jQuery(document).ready(function($) {
                        $('.wrapper-header-currency form select').select2({
                            width: '115px',
                            templateResult: formatCurrency,
                            templateSelection: formatCurrency
                        });

                        function formatCurrency(state) {
                            if (!state.id) {
                                return state.text;
                            }
                            return $('<span>' + state.text + '</span>');
                        }
                    });
                </script>
                <?php
            }
        }
    }
}

/**
 * ---------------------------------------------------------------------------
 * Product URL Issues scanner (duplicate titles & numbered slug suffixes)
 * Read-only: lists products that need manual title/slug updates.
 * Admin: Products > URL Issues
 * ---------------------------------------------------------------------------
 */

/**
 * Normalize a product title for duplicate comparison.
 *
 * @param string $title Raw title.
 * @return string
 */
function vf_normalize_product_title( $title ) {
    $title = html_entity_decode( wp_strip_all_tags( $title ), ENT_QUOTES, 'UTF-8' );
    $title = preg_replace( '/\s+/u', ' ', trim( $title ) );
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $title, 'UTF-8' ) : strtolower( $title );
}

/**
 * Scan all products for duplicate titles and numbered URL suffixes (-2, -3, ...).
 *
 * @param string $filter all | duplicate_title | slug_suffix
 * @return array {
 *     @type int   $scanned Total products scanned.
 *     @type array $groups  Groups of flagged products.
 * }
 */
function vf_scan_product_url_issues( $filter = 'all' ) {
    global $wpdb;

    $rows = $wpdb->get_results(
        "SELECT ID, post_title, post_name, post_status
         FROM {$wpdb->posts}
         WHERE post_type = 'product'
           AND post_status IN ( 'publish', 'draft', 'private', 'pending' )
         ORDER BY post_title ASC, ID ASC"
    );

    $result = array(
        'scanned' => is_array( $rows ) ? count( $rows ) : 0,
        'groups'  => array(),
    );

    if ( empty( $rows ) ) {
        return $result;
    }

    $by_title = array(); // normalized title => [ IDs ]
    $by_slug  = array(); // slug => ID
    $items    = array(); // ID => row

    foreach ( $rows as $row ) {
        $id            = (int) $row->ID;
        $items[ $id ]  = $row;
        $by_title[ vf_normalize_product_title( $row->post_title ) ][] = $id;
        if ( '' !== $row->post_name ) {
            $by_slug[ $row->post_name ] = $id;
        }
    }

    $flags      = array(); // ID => [ issues ]
    $group_keys = array(); // ID => group key
    $groups_ids = array(); // group key => [ IDs ]

    // A. Duplicate titles.
    foreach ( $by_title as $norm => $ids ) {
        if ( count( $ids ) > 1 && '' !== $norm ) {
            foreach ( $ids as $id ) {
                $flags[ $id ][]    = 'duplicate_title';
                $group_keys[ $id ] = 'title:' . $norm;
                $groups_ids[ 'title:' . $norm ][] = $id;
            }
        }
    }

    // B. Numbered slug suffix (-2 .. -99). Years such as -1998 are ignored.
    foreach ( $items as $id => $row ) {
        if ( ! preg_match( '/^(.+)-(\d{1,2})$/', (string) $row->post_name, $m ) ) {
            continue;
        }
        $base       = $m[1];
        $has_base   = isset( $by_slug[ $base ] );
        $is_dup     = isset( $flags[ $id ] ) && in_array( 'duplicate_title', $flags[ $id ], true );

        if ( ! $has_base && ! $is_dup ) {
            continue; // Looks like a legitimate trailing number.
        }

        $flags[ $id ][] = 'slug_suffix';

        if ( ! isset( $group_keys[ $id ] ) ) {
            $key               = 'slug:' . $base;
            $group_keys[ $id ] = $key;
            $groups_ids[ $key ][] = $id;

            // Include the base (un-suffixed) product for context.
            if ( $has_base && ! isset( $group_keys[ $by_slug[ $base ] ] ) ) {
                $base_id               = $by_slug[ $base ];
                $group_keys[ $base_id ] = $key;
                $groups_ids[ $key ][]  = $base_id;
            }
        }
    }

    $group_no = 0;
    foreach ( $groups_ids as $key => $ids ) {
        $ids       = array_values( array_unique( $ids ) );
        $group_rows = array();

        foreach ( $ids as $id ) {
            $issues = isset( $flags[ $id ] ) ? array_values( array_unique( $flags[ $id ] ) ) : array();

            if ( 'all' !== $filter && ! in_array( $filter, $issues, true ) ) {
                continue;
            }

            $terms = get_the_terms( $id, 'product_cat' );
            $cats  = ( ! empty( $terms ) && ! is_wp_error( $terms ) ) ? wp_list_pluck( $terms, 'name' ) : array();

            $group_rows[] = array(
                'id'         => $id,
                'title'      => $items[ $id ]->post_title,
                'slug'       => $items[ $id ]->post_name,
                'status'     => $items[ $id ]->post_status,
                'issues'     => $issues,
                'permalink'  => get_permalink( $id ),
                'edit_url'   => get_edit_post_link( $id, 'raw' ),
                'thumbnail'  => get_the_post_thumbnail_url( $id, 'thumbnail' ),
                'categories' => $cats,
            );
        }

        if ( count( $group_rows ) > 0 ) {
            $group_no++;
            $result['groups'][ $group_no ] = $group_rows;
        }
    }

    return $result;
}

/**
 * Register Products > URL Issues admin page.
 */
function vf_register_url_issues_page() {
    add_submenu_page(
        'edit.php?post_type=product',
        __( 'Product URL Issues', 'xstore-child' ),
        __( 'URL Issues', 'xstore-child' ),
        'manage_woocommerce',
        'vf-product-url-issues',
        'vf_render_url_issues_page'
    );
}
add_action( 'admin_menu', 'vf_register_url_issues_page' );

/**
 * Render the URL Issues report page.
 */
function vf_render_url_issues_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.', 'xstore-child' ) );
    }

    $allowed = array( 'all', 'duplicate_title', 'slug_suffix' );
    $filter  = isset( $_GET['issue'] ) ? sanitize_key( wp_unslash( $_GET['issue'] ) ) : 'all';
    if ( ! in_array( $filter, $allowed, true ) ) {
        $filter = 'all';
    }

    $scan     = vf_scan_product_url_issues( $filter );
    $groups   = $scan['groups'];
    $affected = 0;
    foreach ( $groups as $g ) {
        $affected += count( $g );
    }

    $base_url   = admin_url( 'edit.php?post_type=product&page=vf-product-url-issues' );
    $export_url = wp_nonce_url(
        admin_url( 'admin-post.php?action=vf_export_url_issues&issue=' . $filter ),
        'vf_export_url_issues'
    );
    $labels = array(
        'duplicate_title' => __( 'Duplicate title', 'xstore-child' ),
        'slug_suffix'     => __( 'Numbered URL suffix', 'xstore-child' ),
    );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Product URL Issues', 'xstore-child' ); ?></h1>
        <p>
            <?php
            printf(
                /* translators: 1: scanned, 2: groups, 3: affected */
                esc_html__( 'Scanned %1$d products. Found %2$d groups (%3$d products) to review.', 'xstore-child' ),
                (int) $scan['scanned'],
                count( $groups ),
                (int) $affected
            );
            ?>
        </p>

        <p>
            <a class="button <?php echo 'all' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( $base_url ); ?>"><?php esc_html_e( 'All issues', 'xstore-child' ); ?></a>
            <a class="button <?php echo 'duplicate_title' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'issue', 'duplicate_title', $base_url ) ); ?>"><?php esc_html_e( 'Duplicate title', 'xstore-child' ); ?></a>
            <a class="button <?php echo 'slug_suffix' === $filter ? 'button-primary' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'issue', 'slug_suffix', $base_url ) ); ?>"><?php esc_html_e( 'Numbered URL suffix (-2, -3...)', 'xstore-child' ); ?></a>
            <a class="button" href="<?php echo esc_url( $export_url ); ?>"><?php esc_html_e( 'Download CSV', 'xstore-child' ); ?></a>
        </p>

        <table class="widefat striped" style="max-width:1400px;">
            <thead>
                <tr>
                    <th style="width:60px;"><?php esc_html_e( 'Group', 'xstore-child' ); ?></th>
                    <th style="width:80px;"><?php esc_html_e( 'Image', 'xstore-child' ); ?></th>
                    <th style="width:60px;">ID</th>
                    <th><?php esc_html_e( 'Title', 'xstore-child' ); ?></th>
                    <th><?php esc_html_e( 'Slug', 'xstore-child' ); ?></th>
                    <th><?php esc_html_e( 'Categories', 'xstore-child' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'xstore-child' ); ?></th>
                    <th><?php esc_html_e( 'Issue', 'xstore-child' ); ?></th>
                    <th><?php esc_html_e( 'Links', 'xstore-child' ); ?></th>
                </tr>
            </thead>
            <tbody>
            <?php if ( empty( $groups ) ) : ?>
                <tr><td colspan="9"><?php esc_html_e( 'No issues found.', 'xstore-child' ); ?></td></tr>
            <?php else : ?>
                <?php foreach ( $groups as $group_no => $group_rows ) : ?>
                    <?php foreach ( $group_rows as $row ) : ?>
                        <tr>
                            <td><strong>#<?php echo (int) $group_no; ?></strong></td>
                            <td>
                                <?php if ( $row['thumbnail'] ) : ?>
                                    <img src="<?php echo esc_url( $row['thumbnail'] ); ?>" alt="" style="width:64px;height:64px;object-fit:cover;border-radius:4px;" loading="lazy">
                                <?php endif; ?>
                            </td>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo esc_html( $row['title'] ); ?></td>
                            <td><code><?php echo esc_html( $row['slug'] ); ?></code></td>
                            <td><?php echo esc_html( implode( ', ', $row['categories'] ) ); ?></td>
                            <td><?php echo esc_html( $row['status'] ); ?></td>
                            <td>
                                <?php
                                foreach ( $row['issues'] as $issue ) {
                                    echo '<span style="display:inline-block;margin:0 4px 4px 0;padding:2px 8px;border-radius:10px;background:#fcf0f1;color:#b32d2e;font-size:12px;">' . esc_html( isset( $labels[ $issue ] ) ? $labels[ $issue ] : $issue ) . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( $row['permalink'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'View', 'xstore-child' ); ?></a> |
                                <a href="<?php echo esc_url( $row['edit_url'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Edit', 'xstore-child' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}

/**
 * CSV export of the URL Issues report.
 */
function vf_export_url_issues_csv() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( esc_html__( 'You do not have permission to do this.', 'xstore-child' ) );
    }
    check_admin_referer( 'vf_export_url_issues' );

    $allowed = array( 'all', 'duplicate_title', 'slug_suffix' );
    $filter  = isset( $_GET['issue'] ) ? sanitize_key( wp_unslash( $_GET['issue'] ) ) : 'all';
    if ( ! in_array( $filter, $allowed, true ) ) {
        $filter = 'all';
    }

    $scan = vf_scan_product_url_issues( $filter );

    nocache_headers();
    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=product-url-issues-' . gmdate( 'Y-m-d' ) . '.csv' );

    $out = fopen( 'php://output', 'w' );
    fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM for Excel.
    fputcsv( $out, array( 'Group', 'ID', 'Title', 'Slug', 'Status', 'Categories', 'Issue', 'URL', 'Edit URL', 'Suggested title (Home/Away/Third)', 'Suggested slug' ) );

    foreach ( $scan['groups'] as $group_no => $group_rows ) {
        foreach ( $group_rows as $row ) {
            fputcsv(
                $out,
                array(
                    $group_no,
                    $row['id'],
                    $row['title'],
                    $row['slug'],
                    $row['status'],
                    implode( ' | ', $row['categories'] ),
                    implode( ', ', $row['issues'] ),
                    $row['permalink'],
                    $row['edit_url'],
                    '',
                    '',
                )
            );
        }
    }

    fclose( $out );
    exit;
}
add_action( 'admin_post_vf_export_url_issues', 'vf_export_url_issues_csv' );

/**
 * Cleanup Tool: Empty DATA of ACF 'list_categories_des' in wp_options.
 * Keeps the field key/definition intact so user can delete it manually in ACF.
 *
 * Trigger: ?action=empty_acf_cate_desc
 */
function vf_empty_acf_cate_desc_data() {
    if ( ! is_admin() || ! isset( $_GET['action'] ) || 'empty_acf_cate_desc' !== $_GET['action'] ) {
        return;
    }

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'Access denied. You do not have permission to perform this action.', 'xstore-child' ) );
    }

    global $wpdb;

    // 1. Update the ACF field value to empty array (preserves the field key)
    if ( function_exists( 'update_field' ) ) {
        update_field( 'list_categories_des', [], 'options' );
    } else {
        update_option( 'options_list_categories_des', 0 );
    }

    // 2. Delete all sub-row options created by repeater in wp_options
    // (Preserves '_options_list_categories_des' which holds the field key reference)
    $deleted_rows = $wpdb->query(
        "DELETE FROM {$wpdb->options} WHERE option_name LIKE 'options_list_categories_des_%'"
    );

    wp_die(
        '<div style="font-family:sans-serif;max-width:600px;margin:50px auto;padding:24px;background:#f0fdf4;border:1px solid #86efac;border-radius:8px;color:#166534;">' .
        '<h2 style="margin-top:0;">✅ ACF Data Emptied Successfully</h2>' .
        '<p>Dữ liệu của trường <strong>list_categories_des</strong> đã được xoá sạch khỏi database (' . (int) $deleted_rows . ' sub-field rows đã được dọn dẹp).</p>' .
        '<p style="color:#15803d;font-size:13px;">Key trường và cấu hình Field Group vẫn được giữ nguyên vẹn để bạn tự quản lý hoặc xoá trong trang ACF Field Groups tuỳ ý.</p>' .
        '<p><a href="' . esc_url( admin_url() ) . '" style="display:inline-block;background:#16a34a;color:#fff;padding:8px 16px;border-radius:6px;text-decoration:none;font-weight:600;">Về Trang Quản Trị</a></p>' .
        '</div>'
    );
}
add_action( 'admin_init', 'vf_empty_acf_cate_desc_data' );

