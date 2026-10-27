# Mini-cart Shipping Insurance Context

## Purpose

The site uses the `codenitive-shipping-insurance` plugin for the Shipping Protection option. The plugin already renders a full insurance card and toggle on the Cart and Checkout pages, but XStore’s mini-cart originally displayed only a text-and-price summary.

The child theme adds the plugin’s full interactive insurance UI to the mini-cart, above Subtotal, without modifying plugin files.

## Do not modify plugin source

Keep all custom work in the child theme. The plugin owns:

- insurance eligibility;
- selected state stored in the WooCommerce session;
- fee calculation;
- the nonce-protected AJAX endpoints;
- the toggle markup.

Relevant plugin files:

- `wp-content/plugins/codenitive-shipping-insurance/templates/frontend/insurance-toggle.php`
- `wp-content/plugins/codenitive-shipping-insurance/includes/trait-codenitive-shipping-insurance-checkout.php`
- `wp-content/plugins/codenitive-shipping-insurance/assets/css/checkout.css`

## Child-theme implementation

### `inc/hooks.php`

The child theme conditionally loads the Codenitive frontend CSS outside Cart/Checkout, because the plugin normally enqueues it only on those pages. It also exposes the plugin’s existing endpoints and nonce to JavaScript through `vfMiniCartInsurance`:

- `refreshUrl`: `codenitive_refresh_insurance`
- `toggleUrl`: `codenitive_toggle_insurance`
- `nonce`: `codenitive_toggle_insurance`

Do not replace this with custom fee/session AJAX logic.

### `assets/js/main.js`

`initMiniCartInsurance()` is the mini-cart controller.

It:

1. Finds or creates `.vf-mini-cart-insurance-slot`.
2. Requests full plugin-generated insurance markup from the secure refresh endpoint, using `context: 'cart'`.
3. Inserts that markup into every slot.
4. Handles the insurance checkbox change event.
5. Sends the requested `yes`/`no` state to the plugin’s secure toggle endpoint.
6. Triggers WooCommerce fragment refresh after a successful change so cart UI and totals update.
7. Retains the most recent successful markup while fragments replace mini-cart DOM, preventing the control from disappearing during refresh.

Two XStore mini-cart render paths are supported:

- **Legacy mini-cart footer:** PHP adds `.vf-mini-cart-insurance-slot` directly above subtotal.
- **Elementor off-canvas mini-cart:** XStore renders this footer dynamically and outside the legacy hook. JavaScript detects:
  ```text
  .etheme-elementor-off-canvas_content-footer .cart-popup-footer .cart-widget-subtotal
  ```
  and inserts `.vf-mini-cart-insurance-slot` immediately before it.

The Elementor path is required for markup such as:

```html
<div class="etheme-elementor-off-canvas_content-footer">
  <div class="cart-popup-footer">
    <div class="cart-widget-subtotal">...</div>
  </div>
</div>
```

The UI is refreshed on WooCommerce fragment events and when the off-canvas toggle button is clicked.

### `inc/woo.php`

`vf_mini_cart_footer_with_insurance()` replaces the legacy XStore mini-cart footer and prints the slot above the legacy subtotal.

`vf_discounted_mini_cart_subtotal()` changes the mini-cart subtotal behavior:

- Uses `WC()->cart->get_cart_contents_total()` for the coupon-adjusted product amount.
- Adds `WC()->cart->get_fee_total()` so the selected shipping-insurance fee is included.
- Shows pre-discount amount in `<del>` only when a coupon saving exists.

Important: `get_fee_total()` includes all WooCommerce fees, not just insurance. This is correct for the current store, but revisit it if other fee plugins are introduced.

### `assets/scss/_woo.scss`

The project contains existing compact visual overrides for `.codenitive-insurance` (smaller card/switch, hidden heading, store typography). They originally applied only under `body .woocommerce`, but XStore’s mini-cart footer is outside that wrapper.

The selector was extended to also target:

```scss
.vf-mini-cart-insurance-slot
```

This lets mini-cart insurance use the same compact checkout appearance rather than only the Codenitive default style.

Do not compile SCSS from CLI. The site uses a WordPress SCSS compiler plugin.

## Current behavior expected

- The full Shipping Protection card and toggle appear in the mini-cart footer directly above Subtotal.
- The control remains visible whether enabled or disabled.
- Toggling calls the plugin’s nonce-protected endpoint.
- After a successful toggle, the selected insurance fee is included in the displayed mini-cart subtotal.
- Cart/checkout pages continue using the plugin’s native rendering and behavior.
- No Codenitive plugin files are changed.

## Validation performed

JavaScript syntax was checked with:

```sh
node --check wp-content/themes/xstore-child/assets/js/main.js
```

No CLI SCSS compilation was run.

## Caution for future changes

- Re-read current child files before editing because the site is live and the owner may make edits between tasks.
- Do not mutate coupons or insurance session state inside WooCommerce total-calculation hooks.
- Keep the plugin’s nonce and AJAX endpoints; do not duplicate fee calculation logic.
- XStore can use different mini-cart footer implementations. Preserve both the legacy PHP slot and Elementor JavaScript insertion path unless the theme markup is consolidated.
