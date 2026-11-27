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
 * Redirect WordPress attachment permalink pages to their original media file.
 *
 * Prevents attachment slugs such as /wayne-rooney-vintage-hero/ from serving
 * thin attachment pages while keeping the direct uploads URL accessible.
 */
function vf_redirect_attachment_permalink_to_file() {
    if ( ! is_attachment() ) {
        return;
    }

    $attachment_url = wp_get_attachment_url( get_queried_object_id() );

    if ( $attachment_url ) {
        wp_redirect( $attachment_url, 301 );
        exit;
    }
}
add_action( 'template_redirect', 'vf_redirect_attachment_permalink_to_file', 1 );

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
                "GTQ" => "🇬🇹", "XOF" => "🇸🇳", "XPF" => "🇵🇫", "MAD" => "🇲🇦",
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
                <?php
            }
        }
    }
}

/**
 * Build a srcset string from an attachment's registered sizes.
 *
 * The global srcset filter is disabled in inc/hooks.php, so templates that
 * need responsive images (blog cards, hero slider) build the candidate list
 * from the attachment metadata instead.
 *
 * @param int $attachment_id Attachment ID.
 * @param int $max_width     Largest candidate width to include (0 = no limit).
 * @return string Empty string when there are fewer than two candidates.
 */
function vf_image_srcset( $attachment_id, $max_width = 1600 ) {
    $attachment_id = (int) $attachment_id;

    if ( ! $attachment_id ) {
        return '';
    }

    $sizes      = array( 'medium', 'medium_large', 'large', '1536x1536', '2048x2048', 'full' );
    $candidates = array();

    foreach ( $sizes as $size ) {
        $src = wp_get_attachment_image_src( $attachment_id, $size );

        if ( empty( $src[0] ) || empty( $src[1] ) ) {
            continue;
        }

        if ( $max_width && (int) $src[1] > (int) $max_width ) {
            continue;
        }

        $candidates[ $src[0] ] = (int) $src[1];
    }

    if ( count( $candidates ) < 2 ) {
        return '';
    }

    asort( $candidates );

    $parts = array();

    foreach ( $candidates as $url => $width ) {
        $parts[] = $url . ' ' . $width . 'w';
    }

    return implode( ', ', $parts );
}
