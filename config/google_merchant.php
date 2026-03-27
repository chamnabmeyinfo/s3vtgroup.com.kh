<?php

/**
 * Google Merchant Center — Content API for Shopping (products.insert / delete).
 *
 * Setup:
 * 1. Google Cloud: create a project, enable "Content API for Shopping".
 * 2. Create a service account, download JSON key → place at credentials_path (default below).
 * 3. Merchant Center: Settings → Content API → link Cloud project; add the service account
 *    email as a user with permission to manage products.
 * 4. Set merchant_id to your Merchant Center ID (numeric).
 *
 * Optional env vars: GOOGLE_MERCHANT_ENABLED, GOOGLE_MERCHANT_MERCHANT_ID,
 * GOOGLE_MERCHANT_CREDENTIALS_PATH, GOOGLE_MERCHANT_CONTENT_LANGUAGE,
 * GOOGLE_MERCHANT_TARGET_COUNTRY, GOOGLE_MERCHANT_CURRENCY, GOOGLE_MERCHANT_DEFAULT_BRAND,
 * GOOGLE_MERCHANT_FEED_ENABLED, GOOGLE_MERCHANT_FEED_TOKEN
 */
return [
    'enabled' => filter_var(
        getenv('GOOGLE_MERCHANT_ENABLED') !== false && getenv('GOOGLE_MERCHANT_ENABLED') !== ''
            ? getenv('GOOGLE_MERCHANT_ENABLED')
            : 'false',
        FILTER_VALIDATE_BOOLEAN
    ),
    'merchant_id' => getenv('GOOGLE_MERCHANT_MERCHANT_ID') ?: '',
    'credentials_path' => getenv('GOOGLE_MERCHANT_CREDENTIALS_PATH')
        ?: (__DIR__ . '/../storage/private/google-merchant-credentials.json'),
    'content_language' => getenv('GOOGLE_MERCHANT_CONTENT_LANGUAGE') ?: 'en',
    'target_country' => getenv('GOOGLE_MERCHANT_TARGET_COUNTRY') ?: 'KH',
    'currency' => getenv('GOOGLE_MERCHANT_CURRENCY') ?: 'USD',
    'default_brand' => getenv('GOOGLE_MERCHANT_DEFAULT_BRAND') ?: 'S3V Group',
    'channel' => 'online',
    'feed_enabled' => filter_var(
        getenv('GOOGLE_MERCHANT_FEED_ENABLED') !== false && getenv('GOOGLE_MERCHANT_FEED_ENABLED') !== ''
            ? getenv('GOOGLE_MERCHANT_FEED_ENABLED')
            : 'false',
        FILTER_VALIDATE_BOOLEAN
    ),
    'feed_token' => getenv('GOOGLE_MERCHANT_FEED_TOKEN') ?: '',
];

