<?php
/**
 * Custom Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currency switcher shortcode [currency_switcher]
 */
function custom_currency_switcher_shortcode() {
    if ( function_exists( 'wc_get_currency_switcher_markup' ) ) {
        $instance = array(
            'symbol' => true,
            'flag'   => true,
        );
        $args = array();
        return wc_get_currency_switcher_markup( $instance, $args );
    }
    return '';
}
add_shortcode( 'currency_switcher', 'custom_currency_switcher_shortcode' );

/**
 * Currency switcher select2 initialize on footer
 */
function custom_currency_switcher() {
    ?>
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
add_action( 'wp_footer', 'custom_currency_switcher', 998 );

/**
 * Currency selector shortcode [currency_selector]
 */
function get_data_currency_shortcode() {
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
            "HKD" => "🇭🇰", "NOK" => "NOK", "SEK" => "SEK", "SGD" => "🇸🇬",
            "ZAR" => "🇿🇦", "PHP" => "🇵🇭", "MXN" => "🇲🇽", "BRL" => "🇧🇷",
            "ARS" => "🇦🇷", "CLP" => "🇨🇱", "COP" => "🇨🇴", "PEN" => "🇵🇪",
            "CZK" => "🇨🇿", "HUF" => "🇭🇺", "PLN" => "🇵🇱", "ILS" => "🇮🇱",
            "NGN" => "₦",   "EGP" => "EGP", "TZS" => "TSh", "KES" => "KSh",
            "PKR" => "₨",   "BDT" => "৳",   "HNL" => "L",   "GYD" => "G$",
            "BZD" => "BZ$", "JMD" => "J$",  "TTD" => "TT$", "PAB" => "B/.",
            "GTQ" => "Q",   "XOF" => "🇫🇷", "XPF" => "🇫🇷", "MAD" => "🇲🇦",
            "GMD" => "🇬🇲", "MWK" => "🇲🇼", "ZMW" => "🇿🇲", "UGX" => "UGX",
            "MZN" => "🇲🇿", "BWP" => "🇧🇼", "LSL" => "🇱🇸", "SZL" => "SZL"
        ];

        foreach ( $country_cu as $currency ) {
            if ( isset( $symbol_map[ $currency ] ) ) {
                $currency_symbols[ $symbol_map[ $currency ] ] = $currency;
            }
        }

        foreach ( $country_cu as $currency ) {
            if ( isset( $flag_map[ $currency ] ) ) {
                $flag_symbols[ $currency ] = $flag_map[ $currency ];
            }
        }

        $output = '';
        if ( ! empty( $currency_symbols ) ) {
            $output .= '<div class="wrapper-header-currency">';
            $output .= '<form>';
            $output .= '<select id="currency-select" name="wmc-currency" aria-label="Select currency" onchange="this.form.submit()">';
            
            foreach ( $currency_symbols as $key => $value ) {
                $output .= '<option value="' . esc_attr( $value ) . '">' . $flag_symbols[ $value ] . ' ' . esc_html( $key . ' ' . $value ) . '</option>';
            }
            $output .= '</select>';
            $output .= '</form>';
            $output .= '</div>';
            
            $output .= '<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet">';
            $output .= '<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>';
            $output .= '<script>
                    jQuery(document).ready(function($) {
                        $(".wrapper-header-currency form select").select2({
                            width: "118px",
                            templateResult: formatCurrency,
                            templateSelection: formatCurrency
                        });

                        function formatCurrency(state) {
                            if (!state.id) {
                                return state.text;
                            }
                            return $("<span>" + state.text + "</span>");
                        }
                    });
                    document.addEventListener("DOMContentLoaded", function () {
                        const activeCurrency = document.querySelector(".wmc-currency.wmc-active");
                        if (activeCurrency) {
                            const currency = activeCurrency.getAttribute("data-currency");
                            if (currency) {
                                localStorage.setItem("selectedCurrency", currency);
                            }
                        }
                        const savedCurrency = localStorage.getItem("selectedCurrency");
                        if (savedCurrency) {
                            const selectEl = document.getElementById("currency-select");
                            if (selectEl) {
                                selectEl.value = savedCurrency;
                            }
                        }
                    });
                </script>';
        }
        return $output;
    }
    return '';
}
add_shortcode( 'currency_selector', 'get_data_currency_shortcode' );

/**
 * Category Top Description shortcode [box_description_top_category]
 */
function get_data_box_description_top_category() {
    ob_start();
    $list_category = get_field( 'list_categories_des', 'options' );
    $term = get_queried_object();
    $term_id = isset( $term->term_id ) ? $term->term_id : 0;

    if ( ! empty( $list_category ) && $term_id ) {
        foreach ( $list_category as $ids_cat ) {
            if ( ! empty( $ids_cat['category'] ) && in_array( $term_id, $ids_cat['category'], true ) ) {
                $full_html  = $ids_cat['top_description'];
                $plain_text = wp_strip_all_tags( $full_html );
                $word_count = str_word_count( $plain_text );
                $preview    = wp_trim_words( $plain_text, 40, '…' );
                ?>
                <div class="wrapper-des-top-category">
                  <!-- Desktop: full text -->
                  <div class="desktop-first">
                    <?php echo $full_html; ?>
                  </div>
                
                  <!-- Mobile: truncated with toggle -->
                  <div class="mobile-first">
                    <div class="preview-text">
                      <?php echo $preview; ?>
                    </div>
                    <?php if ( $word_count > 40 ) : ?>
                      <a href="#" class="toggle-more" style="font-weight:bold;">Read More</a>
                      <div class="full-text" style="display:none;">
                        <?php echo $full_html; ?>
                        <a href="#" class="toggle-less" style="font-weight:bold;">Read Less</a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                  document.querySelectorAll('.wrapper-des-top-category .mobile-first').forEach(function(container) {
                    var preview = container.querySelector('.preview-text');
                    var moreBtn = container.querySelector('.toggle-more');
                    var fullDiv = container.querySelector('.full-text');
                    var lessBtn = container.querySelector('.toggle-less');

                    if ( moreBtn ) {
                      moreBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        preview.style.display = 'none';
                        moreBtn.style.display = 'none';
                        fullDiv.style.display = 'block';
                      });
                    }
                    if ( lessBtn ) {
                      lessBtn.addEventListener('click', function(e) {
                        e.preventDefault();
                        fullDiv.style.display = 'none';
                        preview.style.display = 'block';
                        moreBtn.style.display = 'inline';
                      });
                    }
                  });
                });
                </script>
                <?php
                break;
            }
        }
    }
    return ob_get_clean();
}
add_shortcode( 'box_description_top_category', 'get_data_box_description_top_category' );

/**
 * Category Bottom Description shortcode [box_description_bot_category]
 */
function get_data_box_description_bot_category() {
    ob_start();
    $list_category = get_field( 'list_categories_des', 'options' );
    $term = get_queried_object();
    $term_id = isset( $term->term_id ) ? $term->term_id : 0;

    if ( ! empty( $list_category ) && $term_id ) {
        foreach ( $list_category as $ids_cat ) {
            if ( ! empty( $ids_cat['category'] ) && in_array( $term_id, $ids_cat['category'], true ) ) {
                ?>
                <div class="wrapper-des-bottom-category">
                    <?php echo $ids_cat['bottom_description']; ?>
                </div>
                <?php
                break;
            }
        }
    }
    return ob_get_clean();
}
add_shortcode( 'box_description_bot_category', 'get_data_box_description_bot_category' );
