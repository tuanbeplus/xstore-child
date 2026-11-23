# Brand notes — Vintage Football Shop

Extracted from the live site on **20 November 2026 (Asia/Saigon, UTC+7)**.  
Source: [https://vintagefootball.shop](https://vintagefootball.shop) (WordPress + WooCommerce, XStore 2.1.93 + xstore-child, Elementor).  
Anything inferred is marked **chưa chắc chắn**.

There is **no existing blog** on the live site. The sitemap (`sitemap_index.xml`) lists only pages, products, product categories and a local sitemap — no `post-sitemap`. URLs `/blog/`, `/journal/`, `/news/` all return 404. These templates are new, designed to sit on the current brand.

Live reference screenshots: `screenshots/live-reference/`.

---

## Logo

- File: `https://vintagefootball.shop/wp-content/uploads/2025/02/Logo.svg` (HTTP 200, 7,238 bytes).
- Local copy: `assets/img/logo.svg`.
- White wordmark “VINTAGE.FS” with five stars, boxed in a white rule. Built for a dark (`#111111`) header and footer.
- Displayed size on the live header (computed, 1440px viewport): **190 × 58 px**. Footer uses the same file at 201 × 62.

## Fonts

Loaded from the site’s self-hosted Elementor Google Fonts (not a live Google Fonts request):

| Family | Role | Source CSS |
| --- | --- | --- |
| **Staatliches** | Headings, navigation, buttons, ticker, product titles | `.../elementor/google-fonts/css/staatliches.css` |
| **Inter** | Body, prices, UI, breadcrumbs secondary text | `.../elementor/google-fonts/css/inter.css` |

Kirki / body rule (`kirki-styles.css` + computed):

- `body`: `Inter, sans-serif`, 16px / 26px, weight 400, color `#111`.
- Elementor typography globals (`--e-global-typography-*` in the homepage HTML):
  - Primary: Staatliches, 30px, weight 500
  - Text: Inter, 16px / 26px, letter-spacing `-0.5px`
  - Display (custom `3d457c0`): Staatliches, 56px, weight 500, uppercase, line-height 0.9em, letter-spacing `-2.1px`
  - Other heading steps: 44 / 40 / 30 / 24 / 20px Staatliches, weight 500
- Hero H1 (computed): Staatliches, **74.88px**, weight 500, uppercase, letter-spacing `0.5px`, color white, class `vf-hero-title`.
- Nav items (computed): Staatliches, **24px**, weight 500, white, padding `6px 16px`, header row 92px.
- Product title (computed): Staatliches, **24px / 28px**, letter-spacing `-0.5px`, `capitalize`.
- Product price (computed): Inter, **16px**, weight 600, line-height 20.8px.

Local font files in `assets/fonts/` are copies of those same `.woff2` URLs.

## Colour tokens

### Elementor globals (homepage inline CSS)

| Token | Value | Use on the live site |
| --- | --- | --- |
| `--e-global-color-primary` | `#16FC03` | Lime fill (ticker, primary buttons, hover on nav) |
| `--e-global-color-accent` | `#1AFB06` | Active nav, footer Subscribe button |
| `--e-global-color-text` | `#111111` | Body text, header/footer background |
| `--e-global-color-secondary` | `#696B6D` | Muted grey |
| `--e-global-color-8cdbbdb` | `#111111` | Main header bar |
| `--e-global-color-a99046d` | `#F7F7F7` | Soft section background (e.g. “Featured collections”) |
| `--e-global-color-8a95b55` | `#E5E5E5` | Borders |
| `--e-global-color-96ba7a7` | `#D32222` | Sale / alert red |
| `--e-global-color-fa3bfa5` | `#47A432` | Secondary green |

### XStore / Kirki (`kirki-styles.css`)

- `--et_font-color: #111111`
- Buttons (default theme): white background, `#111` text and border; hover inverts to `#111` background / white text. Border radius `0px` in Kirki inputs; live hero buttons use **4px**.
- Sale badge: `--et_on-sale-color: #ffffff`, `--et_on-sale-bg-color: #c62828`
- Add to cart (single): black background, `#ddd9d9` text.

### Child theme (`xstore-child/assets/css/main.css`)

These exist in the child but are **not** the colours driving the current Elementor header/hero. Treated as legacy unless a page clearly uses them:

- `--color-primary-dark: rgba(7, 16, 118, 1)` — **chưa chắc chắn** (not visible on the homepage chrome)
- `--color-primary-light: rgba(237, 26, 57, 1)` — **chưa chắc chắn**
- `--color-footer-background: #1A202F` — the **live footer background is `#111111`** (computed on `elementor-element-24efeb9a`). The child token is not what ships on the current footer.

### Promo ticker (authoritative)

`xstore-child/elementor/widgets/vf-promo-carousel/widget.css`:

- `--vf-promo-bg: #16FC03`
- `--vf-promo-color: #111111`
- The green fill is applied with `box-shadow: inset 0 0 0 100vmax var(--vf-promo-bg)` (the wrapper itself computes as white).
- Type: Staatliches, 18px (15px on viewports ≤767px), letter-spacing `0.04em`, uppercase, padding 10px 0 (8px on small screens), 30s marquee, 4-point star separator (15×15 path in the header markup).
- Copy, as on the live bar: “BUY TWO, GET ONE FREE !”, “FREE Shipping on ALL ORDERS WORLDWIDE”, “Premium VINTAGE KITS”, “HUNDREDS OF NEW RELEASES”.

## Buttons

Hero primary (computed, class `vf-hero-btn vf-hero-btn--primary`):

- Background `#16FC03`, text `#000`, 1px solid `#16FC03`
- Staatliches, 22px / 24.2px, letter-spacing `0.5px`, uppercase
- Padding `12px 30px`, radius **4px**, height 50px

Hero secondary (`vf-hero-btn--secondary`): transparent, text `#fafafa`, 1px solid `#eee`, same type.

Footer Subscribe: background `#1AFB06`, text `#111111`, no radius, sits beside a `#3C3C3C` email field, 20px-ish label in Staatliches.

Theme “button” class (Kirki, not the hero): white, 3px radius, Staatliches 18px, padding `10.5px 30.8px`, uppercase — present but less visible on the homepage hero.

**Contrast note:** lime (`#16FC03`) used as *text* on white fails WCAG 4.5:1 (some mega-menu “View all” links on the live site do this). These templates use lime only as a **fill** with black text, or as a hover colour on a black header (lime on `#111` is fine). Body links stay `#111` and underlined.

## Header and footer structure

### Header (Elementor Theme Builder, template id `84390`)

Fixed (`position: fixed`). Two rows:

1. Promo ticker, 37px tall at 1440px.
2. Black bar, 92px tall, padding `0 20px`:
   - Logo left
   - Nav centre: **Home, Collections, Clubs, National, Legends, About** (Staatliches 24px, white, hover `#16FC03`, active `#1AFB06`). Clubs / National / Legends / About show carets. About opens a simple list (Track Your Order, FAQ, Sizing, Reviews, Affiliates, Sell Or Request). Clubs / National / Legends are **mega menus** on the live site; this static kit does not rebuild those mega menus (links go to the live category pages).
   - Tools right: currency pill (white, rounded, US flag + “$ USD”), “My Account”, search, wishlist, cart. Search opens an XStore ajax popup on the live site; the template uses a simple sliding search bar.

Horizontal nav is hidden from tablet down on the live header (`elementor-hidden-tablet` etc.). Below 1200px this kit switches to the live mobile pattern: logo, currency, cart, hamburger.

Default currency on the homepage is **USD** (`$65.00` product prices, US flag in the switcher). AUD, NZD, GBP and EUR are also offered. The pill in these templates is static.

### Footer (Elementor template id `84379`)

Background `#111111`, padding-top 80px, height ~541px on desktop.

- Column 1: logo, about blurb (“Your home to Australasia’s largest collection… since 2019 — with over 100,000+ satisfied customers. Worldwide Express Shipping”), payment-logo strip (`payment-logos.webp`).
- Column 2: “Contact Us”, lead line, `info@vintagefootball.shop`, Instagram **SOCCERETROS** (live spelling in the footer; the URL is `instagram.com/soccerretros`), phone `0800123694` (`tel:+64800123694`).
- Column 3: “JOIN THE CLUB”, 100K Instagram badge (`ChatGPT-Image-Sep-29-2025-02_33_03-PM.webp`), email + Subscribe.
- Bottom bar: “©2026 Vintage. All Rights Reserved.” (the word Vintage is underlined) plus Reviews, Track Your Order, FAQ, Sizing, Sell Or Request, Privacy Policy, Affiliates.
- Footer body text: `#cfcfcf` / `#FFFFFFB3`, Inter 15px / 26px.
- A circular white back-to-top button sits at the bottom-right on the live site.

## Product card (homepage grid, computed)

- Class: `etheme-product-grid-item etheme-product-hover-mask2 etheme-product-hover-mode-white etheme-product-image-hover-back-zoom-in`
- Card ~321 × 426px at 1440px (4-up in the homepage row).
- Image: **1:1**, border-radius **20px**, hover zoom.
- Title: Staatliches 24/28, letter-spacing `-0.5px`, capitalize.
- Price: Inter 16px / 600, `$65.00` (USD, scraped from the homepage on 20/11/2026). All 12 homepage products were `$65.00`.
- Hover actions: Select options (cart icon), wishlist, compare, quick view — overlaid on the image. The template keeps cart + wishlist as 44px targets (always visible at ≤1024px).

## Layout / spacing

- XStore container max-width: **1670px** (`kirki-styles.css`, `.container, .et-container`).
- Text-heavy inner pages (FAQs, shop intro) use a much tighter measure with generous side padding. These blog templates use a **1400px** shell and a **760px** article measure so long-form type matches the FAQs page more than a full-bleed 1670px grid.
- Image radius used on product images and the shop hero photograph: **20px**.
- Shop breadcrumbs: Staatliches, uppercase, “HOME › SHOP ALL COLLECTIONS”.
- Shop / FAQs page titles: large Staatliches, almost always uppercase (Staatliches is a caps-only face).

## Existing content pages (for typography reference)

- `/`, `/shop/`, `/faqs/`, `/sizing/`, `/reviews/`, `/refunds/`, `/shipping/`, `/track-your-order/`, `/sell-or-request/`, `/terms-and-conditions/`, `/privacy-policy/`, plus `/affiliates/` linked in the footer (not in the page sitemap — **chưa chắc chắn** if that URL is canonical).

## Inferred or unconfirmed

- Blog name, slug (`/blog/` vs `/journal/` vs `/stories/`) and whether “Blog” should be added to the main nav — **chưa chắc chắn**. The templates use “Blog” and add it after About as a proposal.
- Named author vs shop-as-author — **chưa chắc chắn**. The single template uses “Vintage Football Shop” as the editorial byline so no staff name is invented.
- Child-theme navy/red tokens — **chưa chắc chắn**, not used in these files.
- Whether every product in the catalogue is `$65.00`, or only the homepage “hottest kits” row — **chưa chắc chắn**. Related-product prices are taken from that homepage row and dated 20/11/2026.
- Mega-menu contents are recorded in `_work/header.html` if a developer needs them; they are not redrawn here.
