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

/**
 * SEO Footer / Homepage text block [vf_seo_block] or [block_seo]
 *
 * Usage:
 * [vf_seo_block]
 * [vf_seo_block title="Custom Title" max_height="200px"]
 */
function vf_seo_block_shortcode( $atts = array() ) {
    $atts = shortcode_atts( array(
        'title'      => 'Vintage Football Shop - The Home of Classic & Retro Football Shirts',
        'subtitle'   => 'The Home of the Collector. Explore our collection of 1,000+ authentic retro football shirts across the Premier League, Serie A, La Liga, Bundesliga, National Teams & Iconic Legends.',
        'max_height' => '180px',
    ), $atts, 'vf_seo_block' );

    ob_start();
    ?>
    <!-- Block SEO -->
    <div class="seo-wrapper">
        <div class="seo-title"><?php echo esc_html( $atts['title'] ); ?></div>
        <div class="seo-subtitle"><?php echo esc_html( $atts['subtitle'] ); ?></div>

        <div class="seo-scroll-container" style="max-height: <?php echo esc_attr( $atts['max_height'] ); ?>;">
            <div class="seo-scroll" style="height: <?php echo esc_attr( $atts['max_height'] ); ?>;">
                <p>Welcome to <strong>Vintage Football Shop</strong>, the ultimate destination for retro football shirts and classic soccer jerseys. Our extensive range of vintage football shirts spans decades of history, offering collectors and fans the chance to own a piece of football heritage. Whether you’re looking for a retro kit from your favourite club or a classic national team jersey, you’ll find it here.</p>

                <p>Explore our unmatched archive covering the world's most prestigious leagues and tournaments. From rare 1970s and 1980s classics to iconic 1990s and 2000s shirts, we bring football history back to life with premium quality fabrics, authentic stitch detailing, and era-accurate sponsor designs. Don't miss our exclusive <strong>Buy 2 Get 1 Free</strong> offer and enjoy fast worldwide shipping on all orders.</p>

                <h2>Popular Leagues &amp; Competitions:</h2>
                <div class="seo-links">
                    <a href="https://vintagefootball.shop/clubs/">Premier League</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/">La Liga</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/">Serie A</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/">Bundesliga</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/">Ligue 1</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/">World Cup Classics</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/">European Championship</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/">Champions League Classics</a>
                </div>

                <h2>Popular Clubs:</h2>
                <div class="seo-links">
                    <a href="https://vintagefootball.shop/clubs/real-madrid/">Real Madrid</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/barcelona/">Barcelona</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/man-united/">Manchester United</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/liverpool/">Liverpool</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/arsenal/">Arsenal</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/chelsea/">Chelsea</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/man-city/">Manchester City</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/ac-milan/">AC Milan</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/inter-milan/">Inter Milan</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/juventus/">Juventus</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/bayern/">Bayern Munich</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/borussia-dortmund/">Borussia Dortmund</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/psg/">Paris Saint-Germain</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/ajax/">Ajax</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/atletico-madrid/">Atletico Madrid</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/tottenham/">Tottenham</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/newcastle/">Newcastle United</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/as-roma/">AS Roma</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/napoli/">Napoli</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/boca-juniors/">Boca Juniors</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/celtic/">Celtic</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/benfica/">Benfica</a>
                </div>

                <h2>Popular National Teams:</h2>
                <div class="seo-links">
                    <a href="https://vintagefootball.shop/national-teams/brazil/">Brazil</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/argentina/">Argentina</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/france/">France</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/germany/">Germany</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/italy/">Italy</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/england/">England</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/portugal/">Portugal</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/spain/">Spain</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/netherlands/">Netherlands</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/japan/">Japan</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/colombia/">Colombia</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/mexico/">Mexico</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/nigeria/">Nigeria</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/morocco/">Morocco</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/scotland/">Scotland</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/wales/">Wales</a>
                </div>

                <h2>Iconic Legends:</h2>
                <div class="seo-links">
                    <a href="https://vintagefootball.shop/legends/messi/">Lionel Messi</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/cr7/">Cristiano Ronaldo</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/maradona/">Diego Maradona</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/ronaldo/">Ronaldo Nazário</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/ronaldinho/">Ronaldinho</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/zinedine-zidane/">Zinedine Zidane</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/david-beckham/">David Beckham</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/rooney/">Wayne Rooney</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/maldini/">Paolo Maldini</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/thierry-henry/">Thierry Henry</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/drogba/">Didier Drogba</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/andres-iniesta/">Andrés Iniesta</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/roberto-carlos/">Roberto Carlos</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/zlatan-ibrahimovic/">Zlatan Ibrahimović</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/gianluigi-buffon/">Gianluigi Buffon</a>
                </div>

                <h2>Shop By Category &amp; Offers:</h2>
                <div class="seo-links">
                    <a href="https://vintagefootball.shop/shop/">All Collections</a><span>|</span>
                    <a href="https://vintagefootball.shop/clubs/">Club Retro Shirts</a><span>|</span>
                    <a href="https://vintagefootball.shop/national-teams/">National Retro Kits</a><span>|</span>
                    <a href="https://vintagefootball.shop/legends/">Legends Shirts</a><span>|</span>
                    <a href="https://vintagefootball.shop/kids/">Kids Retro Kits</a><span>|</span>
                    <a href="https://vintagefootball.shop/shop/">Buy 2 Get 1 Free Deals</a>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'vf_seo_block', 'vf_seo_block_shortcode' );
add_shortcode( 'block_seo', 'vf_seo_block_shortcode' );
