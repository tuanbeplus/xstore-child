# XStore Child Theme — Vintage Football Shop

[![Theme Version](https://img.shields.io/badge/version-2.0.0-blue.svg)](style.css)
[![Parent Theme](https://img.shields.io/badge/parent-XStore-orange.svg)](https://xstore.8theme.com/)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Ready-96588a.svg)](https://woocommerce.com/)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777bb4.svg)](https://www.php.net/)

A custom WooCommerce child theme built specifically for **Vintage Football Shop** on top of the [XStore](https://xstore.8theme.com/) parent theme. Tailored for football jersey e-commerce, offering custom attribute matching, automated shipment tracking workflows, modular performance optimization, custom sizing charts, and bulk variation management.

---

## Table of Contents

- [Overview](#overview)
- [Key Features](#key-features)
  - [1. E-Commerce & Product Customization](#1-e-commerce--product-customization)
  - [2. Interactive Size Guide](#2-interactive-size-guide)
  - [3. Order Tracking & Shipped Status Automation](#3-order-tracking--shipped-status-automation)
  - [4. Dynamic Delivery Timeline (AJAX)](#4-dynamic-delivery-timeline-ajax)
  - [5. Kids Variation Generator Admin Tool](#5-kids-variation-generator-admin-tool)
  - [6. Performance Optimizations & Asset Control](#6-performance-optimizations--asset-control)
  - [7. Multi-Currency & Category SEO Descriptions](#7-multi-currency--category-seo-descriptions)
  - [8. Admin UX & Clutter Cleanup](#8-admin-ux--clutter-cleanup)
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

This child theme extends XStore without altering parent files, ensuring full upgrade compatibility. It modularizes custom storefront behaviors, custom post-purchase tracking, asset optimization, and custom variation handling.

---

## Key Features

### 1. E-Commerce & Product Customization
- **URL Parameter Variation Auto-Selection (`assets/js/script.js`):**
  - Automatically selects variation attributes when visitors land on product pages via marketing campaigns (e.g., `?attr_customize=10%20Messi` or `?size=L`).
  - Handles variations with typo-tolerance (`cutomize` / `customize`), accent normalization (e.g., `é` &rarr; `e`), curly quote normalization, and whitespace-independent matching (`10Messi` &rarr; `10 Messi`).
  - Smooth scrolls to the variation selection form once matched.
- **PPOM Integration:**
  - Automatically hides/shows custom name & number input fields based on whether the customer selects "Customize" or "No customize".

### 2. Interactive Size Guide
- **Dynamic Sizing Tab (`functions.php`):**
  - Appends a **Size Guide** tab directly to the WooCommerce single product tabs.
  - Automatically detects category: renders **Children sizes (16–28)** with recommended age/height/weight for `kids` products, and **Adult sizes (S–4XL)** for standard jerseys.
  - Interactive unit switcher between **Metric (CM / KG)** and **Imperial (Inches / LB)**.

### 3. Order Tracking & Shipped Status Automation
- **Custom Order Status:** Registers `wc-shipped` ("Shipped") with WooCommerce.
- **Automatic Status Transition:** Listens to `woocommerce_before_order_object_save`. When shipment tracking data (`_wc_shipment_tracking_items`) is added to an order, status automatically transitions to `shipped` with an audit note.

### 4. Dynamic Delivery Timeline (AJAX)
- Displays an estimated order timeline on product pages:
  - **Ordered:** Current date
  - **Order Ready:** +1 to +2 days
  - **Delivered:** +10 to +12 days
- Rendered via `admin-ajax.php` (`get_order_dates`) to prevent server-side page caching issues.

### 5. Kids Variation Generator Admin Tool
- **Dedicated Admin Page (`project-pack/regenerate-variations.php`):**
  - Found under **WP Admin &rarr; Regenerate Variations**.
  - Deletes and re-generates all variations for items in the `kids` category with uniform numeric sizing (16, 18, 20, 22, 24, 26, 28) and customization attributes.
  - Features real-time batch processing with progress bar and execution logs.

### 6. Performance Optimizations & Asset Control
- **Selective Dequeueing on Homepage (`project-pack/functions.php`):**
  - Dequeues non-critical scripts and stylesheets on the front page (e.g., cart scripts, smart coupon forms, swiper instances, PPOM assets) to achieve higher Core Web Vitals and lower TTFB.
- **Cache Busting:**
  - Uses `filemtime()` for `project-pack.css` to guarantee instant client updates after revisions.
- **Native Image Lazy Loading Disabled:** Enforces custom lazy-loading strategy.

### 7. Multi-Currency & Category SEO Descriptions
- **Select2 Currency Dropdown:**
  - Styled dropdown with country flags and native symbols via WooCommerce Multi-Currency.
- **Rich Schema:**
  - Generates JSON-LD `AggregateRating` & `Product` metadata based on custom ACF fields (`number_rating`, `count_rating`).
- **Expandable Category Descriptions:**
  - Supports dual top & bottom category descriptions with mobile "Read More / Read Less" accordion toggle.

### 8. Admin UX & Clutter Cleanup
- Hides unwanted third-party admin bar nodes (Updraft, Weglot, VillaTheme, Brevo, WPCode).
- Disables unused default blog post menus and custom post types (`testimonials`, `staticblocks`, `etheme_slides`) to streamline the admin workflow.

---

## Directory Structure

```text
xstore-child/
├── assets/
│   ├── css/
│   │   └── style.css                 # Custom child theme styling & responsive tweaks
│   └── js/
│       └── script.js                 # URL attribute auto-matching & AJAX timeline loader
├── project-pack/
│   ├── assets/
│   │   └── project-pack.css          # Modular layout overrides & cart styles
│   ├── functions.php                 # Asset dequeueing, coupon badges & tracking hooks
│   └── regenerate-variations.php     # Batch variation regenerator tool for Kids category
├── functions.php                     # Core child theme functions, filters & shortcodes
├── style.css                         # Child theme stylesheet header & UI overrides
├── screenshot.png                    # WordPress theme screenshot
└── README.md                         # Theme documentation
```

---

## Shortcodes Reference

| Shortcode | Description |
| :--- | :--- |
| `[currency_switcher]` | Outputs WooCommerce currency switcher dropdown markup. |
| `[currency_selector]` | Alternate currency selector rendering flag and symbol maps with Select2. |
| `[box_description_top_category]` | Displays top category description (full text on desktop, truncated with Read More on mobile) pulled from ACF options. |
| `[box_description_bot_category]` | Displays bottom SEO category description pulled from ACF options. |

---

## Requirements & Dependencies

- **WordPress:** 6.0 or higher
- **PHP:** 7.4 or higher (PHP 8.0+ recommended)
- **Parent Theme:** [XStore Theme](https://xstore.8theme.com/)
- **Required / Recommended Plugins:**
  - [WooCommerce](https://wordpress.org/plugins/woocommerce/)
  - [Advanced Custom Fields (ACF / ACF Pro)](https://www.advancedcustomfields.com/)
  - [PPOM for WooCommerce](https://wordpress.org/plugins/woocommerce-product-addon/)
  - [WooCommerce Shipment Tracking](https://woocommerce.com/products/shipment-tracking/)
  - [WooCommerce Multi-Currency](https://villatheme.com/extensions/woo-multi-currency/)

---

## Installation & Setup

1. **Upload Theme:**
   - Ensure the parent `xstore` theme is installed in `/wp-content/themes/xstore`.
   - Place this directory in `/wp-content/themes/xstore-child`.
2. **Activate:**
   - In WP Admin, navigate to **Appearance &rarr; Themes** and activate **XStore Child**.
3. **Configure Custom Fields (ACF):**
   - Ensure ACF fields for ratings (`number_rating`, `custom_rating`), header tracking scripts (`vf_header_scripts`), and category descriptions (`list_categories_des`) are configured.
4. **Regenerate Variations (if applicable):**
   - If managing kids products, visit **WP Admin &rarr; Regenerate Variations** to generate uniform size and customization attributes.

---

## Development Guidelines

- **Child Theme Modifications:**
  - Place overarching PHP functions in [functions.php](functions.php).
  - Place scoped or standalone modules inside the `project-pack/` directory.
  - CSS updates should go to [assets/css/style.css](assets/css/style.css) or [project-pack/assets/project-pack.css](project-pack/assets/project-pack.css).
  - Client-side interactions belong in [assets/js/script.js](assets/js/script.js).
- **Cache Invalidation:**
  - When editing `project-pack.css`, `filemtime` handles versioning automatically.
  - When editing `assets/css/style.css` or `assets/js/script.js`, ensure the asset version constant or timestamp is updated.

---

## Credits & Author

- **Author:** Beplus / Tuan Nguyen (<tuan.beplus@gmail.com>)
- **Repository:** [https://github.com/tuanbeplus/xstore-child](https://github.com/tuanbeplus/xstore-child)
- **Parent Theme:** XStore by [8theme](https://8theme.com/)
