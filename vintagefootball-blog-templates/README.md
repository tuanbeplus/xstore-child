# Vintage Football Shop — blog templates

Static, responsive HTML/CSS for two blog templates, designed to match the live site at [https://vintagefootball.shop](https://vintagefootball.shop). Built 20 November 2026 for review. **Nothing in this folder has been published or sent to the client.**

Tom approves before anything goes live or to the client.

## Files

| File | What it is |
| --- | --- |
| `archive.html` | Blog archive: featured post, topic filters, post grid, pagination, sidebar (search, topics, recent, promo) |
| `single.html` | Single post: hero, typography (h2/h3, lists, pull quote, image + caption, table), share, author box, related posts, related products |
| `assets/css/blog.css` | Shared stylesheet (tokens, chrome, both templates) |
| `assets/js/blog.js` | Mobile drawer, header search, archive topic filter (enhancement only) |
| `assets/img/` | Logo, payment strip, Instagram badge, product and lifestyle photos copied from the live shop |
| `assets/fonts/` | Self-hosted copies of the site’s Staatliches and Inter files |
| `BRAND-NOTES.md` | Colours, type, header/footer, product card — with CSS/URL sources |
| `credits.csv` | Image inventory (file, source URL, author, licence, used in) |
| `screenshots/` | Desktop, tablet and mobile captures of both templates, plus `live-reference/` shots of the live site |
| `vintagefootball-blog-templates.zip` | Packaged copy of the above (excludes `_work/`) |

Open `archive.html` and `single.html` in a browser, or serve the folder as static files. Relative paths assume the folder root.

## Example content

All post copy, dates, filters and the author box are **example content for design review**, not live copy. It is marked with an HTML comment in the header of each page (`<!-- Example content for design review. Not live copy. -->`) and should be replaced before launch.

The writing uses real clubs, tournaments and eras as topics (Brazil 2002, France 1998, Manchester United 1998–99, AC Milan 2006–07, and so on). It does not invent store statistics, testimonials, or quotes attributed to real people. The footer blurb, offer lines and “100,000+ customers since 2019” are taken from the live site, not added here as new claims.

Related-product **names, URLs, photographs and prices** come from the live homepage product grid, scraped on 20/11/2026. All twelve of those products displayed **$65.00 USD**. If a price has changed, update the strip from the live product.

## Brand decisions used in the templates

Full evidence is in `BRAND-NOTES.md`. In short:

- Logo: live `Logo.svg` (white wordmark) on a `#111` header and footer
- Type: Staatliches for headings / nav / buttons; Inter for body
- Accent: `#16FC03` (and `#1AFB06` on Subscribe / active nav), used as a fill with black text, never as small text on white
- Promo ticker, header and footer follow the live Elementor templates (ids 84390 / 84379)
- Product cards follow the live homepage card: 1:1 image, 20px radius, Staatliches 24px title, Inter 600 16px price
- “Blog” is a **proposed** main-nav item, placed after About. The live site has no blog yet.

## Mapping to XStore / Elementor

Suggested widgets when a developer turns this HTML into a Theme Builder + Elementor (or XStore) template. Class names are BEM-like and prefixed `vf-` so they can be matched or discarded in favour of theme classes.

### Shared chrome (both templates)

Use the **existing** Elementor header (84390) and footer (84379). Do not rebuild them from this HTML unless the client wants the proposed “Blog” link added to the live menu.

| Section | Template class | Suggested widget / source |
| --- | --- | --- |
| Promo ticker | `.vf-promo` | Existing child widget `vf-promo-carousel` (already on the header) |
| Header / nav / tools | `.vf-header`, `.vf-nav` | Elementor Theme Builder header 84390 (Etheme Nav Menu, logo, currency, ajax search, wishlist, cart, mobile off-canvas) |
| Footer | `.vf-footer` | Elementor Theme Builder footer 84379 (logo, text, Fluent form Subscribe, nav, payments, Instagram badge) |
| Back to top | `.vf-top` | XStore “back to top” (already on the live site) |

### Blog archive (`archive.html`)

| Section | Template class | Suggested widget |
| --- | --- | --- |
| Breadcrumb | `.vf-breadcrumb` | Elementor **Breadcrumbs** or XStore breadcrumbs, trail Home › Blog |
| Page title + intro | `.vf-page-head` | Elementor **Theme Post Title** / Heading + Text Editor, or an Archive Title widget |
| Featured post | `.vf-featured` | Elementor **Posts** or **Loop Grid** (1 post, custom skin: image, badge, title, excerpt, button) |
| Topic filters | `.vf-filters`, `.vf-chip` | Elementor **Taxonomy Filter** (or a Query Filter) on the post category, styled as chips |
| Post grid | `.vf-post-grid`, `.vf-post-card` | Elementor **Loop Grid** / **Posts**, 2 columns (1 on mobile), image, category, title, date, excerpt |
| Pagination | `.vf-pagination` | Loop Grid pagination, or XStore archive pagination |
| Sidebar search | `.vf-widget-search` | Elementor **Search** or WP search widget |
| Sidebar topics | `.vf-widget-cats` | Elementor **Taxonomy** (Categories) |
| Sidebar recent | `.vf-widget-recent` | Elementor **Posts** (recent, list skin with thumbnail) |
| Sidebar promo | `.vf-widget-promo` | Elementor **Call to Action** or HTML widget; link to `/shop/`. Copy must stay consistent with the live offer. |

XStore also ships a blog archive with a sidebar option; if the team prefers a theme archive over a Theme Builder page, map the same sections onto the XStore blog layout and restyle with the tokens in `blog.css`.

### Single post (`single.html`)

| Section | Template class | Suggested widget |
| --- | --- | --- |
| Breadcrumb | `.vf-breadcrumb` | Elementor Breadcrumbs (Home › Blog › post title) |
| Category badge | `.vf-article__cat` | Theme Post Terms (category) |
| Post title + meta | `.vf-article__title`, `.vf-article__meta-row` | Theme Post Title, Post Info (date, reading time, author) |
| Featured image + caption | `.vf-article__hero` | Theme Post Featured Image; 20px radius, caption from the attachment |
| Post body typography | `.vf-article__content` | Theme Post Content. Style `h2`/`h3`, lists, `blockquote`, figures, tables in the theme or with an Elementor custom CSS rule copied from `blog.css` |
| Share buttons | `.vf-share` | Elementor **Share Buttons** or XStore post sharing, 44px targets |
| Author box | `.vf-author` | Elementor **Author Box** or XStore author bio. Default the display name to the shop until a named editor is agreed. |
| Related posts | `.vf-related` | Elementor **Posts** (related by category) or XStore related posts, 3 columns |
| Related products | `.vf-products`, `.vf-product` | Elementor **Product Grid** / **Loop Grid** (WooCommerce), 4 columns (2 on tablet, 2 on mobile or a slider). Skin must match the live homepage product card (Etheme Product Grid). Query: products mentioned in the post, or the same category (club / national team). |

The site already uses **Beplus / custom Elementor widgets** for the homepage hero and promo carousel. Those are not required for the blog; standard Elementor Theme Builder widgets should be enough.

## Responsive breakpoints used here

- Desktop: 1440px (header nav visible from 1200px up)
- Tablet: 1024px (hamburger header, single-column featured, 2-up products, sidebar stacks)
- Mobile: 390px (1-up posts and related posts, 2-up products, article image 1:1)

## QA checklist (for this package)

- Desktop, tablet and mobile screenshots of both pages are in `screenshots/`
- Text should not overflow its box
- Body text contrast is at least 4.5:1 (`#111` on white; `#cfcfcf` on `#111`)
- Interactive targets are at least 44px (nav, chips, pagination, share, subscribe, product hover icons, footer contacts)
- Image files in `assets/img/` were downloaded with HTTP 200 from the live site (see `credits.csv`)

## Open questions for Tom / the client

1. Blog name and URL: “Blog” at `/blog/`, or something else (“Journal”, “Stories”)?
2. Should “Blog” be added to the live main nav, and in which position? The templates put it after About.
3. Author byline: keep “Vintage Football Shop”, or a named editor once one is confirmed?
4. Related products: always pulled from clubs/nations mentioned in the post, or a manual pick per article?
5. Are archive filters meant to be categories, tags, or both?
6. Pagination size (8 posts on page 1 in this mock, with a second page link for the layout only).
