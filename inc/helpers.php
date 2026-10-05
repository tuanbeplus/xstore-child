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
