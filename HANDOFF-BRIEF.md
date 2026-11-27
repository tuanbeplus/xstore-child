# Handoff Brief — Vintage Football Shop (theme work)

> Paste this whole file into a new AI session. It contains project context, current state, conventions, and open issues.

## 1. Project

- **Site:** https://vintagefootball.shop (live, production)
- **Stack:** WordPress + WooCommerce 10.6.x, XStore parent theme + `xstore-child`
- **Local path:** `/Users/macbookpro/Documents/Work_Project/Vintage_Football/wp-content/themes/xstore-child`
- **Parent theme:** `xstore` (read-only, do not modify)
- **Deploy:** manual by the human (they copy files to the live server). Never assume a change is live until they confirm.
- **Credentials:** not included in this file. Ask the human if DB/admin access is needed.

## 2. Ground rules

1. Only edit the **child theme**. Never touch parent theme or plugins.
2. Keep changes scoped: CSS must be scoped under a wrapper class (`.vf-archive`, `.vf-article-wrap`, `body.single-product`, …). **No global resets** (`body{}`, `h1..h6{}`, `*{margin:0}`) in blog CSS.
3. Blog CSS lives in dedicated files (`assets/css/blog-*.css`), not in `assets/css/main.css`.
4. PHP modules go in `inc/*.php` and must be required from `functions.php`.
5. Always run `php -l <file>` and `git diff --check` after edits.
6. Don’t create/lock in canonical URLs without checking with the human: the site’s canonical product/category URLs are **base-free** (`/clubs/ac-milan/`, `/1998-france-home-retro-kit/`).
7. `THEME_VERSION` in `functions.php` keeps being rewritten to `time() . '2.1.97'` by something external. That kills browser cache. Prefer a static version; mention it if you see it.

## 3. Code map (child theme)

```
functions.php                  module loader (see list below)
home.php                       Blog index (posts page) — custom template
single-post.php                Single blog post — custom template
assets/css/blog-archive.css    Blog index styles (scoped .vf-archive)
assets/css/blog-single.css     Single post styles (scoped .vf-article-wrap)
assets/js/blog-archive.js      AJAX filter + pagination for blog index
assets/img/blog-placeholder.svg  Fallback image for posts without thumbnail
inc/blog.php                   Blog helpers, featured posts, comments off, images
inc/breadcrumbs.php            [vf_breadcrumbs] shortcode, widget, single post trail
inc/woocommerce-base-free-permalinks.php  Replacement for 2 plugins (see §6)
inc/faq-schema.php             FAQPage JSON-LD, integrated with Rank Math
inc/llms.php                   /llms.txt endpoint
inc/helpers.php                misc helpers + product category URL canonicalisation
inc/woo.php                    WooCommerce tweaks (product tabs, size guide, care)
inc/shortcodes.php             [vf_category_faq] and other shortcodes
inc/hooks.php                  dequeues, admin tweaks, loading body class
inc/category-content-manager.php  TEMPORARY admin tool — currently DISABLED (commented in functions.php)
inc/regenerate-variations.php  admin tool
vintagefootball-blog-templates/  static design mockups + ACF exports (not live templates)
```

`functions.php` currently requires: helpers, hooks, shortcodes, breadcrumbs, faq-schema, llms, woocommerce-base-free-permalinks, blog, woo, regenerate-variations.

## 4. Verified live state (as of handoff)

| URL | Result |
|---|---|
| `/` | 200 |
| `/blog/` | 200 (custom `home.php`, Posts page is set) |
| `/clubs/`, `/clubs/ac-milan/`, `/legends/messi/`, `/kids/` | 200 |
| `/product-category/clubs/` | 301 → `/clubs/` |
| `/product/1998-france-home-retro-kit/` | 301 → `/1998-france-home-retro-kit/` |
| `/llms.txt` | 200 |
| `/sitemap_index.xml` | 200, but **still contains old base URLs** (open issue §9) |
| `/robots.txt` | 200, **no AI crawler rules yet** (open issue §9) |

Data: **9 blog posts** (4 without featured image → placeholder), **5 post categories**: `club-kits` (3), `collecting` (2), `national-teams` (1), `sizing` (1), `world-cup` (2).

## 5. Blog system (implemented)

### 5.1 Blog index — `home.php`

Layout: breadcrumb → page head (H1 + intro) → 2-column layout:
- left: `.vf-archive__main`
  - `.vf-featured-list` — **static featured block**, outside the AJAX container
  - `.vf-archive__content` — chips + post grid + pagination (this is what AJAX replaces)
- right: `.vf-sidebar` — Recent posts + promo block

Featured posts:
- Read from ACF true/false field **`vf_featured_post`** (post type `post`), all flagged posts, newest first.
- Fallback: newest post if nothing is flagged.
- Rendered **always** (any page / any category filter) and **never** affected by AJAX.
- Featured posts **may also appear in the grid** (duplication is intentional; the two blocks are independent).

Grid:
- Uses the main query (`get_query_var('cat')` for category filtering).
- Cards: image (floating black category badge top-left), 1-line? (currently multi-line) title, date `j F, Y`, 3-line excerpt.

Category chips:
- "All" → blog index URL.
- Others → `?cat=<term_id>` (keeps the blog template + pagination).
- Active state from `get_query_var('cat')`.
- "Uncategorized" is skipped.

Pagination: `paginate_links()` wrapped in `.vf-pagination`, current page class `is-current`.

### 5.2 Single post — `single-post.php`

- Breadcrumb: **Home › Blog › Post title** (no category), 760px wide, single line, last crumb truncates with `…`.
- Category badge: black bg / white text / no border.
- Meta row: date `j F, Y` · N min read · **Vintage Football Shop** (author fixed).
- Hero image: `large`, `loading=eager`, `fetchpriority=high`.
- Content: full typography for WP editor + Gutenberg blocks.
- Blockquote: light grey background, **black left border and black quote mark** (not a black box).
- Share row (Facebook, X, WhatsApp, Email).
- Related posts: same category, 3 cards (image + floating badge + title).
- Related Kits: native WooCommerce/WooCommerce-XStore loop, styled with the **`vf-product-search-filters` widget CSS** (4 cols, square contain image, centred uppercase title/price).

### 5.3 AJAX (chips + pagination)

`assets/js/blog-archive.js`:
- Intercepts `.vf-chip` and `.vf-pagination a.page-numbers`.
- `fetch` → parse → replace `innerHTML` of `.vf-archive__content`.
- `history.pushState` updates the URL; `popstate` reloads content.
- Scrolls to `.vf-archive__content` with **100px offset** after load.
- Loading state: `.is-loading` + `aria-busy` on content only (featured stays untouched).
- Falls back to normal navigation on error.

### 5.4 Images

`vf_blog_post_thumbnail( $post_id, $size, $attr )` in `inc/blog.php`:
- Outputs a **plain `<img>`** (not `get_the_post_thumbnail()`), because XStore’s lazy-load filter swaps `src` for a 10×10 placeholder.
- Uses `wp_get_attachment_image_src()` for real URL + width/height.
- `alt` from Media Library, falls back to post title; `data-no-lazy="1"`.
- Missing thumbnail → `assets/img/blog-placeholder.svg`.
- Sizes used: featured `medium_large`, grid `medium_large`, single hero `large`, sidebar `thumbnail`.

### 5.5 Comments

Disabled for post type `post` only (`inc/blog.php`):
- `remove_post_type_support('post', ['comments','trackbacks'])`
- `comments_open` / `pings_open` → false
- `comments_array` → empty

WooCommerce product reviews are untouched.

### 5.6 ACF fields

| Field | Type | Used for |
|---|---|---|
| `vf_featured_post` | true/false (post) | Featured block on blog index |
| `vf_post_related_products` | post_object → product, return `id`, multiple | "Related Kits" on single post |

ACF export files: `vintagefootball-blog-templates/acf-export-2026-11-25.json`, plus older `acf-export-2026-11-16.json` and `vf-category-faqs-import-2026-11-16.json`.

## 6. Product / category permalinks (important)

Two plugins were used before: **Remove CPT base** and **Remove Taxonomy URL**. They are now **deactivated**, replaced by `inc/woocommerce-base-free-permalinks.php`.

Behaviour (module enabled via option `vf_base_free_permalinks_enabled`):
- Products: `/{product-slug}/`
- Product categories: `/clubs/`, `/clubs/ac-milan/` (hierarchical)
- 301 from `/product/...` and `/product-category/...` to the short URL
- Page > product category > product priority (so `/kids/`, `/legends/messi/` no longer resolve to attachments)
- Admin toggle: **WooCommerce → Base-free Permalinks** (Standby / Enabled, collision audit, flush rules once)

Do **not** change WooCommerce permalink settings to blank; the module depends on the default base internally.

## 7. SEO / AEO modules (Phase 4 tasks done)

- Task 1: mobile menu cleanup, dead links, attachment 301, sitemap H1 audit.
- Task 2: product tabs (Size Guide, Easy Return, Care, FAQ, Reviews) in `inc/woo.php`.
- Task 3: duplicate Product/Breadcrumb schema removed (Rank Math only).
- Task 4: category top descriptions + ACF category FAQs + `[vf_category_faq]` + `inc/faq-schema.php`.
- Task 5: image alt text — done by the human with a plugin on all media. Audit result: **all content images have alt**; only Facebook Pixel `<noscript>` beacons lack it (not content).
- Task 6: `/llms.txt` (`inc/llms.php`) — live.
- Task 7: blog templates — implemented live as `home.php` / `single-post.php` (see §5).
- Task 8: **Core Web Vitals — on hold**, not started.

## 8. Open issues / next steps (priority order)

1. **Rank Math sitemaps still contain base URLs** — `/product/{slug}/` and `/product-category/.../`, despite the filters in `inc/woocommerce-base-free-permalinks.php` (`rank_math/sitemap/entry`, `rank_math/sitemap/xml_post_url`, `rank_math/sitemap/enable_caching`). Needs debugging: verify the deployed file version, Rank Math cache mode (file vs DB), and whether the filters fire for that Rank Math version.
2. **robots.txt has no AI crawler rules.** Prepared policy: allow `OAI-SearchBot`, `PerplexityBot`; block `GPTBot`, `ClaudeBot`, `anthropic-ai`, `CCBot`, `Bytespider`, `Diffbot`, `FacebookBot`, `Amazonbot`, `ImagesiftBot`, `Cohere-ai`. Must be pasted in Rank Math → General Settings → Edit robots.txt.
3. Task 8 (CWV): live sends `Cache-Control: no-store` + `PHPSESSID`/`wmc_*` for guests; multiple tracking stacks (GTM, Site Kit, PixelYourSite, Brevo, Metorik, Pixel Cat); `inc/hooks.php` disables `srcset` and native lazy loading. These are the known bottlenecks.
4. Remove `inc/category-content-manager.php` after category FAQ import is verified (it is currently disabled in `functions.php`).
5. Blog index post-card titles currently wrap to multiple lines; only the breadcrumb and (previously, now reverted) related-post titles were clamped. Confirm desired behaviour with the human.
6. `/terms-and-conditions/` still duplicates refund content; `/login/`, `/registration/`, `/lost-password/` lack H1 and are noindex candidates.

## 9. Gotchas

- **Cache:** WP Rocket + Cloudflare. Always test with a cache-buster (`?cb=$RANDOM`).
- **Rank Math** caches sitemaps (transient or file). Changes may need a cache flush.
- **XStore** rewrites thumbnail markup for lazy load — always use `vf_blog_post_thumbnail()` for blog images.
- **XStore** registers `etheme-*` style handles globally at `wp_enqueue_scripts` priority 30; enqueue them at priority ≥ 60.
- `$etheme_styles` global must be marked to avoid duplicate inline `<link>` when the product loop renders.
- Parent theme overrides `assets/css/main.css` usage; there is **no Sass build step** on this machine for the child theme (SCSS exists, `main.css` is maintained manually).
- Site uses `%postname%` permalinks, no trailing slash issues; keep trailing slashes in internal links.
- The homepage slider heading is the only visible H1 on `/`; never add a hidden SEO H1 there.
- Client approves changes before client-facing delivery; blog templates were design-only at first, now live.

## 10. Useful commands

```bash
# status of the working tree
git -C <theme> status -s

# lint
php -l home.php && php -l single-post.php && php -l inc/blog.php
node --check assets/js/blog-archive.js

# live checks
curl -s -o /dev/null -w '%{http_code} -> %{redirect_url}\n' https://vintagefootball.shop/clubs/
curl -s https://vintagefootball.shop/robots.txt
curl -s https://vintagefootball.shop/product_cat-sitemap.xml | grep -o '<loc>[^<]*</loc>' | head
curl -s https://vintagefootball.shop/wp-json/wp/v2/posts?per_page=100 | python3 -m json.tool
```

## 11. Not yet deployed / uncommitted

Working tree (child theme): modified `functions.php`, `inc/breadcrumbs.php`, `style.css`, `vintagefootball-blog-templates/assets/css/blog.css`; untracked `home.php`, `single-post.php`, `inc/blog.php`, `inc/woocommerce-base-free-permalinks.php`, `assets/css/blog-archive.css`, `assets/css/blog-single.css`, `assets/js/blog-archive.js`, `assets/img/`, ACF export + saved live HTML.

Always confirm with the human which files are already on the server before assuming live behaviour.
