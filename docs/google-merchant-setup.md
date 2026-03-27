# Google Merchant Center — API push + optional feed

This site syncs **published products** (`is_active = 1`) to **Google Merchant Center** using Google’s **Content API for Shopping** (REST `products.insert` / `products.delete`). That is the same product surface Merchant Center uses for Shopping and free listings; you enable the **Content API for Shopping** in Google Cloud (the newer “Merchant API” name in docs refers to additional surfaces — product upload for classic Merchant listings uses this Content API flow).

## 1. Google Cloud

1. Open [Google Cloud Console](https://console.cloud.google.com/) and create or select a project.
2. **APIs & Services → Library** → enable **Content API for Shopping**.
3. **APIs & Services → Credentials** → **Create credentials** → **Service account**.
4. Grant the service account a sensible name (e.g. `merchant-content-sync`), then **Create key** → **JSON** and download the file.
5. Store the JSON on the server outside the web root, or at the path configured in `GOOGLE_MERCHANT_CREDENTIALS_PATH` (default: `storage/private/google-merchant-credentials.json`). **Do not commit** this file.

## 2. Merchant Center

1. Open [Google Merchant Center](https://merchants.google.com/) with an account that owns the store.
2. Use the **numeric Merchant Center ID** of the account that will hold the offers (for multi-client accounts, use the **sub-account** ID Google accepts for the Content API, not an unsupported parent-only ID).
3. **Settings → Content API** (or **Google Cloud** linking): link the Cloud project from step 1.
4. **Users** (or **Account access**): add the **service account email** (from the JSON, `client_email`) with permission to **manage products** / **admin** as required by your UI.
5. Complete **business info**, **website verification**, **shipping**, **tax**, and **returns** as Merchant Center requires; missing policies cause disapprovals even when the API returns success.

## 3. Environment variables

Set these in the host environment (Apache `SetEnv`, nginx `fastcgi_param`, systemd, Plesk “Environment variables”, etc.) or in a **`.env`** file in the project root (loaded automatically if present and `vlucas/phpdotenv` is installed).

| Variable | Purpose |
|----------|---------|
| `GOOGLE_MERCHANT_ENABLED` | `true` to turn on API sync from admin / CLI. |
| `GOOGLE_MERCHANT_MERCHANT_ID` | Numeric Merchant Center account ID. |
| `GOOGLE_MERCHANT_CREDENTIALS_PATH` | Absolute or relative path to the service account JSON (optional if you use the default path). |
| `GOOGLE_MERCHANT_CONTENT_LANGUAGE` | e.g. `en` (must match product pages). |
| `GOOGLE_MERCHANT_TARGET_COUNTRY` | ISO 3166-1 alpha-2, e.g. `KH`. |
| `GOOGLE_MERCHANT_CURRENCY` | ISO 4217, e.g. `USD`. |
| `GOOGLE_MERCHANT_DEFAULT_BRAND` | Brand shown in Merchant (required by Google). |
| `GOOGLE_MERCHANT_FEED_ENABLED` | `true` to expose the tab-delimited **feed URL** (optional fallback). |
| `GOOGLE_MERCHANT_FEED_TOKEN` | Secret token; required in the feed URL query string (`?token=...`). |

See also [`.env.example`](../.env.example) in the repo root.

## 4. Verify after deploy

1. Save an active product with **price &gt; 0** and a **main image** in **Admin → Products**.
2. Check **Merchant Center → Products** (and **Diagnostics**) for the new offer.
3. Run a full push: `php scripts/google-merchant-resync.php`.
4. Optional: register the **feed URL** in Merchant Center (**Feeds → primary feed → scheduled fetch**) using the URL from [google-merchant-feed.php](../google-merchant-feed.php) plus `token` — see comments in that file.

## 5. Troubleshooting

- **401 / 403 from Google:** Service account not added in Merchant Center, wrong Merchant ID, or Content API not enabled for the linked project.
- **Product disapproved in diagnostics:** Policy issues (shipping, return policy, mismatched domain, invalid image URL). Ensure `config/app.php` `url` is the public **https** base URL.
- **Offers skipped:** Products without positive price or without image are intentionally not synced (same rules as the optional feed).
