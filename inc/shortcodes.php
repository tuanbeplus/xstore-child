<?php
/**
 * Custom Shortcodes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Currency switcher shortcode [currency_switcher]
 * Fallback to custom CURCY selector if WooPayments template tag is not available.
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
    return get_data_currency_shortcode();
}
add_shortcode( 'currency_switcher', 'custom_currency_switcher_shortcode' );

/**
 * Currency selector shortcode [currency_selector] for CURCY (WooCommerce Multi Currency)
 */
function get_data_currency_shortcode() {
    $currencies = get_option( 'woo_multi_currency_params' );
    if ( empty( $currencies ) || empty( $currencies['currency'] ) ) {
        return '';
    }

    $country_cu = $currencies['currency'];

    // Get current active currency from CURCY (Premium or Free), fallback to WooCommerce or cookie
    $current_currency = '';
    if ( class_exists( 'WOOMULTI_CURRENCY_Data' ) ) {
        $current_currency = WOOMULTI_CURRENCY_Data::get_ins()->get_current_currency();
    } elseif ( class_exists( 'WOOMULTI_CURRENCY_F_Data' ) ) {
        $current_currency = WOOMULTI_CURRENCY_F_Data::get_ins()->get_current_currency();
    } elseif ( ! empty( $_COOKIE['wmc_current_currency'] ) ) {
        $current_currency = sanitize_text_field( $_COOKIE['wmc_current_currency'] );
    } elseif ( function_exists( 'get_woocommerce_currency' ) ) {
        $current_currency = get_woocommerce_currency();
    }

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

    global $wp;
    $path   = ( isset( $wp->request ) && '' !== $wp->request ) ? user_trailingslashit( $wp->request ) : '/';
    $action = home_url( $path );

    $output  = '<div class="wrapper-header-currency">';
    $output .= '<form method="get" action="' . esc_url( $action ) . '">';
    foreach ( $_GET as $key => $value ) {
        if ( ! is_scalar( $key ) || 'wmc-currency' === (string) $key ) {
            continue;
        }
        $values = is_array( $value ) ? $value : array( $value );
        $name   = is_array( $value ) ? $key . '[]' : $key;
        foreach ( $values as $item ) {
            if ( is_array( $item ) ) {
                continue;
            }
            $output .= '<input type="hidden" name="' . esc_attr( $name ) . '" value="' . esc_attr( wp_unslash( $item ) ) . '" />';
        }
    }
    $output .= '<select id="currency-select" name="wmc-currency" aria-label="Select currency" onchange="this.form.submit()">';

    foreach ( $country_cu as $currency ) {
        $symbol   = isset( $symbol_map[ $currency ] ) ? $symbol_map[ $currency ] : $currency;
        $flag     = isset( $flag_map[ $currency ] ) ? $flag_map[ $currency ] : '';
        $selected = ( $current_currency === $currency ) ? ' selected="selected"' : '';
        $label    = trim( $flag . ' ' . $symbol . ' ' . $currency );

        $output .= '<option value="' . esc_attr( $currency ) . '"' . $selected . '>';
        $output .= esc_html( $label );
        $output .= '</option>';
    }

    $output .= '</select>';
    $output .= '</form>';
    $output .= '</div>';

    return $output;
}
add_shortcode( 'currency_selector', 'get_data_currency_shortcode' );

/**
 * Ensure category description content is properly wrapped in <p> tags.
 * If content does not have <p> tags, converts line breaks to paragraphs via wpautop.
 */
function vf_format_category_description( $content ) {
    if ( empty( $content ) ) {
        return '';
    }

    // If already contains <p> tags, keep as is
    if ( strpos( $content, '<p' ) !== false ) {
        return $content;
    }

    // Normalize line endings
    $content = str_replace( array( "\r\n", "\r" ), "\n", $content );

    // If paragraphs are separated by single newlines, promote to double newlines for wpautop
    if ( strpos( $content, "\n\n" ) === false && strpos( $content, "\n" ) !== false ) {
        $content = preg_replace( "/\n+/", "\n\n", $content );
    }

    return wpautop( $content );
}

/**
 * Category Top Description shortcode [box_description_top_category]
 */
function get_data_box_description_top_category() {
    $term    = get_queried_object();
    $term_id = isset( $term->term_id ) ? (int) $term->term_id : 0;

    if ( ! $term_id ) {
        return '';
    }

    $full_html = '';

    // Primary: Native WooCommerce category description
    if ( ! empty( $term->description ) ) {
        $full_html = $term->description;
    } else {
        $term_obj = get_term( $term_id, 'product_cat' );
        if ( ! empty( $term_obj->description ) ) {
            $full_html = $term_obj->description;
        }
    }

    if ( empty( $full_html ) ) {
        return '';
    }

    // Ensure <p> tags are always present
    $full_html = vf_format_category_description( $full_html );

    $plain_text = wp_strip_all_tags( $full_html );
    $word_count = str_word_count( $plain_text );
    $preview    = wp_trim_words( $plain_text, 40, '…' );

    ob_start();
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
    return ob_get_clean();
}
add_shortcode( 'box_description_top_category', 'get_data_box_description_top_category' );

/**
 * Category Bottom Description shortcode [box_description_bot_category]
 */
function get_data_box_description_bot_category() {
    $term    = get_queried_object();
    $term_id = isset( $term->term_id ) ? (int) $term->term_id : 0;

    if ( ! $term_id ) {
        return '';
    }

    // Primary: XStore native _et_second_description term meta
    $bot_html = get_term_meta( $term_id, '_et_second_description', true );

    if ( empty( $bot_html ) ) {
        return '';
    }

    // Ensure <p> tags are always present
    $bot_html = vf_format_category_description( $bot_html );

    ob_start();
    ?>
    <div class="wrapper-des-bottom-category">
        <?php echo $bot_html; ?>
    </div>
    <?php
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
        'max_height' => '200px',
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
                    <a href="https://vintagefootball.shop/clubs/dortmund/">Borussia Dortmund</a><span>|</span>
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

/**
 * Category FAQ Accordion shortcode [vf_category_faq] / [vf_category_faqs]
 *
 * Renders FAQs from ACF repeater field 'category_faqs' on product category pages.
 * Compatible with Rank Math FAQPage schema.
 *
 * Usage:
 * [vf_category_faq]
 * [vf_category_faq title="Frequently Asked Questions"]
 * [vf_category_faq category_id="1500"]
 */
function vf_category_faq_shortcode( $atts = array() ) {
    $atts = shortcode_atts( array(
        'title'       => __( 'Frequently Asked Questions', 'xstore-child' ),
        'category_id' => 0,
        'open_first'  => 'no',
    ), $atts, 'vf_category_faq' );

    $term_id = absint( $atts['category_id'] );
    if ( ! $term_id ) {
        if ( function_exists( 'is_product_category' ) && is_product_category() ) {
            $term = get_queried_object();
            if ( $term && isset( $term->term_id ) ) {
                $term_id = (int) $term->term_id;
            }
        } elseif ( is_tax( 'product_cat' ) ) {
            $term = get_queried_object();
            if ( $term && isset( $term->term_id ) ) {
                $term_id = (int) $term->term_id;
            }
        }
    }

    $faqs = array();
    if ( $term_id ) {
        // 1. Try ACF repeater field 'category_faqs'
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

        // 2. Fallback to direct term meta if ACF is inactive or get_field returns empty
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
    }

    if ( empty( $faqs ) ) {
        if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
            return '<div style="padding:24px;border:1px dashed #ccd0d4;background:#f9f9f9;text-align:center;color:#666;font-size:14px;border-radius:4px;">[VF Category FAQ Accordion &mdash; FAQs will appear here when configured for the active category]</div>';
        }
        return '';
    }

    ob_start();
    ?>
    <div id="faq-content" class="vf-cat-faq-wrapper">
        <?php if ( ! empty( $atts['title'] ) ) : ?>
            <h2 class="vf-cat-faq-title"><?php echo esc_html( $atts['title'] ); ?></h2>
        <?php endif; ?>
        <div class="vf-cat-faq-accordion" role="region" aria-label="<?php echo esc_attr( $atts['title'] ); ?>">
            <?php foreach ( $faqs as $i => $item ) : ?>
                <?php
                $is_open  = ( 0 === $i && 'yes' === $atts['open_first'] );
                $panel_id = 'vf-cat-faq-panel-' . $term_id . '-' . $i;
                $btn_id   = 'vf-cat-faq-btn-' . $term_id . '-' . $i;
                ?>
                <div class="vf-cat-faq-item <?php echo $is_open ? 'is-active' : ''; ?>">
                    <h3 class="vf-cat-faq-item-title">
                        <button
                            type="button"
                            class="vf-cat-faq-trigger"
                            id="<?php echo esc_attr( $btn_id ); ?>"
                            aria-expanded="<?php echo $is_open ? 'true' : 'false'; ?>"
                            aria-controls="<?php echo esc_attr( $panel_id ); ?>"
                        >
                            <span class="vf-cat-faq-q-text"><?php echo esc_html( $item['question'] ); ?></span>
                            <span class="vf-cat-faq-icon" aria-hidden="true">
                                <svg class="vf-icon-plus" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                                <svg class="vf-icon-minus" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                            </span>
                        </button>
                    </h3>
                    <div
                        id="<?php echo esc_attr( $panel_id ); ?>"
                        class="vf-cat-faq-panel"
                        role="region"
                        aria-labelledby="<?php echo esc_attr( $btn_id ); ?>"
                        <?php echo $is_open ? '' : 'style="display:none;"'; ?>
                    >
                        <div class="vf-cat-faq-answer">
                            <?php echo wpautop( wp_kses_post( $item['answer'] ) ); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode( 'vf_category_faq', 'vf_category_faq_shortcode' );
add_shortcode( 'vf_category_faqs', 'vf_category_faq_shortcode' );
