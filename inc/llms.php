<?php
/**
 * Serve /llms.txt dynamically for AI models and search engines.
 *
 * Provides a structured summary of the store, core categories,
 * key policies, and canonical URLs adhering to the llmstxt.org standard.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Intercept request for /llms.txt and return Markdown specification.
 */
function vf_serve_llms_txt() {
    $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) $_SERVER['REQUEST_URI'] : '';
    $path        = parse_url( $request_uri, PHP_URL_PATH );

    if ( '/llms.txt' !== $path ) {
        return;
    }

    nocache_headers();
    header( 'Content-Type: text/plain; charset=utf-8' );
    header( 'X-Robots-Tag: index, follow' );

    $site_url = trailingslashit( home_url( '/' ) );

    $content = <<<TXT
# Vintage Football Shop

> Vintage Football Shop is an e-commerce specialist providing classic, retro, and vintage football shirts, club kits, national team jerseys, and legend shirts.

## Core Collections
- [All Collections]({$site_url}shop/): Complete catalog of retro football shirts.
- [Clubs]({$site_url}clubs/): Classic club shirts covering the Premier League, La Liga, Serie A, Bundesliga, and South American leagues.
- [National Teams]({$site_url}national-teams/): Historic World Cup and European Championship shirts from Brazil, Argentina, England, France, Germany, Spain, and more.
- [Legends]({$site_url}legends/): Retro player shirts celebrating football icons including Maradona, Messi, Cristiano Ronaldo, Zidane, Ronaldo Nazário, Beckham, and Maldini.
- [Kids Kits]({$site_url}kids/): Retro football kits tailored for younger supporters and children.

## Customer Policies & Support
- [Shipping Information]({$site_url}shipping/): Worldwide delivery times, processing windows, and carrier tracking.
- [Refunds & Returns]({$site_url}refunds/): 30-day return policy for eligible retro shirts.
- [Size Guide]({$site_url}sizing/): Comprehensive metric (cm/kg) and imperial (inches/lb) measurements for adult and youth fits.
- [Track Your Order]({$site_url}track-your-order/): Live order lookup tool.
- [Frequently Asked Questions]({$site_url}faqs/): Detailed Q&A on shirt care, order processing, and international delivery.
- [Customer Reviews]({$site_url}reviews/): Verified buyer feedback and shirt quality ratings.
TXT;

    echo trim( $content ) . "\n";
    exit;
}
add_action( 'init', 'vf_serve_llms_txt', 1 );
