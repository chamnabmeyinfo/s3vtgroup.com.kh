# Website SEO Review

**Date:** February 5, 2025  
**Scope:** Full site review for SEO friendliness, SEO configuration options, and sitemap indexing.

---

## 1. Is the site SEO friendly?

### What’s in place (good)

- **Title and description**
  - Every checked page sets `$pageTitle` and most set `$metaDescription` before including `includes/header.php`.
  - Header outputs: `<title>` and `<meta name="description">` with a sensible default.
- **Semantic HTML**
  - `lang` and `content-language` are set from the current language (e.g. `en`, `km`, `th`).
  - Viewport and charset are correct.
- **Admin SEO fields**
  - **Products:** `meta_title`, `meta_description` in admin (Product Edit → SEO tab).
  - **Services:** `meta_title`, `meta_description` stored and used on frontend (`service.php`).
  - **Pages (CMS):** `meta_title`, `meta_description` stored and used on frontend (`page.php`).
- **Analytics / tracking**
  - Google Tag (gtag.js), Microsoft Clarity, and conversion snippet are present.
- **Technical**
  - `.htaccess`: security headers, compression, cache control, no directory listing.
  - Clean URLs: `robots.txt` and sitemap served via rewrite; optional `.php`-less URLs via rewrite.

### Gaps (not SEO friendly enough)

| Issue | Location | Impact |
|-------|----------|--------|
| **Product meta not used on frontend** | `product.php` | Product `meta_title` and `meta_description` from admin are **never used**. Page always uses product name + site name for title and short/description for meta. Admin SEO tab has no effect on product pages. |
| **No canonical URL** | `includes/header.php` | Duplicate content (e.g. with/without `www`, query params) cannot be canonicalized. |
| **No Open Graph / Twitter Card** | `includes/header.php` | Poor/unpredictable previews when sharing on social (Facebook, LinkedIn, Twitter, etc.). |
| **No JSON-LD (e.g. Organization, Product)** | Site-wide | Search engines get less structured data; fewer chances for rich results. |
| **No `meta keywords`** | Site-wide | Minor; many engines ignore it, but some still use it as a weak signal. |
| **Hardcoded / inconsistent branding in titles** | Several pages | e.g. "Forklift & Equipment Pro" vs "S3 Group" vs site name from settings; weak brand consistency and no single source of truth. |
| **Sitemap missing many public pages** | `sitemap.php` | Important content (about, services, CMS pages, etc.) not listed; see Section 3. |

**Verdict:** Partially SEO friendly. Basics (title, description, language) are there, but product SEO is unused, and there is no canonical, OG/Twitter, or structured data. Sitemap coverage is incomplete.

---

## 2. Are there SEO options to configure?

### What exists

- **Per-entity SEO (admin)**  
  - **Products:** Product Edit → “SEO” tab: Meta Title, Meta Description (saved to DB but **not used** on product pages).  
  - **Services:** Services management: Meta Title, Meta Description (saved and **used** on `service.php`).  
  - **Pages:** Page Edit: Meta Title, Meta Description (saved and **used** on `page.php`).

- **No dedicated “SEO settings” panel**  
  - Admin → Settings has: Site Name, logo, footer, colors, languages, etc.  
  - There is **no** global SEO section for:
    - Default meta title/description (e.g. for homepage or fallback).
    - Default OG image, Twitter card type, or social defaults.
    - Canonical base URL or “noindex” site-wide.
    - Sitemap toggles (include/exclude content types) or lastmod/priority defaults.

So: **Yes, there are SEO options**, but only per content type (and product’s options are not applied). **No**, there is no central “SEO configuration” (global defaults, social, canonical, sitemap behavior).

---

## 3. Can the sitemap be indexed properly?

### Current setup

- **`robots.php`**  
  - Serves `robots.txt` with:
    - `User-agent: *`  
    - `Allow: /`  
    - `Sitemap: {baseUrl}/sitemap_index.xml`  
    - `Sitemap: {baseUrl}/sitemap`  
  - Correct for discovery.

- **`.htaccess`**  
  - `sitemap_index.xml` → `sitemap-index.php`  
  - No explicit rule for `sitemap`; the generic “add `.php`” rule turns `/sitemap` into `sitemap.php`.  
  - So `/sitemap_index.xml` and `/sitemap` both resolve and are indexable.

- **`sitemap-index.php`**  
  - Emits a sitemap index with one child: `<loc>{baseUrl}/sitemap</loc>`.  
  - Valid for indexing.

- **`sitemap.php`**  
  - Outputs **one** sitemap (not split by type or size).  
  - Includes:
    - Homepage  
    - `/products.php`  
    - `/contact.php`  
    - Category URLs: `/products.php?category={slug}`  
    - Product URLs: `/product.php?slug={slug}` (up to **1000** products)  
  - **Missing** from sitemap:
    - `/about-us.php`  
    - `/services.php`  
    - Individual services: `/service.php?slug=...`  
    - CMS pages: `/page.php?slug=...`  
    - `/mission.php`, `/vision.php`, `/mission-vision.php`  
    - `/ceo-message.php`  
    - `/faq.php`  
    - `/blog.php`  
    - `/quote.php`  
    - `/request-catalog.php`  
    - `/testimonials.php`  
  - **Limit:** Only first 1000 products are included; no pagination or multiple sitemap files.

### Can it be indexed?

- **Technically yes:**  
  - `robots.txt` points to the index and main sitemap.  
  - Both URLs are reachable and return valid XML.  
  - Crawlers can index everything that is **listed** in the sitemap.

- **Practically incomplete:**  
  - Many important, public pages are **not** listed, so they rely only on crawling and internal links.  
  - If you have more than 1000 products, only the first 1000 are in the sitemap.

So: **Sitemap is indexable**, but **coverage is incomplete** and product count is capped.

---

## Recommended actions (priority order)

### High priority

1. **Use product SEO fields on the frontend**  
   - In `product.php`, set:
     - `$pageTitle` from `meta_title` if present, else `{product name} - {site_name}`.  
     - `$metaDescription` from `meta_description` if present, else short/description.  
   - Ensures the Product Edit SEO tab actually affects search and social snippets.

2. **Expand the sitemap**  
   - Add static routes: about-us, services, contact (already there), mission, vision, mission-vision, ceo-message, faq, blog, quote, request-catalog, testimonials.  
   - Add dynamic: all active services (`/service.php?slug=...`), all active CMS pages (`/page.php?slug=...`).  
   - If product count can exceed 1000: split product URLs into multiple sitemaps (e.g. `sitemap-products-1.xml`, `sitemap-products-2.xml`) and reference them from the sitemap index.

3. **Add canonical URL in header**  
   - In `includes/header.php`, output:
     - `<link rel="canonical" href="<?= canonical_url() ?>">`  
     - Implement `canonical_url()` to build current page URL (same scheme, host, and path; optionally strip tracking params).  
   - Reduces duplicate-content issues (e.g. `www` vs non-www, trailing slash, query params).

### Medium priority

4. **Add a simple “SEO” section in Admin → Settings**  
   - Options could include: default meta title, default meta description, default OG image URL, “Homepage meta title/description”.  
   - Use these in `header.php` when a page doesn’t set its own (e.g. homepage, or as fallback).

5. **Add Open Graph and Twitter Card meta tags**  
   - In `header.php` (or a shared partial):  
     - `og:title`, `og:description`, `og:image`, `og:url`, `og:type`, `og:site_name`.  
     - `twitter:card`, `twitter:title`, `twitter:description`, `twitter:image`.  
   - Pass per-page values from each script (e.g. `product.php`, `page.php`, `service.php`) or from a small helper that builds defaults from settings.

6. **Add JSON-LD**  
   - Organization on homepage (and optionally in header).  
   - Product schema on product pages (name, description, image, etc.).  
   - Helps rich results and understanding of the site.

### Lower priority

7. **Unify branding in titles**  
   - Use one source (e.g. Settings “site_name”) for the suffix in all page titles (e.g. “Page Name - {site_name}`).  
   - Replace hardcoded “Forklift & Equipment Pro” / “S3 Group” with that value where appropriate.

8. **Explicit sitemap rewrite (optional)**  
   - In `.htaccess`, add: `RewriteRule ^sitemap$ sitemap.php [L]` so `/sitemap` is explicitly handled before the generic “add .php” rule.  
   - Improves clarity; behavior is already correct.

---

## Summary

| Question | Answer |
|----------|--------|
| **1. SEO friendly?** | Partially. Title/description and language are good; product SEO unused, no canonical/OG/structured data, sitemap missing many URLs. |
| **2. SEO options to configure?** | Yes for products, services, and pages in admin; product options not applied on frontend. No global SEO or sitemap configuration. |
| **3. Sitemap indexable?** | Yes. Structure and `robots.txt` are correct. Coverage is incomplete (missing many pages; products capped at 1000). |

Implementing the high-priority items (product meta usage, sitemap expansion, canonical) will make the site much more SEO friendly and ensure the sitemap supports proper indexing of all important content.

---

## Deep fix applied (February 2025)

The following were implemented:

- **Product SEO:** `product.php` now uses `meta_title` and `meta_description` from the database (with fallback to name/short description). Product Edit SEO tab values are reflected on the frontend.
- **Canonical URL:** `canonical_url()` helper and `<link rel="canonical">` in `includes/header.php`. Pages can set `$canonicalUrl` to override.
- **Open Graph & Twitter Card:** Full set of `og:*` and `twitter:*` meta tags in the header, using page title/description/image or SEO defaults from settings.
- **JSON-LD:** Product schema on product pages; Organization schema on all other frontend pages (from header).
- **Sitemap:** Expanded to include static pages (about-us, services, mission, vision, mission-vision, ceo-message, faq, blog, quote, request-catalog, testimonials), all active services, all active CMS pages. Product limit increased to 5000. XML URLs escaped correctly.
- **.htaccess:** Explicit `RewriteRule ^sitemap$ sitemap.php [L]` for the sitemap.
- **Admin SEO settings:** New “SEO Defaults” card in Settings → General: Default Meta Title, Default Meta Description, Default OG Image URL. Stored as `seo_default_meta_title`, `seo_default_meta_description`, `seo_og_image`.
- **Unified branding:** Key frontend pages now use `get_site_name()` for the title suffix instead of hardcoded “Forklift & Equipment Pro”.
- **Service & page canonical/OG:** `service.php` and `page.php` set `$canonicalUrl` and `$ogImage` for correct sharing and canonical.
