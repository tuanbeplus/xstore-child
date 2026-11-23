<?php
/**
 * FAQ Schema Generator (FAQPage JSON-LD).
 *
 * Implements Option 2:
 * 1. Targeted Schema generation: looks specifically for containers or Elementor widgets with id="faq-content".
 * 2. Questions support <h2>, <h3>, or <h4> headings, followed by <p> content as accepted answers.
 * 3. Works automatically on:
 *    - Dedicated /faqs/ page (parses #faq-content, with fallback).
 *    - Any Elementor page/post where a Text Editor widget has CSS ID "faq-content".
 *    - Any Product Category where the description has <div id="faq-content">.
 * 4. Integrates cleanly with Rank Math's unified @graph, plus standalone fallback if Rank Math is inactive.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Extract the inner HTML of a container having id="faq-content".
 *
 * @param string $html Full HTML string.
 * @return string Content inside the #faq-content element, or empty string.
 */
function vf_extract_faq_container_html( $html ) {
    if ( empty( $html ) || false === stripos( $html, 'faq-content' ) ) {
        return '';
    }

    // Match starting from id="faq-content" up until the next Elementor widget, section, or footer.
    if ( preg_match( '/id=[\'"]faq-content[\'"][^>]*>(.*?)(?=<div\s+class=[\'"]elementor-element|<section|<footer|\Z)/is', $html, $matches ) ) {
        return $matches[1];
    }

    return '';
}

/**
 * Extract FAQ Question/Answer pairs from HTML markup.
 *
 * Scans for <h2>, <h3>, or <h4> headings as questions, followed by <p> content as answers.
 *
 * @param string $html HTML content to parse.
 * @return array Array of Schema.org Question objects.
 */
function vf_extract_faq_items_from_html( $html ) {
    if ( empty( $html ) || ! preg_match( '/<h[234][^>]*>/i', $html ) ) {
        return array();
    }

    // Split on <h2>, <h3>, or <h4> headings
    $parts = preg_split( '/<(h[234])[^>]*>/i', $html, -1, PREG_SPLIT_DELIM_CAPTURE );
    // $parts[0] is text before the first heading
    // Subsequent entries alternate: $parts[1] = tag (e.g. 'h2'), $parts[2] = content after tag, etc.

    $faqs = array();
    $total_parts = count( $parts );

    for ( $i = 1; $i < $total_parts; $i += 2 ) {
        $tag     = $parts[ $i ];
        $content = isset( $parts[ $i + 1 ] ) ? $parts[ $i + 1 ] : '';

        $sub = preg_split( "/<\/{$tag}>/i", $content, 2 );
        if ( count( $sub ) < 2 ) {
            continue;
        }

        $question_raw = trim( wp_strip_all_tags( $sub[0] ) );
        if ( empty( $question_raw ) ) {
            continue;
        }

        $answer_part = $sub[1];

        // Collect all <p> paragraphs or fall back to section text
        if ( preg_match_all( '/<p[^>]*>(.*?)<\/p>/is', $answer_part, $p_matches ) && ! empty( $p_matches[1] ) ) {
            $answer_clean = implode( ' ', array_map( 'trim', $p_matches[1] ) );
        } else {
            $answer_clean = trim( $answer_part );
        }

        // Clean extra whitespace and retain safe HTML tags permitted by Google
        $answer_clean = preg_replace( '/\s+/', ' ', $answer_clean );
        $answer_clean = trim( strip_tags( $answer_clean, '<a><b><strong><i><em><br>' ) );

        if ( ! empty( $answer_clean ) ) {
            $faqs[] = array(
                '@type'          => 'Question',
                'name'           => html_entity_decode( $question_raw, ENT_QUOTES, 'UTF-8' ),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => html_entity_decode( $answer_clean, ENT_QUOTES, 'UTF-8' ),
                ),
            );
        }
    }

    return $faqs;
}

/**
 * Return curated fallback FAQ items for the main /faqs/ page.
 *
 * @return array Array of Schema.org Question objects.
 */
function vf_get_default_faqs() {
    return array(
        array(
            '@type'          => 'Question',
            'name'           => 'How can I track my order?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'Once you place an order, you’ll receive an automated email with a tracking number within a few days. Use this number to track your order on our website’s <a href="/track-your-order/" rel="noopener" target="_blank">Track Your Order</a> page. If you haven’t received your tracking number within a week, please check your spam folder or contact us directly to resend it.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'How can I contact Vintage Football Shop?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'Email us at <a href="mailto:info@vintagefootball.shop" rel="noopener">info@vintagefootball.shop</a>. Our Customer Support team is available from 09:00 to 17:00 NZT, Monday through Friday, and we strive to respond within 48 hours, excluding weekends and public holidays.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'What should I do if I entered the wrong address?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'If you’ve entered an incorrect shipping address, please inform us immediately so we can update it before dispatch. Items returned to us due to incorrect addressing may incur a resending or restocking fee. Please double-check your address during checkout.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'What conditions are the shirts in?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'All shirts are new old stock and are typically in excellent condition however there may occasionally be some minor marks from storage.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'How should I care for my jersey?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'To maintain the quality of your Player Issue retro jersey, hand wash or use the delicates setting on your washing machine. Avoid heavy machine washing, especially if your jersey has printed names or numbers.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'What is the refund policy?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'Items can be returned for a full refund within 30 days of purchase if they are faulty, defective, or incorrect. Please contact our support for assistance with returns.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'Can I exchange my item?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'For exchanges, please email us at <a href="mailto:info@vintagefootball.shop">info@vintagefootball.shop</a>. Exchanges are possible if the item is in new condition with original tags and packaging.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'What are the letters on the shirts made from?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'The letters are embroidered onto the shirt with an industrial heat press and will be made with impeccable attention to detail featuring the same font and style of the era.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'What is the returns address?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'Please contact us at our support email before sending any returns, as the correct returns address can vary by country.',
            ),
        ),
        array(
            '@type'          => 'Question',
            'name'           => 'Will I have to pay customs fees?',
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text'  => 'Generally, all taxes and fees are included in the price of your order. However, in rare instances, you might be required to pay a customs fee upon delivery. Please check with your local customs office for specific details regarding international shipments.',
            ),
        ),
    );
}

/**
 * Get FAQ items for the current requested page or category.
 *
 * Scans targeted #faq-content block for H2, H3, or H4 questions.
 *
 * @return array Array of Schema.org Question objects.
 */
function vf_get_current_context_faqs() {
    // 1. Dedicated FAQ page (by slug 'faqs', 'faq', or ID 18649)
    if ( is_page( 'faqs' ) || is_page( 'faq' ) || is_page( 18649 ) ) {
        $post = get_post();
        if ( $post && ! empty( $post->post_content ) ) {
            // First check if target #faq-content ID exists
            $container_html = vf_extract_faq_container_html( $post->post_content );
            if ( ! empty( $container_html ) ) {
                $parsed = vf_extract_faq_items_from_html( $container_html );
                if ( ! empty( $parsed ) ) {
                    return $parsed;
                }
            }

            // Fallback: parse entire post content if ID not yet set in Elementor
            $parsed = vf_extract_faq_items_from_html( $post->post_content );
            if ( ! empty( $parsed ) ) {
                return $parsed;
            }
        }
        return vf_get_default_faqs();
    }

    // 2. Product Category archives: check ACF repeater 'category_faqs' or targeted #faq-content in descriptions
    if ( ( function_exists( 'is_product_category' ) && is_product_category() ) || is_tax( 'product_cat' ) ) {
        $term = get_queried_object();
        if ( $term && isset( $term->term_id ) ) {
            // First check ACF repeater 'category_faqs'
            if ( function_exists( 'get_field' ) ) {
                $acf_faqs = get_field( 'category_faqs', 'product_cat_' . $term->term_id );
                if ( ! empty( $acf_faqs ) && is_array( $acf_faqs ) ) {
                    $schema_items = array();
                    foreach ( $acf_faqs as $row ) {
                        $q = ! empty( $row['question'] ) ? trim( $row['question'] ) : '';
                        $a = ! empty( $row['answer'] ) ? trim( $row['answer'] ) : '';
                        if ( $q && $a ) {
                            $schema_items[] = array(
                                '@type'          => 'Question',
                                'name'           => html_entity_decode( $q, ENT_QUOTES, 'UTF-8' ),
                                'acceptedAnswer' => array(
                                    '@type' => 'Answer',
                                    'text'  => html_entity_decode( $a, ENT_QUOTES, 'UTF-8' ),
                                ),
                            );
                        }
                    }
                    if ( ! empty( $schema_items ) ) {
                        return $schema_items;
                    }
                }
            }

            $second_desc = get_term_meta( $term->term_id, '_et_second_description', true );
            if ( ! empty( $second_desc ) && false !== stripos( $second_desc, 'faq-content' ) ) {
                $container_html = vf_extract_faq_container_html( $second_desc );
                $target_html    = ! empty( $container_html ) ? $container_html : $second_desc;
                $parsed         = vf_extract_faq_items_from_html( $target_html );
                if ( ! empty( $parsed ) ) {
                    return $parsed;
                }
            }
            if ( ! empty( $term->description ) && false !== stripos( $term->description, 'faq-content' ) ) {
                $container_html = vf_extract_faq_container_html( $term->description );
                $target_html    = ! empty( $container_html ) ? $container_html : $term->description;
                $parsed         = vf_extract_faq_items_from_html( $target_html );
                if ( ! empty( $parsed ) ) {
                    return $parsed;
                }
            }
        }
    }

    // 3. Other pages/posts: strictly requires targeted #faq-content
    if ( is_singular() ) {
        $post = get_post();
        if ( $post && ! empty( $post->post_content ) && false !== stripos( $post->post_content, 'faq-content' ) ) {
            $container_html = vf_extract_faq_container_html( $post->post_content );
            if ( ! empty( $container_html ) ) {
                return vf_extract_faq_items_from_html( $container_html );
            }
        }
    }

    return array();
}

/**
 * Filter Rank Math JSON-LD graph to include FAQPage schema.
 *
 * @param array  $data   Rank Math JSON-LD array.
 * @param object $jsonld Rank Math JsonLD instance.
 * @return array
 */
function vf_add_faq_schema_to_rank_math( $data, $jsonld ) {
    $faqs = vf_get_current_context_faqs();
    if ( empty( $faqs ) ) {
        return $data;
    }

    $permalink = trailingslashit( home_url( '/' ) );
    if ( is_singular() ) {
        $permalink = trailingslashit( get_permalink() );
    } elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term_link = get_term_link( get_queried_object() );
        if ( ! is_wp_error( $term_link ) ) {
            $permalink = trailingslashit( $term_link );
        }
    }

    $data['FAQPage'] = array(
        '@type'      => 'FAQPage',
        '@id'        => $permalink . '#faq',
        'mainEntity' => $faqs,
    );

    return $data;
}
add_filter( 'rank_math/json_ld', 'vf_add_faq_schema_to_rank_math', 99, 2 );

/**
 * Fallback standalone FAQPage schema if Rank Math is not active.
 */
function vf_output_faq_schema_fallback() {
    if ( defined( 'RANK_MATH_VERSION' ) ) {
        return;
    }

    $faqs = vf_get_current_context_faqs();
    if ( empty( $faqs ) ) {
        return;
    }

    $permalink = trailingslashit( home_url( '/' ) );
    if ( is_singular() ) {
        $permalink = trailingslashit( get_permalink() );
    } elseif ( function_exists( 'is_product_category' ) && is_product_category() ) {
        $term_link = get_term_link( get_queried_object() );
        if ( ! is_wp_error( $term_link ) ) {
            $permalink = trailingslashit( $term_link );
        }
    }

    $schema = array(
        '@context'   => 'https://schema.org',
        '@type'      => 'FAQPage',
        '@id'        => $permalink . '#faq',
        'mainEntity' => $faqs,
    );

    echo "\n" . '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . '</script>' . "\n";
}
add_action( 'wp_head', 'vf_output_faq_schema_fallback', 50 );
