# XStore Child Theme — Vintage Football Shop

[![Theme Version](https://img.shields.io/badge/version-2.1.0-blue.svg)](style.css)
[![Parent Theme](https://img.shields.io/badge/parent-XStore-orange.svg)](https://xstore.8theme.com/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Ready-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777bb4.svg)](https://www.php.net/)
[![Sass](https://img.shields.io/badge/Sass-SCSS-cc6699.svg)](https://sass-lang.com/)

A modern, high-performance WooCommerce child theme built specifically for **Vintage Football Shop** on top of the [XStore](https://xstore.8theme.com/) parent theme. Engineered for football kit e-commerce, featuring modular architecture, custom variation button swatches, interactive size guides, AJAX product filters, automated shipment tracking workflows, and optimized assets.

---

## Table of Contents

- [Overview](#overview)
- [Architecture & Key Features](#architecture--key-features)
  - [1. Modular PHP Architecture (`inc/`)](#1-modular-php-architecture-inc)
  - [2. Modern SCSS & Stylesheet System (`assets/scss/`)](#2-modern-scss--stylesheet-system-assetsscss)
  - [3. Interactive Single Product Experience](#3-interactive-single-product-experience)
  - [4. Custom Elementor Widgets](#4-custom-elementor-widgets)
  - [5. Interactive Size Guide](#5-interactive-size-guide)
  - [6. Order Tracking & Shipped Status Automation](#6-order-tracking--shipped-status-automation)
  - [7. Dynamic Delivery Timeline (AJAX)](#7-dynamic-delivery-timeline-ajax)
  - [8. Kids Variation Generator Admin Tool](#8-kids-variation-generator-admin-tool)
  - [9. Performance Optimizations & Asset Control](#9-performance-optimizations--asset-control)
  - [10. Multi-Currency, Tracking & SEO](#10-multi-currency-tracking--seo)
- [Directory Structure](#directory-structure)
- [Shortcodes Reference](#shortcodes-reference)
- [Requirements & Dependencies](#requirements--dependencies)
- [Installation & Setup](#installation--setup)
- [Development Guidelines](#development-guidelines)
- [Credits & Author](#credits--author)

---

## Overview

- **Theme Name:** XStore Child (Vintage Football Shop)
- **Parent Theme:** `xstore`
- **Text Domain:** `xstore-child`
- **Author:** Beplus (Tuan Nguyen)

This child theme extends XStore without altering parent theme files, ensuring full upgrade compatibility. The codebase is organized into modular PHP components in `inc/`, modular SCSS partials in `assets/scss/`, and custom Elementor widgets.

---

## Architecture & Key Features

### 1. Modular PHP Architecture (`inc/`)
All backend logic previously located in `functions.php` and the deprecated `project-pack/` directory has been restructured into specialized modules:
- **`inc/helpers.php`**: Utility functions such as `random_product_redirect()` (random product via `?random-product`) and currency data helper.
- **`inc/hooks.php`**: Enqueuing of child theme scripts and styles with `filemtime` cache busting, front-page performance dequeuing, responsive image/lazy-loading filters, header tracking code, and admin bar/menu clutter reduction.
- **`inc/shortcodes.php`**: Shortcodes for currency selectors and dynamic category SEO descriptions.
- **`inc/woo.php`**: WooCommerce-specific filters, custom order statuses, shipping timeline AJAX handler, AggregateRating schema, Google Pay args, coupon label styling, Meta Pixel purchase tracking, PPOM show/hide logic, and the Size Guide tab.
- **`inc/regenerate-variations.php`**: Admin tool for batch-generating kids kit sizes and customization variations.

### 2. Modern SCSS & Stylesheet System (`assets/scss/`)
The styling architecture has been refactored from legacy CSS into modular SCSS partials compiled to `assets/css/main.css`:
- **`assets/scss/_common.scss`**: Design tokens, resets, typography, navigation, mobile drawer menu, cards, currency switcher dropdowns, and abandoned cart modal.
- **`assets/scss/_woo.scss`**: Category SEO descriptions (top & bottom), cart table, wishlist, checkout, edit-account, and user registration form styling.
- **`assets/scss/_single-product.scss`**: Single product layout, page headings, custom ACF ratings, variation swatches, PPOM fields, quantity inputs, shipping timeline, Size Guide tab & tables, related products, and responsive breakpoints.
- **`assets/scss/main.scss`**: Master entry stylesheet importing all partials.

### 3. Interactive Single Product Experience (`assets/js/main.js`)
- **Variation Swatch Button UI:**
  - Converts standard WooCommerce `<select>` variation dropdowns into interactive list button swatches (`.vf-attr-buttons`, `.vf-attr-btn`).
  - Keeps native select elements in sync, handling disabled/out-of-stock states and active selections seamlessly.
- **Size Guide Quick Link:**
  - Injects a direct "Size Guide" link with ruler icon adjacent to size attributes.
  - Automatically triggers the WooCommerce "Size Guide" tab and smoothly scrolls with a 100px sticky header offset.
- **URL Parameter Variation Auto-Matching:**
  - Automatically selects attributes when customers land via marketing URLs (e.g., `?size=XL` or `?customize=10%20Messi`).
  - Includes typo-tolerance, accent normalization, curly quote sanitization, and whitespace-independent matching.
- **PPOM Integration:**
  - Automatically reveals custom name and number input fields only when "Customize" is selected.

### 4. Custom Elementor Widgets
- **VF Product Categories Filters (`elementor/widgets/vf-product-categories-filters/`):**
  - Custom Elementor widget providing interactive category filters.
  - Supports AJAX product count calculation, active filter indicators, parent/child hierarchy toggles, hide-empty categories option, and responsive drawer support.

### 5. Interactive Size Guide
- **Dynamic Sizing Tab (`inc/woo.php`):**
  - Injects a dedicated **Size Guide** tab into the product tabs.
  - Context-aware sizing: displays **Children sizes (16–28)** with recommended age/height/weight for items in the `kids` category, and **Adult sizes (S–4XL)** for standard jerseys.
  - Interactive unit switcher between **Metric (CM / KG)** and **Imperial (Inches / LB)**.

### 6. Order Tracking & Shipped Status Automation
- **Custom Order Status:** Registers `wc-shipped` ("Shipped") with WooCommerce.
- **Automated Transition:** Hooks into `woocommerce_before_order_object_save`. When tracking numbers (`_wc_shipment_tracking_items`) are added to an order, the status updates automatically to `shipped` with an order note.

### 7. Dynamic Delivery Timeline (AJAX)
- Displays an estimated delivery timeline on single product pages:
  - **Ordered:** Current date
  - **Order Ready:** +1 to +2 days
  - **Delivered:** +10 to +12 days
- Rendered via AJAX (`get_order_dates`) to prevent cache staleness on cached product pages.

### 8. Kids Variation Generator Admin Tool
- Dedicated dashboard tool under **WP Admin &rarr; Regenerate Variations**.
- Batch-generates standard numeric sizes (16, 18, 20, 22, 24, 26, 28) and customization attributes across all kids kits with a real-time progress bar.

### 9. Performance Optimizations & Asset Control
- **Selective Dequeuing on Front Page (`inc/hooks.php`):**
  - Strips non-essential scripts and stylesheets on the homepage (cart scripts, smart coupon forms, swiper instances, PPOM assets) to maximize Core Web Vitals.
- **Asset Versioning:** Employs `filemtime()` for both `main.css` and `main.js` to ensure zero stale client-side cache after releases.
- **Image Optimizations:** Disables redundant `srcset` and native lazy loading where custom lazy-loading implementations take precedence.

### 10. Multi-Currency, Tracking & SEO
- **Select2 Currency Dropdown:** Enhanced currency switcher with country flags and native symbols via WooCommerce Multi-Currency.
- **Structured Data:** Generates JSON-LD `Product` and `AggregateRating` schema from ACF fields (`number_rating`, `count_rating`).
- **Meta Pixel Purchase Tracking:** Fires accurate purchase conversion events on the thank you page with server-side duplicate protection.
- **Category Descriptions:** Supports dual top and bottom category descriptions with mobile read-more toggle.

---

## Directory Structure

```text
xstore-child/
├── assets/
│   ├── css/
│   │   └── main.css                  # Compiled production stylesheet
│   ├── js/
│   │   └── main.js                   # Variation swatches, size guide trigger & URL auto-matcher
│   └── scss/
│       ├── _common.scss              # Global design tokens, navigation, footer, currency & modals
│       ├── _single-product.scss      # Single product layout, variation buttons, size guide & responsive
│       ├── _woo.scss                 # WooCommerce pages (category descs, cart, wishlist, checkout)
│       └── main.scss                 # Master SCSS entry file
├── elementor/
│   ├── ajax-filter.php               # AJAX filter endpoints for category widgets
│   ├── widgets-load.php              # Elementor widget loader
│   └── widgets/
│       └── vf-product-categories-filters/ # Custom Category Filter Elementor widget
├── inc/
│   ├── helpers.php                   # Utility functions & redirects
│   ├── hooks.php                     # Theme setup, enqueues, optimizations & admin bar cleanups
│   ├── regenerate-variations.php     # Batch variation regenerator tool for Kids kits
│   ├── shortcodes.php                # Currency & category description shortcodes
│   └── woo.php                       # WooCommerce hooks, order status, size guide, ratings & tracking
├── .gitignore                        # Git ignore rules for OS, IDEs, caches & logs
├── functions.php                     # Clean child theme entry point requiring inc/ modules
├── style.css                         # Child theme declaration header
├── screenshot.png                    # WordPress theme screenshot
└── README.md                         # Theme documentation
```

---

## Shortcodes Reference

| Shortcode | Description |
| :--- | :--- |
| `[currency_switcher]` | Outputs WooCommerce Multi-Currency switcher dropdown markup. |
| `[currency_selector]` | Enhanced currency selector with flag & symbol mapping initialized with Select2. |
| `[box_description_top_category]` | Top category description with mobile accordion toggle ("Read More / Read Less") from ACF Options. |
| `[box_description_bot_category]` | Bottom SEO category description from ACF Options. |

---

## Requirements & Dependencies

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher (PHP 8.0+ recommended)
- **Parent Theme:** [XStore Theme](https://xstore.8theme.com/)
- **SCSS Compiler Plugin:** [WP-SCSS](https://wordpress.org/plugins/wp-scss/) (automatically compiles SCSS on save inside WordPress)
- **Required / Recommended Plugins:**
  - [WP-SCSS](https://wordpress.org/plugins/wp-scss/)
  - [WooCommerce](https://wordpress.org/plugins/woocommerce/)
  - [Elementor](https://wordpress.org/plugins/elementor/)
  - [Advanced Custom Fields (ACF / ACF Pro)](https://www.advancedcustomfields.com/)
  - [PPOM for WooCommerce](https://wordpress.org/plugins/woocommerce-product-addon/)
  - [WooCommerce Shipment Tracking](https://woocommerce.com/products/shipment-tracking/)
  - [WooCommerce Multi-Currency](https://villatheme.com/extensions/woo-multi-currency/)

---

## Installation & Setup

1. **Install Theme:**
   - Ensure the parent `xstore` theme is installed in `/wp-content/themes/xstore`.
   - Place this child theme in `/wp-content/themes/xstore-child`.
2. **Activate:**
   - In WP Admin, navigate to **Appearance &rarr; Themes** and activate **XStore Child**.
3. **Configure WP-SCSS Plugin (Settings &rarr; WP-SCSS):**
   - **SCSS Location:** `/assets/scss/`
   - **CSS Location:** `/assets/css/`
   - **Compiling Mode:** `Compressed`
   - **Enqueuing:** Disabled (assets are enqueued manually in `inc/hooks.php` with `filemtime` cache busting).
4. **Configure Custom Fields (ACF):**
   - Ensure ACF fields for ratings (`number_rating`, `custom_rating`), header scripts (`vf_header_scripts`), and category descriptions (`list_categories_des`) are registered in ACF Options.
5. **Regenerate Variations (if applicable):**
   - If managing kids kit products, navigate to **WP Admin &rarr; Regenerate Variations** to generate uniform numeric sizes and customization attributes in batch.

---

## Development Guidelines

### Working with SCSS (via WP-SCSS Plugin)
Styles are structured into partials in `assets/scss/` and imported into `main.scss` via standard `@import` rules (compatible with `scssphp` used by WP-SCSS):
- Edit `_common.scss` for site-wide elements, typography, navigation, and global utilities.
- Edit `_woo.scss` for category archives, cart, checkout, and account pages.
- Edit `_single-product.scss` for single product pages, variation swatches, and size guide tables.

**Compilation Workflow:**
- The **WP-SCSS** plugin automatically detects changes and compiles `assets/scss/main.scss` &rarr; `assets/css/main.css` directly within WordPress on page refresh / save.
- *Note:* Keep `@import "common"; @import "woo"; @import "single-product";` syntax in `main.scss` for compatibility with `scssphp`.

*(Optional) External CLI compilation via Dart Sass:*
```bash
sass assets/scss/main.scss assets/css/main.css --no-source-map --style=compressed
```

### Working with PHP (`inc/`)
- Add general utilities to `inc/helpers.php`.
- Add asset enqueues, scripts, and theme actions to `inc/hooks.php`.
- Add new shortcodes to `inc/shortcodes.php`.
- Add WooCommerce filters, order logic, and template hooks to `inc/woo.php`.
- Keep `functions.php` as a clean master entry point that requires modules.

---

## Credits & Author

- **Author:** Beplus / Tuan Nguyen (<tuan.beplus@gmail.com>)
- **Repository:** [https://github.com/tuanbeplus/xstore-child](https://github.com/tuanbeplus/xstore-child)
- **Parent Theme:** XStore by [8theme](https://8theme.com/)
